<?php
/* ============================================================
   api/projects/update.php — Update Project Details
   ============================================================
   PUT /api/v1/projects/:id
   Updates specific fields of a project.
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

// Check authentication
session_start();
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'] ?? '';

try {
    $pdo = getDB();
    
    // Get project ID from path
    $path = $_GET['path'] ?? '';
    
    // Extract ID from path like "projects/123"
    if (preg_match('#^projects/(\d+)$#', $path, $matches)) {
        $projectId = (int)$matches[1];
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid project ID in path: ' . $path]);
        exit;
    }
    
    // Parse JSON body
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
        exit;
    }
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data provided']);
        exit;
    }
    
    // Verify project exists
    $stmt = $pdo->prepare('SELECT id, archived_at FROM projects WHERE id = ?');
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }
    
    if ($project['archived_at']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot update archived projects']);
        exit;
    }
    
    // Build dynamic UPDATE query based on provided fields
    $allowedFields = [
        // Contract Details
        'published_date', 'source', 'contract_id', 'contractor_name', 
        'contact_person', 'contact_number',
        
        // Project Details
        'project_id', 'project_name', 'country', 'region', 'province', 
        'city', 'barangay', 'street', 'bulk_lot', 'coordinates', 'complete_address',
        
        // Materials
        'steel_bars', 'h_beams', 'i_beams', 'c_purlins', 'square_tubes',
        'round_pipes', 'gi_sheets', 'metal_deck', 'other_materials'
    ];
    
    $updates = [];
    $params = [];
    
    foreach ($input as $key => $value) {
        if ($key === 'id') continue; // Skip ID
        
        if (in_array($key, $allowedFields)) {
            $updates[] = "`$key` = ?";
            $params[] = $value;
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        exit;
    }
    
    // Add updated_at timestamp
    $updates[] = "`updated_at` = NOW()";
    
    // Add project ID to params
    $params[] = $projectId;
    
    // Execute update
    $sql = "UPDATE projects SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);
    
    if (!$success) {
        throw new Exception('Failed to execute update query');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Project updated successfully',
        'updated_fields' => count($updates) - 1, // Exclude updated_at
        'project_id' => $projectId
    ]);
    
} catch (PDOException $e) {
    error_log("Project Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Project Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
