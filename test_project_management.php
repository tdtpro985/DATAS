<?php
/* ============================================================
   Test Project Management API responses
   ============================================================ */

require_once 'api/db.php';
require_once 'api/helpers.php';

echo "<h1>Testing Project Management APIs</h1>\n";
echo "<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;} .view{margin:2rem 0;padding:1rem;border:1px solid #334155;border-radius:0.5rem;} .project{margin:0.5rem 0;padding:0.5rem;background:#1e293b;border-radius:0.25rem;} .count{color:#fbbf24;font-weight:bold;}</style>\n";

$db = getDB();

// Test each view
$views = ['unassigned', 'assigned', 'unprocessed', 'processed'];

foreach ($views as $view) {
    echo "<div class='view'>";
    echo "<h2>📊 " . ucfirst($view) . " Projects</h2>";
    
    try {
        // Build query based on view
        switch($view) {
            case 'unassigned':
                $where = 'p.assigned_to IS NULL';
                break;
            case 'assigned':
                $where = 'p.assigned_to IS NOT NULL';
                break;
            case 'unprocessed':
                $where = 'p.assigned_to IS NOT NULL AND st.project_id IS NULL';
                break;
            case 'processed':
                $where = 'st.project_id IS NOT NULL';
                break;
        }
        
        // Get count
        $countStmt = $db->prepare("
            SELECT COUNT(*) as cnt 
            FROM projects p
            LEFT JOIN sales_tracking st ON p.id = st.project_id
            WHERE $where
        ");
        $countStmt->execute();
        $count = $countStmt->fetch()['cnt'];
        
        echo "<p><span class='count'>Count: $count</span></p>";
        
        // Get sample projects (limit 3)
        $stmt = $db->prepare("
            SELECT 
                p.id,
                p.contractor_name,
                p.project_name,
                p.assigned_to,
                u_assigned.full_name as assigned_to_name,
                st.tracking_status,
                CASE 
                    WHEN p.assigned_to IS NULL THEN 'Unassigned'
                    WHEN p.assigned_to IS NOT NULL AND st.project_id IS NULL THEN 'Assigned but Unprocessed'
                    WHEN st.project_id IS NOT NULL THEN 'Processed'
                    ELSE 'Unknown'
                END as calculated_status
            FROM projects p
            LEFT JOIN users u_assigned ON p.assigned_to = u_assigned.id
            LEFT JOIN sales_tracking st ON p.id = st.project_id
            WHERE $where
            ORDER BY p.created_at DESC
            LIMIT 3
        ");
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
        foreach ($projects as $project) {
            echo "<div class='project'>";
            echo "<strong>#{$project['id']}</strong> - {$project['contractor_name']} | {$project['project_name']}<br>";
            echo "Assigned to: " . ($project['assigned_to_name'] ?: 'None') . "<br>";
            echo "Tracking Status: " . ($project['tracking_status'] ?: 'Not Started') . "<br>";
            echo "Calculated Status: {$project['calculated_status']}";
            echo "</div>";
        }
        
        if (empty($projects)) {
            echo "<p>No projects found in this category.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:#ef4444;'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// Test sales tracking table structure
echo "<div class='view'>";
echo "<h2>🔍 Sales Tracking Table Structure</h2>";
try {
    $stmt = $db->query("SHOW COLUMNS FROM sales_tracking");
    $columns = $stmt->fetchAll();
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:#ef4444;'>Error checking sales_tracking table: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<h3>✅ Test completed. Check the counts and sample data above.</h3>";
?>