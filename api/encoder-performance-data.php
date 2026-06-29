<?php
/* ============================================================
   GET /api/v1/encoder-performance-data
   Returns encoder performance data (replaces inline data injection)
   ============================================================ */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

// Get user ID from session
$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId === 0) {
    jsonError('Invalid user', 400);
}

try {
    $pdo = getDB();

    function ep_row($pdo, $sql, $params = []) {
        $s = $pdo->prepare($sql); 
        $s->execute($params);
        return $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    function ep_all($pdo, $sql, $params = []) {
        $s = $pdo->prepare($sql); 
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    // Summary data
    $summary = ep_row($pdo, "
        SELECT COUNT(*) AS total,
            COALESCE(SUM(project_value), 0) AS total_value,
            SUM(DATE(created_at) = CURDATE()) AS today,
            SUM(YEARWEEK(created_at,1) = YEARWEEK(NOW(),1)) AS this_week,
            SUM(YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())) AS this_month,
            SUM(status = 'Priority') AS priority_count,
            MIN(created_at) AS first_encoded_at,
            GREATEST(DATEDIFF(NOW(), MIN(created_at))+1, 1) AS days_active
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL
    ", [$userId]);

    // Daily (last 30 days)
    $dailyRows = ep_all($pdo, "
        SELECT DATE(created_at) AS day, COUNT(*) AS cnt
        FROM projects WHERE encoded_by = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) AND archived_at IS NULL
        GROUP BY day ORDER BY day
    ", [$userId]);
    $dailyMap = [];
    foreach ($dailyRows as $r) $dailyMap[$r['day']] = (int)$r['cnt'];
    $daily30 = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $daily30[] = [
            'date' => $d, 
            'label' => date('M j', strtotime($d)), 
            'cnt' => $dailyMap[$d] ?? 0
        ];
    }

    // Weekly (last 8 weeks)
    $weeklyRows = ep_all($pdo, "
        SELECT YEARWEEK(created_at,1) AS yw, MIN(DATE(created_at)) AS week_start, COUNT(*) AS cnt
        FROM projects WHERE encoded_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK) AND archived_at IS NULL
        GROUP BY yw ORDER BY yw
    ", [$userId]);
    $weeklyMap = [];
    foreach ($weeklyRows as $r) $weeklyMap[$r['yw']] = (int)$r['cnt'];
    $weekly8 = [];
    for ($i = 7; $i >= 0; $i--) {
        $ts  = strtotime("-{$i} weeks", strtotime('monday this week'));
        $yw  = (int)date('oW', $ts);
        $weekly8[] = [
            'label' => 'Wk ' . date('M j', $ts), 
            'cnt' => $weeklyMap[$yw] ?? 0
        ];
    }

    // Monthly (last 6 months)
    $monthlyRows = ep_all($pdo, "
        SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt
        FROM projects WHERE encoded_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH) AND archived_at IS NULL
        GROUP BY month ORDER BY month
    ", [$userId]);
    $monthlyMap = [];
    foreach ($monthlyRows as $r) $monthlyMap[$r['month']] = (int)$r['cnt'];
    $monthly6 = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-{$i} months"));
        $monthly6[] = [
            'label' => date('M Y', strtotime($m . '-01')), 
            'cnt' => $monthlyMap[$m] ?? 0
        ];
    }

    // Breakdowns
    $bySource = ep_all($pdo, "
        SELECT COALESCE(NULLIF(source,''),'Unknown') AS source, COUNT(*) AS cnt 
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL 
        GROUP BY source ORDER BY cnt DESC
    ", [$userId]);
    
    $byStatus = ep_all($pdo, "
        SELECT COALESCE(NULLIF(status,''),'Unknown') AS status, COUNT(*) AS cnt 
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL 
        GROUP BY status ORDER BY cnt DESC
    ", [$userId]);
    
    $byRegion = ep_all($pdo, "
        SELECT COALESCE(NULLIF(project_region,''),NULLIF(region,''),'Unknown') AS region_name, COUNT(*) AS cnt 
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL 
        GROUP BY region_name ORDER BY cnt DESC LIMIT 10
    ", [$userId]);

    // Data quality
    $q = ep_row($pdo, "
        SELECT COUNT(*) AS total,
            SUM(contractor_name IS NOT NULL AND contractor_name != '') AS has_contractor,
            SUM(project_name IS NOT NULL AND project_name != '') AS has_project_name,
            SUM(project_value IS NOT NULL AND project_value > 0) AS has_value,
            SUM(contract_blk_lot IS NOT NULL OR contract_street IS NOT NULL) AS has_address
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL
    ", [$userId]);

    $qualityScore = 0;
    if ((int)$q['total'] > 0) {
        $qualityScore = round((
            ((int)$q['has_contractor'] / (int)$q['total']) * 0.30 +
            ((int)$q['has_project_name'] / (int)$q['total']) * 0.25 +
            ((int)$q['has_value'] / (int)$q['total']) * 0.25 +
            ((int)$q['has_address'] / (int)$q['total']) * 0.20
        ) * 100);
    }

    $scoreColor = $qualityScore >= 80 ? '#10b981' : ($qualityScore >= 60 ? '#f59e0b' : '#ef4444');

    jsonResponse([
        'userId' => $userId,
        'daily30' => $daily30,
        'weekly8' => $weekly8,
        'monthly6' => $monthly6,
        'bySource' => $bySource,
        'byStatus' => $byStatus,
        'byRegion' => $byRegion,
        'qualityScore' => $qualityScore,
        'scoreColor' => $scoreColor
    ]);

} catch (Exception $e) {
    error_log('Encoder performance data error: ' . $e->getMessage());
    jsonError('Failed to load performance data', 500);
}
