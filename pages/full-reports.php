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
    <!-- SheetJS for Excel export with multiple sheets -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
    
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
            align-items: flex-end;
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
            min-width: 160px;
            cursor: pointer;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .period-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            padding: 0.2rem 0.5rem;
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
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.95) 0%, rgba(42, 42, 42, 0.95) 100%);
            border: 1px solid rgba(255, 128, 0, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(255, 128, 0, 0.25);
            border-color: rgba(255, 128, 0, 0.5);
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #888;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(255, 128, 0, 0.2);
            line-height: 1.2;
        }

        .stat-sublabel {
            font-size: 0.7rem;
            color: #ff8000;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
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

        /* Export Modal - Explicit Rules */
        #exportModal .modal-box {
            position: relative;
            z-index: 1;
        }
        
        /* Scroll to Top Button */
        #scrollToTopBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ff8000, #ffa500);
            border: 2px solid rgba(255, 128, 0, 0.3);
            border-radius: 50%;
            color: #000;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(255, 128, 0, 0.4);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        
        #scrollToTopBtn.show {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }
        
        #scrollToTopBtn:hover {
            background: linear-gradient(135deg, #ffa500, #ffb300);
            box-shadow: 0 6px 24px rgba(255, 128, 0, 0.6);
            transform: translateY(-4px);
        }
        
        #scrollToTopBtn:active {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(255, 128, 0, 0.4);
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

    <!-- Quick Navigation -->
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.5rem;padding:1rem;background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;align-items:center;">
        <span style="font-size:0.75rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-right:0.5rem;">Jump to:</span>
        <button onclick="FullReports.scrollToSection('executiveSummarySec')" style="padding:0.4rem 0.8rem;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2);border-radius:6px;color:var(--primary);font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='rgba(249,115,22,0.2)'" onmouseout="this.style.background='rgba(249,115,22,0.1)'">📈 Executive</button>
        <button onclick="FullReports.scrollToSection('projectAnalyticsSec')" style="padding:0.4rem 0.8rem;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2);border-radius:6px;color:var(--primary);font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='rgba(249,115,22,0.2)'" onmouseout="this.style.background='rgba(249,115,22,0.1)'">📊 Projects</button>
        <button onclick="FullReports.scrollToSection('contractorAnalyticsSec')" style="padding:0.4rem 0.8rem;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2);border-radius:6px;color:var(--primary);font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='rgba(249,115,22,0.2)'" onmouseout="this.style.background='rgba(249,115,22,0.1)'">🏢 Contractors</button>
        <button onclick="FullReports.scrollToSection('salesPerformanceSec')" style="padding:0.4rem 0.8rem;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2);border-radius:6px;color:var(--primary);font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='rgba(249,115,22,0.2)'" onmouseout="this.style.background='rgba(249,115,22,0.1)'">💼 Sales</button>
        <button onclick="FullReports.scrollToSection('srPerformanceSec')" style="padding:0.4rem 0.8rem;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2);border-radius:6px;color:var(--primary);font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='rgba(249,115,22,0.2)'" onmouseout="this.style.background='rgba(249,115,22,0.1)'">📊 SR Performance</button>
        <button onclick="FullReports.scrollToSection('geographicAnalysisSec')" style="padding:0.4rem 0.8rem;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2);border-radius:6px;color:var(--primary);font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='rgba(249,115,22,0.2)'" onmouseout="this.style.background='rgba(249,115,22,0.1)'">🗺️ Geographic</button>
        <button onclick="FullReports.scrollToSection('materialRequirementsSec')" style="padding:0.4rem 0.8rem;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2);border-radius:6px;color:var(--primary);font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='rgba(249,115,22,0.2)'" onmouseout="this.style.background='rgba(249,115,22,0.1)'">🔩 Materials</button>
        <button onclick="FullReports.scrollToSection('encodingPerformanceSec')" style="padding:0.4rem 0.8rem;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2);border-radius:6px;color:var(--primary);font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='rgba(249,115,22,0.2)'" onmouseout="this.style.background='rgba(249,115,22,0.1)'">⌨️ Encoding</button>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label>Period</label>
            <select id="periodSelect">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="semi-monthly">Semi-Monthly</option>
                <option value="monthly" selected>Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <div class="period-label" id="periodRangeLabel">Loading period...</div>
        </div>
        <div class="filter-group" style="min-width:auto;">
            <label>Date Basis</label>
            <div style="display:flex;gap:0;background:rgba(255,255,255,0.05);border:1px solid var(--border-color);border-radius:8px;overflow:hidden;">
                <button id="dateTogglePublished" type="button" style="flex:1;padding:0.6rem 1rem;border:none;background:var(--primary);color:#fff;font-weight:700;font-size:0.8rem;cursor:pointer;transition:background 0.15s;">Published</button>
                <button id="dateToggleEncoded" type="button" style="flex:1;padding:0.6rem 1rem;border:none;background:transparent;color:var(--text-secondary);font-weight:600;font-size:0.8rem;cursor:pointer;transition:background 0.15s,color 0.15s;">Encoded</button>
            </div>
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
        <button class="btn-export" id="btnExportReport" type="button">
            <span>📥</span>
            <span>Export Report</span>
        </button>
    </div>

    <!-- Executive Summary -->
    <div class="report-section" id="executiveSummarySec">
        <div class="section-header" style="cursor:pointer;" onclick="FullReports.scrollToSection('projectAnalyticsSec')" title="Click to go to Project Analytics">
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
    <div class="report-section" id="projectAnalyticsSec">
        <div class="section-header" style="cursor:pointer;" onclick="FullReports.scrollToSection('contractorAnalyticsSec')" title="Click to go to Contractor Analytics">
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
    <div class="report-section" id="contractorAnalyticsSec">
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
    <div class="report-section" id="salesPerformanceSec">
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

    <!-- SR Performance Report -->
    <div class="report-section" id="srPerformanceSec">
        <div class="section-header">
            <span class="section-icon">📊</span>
            <h2>SR Performance Report</h2>
        </div>
        <div id="srPerformanceReport">
            <div class="loading">
                <div class="spinner"></div>
                <span>Loading data...</span>
            </div>
        </div>
    </div>

    <!-- Geographic Analysis -->
    <div class="report-section" id="geographicAnalysisSec">
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
    <div class="report-section" id="materialRequirementsSec">
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
    <div class="report-section" id="encodingPerformanceSec">
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

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/full-reports.js?v=12"></script>

