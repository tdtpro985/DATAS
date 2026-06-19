<?php
/**
 * Migration: Create platform_tracking table
 * Run this file via browser: http://datas.lan/migrate-platform-tracking.php
 */

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

// Set content type to HTML for browser viewing
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

echo "<span class='info'>Database Configuration:</span>\n";
echo "Host: " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";
echo "Password: " . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) : '(empty)') . "\n\n";

try {
    require_once __DIR__ . '/api/db.php';
    $db = getDB();
    
    echo "<span class='success'>✓ Database connection successful!</span>\n\n";
    
    // Check if table already exists
    echo "<span class='info'>Checking if table exists...</span>\n";
    $stmt = $db->query("SHOW TABLES LIKE 'platform_tracking'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<span class='success'>✓ Table 'platform_tracking' already exists. Skipping creation.</span>\n";
    } else {
        echo "<span class='info'>Creating 'platform_tracking' table...</span>\n";
        
        $sqlFile = __DIR__ . '/database/create-platform-tracking-table.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        $db->exec($sql);
        
        echo "<span class='success'>✓ Table 'platform_tracking' created successfully!</span>\n";
    }
    
    // Verify table structure
    echo "\n<span class='info'>Verifying table structure...</span>\n";
    $stmt = $db->query("DESCRIBE platform_tracking");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='success'>Columns in platform_tracking:</span>\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n<span class='success'>=== Migration completed successfully! ===</span>\n";
    echo "\n<span class='info'>You can now close this page.</span>\n";
    
} catch (Exception $e) {
    echo "\n<span class='error'>❌ Migration failed:</span>\n";
    echo "<span class='error'>" . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\n<span class='error'>Stack trace:</span>\n";
    echo "<span class='error'>" . htmlspecialchars($e->getTraceAsString()) . "</span>\n";
}
?>
</pre>
</body>
</html>
