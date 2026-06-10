<?php
/* ============================================================
   GET /api/v1/projects/unassigned
   ============================================================
   Returns all unassigned projects (assigned_to IS NULL).
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

$db = getDB();

// Build WHERE clause - check if project has NO assignment (assigned_to IS NULL) and is not archived
$where = ['p.assigned_to IS NULL', 'p.archived_at IS NULL', "(p.is_actual_project IS NULL OR p.is_actual_project != 'no')"]; // No assignment and not archived and not illegitimate
$params = [];

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
    $where[] = '(p.contractor_name LIKE :search OR p.project_name LIKE :search OR p.project_id LIKE :search OR p.contractor_id LIKE :search)';
    $params[':search'] = '%' . $search . '%';
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

// Get paginated results with sales tracking data (if columns exist)
$stmt = $db->prepare("
    SELECT 
        p.*,
        u.full_name as encoded_by_name,
        st.tracking_status,
        st.contacted,
        st.quoted,
        st.sales_qualified,
        st.to_win,
        st.wa_amount,
        st.sales_rep_id,
        st.branch,
        st.notes as sales_tracking_notes
    FROM projects p
    LEFT JOIN users u ON p.encoded_by = u.id
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

// Process projects to include default tracking status since data comes from JOIN
foreach ($projects as &$project) {
    // Set tracking status from JOIN data or default to "Not Started"
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
