/* ============================================================
   Sales Representatives Management
   ============================================================ */

// Note: this API endpoint lives under /api/v1, matching the central API router.
const API_BASE = (typeof BASE !== 'undefined' ? BASE : '') + '/api/v1';

// State
let currentEditId = null;
let salesReps = [];
let filteredReps = [];

// DOM Elements
const searchInput = document.getElementById('searchInput');
const addSalesRepBtn = document.getElementById('addSalesRepBtn');
const salesRepModal = document.getElementById('salesRepModal');
const salesRepForm = document.getElementById('salesRepForm');
const closeModal = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn');
const modalTitle = document.getElementById('modalTitle');
const submitBtn = document.getElementById('submitBtn');
const submitText = document.getElementById('submitText');
const submitLoader = document.getElementById('submitLoader');
const formError = document.getElementById('formError');
const deleteModal = document.getElementById('deleteModal');
const closeDeleteModal = document.getElementById('closeDeleteModal');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const deleteUserName = document.getElementById('deleteUserName');
const deleteText = document.getElementById('deleteText');
const deleteLoader = document.getElementById('deleteLoader');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadSalesReps();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    addSalesRepBtn.addEventListener('click', () => openModal());
    closeModal.addEventListener('click', () => closeModalHandler());
    cancelBtn.addEventListener('click', () => closeModalHandler());
    salesRepForm.addEventListener('submit', handleSubmit);
    searchInput.addEventListener('input', handleSearch);
    
    // Delete modal
    closeDeleteModal.addEventListener('click', () => closeDeleteModalHandler());
    cancelDeleteBtn.addEventListener('click', () => closeDeleteModalHandler());
    
    // Close modals on overlay click
    salesRepModal.addEventListener('click', (e) => {
        if (e.target === salesRepModal) closeModalHandler();
    });
    deleteModal.addEventListener('click', (e) => {
        if (e.target === deleteModal) closeDeleteModalHandler();
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

// Render Sales Reps as Branch Cards with Overview
function renderSalesReps() {
    const container = document.getElementById('salesRepsContainer');
    
    if (filteredReps.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                <h3 style="margin: 0 0 0.5rem;">No sales representatives found</h3>
                <p style="margin: 0; font-size: 0.875rem;">Try adjusting your search</p>
            </div>
        `;
        return;
    }
    
    // Group sales reps by branch
    const repsByBranch = {};
    filteredReps.forEach(rep => {
        const branch = rep.branch || 'Unassigned';
        if (!repsByBranch[branch]) {
            repsByBranch[branch] = [];
        }
        repsByBranch[branch].push(rep);
    });
    
    // Sort branches alphabetically
    const sortedBranches = Object.keys(repsByBranch).sort();
    
    // Render branch cards
    let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">';
    
    sortedBranches.forEach(branch => {
        const reps = repsByBranch[branch];
        const repCount = reps.length;
        
        // TODO: Calculate actual project counts from API
        const assignedProjects = 0;
        const processedProjects = 0;
        
        html += `
            <div class="branch-card" onclick="toggleBranchExpand('${escapeHtml(branch).replace(/'/g, "\\'")}')">
                <div class="branch-card-header">
                    <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-primary);">${escapeHtml(branch)}</h2>
                    <span class="badge badge-info" style="font-size: 0.75rem;">${repCount} ${repCount === 1 ? 'REP' : 'REPS'}</span>
                </div>
                
                <div class="branch-stats">
                    <div class="branch-stat-item">
                        <div class="branch-stat-label">Sales Representatives</div>
                        <div class="branch-stat-value">${repCount}</div>
                    </div>
                    <div class="branch-stat-item">
                        <div class="branch-stat-label">Assigned Projects</div>
                        <div class="branch-stat-value">${assignedProjects}</div>
                    </div>
                    <div class="branch-stat-item">
                        <div class="branch-stat-label">Processed Projects</div>
                        <div class="branch-stat-value">${processedProjects}</div>
                    </div>
                </div>
                
                <div class="branch-expand-indicator">
                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Click to view sales reps</span>
                    <span style="font-size: 1.25rem; color: var(--orange-500);">▼</span>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    
    // Add expanded branch section (hidden by default)
    html += '<div id="expandedBranchSection" style="display: none; margin-top: 2rem;"></div>';
    
    container.innerHTML = html;
}

// Toggle branch expansion to show sales reps
window.toggleBranchExpand = function(branchName) {
    const expandedSection = document.getElementById('expandedBranchSection');
    const reps = filteredReps.filter(rep => (rep.branch || 'Unassigned') === branchName);
    
    // If clicking the same branch, collapse it
    if (expandedSection.dataset.currentBranch === branchName && expandedSection.style.display !== 'none') {
        expandedSection.style.display = 'none';
        expandedSection.dataset.currentBranch = '';
        return;
    }
    
    // Show the expanded section with sales rep cards
    expandedSection.dataset.currentBranch = branchName;
    expandedSection.style.display = 'block';
    
    let html = `
        <div style="background: rgba(255, 128, 0, 0.05); border: 2px solid var(--orange-500); border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="margin: 0 0 0.25rem; font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">${escapeHtml(branchName)}</h2>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">${reps.length} Sales ${reps.length === 1 ? 'Representative' : 'Representatives'}</p>
                </div>
                <button onclick="toggleBranchExpand('${escapeHtml(branchName).replace(/'/g, "\\'")}'); event.stopPropagation();" 
                        style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); 
                               border-radius: 8px; color: var(--text-primary); cursor: pointer; font-size: 0.875rem; font-weight: 600;">
                    Close ✕
                </button>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                ${reps.map(rep => renderSalesRepCard(rep)).join('')}
            </div>
        </div>
    `;
    
    expandedSection.innerHTML = html;
    
    // Scroll to expanded section
    expandedSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};

// Render individual sales rep card
function renderSalesRepCard(rep) {
    const isOnline = rep.is_online || false;
    const statusBadge = isOnline 
        ? '<span class="badge badge-success">Online</span>'
        : '<span class="badge badge-secondary">Offline</span>';
    
    const remainingProjects = 0; // TODO: Get actual count from API
    
    const initials = rep.full_name ? rep.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
    
    return `
        <div class="sales-rep-card" onclick="viewSalesRep(${rep.id}); event.stopPropagation();" style="cursor: pointer;">
            <div style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--orange-500), var(--orange-600)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; color: #000; flex-shrink: 0;">
                    ${initials}
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h3 style="margin: 0 0 0.25rem; font-size: 1rem; font-weight: 600; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(rep.full_name)}</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--text-secondary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(rep.email)}</p>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; color: var(--text-secondary);">Status</span>
                    ${statusBadge}
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; color: var(--text-secondary);">Projects</span>
                    <span class="badge badge-info">${remainingProjects}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; color: var(--text-secondary);">Created</span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">${formatDate(rep.created_at)}</span>
                </div>
            </div>
        </div>
    `;
}

// Search Handler
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
    
    if (rep) {
        // View/Edit mode
        modalTitle.textContent = 'Sales Representative Details';
        document.getElementById('salesRepId').value = rep.id;
        document.getElementById('email').value = rep.email;
        document.getElementById('fullName').value = rep.full_name;
        document.getElementById('branch').value = rep.branch || '';
        document.getElementById('passwordGroup').style.display = 'none';
        document.getElementById('confirmPasswordGroup').style.display = 'none';
        document.getElementById('password').removeAttribute('required');
        document.getElementById('confirmPassword').removeAttribute('required');
        
        // Show account details
        document.getElementById('createdAtDisplay').innerHTML = formatDateTime(rep.created_at);
        
        // Show login status with last seen
        let statusHtml = '';
        if (rep.is_online) {
            statusHtml = '<span class="badge badge-success">Online</span>';
        } else {
            statusHtml = '<span class="badge badge-secondary">Offline</span>';
            if (rep.last_seen) {
                const lastSeen = formatLastSeen(rep.last_seen);
                statusHtml += `<br><small style="color: #94a3b8; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Last seen: ${lastSeen}</small>`;
            }
        }
        document.getElementById('loginStatusDisplay').innerHTML = statusHtml;
        
        // Show total projects (will be updated by API)
        document.getElementById('totalProjectsBadge').textContent = '0 projects';
        
        // Show pending projects section
        document.getElementById('pendingProjectsSection').style.display = 'block';
        loadPendingProjects(rep.id);
        
        // Show edit/delete buttons
        document.getElementById('editButtons').style.display = 'flex';
        document.getElementById('createButtons').style.display = 'none';
        
        // Make fields readonly initially
        document.getElementById('email').setAttribute('readonly', 'readonly');
        document.getElementById('fullName').setAttribute('readonly', 'readonly');
        document.getElementById('branch').setAttribute('disabled', 'disabled');
    } else {
        // Add mode
        modalTitle.textContent = 'Add Sales Representative';
        salesRepForm.reset();
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('confirmPasswordGroup').style.display = 'block';
        document.getElementById('password').setAttribute('required', 'required');
        document.getElementById('confirmPassword').setAttribute('required', 'required');
        
        // Hide account details
        document.getElementById('pendingProjectsSection').style.display = 'none';
        
        // Show create buttons
        document.getElementById('editButtons').style.display = 'none';
        document.getElementById('createButtons').style.display = 'flex';
        
        // Make fields editable
        document.getElementById('email').removeAttribute('readonly');
        document.getElementById('fullName').removeAttribute('readonly');
        document.getElementById('branch').removeAttribute('disabled');
    }
    
    formError.style.display = 'none';
    salesRepModal.classList.add('active');
}

