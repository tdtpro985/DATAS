<?php
/**
 * Platform Leads API - Create new platform lead
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
    
    // Check for complete duplicate (all fields identical)
    $duplicateCheckStmt = $pdo->prepare("
        SELECT id FROM platform_leads 
        WHERE source = :source 
        AND contact_person = :contact_person 
        AND contact_number = :contact_number 
        AND email_address = :email_address 
        AND COALESCE(company_name, '') = :company_name
        AND COALESCE(company_location, '') = :company_location
        AND COALESCE(materials_quantity, '') = :materials_quantity
        LIMIT 1
    ");
    
    $duplicateCheckStmt->execute([
        ':source' => $source,
        ':contact_person' => $contact_person,
        ':contact_number' => $contact_number,
        ':email_address' => $email_address,
        ':company_name' => $company_name,
        ':company_location' => $company_location,
        ':materials_quantity' => $materials_quantity
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
    
    // Insert platform lead
    $stmt = $pdo->prepare("
        INSERT INTO platform_leads (
            source,
            company_name,
            contact_person,
            contact_number,
            email_address,
            company_location,
            materials_quantity,
            created_by,
            created_at,
            updated_at
        ) VALUES (
            :source,
            :company_name,
            :contact_person,
            :contact_number,
            :email_address,
            :company_location,
            :materials_quantity,
            :created_by,
            NOW(),
            NOW()
        )
    ");
    
    $result = $stmt->execute([
        ':source' => $source,
        ':company_name' => $company_name ?: null,
        ':contact_person' => $contact_person,
        ':contact_number' => $contact_number,
        ':email_address' => $email_address,
        ':company_location' => $company_location ?: null,
        ':materials_quantity' => $materials_quantity ?: null,
        ':created_by' => $_SESSION['user']['id']
    ]);
    
    if ($result) {
        $platform_id = $pdo->lastInsertId();
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Platform lead created successfully',
            'platform_id' => $platform_id
        ]);
    } else {
        throw new Exception('Failed to create platform lead');
    }
    
} catch (Exception $e) {
    error_log("Platform Lead Create Error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>