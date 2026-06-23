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
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/components.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">
    
    <!-- Philippine DateTime -->
    <script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>
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

                <!-- KPI Cards with Navigation -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;">
                    <div class="ap-stat-card clickable-card" onclick="navigateTo('users')" style="cursor:pointer;" title="Go to User Management">
                        <div class="ap-stat-icon">👑</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Superadmin</div>
                            <div class="ap-stat-value" id="dash-superadmin-count">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card clickable-card" onclick="navigateTo('users')" style="cursor:pointer;" title="Go to User Management">
                        <div class="ap-stat-icon">👨‍💼</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Admin (RSM)</div>
                            <div class="ap-stat-value" id="dash-admin-count">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card clickable-card" onclick="navigateTo('sales-reps')" style="cursor:pointer;" title="Go to Sales Representatives">
                        <div class="ap-stat-icon">👤</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Sales Reps</div>
                            <div class="ap-stat-value" id="dash-salesrep-count">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card clickable-card" onclick="navigateTo('users')" style="cursor:pointer;" title="Go to User Management">
                        <div class="ap-stat-icon">⌨️</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Encoders</div>
                            <div class="ap-stat-value" id="dash-encoder-count">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card clickable-card" onclick="navigateTo('projects')" style="cursor:pointer;" title="Go to Project Leads Non-Priority">
                        <div class="ap-stat-icon">📋</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Non-Priority Projects</div>
                            <div class="ap-stat-value" id="dash-total-projects">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card clickable-card" onclick="navigateTo('priority-projects')" style="cursor:pointer;" title="Go to Project Leads Priority Projects">
                        <div class="ap-stat-icon">🔴</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Priority Projects</div>
                            <div class="ap-stat-value" id="dash-priority-projects">—</div>
                        </div>
                    </div>
                    <div class="ap-stat-card clickable-card" onclick="navigateTo('reports')" style="cursor:pointer;" title="Go to Reports Full Reports">
                        <div class="ap-stat-icon">💰</div>
                        <div class="ap-stat-content">
                            <div class="ap-stat-label">Total Pipeline Value</div>
                            <div class="ap-stat-value" id="dash-pipeline-value">—</div>
                        </div>
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

        <?php if ($role === 'superadmin'): ?>
        <!-- ── Settings page ── -->
        <div id="page-settings" class="admin-page">

            <div class="card animate-fadeInUp">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-5);">
                    <div>
                        <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                            <span style="margin-right: 0.5rem;">⚙️</span>System Settings
                        </h2>
                        <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                            Configure system-wide settings and preferences (Superadmin only)
                        </p>
                    </div>
                    <button class="btn-primary" onclick="saveAllSettings()">
                        💾 Save All Settings
                    </button>
                </div>

                <!-- Settings Tabs -->
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:2rem;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:1rem;">
                    <button class="admin-tab-btn active" data-tab="general" onclick="switchSettingsTab('general')">
                        <span>⚙️</span> General
                    </button>
                    <button class="admin-tab-btn" data-tab="security" onclick="switchSettingsTab('security')">
                        <span>🔒</span> Security
                    </button>
                    <button class="admin-tab-btn" data-tab="projects" onclick="switchSettingsTab('projects')">
                        <span>📋</span> Projects
                    </button>
                    <button class="admin-tab-btn" data-tab="notifications" onclick="switchSettingsTab('notifications')">
                        <span>🔔</span> Notifications
                    </button>
                    <button class="admin-tab-btn" data-tab="regional" onclick="switchSettingsTab('regional')">
                        <span>🌏</span> Regional
                    </button>
                    <button class="admin-tab-btn" data-tab="system" onclick="switchSettingsTab('system')">
                        <span>🖥️</span> System Info
                    </button>
                    <button class="admin-tab-btn" data-tab="tools" onclick="switchSettingsTab('tools')">
                        <span>🛠️</span> Tools
                    </button>
                </div>

                <!-- Loading / Error -->
                <div id="settings-loading" style="text-align:center;padding:3rem;color:var(--text-dim);">Loading settings...</div>
                <div id="settings-error" style="display:none;text-align:center;padding:2rem;color:var(--text-danger);"></div>

                <!-- General Settings -->
                <div id="settings-tab-general" class="settings-tab-content active">
                    <div class="settings-section">
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label class="setting-label">App Name</label>
                                <p class="setting-desc">The system name displayed in browser title and headers</p>
                                <input type="text" id="setting-app_name" class="setting-input" placeholder="TDT Powersteel SILEP - DATAS">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">App Version</label>
                                <p class="setting-desc">Current application version number</p>
                                <input type="text" id="setting-app_version" class="setting-input" placeholder="3.6">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Items Per Page</label>
                                <p class="setting-desc">Default number of items per page in tables</p>
                                <input type="number" id="setting-items_per_page" class="setting-input" min="10" max="200">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Date Format</label>
                                <p class="setting-desc">Default date/time format for the system</p>
                                <input type="text" id="setting-date_format" class="setting-input" placeholder="Y-m-d H:i:s">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Log Retention (Days)</label>
                                <p class="setting-desc">Number of days to retain activity logs</p>
                                <input type="number" id="setting-log_retention_days" class="setting-input" min="30" max="3650">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div id="settings-tab-security" class="settings-tab-content">
                    <div class="settings-section">
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label class="setting-label">Max Login Attempts</label>
                                <p class="setting-desc">Maximum failed login attempts before account lockout</p>
                                <input type="number" id="setting-max_login_attempts" class="setting-input" min="3" max="20">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Session Timeout (Minutes)</label>
                                <p class="setting-desc">Session timeout duration in minutes</p>
                                <input type="number" id="setting-session_timeout_minutes" class="setting-input" min="5" max="1440">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Min Password Length</label>
                                <p class="setting-desc">Minimum password length requirement</p>
                                <input type="number" id="setting-password_min_length" class="setting-input" min="6" max="32">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projects Settings -->
                <div id="settings-tab-projects" class="settings-tab-content">
                    <div class="settings-section">
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label class="setting-label">Default Project Status</label>
                                <p class="setting-desc">Default status assigned to newly created projects</p>
                                <input type="text" id="setting-default_project_status" class="setting-input" placeholder="New">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Priority Threshold (Days)</label>
                                <p class="setting-desc">Days without update before project is flagged as priority</p>
                                <input type="number" id="setting-priority_project_threshold_days" class="setting-input" min="1" max="90">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Auto-Archive (Days)</label>
                                <p class="setting-desc">Days after completion before auto-archiving projects</p>
                                <input type="number" id="setting-auto_archive_days" class="setting-input" min="30" max="730">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications Settings -->
                <div id="settings-tab-notifications" class="settings-tab-content">
                    <div class="settings-section">
                        <div class="settings-grid">
                            <div class="setting-item">
                                <div class="setting-toggle-row">
                                    <div>
                                        <label class="setting-label">Email Notifications</label>
                                        <p class="setting-desc">Enable email notifications for project assignments</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="setting-enable_email_notifications">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="setting-item">
                                <div class="setting-toggle-row">
                                    <div>
                                        <label class="setting-label">SMS Notifications</label>
                                        <p class="setting-desc">Enable SMS notifications for project updates</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="setting-enable_sms_notifications">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Regional Settings -->
                <div id="settings-tab-regional" class="settings-tab-content">
                    <div class="settings-section">
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label class="setting-label">Timezone</label>
                                <p class="setting-desc">System timezone for date/time display</p>
                                <select id="setting-timezone" class="setting-input">
                                    <option value="Asia/Manila">Asia/Manila (UTC+8)</option>
                                    <option value="Asia/Singapore">Asia/Singapore (UTC+8)</option>
                                    <option value="Asia/Shanghai">Asia/Shanghai (UTC+8)</option>
                                    <option value="Asia/Tokyo">Asia/Tokyo (UTC+9)</option>
                                    <option value="America/New_York">America/New_York</option>
                                    <option value="America/Chicago">America/Chicago</option>
                                    <option value="America/Los_Angeles">America/Los_Angeles</option>
                                    <option value="Europe/London">Europe/London</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Currency Symbol</label>
                                <p class="setting-desc">Currency symbol for monetary values</p>
                                <input type="text" id="setting-currency_symbol" class="setting-input" placeholder="₱" maxlength="5">
                            </div>
                            <div class="setting-item">
                                <label class="setting-label">Currency Code</label>
                                <p class="setting-desc">ISO currency code</p>
                                <input type="text" id="setting-currency_code" class="setting-input" placeholder="PHP" maxlength="3" style="text-transform:uppercase;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div id="settings-tab-system" class="settings-tab-content">
                    <div class="settings-section">
                        <div class="settings-grid" id="system-info-grid">
                            <div class="setting-item info-item">
                                <label class="setting-label">PHP Version</label>
                                <div class="info-value" id="sysinfo-php_version">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Database Version</label>
                                <div class="info-value" id="sysinfo-database_version">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Database Size</label>
                                <div class="info-value" id="sysinfo-database_size_mb">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Server Software</label>
                                <div class="info-value" id="sysinfo-server_software">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">App Environment</label>
                                <div class="info-value" id="sysinfo-app_environment">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">App Version</label>
                                <div class="info-value" id="sysinfo-app_version">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Debug Mode</label>
                                <div class="info-value" id="sysinfo-debug_mode">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Timezone (PHP)</label>
                                <div class="info-value" id="sysinfo-timezone">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Current Server Time</label>
                                <div class="info-value" id="sysinfo-current_time">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Memory Usage</label>
                                <div class="info-value" id="sysinfo-memory_usage">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Peak Memory</label>
                                <div class="info-value" id="sysinfo-peak_memory">—</div>
                            </div>
                            <div class="setting-item info-item">
                                <label class="setting-label">Active Sessions</label>
                                <div class="info-value" id="sysinfo-active_sessions">—</div>
                            </div>
                            <div class="setting-item info-item" style="grid-column:1/-1;">
                                <label class="setting-label">PHP Extensions (first 20)</label>
                                <div class="info-value" id="sysinfo-php_extensions" style="font-size:0.8rem;font-family:monospace;word-break:break-all;">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tools -->
                <div id="settings-tab-tools" class="settings-tab-content">
                    <div class="settings-section">
                        <div class="settings-grid" style="grid-template-columns:1fr;">
                            <!-- Maintenance Mode -->
                            <div class="setting-item">
                                <div class="setting-toggle-row">
                                    <div>
                                        <label class="setting-label">Maintenance Mode</label>
                                        <p class="setting-desc">When enabled, only superadmin users can access the system. Other users will see the maintenance message.</p>
                                    </div>
                                    <label class="toggle-switch toggle-warning">
                                        <input type="checkbox" id="setting-maintenance_mode">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="setting-item" id="maintenance-message-group">
                                <label class="setting-label">Maintenance Message</label>
                                <p class="setting-desc">Message displayed to users during maintenance</p>
                                <textarea id="setting-maintenance_message" class="setting-input" rows="3" placeholder="System is currently under maintenance..."></textarea>
                            </div>

                            <!-- Export Section -->
                            <div class="setting-item" style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.1);">
                                <label class="setting-label">📤 Database Export</label>
                                <p class="setting-desc">Download database backups as .SQL files</p>
                                <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
                                    <button class="btn-primary" onclick="exportDatabase()">
                                        💾 Export Full Database
                                    </button>
                                    <button class="btn-secondary" onclick="exportData()">
                                        📄 Export Data Only
                                    </button>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="setting-item" style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.1);">
                                <label class="setting-label">System Tools</label>
                                <p class="setting-desc">Perform system maintenance and diagnostic operations</p>
                                <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
                                    <button class="btn-secondary" onclick="clearSystemCache()">
                                        🗑️ Clear Cache
                                    </button>
                                    <button class="btn-secondary" onclick="checkDatabaseHealth()">
                                        🔍 Check Database
                                    </button>
                                    <button class="btn-secondary" onclick="optimizeDatabaseTables()">
                                        ⚡ Optimize Tables
                                    </button>
                                    <button class="btn-secondary" onclick="refreshSystemInfo()">
                                        🔄 Refresh System Info
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Save Button -->
                <div style="display:flex;justify-content:flex-end;gap:1rem;margin-top:2rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,0.1);">
                    <button class="btn-secondary" onclick="restoreDefaultSettings()">↩️ Restore Defaults</button>
                    <button class="btn-primary" onclick="saveAllSettings()">💾 Save All Settings</button>
                </div>
            </div>
        </div>
        <!-- END #page-settings -->
        <?php endif; ?>

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

<!-- Settings Save Result Modal -->
<div class="modal-overlay" id="settingsSaveModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Settings Saved</h2>
            <button class="modal-close" onclick="hideSettingsSaveModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin:0 0 1rem;color:var(--text-secondary);">Your changes were saved successfully. Review the updated settings below.</p>
            <div id="settingsSaveSummary" style="margin-bottom:1rem;font-size:0.95rem;color:var(--text-muted);"></div>
            <div id="settingsSaveChangesList" style="display:grid;gap:0.75rem;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-primary" type="button" id="settingsSaveConfirmButton">Close</button>
        </div>
    </div>
</div>

<!-- Restore Defaults Confirmation Modal -->
<div class="modal-overlay" id="restoreDefaultsModal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2>Restore Defaults</h2>
            <button class="modal-close" onclick="hideRestoreDefaultsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin:0 0 1rem;color:var(--text-secondary);">Restore all settings back to the system defaults? This cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" type="button" onclick="hideRestoreDefaultsModal()">Cancel</button>
            <button class="btn-primary" type="button" id="confirmRestoreDefaultsButton">Restore Defaults</button>
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
