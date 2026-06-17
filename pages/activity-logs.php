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
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=23">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    
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

        .activity-logs-container {
            padding: 2rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        .logs-header {
            margin-bottom: 2rem;
        }

        .logs-header h1 {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
            margin: 0 0 0.5rem 0;
        }

        .logs-header p {
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
            min-width: 180px;
            cursor: pointer;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(249, 115, 22, 0.3);
        }

        /* Activity Type Tabs */
        .activity-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Logs Table */
        .logs-table-wrapper {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logs-table thead {
            background: rgba(249, 115, 22, 0.1);
        }

        .logs-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--primary);
            border-bottom: 1px solid var(--border-color);
        }

        .logs-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .logs-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .action-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .action-badge.create { background: rgba(34, 197, 94, 0.2); color: #10b981; }
        .action-badge.update { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .action-badge.delete { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .action-badge.login { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .action-badge.logout { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }
        .action-badge.assign { background: rgba(168, 85, 247, 0.2); color: #a855f7; }
        .action-badge.archive { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
        }

        .pagination-info {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-btn {
            padding: 0.4rem 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-page {
            display: flex;
            align-items: center;
            padding: 0 1rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.85rem;
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
    </style>
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

<script>
const BASE = '<?= $base ?>';
</script>
<script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/activity-logs.js?v=1"></script>

</body>
</html>