<script>
// Move modals to body to escape stacking context issues
document.addEventListener('DOMContentLoaded', function() {
    ['srDetailModal', 'projDetailModal'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el && el.parentNode !== document.body) {
            document.body.appendChild(el);
        }
    });
});
</script>

<!-- SR Detail Modal -->
<div class="detail-modal-overlay" id="srDetailModal" style="position: fixed; inset: 0; z-index: 999999; background: rgba(0, 0, 0, 0.90); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center;">
    <div class="detail-modal" id="srDetailModalInner" style="position: relative; z-index: 1000000;">
        <button class="modal-close-btn" id="closeSRDetailModal" title="Close">✕</button>

        <div class="modal-sr-header">
            <div class="modal-avatar" id="srModalAvatar">?</div>
            <div>
                <div class="modal-sr-name" id="srModalName">—</div>
                <div class="modal-sr-email" id="srModalEmail">—</div>
                <span class="modal-sr-branch" id="srModalBranch" style="display:none;"></span>
            </div>
        </div>

        <!-- Overview stats -->
        <div class="modal-section">
            <div class="modal-section-title">Overview</div>
            <div class="modal-stat-grid">
                <div class="modal-stat">
                    <div class="modal-stat-label">Assigned</div>
                    <div class="modal-stat-val c-blue" id="srModalAssigned">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Win Rate</div>
                    <div class="modal-stat-val c-green" id="srModalWinRate">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Win Amount</div>
                    <div class="modal-stat-val c-purple" id="srModalWinAmount">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Pipeline Value</div>
                    <div class="modal-stat-val c-orange" id="srModalPipeline">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Contacted</div>
                    <div class="modal-stat-val c-blue" id="srModalContacted">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">SQL Yes</div>
                    <div class="modal-stat-val c-yellow" id="srModalSqlYes">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">SQL No</div>
                    <div class="modal-stat-val" id="srModalSqlNo">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Quoted</div>
                    <div class="modal-stat-val c-green" id="srModalQuoted">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Wins</div>
                    <div class="modal-stat-val c-green" id="srModalWins">—</div>
                </div>
            </div>
        </div>

        <!-- Conversion Rates -->
        <div class="modal-section">
            <div class="modal-section-title">Conversion Rates</div>
            <div class="modal-stat-grid" style="grid-template-columns:repeat(2,1fr);">
                <div class="modal-stat">
                    <div class="modal-stat-label">Contact Rate</div>
                    <div class="modal-stat-val c-blue" id="srModalContactRate">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">SQL Rate</div>
                    <div class="modal-stat-val c-yellow" id="srModalSqlRate">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Quote Rate</div>
                    <div class="modal-stat-val c-green" id="srModalQuoteRate">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Win Rate</div>
                    <div class="modal-stat-val c-green" id="srModalWinRate2">—</div>
                </div>
            </div>
        </div>

        <!-- Tracking status -->
        <div class="modal-section">
            <div class="modal-section-title">Tracking Status Breakdown</div>
            <div class="modal-tracking-row">
                <div class="modal-track-badge modal-track-ns">
                    <span class="mtn" id="srModalNotStarted">—</span>Not Started
                </div>
                <div class="modal-track-badge modal-track-ip">
                    <span class="mtn" id="srModalInProgress">—</span>In Progress
                </div>
                <div class="modal-track-badge modal-track-co">
                    <span class="mtn" id="srModalComplete">—</span>Complete
                </div>
            </div>
        </div>

        <!-- Speed / Timing -->
        <div class="modal-section" id="srModalTimingSection" style="display:none;">
            <div class="modal-section-title">⚡ Speed Metrics (avg days per stage)</div>
            <div class="modal-stat-grid" style="grid-template-columns:repeat(2,1fr);">
                <div class="modal-stat" style="grid-column:1/-1; background:rgba(255,128,0,0.07); border-color:rgba(255,128,0,0.2);">
                    <div class="modal-stat-label">Full Cycle (Assign → Win)</div>
                    <div class="modal-stat-val c-orange" id="srModalFullCycle">—</div>
                    <div style="font-size:0.7rem;color:var(--text-muted);margin-top:0.2rem;">based on <span id="srModalCycles">0</span> completed cycles</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Assign → Contact</div>
                    <div class="modal-stat-val c-blue" id="srModalToContact">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Contact → SQL</div>
                    <div class="modal-stat-val c-yellow" id="srModalToSql">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">SQL → Quote</div>
                    <div class="modal-stat-val c-green" id="srModalToQuote">—</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-label">Quote → Win</div>
                    <div class="modal-stat-val c-purple" id="srModalToWin">—</div>
                </div>
            </div>
        </div>

        <!-- Assigned Projects List -->
        <div class="modal-section">
            <div class="modal-section-title">📋 Assigned Projects</div>
            <div id="srModalProjectsList" style="margin-top:0.6rem;">
                <div style="text-align:center;padding:1.2rem;color:var(--text-secondary);font-size:0.82rem;">Loading projects...</div>
            </div>
        </div>
    </div>
