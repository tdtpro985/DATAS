<?php
/*
   pages/sidebar.php — Shared sidebar partial for authenticated pages.
   Requires $base, $role, and $fullName to be defined by the including page.
*/
$base = $base ?? '';
$role = $role ?? '';
$fullName = $fullName ?? '';

// Get database connection for dynamic sidebar content
require_once __DIR__ . '/../api/db.php';
try {
    $pdo = getDB();
} catch (Exception $e) {
    error_log("Sidebar DB Error: " . $e->getMessage());
    $pdo = null;
}
?>
<link rel="stylesheet" href="<?= $base ?>/static/css/light-theme.css?v=1">
<div class="ap-shell">
    <aside class="ap-sidebar" id="ap-sidebar">
        <div class="ap-sidebar-logo">
            <img src="<?= $base ?>/static/images/logo_header.png" alt="TDT Powersteel">
            <span class="ap-sidebar-logo-text">SILEP</span>
        </div>

        <div class="ap-sidebar-user">
            <div class="ap-sidebar-avatar"><?= strtoupper(substr($fullName, 0, 1)) ?></div>
            <div class="ap-sidebar-user-info">
                <div class="ap-sidebar-user-name"><?= htmlspecialchars($fullName) ?></div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div class="ap-sidebar-user-role role-badge-<?= $role ?>">
                        <?php
                        if ($role === 'superadmin') {
                            echo 'SUPERADMIN';
                        } elseif ($role === 'admin') {
                            echo 'ADMIN';
                        } elseif ($role === 'sales_rep') {
                            echo 'SALES REP';
                        } else {
                            echo 'ENCODER';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <nav class="ap-sidebar-nav">
            <div class="ap-nav-section-label">Navigation</div>
            
            <?php if (in_array($role, ['admin', 'superadmin', 'sales_rep'], true)): ?>
            <a href="<?= $base ?>/admin" class="ap-nav-item">
                <span class="ap-nav-label">Dashboard</span>
            </a>
            
            <!-- Reports Dropdown -->
            <div class="ap-nav-dropdown">
                <button class="ap-nav-item ap-nav-dropdown-toggle" type="button">
                    <span class="ap-nav-label">Reports</span>
                    <span class="ap-nav-arrow">▼</span>
                </button>
                <div class="ap-nav-dropdown-menu">
                    <a href="<?= $base ?>/reports" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Dashboard Report</span>
                    </a>
                    <a href="<?= $base ?>/full-reports" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Full Reports</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Project Leads Dropdown -->
            <div class="ap-nav-dropdown">
                <button class="ap-nav-item ap-nav-dropdown-toggle" type="button">
                    <span class="ap-nav-label">Project Leads</span>
                    <span class="ap-nav-arrow">▼</span>
                </button>
                <div class="ap-nav-dropdown-menu">
                    <a href="<?= $base ?>/projects?type=non-priority" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Non-Priority Projects</span>
                    </a>
                    <a href="<?= $base ?>/projects?type=priority" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Priority Projects</span>
                    </a>
                </div>
            </div>

            <a href="<?= $base ?>/platforms" class="ap-nav-item">
                <span class="ap-nav-label">Platform Leads</span>
            </a>

            <?php if (in_array($role, ['encoder', 'admin', 'superadmin'], true)): ?>
            <a href="<?= $base ?>/encode" class="ap-nav-item">
                <span class="ap-nav-label">Encode Projects</span>
            </a>
            <a href="<?= $base ?>/encode-platforms" class="ap-nav-item">
                <span class="ap-nav-label">Encode Platform Leads</span>
            </a>
            <?php endif; ?>
            
            <?php if ($role === 'superadmin' || $role === 'admin'): ?>
            <div class="ap-nav-section-label" style="margin-top: 1.5rem;">Management</div>
            
            <!-- Project Management Dropdown -->
            <div class="ap-nav-dropdown">
                <button class="ap-nav-item ap-nav-dropdown-toggle" type="button">
                    <span class="ap-nav-label">Project Management</span>
                    <span class="ap-nav-arrow">▼</span>
                </button>
                <div class="ap-nav-dropdown-menu">
                    <a href="<?= $base ?>/projects-management?view=unassigned" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Unassigned Projects</span>
                    </a>
                    <a href="<?= $base ?>/projects-management?view=assigned" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Assigned Projects</span>
                    </a>
                    <a href="<?= $base ?>/projects-management?view=unprocessed" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Unprocessed Projects</span>
                    </a>
                    <a href="<?= $base ?>/projects-management?view=processed" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Processed Projects</span>
                    </a>
                    <a href="<?= $base ?>/projects-management?view=archived" class="ap-nav-dropdown-item role-only--admin role-only--superadmin">
                        <span class="ap-nav-label">Archived Projects</span>
                    </a>
                    <a href="<?= $base ?>/illegitimate-projects" class="ap-nav-dropdown-item" data-role-access="admin,superadmin,sales_rep">
                        <span class="ap-nav-label">Illegitimate Projects</span>
                    </a>
                </div>
            </div>
            
            <a href="<?= $base ?>/sales-reps" class="ap-nav-item">
                <span class="ap-nav-label">Sales Representatives</span>
            </a>
            <?php if ($role === 'superadmin'): ?>
            <a href="<?= $base ?>/users" class="ap-nav-item">
                <span class="ap-nav-label">User Management</span>
            </a>
            <a href="<?= $base ?>/admin?page=settings" class="ap-nav-item settings-nav-link">
                <span class="ap-nav-label">Settings</span>
            </a>
            <?php endif; ?>
            <a href="<?= $base ?>/activity-logs" class="ap-nav-item">
                <span class="ap-nav-label">Activity Logs</span>
            </a>
            <?php endif; ?>
            
            <?php if ($role === 'sales_rep'): ?>
            <div class="ap-nav-section-label" style="margin-top: 1.5rem;">My Work</div>
            
            <!-- My Projects Dropdown -->
            <div class="ap-nav-dropdown">
                <button class="ap-nav-item ap-nav-dropdown-toggle" type="button">
                    <span class="ap-nav-label">My Projects</span>
                    <span class="ap-nav-arrow">▼</span>
                </button>
                <div class="ap-nav-dropdown-menu">
                    <a href="<?= $base ?>/my-projects?view=non-priority" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Non-Priority Projects</span>
                    </a>
                    <a href="<?= $base ?>/my-projects?view=priority" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Priority Projects</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </nav>

        <div class="ap-sidebar-footer">
            <button type="button" class="ap-logout-btn" id="logoutBtn">
                Logout
            </button>
        </div>
    </aside>

    <!-- Logout Confirmation Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Confirm Logout</h2>
                <button class="modal-close" id="closeLogoutModal">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin: 0 0 1rem; color: var(--text-secondary);">Are you sure you want to logout?</p>
                <p style="margin: 0; color: var(--text-primary); font-weight: 600;">
                    <?= htmlspecialchars($fullName) ?>
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                <button type="button" class="btn-secondary" id="cancelLogoutBtn">Cancel</button>
                <button type="button" class="btn-danger" id="confirmLogoutBtn">
                    Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Credits Modal -->
    <div class="credits-modal-overlay" id="creditsModal">
        <div class="credits-modal-container">
            <button type="button" class="credits-close-btn" onclick="closeCreditsModal()">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            
            <!-- Header Section -->
            <div class="credits-header">
                <div class="credits-logo-container">
                    <div class="credits-logo">
                        <span class="logo-tdt">TDT</span><span class="logo-powersteel">POWERSTEEL</span>
                    </div>
                    <div class="credits-badge">DATAS</div>
                </div>
                <div class="credits-tagline">Data-Allocation, Tracking and Assigning System</div>
            </div>
            
            <!-- Team Section -->
            <div class="credits-body">
                <div class="team-section">
                    <div class="team-title-container">
                        <div class="team-title-line"></div>
                        <h3 class="team-title">DEPLOYMENT TEAM 1</h3>
                        <div class="team-title-line"></div>
                    </div>
                    
                    <div class="team-members">
                        <div class="member-card featured">
                            <div class="member-icon">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="12" r="5" fill="currentColor"/>
                                    <path d="M6 26C6 21 10 18 16 18C22 18 26 21 26 26" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="member-info">
                                <div class="member-name">Genless Vivas</div>
                                <div class="member-role">DEVELOPER / QA</div>
                                <div class="member-contact">09959572648</div>
                            </div>
                            <div class="member-shine"></div>
                            <div class="featured-badge">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 1L10 6H15L11 9L13 14L8 11L3 14L5 9L1 6H6L8 1Z"/>
                                </svg>
                            </div>
                        </div>
                        
                        <div class="member-card">
                            <div class="member-icon">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="12" r="5" fill="currentColor"/>
                                    <path d="M6 26C6 21 10 18 16 18C22 18 26 21 26 26" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="member-info">
                                <div class="member-name">Homer B. Dela Cruz</div>
                                <div class="member-role">DATA MINING</div>
                                <div class="member-contact">09542036542</div>
                            </div>
                            <div class="member-shine"></div>
                        </div>
                        
                        <div class="member-card">
                            <div class="member-icon">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="12" r="5" fill="currentColor"/>
                                    <path d="M6 26C6 21 10 18 16 18C22 18 26 21 26 26" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="member-info">
                                <div class="member-name">Jaderick Austria</div>
                                <div class="member-role">DATA MINING</div>
                                <div class="member-contact">09654525265</div>
                            </div>
                            <div class="member-shine"></div>
                        </div>
                        
                        <div class="member-card">
                            <div class="member-icon">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="12" r="5" fill="currentColor"/>
                                    <path d="M6 26C6 21 10 18 16 18C22 18 26 21 26 26" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="member-info">
                                <div class="member-name">Adrian Carl L. Labutong</div>
                                <div class="member-role">DATA MINING</div>
                                <div class="member-contact">09597155554</div>
                            </div>
                            <div class="member-shine"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="credits-footer">
                <div class="footer-content">
                    <div class="footer-left">
                        <div class="footer-copyright">© 2025 TDT PowerSteel I.S.</div>
                        <div class="footer-rights">All rights reserved.</div>
                    </div>
                    <div class="footer-version">
                        <span class="version-label">DATAS</span>
                        <span class="version-number">V3.6</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile sidebar overlay -->
    <div class="ap-sidebar-overlay" id="ap-sidebar-overlay"></div>

    <div class="ap-main">
        <!-- Top Bar -->
        <div class="ap-topbar">
            <div class="ap-topbar-title">
                <!-- Hamburger button — visible on mobile only -->
                <button type="button" class="ap-hamburger" id="ap-hamburger" aria-label="Toggle sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="3" y1="6"  x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <span id="pageTitle">Dashboard</span>
            </div>
            <div class="ap-topbar-actions">
                <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--text-secondary); font-size: 0.875rem;">
                    <span><?= htmlspecialchars($fullName) ?></span>
                    <span class="role-badge-<?= $role ?>" style="padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                        <?= $role === 'superadmin' ? 'Superadmin' : ($role === 'admin' ? 'Admin' : ($role === 'encoder' ? 'Encoder' : 'Sales Rep')) ?>
                    </span>
                    <button id="creditsOpenBtn" type="button" class="credits-btn topbar-credits-btn" title="Credits & About">
                        <span>ℹ️</span>
                    </button>
                </div>
            </div>
        </div>


<script>
const BASE = '<?= $base ?>';
</script>
<script src="<?= $base ?>/static/js/sidebar.js?v=1"></script>