<?php
/* ============================================================
   pages/sr-performance.php — SR Performance Report
   ============================================================
   Accessible by superadmin and admin.
   ============================================================ */

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

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/header.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">

    <style>
        /* ── Page-specific styles ────────────────────────── */

        /* KPI Summary Row */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: var(--sp-4);
            margin-bottom: var(--sp-5);
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: var(--radius-lg);
            padding: var(--sp-4) var(--sp-5);
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .kpi-card:hover {
            box-shadow: var(--shadow-glow);
            transform: translateY(-2px);
        }

        .kpi-label {
            font-size: var(--text-xs);
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 600;
        }

        .kpi-value {
            font-size: var(--text-xl);
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.1;
        }

        .kpi-card.accent .kpi-value { color: var(--orange-500); }
        .kpi-card.green  .kpi-value { color: #10B981; }
        .kpi-card.purple .kpi-value { color: #8B5CF6; }
        .kpi-card.blue   .kpi-value { color: #3B82F6; }
        .kpi-card.yellow .kpi-value { color: #F59E0B; }

        /* Filters toolbar */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: var(--sp-4);
            align-items: flex-end;
            margin-bottom: var(--sp-5);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .filter-group label {
            font-size: var(--text-xs);
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .filter-group select,
        .filter-group input {
            background: var(--bg-input);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            padding: 0.5rem 0.85rem;
            font-size: var(--text-sm);
            min-width: 130px;
            transition: border-color 0.2s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--orange-500);
        }

        .filter-group select option {
            background: var(--bg-card);
        }

        /* Table tweaks */
        .sr-table-wrap {
            overflow-x: auto;
            border-radius: var(--radius-lg);
        }

        .sr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--text-sm);
        }

        .sr-table thead th {
            background: rgba(255,255,255,0.04);
            color: var(--text-secondary);
            font-weight: 700;
            font-size: var(--text-xs);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            padding: 0.85rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            white-space: nowrap;
            user-select: none;
        }

        .sr-table thead th[data-sort]:hover {
            color: var(--orange-500);
        }

        .sr-table thead th.sort-desc::after { content: ' ↓'; color: var(--orange-500); }
        .sr-table thead th.sort-asc::after  { content: ' ↑'; color: var(--orange-500); }

        .sr-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.15s;
        }

        .sr-table tbody tr:hover { background: rgba(255,255,255,0.03); }

        .sr-table td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
        }

        /* Rank badge */
        .rank-cell { white-space: nowrap; }

        .rank-badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: var(--text-xs);
            font-weight: 700;
            white-space: nowrap;
        }

        .rank-1 { background: rgba(255,193,7,0.15); color: #FFD700; }
        .rank-2 { background: rgba(176,196,222,0.15); color: #C0C0C0; }
        .rank-3 { background: rgba(205,127,50,0.15); color: #CD7F32; }
        .rank-n { background: rgba(255,255,255,0.07); color: var(--text-secondary); }

        /* SR name cell */
        .sr-name-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sr-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            color: #000;
            flex-shrink: 0;
        }

        .sr-name  { font-weight: 700; color: var(--text-primary); line-height: 1.2; }
        .sr-email { font-size: var(--text-xs); color: var(--text-muted); }
        .sr-branch {
            display: inline-block;
            margin-top: 0.2rem;
            font-size: var(--text-xs);
            background: rgba(255,128,0,0.1);
            color: var(--orange-400);
            padding: 0.1rem 0.5rem;
            border-radius: 999px;
            font-weight: 600;
        }

        /* Funnel mini bars */
        .funnel-mini {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-width: 200px;
        }

        .funnel-row {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .funnel-label {
            font-size: 0.68rem;
            color: var(--text-secondary);
            width: 58px;
            flex-shrink: 0;
            font-weight: 600;
        }

        .funnel-bar-wrap {
            flex: 1;
            height: 6px;
            background: rgba(255,255,255,0.07);
            border-radius: 3px;
            overflow: hidden;
        }

        .funnel-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }

        .funnel-num {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-primary);
            width: 22px;
            text-align: right;
            flex-shrink: 0;
        }

        /* Numeric + money cells */
        .num-cell   { text-align: right; white-space: nowrap; }
        .money-cell { font-weight: 700; }
        .time-cell  { font-size: var(--text-xs); color: var(--text-secondary); }

        /* Tracking status badges */
        .tracking-badges {
            display: flex;
            gap: 0.3rem;
            flex-wrap: wrap;
            min-width: 90px;
        }

        .track-badge {
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: default;
        }

        .track-notstarted { background: rgba(108,117,125,0.2); color: #adb5bd; }
        .track-inprogress { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .track-complete   { background: rgba(16,185,129,0.15); color: #34d399; }

        /* Badge colours reused from existing badges.css conventions */
        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: var(--text-xs); font-weight: 700; }
        .badge-success { background: rgba(16,185,129,0.15); color: #34d399; }
        .badge-warning { background: rgba(245,158,11,0.15); color: #fcd34d; }
        .badge-danger  { background: rgba(239,68,68,0.15);  color: #f87171; }

        /* No data message */
        #noData {
            display: none;
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        #noData .nd-icon { font-size: 3rem; margin-bottom: 1rem; }
        #noData .nd-title { font-size: var(--text-lg); font-weight: 700; color: var(--text-primary); }
        #noData .nd-sub   { font-size: var(--text-sm); margin-top: 0.5rem; }

        /* Loading row */
        #loadingRow td {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .spinner {
            display: inline-block;
            width: 24px; height: 24px;
            border: 3px solid rgba(255,128,0,0.2);
            border-top-color: var(--orange-500);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
            margin-right: 0.5rem;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Legend */
        .tracking-legend {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: var(--text-xs);
            color: var(--text-secondary);
        }

        .legend-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .kpi-row { grid-template-columns: repeat(2, 1fr); }
            .funnel-mini { min-width: 150px; }
        }
    </style>
</head>

<body data-role="<?= $role ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">

    <div class="card animate-fadeInUp" style="grid-column: 1 / -1;">

        <!-- Page header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--sp-5); flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                    📊 SR Performance Report
                </h1>
                <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                    Sales representative funnel performance &amp; win metrics
                </p>
            </div>
        </div>

        <!-- KPI Summary -->
        <div class="kpi-row">
            <div class="kpi-card">
                <span class="kpi-label">Active SRs</span>
                <span class="kpi-value" id="kpiReps">—</span>
            </div>
            <div class="kpi-card blue">
                <span class="kpi-label">Total Assigned</span>
                <span class="kpi-value" id="kpiAssigned">—</span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Contacted</span>
                <span class="kpi-value" id="kpiContacted">—</span>
            </div>
            <div class="kpi-card yellow">
                <span class="kpi-label">SQL Yes</span>
                <span class="kpi-value" id="kpiSql">—</span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Quoted</span>
                <span class="kpi-value" id="kpiQuoted">—</span>
            </div>
            <div class="kpi-card green">
                <span class="kpi-label">Total Wins</span>
                <span class="kpi-value" id="kpiWins">—</span>
            </div>
            <div class="kpi-card purple">
                <span class="kpi-label">Win Amount</span>
                <span class="kpi-value" id="kpiWinAmount">—</span>
            </div>
            <div class="kpi-card accent">
                <span class="kpi-label">Pipeline Value</span>
                <span class="kpi-value" id="kpiPipeline">—</span>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <label for="filterMonth">Month</label>
                <select id="filterMonth">
                    <option value="">All Months</option>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filterYear">Year</label>
                <select id="filterYear"><!-- populated by JS --></select>
            </div>

            <div class="filter-group">
                <label for="filterRegion">Region</label>
                <select id="filterRegion">
                    <option value="">All Regions</option>
                    <option value="NCR">NCR</option>
                    <option value="CAR">CAR</option>
                    <option value="I">Region I</option>
                    <option value="II">Region II</option>
                    <option value="III">Region III</option>
                    <option value="IV-A">Region IV-A</option>
                    <option value="IV-B">MIMAROPA</option>
                    <option value="V">Region V</option>
                    <option value="VI">Region VI</option>
                    <option value="VII">Region VII</option>
                    <option value="VIII">Region VIII</option>
                    <option value="IX">Region IX</option>
                    <option value="X">Region X</option>
                    <option value="XI">Region XI</option>
                    <option value="XII">Region XII</option>
                    <option value="XIII">Caraga</option>
                    <option value="BARMM">BARMM</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="searchInput">Search SR</label>
                <input type="text" id="searchInput" placeholder="Name, branch, email…">
            </div>
        </div>

        <!-- Tracking status legend -->
        <div class="tracking-legend" style="margin-bottom: var(--sp-4);">
            <span style="font-size: var(--text-xs); color: var(--text-secondary); font-weight: 600;">Tracking Status:</span>
            <span class="legend-item"><span class="legend-dot" style="background:#adb5bd;"></span> Not Started</span>
            <span class="legend-item"><span class="legend-dot" style="background:#60a5fa;"></span> In Progress</span>
            <span class="legend-item"><span class="legend-dot" style="background:#34d399;"></span> Complete</span>
        </div>

        <!-- Table -->
        <div class="sr-table-wrap">
            <table class="sr-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th data-sort="full_name">Sales Rep</th>
                        <th data-sort="total_assigned" class="num-cell sort-desc">Assigned</th>
                        <th>Funnel Breakdown</th>
                        <th data-sort="win_rate" class="num-cell">Win Rate</th>
                        <th data-sort="total_win_amount" class="num-cell">Win Amount</th>
                        <th data-sort="total_pipeline_value" class="num-cell">Pipeline</th>
                        <th>Tracking Status</th>
                        <th data-sort="last_activity" class="num-cell">Last Activity</th>
                    </tr>
                </thead>
                <tbody id="srTableBody">
                    <tr id="loadingRow">
                        <td colspan="9">
                            <span class="spinner"></span> Loading performance data…
                        </td>
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

</div> <!-- .dashboard -->
</div> <!-- .ap-main -->
</div> <!-- .ap-shell -->

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/auth.js?v=3"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/sr-performance.js?v=1"></script>

</body>
</html>
