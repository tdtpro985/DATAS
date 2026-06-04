<?php
/**
 * Platform Leads Migration Runner
 */

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    echo "Connected to database successfully.\n";
    
    // Read and execute the migration
    $sql = file_get_contents(__DIR__ . '/database/platform_leads_migration.sql');
    
    if ($sql === false) {
        throw new Exception('Could not read migration file');
    }
    
    echo "Executing platform_leads migration...\n";
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        $pdo->exec($statement);
    }
    
    echo "✅ Platform leads migration completed successfully!\n";
    echo "\nTable created:\n";
    echo "- platform_leads (for storing platform lead information)\n";
    echo "\nYou can now:\n";
    echo "1. Access Platform Leads at: /platforms\n";
    echo "2. Encode new Platform Leads at: /encode-platforms\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>