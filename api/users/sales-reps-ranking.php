<?php
/* ============================================================
   GET /api/users/sales-reps-ranking
   ============================================================
   Returns sales representatives ranked by number of projects processed.
   Shows sales reps with the most projects encoded.
   Query params: period, month, region, limit
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db     = getDB();
$date   = buildDateFilter('p.publication_date');
$region = getRegion();
$limit  = min(100, max(1, (int)qp('limit', 10))); // Default top 10, max 100

// Build region clause
$regionSql    = '';
$regionParams = [];
if ($region !== null) {
    $regionSql    = ' AND p.region = :region';
    $regionParams = [':region' => $region];
}

$params = array_merge($date['params'], $regionParams);
$where  = 'WHERE ' . $date['sql'] . $regionSql;

// Get sales reps ranked by project count
$stmt = $db->prepare("
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.branch,
        COUNT(p.id) AS projects_count,
        COALESCE(SUM(p.project_value), 0) AS total_value,
        MAX(p.created_at) AS last_project_date
    FROM users u
    INNER JOIN projects p ON u.id = p.encoded_by
    $where
    AND u.role = 'sales_rep'
    GROUP BY u.id, u.full_name, u.email, u.branch
    ORDER BY projects_count DESC, total_value DESC
    LIMIT :limit
");

// Bind date and region parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$rankings = $stmt->fetchAll();

// Cast numeric values to proper types
$rankings = array_map(function ($r) {
    return [
        'id'                => (int)$r['id'],
        'full_name'         => $r['full_name'],
        'email'             => $r['email'],
        'branch'            => $r['branch'],
        'projects_count'    => (int)$r['projects_count'],
        'total_value'       => (float)$r['total_value'],
        'last_project_date' => $r['last_project_date']
    ];
}, $rankings);

jsonResponse([
    'rankings' => $rankings,
    'total'    => count($rankings),
    'filters'  => [
        'period' => qp('period', 'all'),
        'month'  => qp('month'),
        'region' => $region ?? 'All Regions',
        'limit'  => $limit
    ]
]);
