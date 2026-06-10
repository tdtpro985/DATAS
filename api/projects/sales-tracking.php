<?php
/* ============================================================
   GET/POST /api/v1/projects/{id}/sales-tracking — Get/Save sales tracking data
   ============================================================ */

// Clean any existing output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

try {
    $user = requireRole(['superadmin', 'admin', 'sales_rep']);
} catch (Exception $e) {
    jsonError('Authentication failed: ' . $e->getMessage(), 401);
}

// Get project ID from URL parameter
$projectId = (int)($_GET['id'] ?? 0);

if (!$projectId) {
    jsonError('Project ID is required', 400);
}

// Handle GET request - retrieve existing sales tracking data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $db = getDB();
        
        // Debug logging if debug mode is enabled
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log('[SALES_TRACKING] GET request for project ID: ' . $projectId);
        }
        
        // Verify project exists and get is_actual_project
        $projectStmt = $db->prepare('SELECT id, is_actual_project FROM projects WHERE id = :id LIMIT 1');
        $projectStmt->execute([':id' => $projectId]);
        $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log('[SALES_TRACKING] Project not found: ' . $projectId);
            }
            jsonError('Project not found', 404);
        }
        
        // Get sales tracking data
        $trackingStmt = $db->prepare("
            SELECT st.*, u.full_name as sales_rep_name
            FROM sales_tracking st
            LEFT JOIN users u ON st.sales_rep_id = u.id
            WHERE st.project_id = :project_id
            LIMIT 1
        ");
        $trackingStmt->execute([':project_id' => $projectId]);
        $tracking = $trackingStmt->fetch(PDO::FETCH_ASSOC);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log('[SALES_TRACKING] Query result: ' . ($tracking ? 'Found' : 'Not found'));
        }
        
        if (!$tracking) {
            // No tracking data exists yet
            jsonResponse([
                'exists' => false,
                'data' => null
            ]);
        } else {
            // Convert Yes/No strings to boolean for frontend, preserve null for unset fields
            $tracking['contacted'] = $tracking['contacted'] === 'Yes' ? true : ($tracking['contacted'] === 'No' ? false : null);
            $tracking['quoted'] = $tracking['quoted'] === 'Yes' ? true : ($tracking['quoted'] === 'No' ? false : null);
            $tracking['sales_qualified'] = $tracking['sales_qualified'] === 'Yes' ? true : ($tracking['sales_qualified'] === 'No' ? false : null);
            $tracking['to_win'] = $tracking['to_win'] === 'Yes' ? true : ($tracking['to_win'] === 'No' ? false : null);
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log('[SALES_TRACKING] Returning data for project: ' . $projectId);
            }
            
            jsonResponse([
                'exists' => true,
                'data' => $tracking
            ]);
        }
        
    } catch (PDOException $e) {
        // Log database error
        error_log('[SALES_TRACKING] Database error: ' . $e->getMessage());
        error_log('[SALES_TRACKING] Stack trace: ' . $e->getTraceAsString());
        jsonError('Database error: Unable to fetch sales tracking data', 500);
    } catch (Exception $e) {
        // Log general error
        error_log('[SALES_TRACKING] General error: ' . $e->getMessage());
        error_log('[SALES_TRACKING] Stack trace: ' . $e->getTraceAsString());
        jsonError('Error: ' . $e->getMessage(), 500);
    }
    return;
}

