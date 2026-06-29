<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role     = $_SESSION['user']['role']      ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

if (!in_array($role, ['superadmin', 'admin'], true)) {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SR Performance Report — TDT Powersteel</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/header.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">

    <link rel="stylesheet" href="<?= $base ?>/static/css/sr-performance.css?v=1">
</head>

<body data-role="<?= $role ?>">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">
<div class="card animate-fadeInUp" style="grid-column: 1 / -1;">

    <!-- Page header -->
    <div style="margin-bottom: var(--sp-5);">
        <h1 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
            📊 SR Performance Report
        </h1>
        <p style="margin: 0.4rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
            Sales representative funnel performance &amp; win metrics
        </p>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card c-blue">
            <span class="kpi-label">Active SRs</span>
            <span class="kpi-value" id="kpiReps">—</span>
            <span class="kpi-sub">with tracked activity</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Total Assigned</span>
            <span class="kpi-value" id="kpiAssigned">—</span>
            <span class="kpi-sub">projects tracked</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Contacted</span>
            <span class="kpi-value" id="kpiContacted">—</span>
            <span class="kpi-sub">clients reached</span>
        </div>
        <div class="kpi-card c-yellow">
            <span class="kpi-label">SQL Yes</span>
            <span class="kpi-value" id="kpiSql">—</span>
            <span class="kpi-sub">qualified leads</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Quoted</span>
            <span class="kpi-value" id="kpiQuoted">—</span>
            <span class="kpi-sub">proposals sent</span>
        </div>
        <div class="kpi-card c-green">
            <span class="kpi-label">Total Wins</span>
            <span class="kpi-value" id="kpiWins">—</span>
            <span class="kpi-sub">closed deals</span>
        </div>
        <div class="kpi-card c-purple">
            <span class="kpi-label">Win Amount</span>
            <span class="kpi-value" id="kpiWinAmount">—</span>
            <span class="kpi-sub">total won value</span>
        </div>
        <div class="kpi-card c-orange">
            <span class="kpi-label">Pipeline Value</span>
            <span class="kpi-value" id="kpiPipeline">—</span>
            <span class="kpi-sub">total project value</span>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <!-- Search first -->
        <div class="tb-group tb-search">
            <label for="searchInput">Search</label>
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" placeholder="Name, branch, email…">
            </div>
        </div>

        <div class="tb-group">
            <label for="filterDateFrom">Date From</label>
            <input type="date" id="filterDateFrom">
        </div>

        <div class="tb-group">
            <label for="filterDateTo">Date To</label>
            <input type="date" id="filterDateTo">
        </div>

        <div class="tb-group">
            <label for="filterBranch">Branch</label>
            <select id="filterBranch">
                <option value="">All Branches</option>
            </select>
        </div>
    </div>

    <!-- Tracking legend -->
    <div class="track-legend">
        <span style="font-size:0.7rem; color:var(--text-secondary); font-weight:700;">Tracking Status:</span>
        <span class="legend-item"><span class="legend-dot" style="background:#adb5bd;"></span>Not Started</span>
        <span class="legend-item"><span class="legend-dot" style="background:#60a5fa;"></span>In Progress</span>
        <span class="legend-item"><span class="legend-dot" style="background:#34d399;"></span>Complete</span>
        <span style="margin-left:auto; font-size:0.7rem; color:var(--text-muted);">Click a row to view full details</span>
    </div>

    <!-- Migration notice -->
    <div id="timingNotice" style="
        display:none; align-items:center; gap:0.75rem;
        background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25);
        border-radius:var(--radius-md); padding:0.75rem 1rem; margin-bottom:var(--sp-4);
        font-size:0.8rem; color:#fcd34d;
    ">
        <span>⏱</span>
        <span>Speed ranking requires the timing migration. Run <strong>migrate-tracking-timestamps.php</strong> on the server once to enable it. Until then, ranking is by wins.</span>
    </div>

    <!-- Table -->
    <div class="sr-table-wrap">
        <table class="sr-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th data-sort="full_name">Sales Rep</th>
                    <th data-sort="total_assigned" class="num-cell">Assigned</th>
                    <th>Funnel Breakdown</th>
                    <th data-sort="avg_days_full_cycle" class="num-cell col-timing sort-asc" title="Avg full cycle (lower = faster = better rank)">⚡ Full Cycle</th>
                    <th data-sort="win_rate" class="num-cell">Win Rate</th>
                </tr>
            </thead>
            <tbody id="srTableBody">
                <tr id="loadingRow">
                    <td colspan="6"><span class="spinner"></span> Loading performance data…</td>
                </tr>
            </tbody>
        </table>
        <div id="noData">
            <div class="nd-icon">📭</div>
            <div class="nd-title">No performance data found</div>
            <div class="nd-sub">No sales tracking records match the current filters.</div>
        </div>
    </div>

