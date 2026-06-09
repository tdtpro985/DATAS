<?php
/**
 * Archived Projects API
 * 
 * GET - List archived projects (admin/superadmin only)
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$user = requireAuth();
$userRole = $user['role'] ?? '';

// Only admins and superadmins can view archived projects
if (!in_array($userRole, ['admin', 'superadmin'], true)) {
    jsonError('Access denied', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

handleGetArchived();

/**
 * Get archived projects with pagination and filtering
 */
function handleGetArchived() {
    try {
        $pdo = getDB();
        
        // Get pagination parameters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $size = min(100, max(1, (int)($_GET['size'] ?? 20)));
        $offset = ($page - 1) * $size;
        
        // Get filtering parameters
        $search = trim($_GET['search'] ?? '');
        $region = trim($_GET['region'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $source = trim($_GET['source'] ?? '');
        
        // Build WHERE clause for archived projects
        $where = ['p.archived_at IS NOT NULL'];
        $params = [];
        
        if ($search) {
            $where[] = '(p.contractor_name LIKE :search OR p.project_name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($region && $region !== 'all') {
            $where[] = '(p.region = :region OR p.contract_region = :region OR p.project_region = :region)';
            $params[':region'] = $region;
        }
        
        if ($status && $status !== 'all') {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }
        
        if ($source && $source !== 'all') {
            $where[] = 'p.source = :source';
            $params[':source'] = $source;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as cnt 
            FROM projects p
            $whereClause
        ");
        $countStmt->execute($params);
        $countRow = $countStmt->fetch();
        $total = (int)$countRow['cnt'];
        
        // Get paginated results
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                u_archived.full_name as archived_by_name,
                u_assigned.full_name as assigned_to_name
            FROM projects p
            LEFT JOIN users u_archived ON p.archived_by = u_archived.id
            LEFT JOIN users u_assigned ON p.assigned_to = u_assigned.id
            $whereClause
            ORDER BY p.archived_at DESC
            LIMIT :size OFFSET :offset
        ");
        
        // Bind pagination params
        $stmt->bindValue(':size', $size, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // Bind filter params
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates and values
        foreach ($projects as &$project) {
            $project['project_value'] = (float)$project['project_value'];
            $project['archived_at'] = $project['archived_at'] ? date('Y-m-d H:i:s', strtotime($project['archived_at'])) : null;
            $project['created_at'] = date('Y-m-d H:i:s', strtotime($project['created_at']));
            $project['updated_at'] = date('Y-m-d H:i:s', strtotime($project['updated_at']));
        }
        
        echo json_encode([
            'success' => true,
            'projects' => $projects,
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'total_pages' => ceil($total / $size)
        ]);
        
    } catch (Exception $e) {
        error_log('Archived projects API error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        jsonError('Internal server error: ' . $e->getMessage(), 500);
    }
}
?>