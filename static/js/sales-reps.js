/* ============================================================
   Sales Representatives Management
   ============================================================ */

const API_BASE = (typeof BASE !== 'undefined' ? BASE : '') + '/api/v1';

let currentEditId = null;
let salesReps = [];
let filteredReps = [];

const searchInput = document.getElementById('searchInput');
const branchesContainer = document.getElementById('branchesContainer');
const salesRepModal = document.getElementById('salesRepModal');
const salesRepForm = document.getElementById('salesRepForm');
const closeModal = document.getElementById('closeModal');
const modalTitle = document.getElementById('modalTitle');
const formError = document.getElementById('formError');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadSalesReps();
    setupEventListeners();
});

function setupEventListeners() {
    const addBtn = document.getElementById('addSalesRepBtn');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    if (addBtn) addBtn.addEventListener('click', () => openModal());
    if (cancelBtn) cancelBtn.addEventListener('click', closeModalHandler);
    if (saveBtn) saveBtn.addEventListener('click', handleSubmit);
    if (salesRepForm) salesRepForm.addEventListener('submit', (e) => { e.preventDefault(); handleSubmit(); });
    if (closeModal) closeModal.addEventListener('click', closeModalHandler);
    if (searchInput) searchInput.addEventListener('input', handleSearch);
    
    // Password toggle
    const togglePwd = document.getElementById('togglePwd');
    if (togglePwd) {
        togglePwd.addEventListener('click', () => {
            const pwdInput = document.getElementById('password');
            const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
            pwdInput.setAttribute('type', type);
            togglePwd.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }
    
    if (salesRepModal) salesRepModal.addEventListener('click', (e) => {
        if (e.target === salesRepModal) closeModalHandler();
    });
}

// Load Sales Reps
async function loadSalesReps() {
    try {
        const response = await fetch(`${API_BASE}/users/sales-reps`, { credentials: 'include' });
        const data = await response.json();
        
        if (response.ok && data.success) {
            salesReps = data.data;
            filteredReps = salesReps;
            renderSalesReps();
        } else {
            console.error('Sales reps API error:', response.status, data);
            showError('Failed to load sales representatives');
        }
    } catch (error) {
        console.error('Error loading sales reps:', error);
        showError('Error loading sales representatives');
    }
}

// Render Branch Cards
function renderSalesReps() {
    if (!branchesContainer) return;
    
    if (filteredReps.length === 0) {
        branchesContainer.innerHTML = `
            <div class="sr-empty">
                <div class="sr-empty-icon">👤</div>
                <h3>No Sales Representatives Found</h3>
                <p>Try adjusting your search or add a new sales rep</p>
            </div>
        `;
        return;
    }
    
    const repsByBranch = {};
    filteredReps.forEach(rep => {
        const branch = rep.branch || 'Unassigned';
        if (!repsByBranch[branch]) repsByBranch[branch] = [];
        repsByBranch[branch].push(rep);
    });
    
    const sortedBranches = Object.keys(repsByBranch).sort();
    let html = '<div class="sr-grid">';
    
    sortedBranches.forEach(branch => {
        const reps = repsByBranch[branch];
        const repCount = reps.length;
        const safeBranch = escapeHtml(branch).replace(/'/g, "\\'");
        
        html += `
            <div class="sr-branch" onclick="toggleBranchExpand('${safeBranch}')">
                <div class="sr-branch-name">
                    <span>${escapeHtml(branch)}</span>
                    <span class="sr-badge">${repCount} ${repCount === 1 ? 'REP' : 'REPS'}</span>
                </div>
                <div class="sr-stats">
                    <div class="sr-stat">
                        <div class="sr-stat-label">Sales Reps</div>
                        <div class="sr-stat-value">${repCount}</div>
                    </div>
                    <div class="sr-stat">
                        <div class="sr-stat-label">Assigned</div>
                        <div class="sr-stat-value">0</div>
                    </div>
                    <div class="sr-stat">
                        <div class="sr-stat-label">Processed</div>
                        <div class="sr-stat-value">0</div>
                    </div>
                </div>
                <div class="sr-hint">
                    <span>Click to view sales reps</span>
                    <span class="sr-arrow">▼</span>
                </div>
            </div>
        `;
    });
    
    html += '</div><div id="expandedBranchSection" style="display:none; margin-top:2rem;"></div>';
    branchesContainer.innerHTML = html;
}

// Toggle branch expansion
window.toggleBranchExpand = function(branchName) {
    const expandedSection = document.getElementById('expandedBranchSection');
    const reps = filteredReps.filter(rep => (rep.branch || 'Unassigned') === branchName);
    
    if (expandedSection.dataset.currentBranch === branchName && expandedSection.style.display !== 'none') {
        expandedSection.style.display = 'none';
        expandedSection.dataset.currentBranch = '';
        return;
    }
    
    expandedSection.dataset.currentBranch = branchName;
    expandedSection.style.display = 'block';
    
    const safeBranch = escapeHtml(branchName).replace(/'/g, "\\'");
    
    let html = `
        <div class="sr-expanded">
            <div class="sr-expanded-header">
                <div>
                    <h2>${escapeHtml(branchName)}</h2>
                    <p>${reps.length} Sales ${reps.length === 1 ? 'Representative' : 'Representatives'}</p>
                </div>
                <button class="btn btn-secondary btn-sm" onclick="toggleBranchExpand('${safeBranch}'); event.stopPropagation();">Close ✕</button>
            </div>
            <div class="sr-cards-grid">
                ${reps.map(rep => renderSalesRepCard(rep)).join('')}
            </div>
        </div>
    `;
    
    expandedSection.innerHTML = html;
    expandedSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};

// Render individual Sales Rep card
function renderSalesRepCard(rep) {
    const initials = rep.full_name ? rep.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
    const repData = encodeURIComponent(JSON.stringify(rep));
    return `
        <div class="sr-card" data-rep-id="${rep.id}" data-rep-data="${repData}" onclick="handleCardClick(this); event.stopPropagation();">
            <div style="display:flex; align-items:flex-start; gap:1rem; margin-bottom:1rem;">
                <div class="avatar">${escapeHtml(initials)}</div>
                <div style="flex:1; min-width:0;">
                    <h3>${escapeHtml(rep.full_name)}</h3>
                    <p class="email-label">${escapeHtml(rep.email)}</p>
                </div>
            </div>
            <div class="info-row">
                <span class="info-label">Branch</span>
                <span class="info-value"><span class="branch-pill">${escapeHtml(rep.branch || 'Unassigned')}</span></span>
            </div>
            <div class="info-row">
                <span class="info-label">Contact</span>
                <span class="info-value">${rep.contact_number ? escapeHtml(rep.contact_number) : '<span style="color:var(--text-muted);">—</span>'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Created</span>
                <span class="info-value" style="color:var(--text-muted);font-weight:400;font-size:0.78rem;">${formatDate(rep.created_at)}</span>
            </div>
            <div class="sr-footer">
                <button class="btn btn-primary btn-sm" onclick="handleEditClick(this); event.stopPropagation();">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); promptDelete(${rep.id}, '${escapeHtml(rep.full_name)}')">Delete</button>
            </div>
        </div>
    `;
}

// Search
function handleSearch(e) {
    const query = e.target.value.toLowerCase();
    filteredReps = salesReps.filter(rep => 
        rep.email.toLowerCase().includes(query) ||
        rep.full_name.toLowerCase().includes(query) ||
        (rep.branch && rep.branch.toLowerCase().includes(query))
    );
    renderSalesReps();
}

// Open Modal
function openModal(rep = null) {
    currentEditId = rep ? rep.id : null;
    
    salesRepForm.reset();
    document.getElementById('editId').value = rep ? rep.id : '';
    document.getElementById('fullName').value = rep ? rep.full_name : '';
    document.getElementById('email').value = rep ? rep.email : '';
    document.getElementById('branch').value = rep ? (rep.branch || '') : '';
    document.getElementById('contactNumber').value = rep ? (rep.contact_number || '') : '';
    
    if (rep) {
        modalTitle.textContent = 'Edit Sales Representative';
        document.getElementById('password').removeAttribute('required');
        document.querySelector('#passwordGroup small').textContent = 'Leave blank to keep current password';
    } else {
        modalTitle.textContent = 'Add Sales Representative';
        document.getElementById('password').setAttribute('required', 'required');
        document.querySelector('#passwordGroup small').textContent = 'Minimum 8 characters';
    }
    
    formError.style.display = 'none';
    salesRepModal.classList.add('active');
}

function closeModalHandler() {
    salesRepModal.classList.remove('active');
    salesRepForm.reset();
    currentEditId = null;
}

// Handle Form Submit
async function handleSubmit() {
    const fullName = document.getElementById('fullName').value.trim();
    const email = document.getElementById('email').value.trim();
    const branch = document.getElementById('branch').value;
    const password = document.getElementById('password').value;
    const contactNumber = document.getElementById('contactNumber').value.trim();
    
    if (!fullName || !email || !branch) {
        showFormError('Full Name, Email, and Branch are required');
        return;
    }
    
    if (!currentEditId && password.length < 8) {
        showFormError('Password must be at least 8 characters');
        return;
    }
    
    const payload = { full_name: fullName, email, branch };
    if (password) payload.password = password;
    if (contactNumber) payload.contact_number = contactNumber;
    
    try {
        const url = currentEditId ? `${API_BASE}/users/sales-reps/${currentEditId}` : `${API_BASE}/users/sales-reps`;
        const method = currentEditId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        if (response.ok && result.success) {
            closeModalHandler();
            await loadSalesReps();
            showSuccess(currentEditId ? 'Sales rep updated' : 'Sales rep created');
        } else {
            showFormError(result.message || 'Operation failed');
        }
    } catch (error) {
        console.error('Error:', error);
        showFormError('An error occurred');
    }
}

// Delete
async function promptDelete(id, name) {
    const confirmed = await ModalSystem.confirm({
        title: 'Delete Sales Representative',
        message: `Delete ${name}? This action cannot be undone.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        type: 'danger'
    });
    
    if (confirmed) {
        deleteSalesRep(id);
    }
}

async function deleteSalesRep(id) {
    try {
        const response = await fetch(`${API_BASE}/users/sales-reps/${id}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            loadSalesReps();
            showSuccess('Sales rep deleted');
        } else {
            showError(result.message || 'Failed to delete');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('An error occurred');
    }
}

function showFormError(message) {
    formError.textContent = message;
    formError.style.display = 'block';
    setTimeout(() => formError.style.display = 'none', 5000);
}

// Helper functions for card and button clicks
function handleCardClick(cardElement) {
    const repData = cardElement.dataset.repData;
    if (repData) {
        try {
            const rep = JSON.parse(decodeURIComponent(repData));
            openModal(rep);
        } catch (error) {
            console.error('Error parsing rep data:', error);
            showError('Error loading sales rep data');
        }
    }
}

function handleEditClick(buttonElement) {
    const card = buttonElement.closest('.sr-card');
    if (card) {
        handleCardClick(card);
    }
}

function showError(message) { 
    if (typeof ModalSystem !== 'undefined') {
        ModalSystem.error(message);
    } else {
        console.error(message);
    }
}

function showSuccess(message) { 
    if (typeof ModalSystem !== 'undefined') {
        ModalSystem.success(message);
    } else {
        console.log(message);
    }
}

function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
function formatDate(s) { if (!s) return '—'; return new Date(s).toLocaleDateString(); }
