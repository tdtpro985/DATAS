<?php
/* ============================================================
   GET /api/v1/activity-logs — Get activity logs with filters
   ============================================================
   Admin / superadmin only.
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireRole(['admin', 'superadmin']);

$db = getDB();

// ── GET: list activity logs ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = min(100, max(10, (int)($_GET['size'] ?? 50)));
        $offset = ($page - 1) * $pageSize;

        // Filters
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $actionType = isset($_GET['action_type']) ? trim($_GET['action_type']) : null;
        $actionTypePattern = isset($_GET['action_type_pattern']) ? trim($_GET['action_type_pattern']) : null;
        $entityType = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : null;
        $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
        $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;

        // Build query
        $where = ['1=1'];
        $params = [];

        if ($userId) {
            $where[] = 'al.user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        // Support pattern matching for tabs (e.g., "PROJECT_" matches PROJECT_CREATE, PROJECT_UPDATE, etc.)
        if ($actionTypePattern) {
            $where[] = 'al.action_type LIKE :action_type_pattern';
            $params[':action_type_pattern'] = $actionTypePattern . '%';
        } elseif ($actionType) {
            $where[] = 'al.action_type = :action_type';
            $params[':action_type'] = $actionType;
        }

        if ($entityType) {
            $where[] = 'al.entity_type = :entity_type';
            $params[':entity_type'] = $entityType;
        }

        if ($startDate) {
            $where[] = 'al.created_at >= :start_date';
            $params[':start_date'] = $startDate . ' 00:00:00';
        }

        if ($endDate) {
            $where[] = 'al.created_at <= :end_date';
            $params[':end_date'] = $endDate . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM activity_logs al WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // Get logs with user info
        $stmt = $db->prepare("
            SELECT 
                al.id,
                al.user_id,
                u.full_name as user_name,
                u.email as user_email,
                u.role as user_role,
                al.action_type,
                al.entity_type,
                al.entity_id,
                al.description,
                al.metadata,
                al.ip_address,
                al.user_agent,
                al.created_at
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE {$whereClause}
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $logs = $stmt->fetchAll();

        // Parse JSON metadata
        $logs = array_map(function($log) {
            return [
                'id' => (int)$log['id'],
                'user_id' => (int)$log['user_id'],
                'user_name' => $log['user_name'],
                'user_email' => $log['user_email'],
                'user_role' => $log['user_role'],
                'action_type' => $log['action_type'],
                'entity_type' => $log['entity_type'],
                'entity_id' => $log['entity_id'] ? (int)$log['entity_id'] : null,
                'description' => $log['description'],
                'metadata' => $log['metadata'] ? json_decode($log['metadata'], true) : null,
                'ip_address' => $log['ip_address'],
                'user_agent' => $log['user_agent'],
                'created_at' => $log['created_at']
            ];
        }, $logs);

        jsonResponse([
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize)
            ]
        ]);
    } catch (Exception $e) {
        error_log('GET /api/v1/activity-logs error: ' . $e->getMessage());
        jsonError('Failed to fetch activity logs', 500);
    }
}

jsonError('Method not allowed', 405);