// Load Pending Projects for Sales Rep
async function loadPendingProjects(salesRepId) {
    const tbody = document.getElementById('pendingProjectsBody');
    tbody.innerHTML = '<tr><td colspan="6" class="loading-text">Loading pending projects...</td></tr>';
    
    try {
        // TODO: Create API endpoint to get pending projects for sales rep
        // For now, show placeholder
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 2rem; color: #94a3b8;">
                    <div>No pending projects</div>
                    <div style="font-size: 0.875rem; margin-top: 0.5rem;">
                        Projects will appear here when they need sales tracking updates
                    </div>
                </td>
            </tr>
        `;
        
        // Update total projects badge
        document.getElementById('totalProjectsBadge').textContent = '0 projects';
    } catch (error) {
        console.error('Error loading pending projects:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="error-text">Error loading projects</td></tr>';
    }
}

// Enable editing
window.enableEdit = function() {
    document.getElementById('email').removeAttribute('readonly');
    document.getElementById('fullName').removeAttribute('readonly');
    document.getElementById('branch').removeAttribute('disabled');
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('confirmPasswordGroup').style.display = 'block';
    
    // Change buttons
    document.getElementById('viewModeButtons').style.display = 'none';
    document.getElementById('editModeButtons').style.display = 'flex';
};

// Cancel editing
window.cancelEdit = function() {
    const rep = salesReps.find(r => r.id === currentEditId);
    if (rep) {
        // Restore original values
        document.getElementById('email').value = rep.email;
        document.getElementById('fullName').value = rep.full_name;
        document.getElementById('branch').value = rep.branch || '';
        
        // Make readonly again
        document.getElementById('email').setAttribute('readonly', 'readonly');
        document.getElementById('fullName').setAttribute('readonly', 'readonly');
        document.getElementById('branch').setAttribute('disabled', 'disabled');
        document.getElementById('passwordGroup').style.display = 'none';
        document.getElementById('confirmPasswordGroup').style.display = 'none';
        
        // Change buttons back
        document.getElementById('viewModeButtons').style.display = 'flex';
        document.getElementById('editModeButtons').style.display = 'none';
    }
};

// Confirm delete
window.confirmDelete = function() {
    const rep = salesReps.find(r => r.id === currentEditId);
    if (rep) {
        closeModalHandler();
        deleteSalesRep(rep.id, rep.full_name);
    }
};

// Close Modal
function closeModalHandler() {
    salesRepModal.classList.remove('active');
    salesRepForm.reset();
    currentEditId = null;
}

// Handle Form Submit
async function handleSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(salesRepForm);
    const data = Object.fromEntries(formData.entries());
    
    // Validate passwords match (if creating new)
    if (!currentEditId) {
        if (data.password !== data.confirm_password) {
            showFormError('Passwords do not match');
            return;
        }
        
        if (data.password.length < 8) {
            showFormError('Password must be at least 8 characters');
            return;
        }
    }
    
    // Show loading
    submitBtn.disabled = true;
    submitText.style.display = 'none';
    submitLoader.style.display = 'inline-block';
    formError.style.display = 'none';
    
    try {
        const url = currentEditId 
            ? `${API_BASE}/users/sales-reps/${currentEditId}`
            : `${API_BASE}/users/sales-reps`;
        
        const method = currentEditId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: data.email,
                full_name: data.full_name,
                branch: data.branch,
                password: data.password || undefined
            })
        });
        
        const result = await response.json();
        
        console.log('API Response:', { status: response.status, ok: response.ok, result });
        
        if (response.ok && result.success) {
            closeModalHandler();
            await loadSalesReps();
            showSuccess(currentEditId ? 'Sales representative updated successfully' : 'Sales representative created successfully');
        } else {
            showFormError(result.message || 'Operation failed');
        }
    } catch (error) {
        console.error('Error:', error);
        showFormError('An error occurred. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitText.style.display = 'inline';
        submitLoader.style.display = 'none';
    }
}

// Delete Sales Rep
let deleteUserId = null;

window.deleteSalesRep = function(id, name) {
    deleteUserId = id;
    deleteUserName.textContent = name;
    deleteModal.classList.add('active');
};

function closeDeleteModalHandler() {
    deleteModal.classList.remove('active');
    deleteUserId = null;
}

confirmDeleteBtn.addEventListener('click', async () => {
    if (!deleteUserId) return;
    
    // Show loading
    confirmDeleteBtn.disabled = true;
    deleteText.style.display = 'none';
    deleteLoader.style.display = 'inline-block';
    
    try {
        const response = await fetch(`${API_BASE}/users/sales-reps/${deleteUserId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeDeleteModalHandler();
            loadSalesReps();
            showSuccess('Sales representative deleted successfully');
        } else {
            showError(result.message || 'Failed to delete sales representative');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('An error occurred. Please try again.');
    } finally {
        confirmDeleteBtn.disabled = false;
        deleteText.style.display = 'inline';
        deleteLoader.style.display = 'none';
    }
});

// Utility Functions
function showFormError(message) {
    formError.textContent = message;
    formError.style.display = 'block';
    formError.classList.add('animate-shake');
    setTimeout(() => formError.classList.remove('animate-shake'), 500);
}

function showError(message) {
    Toast.error(message);
}

function showSuccess(message) {
    Toast.success(message);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const dateStr = date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
    const timeStr = date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
    return `${dateStr}<br><small style="color: #888;">${timeStr}</small>`;
}

function formatLastSeen(dateString) {
    if (!dateString) return 'Never';
    
    const now = new Date();
    const lastSeen = new Date(dateString);
    const diffMs = now - lastSeen;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    // More than a week, show date
    return lastSeen.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: now.getFullYear() !== lastSeen.getFullYear() ? 'numeric' : undefined
    });
}

// View Sales Rep Details
window.viewSalesRep = function(id) {
    const rep = salesReps.find(r => r.id === id);
    if (rep) {
        openModal(rep);
    }
};
