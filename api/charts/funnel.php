<?php
/* ============================================================
   GET /api/v1/charts/funnel
   Returns sales funnel stages.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$fallback = [
    'stages' => [
        ['name' => 'Assigned',              'color' => '#3B82F6', 'count' => 0, 'description' => 'Projects assigned to an SR',    'conversion' => null],
        ['name' => 'Contacted',             'color' => '#8B5CF6', 'count' => 0, 'description' => 'Contacted',                     'conversion' => null],
        ['name' => 'Sales Qualified Leads', 'color' => '#10B981', 'count' => 0, 'description' => 'SQL Yes',                       'conversion' => null],
        ['name' => 'Quoted',                'color' => '#F59E0B', 'count' => 0, 'description' => 'Quoted Yes',                    'conversion' => null],
        ['name' => 'Win',                   'color' => '#F97316', 'count' => 0, 'description' => 'Win',                           'conversion' => null],
    ],
];

try {
    $db = getDB();

    $conditions = ['p.archived_at IS NULL'];
    $params     = [];

    // Check if is_actual_project column exists (exclude illegitimate projects)
    static $hasIllegitimateCol = null;
    if ($hasIllegitimateCol === null) {
        try {
            $colChk = $db->query("SHOW COLUMNS FROM projects LIKE 'is_actual_project'");
            $hasIllegitimateCol = $colChk->rowCount() > 0;
        } catch (Exception $e) {
            $hasIllegitimateCol = false;
        }
    }
    if ($hasIllegitimateCol) {
        $conditions[] = "(p.is_actual_project IS NULL OR p.is_actual_project != 'no')";
    }

    $month  = getMonth();
    $year   = getYear();
    if ($month !== null && $year !== null) {
        $conditions[] = 'MONTH(p.publication_date) = :month AND YEAR(p.publication_date) = :year';
        $params[':month'] = $month;
        $params[':year']  = $year;
    } elseif ($year !== null) {
        $conditions[] = 'YEAR(p.publication_date) = :year';
        $params[':year'] = $year;
    }

    $region = getRegion();
    if ($region !== null) {
        $conditions[] = 'p.region = :region';
        $params[':region'] = $region;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    // Total prospects
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM projects p $where");
    $stmt->execute($params);
    $totalProjects = (int) $stmt->fetch()['cnt'];

    // Sales tracking breakdown
    $stmt = $db->prepare("
        SELECT
            COUNT(*)                                                       AS total_tracked,
            SUM(CASE WHEN LOWER(st.contacted)       = 'yes' THEN 1 ELSE 0 END) AS contacted,
            SUM(CASE WHEN LOWER(st.sales_qualified) = 'yes' THEN 1 ELSE 0 END) AS sql_yes,
            SUM(CASE WHEN LOWER(st.sales_qualified) = 'no'  THEN 1 ELSE 0 END) AS sql_no,
            SUM(CASE WHEN LOWER(st.quoted)          = 'yes' THEN 1 ELSE 0 END) AS quoted,
            SUM(CASE WHEN LOWER(st.to_win)          = 'yes' AND COALESCE(st.wa_amount, 0) > 0 THEN 1 ELSE 0 END) AS win
        FROM projects p
        INNER JOIN sales_tracking st ON p.id = st.project_id
        $where
    ");
    $stmt->execute($params);
    $td = $stmt->fetch();

    // Flow: Assigned → Contacted → Sales Qualified Leads → Quoted → Win
    $stages = [
        ['name' => 'Assigned',              'color' => '#3B82F6', 'count' => (int) $td['total_tracked'], 'description' => 'Projects na naka-assign sa SR'],
        ['name' => 'Contacted',             'color' => '#8B5CF6', 'count' => (int) $td['contacted'],     'description' => 'Projects na naka Yes ang contacted'],
        ['name' => 'Sales Qualified Leads', 'color' => '#10B981', 'count' => (int) $td['sql_yes'],       'description' => 'Naka yes ang Sales Qualified Leads'],
        ['name' => 'Quoted',                'color' => '#F59E0B', 'count' => (int) $td['quoted'],        'description' => 'Mga naka Yes ang Quoted'],
        ['name' => 'Win',                   'color' => '#F97316', 'count' => (int) $td['win'],           'description' => 'Naka yes ang Win at may W/A Amount na'],
    ];

    $prevCount = $totalProjects > 0 ? $totalProjects : null;
    foreach ($stages as &$stage) {
        $stage['conversion'] = ($prevCount !== null && $prevCount > 0)
            ? round(($stage['count'] / $prevCount) * 100, 1)
            : null;
        if ($stage['count'] > 0) {
            $prevCount = $stage['count'];
        }
    }

    jsonResponse(['stages' => $stages]);

} catch (Exception $e) {
    error_log('Funnel API error: ' . $e->getMessage());
    jsonResponse($fallback);
}