</div>
</div>

</div><!-- .ap-main -->
</div><!-- .ap-shell -->

<!-- Detail Modal — outside ap-shell to avoid stacking context issues -->
<div class="detail-modal-overlay" id="detailModal">
    <div class="detail-modal" id="detailModalInner">
        <button class="modal-close-btn" id="closeDetailModal" title="Close">✕</button>

        <div class="modal-sr-header">
            <div class="modal-avatar" id="mAvatar">?</div>
            <div>
                <div class="modal-sr-name"  id="mName">—</div>
                <div class="modal-sr-email" id="mEmail">—</div>
                <span class="modal-sr-branch" id="mBranch" style="display:none;"></span>
            </div>
        </div>

        <!-- Overview stats -->
        <div class="modal-section">
            <div class="modal-section-title">Overview</div>
            <div class="modal-stat-grid">
                <div class="modal-stat">
                    <div class="modal-stat-label">Assigned</div>
                    <div class="modal-stat-val c-blue" id="mAssigned">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Win Rate</div>
                    <div class="modal-stat-val c-green" id="mWinRate">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Win Amount</div>
                    <div class="modal-stat-val c-purple" id="mWinAmt">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Pipeline Value</div>
                    <div class="modal-stat-val c-orange" id="mPipeline">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">SQL Yes</div>
                    <div class="modal-stat-val c-yellow" id="mSql">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">SQL No</div>
                    <div class="modal-stat-val" id="mSqlNo">—</div>
                </div>
            </div>
        </div>

        <!-- Funnel -->
        <div class="modal-section">
            <div class="modal-section-title">Sales Funnel</div>
            <div class="modal-funnel" id="mFunnel"></div>
        </div>

        <!-- Tracking status -->
        <div class="modal-section">
            <div class="modal-section-title">Tracking Status Breakdown</div>
            <div class="modal-tracking-row">
                <div class="modal-track-badge modal-track-ns">
                    <span class="mtn" id="mNS">—</span>Not Started
                </div>
                <div class="modal-track-badge modal-track-ip">
                    <span class="mtn" id="mIP">—</span>In Progress
                </div>
                <div class="modal-track-badge modal-track-co">
                    <span class="mtn" id="mCO">—</span>Complete
                </div>
            </div>
        </div>

        <!-- Speed / Timing -->
        <div class="modal-section" id="mTimingSection" style="display:none;">
            <div class="modal-section-title">⚡ Speed Metrics (avg days per stage)</div>
            <div class="modal-stat-grid" style="grid-template-columns: repeat(2,1fr);">
                <div class="modal-stat" style="grid-column:1/-1; background:rgba(255,128,0,0.07); border-color:rgba(255,128,0,0.2);">
                    <div class="modal-stat-label">Full Cycle (Assign → Win)</div>
                    <div class="modal-stat-val c-orange" id="mFullCycle">—</div>
                    <div style="font-size:0.7rem;color:var(--text-muted);margin-top:0.2rem;">based on <span id="mCycles">0</span> completed cycles</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Assign → Contact</div>
                    <div class="modal-stat-val c-blue" id="mToContact">—</div>
                </div>
                <div class="modal-stat">
                <div class="modal-stat-label">Contact → Quote</div>
                <div class="modal-stat-val c-yellow" id="mToQuote">—</div>
                </div>
                <div class="modal-stat">
                <div class="modal-stat-label">Quote → SQL</div>
                <div class="modal-stat-val c-green" id="mToSql">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">SQL → Win</div>
                    <div class="modal-stat-val c-purple" id="mToWin">—</div>
                </div>
            </div>
        </div>

        <!-- Per-project timestamps -->
        <div class="modal-section" id="mProjectsSection">
            <div class="modal-section-title" style="display:flex;align-items:center;justify-content:space-between;">
                <span>📋 Per-Project Timestamps</span>
                <span id="mProjectsLoading" style="font-size:0.72rem;color:var(--text-muted);">Loading…</span>
            </div>
            <div id="mProjectsTable" style="overflow-x:auto; overflow-y:auto; max-height:300px; margin-top:0.5rem; border-radius:6px;"></div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/static/js/auth.js?v=3"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/sr-performance.js?v=6"></script>
<script>
const BASE = '<?= $base ?>';
</script>
</body>
</html>
