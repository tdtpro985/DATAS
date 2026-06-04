<?php
/* ============================================================
   Fix Location Fields Script
   ============================================================
   Manually adds location fields one by one
   ============================================================ */

require_once __DIR__ . '/config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n\n";
    
    // List of fields to add
    $fields = [
        'contract_country' => 'VARCHAR(100) NULL',
        'contract_region' => 'VARCHAR(100) NULL',
        'contract_province' => 'VARCHAR(100) NULL',
        'contract_city' => 'VARCHAR(100) NULL',
        'contract_barangay' => 'VARCHAR(100) NULL',
        'contract_street' => 'VARCHAR(255) NULL',
        'contract_blk_lot' => 'VARCHAR(100) NULL',
        'contract_coordinates' => 'VARCHAR(255) NULL',
        'project_country' => 'VARCHAR(100) NULL',
        'project_region' => 'VARCHAR(100) NULL',
        'project_province' => 'VARCHAR(100) NULL',
        'project_city' => 'VARCHAR(100) NULL',
        'project_barangay' => 'VARCHAR(100) NULL',
        'project_street' => 'VARCHAR(255) NULL',
        'project_blk_lot' => 'VARCHAR(100) NULL',
        'project_coordinates' => 'VARCHAR(255) NULL'
    ];
    
    foreach ($fields as $fieldName => $fieldType) {
        try {
            $sql = "ALTER TABLE projects ADD COLUMN $fieldName $fieldType";
            $pdo->exec($sql);
            echo "✓ Added field: $fieldName\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠ Field already exists: $fieldName\n";
            } else {
                echo "❌ Failed to add field $fieldName: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Location fields setup completed!\n";
    
    // Now check the table structure
    echo "\nChecking table structure:\n";
    $stmt = $pdo->query("DESCRIBE projects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $locationFields = 0;
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'contract_') === 0 || strpos($column['Field'], 'project_') === 0) {
            echo "   ✓ {$column['Field']}\n";
            $locationFields++;
        }
    }
    
    echo "\nTotal location fields added: $locationFields\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>