</div>

<!-- Project Detail Sub-Modal (stacked above SR modal) -->
<div id="projDetailModal" style="position:fixed;inset:0;z-index:2000000;background:rgba(0,0,0,0.82);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:16px;width:min(700px,95vw);max-height:88vh;overflow-y:auto;padding:2rem 2rem 1.5rem;position:relative;box-shadow:0 28px 90px rgba(0,0,0,0.75);">
        <button onclick="FullReports.closeProjModal()" title="Close" style="position:absolute;top:1rem;right:1rem;background:rgba(255,255,255,0.08);border:1px solid var(--border-color);color:var(--text-primary);width:2rem;height:2rem;border-radius:50%;cursor:pointer;font-size:0.95rem;display:flex;align-items:center;justify-content:center;line-height:1;">✕</button>
        <div id="projDetailContent">
            <div class="loading"><div class="spinner"></div><span>Loading project...</span></div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" style="visibility:hidden;opacity:0;position:fixed;inset:0;background:rgba(0,0,0,0.90);z-index:999999;display:flex;align-items:center;justify-content:center;transition:opacity 0.3s;backdrop-filter:blur(8px);">
    <div class="modal-box" style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;width:540px;max-width:90vw;max-height:85vh;overflow-y:auto;padding:1.75rem;box-shadow:0 24px 80px rgba(0,0,0,0.6);position:relative;z-index:1000000;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h3 style="margin:0;font-size:1.2rem;font-weight:800;color:var(--text-primary);">📥 Export Report</h3>
            <button onclick="closeExportModal()" style="background:rgba(255,255,255,0.07);border:none;border-radius:50%;width:30px;height:30px;color:var(--text-secondary);font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
        </div>
        <div style="margin-bottom:1.25rem;">
            <label style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-secondary);display:block;margin-bottom:0.5rem;">Period</label>
            <select id="exportPeriodSelect" style="width:100%;background:rgba(255,255,255,0.05);border:1px solid var(--border-color);border-radius:8px;padding:0.6rem 0.9rem;color:var(--text-primary);font-size:0.85rem;">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="semi-monthly">Semi-Monthly</option>
                <option value="monthly" selected>Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>
        <div style="margin-bottom:1.25rem;">
            <label style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-secondary);display:block;margin-bottom:0.5rem;">Date Basis</label>
            <div style="display:flex;gap:0;background:rgba(255,255,255,0.05);border:1px solid var(--border-color);border-radius:8px;overflow:hidden;">
                <button id="exportTogglePub" type="button" style="flex:1;padding:0.6rem 1rem;border:none;background:var(--primary);color:#fff;font-weight:700;font-size:0.8rem;cursor:pointer;">Published</button>
                <button id="exportToggleEnc" type="button" style="flex:1;padding:0.6rem 1rem;border:none;background:transparent;color:var(--text-secondary);font-weight:600;font-size:0.8rem;cursor:pointer;">Encoded</button>
            </div>
        </div>
        <div id="exportPeriodRange" style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:1rem;padding:0.5rem 0.75rem;background:rgba(255,255,255,0.03);border-radius:6px;">
            Period: <span id="exportPeriodRangeText" style="color:var(--text-primary);font-weight:600;">Loading...</span>
        </div>
        <div id="exportDateError" style="display:none;font-size:0.75rem;color:#f87171;margin-bottom:0.75rem;padding:0.4rem 0.6rem;background:rgba(239,68,68,0.1);border-radius:6px;"></div>
        <div style="margin-bottom:1.5rem;">
            <label style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-secondary);display:block;margin-bottom:0.6rem;">Sections to Export</label>
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:rgba(255,255,255,0.03);border-radius:8px;cursor:pointer;font-size:0.9rem;color:var(--text-primary);border:1px solid rgba(255,255,255,0.08);transition:background 0.15s;">
                    <input type="checkbox" class="export-section" value="executive" checked style="accent-color:var(--primary);width:18px;height:18px;cursor:pointer;"> 📈 Executive Summary
                </label>
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:rgba(255,255,255,0.03);border-radius:8px;cursor:pointer;font-size:0.9rem;color:var(--text-primary);border:1px solid rgba(255,255,255,0.08);transition:background 0.15s;">
                    <input type="checkbox" class="export-section" value="projects" checked style="accent-color:var(--primary);width:18px;height:18px;cursor:pointer;"> 📊 Project Analytics
                </label>
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:rgba(255,255,255,0.03);border-radius:8px;cursor:pointer;font-size:0.9rem;color:var(--text-primary);border:1px solid rgba(255,255,255,0.08);transition:background 0.15s;">
                    <input type="checkbox" class="export-section" value="contractors" checked style="accent-color:var(--primary);width:18px;height:18px;cursor:pointer;"> 🏢 Contractor Analytics
                </label>
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:rgba(255,255,255,0.03);border-radius:8px;cursor:pointer;font-size:0.9rem;color:var(--text-primary);border:1px solid rgba(255,255,255,0.08);transition:background 0.15s;">
                    <input type="checkbox" class="export-section" value="sales" checked style="accent-color:var(--primary);width:18px;height:18px;cursor:pointer;"> 💼 Sales Performance
                </label>
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:rgba(255,255,255,0.03);border-radius:8px;cursor:pointer;font-size:0.9rem;color:var(--text-primary);border:1px solid rgba(255,255,255,0.08);transition:background 0.15s;">
                    <input type="checkbox" class="export-section" value="geographic" checked style="accent-color:var(--primary);width:18px;height:18px;cursor:pointer;"> 🗺️ Geographic
                </label>
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:rgba(255,255,255,0.03);border-radius:8px;cursor:pointer;font-size:0.9rem;color:var(--text-primary);border:1px solid rgba(255,255,255,0.08);transition:background 0.15s;">
                    <input type="checkbox" class="export-section" value="material" checked style="accent-color:var(--primary);width:18px;height:18px;cursor:pointer;"> 🔩 Material Req.
                </label>
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;background:rgba(255,255,255,0.03);border-radius:8px;cursor:pointer;font-size:0.9rem;color:var(--text-primary);border:1px solid rgba(255,255,255,0.08);transition:background 0.15s;">
                    <input type="checkbox" class="export-section" value="encoding" checked style="accent-color:var(--primary);width:18px;height:18px;cursor:pointer;"> ⌨️ Encoding Performance
                </label>
            </div>
        </div>
        <div style="display:flex;gap:0.75rem;">
            <button onclick="closeExportModal()" style="flex:1;padding:0.65rem;border:1px solid var(--border-color);border-radius:8px;background:transparent;color:var(--text-secondary);font-weight:600;font-size:0.85rem;cursor:pointer;">Cancel</button>
            <button onclick="FullReports.exportReport()" style="flex:1;padding:0.65rem;border:none;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:0.85rem;cursor:pointer;">Download Report</button>
        </div>
    </div>
