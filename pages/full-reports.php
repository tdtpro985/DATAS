<?php
/* ============================================================
   pages/full-reports.php — Comprehensive Statistical Reports
   ============================================================ */
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

// Only admin, superadmin, and sales_rep can access reports
if (!in_array($role, ['admin', 'superadmin', 'sales_rep'], true)) {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Reports — TDT Powersteel</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=23">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }

        .full-reports-container {
            padding: 2rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        .reports-header {
            margin-bottom: 2rem;
        }

        .reports-header h1 {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
            margin: 0 0 0.5rem 0;
        }

        .reports-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
        }

        .filter-group select,
        .filter-group input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.6rem 1rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            min-width: 180px;
            cursor: pointer;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .btn-export {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-left: auto;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(249, 115, 22, 0.3);
        }

        /* Section */
        .report-section {
            margin-bottom: 2.5rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary);
        }

        .section-header h2 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
        }

        .section-icon {
            font-size: 1.6rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-sublabel {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
        }

        .stat-change.positive {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .stat-change.negative {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        /* Data Table */
        .data-table-wrapper {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: rgba(249, 115, 22, 0.1);
        }

        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--primary);
            border-bottom: 1px solid var(--border-color);
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        /* Chart Container */
        .chart-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            min-height: 400px;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        /* Loading State */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem;
            color: var(--text-secondary);
        }

        .spinner {
            border: 3px solid rgba(249, 115, 22, 0.1);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-right: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .full-reports-container {
                padding: 1rem;
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group select,
            .filter-group input {
                width: 100%;
            }

            .btn-export {
                width: 100%;
                justify-content: center;
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <!-- Modern Select Dropdowns Styling -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="full-reports-container">
    <div class="reports-header">
        <h1>📊 Full Reports</h1>
        <p>Comprehensive statistical analysis and detailed reporting</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label>Date Range</label>
            <select id="dateRange">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month" selected>This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Region</label>
            <select id="regionFilter">
                <option value="">All Regions</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Status</label>
            <select id="statusFilter">
                <option value="">All Statuses</option>
                <option value="Priority">Priority</option>
                <option value="Awarded">Awarded</option>
                <option value="For Execution">For Execution</option>
                <option value="For Bidding">For Bidding</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Source</label>
            <select id="sourceFilter">
                <option value="">All Sources</option>
            </select>
        </div>
        <button class="btn-export" onclick="exportReport()">
            <span>📥</span>
            <span>Export Report</span>
        </button>
    </div>

    <!-- Executive Summary -->
    <div class="report-section">
        <div class="section-header">
            <span class="section-icon">📈</span>
            <h2>Executive Summary</h2>
        </div>
        <div class="stats-grid" id="executiveSummary">
            <div class="loading">
                <div class="spinner"></div>
                <span>Loading data...</span>
            </div>
        </div>
    </div>

    <!-- Project Analytics -->
    <div class="report-section">
        <div class="section-header">
            <span class="section-icon">📊</span>
            <h2>Project Analytics</h2>
        </div>
        <div id="projectAnalytics">
            <div class="loading">
                <div class="spinner"></div>
                <span>Loading data...</span>
            </div>
        </div>
    </div>

    <!-- Contractor Analytics -->
    <div class="report-section">
        <div class="section-header">
            <span class="section-icon">🏢</span>
            <h2>Contractor Analytics</h2>
        </div>
        <div id="contractorAnalytics">
            <div class="loading">
                <div class="spinner"></div>
                <span>Loading data...</span>
            </div>
        </div>
    </div>

    <!-- Sales Performance -->
    <div class="report-section">
        <div class="section-header">
            <span class="section-icon">💼</span>
            <h2>Sales Performance</h2>
        </div>
        <div id="salesPerformance">
            <div class="loading">
                <div class="spinner"></div>
                <span>Loading data...</span>
            </div>
        </div>
    </div>

    <!-- Geographic Analysis -->
    <div class="report-section">
        <div class="section-header">
            <span class="section-icon">🗺️</span>
            <h2>Geographic Distribution</h2>
        </div>
        <div id="geographicAnalysis">
            <div class="loading">
                <div class="spinner"></div>
                <span>Loading data...</span>
            </div>
        </div>
    </div>

    <!-- Material Requirements -->
    <div class="report-section">
        <div class="section-header">
            <span class="section-icon">🔩</span>
            <h2>Material Requirements</h2>
        </div>
        <div id="materialRequirements">
            <div class="loading">
                <div class="spinner"></div>
                <span>Loading data...</span>
            </div>
        </div>
    </div>

    <!-- Encoding Performance -->
    <div class="report-section">
        <div class="section-header">
            <span class="section-icon">⌨️</span>
            <h2>Encoding Performance</h2>
        </div>
        <div id="encodingPerformance">
            <div class="loading">
                <div class="spinner"></div>
                <span>Loading data...</span>
            </div>
        </div>
    </div>
</div>

<script>
const BASE = '<?= $base ?>';
</script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/full-reports.js?v=1"></script>

</body>
</html>
