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

        <nav class="ap-sidebar-nav">
            <div class="ap-nav-section-label">Navigation</div>
            
            <?php if (in_array($role, ['admin', 'superadmin', 'sales_rep'], true)): ?>
            <a href="<?= $base ?>/admin" class="ap-nav-item">
                <span class="ap-nav-label">Dashboard</span>
            </a>
            
            <a href="<?= $base ?>/reports" class="ap-nav-item">
                <span class="ap-nav-label">Reports</span>
            </a>
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
                </div>
            </div>
            
            <a href="<?= $base ?>/sales-reps" class="ap-nav-item">
                <span class="ap-nav-label">Sales Representatives</span>
            </a>
            <a href="<?= $base ?>/users" class="ap-nav-item">
                <span class="ap-nav-label">User Management</span>
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
                    <a href="<?= $base ?>/my-projects?view=assigned" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">Assigned to Me</span>
                    </a>
                    <a href="<?= $base ?>/my-projects?view=processed" class="ap-nav-dropdown-item">
                        <span class="ap-nav-label">My Processed</span>
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

    <div class="ap-main">
        <!-- Top Bar -->
        <div class="ap-topbar">
            <div class="ap-topbar-title">
                <span id="pageTitle">Dashboard</span>
            </div>
            <div class="ap-topbar-actions">
                <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--text-secondary); font-size: 0.875rem;">
                    <span><?= htmlspecialchars($fullName) ?></span>
                    <span class="role-badge-<?= $role ?>" style="padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                        <?= $role === 'superadmin' ? 'Superadmin' : ($role === 'admin' ? 'Admin' : ($role === 'encoder' ? 'Encoder' : 'Sales Rep')) ?>
                    </span>
                </div>
            </div>
        </div>

<script>
// Sidebar dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.ap-nav-dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.closest('.ap-nav-dropdown');
            const isOpen = dropdown.classList.contains('open');
            
            // Close all other dropdowns
            document.querySelectorAll('.ap-nav-dropdown').forEach(d => {
                if (d !== dropdown) {
                    d.classList.remove('open');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('open', !isOpen);
        });
    });
    
    // Set active state for dropdown items based on current URL
    const currentPath = window.location.pathname + window.location.search;
    document.querySelectorAll('.ap-nav-dropdown-item').forEach(item => {
        if (item.getAttribute('href') === currentPath) {
            item.classList.add('active');
            // Open parent dropdown
            const dropdown = item.closest('.ap-nav-dropdown');
            if (dropdown) {
                dropdown.classList.add('open');
            }
        }
    });
    
    // Set active state for regular nav items
    document.querySelectorAll('.ap-nav-item:not(.ap-nav-dropdown-toggle)').forEach(item => {
        const href = item.getAttribute('href');
        if (href && (href === currentPath || (href === '<?= $base ?>/' && currentPath === '<?= $base ?>/'))) {
            item.classList.add('active');
        }
    });
    
    // Update page title based on current page
    updatePageTitle();
    
    // Logout modal functionality
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const closeLogoutModal = document.getElementById('closeLogoutModal');
    const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    
    function openLogoutModal() {
        logoutModal.classList.add('active');
    }
    
    function closeLogoutModalHandler() {
        logoutModal.classList.remove('active');
    }
    
    function confirmLogout() {
        // Use the centralized JS logout helper when available.
        if (typeof Auth !== 'undefined' && typeof Auth.logout === 'function') {
            Auth.logout();
            return;
        }

        // Fallback to the legacy server-side logout page.
        window.location.href = '<?= $base ?>/logout';
    }
    
    // Event listeners
    if (logoutBtn) {
        logoutBtn.addEventListener('click', openLogoutModal);
    }
    
    if (closeLogoutModal) {
        closeLogoutModal.addEventListener('click', closeLogoutModalHandler);
    }
    
    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', closeLogoutModalHandler);
    }
    
    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', confirmLogout);
    }
    
    // Close modal on overlay click
    if (logoutModal) {
        logoutModal.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                closeLogoutModalHandler();
            }
        });
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal.classList.contains('active')) {
            closeLogoutModalHandler();
        }
    });
});

// Update page title and icon based on current page
function updatePageTitle() {
    const path = window.location.pathname;
    const search = window.location.search;
    const pageTitle = document.getElementById('pageTitle');
    
    if (!pageTitle) return;
    
    // Define page titles
    const pages = {
        '/admin': { title: 'Dashboard' },
        '/reports': { title: 'Reports' },
        '/projects': { title: 'Projects' },
        '/platforms': { title: 'Platform Leads' },
        '/encode': { title: 'Data Entry' },
        '/encode-platforms': { title: 'Encode Platform Leads' },
        '/encode/non-priority': { title: 'Encode Non-Priority Project' },
        '/encode/priority': { title: 'Encode Priority Project' },
        '/sales-reps': { title: 'Sales Representatives' },
        '/projects-management': { title: 'Project Management' },
        '/my-projects': { title: 'My Projects' }
    };
    
    // Check for specific query parameters
    if (path.includes('/projects-management')) {
        if (search.includes('view=unassigned')) {
            pageTitle.textContent = 'Unassigned Projects';
        } else if (search.includes('view=assigned')) {
            pageTitle.textContent = 'Assigned Projects';
        } else if (search.includes('view=unprocessed')) {
            pageTitle.textContent = 'Unprocessed Projects';
        } else if (search.includes('view=processed')) {
            pageTitle.textContent = 'Processed Projects';
        } else {
            pageTitle.textContent = 'Project Management';
        }
    } else if (path.includes('/my-projects')) {
        if (search.includes('view=assigned')) {
            pageTitle.textContent = 'My Assigned Projects';
        } else if (search.includes('view=processed')) {
            pageTitle.textContent = 'My Processed Projects';
        } else {
            pageTitle.textContent = 'My Projects';
        }
    } else if (path.includes('/encode/non-priority')) {
        pageTitle.textContent = 'Encode Non-Priority Project';
    } else if (path.includes('/encode/priority')) {
        pageTitle.textContent = 'Encode Priority Project';
    } else if (path.includes('/encode')) {
        pageTitle.textContent = 'Data Entry';
    } else if (path.includes('/projects') && search.includes('type=priority')) {
        pageTitle.textContent = 'Priority Projects';
    } else if (path.includes('/projects') && search.includes('type=non-priority')) {
        pageTitle.textContent = 'Non-Priority Projects';
    } else if (path.includes('/encode')) {
        // Get form parameter from URL
        const urlParams = new URLSearchParams(search);
        const formSlug = urlParams.get('form');
        
        if (formSlug === 'priority') {
            pageTitle.textContent = 'Encode Priority Project';
        } else if (formSlug === 'non-priority') {
            pageTitle.textContent = 'Encode Non-Priority Project';
        } else if (formSlug) {
            // Capitalize first letter of each word
            const formName = formSlug.split('-').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
            pageTitle.textContent = 'Encode ' + formName;
        } else {
            pageTitle.textContent = 'Data Entry';
        }
    } else {
        // Match base path
        for (const [pagePath, info] of Object.entries(pages)) {
            if (path === '<?= $base ?>' + pagePath || path === '<?= $base ?>' + pagePath + '/') {
                pageTitle.textContent = info.title;
                break;
            }
        }
        
        // Default to Projects for root path
        if (path === '<?= $base ?>/' || path === '<?= $base ?>') {
            pageTitle.textContent = 'Projects';
        }
        
        // Admin Panel is the Dashboard
        if (path === '<?= $base ?>/admin' || path === '<?= $base ?>/admin/') {
            pageTitle.textContent = 'Dashboard';
        }
    }
}
</script>
