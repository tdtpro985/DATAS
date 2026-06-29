<?php
/* ============================================================
   GET /api/v1/contractors/ranking
   Returns contractors sorted by total project value DESC.
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
        $conditions[] = "(is_actual_project IS NULL OR is_actual_project != 'no')";
    }

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

    $contractors = array_map(fn($r) => [
        'project_id'      => (int)   $r['project_id'],
        'contractor_name' => $r['contractor_name'],
        'project_name'    => $r['project_name'],
        'status'          => $r['status'],
        'total_value'     => (float) $r['total_value'],
    ], $rows);

    jsonResponse(['contractors' => $contractors]);

} catch (Exception $e) {
    error_log('Contractors ranking error: ' . $e->getMessage());
    jsonResponse(['contractors' => []]);
}
