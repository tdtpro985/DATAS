<?php
/**
 * Platform Leads API - List all platform leads
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../db.php';

try {
    $pdo = getDB();
    
    // Get all platform leads ordered by creation date (newest first)
    $stmt = $pdo->prepare("
        SELECT 
            id,
            source,
            company_name,
            contact_person,
            contact_number,
            email_address,
            company_location,
            materials_quantity,
            created_at,
            updated_at
        FROM platform_leads 
        WHERE archived_at IS NULL
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'platforms' => $platforms,
        'count' => count($platforms)
    ]);
    
} catch (Exception $e) {
    error_log("Platform Leads API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>