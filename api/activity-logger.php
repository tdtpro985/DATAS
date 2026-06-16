<?php
/* ============================================================
   activity-logger.php — Helper functions for logging activities
   ============================================================ */

/**
 * Log an activity to the activity_logs table
 * 
 * @param PDO $db Database connection
 * @param int $userId User ID performing the action
 * @param string $actionType Action type (e.g., 'USER_LOGIN', 'PROJECT_CREATE')
 * @param string $entityType Entity type (e.g., 'project', 'platform', 'user')
 * @param int|null $entityId Entity ID (optional)
 * @param string $description Human-readable description
 * @param array|null $metadata Additional metadata (optional)
 * @return bool Success status
 */
function logActivity($db, $userId, $actionType, $entityType, $entityId, $description, $metadata = null) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (user_id, action_type, entity_type, entity_id, description, metadata, ip_address, user_agent)
            VALUES 
            (:user_id, :action_type, :entity_type, :entity_id, :description, :metadata, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':action_type' => $actionType,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':description' => $description,
            ':metadata' => $metadata ? json_encode($metadata) : null,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('Activity logging failed: ' . $e->getMessage());
        // Don't throw - activity logging should not break the main flow
        return false;
    }
}

/**
 * Activity Type Constants
 */
class ActivityType {
    // User activities
    const USER_LOGIN = 'USER_LOGIN';
    const USER_LOGOUT = 'USER_LOGOUT';
    const USER_CREATE = 'USER_CREATE';
    const USER_UPDATE = 'USER_UPDATE';
    const USER_DELETE = 'USER_DELETE';
    
    // Project activities
    const PROJECT_CREATE = 'PROJECT_CREATE';
    const PROJECT_UPDATE = 'PROJECT_UPDATE';
    const PROJECT_DELETE = 'PROJECT_DELETE';
    const PROJECT_ASSIGN = 'PROJECT_ASSIGN';
    const PROJECT_UNASSIGN = 'PROJECT_UNASSIGN';
    const PROJECT_ARCHIVE = 'PROJECT_ARCHIVE';
    const PROJECT_MARK_ILLEGITIMATE = 'PROJECT_MARK_ILLEGITIMATE';
    const PROJECT_BULK_ASSIGN = 'PROJECT_BULK_ASSIGN';
    const PROJECT_BULK_UNASSIGN = 'PROJECT_BULK_UNASSIGN';
    
    // Platform activities
    const PLATFORM_CREATE = 'PLATFORM_CREATE';
    const PLATFORM_UPDATE = 'PLATFORM_UPDATE';
    const PLATFORM_DELETE = 'PLATFORM_DELETE';
    const PLATFORM_ARCHIVE = 'PLATFORM_ARCHIVE';
    
    // Sales tracking activities
    const SALES_TRACKING_START = 'SALES_TRACKING_START';
    const SALES_TRACKING_UPDATE = 'SALES_TRACKING_UPDATE';
    const SALES_TRACKING_COMPLETE = 'SALES_TRACKING_COMPLETE';
    
    // Data activities
    const EXPORT_DATA = 'EXPORT_DATA';
    const IMPORT_DATA = 'IMPORT_DATA';
}

/**
 * Entity Type Constants
 */
class EntityType {
    const PROJECT = 'project';
    const PLATFORM = 'platform';
    const USER = 'user';
    const SALES_TRACKING = 'sales_tracking';
    const EXPORT = 'export';
}
