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

$steps = [
    "ALTER TABLE `sales_tracking` ADD COLUMN IF NOT EXISTS `assigned_at`        datetime DEFAULT NULL AFTER `branch`",
    "ALTER TABLE `sales_tracking` ADD COLUMN IF NOT EXISTS `contacted_at`       datetime DEFAULT NULL AFTER `assigned_at`",
    "ALTER TABLE `sales_tracking` ADD COLUMN IF NOT EXISTS `sales_qualified_at` datetime DEFAULT NULL AFTER `contacted_at`",
    "ALTER TABLE `sales_tracking` ADD COLUMN IF NOT EXISTS `quoted_at`          datetime DEFAULT NULL AFTER `sales_qualified_at`",
    "ALTER TABLE `sales_tracking` ADD COLUMN IF NOT EXISTS `to_win_at`          datetime DEFAULT NULL AFTER `quoted_at`",
    "UPDATE `sales_tracking` SET `assigned_at` = `created_at` WHERE `assigned_at` IS NULL",
];

foreach ($steps as $sql) {
    try {
        $db->exec($sql);
        $results[] = ['ok', $sql];
    } catch (PDOException $e) {
        $results[] = ['err', $sql . ' — ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html><html><body style="font-family:monospace;padding:2rem;background:#111;color:#eee;">
<h2>Migration: Tracking Timestamps</h2>
<?php foreach ($results as [$status, $msg]): ?>
<p style="color:<?= $status === 'ok' ? '#4ade80' : '#f87171' ?>">
    <?= $status === 'ok' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?>
</p>
<?php endforeach; ?>
<p style="margin-top:2rem;color:#94a3b8;">Done. You can delete this file after running.</p>
</body></html>
