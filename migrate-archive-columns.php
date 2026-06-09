<?php
/**
 * Migration Script: Add Archive Columns to Projects Table
 * 
 * This script adds archived_at and archived_by columns to the projects table
 * if they don't already exist.
 * 
 * Access via browser: http://localhost/migrate-archive-columns.php
 */

// Check if accessed via browser
$isBrowser = php_sapi_name() !== 'cli';

if ($isBrowser) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Archive Columns</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: "Courier New", monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .info {
            color: #17a2b8;
        }
        .step {
            margin: 10px 0;
            padding-left: 20px;
        }
        .back-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Database Migration</h1>
        <div class="subtitle">Adding Archive Columns to Projects Table</div>
        <div class="output">';
}

function outputLine($message, $type = 'info') {
    global $isBrowser;
    
    if ($isBrowser) {
        $class = $type;
        echo "<div class='step $class'>$message</div>";
        flush();
        ob_flush();
    } else {
        echo $message . "\n";
    }
}

if ($isBrowser) {
    ob_start();
}

outputLine("=== Archive Columns Migration ===", 'info');
outputLine("");

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

try {
    $db = getDB();
    
    outputLine("✓ Database connection established", 'success');
    outputLine("Checking projects table structure...", 'info');
    
    // Check if archived_at column exists
    $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'archived_at'");
    $archivedAtExists = $stmt->fetch() !== false;
    
    // Check if archived_by column exists
    $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'archived_by'");
    $archivedByExists = $stmt->fetch() !== false;
    
    if ($archivedAtExists && $archivedByExists) {
        outputLine("✓ Archive columns already exist. No migration needed.", 'success');
        outputLine("");
        outputLine("Database is up to date!", 'success');
    } else {
        outputLine("Adding archive columns to projects table...", 'info');
        outputLine("");
        
        // Add archived_at column if it doesn't exist
        if (!$archivedAtExists) {
            outputLine("Adding archived_at column...", 'info');
            $db->exec("
                ALTER TABLE projects 
                ADD COLUMN archived_at DATETIME DEFAULT NULL
                AFTER encoded_by
            ");
            outputLine("✓ archived_at column added successfully", 'success');
        } else {
            outputLine("✓ archived_at column already exists", 'success');
        }
        
        // Add archived_by column if it doesn't exist
        if (!$archivedByExists) {
            outputLine("Adding archived_by column...", 'info');
            $db->exec("
                ALTER TABLE projects 
                ADD COLUMN archived_by INT(10) UNSIGNED DEFAULT NULL
                AFTER archived_at
            ");
            outputLine("✓ archived_by column added successfully", 'success');
        } else {
            outputLine("✓ archived_by column already exists", 'success');
        }
        
        // Add indexes if they don't exist
        outputLine("", 'info');
        outputLine("Adding indexes...", 'info');
        
        try {
            $db->exec("ALTER TABLE projects ADD INDEX idx_archived_at (archived_at)");
            outputLine("✓ idx_archived_at index added", 'success');
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                outputLine("✓ idx_archived_at index already exists", 'success');
            } else {
                throw $e;
            }
        }
        
        try {
            $db->exec("ALTER TABLE projects ADD INDEX idx_archived_by (archived_by)");
            outputLine("✓ idx_archived_by index added", 'success');
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                outputLine("✓ idx_archived_by index already exists", 'success');
            } else {
                throw $e;
            }
        }
        
        outputLine("", 'info');
        outputLine("=== Migration completed successfully! ===", 'success');
        outputLine("", 'info');
        outputLine("You can now archive projects in the DATAS system.", 'success');
    }
    
} catch (PDOException $e) {
    outputLine("", 'info');
    outputLine("✗ Migration failed!", 'error');
    outputLine("Database Error: " . $e->getMessage(), 'error');
    if ($isBrowser) {
        echo '</div>';
        echo '<a href="/" class="back-btn">← Back to Dashboard</a>';
        echo '</div></body></html>';
    }
    exit(1);
} catch (Exception $e) {
    outputLine("", 'info');
    outputLine("✗ Migration failed!", 'error');
    outputLine("Error: " . $e->getMessage(), 'error');
    if ($isBrowser) {
        echo '</div>';
        echo '<a href="/" class="back-btn">← Back to Dashboard</a>';
        echo '</div></body></html>';
    }
    exit(1);
}

if ($isBrowser) {
    echo '</div>';
    echo '<a href="/" class="back-btn">← Back to Dashboard</a>';
    echo '</div></body></html>';
}

