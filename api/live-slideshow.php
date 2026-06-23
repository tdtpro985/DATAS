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
            drbs,
            drbs_value,
            sheet_pile_type,
            sheet_pile_amount,
            ms_plate,
            angle_bars,
            channel_bars,
            wide_flange,
            gi_bi
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
            'drbs'                => 'Standard DRB',
            'drbs_value'          => 4000210,
            'sheet_pile_type'     => 'Steel Sheet Pile',
            'sheet_pile_amount'   => 16551236.26,
            'ms_plate'            => 1200000,
            'angle_bars'          => 850000,
            'channel_bars'        => 640000,
            'wide_flange'         => 910000,
            'gi_bi'               => 430000,
        ];
    }

    jsonResponse([
        'contractor_name'    => $project['contractor_name'],
        'contact'            => $project['contractor_contact'] ?: 'N/A',
        'phone'              => $project['contractor_phone']   ?: 'N/A',
        'project_title'      => $project['project_name']      ?: 'N/A',
        'project_value'      => (float) $project['project_value'],
        'status'             => $project['status']             ?: 'UNKNOWN',
        'drbs'               => $project['drbs']              ?: null,
        'drbs_value'         => (float) $project['drbs_value'],
        'sheet_pile_type'    => $project['sheet_pile_type']   ?: null,
        'sheet_pile_amount'  => (float) $project['sheet_pile_amount'],
        'ms_plate'           => (float) $project['ms_plate'],
        'angle_bars'         => (float) $project['angle_bars'],
        'channel_bars'       => (float) $project['channel_bars'],
        'wide_flange'        => (float) $project['wide_flange'],
        'gi_bi'              => (float) $project['gi_bi'],
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
        'drbs'              => null,
        'drbs_value'        => 0,
        'sheet_pile_type'   => null,
        'sheet_pile_amount' => 0,
        'ms_plate'          => 0,
        'angle_bars'        => 0,
        'channel_bars'      => 0,
        'wide_flange'       => 0,
        'gi_bi'             => 0,
    ]);
}
