<?php
/* ============================================================
   GET /api/v1/map/regions
   ============================================================
   Returns project counts and pipeline values per region
   for the Philippines choropleth map.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db   = getDB();
$date = buildDateFilter('publication_date');

$stmt = $db->prepare("
    SELECT
        region                          AS name,
        COUNT(*)                        AS value,
        COALESCE(SUM(project_value), 0) AS total_value
    FROM projects
    WHERE " . $date['sql'] . " AND region IS NOT NULL AND region != ''
    GROUP BY region
    ORDER BY total_value DESC
");
$stmt->execute($date['params']);
$rows = $stmt->fetchAll();

$regions = array_map(fn($r) => [
    'name'        => $r['name'],
    'value'       => (int)   $r['value'],
    'total_value' => (float) $r['total_value'],
], $rows);

jsonResponse($regions);
