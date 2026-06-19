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
        
        // Read SQL file
        $sqlFile = __DIR__ . '/database/create-platform-tracking-table.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Execute with error handling
        try {
            $db->exec($sql);
            echo "<span class='success'>✓ Table created successfully!</span>\n";
        } catch (PDOException $e) {
            // Check if error is due to existing foreign key constraint
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<span class='success'>✓ Table already exists (constraint check)</span>\n";
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
