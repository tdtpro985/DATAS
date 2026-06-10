<?php
/**
 * Sales Tracking API Test Script
 * Tests the exact API endpoint that the frontend calls
 * 
 * Usage:
 * 1. Access via browser: http://yourdomain.com/check-sales-tracking-api.php?project_id=5
 * 2. Or via curl: curl "http://yourdomain.com/check-sales-tracking-api.php?project_id=5"
 */

// Set headers for output
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>";
echo "<html><head><title>Sales Tracking API Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .info { color: #2196F3; }
    .warning { color: #ff9800; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .step { margin: 20px 0; padding: 15px; border-left: 4px solid #2196F3; background: #e3f2fd; }
</style></head><body>";

echo "<div class='container'>";
echo "<h2>🔍 Sales Tracking API Test</h2>";
echo "<hr>";

// Get project ID from query string
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

if (!$projectId) {
    echo "<p class='error'>❌ No project_id provided</p>";
    echo "<p>Usage: <code>?project_id=5</code></p>";
    echo "<p>Example: <a href='?project_id=5'>Test with Project ID 5</a></p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='info'>Testing Project ID: <strong>$projectId</strong></p>";
echo "<hr>";

// Test 1: Check if we have a valid session
echo "<div class='step'>";
echo "<h3>Step 1: Session Check</h3>";
session_start();

if (isset($_SESSION['user'])) {
    echo "<p class='success'>✅ Session active</p>";
    echo "<table>";
    echo "<tr><th>User ID</th><td>" . htmlspecialchars($_SESSION['user']['id'] ?? 'N/A') . "</td></tr>";
    echo "<tr><th>Username</th><td>" . htmlspecialchars($_SESSION['user']['username'] ?? 'N/A') . "</td></tr>";
    echo "<tr><th>Role</th><td>" . htmlspecialchars($_SESSION['user']['role'] ?? 'N/A') . "</td></tr>";
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No active session - API call will require login</p>";
    echo "<p>This is normal if testing outside logged-in context.</p>";
}
echo "</div>";

// Test 2: Test the API endpoint directly
echo "<div class='step'>";
echo "<h3>Step 2: API Endpoint Test</h3>";

try {
    // Build the full URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $apiUrl = "$protocol://$host/api/v1/projects/$projectId/sales-tracking";
    
    echo "<p>API URL: <code>$apiUrl</code></p>";
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Include cookies to maintain session
    $cookieHeader = '';
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookieHeader = $_SERVER['HTTP_COOKIE'];
    } else if (session_id()) {
        $cookieHeader = session_name() . '=' . session_id();
    }
    
    if ($cookieHeader) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookieHeader);
        echo "<p class='info'>ℹ️ Sending session cookie</p>";
    }
    
    // Get response info
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    echo "<h4>Response Status: ";
    if ($httpCode === 200) {
        echo "<span class='success'>$httpCode OK</span>";
    } else if ($httpCode === 401) {
        echo "<span class='error'>$httpCode Unauthorized</span>";
    } else if ($httpCode === 404) {
        echo "<span class='error'>$httpCode Not Found</span>";
    } else if ($httpCode === 500) {
        echo "<span class='error'>$httpCode Internal Server Error</span>";
    } else {
        echo "<span class='warning'>$httpCode</span>";
    }
    echo "</h4>";
    
    // Show response headers
    echo "<details>";
    echo "<summary>Response Headers (click to expand)</summary>";
    echo "<pre>" . htmlspecialchars($header) . "</pre>";
    echo "</details>";
    
    // Try to parse response body
    echo "<h4>Response Body:</h4>";
    
    $jsonData = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<pre>" . htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        
        // Analyze the response
        if ($httpCode === 200 && isset($jsonData['exists'])) {
            if ($jsonData['exists'] === true && isset($jsonData['data'])) {
                echo "<p class='success'>✅ Sales tracking data found!</p>";
                
                // Show key fields
                $data = $jsonData['data'];
                echo "<h4>Sales Tracking Details:</h4>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                echo "<tr><td>Contacted</td><td>" . ($data['contacted'] === true ? '✅ Yes' : ($data['contacted'] === false ? '❌ No' : '⚪ Not Set')) . "</td></tr>";
                echo "<tr><td>Quoted</td><td>" . ($data['quoted'] === true ? '✅ Yes' : ($data['quoted'] === false ? '❌ No' : '⚪ Not Set')) . "</td></tr>";
                echo "<tr><td>Sales Qualified</td><td>" . ($data['sales_qualified'] === true ? '✅ Yes' : ($data['sales_qualified'] === false ? '❌ No' : '⚪ Not Set')) . "</td></tr>";
                echo "<tr><td>To Win</td><td>" . ($data['to_win'] === true ? '✅ Yes' : ($data['to_win'] === false ? '❌ No' : '⚪ Not Set')) . "</td></tr>";
                echo "<tr><td>Tracking Status</td><td><strong>" . htmlspecialchars($data['tracking_status'] ?? 'N/A') . "</strong></td></tr>";
                echo "<tr><td>Sales Rep</td><td>" . htmlspecialchars($data['sales_rep_name'] ?? 'N/A') . "</td></tr>";
                echo "<tr><td>Branch</td><td>" . htmlspecialchars($data['branch'] ?? 'N/A') . "</td></tr>";
                echo "<tr><td>W/A Amount</td><td>₱" . number_format($data['wa_amount'] ?? 0, 2) . "</td></tr>";
                echo "<tr><td>Notes</td><td>" . htmlspecialchars(substr($data['notes'] ?? '', 0, 100)) . "</td></tr>";
                echo "</table>";
                
            } else {
                echo "<p class='warning'>⚠️ No sales tracking data for this project</p>";
            }
        } else if ($httpCode === 401) {
            echo "<p class='error'>❌ Authentication required - please log in first</p>";
        } else if ($httpCode === 404) {
            echo "<p class='error'>❌ Project not found</p>";
        } else if ($httpCode === 500) {
            echo "<p class='error'>❌ Server error - check server logs</p>";
            if (isset($jsonData['detail'])) {
                echo "<p>Error message: " . htmlspecialchars($jsonData['detail']) . "</p>";
            }
        }
        
    } else {
        echo "<pre>" . htmlspecialchars($body) . "</pre>";
        echo "<p class='error'>❌ Response is not valid JSON</p>";
        echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Test 3: Direct database query
echo "<div class='step'>";
echo "<h3>Step 3: Direct Database Query</h3>";

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/api/db.php';
    
    $db = getDB();
    echo "<p class='success'>✅ Database connection successful</p>";
    
    // Query the database directly
    $stmt = $db->prepare("
        SELECT st.*, u.full_name as sales_rep_name
        FROM sales_tracking st
        LEFT JOIN users u ON st.sales_rep_id = u.id
        WHERE st.project_id = :project_id
        LIMIT 1
    ");
    $stmt->execute([':project_id' => $projectId]);
    $tracking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tracking) {
        echo "<p class='success'>✅ Sales tracking record found in database</p>";
        echo "<h4>Database Record:</h4>";
        echo "<pre>" . htmlspecialchars(print_r($tracking, true)) . "</pre>";
    } else {
        echo "<p class='warning'>⚠️ No sales tracking record in database for project $projectId</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Final recommendation
echo "<hr>";
echo "<h3>📋 Summary & Next Steps</h3>";

if ($httpCode === 200) {
    echo "<p class='success'>✅ <strong>API is working correctly!</strong></p>";
    echo "<p>If the frontend still shows issues:</p>";
    echo "<ul>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Verify the frontend is calling the correct URL</li>";
    echo "<li>Check that cookies/session are being sent properly</li>";
    echo "<li>Clear browser cache and try again</li>";
    echo "</ul>";
} else if ($httpCode === 401) {
    echo "<p class='warning'>⚠️ <strong>Authentication Issue</strong></p>";
    echo "<p>The API requires authentication. Make sure you're logged in.</p>";
} else if ($httpCode === 500) {
    echo "<p class='error'>❌ <strong>Server Error</strong></p>";
    echo "<p>Check server logs:</p>";
    echo "<ul>";
    echo "<li>PHP error log: <code>logs/php_errors.log</code></li>";
    echo "<li>Apache/Nginx error log</li>";
    echo "<li>MariaDB/MySQL error log</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ <strong>Unexpected Response</strong></p>";
    echo "<p>Review the response details above and check server configuration.</p>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #999;'>";
echo "Test script: check-sales-tracking-api.php | ";
echo "Generated: " . date('Y-m-d H:i:s') . " | ";
echo "<a href='?project_id=" . ($projectId + 1) . "'>Test Next Project</a>";
echo "</p>";

echo "</div>";
echo "</body></html>";
?>
