<?php
/* ============================================================
   GET /api/v1/charts/funnel
   ============================================================
   Returns sales funnel stages based on sales tracking data.
   Stages:
   1. Prospects - Raw projects (not yet tracked)
   2. Contacted - Projects with contacted = 'Yes'
   3. Sales Qualified Leads - Projects with sql = 'Yes'
   4. Not Sales Qualified Leads - Projects with sql = 'No'
   5. Quoted - Projects with quoted = 'Yes'
   6. Win - Projects with to_win = 'Yes' and wa_amount > 0
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db     = getDB();
$date   = buildDateFilter('p.publication_date');
$region = getRegion();

$regionSql    = '';
$regionParams = [];
if ($region !== null) {
    $regionSql    = ' AND p.region = :region';
    $regionParams = [':region' => $region];
}

$params = array_merge($date['params'], $regionParams);
$where  = 'WHERE ' . $date['sql'] . $regionSql;

// Get total projects (Prospects - raw projects, di pa nagagalaw)
$stmt = $db->prepare("
    SELECT COUNT(*) as count
    FROM projects p
    $where
");
$stmt->execute($params);
$totalProjects = $stmt->fetch()['count'];

// Get projects with sales tracking data
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_tracked,
        SUM(CASE WHEN st.contacted = 'Yes' THEN 1 ELSE 0 END) as contacted,
        SUM(CASE WHEN st.sales_qualified = 'Yes' THEN 1 ELSE 0 END) as sql_yes,
        SUM(CASE WHEN st.sales_qualified = 'No' THEN 1 ELSE 0 END) as sql_no,
        SUM(CASE WHEN st.quoted = 'Yes' THEN 1 ELSE 0 END) as quoted,
        SUM(CASE WHEN st.to_win = 'Yes' AND st.wa_amount > 0 THEN 1 ELSE 0 END) as win
    FROM projects p
    LEFT JOIN sales_tracking st ON p.id = st.project_id
    $where
    AND st.id IS NOT NULL
");
$stmt->execute($params);
$trackingData = $stmt->fetch();

// Build funnel stages based on your requirements
$stages = [
    [
        'name' => 'Prospects',
        'color' => '#64748B',
        'count' => (int) $totalProjects,
        'description' => 'Raw projects (di pa nagagalaw)'
    ],
    [
        'name' => 'Contacted',
        'color' => '#3B82F6',
        'count' => (int) $trackingData['contacted'],
        'description' => 'Projects na naka Yes ung contacted'
    ],
    [
        'name' => 'Sales Qualified Leads',
        'color' => '#10B981',
        'count' => (int) $trackingData['sql_yes'],
        'description' => 'Naka yes na ung Sales Qualified Leads'
    ],
    [
        'name' => 'Not Sales Qualified Leads',
        'color' => '#EF4444',
        'count' => (int) $trackingData['sql_no'],
        'description' => 'Naka No sa Sales Qualified Leads'
    ],
    [
        'name' => 'Quoted',
        'color' => '#F59E0B',
        'count' => (int) $trackingData['quoted'],
        'description' => 'Mga naka Yes na Quoted'
    ],
    [
        'name' => 'Win',
        'color' => '#8B5CF6',
        'count' => (int) $trackingData['win'],
        'description' => 'Naka yes na yung Win at may W/L Amount na'
    ]
];

// Calculate conversion rates (skip "Not Sales Qualified Leads" for main funnel flow)
$prevCount = null;
foreach ($stages as &$stage) {
    $conversion = null;
    if ($prevCount !== null && $prevCount > 0) {
        $conversion = round(($stage['count'] / $prevCount) * 100, 1);
    }
    $stage['conversion'] = $conversion;
    
    // Set previous count for next iteration
    // Skip "Not Sales Qualified Leads" as it's a branch, not part of main flow
    if ($stage['name'] !== 'Not Sales Qualified Leads' && $stage['count'] > 0) {
        $prevCount = $stage['count'];
    }
}

jsonResponse(['stages' => $stages]);
