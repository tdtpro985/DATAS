<?php
/* ============================================================
   GET /api/v1/charts/pie
   Returns material breakdown slices for the pie chart.
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

    $region = getRegion();
    if ($region !== null) {
        $conditions[] = 'region = :region';
        $params[':region'] = $region;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $stmt = $db->prepare("
        SELECT
            COALESCE(SUM(sheet_pile_amount), 0) AS sheet_pile_total,
            COALESCE(SUM(drbs_value), 0)        AS drbs_total
        FROM projects
        $where
    ");
    $stmt->execute($params);
    $row = $stmt->fetch();

    $slices = [];
    if ($row['sheet_pile_total'] > 0) {
        $slices[] = ['label' => 'Sheet Pile', 'value' => (float) $row['sheet_pile_total']];
    }
    if ($row['drbs_total'] > 0) {
        $slices[] = ['label' => 'DRBs', 'value' => (float) $row['drbs_total']];
    }

    jsonResponse(['slices' => $slices]);

} catch (Exception $e) {
    error_log('Pie chart error: ' . $e->getMessage());
    jsonResponse(['slices' => []]);
}
