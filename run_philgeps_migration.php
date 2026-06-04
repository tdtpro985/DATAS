<?php
/**
 * Migration Runner: Add PHILGEPS source and Notice Reference Number field
 * Run this script once to apply the database changes
 */

require_once 'config.php';

try {
    // Read the migration SQL
    $migrationSQL = file_get_contents(__DIR__ . '/database/add_philgeps_source_migration.sql');
    
    if (!$migrationSQL) {
        throw new Exception('Could not read migration file');
    }
    
    echo "<h2>🔄 Running PHILGEPS Migration</h2>";
    echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
    // Connect to database using constants from config.php
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "<h3>📋 Migration Steps:</h3>";
    echo "<ul>";
    
    // Split migration into individual statements
    $statements = explode(';', $migrationSQL);
    $executed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Extract action from statement for reporting
            if (strpos($statement, 'ADD COLUMN') !== false) {
                echo "<li>✅ Added notice_reference_number column to projects table</li>";
            } elseif (strpos($statement, 'ADD INDEX') !== false) {
                echo "<li>✅ Added index for notice_reference_number field</li>";
            } elseif (strpos($statement, 'MODIFY COLUMN') !== false) {
                echo "<li>✅ Added column comment and constraints</li>";
            } else {
                echo "<li>✅ Executed statement successfully</li>";
            }
            
        } catch (PDOException $e) {
            // Check if error is about column already existing
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<li>⚠️ notice_reference_number column already exists - skipping</li>";
            } elseif (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<li>⚠️ Index already exists - skipping</li>";
            } else {
                echo "<li>❌ Error: " . htmlspecialchars($e->getMessage()) . "</li>";
            }
        }
    }
    
    echo "</ul>";
    
    // Verify the changes
    echo "<h3>🔍 Verification:</h3>";
    echo "<ul>";
    
    // Check if column was added
    $result = $pdo->query("SHOW COLUMNS FROM projects LIKE 'notice_reference_number'")->fetch();
    if ($result) {
        echo "<li>✅ notice_reference_number column exists</li>";
        echo "<li>📋 Column details: " . htmlspecialchars($result['Type'] . ' ' . $result['Null'] . ' ' . $result['Default']) . "</li>";
    } else {
        echo "<li>❌ notice_reference_number column not found</li>";
    }
    
    // Check if index was added  
    $result = $pdo->query("SHOW INDEX FROM projects WHERE Key_name = 'idx_notice_reference_number'")->fetch();
    if ($result) {
        echo "<li>✅ Index idx_notice_reference_number exists</li>";
    } else {
        echo "<li>❌ Index idx_notice_reference_number not found</li>";
    }
    
    echo "</ul>";
    
    echo "<h3>🎉 Migration Completed!</h3>";
    echo "<p><strong>Executed:</strong> {$executed} statements</p>";
    echo "<p><strong>Status:</strong> Ready to use PHILGEPS source with Notice Reference Number</p>";
    
    echo "<hr>";
    echo "<h4>🔄 Next Steps:</h4>";
    echo "<ol>";
    echo "<li>Update form interfaces to include PHILGEPS source option</li>";
    echo "<li>Add conditional Notice Reference Number field when PHILGEPS is selected</li>";
    echo "<li>Add validation for 5-digit numeric format</li>";
    echo "<li>Update project management and encoding pages</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h3>❌ Migration Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem; }
li { margin: 0.5rem 0; }
.code { background: #f4f4f4; padding: 0.5rem; border-radius: 4px; font-family: monospace; }
</style>";
?>