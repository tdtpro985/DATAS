<?php
/**
 * Quick Login Test - Create a temporary admin user and auto-login
 * Run this once to test if authentication fixes the dashboard
 */

require_once 'config.php';
require_once 'api/db.php';
require_once 'api/helpers.php';

session_start();

echo "<h1>🔐 Quick Login Test</h1>";

try {
    $db = getDB();
    
    // Check if users table exists
    $tables = $db->query("SHOW TABLES LIKE 'users'")->fetchAll();
    
    if (empty($tables)) {
        echo "<h2>Creating users table...</h2>";
        
        // Create users table
        $createTable = "
        CREATE TABLE IF NOT EXISTS users (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL UNIQUE,
            password varchar(255) NOT NULL,
            role enum('admin','encoder','sales_rep','viewer') NOT NULL DEFAULT 'viewer',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $db->exec($createTable);
        echo "<p>✅ Users table created</p>";
    } else {
        echo "<p>✅ Users table exists</p>";
    }
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE role = 'superadmin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "<h2>Creating admin user...</h2>";
        
        // Create default admin user  
        $password = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, password_hash, role) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute(['Test Admin', 'test@admin.com', $password, 'superadmin']);
        
        echo "<p>✅ Admin user created</p>";
        echo "<p><strong>Login credentials:</strong></p>";
        echo "<ul>";
        echo "<li>Email: test@admin.com</li>";
        echo "<li>Password: admin123</li>";
        echo "</ul>";
        
        // Get the new user
        $admin = [
            'id' => $db->lastInsertId(),
            'full_name' => 'Test Admin',
            'email' => 'test@admin.com',
            'role' => 'superadmin'
        ];
    } else {
        echo "<p>✅ Admin user exists: {$admin['full_name']} ({$admin['email']})</p>";
    }
    
    // Auto-login for testing
    if (isset($_GET['auto_login'])) {
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'full_name' => $admin['full_name'],
            'email' => $admin['email'],
            'role' => $admin['role']
        ];
        
        echo "<p style='color: green; font-weight: bold;'>✅ Auto-logged in as: {$admin['full_name']}</p>";
        echo "<p><a href='pages/reports.php'>🎯 Go to Dashboard</a></p>";
        echo "<p><a href='test_dashboard_fix.php'>🔧 Run Full Diagnostic</a></p>";
        
    } else {
        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li><a href='?auto_login=1'>🚀 Auto-Login for Testing</a></li>";
        echo "<li><a href='pages/login.php'>🔐 Use Normal Login Page</a></li>";
        echo "<li><a href='pages/reports.php'>📊 Go to Dashboard (after login)</a></li>";
        echo "</ol>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>