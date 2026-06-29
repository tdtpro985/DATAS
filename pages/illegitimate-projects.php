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
    <title>Illegitimate Projects | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
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
    <link rel="stylesheet" href="<?= $base ?>/static/css/modal-system.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/illegitimate-modal-fix.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/illegitimate-projects.css?v=1">
</head>
<body data-role="<?= htmlspecialchars($role) ?>" data-page="illegitimate-projects">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="dashboard">
    <div id="admin-content-wrapper" style="grid-column: 1 / -1;">
        <div class="card animate-fadeInUp">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 0.75rem;">
                        <span>🚫</span>Illegitimate Projects
                    </h2>
                    <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: 0.9rem;">Projects marked as not legitimate</p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div class="pm-stat-card">
                    <div class="pm-stat-icon">🚫</div>
                    <div class="pm-stat-content">
                        <div class="pm-stat-label">Total Illegitimate</div>
                        <div class="pm-stat-value" id="totalIllegitimate">0</div>
                    </div>
                </div>
            </div>
            <div class="pm-filters">
                <input type="text" id="search-input" class="pm-search" placeholder="🔍 Search projects...">
                <select id="region-filter" class="pm-filter"><option value="">All Regions</option></select>
                <select id="source-filter" class="pm-filter"><option value="">All Sources</option><option value="PHILGEPS">PHILGEPS</option><option value="DPWH">DPWH</option><option value="BCI">BCI</option></select>
                <select id="sort-filter" class="pm-filter"><option value="publication_date_desc">Newest First</option><option value="publication_date_asc">Oldest First</option></select>
                <button id="refresh-btn" class="btn-refresh">🔄 Refresh</button>
            </div>
            <div class="status-legend">
                <div class="legend-item"><span class="status-circle priority"></span> Priority</div>
                <div class="legend-item"><span class="status-circle awarded"></span> Awarded</div>
                <div class="legend-item"><span class="status-circle for-execution"></span> For Execution</div>
                <div class="legend-item"><span class="status-circle for-bidding"></span> For Bidding</div>
            </div>
            <div class="table-wrapper">
                <table class="data-table" style="table-layout: fixed; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 200px; padding: 0.75rem;">Contractor</th>
                            <th style="width: 250px; padding: 0.75rem;">Project Name</th>
                            <th style="width: 150px; padding: 0.75rem;">Region</th>
                            <th style="width: 100px; padding: 0.75rem;">Source</th>
                            <th style="text-align: center; width: 80px; padding: 0.75rem;">Status</th>
                            <th style="text-align: right; width: 120px; padding: 0.75rem;">Project Value</th>
                            <th style="text-align: center; width: 120px; padding: 0.75rem;">Sales Tracking</th>
                            <th style="text-align: right; width: 110px; padding: 0.75rem;">Published Date</th>
                        </tr>
                    </thead>
                    <tbody id="projects-tbody">
                        <tr><td colspan="8" style="text-align: center; padding: 2rem;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <div id="pagination-info" class="pagination-info"></div>
                <div id="pagination-controls" class="pagination-controls"></div>
            </div>
        </div>
    </div>
</div>
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content modal-large">
        <div class="modal-header"><h2>Project Details</h2><button class="modal-close" onclick="closeDetailsModal()">&times;</button></div>
        <div class="modal-body" id="detailsModalBody"></div>
        <div class="modal-actions"></div>
    </div>
</div>
<script src="<?= $base ?>/static/js/modal-system.js?v=1"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/illegitimate-projects.js?v=2"></script><script>
const BASE = '<?= $base ?>';
</script>
</body>
</html>
