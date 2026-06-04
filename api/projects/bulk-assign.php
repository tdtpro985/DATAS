<?php
/* ============================================================
   POST /api/v1/projects/bulk-assign - COMPLETELY REWRITTEN
   ============================================================
   Assigns multiple projects to a sales representative
   Body: { sales_rep_id: number, project_ids: number[] }
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

// Ensure clean output
ob_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    jsonError('Method not allowed', 405);
}

// Check authentication
$user = requireAuth();
$role = $user['role'] ?? '';

// Only admin and superadmin can bulk assign
if (!in_array($role, ['admin', 'superadmin'])) {
    ob_clean();
    jsonError('Forbidden: Only admins can bulk assign projects', 403);
}

// Get request body
$requestBody = getJsonBody();
if (!$requestBody) {
    ob_clean();
    jsonError('Invalid JSON body', 400);
}

// Validate required fields
$salesRepId = $requestBody['sales_rep_id'] ?? null;
$projectIds = $requestBody['project_ids'] ?? null;

if (!$salesRepId || !is_numeric($salesRepId)) {
    ob_clean();
    jsonError('sales_rep_id is required and must be a number', 400);
}

if (!$projectIds || !is_array($projectIds) || empty($projectIds)) {
    ob_clean();
    jsonError('project_ids is required and must be a non-empty array', 400);
}

// Validate all project IDs are numbers
foreach ($projectIds as $projectId) {
    if (!is_numeric($projectId)) {
        ob_clean();
        jsonError('All project_ids must be numbers', 400);
    }
}

try {
    $db = getDB();
    
    // Verify sales rep exists and has correct role
    $stmt = $db->prepare("SELECT id, full_name, role FROM users WHERE id = ? AND role = 'sales_rep'");
    $stmt->execute([$salesRepId]);
    $salesRep = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$salesRep) {
        ob_clean();
        jsonError('Sales representative not found or invalid', 404);
    }
    
    // Verify projects exist and are unassigned
    $placeholders = str_repeat('?,', count($projectIds) - 1) . '?';
    $stmt = $db->prepare("SELECT id, contractor_name, project_name, assigned_to FROM projects WHERE id IN ($placeholders)");
    $stmt->execute($projectIds);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($projects) !== count($projectIds)) {
        ob_clean();
        jsonError('One or more projects not found', 404);
    }
    
    // Check for already assigned projects
    $alreadyAssigned = [];
    foreach ($projects as $project) {
        if ($project['assigned_to'] !== null) {
            $alreadyAssigned[] = $project['contractor_name'] . ' - ' . $project['project_name'];
        }
    }
    
    if (!empty($alreadyAssigned)) {
        ob_clean();
        jsonError('Some projects are already assigned: ' . implode(', ', $alreadyAssigned), 409);
    }
    
    // Start transaction for bulk assignment
    $db->beginTransaction();
    
    try {
        // Assign all projects
        $assignStmt = $db->prepare("UPDATE projects SET assigned_to = ?, assigned_at = NOW() WHERE id = ?");
        $successfulAssignments = 0;
        
        foreach ($projectIds as $projectId) {
            if ($assignStmt->execute([$salesRepId, $projectId])) {
                $successfulAssignments++;
            }
        }
        
        // Commit transaction
        $db->commit();
        
        ob_clean();
        jsonResponse([
            'success' => true,
            'message' => "Successfully assigned $successfulAssignments project(s) to {$salesRep['full_name']}",
            'data' => [
                'assigned_count' => $successfulAssignments,
                'sales_rep_id' => $salesRepId,
                'sales_rep_name' => $salesRep['full_name'],
                'project_ids' => $projectIds
            ]
        ], 200);
        
    } catch (Exception $e) {
        $db->rollback();
        ob_clean();
        jsonError('Assignment failed: ' . $e->getMessage(), 500);
    }
    
} catch (PDOException $e) {
    ob_clean();
    jsonError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    ob_clean();
    jsonError('Error: ' . $e->getMessage(), 500);
}

ob_end_flush();
?>