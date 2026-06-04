<?php
/* ============================================================
   POST /api/v1/projects/{id}/assign
   ============================================================
   Assigns a project to a sales representative.
   Superadmin and admin only.
   Body: { "sales_rep_id": 123 }
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

$body = getJsonBody();
$salesRepId = (int) ($body['sales_rep_id'] ?? 0);

if ($salesRepId <= 0) {
    jsonError('Invalid sales rep ID', 400);
}

$db = getDB();

// Verify project exists
$stmt = $db->prepare('SELECT id, assigned_to FROM projects WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $projectId]);
$project = $stmt->fetch();

if (!$project) {
    jsonError('Project not found', 404);
}

// Verify sales rep exists and has correct role
$stmt = $db->prepare('SELECT id, full_name, role FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $salesRepId]);
$salesRep = $stmt->fetch();

if (!$salesRep) {
    jsonError('Sales representative not found', 404);
}

if ($salesRep['role'] !== 'sales_rep') {
    jsonError('User is not a sales representative', 400);
}

// Check if already assigned to this sales rep
if ($project['assigned_to'] == $salesRepId) {
    jsonError('Project already assigned to this sales representative', 400);
}

$action = $project['assigned_to'] ? 'reassigned' : 'assigned';

// Begin transaction
$db->beginTransaction();

try {
    // Update project assignment
    $stmt = $db->prepare('
        UPDATE projects 
        SET assigned_to = :sales_rep_id, 
            assigned_by = :assigned_by, 
            assigned_at = NOW()
        WHERE id = :project_id
    ');
    $stmt->execute([
        ':project_id' => $projectId,
        ':sales_rep_id' => $salesRepId,
        ':assigned_by' => $user['id']
    ]);
    
    $db->commit();
    
    jsonResponse([
        'success' => true,
        'message' => "Project {$action} to {$salesRep['full_name']}",
        'project_id' => $projectId,
        'assigned_to' => [
            'id' => $salesRep['id'],
            'full_name' => $salesRep['full_name']
        ]
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    jsonError('Failed to assign project: ' . $e->getMessage(), 500);
}
