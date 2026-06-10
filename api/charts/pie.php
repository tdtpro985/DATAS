<?php
/* ============================================================
   GET /api/v1/charts/pie
   ============================================================
   Returns material breakdown slices for the pie chart.
   Query params: period, month, region, group_by (ignored — always material)
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db     = getDB();
$date   = buildDateFilter('publication_date');
$region = getRegion();

$regionSql    = '';
$regionParams = [];
if ($region !== null) {
    $regionSql    = ' AND region = :region';
    $regionParams = [':region' => $region];
}

$params = array_merge($date['params'], $regionParams);
$where  = 'WHERE ' . $date['sql'] . $regionSql;

// Exclude archived and illegitimate projects
$where .= " AND (archived_at IS NULL OR archived_at = '') AND (is_actual_project IS NULL OR is_actual_project != 'no')";

// Build slices from sheet_pile and drbs values
// Each project contributes up to 2 material slices
$stmt = $db->prepare("
    SELECT
        'Sheet Pile'                        AS label,
        COALESCE(SUM(sheet_pile_amount), 0) AS value
    FROM projects
    $where AND sheet_pile_amount > 0
    UNION ALL
    SELECT
        'DRBs'                              AS label,
        COALESCE(SUM(drbs_value), 0)        AS value
    FROM projects
    $where AND drbs_value > 0
");
// params used twice (once per UNION part)
$stmt->execute(array_merge($params, $params));
$rows = $stmt->fetchAll();

// Filter out zero-value slices
$slices = array_values(array_filter(
    array_map(fn($r) => ['label' => $r['label'], 'value' => (float) $r['value']], $rows),
    fn($s) => $s['value'] > 0
));

jsonResponse(['slices' => $slices]);
