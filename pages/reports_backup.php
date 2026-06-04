<?php
/* ============================================================
   pages/reports.php — Full-screen Reports View
   ============================================================
   Displays the dashboard in full-screen mode without scrolling.
   All content fits within the viewport.
   ============================================================ */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Compute base path dynamically for subdirectory deployments
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role     = $_SESSION['user']['role']      ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

// Restrict access: encoders should not access reports
if ($role === 'encoder') {
    header('Location: ' . $base . '/encode');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDT Powersteel — Reports</title>

    <!-- External CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Internal CSS -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/header.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/kpi.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/charts.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/rotating-card.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    
    <!-- Reports-specific full-screen CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
            font-family: 'Inter', sans-serif;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        
        .reports-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        
        .reports-header {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            height: 70px;
        }
        
        .reports-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .reports-logo {
            height: 40px;
        }
        
        .reports-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        
        .reports-title .accent {
            color: #ff6b35;
        }
        
        .reports-header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .reports-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .reports-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .reports-select:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .reports-select:focus {
            outline: none;
            border-color: #ff6b35;
        }
        
        .reports-time {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
        }
        
        .reports-content {
            flex: 1;
            overflow: hidden;
            display: grid;
            grid-template-columns: 300px 1fr 350px;
            grid-template-rows: auto 1fr;
            gap: 1rem;
            padding: 1rem;
            min-height: 0;
        }
        
        .reports-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .reports-card-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        
        /* KPI Cards - Top Row */
        .kpi-summary {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .kpi-card-reports {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card-reports::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
        }
        
        .kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .kpi-icon-reports {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .kpi-trend {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .kpi-trend.up {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .kpi-trend.down {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .kpi-info-reports {
            flex: 1;
        }
        
        .kpi-value-reports {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            display: block;
            line-height: 1;
        }
        
        .kpi-label-reports {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
            display: block;
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .kpi-subtitle {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.25rem;
        }
        
        /* Left Sidebar - Contractors List */
        .contractors-sidebar {
            grid-column: 1;
            grid-row: 2;
            display: flex;
            flex-direction: column;
        }
        
        /* Main Content Area */
        .main-dashboard {
            grid-column: 2;
            grid-row: 2;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .dashboard-top {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
            height: 200px;
        }
        
        .dashboard-bottom {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            min-height: 0;
        }
        
        /* Right Sidebar - Maps and Analytics */
        .analytics-sidebar {
            grid-column: 3;
            grid-row: 2;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        /* Target Progress Styling */
        .target-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .target-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .target-title {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }
        
        .target-percentage-large {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ff6b35;
        }
        
        .target-status {
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .target-status.behind {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .target-status.on-track {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .target-progress-visual {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            height: 12px;
            position: relative;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .target-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
            border-radius: 12px;
            transition: width 0.8s ease;
            position: relative;
        }
        
        .target-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Live Contractor Card */
        .live-contractor-card {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(247, 147, 30, 0.05));
            border: 1px solid rgba(255, 107, 53, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .live-contractor-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 107, 53, 0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .live-contractor-title {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .live-contractor-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ff6b35;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        
        .live-contractor-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .live-contractor-detail {
            text-align: center;
        }
        
        .live-contractor-detail-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .live-contractor-detail-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            margin-top: 0.25rem;
        }
        
        /* Philippines Map */
        .map-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1rem;
            height: 300px;
            display: flex;
            flex-direction: column;
        }
        
        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .map-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .map-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
            position: relative;
        }
        
        .map-placeholder {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            text-align: center;
        }
        
        /* Region Stats */
        .region-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .region-stat {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            padding: 0.75rem;
            text-align: center;
        }
        
        .region-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ff6b35;
        }
        
        .region-stat-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 0.25rem;
        }
        
        /* Category Breakdown */
        .category-breakdown {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .category-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-name {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .category-value {
            font-size: 0.875rem;
            font-weight: 700;
            color: #ff6b35;
        }
        
        .category-percentage {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            margin-left: 0.5rem;
        }
        
        /* Target Progress - Row 2 */
        .target-row {
            grid-column: 1 / -1;
            min-height: 0;
        }
        
        /* Main Content - Row 3 */
        .slideshow-section {
            grid-column: 1 / 2;
            grid-row: 3;
            min-height: 0;
        }
        
        .funnel-section {
            grid-column: 2 / 3;
            grid-row: 3;
            min-height: 0;
        }
        
        /* Slideshow Styles */
        .slideshow-container {
            flex: 1;
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        
        .slide {
            display: none;
            flex: 1;
            flex-direction: column;
            animation: fadeIn 0.5s ease-in-out;
            min-height: 0;
        }
        
        .slide.active {
            display: flex;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .slide-indicators {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 0;
            flex-shrink: 0;
        }
        
        .indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .indicator.active {
            background: #ff6b35;
            width: 24px;
            border-radius: 4px;
        }
        
        .slide-content {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }
        
        .table-wrapper {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }
        
        .table-wrapper::-webkit-scrollbar {
            width: 6px;
        }
        
        .table-wrapper::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            padding: 0.5rem;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .data-table td {
            padding: 0.5rem;
            color: #fff;
            font-size: 0.8rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .funnel-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.5rem;
            min-height: 0;
            overflow-y: auto;
        }
        
        .target-progress {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            height: 32px;
            position: relative;
            overflow: hidden;
        }
        
        .target-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
            border-radius: 999px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 0.875rem;
        }
        
        .target-info {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .back-to-dashboard {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        .back-to-dashboard:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateX(-2px);
        }
    </style>
</head>

<body>
    <div class="reports-container">
        <!-- Header -->
        <div class="reports-header">
            <div class="reports-header-left">
                <img src="<?= $base ?>/static/images/logo_header.png" alt="TDT Powersteel" class="reports-logo">
                <h1 class="reports-title">Sales Intelligence <span class="accent">Reports</span></h1>
            </div>
            <div class="reports-header-right">
                <div class="reports-controls">
                    <select class="reports-select" id="period-select">
                        <option value="daily" selected>Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                    <select class="reports-select" id="region-filter">
                        <option value="all">All Regions</option>
                        <option value="NCR">NCR</option>
                        <option value="Region I">Region I</option>
                        <option value="Region II">Region II</option>
                        <option value="Region III">Region III</option>
                        <option value="Region IV-A">Region IV-A</option>
                        <option value="Region V">Region V</option>
                        <option value="Region VI">Region VI</option>
                        <option value="Region VII">Region VII</option>
                        <option value="Region XI">Region XI</option>
                    </select>
                    <select class="reports-select" id="month-selector">
                        <!-- Populated by JS -->
                    </select>
                </div>
                <div class="reports-time" id="clock"></div>
                <a href="<?= $base ?>/admin" class="back-to-dashboard">
                    ← To Dashboard
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="reports-content">
            <!-- KPI Summary Cards -->
            <div class="kpi-summary">
                <div class="kpi-card-reports">
                    <div class="kpi-header">
                        <div class="kpi-icon-reports">📋</div>
                        <div class="kpi-trend up" id="projects-trend">↗ +12%</div>
                    </div>
                    <div class="kpi-info-reports">
                        <span class="kpi-value-reports" id="kpi-projects-val">230</span>
                        <span class="kpi-label-reports">Total Projects</span>
                        <div class="kpi-subtitle">vs last month</div>
                    </div>
                </div>
                
                <div class="kpi-card-reports">
                    <div class="kpi-header">
                        <div class="kpi-icon-reports">👷</div>
                        <div class="kpi-trend up" id="contractors-trend">↗ +8%</div>
                    </div>
                    <div class="kpi-info-reports">
                        <span class="kpi-value-reports" id="kpi-contractors-val">190</span>
                        <span class="kpi-label-reports">Total Contractors</span>
                        <div class="kpi-subtitle">active this month</div>
                    </div>
                </div>
                
                <div class="kpi-card-reports">
                    <div class="kpi-header">
                        <div class="kpi-icon-reports">💰</div>
                        <div class="kpi-trend up" id="pipeline-trend">↗ +15%</div>
                    </div>
                    <div class="kpi-info-reports">
                        <span class="kpi-value-reports" id="kpi-pipeline-val">₱7.6B</span>
                        <span class="kpi-label-reports">Pipeline Value</span>
                        <div class="kpi-subtitle">total project value</div>
                    </div>
                </div>
                
                <div class="kpi-card-reports">
                    <div class="kpi-header">
                        <div class="kpi-icon-reports">📊</div>
                        <div class="kpi-trend down" id="sync-trend">↘ -2%</div>
                    </div>
                    <div class="kpi-info-reports">
                        <span class="kpi-value-reports" id="kpi-sync-val">98.2%</span>
                        <span class="kpi-label-reports">Sync Status</span>
                        <div class="kpi-subtitle">data accuracy</div>
                    </div>
                </div>
            </div>

            <!-- Left Sidebar: Contractors List -->
            <div class="reports-card contractors-sidebar">
                <h2 class="reports-card-title">📋 List of Contractors</h2>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Contractor</th>
                                <th>Value (₱)</th>
                            </tr>
                        </thead>
                        <tbody id="contractors-list-body">
                            <tr><td colspan="3" style="text-align: center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Slideshow Section -->
            <div class="reports-card slideshow-section">
                <h2 class="reports-card-title">� Data Insights</h2>
                <div class="slideshow-container">
                    <!-- Slide 1: Top Contractors -->
                    <div class="slide active">
                        <div class="slide-content">
                            <h3 style="color: #ff6b35; font-size: 1rem; margin-bottom: 0.75rem; text-align: center;">⛑️ Top Contractors</h3>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Contractor</th>
                                            <th>Value (₱)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ranking-body">
                                        <tr>
                                            <td colspan="3" style="text-align: center;">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2: Recent Projects -->
                    <div class="slide">
                        <div class="slide-content">
                            <h3 style="color: #ff6b35; font-size: 1rem; margin-bottom: 0.75rem; text-align: center;">� Recent Projects</h3>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Project</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent-projects-body">
                                        <tr>
                                            <td colspan="3" style="text-align: center;">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 3: Sales Reps Performance -->
                    <div class="slide">
                        <div class="slide-content">
                            <h3 style="color: #ff6b35; font-size: 1rem; margin-bottom: 0.75rem; text-align: center;">👥 Sales Reps Performance</h3>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Sales Rep</th>
                                            <th>Projects</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sales-reps-body">
                                        <tr>
                                            <td colspan="3" style="text-align: center;">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="slide-indicators">
                    <span class="indicator active" data-slide="0"></span>
                    <span class="indicator" data-slide="1"></span>
                    <span class="indicator" data-slide="2"></span>
                </div>
            </div>

            <!-- Funnel Section -->
            <div class="reports-card funnel-section">
                <h2 class="reports-card-title">🔽 Sales Funnel</h2>
                <div class="funnel-container" id="funnel-container">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- External JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Internal JS -->
    <script>
        const BASE = '<?= $base ?>';
        const API = BASE + '/api/v1';
        
        // Slideshow functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const indicators = document.querySelectorAll('.indicator');
        
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
            currentSlide = index;
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }
        
        // Auto-advance slideshow every 5 seconds
        setInterval(nextSlide, 5000);
        
        // Manual slide navigation
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => showSlide(index));
        });
        
        // Initialize clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-PH', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('clock').textContent = timeStr;
        }
        updateClock();
        setInterval(updateClock, 1000);
        
        // Initialize month selector
        function initMonthSelector() {
            const selector = document.getElementById('month-selector');
            const currentMonth = new Date().getMonth() + 1;
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            
            months.forEach((month, index) => {
                const option = document.createElement('option');
                option.value = index + 1;
                option.textContent = month;
                if (index + 1 === currentMonth) {
                    option.selected = true;
                }
                selector.appendChild(option);
            });
        }
        
        // Load KPI data
        async function loadKPI() {
            try {
                const period = document.getElementById('period-select').value;
                const region = document.getElementById('region-filter').value;
                const month = document.getElementById('month-selector').value;
                
                const response = await fetch(`${API}/kpi?period=${period}&region=${region}&month=${month}`);
                const data = await response.json();
                
                document.getElementById('kpi-projects-val').textContent = data.total_projects || 0;
                document.getElementById('kpi-contractors-val').textContent = data.total_contractors || 0;
                document.getElementById('kpi-pipeline-val').textContent = '₱' + (data.pipeline_value || 0).toLocaleString();
                
                // Update target progress
                const targets = { daily: 30, weekly: 150, monthly: 600 };
                const target = targets[period] || 30;
                const encoded = data.total_projects || 0;
                const percentage = Math.min(100, Math.round((encoded / target) * 100));
                
                document.getElementById('target-progress-bar').style.width = percentage + '%';
                document.getElementById('target-percentage').textContent = percentage + '%';
                document.getElementById('target-encoded').textContent = encoded;
                document.getElementById('target-goal').textContent = target;
                
                // Color coding
                const bar = document.getElementById('target-progress-bar');
                if (percentage >= 100) {
                    bar.style.background = 'linear-gradient(90deg, #10b981, #059669)';
                } else if (percentage >= 70) {
                    bar.style.background = 'linear-gradient(90deg, #ff6b35, #f7931e)';
                } else {
                    bar.style.background = 'linear-gradient(90deg, #ef4444, #dc2626)';
                }
            } catch (error) {
                console.error('Error loading KPI:', error);
            }
        }
        
        // Load contractors ranking
        async function loadRanking() {
            try {
                const period = document.getElementById('period-select').value;
                const region = document.getElementById('region-filter').value;
                const month = document.getElementById('month-selector').value;
                
                const response = await fetch(`${API}/contractors/ranking?period=${period}&region=${region}&month=${month}&page=1&size=20`);
                const data = await response.json();
                
                const tbody = document.getElementById('ranking-body');
                tbody.innerHTML = '';
                
                if (data.items && data.items.length > 0) {
                    data.items.forEach((item, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${item.contractor_name || '—'}</td>
                            <td>₱${(item.total_value || 0).toLocaleString()}</td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No data</td></tr>';
                }
            } catch (error) {
                console.error('Error loading ranking:', error);
            }
        }
        
        // Load recent projects
        async function loadRecentProjects() {
            try {
                const period = document.getElementById('period-select').value;
                const region = document.getElementById('region-filter').value;
                const month = document.getElementById('month-selector').value;
                
                const response = await fetch(`${API}/projects?period=${period}&region=${region}&month=${month}&page=1&size=20`);
                const data = await response.json();
                
                const tbody = document.getElementById('recent-projects-body');
                tbody.innerHTML = '';
                
                if (data.items && data.items.length > 0) {
                    data.items.slice(0, 15).forEach(item => {
                        const row = document.createElement('tr');
                        const date = new Date(item.date_added || item.created_at);
                        const dateStr = date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
                        const statusBadge = item.status === 'processed' ? 
                            '<span style="color: #10b981;">✓ Processed</span>' : 
                            '<span style="color: #fbbf24;">⏳ Pending</span>';
                        
                        row.innerHTML = `
                            <td>${dateStr}</td>
                            <td>${(item.project_name || item.contractor_name || '—').substring(0, 30)}</td>
                            <td>${statusBadge}</td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No data</td></tr>';
                }
            } catch (error) {
                console.error('Error loading recent projects:', error);
                document.getElementById('recent-projects-body').innerHTML = '<tr><td colspan="3" style="text-align: center;">No data</td></tr>';
            }
        }
        
        // Load sales reps performance
        async function loadSalesReps() {
            try {
                const period = document.getElementById('period-select').value;
                const region = document.getElementById('region-filter').value;
                const month = document.getElementById('month-selector').value;
                
                const response = await fetch(`${API}/users/sales-reps-ranking?period=${period}&region=${region}&month=${month}&page=1&size=20`);
                const data = await response.json();
                
                const tbody = document.getElementById('sales-reps-body');
                tbody.innerHTML = '';
                
                if (data.items && data.items.length > 0) {
                    data.items.forEach((item, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${item.full_name || item.username || '—'}</td>
                            <td>${item.projects_count || 0}</td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No data</td></tr>';
                }
            } catch (error) {
                console.error('Error loading sales reps:', error);
                document.getElementById('sales-reps-body').innerHTML = '<tr><td colspan="3" style="text-align: center;">No data</td></tr>';
            }
        }
        
        // Load funnel
        async function loadFunnel() {
            try {
                const period = document.getElementById('period-select').value;
                const region = document.getElementById('region-filter').value;
                const month = document.getElementById('month-selector').value;
                
                const response = await fetch(`${API}/charts/funnel?period=${period}&region=${region}&month=${month}`);
                const data = await response.json();
                
                const container = document.getElementById('funnel-container');
                container.innerHTML = '';
                
                if (data.stages && data.stages.length > 0) {
                    data.stages.forEach(stage => {
                        const segment = document.createElement('div');
                        segment.style.cssText = `
                            background: rgba(255, 107, 53, 0.2);
                            border: 1px solid rgba(255, 107, 53, 0.5);
                            border-radius: 6px;
                            padding: 0.75rem;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        `;
                        segment.innerHTML = `
                            <span style="color: #fff; font-weight: 600; font-size: 0.85rem;">${stage.name}</span>
                            <span style="color: #ff6b35; font-weight: 700; font-size: 0.9rem;">${stage.count}</span>
                        `;
                        container.appendChild(segment);
                    });
                }
            } catch (error) {
                console.error('Error loading funnel:', error);
            }
        }
        
        // Refresh all data
        function refreshData() {
            loadKPI();
            loadRanking();
            loadRecentProjects();
            loadSalesReps();
            loadFunnel();
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initMonthSelector();
            refreshData();
            
            // Event listeners
            document.getElementById('period-select').addEventListener('change', refreshData);
            document.getElementById('region-filter').addEventListener('change', refreshData);
            document.getElementById('month-selector').addEventListener('change', refreshData);
            
            // Auto-refresh every 30 seconds
            setInterval(refreshData, 30000);
        });
    </script>
</body>
</html>
