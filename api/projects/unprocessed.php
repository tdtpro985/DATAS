<?php
/* ============================================================
   GET /api/v1/projects/unprocessed
   ============================================================
   Returns all unprocessed projects (is_processed = 0).
   These are projects without sales tracking yet.
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
$assignedOnly = qp('assigned_only', ''); // Filter for assigned unprocessed projects

$db = getDB();

// Build WHERE clause
// LOGIC: "Unprocessed" = Projects with tracking_status = 'Not Started' (includes assigned projects without tracking data)
$where = [];
$params = [];

// Core logic: Show all projects where tracking_status is 'Not Started' or doesn't exist
$where[] = 'p.archived_at IS NULL';      // Not archived
$where[] = '(st.tracking_status IS NULL OR st.tracking_status = "Not Started")';  // Not Started or no tracking

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

// Get paginated results
$stmt = $db->prepare("
    SELECT 
        p.*,
        u_encoded.full_name as encoded_by_name,
        u_assigned.full_name as assigned_to_name,
        u_assigned.email as assigned_to_email,
        u_assigned.branch as assigned_to_branch,
        u_assigned_by.full_name as assigned_by_name,
        st.tracking_status,
        st.contacted,
        st.quoted,
        st.sales_qualified,
        st.to_win,
        st.wa_amount,
        st.sales_rep_id,
        st.branch as tracking_branch,
        st.notes as sales_tracking_notes
    FROM projects p
    LEFT JOIN users u_encoded ON p.encoded_by = u_encoded.id
    LEFT JOIN users u_assigned ON p.assigned_to = u_assigned.id
    LEFT JOIN users u_assigned_by ON p.assigned_by = u_assigned_by.id
    LEFT JOIN sales_tracking st ON p.id = st.project_id
    $whereClause
    ORDER BY p.created_at DESC
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
