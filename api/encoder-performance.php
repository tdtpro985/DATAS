<?php
/* ============================================================
   api/encoder-performance.php — Encoder Performance Data
   ============================================================ */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/db.php';
    session_start();

    if (empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    if (!in_array($_SESSION['user']['role'], ['encoder', 'admin', 'superadmin'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $pdo    = getDB();
    $userId = (int)$_SESSION['user']['id'];

    function ep_row($pdo, $sql, $params = []) {
        $s = $pdo->prepare($sql); $s->execute($params);
        return $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    function ep_all($pdo, $sql, $params = []) {
        $s = $pdo->prepare($sql); $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── 1. Summary ───────────────────────────────────────────────
    $summary = ep_row($pdo, "
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(project_value), 0) AS total_value,
            SUM(DATE(created_at) = CURDATE()) AS today,
            SUM(YEARWEEK(created_at,1) = YEARWEEK(NOW(),1)) AS this_week,
            SUM(YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())) AS this_month,
            SUM(status = 'Priority') AS priority_count,
            MIN(created_at) AS first_encoded_at,
            GREATEST(DATEDIFF(NOW(), MIN(created_at)) + 1, 1) AS days_active
        FROM projects
        WHERE encoded_by = ? AND archived_at IS NULL
    ", [$userId]);

    $summary['avg_per_day'] = $summary['days_active'] > 0
        ? round($summary['total'] / $summary['days_active'], 2)
        : 0;

    // ── 2. Daily — last 30 days (fill gaps) ──────────────────────
    $dailyRows = ep_all($pdo, "
        SELECT DATE(created_at) AS day, COUNT(*) AS cnt
        FROM projects
        WHERE encoded_by = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
          AND archived_at IS NULL
        GROUP BY day ORDER BY day
    ", [$userId]);
    $dailyMap = [];
    foreach ($dailyRows as $r) $dailyMap[$r['day']] = (int)$r['cnt'];
    $daily30 = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $daily30[] = ['date' => $d, 'label' => date('M j', strtotime($d)), 'cnt' => $dailyMap[$d] ?? 0];
    }

    // ── 3. Weekly — last 8 weeks ─────────────────────────────────
    $weeklyRows = ep_all($pdo, "
        SELECT YEARWEEK(created_at,1) AS yw,
               MIN(DATE(created_at)) AS week_start,
               COUNT(*) AS cnt
        FROM projects
        WHERE encoded_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
          AND archived_at IS NULL
        GROUP BY yw ORDER BY yw
    ", [$userId]);
    // fill missing weeks
    $weeklyMap = [];
    foreach ($weeklyRows as $r) $weeklyMap[$r['yw']] = ['cnt' => (int)$r['cnt'], 'start' => $r['week_start']];
    $weekly8 = [];
    for ($i = 7; $i >= 0; $i--) {
        $ts  = strtotime("-{$i} weeks", strtotime('monday this week'));
        $yw  = date('oW', $ts);  // ISO week
        $lbl = 'Wk ' . date('M j', $ts);
        $weekly8[] = ['label' => $lbl, 'cnt' => $weeklyMap[$yw]['cnt'] ?? 0];
    }

    // ── 4. Monthly — last 6 months ───────────────────────────────
    $monthlyRows = ep_all($pdo, "
        SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt
        FROM projects
        WHERE encoded_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
          AND archived_at IS NULL
        GROUP BY month ORDER BY month
    ", [$userId]);
    $monthlyMap = [];
    foreach ($monthlyRows as $r) $monthlyMap[$r['month']] = (int)$r['cnt'];
    $monthly6 = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-{$i} months"));
        $monthly6[] = ['label' => date('M Y', strtotime($m . '-01')), 'cnt' => $monthlyMap[$m] ?? 0];
    }

    // ── 5. By source ─────────────────────────────────────────────
    $bySource = ep_all($pdo, "
        SELECT COALESCE(NULLIF(source,''), 'Unknown') AS source, COUNT(*) AS cnt
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL
        GROUP BY source ORDER BY cnt DESC
    ", [$userId]);

    // ── 6. By status ─────────────────────────────────────────────
    $byStatus = ep_all($pdo, "
        SELECT COALESCE(NULLIF(status,''), 'Unknown') AS status, COUNT(*) AS cnt
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL
        GROUP BY status ORDER BY cnt DESC
    ", [$userId]);

    // ── 7. By region (top 10) ────────────────────────────────────
    $byRegion = ep_all($pdo, "
        SELECT COALESCE(NULLIF(project_region,''), NULLIF(region,''), 'Unknown') AS region_name, COUNT(*) AS cnt
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL
        GROUP BY region_name ORDER BY cnt DESC LIMIT 10
    ", [$userId]);

    // ── 8. Data quality ──────────────────────────────────────────
    $q = ep_row($pdo, "
        SELECT COUNT(*) AS total,
            SUM(contractor_name  IS NOT NULL AND contractor_name  != '') AS has_contractor,
            SUM(contact_number   IS NOT NULL AND contact_number   != '') AS has_contact,
            SUM(contact_person   IS NOT NULL AND contact_person   != '') AS has_contact_person,
            SUM((project_region IS NOT NULL AND project_region != '') OR (region IS NOT NULL AND region != '')) AS has_region,
            SUM((project_city   IS NOT NULL AND project_city   != '') OR (city_province IS NOT NULL AND city_province != '')) AS has_city,
            SUM(project_coordinates IS NOT NULL AND project_coordinates != '') AS has_coords,
            SUM(project_value IS NOT NULL AND project_value > 0) AS has_value,
            SUM(publication_date IS NOT NULL) AS has_pub_date
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL
    ", [$userId]);
    $qt = (int)($q['total'] ?? 0);
    $pct = fn($n) => $qt > 0 ? round(100 * (int)$n / $qt) : 0;
    $quality = [
        'total' => $qt,
        'score' => 0,
        'metrics' => [
            ['label' => 'Contractor Name',  'pct' => $pct($q['has_contractor']),      'icon' => '🏢'],
            ['label' => 'Contact Number',   'pct' => $pct($q['has_contact']),          'icon' => '📞'],
            ['label' => 'Published Date',   'pct' => $pct($q['has_pub_date']),         'icon' => '📅'],
            ['label' => 'Project Value',    'pct' => $pct($q['has_value']),            'icon' => '💰'],
            ['label' => 'Region',           'pct' => $pct($q['has_region']),           'icon' => '📍'],
            ['label' => 'City / Province',  'pct' => $pct($q['has_city']),             'icon' => '🏙️'],
            ['label' => 'Contact Person',   'pct' => $pct($q['has_contact_person']),   'icon' => '👤'],
            ['label' => 'Coordinates',      'pct' => $pct($q['has_coords']),           'icon' => '🗺️'],
        ]
    ];
    // Weighted quality score
    $weights = [1.5, 1.5, 1.0, 1.5, 1.5, 1.0, 0.75, 0.75];
    $wTotal  = array_sum($weights);
    $wSum    = 0;
    foreach ($quality['metrics'] as $i => $m) $wSum += ($m['pct'] / 100) * $weights[$i];
    $quality['score'] = $wTotal > 0 ? round(100 * $wSum / $wTotal) : 0;

    // ── 9. Recent projects (last 10) ─────────────────────────────
    $recent = ep_all($pdo, "
        SELECT project_name, contractor_name, project_value, status, source,
               COALESCE(NULLIF(project_region,''), region, '') AS region_name,
               created_at
        FROM projects WHERE encoded_by = ? AND archived_at IS NULL
        ORDER BY created_at DESC LIMIT 10
    ", [$userId]);

    // ── 10. Team ranking (same encoder role) ─────────────────────
    $team = ep_all($pdo, "
        SELECT u.id, u.full_name, COUNT(p.id) AS cnt
        FROM users u
        LEFT JOIN projects p ON p.encoded_by = u.id AND p.archived_at IS NULL
        WHERE u.role = 'encoder'
        GROUP BY u.id ORDER BY cnt DESC
    ", []);
    $myRank = 1;
    foreach ($team as $i => $t) {
        if ((int)$t['id'] === $userId) { $myRank = $i + 1; break; }
    }

    echo json_encode([
        'summary'  => $summary,
        'daily30'  => $daily30,
        'weekly8'  => $weekly8,
        'monthly6' => $monthly6,
        'bySource' => $bySource,
        'byStatus' => $byStatus,
        'byRegion' => $byRegion,
        'quality'  => $quality,
        'recent'   => $recent,
        'team'     => $team,
        'myRank'   => $myRank,
        'userId'   => $userId,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('[ENCODER PERF] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
