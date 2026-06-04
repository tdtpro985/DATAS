<?php
/**
 * Test Rebuilt Assignment System
 * Tests the completely rebuilt project assignment functionality
 */

// Same session setup as the main app
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

echo "<style>
body { font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; max-width: 800px; margin: 0 auto; padding: 2rem; }
.test-section { margin: 2rem 0; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; }
.success { color: #10b981; font-weight: 500; }
.error { color: #ef4444; font-weight: 500; }
.warning { color: #f59e0b; font-weight: 500; }
pre { background: #f9fafb; padding: 1rem; border-radius: 4px; overflow-x: auto; }
.button { background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; display: inline-block; }
</style>";

echo "<h1>🔧 Rebuilt Assignment System Test</h1>";
echo "<p>Testing the completely rebuilt project assignment functionality.</p>";

// Check login
if (empty($_SESSION['user'])) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>❌ Not Logged In</h2>";
    echo "<p>You must be logged in as admin or superadmin to test this functionality.</p>";
    echo "<a href='pages/login.php' class='button'>Login Here</a>";
    echo "</div>";
    exit;
}

$user = $_SESSION['user'];
if (!in_array($user['role'], ['admin', 'superadmin'])) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>❌ Insufficient Permissions</h2>";
    echo "<p>Your role ({$user['role']}) cannot test project assignment. Need admin or superadmin.</p>";
    echo "</div>";
    exit;
}

echo "<div class='test-section'>";
echo "<h2 class='success'>✅ User Session Valid</h2>";
echo "<p><strong>Name:</strong> {$user['full_name']}</p>";
echo "<p><strong>Role:</strong> {$user['role']}</p>";
echo "</div>";

// Test 1: Sales Reps API
echo "<div class='test-section'>";
echo "<h2>🧪 Test 1: Sales Reps API</h2>";

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
    echo "<p class='error'>❌ cURL Error: $curlError</p>";
} else {
    echo "<p>API URL: <code>$apiUrl</code></p>";
    echo "<p>HTTP Status: <strong>$httpCode</strong></p>";
    
    if ($httpCode == 200) {
        echo "<p class='success'>✅ Sales Reps API is working!</p>";
        
        $data = json_decode($response, true);
        if ($data && isset($data['data'])) {
            $salesReps = $data['data'];
            echo "<p><strong>Sales Reps Found:</strong> " . count($salesReps) . "</p>";
            
            if (count($salesReps) > 0) {
                echo "<ul>";
                foreach (array_slice($salesReps, 0, 2) as $rep) {
                    echo "<li>ID: {$rep['id']} - {$rep['full_name']} ({$rep['email']})</li>";
                }
                if (count($salesReps) > 2) {
                    echo "<li>... and " . (count($salesReps) - 2) . " more</li>";
                }
                echo "</ul>";
            }
        }
    } else {
        echo "<p class='error'>❌ API failed with HTTP $httpCode</p>";
        echo "<details><summary>Response</summary><pre>" . htmlspecialchars($response) . "</pre></details>";
    }
}
echo "</div>";

// Test 2: Database Check
echo "<div class='test-section'>";
echo "<h2>🧪 Test 2: Database Status</h2>";
try {
    require_once __DIR__ . '/api/db.php';
    $db = getDB();
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'sales_rep'");
    $stmt->execute();
    $salesRepCount = $stmt->fetch()['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM projects WHERE assigned_to IS NULL");
    $stmt->execute();
    $unassignedCount = $stmt->fetch()['count'];
    
    echo "<p class='success'>✅ Database connection successful</p>";
    echo "<ul>";
    echo "<li><strong>Sales Representatives:</strong> $salesRepCount</li>";
    echo "<li><strong>Unassigned Projects:</strong> $unassignedCount</li>";
    echo "</ul>";
    
    if ($salesRepCount == 0) {
        echo "<p class='warning'>⚠️ No sales representatives found. <a href='test_and_create_sales_rep.php'>Create one here</a></p>";
    }
    
    if ($unassignedCount == 0) {
        echo "<p class='warning'>⚠️ No unassigned projects found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Frontend Test Instructions
echo "<div class='test-section'>";
echo "<h2>🖥️ Frontend Test Instructions</h2>";
echo "<p>Now test the rebuilt assignment interface:</p>";
echo "<ol>";
echo "<li><a href='pages/projects-management.php?view=unassigned' target='_blank' class='button'>Open Project Management</a></li>";
echo "<li>Click the <strong>\"Bulk Assign Projects\"</strong> button</li>";
echo "<li>You should see a clean modal with sales representatives</li>";
echo "<li>Click on a sales rep to select them</li>";
echo "<li>You should see a green status bar at the top indicating assignment mode</li>";
echo "<li>Check the boxes next to projects you want to assign</li>";
echo "<li>Click <strong>\"Assign Selected\"</strong> in the status bar</li>";
echo "</ol>";
echo "</div>";

// What's New Section
echo "<div class='test-section'>";
echo "<h2>🆕 What's Been Rebuilt</h2>";
echo "<ul>";
echo "<li>✅ Completely rewritten JavaScript assignment logic</li>";
echo "<li>✅ Simplified modal interface</li>";
echo "<li>✅ Clear visual feedback with status bar</li>";
echo "<li>✅ Rebuilt API endpoint with better error handling</li>";
echo "<li>✅ Clean state management</li>";
echo "<li>✅ Proper session authentication</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center;'>";
echo "<a href='pages/projects-management.php?view=unassigned' class='button'>Test Assignment Interface</a> | ";
echo "<a href='test_assignment_workflow.php'>Run Old Test</a>";
echo "</p>";
?>