<?php
/* ============================================================
   GET /api/v1/priority-alerts
   ============================================================
   Returns new priority projects that need to be alerted
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db = getDB();

try {
    // Ensure database connection is working with enhanced error handling
    $db = getDB();
    
    // Test the connection first with timeout
    $db->setAttribute(PDO::ATTR_TIMEOUT, 5);
    $testResult = $db->query("SELECT 1 as test")->fetch();
    
    if (!$testResult || $testResult['test'] !== 1) {
        throw new Exception('Database connection test failed');
    }
    
    // Get priority projects that haven't been alerted yet
    // Priority projects are those with status = 'PRIORITY'
    $stmt = $db->prepare("
        SELECT DISTINCT
            p.id,
            p.contractor_id,
            p.project_id,
            p.contractor_name,
            p.contact_person,
            p.contact_number,
            p.project_name,
            p.project_value,
            p.status,
            p.source,
            p.publication_date,
            p.address,
            p.region,
            p.city_province,
            p.contract_country,
            p.contract_region,
            p.contract_province,
            p.contract_city,
            p.contract_barangay,
            p.contract_street,
            p.contract_blk_lot,
            p.contract_coordinates,
            p.project_country,
            p.project_region,
            p.project_province,
            p.project_city,
            p.project_barangay,
            p.project_street,
            p.project_blk_lot,
            p.project_coordinates,
            p.sheet_pile_type,
            p.sheet_pile_amount,
            p.drbs,
            p.drbs_value,
            p.accomplishment_rate,
            p.ms_plate,
            p.angle_bars,
            p.channel_bars,
            p.wide_flange,
            p.gi_bi,
            p.created_at,
            p.updated_at
        FROM projects p
        WHERE p.status = 'PRIORITY'
        AND p.id NOT IN (
            SELECT COALESCE(project_id, 0) FROM priority_alerts WHERE project_id = p.id
        )
        ORDER BY p.created_at DESC, p.updated_at DESC
        LIMIT 1
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare priority projects query: ' . implode(', ', $db->errorInfo()));
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute priority projects query: ' . implode(', ', $stmt->errorInfo()));
    }
    $project = $stmt->fetch();
    
    if (!$project) {
        // No new priority projects
        jsonResponse(['alert' => null]);
        return;
    }
    
    // Get project images with enhanced error handling
    $images = [];
    try {
        // Check if project_images table exists first
        $tableCheck = $db->query("SHOW TABLES LIKE 'project_images'")->fetch();
        
        if ($tableCheck) {
            $stmt = $db->prepare("
                SELECT id, file_path, created_at
                FROM project_images
                WHERE project_id = :project_id
                ORDER BY created_at ASC
            ");
            
            if ($stmt && $stmt->execute([':project_id' => $project['id']])) {
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } else {
                error_log('Priority alerts images query failed: ' . implode(', ', $stmt ? $stmt->errorInfo() : $db->errorInfo()));
            }
        } else {
            error_log('Priority alerts: project_images table does not exist');
        }
    } catch (PDOException $e) {
        // If images table has issues, continue without images
        error_log('Priority alerts images error: ' . $e->getMessage());
        $images = [];
    }
    
    // Mark this project as alerted with enhanced error handling
    try {
        // Check if priority_alerts table exists first
        $tableCheck = $db->query("SHOW TABLES LIKE 'priority_alerts'")->fetch();
        
        if ($tableCheck) {
            $stmt = $db->prepare("
                INSERT INTO priority_alerts (project_id, alerted_at)
                VALUES (:project_id, NOW())
                ON DUPLICATE KEY UPDATE alerted_at = NOW()
            ");
            
            if (!$stmt || !$stmt->execute([':project_id' => $project['id']])) {
                error_log('Priority alerts marking failed: ' . implode(', ', $stmt ? $stmt->errorInfo() : ['Failed to prepare statement']));
            }
        } else {
            // Create table if it doesn't exist
            $createTable = "
                CREATE TABLE IF NOT EXISTS priority_alerts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    project_id INT NOT NULL,
                    alerted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_project (project_id),
                    INDEX idx_project_id (project_id)
                )
            ";
            
            if ($db->exec($createTable) !== false) {
                // Try again now that table exists
                $stmt = $db->prepare("
                    INSERT INTO priority_alerts (project_id, alerted_at)
                    VALUES (:project_id, NOW())
                ");
                
                if ($stmt && $stmt->execute([':project_id' => $project['id']])) {
                    error_log('Priority alerts: Created table and marked project ' . $project['id']);
                }
            }
        }
    } catch (PDOException $e) {
        // If priority_alerts table has issues, log but continue
        error_log('Priority alerts marking error: ' . $e->getMessage());
    }
    
    // Format the response
    $alert = [
        'project' => [
            'id' => (int) $project['id'],
            'contractor_id' => $project['contractor_id'],
            'project_id' => $project['project_id'],
            'name' => $project['project_name'],
            'contractor_name' => $project['contractor_name'],
            'contact_person' => $project['contact_person'],
            'contact_number' => $project['contact_number'],
            'project_value' => (float) $project['project_value'],
            'status' => $project['status'],
            'source' => $project['source'],
            'publication_date' => $project['publication_date'],
            'address' => $project['address'],
            'region' => $project['region'],
            'city_province' => $project['city_province'],
            'contract_country' => $project['contract_country'],
            'contract_region' => $project['contract_region'],
            'contract_province' => $project['contract_province'],
            'contract_city' => $project['contract_city'],
            'contract_barangay' => $project['contract_barangay'],
            'contract_street' => $project['contract_street'],
            'contract_blk_lot' => $project['contract_blk_lot'],
            'contract_coordinates' => $project['contract_coordinates'],
            'project_country' => $project['project_country'],
            'project_region' => $project['project_region'],
            'project_province' => $project['project_province'],
            'project_city' => $project['project_city'],
            'project_barangay' => $project['project_barangay'],
            'project_street' => $project['project_street'],
            'project_blk_lot' => $project['project_blk_lot'],
            'project_coordinates' => $project['project_coordinates'],
            'sheet_pile_type' => $project['sheet_pile_type'],
            'sheet_pile_amount' => (float) ($project['sheet_pile_amount'] ?: 0),
            'drbs' => $project['drbs'],
            'drbs_value' => (float) ($project['drbs_value'] ?: 0),
            'accomplishment_rate' => (float) ($project['accomplishment_rate'] ?: 0),
            'ms_plate' => (float) ($project['ms_plate'] ?: 0),
            'angle_bars' => (float) ($project['angle_bars'] ?: 0),
            'channel_bars' => (float) ($project['channel_bars'] ?: 0),
            'wide_flange' => (float) ($project['wide_flange'] ?: 0),
            'gi_bi' => (float) ($project['gi_bi'] ?: 0),
            'created_at' => $project['created_at'],
            'updated_at' => $project['updated_at']
        ],
        'images' => array_map(function($img) {
            return [
                'id' => (int) $img['id'],
                'file_path' => $img['file_path'],
                'created_at' => $img['created_at']
            ];
        }, $images),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    jsonResponse(['alert' => $alert]);
    
} catch (PDOException $e) {
    error_log('Priority alerts API error: ' . $e->getMessage());
    jsonError('Database error', 500);
} catch (Exception $e) {
    error_log('Priority alerts API error: ' . $e->getMessage());
    jsonError('Internal server error', 500);
}