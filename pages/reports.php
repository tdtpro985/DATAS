<?php
/* ============================================================
   pages/reports.php — TDT Powersteel Dashboard (Complete Rewrite)
   ============================================================
   Clean, robust implementation maintaining exact same design
   ============================================================ */

// Error handling and session setup
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

// Authentication check
if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

// Role-based access control
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
    <title>TDT Powersteel — Dashboard</title>

    <!-- External CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    
    <!-- Modern Select Dropdowns Styling -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/custom-select-dropdown.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/reports.css?v=1">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <div class="logo" onclick="window.history.back()" title="Back"></div>
                <div class="title"><span class="brand">TDT POWERSTEEL</span> Dashboard</div>
            </div>
            <div class="header-right">
                <div class="header-controls">
                    <div class="control-group">
                        <div class="control-label">Period</div>
                        <select class="control-select" id="period-select">
                            <option value="overall" selected>Overall (All Time)</option>
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <div class="control-label">Region</div>
                        <select class="control-select" id="region-select">
                            <option value="all" selected>All Regions</option>
                            <option value="NCR">NCR</option>
                            <option value="CAR">CAR</option>
                            <option value="I">Region I - Ilocos</option>
                            <option value="II">Region II - Cagayan Valley</option>
                            <option value="III">Region III - Central Luzon</option>
                            <option value="IV-A">Region IV-A - CALABARZON</option>
                            <option value="IV-B">Region IV-B - MIMAROPA</option>
                            <option value="V">Region V - Bicol</option>
                            <option value="VI">Region VI - Western Visayas</option>
                            <option value="VII">Region VII - Central Visayas</option>
                            <option value="VIII">Region VIII - Eastern Visayas</option>
                            <option value="IX">Region IX - Zamboanga Peninsula</option>
                            <option value="X">Region X - Northern Mindanao</option>
                            <option value="XI">Region XI - Davao</option>
                            <option value="XII">Region XII - SOCCSKSARGEN</option>
                            <option value="XIII">Region XIII - Caraga</option>
                            <option value="BARMM">BARMM</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <div class="control-label">Month</div>
                        <select class="control-select" id="month-select">
                            <option value="" selected>Loading months...</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <div class="control-label">Synced</div>
                        <div class="sync-status">
                            <div class="sync-dot"></div>
                            <span id="sync-time">09:00 AM</span>
                        </div>
                    </div>
                    <div class="control-group">
                        <div class="control-label">Time</div>
                        <div class="time-display" id="current-time">08:00:47 AM</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-content">
            <!-- Main Grid Layout -->
            <div class="main-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- KPI Summary -->
                    <div class="kpi-summary-left">
                        <div class="kpi-card">
                            <div class="kpi-icon">📋</div>
                            <div class="kpi-info">
                                <div class="kpi-value" id="total-projects">0</div>
                                <div class="kpi-label">Total Projects</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon">👷</div>
                            <div class="kpi-info">
                                <div class="kpi-value" id="total-contractors">0</div>
                                <div class="kpi-label">Total Contractors</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon">💰</div>
                            <div class="kpi-info">
                                <div class="kpi-value" id="total-value">₱0</div>
                                <div class="kpi-label">Pipeline Projects</div>
                            </div>
                        </div>
                    </div>

                    <!-- List of Contractors -->
                    <div class="contractors-section">
                        <div class="section-title">📋 List of Contractors</div>
                        <div class="contractors-table" id="contractors-list">
                            <!-- Contractors will be loaded from database -->
                        </div>
                    </div>

                    <!-- Sales Funnel -->
                    <div class="funnel-section">
                        <div class="section-title">🔽 Sales Funnel</div>
                        <div class="funnel-list">
                            <!-- Funnel data will be loaded from database -->
                        </div>
                    </div>
                </div>

                <!-- Center Column -->
                <div class="center-column">
                    <!-- Target Projects -->
                    <div class="target-section">
                        <div class="target-left">
                            <div class="target-label">Encoded</div>
                            <div class="target-number">0</div>
                        </div>
                        <div class="target-center">
                            <div class="target-percentage">0%</div>
                            <div class="target-status">🔺 LOADING...</div>
                            <div class="target-progress-container">
                                <div class="target-progress-bar">
                                    <div class="target-progress-fill"></div>
                                </div>
                            </div>
                        </div>
                        <div class="target-right">
                            <div class="target-label">Target</div>
                            <div class="target-number">600</div>
                        </div>
                    </div>

                    <!-- Live Slideshow -->
                    <div class="live-slideshow">
                        <div class="section-title">🔴 Live Slideshow</div>
                        <div class="slideshow-content">
                            <div class="slideshow-body">
                                <div class="live-contractor-name highlight-main">Loading...</div>
                                <div class="live-details">
                                    <div class="live-detail">
                                        <div class="live-detail-label">Contact:</div>
                                        <div class="live-detail-value highlight-secondary" id="liveContact">Loading...</div>
                                    </div>
                                    <div class="live-detail">
                                        <div class="live-detail-label">Phone:</div>
                                        <div class="live-detail-value highlight-secondary" id="livePhone">Loading...</div>
                                    </div>
                                    <div class="live-detail">
                                        <div class="live-detail-label">Project:</div>
                                        <div class="live-detail-value highlight-main" id="liveProject">Loading...</div>
                                    </div>
                                    <div class="live-detail">
                                        <div class="live-detail-label">Value:</div>
                                        <div class="live-detail-value highlight-main" id="liveProjectValue">₱0</div>
                                    </div>
                                    <div class="live-detail">
                                        <div class="live-detail-label">Status:</div>
                                        <div class="live-detail-value highlight-secondary" id="liveStatus">Loading...</div>
                                    </div>
                                </div>
                                <div class="materials-list" id="liveMaterialsList">
                                    <div>
                                        <div class="material-label">DRBs Type</div>
                                        <div class="material-value">__</div>
                                    </div>
                                    <div>
                                        <div class="material-label">DRBs Amount</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                    <div>
                                        <div class="material-label">Sheet Pile Type</div>
                                        <div class="material-value">—</div>
                                    </div>
                                    <div>
                                        <div class="material-label">Sheet Pile Amount</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                    <div>
                                        <div class="material-label">MS Plate</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                    <div>
                                        <div class="material-label">Angle Bars</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                    <div>
                                        <div class="material-label">Channel Bars</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                    <div>
                                        <div class="material-label">Wide Flange</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                    <div>
                                        <div class="material-label">GI/BI</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                </div>
                                <div class="live-footer" style="display:none;"></div>
                            </div>
                            <div class="slideshow-controls">
                                <!-- Loading Progress Bar -->
                                <div class="slideshow-loading-bar" id="slideshowLoadingBar" style="display: none;">
                                    <div class="loading-progress" id="loadingProgress"></div>
                                </div>
                                <!-- Slideshow Countdown Timer -->
                                <div class="slideshow-countdown-bar" id="slideshowCountdownBar">
                                    <div class="countdown-progress" id="countdownProgress"></div>
                                </div>
                                <div class="slideshow-timer-text" id="slideshowTimerText">Next slide in 10s</div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Status -->
                    <div class="project-status-section">
                        <div class="section-title">📈 Project Status</div>
                        <div class="category-item">
                            <div class="category-name">PRIORITY</div>
                            <div class="category-stats">
                                <div class="category-count">0</div>
                                <div class="category-value">₱0</div>
                                <div class="category-percentage">0.0%</div>
                                <div class="category-bar">
                                    <div class="category-bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="category-item">
                            <div class="category-name">FOR EXECUTION</div>
                            <div class="category-stats">
                                <div class="category-count">0</div>
                                <div class="category-value">₱0</div>
                                <div class="category-percentage">0.0%</div>
                                <div class="category-bar">
                                    <div class="category-bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="category-item">
                            <div class="category-name">AWARDED</div>
                            <div class="category-stats">
                                <div class="category-count">0</div>
                                <div class="category-value">₱0</div>
                                <div class="category-percentage">0.0%</div>
                                <div class="category-bar">
                                    <div class="category-bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="category-item">
                            <div class="category-name">FOR BIDDING</div>
                            <div class="category-stats">
                                <div class="category-count">0</div>
                                <div class="category-value">₱0</div>
                                <div class="category-percentage">0.0%</div>
                                <div class="category-bar">
                                    <div class="category-bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <!-- Combined Regional Card with Toggle -->
                    <div class="regional-combined-section">
                        <div class="section-header-with-toggle">
                            <div class="section-title">📊 Regional Analytics</div>
                            <div class="chart-toggle-buttons">
                                <button class="toggle-btn active" data-chart="values">Values</button>
                                <button class="toggle-btn" data-chart="projects">Projects</button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="regional-values-chart"></canvas>
                            <canvas id="regional-distribution-chart" style="display: none;"></canvas>
                        </div>
                    </div>

                    <!-- Sources Pie Chart -->
                    <div class="sources-chart-section">
                        <div class="section-title">📍 Project Sources</div>
                        <div class="chart-container">
                            <canvas id="sources-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modals - REMOVED -->
            <div class="export-modal-header">
                <h3>📊 Select Reports to Export</h3>
                <button class="export-modal-close" onclick="ExportModal.closeReportSelection()">×</button>
            </div>
            
            <div class="export-modal-content">
                <div class="export-select-all">
                    <label class="export-checkbox-container">
                        <input type="checkbox" id="selectAllReports" onchange="ExportModal.toggleSelectAll()">
                        <span class="export-checkmark"></span>
                        <span class="export-label">Select All Reports</span>
                    </label>
                </div>
                
                <div class="export-reports-list">
                    <label class="export-checkbox-container">
                        <div>
                            <input type="checkbox" name="exportReport" value="users" id="exportUsers">
                            <span class="export-checkmark"></span>
                            <div>
                                <div class="export-label">👥 Users</div>
                                <div class="export-description">All system users and their details</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="export-checkbox-container">
                        <div>
                            <input type="checkbox" name="exportReport" value="sales_reps" id="exportSalesReps">
                            <span class="export-checkmark"></span>
                            <div>
                                <div class="export-label">💼 Sales Representatives</div>
                                <div class="export-description">Sales team members and performance data</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="export-checkbox-container">
                        <div>
                            <input type="checkbox" name="exportReport" value="non_priority_projects" id="exportNonPriorityProjects">
                            <span class="export-checkmark"></span>
                            <div>
                                <div class="export-label">📋 Non-Priority Projects</div>
                                <div class="export-description">Regular projects and their status</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="export-checkbox-container">
                        <div>
                            <input type="checkbox" name="exportReport" value="priority_projects" id="exportPriorityProjects">
                            <span class="export-checkmark"></span>
                            <div>
                                <div class="export-label">🚨 Priority Projects</div>
                                <div class="export-description">High-priority projects requiring immediate attention</div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            
            <div class="export-modal-footer">
                <button class="export-btn-cancel" onclick="ExportModal.closeReportSelection()">Cancel</button>
                <button class="export-btn-next" onclick="ExportModal.showFormatSelection()">Next →</button>
            </div>
        </div>
    </div>

    <!-- Second Modal: Export Format Selection -->
    <div class="export-modal-overlay" id="exportFormatModal" style="display: none;">
        <div class="export-modal">
            <div class="export-modal-header">
                <h3>📄 Select Export Format</h3>
                <button class="export-modal-close" onclick="ExportModal.closeFormatSelection()">×</button>
            </div>
            
            <div class="export-modal-content">
                <div class="export-selected-reports">
                    <h4>Selected Reports:</h4>
                    <div id="selectedReportsDisplay" class="selected-reports-list">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
                
                <div class="export-format-options">
                    <div class="export-format-option" onclick="ExportModal.selectFormat('pdf')">
                        <div class="format-icon">📄</div>
                        <div class="format-details">
                            <div class="format-name">Export as PDF</div>
                            <div class="format-description">Professional document format, ideal for printing and sharing</div>
                        </div>
                        <div class="format-radio">
                            <input type="radio" name="exportFormat" value="pdf" id="formatPdf">
                        </div>
                    </div>
                    
                    <div class="export-format-option" onclick="ExportModal.selectFormat('excel')">
                        <div class="format-icon">📊</div>
                        <div class="format-details">
                            <div class="format-name">Export as Excel</div>
                            <div class="format-description">Spreadsheet format, perfect for data analysis and calculations</div>
                        </div>
                        <div class="format-radio">
                            <input type="radio" name="exportFormat" value="excel" id="formatExcel">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="export-modal-footer">
                <button class="export-btn-back" onclick="ExportModal.showReportSelection()">← Back</button>
                <button class="export-btn-export" onclick="ExportModal.startExport()" disabled>Export Reports</button>
            </div>
        </div>
    </div>

    <!-- Third Modal: Export Status/Completion -->
    <div class="export-modal-overlay" id="exportStatusModal" style="display: none;">
        <div class="export-modal">
            <div class="export-modal-header">
                <h3 id="exportStatusTitle">📦 Preparing Export...</h3>
                <button class="export-modal-close" onclick="ExportModal.closeStatusModal()" id="exportStatusCloseBtn" style="display: none;">×</button>
            </div>
            
            <div class="export-modal-content">
                <div class="export-status-content">
                    <!-- Loading State -->
                    <div id="exportLoadingState" class="export-loading-state">
                        <div class="export-spinner"></div>
                        <div class="export-loading-text">
                            <div class="loading-message">Generating your export files...</div>
                            <div class="loading-details" id="loadingDetails">Preparing data...</div>
                        </div>
                        <div class="export-progress-bar">
                            <div class="export-progress-fill" id="exportProgress"></div>
                        </div>
                    </div>
                    
                    <!-- Success State -->
                    <div id="exportSuccessState" class="export-success-state" style="display: none;">
                        <div class="success-icon">✅</div>
                        <div class="success-message">Export completed successfully!</div>
                        <div class="export-summary">
                            <div class="summary-item">
                                <span class="summary-label">Reports:</span>
                                <span class="summary-value" id="exportedReportsCount">0</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Format:</span>
                                <span class="summary-value" id="exportedFormat">PDF</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">File size:</span>
                                <span class="summary-value" id="exportedFileSize">2.3 MB</span>
                            </div>
                        </div>
                        <div class="download-actions">
                            <button class="download-btn" id="downloadBtn" onclick="ExportModal.triggerDownload()">
                                📥 Download File
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="export-modal-footer" id="exportStatusFooter">
                <button class="export-btn-cancel" onclick="ExportModal.cancelExport()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Audio unlock banner -->
    <div id="audio-unlock-banner" style="
        position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
        background: linear-gradient(135deg, #ff8000, #ffa500);
        color: #000; font-weight: 700; font-size: 0.85rem;
        padding: 0.65rem 1.5rem; border-radius: 999px;
        box-shadow: 0 4px 20px rgba(255,128,0,0.5);
        z-index: 99998; cursor: pointer;
        display: flex; align-items: center; gap: 0.5rem;
        transition: opacity 0.3s;
    " onclick="PriorityAlert.unlockAudio()">
        🔊 Click to enable priority alert sound
    </div>

    <!-- Priority Alert Modals -->
    <!-- First Modal: Pictures Only -->
    <div class="priority-alert-overlay" id="priorityPicturesOverlay">
        <div class="priority-pictures-modal">
            <button class="priority-alert-close" onclick="PriorityAlert.stopSoundAndClose()">×</button>
            
            <div class="priority-pictures-header">
                🚨 PRIORITY PROJECT ALERT - IMAGES
            </div>
            
            <div class="priority-pictures-content" id="priorityPicturesContent">
                <div class="priority-alert-no-images" id="priorityNoImagesFirst">
                    📷
                    <div>No images available for this priority project</div>
                </div>
                <div class="priority-image-counter" id="priorityImageCounterFirst" style="display: none;">
                    1 / 1
                </div>
                <div class="priority-slideshow-timer" id="prioritySlideshowTimerFirst" style="display: none;">
                    Next in 5s
                </div>
            </div>
            
            <div class="priority-pictures-footer">
                <div class="priority-click-indicator">
                    📸 Click anywhere to continue to project details
                </div>
            </div>
        </div>
    </div>

    <!-- Second Modal: Project Data Only -->
    <div class="priority-alert-overlay" id="priorityDataOverlay">
        <div class="priority-data-modal-new">
            <button class="priority-alert-close" onclick="PriorityAlert.close()">×</button>
            
            <!-- Header -->
            <div class="priority-header-new">
                <div class="priority-source-new" id="priorityDataSource">DPWH</div>
            </div>
            
            <!-- Main Grid Container -->
            <div class="priority-grid-container">
                <!-- Field 1 -->
                <div class="priority-field-new">
                    <div class="field-label-new">CONTRACTOR</div>
                    <div class="field-value-new" id="priorityContractorGrid">Loading...</div>
                </div>
                
                <!-- Field 2 -->
                <div class="priority-field-new">
                    <div class="field-label-new">CONTACT PERSON</div>
                    <div class="field-value-new" id="priorityContactPersonGrid">N/A</div>
                </div>
                
                <!-- Field 3 -->
                <div class="priority-field-new">
                    <div class="field-label-new">CONTACT NUMBER</div>
                    <div class="field-value-new" id="priorityContactNumberGrid">N/A</div>
                </div>
                
                <!-- Field 4 -->
                <div class="priority-field-new">
                    <div class="field-label-new">ADDRESS</div>
                    <div class="field-value-new" id="priorityAddressGrid">N/A</div>
                </div>
                
                <!-- Field 5 -->
                <div class="priority-field-new">
                    <div class="field-label-new">PROJECT NAME</div>
                    <div class="field-value-new" id="priorityProjectNameGrid">Loading...</div>
                </div>
                
                <!-- Field 6 -->
                <div class="priority-field-new">
                    <div class="field-label-new">LOCATION</div>
                    <div class="field-value-new" id="priorityLocationGrid">N/A</div>
                </div>
                
                <!-- Field 7 -->
                <div class="priority-field-new">
                    <div class="field-label-new">SHEET PILE TYPE</div>
                    <div class="field-value-new" id="prioritySheetPileTypeGrid">N/A</div>
                </div>
                
                <!-- Field 8 -->
                <div class="priority-field-new">
                    <div class="field-label-new">SHEET PILE AMOUNT</div>
                    <div class="field-value-new" id="prioritySheetPileAmountGrid">₱0.00</div>
                </div>
                
                <!-- Field 9 -->
                <div class="priority-field-new">
                    <div class="field-label-new">PROJECT VALUE</div>
                    <div class="field-value-new" id="priorityProjectValueMainGrid">₱0.00</div>
                </div>
                
                <!-- Field 10 -->
                <div class="priority-field-new">
                    <div class="field-label-new">ACCOMPLISHMENT RATE</div>
                    <div class="field-value-new" id="priorityAccomplishmentMainGrid">0.00%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Philippine DateTime Formatter -->
    <script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>
    
    <!-- Custom Select Dropdown -->
    <script src="<?= $base ?>/static/js/custom-select-dropdown.js"></script>


<script>
const BASE = '<?= $base ?>';
</script>
<script src="<?= $base ?>/static/js/reports.js?v=1"></script>
</body>
</html>