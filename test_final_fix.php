<?php
/**
 * Final Fix Test - Complete API Authentication Test
 */

// Use same session configuration as the app
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

echo "<h1>🔧 Final Fix Test</h1>";
echo "<p>Testing the complete project assignment workflow after applying fixes.</p>";

// Check login status
if (empty($_SESSION['user'])) {
    echo "<h2>❌ Please Login First</h2>";
    echo "<p>You must be logged in as admin or superadmin to test this functionality.</p>";
    echo "<p><a href='pages/login.php' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Login Here</a></p>";
    exit;
}

$user = $_SESSION['user'];
echo "<h2>✅ User Session Active</h2>";
echo "<ul>";
echo "<li><strong>Name:</strong> {$user['full_name']}</li>";
echo "<li><strong>Role:</strong> {$user['role']}</li>";
echo "<li><strong>Session ID:</strong> " . session_id() . "</li>";
echo "</ul>";

if (!in_array($user['role'], ['admin', 'superadmin'])) {
    echo "<p style='color: red;'>❌ Your role ({$user['role']}) cannot test project assignment. Need admin or superadmin.</p>";
    exit;
}

echo "<hr>";

// Test 1: Direct API call using curl
echo "<h2>🌐 Test 1: HTTP API Call</h2>";
$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/api/v1/users/sales-reps';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "<p style='color: red;'>❌ cURL Error: $curlError</p>";
} else {
    echo "<p>URL: <code>$apiUrl</code></p>";
    echo "<p>HTTP Status: <strong>$httpCode</strong></p>";
    
    if ($httpCode == 200) {
        echo "<p style='color: green;'>✅ API call successful!</p>";
        
        $data = json_decode($response, true);
        if ($data && isset($data['data'])) {
            $salesReps = $data['data'];
            echo "<p><strong>Sales Representatives Found:</strong> " . count($salesReps) . "</p>";
            if (count($salesReps) > 0) {
                echo "<ul>";
                foreach (array_slice($salesReps, 0, 3) as $rep) {
                    echo "<li>ID: {$rep['id']} - {$rep['full_name']} ({$rep['email']})</li>";
                }
                if (count($salesReps) > 3) {
                    echo "<li>... and " . (count($salesReps) - 3) . " more</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ API response format unexpected</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ API call failed</p>";
    }
    
    echo "<details style='margin-top: 1rem;'>";
    echo "<summary>Raw Response</summary>";
    echo "<pre style='background: #f5f5f5; padding: 1rem; max-height: 200px; overflow-y: auto;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    echo "</details>";
}

echo "<hr>";

// Test 2: Database check
echo "<h2>📊 Test 2: Database Status</h2>";
try {
    require_once __DIR__ . '/api/db.php';
    $db = getDB();
    
    // Check sales reps
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'sales_rep'");
    $stmt->execute();
    $salesRepCount = $stmt->fetch()['count'];
    
    // Check unassigned projects
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM projects WHERE assigned_to IS NULL");
    $stmt->execute();
    $unassignedCount = $stmt->fetch()['count'];
    
    echo "<p>✅ Database connection successful</p>";
    echo "<ul>";
    echo "<li><strong>Sales Representatives:</strong> $salesRepCount</li>";
    echo "<li><strong>Unassigned Projects:</strong> $unassignedCount</li>";
    echo "</ul>";
    
    if ($salesRepCount == 0) {
        echo "<p style='color: orange;'>⚠️ No sales representatives found. <a href='test_and_create_sales_rep.php'>Create one here</a></p>";
    }
    
    if ($unassignedCount == 0) {
        echo "<p style='color: orange;'>⚠️ No unassigned projects found. Assignment testing may not be possible.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 3: Frontend test
echo "<h2>🖥️ Test 3: Frontend Integration</h2>";
echo "<p>Now test the actual user interface:</p>";
echo "<ol>";
echo "<li><a href='pages/projects-management.php?view=unassigned' target='_blank' style='background: #10b981; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Open Project Management</a></li>";
echo "<li>Click the <strong>\"Bulk Assign Projects\"</strong> button</li>";
echo "<li>You should see a modal with sales representatives</li>";
echo "<li>Select a sales rep, then select some projects</li>";
echo "<li>Try assigning the projects</li>";
echo "</ol>";

echo "<hr>";

// Summary
echo "<h2>📋 Summary</h2>";
if ($httpCode == 200) {
    echo "<p style='color: green; font-size: 1.1rem;'>✅ <strong>API authentication is working!</strong></p>";
    echo "<p>The sales-reps API is now returning HTTP 200 instead of 401 Unauthorized.</p>";
    echo "<p>Project assignment should now work in the frontend.</p>";
} else {
    echo "<p style='color: red; font-size: 1.1rem;'>❌ <strong>API still has issues</strong></p>";
    echo "<p>The API is still returning HTTP $httpCode. Further debugging may be needed.</p>";
}

echo "<hr>";
echo "<p><a href='test_assignment_workflow.php'>← Run Complete Assignment Workflow Test</a></p>";
?>