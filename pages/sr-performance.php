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
        /* ── KPI Grid ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--sp-4);
            margin-bottom: var(--sp-6);
        }
        @media (max-width: 1100px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px)  { .kpi-grid { grid-template-columns: 1fr 1fr; } }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: var(--radius-lg);
            padding: 1.1rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
        }
        .kpi-card:hover {
            box-shadow: 0 0 0 1px rgba(255,128,0,0.25), var(--shadow-md);
            transform: translateY(-2px);
        }
        .kpi-label {
            font-size: 0.68rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }
        .kpi-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.1;
        }
        .kpi-sub {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 0.1rem;
        }
        .kpi-card.c-orange .kpi-value { color: var(--orange-500); }
        .kpi-card.c-green  .kpi-value { color: #10B981; }
        .kpi-card.c-purple .kpi-value { color: #8B5CF6; }
        .kpi-card.c-blue   .kpi-value { color: #3B82F6; }
        .kpi-card.c-yellow .kpi-value { color: #F59E0B; }

        /* ── Toolbar ── */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-end;
            margin-bottom: var(--sp-5);
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
        }

        .tb-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            flex: 1;
            min-width: 140px;
        }
        .tb-group.tb-search { flex: 2; min-width: 200px; }
        .tb-group label {
            font-size: 0.68rem;
            color: var(--text-secondary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }
        .tb-group input,
        .tb-group select {
            background: var(--bg-input);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            padding: 0.55rem 0.9rem;
            font-size: 0.85rem;
            width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: var(--font);
        }
        .tb-group input:focus,
        .tb-group select:focus {
            outline: none;
            border-color: var(--orange-500);
            box-shadow: 0 0 0 2px rgba(255,128,0,0.12);
        }
        .tb-group input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.6);
            cursor: pointer;
        }
        .tb-group select option { background: var(--bg-card); }

        /* search icon wrapper */
        .search-wrap {
            position: relative;
        }
        .search-wrap input {
            padding-left: 2.2rem;
        }
        .search-icon {
            position: absolute;
            left: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            font-size: 0.85rem;
        }

        /* ── Table ── */
        .sr-table-wrap { overflow-x: auto; border-radius: var(--radius-lg); }

        .sr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .sr-table thead th {
            background: rgba(255,255,255,0.035);
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.8rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            white-space: nowrap;
            user-select: none;
        }
        .sr-table thead th[data-sort] { cursor: pointer; }
        .sr-table thead th[data-sort]:hover { color: var(--orange-400); }
        .sr-table thead th.sort-desc::after { content: ' ↓'; color: var(--orange-500); }
        .sr-table thead th.sort-asc::after  { content: ' ↑'; color: var(--orange-500); }

        .sr-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.045);
            cursor: pointer;
            transition: background 0.12s;
        }
        .sr-table tbody tr:hover { background: rgba(255,128,0,0.05); }
        .sr-table td { padding: 0.8rem 1rem; vertical-align: middle; }

        /* rank */
        .rank-badge {
            display: inline-block;
            padding: 0.18rem 0.55rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .rank-1 { background: rgba(255,193,7,0.15);  color: #FFD700; }
        .rank-2 { background: rgba(192,192,192,0.15); color: #C0C0C0; }
        .rank-3 { background: rgba(205,127,50,0.15);  color: #CD7F32; }
        .rank-n { background: rgba(255,255,255,0.06); color: var(--text-secondary); }

        /* sr name */
        .sr-name-cell { display: flex; align-items: center; gap: 0.7rem; }
        .sr-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.95rem; color: #000;
            flex-shrink: 0;
        }
        .sr-name  { font-weight: 700; color: var(--text-primary); line-height: 1.2; font-size: 0.875rem; }
        .sr-email { font-size: 0.72rem; color: var(--text-muted); }
        .sr-branch {
            display: inline-block; margin-top: 0.2rem;
            font-size: 0.68rem;
            background: rgba(255,128,0,0.1); color: var(--orange-400);
            padding: 0.08rem 0.45rem; border-radius: 999px; font-weight: 700;
        }

        /* funnel mini */
        .funnel-mini { display: flex; flex-direction: column; gap: 0.3rem; min-width: 190px; }
        .funnel-row  { display: flex; align-items: center; gap: 0.4rem; }
        .funnel-label { font-size: 0.65rem; color: var(--text-secondary); width: 55px; flex-shrink: 0; font-weight: 600; }
        .funnel-bar-wrap { flex: 1; height: 5px; background: rgba(255,255,255,0.07); border-radius: 3px; overflow: hidden; }
        .funnel-bar  { height: 100%; border-radius: 3px; transition: width 0.4s ease; }
        .funnel-num  { font-size: 0.68rem; font-weight: 700; color: var(--text-primary); width: 20px; text-align: right; flex-shrink: 0; }

        /* misc cells */
        .num-cell   { text-align: right; white-space: nowrap; }
        .money-cell { font-weight: 700; }

        /* tracking badges */
        .tracking-badges { display: flex; gap: 0.3rem; }
        .track-badge { padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; }
        .track-ns { background: rgba(108,117,125,0.2); color: #adb5bd; }
        .track-ip { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .track-co { background: rgba(16,185,129,0.15); color: #34d399; }

        /* win rate badge */
        .badge { display: inline-block; padding: 0.18rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
        .badge-success { background: rgba(16,185,129,0.15); color: #34d399; }
        .badge-warning { background: rgba(245,158,11,0.15); color: #fcd34d; }
        .badge-danger  { background: rgba(239,68,68,0.15);  color: #f87171; }

        /* no data */
        #noData { display:none; text-align:center; padding:3rem; color:var(--text-secondary); }
        #noData .nd-icon  { font-size:2.5rem; margin-bottom:0.75rem; }
        #noData .nd-title { font-size:1rem; font-weight:700; color:var(--text-primary); }
        #noData .nd-sub   { font-size:0.8rem; margin-top:0.35rem; }

        /* loading */
        #loadingRow td { text-align:center; padding:3rem; color:var(--text-secondary); }
        .spinner {
            display:inline-block; width:22px; height:22px;
            border:3px solid rgba(255,128,0,0.2);
            border-top-color:var(--orange-500);
            border-radius:50%; animation:spin 0.8s linear infinite;
            vertical-align:middle; margin-right:0.5rem;
        }
        @keyframes spin { to { transform:rotate(360deg); } }

        /* legend */
        .track-legend { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; margin-bottom:var(--sp-4); }
        .legend-item  { display:flex; align-items:center; gap:0.3rem; font-size:0.7rem; color:var(--text-secondary); }
        .legend-dot   { width:7px; height:7px; border-radius:50%; }

        /* ── Detail Modal ── */
        .detail-modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.65);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }
        .detail-modal-overlay.active { display: flex; }
        .detail-modal {
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-lg);
            width: 92%;
            max-width: 680px;
            max-height: 88vh;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
            box-shadow: var(--shadow-xl);
            animation: modalIn 0.18s ease;
        }
        @keyframes modalIn { from { opacity:0; transform:translateY(12px) scale(0.98); } to { opacity:1; transform:none; } }

        .modal-close-btn {
            position: absolute; top: 1rem; right: 1rem;
            background: rgba(255,255,255,0.07);
            border: none; border-radius: 50%;
            width: 30px; height: 30px;
            color: var(--text-secondary);
            font-size: 1.1rem; line-height: 1;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.15s, color 0.15s;
        }
        .modal-close-btn:hover { background: rgba(255,255,255,0.12); color: var(--text-primary); }

        .modal-sr-header {
            display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;
        }
        .modal-avatar {
            width: 52px; height: 52px; border-radius: 50%;
            background: var(--gradient-primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.4rem; color: #000; flex-shrink: 0;
        }
        .modal-sr-name  { font-size: 1.2rem; font-weight: 800; color: var(--text-primary); }
        .modal-sr-email { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem; }
        .modal-sr-branch {
            display: inline-block; margin-top: 0.3rem;
            font-size: 0.72rem; background: rgba(255,128,0,0.1);
            color: var(--orange-400); padding: 0.1rem 0.55rem;
            border-radius: 999px; font-weight: 700;
        }

        .modal-section { margin-bottom: 1.5rem; }
        .modal-section-title {
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: var(--text-secondary); margin-bottom: 0.75rem;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .modal-stat-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;
        }
        .modal-stat {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
        }
        .modal-stat-label { font-size: 0.68rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
        .modal-stat-val   { font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin-top: 0.15rem; }
        .modal-stat-val.c-green  { color: #10B981; }
        .modal-stat-val.c-blue   { color: #3B82F6; }
        .modal-stat-val.c-yellow { color: #F59E0B; }
        .modal-stat-val.c-purple { color: #8B5CF6; }
        .modal-stat-val.c-orange { color: var(--orange-500); }

        .modal-funnel { display: flex; flex-direction: column; gap: 0.6rem; }
        .modal-funnel-row { display: flex; align-items: center; gap: 0.75rem; }
        .modal-funnel-label { width: 130px; flex-shrink:0; font-size: 0.8rem; color: var(--text-secondary); font-weight: 600; }
        .modal-funnel-bar-wrap { flex:1; height:8px; background:rgba(255,255,255,0.07); border-radius:4px; overflow:hidden; }
        .modal-funnel-bar { height:100%; border-radius:4px; transition: width 0.5s ease; }
        .modal-funnel-count { width: 30px; text-align:right; font-size:0.8rem; font-weight:700; color:var(--text-primary); flex-shrink:0; }
        .modal-funnel-pct { width: 48px; text-align:right; font-size:0.72rem; color:var(--text-muted); flex-shrink:0; }

        .modal-tracking-row {
            display: flex; gap: 0.6rem;
        }
        .modal-track-badge {
            flex: 1; text-align: center; padding: 0.6rem;
            border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 700;
        }
        .modal-track-badge .mtn { font-size: 1.4rem; font-weight: 800; display: block; }
        .modal-track-ns { background: rgba(108,117,125,0.15); color: #adb5bd; }
        .modal-track-ip { background: rgba(59,130,246,0.12);  color: #60a5fa; }
        .modal-track-co { background: rgba(16,185,129,0.12);  color: #34d399; }
    </style>
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

<!-- Detail Modal -->
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
                    <div class="modal-stat-label">Contact → SQL</div>
                    <div class="modal-stat-val c-green" id="mToSql">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">SQL → Quote</div>
                    <div class="modal-stat-val c-yellow" id="mToQuote">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Quote → Win</div>
                    <div class="modal-stat-val c-purple" id="mToWin">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

</div><!-- .ap-main -->
</div><!-- .ap-shell -->

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/auth.js?v=3"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/sr-performance.js?v=3"></script>
</body>
</html>
