<?php
/* ============================================================
   GET /api/v1/projects/my-recent
   ============================================================
   Returns the last 10 projects encoded by the current user.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db = getDB();
$stmt = $db->prepare("
    SELECT
        id,
        contractor_name,
        project_name,
        status,
        project_value,
        created_at
    FROM projects
    WHERE encoded_by = :uid
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([':uid' => $user['id']]);
$rows = $stmt->fetchAll();

$projects = array_map(fn($r) => [
    'id'              => (int)   $r['id'],
    'contractor_name' => $r['contractor_name'],
    'project_name'    => $r['project_name'],
    'status'          => $r['status'],
    'project_value'   => (float) $r['project_value'],
    'created_at'      => $r['created_at'],
], $rows);

jsonResponse(['projects' => $projects]);
