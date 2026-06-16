<?php
/* ============================================================
   pages/admin.php — Admin Panel with Sidebar
   ============================================================
   Requires admin or superadmin role.
   ============================================================ */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/admin/login');
    exit;
}

$role     = $_SESSION['user']['role']      ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['username'] ?? 'Admin');
$username = $_SESSION['user']['username']  ?? '';

if ($role !== 'admin' && $role !== 'superadmin') {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- CSS -->
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
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
</head>

<body data-role="<?= $role ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">

    <!-- Page content container -->
    <div id="admin-content-wrapper" style="grid-column: 1 / -1;">

        <!-- ── Dashboard page ── -->
        <div id="page-dashboard" class="admin-page active">
            
            <div class="card animate-fadeInUp">
                <div style="margin-bottom: var(--sp-5);">
                    <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                        <span style="margin-right: 0.5rem;">📈</span>Dashboard Overview
                    </h2>
                    <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                        Summary and key metrics at a glance
                    </p>
                </div>

                <!-- KPI Cards -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem;">
                    <div class="ap-stat-card">
                        <div class="ap-stat-icon">👥</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Total Users</div>
                            <div class="ap-stat-value" id="dash-total-users">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card">
                        <div class="ap-stat-icon">📋</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Non-Priority Projects</div>
                            <div class="ap-stat-value" id="dash-total-projects">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card">
                        <div class="ap-stat-icon">🔴</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Priority Projects</div>
                            <div class="ap-stat-value" id="dash-priority-projects">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card">
                        <div class="ap-stat-icon">💰</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Total Pipeline Value</div>
                            <div class="ap-stat-value" id="dash-pipeline-value">—</div>
                        </div>
                    </div>
                </div>

                <!-- Sales Rep Rankings -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="margin:0;font-size:1.1rem;color:var(--text-primary);">
                            🏆 Top Sales Representatives
                        </h3>
                        <div style="display: flex; gap: 0.5rem;">
                            <select id="salesRepPeriodFilter" style="padding: 0.5rem; background: var(--bg-input); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-sm); color: var(--text-primary); font-size: 0.85rem;">
                                <option value="all">All Time</option>
                                <option value="daily">Today</option>
                                <option value="weekly">This Week</option>
                                <option value="monthly" selected>This Month</option>
                            </select>
                            <select id="salesRepRegionFilter" style="padding: 0.5rem; background: var(--bg-input); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-sm); color: var(--text-primary); font-size: 0.85rem;">
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
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Rank</th>
                                    <th>Sales Representative</th>
                                    <th>Branch</th>
                                    <th>Projects Processed</th>
                                    <th>Total Value (₱)</th>
                                    <th>Last Project</th>
                                </tr>
                            </thead>
                            <tbody id="salesRepRankingBody">
                                <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div>
                    <h3 style="margin:0 0 1rem;font-size:1.1rem;color:var(--text-primary);">Recent Projects</h3>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date Added</th>
                                    <th>Contractor</th>
                                    <th>Project Name</th>
                                    <th>Value (₱)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="dashRecentProjectsBody">
                                <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
        <!-- END #page-dashboard -->

        <!-- ── Users page ── -->
        <div id="page-users" class="admin-page">

            <div class="card animate-fadeInUp">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-5);">
                    <div>
                        <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                            <span style="margin-right: 0.5rem;">👥</span>All Users
                        </h2>
                        <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                            Manage system accounts and role assignments
                        </p>
                    </div>
                    <div id="ap-user-count">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-4); gap: 1rem;">
                    <input type="text" id="searchInput" placeholder="Search users..." 
                           style="flex: 1; max-width: 400px; padding: 0.75rem 1rem; background: var(--bg-input); 
                                  border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-md); 
                                  color: var(--text-primary); font-size: 0.9rem;">
                    <button class="btn-primary" onclick="showModal()">+ New User</button>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                        </tbody>
                    </table>
                    <div id="paginationControls" class="pagination-controls"></div>
                </div>
            </div>

        </div>
        <!-- END #page-users -->

        <!-- ── Projects page ── -->
        <div id="page-projects" class="admin-page">

            <div class="card animate-fadeInUp">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-5);">
                    <div>
                        <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                            <span style="margin-right: 0.5rem;">📋</span>Non-Priority Projects
                        </h2>
                        <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                            View and manage non-priority projects
                        </p>
                    </div>
                    <div id="ap-project-count">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date & Time Added</th>
                                <th>Source</th>
                                <th>Contractor</th>
                                <th>Contact Person</th>
                                <th>Contact Number</th>
                                <th>Address</th>
                                <th>Project Name</th>
                                <th>Value (₱)</th>
                            </tr>
                        </thead>
                        <tbody id="projectTableBody">
                            <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                        </tbody>
                    </table>
                    <div id="projectPaginationControls" class="pagination-controls"></div>
                </div>
            </div>

        </div>
        <!-- END #page-projects -->

        <!-- ── Priority Projects page ── -->
        <div id="page-priority-projects" class="admin-page">

            <div class="card animate-fadeInUp">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-5);">
                    <div>
                        <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                            <span style="margin-right: 0.5rem;">🔴</span>Priority Projects
                        </h2>
                        <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                            Urgent projects requiring immediate attention
                        </p>
                    </div>
                    <div id="ap-priority-count">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date Time</th>
                                <th>Source</th>
                                <th>Contractor</th>
                                <th>Contact Person</th>
                                <th>Contact Number</th>
                                <th>Address</th>
                                <th>Pictures</th>
                                <th>Project Name</th>
                                <th>Value</th>
                                <th>Accomplishment Rate</th>
                            </tr>
                        </thead>
                        <tbody id="priorityTableBody">
                            <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-dim);">No priority projects</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <!-- END #page-priority-projects -->

    </div>
    <!-- END #admin-content-wrapper -->

</div>
<!-- END .dashboard -->

    </div> <!-- .ap-main -->
</div> <!-- .ap-shell -->

<!-- ── Create / Edit User Modal ── -->
<div class="modal-overlay" id="userModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create New User</h2>
            <button class="modal-close" onclick="hideModal()">&times;</button>
        </div>
        <form id="userForm">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="usernameInput" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="passwordInput" name="password" required>
                <small id="passwordHelp" style="display:none;color:var(--text-dim);margin-top:0.25rem;">
                    Leave blank to keep the current password.
                </small>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="fullNameInput" name="full_name">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select id="roleInput" name="role">
                    <option value="encoder">Encoder (Data Entry)</option>
                    <option value="sales_rep">Sales Representative</option>
                    <option value="admin">Administrator</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="hideModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Global Action Dropdown -->
<div id="globalActionMenu" class="action-menu" style="display:none;position:absolute;z-index:99999;"></div>

<!-- Project Details Modal -->
<div class="modal-overlay" id="projectModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="projectModalTitle">Project Details</h2>
            <button class="modal-close" onclick="closeProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="projectModalSubtitle" style="margin:0 0 1rem;color:var(--text-muted);font-size:0.9rem;">Tap any row to view full details.</p>
            <div id="projectDetailList" class="project-detail-list"></div>
        </div>
    </div>
</div>

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/admin.js?v=14"></script>
<script>
// Load dashboard stats on page load
loadDashboardStats();
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay[id], .detail-modal-overlay[id]').forEach(function(el) {
        if (el.parentNode !== document.body) document.body.appendChild(el);
    });
});
</script></body>
</html>
