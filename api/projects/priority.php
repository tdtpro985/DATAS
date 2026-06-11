<?php
/* ============================================================
   GET /api/v1/projects/priority
   ============================================================
   Returns non-archived, active priority projects.
   Supports pagination: page, size query params
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$page   = max(1, (int)qp('page', 1));
$size   = min(500, max(1, (int)qp('size', 100)));
$offset = ($page - 1) * $size;

$db = getDB();

// Exclude: archived projects AND illegitimate projects (is_actual_project = 'no')
// Case-insensitive status match to catch both 'Priority' and 'PRIORITY'
$baseWhere = "WHERE LOWER(TRIM(p.status)) = 'priority'
              AND p.archived_at IS NULL";

// Also exclude is_actual_project = 'no' only if the column exists
try {
    $colCheck = $db->query("SHOW COLUMNS FROM projects LIKE 'is_actual_project'");
    if ($colCheck->rowCount() > 0) {
        $baseWhere .= " AND (p.is_actual_project IS NULL OR p.is_actual_project != 'no')";
    }
} catch (Exception $e) {
    // Column doesn't exist — skip the filter
}

// Total count
$countStmt = $db->query("SELECT COUNT(*) AS cnt FROM projects p $baseWhere");
$total = (int) $countStmt->fetch()['cnt'];

// Paginated results
$stmt = $db->prepare("
    SELECT
        p.*,
        u.full_name AS encoded_by_user,
        u.email     AS encoded_by_email
    FROM projects p
    LEFT JOIN users u ON p.encoded_by = u.id
    $baseWhere
    ORDER BY p.created_at DESC
    LIMIT :size OFFSET :offset
");
$stmt->bindValue(':size',   $size,   PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

jsonResponse([
    'projects'    => $projects,
    'total'       => $total,
    'page'        => $page,
    'size'        => $size,
    'total_pages' => (int) ceil($total / $size),
]);
