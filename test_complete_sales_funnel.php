<?php
/**
 * Complete Sales Funnel Test
 * This script tests the entire sales funnel implementation
 * Access via: http://localhost/DATAS/test_complete_sales_funnel.php
 */

session_start();

if (!isset($_SESSION['user'])) {
    echo "<h2>⚠️ Please login first</h2>";
    echo "<p><a href='pages/login.php'>Login here</a></p>";
    exit;
}

echo "<h2>🔧 Complete Sales Funnel Test</h2>";
echo "<p>Testing the sales funnel based on your requirements:</p>";
echo "<ol>";
echo "<li><strong>Prospects</strong> - Raw Projects (di pa nagagalaw)</li>";
echo "<li><strong>Contacted</strong> - Projects na naka Yes ung contacted</li>";
echo "<li><strong>Sales Qualified Leads</strong> - naka yes na ung Sales Qualified Leads</li>";
echo "<li><strong>Not Sales Qualified Leads</strong> - naka No sa Sales Qualified Leads</li>";
echo "<li><strong>Quoted</strong> - mga naka Yes na Quoted</li>";
echo "<li><strong>Win</strong> - naka yes na yung Win at may W/L Amount na</li>";
echo "</ol>";
echo "<hr>";

require_once 'config.php';
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Step 1: Check if migration is needed
    echo "<h3>📋 Step 1: Database Schema Check</h3>";
    $stmt = $db->query("DESCRIBE sales_tracking");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['contacted', 'quoted', 'sales_qualified', 'to_win', 'wa_amount', 'tracking_status'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (!empty($missingColumns)) {
        echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missingColumns) . "</p>";
        echo "<p><strong>Action needed:</strong> <a href='run_sales_funnel_migration.php'>Run the migration</a></p>";
        echo "<hr>";
        echo "<h3>🔧 Next Steps:</h3>";
        echo "<ul>";
        echo "<li>Run the migration to add missing columns</li>";
        echo "<li>Add sales tracking data to projects</li>";
        echo "<li>Test the funnel again</li>";
        echo "</ul>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ All required columns exist!</p>";
    
    // Step 2: Check current data
    echo "<h3>📊 Step 2: Current Data Analysis</h3>";
    
    // Total projects
    $stmt = $db->query("SELECT COUNT(*) as total FROM projects");
    $totalProjects = $stmt->fetch()['total'];
    echo "<p><strong>Total Projects (Prospects):</strong> $totalProjects</p>";
    
    // Sales tracking records
    $stmt = $db->query("SELECT COUNT(*) as total FROM sales_tracking");
    $totalTracking = $stmt->fetch()['total'];
    echo "<p><strong>Projects with Sales Tracking:</strong> $totalTracking</p>";
    
    if ($totalTracking > 0) {
        // Detailed breakdown
        $stmt = $db->query("
            SELECT 
                SUM(CASE WHEN contacted = 'Yes' THEN 1 ELSE 0 END) as contacted,
                SUM(CASE WHEN sales_qualified = 'Yes' THEN 1 ELSE 0 END) as sql_yes,
                SUM(CASE WHEN sales_qualified = 'No' THEN 1 ELSE 0 END) as sql_no,
                SUM(CASE WHEN quoted = 'Yes' THEN 1 ELSE 0 END) as quoted,
                SUM(CASE WHEN to_win = 'Yes' AND wa_amount > 0 THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN tracking_status = 'Not Started' THEN 1 ELSE 0 END) as not_started,
                SUM(CASE WHEN tracking_status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN tracking_status = 'Complete' THEN 1 ELSE 0 END) as complete_status
            FROM sales_tracking
        ");
        $stats = $stmt->fetch();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Stage</th><th>Count</th><th>Description</th></tr>";
        echo "<tr><td>Prospects</td><td>$totalProjects</td><td>Raw projects (di pa nagagalaw)</td></tr>";
        echo "<tr><td>Contacted</td><td>" . $stats['contacted'] . "</td><td>Projects na naka Yes ung contacted</td></tr>";
        echo "<tr><td>Sales Qualified Leads</td><td>" . $stats['sql_yes'] . "</td><td>Naka yes na ung Sales Qualified Leads</td></tr>";
        echo "<tr><td>Not Sales Qualified Leads</td><td>" . $stats['sql_no'] . "</td><td>Naka No sa Sales Qualified Leads</td></tr>";
        echo "<tr><td>Quoted</td><td>" . $stats['quoted'] . "</td><td>Mga naka Yes na Quoted</td></tr>";
        echo "<tr><td>Win</td><td>" . $stats['wins'] . "</td><td>Naka yes na yung Win at may W/L Amount na</td></tr>";
        echo "</table>";
        
        echo "<h4>📊 Sales Tracking Status:</h4>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Status</th><th>Count</th><th>Description</th></tr>";
        echo "<tr><td>Not Started</td><td>" . $stats['not_started'] . "</td><td>Di pa nagagalaw yung Sales Tracking</td></tr>";
        echo "<tr><td>In Progress</td><td>" . $stats['in_progress'] . "</td><td>May nagsimula na pero hindi pa complete</td></tr>";
        echo "<tr><td>Complete</td><td>" . $stats['complete_status'] . "</td><td>Tapos na lahat ng Sales Tracking</td></tr>";
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No sales tracking data found. Add some sales tracking records to test the funnel.</p>";
    }
    
    // Step 3: Test the API
    echo "<h3>🔍 Step 3: API Test</h3>";
    
    $base_url = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']);
    $url = $base_url . '/api/v1/charts/funnel';
    
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
        echo "<p style='color: green;'>✅ API Status: $http_code OK</p>";
        
        $json_data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json_data['stages'])) {
            echo "<h4>📊 Sales Funnel Results:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Stage</th><th>Count</th><th>Conversion</th><th>Description</th></tr>";
            
            foreach ($json_data['stages'] as $stage) {
                $conversion = $stage['conversion'] ? $stage['conversion'] . '%' : '-';
                $color = $stage['count'] > 0 ? 'color: green; font-weight: bold;' : 'color: #888;';
                echo "<tr>";
                echo "<td style='$color'>" . htmlspecialchars($stage['name']) . "</td>";
                echo "<td style='$color'>" . $stage['count'] . "</td>";
                echo "<td style='$color'>" . $conversion . "</td>";
                echo "<td>" . htmlspecialchars($stage['description']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h4>📋 Raw API Response:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
            echo htmlspecialchars(json_encode($json_data, JSON_PRETTY_PRINT));
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Invalid API response</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ HTTP Error: $http_code</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>🔧 Next Steps:</h3>";
echo "<ul>";
echo "<li>If migration is needed: <a href='run_sales_funnel_migration.php'>Run Migration</a></li>";
echo "<li>If no data: Add sales tracking to projects in the system</li>";
echo "<li>Test the reports page: <a href='pages/reports.php'>Reports Page</a></li>";
echo "<li>Check contractors list: <a href='debug_contractors.php'>Debug Contractors</a></li>";
echo "</ul>";
?>