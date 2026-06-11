<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../helpers.php';

    session_start();
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $pdo = getDB();
    
    $path = $_GET['path'] ?? '';
    error_log("[UPDATE] Path: " . $path);
    
    if (preg_match('#^projects/(\d+)$#', $path, $matches)) {
        $projectId = (int)$matches[1];
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid path: ' . $path]);
        exit;
    }
    
    $rawInput = file_get_contents('php://input');
    error_log("[UPDATE] Raw: " . substr($rawInput, 0, 500));
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'JSON error: ' . json_last_error_msg()]);
        exit;
    }
    
    if (!is_array($input) || empty($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data']);
        exit;
    }
    
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
        echo json_encode(['success' => false, 'message' => 'Cannot update archived project']);
        exit;
    }
    
    $allowedFields = [
        'published_date', 'source', 'contract_id', 'contractor_name', 
        'contact_person', 'contact_number', 'project_id', 'project_name', 
        'country', 'region', 'province', 'city', 'barangay', 'street', 
        'bulk_lot', 'coordinates', 'complete_address', 'steel_bars', 
        'h_beams', 'i_beams', 'c_purlins', 'square_tubes', 'round_pipes', 
        'gi_sheets', 'metal_deck', 'other_materials'
    ];
    
    $updates = [];
    $params = [];
    
    foreach ($input as $key => $value) {
        if ($key === 'id') continue;
        if (in_array($key, $allowedFields, true)) {
            $updates[] = "`$key` = ?";
            $params[] = $value;
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid fields']);
        exit;
    }
    
    $updates[] = "`updated_at` = NOW()";
    $params[] = $projectId;
    
    $sql = "UPDATE projects SET " . implode(', ', $updates) . " WHERE id = ?";
    error_log("[UPDATE] SQL: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Updated successfully',
        'updated_fields' => count($updates) - 1
    ]);
    
} catch (PDOException $e) {
    error_log("[UPDATE] DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("[UPDATE] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error', 'error' => $e->getMessage()]);
}
