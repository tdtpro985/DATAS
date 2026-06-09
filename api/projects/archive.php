<?php
/**
 * Archive Projects API
 * 
 * POST - Archive a project (soft delete)
 * PUT  - Restore an archived project  
 */

require_once '../db.php';
require_once '../helpers.php';

try {
    // Authenticate and require admin/superadmin role
    $user = requireRole(['admin', 'superadmin']);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $userId = $user['id'];
    $db = getDB();
    
    if ($method === 'POST') {
        handleArchive($db, $userId);
    } elseif ($method === 'PUT') {
        handleRestore($db, $userId);
    } else {
        jsonError('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log('Archive API error: ' . $e->getMessage());
    jsonError('Internal server error: ' . $e->getMessage(), 500);
}

/**
 * Archive a project (soft delete)
 */
function handleArchive($db, $userId) {
    $body = getJsonBody();
    
    if (!$body || !isset($body['project_id'])) {
        jsonError('Project ID is required', 400);
    }
    
    $projectId = (int)$body['project_id'];
    
    // Check if project exists and is not already archived
    $stmt = $db->prepare("
        SELECT id, contractor_name, project_name, archived_at 
        FROM projects 
        WHERE id = :id AND archived_at IS NULL
    ");
    $stmt->execute([':id' => $projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        jsonError('Project not found or already archived', 404);
    }
    
    // Archive the project
    $stmt = $db->prepare("
        UPDATE projects 
        SET archived_at = NOW(), archived_by = :user_id
        WHERE id = :id
    ");
    
    $success = $stmt->execute([
        ':user_id' => $userId,
        ':id' => $projectId
    ]);
    
    if ($success) {
        jsonResponse([
            'id' => $projectId,
            'message' => 'Project archived successfully',
            'contractor_name' => $project['contractor_name'],
            'project_name' => $project['project_name']
        ]);
    } else {
        jsonError('Failed to archive project', 500);
    }
}

/**
 * Restore an archived project
 */
function handleRestore($db, $userId) {
    $body = getJsonBody();
    
    if (!$body || !isset($body['project_id'])) {
        jsonError('Project ID is required', 400);
    }
    
    $projectId = (int)$body['project_id'];
    
    // Check if project exists and is archived
    $stmt = $db->prepare("
        SELECT id, contractor_name, project_name, archived_at 
        FROM projects 
        WHERE id = :id AND archived_at IS NOT NULL
    ");
    $stmt->execute([':id' => $projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        jsonError('Archived project not found', 404);
    }
    
    // Restore the project
    $stmt = $db->prepare("
        UPDATE projects 
        SET archived_at = NULL, archived_by = NULL
        WHERE id = :id
    ");
    
    $success = $stmt->execute([':id' => $projectId]);
    
    if ($success) {
        jsonResponse([
            'id' => $projectId,
            'message' => 'Project restored successfully',
            'contractor_name' => $project['contractor_name'],
            'project_name' => $project['project_name']
        ]);
    } else {
        jsonError('Failed to restore project', 500);
    }
}