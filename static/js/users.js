/* ============================================================
   Minimal User Management client for pages/users.php
   Supports: list users, search, create, edit, delete via /api/users
   ============================================================ */

// The users API is under /api/v1/users (returns raw array for GET)
const USERS_API = (typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/users';

const ROLE_LABELS = {
    superadmin: 'Superadmin',
    admin: 'Admin',
    encoder: 'Encoder',
    unknown: 'Unknown'
};

const ROLE_ORDER = ['superadmin', 'admin', 'encoder', 'unknown'];

let users = [];
let filteredUsers = [];

// DOM
const usersListEl = document.getElementById('usersList');
const userSearch = document.getElementById('userSearch');
const addUserBtn = document.getElementById('addUserBtn');
const userModal = document.getElementById('userModal');
const closeUserModal = document.getElementById('closeUserModal');
const cancelUserBtn = document.getElementById('cancelUserBtn');
const saveUserBtn = document.getElementById('saveUserBtn');
const userForm = document.getElementById('userForm');
const userModalTitle = document.getElementById('userModalTitle');

function initUsersPage() {
    if (!usersListEl) return;
    loadUsers();
    setupListeners();
}

function setupListeners() {
    userSearch.addEventListener('input', onSearch);
    addUserBtn.addEventListener('click', () => openUserModal());
    closeUserModal.addEventListener('click', closeUserModalHandler);
    cancelUserBtn.addEventListener('click', closeUserModalHandler);
    saveUserBtn.addEventListener('click', onSaveUser);
    userForm.addEventListener('submit', (e) => { e.preventDefault(); onSaveUser(); });
    userModal.addEventListener('click', (e) => { if (e.target === userModal) closeUserModalHandler(); });
    
    // Password toggle listeners
    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    
    if (togglePassword) {
        togglePassword.addEventListener('click', () => {
            const passwordInput = document.getElementById('userPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }
    
    if (togglePasswordConfirm) {
        togglePasswordConfirm.addEventListener('click', () => {
            const passwordInput = document.getElementById('userPasswordConfirm');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePasswordConfirm.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }
}

async function loadUsers() {
    usersListEl.innerHTML = `
        <div class="um-loading">
            <div class="um-spinner"></div>
            <p>Loading users...</p>
        </div>
    `;
    try {
        const res = await fetch(USERS_API, { credentials: 'include' });
        if (!res.ok) throw new Error('Failed to load users');
        const data = await res.json();
        // API returns an array of users directly on success
        if (Array.isArray(data)) {
            users = data;
        } else if (Array.isArray(data.data)) {
            users = data.data;
        } else {
            throw new Error('Invalid response format from server');
        }
        
        // Filter out sales_rep users - they should only appear in Sales Representatives page
        users = users.filter(user => user.role !== 'sales_rep');
        filteredUsers = users;
        renderUsers();
    } catch (err) {
        console.error('loadUsers error', err);
        usersListEl.innerHTML = `
            <div class="um-empty">
                <div class="um-empty-icon">👥</div>
                <h3>Failed to load users</h3>
                <p>Please refresh the page and try again</p>
            </div>
        `;
        if (typeof Toast !== 'undefined') Toast.error('Failed to load users');
    }
}

function renderUsers() {
    if (!filteredUsers.length) {
        usersListEl.innerHTML = `
            <div class="um-empty">
                <div class="um-empty-icon">👥</div>
                <h3>No Users Found</h3>
                <p>Try adjusting your search or create a new user</p>
            </div>
        `;
        return;
    }

    const roleNames = ROLE_LABELS;
    const usersByRole = {};

    filteredUsers.forEach(u => {
        const role = u.role || 'unknown';
        if (!usersByRole[role]) {
            usersByRole[role] = [];
        }
        usersByRole[role].push(u);
    });

    const sortedRoles = Object.keys(usersByRole)
        .sort((a, b) => ROLE_ORDER.indexOf(a) - ROLE_ORDER.indexOf(b));

    let html = '<div class="um-roles-grid">';

    sortedRoles.forEach(role => {
        const group = usersByRole[role];
        const count = group.length;
        const label = roleNames[role] || ROLE_LABELS.unknown;
        const safeRole = escapeHtml(role).replace(/'/g, "\\'");

        html += `
            <div class="role-group-card" onclick="toggleRoleExpand('${safeRole}')">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <h3>${escapeHtml(label)}</h3>
                    <span class="user-count">${count} ${count === 1 ? 'USER' : 'USERS'}</span>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">Users</div>
                        <div class="stat-value">${count}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Created</div>
                        <div class="stat-value">${formatDate(group[0].created_at)}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Latest</div>
                        <div class="stat-value">${formatDate(group[group.length - 1].created_at)}</div>
                    </div>
                </div>
                <div class="expand-hint">
                    <span>Click to view users</span>
                    <span class="arrow">▼</span>
                </div>
            </div>
        `;
    });

    html += '</div>';
    html += '<div id="expandedRoleSection" style="display:none; margin-top:2rem;"></div>';

    usersListEl.innerHTML = html;
}

window.toggleRoleExpand = function(roleName) {
    const expandedSection = document.getElementById('expandedRoleSection');
    const roleLabelMap = {
        superadmin: 'Superadmin',
        admin: 'Admin',
        encoder: 'Encoder',
        unknown: 'Unknown'
    };
    const roleLabel = roleLabelMap[roleName] || roleName;
    const usersInRole = filteredUsers.filter(u => (u.role || 'unknown') === roleName);

    if (expandedSection.dataset.currentRole === roleName && expandedSection.style.display !== 'none') {
        expandedSection.style.display = 'none';
        expandedSection.dataset.currentRole = '';
        return;
    }

    expandedSection.dataset.currentRole = roleName;
    expandedSection.style.display = 'block';

    let html = `
        <div class="expanded-section">
            <div class="expanded-header">
                <div>
                    <h2>${escapeHtml(roleLabel)}</h2>
                    <p>${usersInRole.length} ${usersInRole.length === 1 ? 'user' : 'users'} in this role</p>
                </div>
                <button class="close-btn" onclick="toggleRoleExpand('${escapeHtml(roleName).replace(/'/g, "\\'")}'); event.stopPropagation();">
                    Close ✕
                </button>
            </div>
            <div class="users-grid">
                ${usersInRole.map(u => renderUserCard(u)).join('')}
            </div>
        </div>
    `;

    expandedSection.innerHTML = html;
    expandedSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};

function renderUserCard(u) {
    const initials = u.full_name ? u.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
    const roleBadgeClass = 'badge badge-role-' + (u.role || 'unknown');

    return `
        <div class="user-card">
            <div style="display:flex; align-items:flex-start; gap:1rem; margin-bottom:1rem;">
                <div class="avatar">${escapeHtml(initials)}</div>
                <div style="flex:1; min-width:0;">
                    <h3>${escapeHtml(u.full_name)}</h3>
                    <p class="email">${escapeHtml(u.email)}</p>
                </div>
            </div>
            <div class="meta-row">
                <span class="meta-label">Role</span>
                <span class="meta-value"><span class="${roleBadgeClass}">${escapeHtml(ROLE_LABELS[u.role] || ROLE_LABELS.unknown)}</span></span>
            </div>
            ${u.branch ? `
            <div class="meta-row">
                <span class="meta-label">Branch</span>
                <span class="meta-value"><span class="branch-tag">${escapeHtml(u.branch)}</span></span>
            </div>` : ''}
            <div class="meta-row">
                <span class="meta-label">Created</span>
                <span class="meta-value" style="color:var(--text-muted);font-weight:400;font-size:0.78rem;">${formatDate(u.created_at)}</span>
            </div>
            <div class="card-actions">
                <button class="btn btn-secondary btn-sm" onclick="viewUser(${u.id}); event.stopPropagation();">View</button>
                <button class="btn btn-primary btn-sm" onclick="editUser(${u.id}); event.stopPropagation();">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteUserConfirm(${u.id}); event.stopPropagation();">Delete</button>
            </div>
        </div>
    `;
}

function onSearch(e) {
    const q = (e.target.value || '').toLowerCase().trim();
    if (!q) {
        filteredUsers = users; // users array already has sales_rep filtered out
    } else {
        filteredUsers = users.filter(u =>
            (u.full_name || '').toLowerCase().includes(q) ||
            (u.email || '').toLowerCase().includes(q) ||
            (u.role || '').toLowerCase().includes(q)
        );
    }
    renderUsers();
}

function openUserModal(user = null) {
    userForm.reset();
    document.getElementById('userId').value = user ? user.id : '';
    document.getElementById('userEmail').value = user ? user.email : '';
    document.getElementById('userFullName').value = user ? user.full_name : '';
    document.getElementById('userRole').value = user ? user.role : 'encoder';
    document.getElementById('userBranch').value = user ? (user.branch || '') : '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userPasswordConfirm').value = '';

    toggleBranchField();
    
    // Show/hide password fields based on edit vs create
    const passwordHint = document.querySelector('#passwordGroup .form-hint');
    
    if (user) {
        passwordHint.textContent = 'Leave blank to keep current password. Minimum 8 characters if changing.';
        document.getElementById('userPassword').required = false;
    } else {
        passwordHint.textContent = 'Minimum 8 characters for new accounts';
        document.getElementById('userPassword').required = true;
    }
    
    userModalTitle.textContent = user ? 'Edit User' : 'Create User';
    userModal.classList.add('active');
}

function closeUserModalHandler() {
    userModal.classList.remove('active');
}

function viewUser(id) {
    const u = users.find(x => x.id === id);
    if (!u) return;
    openUserModal(u);
}

function editUser(id) {
    const u = users.find(x => x.id === id);
    if (!u) return;
    openUserModal(u);
}

async function onSaveUser() {
    const id = document.getElementById('userId').value || null;
    const payload = {
        email: document.getElementById('userEmail').value.trim(),
        full_name: document.getElementById('userFullName').value.trim(),
        role: document.getElementById('userRole').value,
        branch: document.getElementById('userBranch')?.value || null,
    };
    
    const pwd = document.getElementById('userPassword').value;
    const confirmPwd = document.getElementById('userPasswordConfirm').value;
    
    // Password validation for new users
    if (!id && !pwd) {
        if (typeof Toast !== 'undefined') Toast.error('Password is required for new users');
        return;
    }
    
    // Password confirmation validation
    if (pwd && pwd !== confirmPwd) {
        if (typeof Toast !== 'undefined') Toast.error('Passwords do not match');
        return;
    }
    
    // Add password only if provided
    if (pwd) payload.password = pwd;

    if (!payload.email || !payload.full_name) {
        if (typeof Toast !== 'undefined') Toast.error('Email and full name are required');
        return;
    }

    try {
        let url, method;
        if (id) {
            // Edit existing user - use POST with id parameter
            url = `${USERS_API}?id=${id}`;
            method = 'POST';
        } else {
            // Create new user
            url = USERS_API;
            method = 'POST';
        }
        
        const res = await fetch(url, {
            method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.detail || data.message || 'Operation failed');
        }
        closeUserModalHandler();
        if (typeof Toast !== 'undefined') Toast.success(data.message || (id ? 'User updated' : 'User created'));
        await loadUsers();
    } catch (err) {
        console.error('saveUser error', err);
        if (typeof Toast !== 'undefined') Toast.error(err.message || 'Failed to save user');
    }
}

async function deleteUserConfirm(id) {
    const u = users.find(x => x.id === id);
    if (!u) return;
    const confirmed = await ModalSystem.confirm({
        title: 'Delete User',
        message: `Delete user ${u.full_name}? This action cannot be undone.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        type: 'danger'
    });
    if (!confirmed) return;
    deleteUser(id);
}

async function deleteUser(id) {
    try {
        const res = await fetch(`${USERS_API}?id=${id}`, { method: 'DELETE', credentials: 'include' });
        const data = await res.json();
        if (!res.ok) throw new Error(data.detail || data.message || 'Delete failed');
        if (typeof Toast !== 'undefined') Toast.success(data.message || 'User deleted');
        await loadUsers();
    } catch (err) {
        console.error('deleteUser error', err);
        if (typeof Toast !== 'undefined') Toast.error(err.message || 'Failed to delete user');
    }
}

function escapeHtml(text) { const d = document.createElement('div'); d.textContent = text || ''; return d.innerHTML; }
function formatDate(s) { if (!s) return '—'; const d = new Date(s); return d.toLocaleDateString(); }

window.toggleBranchField = function() {
    const role = document.getElementById('userRole')?.value;
    const group = document.getElementById('branchGroup');
    if (group) group.style.display = role === 'admin' ? 'block' : 'none';
};

// Init on DOM ready
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initUsersPage); else initUsersPage();
