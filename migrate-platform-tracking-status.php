<?php
/**
 * Migration: Add sales_tracking_status to platform_leads
 * Run via: http://datas.lan/migrate-platform-tracking-status.php
 */

set_time_limit(60);
ini_set('max_execution_time', 60);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Platform Tracking Status Migration</title>
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

echo "=== Platform Tracking Status Migration ===\n";
echo "Starting migration...\n\n";

try {
    $db = getDB();
    
    echo "<span class='success'>✓ Database connected</span>\n\n";
    
    // Check if column already exists
    echo "<span class='info'>Checking if column exists...</span>\n";
    $stmt = $db->query("SHOW COLUMNS FROM platform_leads LIKE 'sales_tracking_status'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<span class='success'>✓ Column 'sales_tracking_status' already exists. Skipping.</span>\n";
    } else {
        echo "<span class='info'>Adding 'sales_tracking_status' column...</span>\n";
        flush();
        ob_flush();
        
        $db->exec("ALTER TABLE `platform_leads` 
            ADD COLUMN `sales_tracking_status` VARCHAR(50) DEFAULT NULL AFTER `materials_quantity`,
            ADD KEY `idx_sales_tracking_status` (`sales_tracking_status`)");
        
        echo "<span class='success'>✓ Column added successfully!</span>\n";
    }
    
    // Update existing records
    echo "\n<span class='info'>Updating existing platform leads...</span>\n";
    flush();
    ob_flush();
    
    $stmt = $db->exec("
        UPDATE `platform_leads` pl
        LEFT JOIN `platform_tracking` pt ON pl.id = pt.platform_id
        SET pl.sales_tracking_status = 
            CASE
                WHEN pt.to_win = 1 THEN 'To Win'
                WHEN pt.sales_qualified = 1 THEN 'Sales Qualified'
                WHEN pt.quoted = 1 THEN 'Quoted'
                WHEN pt.contacted = 1 THEN 'Contacted'
                ELSE NULL
            END
        WHERE pt.id IS NOT NULL
    ");
    
    echo "<span class='success'>✓ Updated $stmt records</span>\n";
    
    // Verify
    echo "\n<span class='info'>Verifying...</span>\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM platform_leads WHERE sales_tracking_status IS NOT NULL");
    $result = $stmt->fetch();
    echo "<span class='success'>Platform leads with status: {$result['count']}</span>\n";
    
    echo "\n<span class='success'>=== Migration completed successfully! ===</span>\n";
    echo "\n<span class='info'>Delete this file after checking.</span>\n";
    
} catch (Exception $e) {
    echo "\n<span class='error'>❌ Migration failed:</span>\n";
    echo "<span class='error'>" . htmlspecialchars($e->getMessage()) . "</span>\n\n";
    echo "<span class='info'>Debug Info:</span>\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
</pre>
</body>
</html>
