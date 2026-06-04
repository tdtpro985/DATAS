<?php
/* ============================================================
   POST /api/v1/projects/{id}/unassign
   ============================================================
   Removes assignment from a project.
   Superadmin and admin only.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$user = requireRole(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Extract project ID from URL
$projectId = (int) ($_GET['id'] ?? 0);
if ($projectId <= 0) {
    jsonError('Invalid project ID', 400);
}

$db = getDB();

// Verify project exists and check assignment
$stmt = $db->prepare('SELECT id, assigned_to FROM projects WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $projectId]);
$project = $stmt->fetch();

if (!$project) {
    jsonError('Project not found', 404);
}

if (!$project['assigned_to']) {
    jsonError('Project is not assigned', 400);
}

// Remove assignment
$stmt = $db->prepare('
    UPDATE projects 
    SET assigned_to = NULL, 
        assigned_by = NULL, 
        assigned_at = NULL
    WHERE id = :project_id
');
$stmt->execute([':project_id' => $projectId]);

jsonResponse([
    'success' => true,
    'message' => 'Project unassigned successfully',
    'project_id' => $projectId
]);
