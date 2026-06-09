<?php
/**
 * Check and Fix Archive Columns
 * 
 * This script checks if the archived_at and archived_by columns exist
 * in the projects table and adds them if they don't.
 * 
 * Usage: Run this file in your browser: http://your-domain.com/DATAS/check-and-fix-archive-columns.php
 */

// Load database connection
require_once __DIR__ . '/api/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Archive Columns Migration</title>\n";
echo "<style>\n";
echo "body { font-family: monospace; padding: 2rem; background: #1e293b; color: #e2e8f0; }\n";
echo ".success { color: #10b981; }\n";
echo ".error { color: #ef4444; }\n";
echo ".info { color: #3b82f6; }\n";
echo ".warning { color: #f59e0b; }\n";
echo "pre { background: #0f172a; padding: 1rem; border-radius: 0.5rem; border: 1px solid #334155; }\n";
echo "</style>\n";
echo "</head><body>\n";

echo "<h1>🔧 Archive Columns Migration Tool</h1>\n";
echo "<hr>\n";

try {
    $pdo = getDB();
    
    echo "<h2 class='info'>Step 1: Checking database connection...</h2>\n";
    echo "<p class='success'>✓ Database connection successful!</p>\n";
    
    echo "<h2 class='info'>Step 2: Checking if columns exist...</h2>\n";
    
    // Check if archived_at column exists
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'projects' 
        AND COLUMN_NAME = 'archived_at'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $archivedAtExists = $result['count'] > 0;
    
    // Check if archived_by column exists
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'projects' 
        AND COLUMN_NAME = 'archived_by'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $archivedByExists = $result['count'] > 0;
    
    echo "<pre>\n";
    echo "archived_at column exists: " . ($archivedAtExists ? '<span class="success">YES ✓</span>' : '<span class="warning">NO ✗</span>') . "\n";
    echo "archived_by column exists: " . ($archivedByExists ? '<span class="success">YES ✓</span>' : '<span class="warning">NO ✗</span>') . "\n";
    echo "</pre>\n";
    
    // Add columns if they don't exist
    if (!$archivedAtExists || !$archivedByExists) {
        echo "<h2 class='warning'>Step 3: Adding missing columns...</h2>\n";
        
        if (!$archivedAtExists) {
            echo "<p class='info'>Adding archived_at column...</p>\n";
            $pdo->exec("
                ALTER TABLE projects 
                ADD COLUMN archived_at DATETIME DEFAULT NULL 
                AFTER encoded_by
            ");
            echo "<p class='success'>✓ archived_at column added successfully!</p>\n";
        }
        
        if (!$archivedByExists) {
            echo "<p class='info'>Adding archived_by column...</p>\n";
            $pdo->exec("
                ALTER TABLE projects 
                ADD COLUMN archived_by INT(10) UNSIGNED DEFAULT NULL 
                AFTER archived_at
            ");
            echo "<p class='success'>✓ archived_by column added successfully!</p>\n";
        }
        
        echo "<h2 class='info'>Step 4: Adding indexes...</h2>\n";
        
        // Try to add indexes (ignore if they already exist)
        try {
            $pdo->exec("CREATE INDEX idx_archived_at ON projects(archived_at)");
            echo "<p class='success'>✓ Index idx_archived_at created!</p>\n";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate key name')) {
                echo "<p class='info'>→ Index idx_archived_at already exists (skipped)</p>\n";
            } else {
                throw $e;
            }
        }
        
        try {
            $pdo->exec("CREATE INDEX idx_archived_by ON projects(archived_by)");
            echo "<p class='success'>✓ Index idx_archived_by created!</p>\n";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate key name')) {
                echo "<p class='info'>→ Index idx_archived_by already exists (skipped)</p>\n";
            } else {
                throw $e;
            }
        }
        
    } else {
        echo "<h2 class='success'>Step 3: All columns already exist!</h2>\n";
        echo "<p>No migration needed. ✓</p>\n";
    }
    
    echo "<h2 class='info'>Step 5: Verifying final state...</h2>\n";
    
    // Verify columns
    $stmt = $pdo->query("
        SELECT 
            COLUMN_NAME, 
            COLUMN_TYPE, 
            IS_NULLABLE, 
            COLUMN_DEFAULT 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'projects' 
        AND COLUMN_NAME IN ('archived_at', 'archived_by')
        ORDER BY ORDINAL_POSITION
    ");
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>\n";
    echo "<strong>Projects Table - Archive Columns:</strong>\n";
    echo "----------------------------------------\n";
    foreach ($columns as $col) {
        echo sprintf(
            "%-15s | %-20s | Nullable: %-3s | Default: %s\n",
            $col['COLUMN_NAME'],
            $col['COLUMN_TYPE'],
            $col['IS_NULLABLE'],
            $col['COLUMN_DEFAULT'] ?? 'NULL'
        );
    }
    echo "</pre>\n";
    
    echo "<hr>\n";
    echo "<h2 class='success'>✅ Migration completed successfully!</h2>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Go back to your application: <a href='./projects-management?view=archived' style='color: #3b82f6;'>View Archived Projects</a></li>\n";
    echo "<li>Try archiving a project to test the functionality</li>\n";
    echo "<li>For security, you can delete this file: check-and-fix-archive-columns.php</li>\n";
    echo "</ol>\n";
    
} catch (PDOException $e) {
    echo "<h2 class='error'>❌ Database Error</h2>\n";
    echo "<pre class='error'>\n";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "</pre>\n";
    echo "<p><strong>Troubleshooting:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Check your database credentials in config.php</li>\n";
    echo "<li>Ensure your database user has ALTER TABLE permissions</li>\n";
    echo "<li>Check if the database is accessible</li>\n";
    echo "</ul>\n";
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Unexpected Error</h2>\n";
    echo "<pre class='error'>\n";
    echo htmlspecialchars($e->getMessage()) . "\n";
    echo "</pre>\n";
}

echo "</body></html>\n";
?>
