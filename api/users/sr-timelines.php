<?php
/* ============================================================
   GET /api/v1/users/sr-timelines
   Per-project sales tracking timeline data for all active SRs.
   ============================================================ */
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireRole(['superadmin', 'admin', 'sales_rep']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { jsonError('Method not allowed', 405); }

try {
    $db = getDB();

    $params  = [];
    $dateWhere = '1=1';
    $from = trim($_GET['date_from'] ?? '');
    $to   = trim($_GET['date_to']   ?? '');
    if ($from && $to) {
        $dateWhere = 'p.publication_date BETWEEN :dfrom AND :dto';
        $params[':dfrom'] = $from; $params[':dto'] = $to;
    } elseif ($from) {
        $dateWhere = 'p.publication_date >= :dfrom';
        $params[':dfrom'] = $from;
    } elseif ($to) {
        $dateWhere = 'p.publication_date <= :dto';
        $params[':dto'] = $to;
    }

    // PHP helper: format DB datetime (PH time) as ISO+08:00
    $iso = fn(?string $dt): ?string =>
        ($dt !== null && $dt !== '') ? str_replace(' ', 'T', $dt) . '+08:00' : null;

    // ── Per-project timeline rows ─────────────────────────────
    $sql = "
        SELECT
            u.id AS sr_id, u.full_name, u.email, u.branch,
            p.id AS proj_id, p.project_name, p.contractor_name,
            p.project_value, p.status AS proj_status, p.source,
            COALESCE(NULLIF(p.project_region,''), p.region, '') AS region_name,
            st.tracking_status, st.wa_amount, st.probability_percentage,
            COALESCE(st.assigned_at, st.created_at)            AS assigned_at,
            st.contacted_at,
            st.quoted_at,
            st.sales_qualified_at,
            st.to_win_at,
            CASE WHEN st.tracking_status = 'Complete' THEN st.updated_at ELSE NULL END AS completed_at,
            st.next_followup_date,
            -- Duration in seconds between each stage (flow: Assigned→Contacted→SQL→Quoted→Win)
            TIMESTAMPDIFF(SECOND, COALESCE(st.assigned_at, st.created_at), st.contacted_at)        AS secs_to_contact,
            TIMESTAMPDIFF(SECOND, st.contacted_at, st.sales_qualified_at)                          AS secs_to_qualify,
            TIMESTAMPDIFF(SECOND, st.sales_qualified_at, st.quoted_at)                             AS secs_to_quote,
            TIMESTAMPDIFF(SECOND, st.quoted_at, st.to_win_at)                                      AS secs_to_win,
            CASE WHEN st.tracking_status = 'Complete'
                THEN TIMESTAMPDIFF(SECOND, COALESCE(st.assigned_at, st.created_at), st.updated_at)
                ELSE TIMESTAMPDIFF(SECOND, COALESCE(st.assigned_at, st.created_at), NOW())
            END AS secs_total
        FROM users u
        INNER JOIN sales_tracking st ON st.sales_rep_id = u.id
        INNER JOIN projects p        ON p.id = st.project_id AND p.archived_at IS NULL
        WHERE u.role IN ('sales_rep', 'admin')
          AND $dateWhere
        ORDER BY u.full_name ASC, COALESCE(st.assigned_at, st.created_at) DESC
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Group by SR ───────────────────────────────────────────
    $repMap = [];
    foreach ($rows as $r) {
        $sid = (int)$r['sr_id'];
        if (!isset($repMap[$sid])) {
            $repMap[$sid] = [
                'id'        => $sid,
                'full_name' => $r['full_name'],
                'email'     => $r['email'],
                'branch'    => $r['branch'],
                'projects'  => [],
            ];
        }
        $repMap[$sid]['projects'][] = [
            'proj_id'             => (int)$r['proj_id'],
            'project_name'        => $r['project_name'],
            'contractor_name'     => $r['contractor_name'],
            'project_value'       => (float)$r['project_value'],
            'proj_status'         => $r['proj_status'],
            'source'              => $r['source'],
            'region_name'         => $r['region_name'],
            'tracking_status'     => $r['tracking_status'],
            'wa_amount'           => (float)$r['wa_amount'],
            'probability_percentage' => (float)$r['probability_percentage'],
            'next_followup_date'  => $iso($r['next_followup_date']),
            // Timestamps (already in PH time, append offset)
            'assigned_at'         => $iso($r['assigned_at']),
            'contacted_at'        => $iso($r['contacted_at']),
            'quoted_at'           => $iso($r['quoted_at']),
            'sales_qualified_at'  => $iso($r['sales_qualified_at']),
            'to_win_at'           => $iso($r['to_win_at']),
            'completed_at'        => $iso($r['completed_at']),
            // Durations
            'secs_to_contact'     => $r['secs_to_contact']  !== null ? (int)$r['secs_to_contact']  : null,
            'secs_to_quote'       => $r['secs_to_quote']    !== null ? (int)$r['secs_to_quote']    : null,
            'secs_to_qualify'     => $r['secs_to_qualify']  !== null ? (int)$r['secs_to_qualify']  : null,
            'secs_to_win'         => $r['secs_to_win']      !== null ? (int)$r['secs_to_win']      : null,
            'secs_total'          => $r['secs_total']        !== null ? (int)$r['secs_total']       : null,
        ];
    }

    // ── Build per-rep summary + aggregate ────────────────────
    $reps = [];
    $agg = ['total'=>0,'contacted'=>0,'quoted'=>0,'qualified'=>0,'to_win'=>0,'completed'=>0,
            'total_wa'=>0,'secs_contact'=>[],'secs_quote'=>[],'secs_qualify'=>[],'secs_win'=>[],'secs_total'=>[]];

    foreach ($repMap as $rep) {
        $p = $rep['projects'];
        $cnt = count($p);
        $contacted = count(array_filter($p, fn($x) => !empty($x['contacted_at'])));
        $quoted    = count(array_filter($p, fn($x) => !empty($x['quoted_at'])));
        $qualified = count(array_filter($p, fn($x) => !empty($x['sales_qualified_at'])));
        $toWin     = count(array_filter($p, fn($x) => !empty($x['to_win_at'])));
        $completed = count(array_filter($p, fn($x) => $x['tracking_status'] === 'Complete'));
        $totalWa   = array_sum(array_column($p, 'wa_amount'));

        // Collect non-null durations
        $scArr = array_values(array_filter(array_column($p,'secs_to_contact'), fn($v) => $v !== null && $v >= 0));
        $sqArr = array_values(array_filter(array_column($p,'secs_to_quote'),   fn($v) => $v !== null && $v >= 0));
        $sxArr = array_values(array_filter(array_column($p,'secs_to_qualify'), fn($v) => $v !== null && $v >= 0));
        $swArr = array_values(array_filter(array_column($p,'secs_to_win'),     fn($v) => $v !== null && $v >= 0));
        $stArr = array_values(array_filter(array_column($p,'secs_total'),      fn($v) => $v !== null && $v >= 0));

        $avg = fn($arr) => count($arr) > 0 ? (int)round(array_sum($arr) / count($arr)) : null;

        $reps[] = $rep + [
            'total'     => $cnt,
            'contacted' => $contacted,
            'quoted'    => $quoted,
            'qualified' => $qualified,
            'to_win'    => $toWin,
            'completed' => $completed,
            'total_wa'  => $totalWa,
            'win_rate'  => $cnt > 0 ? round(100 * $toWin / $cnt, 1) : 0,
            'avg_secs_to_contact' => $avg($scArr),
            'avg_secs_to_quote'   => $avg($sqArr),
            'avg_secs_to_qualify' => $avg($sxArr),
            'avg_secs_to_win'     => $avg($swArr),
            'avg_secs_total'      => $avg($stArr),
        ];

        $agg['total']     += $cnt;
        $agg['contacted'] += $contacted;
        $agg['quoted']    += $quoted;
        $agg['qualified'] += $qualified;
        $agg['to_win']    += $toWin;
        $agg['completed'] += $completed;
        $agg['total_wa']  += $totalWa;
        array_push($agg['secs_contact'],  ...$scArr);
        array_push($agg['secs_quote'],    ...$sqArr);
        array_push($agg['secs_qualify'],  ...$sxArr);
        array_push($agg['secs_win'],      ...$swArr);
        array_push($agg['secs_total'],    ...$stArr);
    }

    $avg = fn($arr) => count($arr) > 0 ? (int)round(array_sum($arr) / count($arr)) : null;

    // Sort reps by total_wa desc
    usort($reps, fn($a,$b) => $b['total_wa'] <=> $a['total_wa']);

    jsonResponse([
        'summary' => [
            'total'     => $agg['total'],
            'contacted' => $agg['contacted'],
            'quoted'    => $agg['quoted'],
            'qualified' => $agg['qualified'],
            'to_win'    => $agg['to_win'],
            'completed' => $agg['completed'],
            'total_wa'  => $agg['total_wa'],
            'avg_secs_to_contact' => $avg($agg['secs_contact']),
            'avg_secs_to_quote'   => $avg($agg['secs_quote']),
            'avg_secs_to_qualify' => $avg($agg['secs_qualify']),
            'avg_secs_to_win'     => $avg($agg['secs_win']),
            'avg_secs_total'      => $avg($agg['secs_total']),
        ],
        'reps' => $reps,
    ]);

} catch (Exception $e) {
    error_log('[SR TIMELINES] ' . $e->getMessage());
    jsonError('Server error', 500);
}