</div>
<script>
function openExportModal() { 
    const modal = document.getElementById('exportModal');
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    document.body.style.overflow = 'hidden';
    
    // Sync export period with filter bar period
    const filterPeriod = document.getElementById('periodSelect').value;
    document.getElementById('exportPeriodSelect').value = filterPeriod;
    
    // Sync the date mode toggle with current filter mode
    if (typeof FullReports !== 'undefined' && FullReports._syncExportToggle) {
        FullReports._syncExportToggle();
    }
    
    // Update period range display
    if (typeof FullReports !== 'undefined') {
        FullReports.updateExportPeriodRange();
    }
    
    // Clear any previous error
    const errEl = document.getElementById('exportDateError');
    if (errEl) errEl.style.display = 'none';
}
function closeExportModal() { 
    const modal = document.getElementById('exportModal');
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
    document.body.style.overflow = ''; 
}

// Scroll to Top Button Functionality
window.addEventListener('scroll', function() {
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');
    if (window.pageYOffset > 300) {
        scrollToTopBtn.classList.add('show');
    } else {
        scrollToTopBtn.classList.remove('show');
    }
});

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
</script>

<!-- Scroll to Top Button -->
<button id="scrollToTopBtn" onclick="scrollToTop()" title="Scroll to top">↑</button>

</body>
</html>