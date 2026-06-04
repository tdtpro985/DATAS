<?php
/**
 * Comprehensive Dashboard Diagnostic Tool
 * 
 * This script tests all possible issues causing "No data" in the dashboard:
 * 1. Database connection and data
 * 2. Session and authentication 
 * 3. API endpoints
 * 4. Individual component data
 */

require_once 'config.php';
require_once 'api/db.php';
require_once 'api/helpers.php';

// Start session for testing
session_start();

echo "<style>
body { font-family: Arial; margin: 20px; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.info { color: #17a2b8; font-weight: bold; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>";

echo "<h1>🔧 DATAS Dashboard Diagnostic Tool</h1>";
echo "<hr>";

// Test 1: Database Connection
echo "<h2>1. 🗄️ Database Connection Test</h2>";
try {
    $db = getDB();
    echo "<p class='success'>✅ Database connection: SUCCESS</p>";
    
    // Count total projects
    $stmt = $db->query("SELECT COUNT(*) as total FROM projects");
    $total = $stmt->fetch()['total'];
    echo "<p class='info'>📊 Total Projects: $total</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection FAILED: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Session Status
echo "<h2>2. 🔐 Session & Authentication Test</h2>";
echo "<p class='info'>Session ID: " . session_id() . "</p>";

if (empty($_SESSION['user'])) {
    echo "<p class='error'>❌ No authenticated user in session</p>";
    echo "<p class='warning'>⚠️ This is likely the main cause of 'No data' issue!</p>";
    
    // Show sample login for testing
    echo "<h3>🔑 Quick Login Test</h3>";
    echo "<p>To test authentication, you need to log in first:</p>";
    echo "<ol>";
    echo "<li>Go to <a href='pages/login.php' target='_blank'>Login Page</a></li>";
    echo "<li>Or manually add a test user to session (for debugging only)</li>";
    echo "</ol>";
    
    echo "<p><strong>For immediate testing, click this button to simulate login:</strong></p>";
    if (isset($_GET['test_login'])) {
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin'
        ];
        echo "<p class='success'>✅ Test user session created! Refresh page to continue tests.</p>";
        echo "<script>window.location.href = window.location.pathname;</script>";
    } else {
        echo "<a href='?test_login=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Create Test Session</a>";
    }
    
} else {
    echo "<p class='success'>✅ User is authenticated</p>";
    echo "<p class='info'>User: " . json_encode($_SESSION['user']) . "</p>";
}

// Test 3: API Endpoints (only if authenticated)
if (!empty($_SESSION['user'])) {
    echo "<h2>3. 🌐 API Endpoints Test</h2>";
    
    // Test KPI endpoint
    echo "<h3>📈 KPI Data Test</h3>";
    try {
        // Simulate KPI API call
        $date = buildDateFilter('publication_date');
        $region = getRegion();
        
        $sql = "
            SELECT 
                COUNT(p.id) as projects_encoded,
                COUNT(DISTINCT CASE WHEN p.contractor_name IS NOT NULL AND p.contractor_name != '' THEN p.contractor_name END) as contractors_identified,
                COALESCE(SUM(p.project_value), 0) as total_pipeline_value
            FROM projects p 
            WHERE p.project_value IS NOT NULL AND p.project_value > 0
            $date
        ";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->fetch();
        
        echo "<p class='success'>✅ KPI data retrieved successfully</p>";
        echo "<p class='info'>Projects: {$result['projects_encoded']}</p>";
        echo "<p class='info'>Contractors: {$result['contractors_identified']}</p>";
        echo "<p class='info'>Total Value: ₱" . number_format($result['total_pipeline_value'], 2) . "</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ KPI test failed: " . $e->getMessage() . "</p>";
    }
    
    // Test Contractors Ranking
    echo "<h3>🏗️ Contractors Ranking Test</h3>";
    try {
        $sql = "
            SELECT 
                contractor_name,
                COUNT(*) as projects,
                COALESCE(SUM(project_value), 0) as total_value
            FROM projects 
            WHERE contractor_name IS NOT NULL AND contractor_name != ''
            GROUP BY contractor_name 
            ORDER BY total_value DESC 
            LIMIT 5
        ";
        
        $stmt = $db->prepare($sql);
        $contractors = $stmt->fetchAll();
        
        if (count($contractors) > 0) {
            echo "<p class='success'>✅ Contractors data retrieved: " . count($contractors) . " contractors</p>";
            echo "<table>";
            echo "<tr><th>Contractor</th><th>Projects</th><th>Total Value</th></tr>";
            foreach ($contractors as $c) {
                echo "<tr>";
                echo "<td>{$c['contractor_name']}</td>";
                echo "<td>{$c['projects']}</td>";
                echo "<td>₱" . number_format($c['total_value'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No contractors found</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Contractors test failed: " . $e->getMessage() . "</p>";
    }
}

// Test 4: Frontend API Calls
echo "<h2>4. 🖥️ Frontend API Simulation Test</h2>";

if (!empty($_SESSION['user'])) {
    echo "<p class='info'>Testing what the dashboard JavaScript would receive...</p>";
    
    // Simulate the exact API calls made by the frontend
    $apiTests = [
        'kpi' => '/api/v1/kpi',
        'contractors' => '/api/v1/contractors/ranking',
        'funnel' => '/api/v1/charts/funnel'
    ];
    
    foreach ($apiTests as $name => $endpoint) {
        echo "<h4>Testing: $endpoint</h4>";
        
        // Create a mock request to test the endpoint
        $original_uri = $_SERVER['REQUEST_URI'] ?? '';
        $original_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        $_SERVER['REQUEST_URI'] = $endpoint;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        ob_start();
        try {
            // Include the router with the mocked request
            include 'api/router.php';
            $response = ob_get_clean();
            
            if ($response && json_decode($response)) {
                echo "<p class='success'>✅ $name API response: Valid JSON</p>";
                echo "<details><summary>View Response</summary><code>" . htmlspecialchars($response) . "</code></details>";
            } else {
                echo "<p class='error'>❌ $name API response: Invalid or empty</p>";
                if ($response) {
                    echo "<code>" . htmlspecialchars($response) . "</code>";
                }
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "<p class='error'>❌ $name API error: " . $e->getMessage() . "</p>";
        }
        
        // Restore original values
        $_SERVER['REQUEST_URI'] = $original_uri;
        $_SERVER['REQUEST_METHOD'] = $original_method;
    }
}

// Test 5: Configuration Check
echo "<h2>5. ⚙️ Configuration Check</h2>";
echo "<p class='info'>Debug Mode: " . (DEBUG_MODE ? 'ON' : 'OFF') . "</p>";
echo "<p class='info'>Database: " . DB_NAME . " @ " . DB_HOST . "</p>";
echo "<p class='info'>Session Timeout: " . SESSION_TIMEOUT . " seconds</p>";

// Final Diagnosis
echo "<h2>🎯 Final Diagnosis</h2>";

if (empty($_SESSION['user'])) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
    echo "<h3 class='warning'>⚠️ ROOT CAUSE IDENTIFIED: Authentication Issue</h3>";
    echo "<p><strong>Problem:</strong> Users are not logged in, so all API calls return 401 errors instead of data.</p>";
    echo "<p><strong>Solution:</strong></p>";
    echo "<ol>";
    echo "<li>Make sure users log in through <a href='pages/login.php'>the login page</a></li>";
    echo "<li>Check that login credentials are working</li>";
    echo "<li>Verify session cookies are being set correctly</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 class='success'>✅ System Status: Authentication OK</h3>";
    echo "<p>If you're still seeing 'No data', check:</p>";
    echo "<ol>";
    echo "<li>Browser developer console for JavaScript errors</li>";
    echo "<li>Network tab for failed API requests</li>";
    echo "<li>Clear browser cache and cookies</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<p><em>Generated at: " . date('Y-m-d H:i:s') . "</em></p>";
?>