<?php
/* ============================================================
   GET /api/v1/sync-status
   ============================================================
   Returns the last data sync timestamp.
   Since this is a manual-entry system (no ETL), we return
   the timestamp of the most recently updated project.
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db = getDB();
$stmt = $db->query("SELECT MAX(updated_at) AS finished_at FROM projects");
$row = $stmt->fetch();

jsonResponse([
    'last_sync' => [
        'finished_at' => $row['finished_at'] ?? date('Y-m-d H:i:s'),
    ],
]);
