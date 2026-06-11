<?php
/* ============================================================
   GET /api/v1/users/sr-performance
   ============================================================
   Returns per-sales-rep performance metrics derived from
   the sales_tracking table.

   Query params:
     month  — 1-12  (optional)
     year   — YYYY  (optional)
     region — code  (optional)
     sr_id  — filter to a single sales rep (optional)
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireRole(['superadmin', 'admin', 'sales_rep']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();

    // ── Date filter ────────────────────────────────────────
    $dateFilter = buildDateFilter('p.publication_date');
    $dateWhere  = $dateFilter['sql'];
    $params     = $dateFilter['params'];

    // ── Region filter ──────────────────────────────────────
    $region = getRegion();
    $regionSql = '';
    if ($region !== null) {
        $regionSql = ' AND p.region = :region';
        $params[':region'] = $region;
    }

    // ── Optional single SR filter ──────────────────────────
    $srId = isset($_GET['sr_id']) ? (int)$_GET['sr_id'] : null;
    $srSql = '';
    if ($srId) {
        $srSql = ' AND u.id = :sr_id';
        $params[':sr_id'] = $srId;
    }

    // ── Main performance query ────────────────────────────
    // One row per sales rep with all funnel counts + value totals
    $stmt = $db->prepare("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.branch,

            /* Assigned projects (via sales_tracking) */
            COUNT(DISTINCT st.project_id)                                                   AS total_assigned,

            /* Funnel stages */
            SUM(CASE WHEN LOWER(st.contacted)       = 'yes'                    THEN 1 ELSE 0 END) AS contacted_count,
            SUM(CASE WHEN LOWER(st.sales_qualified) = 'yes'                    THEN 1 ELSE 0 END) AS sql_yes_count,
            SUM(CASE WHEN LOWER(st.sales_qualified) = 'no'                     THEN 1 ELSE 0 END) AS sql_no_count,
            SUM(CASE WHEN LOWER(st.quoted)          = 'yes'                    THEN 1 ELSE 0 END) AS quoted_count,
            SUM(CASE WHEN LOWER(st.to_win)          = 'yes'
                     AND COALESCE(st.wa_amount, 0) > 0                         THEN 1 ELSE 0 END) AS win_count,

            /* Tracking status breakdown */
            SUM(CASE WHEN st.tracking_status = 'Not Started'                   THEN 1 ELSE 0 END) AS not_started_count,
            SUM(CASE WHEN st.tracking_status = 'In Progress'                   THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN st.tracking_status = 'Complete'                      THEN 1 ELSE 0 END) AS complete_count,

            /* Value metrics */
            COALESCE(SUM(p.project_value), 0)                                               AS total_pipeline_value,
            COALESCE(SUM(CASE WHEN LOWER(st.to_win) = 'yes'
                              AND COALESCE(st.wa_amount, 0) > 0
                              THEN st.wa_amount END), 0)                                    AS total_win_amount,

            /* Activity dates */
            MAX(st.updated_at)                                                              AS last_activity

        FROM users u
        INNER JOIN sales_tracking st ON u.id = st.sales_rep_id
        INNER JOIN projects p        ON st.project_id = p.id
        WHERE u.role = 'sales_rep'
          AND p.archived_at IS NULL
          AND $dateWhere
          $regionSql
          $srSql
        GROUP BY u.id, u.full_name, u.email, u.branch
        ORDER BY win_count DESC, total_win_amount DESC, contacted_count DESC
    ");

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Per-rep funnel conversion rates ───────────────────
    $reps = array_map(function ($r) {
        $assigned  = (int) $r['total_assigned'];
        $contacted = (int) $r['contacted_count'];
        $sqlYes    = (int) $r['sql_yes_count'];
        $quoted    = (int) $r['quoted_count'];
        $win       = (int) $r['win_count'];

        return [
            'id'                   => (int)   $r['id'],
            'full_name'            => $r['full_name'],
            'email'                => $r['email'],
            'branch'               => $r['branch'],
            'total_assigned'       => $assigned,
            'contacted_count'      => $contacted,
            'sql_yes_count'        => $sqlYes,
            'sql_no_count'         => (int) $r['sql_no_count'],
            'quoted_count'         => $quoted,
            'win_count'            => $win,
            'not_started_count'    => (int) $r['not_started_count'],
            'in_progress_count'    => (int) $r['in_progress_count'],
            'complete_count'       => (int) $r['complete_count'],
            'total_pipeline_value' => (float) $r['total_pipeline_value'],
            'total_win_amount'     => (float) $r['total_win_amount'],
            'last_activity'        => $r['last_activity'],

            /* Conversion rates (null-safe) */
            'contact_rate'  => $assigned > 0 ? round($contacted / $assigned * 100, 1) : 0,
            'sql_rate'      => $contacted > 0 ? round($sqlYes / $contacted * 100, 1) : 0,
            'quote_rate'    => $sqlYes > 0 ? round($quoted / $sqlYes * 100, 1) : 0,
            'win_rate'      => $quoted > 0 ? round($win / $quoted * 100, 1) : 0,
        ];
    }, $rows);

    // ── Summary totals (all reps combined) ────────────────
    $summary = [
        'total_reps'            => count($reps),
        'total_assigned'        => array_sum(array_column($reps, 'total_assigned')),
        'total_contacted'       => array_sum(array_column($reps, 'contacted_count')),
        'total_sql_yes'         => array_sum(array_column($reps, 'sql_yes_count')),
        'total_quoted'          => array_sum(array_column($reps, 'quoted_count')),
        'total_wins'            => array_sum(array_column($reps, 'win_count')),
        'total_pipeline_value'  => array_sum(array_column($reps, 'total_pipeline_value')),
        'total_win_amount'      => array_sum(array_column($reps, 'total_win_amount')),
    ];

    jsonResponse([
        'reps'    => $reps,
        'summary' => $summary,
        'filters' => [
            'month'  => qp('month'),
            'year'   => qp('year'),
            'region' => $region ?? 'All Regions',
        ],
    ]);

} catch (Exception $e) {
    error_log('SR Performance API error: ' . $e->getMessage());
    jsonResponse([
        'reps'    => [],
        'summary' => [],
        'filters' => [],
    ], 500);
}
