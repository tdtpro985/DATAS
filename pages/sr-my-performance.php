<?php
/* ============================================================
   pages/sr-my-performance.php — My Performance (Sales Rep)
   ============================================================ */
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) { header('Location: ' . $base . '/login'); exit; }

$role     = $_SESSION['user']['role']      ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');
$userId   = (int)($_SESSION['user']['id'] ?? 0);

if (!in_array($role, ['sales_rep', 'admin', 'superadmin'], true)) {
    header('Location: ' . $base . '/'); exit;
}

require_once __DIR__ . '/../api/db.php';
$pdo = getDB();

function sp_row($pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetch(PDO::FETCH_ASSOC) ?: []; }
function sp_all($pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(PDO::FETCH_ASSOC); }

// DB is stored in PH time (+08:00). Format as PH local time.
function phDt($dt) {
    if (!$dt) return null;
    $d = new DateTime($dt, new DateTimeZone('Asia/Manila'));
    return $d->format('M j, Y g:i:s A');
}

function durFmt($secs) {
    if ($secs === null || $secs === '' || (int)$secs < 0) return null;
    $secs = (int)$secs;
    if ($secs === 0) return '0 secs';
    $d = intdiv($secs, 86400);
    $h = intdiv($secs % 86400, 3600);
    $m = intdiv($secs % 3600, 60);
    $s = $secs % 60;
    $parts = [];
    if ($d > 0) $parts[] = $d . ' day' . ($d > 1 ? 's' : '');
    if ($h > 0) $parts[] = $h . ' hr' . ($h > 1 ? 's' : '');
    if ($m > 0) $parts[] = $m . ' min' . ($m > 1 ? 's' : '');
    $parts[] = $s . ' sec' . ($s !== 1 ? 's' : '');
    return implode(' ', $parts);
}

function pct($a, $b) { return $b > 0 ? round(100 * (int)$a / (int)$b) : 0; }
function valFmt($n)  { return '₱' . number_format((float)$n, 0, '.', ','); }
function numFmt($n)  { return number_format((float)$n, 0, '.', ','); }

// ── Pipeline summary (correct order: Assigned→Contacted→Quoted→Qualified→To Win→Complete) ─────
$pipe = sp_row($pdo, "
    SELECT
        COUNT(*)                                       AS total_assigned,
        SUM(st.tracking_status = 'Complete')           AS completed,
        SUM(st.contacted       = 'Yes')                AS contacted,
        SUM(st.quoted          = 'Yes')                AS quoted,
        SUM(st.sales_qualified = 'Yes')                AS qualified,
        SUM(st.to_win          = 'Yes')                AS to_win,
        COALESCE(SUM(st.wa_amount), 0)                 AS total_wa,
        COALESCE(AVG(NULLIF(st.probability_percentage, 0)), 0) AS avg_prob,
        SUM(st.next_followup_date >= CURDATE())        AS upcoming_followups,
        MIN(st.created_at)                             AS first_track,
        GREATEST(DATEDIFF(NOW(), MIN(st.created_at)) + 1, 1) AS days_active,
        -- Avg seconds between each stage (correct order)
        ROUND(AVG(CASE WHEN st.contacted_at IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, COALESCE(st.assigned_at, st.created_at), st.contacted_at) END))
            AS avg_secs_to_contact,
        ROUND(AVG(CASE WHEN st.quoted_at IS NOT NULL AND st.contacted_at IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, st.contacted_at, st.quoted_at) END))
            AS avg_secs_to_quote,
        ROUND(AVG(CASE WHEN st.sales_qualified_at IS NOT NULL AND st.quoted_at IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, st.quoted_at, st.sales_qualified_at) END))
            AS avg_secs_to_qualify,
        ROUND(AVG(CASE WHEN st.to_win_at IS NOT NULL AND st.sales_qualified_at IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, st.sales_qualified_at, st.to_win_at) END))
            AS avg_secs_to_win,
        ROUND(AVG(CASE WHEN st.tracking_status = 'Complete'
            THEN TIMESTAMPDIFF(SECOND, COALESCE(st.assigned_at, st.created_at), st.updated_at) END))
            AS avg_secs_total
    FROM sales_tracking st
    INNER JOIN projects p ON p.id = st.project_id AND p.archived_at IS NULL
    WHERE st.sales_rep_id = ?
