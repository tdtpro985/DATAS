<?php
/**
 * Platform Leads API - Archive platform lead
 */

// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    echo json_encode(['success' => false, 'message' => 'Only admins can archive platform leads']);
    exit;
}

require_once __DIR__ . '/../db.php';

try {
    $pdo = getDB();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $platform_id = intval($input['platform_id'] ?? 0);
    
    if ($platform_id <= 0) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid platform ID']);
        exit;
    }
    
    // Check if platform exists
    $checkStmt = $pdo->prepare("SELECT id FROM platform_leads WHERE id = :id");
    $checkStmt->execute([':id' => $platform_id]);
    
    if ($checkStmt->rowCount() === 0) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Platform lead not found']);
        exit;
    }
    
    // Archive platform lead (soft delete by adding archived_at timestamp)
    $stmt = $pdo->prepare("
        UPDATE platform_leads SET
            archived_at = NOW(),
            archived_by = :archived_by
        WHERE id = :id
    ");
    
    $result = $stmt->execute([
        ':id' => $platform_id,
        ':archived_by' => $_SESSION['user']['id']
    ]);
    
    if ($result) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Platform lead archived successfully'
        ]);
    } else {
        throw new Exception('Failed to archive platform lead');
    }
    
} catch (Exception $e) {
    error_log("Platform Lead Archive Error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>