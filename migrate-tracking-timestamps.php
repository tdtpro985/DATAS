<?php
/**
 * Migration: Add per-stage timestamps to sales_tracking
 * Run once: http://datas.lan/migrate-tracking-timestamps.php
 */
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

$db = getDB();
$results = [];

// Columns to add: [ column_name, definition, after_column ]
$columns = [
    ['assigned_at',        'datetime DEFAULT NULL COMMENT \'When assigned to this SR\'',            'branch'],
    ['contacted_at',       'datetime DEFAULT NULL COMMENT \'When contacted was first set to Yes\'',  'assigned_at'],
    ['sales_qualified_at', 'datetime DEFAULT NULL COMMENT \'When sales_qualified was first set\'',   'contacted_at'],
    ['quoted_at',          'datetime DEFAULT NULL COMMENT \'When quoted was first set to Yes\'',     'sales_qualified_at'],
    ['to_win_at',          'datetime DEFAULT NULL COMMENT \'When to_win was first set to Yes\'',     'quoted_at'],
];

foreach ($columns as [$col, $def, $after]) {
    // Check if column already exists
    $check = $db->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'sales_tracking'
          AND COLUMN_NAME  = ?
    ");
    $check->execute([$col]);
    $exists = (int)$check->fetchColumn();

    if ($exists) {
        $results[] = ['skip', "Column `$col` already exists — skipped"];
        continue;
    }

    try {
        $db->exec("ALTER TABLE `sales_tracking` ADD COLUMN `$col` $def AFTER `$after`");
        $results[] = ['ok', "Added column `$col`"];
    } catch (PDOException $e) {
        $results[] = ['err', "Add `$col` — " . $e->getMessage()];
    }
}

// Back-fill assigned_at from created_at
try {
    $db->exec("UPDATE `sales_tracking` SET `assigned_at` = `created_at` WHERE `assigned_at` IS NULL");
    $results[] = ['ok', 'Back-filled assigned_at from created_at'];
} catch (PDOException $e) {
    $results[] = ['err', 'Back-fill — ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html><body style="font-family:monospace;padding:2rem;background:#111;color:#eee;">
<h2 style="color:#ff8000;">Migration: Tracking Timestamps</h2>
<?php foreach ($results as [$status, $msg]): ?>
<p style="color:<?= $status === 'ok' ? '#4ade80' : ($status === 'skip' ? '#94a3b8' : '#f87171') ?>">
    <?= $status === 'ok' ? '✅' : ($status === 'skip' ? '⏭' : '❌') ?> <?= htmlspecialchars($msg) ?>
</p>
<?php endforeach; ?>
<p style="margin-top:2rem;color:#94a3b8;">Done. You can delete this file after running.</p>
</body></html>
