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
    usersListEl.innerHTML = '<div style="text-align:center; padding:2rem;">Loading...</div>';
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
        usersListEl.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-secondary);">Failed to load users</div>';
        if (typeof Toast !== 'undefined') Toast.error('Failed to load users');
    }
}

function renderUsers() {
    if (!filteredUsers.length) {
        usersListEl.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-secondary);">No users found</div>';
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

    let html = '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem;">';

    sortedRoles.forEach(role => {
        const group = usersByRole[role];
        const count = group.length;
        const label = roleNames[role] || ROLE_LABELS.unknown;

        html += `
            <div class="branch-card" onclick="toggleRoleExpand('${escapeHtml(role).replace(/'/g, "\\'")}')" style="cursor:pointer;">
                <div class="branch-card-header">
                    <h2 style="margin:0; font-size:1.25rem; font-weight:700; color:var(--text-primary);">${escapeHtml(label)}</h2>
                    <span class="badge badge-info" style="font-size:0.75rem;">${count} ${count === 1 ? 'USER' : 'USERS'}</span>
                </div>
                <div class="branch-stats">
                    <div class="branch-stat-item">
                        <div class="branch-stat-label">Users</div>
                        <div class="branch-stat-value">${count}</div>
                    </div>
                    <div class="branch-stat-item">
                        <div class="branch-stat-label">Created</div>
                        <div class="branch-stat-value">${formatDate(group[0].created_at)}</div>
                    </div>
                    <div class="branch-stat-item">
                        <div class="branch-stat-label">Last updated</div>
                        <div class="branch-stat-value">${formatDate(group[group.length - 1].created_at)}</div>
                    </div>
                </div>
                <div class="branch-expand-indicator">
                    <span style="font-size:0.875rem; color:var(--text-secondary);">Click to view users</span>
                    <span style="font-size:1.25rem; color:var(--orange-500);">▼</span>
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
        <div style="background: rgba(255, 128, 0, 0.05); border: 2px solid var(--orange-500); border-radius: 12px; padding: 1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; gap:1rem; flex-wrap:wrap;">
                <div>
                    <h2 style="margin:0 0 0.25rem; font-size:1.5rem; font-weight:700; color:var(--text-primary);">${escapeHtml(roleLabel)}</h2>
                    <p style="margin:0; color:var(--text-secondary); font-size:0.875rem;">${usersInRole.length} ${usersInRole.length === 1 ? 'user' : 'users'} in this role</p>
                </div>
                <button onclick="toggleRoleExpand('${escapeHtml(roleName).replace(/'/g, "\\'")}'); event.stopPropagation();" 
                        style="padding:0.5rem 1rem; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); border-radius:8px; color:var(--text-primary); cursor:pointer; font-size:0.875rem; font-weight:600;">
                    Close ✕
                </button>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem;">
                ${usersInRole.map(u => renderUserCard(u)).join('')}
            </div>
        </div>
    `;

    expandedSection.innerHTML = html;
    expandedSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};

function renderUserCard(u) {
    const initials = u.full_name ? u.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
    return `
        <div class="sales-rep-card" style="cursor:default;">
            <div style="display:flex; align-items:flex-start; gap:1rem; margin-bottom:1rem;">
                <div style="width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg, var(--orange-500), var(--orange-600)); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem; color:#000; flex-shrink:0;">
                    ${escapeHtml(initials)}
                </div>
                <div style="flex:1; min-width:0;">
                    <h3 style="margin:0 0 0.25rem; font-size:1rem; font-weight:600; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(u.full_name)}</h3>
                    <p style="margin:0; font-size:0.8rem; color:var(--text-secondary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(u.email)}</p>
                </div>
            </div>
            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.8rem; color:var(--text-secondary);">Role</span>
                    <span class="badge badge-secondary" style="text-transform:none;">${escapeHtml(ROLE_LABELS[u.role] || ROLE_LABELS.unknown)}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.8rem; color:var(--text-secondary);">Created</span>
                    <span style="font-size:0.8rem; color:var(--text-muted);">${formatDate(u.created_at)}</span>
                </div>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.5rem;">
                    <button class="btn-outline" style="flex:1; min-width:96px;" onclick="viewUser(${u.id}); event.stopPropagation();">View</button>
                    <button class="btn-primary" style="flex:1; min-width:96px;" onclick="editUser(${u.id}); event.stopPropagation();">Edit</button>
                    <button class="btn-danger" style="flex:1; min-width:96px;" onclick="deleteUserConfirm(${u.id}); event.stopPropagation();">Delete</button>
                </div>
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
    document.getElementById('userPassword').value = '';
    document.getElementById('userPasswordConfirm').value = '';
    
    // Show/hide password fields based on edit vs create
    const passwordGroup = document.getElementById('passwordGroup');
    const passwordGroupConfirm = document.getElementById('passwordGroupConfirm');
    const passwordHint = document.querySelector('#passwordGroup .form-hint');
    
    if (user) {
        // Editing existing user - password is optional
        passwordHint.textContent = 'Leave blank to keep current password. Minimum 8 characters if changing.';
        document.getElementById('userPassword').required = false;
    } else {
        // Creating new user - password is required
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

function deleteUserConfirm(id) {
    const u = users.find(x => x.id === id);
    if (!u) return;
    if (!confirm(`Delete user ${u.full_name}? This action cannot be undone.`)) return;
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

// Init on DOM ready
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initUsersPage); else initUsersPage();
