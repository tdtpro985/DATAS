<?php
/* ============================================================
   GET /api/v1/live-slideshow
   ============================================================
   Returns rotating contractor data for the live slideshow.
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db = getDB();
$date = buildDateFilter('publication_date');

$params = $date['params'];
$where = 'WHERE ' . $date['sql'];

// Exclude archived and illegitimate projects
$where .= " AND (archived_at IS NULL OR archived_at = '') AND (is_actual_project IS NULL OR is_actual_project != 'no')";

// Get a random project with contractor details
$stmt = $db->prepare("
    SELECT
        contractor_name,
        contact_person as contractor_contact,
        contact_number as contractor_phone,
        project_name,
        project_value,
        status,
        drbs_value,
        sheet_pile_amount
    FROM projects
    $where
    AND contractor_name IS NOT NULL
    AND contractor_name != ''
    ORDER BY RAND()
    LIMIT 1
");
$stmt->execute($params);
$project = $stmt->fetch();

if (!$project) {
    // Fallback data if no projects found
    $project = [
        'contractor_name' => 'PTM DEVELOPMENT CORPORATION',
        'contractor_contact' => 'Bartolome M. San Martin, III',
        'contractor_phone' => '02014',
        'project_name' => 'CONSTRUCTION OF REVETMENT AT BARANGAY LONGOS, PULILAN, BULACAN',
        'project_value' => 28200000,
        'status' => 'UNKNOWN',
        'drbs_value' => 4000210,
        'sheet_pile_amount' => 16551236.26
    ];
}

jsonResponse([
    'contractor_name' => $project['contractor_name'],
    'contact' => $project['contractor_contact'] ?: 'N/A',
    'phone' => $project['contractor_phone'] ?: 'N/A',
    'project_title' => $project['project_name'] ?: 'N/A',
    'project_value' => (float) $project['project_value'],
    'status' => $project['status'] ?: 'UNKNOWN',
    'drbs_value' => (float) $project['drbs_value'],
    'sheet_pile_amount' => (float) $project['sheet_pile_amount']
]);