<?php
/**
 * Migration Script: Add is_actual_project column to projects table
 * Run this once to add the column for tracking legitimate vs illegitimate projects
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

try {
    $pdo = getDB();
    
    echo "Starting migration: Add is_actual_project column...\n";
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM projects LIKE 'is_actual_project'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "✓ Column 'is_actual_project' already exists. No action needed.\n";
    } else {
        // Add the column
        $pdo->exec("
            ALTER TABLE projects 
            ADD COLUMN is_actual_project ENUM('yes', 'no', 'pending') DEFAULT 'pending' AFTER tracking_status
        ");
        echo "✓ Column 'is_actual_project' added successfully.\n";
        
        // Add index
        $pdo->exec("CREATE INDEX idx_is_actual_project ON projects(is_actual_project)");
        echo "✓ Index added successfully.\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
