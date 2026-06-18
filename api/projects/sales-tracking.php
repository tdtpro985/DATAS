<?php
/* ============================================================
   GET/POST /api/v1/projects/{id}/sales-tracking — Get/Save sales tracking data
   ============================================================ */

// Set error handling to prevent output
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Clean any existing output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Start new output buffer
ob_start();

try {
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../helpers.php';
    require_once __DIR__ . '/../activity-logger.php';
} catch (Exception $e) {
    // Clean output buffer
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['detail' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

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
            // Case-insensitive comparison to handle any value formatting
            $tracking['contacted'] = strcasecmp($tracking['contacted'] ?? '', 'Yes') === 0 ? true : (strcasecmp($tracking['contacted'] ?? '', 'No') === 0 ? false : null);
            $tracking['quoted'] = strcasecmp($tracking['quoted'] ?? '', 'Yes') === 0 ? true : (strcasecmp($tracking['quoted'] ?? '', 'No') === 0 ? false : null);
            $tracking['sales_qualified'] = strcasecmp($tracking['sales_qualified'] ?? '', 'Yes') === 0 ? true : (strcasecmp($tracking['sales_qualified'] ?? '', 'No') === 0 ? false : null);
            $tracking['to_win'] = strcasecmp($tracking['to_win'] ?? '', 'Yes') === 0 ? true : (strcasecmp($tracking['to_win'] ?? '', 'No') === 0 ? false : null);
            
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
    $projectStmt = $db->prepare('SELECT id, assigned_to FROM projects WHERE id = :id LIMIT 1');
    $projectStmt->execute([':id' => $projectId]);
    $projectRow = $projectStmt->fetch(PDO::FETCH_ASSOC);
    if (!$projectRow) {
        jsonError('Project not found', 404);
    }

    // Admin can only track unassigned projects
    if ($user['role'] === 'admin' && !empty($projectRow['assigned_to'])) {
        jsonError('Admin can only save sales tracking for unassigned projects.', 403);
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
    // Complete = all 4 yes/no fields answered
    // If to_win = Yes, wa_amount must also be > 0 for Complete
    $yesNoFields = [$contacted, $quoted, $salesQualified, $toWin];
    $allAnswered  = count(array_filter($yesNoFields, fn($v) => $v !== null)) === 4;
    $winNeedsAmount = ($toWin === 'Yes' && (!$waAmount || $waAmount <= 0));

    if (array_filter($yesNoFields, fn($v) => $v !== null) === []) {
        $trackingStatus = 'Not Started';
    } elseif ($allAnswered && !$winNeedsAmount) {
        $trackingStatus = 'Complete';
    } else {
        $trackingStatus = 'In Progress';
    }
    
    // Check if sales tracking record exists
    $existingStmt = $db->prepare('SELECT * FROM sales_tracking WHERE project_id = :project_id LIMIT 1');
    $existingStmt->execute([':project_id' => $projectId]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

    // ── Compute which stage timestamps to set (only stamp the FIRST time) ──
    // Check if the new columns exist (graceful degradation)
    $hasTimestampCols = false;
    try {
        $colCheck = $db->query("SHOW COLUMNS FROM sales_tracking LIKE 'contacted_at'")->fetch();
        $hasTimestampCols = (bool)$colCheck;
    } catch (Exception $e) {}

    $now = date('Y-m-d H:i:s');
    $contactedAt       = null;
    $salesQualifiedAt  = null;
    $quotedAt          = null;
    $toWinAt           = null;
    $assignedAt        = null;

    if ($hasTimestampCols && $existing) {
        // Stamp when a field transitions from null → any value (Yes or No)
        $contactedAt      = ($contacted !== null    && empty($existing['contacted_at']))       ? $now : ($existing['contacted_at']      ?? null);
        $salesQualifiedAt = ($salesQualified !== null && empty($existing['sales_qualified_at'])) ? $now : ($existing['sales_qualified_at'] ?? null);
        $quotedAt         = ($quoted !== null        && empty($existing['quoted_at']))           ? $now : ($existing['quoted_at']          ?? null);
        $toWinAt          = ($toWin !== null         && empty($existing['to_win_at']))           ? $now : ($existing['to_win_at']          ?? null);
        $assignedAt       = $existing['assigned_at'] ?? $existing['created_at'] ?? $now;
    } elseif ($hasTimestampCols && !$existing) {
        $assignedAt       = $now;
        $contactedAt      = ($contacted !== null)     ? $now : null;
        $salesQualifiedAt = ($salesQualified !== null) ? $now : null;
        $quotedAt         = ($quoted !== null)         ? $now : null;
        $toWinAt          = ($toWin !== null)          ? $now : null;
    }

    if ($existing) {
        // Update existing record
        // For updates, if no sales_rep_id is provided, keep the existing one or use current user
        if (!$salesRepId) {
            $salesRepId = $existing['sales_rep_id'] ?? $user['id'];
        }

        $timestampSql = $hasTimestampCols ? "
                assigned_at        = :assigned_at,
                contacted_at       = :contacted_at,
                sales_qualified_at = :sales_qualified_at,
                quoted_at          = :quoted_at,
                to_win_at          = :to_win_at," : '';

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
                $timestampSql
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
        ] + ($hasTimestampCols ? [
            ':assigned_at'        => $assignedAt,
            ':contacted_at'       => $contactedAt,
            ':sales_qualified_at' => $salesQualifiedAt,
            ':quoted_at'          => $quotedAt,
            ':to_win_at'          => $toWinAt,
        ] : []));
        
    } else {
        // Create new record
        if (!$salesRepId) {
            $salesRepId = $user['id'];
        }

        $tsColumns = $hasTimestampCols ? ", assigned_at, contacted_at, sales_qualified_at, quoted_at, to_win_at" : '';
        $tsValues  = $hasTimestampCols ? ", :assigned_at, :contacted_at, :sales_qualified_at, :quoted_at, :to_win_at" : '';

        $insertStmt = $db->prepare("
            INSERT INTO sales_tracking (
                project_id, sales_rep_id, contacted, quoted, sales_qualified,
                to_win, wa_amount, branch, notes, tracking_status, updated_by
                $tsColumns
            ) VALUES (
                :project_id, :sales_rep_id, :contacted, :quoted, :sales_qualified,
                :to_win, :wa_amount, :branch, :remarks, :tracking_status, :updated_by
                $tsValues
            )
        ");

        $insertStmt->execute([
            ':project_id'      => $projectId,
            ':sales_rep_id'    => $salesRepId,
            ':contacted'       => $contacted,
            ':quoted'          => $quoted,
            ':sales_qualified' => $salesQualified,
            ':to_win'          => $toWin,
            ':wa_amount'       => $waAmount,
            ':branch'          => $branch,
            ':remarks'         => $remarks,
            ':tracking_status' => $trackingStatus,
            ':updated_by'      => $user['id'],
        ] + ($hasTimestampCols ? [
            ':assigned_at'        => $assignedAt,
            ':contacted_at'       => $contactedAt,
            ':sales_qualified_at' => $salesQualifiedAt,
            ':quoted_at'          => $quotedAt,
            ':to_win_at'          => $toWinAt,
        ] : []));
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
    
    logActivity($db, $user['id'], ActivityType::SALES_TRACKING_UPDATE, EntityType::SALES_TRACKING, $projectId, "Sales tracking updated for project #{$projectId}", ['tracking_status' => $trackingStatus]);

    jsonResponse([
        'message' => 'Sales tracking saved successfully',
        'tracking_status' => $trackingStatus,
        'auto_assigned' => ($project && $project['assigned_to'] === null && $salesRepId) ? true : false
    ]);
    
    } catch (PDOException $e) {
        // Log database error
        error_log('[SALES_TRACKING] POST Database error: ' . $e->getMessage());
        error_log('[SALES_TRACKING] POST Stack trace: ' . $e->getTraceAsString());
        jsonError('Database error: Unable to save sales tracking data', 500);
    } catch (Exception $e) {
        // Log general error
        error_log('[SALES_TRACKING] POST General error: ' . $e->getMessage());
        error_log('[SALES_TRACKING] POST Stack trace: ' . $e->getTraceAsString());
        jsonError('Error: ' . $e->getMessage(), 500);
    }
    
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
        
        logActivity($db, $user['id'], ActivityType::SALES_TRACKING_UPDATE, EntityType::SALES_TRACKING, $projectId, "Sales tracking cleared for project #{$projectId}");
        
        jsonResponse(['success' => true, 'message' => 'Sales tracking cleared successfully']);
        
    } catch (Exception $e) {
        jsonError('Database error: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed', 405);