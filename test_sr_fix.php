<?php
/**
 * Test Sales Rep Fix
 * Quick test to verify sales representatives are now showing
 */

session_start();

echo "<style>
body { font-family: system-ui; max-width: 600px; margin: 2rem auto; padding: 1rem; line-height: 1.6; }
.success { color: #10b981; }
.error { color: #ef4444; }
.btn { background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
</style>";

echo "<h1>🔧 Sales Rep Display Fix Test</h1>";

// Check login
if (empty($_SESSION['user'])) {
    echo "<p class='error'>❌ Please login first to test</p>";
    echo "<p><a href='pages/login.php' class='btn'>Login</a></p>";
    exit;
}

echo "<p class='success'>✅ User logged in: {$_SESSION['user']['full_name']}</p>";

// Quick API test
echo "<h2>🧪 API Test</h2>";
$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/api/v1/users/sales-reps';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    $salesReps = $data['data'] ?? $data['users'] ?? [];
    
    echo "<p class='success'>✅ API working (HTTP 200)</p>";
    echo "<p>Sales representatives found: " . count($salesReps) . "</p>";
    
    if (count($salesReps) > 0) {
        echo "<ul>";
        foreach (array_slice($salesReps, 0, 3) as $rep) {
            echo "<li>{$rep['full_name']} ({$rep['email']}) - {$rep['branch']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>⚠️ No sales reps found in database</p>";
        echo "<p><a href='test_and_create_sales_rep.php'>Create test sales rep</a></p>";
    }
} else {
    echo "<p class='error'>❌ API failed (HTTP $httpCode)</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
}

echo "<h2>🛠️ What Was Fixed</h2>";
echo "<ul>";
echo "<li>✅ Fixed container ID mismatch (salesRepList → salesRepsGrid)</li>";
echo "<li>✅ Restored original renderSalesReps function</li>";
echo "<li>✅ Fixed function name compatibility</li>";
echo "<li>✅ Added missing project selection functions</li>";
echo "</ul>";

echo "<h2>🎯 Test Now</h2>";
echo "<ol>";
echo "<li><a href='pages/projects-management.php?view=unassigned' class='btn'>Open Project Management</a></li>";
echo "<li>Click \"Bulk Assign Projects\" button</li>";
echo "<li>Sales representatives should now appear!</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Expected Result:</strong> Modal opens with sales rep cards displayed in grid layout, same as original design.</p>";
?>