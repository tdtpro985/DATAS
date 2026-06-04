<?php
/**
 * Authentication Issue Debug
 * This script helps identify the specific session/authentication problem
 */

// Set up same session configuration as the main app
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

echo "<h2>🔍 Authentication Issue Debug</h2>";

// Check if user is logged in to main session
echo "<h3>1. Main Session Status</h3>";
if (empty($_SESSION['user'])) {
    echo "<p style='color: red;'>❌ User not logged in to main session</p>";
    echo "<p><a href='pages/login.php'>Login here</a> then come back to this page.</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ User logged in to main session:</p>";
    echo "<ul>";
    echo "<li>Name: " . $_SESSION['user']['full_name'] . "</li>";
    echo "<li>Email: " . $_SESSION['user']['email'] . "</li>";
    echo "<li>Role: " . $_SESSION['user']['role'] . "</li>";
    echo "<li>ID: " . $_SESSION['user']['id'] . "</li>";
    echo "</ul>";
}

// Test if helpers.php loads correctly
echo "<h3>2. Helper Functions Test</h3>";
try {
    require_once __DIR__ . '/api/helpers.php';
    echo "<p style='color: green;'>✅ helpers.php loaded successfully</p>";
    
    // Test requireAuth function
    try {
        $authUser = requireAuth();
        echo "<p style='color: green;'>✅ requireAuth() successful</p>";
        echo "<pre>";
        print_r($authUser);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ requireAuth() failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to load helpers.php: " . $e->getMessage() . "</p>";
}

// Test direct API include
echo "<h3>3. Direct Sales-Reps API Test</h3>";
try {
    // Capture output buffer
    ob_start();
    
    // Mock the request environment
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    // Include the API file
    include __DIR__ . '/api/users/sales-reps.php';
    
    $apiOutput = ob_get_contents();
    ob_end_clean();
    
    echo "<p>API Output:</p>";
    echo "<pre style='background: #f5f5f5; padding: 1rem; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars($apiOutput);
    echo "</pre>";
    
    // Parse JSON if possible
    $jsonResponse = json_decode($apiOutput, true);
    if ($jsonResponse) {
        if (isset($jsonResponse['success']) && $jsonResponse['success']) {
            echo "<p style='color: green;'>✅ API returned success response</p>";
            if (isset($jsonResponse['data'])) {
                echo "<p>Sales reps found: " . count($jsonResponse['data']) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ API returned error: " . ($jsonResponse['message'] ?? 'Unknown error') . "</p>";
        }
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ Direct API test failed: " . $e->getMessage() . "</p>";
}

// Test HTTP request to API
echo "<h3>4. HTTP Request to Sales-Reps API</h3>";

$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/api/v1/users/sales-reps';
echo "<p>Testing URL: <code>$apiUrl</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$httpResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "<p style='color: red;'>❌ cURL Error: $curlError</p>";
} else {
    echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";
    
    if ($httpCode === 200) {
        echo "<p style='color: green;'>✅ API request successful</p>";
    } else {
        echo "<p style='color: red;'>❌ API request failed</p>";
    }
    
    echo "<p>Response:</p>";
    echo "<pre style='background: #f5f5f5; padding: 1rem; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars($httpResponse);
    echo "</pre>";
    
    // Parse JSON response
    $jsonResponse = json_decode($httpResponse, true);
    if ($jsonResponse) {
        echo "<p>Parsed response:</p>";
        echo "<pre>";
        print_r($jsonResponse);
        echo "</pre>";
    }
}

// Session and cookie debug
echo "<h3>5. Session & Cookie Debug</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Cookie: " . (isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : 'Not found') . "\n";
echo "Session Status: " . session_status() . " (1=disabled, 2=none, 3=active)\n";
echo "</pre>";

// Database connection test
echo "<h3>6. Database Connection Test</h3>";
try {
    require_once __DIR__ . '/api/db.php';
    $db = getDB();
    
    // Test query - check for sales reps
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'sales_rep'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    echo "<p>Sales reps in database: " . $result['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>If all tests above are successful but project assignment still doesn't work, the issue may be in the JavaScript frontend.</p>";
echo "<p><a href='test_assignment_workflow.php'>← Run Full Assignment Workflow Test</a></p>";
?>