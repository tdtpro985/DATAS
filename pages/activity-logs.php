<?php
/* ============================================================
   pages/activity-logs.php — System Activity Logs
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

// Only admin and superadmin can access activity logs
if (!in_array($role, ['admin', 'superadmin'], true)) {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs — TDT Powersteel</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=23">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    
    <link rel="stylesheet" href="<?= $base ?>/static/css/activity-logs.css?v=1">
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="activity-logs-container">
    <div class="logs-header">
        <h1>📋 Activity Logs</h1>
        <p>Monitor all system activities and user actions</p>
    </div>

    <!-- Activity Type Tabs -->
    <div class="activity-tabs">
        <button class="tab-btn active" data-type="">All Activities</button>
        <button class="tab-btn" data-type="PROJECT">Projects</button>
        <button class="tab-btn" data-type="PLATFORM">Platforms</button>
        <button class="tab-btn" data-type="USER">User Management</button>
        <button class="tab-btn" data-type="SALES_TRACKING">Sales Tracking</button>
        <button class="tab-btn" data-type="EXPORT">Exports</button>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label>Start Date</label>
            <input type="date" id="startDate">
        </div>
        <div class="filter-group">
            <label>End Date</label>
            <input type="date" id="endDate">
        </div>
        <div class="filter-group">
            <label>User</label>
            <select id="userFilter">
                <option value="">All Users</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Entity Type</label>
            <select id="entityFilter">
                <option value="">All Entities</option>
                <option value="project">Project</option>
                <option value="platform">Platform</option>
                <option value="user">User</option>
            </select>
        </div>
        <button class="btn-filter" onclick="ActivityLogs.applyFilters()">Apply Filters</button>
    </div>

    <!-- Logs Table -->
    <div class="logs-table-wrapper">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody id="logsTableBody">
                <tr>
                    <td colspan="6" style="text-align:center;">
                        <div class="loading">
                            <div class="spinner"></div>
                            <span>Loading activity logs...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="pagination" id="paginationControls" style="display:none;">
            <div class="pagination-info">
                Showing <span id="recordRange">0-0</span> of <span id="totalRecords">0</span> activities
            </div>
            <div class="pagination-controls">
                <button class="pagination-btn" id="prevBtn" onclick="ActivityLogs.prevPage()">Previous</button>
                <span class="pagination-page">Page <span id="currentPage">1</span> of <span id="totalPages">1</span></span>
                <button class="pagination-btn" id="nextBtn" onclick="ActivityLogs.nextPage()">Next</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/activity-logs.js?v=1"></script>

<script>
const BASE = '<?= $base ?>';
</script>
</body>
</html>
