<?php
/* ============================================================
   pages/encoder-performance.php — My Performance (Encoder)
   ============================================================ */
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login'); exit;
}

$role     = $_SESSION['user']['role']      ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');
$userId   = (int)($_SESSION['user']['id'] ?? 0);

if (!in_array($role, ['encoder', 'admin', 'superadmin'], true)) {
    header('Location: ' . $base . '/'); exit;
}

require_once __DIR__ . '/../api/db.php';
$pdo = getDB();

function ep_row($pdo, $sql, $params = []) {
    $s = $pdo->prepare($sql); $s->execute($params);
    return $s->fetch(PDO::FETCH_ASSOC) ?: [];
}
function ep_all($pdo, $sql, $params = []) {
    $s = $pdo->prepare($sql); $s->execute($params);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}

// ── Data ────────────────────────────────────────────────────────
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
$avgPerDay = $summary['days_active'] > 0 ? round($summary['total'] / $summary['days_active'], 1) : 0;

// Daily (last 30 days, fill gaps)
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
    $daily30[] = ['date' => $d, 'label' => date('M j', strtotime($d)), 'cnt' => $dailyMap[$d] ?? 0];
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
    $weekly8[] = ['label' => 'Wk ' . date('M j', $ts), 'cnt' => $weeklyMap[$yw] ?? 0];
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
    $monthly6[] = ['label' => date('M Y', strtotime($m . '-01')), 'cnt' => $monthlyMap[$m] ?? 0];
}

// Breakdowns
$bySource = ep_all($pdo, "SELECT COALESCE(NULLIF(source,''),'Unknown') AS source, COUNT(*) AS cnt FROM projects WHERE encoded_by = ? AND archived_at IS NULL GROUP BY source ORDER BY cnt DESC", [$userId]);
$byStatus = ep_all($pdo, "SELECT COALESCE(NULLIF(status,''),'Unknown') AS status, COUNT(*) AS cnt FROM projects WHERE encoded_by = ? AND archived_at IS NULL GROUP BY status ORDER BY cnt DESC", [$userId]);
$byRegion = ep_all($pdo, "SELECT COALESCE(NULLIF(project_region,''),NULLIF(region,''),'Unknown') AS region_name, COUNT(*) AS cnt FROM projects WHERE encoded_by = ? AND archived_at IS NULL GROUP BY region_name ORDER BY cnt DESC LIMIT 10", [$userId]);

