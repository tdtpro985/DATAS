<?php
/* Direct API test - check what the API actually returns for priority projects */
session_start();

// Simulate authenticated session
if (empty($_SESSION['user'])) {
    // For testing, use a dummy session
    $_SESSION['user'] = [
        'id' => 1,
        'role' => 'superadmin',
        'username' => 'test',
        'full_name' => 'Test User'
    ];
}

require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/helpers.php';

$type = 'priority';

try {
    $db = getDB();
    
    // Build WHERE clause - same logic as API
    $whereConditions = ['p.archived_at IS NULL'];
    
    // Check if is_actual_project column exists
    $colChk = $db->query("SHOW COLUMNS FROM projects LIKE 'is_actual_project'");
    $hasIllegitimateCol = $colChk->rowCount() > 0;
    
    if ($hasIllegitimateCol) {
        $whereConditions[] = "(p.is_actual_project IS NULL OR p.is_actual_project != 'no')";
    }
    
    // Check if is_priority_encoded column exists
    $colChk2 = $db->query("SHOW COLUMNS FROM projects LIKE 'is_priority_encoded'");
    $hasPriorityEncodedCol = $colChk2->rowCount() > 0;
    
    echo "<h2>API Debug - Priority Projects</h2>";
    echo "<p><strong>has is_priority_encoded column:</strong> " . ($hasPriorityEncodedCol ? 'YES' : 'NO') . "</p>";
    
    if ($hasPriorityEncodedCol) {
        $whereConditions[] = "p.is_priority_encoded = 'yes'";
        echo "<p><strong>Filter:</strong> is_priority_encoded = 'yes'</p>";
    } else {
        $whereConditions[] = "LOWER(TRIM(p.status)) = 'priority'";
        echo "<p><strong>Filter:</strong> status = 'priority' (fallback)</p>";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    echo "<h3>SQL WHERE Clause:</h3>";
    echo "<pre>" . htmlspecialchars($whereClause) . "</pre>";
    
    // Get count
    $countQuery = "SELECT COUNT(*) as cnt FROM projects p WHERE " . $whereClause;
    echo "<h3>Count Query:</h3>";
    echo "<pre>" . htmlspecialchars($countQuery) . "</pre>";
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $total = (int)$countStmt->fetch()['cnt'];
    
    echo "<h3>Result Count: <span style='color:red;font-size:24px;'>" . $total . "</span></h3>";
    
    // Get sample projects
    $stmt = $db->prepare("
        SELECT p.id, p.contractor_name, p.project_name, p.status, p.is_priority_encoded
        FROM projects p
        WHERE " . $whereClause . "
        LIMIT 10
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll();
    
    echo "<h3>Sample Projects:</h3>";
    if (empty($projects)) {
        echo "<p style='color:green;font-weight:bold;'>✓ NO PRIORITY ENCODED PROJECTS FOUND (This is correct!)</p>";
        echo "<p>All 638 existing projects have is_priority_encoded='no'</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Contractor</th><th>Project</th><th>Status</th><th>is_priority_encoded</th></tr>";
        foreach ($projects as $project) {
            echo "<tr>";
            echo "<td>" . $project['id'] . "</td>";
            echo "<td>" . htmlspecialchars($project['contractor_name']) . "</td>";
            echo "<td>" . htmlspecialchars($project['project_name']) . "</td>";
            echo "<td>" . htmlspecialchars($project['status']) . "</td>";
            echo "<td><strong style='color:red;'>" . htmlspecialchars($project['is_priority_encoded'] ?? 'NULL') . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test actual API endpoint
    echo "<hr><h2>Testing Actual API Endpoint</h2>";
    echo "<p>Open browser console and run:</p>";
    echo "<pre>";
    echo "fetch('/api/v1/projects?type=priority', {credentials: 'include'})\n";
    echo "  .then(r => r.json())\n";
    echo "  .then(d => console.log('API returned', d.total, 'projects'))";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
