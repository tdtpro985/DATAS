<?php
/**
 * Check if there's data in the database
 * Access via: http://localhost/DATAS/check_data.php
 */

require_once 'config.php';
require_once 'api/db.php';

echo "<h2>📊 Database Data Check</h2>";
echo "<hr>";

try {
    $db = getDB();
    
    // Check total projects
    $stmt = $db->query("SELECT COUNT(*) as total FROM projects");
    $total_projects = $stmt->fetch()['total'];
    echo "<h3>📋 Total Projects: $total_projects</h3>";
    
    if ($total_projects > 0) {
        // Check projects with contractor names
        $stmt = $db->query("SELECT COUNT(*) as total FROM projects WHERE contractor_name IS NOT NULL AND contractor_name != ''");
        $projects_with_contractors = $stmt->fetch()['total'];
        echo "<h3>🏗️ Projects with Contractor Names: $projects_with_contractors</h3>";
        
        if ($projects_with_contractors > 0) {
            // Show sample contractors
            $stmt = $db->query("
                SELECT 
                    contractor_name, 
                    COUNT(*) as project_count,
                    SUM(project_value) as total_value
                FROM projects 
                WHERE contractor_name IS NOT NULL AND contractor_name != ''
                GROUP BY contractor_name 
                ORDER BY total_value DESC 
                LIMIT 5
            ");
            $contractors = $stmt->fetchAll();
            
            echo "<h3>🏆 Top 5 Contractors:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Contractor Name</th><th>Projects</th><th>Total Value</th></tr>";
            foreach ($contractors as $contractor) {
                $formatted_value = number_format($contractor['total_value'], 2);
                echo "<tr>";
                echo "<td>" . htmlspecialchars($contractor['contractor_name']) . "</td>";
                echo "<td>" . $contractor['project_count'] . "</td>";
                echo "<td>₱" . $formatted_value . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No projects have contractor names</p>";
            echo "<p>You need to add contractor names to your projects for the contractors list to work.</p>";
        }
        
        // Check project statuses
        $stmt = $db->query("
            SELECT status, COUNT(*) as count 
            FROM projects 
            GROUP BY status 
            ORDER BY count DESC
        ");
        $statuses = $stmt->fetchAll();
        
        echo "<h3>📈 Project Statuses:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        foreach ($statuses as $status) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($status['status']) . "</td>";
            echo "<td>" . $status['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ No projects found in database</p>";
        echo "<p>You need to add some projects first.</p>";
        echo "<p><a href='pages/encode.php'>Add projects here</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>🔧 Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='debug_contractors.php'>Test Contractors API</a></li>";
echo "<li><a href='pages/reports.php'>Test Reports Page</a></li>";
echo "<li><a href='pages/encode.php'>Add More Projects</a></li>";
echo "</ul>";
?>