// Data quality
$q = ep_row($pdo, "
    SELECT COUNT(*) AS total,
        SUM(contractor_name IS NOT NULL AND contractor_name != '') AS has_contractor,
        SUM(contact_number IS NOT NULL AND contact_number != '') AS has_contact,
        SUM(contact_person IS NOT NULL AND contact_person != '') AS has_contact_person,
        SUM((project_region IS NOT NULL AND project_region != '') OR (region IS NOT NULL AND region != '')) AS has_region,
        SUM((project_city IS NOT NULL AND project_city != '') OR (city_province IS NOT NULL AND city_province != '')) AS has_city,
        SUM(project_coordinates IS NOT NULL AND project_coordinates != '') AS has_coords,
        SUM(project_value IS NOT NULL AND project_value > 0) AS has_value,
        SUM(publication_date IS NOT NULL) AS has_pub_date
    FROM projects WHERE encoded_by = ? AND archived_at IS NULL
", [$userId]);
$qt = (int)($q['total'] ?? 0);
$pctFn = fn($n) => $qt > 0 ? round(100 * (int)$n / $qt) : 0;
$qualityMetrics = [
    ['label' => 'Contractor Name',  'pct' => $pctFn($q['has_contractor']),      'icon' => '🏢', 'w' => 1.5],
    ['label' => 'Contact Number',   'pct' => $pctFn($q['has_contact']),          'icon' => '📞', 'w' => 1.5],
    ['label' => 'Published Date',   'pct' => $pctFn($q['has_pub_date']),         'icon' => '📅', 'w' => 1.0],
    ['label' => 'Project Value',    'pct' => $pctFn($q['has_value']),            'icon' => '💰', 'w' => 1.5],
    ['label' => 'Region',           'pct' => $pctFn($q['has_region']),           'icon' => '📍', 'w' => 1.5],
    ['label' => 'City / Province',  'pct' => $pctFn($q['has_city']),             'icon' => '🏙', 'w' => 1.0],
    ['label' => 'Contact Person',   'pct' => $pctFn($q['has_contact_person']),   'icon' => '👤', 'w' => 0.75],
    ['label' => 'Coordinates',      'pct' => $pctFn($q['has_coords']),           'icon' => '🗺', 'w' => 0.75],
];
$wTotal = array_sum(array_column($qualityMetrics, 'w'));
$wSum   = 0;
foreach ($qualityMetrics as $m) $wSum += ($m['pct'] / 100) * $m['w'];
$qualityScore = $wTotal > 0 ? round(100 * $wSum / $wTotal) : 0;

// Recent projects
$recent = ep_all($pdo, "
    SELECT project_name, contractor_name, project_value, status, source,
           COALESCE(NULLIF(project_region,''), region, '—') AS region_name, created_at
    FROM projects WHERE encoded_by = ? AND archived_at IS NULL
    ORDER BY created_at DESC LIMIT 10
", [$userId]);

// Team
$team = ep_all($pdo, "
    SELECT u.id, u.full_name, COUNT(p.id) AS cnt
    FROM users u LEFT JOIN projects p ON p.encoded_by = u.id AND p.archived_at IS NULL
    WHERE u.role = 'encoder'
    GROUP BY u.id ORDER BY cnt DESC
", []);
$myRank = 1;
foreach ($team as $i => $t) {
    if ((int)$t['id'] === $userId) { $myRank = $i + 1; break; }
}

// ── Helpers ─────────────────────────────────────────────────────
function numFmt($n)  { return number_format((float)$n, 0, '.', ','); }
function valFmt($n)  { return '₱' . number_format((float)$n, 0, '.', ','); }
function scoreBadge($s) {
    if ($s >= 90) return ['🟢', '#34d399', 'Excellent'];
    if ($s >= 75) return ['🟡', '#fbbf24', 'Good'];
    if ($s >= 55) return ['🟠', '#fb923c', 'Fair'];
    return ['🔴', '#f87171', 'Needs Work'];
}
[$scoreIcon, $scoreColor, $scoreLabel] = scoreBadge($qualityScore);

$statusColors = [
    'For Execution' => 'rgba(96,165,250,0.85)',
    'Awarded'       => 'rgba(52,211,153,0.85)',
    'For Bidding'   => 'rgba(251,191,36,0.85)',
    'Priority'      => 'rgba(255,122,0,0.9)',
    'Unknown'       => 'rgba(148,163,184,0.6)',
];
$sourceColors = [
    'DPWH'    => 'rgba(255,122,0,0.85)',
    'BCI'     => 'rgba(96,165,250,0.85)',
    'EGOV'    => 'rgba(167,139,250,0.85)',
    'Unknown' => 'rgba(148,163,184,0.6)',
];

$firstDate = $summary['first_encoded_at'] ? date('M j, Y', strtotime($summary['first_encoded_at'])) : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Performance — TDT Powersteel</title>
<link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
<link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
<link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
<link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
<link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
<link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
<link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
<link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
<link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/encoder-performance.css?v=1">
</head>
<body data-role="<?= htmlspecialchars($role) ?>">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="perf-wrap">

    <!-- ── Page header ── -->
    <div class="perf-header">
        <div class="perf-header-left">
            <h1>📊 My Performance</h1>
            <div class="perf-header-meta">
                <span class="perf-meta-tag">👤 <strong><?= htmlspecialchars($fullName) ?></strong></span>
                <span class="perf-meta-tag">🗓 Encoding since <strong><?= $firstDate ?></strong></span>
                <?php if ($myRank && count($team) > 0): ?>
                <span class="perf-meta-tag">🏆 Rank <strong>#<?= $myRank ?> of <?= count($team) ?></strong></span>
                <?php endif; ?>
            </div>
        </div>
        <button class="perf-refresh-btn" onclick="window.location.reload()">↻ Refresh</button>
    </div>

    <!-- ── KPI Row 1 ── -->
    <div class="perf-section-label">Overview</div>
    <div class="kpi-grid" style="margin-bottom:1rem;">
        <div class="kpi-card" style="--kpi-accent:rgba(255,122,0,0.5);--kpi-icon-bg:rgba(255,122,0,0.1);">
            <div class="kpi-top">
                <div class="kpi-icon">📋</div>
                <span class="kpi-trend flat">All Time</span>
            </div>
            <div class="kpi-value"><?= numFmt($summary['total']) ?></div>
            <div class="kpi-label">Total Projects Encoded</div>
            <div class="kpi-sub">Since <?= $firstDate ?></div>
        </div>
        <div class="kpi-card" style="--kpi-accent:rgba(52,211,153,0.5);--kpi-icon-bg:rgba(52,211,153,0.1);">
            <div class="kpi-top">
                <div class="kpi-icon">💰</div>
                <span class="kpi-trend up">₱</span>
            </div>
            <div class="kpi-value" style="font-size:clamp(1rem,1.8vw,1.4rem);"><?= valFmt($summary['total_value']) ?></div>
            <div class="kpi-label">Total Value Encoded</div>
            <div class="kpi-sub">Cumulative project values</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:rgba(96,165,250,0.5);--kpi-icon-bg:rgba(96,165,250,0.1);">
            <div class="kpi-top">
                <div class="kpi-icon">📅</div>
                <span class="kpi-trend flat">Month</span>
            </div>
            <div class="kpi-value"><?= numFmt($summary['this_month']) ?></div>
            <div class="kpi-label">This Month</div>
            <div class="kpi-sub"><?= date('F Y') ?></div>
        </div>
        <div class="kpi-card" style="--kpi-accent:rgba(167,139,250,0.5);--kpi-icon-bg:rgba(167,139,250,0.1);">
            <div class="kpi-top">
                <div class="kpi-icon">🗓</div>
                <span class="kpi-trend flat">Week</span>
            </div>
            <div class="kpi-value"><?= numFmt($summary['this_week']) ?></div>
            <div class="kpi-label">This Week</div>
            <div class="kpi-sub">Current ISO week</div>
        </div>
    </div>
    <div class="kpi-grid" style="margin-bottom:1.75rem;">
        <div class="kpi-card" style="--kpi-accent:rgba(251,191,36,0.5);--kpi-icon-bg:rgba(251,191,36,0.1);">
            <div class="kpi-top">
                <div class="kpi-icon">☀️</div>
                <span class="kpi-trend flat">Today</span>
            </div>
            <div class="kpi-value"><?= numFmt($summary['today']) ?></div>
            <div class="kpi-label">Encoded Today</div>
            <div class="kpi-sub"><?= date('F j, Y') ?></div>
        </div>
        <div class="kpi-card" style="--kpi-accent:rgba(255,122,0,0.4);--kpi-icon-bg:rgba(255,122,0,0.08);">
            <div class="kpi-top">
                <div class="kpi-icon">📈</div>
                <span class="kpi-trend up">avg</span>
            </div>
            <div class="kpi-value"><?= $avgPerDay ?></div>
            <div class="kpi-label">Avg. Projects / Day</div>
            <div class="kpi-sub">Over <?= numFmt($summary['days_active']) ?> active days</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:rgba(248,113,113,0.5);--kpi-icon-bg:rgba(248,113,113,0.1);">
            <div class="kpi-top">
                <div class="kpi-icon">⭐</div>
                <span class="kpi-trend rank">Priority</span>
            </div>
            <div class="kpi-value"><?= numFmt($summary['priority_count']) ?></div>
            <div class="kpi-label">Priority Projects</div>
            <div class="kpi-sub"><?= $qt > 0 ? round(100 * $summary['priority_count'] / $qt) : 0 ?>% of total</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:<?= $scoreColor ?>88;--kpi-icon-bg:<?= $scoreColor ?>18;">
            <div class="kpi-top">
                <div class="kpi-icon"><?= $scoreIcon ?></div>
                <span class="kpi-trend <?= $qualityScore >= 75 ? 'up' : 'flat' ?>"><?= $scoreLabel ?></span>
            </div>
            <div class="kpi-value" style="color:<?= $scoreColor ?>;"><?= $qualityScore ?>%</div>
            <div class="kpi-label">Data Quality Score</div>
            <div class="kpi-sub">Weighted field completeness</div>
        </div>
    </div>

    <!-- ── Charts Row 1: Daily bar + Status doughnut ── -->
    <div class="perf-section-label">Encoding Activity</div>
    <div class="chart-row chart-row-2-1" style="margin-bottom:1rem;">
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <div class="chart-card-title">Daily Encoding Rate</div>
                    <div class="chart-card-sub">Projects encoded per day</div>
                </div>
                <div class="chart-tabs" id="dailyTabs">
                    <button class="chart-tab active" data-range="30">30 Days</button>
                    <button class="chart-tab" data-range="7">7 Days</button>
                </div>
            </div>
            <div class="chart-wrap" style="height:220px;">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <div class="chart-card-title">By Status</div>
                    <div class="chart-card-sub">Project status breakdown</div>
                </div>
            </div>
            <div class="chart-wrap" style="height:220px;display:flex;align-items:center;justify-content:center;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ── Charts Row 2: Source + Region ── -->
    <div class="chart-row chart-row-1-1" style="margin-bottom:1rem;">
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <div class="chart-card-title">By Source</div>
                    <div class="chart-card-sub">Projects per data source</div>
                </div>
            </div>
            <div class="chart-wrap" style="height:200px;">
                <canvas id="sourceChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <div class="chart-card-title">Top Regions</div>
                    <div class="chart-card-sub">Most encoded project regions</div>
                </div>
                <span class="chart-card-badge">Top <?= min(10, count($byRegion)) ?></span>
            </div>
            <div class="chart-wrap" style="height:200px;">
                <canvas id="regionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ── Trend chart ── -->
    <div class="chart-row chart-row-1" style="margin-bottom:1rem;">
        <div class="chart-card">
            <div class="chart-card-header">
                <div>
                    <div class="chart-card-title">Encoding Trend</div>
                    <div class="chart-card-sub">Projects encoded over time</div>
                </div>
                <div class="chart-tabs" id="trendTabs">
                    <button class="chart-tab active" data-period="weekly">Weekly</button>
                    <button class="chart-tab" data-period="monthly">Monthly</button>
                </div>
            </div>
            <div class="chart-wrap" style="height:180px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ── Data Quality ── -->
    <div class="perf-section-label">Data Quality</div>
    <div class="quality-grid" style="margin-bottom:1rem;">
        <div class="quality-score-card">
            <div class="quality-ring-wrap">
                <canvas id="qualityRingChart" width="130" height="130"></canvas>
                <div class="quality-ring-center">
                    <div class="quality-ring-pct" style="color:<?= $scoreColor ?>;"><?= $qualityScore ?></div>
                    <div class="quality-ring-lbl" style="color:<?= $scoreColor ?>;">%</div>
                </div>
            </div>
            <div class="quality-score-title"><?= $scoreIcon ?> <?= $scoreLabel ?> Quality</div>
            <div class="quality-score-note">
                Based on <?= numFmt($qt) ?> project<?= $qt !== 1 ? 's' : '' ?>.<br>
                Measures completeness of key fields.
            </div>
        </div>
        <div class="quality-bars-card">
            <div class="chart-card-title" style="margin-bottom:0.25rem;">Field Completeness</div>
            <div class="chart-card-sub" style="margin-bottom:0.75rem;">Percentage of projects with each field filled in</div>
            <?php foreach ($qualityMetrics as $m):
                $barColor = $m['pct'] >= 90 ? '#34d399' : ($m['pct'] >= 70 ? '#fb923c' : ($m['pct'] >= 40 ? '#fbbf24' : '#f87171'));
            ?>
            <div class="quality-bar-row">
                <span class="quality-bar-icon"><?= $m['icon'] ?></span>
                <span class="quality-bar-label"><?= htmlspecialchars($m['label']) ?></span>
                <div class="quality-bar-track">
                    <div class="quality-bar-fill" style="width:<?= $m['pct'] ?>%;background:<?= $barColor ?>;"></div>
                </div>
                <span class="quality-bar-pct" style="color:<?= $barColor ?>;"><?= $m['pct'] ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Team Ranking ── -->
    <?php if (count($team) > 0): ?>
    <div class="perf-section-label">Team Comparison</div>
    <div class="team-card" style="margin-bottom:1rem;">
        <div class="chart-card-title" style="margin-bottom:0.15rem;">Encoder Leaderboard</div>
        <div class="chart-card-sub" style="margin-bottom:1rem;">Total projects encoded (all time, active projects only)</div>
        <?php
        $maxTeam = max(array_column($team, 'cnt')) ?: 1;
        $rankBadgeClass = ['gold', 'silver', 'bronze'];
        foreach ($team as $i => $t):
            $isMe   = (int)$t['id'] === $userId;
            $rClass = $isMe ? 'me' : ($rankBadgeClass[$i] ?? 'other');
            $barPct = round(100 * (int)$t['cnt'] / $maxTeam);
            $barCol = $isMe ? 'linear-gradient(90deg,#ff7a00,#fbbf24)' : 'rgba(0,0,0,0.08)';
            $medal  = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#' . ($i + 1)));
        ?>
        <div class="team-row">
            <div class="team-rank-badge <?= $rClass ?>"><?= $i < 3 ? ($i + 1) : ($i + 1) ?></div>
            <div class="team-name <?= $isMe ? 'me' : '' ?>">
                <?= $i < 3 ? $medal . ' ' : '' ?><?= htmlspecialchars($t['full_name']) ?>
                <?= $isMe ? '<span style="font-size:0.65rem;font-weight:700;color:#fb923c;margin-left:0.35rem;padding:0.1rem 0.4rem;background:rgba(255,122,0,0.12);border-radius:4px;">YOU</span>' : '' ?>
            </div>
            <div class="team-bar-wrap">
                <div class="team-bar-track">
                    <div class="team-bar-fill" style="width:<?= $barPct ?>%;background:<?= $barCol ?>;"></div>
                </div>
            </div>
            <div class="team-count"><?= numFmt($t['cnt']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── Recent Projects ── -->
    <div class="perf-section-label">Recent Projects</div>
    <div class="recent-card">
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Region</th>
                    <th>Encoded</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recent)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No projects encoded yet.</td></tr>
            <?php else: ?>
            <?php foreach ($recent as $p):
                $chipClass = match(true) {
                    str_contains($p['status'], 'Execution') => 'chip-execution',
                    str_contains($p['status'], 'Bidding')   => 'chip-bidding',
                    $p['status'] === 'Awarded'              => 'chip-awarded',
                    $p['status'] === 'Priority'             => 'chip-priority',
                    default                                 => 'chip-other'
                };
                $dt = date('M j, Y g:ia', strtotime($p['created_at']));
            ?>
            <tr>
                <td class="td-proj">
                    <div class="proj-name"><?= htmlspecialchars($p['project_name'] ?: '—') ?></div>
                    <div class="proj-contractor"><?= htmlspecialchars($p['contractor_name'] ?: '—') ?></div>
                </td>
                <td class="td-val"><?= $p['project_value'] > 0 ? valFmt($p['project_value']) : '<span style="color:var(--text-muted);">—</span>' ?></td>
                <td><span class="status-chip <?= $chipClass ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                <td style="color:var(--text-secondary);font-size:0.75rem;"><?= htmlspecialchars($p['source'] ?: '—') ?></td>
                <td style="color:var(--text-secondary);font-size:0.75rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['region_name']) ?></td>
                <td class="td-dt"><?= $dt ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /perf-wrap -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>

<script>
const BASE = '<?= $base ?>';
const myUserId = <?= $userId ?>;
</script>
<script src="<?= $base ?>/static/js/encoder-performance.js?v=2"></script>
</body>
</html>
