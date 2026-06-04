<?php
/**
 * Add archive columns to platform_leads table
 */

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
    
    // Add archive columns
    try {
        $pdo->exec("ALTER TABLE platform_leads ADD COLUMN archived_at TIMESTAMP NULL");
        echo "✅ Added archived_at column\n";
    } catch (Exception $e) {
        echo "⚠️ archived_at column already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE platform_leads ADD COLUMN archived_by INT NULL");
        echo "✅ Added archived_by column\n";
    } catch (Exception $e) {
        echo "⚠️ archived_by column already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_archived_at ON platform_leads (archived_at)");
        echo "✅ Added archived_at index\n";
    } catch (Exception $e) {
        echo "⚠️ Index already exists or error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Archive functionality setup complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>