", [$userId]);

// ── Project value summary (no archived) ────────────────────────────
$projSummary = sp_row($pdo, "
    SELECT COUNT(DISTINCT p.id)               AS proj_count,
        COALESCE(SUM(p.project_value), 0)     AS total_value,
        SUM(p.status = 'Awarded')             AS awarded_count,
        COALESCE(SUM(CASE WHEN p.status='Awarded' THEN p.project_value ELSE 0 END), 0) AS awarded_value
    FROM sales_tracking st
    INNER JOIN projects p ON p.id = st.project_id AND p.archived_at IS NULL
    WHERE st.sales_rep_id = ?
", [$userId]);

// ── Per-project pipeline timelines ─────────────────────────────────
$timelines = sp_all($pdo, "
    SELECT
        p.id, p.project_name, p.contractor_name, p.project_value, p.status AS proj_status,
        COALESCE(NULLIF(p.project_region,''), p.region, '') AS region_name,
        st.tracking_status, st.wa_amount, st.probability_percentage,
        COALESCE(st.assigned_at, st.created_at)            AS assigned_at,
        st.contacted_at,
        st.quoted_at,
        st.sales_qualified_at,
        st.to_win_at,
        CASE WHEN st.tracking_status = 'Complete' THEN st.updated_at ELSE NULL END AS completed_at,
        TIMESTAMPDIFF(SECOND, COALESCE(st.assigned_at, st.created_at), st.contacted_at)        AS secs_to_contact,
        TIMESTAMPDIFF(SECOND, st.contacted_at, st.quoted_at)                                   AS secs_to_quote,
        TIMESTAMPDIFF(SECOND, st.quoted_at, st.sales_qualified_at)                             AS secs_to_qualify,
        TIMESTAMPDIFF(SECOND, st.sales_qualified_at, st.to_win_at)                             AS secs_to_win,
        CASE WHEN st.tracking_status = 'Complete'
            THEN TIMESTAMPDIFF(SECOND, COALESCE(st.assigned_at, st.created_at), st.updated_at)
            ELSE TIMESTAMPDIFF(SECOND, COALESCE(st.assigned_at, st.created_at), NOW())
        END AS secs_total
    FROM sales_tracking st
    INNER JOIN projects p ON p.id = st.project_id AND p.archived_at IS NULL
    WHERE st.sales_rep_id = ?
    ORDER BY COALESCE(st.assigned_at, st.created_at) DESC
", [$userId]);

// ── Breakdowns (no archived) ────────────────────────────────────────
$bySource = sp_all($pdo, "
    SELECT COALESCE(NULLIF(p.source,''),'Unknown') AS source, COUNT(*) AS cnt
    FROM sales_tracking st
    INNER JOIN projects p ON p.id = st.project_id AND p.archived_at IS NULL
    WHERE st.sales_rep_id = ?
    GROUP BY source ORDER BY cnt DESC
", [$userId]);

$byStatus = sp_all($pdo, "
    SELECT COALESCE(NULLIF(p.status,''),'Unknown') AS proj_status, COUNT(*) AS cnt
    FROM sales_tracking st
    INNER JOIN projects p ON p.id = st.project_id AND p.archived_at IS NULL
    WHERE st.sales_rep_id = ?
    GROUP BY proj_status ORDER BY cnt DESC
", [$userId]);

// ── Activity — last 30 days ─────────────────────────────────────────
$actRows = sp_all($pdo, "
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM activity_logs
    WHERE user_id = ? AND action_type = 'SALES_TRACKING_UPDATE'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 29 DAY)
    GROUP BY day ORDER BY day
", [$userId]);
$actMap = [];
foreach ($actRows as $r) $actMap[$r['day']] = (int)$r['cnt'];
$activity30 = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days", strtotime(date('Y-m-d', strtotime('+8 hours')))));
    $activity30[] = ['date' => $d, 'label' => date('M j', strtotime($d)), 'cnt' => $actMap[$d] ?? 0];
}

// ── Upcoming follow-ups (no archived) ──────────────────────────────
$followups = sp_all($pdo, "
    SELECT p.id, p.project_name, p.contractor_name, p.project_value, p.status,
           st.next_followup_date, st.tracking_status, st.probability_percentage,
           st.contacted, st.quoted, st.to_win,
           DATEDIFF(st.next_followup_date, CURDATE()) AS days_until
    FROM sales_tracking st
    INNER JOIN projects p ON p.id = st.project_id AND p.archived_at IS NULL
    WHERE st.sales_rep_id = ? AND st.next_followup_date >= CURDATE()
    ORDER BY st.next_followup_date ASC LIMIT 10
", [$userId]);

// ── Team comparison ─────────────────────────────────────────────────
$team = sp_all($pdo, "
    SELECT u.id, u.full_name,
        COUNT(st.id)                            AS assigned,
        COALESCE(SUM(st.to_win = 'Yes'), 0)     AS to_win_count,
        COALESCE(SUM(st.wa_amount), 0)          AS total_wa,
        SUM(st.tracking_status = 'Complete')    AS completed
    FROM users u
    LEFT JOIN sales_tracking st ON st.sales_rep_id = u.id
    WHERE u.role = 'sales_rep'
    GROUP BY u.id ORDER BY total_wa DESC, to_win_count DESC
", []);
$myRank = 1;
foreach ($team as $i => $t) {
    if ((int)$t['id'] === $userId) { $myRank = $i + 1; break; }
}

$total     = max((int)$pipe['total_assigned'], 1);
$contacted = (int)$pipe['contacted'];
$quoted    = (int)$pipe['quoted'];
$qualified = (int)$pipe['qualified'];
$toWin     = (int)$pipe['to_win'];
$completed = (int)$pipe['completed'];
$firstDate = $pipe['first_track'] ? phDt($pipe['first_track']) : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Performance — TDT Powersteel</title>
<link rel="icon" href="<?= $base ?>/static/images/logo_header.png" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
<link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
<link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
<link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
<link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
<link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
<link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
<link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
<link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/sr-my-performance.css?v=1">
</head>
<body data-role="<?= htmlspecialchars($role) ?>">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="perf-wrap">

<!-- ── Header ── -->
<div class="perf-header">
    <div>
        <h1>📊 My Performance</h1>
        <div class="perf-header-meta">
            <span class="pmeta">👤 <strong><?= htmlspecialchars($fullName) ?></strong></span>
            <?php if ($pipe['first_track']): ?>
            <span class="pmeta">🗓 Since <strong><?= phDt($pipe['first_track']) ?></strong></span>
            <?php endif; ?>
            <span class="pmeta">🏆 Rank <strong>#<?= $myRank ?> of <?= count($team) ?></strong> by WA</span>
        </div>
    </div>
    <button class="refresh-btn" onclick="window.location.reload()">↻ Refresh</button>
</div>

<!-- ── KPI ── -->
<div class="sec-label">Overview</div>
<div class="kpi-grid" style="margin-bottom:.875rem;">
    <div class="kpi-card" style="--ka:rgba(96,165,250,.5);--ki:rgba(96,165,250,.1);">
        <div class="kpi-top"><div class="kpi-icon">📋</div><span class="kpi-badge bb">All Time</span></div>
        <div class="kpi-val"><?= numFmt($pipe['total_assigned']) ?></div>
        <div class="kpi-lbl">Assigned Projects</div>
        <div class="kpi-sub">Active (non-archived)</div>
    </div>
    <div class="kpi-card" style="--ka:rgba(52,211,153,.5);--ki:rgba(52,211,153,.1);">
        <div class="kpi-top"><div class="kpi-icon">✅</div><span class="kpi-badge bg"><?= pct($completed, $total) ?>%</span></div>
        <div class="kpi-val"><?= numFmt($completed) ?></div>
        <div class="kpi-lbl">Completed Trackings</div>
        <div class="kpi-sub">Tracking status = Complete</div>
    </div>
    <div class="kpi-card" style="--ka:rgba(251,191,36,.5);--ki:rgba(251,191,36,.1);">
        <div class="kpi-top"><div class="kpi-icon">🏆</div><span class="kpi-badge by"><?= pct($toWin, $total) ?>% win rate</span></div>
        <div class="kpi-val"><?= numFmt($toWin) ?></div>
        <div class="kpi-lbl">To Win</div>
        <div class="kpi-sub">Deals marked To Win</div>
    </div>
    <div class="kpi-card" style="--ka:rgba(52,211,153,.5);--ki:rgba(52,211,153,.1);">
        <div class="kpi-top"><div class="kpi-icon">💰</div><span class="kpi-badge bg">WA Total</span></div>
        <div class="kpi-val" style="font-size:clamp(.95rem,1.7vw,1.3rem);"><?= valFmt($pipe['total_wa']) ?></div>
        <div class="kpi-lbl">Total Won Amount</div>
        <div class="kpi-sub">Sum of all WA entries</div>
    </div>
</div>
<div class="kpi-grid" style="margin-bottom:1.75rem;">
    <div class="kpi-card" style="--ka:rgba(96,165,250,.4);--ki:rgba(96,165,250,.08);">
        <div class="kpi-top"><div class="kpi-icon">📞</div><span class="kpi-badge bb"><?= pct($contacted, $total) ?>%</span></div>
        <div class="kpi-val"><?= numFmt($contacted) ?></div>
        <div class="kpi-lbl">Contacted</div>
        <div class="kpi-sub"><?php $d=durFmt($pipe['avg_secs_to_contact']); echo $d ? 'Avg ' . $d : 'No data yet'; ?></div>
    </div>
    <div class="kpi-card" style="--ka:rgba(167,139,250,.4);--ki:rgba(167,139,250,.08);">
        <div class="kpi-top"><div class="kpi-icon">📄</div><span class="kpi-badge bp"><?= pct($quoted, $total) ?>%</span></div>
        <div class="kpi-val"><?= numFmt($quoted) ?></div>
        <div class="kpi-lbl">Quoted</div>
        <div class="kpi-sub"><?php $d=durFmt($pipe['avg_secs_to_quote']); echo $d ? 'Avg ' . $d : 'No data yet'; ?></div>
    </div>
    <div class="kpi-card" style="--ka:rgba(248,113,113,.4);--ki:rgba(248,113,113,.08);">
        <div class="kpi-top"><div class="kpi-icon">🗓</div><span class="kpi-badge <?= (int)$pipe['upcoming_followups'] > 0 ? 'br' : 'bm' ?>"><?= numFmt($pipe['upcoming_followups']) ?> due</span></div>
        <div class="kpi-val"><?= numFmt($pipe['upcoming_followups']) ?></div>
        <div class="kpi-lbl">Follow-ups Due</div>
        <div class="kpi-sub">From today onwards</div>
    </div>
    <div class="kpi-card" style="--ka:rgba(251,191,36,.4);--ki:rgba(251,191,36,.08);">
        <div class="kpi-top"><div class="kpi-icon">📈</div><span class="kpi-badge by">avg</span></div>
        <div class="kpi-val"><?= round($pipe['avg_prob']) ?>%</div>
        <div class="kpi-lbl">Avg Win Probability</div>
        <div class="kpi-sub">Across active deals</div>
    </div>
</div>

<!-- ── Funnel ── -->
<div class="sec-label">Sales Pipeline Funnel</div>
<div class="funnel-card">
    <div class="funnel-header">
        <div>
            <div class="funnel-title">Deal Progression</div>
            <div class="funnel-sub">Assigned → Contacted → Quoted → Sales Qualified → To Win · <?= numFmt($total) ?> project<?= $total !== 1 ? 's' : '' ?> in pipeline</div>
        </div>
        <span class="funnel-badge"><?= pct($toWin, $total) ?>% Win Rate</span>
    </div>
    <div class="funnel-row">
    <?php
    // CORRECT ORDER: Assigned → Contacted → Quoted → Sales Qualified → To Win → Complete
    $stages = [
        ['icon'=>'📋','label'=>'Assigned',       'count'=>$total,     'prev'=>$total,     'fc'=>'rgba(148,163,184,.7)', 'fb'=>'rgba(148,163,184,.06)', 'avgKey'=>null],
        ['icon'=>'📞','label'=>'Contacted',       'count'=>$contacted, 'prev'=>$total,     'fc'=>'rgba(96,165,250,.9)',  'fb'=>'rgba(96,165,250,.08)',  'avgKey'=>'avg_secs_to_contact'],
        ['icon'=>'📄','label'=>'Quoted',          'count'=>$quoted,    'prev'=>$contacted, 'fc'=>'rgba(167,139,250,.9)','fb'=>'rgba(167,139,250,.08)','avgKey'=>'avg_secs_to_quote'],
        ['icon'=>'✔', 'label'=>'Sales Qualified','count'=>$qualified, 'prev'=>$quoted,    'fc'=>'rgba(251,191,36,.9)', 'fb'=>'rgba(251,191,36,.08)', 'avgKey'=>'avg_secs_to_qualify'],
        ['icon'=>'🏆','label'=>'To Win',          'count'=>$toWin,     'prev'=>$qualified, 'fc'=>'rgba(52,211,153,.9)', 'fb'=>'rgba(52,211,153,.08)', 'avgKey'=>'avg_secs_to_win'],
        ['icon'=>'✅','label'=>'Complete',        'count'=>$completed, 'prev'=>$toWin,     'fc'=>'rgba(52,211,153,1)',  'fb'=>'rgba(52,211,153,.12)', 'avgKey'=>null],
    ];
    foreach ($stages as $idx => $stg):
        $active = $stg['count'] > 0;
        $conv   = $idx === 0 ? 100 : pct($stg['count'], $stg['prev']);
        $avgDur = $stg['avgKey'] ? durFmt($pipe[$stg['avgKey']]) : null;
    ?>
    <?php if ($idx > 0): ?><div class="funnel-arrow">›</div><?php endif; ?>
    <div class="funnel-stage">
        <div class="f-box <?= $active ? 'active' : '' ?>" style="--fc:<?= $stg['fc'] ?>;--fb:<?= $stg['fb'] ?>;">
            <div class="f-icon"><?= $stg['icon'] ?></div>
            <div class="f-count"><?= numFmt($stg['count']) ?></div>
            <div class="f-label"><?= $stg['label'] ?></div>
            <?php if ($idx > 0): ?>
            <div class="f-pct"><?= $conv ?>% conv.</div>
            <?php endif; ?>
            <?php if ($avgDur): ?>
            <div class="f-dur"><strong>Avg time:</strong><br><?= htmlspecialchars($avgDur) ?></div>
            <?php endif; ?>
            <?php if ($stg['avgKey'] === null && $idx === count($stages)-1 && durFmt($pipe['avg_secs_total'])): ?>
            <div class="f-dur"><strong>Avg total:</strong><br><?= htmlspecialchars(durFmt($pipe['avg_secs_total'])) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- ── Per-project Timeline ── -->
<div class="sec-label">Project Tracking Timelines</div>
<div class="tl-section">
<?php if (empty($timelines)): ?>
    <div style="text-align:center;color:var(--text-muted);padding:2.5rem;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:.875rem;">No projects tracked yet.</div>
<?php else: ?>
<?php foreach ($timelines as $idx => $tl):
    $stagesDef = [
        ['key'=>'assigned_at',       'dur'=>null,               'label'=>'Assigned',       'icon'=>'📋', 'sc'=>'rgba(148,163,184,.7)'],
        ['key'=>'contacted_at',      'dur'=>'secs_to_contact',  'label'=>'Contacted',      'icon'=>'📞', 'sc'=>'rgba(96,165,250,.9)'],
        ['key'=>'quoted_at',         'dur'=>'secs_to_quote',    'label'=>'Quoted',         'icon'=>'📄', 'sc'=>'rgba(167,139,250,.9)'],
        ['key'=>'sales_qualified_at','dur'=>'secs_to_qualify',  'label'=>'Sales Qualified','icon'=>'✔',  'sc'=>'rgba(251,191,36,.9)'],
        ['key'=>'to_win_at',         'dur'=>'secs_to_win',      'label'=>'To Win',         'icon'=>'🏆', 'sc'=>'rgba(52,211,153,.9)'],
        ['key'=>'completed_at',      'dur'=>null,               'label'=>'Complete',       'icon'=>'✅', 'sc'=>'rgba(52,211,153,1)'],
    ];
    $projChip = match(true) {
        str_contains($tl['proj_status'],'Execution') => 'chip-ex',
        str_contains($tl['proj_status'],'Bidding')   => 'chip-bi',
        $tl['proj_status'] === 'Awarded'             => 'chip-aw',
        $tl['proj_status'] === 'Priority'            => 'chip-pr',
        default                                      => 'chip-ns'
    };
    $trackChip = $tl['tracking_status'] === 'Complete' ? 'chip-cp' : 'chip-ns';
    $totalDur  = durFmt($tl['secs_total']);
    $isComplete = $tl['tracking_status'] === 'Complete';
    $totalDurLabel = $isComplete ? 'Total: ' . ($totalDur ?? '—') : 'Ongoing: ' . ($totalDur ?? '—');
    $totalDurColor = $isComplete ? '#34d399' : '#fbbf24';
    $assignedDt = phDt($tl['assigned_at']);
?>
<div class="tl-card" id="tlcard-<?= $idx ?>">
    <div class="tl-header" onclick="toggleTl(<?= $idx ?>)">
        <div class="tl-proj-info">
            <div class="tl-proj-name"><?= htmlspecialchars($tl['project_name'] ?: '—') ?></div>
            <div class="tl-proj-sub"><?= htmlspecialchars($tl['contractor_name'] ?: ($tl['region_name'] ?: '—')) ?></div>
        </div>
        <div class="tl-proj-val"><?= $tl['project_value'] > 0 ? valFmt($tl['project_value']) : '—' ?></div>
        <span class="tl-chip <?= $projChip ?>"><?= htmlspecialchars($tl['proj_status']) ?></span>
        <span class="tl-chip <?= $trackChip ?>"><?= htmlspecialchars($tl['tracking_status'] ?: '—') ?></span>
        <div class="tl-total-dur" style="color:<?= $totalDurColor ?>;"><?= htmlspecialchars($totalDurLabel) ?></div>
        <div class="tl-toggle">▼</div>
    </div>
    <div class="tl-body">
        <div class="tl-pipeline">
        <?php foreach ($stagesDef as $si => $stg):
            $ts     = $tl[$stg['key']] ?? null;
            $isDone = !empty($ts);
            $phTs   = phDt($ts);
            $dur    = $stg['dur'] ? durFmt($tl[$stg['dur']] ?? null) : null;
            $datePart = $phTs ? explode(' ', $phTs, 3)[0] . ' ' . explode(' ', $phTs, 3)[1] . ',' . explode(',', $phTs)[1] : null;
            // Split PH date: "Jun 15, 2026 2:21:57 PM" → date + time
            $tsFormatted = $phTs ? $phTs : null;
            $tsParts = $tsFormatted ? explode(' ', $tsFormatted) : [];
            // Format: "Jun 15, 2026" + "2:21:57 PM"
            $dateStr = $tsFormatted ? implode(' ', array_slice($tsParts, 0, 3)) : null;
            $timeStr = $tsFormatted ? implode(' ', array_slice($tsParts, 3)) : null;
        ?>
        <div class="tl-stage">
            <div class="tl-dot-wrap">
                <div class="tl-dot <?= $isDone ? 'done' : 'pending' ?>" style="--sc:<?= $stg['sc'] ?>;">
                    <?= $isDone ? $stg['icon'] : '○' ?>
                </div>
            </div>
            <div class="tl-stage-label" style="color:<?= $isDone ? $stg['sc'] : '' ?>;"><?= $stg['label'] ?></div>
            <div class="tl-stage-ts">
                <?php if ($isDone && $dateStr): ?>
                    <div class="ts-date"><?= htmlspecialchars($dateStr) ?></div>
                    <div class="ts-time"><?= htmlspecialchars($timeStr ?? '') ?></div>
                <?php else: ?>
                    <div class="ts-none">Not yet</div>
                <?php endif; ?>
            </div>
            <?php if ($si === 0): ?>
                <div class="tl-dur-badge first">—</div>
            <?php elseif ($dur && $isDone): ?>
                <div class="tl-dur-badge">↑ <?= htmlspecialchars($dur) ?></div>
            <?php elseif (!$isDone): ?>
                <div class="tl-dur-badge" style="background:transparent;color:rgba(255,255,255,.15);">—</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <!-- summary bar -->
        <div class="tl-summary-bar">
            <div class="tl-sum-item">
                <div class="tl-sum-key">WA Amount</div>
                <div class="tl-sum-val" style="color:#34d399;"><?= (float)$tl['wa_amount'] > 0 ? valFmt($tl['wa_amount']) : '—' ?></div>
            </div>
            <div class="tl-sum-item">
                <div class="tl-sum-key">Win Probability</div>
                <div class="tl-sum-val" style="color:#fbbf24;"><?= round($tl['probability_percentage']) ?>%</div>
            </div>
            <div class="tl-sum-item">
                <div class="tl-sum-key">Total Duration</div>
                <div class="tl-sum-val" style="color:<?= $totalDurColor ?>;"><?= htmlspecialchars($totalDur ?? '—') ?></div>
            </div>
            <div class="tl-sum-item">
                <div class="tl-sum-key">Tracking Status</div>
                <div class="tl-sum-val"><?= htmlspecialchars($tl['tracking_status'] ?: '—') ?></div>
            </div>
            <?php if ($assignedDt): ?>
            <div class="tl-sum-item">
                <div class="tl-sum-key">Assigned At (PH)</div>
                <div class="tl-sum-val" style="font-size:.72rem;"><?= htmlspecialchars($assignedDt) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- ── Charts ── -->
<div class="sec-label">Activity &amp; Breakdown</div>
<div class="chart-row cr-2-1" style="margin-bottom:1rem;">
    <div class="chart-card">
        <div><div class="cc-title">Tracking Activity</div><div class="cc-sub">Sales tracking updates per day — last 30 days (PH)</div></div>
        <div style="position:relative;height:200px;"><canvas id="actChart"></canvas></div>
    </div>
    <div class="chart-card">
        <div><div class="cc-title">By Source</div><div class="cc-sub">Project sources in pipeline</div></div>
        <div style="position:relative;height:200px;display:flex;align-items:center;justify-content:center;"><canvas id="srcChart"></canvas></div>
    </div>
</div>
<div class="chart-row cr-1-1" style="margin-bottom:1rem;">
    <div class="chart-card">
        <div><div class="cc-title">Project Status</div><div class="cc-sub">By project status</div></div>
        <div style="position:relative;height:190px;"><canvas id="stChart"></canvas></div>
    </div>
    <div class="chart-card">
        <div><div class="cc-title">Pipeline Stage Counts</div><div class="cc-sub">Projects at each stage</div></div>
        <div style="position:relative;height:190px;"><canvas id="plChart"></canvas></div>
    </div>
</div>

<!-- ── Team ── -->
<?php if (count($team) > 0): ?>
<div class="sec-label">Team Comparison</div>
<div class="team-card">
    <div class="cc-title" style="margin-bottom:.15rem;">SR Leaderboard</div>
    <div class="cc-sub" style="margin-bottom:.875rem;">Ranked by total Won Amount (WA) — active projects only</div>
    <?php
    $maxWa = max(array_column($team,'total_wa') ?: [1]); $maxWa = max($maxWa, 1);
    $rnkCls = ['gold','silver','bronze']; $medals = ['🥇','🥈','🥉'];
    foreach ($team as $i => $t):
        $isMe   = (int)$t['id'] === $userId;
        $rc     = $isMe ? 'me' : ($rnkCls[$i] ?? 'other');
        $barPct = round(100 * (float)$t['total_wa'] / $maxWa);
        $bc     = $isMe ? 'linear-gradient(90deg,#60a5fa,#818cf8)' : 'rgba(255,255,255,.1)';
    ?>
    <div class="team-row">
        <div class="rnk <?= $rc ?>"><?= ($i+1) ?></div>
        <div class="tnm <?= $isMe ? 'me' : '' ?>">
            <?= $i < 3 ? $medals[$i] . ' ' : '' ?><?= htmlspecialchars($t['full_name']) ?>
            <?php if ($isMe): ?><span style="font-size:.6rem;font-weight:700;color:#60a5fa;margin-left:.35rem;padding:.08rem .35rem;background:rgba(96,165,250,.12);border-radius:4px;">YOU</span><?php endif; ?>
        </div>
        <div style="font-size:.68rem;color:var(--text-secondary);flex-shrink:0;width:3rem;text-align:center;"><?= numFmt($t['assigned']) ?>p</div>
        <div class="tbar-wrap"><div class="tbar-track"><div class="tbar-fill" style="width:<?= $barPct ?>%;background:<?= $bc ?>;"></div></div></div>
        <div class="twa"><?= (float)$t['total_wa'] > 0 ? valFmt($t['total_wa']) : '—' ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Follow-ups ── -->
<?php if (!empty($followups)): ?>
<div class="sec-label">Upcoming Follow-ups</div>
<div class="tbl-card">
    <table class="ptbl">
        <thead><tr><th>Project</th><th>Follow-up Date (PH)</th><th>Status</th><th>Pipeline</th><th>Prob.</th><th>Value</th></tr></thead>
        <tbody>
        <?php foreach ($followups as $f):
            $du = (int)$f['days_until'];
            $uc = $du === 0 ? 'urg-today' : ($du <= 3 ? 'urg-soon' : 'urg-ok');
            $ul = $du === 0 ? 'Today!' : ($du === 1 ? 'Tomorrow' : 'In ' . $du . ' days');
            $tc = $f['tracking_status'] === 'Complete' ? 'chip-cp' : 'chip-ns';
        ?>
        <tr>
            <td><span class="pn"><?= htmlspecialchars($f['project_name'] ?: '—') ?></span><span class="ps"><?= htmlspecialchars($f['contractor_name'] ?: '—') ?></span></td>
            <td><div style="font-size:.77rem;font-weight:600;"><?= phDt($f['next_followup_date']) ?></div><div class="<?= $uc ?>" style="font-size:.68rem;"><?= $ul ?></div></td>
            <td><span class="chip <?= $tc ?>"><?= htmlspecialchars($f['tracking_status'] ?: '—') ?></span></td>
            <td><div class="sdots">
                <div class="sd <?= $f['contacted']==='Yes'?'sd-y':($f['contacted']==='No'?'sd-n':'sd-p') ?>" title="Contacted"></div>
                <div class="sd <?= $f['quoted']   ==='Yes'?'sd-y':($f['quoted']   ==='No'?'sd-n':'sd-p') ?>" title="Quoted"></div>
                <div class="sd <?= $f['to_win']   ==='Yes'?'sd-y':($f['to_win']   ==='No'?'sd-n':'sd-p') ?>" title="To Win"></div>
            </div></td>
            <td style="color:#fbbf24;font-weight:600;font-size:.75rem;"><?= round($f['probability_percentage']) ?>%</td>
            <td style="font-weight:700;color:#34d399;white-space:nowrap;font-size:.75rem;"><?= $f['project_value']>0?valFmt($f['project_value']):'—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

</div><!-- /perf-wrap -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>

<script>
const BASE = '<?= $base ?>';
window.SR_DATA = {
    activity30: <?= json_encode($activity30, JSON_UNESCAPED_UNICODE) ?>,
    bySource:   <?= json_encode($bySource, JSON_UNESCAPED_UNICODE) ?>,
    byStatus:   <?= json_encode($byStatus, JSON_UNESCAPED_UNICODE) ?>,
    pipeline:   [<?= $total ?>,<?= $contacted ?>,<?= $quoted ?>,<?= $qualified ?>,<?= $toWin ?>,<?= $completed ?>]
};
</script>
<script src="<?= $base ?>/static/js/sr-my-performance.js?v=1"></script>
</body>
</html>
