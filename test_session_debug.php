<?php
/**
 * Session Debug Test
 * This script tests session functionality and API authentication
 */

session_start();

echo "<h2>🔍 Session Debug Information</h2>";

// Display session information
echo "<h3>Session Status</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . " (1=disabled, 2=none, 3=active)\n";
echo "Session Cookie Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "</pre>";

// Display session data
echo "<h3>Session Data</h3>";
echo "<pre>";
if (empty($_SESSION)) {
    echo "❌ No session data found\n";
} else {
    echo "✅ Session data exists:\n";
    print_r($_SESSION);
}
echo "</pre>";

// Check if user is logged in
echo "<h3>Authentication Status</h3>";
if (empty($_SESSION['user'])) {
    echo "<p style='color: red;'>❌ User not logged in</p>";
    echo "<p><a href='pages/login.php'>Login here</a></p>";
} else {
    echo "<p style='color: green;'>✅ User logged in:</p>";
    echo "<pre>";
    print_r($_SESSION['user']);
    echo "</pre>";
    
    // Test API call
    echo "<h3>API Test</h3>";
    echo "<p>Testing sales-reps API call...</p>";
    
    $url = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/api/v1/users/sales-reps';
    echo "<p>URL: <code>$url</code></p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>❌ cURL Error: $error</p>";
    } else {
        echo "<p>HTTP Status: <strong>$httpCode</strong></p>";
        echo "<p>Response:</p>";
        echo "<pre style='background: #f5f5f5; padding: 1rem; max-height: 400px; overflow-y: auto;'>";
        echo htmlspecialchars($response);
        echo "</pre>";
        
        // Try to decode JSON
        $jsonData = json_decode($response, true);
        if ($jsonData) {
            echo "<p>Parsed JSON:</p>";
            echo "<pre>";
            print_r($jsonData);
            echo "</pre>";
        }
    }
}

// Display cookie information
echo "<h3>Cookie Information</h3>";
echo "<pre>";
if (empty($_COOKIE)) {
    echo "❌ No cookies found\n";
} else {
    echo "Cookies:\n";
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'PHPSESSID') !== false) {
            echo "✅ $name = $value\n";
        } else {
            echo "$name = " . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . "\n";
        }
    }
}
echo "</pre>";

// Display server information
echo "<h3>Server Information</h3>";
echo "<pre>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "</pre>";

echo "<hr>";
echo "<p><a href='pages/projects-management.php'>← Back to Project Management</a></p>";
?>