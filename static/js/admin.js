let editingUserId = null;
let currentUserId = null;
let allUsers = [];
let filteredUsers = [];
const USERS_PER_PAGE = 7;
let currentPage = 1;

// Base path injected by admin.php — falls back to '/new-dashboard' if not set
const _B = (typeof BASE !== 'undefined') ? BASE : '/new-dashboard';

// ── Load Dashboard Stats ──────────────────────────────────────
async function loadDashboardStats() {
    const safeText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    const parseJson = async (res) => {
        if (!res.ok) {
            console.warn('API response not ok:', res.status, res.statusText, res.url);
            return null;
        }
        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await res.text();
            console.warn('API response not JSON:', contentType, text.substring(0, 200));
            return null;
        }
        try {
            return await res.json();
        } catch (e) {
            const text = await res.text();
            console.error('JSON parse error:', e.message, 'Response:', text.substring(0, 200));
            return null;
        }
    };

    try {
        console.log('[Dashboard] Base path:', _B);
        
        // Load total users
        const usersRes = await fetch(_B + '/api/v1/users', { credentials: 'include' });
        const users = await parseJson(usersRes);
        safeText('dash-total-users', Array.isArray(users) ? users.length : 0);

        // Load projects count
        const projectsRes = await fetch(_B + '/api/v1/projects?page=1&size=1', { credentials: 'include' });
        const projectsData = await parseJson(projectsRes);
        safeText('dash-total-projects', (projectsData && typeof projectsData.total === 'number') ? projectsData.total : 0);

        // Load priority projects count
        const priorityRes = await fetch(_B + '/api/v1/projects/priority?page=1&size=1', { credentials: 'include' });
        const priorityData = await parseJson(priorityRes);
        const priorityCount = priorityData && (typeof priorityData.total === 'number' ? priorityData.total : (Array.isArray(priorityData.projects) ? priorityData.projects.length : 0));
        safeText('dash-priority-projects', priorityCount);

        // Load KPI for pipeline value
        const kpiRes = await fetch(_B + '/api/v1/kpi?period=monthly&region=all', { credentials: 'include' });
        const kpiData = await parseJson(kpiRes);
        const kpiMetrics = kpiData && (kpiData.data || kpiData);
        const pipelineValue = (kpiMetrics && typeof kpiMetrics.total_pipeline_value === 'number') ? kpiMetrics.total_pipeline_value : 0;
        safeText('dash-pipeline-value', '₱' + pipelineValue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        // Load sales rep rankings
        loadSalesRepRankings();

        // Load recent projects
        const recentRes = await fetch(_B + '/api/v1/projects?page=1&size=10', { credentials: 'include' });
        const recentData = await parseJson(recentRes);
        const tbody = document.getElementById('dashRecentProjectsBody');
        if (tbody) {
            if (recentData && Array.isArray(recentData.projects) && recentData.projects.length > 0) {
                tbody.innerHTML = recentData.projects.map(p => {
                    const date = new Date(p.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
                    const value = (p.project_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const statusClass = (p.status || '').toLowerCase().replace(/\s+/g, '-');
                    return `<tr>
                        <td>${date}</td>
                        <td>${p.contractor_name || '—'}</td>
                        <td>${p.project_name || '—'}</td>
                        <td style="text-align:right;">₱${value}</td>
                        <td><span class="status-badge status-${statusClass}">${p.status || '—'}</span></td>
                    </tr>`;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-dim);">No recent projects</td></tr>';
            }
        }
    } catch (err) {
        console.error('Error loading dashboard stats:', err);
    }
}

async function loadUsers() {
    // Get current user ID to prevent self-deletion
    try {
        const meRes = await fetch(_B + '/api/v1/auth/me', { credentials: 'include' });
        if (meRes.ok) {
            const meData = await meRes.json();
            currentUserId = meData.id;
        }
    } catch (e) { }

    const res = await fetch(_B + '/api/v1/users', { credentials: 'include' });
    if (res.ok) {
        allUsers = await res.json();
        filteredUsers = [...allUsers];
        currentPage = 1;
        renderUsersPage(currentPage);
        renderPaginationControls();

        // Update stat chip
        const chip = document.getElementById('ap-user-count');
        if (chip) {
            chip.innerHTML = `<div class="ap-stat-chip"><strong>${allUsers.length}</strong> total users</div>`;
        }
    }
}

function renderUsersPage(page) {
    const tbody = document.getElementById('userTableBody');
    const startIndex = (page - 1) * USERS_PER_PAGE;
    const endIndex = startIndex + USERS_PER_PAGE;
    const usersToRender = filteredUsers.slice(startIndex, endIndex);

    tbody.innerHTML = usersToRender.map(u => {
        const isMe = u.id === currentUserId;
        // Stringify user for the edit function call
        const userJson = JSON.stringify(u).replace(/"/g, '&quot;');

        const resetBadge = u.reset_requested ? '<span style="background:rgba(239,68,68,0.2);color:#ef4444;padding:0.1rem 0.5rem;border-radius:999px;font-size:0.7rem;margin-left:0.5rem;font-weight:600;border:1px solid rgba(239,68,68,0.3);">Reset Requested</span>' : '';

        return `
        <tr>
            <td style="font-weight:500;">${u.username} ${isMe ? '<span style="color:var(--text-muted);font-weight:400;">(You)</span>' : ''} ${resetBadge}</td>
            <td>${u.full_name || '—'}</td>
            <td><span class="role-badge role-${u.role}">${u.role}</span></td>
            <td style="color:var(--text-dim);font-size:0.85rem;">${new Date(u.created_at).toLocaleDateString()}</td>
            <td>
                <button class="action-toggle"
                        data-user='${userJson}'
                        data-isme="${isMe}"
                        data-id="${u.id}"
                        onclick="openActionMenu(event, this)">
                    Actions &#9662;
                </button>
            </td>
        </tr>
        `;
    }).join('');
}

function renderPaginationControls() {
    const container = document.getElementById('paginationControls');
    if (!container) return;

    const totalPages = Math.ceil(filteredUsers.length / USERS_PER_PAGE);
    if (totalPages <= 1) {
        container.innerHTML = ''; // Hide if only 1 page
        return;
    }

    let html = `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="goToPage(${currentPage - 1})">Previous</button>`;

    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }

    html += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="goToPage(${currentPage + 1})">Next</button>`;

    container.innerHTML = html;
}

function goToPage(page) {
    const totalPages = Math.ceil(filteredUsers.length / USERS_PER_PAGE);
    if (page < 1 || page > totalPages) return;

    currentPage = page;
    renderUsersPage(currentPage);
    renderPaginationControls();
    closeActionMenu(); // Ensure floating menu closes when navigating pages
}

async function deleteUser(id) {
    const confirmed = await Confirm.show({
        title: 'Delete User',
        message: 'Are you sure you want to delete this user? This action cannot be undone.',
        confirmText: 'Delete',
        cancelText: 'Cancel',
        type: 'danger'
    });
    
    if (!confirmed) return;
    
    const res = await fetch(_B + `/api/v1/users/${id}`, { method: 'DELETE', credentials: 'include' });
    if (res.ok) {
        Toast.success('User deleted successfully');
        loadUsers();
    } else {
        const data = await res.json();
        Toast.error(data.detail || 'Failed to delete user');
    }
}

async function reset2FA(id) {
    const confirmed = await Confirm.show({
        title: 'Reset 2FA',
        message: 'Are you sure you want to reset 2FA for this user? They will need to set it up again on their next login.',
        confirmText: 'Reset 2FA',
        cancelText: 'Cancel',
        type: 'warning'
    });
    
    if (!confirmed) return;

    const user = allUsers.find(u => u.id == id);
    if (!user) return;

    const data = {
        username: user.username,
        full_name: user.full_name,
        role: user.role,
        reset_2fa: true
    };

    const res = await fetch(_B + `/api/v1/users/${id}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    if (res.ok) {
        Toast.success("2FA has been successfully reset for this user.");
    } else {
        const err = await res.json();
        Toast.error(err.detail || 'Failed to reset 2FA');
    }
}

function showModal(isEdit = false) {
    document.getElementById('modalTitle').textContent = isEdit ? 'Edit User' : 'Create New User';
    document.getElementById('submitBtn').textContent = isEdit ? 'Save Changes' : 'Create User';
    document.getElementById('passwordInput').required = !isEdit;
    document.getElementById('passwordHelp').style.display = isEdit ? 'block' : 'none';
    document.getElementById('userModal').style.display = 'flex';
}

function hideModal() {
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('userForm').reset();
    editingUserId = null;
}

function editUser(user) {
    editingUserId = user.id;
    document.getElementById('usernameInput').value = user.username;
    document.getElementById('fullNameInput').value = user.full_name || '';
    document.getElementById('roleInput').value = user.role;
    showModal(true);
}

document.getElementById('userForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    // If password is empty during edit, don't send it so backend ignores it
    if (editingUserId && !data.password) {
        delete data.password;
    }

    const url = editingUserId ? _B + `/api/v1/users/${editingUserId}` : _B + '/api/v1/users';
    const method = editingUserId ? 'PUT' : 'POST';

    const res = await fetch(url, {
        method: method,
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    if (res.ok) {
        hideModal();
        Toast.success(`User ${editingUserId ? 'updated' : 'created'} successfully`);
        loadUsers();
    } else {
        const err = await res.json();
        Toast.error(err.detail || `Failed to ${editingUserId ? 'update' : 'create'} user`);
    }
});

// Initialize
loadUsers();
// Load priority projects count in background for badge
loadPriorityProjects();

// Global Action Dropdown logic
function openActionMenu(e, btn) {
    e.stopPropagation();

    const menu = document.getElementById('globalActionMenu');

    // If clicking the already open menu on the same button
    if (btn.classList.contains('active')) {
        closeActionMenu();
        return;
    }

    // Close other active toggles
    document.querySelectorAll('.action-toggle.active').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Parse user data from button
    const userJsonStr = btn.getAttribute('data-user');
    const isMe = btn.getAttribute('data-isme') === 'true';
    const userId = btn.getAttribute('data-id');

    // Populate the global menu DOM
    menu.innerHTML = `
        <button onclick='editUser(${userJsonStr}); closeActionMenu()'>Edit</button>
        <button onclick='reset2FA(${userId}); closeActionMenu()'>Reset 2FA</button>
        ${!isMe ? `<button class="text-danger" onclick="deleteUser(${userId}); closeActionMenu()">Delete</button>` : ''}
    `;

    // Display the menu so it has dimensions
    menu.style.display = 'flex';

    // Position menu exactly under the button using viewport tracking
    const rect = btn.getBoundingClientRect();
    menu.style.top = `${rect.bottom + window.scrollY + 4}px`;
    menu.style.left = `${rect.right + window.scrollX - menu.offsetWidth}px`;
}

function closeActionMenu() {
    const menu = document.getElementById('globalActionMenu');
    if (menu) menu.style.display = 'none';
    document.querySelectorAll('.action-toggle.active').forEach(b => b.classList.remove('active'));
}

document.addEventListener('click', closeActionMenu);
document.addEventListener('scroll', closeActionMenu, { passive: true });

// Search functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    filteredUsers = allUsers.filter(u =>
        u.username.toLowerCase().includes(query) ||
        (u.full_name && u.full_name.toLowerCase().includes(query)) ||
        u.role.toLowerCase().includes(query)
    );
    currentPage = 1;
    renderUsersPage(currentPage);
    renderPaginationControls();
});

/* ============================================================
   PROJECTS MANAGEMENT
   ============================================================ */

let allProjects = [];
let filteredProjects = [];
const PROJECTS_PER_PAGE = 10;
let projectsCurrentPage = 1;

async function loadProjects() {
    const res = await fetch(_B + '/api/v1/projects?page=1&size=500', { credentials: 'include' });
    if (res.ok) {
        const data = await res.json();
        allProjects = (data.projects || []).filter(p => String(p.status || '').trim().toLowerCase() !== 'priority');
        filteredProjects = [...allProjects];
        projectsCurrentPage = 1;
        renderProjectsPage(projectsCurrentPage);
        renderProjectsPaginationControls();

        // Update stat chip
        const chip = document.getElementById('ap-project-count');
        if (chip) {
            chip.innerHTML = `<div class="ap-stat-chip"><strong>${allProjects.length}</strong> total non-priority projects</div>`;
        }
    }
}

function renderProjectsPage(page) {
    const tbody = document.getElementById('projectTableBody');
    const startIndex = (page - 1) * PROJECTS_PER_PAGE;
    const endIndex = startIndex + PROJECTS_PER_PAGE;
    const projectsToRender = filteredProjects.slice(startIndex, endIndex);

    if (projectsToRender.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-dim);">No projects found</td></tr>';
        return;
    }

    tbody.innerHTML = projectsToRender.map(p => {
        const dateTimeAdded = new Date(p.created_at).toLocaleString();
        const value = p.project_value ? parseFloat(p.project_value).toLocaleString() : '0';

        return `
        <tr class="clickable-row" onclick="openProjectModal(${p.id}, 'all')">
            <td>${dateTimeAdded}</td>
            <td><span class="source-badge">${p.source || '—'}</span></td>
            <td style="font-weight:500;">${p.contractor_name || '—'}</td>
            <td>${p.contact_person || '—'}</td>
            <td>${p.contact_number || '—'}</td>
            <td>${p.address ? p.address.substring(0, 50) + (p.address.length > 50 ? '...' : '') : '—'}</td>
            <td>${p.project_name || '—'}</td>
            <td style="text-align:right;color:var(--text-dim);">₱${value}</td>
        </tr>
        `;
    }).join('');
}

