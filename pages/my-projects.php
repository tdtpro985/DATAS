<?php
/* ============================================================
   pages/my-projects.php — My Projects (Sales Rep View)
   ============================================================
   Shows projects assigned to current sales rep.
   Sales Rep only.
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
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? 'User');
$userId   = $_SESSION['user']['id']        ?? 0;

// Only sales rep can access
if ($role !== 'sales_rep') {
    header('Location: ' . $base . '/');
    exit;
}

$currentView = $_GET['view'] ?? 'non-priority';
$pageTitle = $currentView === 'priority' ? 'Priority Projects' : 'Non-Priority Projects';
$pageIcon  = $currentView === 'priority' ? '⭐' : '📋';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/components.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    
    
    <!-- Modern Select Dropdowns Styling -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">
    
    <!-- Philippine DateTime -->
    <script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>
    <link rel="stylesheet" href="<?= $base ?>/static/css/my-projects.css?v=1">
</head>

<body data-role="<?= $role ?>" data-user-id="<?= (int)$userId ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard" style="display: block; max-width: 100%; padding: var(--sp-4); box-sizing: border-box;">
    <div class="card animate-fadeInUp" style="max-width: 100%; margin: 0 auto;">
        <div style="margin-bottom: var(--sp-5);">
            <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                <span style="margin-right: 0.5rem;"><?= $pageIcon ?></span><?= $pageTitle ?>
            </h2>
            <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                <?= $currentView === 'priority' ? 'Priority projects for your sales tracking' : 'Non-priority projects for your sales tracking' ?>
            </p>
        </div>

        <!-- View Tabs -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 0;">
            <a href="?view=non-priority"
               style="padding: 0.75rem 1.5rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; border-radius: 0.5rem 0.5rem 0 0; transition: all 0.2s;
                      <?= $currentView !== 'priority' ? 'color: var(--orange-500); border-bottom: 2px solid var(--orange-500); background: rgba(255,128,0,0.08);' : 'color: var(--text-secondary); border-bottom: 2px solid transparent;' ?>">
                📋 Non-Priority
            </a>
            <a href="?view=priority"
               style="padding: 0.75rem 1.5rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; border-radius: 0.5rem 0.5rem 0 0; transition: all 0.2s;
                      <?= $currentView === 'priority' ? 'color: var(--orange-500); border-bottom: 2px solid var(--orange-500); background: rgba(255,128,0,0.08);' : 'color: var(--text-secondary); border-bottom: 2px solid transparent;' ?>">
                ⭐ Priority
            </a>
        </div>

        <!-- Filters -->
        <div class="pm-filters">
            <input type="text" id="searchInput" placeholder="Search projects..." class="pm-search">
            <select id="regionFilter" class="pm-filter">
                <option value="">All Regions</option>
                <option value="NCR">NCR</option>
                <option value="Region I">Region I</option>
                <option value="Region II">Region II</option>
                <option value="Region III">Region III</option>
                <option value="Region IV-A">Region IV-A</option>
                <option value="Region IV-B">Region IV-B</option>
                <option value="Region V">Region V</option>
                <option value="Region VI">Region VI</option>
                <option value="Region VII">Region VII</option>
                <option value="Region XI">Region XI</option>
            </select>
            <select id="statusFilter" class="pm-filter">
                <option value="">All Status</option>
                <option value="Prospect">Prospect</option>
                <option value="Contacted">Contacted</option>
                <option value="Sales Qualified">Sales Qualified</option>
                <option value="Not Sales Qualified">Not Sales Qualified</option>
                <option value="Quoted">Quoted</option>
                <option value="Awarded">Awarded</option>
                <option value="For Execution">For Execution</option>
                <option value="Priority">Priority</option>
            </select>
        </div>

        <!-- Content Area -->
        <div id="pm-content">
            <div class="table-wrapper" style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; min-width: 800px;">
                    <thead>
                        <tr>
                            <th>Date Assigned</th>
                            <th>Contractor</th>
                            <th>Project Name</th>
                            <th>Region</th>
                            <th>Value (₱)</th>
                            <th>Status</th>
                            <th>Sales Tracking</th>
                        </tr>
                    </thead>
                    <tbody id="pm-table-body">
                        <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="pm-pagination" class="pagination-controls"></div>
        </div>
    </div>
</div>

<!-- Project Details Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>📋 Project Details</h2>
            <button class="modal-close" onclick="closeDetailsModal()">×</button>
        </div>
        <div class="modal-body" id="detailsModalBody" style="color:var(--text-primary);">
            <!-- Populated dynamically -->
        </div>
        <div class="modal-actions">
            <!-- buttons are injected dynamically -->
        </div>
    </div>
</div>

<script src="<?= $base ?>/static/js/modal-system.js?v=1"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/roles.js?v=2"></script>
<script src="<?= $base ?>/static/js/my-projects.js?v=9"></script>
<script>
const BASE = '<?= $base ?>';
const CURRENT_USER_ID = <?= $userId ?>;
</script>
</body>
</html>
