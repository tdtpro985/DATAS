<?php
/**
 * Test Assignment Workflow
 * This script tests the complete project assignment workflow
 */

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

if (empty($_SESSION['user'])) {
    echo "<h2>❌ Please Login First</h2>";
    echo "<p><a href='pages/login.php'>Login here</a></p>";
    exit;
}

$userRole = $_SESSION['user']['role'] ?? '';
if (!in_array($userRole, ['admin', 'superadmin'])) {
    echo "<h2>❌ Access Denied</h2>";
    echo "<p>Only admin and superadmin can test project assignment. Your role: $userRole</p>";
    exit;
}

echo "<h2>🔧 Project Assignment Workflow Test</h2>";
echo "<p>Current user: {$_SESSION['user']['full_name']} ($userRole)</p>";
echo "<hr>";

try {
    $db = getDB();
    
    // Step 1: Check Sales Reps
    echo "<h3>1. Checking Sales Representatives...</h3>";
    $stmt = $db->prepare("SELECT id, email, full_name, branch FROM users WHERE role = 'sales_rep' LIMIT 5");
    $stmt->execute();
    $salesReps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($salesReps)) {
        echo "<p style='color: red;'>❌ No sales representatives found!</p>";
        echo "<p><a href='test_and_create_sales_rep.php'>Create a test sales rep</a></p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($salesReps) . " sales representative(s):</p>";
        echo "<ul>";
        foreach ($salesReps as $rep) {
            echo "<li>ID: {$rep['id']} - {$rep['full_name']} ({$rep['email']}) - Branch: " . ($rep['branch'] ?: 'None') . "</li>";
        }
        echo "</ul>";
        
        $testSalesRepId = $salesReps[0]['id'];
        echo "<p>Will use Sales Rep ID: <strong>$testSalesRepId</strong> for testing</p>";
    }
    
    // Step 2: Check Unassigned Projects
    echo "<h3>2. Checking Unassigned Projects...</h3>";
    $stmt = $db->prepare("SELECT id, contractor_name, project_name, assigned_to FROM projects WHERE assigned_to IS NULL LIMIT 5");
    $stmt->execute();
    $unassignedProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($unassignedProjects)) {
        echo "<p style='color: orange;'>⚠️ No unassigned projects found!</p>";
        echo "<p>You need unassigned projects to test assignment functionality.</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($unassignedProjects) . " unassigned project(s):</p>";
        echo "<ul>";
        foreach ($unassignedProjects as $project) {
            echo "<li>ID: {$project['id']} - {$project['contractor_name']} - {$project['project_name']}</li>";
        }
        echo "</ul>";
        
        $testProjectIds = array_column($unassignedProjects, 'id');
        echo "<p>Will use Project IDs: <strong>" . implode(', ', array_slice($testProjectIds, 0, 2)) . "</strong> for testing</p>";
    }
    
    // Step 3: Test API Endpoints
    echo "<h3>3. Testing API Endpoints...</h3>";
    
    $baseUrl = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']);
    
    // Test Sales Reps API
    echo "<h4>3.1 Testing Sales Reps API</h4>";
    $salesRepsUrl = $baseUrl . '/api/v1/users/sales-reps';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $salesRepsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>❌ cURL Error: $error</p>";
    } elseif ($httpCode == 200) {
        echo "<p style='color: green;'>✅ Sales Reps API working (HTTP $httpCode)</p>";
        $apiData = json_decode($response, true);
        if ($apiData && (isset($apiData['users']) || isset($apiData['data']))) {
            $apiSalesReps = $apiData['users'] ?? $apiData['data'] ?? [];
            echo "<p>API returned " . count($apiSalesReps) . " sales rep(s)</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Sales Reps API Error (HTTP $httpCode): $response</p>";
    }
    
    // Step 4: Test Bulk Assignment (only if we have both sales reps and projects)
    if (!empty($salesReps) && !empty($unassignedProjects) && isset($testSalesRepId) && isset($testProjectIds)) {
        echo "<h4>3.2 Testing Bulk Assignment API</h4>";
        
        $assignmentUrl = $baseUrl . '/api/v1/projects/bulk-assign';
        $assignmentData = json_encode([
            'sales_rep_id' => $testSalesRepId,
            'project_ids' => array_slice($testProjectIds, 0, 2) // Only test with 2 projects
        ]);
        
        echo "<p>Test assignment data: <code>" . htmlspecialchars($assignmentData) . "</code></p>";
        
        if (isset($_GET['test_assignment']) && $_GET['test_assignment'] === 'yes') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $assignmentUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $assignmentData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "<p style='color: red;'>❌ Assignment cURL Error: $error</p>";
            } elseif ($httpCode == 200) {
                echo "<p style='color: green;'>✅ Bulk Assignment API working (HTTP $httpCode)</p>";
                echo "<p>Response: <pre>" . htmlspecialchars($response) . "</pre></p>";
            } else {
                echo "<p style='color: red;'>❌ Assignment API Error (HTTP $httpCode): $response</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ <a href='?test_assignment=yes'>Click here to test actual assignment</a> (this will assign 2 projects to the sales rep)</p>";
        }
    }
    
    // Step 5: JavaScript Test
    echo "<h3>4. Frontend JavaScript Test</h3>";
    echo "<p>Test the project management interface:</p>";
    echo "<p><a href='pages/projects-management.php?view=unassigned' target='_blank'>Open Project Management</a></p>";
    
    echo "<h4>JavaScript Debug Steps:</h4>";
    echo "<ol>";
    echo "<li>Open the project management page</li>";
    echo "<li>Open browser console (F12)</li>";
    echo "<li>Click 'Bulk Assign Projects' button</li>";
    echo "<li>Check console for any errors</li>";
    echo "<li>If modal opens, select a sales rep</li>";
    echo "<li>Try selecting projects and assigning them</li>";
    echo "</ol>";
    
    // Step 6: Common Issues
    echo "<h3>5. Common Issues & Solutions</h3>";
    echo "<ul>";
    echo "<li><strong>No sales reps:</strong> <a href='test_and_create_sales_rep.php'>Create test sales rep</a></li>";
    echo "<li><strong>No unassigned projects:</strong> Create some projects or unassign existing ones</li>";
    echo "<li><strong>Permission errors:</strong> Make sure you're logged in as admin/superadmin</li>";
    echo "<li><strong>JavaScript errors:</strong> Check browser console for details</li>";
    echo "<li><strong>API errors:</strong> Check the responses above for error details</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='pages/projects-management.php?view=unassigned'>← Back to Project Management</a></p>";
?>