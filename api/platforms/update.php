<?php
/**
 * Platform Leads API - Update platform lead
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
if (!in_array($role, ['encoder', 'admin', 'superadmin'], true)) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

require_once __DIR__ . '/../db.php';

try {
    $pdo = getDB();
    
    // Get platform ID
    $platform_id = intval($_POST['platform_id'] ?? 0);
    if ($platform_id <= 0) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid platform ID']);
        exit;
    }
    
    // Validate required fields
    $source = trim($_POST['source'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email_address = trim($_POST['email_address'] ?? '');
    
    if (empty($source) || empty($contact_person) || empty($contact_number) || empty($email_address)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address format']);
        exit;
    }
    
    // Optional fields
    $company_name = trim($_POST['company_name'] ?? '');
    $company_location = trim($_POST['company_location'] ?? '');
    $materials_quantity = trim($_POST['materials_quantity'] ?? '');
    
    // Check if platform exists
    $checkStmt = $pdo->prepare("SELECT id FROM platform_leads WHERE id = :id");
    $checkStmt->execute([':id' => $platform_id]);
    
    if ($checkStmt->rowCount() === 0) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Platform lead not found']);
        exit;
    }
    
    // Check for complete duplicate (excluding current record)
    $duplicateCheckStmt = $pdo->prepare("
        SELECT id FROM platform_leads 
        WHERE source = :source 
        AND contact_person = :contact_person 
        AND contact_number = :contact_number 
        AND email_address = :email_address 
        AND COALESCE(company_name, '') = :company_name
        AND COALESCE(company_location, '') = :company_location
        AND COALESCE(materials_quantity, '') = :materials_quantity
        AND id != :current_id
        LIMIT 1
    ");
    
    $duplicateCheckStmt->execute([
        ':source' => $source,
        ':contact_person' => $contact_person,
        ':contact_number' => $contact_number,
        ':email_address' => $email_address,
        ':company_name' => $company_name,
        ':company_location' => $company_location,
        ':materials_quantity' => $materials_quantity,
        ':current_id' => $platform_id
    ]);
    
    if ($duplicateCheckStmt->rowCount() > 0) {
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'This exact platform lead already exists. Please modify at least one field.'
        ]);
        exit;
    }
    
    // Update platform lead
    $stmt = $pdo->prepare("
        UPDATE platform_leads SET
            source = :source,
            company_name = :company_name,
            contact_person = :contact_person,
            contact_number = :contact_number,
            email_address = :email_address,
            company_location = :company_location,
            materials_quantity = :materials_quantity,
            updated_at = NOW()
        WHERE id = :id
    ");
    
    $result = $stmt->execute([
        ':source' => $source,
        ':company_name' => $company_name ?: null,
        ':contact_person' => $contact_person,
        ':contact_number' => $contact_number,
        ':email_address' => $email_address,
        ':company_location' => $company_location ?: null,
        ':materials_quantity' => $materials_quantity ?: null,
        ':id' => $platform_id
    ]);
    
    if ($result) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Platform lead updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update platform lead');
    }
    
} catch (Exception $e) {
    error_log("Platform Lead Update Error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>