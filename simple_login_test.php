<?php
/**
 * Simple Login Test - Use existing admin and create session
 * This will test if authentication fixes the "No data" issue
 */

require_once 'config.php';
require_once 'api/db.php';

session_start();

echo "<style>
body { font-family: Arial; margin: 20px; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.info { color: #17a2b8; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
</style>";

echo "<h1>🚀 Simple Login Test for DATAS Dashboard</h1>";

try {
    $db = getDB();
    
    // Get the first superadmin user from the existing users
    $stmt = $db->prepare("SELECT * FROM users WHERE role = 'superadmin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✅ Found superadmin user: {$admin['full_name']} ({$admin['email']})</p>";
        
        // Create session for testing
        if (isset($_GET['login'])) {
            $_SESSION['user'] = [
                'id' => $admin['id'],
                'full_name' => $admin['full_name'],
                'email' => $admin['email'],
                'role' => $admin['role']
            ];
            
            echo "<p class='success'>✅ Session created! You are now logged in as: {$admin['full_name']}</p>";
            echo "<hr>";
            echo "<h2>🎯 Test Dashboard Now</h2>";
            echo "<p><a href='pages/reports.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a></p>";
            echo "<p><a href='test_dashboard_fix.php'>🔧 Run Full Diagnostic Again</a></p>";
            echo "<hr>";
            echo "<p class='info'>If the dashboard still shows 'No data', check:</p>";
            echo "<ul>";
            echo "<li>Browser developer console for JavaScript errors</li>";
            echo "<li>Network tab for 401 authentication errors</li>";
            echo "<li>Make sure this session persists in your browser</li>";
            echo "</ul>";
            
        } else {
            echo "<h2>🔐 Ready to Test Authentication Fix</h2>";
            echo "<p>Click the button below to create a login session:</p>";
            echo "<p><a href='?login=1' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>🚀 Login & Test Dashboard</a></p>";
            echo "<hr>";
            echo "<p class='warning'>ℹ️ <strong>What this will do:</strong></p>";
            echo "<ul>";
            echo "<li>Create a PHP session with admin user credentials</li>";
            echo "<li>Allow API endpoints to return data instead of 401 errors</li>";
            echo "<li>Fix the 'No data' issue if it's authentication-related</li>";
            echo "</ul>";
        }
        
    } else {
        echo "<p class='error'>❌ No superadmin user found in database</p>";
        echo "<p class='info'>Available users:</p>";
        
        $stmt = $db->query("SELECT id, full_name, email, role FROM users ORDER BY role");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>{$user['full_name']} ({$user['email']}) - Role: {$user['role']}</li>";
            }
            echo "</ul>";
            echo "<p>You can use the normal login page: <a href='pages/login.php'>Login Page</a></p>";
        } else {
            echo "<p class='error'>No users found in the system!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
}

// Show current session status
echo "<hr>";
echo "<h3>Current Session Status:</h3>";
if (!empty($_SESSION['user'])) {
    echo "<p class='success'>✅ Logged in as: {$_SESSION['user']['full_name']}</p>";
    echo "<p class='info'>Role: {$_SESSION['user']['role']}</p>";
    echo "<p class='info'>Email: {$_SESSION['user']['email']}</p>";
} else {
    echo "<p class='warning'>⚠️ No active session</p>";
}
?>