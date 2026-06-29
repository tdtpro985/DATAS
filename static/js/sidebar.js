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
    
    // Set active state based on current page content, not just URL
    const activePage = document.querySelector('.admin-page.active');
    const activePageId = activePage ? activePage.id : null;
    
    document.querySelectorAll('.ap-nav-item:not(.ap-nav-dropdown-toggle)').forEach(item => {
        const href = item.getAttribute('href');
        if (href) {
            // Special handling for Settings link - only active when settings page is visible
            if (item.classList.contains('settings-nav-link')) {
                if (activePageId === 'page-settings') {
                    item.classList.add('active');
                }
            } 
            // Dashboard link
            else if (href.endsWith('/admin') || href.endsWith('/admin/')) {
                // Only active if we're on admin page AND settings page is not active
                if ((currentPath === href || currentPath.startsWith(href)) && activePageId !== 'page-settings') {
                    item.classList.add('active');
                }
            }
            // Regular matching for other links
            else if (href === currentPath || (href === BASE + '/' && currentPath === BASE + '/')) {
                item.classList.add('active');
            }
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

    if (logoutModal) {
        logoutModal.style.display = 'none';
    }

    function openLogoutModal() {
        if (!logoutModal) return;
        logoutModal.classList.add('active');
        logoutModal.style.display = 'flex';
    }

    function closeLogoutModalHandler() {
        if (!logoutModal) return;
        logoutModal.classList.remove('active');
        logoutModal.style.display = 'none';
    }

    function confirmLogout() {
        // Use the centralized JS logout helper when available.
        if (typeof Auth !== 'undefined' && typeof Auth.logout === 'function') {
            Auth.logout();
            return;
        }

        // Fallback to the legacy server-side logout page.
        window.location.href = BASE + '/logout';
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

    // Modals are already properly positioned within ap-shell layout.
    // The ap-main content area automatically handles margins.
    // No need to move modals to body - doing so causes CSS cascade issues.

    // ── Hamburger / Sidebar toggle (mobile) ──────────────
    const hamburger = document.getElementById('ap-hamburger');
    const sidebar   = document.getElementById('ap-sidebar');
    const overlay   = document.getElementById('ap-sidebar-overlay');

    function openSidebar() {
        sidebar?.classList.add('open');
        overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburger?.addEventListener('click', () => {
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay?.addEventListener('click', closeSidebar);

    // Close sidebar when a nav link is clicked on mobile
    document.querySelectorAll('.ap-nav-item, .ap-nav-dropdown-item').forEach(el => {
        el.addEventListener('click', () => {
            if (window.innerWidth <= 768) closeSidebar();
            closeLogoutModalHandler();
        });
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
        '/users': { title: 'User Management' },
        '/sr-performance': { title: 'SR Performance Report' },
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
        } else if (search.includes('view=archived')) {
            pageTitle.textContent = 'Archived Projects';
        } else {
            pageTitle.textContent = 'Project Management';
        }
    } else if (path.includes('/my-projects')) {
        if (search.includes('view=priority')) {
            pageTitle.textContent = 'Priority Projects';
        } else {
            pageTitle.textContent = 'Non-Priority Projects';
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
            if (path === BASE + pagePath || path === BASE + pagePath + '/') {
                pageTitle.textContent = info.title;
                break;
            }
        }
        
        // Default to Projects for root path
        if (path === BASE + '/' || path === BASE) {
            pageTitle.textContent = 'Projects';
        }
        
        // Admin Panel is the Dashboard
        if (path === BASE + '/admin' || path === BASE + '/admin/') {
            pageTitle.textContent = 'Dashboard';
        }
    }
}

// Credits modal functions
window.showCreditsModal = function() {
    const modal = document.getElementById('creditsModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
    modal.classList.add('active');
};

window.closeCreditsModal = function() {
    const modal = document.getElementById('creditsModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.classList.remove('modal-open');
    modal.style.display = 'none';
};

// Add event listeners for credits modal when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    
    // Credits modal functionality
    const creditsModal = document.getElementById('creditsModal');
    const creditsOpenButtons = document.querySelectorAll('.credits-btn, #creditsOpenBtn');
    const creditsCloseButtons = document.querySelectorAll('.credits-close-btn');

    creditsOpenButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showCreditsModal();
        });
    });
    
    creditsCloseButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            closeCreditsModal();
        });
    });

    // Close modal on overlay click
    if (creditsModal) {
        creditsModal.addEventListener('click', function(e) {
            if (e.target === creditsModal) {
                closeCreditsModal();
            }
        });
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && creditsModal && creditsModal.classList.contains('active')) {
            closeCreditsModal();
        }
    });
});
