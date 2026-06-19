<?php
/**
 * Migration: Create platform_tracking table
 * Run this file once via: php migrate-platform-tracking.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

echo "=== Platform Tracking Table Migration ===\n";
echo "Starting migration...\n\n";

try {
    $db = getDB();
    
    // Check if table already exists
    $stmt = $db->query("SHOW TABLES LIKE 'platform_tracking'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "✓ Table 'platform_tracking' already exists. Skipping creation.\n";
    } else {
        echo "Creating 'platform_tracking' table...\n";
        
        $sql = file_get_contents(__DIR__ . '/database/create-platform-tracking-table.sql');
        $db->exec($sql);
        
        echo "✓ Table 'platform_tracking' created successfully!\n";
    }
    
    // Verify table structure
    echo "\nVerifying table structure...\n";
    $stmt = $db->query("DESCRIBE platform_tracking");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in platform_tracking:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n=== Migration completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
