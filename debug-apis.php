<?php
// Simple test page to debug API endpoints
require_once __DIR__ . '/api/db.php';

echo "<h1>API Debug Test</h1>";

try {
    $db = getDB();
    echo "<p>✅ Database connection: OK</p>";
    
    // Test unprocessed projects query
    echo "<h2>Unprocessed Projects Query Test</h2>";
    $sql = "
        SELECT COUNT(*) as cnt 
        FROM projects p
        LEFT JOIN sales_tracking st ON p.id = st.project_id
        WHERE p.assigned_to IS NOT NULL AND st.project_id IS NULL
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $count = $stmt->fetch()['cnt'];
    
    echo "<p>Unprocessed projects count: $count</p>";
    echo "<p>Query: <code>$sql</code></p>";
    
    // Test processed projects query
    echo "<h2>Processed Projects Query Test</h2>";
    $sql2 = "
        SELECT COUNT(*) as cnt 
        FROM projects p
        LEFT JOIN sales_tracking st ON p.id = st.project_id
        WHERE st.project_id IS NOT NULL
    ";
    
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute();
    $count2 = $stmt2->fetch()['cnt'];
    
    echo "<p>Processed projects count: $count2</p>";
    echo "<p>Query: <code>$sql2</code></p>";
    
    // Show some sample data
    echo "<h2>Sample Projects with Assignment Status</h2>";
    $sampleSql = "
        SELECT p.id, p.project_name, p.assigned_to, st.project_id as has_tracking
        FROM projects p
        LEFT JOIN sales_tracking st ON p.id = st.project_id
        LIMIT 5
    ";
    
    $sampleStmt = $db->prepare($sampleSql);
    $sampleStmt->execute();
    $samples = $sampleStmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Project Name</th><th>Assigned To</th><th>Has Tracking</th><th>Category</th></tr>";
    foreach ($samples as $row) {
        $category = '';
        if ($row['assigned_to'] === null) {
            $category = 'Unassigned';
        } elseif ($row['has_tracking'] === null) {
            $category = 'Unprocessed';
        } else {
            $category = 'Processed';
        }
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['project_name']}</td>";
        echo "<td>" . ($row['assigned_to'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['has_tracking'] ?? 'NULL') . "</td>";
        echo "<td><strong>$category</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>