<?php
/* ============================================================
   GET /api/v1/projects/processed
   ============================================================
   Returns all processed projects (is_processed = 1).
   These are projects with sales tracking.
   Supports pagination and filtering.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$page = max(1, (int)qp('page', 1));
$size = min(500, max(1, (int)qp('size', 50)));
$offset = ($page - 1) * $size;

// Filters
$status = qp('status', '');
$region = qp('region', '');
$source = qp('source', '');
$search = qp('search', '');
$salesRepId = qp('sales_rep_id', '');

$db = getDB();

// Build WHERE clause
// LOGIC: "Processed" = Projects with active tracking (any status EXCEPT 'Not Started')
$where = [];
$params = [];

// Core logic: Show projects with tracking status that is NOT 'Not Started'
$where[] = 'st.project_id IS NOT NULL';  // Must have sales tracking data
$where[] = 'st.tracking_status IS NOT NULL';  // Must have a tracking status
$where[] = 'st.tracking_status != "Not Started"';  // Must NOT be "Not Started"
$where[] = 'p.archived_at IS NULL';      // Not archived
$where[] = "(p.is_actual_project IS NULL OR p.is_actual_project != 'no')";  // Not illegitimate

// Additional filters
if ($status && $status !== 'all') {
    $where[] = 'p.status = :status';
    $params[':status'] = $status;
}

if ($region && $region !== 'all') {
    $where[] = '(p.region = :region OR p.contract_region = :region OR p.project_region = :region)';
    $params[':region'] = $region;
}

if ($source && $source !== 'all') {
    $where[] = 'p.source = :source';
    $params[':source'] = $source;
}

if ($salesRepId) {
    $where[] = 'p.assigned_to = :sales_rep_id';
    $params[':sales_rep_id'] = $salesRepId;
}

if ($search) {
    $where[] = '(p.contractor_name LIKE :search OR p.project_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

// Ensure we always have a valid WHERE clause
if (empty($where)) {
    $where[] = '1=1'; // Always true condition as fallback
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Get total count
$countStmt = $db->prepare("
    SELECT COUNT(*) as cnt 
    FROM projects p
    LEFT JOIN sales_tracking st ON p.id = st.project_id
    $whereClause
");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['cnt'];

// Get paginated results with sales tracking info
$stmt = $db->prepare("
    SELECT 
        p.*,
        u_encoded.full_name as encoded_by_name,
        u_assigned.full_name as assigned_to_name,
        u_assigned.email as assigned_to_email,
        u_assigned.branch as assigned_to_branch,
        u_assigned_by.full_name as assigned_by_name,
        st.tracking_status,
        st.last_contact_date,
        st.next_followup_date,
        st.notes as tracking_notes
    FROM projects p
    LEFT JOIN users u_encoded ON p.encoded_by = u_encoded.id
    LEFT JOIN users u_assigned ON p.assigned_to = u_assigned.id
    LEFT JOIN users u_assigned_by ON p.assigned_by = u_assigned_by.id
    LEFT JOIN sales_tracking st ON p.id = st.project_id
    $whereClause
    ORDER BY p.updated_at DESC
    LIMIT :size OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':size', $size, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$projects = $stmt->fetchAll();

// Ensure all projects have tracking_status for consistent frontend display
foreach ($projects as &$project) {
    if (!isset($project['tracking_status']) || empty($project['tracking_status'])) {
        $project['tracking_status'] = 'Not Started';
    }
}

jsonResponse([
    'projects' => $projects,
    'total' => $total,
    'page' => $page,
    'size' => $size,
    'pages' => ceil($total / $size)
]);