<?php
/* ============================================================
   GET /api/v1/kpi
   ============================================================
   Returns KPI summary + category breakdown for the dashboard.
   Query params: period, month, region
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db     = getDB();
    $date   = buildDateFilter('publication_date');
    $region = getRegion();

    // Build region clause
    $regionSql    = '';
    $regionParams = [];
    if ($region !== null) {
        $regionSql    = ' AND region = :region';
        $regionParams = [':region' => $region];
    }

    $params = array_merge($date['params'], $regionParams);
    $where  = 'WHERE ' . $date['sql'] . $regionSql;

    // Exclude archived projects only
    $where .= " AND (archived_at IS NULL OR archived_at = '')";

    // ── Total projects, contractors, pipeline value ────────────
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

    // ── Category breakdown by status ──────────────────────────
    $stmt2 = $db->prepare("
        SELECT
            status,
            COUNT(*)                        AS cnt,
            COALESCE(SUM(project_value), 0) AS val
        FROM projects
        $where
        GROUP BY status
        ORDER BY val DESC
    ");
    $stmt2->execute($params);
    $categories = $stmt2->fetchAll();
} catch (Exception $e) {
    error_log("KPI API error: " . $e->getMessage());
    jsonResponse([
        'data' => [
            'projects_encoded' => 0,
            'contractors_identified' => 0,
            'total_pipeline_value' => 0,
            'pipeline_value' => 0
        ]
    ]);
    exit;
}

// Build category map keyed by snake_case status name
$categoryMap = [];
foreach ($categories as $cat) {
    $key = strtolower(str_replace([' ', '-', '/'], '_', $cat['status']));
    $categoryMap[$key] = [
        'count' => (int) $cat['cnt'],
        'value' => (float) $cat['val'],
    ];
}

jsonResponse([
    'data' => array_merge(
        [
            'projects_encoded'      => (int)   $totals['projects_encoded'],
            'contractors_identified'=> (int)   $totals['contractors_identified'],
            'total_pipeline_value'  => (float) $totals['total_pipeline_value'],
            'pipeline_value'        => (float) $totals['total_pipeline_value'],
        ],
        $categoryMap
    ),
]);
