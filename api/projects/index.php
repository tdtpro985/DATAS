<?php
/* ============================================================
   GET  /api/v1/projects      — List all projects (with pagination)
   POST /api/v1/projects      — Create a new project
   ============================================================
   GET supports query params: page, size (default 1, 50)
   ============================================================ */

// Clean any existing output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

try {
    $user = requireRole(['encoder', 'superadmin', 'admin', 'sales_rep']);
} catch (Exception $e) {
    jsonError('Authentication failed: ' . $e->getMessage(), 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // List projects with pagination
        $page = max(1, (int)qp('page', 1));
        $size = min(500, max(1, (int)qp('size', 50)));
        $offset = ($page - 1) * $size;
        
        // Get type filter (for compatibility with frontend calls)
        $type = trim($_GET['type'] ?? '');

        $db = getDB();

        // Build WHERE clause based on type parameter
        $whereConditions = ['p.archived_at IS NULL'];
        $params = [];
        
        if ($type === 'priority') {
            $whereConditions[] = "LOWER(TRIM(p.status)) = 'priority'";
        } elseif ($type === 'non-priority') {
            $whereConditions[] = "LOWER(TRIM(p.status)) != 'priority'";
        } elseif ($type === 'leads') {
            // Handle leads type (same as non-priority for compatibility)
            $whereConditions[] = "LOWER(TRIM(p.status)) != 'priority'";
        }
        
        $whereClause = implode(' AND ', $whereConditions);

        // Get total count (exclude archived projects)
        $countQuery = "SELECT COUNT(*) as cnt FROM projects p WHERE " . $whereClause;
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $countRow = $countStmt->fetch();
        $total = (int)$countRow['cnt'];

        // Get paginated results with sales tracking data (if columns exist)
        $stmt = $db->prepare("
            SELECT p.*
            FROM projects p
            WHERE " . $whereClause . "
            ORDER BY p.created_at DESC
            LIMIT :size OFFSET :offset
        ");
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':size', $size, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
        // Check if sales tracking columns exist
        $hasTrackingColumns = false;
        try {
            $checkStmt = $db->query("SHOW COLUMNS FROM sales_tracking LIKE 'contacted'");
            $hasTrackingColumns = $checkStmt->rowCount() > 0;
        } catch (Exception $e) {
            // Table might not exist or columns don't exist
            $hasTrackingColumns = false;
        }
        
        // If tracking columns exist, fetch sales tracking data
        if ($hasTrackingColumns) {
            $trackingStmt = $db->prepare("
                SELECT project_id, contacted, quoted, sales_qualified, to_win, wa_amount, tracking_status,
                       sales_rep_id, branch, notes as sales_remarks
                FROM sales_tracking
            ");
            $trackingStmt->execute();
            $trackingData = [];
            while ($row = $trackingStmt->fetch()) {
                $trackingData[$row['project_id']] = $row;
            }
            
            // Process projects to include sales tracking status
            foreach ($projects as &$project) {
                $tracking = $trackingData[$project['id']] ?? null;
                
                if ($tracking) {
                    // Determine tracking status based on filled fields
                    $trackingFields = ['contacted', 'quoted', 'sales_qualified', 'to_win', 'wa_amount'];
                    $filledFields = 0;
                    
                    foreach ($trackingFields as $field) {
                        if (!empty($tracking[$field])) {
                            $filledFields++;
                        }
                    }
                    
                    if ($filledFields === 0) {
                        $project['sales_tracking_status'] = 'Not Started';
                    } elseif ($filledFields === count($trackingFields)) {
                        $project['sales_tracking_status'] = 'Complete';
                    } else {
                        $project['sales_tracking_status'] = 'In Progress';
                    }
                    
                    // Group sales tracking data
                    $project['sales_tracking'] = [
                        'contacted' => $tracking['contacted'],
                        'quoted' => $tracking['quoted'],
                        'sales_qualified' => $tracking['sales_qualified'],
                        'to_win' => $tracking['to_win'],
                        'wa_amount' => $tracking['wa_amount'],
                        'tracking_status' => $tracking['tracking_status'],
                        'sales_rep_id' => $tracking['sales_rep_id'],
                        'branch' => $tracking['branch'],
                        'remarks' => $tracking['sales_remarks']
                    ];
                } else {
                    // No sales tracking data
                    $project['sales_tracking_status'] = 'Not Started';
                    $project['sales_tracking'] = null;
                }
            }
        } else {
            // Sales tracking columns don't exist yet, set default status
            foreach ($projects as &$project) {
                $project['sales_tracking_status'] = 'Not Started';
                $project['sales_tracking'] = null;
            }
        }

        jsonResponse([
            'projects' => $projects,
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'total_pages' => ceil($total / $size),
        ]);
        
    } catch (Exception $e) {
        jsonError('Database error: ' . $e->getMessage(), 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$isMultipart = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
$body = $isMultipart ? $_POST : getJsonBody();
if (!$body) {
    jsonError('Request body is required', 400);
}

// Required fields
$contractorName = trim($body['contractor_name'] ?? '');
$projectName    = trim($body['project_name']    ?? '');
$region         = trim($body['region']          ?? '');
$projectValue   = isset($body['project_value']) ? (float) $body['project_value'] : null;
$status         = trim($body['status']          ?? '');
$source         = trim($body['source']          ?? '');

// Optional ID fields for duplicate checking
$contractorId   = trim($body['contractor_id']   ?? $body['contract_id'] ?? '') ?: null;
$projectId      = trim($body['project_id']      ?? '') ?: null;

// Require at least one external identifier: contractor_id or project_id
if ($contractorId === null && $projectId === null) {
    jsonError('Either Contract ID or Project ID is required', 422);
}

$db = getDB();

// Enforce uniqueness for each ID when provided
if ($projectId !== null) {
    $pStmt = $db->prepare('SELECT id FROM projects WHERE project_id = :project_id LIMIT 1');
    $pStmt->execute([':project_id' => $projectId]);
    if ($pStmt->fetch()) {
        jsonError('Project ID already exists for another project', 422);
    }
}

if ($contractorId !== null) {
    $cStmt = $db->prepare('SELECT id FROM projects WHERE contractor_id = :contractor_id LIMIT 1');
    $cStmt->execute([':contractor_id' => $contractorId]);
    if ($cStmt->fetch()) {
        jsonError('Contractor ID already exists for another project', 422);
    }
}

$uploadDir = __DIR__ . '/../../uploads/project_photos';
$uploadedPhotos = [];
if ($isMultipart && !empty($_FILES['photos'])) {
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        jsonError('Unable to create upload directory', 500);
    }

    $allowedMimeTypes = ALLOWED_IMAGE_MIMES;
    if (!defined('ALLOWED_IMAGE_MIMES')) {
        require_once __DIR__ . '/../../config.php';
        $allowedMimeTypes = ALLOWED_IMAGE_MIMES;
    }
    
    $files = $_FILES['photos'];
    $count = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $count; $i++) {
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];

        if ($error !== UPLOAD_ERR_OK || empty($tmpName)) {
            continue;
        }

        // SECURITY: Validate MIME type by file signature, not client header or extension
        if (!validateFileMimeType($tmpName, $allowedMimeTypes)) {
            jsonError('Invalid image file. Only JPG, PNG, GIF, and WEBP images are allowed.', 422);
        }

        // Generate safe filename using a UUID instead of user-provided extension
        $fileExtension = 'jpg'; // Default
        if (strpos($type, 'png') !== false) {
            $fileExtension = 'png';
        } elseif (strpos($type, 'webp') !== false) {
            $fileExtension = 'webp';
        } elseif (strpos($type, 'gif') !== false) {
            $fileExtension = 'gif';
        }
        
        $filename = sprintf('%s_%s.%s', time(), bin2hex(random_bytes(8)), $fileExtension);
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            jsonError('Failed to upload project photo.', 500);
        }

        $uploadedPhotos[] = 'uploads/project_photos/' . $filename;
    }
}

if (strlen($contractorName) < 2) {
    jsonError('Contractor Name must be at least 2 characters', 422);
}
if (strlen($projectName) < 3) {
    jsonError('Project Name must be at least 3 characters', 422);
}
if (empty($region)) {
    jsonError('Region is required', 422);
}
// SECURITY: Validate region format
if (!preg_match('/^[a-zA-Z0-9\s\-,\.\(\)]+$/', $region)) {
    jsonError('Invalid region format', 422);
}
if ($projectValue === null || $projectValue < 0) {
    jsonError('Project Value must be 0 or greater', 422);
}
if (empty($status)) {
    jsonError('Status is required', 422);
}
// SECURITY: Validate status against expected values
$allowedStatuses = ['Prospect', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost', 'Priority', 'Unqualified', 'For Execution', 'For Bidding', 'Awarded'];
if (!in_array($status, $allowedStatuses, true)) {
    jsonError('Invalid status value', 422);
}
if (empty($source)) {
    jsonError('Source is required', 422);
}

$db = getDB();
$stmt = $db->prepare("
    INSERT INTO projects (
        contractor_id, contractor_name, contact_person, contact_number, address,
        region, city_province, project_id, project_name, project_value,
        status, source, notice_reference_number, publication_date, sheet_pile_type, sheet_pile_amount,
        drbs, drbs_value, accomplishment_rate, ms_plate, angle_bars,
        channel_bars, wide_flange, gi_bi, encoded_by,
        contract_country, contract_region, contract_province, contract_city,
        contract_barangay, contract_street, contract_blk_lot, contract_coordinates,
        project_country, project_region, project_province, project_city,
        project_barangay, project_street, project_blk_lot, project_coordinates
    ) VALUES (
        :contractor_id, :contractor_name, :contact_person, :contact_number, :address,
        :region, :city_province, :project_id, :project_name, :project_value,
        :status, :source, :notice_reference_number, :publication_date, :sheet_pile_type, :sheet_pile_amount,
        :drbs, :drbs_value, :accomplishment_rate, :ms_plate, :angle_bars,
        :channel_bars, :wide_flange, :gi_bi, :encoded_by,
        :contract_country, :contract_region, :contract_province, :contract_city,
        :contract_barangay, :contract_street, :contract_blk_lot, :contract_coordinates,
        :project_country, :project_region, :project_province, :project_city,
        :project_barangay, :project_street, :project_blk_lot, :project_coordinates
    )
");

$stmt->execute([
    ':contractor_id'     => $contractorId,
    ':contractor_name'   => $contractorName,
    ':contact_person'    => trim($body['contact_person']   ?? '') ?: null,
    ':contact_number'    => trim($body['contact_number']   ?? '') ?: null,
    ':address'           => trim($body['address']          ?? '') ?: null,
    ':region'            => $region,
    ':city_province'     => trim($body['city_province']    ?? '') ?: null,
    ':project_id'        => $projectId,
    ':project_name'      => $projectName,
    ':project_value'     => $projectValue,
    ':status'            => $status,
    ':source'            => $source,
    ':notice_reference_number' => trim($body['notice_reference_number'] ?? '') ?: null,
    ':publication_date'  => trim($body['publication_date'] ?? '') ?: null,
    ':sheet_pile_type'   => trim($body['sheet_pile_type']  ?? '') ?: null,
    ':sheet_pile_amount' => isset($body['sheet_pile_amount']) && $body['sheet_pile_amount'] !== '' ? (float) $body['sheet_pile_amount'] : null,
    ':drbs'              => trim($body['drbs']             ?? '') ?: null,
    ':drbs_value'        => isset($body['drbs_value']) && $body['drbs_value'] !== '' ? (float) $body['drbs_value'] : null,
    ':accomplishment_rate' => isset($body['accomplishment_rate']) && $body['accomplishment_rate'] !== '' ? (float) $body['accomplishment_rate'] : 0,
    ':ms_plate'          => isset($body['ms_plate']) && $body['ms_plate'] !== '' ? (float) $body['ms_plate'] : null,
    ':angle_bars'        => isset($body['angle_bars']) && $body['angle_bars'] !== '' ? (float) $body['angle_bars'] : null,
    ':channel_bars'      => isset($body['channel_bars']) && $body['channel_bars'] !== '' ? (float) $body['channel_bars'] : null,
    ':wide_flange'       => isset($body['wide_flange']) && $body['wide_flange'] !== '' ? (float) $body['wide_flange'] : null,
    ':gi_bi'             => isset($body['gi_bi']) && $body['gi_bi'] !== '' ? (float) $body['gi_bi'] : null,
    ':encoded_by'        => $user['id'],
    
    // New contractor location fields
    ':contract_country'     => trim($body['contract_country']     ?? '') ?: null,
    ':contract_region'      => trim($body['contract_region']      ?? '') ?: null,
    ':contract_province'    => trim($body['contract_province']    ?? '') ?: null,
    ':contract_city'        => trim($body['contract_city']        ?? '') ?: null,
    ':contract_barangay'    => trim($body['contract_barangay']    ?? '') ?: null,
    ':contract_street'      => trim($body['contract_street']      ?? '') ?: null,
    ':contract_blk_lot'     => trim($body['contract_blk_lot']     ?? '') ?: null,
    ':contract_coordinates' => trim($body['contract_coordinates'] ?? '') ?: null,
    
    // New project location fields
    ':project_country'      => trim($body['project_country']      ?? '') ?: null,
    ':project_region'       => trim($body['project_region']       ?? '') ?: null,
    ':project_province'     => trim($body['project_province']     ?? '') ?: null,
    ':project_city'         => trim($body['project_city']         ?? '') ?: null,
    ':project_barangay'     => trim($body['project_barangay']     ?? '') ?: null,
    ':project_street'       => trim($body['project_street']       ?? '') ?: null,
    ':project_blk_lot'      => trim($body['project_blk_lot']      ?? '') ?: null,
    ':project_coordinates'  => trim($body['project_coordinates']  ?? '') ?: null,
]);

$newId = (int) $db->lastInsertId();

if (!empty($uploadedPhotos)) {
    $imageStmt = $db->prepare("INSERT INTO project_images (project_id, file_path) VALUES (:project_id, :file_path)");
    foreach ($uploadedPhotos as $path) {
        $imageStmt->execute([
            ':project_id' => $newId,
            ':file_path'  => $path,
        ]);
    }
}

jsonResponse(['id' => $newId, 'message' => 'Project created successfully.'], 201);
