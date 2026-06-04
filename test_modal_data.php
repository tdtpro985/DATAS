<?php
// Simple test script to check project data structure
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/helpers.php';

// Get a sample project from unassigned
$db = getDB();

$stmt = $db->prepare("
    SELECT p.*, u.full_name as encoded_by_name
    FROM projects p
    LEFT JOIN users u ON p.encoded_by = u.id
    WHERE p.assigned_to IS NULL
    LIMIT 1
");
$stmt->execute();
$project = $stmt->fetch();

echo "<h2>Sample Project Data Structure</h2>";
echo "<pre>";
print_r($project);
echo "</pre>";

// Test sales tracking API
if ($project) {
    echo "<h2>Testing Sales Tracking API for Project ID: " . $project['id'] . "</h2>";
    
    // Check if tracking exists
    $trackingStmt = $db->prepare("SELECT * FROM sales_tracking WHERE project_id = :id LIMIT 1");
    $trackingStmt->execute([':id' => $project['id']]);
    $tracking = $trackingStmt->fetch();
    
    if ($tracking) {
        echo "<h3>Existing Sales Tracking Data:</h3>";
        echo "<pre>";
        print_r($tracking);
        echo "</pre>";
    } else {
        echo "<p>No existing sales tracking data for this project.</p>";
    }
    
    // Check table structure
    echo "<h3>Sales Tracking Table Structure:</h3>";
    $structureStmt = $db->query("DESCRIBE sales_tracking");
    $structure = $structureStmt->fetchAll();
    echo "<pre>";
    foreach ($structure as $column) {
        echo $column['Field'] . " - " . $column['Type'] . " - " . ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " - Default: " . $column['Default'] . "\n";
    }
    echo "</pre>";
}
?>