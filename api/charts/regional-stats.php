<?php
/* ============================================================
   GET /api/v1/charts/regional-stats
   ============================================================
   Returns regional project statistics for charts.
   Query params: period, month, region
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();
    $date = buildDateFilter('publication_date');

    $params = $date['params'];
    $where = 'WHERE ' . $date['sql'];

    // Exclude archived and illegitimate projects  
    $where .= " AND (archived_at IS NULL OR archived_at = '') AND (is_actual_project IS NULL OR is_actual_project != 'no')";

    // Get regional statistics
    $stmt = $db->prepare("
        SELECT
            COALESCE(region, 'Unknown') AS region,
            COUNT(*) AS project_count,
            COALESCE(SUM(project_value), 0) AS total_value
        FROM projects
        $where
        GROUP BY region
        ORDER BY total_value DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Regional stats error: " . $e->getMessage());
    jsonResponse([
        'regions' => [],
        'projectCounts' => [],
        'values' => []
    ]);
    exit;
}

$regions = [];
$projectCounts = [];
$values = [];

foreach ($rows as $row) {
    $regions[] = $row['region'];
    $projectCounts[] = (int) $row['project_count'];
    $values[] = (float) $row['total_value'];
}

jsonResponse([
    'regions' => $regions,
    'projectCounts' => $projectCounts,
    'values' => $values
]);