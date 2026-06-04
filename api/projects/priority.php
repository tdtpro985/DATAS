<?php
/* ============================================================
   GET /api/v1/projects/priority
   ============================================================
   Returns all projects with status = "Priority"
   Supports pagination: page, size query params
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$page = max(1, (int)qp('page', 1));
$size = min(500, max(1, (int)qp('size', 100)));
$offset = ($page - 1) * $size;

$db = getDB();

// Get total count of priority projects
$countStmt = $db->query("SELECT COUNT(*) as cnt FROM projects WHERE status = 'Priority'");
$countRow = $countStmt->fetch();
$total = (int)$countRow['cnt'];

// Get paginated priority projects
$stmt = $db->prepare("
    SELECT 
        p.*,
        u.full_name as encoded_by_user,
        u.email as encoded_by_email
    FROM projects p
    LEFT JOIN users u ON p.encoded_by = u.id
    WHERE p.status = 'Priority'
    ORDER BY p.updated_at DESC, p.created_at DESC
    LIMIT :size OFFSET :offset
");
$stmt->bindValue(':size', $size, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

jsonResponse([
    'projects' => $projects,
    'total' => $total,
    'page' => $page,
    'size' => $size,
    'total_pages' => ceil($total / $size),
]);
