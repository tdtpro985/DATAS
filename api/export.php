<?php
/* ============================================================
   GET/POST /api/v1/export
   ============================================================
   Handles export requests for various reports
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/activity-logger.php';

$user = requireAuth();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    jsonError('Method not allowed', 405);
}

$db = getDB();

try {
    // Get request parameters
    $reports = isset($_POST['reports']) ? $_POST['reports'] : (isset($_GET['reports']) ? explode(',', $_GET['reports']) : []);
    $format = isset($_POST['format']) ? $_POST['format'] : (isset($_GET['format']) ? $_GET['format'] : 'csv');
    
    if (empty($reports)) {
        jsonError('No reports specified', 400);
    }
    
    // Validate format
    if (!in_array($format, ['csv', 'excel', 'pdf'])) {
        $format = 'csv';
    }
    
    $exportData = [];
    
    // Log export activity
    logActivity($db, $user['id'], ActivityType::EXPORT_DATA, EntityType::EXPORT, null, "Data exported: " . implode(', ', $reports) . " (format: {$format})");

    // Generate data for each requested report
    foreach ($reports as $reportType) {
        switch ($reportType) {
            case 'users':
                $exportData['users'] = exportUsers($db);
                break;
                
            case 'sales_reps':
                $exportData['sales_reps'] = exportSalesReps($db);
                break;
                
            case 'non_priority_projects':
                $exportData['non_priority_projects'] = exportNonPriorityProjects($db);
                break;
                
            case 'priority_projects':
                $exportData['priority_projects'] = exportPriorityProjects($db);
                break;
        }
    }
    
    // Generate export file based on format
    switch ($format) {
        case 'csv':
            generateCSVExport($exportData, $reports);
            break;
            
        case 'excel':
            generateExcelExport($exportData, $reports);
            break;
            
        case 'pdf':
            generatePDFExport($exportData, $reports);
            break;
            
        default:
            generateCSVExport($exportData, $reports);
    }
    
} catch (PDOException $e) {
    error_log('Export API database error: ' . $e->getMessage());
    jsonError('Database error during export', 500);
} catch (Exception $e) {
    error_log('Export API error: ' . $e->getMessage());
    jsonError('Export failed: ' . $e->getMessage(), 500);
}

function exportUsers($db) {
    $stmt = $db->prepare("
        SELECT 
            id, 
            full_name, 
            email, 
            role, 
            created_at,
            updated_at
        FROM users 
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportSalesReps($db) {
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.full_name,
            u.email,
            COUNT(p.id) as total_projects,
            COALESCE(SUM(p.project_value), 0) as total_value
        FROM users u
        LEFT JOIN projects p ON u.id = p.assigned_to
        WHERE u.role = 'sales_rep'
        GROUP BY u.id, u.full_name, u.email
        ORDER BY total_value DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportNonPriorityProjects($db) {
    $stmt = $db->prepare("
        SELECT 
            id,
            contractor_name,
            project_name,
            project_value,
            status,
            source,
            region,
            city_province,
            created_at
        FROM projects 
        WHERE LOWER(TRIM(status)) != 'priority'
        ORDER BY created_at DESC
        LIMIT 1000
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportPriorityProjects($db) {
    $stmt = $db->prepare("
        SELECT 
            id,
            contractor_name,
            contact_person,
            contact_number,
            project_name,
            project_value,
            status,
            source,
            region,
            city_province,
            accomplishment_rate,
            created_at
        FROM projects 
        WHERE LOWER(TRIM(status)) = 'priority'
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateCSVExport($exportData, $reports) {
    $filename = 'TDT_Powersteel_Reports_' . date('Ymd_Hi') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper Excel UTF-8 handling
    fwrite($output, "\xEF\xBB\xBF");
    
    // Write header
    fputcsv($output, ['TDT POWERSTEEL DASHBOARD REPORTS']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    foreach ($reports as $reportType) {
        if (!isset($exportData[$reportType])) continue;
        
        $data = $exportData[$reportType];
        $reportTitle = ucwords(str_replace('_', ' ', $reportType));
        
        fputcsv($output, [$reportTitle . ' Report']);
        fputcsv($output, []);
        
        if (!empty($data)) {
            // Write headers
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ['No data available']);
        }
        
        fputcsv($output, []);
        fputcsv($output, []);
    }
    
    fclose($output);
    exit;
}

function generateExcelExport($exportData, $reports) {
    // For now, generate CSV with Excel-compatible format
    generateCSVExport($exportData, $reports);
}

function generatePDFExport($exportData, $reports) {
    // For now, generate CSV format
    // You can implement proper PDF generation using libraries like TCPDF or mPDF
    generateCSVExport($exportData, $reports);
}