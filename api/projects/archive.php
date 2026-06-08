<?php
/**
 * Archive Projects API
 * 
 * POST - Archive a project (soft delete)
 * PUT  - Restore an archived project  
 */

require_once '../db.php';
require_once '../helpers.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check authentication
session_start();
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user = $_SESSION['user'];
$userId = $user['id'];
$userRole = $user['role'];

// Only admins and superadmins can archive/restore projects
if (!in_array($userRole, ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $pdo = getDbConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            handleArchive($pdo, $userId);
            break;
        case 'PUT':
            handleRestore($pdo, $userId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log('Archive API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Archive a project (soft delete)
 */
function handleArchive($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['project_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Project ID is required']);
        return;
    }
    
    $projectId = (int)$input['project_id'];
    
    // Check if project exists and is not already archived
    $stmt = $pdo->prepare("
        SELECT id, contractor_name, project_name, archived_at 
        FROM projects 
        WHERE id = ? AND archived_at IS NULL
    ");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found or already archived']);
        return;
    }
    
    // Archive the project
    $stmt = $pdo->prepare("
        UPDATE projects 
        SET archived_at = NOW(), archived_by = ?
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$userId, $projectId]);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Project archived successfully',
            'data' => [
                'project_id' => $projectId,
                'contractor_name' => $project['contractor_name'],
                'project_name' => $project['project_name']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to archive project']);
    }
}

/**
 * Restore an archived project
 */
function handleRestore($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['project_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Project ID is required']);
        return;
    }
    
    $projectId = (int)$input['project_id'];
    
    // Check if project exists and is archived
    $stmt = $pdo->prepare("
        SELECT id, contractor_name, project_name, archived_at 
        FROM projects 
        WHERE id = ? AND archived_at IS NOT NULL
    ");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Archived project not found']);
        return;
    }
    
    // Restore the project
    $stmt = $pdo->prepare("
        UPDATE projects 
        SET archived_at = NULL, archived_by = NULL
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$projectId]);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Project restored successfully',
            'data' => [
                'project_id' => $projectId,
                'contractor_name' => $project['contractor_name'],
                'project_name' => $project['project_name']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to restore project']);
    }
}
?>