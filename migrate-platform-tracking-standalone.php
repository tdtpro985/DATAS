<?php
/**
 * Standalone Migration: Create platform_tracking table
 * Run this file via browser: http://datas.lan/migrate-platform-tracking-standalone.php
 * 
 * IMPORTANT: Edit database credentials below before running
 */

// Enable error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ============================================
// EDIT THESE DATABASE CREDENTIALS
// ============================================
$db_host = 'localhost';
$db_name = 'datas_db';
$db_user = 'root';
$db_pass = '';  // Enter your database password here
// ============================================

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Platform Tracking Migration (Standalone)</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #ff0; }
        .warning { color: #ffa500; }
        pre { background: #000; padding: 10px; border: 1px solid #333; }
        .box { border: 1px solid #333; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
<pre>
<?php

echo "=== Platform Tracking Table Migration (Standalone) ===\n\n";

echo "<span class='warning'>⚠ EDIT DB CREDENTIALS IN THIS FILE FIRST!</span>\n\n";

echo "<span class='info'>Database Configuration:</span>\n";
echo "Host: $db_host\n";
echo "Database: $db_name\n";
echo "User: $db_user\n";
echo "Password: " . ($db_pass ? str_repeat('*', strlen($db_pass)) : '(empty)') . "\n\n";

try {
    // Connect to database
    echo "<span class='info'>Connecting to database...</span>\n";
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<span class='success'>✓ Connected successfully!</span>\n\n";
    
    // Check if table exists
    echo "<span class='info'>Checking if table exists...</span>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'platform_tracking'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<span class='success'>✓ Table 'platform_tracking' already exists.</span>\n\n";
    } else {
        echo "<span class='info'>Creating 'platform_tracking' table...</span>\n";
        
        // Create table directly (no file dependency)
        $sql = "CREATE TABLE IF NOT EXISTS `platform_tracking` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `platform_id` INT(11) NOT NULL,
            `contacted` TINYINT(1) DEFAULT NULL,
            `quoted` TINYINT(1) DEFAULT NULL,
            `sales_qualified` TINYINT(1) DEFAULT NULL,
            `to_win` TINYINT(1) DEFAULT NULL,
            `wa_amount` DECIMAL(18,2) DEFAULT NULL,
            `remarks` TEXT DEFAULT NULL,
            `sales_rep_id` INT(11) DEFAULT NULL,
            `branch` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `platform_id` (`platform_id`),
            KEY `sales_rep_id` (`sales_rep_id`),
            CONSTRAINT `fk_platform_tracking_platform` FOREIGN KEY (`platform_id`) REFERENCES `platform_leads` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_platform_tracking_sales_rep` FOREIGN KEY (`sales_rep_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "<span class='success'>✓ Table created successfully!</span>\n\n";
    }
    
    // Verify structure
    echo "<span class='info'>Verifying table structure...</span>\n";
    $stmt = $pdo->query("DESCRIBE platform_tracking");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='success'>Table structure:</span>\n";
    echo "<div class='box'>";
    foreach ($columns as $col) {
        $null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $key = $col['Key'] ? " [{$col['Key']}]" : '';
        echo "{$col['Field']}: {$col['Type']} $null$key\n";
    }
    echo "</div>";
    
    // Check foreign keys
    echo "\n<span class='info'>Checking foreign keys...</span>\n";
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = '$db_name'
        AND TABLE_NAME = 'platform_tracking'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($fks) {
        echo "<span class='success'>Foreign keys:</span>\n";
        echo "<div class='box'>";
        foreach ($fks as $fk) {
            echo "{$fk['CONSTRAINT_NAME']}: {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        }
        echo "</div>";
    }
    
    echo "\n<span class='success'>========================================</span>\n";
    echo "<span class='success'>✓✓✓ MIGRATION COMPLETED SUCCESSFULLY! ✓✓✓</span>\n";
    echo "<span class='success'>========================================</span>\n\n";
    echo "<span class='info'>You can now:</span>\n";
    echo "1. Close this page\n";
    echo "2. Delete this migration file for security\n";
    echo "3. Test Platform Leads sales tracking feature\n";
    
} catch (PDOException $e) {
    echo "\n<span class='error'>========================================</span>\n";
    echo "<span class='error'>❌ DATABASE ERROR</span>\n";
    echo "<span class='error'>========================================</span>\n\n";
    echo "<span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span>\n\n";
    echo "<span class='warning'>Possible causes:</span>\n";
    echo "1. Wrong database credentials (edit this file)\n";
    echo "2. Database server is not running\n";
    echo "3. Database 'datas_db' doesn't exist\n";
    echo "4. User doesn't have permission\n";
    
} catch (Exception $e) {
    echo "\n<span class='error'>========================================</span>\n";
    echo "<span class='error'>❌ MIGRATION FAILED</span>\n";
    echo "<span class='error'>========================================</span>\n\n";
    echo "<span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

?>
</pre>
</body>
</html>
