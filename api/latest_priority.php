<?php
/* ============================================================
   GET /api/v1/latest_priority
   ============================================================
   Returns the latest priority project timestamp and any
   new priority projects since the last poll.
   The frontend polls this every 8 seconds.
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db = getDB();

// Get the most recent priority project timestamp
$stmt = $db->query("
    SELECT MAX(updated_at) AS last_ts
    FROM projects
    WHERE status = 'Priority'
");
$row = $stmt->fetch();
$timestamp = $row['last_ts'] ? strtotime($row['last_ts']) : 0;

// Get priority projects updated in the last 30 seconds (new arrivals)
$stmt2 = $db->prepare("
    SELECT
        id,
        contractor_name     AS Contractor,
        contact_person      AS `Contact Person`,
        contact_number      AS `Contact Number`,
        address             AS Address,
        region              AS Region,
        city_province       AS City,
        project_name        AS `Project Name`,
        project_value       AS Value,
        status              AS Status,
        source              AS Source,
        sheet_pile_type     AS `Sheet Pile Type`,
        sheet_pile_amount   AS `Sheet Pile Amount`,
        drbs                AS DRBs,
        drbs_value          AS `DRBs Value`,
        accomplishment_rate AS `Accomplishment Rate`
    FROM projects
    WHERE status = 'Priority'
      AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
    ORDER BY updated_at DESC
    LIMIT 10
");
$stmt2->execute();
$newArrivals = $stmt2->fetchAll();

// Cast numeric fields
$newArrivals = array_map(function ($p) {
    $p['Value']              = (float) $p['Value'];
    $p['Sheet Pile Amount']  = (float) $p['Sheet Pile Amount'];
    $p['DRBs Value']         = (float) $p['DRBs Value'];
    $p['Accomplishment Rate']= (float) $p['Accomplishment Rate'];
    return $p;
}, $newArrivals);

jsonResponse([
    'timestamp'   => $timestamp,
    'new_arrivals'=> $newArrivals,
]);
