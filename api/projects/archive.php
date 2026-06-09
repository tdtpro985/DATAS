<?php
/**
 * Projects API - Archive/Restore Project
 * POST: Archive a project (soft delete)
 * PUT: Restore an archived project
 */

// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['POST', 'PUT'], true)) {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'superadmin'], true)) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only admins can archive/restore projects']);
    exit;
}

require_once __DIR__ . '/../db.php';

try {
    $pdo = getDB();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $project_id = intval($input['project_id'] ?? 0);
    
    if ($project_id <= 0) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }
    
    // Check if project exists
    $checkStmt = $pdo->prepare("SELECT id, archived_at FROM projects WHERE id = :id");
    $checkStmt->execute([':id' => $project_id]);
    $project = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }
    
    if ($method === 'POST') {
        // Archive project
        if ($project['archived_at'] !== null) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Project is already archived']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE projects SET
                archived_at = NOW(),
                archived_by = :archived_by
            WHERE id = :id
        ");
        
        $result = $stmt->execute([
            ':id' => $project_id,
            ':archived_by' => $_SESSION['user']['id']
        ]);
        
        if ($result) {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Project archived successfully'
            ]);
        } else {
            throw new Exception('Failed to archive project');
        }
        
    } else if ($method === 'PUT') {
        // Restore project
        if ($project['archived_at'] === null) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Project is not archived']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE projects SET
                archived_at = NULL,
                archived_by = NULL
            WHERE id = :id
        ");
        
        $result = $stmt->execute([':id' => $project_id]);
        
        if ($result) {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Project restored successfully'
            ]);
        } else {
            throw new Exception('Failed to restore project');
        }
    }
    
} catch (Exception $e) {
    error_log("Project Archive Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
