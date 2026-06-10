<?php
/* ============================================================
   GET /api/v1/projects/illegitimate — Get illegitimate projects
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

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 20;
$offset = ($page - 1) * $size;

$search = $_GET['search'] ?? '';
$region = $_GET['region'] ?? '';
$status = $_GET['status'] ?? '';
$source = $_GET['source'] ?? '';
$sort = $_GET['sort'] ?? 'desc';

try {
    $db = getDB();
    
    // Build WHERE clause
    $where = ["p.is_actual_project = 'no'"];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(p.contractor_name LIKE :search OR p.project_name LIKE :search OR p.region LIKE :search OR p.project_id LIKE :search OR p.contractor_id LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($region)) {
        $where[] = "p.region = :region";
        $params[':region'] = $region;
    }
    
    if (!empty($status)) {
        $where[] = "p.status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($source)) {
        $where[] = "p.source = :source";
        $params[':source'] = $source;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Order by publication_date
    $orderBy = $sort === 'asc' ? 'ASC' : 'DESC';
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM projects p WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get projects with sales tracking status
    $stmt = $db->prepare("
        SELECT 
            p.*,
            u.full_name as assigned_to_name,
            st.tracking_status as sales_tracking_status
        FROM projects p
        LEFT JOIN users u ON p.assigned_to = u.id
        LEFT JOIN sales_tracking st ON p.id = st.project_id
        WHERE $whereClause
        ORDER BY p.publication_date $orderBy, p.created_at $orderBy
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $size, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add tracking_status field for compatibility
    foreach ($projects as &$project) {
        $project['tracking_status'] = $project['sales_tracking_status'] ?? 'Not Started';
    }
    
    jsonResponse([
        'projects' => $projects,
        'total' => $total,
        'page' => $page,
        'size' => $size,
        'pages' => ceil($total / $size)
    ]);
    
} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
