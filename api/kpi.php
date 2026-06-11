<?php
/* ============================================================
   GET /api/v1/kpi
   Returns KPI summary + status category breakdown.
   Query params: period, month, year, region
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();

    // Build filters
    $conditions = ['archived_at IS NULL'];
    $params     = [];

    // Date filter
    $month  = getMonth();
    $year   = getYear();
    if ($month !== null && $year !== null) {
        $conditions[] = 'MONTH(publication_date) = :month AND YEAR(publication_date) = :year';
        $params[':month'] = $month;
        $params[':year']  = $year;
    } elseif ($year !== null) {
        $conditions[] = 'YEAR(publication_date) = :year';
        $params[':year'] = $year;
    }

    // Region filter
    $region = getRegion();
    if ($region !== null) {
        $conditions[] = 'region = :region';
        $params[':region'] = $region;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    // Totals
    $stmt = $db->prepare("
        SELECT
            COUNT(*)                        AS projects_encoded,
            COUNT(DISTINCT contractor_name) AS contractors_identified,
            COALESCE(SUM(project_value), 0) AS total_pipeline_value
        FROM projects
        $where
    ");
    $stmt->execute($params);
    $totals = $stmt->fetch();

    // Status breakdown
    $stmt2 = $db->prepare("
        SELECT status, COUNT(*) AS cnt, COALESCE(SUM(project_value), 0) AS val
        FROM projects
        $where
        GROUP BY status
        ORDER BY val DESC
    ");
    $stmt2->execute($params);
    $categories = $stmt2->fetchAll();

    $categoryMap = [];
    foreach ($categories as $cat) {
        $key = strtolower(str_replace([' ', '-', '/'], '_', $cat['status']));
        $categoryMap[$key] = [
            'count' => (int)   $cat['cnt'],
            'value' => (float) $cat['val'],
        ];
    }

    jsonResponse([
        'data' => array_merge([
            'projects_encoded'       => (int)   $totals['projects_encoded'],
            'contractors_identified' => (int)   $totals['contractors_identified'],
            'total_pipeline_value'   => (float) $totals['total_pipeline_value'],
            'pipeline_value'         => (float) $totals['total_pipeline_value'],
        ], $categoryMap),
    ]);

} catch (Exception $e) {
    error_log('KPI API error: ' . $e->getMessage());
    jsonResponse([
        'data' => [
            'projects_encoded'       => 0,
            'contractors_identified' => 0,
            'total_pipeline_value'   => 0,
            'pipeline_value'         => 0,
        ],
    ]);
}
