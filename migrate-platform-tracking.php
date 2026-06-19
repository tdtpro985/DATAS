<?php
/**
 * Migration: Create platform_tracking table
 * Run this file via browser: http://datas.lan/migrate-platform-tracking.php
 */

// Set timeouts
set_time_limit(60);
ini_set('max_execution_time', 60);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Platform Tracking Migration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #ff0; }
        pre { background: #000; padding: 10px; border: 1px solid #333; }
    </style>
</head>
<body>
<pre>
<?php

echo "=== Platform Tracking Table Migration ===\n";
echo "Starting migration...\n\n";

try {
    $db = getDB();
    
    echo "<span class='success'>✓ Database connected</span>\n\n";
    
    // Check if table already exists
    echo "<span class='info'>Checking if table exists...</span>\n";
    flush();
    ob_flush();
    
    $stmt = $db->query("SHOW TABLES LIKE 'platform_tracking'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<span class='success'>✓ Table 'platform_tracking' already exists. Skipping creation.</span>\n";
    } else {
        echo "<span class='info'>Creating 'platform_tracking' table...</span>\n";
        flush();
        ob_flush();
        
        // Embedded SQL (no file dependency)
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
            KEY `sales_rep_id` (`sales_rep_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // Execute table creation
        try {
            $db->exec($sql);
            echo "<span class='success'>✓ Table created successfully!</span>\n";
            flush();
            ob_flush();
            
            // Add foreign keys separately to handle conflicts
            echo "<span class='info'>Adding foreign key constraints...</span>\n";
            flush();
            ob_flush();
            
            try {
                $db->exec("ALTER TABLE `platform_tracking` 
                    ADD CONSTRAINT `fk_plat_track_platform` 
                    FOREIGN KEY (`platform_id`) REFERENCES `platform_leads` (`id`) ON DELETE CASCADE");
                echo "<span class='success'>✓ Platform foreign key added</span>\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<span class='info'>- Platform foreign key already exists</span>\n";
                } else {
                    throw $e;
                }
            }
            
            try {
                $db->exec("ALTER TABLE `platform_tracking` 
                    ADD CONSTRAINT `fk_plat_track_sales_rep` 
                    FOREIGN KEY (`sales_rep_id`) REFERENCES `users` (`id`) ON DELETE SET NULL");
                echo "<span class='success'>✓ Sales rep foreign key added</span>\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<span class='info'>- Sales rep foreign key already exists</span>\n";
                } else {
                    throw $e;
                }
            }
            
        } catch (PDOException $e) {
            // Check if error is due to existing table
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<span class='success'>✓ Table already exists</span>\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Verify table structure
    echo "\n<span class='info'>Verifying table structure...</span>\n";
    flush();
    ob_flush();
    
    $stmt = $db->query("DESCRIBE platform_tracking");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='success'>Columns in platform_tracking:</span>\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n<span class='success'>=== Migration completed successfully! ===</span>\n";
    echo "\n<span class='info'>You can now close this page and delete this file.</span>\n";
    
} catch (Exception $e) {
    echo "\n<span class='error'>❌ Migration failed:</span>\n";
    echo "<span class='error'>" . htmlspecialchars($e->getMessage()) . "</span>\n\n";
    
    // Show more debug info
    echo "<span class='info'>Debug Info:</span>\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
</pre>
</body>
</html>
