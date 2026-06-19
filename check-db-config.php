<?php
/**
 * Database Configuration Checker
 * This will show if database connection is working without exposing password
 * 
 * DELETE THIS FILE AFTER CHECKING!
 */

// Enable errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>DB Config Checker</title>
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

echo "=== Database Configuration Check ===\n\n";

try {
    require_once __DIR__ . '/config.php';
    
    echo "<span class='info'>Configuration loaded:</span>\n";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
    echo "DB_PASS: " . (defined('DB_PASS') ? (DB_PASS ? '***SET***' : 'EMPTY') : 'NOT DEFINED') . "\n";
    echo "DB_CHARSET: " . (defined('DB_CHARSET') ? DB_CHARSET : 'NOT DEFINED') . "\n\n";
    
    echo "<span class='info'>Testing connection...</span>\n";
    
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<span class='success'>✓ Database connection successful!</span>\n\n";
    
    // Test query
    echo "<span class='info'>Testing query...</span>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM platform_leads");
    $result = $stmt->fetch();
    echo "<span class='success'>✓ Query successful! Found {$result['count']} platform leads.</span>\n\n";
    
    // Check if platform_tracking exists
    echo "<span class='info'>Checking platform_tracking table...</span>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'platform_tracking'");
    if ($stmt->fetch()) {
        echo "<span class='success'>✓ platform_tracking table exists</span>\n";
    } else {
        echo "<span class='error'>✗ platform_tracking table NOT found - needs migration</span>\n";
    }
    
} catch (PDOException $e) {
    echo "<span class='error'>✗ Database connection failed!</span>\n\n";
    echo "<span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span>\n\n";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<span class='info'>This is usually caused by:</span>\n";
        echo "1. Wrong username or password in config.php\n";
        echo "2. Database user doesn't have permission\n";
        echo "3. MySQL server doesn't allow connections from this host\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

?>

<span class='info'>
⚠️ DELETE THIS FILE AFTER CHECKING FOR SECURITY!
</span>
</pre>
</body>
</html>
