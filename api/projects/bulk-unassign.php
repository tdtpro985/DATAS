<?php
/* ============================================================
   api/projects/bulk-unassign.php — Bulk Project Unassignment
   ============================================================
   POST /api/v1/projects/bulk-unassign
   Body: {
     "project_ids": [456, 789, 101]
   }
   ============================================================ */

ob_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

// Check authentication
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['user']['role'] ?? '';

// Only admin and superadmin can bulk unassign
if ($role !== 'admin' && $role !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    ob_clean();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['project_ids'])) {
        throw new Exception('project_ids is required');
    }
    
    $projectIds = $input['project_ids'];
    
    if (!is_array($projectIds) || empty($projectIds)) {
        throw new Exception('project_ids must be a non-empty array');
    }
    
    $pdo = getDB();
    
    // Begin transaction
    $pdo->beginTransaction();
    
    $successCount = 0;
    $failedCount = 0;
    $errors = [];
    
    foreach ($projectIds as $projectId) {
        try {
            $projectId = (int)$projectId;
            
            // Check if project exists and is assigned
            $stmt = $pdo->prepare("SELECT id, assigned_to FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                $errors[] = "Project $projectId not found";
                $failedCount++;
                continue;
            }
            
            if (!$project['assigned_to']) {
                $errors[] = "Project $projectId is not assigned";
                $failedCount++;
                continue;
            }
            
            // Remove assignment
            $stmt = $pdo->prepare("
                UPDATE projects 
                SET assigned_to = NULL, assigned_by = NULL, assigned_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$projectId]);
            
            $successCount++;
        } catch (Exception $e) {
            $errors[] = "Project $projectId: " . $e->getMessage();
            $failedCount++;
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    $message = "Successfully unassigned $successCount project(s)";
    if ($failedCount > 0) {
        $message .= ", $failedCount failed";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'unassigned' => $successCount,
        'failed' => $failedCount,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();