<?php
/* ============================================================
   GET /api/v1/kpi
   Returns KPI summary + status category breakdown.
   Query params: period, month, year, region
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $db = getDB();

    // Build filters
    $conditions = ['archived_at IS NULL'];
    $params     = [];

    // Check if is_actual_project column exists (exclude illegitimate projects)
    static $hasIllegitimateCol = null;
    if ($hasIllegitimateCol === null) {
        try {
            $colChk = $db->query("SHOW COLUMNS FROM projects LIKE 'is_actual_project'");
            $hasIllegitimateCol = $colChk->rowCount() > 0;
        } catch (Exception $e) {
            $hasIllegitimateCol = false;
        }
    }
    if ($hasIllegitimateCol) {
        $conditions[] = "(is_actual_project IS NULL OR is_actual_project != 'no')";
    }

    // Date filter
    $month  = getMonth();
    $year   = getYear();
    if ($month !== null && $year !== null) {
        $conditions[] = 'MONTH(publication_date) = :month AND YEAR(publication_date) = :year';
        $params[':month'] = $month;
        $params[':year']  = $year;
    } elseif ($year !== null) {
        $conditions[] = 'YEAR(publication_date) = :year';
        $params[':year'] = $year;
    }

    // Region filter
    $region = getRegion();
    if ($region !== null) {
        $conditions[] = 'region = :region';
        $params[':region'] = $region;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    // Totals - count projects and unique contractors (trimmed, non-empty)
    $stmt = $db->prepare("
        SELECT
            COUNT(*) AS projects_encoded,
            COALESCE(SUM(project_value), 0) AS total_pipeline_value
        FROM projects
        $where
    ");
    $stmt->execute($params);
    $totals = $stmt->fetch();
    
    // Count unique contractors separately (excluding empty/null after trim)
    $stmt_contractors = $db->prepare("
        SELECT COUNT(DISTINCT TRIM(contractor_name)) AS contractors_identified
        FROM projects
        $where
        AND TRIM(COALESCE(contractor_name, '')) != ''
    ");
    $stmt_contractors->execute($params);
    $contractorCount = $stmt_contractors->fetch();
    
    $totals['contractors_identified'] = $contractorCount['contractors_identified'];

    // Status breakdown
    $stmt2 = $db->prepare("
        SELECT status, COUNT(*) AS cnt, COALESCE(SUM(project_value), 0) AS val
        FROM projects
        $where
        GROUP BY status
        ORDER BY val DESC
    ");
    $stmt2->execute($params);
    $categories = $stmt2->fetchAll();

    $categoryMap = [];
    foreach ($categories as $cat) {
        $key = strtolower(str_replace([' ', '-', '/'], '_', $cat['status']));
        $categoryMap[$key] = [
            'count' => (int)   $cat['cnt'],
            'value' => (float) $cat['val'],
        ];
    }

    // Source distribution
    $stmt3 = $db->prepare("
        SELECT source, COUNT(*) AS cnt
        FROM projects
        $where
        GROUP BY source
        ORDER BY cnt DESC
    ");
    $stmt3->execute($params);
    $sources = $stmt3->fetchAll();

    $sourceDistribution = [];
    foreach ($sources as $src) {
        $sourceName = $src['source'] ?: 'Unknown';
        $sourceDistribution[$sourceName] = (int) $src['cnt'];
    }

    jsonResponse([
        'data' => array_merge([
            'projects_encoded'       => (int)   $totals['projects_encoded'],
            'contractors_identified' => (int)   $totals['contractors_identified'],
            'total_pipeline_value'   => (float) $totals['total_pipeline_value'],
            'pipeline_value'         => (float) $totals['total_pipeline_value'],
            'source_distribution'    => $sourceDistribution,
        ], $categoryMap),
    ]);

} catch (Exception $e) {
    error_log('KPI API error: ' . $e->getMessage());
    jsonResponse([
        'data' => [
            'projects_encoded'       => 0,
            'contractors_identified' => 0,
            'total_pipeline_value'   => 0,
            'pipeline_value'         => 0,
        ],
    ]);
}
