<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireRole(['superadmin', 'admin', 'sales_rep']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();

    $params    = [];
    $dateSql   = '1=1';

    $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $dateTo   = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';

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

    // Branch filter
    $branch    = isset($_GET['branch']) ? trim($_GET['branch']) : '';
    $branchSql = '';
    if ($branch !== '') {
        $branchSql = ' AND u.branch = :branch';
        $params[':branch'] = $branch;
    }

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
            MAX(st.updated_at) AS last_activity
        FROM users u
        INNER JOIN sales_tracking st ON u.id = st.sales_rep_id
        INNER JOIN projects p        ON st.project_id = p.id
        WHERE u.role = 'sales_rep'
          AND p.archived_at IS NULL
          AND $dateSql
          $regionSql
          $srSql
          $branchSql
        GROUP BY u.id, u.full_name, u.email, u.branch
        ORDER BY win_count DESC, total_win_amount DESC, contacted_count DESC
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rows = array_filter($rows, function ($r) {
        return (int)$r['contacted_count']   > 0
            || (int)$r['sql_yes_count']     > 0
            || (int)$r['sql_no_count']      > 0
            || (int)$r['quoted_count']      > 0
            || (int)$r['win_count']         > 0
            || (int)$r['in_progress_count'] > 0
            || (int)$r['complete_count']    > 0;
    });

    $reps = array_values(array_map(function ($r) {
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
            'contact_rate'  => $assigned  > 0 ? round($contacted / $assigned  * 100, 1) : 0,
            'sql_rate'      => $contacted > 0 ? round($sqlYes    / $contacted * 100, 1) : 0,
            'quote_rate'    => $sqlYes    > 0 ? round($quoted    / $sqlYes    * 100, 1) : 0,
            'win_rate'      => $quoted    > 0 ? round($win       / $quoted    * 100, 1) : 0,
        ];
    }, $rows));

    // Unique branches for filter dropdown
    $branchesStmt = $db->query("SELECT DISTINCT branch FROM users WHERE role = 'sales_rep' AND branch IS NOT NULL ORDER BY branch");
    $branches = $branchesStmt->fetchAll(PDO::FETCH_COLUMN);

    $summary = [
        'total_reps'           => count($reps),
        'total_assigned'       => array_sum(array_column($reps, 'total_assigned')),
        'total_contacted'      => array_sum(array_column($reps, 'contacted_count')),
        'total_sql_yes'        => array_sum(array_column($reps, 'sql_yes_count')),
        'total_quoted'         => array_sum(array_column($reps, 'quoted_count')),
        'total_wins'           => array_sum(array_column($reps, 'win_count')),
        'total_pipeline_value' => array_sum(array_column($reps, 'total_pipeline_value')),
        'total_win_amount'     => array_sum(array_column($reps, 'total_win_amount')),
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
