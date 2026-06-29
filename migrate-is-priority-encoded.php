<?php
/* ============================================================
   migrate-is-priority-encoded.php — Add is_priority_encoded column
   ============================================================ */

require_once __DIR__ . '/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "===== MIGRATION: Add is_priority_encoded Column =====\n\n";

    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'is_priority_encoded'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'is_priority_encoded' already exists. Skipping creation.\n";
    } else {
        echo "→ Adding 'is_priority_encoded' column...\n";
        $db->exec("
            ALTER TABLE `projects` 
            ADD COLUMN `is_priority_encoded` ENUM('yes', 'no') NOT NULL DEFAULT 'no' 
            COMMENT 'Marks if project was encoded via Priority form' 
            AFTER `status`
        ");
        echo "✓ Column added successfully.\n";
    }

    // Check if index exists
    $stmt = $db->query("SHOW INDEX FROM projects WHERE Key_name = 'idx_is_priority_encoded'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Index 'idx_is_priority_encoded' already exists. Skipping creation.\n";
    } else {
        echo "→ Adding index on 'is_priority_encoded'...\n";
        $db->exec("ALTER TABLE `projects` ADD INDEX `idx_is_priority_encoded` (`is_priority_encoded`)");
        echo "✓ Index added successfully.\n";
    }

    // Migrate existing data: Projects with status='Priority' should be marked as priority encoded
    // Check different case variations
    echo "\n→ Migrating existing priority projects...\n";
    $stmt = $db->prepare("
        UPDATE projects 
        SET is_priority_encoded = 'yes' 
        WHERE (
            LOWER(TRIM(status)) = 'priority'
            OR UPPER(TRIM(status)) = 'PRIORITY'
            OR status LIKE '%Priority%'
            OR status LIKE '%PRIORITY%'
        )
        AND is_priority_encoded = 'no'
        AND archived_at IS NULL
    ");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Migrated $count existing priority projects.\n";
    
    // Show what statuses exist in the database
    echo "\n→ Checking existing status values...\n";
    $stmt = $db->query("
        SELECT DISTINCT status, COUNT(*) as count
        FROM projects
        WHERE archived_at IS NULL
        GROUP BY status
        ORDER BY count DESC
    ");
    echo "Status values in database:\n";
    while ($row = $stmt->fetch()) {
        $status = $row['status'] ?? 'NULL';
        $count = $row['count'];
        echo "  - '$status': $count projects\n";
    }

    echo "\n===== MIGRATION COMPLETE =====\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
