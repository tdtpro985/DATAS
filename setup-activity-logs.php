<?php
/* ============================================================
   setup-activity-logs.php — Create activity_logs table
   ============================================================
   Run this once via browser to create the activity logs table
   URL: http://your-domain.com/setup-activity-logs.php
   ============================================================ */

// Allow direct access (internal system)
// No authentication required for setup script

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "===========================================\n";
echo "  ACTIVITY LOGS TABLE SETUP\n";
echo "===========================================\n\n";

require_once __DIR__ . '/api/db.php';

try {
    $db = getDB();
    echo "[INFO] Database connection established.\n\n";

    // Check if table already exists
    echo "[STEP 1] Checking if activity_logs table exists...\n";
    $checkStmt = $db->query("SHOW TABLES LIKE 'activity_logs'");
    $tableExists = $checkStmt->rowCount() > 0;

    if ($tableExists) {
        echo "[SUCCESS] Table 'activity_logs' already exists!\n\n";
    } else {
        echo "[INFO] Table does not exist. Creating...\n\n";
        
        // Create the table
        echo "[STEP 2] Creating activity_logs table...\n";
        
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `activity_logs` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NOT NULL,
          `action_type` VARCHAR(50) NOT NULL,
          `entity_type` VARCHAR(50) NOT NULL COMMENT 'project, platform, user, etc.',
          `entity_id` INT UNSIGNED DEFAULT NULL,
          `description` TEXT NOT NULL,
          `metadata` JSON DEFAULT NULL COMMENT 'Additional data about the activity',
          `ip_address` VARCHAR(45) DEFAULT NULL,
          `user_agent` VARCHAR(255) DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_user_id` (`user_id`),
          INDEX `idx_action_type` (`action_type`),
          INDEX `idx_entity_type` (`entity_type`),
          INDEX `idx_created_at` (`created_at`),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $db->exec($createTableSQL);
        echo "[SUCCESS] Table 'activity_logs' created successfully!\n\n";
    }

    // Verify the table structure
    echo "[STEP 3] Verifying table structure...\n";
    $columns = $db->query("DESCRIBE activity_logs")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "[INFO] Table columns:\n";
    foreach ($columns as $column) {
        echo "        - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";

    // Count existing logs
    $countStmt = $db->query("SELECT COUNT(*) as total FROM activity_logs");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "[INFO] Current activity logs count: {$count}\n\n";

    // Summary
    echo "===========================================\n";
    echo "  SETUP COMPLETED SUCCESSFULLY!\n";
    echo "===========================================\n\n";
    
    echo "[SUMMARY]\n";
    echo "  • Table: activity_logs\n";
    echo "  • Status: Ready\n";
    echo "  • Existing Logs: {$count}\n\n";
    
    echo "[NEXT STEPS]\n";
    echo "  1. Access Activity Logs at: /activity-logs\n";
    echo "  2. Perform actions (login, create project, etc.)\n";
    echo "  3. Check this page to see logged activities\n\n";
    
    echo "[SECURITY]\n";
    echo "  ⚠ Remember to delete this setup file after running!\n";
    echo "     File: " . __FILE__ . "\n\n";

} catch (Exception $e) {
    echo "[ERROR] Setup failed!\n";
    echo "        " . $e->getMessage() . "\n";
    echo "        Stack trace:\n";
    echo "        " . $e->getTraceAsString() . "\n";
    http_response_code(500);
}

echo "===========================================\n";
echo "</pre>";
?>