<?php
/* ============================================================
   GET /api/v1/live-slideshow
   Returns a random project for the live slideshow.
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT
            contractor_name,
            contact_person  AS contractor_contact,
            contact_number  AS contractor_phone,
            project_name,
            project_value,
            status,
            drbs_value,
            sheet_pile_amount
        FROM projects
        WHERE archived_at IS NULL
          AND contractor_name IS NOT NULL
          AND contractor_name != ''
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute();
    $project = $stmt->fetch();

    if (!$project) {
        $project = [
            'contractor_name'     => 'PTM DEVELOPMENT CORPORATION',
            'contractor_contact'  => 'Bartolome M. San Martin, III',
            'contractor_phone'    => '02014',
            'project_name'        => 'CONSTRUCTION OF REVETMENT AT BARANGAY LONGOS, PULILAN, BULACAN',
            'project_value'       => 28200000,
            'status'              => 'UNKNOWN',
            'drbs_value'          => 4000210,
            'sheet_pile_amount'   => 16551236.26,
        ];
    }

    jsonResponse([
        'contractor_name'    => $project['contractor_name'],
        'contact'            => $project['contractor_contact'] ?: 'N/A',
        'phone'              => $project['contractor_phone']   ?: 'N/A',
        'project_title'      => $project['project_name']      ?: 'N/A',
        'project_value'      => (float) $project['project_value'],
        'status'             => $project['status']             ?: 'UNKNOWN',
        'drbs_value'         => (float) $project['drbs_value'],
        'sheet_pile_amount'  => (float) $project['sheet_pile_amount'],
    ]);

} catch (Exception $e) {
    error_log('Live slideshow error: ' . $e->getMessage());
    jsonResponse([
        'contractor_name'   => 'No Data Available',
        'contact'           => 'N/A',
        'phone'             => 'N/A',
        'project_title'     => 'N/A',
        'project_value'     => 0,
        'status'            => 'UNKNOWN',
        'drbs_value'        => 0,
        'sheet_pile_amount' => 0,
    ]);
}
