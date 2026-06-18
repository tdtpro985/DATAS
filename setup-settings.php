<?php
/**
 * Web-accessible setup for Superadmin Settings.
 * Access: http://your-domain/setup-settings.php
 * Run ONCE, then DELETE this file for security.
 */
require_once 'config.php';
require_once 'api/db.php';

// Only allow if no settings exist yet (safety check)
try {
    $pdo = getDB();
    
    // Check if table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'system_settings'")->rowCount() > 0;
    
    if ($tableExists) {
        $count = $pdo->query("SELECT COUNT(*) FROM system_settings")->fetchColumn();
        if ($count > 0) {
            die("Settings already exist ({$count} records). This script is for initial setup only. Delete this file after use.");
        }
    }
    
    // Create table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `setting_type` ENUM('string', 'boolean', 'integer', 'float', 'json') NOT NULL DEFAULT 'string',
        `description` TEXT,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "✓ Table created<br>";
    
    // Seed defaults
    $defaults = [
        ['app_name', 'TDT Powersteel SILEP - DATAS', 'string', 'System application name'],
        ['app_version', '3.6', 'string', 'Current application version'],
        ['maintenance_mode', '0', 'boolean', 'Enable maintenance mode'],
        ['maintenance_message', 'System is currently under maintenance. Please check back later.', 'string', 'Maintenance mode message'],
        ['max_login_attempts', '5', 'integer', 'Max failed login attempts before lockout'],
        ['session_timeout_minutes', '60', 'integer', 'Session timeout in minutes'],
        ['password_min_length', '8', 'integer', 'Minimum password length'],
        ['require_2fa', '0', 'boolean', 'Require 2FA for admin users'],
        ['enable_email_notifications', '1', 'boolean', 'Enable email notifications'],
        ['enable_sms_notifications', '0', 'boolean', 'Enable SMS notifications'],
        ['items_per_page', '50', 'integer', 'Default items per page'],
        ['enable_animations', '1', 'boolean', 'Enable UI animations'],
        ['date_format', 'Y-m-d H:i:s', 'string', 'Default date format'],
        ['default_project_status', 'New', 'string', 'Default project status'],
        ['priority_project_threshold_days', '7', 'integer', 'Days before priority flag'],
        ['auto_archive_days', '90', 'integer', 'Days before auto-archive'],
        ['enable_activity_logging', '1', 'boolean', 'Enable activity logging'],
        ['log_retention_days', '365', 'integer', 'Days to retain logs'],
        ['timezone', 'Asia/Manila', 'string', 'System timezone'],
        ['currency_symbol', '₱', 'string', 'Currency symbol'],
        ['currency_code', 'PHP', 'string', 'Currency code'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    $inserted = 0;
    foreach ($defaults as $d) {
        $stmt->execute($d);
        if ($stmt->rowCount() > 0) $inserted++;
    }
    
    echo "✓ {$inserted} settings inserted<br>";
    echo "<br><strong>Setup complete!</strong> You can now access Settings in the Admin Panel.";
    echo "<br><br><strong style='color:red;'>⚠ DELETE THIS FILE (setup-settings.php) AFTER USE!</strong>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}