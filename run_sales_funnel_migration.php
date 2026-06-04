<?php
/**
 * Run Sales Funnel Migration
 * This script adds the required fields to the sales_tracking table
 * Access via: http://localhost/DATAS/run_sales_funnel_migration.php
 */

require_once 'config.php';
require_once 'api/db.php';

echo "<h2>🔧 Sales Funnel Migration</h2>";
echo "<p>Adding required fields to sales_tracking table...</p>";
echo "<hr>";

try {
    $db = getDB();
    
    // Check if columns already exist
    $stmt = $db->query("DESCRIBE sales_tracking");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['contacted', 'quoted', 'sales_qualified', 'to_win', 'wa_amount', 'remarks'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✅ All required columns already exist!</p>";
        echo "<h3>📋 Current sales_tracking columns:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            $highlight = in_array($column, $requiredColumns) ? ' style="color: green; font-weight: bold;"' : '';
            echo "<li$highlight>$column</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Missing columns: " . implode(', ', $missingColumns) . "</p>";
        echo "<p>Running migration...</p>";
        
        // Read and execute migration
        $migrationSql = file_get_contents(__DIR__ . '/database/migration_add_sales_funnel_fields.sql');
        
        // Remove comments and split by semicolon
        $statements = array_filter(
            array_map('trim', explode(';', $migrationSql)),
            function($stmt) {
                return !empty($stmt) && !str_starts_with($stmt, '--') && !str_starts_with($stmt, 'COMMIT');
            }
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                echo "<p>Executing: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
                $db->exec($statement);
            }
        }
        
        echo "<p style='color: green;'>✅ Migration completed successfully!</p>";
        
        // Verify columns were added
        $stmt = $db->query("DESCRIBE sales_tracking");
        $newColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $addedColumns = array_intersect($requiredColumns, $newColumns);
        
        echo "<h3>📋 Added columns:</h3>";
        echo "<ul>";
        foreach ($addedColumns as $column) {
            echo "<li style='color: green; font-weight: bold;'>$column</li>";
        }
        echo "</ul>";
    }
    
    // Check current data
    $stmt = $db->query("SELECT COUNT(*) as total FROM sales_tracking");
    $totalTracking = $stmt->fetch()['total'];
    
    echo "<h3>📊 Current Data:</h3>";
    echo "<p><strong>Total sales tracking records:</strong> $totalTracking</p>";
    
    if ($totalTracking > 0) {
        $stmt = $db->query("
            SELECT 
                SUM(CASE WHEN contacted = 'Yes' THEN 1 ELSE 0 END) as contacted,
                SUM(CASE WHEN quoted = 'Yes' THEN 1 ELSE 0 END) as quoted,
                SUM(CASE WHEN sales_qualified = 'Yes' THEN 1 ELSE 0 END) as sql_yes,
                SUM(CASE WHEN sales_qualified = 'No' THEN 1 ELSE 0 END) as sql_no,
                SUM(CASE WHEN to_win = 'Yes' THEN 1 ELSE 0 END) as wins
            FROM sales_tracking
        ");
        $stats = $stmt->fetch();
        
        echo "<ul>";
        echo "<li><strong>Contacted:</strong> " . $stats['contacted'] . "</li>";
        echo "<li><strong>Quoted:</strong> " . $stats['quoted'] . "</li>";
        echo "<li><strong>Sales Qualified Leads:</strong> " . $stats['sql_yes'] . "</li>";
        echo "<li><strong>Not Sales Qualified:</strong> " . $stats['sql_no'] . "</li>";
        echo "<li><strong>Wins:</strong> " . $stats['wins'] . "</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>🔧 Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='pages/reports.php'>Test the updated sales funnel</a></li>";
echo "<li><a href='pages/projects.php'>Add sales tracking to projects</a></li>";
echo "<li><a href='debug_contractors.php'>Debug other issues</a></li>";
echo "</ul>";
?>