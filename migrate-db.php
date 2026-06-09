<?php
/**
 * Database Migration - Add archived_at and archived_by columns to projects table
 * 
 * Usage: Open in browser once - http://localhost/DATAS/migrate-db.php
 * After successful migration, delete this file
 */

require_once 'config.php';

try {
    // Create PDO connection
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Check if columns already exist
    $checkStmt = $pdo->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'projects' 
        AND COLUMN_NAME IN ('archived_at', 'archived_by')
    ");
    $existingColumns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($existingColumns) === 2) {
        echo "<h2 style='color:#1976d2;'>ℹ️ Already Migrated</h2>";
        echo "<p>Columns <strong>archived_at</strong> and <strong>archived_by</strong> already exist.</p>";
        echo "<p>No action needed.</p>";
        echo "<p><a href='pages/admin.php' style='color:#1976d2; text-decoration:underline;'>→ Go to Admin</a></p>";
        exit;
    }
    
    // Run migration
    $sql = "ALTER TABLE projects
    ADD COLUMN archived_at DATETIME DEFAULT NULL,
    ADD COLUMN archived_by INT(10) UNSIGNED DEFAULT NULL,
    ADD INDEX idx_archived_at (archived_at),
    ADD INDEX idx_archived_by (archived_by)";
    
    $pdo->exec($sql);
    
    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
    echo "<title>Migration Success</title>";
    echo "<style>";
    echo "body { font-family: 'Inter', sans-serif; background: #f5f5f5; padding: 40px 20px; }";
    echo ".container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }";
    echo "h2 { color: #4caf50; margin: 0 0 15px 0; }";
    echo ".success-icon { font-size: 48px; margin-bottom: 20px; }";
    echo ".details { background: #f9fff9; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 4px; }";
    echo ".details p { margin: 5px 0; font-size: 14px; }";
    echo ".code { background: #f0f0f0; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 13px; }";
    echo "a { color: #1976d2; text-decoration: none; font-weight: 500; }";
    echo "a:hover { text-decoration: underline; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container'>";
    echo "<div class='success-icon'>✅</div>";
    echo "<h2>Migration Successful!</h2>";
    echo "<p>Database schema has been updated.</p>";
    echo "<div class='details'>";
    echo "<p><strong>Added Columns:</strong></p>";
    echo "<p class='code'>• archived_at (DATETIME)</p>";
    echo "<p class='code'>• archived_by (INT)</p>";
    echo "<p class='code'>• Indexes created</p>";
    echo "</div>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Delete this file (migrate-db.php) for security</li>";
    echo "<li><a href='pages/admin.php'>→ Go to Admin Dashboard</a></li>";
    echo "<li>Test the projects page</li>";
    echo "</ol>";
    echo "</div>";
    echo "</body>";
    echo "</html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>Migration Error</title>";
    echo "<style>";
    echo "body { font-family: 'Inter', sans-serif; background: #f5f5f5; padding: 40px 20px; }";
    echo ".container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }";
    echo "h2 { color: #d32f2f; margin: 0 0 15px 0; }";
    echo ".error { background: #ffebee; border-left: 4px solid #d32f2f; padding: 15px; margin: 20px 0; border-radius: 4px; }";
    echo ".error-code { background: #f0f0f0; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 13px; word-break: break-all; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container'>";
    echo "<h2>❌ Migration Failed</h2>";
    echo "<div class='error'>";
    echo "<p><strong>Error:</strong></p>";
    echo "<p class='error-code'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Check database credentials in config.php</li>";
    echo "<li>Verify MariaDB service is running</li>";
    echo "<li>Ensure datas_db database exists</li>";
    echo "</ul>";
    echo "</div>";
    echo "</body>";
    echo "</html>";
}
?>