// Handle POST request - save sales tracking data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    if (!$body) {
        jsonError('Request body is required', 400);
    }

    try {
        $db = getDB();
    
    // Verify project exists
    $projectStmt = $db->prepare('SELECT id FROM projects WHERE id = :id LIMIT 1');
    $projectStmt->execute([':id' => $projectId]);
    if (!$projectStmt->fetch()) {
        jsonError('Project not found', 404);
    }
    
    // Prepare sales tracking data - properly handle null values
    $contacted = array_key_exists('contacted', $body) && $body['contacted'] !== null ? ($body['contacted'] ? 'Yes' : 'No') : null;
    $quoted = array_key_exists('quoted', $body) && $body['quoted'] !== null ? ($body['quoted'] ? 'Yes' : 'No') : null;
    $salesQualified = array_key_exists('sales_qualified', $body) && $body['sales_qualified'] !== null ? ($body['sales_qualified'] ? 'Yes' : 'No') : null;
    $toWin = array_key_exists('to_win', $body) && $body['to_win'] !== null ? ($body['to_win'] ? 'Yes' : 'No') : null;
    $waAmount = isset($body['wa_amount']) ? (float)$body['wa_amount'] : null;
    $salesRepId = isset($body['sales_rep_id']) ? (int)$body['sales_rep_id'] : null;
    $branch = isset($body['branch']) ? trim($body['branch']) : null;
    $remarks = isset($body['remarks']) ? trim($body['remarks']) : (isset($body['notes']) ? trim($body['notes']) : null);
    
    // Debug logging if debug mode is enabled
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log('Sales tracking API - received data: ' . json_encode($body));
        error_log('Sales tracking API - processed data: ' . json_encode([
            'contacted' => $contacted,
            'quoted' => $quoted,
            'sales_qualified' => $salesQualified,
            'to_win' => $toWin,
            'wa_amount' => $waAmount,
            'sales_rep_id' => $salesRepId,
            'branch' => $branch,
            'remarks' => $remarks
        ]));
    }
    
    // Determine tracking status based on filled fields
    $trackingFields = [$contacted, $quoted, $salesQualified, $toWin, $waAmount];
    $filledFields = count(array_filter($trackingFields, function($value) {
        return $value !== null && $value !== '';
    }));
    
    if ($filledFields === 0) {
        $trackingStatus = 'Not Started';
    } elseif ($filledFields === count($trackingFields)) {
        $trackingStatus = 'Complete';
    } else {
        $trackingStatus = 'In Progress';
    }
    
    // Check if sales tracking record exists
    $existingStmt = $db->prepare('SELECT * FROM sales_tracking WHERE project_id = :project_id LIMIT 1');
    $existingStmt->execute([':project_id' => $projectId]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing record
        // For updates, if no sales_rep_id is provided, keep the existing one or use current user
        if (!$salesRepId) {
            $salesRepId = $existing['sales_rep_id'] ?? $user['id'];
        }
        
        $updateStmt = $db->prepare("
            UPDATE sales_tracking SET
                contacted = :contacted,
                quoted = :quoted,
                sales_qualified = :sales_qualified,
                to_win = :to_win,
                wa_amount = :wa_amount,
                sales_rep_id = :sales_rep_id,
                branch = :branch,
                notes = :remarks,
                tracking_status = :tracking_status,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE project_id = :project_id
        ");
        
        $updateStmt->execute([
            ':contacted' => $contacted,
            ':quoted' => $quoted,
            ':sales_qualified' => $salesQualified,
            ':to_win' => $toWin,
            ':wa_amount' => $waAmount,
            ':sales_rep_id' => $salesRepId,
            ':branch' => $branch,
            ':remarks' => $remarks,
            ':tracking_status' => $trackingStatus,
            ':updated_by' => $user['id'],
            ':project_id' => $projectId
        ]);
        
    } else {
        // Create new record
        // For new records, we need a sales_rep_id (required field)
        if (!$salesRepId) {
            $salesRepId = $user['id']; // Use current user as default
        }
        
        $insertStmt = $db->prepare("
            INSERT INTO sales_tracking (
                project_id, sales_rep_id, contacted, quoted, sales_qualified, 
                to_win, wa_amount, branch, notes, tracking_status, updated_by
            ) VALUES (
                :project_id, :sales_rep_id, :contacted, :quoted, :sales_qualified,
                :to_win, :wa_amount, :branch, :remarks, :tracking_status, :updated_by
            )
        ");
        
        $insertStmt->execute([
            ':project_id' => $projectId,
            ':sales_rep_id' => $salesRepId,
            ':contacted' => $contacted,
            ':quoted' => $quoted,
            ':sales_qualified' => $salesQualified,
            ':to_win' => $toWin,
            ':wa_amount' => $waAmount,
            ':branch' => $branch,
            ':remarks' => $remarks,
            ':tracking_status' => $trackingStatus,
            ':updated_by' => $user['id']
        ]);
    }
    
    // AUTO-ASSIGNMENT LOGIC: If project was unassigned and SR saves tracking, auto-assign to that SR
    $projectCheckStmt = $db->prepare('SELECT assigned_to FROM projects WHERE id = :id LIMIT 1');
    $projectCheckStmt->execute([':id' => $projectId]);
    $project = $projectCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project && $project['assigned_to'] === null && $salesRepId) {
        // Project is currently unassigned, auto-assign to the SR who saved the tracking
        $assignStmt = $db->prepare("
            UPDATE projects SET 
                assigned_to = :sales_rep_id,
                updated_at = NOW()
            WHERE id = :project_id
        ");
        $assignStmt->execute([
            ':sales_rep_id' => $salesRepId,
            ':project_id' => $projectId
        ]);
        
        // Log the auto-assignment
        error_log("AUTO-ASSIGNED: Project $projectId auto-assigned to SR $salesRepId");
    }
    
    jsonResponse([
        'message' => 'Sales tracking saved successfully',
        'tracking_status' => $trackingStatus,
        'auto_assigned' => ($project && $project['assigned_to'] === null && $salesRepId) ? true : false
    ]);
    
    return;
}

// Handle DELETE request - clear sales tracking data
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $db = getDB();
        
        // Verify project exists
        $projectStmt = $db->prepare('SELECT id FROM projects WHERE id = :id LIMIT 1');
        $projectStmt->execute([':id' => $projectId]);
        if (!$projectStmt->fetch()) {
            jsonError('Project not found', 404);
        }
        
        // Delete sales tracking record
        $deleteStmt = $db->prepare('DELETE FROM sales_tracking WHERE project_id = :project_id');
        $deleteStmt->execute([':project_id' => $projectId]);
        
        jsonResponse(['success' => true, 'message' => 'Sales tracking cleared successfully']);
        
    } catch (Exception $e) {
        jsonError('Database error: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed', 405);