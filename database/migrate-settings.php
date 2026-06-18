<?php
/**
 * Migration: Create system_settings table and seed default values.
 * Run this file ONCE after deploying.
 */

// Bootstrap
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/db.php';

echo "=== Superadmin Settings Migration ===\n\n";

try {
    $pdo = getDB();
    
    // ── Step 1: Create system_settings table ───────────────
    echo "[1/3] Creating system_settings table...\n";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `setting_type` ENUM('string', 'boolean', 'integer', 'float', 'json') NOT NULL DEFAULT 'string',
        `description` TEXT,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "   ✓ Table created successfully\n\n";
    
    // ── Step 2: Seed default settings ──────────────────────
    echo "[2/3] Seeding default settings...\n";
    
    $defaultSettings = [
        // Application Settings
        [
            'key' => 'app_name',
            'value' => 'TDT Powersteel SILEP - DATAS',
            'type' => 'string',
            'desc' => 'System application name displayed in browser title and headers',
        ],
        [
            'key' => 'app_version',
            'value' => '3.6',
            'type' => 'string',
            'desc' => 'Current application version number',
        ],
        [
            'key' => 'maintenance_mode',
            'value' => '0',
            'type' => 'boolean',
            'desc' => 'Enable maintenance mode to block non-superadmin users from accessing the system',
        ],
        [
            'key' => 'maintenance_message',
            'value' => 'System is currently under maintenance. Please check back later.',
            'type' => 'string',
            'desc' => 'Message displayed to users when maintenance mode is enabled',
        ],
        
        // Security Settings
        [
            'key' => 'max_login_attempts',
            'value' => '5',
            'type' => 'integer',
            'desc' => 'Maximum failed login attempts before account lockout',
        ],
        [
            'key' => 'session_timeout_minutes',
            'value' => '60',
            'type' => 'integer',
            'desc' => 'Session timeout duration in minutes',
        ],
        [
            'key' => 'password_min_length',
            'value' => '8',
            'type' => 'integer',
            'desc' => 'Minimum password length requirement',
        ],
        [
            'key' => 'require_2fa',
            'value' => '0',
            'type' => 'boolean',
            'desc' => 'Require two-factor authentication for all admin users',
        ],
        
        // Notification Settings
        [
            'key' => 'enable_email_notifications',
            'value' => '1',
            'type' => 'boolean',
            'desc' => 'Enable email notifications for project assignments (when configured)',
        ],
        [
            'key' => 'enable_sms_notifications',
            'value' => '0',
            'type' => 'boolean',
            'desc' => 'Enable SMS notifications for project updates (when configured)',
        ],
        
        // Display Settings
        [
            'key' => 'items_per_page',
            'value' => '50',
            'type' => 'integer',
            'desc' => 'Default number of items to display per page in tables',
        ],
        [
            'key' => 'enable_animations',
            'value' => '1',
            'type' => 'boolean',
            'desc' => 'Enable UI animations and transitions',
        ],
        [
            'key' => 'date_format',
            'value' => 'Y-m-d H:i:s',
            'type' => 'string',
            'desc' => 'Default date/time format for the system',
        ],
        
        // Project Settings
        [
            'key' => 'default_project_status',
            'value' => 'New',
            'type' => 'string',
            'desc' => 'Default status assigned to newly created projects',
        ],
        [
            'key' => 'priority_project_threshold_days',
            'value' => '7',
            'type' => 'integer',
            'desc' => 'Number of days without update before a project is flagged as priority',
        ],
        [
            'key' => 'auto_archive_days',
            'value' => '90',
            'type' => 'integer',
            'desc' => 'Number of days after which completed projects are auto-archived',
        ],
        
        // Tracking Settings
        [
            'key' => 'enable_activity_logging',
            'value' => '1',
            'type' => 'boolean',
            'desc' => 'Enable detailed activity logging for all user actions',
        ],
        [
            'key' => 'log_retention_days',
            'value' => '365',
            'type' => 'integer',
            'desc' => 'Number of days to retain activity logs before auto-cleanup',
        ],
        
        // Regional Settings
        [
            'key' => 'timezone',
            'value' => 'Asia/Manila',
            'type' => 'string',
            'desc' => 'System timezone for date/time display',
        ],
        [
            'key' => 'currency_symbol',
            'value' => '₱',
            'type' => 'string',
            'desc' => 'Currency symbol used for monetary values',
        ],
        [
            'key' => 'currency_code',
            'value' => 'PHP',
            'type' => 'string',
            'desc' => 'ISO currency code for monetary values',
        ],
    ];
    
    $insertStmt = $pdo->prepare(
        "INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) 
         VALUES (:key, :value, :type, :desc)"
    );
    
    $inserted = 0;
    foreach ($defaultSettings as $setting) {
        $insertStmt->execute([
            ':key' => $setting['key'],
            ':value' => $setting['value'],
            ':type' => $setting['type'],
            ':desc' => $setting['desc'],
        ]);
        if ($insertStmt->rowCount() > 0) {
            $inserted++;
        }
    }
    
    echo "   ✓ {$inserted} new settings inserted (" . count($defaultSettings) . " total defaults)\n\n";
    
    // ── Step 3: Verify ────────────────────────────────────
    echo "[3/3] Verifying migration...\n";
    
    $count = $pdo->query("SELECT COUNT(*) FROM system_settings")->fetchColumn();
    echo "   ✓ Total settings in database: {$count}\n\n";
    
    echo "=== Migration Complete! ===\n";
    echo "Superadmin Settings can now be accessed via the Admin Panel → Settings tab.\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}