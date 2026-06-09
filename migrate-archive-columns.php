<?php
/**
 * Migration Script: Add Archive Columns to Projects Table
 * 
 * This script adds archived_at and archived_by columns to the projects table
 * if they don't already exist.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

echo "=== Archive Columns Migration ===\n\n";

try {
    $db = getDB();
    
    echo "Checking projects table structure...\n";
    
    // Check if archived_at column exists
    $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'archived_at'");
    $archivedAtExists = $stmt->fetch() !== false;
    
    // Check if archived_by column exists
    $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'archived_by'");
    $archivedByExists = $stmt->fetch() !== false;
    
    if ($archivedAtExists && $archivedByExists) {
        echo "✓ Archive columns already exist. No migration needed.\n";
        exit(0);
    }
    
    echo "Adding archive columns to projects table...\n\n";
    
    // Add archived_at column if it doesn't exist
    if (!$archivedAtExists) {
        echo "Adding archived_at column...\n";
        $db->exec("
            ALTER TABLE projects 
            ADD COLUMN archived_at DATETIME DEFAULT NULL
            AFTER encoded_by
        ");
        echo "✓ archived_at column added\n";
    } else {
        echo "✓ archived_at column already exists\n";
    }
    
    // Add archived_by column if it doesn't exist
    if (!$archivedByExists) {
        echo "Adding archived_by column...\n";
        $db->exec("
            ALTER TABLE projects 
            ADD COLUMN archived_by INT(10) UNSIGNED DEFAULT NULL
            AFTER archived_at
        ");
        echo "✓ archived_by column added\n";
    } else {
        echo "✓ archived_by column already exists\n";
    }
    
    // Add indexes if they don't exist
    echo "\nAdding indexes...\n";
    
    try {
        $db->exec("ALTER TABLE projects ADD INDEX idx_archived_at (archived_at)");
        echo "✓ idx_archived_at index added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ idx_archived_at index already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $db->exec("ALTER TABLE projects ADD INDEX idx_archived_by (archived_by)");
        echo "✓ idx_archived_by index added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ idx_archived_by index already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n=== Migration completed successfully! ===\n";
    echo "\nYou can now archive projects in the system.\n";
    
} catch (PDOException $e) {
    echo "\n✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
