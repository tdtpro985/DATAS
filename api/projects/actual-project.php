<?php
/* ============================================================
   POST /api/v1/projects/{id}/actual-project — Save is_actual_project field
   ============================================================ */

// Clean any existing output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

try {
    $user = requireRole(['superadmin', 'admin', 'sales_rep']);
} catch (Exception $e) {
    jsonError('Authentication failed: ' . $e->getMessage(), 401);
}

// Get project ID from URL parameter
$projectId = (int)($_GET['id'] ?? 0);

if (!$projectId) {
    jsonError('Project ID is required', 400);
}

// Handle POST request only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$body = getJsonBody();
if (!$body) {
    jsonError('Request body is required', 400);
}

try {
    $db = getDB();
    
    // Verify project exists
    $projectStmt = $db->prepare('SELECT id FROM projects WHERE id = :id LIMIT 1');
    $projectStmt->execute([':id' => $projectId]);
    if (!$projectStmt->fetch()) {
        jsonError('Project not found', 404);
    }
    
    // Get is_actual_project value
    if (!isset($body['is_actual_project'])) {
        jsonError('is_actual_project field is required', 400);
    }
    
    $isActualProject = $body['is_actual_project']; // 'yes' or 'no'
    
    // Update projects table
    $updateProjectStmt = $db->prepare("
        UPDATE projects SET 
            is_actual_project = :is_actual_project,
            updated_at = NOW()
        WHERE id = :project_id
    ");
    $updateProjectStmt->execute([
        ':is_actual_project' => $isActualProject,
        ':project_id' => $projectId
    ]);
    
    jsonResponse([
        'message' => 'Project status saved successfully',
        'is_actual_project' => $isActualProject
    ]);
    
} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
