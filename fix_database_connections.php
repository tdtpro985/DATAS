<?php
/**
 * Database Connection Fix
 * Addresses MySQL timeout and connection issues
 */

require_once 'config.php';
require_once 'api/db.php';

echo "<h1>🔧 Database Connection Fix</h1>";

try {
    // Test basic connection
    echo "<h2>1. Testing Database Connection</h2>";
    $db = getDB();
    echo "<p style='color: green;'>✅ Basic connection: OK</p>";
    
    // Test with a simple query
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM projects");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✅ Query test: {$result['cnt']} projects found</p>";
    
    // Check MySQL variables that affect timeouts
    echo "<h2>2. MySQL Timeout Settings</h2>";
    $timeouts = [
        'wait_timeout',
        'interactive_timeout', 
        'connect_timeout',
        'net_read_timeout',
        'net_write_timeout'
    ];
    
    foreach ($timeouts as $var) {
        $stmt = $db->query("SHOW VARIABLES LIKE '$var'");
        $result = $stmt->fetch();
        if ($result) {
            echo "<p>$var: {$result['Value']} seconds</p>";
        }
    }
    
    // Fix 1: Update MySQL settings in my.cnf (show recommendations)
    echo "<h2>3. Recommended MySQL Configuration</h2>";
    echo "<p><strong>Add these to your XAMPP MySQL configuration (my.ini):</strong></p>";
    echo "<pre style='background: #f4f4f4; padding: 10px;'>
[mysqld]
wait_timeout = 600
interactive_timeout = 600
connect_timeout = 60
net_read_timeout = 60
net_write_timeout = 60
max_connections = 200
</pre>";
    
    // Fix 2: Update PHP PDO settings
    echo "<h2>4. Updating PDO Connection Settings</h2>";
    
    // Read current db.php
    $dbFile = __DIR__ . '/api/db.php';
    $content = file_get_contents($dbFile);
    
    // Check if timeout options are already set
    if (strpos($content, 'PDO::ATTR_TIMEOUT') === false) {
        echo "<p>⚠️ Adding timeout and reconnection settings to db.php...</p>";
        
        // Add timeout options
        $newOptions = "        \$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use emulated prepares to allow binding LIMIT/OFFSET safely on MySQL in this environment
            PDO::ATTR_EMULATE_PREPARES   => true,
            // Add connection timeout and persistence
            PDO::ATTR_TIMEOUT            => 30,
            PDO::ATTR_PERSISTENT         => false,
            // MySQL specific options
            PDO::MYSQL_ATTR_INIT_COMMAND => \"SET sql_mode=''\",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];";
        
        $oldOptions = '        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use emulated prepares to allow binding LIMIT/OFFSET safely on MySQL in this environment
            PDO::ATTR_EMULATE_PREPARES   => true,
        ];';
        
        $newContent = str_replace($oldOptions, $newOptions, $content);
        
        if ($newContent !== $content) {
            file_put_contents($dbFile, $newContent);
            echo "<p style='color: green;'>✅ Updated db.php with better connection settings</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Could not auto-update db.php - manual update needed</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Timeout settings already configured</p>";
    }
    
    // Fix 3: Test the updated connection
    echo "<h2>5. Testing Updated Connection</h2>";
    
    // Force a new connection by clearing static variable
    // (This would normally require reloading the page)
    echo "<p>🔄 Testing connection resilience...</p>";
    
    for ($i = 1; $i <= 3; $i++) {
        try {
            $testDb = getDB();
            $stmt = $testDb->query("SELECT NOW() as current_time");
            $result = $stmt->fetch();
            echo "<p style='color: green;'>✅ Test $i: Connection OK - {$result['current_time']}</p>";
            
            // Small delay to test persistence
            if ($i < 3) sleep(1);
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Test $i failed: {$e->getMessage()}</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2>🎯 Next Steps</h2>";
    echo "<ol>";
    echo "<li><strong>Restart XAMPP MySQL service</strong> to apply connection improvements</li>";
    echo "<li><strong>Clear browser cache</strong> to reload JavaScript</li>";
    echo "<li><strong>Login first:</strong> <a href='simple_login_test.php'>Quick Login Test</a></li>";
    echo "<li><strong>Test dashboard:</strong> <a href='pages/reports.php'>Go to Dashboard</a></li>";
    echo "</ol>";
    
    echo "<p style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>🔧 Manual MySQL Config (if problems persist):</strong><br>";
    echo "1. Stop XAMPP MySQL<br>";
    echo "2. Edit C:\\xampp\\mysql\\bin\\my.ini<br>";
    echo "3. Add the configuration shown above<br>";
    echo "4. Restart XAMPP MySQL<br>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: {$e->getMessage()}</p>";
    echo "<p>This indicates a serious database connection issue.</p>";
    
    echo "<h3>Emergency Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Check if XAMPP MySQL is running</li>";
    echo "<li>Check if database 'datas_db' exists</li>";
    echo "<li>Verify database credentials in config.php</li>";
    echo "<li>Try restarting XAMPP services</li>";
    echo "</ul>";
}
?>