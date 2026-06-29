<?php
/* Test if is_priority_encoded field exists and has data */
require_once __DIR__ . '/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "<h2>Testing is_priority_encoded field</h2>";
    
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'is_priority_encoded'");
    $col = $stmt->fetch();
    
    if (!$col) {
        echo "<p style='color:red;'>❌ Column 'is_priority_encoded' DOES NOT EXIST!</p>";
        echo "<p>Run migration: <a href='/migrate-is-priority-encoded.php'>Click here</a></p>";
        exit;
    }
    
    echo "<p style='color:green;'>✅ Column 'is_priority_encoded' exists</p>";
    echo "<pre>" . print_r($col, true) . "</pre>";
    
    // Count projects by is_priority_encoded
    echo "<h3>Project counts:</h3>";
    $stmt = $db->query("
        SELECT 
            is_priority_encoded,
            COUNT(*) as count
        FROM projects
        WHERE archived_at IS NULL
        GROUP BY is_priority_encoded
    ");
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>is_priority_encoded</th><th>Count</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['is_priority_encoded']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample priority projects
    echo "<h3>Sample Priority Encoded Projects:</h3>";
    $stmt = $db->query("
        SELECT id, contractor_name, project_name, status, is_priority_encoded
        FROM projects
        WHERE is_priority_encoded = 'yes'
        AND archived_at IS NULL
        LIMIT 10
    ");
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Contractor</th><th>Project</th><th>Status</th><th>is_priority_encoded</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['contractor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['project_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['is_priority_encoded']) . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample non-priority projects
    echo "<h3>Sample Non-Priority Encoded Projects:</h3>";
    $stmt = $db->query("
        SELECT id, contractor_name, project_name, status, is_priority_encoded
        FROM projects
        WHERE (is_priority_encoded = 'no' OR is_priority_encoded IS NULL)
        AND archived_at IS NULL
        LIMIT 10
    ");
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Contractor</th><th>Project</th><th>Status</th><th>is_priority_encoded</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['contractor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['project_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['is_priority_encoded'] ?? 'NULL') . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}
