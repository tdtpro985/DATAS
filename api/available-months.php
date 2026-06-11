<?php
/* ============================================================
   GET /api/v1/available-months
   Returns months that have projects (based on publication_date).
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT
            YEAR(publication_date)      AS year,
            MONTH(publication_date)     AS month,
            MONTHNAME(publication_date) AS month_name,
            COUNT(*)                    AS project_count
        FROM projects
        WHERE publication_date IS NOT NULL
          AND publication_date > '1970-01-01'
          AND archived_at IS NULL
        GROUP BY YEAR(publication_date), MONTH(publication_date)
        ORDER BY year DESC, month DESC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();

    $months = array_map(fn($r) => [
        'value'         => $r['month'] . '-' . $r['year'],
        'label'         => $r['month_name'] . ' ' . $r['year'],
        'year'          => (int) $r['year'],
        'month'         => (int) $r['month'],
        'project_count' => (int) $r['project_count'],
    ], $results);

    jsonResponse([
        'months'       => $months,
        'total_months' => count($months),
    ]);

} catch (Exception $e) {
    error_log('Available months error: ' . $e->getMessage());
    jsonResponse(['months' => [], 'total_months' => 0]);
}
