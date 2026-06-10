<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

require_once __DIR__ . '/../config.php';

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$userId = $_SESSION['user']['id'] ?? null;
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['username'] ?? 'User');

// Role check
if (!in_array($role, ['superadmin', 'admin', 'sales_rep'])) {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Illegitimate Projects | DATAS</title>
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/header.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/components.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/projects.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modal-system.css">
    <script>
        const BASE = '<?= $base ?>';
    </script>
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php require __DIR__ . '/sidebar.php'; ?>

<div class="ap-content">
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="card">
            <div class="card-header">🚫 Total Illegitimate</div>
            <div class="card-value" id="totalIllegitimate">0</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-panel">
        <div class="search-box">
            <input type="text" id="search-input" placeholder="🔍 Search projects...">
        </div>
        <select id="region-filter">
            <option value="">All Regions</option>
        </select>
        <select id="source-filter">
            <option value="">All Sources</option>
            <option value="PHILGEPS">PHILGEPS</option>
            <option value="DPWH">DPWH</option>
            <option value="BCI">BCI</option>
        </select>
        <select id="sort-filter">
            <option value="publication_date_desc">Newest First</option>
            <option value="publication_date_asc">Oldest First</option>
        </select>
        <button id="refresh-btn" class="btn-primary">🔄 Refresh</button>
    </div>

    <!-- Status Legend -->
    <div class="status-legend">
        <div class="legend-item">
            <span class="status-circle priority"></span> Priority
        </div>
        <div class="legend-item">
            <span class="status-circle awarded"></span> Awarded
        </div>
        <div class="legend-item">
            <span class="status-circle for-execution"></span> For Execution
        </div>
        <div class="legend-item">
            <span class="status-circle for-bidding"></span> For Bidding
        </div>
    </div>

    <!-- Projects Table -->
    <div class="table-container">
        <table class="projects-table">
            <thead>
                <tr>
                    <th>Contractor</th>
                    <th>Project Name</th>
                    <th>Region</th>
                    <th>Source</th>
                    <th style="text-align: center;">Status</th>
                    <th>Project Value</th>
                    <th>Sales Tracking</th>
                    <th>Published Date</th>
                </tr>
            </thead>
            <tbody id="projects-tbody">
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem;">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <div id="pagination-info"></div>
        <div id="pagination-controls"></div>
    </div>
</div>

<!-- Project Details Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Project Details</h2>
            <button class="modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="detailsModalBody">
            <!-- Content will be dynamically inserted -->
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeDetailsModal()">Close</button>
            <button class="btn-save" onclick="saveSalesTracking()">💾 Save Sales Tracking</button>
        </div>
    </div>
</div>

<script src="<?= $base ?>/static/js/auth.js"></script>
<script src="<?= $base ?>/static/js/role-manager.js"></script>
<script src="<?= $base ?>/static/js/illegitimate-projects.js"></script>
</body>
</html>
