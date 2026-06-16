<?php
/* ============================================================
   migrate-activity-logs.php — Create activity_logs table
   ============================================================
   Run this once via browser to create the activity logs table
   URL: https://your-domain.com/migrate-activity-logs.php
   ============================================================ */

// Prevent running in production accidentally
$allowedIPs = ['127.0.0.1', '::1', 'localhost'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

// Security check - only allow from localhost OR with special token
$token = $_GET['token'] ?? '';
$expectedToken = 'TDT2026MIGRATE'; // Change this to something secure

if (!in_array($clientIP, $allowedIPs) && $token !== $expectedToken) {
    http_response_code(403);
    die('Access denied. Run this script from localhost or provide the correct token parameter.');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "===========================================\n";
echo "  ACTIVITY LOGS TABLE MIGRATION\n";
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
        echo "[WARNING] Table 'activity_logs' already exists!\n";
        echo "         Do you want to skip or recreate it?\n";
        echo "         To recreate, add '&recreate=yes' to the URL.\n\n";
        
        if (($_GET['recreate'] ?? '') === 'yes') {
            echo "[STEP 2] Dropping existing table...\n";
            $db->exec("DROP TABLE IF EXISTS activity_logs");
            echo "[SUCCESS] Table dropped.\n\n";
        } else {
            echo "[SKIPPED] Migration skipped. Table already exists.\n";
            echo "===========================================\n";
            echo "</pre>";
            exit;
        }
    } else {
        echo "[INFO] Table does not exist. Creating...\n\n";
    }

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

    // Verify the table structure
    echo "[STEP 3] Verifying table structure...\n";
    $columns = $db->query("DESCRIBE activity_logs")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "[INFO] Table columns:\n";
    foreach ($columns as $column) {
        echo "        - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";

    // Insert a test log entry (optional)
    echo "[STEP 4] Inserting test log entry...\n";
    
    // Get first user ID for test
    $userStmt = $db->query("SELECT id FROM users LIMIT 1");
    $userId = $userStmt->fetch(PDO::FETCH_ASSOC)['id'] ?? 1;
    
    $testLogSQL = "
    INSERT INTO activity_logs 
    (user_id, action_type, entity_type, description, ip_address, created_at)
    VALUES 
    (:user_id, 'SYSTEM_MIGRATION', 'system', 'Activity logs table created via migration script', :ip_address, NOW())
    ";
    
    $stmt = $db->prepare($testLogSQL);
    $stmt->execute([
        ':user_id' => $userId,
        ':ip_address' => $clientIP
    ]);
    
    echo "[SUCCESS] Test log entry inserted (ID: " . $db->lastInsertId() . ")\n\n";

    // Summary
    echo "===========================================\n";
    echo "  MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "===========================================\n\n";
    
    echo "[SUMMARY]\n";
    echo "  • Table: activity_logs\n";
    echo "  • Status: Created\n";
    echo "  • Test Entry: Inserted\n";
    echo "  • Ready: YES\n\n";
    
    echo "[NEXT STEPS]\n";
    echo "  1. Access Activity Logs at: /activity-logs\n";
    echo "  2. Integrate logActivity() function in your code\n";
    echo "  3. Start tracking system activities\n\n";
    
    echo "[SECURITY]\n";
    echo "  ⚠ Remember to delete this migration file after running!\n";
    echo "     File: " . __FILE__ . "\n\n";

} catch (Exception $e) {
    echo "[ERROR] Migration failed!\n";
    echo "        " . $e->getMessage() . "\n";
    echo "        Stack trace:\n";
    echo "        " . $e->getTraceAsString() . "\n";
    http_response_code(500);
}

echo "===========================================\n";
echo "</pre>";
?>
