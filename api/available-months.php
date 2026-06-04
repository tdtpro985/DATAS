<?php
/* ============================================================
   GET /api/v1/available-months
   ============================================================
   Returns available months based on published dates in projects
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$db = getDB();

try {
    // Get distinct months and years from publication_date where it's not null
    $stmt = $db->prepare("
        SELECT DISTINCT 
            YEAR(publication_date) as year,
            MONTH(publication_date) as month,
            MONTHNAME(publication_date) as month_name,
            COUNT(*) as project_count
        FROM projects 
        WHERE publication_date IS NOT NULL 
        AND publication_date != '0000-00-00'
        GROUP BY YEAR(publication_date), MONTH(publication_date)
        ORDER BY year DESC, month DESC
    ");
    
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    $months = [];
    foreach ($results as $row) {
        $months[] = [
            'value' => $row['month'] . '-' . $row['year'],
            'label' => $row['month_name'] . ' ' . $row['year'],
            'year' => (int) $row['year'],
            'month' => (int) $row['month'],
            'project_count' => (int) $row['project_count']
        ];
    }
    
    // If no months found, add current month as fallback
    if (empty($months)) {
        $currentYear = date('Y');
        $currentMonth = date('n');
        $currentMonthName = date('F');
        
        $months[] = [
            'value' => $currentMonth . '-' . $currentYear,
            'label' => $currentMonthName . ' ' . $currentYear,
            'year' => (int) $currentYear,
            'month' => (int) $currentMonth,
            'project_count' => 0
        ];
    }
    
    jsonResponse([
        'months' => $months,
        'total_months' => count($months)
    ]);
    
} catch (Exception $e) {
    error_log("Available months API error: " . $e->getMessage());
    jsonError('Failed to load available months', 500);
}