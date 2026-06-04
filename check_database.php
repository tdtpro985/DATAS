<?php
/* ============================================================
   Check Database Script
   ============================================================
   Checks the current state of the database
   ============================================================ */

require_once __DIR__ . '/config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Database Check ===\n\n";
    
    // Check if projects table exists and show structure
    echo "1. Projects table structure:\n";
    $stmt = $pdo->query("DESCRIBE projects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'contract_') === 0 || strpos($column['Field'], 'project_') === 0) {
            echo "   ✓ {$column['Field']} ({$column['Type']})\n";
        }
    }
    
    // Check number of projects
    echo "\n2. Projects count:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM projects");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Total projects: $count\n";
    
    if ($count > 0) {
        echo "\n3. Sample project data:\n";
        $stmt = $pdo->query("SELECT id, contractor_name, project_name, contract_country, project_region FROM projects LIMIT 3");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($projects as $project) {
            echo "   ID: {$project['id']} | {$project['contractor_name']} | {$project['project_name']}\n";
            echo "      Contract Country: " . ($project['contract_country'] ?: 'NULL') . "\n";
            echo "      Project Region: " . ($project['project_region'] ?: 'NULL') . "\n";
        }
    }
    
    echo "\n✅ Database check completed!\n";
    
} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "\n";
}
?>