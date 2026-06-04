<?php
/* ============================================================
   GET /api/v1/contractors/rotating-card
   ============================================================
   Returns contractor details for the rotating card widget.
   Each contractor gets their top project + material items.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db     = getDB();
$date   = buildDateFilter('publication_date');
$region = getRegion();

$regionSql    = '';
$regionParams = [];
if ($region !== null) {
    $regionSql    = ' AND region = :region';
    $regionParams = [':region' => $region];
}

$params = array_merge($date['params'], $regionParams);
$where  = 'WHERE ' . $date['sql'] . $regionSql;

// Get top project per contractor (by value)
$stmt = $db->prepare("
    SELECT
        p.id,
        p.contractor_name,
        p.contact_person,
        p.contact_number,
        p.project_name,
        p.project_value  AS value_php,
        p.status,
        p.sheet_pile_type,
        p.sheet_pile_amount,
        p.drbs,
        p.drbs_value,
        p.accomplishment_rate
    FROM projects p
    INNER JOIN (
        SELECT contractor_name, MAX(project_value) AS max_val
        FROM projects
        $where
        GROUP BY contractor_name
    ) top ON p.contractor_name = top.contractor_name
          AND p.project_value = top.max_val
    $where
    ORDER BY p.project_value DESC
    LIMIT 50
");
$stmt->execute(array_merge($params, $params)); // params used twice (subquery + outer)
$rows = $stmt->fetchAll();

$contractors = array_map(function ($r) {
    // Build material items array from available fields
    $items = [];
    if (!empty($r['sheet_pile_type']) && $r['sheet_pile_amount'] > 0) {
        $items[] = ['label' => 'Sheet Pile (' . $r['sheet_pile_type'] . ')', 'value' => $r['sheet_pile_amount']];
    }
    if (!empty($r['drbs']) && $r['drbs_value'] > 0) {
        $items[] = ['label' => 'DRBs', 'value' => $r['drbs_value']];
    }

    return [
        'id'               => (int)   $r['id'],
        'contractor_name'  => $r['contractor_name'],
        'contact_person'   => $r['contact_person'] ?? '',
        'contact_number'   => $r['contact_number'] ?? '',
        'project_name'     => $r['project_name'],
        'value_php'        => (float) $r['value_php'],
        'status'           => $r['status'],
        'accomplishment_rate' => (float) ($r['accomplishment_rate'] ?? 0),
        'items'            => $items,
    ];
}, $rows);

jsonResponse(['contractors' => $contractors]);
