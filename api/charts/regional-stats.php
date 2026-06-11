<?php
/* ============================================================
   GET /api/v1/charts/regional-stats
   Returns regional project statistics.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();

    $conditions = ['archived_at IS NULL'];
    $params     = [];

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

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $stmt = $db->prepare("
        SELECT
            COALESCE(NULLIF(TRIM(project_region), ''), COALESCE(NULLIF(TRIM(region), ''), 'Unknown')) AS region_name,
            COUNT(*)                        AS project_count,
            COALESCE(SUM(project_value), 0) AS total_value
        FROM projects
        $where
        GROUP BY region_name
        ORDER BY total_value DESC
        LIMIT 20
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $regions       = [];
    $projectCounts = [];
    $values        = [];

    foreach ($rows as $row) {
        $regions[]       = $row['region_name'];
        $projectCounts[] = (int)   $row['project_count'];
        $values[]        = (float) $row['total_value'];
    }

    jsonResponse([
        'regions'       => $regions,
        'projectCounts' => $projectCounts,
        'values'        => $values,
    ]);

} catch (Exception $e) {
    error_log('Regional stats error: ' . $e->getMessage());
    jsonResponse(['regions' => [], 'projectCounts' => [], 'values' => []]);
}
