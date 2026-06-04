<?php
/**
 * Debug script to test the contractors API specifically
 * Access via: http://localhost/DATAS/debug_contractors.php
 */

session_start();

if (!isset($_SESSION['user'])) {
    echo "<h2>⚠️ Please login first</h2>";
    echo "<p><a href='pages/login.php'>Login here</a></p>";
    exit;
}

echo "<h2>🔍 Contractors API Debug</h2>";
echo "<hr>";

// Test the contractors API directly
$base_url = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']);
$url = $base_url . '/api/v1/contractors/ranking';

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
        echo "<h4>📋 Response Structure:</h4>";
        echo "<pre>" . htmlspecialchars(json_encode($json_data, JSON_PRETTY_PRINT)) . "</pre>";
        
        if (isset($json_data['contractors'])) {
            echo "<h4>📊 Contractors Found: " . count($json_data['contractors']) . "</h4>";
            
            if (count($json_data['contractors']) > 0) {
                echo "<h4>🏗️ Sample Contractor Data:</h4>";
                $sample = $json_data['contractors'][0];
                echo "<ul>";
                foreach ($sample as $key => $value) {
                    echo "<li><strong>$key:</strong> " . htmlspecialchars($value) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: orange;'>⚠️ No contractors found in database</p>";
                echo "<p>This could mean:</p>";
                echo "<ul>";
                echo "<li>No projects in the database</li>";
                echo "<li>No contractor names in existing projects</li>";
                echo "<li>Date filter is too restrictive</li>";
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>❌ Response missing 'contractors' key</p>";
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
echo "<li>If no contractors found, add some projects with contractor names</li>";
echo "<li>If API works here but not in reports page, check browser console for JavaScript errors</li>";
echo "<li><a href='pages/reports.php'>Test the reports page</a></li>";
echo "</ul>";
?>