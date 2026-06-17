<?php
/* ============================================================
   POST /api/v1/log-export — Log export activity from client-side
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/activity-logger.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$body = getJsonBody();
if (!$body) {
    jsonError('Request body is required', 400);
}

try {
    $db = getDB();
    
    $period = $body['period'] ?? 'monthly';
    $dateMode = $body['dateMode'] ?? 'published';
    $sections = $body['sections'] ?? [];
    $projectCount = (int)($body['projectCount'] ?? 0);
    
    $description = "Full Reports exported - Period: {$period}, Date basis: {$dateMode}, Sections: " . implode(', ', $sections) . ", Projects: {$projectCount}";
    
    logActivity($db, $user['id'], ActivityType::EXPORT_DATA, EntityType::EXPORT, null, $description, [
        'period' => $period,
        'date_mode' => $dateMode,
        'sections' => $sections,
        'project_count' => $projectCount
    ]);
    
    jsonResponse(['success' => true, 'message' => 'Export logged successfully']);
    
} catch (Exception $e) {
    error_log('Export logging error: ' . $e->getMessage());
    jsonError('Failed to log export', 500);
}