<?php
/* ============================================================
   PATCH /api/v1/projects/{id}/status
   ============================================================
   Updates a project's status. sales_rep and superadmin only.
   Accepts: JSON { "status": "<new_status>" }
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../activity-logger.php';

$user = requireRole(['sales_rep', 'superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonError('Method not allowed', 405);
}

// Extract project ID from URL — router sets it as a query param
$projectId = (int) ($_GET['id'] ?? 0);
if ($projectId <= 0) {
    jsonError('Invalid project ID', 400);
}

$body = getJsonBody();
$newStatus = trim($body['status'] ?? '');

$validStatuses = ['Prospect', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost', 'Priority', 'Unqualified', 'For Execution', 'For Bidding', 'Awarded', 'Contacted', 'Sales Qualified', 'Not Sales Qualified', 'Quoted'];
if (!in_array($newStatus, $validStatuses, true)) {
    jsonError('Invalid status value', 422);
}

$db = getDB();

// Verify project exists
$stmt = $db->prepare('SELECT id, status FROM projects WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $projectId]);
$project = $stmt->fetch();

if (!$project) {
    jsonError('Project not found', 404);
}

// Update status
$db->prepare('UPDATE projects SET status = :status WHERE id = :id')
   ->execute([':status' => $newStatus, ':id' => $projectId]);

logActivity($db, $user['id'], ActivityType::PROJECT_UPDATE, EntityType::PROJECT, $projectId, "Project #{$projectId} status changed to '{$newStatus}'");

jsonResponse([
    'id'      => $projectId,
    'status'  => $newStatus,
    'message' => 'Status updated.',
]);