function renderProjectsPaginationControls() {
    const container = document.getElementById('projectPaginationControls');
    if (!container) return;

    const totalPages = Math.ceil(filteredProjects.length / PROJECTS_PER_PAGE);
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = `<button class="page-btn" ${projectsCurrentPage === 1 ? 'disabled' : ''} onclick="goToProjectsPage(${projectsCurrentPage - 1})">Previous</button>`;

    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="page-btn ${i === projectsCurrentPage ? 'active' : ''}" onclick="goToProjectsPage(${i})">${i}</button>`;
    }

    html += `<button class="page-btn" ${projectsCurrentPage === totalPages ? 'disabled' : ''} onclick="goToProjectsPage(${projectsCurrentPage + 1})">Next</button>`;

    container.innerHTML = html;
}

function goToProjectsPage(page) {
    const totalPages = Math.ceil(filteredProjects.length / PROJECTS_PER_PAGE);
    if (page < 1 || page > totalPages) return;

    projectsCurrentPage = page;
    renderProjectsPage(projectsCurrentPage);
    renderProjectsPaginationControls();
}

function openProjectModal(projectId, source = 'all') {
    const list = source === 'priority' ? allPriorityProjects : allProjects;
    const project = list.find(p => p.id === projectId);
    if (!project) return;

    const modal = document.getElementById('projectModal');
    const title = document.getElementById('projectModalTitle');
    const detailList = document.getElementById('projectDetailList');

    if (!modal || !title || !detailList) return;

    title.textContent = project.project_name || 'Project Details';
    detailList.innerHTML = `
        <div class="detail-row"><span>Date & Time Added</span><strong>${new Date(project.created_at).toLocaleString()}</strong></div>
        <div class="detail-row"><span>Source</span><strong>${project.source || '—'}</strong></div>
        <div class="detail-row"><span>Contractor</span><strong>${project.contractor_name || '—'}</strong></div>
        <div class="detail-row"><span>Contact Person</span><strong>${project.contact_person || '—'}</strong></div>
        <div class="detail-row"><span>Contact Number</span><strong>${project.contact_number || '—'}</strong></div>
        <div class="detail-row"><span>Address</span><strong>${project.address || '—'}</strong></div>
        <div class="detail-row"><span>Project Name</span><strong>${project.project_name || '—'}</strong></div>
        <div class="detail-row"><span>Province</span><strong>${project.city_province || '—'}</strong></div>
        <div class="detail-row"><span>City</span><strong>${project.city_province ? project.city_province.split(',')[0] : '—'}</strong></div>
        <div class="detail-row"><span>Region</span><strong>${project.region || '—'}</strong></div>
        <div class="detail-row"><span>Value</span><strong>₱${project.project_value ? parseFloat(project.project_value).toLocaleString() : '0'}</strong></div>
        <div class="detail-row"><span>Sheet Pile Type</span><strong>${project.sheet_pile_type || '—'}</strong></div>
        <div class="detail-row"><span>Sheet Pile Amount</span><strong>${project.sheet_pile_amount || '—'}</strong></div>
        <div class="detail-row"><span>DRBs</span><strong>${project.drbs || '—'}</strong></div>
        <div class="detail-row"><span>DRBs Value</span><strong>₱${project.drbs_value ? parseFloat(project.drbs_value).toLocaleString() : '0'}</strong></div>
        <div class="detail-row"><span>Accomplishment</span><strong>${project.accomplishment_rate ? project.accomplishment_rate + '%' : '—'}</strong></div>
        <div class="detail-row"><span>Status</span><strong>${project.status || '—'}</strong></div>
        <div class="detail-row"><span>Encoded By</span><strong>${project.encoded_by_user || project.encoded_by || '—'}</strong></div>
        <div class="detail-row"><span>Last Updated</span><strong>${new Date(project.updated_at).toLocaleString()}</strong></div>
    `;
    modal.style.display = 'flex';
}

function closeProjectModal() {
    const modal = document.getElementById('projectModal');
    if (modal) modal.style.display = 'none';
}

/* ============================================================
   PRIORITY PROJECTS MANAGEMENT
   ============================================================ */

let allPriorityProjects = [];
let priorityCurrentPage = 1;

async function loadPriorityProjects() {
    const res = await fetch(_B + '/api/v1/projects/priority?page=1&size=100', { credentials: 'include' });
    if (res.ok) {
        const data = await res.json();
        allPriorityProjects = data.projects || [];
        priorityCurrentPage = 1;
        renderPriorityProjects();

        // Update stat chip
        const chip = document.getElementById('ap-priority-count');
        if (chip) {
            const count = allPriorityProjects.length;
            chip.innerHTML = `<div class="ap-stat-chip"><strong>${count}</strong> priority project${count !== 1 ? 's' : ''}</div>`;
        }

        // Update badge on nav item
        const badge = document.getElementById('priority-badge');
        if (badge && count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'block';
        }
    }
}

function renderPriorityProjects() {
    const tbody = document.getElementById('priorityTableBody');

    if (allPriorityProjects.length === 0) {
        tbody.innerHTML = '<tr><td colspan="26" style="text-align:center;padding:3rem;color:var(--text-dim);font-size:0.95rem;">✓ No priority projects — all systems nominal!</td></tr>';
        return;
    }

    tbody.innerHTML = allPriorityProjects.map(p => {
        const dateTimeAdded = new Date(p.created_at).toLocaleString();
        const formatPHP = (val) => {
            const num = Number(val);
            return !isNaN(num) ? new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(num) : '—';
        };

        const picturesRaw = p.pictures || p.Pictures || '';
        const picturesCount = Array.isArray(picturesRaw)
            ? picturesRaw.length
            : (typeof picturesRaw === 'string' ? picturesRaw.split(',').map(item => item.trim()).filter(Boolean).length : 0);
        const picturesLabel = picturesCount > 0 ? `${picturesCount} image${picturesCount === 1 ? '' : 's'}` : '—';

        const cityProvinceParts = (p.city_province || '').split(',').map(part => part.trim());
        const city = cityProvinceParts[0] || '—';
        const province = cityProvinceParts[1] || '—';

        const value = p.project_value ? formatPHP(p.project_value) : '—';
        const sheetPileAmount = p.sheet_pile_amount ? formatPHP(p.sheet_pile_amount) : '—';
        const drbsValue = p.drbs_value ? formatPHP(p.drbs_value) : '—';
        const accomplishmentRate = p.accomplishment_rate !== null && p.accomplishment_rate !== undefined ? `${p.accomplishment_rate}%` : '—';

        const lookup = (keys) => {
            for (const key of keys) {
                if (p[key] !== undefined && p[key] !== null && String(p[key]).trim() !== '') {
                    return p[key];
                }
            }
            return '—';
        };

        return `
        <tr class="clickable-row" onclick="openProjectModal(${p.id}, 'priority')">
            <td>${dateTimeAdded}</td>
            <td><span class="source-badge">${p.source || '—'}</span></td>
            <td style="font-weight:500;">${p.contractor_name || '—'}</td>
            <td>${p.contact_person || '—'}</td>
            <td>${p.contact_number || '—'}</td>
            <td>${p.address ? p.address.substring(0, 80) + (p.address.length > 80 ? '...' : '') : '—'}</td>
            <td>${picturesLabel}</td>
            <td>${p.project_name || '—'}</td>
            <td>${value}</td>
            <td>${accomplishmentRate}</td>
        </tr>
        `;
    }).join('');
}


/* ============================================================
   SALES REP RANKINGS
   ============================================================ */

async function loadSalesRepRankings() {
    const periodFilter = document.getElementById('salesRepPeriodFilter');
    const regionFilter = document.getElementById('salesRepRegionFilter');
    const tbody = document.getElementById('salesRepRankingBody');

    if (!tbody) return;

    const period = periodFilter ? periodFilter.value : 'monthly';
    const region = regionFilter ? regionFilter.value : '';

    try {
        let url = _B + `/api/v1/users/sales-reps-ranking?period=${period}&limit=10`;
        if (region) {
            url += `&region=${encodeURIComponent(region)}`;
        }

        const res = await fetch(url, { credentials: 'include' });
        
        if (!res.ok) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-danger);">Failed to load rankings</td></tr>';
            return;
        }

        const data = await res.json();
        const rankings = data.rankings || [];

        if (rankings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-dim);">No sales representatives found with projects in this period</td></tr>';
            return;
        }

        tbody.innerHTML = rankings.map((rep, index) => {
            const rank = index + 1;
            const rankBadge = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : rank;
            const value = rep.total_value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const lastProjectDate = rep.last_project_date 
                ? new Date(rep.last_project_date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
                : '—';

            return `
            <tr>
                <td style="text-align:center;font-size:1.2rem;font-weight:700;">${rankBadge}</td>
                <td style="font-weight:500;">${rep.full_name || rep.email}</td>
                <td>${rep.branch || '—'}</td>
                <td style="text-align:center;font-weight:600;color:var(--accent-primary);">${rep.projects_count}</td>
                <td style="text-align:right;color:var(--text-dim);">₱${value}</td>
                <td style="color:var(--text-dim);font-size:0.85rem;">${lastProjectDate}</td>
            </tr>
            `;
        }).join('');

    } catch (err) {
        console.error('Error loading sales rep rankings:', err);
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-danger);">Error loading rankings</td></tr>';
    }
}

// Add event listeners for filters
document.addEventListener('DOMContentLoaded', () => {
    const periodFilter = document.getElementById('salesRepPeriodFilter');
    const regionFilter = document.getElementById('salesRepRegionFilter');

    if (periodFilter) {
        periodFilter.addEventListener('change', loadSalesRepRankings);
    }

    if (regionFilter) {
        regionFilter.addEventListener('change', loadSalesRepRankings);
    }
});
