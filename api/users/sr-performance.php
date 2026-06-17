<?php
// Ensure all PHP date/time functions use Philippine Time (UTC+8)
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireRole(['superadmin', 'admin', 'sales_rep']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();

    // ── Check if timestamp columns exist ──────────────────
    $hasTs = (bool)$db->query("SHOW COLUMNS FROM sales_tracking LIKE 'contacted_at'")->fetch();

    // ── Filters ───────────────────────────────────────────
    $params    = [];
    $dateSql   = '1=1';
    $dateFrom  = trim($_GET['date_from'] ?? '');
    $dateTo    = trim($_GET['date_to']   ?? '');
    if ($dateFrom && $dateTo) {
        $dateSql = 'p.publication_date BETWEEN :date_from AND :date_to';
        $params[':date_from'] = $dateFrom;
        $params[':date_to']   = $dateTo;
    } elseif ($dateFrom) {
        $dateSql = 'p.publication_date >= :date_from';
        $params[':date_from'] = $dateFrom;
    } elseif ($dateTo) {
        $dateSql = 'p.publication_date <= :date_to';
        $params[':date_to'] = $dateTo;
    }

    $region    = getRegion();
    $regionSql = '';
    if ($region !== null) {
        $regionSql = ' AND p.region = :region';
        $params[':region'] = $region;
    }

    $srId  = isset($_GET['sr_id']) ? (int)$_GET['sr_id'] : null;
    $srSql = '';
    if ($srId) {
        $srSql = ' AND u.id = :sr_id';
        $params[':sr_id'] = $srId;
    }

    $branch    = trim($_GET['branch'] ?? '');
    $branchSql = '';
    if ($branch !== '') {
        $branchSql = ' AND u.branch = :branch';
        $params[':branch'] = $branch;
    }

    // ── Timing columns (only when migration has been run) ─
    $timingSelect = $hasTs ? "
        /* Average days per stage (only completed transitions) */
        AVG(CASE WHEN st.contacted_at IS NOT NULL AND st.assigned_at IS NOT NULL
                 THEN TIMESTAMPDIFF(HOUR, st.assigned_at, st.contacted_at) / 24.0
            END) AS avg_days_to_contact,

        AVG(CASE WHEN st.quoted_at IS NOT NULL AND st.contacted_at IS NOT NULL
                 THEN TIMESTAMPDIFF(HOUR, st.contacted_at, st.quoted_at) / 24.0
            END) AS avg_days_contact_to_quote,

        AVG(CASE WHEN st.sales_qualified_at IS NOT NULL AND st.quoted_at IS NOT NULL
                 THEN TIMESTAMPDIFF(HOUR, st.quoted_at, st.sales_qualified_at) / 24.0
            END) AS avg_days_quote_to_sql,

        AVG(CASE WHEN st.to_win_at IS NOT NULL AND st.quoted_at IS NOT NULL
                 THEN TIMESTAMPDIFF(HOUR, st.quoted_at, st.to_win_at) / 24.0
            END) AS avg_days_quote_to_win,

        /* SQL → Win: time from sales qualified to win */
        AVG(CASE WHEN st.to_win_at IS NOT NULL AND st.sales_qualified_at IS NOT NULL
                 THEN TIMESTAMPDIFF(HOUR, st.sales_qualified_at, st.to_win_at) / 24.0
            END) AS avg_days_sql_to_win,

        /* Full cycle: assigned → win */
        AVG(CASE WHEN st.to_win_at IS NOT NULL AND st.assigned_at IS NOT NULL
                 THEN TIMESTAMPDIFF(HOUR, st.assigned_at, st.to_win_at) / 24.0
            END) AS avg_days_full_cycle,

        COUNT(CASE WHEN st.to_win_at IS NOT NULL AND st.assigned_at IS NOT NULL THEN 1 END) AS completed_cycles
    " : "
        NULL AS avg_days_to_contact,
        NULL AS avg_days_contact_to_quote,
        NULL AS avg_days_quote_to_sql,
        NULL AS avg_days_quote_to_win,
        NULL AS avg_days_sql_to_win,
        NULL AS avg_days_full_cycle,
        0    AS completed_cycles
    ";

    $sql = "
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.branch,
            COUNT(DISTINCT st.project_id) AS total_assigned,
            SUM(CASE WHEN LOWER(st.contacted)       = 'yes' THEN 1 ELSE 0 END) AS contacted_count,
            SUM(CASE WHEN LOWER(st.sales_qualified) = 'yes' THEN 1 ELSE 0 END) AS sql_yes_count,
            SUM(CASE WHEN LOWER(st.sales_qualified) = 'no'  THEN 1 ELSE 0 END) AS sql_no_count,
            SUM(CASE WHEN LOWER(st.quoted)          = 'yes' THEN 1 ELSE 0 END) AS quoted_count,
            SUM(CASE WHEN LOWER(st.to_win) = 'yes' AND COALESCE(st.wa_amount,0) > 0 THEN 1 ELSE 0 END) AS win_count,
            SUM(CASE WHEN st.tracking_status = 'Not Started'  THEN 1 ELSE 0 END) AS not_started_count,
            SUM(CASE WHEN st.tracking_status = 'In Progress'  THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN st.tracking_status = 'Complete'     THEN 1 ELSE 0 END) AS complete_count,
            COALESCE(SUM(p.project_value), 0) AS total_pipeline_value,
            COALESCE(SUM(CASE WHEN LOWER(st.to_win) = 'yes' AND COALESCE(st.wa_amount,0) > 0 THEN st.wa_amount END), 0) AS total_win_amount,
            MAX(st.updated_at) AS last_activity,
            AVG(CASE WHEN st.assigned_at IS NOT NULL
                     THEN TIMESTAMPDIFF(HOUR, st.assigned_at, st.updated_at) / 24.0
                END) AS avg_days_processing,
            $timingSelect
        FROM users u
        INNER JOIN sales_tracking st ON u.id = st.sales_rep_id
        INNER JOIN projects p        ON st.project_id = p.id
        WHERE u.role IN ('sales_rep', 'admin')
          AND p.archived_at IS NULL
          AND $dateSql
          $regionSql
          $srSql
          $branchSql
        GROUP BY u.id, u.full_name, u.email, u.branch
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter out reps with zero activity
    $rows = array_filter($rows, function ($r) {
        return (int)$r['contacted_count']   > 0
            || (int)$r['sql_yes_count']     > 0
            || (int)$r['sql_no_count']      > 0
            || (int)$r['quoted_count']      > 0
            || (int)$r['win_count']         > 0
            || (int)$r['in_progress_count'] > 0
            || (int)$r['complete_count']    > 0;
    });

    // Helper: append +08:00 offset to a MySQL datetime string so browsers
    // parse it as Philippine Time (UTC+8) instead of local/UTC.
    $phTs = fn(?string $dt): ?string =>
        ($dt !== null && $dt !== '') ? str_replace(' ', 'T', $dt) . '+08:00' : null;

    $reps = array_values(array_map(function ($r) use ($phTs) {
        $assigned  = (int) $r['total_assigned'];
        $contacted = (int) $r['contacted_count'];
        $sqlYes    = (int) $r['sql_yes_count'];
        $quoted    = (int) $r['quoted_count'];
        $win       = (int) $r['win_count'];

        $avgFull = $r['avg_days_full_cycle'] !== null ? round((float)$r['avg_days_full_cycle'], 1) : null;

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
            'last_activity'        => $phTs($r['last_activity']),

            // Timing (null if migration not yet run)
            'avg_days_to_contact'      => $r['avg_days_to_contact']      !== null ? round((float)$r['avg_days_to_contact'],       1) : null,
            'avg_days_contact_to_quote'=> $r['avg_days_contact_to_quote']!== null ? round((float)$r['avg_days_contact_to_quote'],  1) : null,
            'avg_days_quote_to_sql'    => $r['avg_days_quote_to_sql']    !== null ? round((float)$r['avg_days_quote_to_sql'],     1) : null,
            'avg_days_quote_to_win'    => $r['avg_days_quote_to_win']    !== null ? round((float)$r['avg_days_quote_to_win'],     1) : null,
            'avg_days_sql_to_win'      => $r['avg_days_sql_to_win']      !== null ? round((float)$r['avg_days_sql_to_win'],       1) : null,
            'avg_days_full_cycle'      => $avgFull,
            'avg_days_processing'    => $r['avg_days_processing'] !== null ? round((float)$r['avg_days_processing'], 1) : null,
            'completed_cycles'       => (int) $r['completed_cycles'],

            // Conversion rates
            'contact_rate'  => $assigned  > 0 ? round($contacted / $assigned  * 100, 1) : 0,
            'sql_rate'      => $contacted > 0 ? round($sqlYes    / $contacted * 100, 1) : 0,
            'quote_rate'    => $sqlYes    > 0 ? round($quoted    / $sqlYes    * 100, 1) : 0,
            'win_rate'      => $quoted    > 0 ? round($win       / $quoted    * 100, 1) : 0,

            // Speed score: lower avg_days_full_cycle = faster = better
            // Null (no completed cycles) ranks last
            'speed_score'   => $avgFull !== null ? $avgFull : PHP_FLOAT_MAX,
        ];
    }, $rows));

    // ── Sort: fastest full cycle first (nulls last), then most wins ──
    usort($reps, function ($a, $b) {
        $aVal = $a['avg_days_full_cycle'] ?? $a['avg_days_processing'];
        $bVal = $b['avg_days_full_cycle'] ?? $b['avg_days_processing'];
        if ($aVal === null && $bVal === null) return $b['win_count'] - $a['win_count'];
        if ($aVal === null) return 1;
        if ($bVal === null) return -1;
        $diff = $aVal - $bVal;
        if (abs($diff) < 0.01) return $b['win_count'] - $a['win_count'];
        return $diff < 0 ? -1 : 1;
    });

    // Remove internal speed_score before sending
    foreach ($reps as &$rep) { unset($rep['speed_score']); }

    // Unique branches for filter dropdown
    $branches = $db->query(
        "SELECT DISTINCT branch FROM users WHERE role IN ('sales_rep', 'admin') AND branch IS NOT NULL ORDER BY branch"
    )->fetchAll(PDO::FETCH_COLUMN);

    $summary = [
        'total_reps'           => count($reps),
        'total_assigned'       => array_sum(array_column($reps, 'total_assigned')),
        'total_contacted'      => array_sum(array_column($reps, 'contacted_count')),
        'total_sql_yes'        => array_sum(array_column($reps, 'sql_yes_count')),
        'total_quoted'         => array_sum(array_column($reps, 'quoted_count')),
        'total_wins'           => array_sum(array_column($reps, 'win_count')),
        'total_pipeline_value' => array_sum(array_column($reps, 'total_pipeline_value')),
        'total_win_amount'     => array_sum(array_column($reps, 'total_win_amount')),
        'has_timing_data'      => $hasTs,
    ];

    jsonResponse([
        'reps'     => $reps,
        'summary'  => $summary,
        'branches' => $branches,
    ]);

} catch (Exception $e) {
    error_log('SR Performance API error: ' . $e->getMessage());
    jsonResponse(['reps' => [], 'summary' => [], 'branches' => []], 500);
}
