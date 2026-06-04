<?php
/**
 * Test Sales Funnel API
 * Access via: http://localhost/DATAS/test_sales_funnel.php
 */

session_start();

if (!isset($_SESSION['user'])) {
    echo "<h2>⚠️ Please login first</h2>";
    echo "<p><a href='pages/login.php'>Login here</a></p>";
    exit;
}

echo "<h2>🔍 Sales Funnel API Test</h2>";
echo "<hr>";

// Test the funnel API
$base_url = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']);
$url = $base_url . '/api/v1/charts/funnel';

echo "<h3>Testing: $url</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ cURL Error: $error</p>";
} elseif ($http_code == 200) {
    echo "<p style='color: green;'>✅ Status: $http_code OK</p>";
    
    $json_data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<h4>📊 Sales Funnel Stages:</h4>";
        
        if (isset($json_data['stages']) && is_array($json_data['stages'])) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Stage</th><th>Count</th><th>Conversion</th><th>Description</th></tr>";
            
            foreach ($json_data['stages'] as $stage) {
                $conversion = $stage['conversion'] ? $stage['conversion'] . '%' : '-';
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($stage['name']) . "</strong></td>";
                echo "<td>" . $stage['count'] . "</td>";
                echo "<td>" . $conversion . "</td>";
                echo "<td>" . htmlspecialchars($stage['description'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h4>📋 Raw Response:</h4>";
            echo "<pre>" . htmlspecialchars(json_encode($json_data, JSON_PRETTY_PRINT)) . "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Response missing 'stages' array</p>";
            echo "<pre>" . htmlspecialchars(json_encode($json_data, JSON_PRETTY_PRINT)) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON response</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ HTTP Error: $http_code</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<hr>";
echo "<h3>🔧 Next Steps:</h3>";
echo "<ul>";
echo "<li>If you see database errors, run the <a href='run_sales_funnel_migration.php'>migration script</a></li>";
echo "<li>If stages show 0 counts, add sales tracking data to projects</li>";
echo "<li><a href='pages/reports.php'>Test the reports page</a></li>";
echo "</ul>";
?>