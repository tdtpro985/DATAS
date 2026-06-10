<?php
/* ============================================================
   GET /api/v1/contractors/ranking
   ============================================================
   Returns contractors sorted by total project value DESC.
   Query params: period, month, region, page, size
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
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

    $stmt = $db->prepare("
        SELECT
            MIN(id)                         AS project_id,
            contractor_name,
            MIN(project_name)               AS project_name,
            MIN(status)                     AS status,
            COALESCE(SUM(project_value), 0) AS total_value
        FROM projects
        $where
        GROUP BY contractor_name
        ORDER BY total_value DESC
        LIMIT 500
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Contractors ranking error: " . $e->getMessage());
    jsonResponse(['contractors' => []]);
    exit;
}

// Cast types
$contractors = array_map(function ($r) {
    return [
        'project_id'      => (int)   $r['project_id'],
        'contractor_name' => $r['contractor_name'],
        'project_name'    => $r['project_name'],
        'status'          => $r['status'],
        'total_value'     => (float) $r['total_value'],
    ];
}, $rows);

jsonResponse(['contractors' => $contractors]);
