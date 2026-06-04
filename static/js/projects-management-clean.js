/* ============================================================
   projects-management.js — Project Management Page - CLEAN VERSION
   ============================================================ */

console.log('[PM] projects-management.js loaded');

// GLOBAL STATE VARIABLES
let currentView = 'unassigned';
let currentPage = 1;
let currentFilters = {
    search: '',
    region: '',
    status: '',
    source: ''
};
let selectedProjectId = null;
let salesReps = [];

// ASSIGNMENT STATE VARIABLES
let selectedProjects = new Set(); // Track selected project IDs for bulk operations
let selectedSalesRepId = null; // Track the selected sales rep for bulk assignment
let selectedSalesRepName = null; // Track the selected sales rep name
let isProjectSelectionMode = false; // Track if we're in project selection mode
let isBulkUnassignMode = false; // Track if we're in bulk unassign mode

// BASE URL
const _B = (typeof BASE !== 'undefined') ? BASE : '/DATAS';

console.log('[PM] Variables initialized:', {
    selectedProjects: selectedProjects,
    selectedSalesRepId: selectedSalesRepId,
    isProjectSelectionMode: isProjectSelectionMode
});

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    console.log('[PM] DOMContentLoaded');
    
    // Get current view from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentView = urlParams.get('view') || 'unassigned';
    
    // Get user role from body data attribute
    const userRole = document.body.dataset.role || '';
    
    // Load sales reps for assignment
    if (userRole === 'admin' || userRole === 'superadmin') {
        loadSalesReps();
        loadCounts();
    }
    
    // Load initial project data
    loadProjects();
    
    // Filter handlers
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            currentFilters.search = document.getElementById('searchInput').value;
            currentPage = 1;
            loadProjects();
        }, 500));
    }
    
    const regionFilter = document.getElementById('regionFilter');
    if (regionFilter) {
        regionFilter.addEventListener('change', () => {
            currentFilters.region = document.getElementById('regionFilter').value;
            currentPage = 1;
            loadProjects();
        });
    }
    
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            currentFilters.status = document.getElementById('statusFilter').value;
            currentPage = 1;
            loadProjects();
        });
    }
    
    const sourceFilter = document.getElementById('sourceFilter');
    if (sourceFilter) {
        sourceFilter.addEventListener('change', () => {
            currentFilters.source = document.getElementById('sourceFilter').value;
            currentPage = 1;
            loadProjects();
        });
    }
});

// Load projects
async function loadProjects() {
    const tbody = document.getElementById('pm-table-body');
    const thead = document.getElementById('pm-table-head');
    
    if (!tbody) return;
    
    const maxCols = currentView === 'unassigned' ? 7 : 8;
    tbody.innerHTML = `<tr><td colspan="${maxCols}" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>`;
    
    try {
        let endpoint = `${_B}/api/v1/projects/${currentView}`;
        const params = new URLSearchParams({
            page: currentPage,
            size: 20,
            ...currentFilters
        });
        
        const res = await fetch(`${endpoint}?${params}`, { credentials: 'include' });
        if (!res.ok) throw new Error('Failed to load projects');
        
        const data = await res.json();
        const projects = data.projects || [];
        
        // Store projects data globally for viewProject function
        window.currentProjectsData = data;
        
        // Render table headers based on view
        if (thead) thead.innerHTML = getTableHeaders();
        
        // Render table body
        if (projects.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${maxCols}" style="text-align:center;padding:2rem;color:var(--text-dim);">No projects found</td></tr>`;
        } else {
            tbody.innerHTML = projects.map(p => getTableRow(p)).join('');

            // Attach click listeners to rows
            setTimeout(() => {
                const rows = document.querySelectorAll('#pm-table-body tr[data-project]');
                rows.forEach(r => {
                    r.removeEventListener('click', rowClickHandler);
                    r.addEventListener('click', rowClickHandler);
                });
            }, 0);
        }
        
        // Render pagination
        renderPagination(data.total, data.size);
        
    } catch (err) {
        console.error('Error loading projects:', err);
        tbody.innerHTML = `<tr><td colspan="${maxCols}" style="text-align:center;padding:2rem;color:var(--text-danger);">Error loading projects</td></tr>`;
    }
}

// Get table headers based on view
function getTableHeaders() {
    const commonHeaders = `
        <th>Published Date</th>
        <th>Contractor</th>
        <th>Project Name</th>
        <th>Region</th>
        <th>Value (₱)</th>
        <th>Status</th>
    `;
    
    switch (currentView) {
        case 'unassigned':
            return `<tr>${commonHeaders}<th>Sales Tracking Status</th></tr>`;
        case 'assigned':
            return `<tr>${commonHeaders}<th>Assigned To</th><th>Sales Tracking Status</th></tr>`;
        case 'unprocessed':
            return `<tr>${commonHeaders}<th>Assigned To</th><th>Sales Tracking Status</th></tr>`;
        case 'processed':
            return `<tr>${commonHeaders}<th>Assigned To</th><th>Sales Tracking Status</th></tr>`;
        default:
            return `<tr>${commonHeaders}<th>Actions</th></tr>`;
    }
}

// Get table row based on view
function getTableRow(p) {
    const published = p.publication_date || p.published_date || p.published_at || null;
    const publishedFormatted = published ? new Date(published).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
    const value = (p.project_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    const statusClass = (p.status || '').toLowerCase().replace(/\s+/g, '-');
    
    // Get tracking status from API data
    const trackingStatus = p.tracking_status || 'Not Started';
    const trackingStatusClass = trackingStatus.toLowerCase().replace(/\s+/g, '-');
    
    const commonCells = `
        <td>${publishedFormatted}</td>
        <td style="font-weight:500;">${p.contractor_name || '—'}</td>
        <td>${p.project_name || '—'}</td>
        <td>${p.region || '—'}</td>
        <td style="text-align:right;">₱${value}</td>
        <td><span class="status-badge status-${statusClass}">${p.status || '—'}</span></td>
    `;
    
    switch (currentView) {
        case 'unassigned':
            return `<tr data-project="${encodeURIComponent(JSON.stringify(p))}" style="cursor:pointer">
                ${commonCells}
                <td><span class="tracking-badge tracking-${trackingStatusClass}">${trackingStatus}</span></td>
            </tr>`;
            
        case 'assigned':
            return `<tr data-project="${encodeURIComponent(JSON.stringify(p))}" style="cursor:pointer">
                ${commonCells}
                <td>${p.assigned_to_name || '—'}</td>
                <td><span class="tracking-badge tracking-${trackingStatusClass}">${trackingStatus}</span></td>
            </tr>`;
            
        case 'unprocessed':
            return `<tr data-project="${encodeURIComponent(JSON.stringify(p))}" style="cursor:pointer">
                ${commonCells}
                <td>${p.assigned_to_name || '—'}</td>
                <td><span class="tracking-badge tracking-${trackingStatusClass}">${trackingStatus}</span></td>
            </tr>`;
            
        case 'processed':
            return `<tr data-project="${encodeURIComponent(JSON.stringify(p))}" style="cursor:pointer">
                ${commonCells}
                <td>${p.assigned_to_name || '—'}</td>
                <td><span class="tracking-badge tracking-${trackingStatusClass}">${trackingStatus}</span></td>
            </tr>`;
            
        default:
            return `<tr data-project="${encodeURIComponent(JSON.stringify(p))}" style="cursor:pointer">${commonCells}<td>—</td></tr>`;
    }
}

// Load counts for tabs
async function loadCounts() {
    try {
        const views = ['unassigned', 'assigned', 'unprocessed', 'processed'];
        
        for (const view of views) {
            const res = await fetch(`${_B}/api/v1/projects/${view}?page=1&size=1`, { credentials: 'include' });
            if (res.ok) {
                const data = await res.json();
                const countEl = document.getElementById(`${view}-count`);
                if (countEl) {
                    countEl.textContent = data.total || 0;
                }
            }
        }
    } catch (err) {
        console.error('Error loading counts:', err);
    }
}

// Load sales reps (legacy compatibility)
async function loadSalesReps() {
    try {
        const res = await fetch(`${_B}/api/v1/users/sales-reps`, { credentials: 'include' });
        
        if (!res.ok) {
            console.error('[PM] Failed to load sales reps:', res.status);
            return [];
        }
        
        const response = await res.json();
        salesReps = response.users || response.data || response || [];
        
        return salesReps;
    } catch (err) {
        console.error('[PM] Error loading sales reps:', err);
        return [];
    }
}

// Render pagination
function renderPagination(total, size) {
    const container = document.getElementById('pm-pagination');
    if (!container) return;
    
    const totalPages = Math.ceil(total / size);
    
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="goToPage(${currentPage - 1})">Previous</button>`;
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += `<span style="padding:0.5rem;">...</span>`;
        }
    }
    
    html += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="goToPage(${currentPage + 1})">Next</button>`;
    
    container.innerHTML = html;
}

// Go to page
function goToPage(page) {
    currentPage = page;
    loadProjects();
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeHtml(text) {
    if (typeof text !== 'string') {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function rowClickHandler(e) {
    const tag = e.target.tagName.toLowerCase();
    if (tag === 'input' || tag === 'button' || e.target.closest('button') || e.target.closest('input')) return;
    const row = e.currentTarget;
    try {
        const projectData = row && row.dataset && row.dataset.project ? JSON.parse(decodeURIComponent(row.dataset.project)) : null;

        if (projectData && projectData.id) {
            openDetailsModal(projectData.id, row, false);
            return;
        }
    } catch (err) {
        console.error('Row click handler error:', err);
    }
}

// Open details modal
async function openDetailsModal(projectId, rowEl, forceRefresh = false) {
    try {
        selectedProjectId = projectId;
        viewProject(projectId);
    } catch (err) {
        console.error('Error opening details modal:', err);
    }
}

function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    if (modal) modal.classList.remove('active');
}

// Main viewProject function (simplified)
function viewProject(projectId) {
    // Find the project from the current projects data
    let project = null;
    
    if (window.currentProjectsData && window.currentProjectsData.projects) {
        project = window.currentProjectsData.projects.find(p => p.id === projectId);
    }
    
    if (!project) {
        console.error('Project not found:', projectId);
        return;
    }

    const modal = document.getElementById('detailsModal');
    const modalBody = document.getElementById('detailsModalBody');

    if (!modal || !modalBody) {
        console.error('Modal elements not found');
        return;
    }

    // Simple modal content
    modalBody.innerHTML = `
        <div class="detail-section">
            <div class="detail-section-title">📋 Project Information</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Contractor</div>
                    <div class="detail-value">${escapeHtml(project.contractor_name || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Project Name</div>
                    <div class="detail-value">${escapeHtml(project.project_name || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Region</div>
                    <div class="detail-value">${escapeHtml(project.region || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">${escapeHtml(project.status || '—')}</div>
                </div>
            </div>
        </div>
    `;

    modal.dataset.projectId = projectId;
    modal.classList.add('active');
}

// ============================================================================
// CLEAN ASSIGNMENT FUNCTIONALITY
// ============================================================================

// Open Sales Rep Selection Modal
async function openSalesRepModal() {
    console.log('[PM] openSalesRepModal called');
    
    const modal = document.getElementById('salesRepModal');
    if (!modal) {
        console.error('[PM] Sales Rep modal element not found with ID: salesRepModal');
        return;
    }
    
    // Reset state
    selectedProjects.clear();
    selectedSalesRepId = null;
    isProjectSelectionMode = false;
    
    console.log('[PM] State reset - selectedSalesRepId:', selectedSalesRepId);
    
    try {
        // Load sales reps using original function
        await loadSalesRepsInModal();
        
        // Show modal
        modal.classList.add('active');
        console.log('[PM] Modal opened successfully');
    } catch (error) {
        console.error('[PM] Error in openSalesRepModal:', error);
    }
}

// Load Sales Reps in Modal (original function)
async function loadSalesRepsInModal() {
    try {
        const grid = document.getElementById('salesRepsGrid');
        if (!grid) {
            console.error('[PM] salesRepsGrid element not found');
            return;
        }

        // Show loading state
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-secondary);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏳</div>
                <p>Loading sales representatives...</p>
            </div>
        `;

        console.log('[PM] Making request to:', `${_B}/api/v1/users/sales-reps`);
        const response = await fetch(`${_B}/api/v1/users/sales-reps`, { credentials: 'include' });
        
        console.log('[PM] Response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        const salesReps = data.users || data.data || data || [];

        console.log('[PM] Loaded sales reps:', salesReps);

        if (salesReps.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">👤</div>
                    <p><strong>No Sales Representatives</strong></p>
                    <p>Please create sales representatives to enable project assignment.</p>
                    <a href="users" style="display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: var(--primary-500); color: white; text-decoration: none; border-radius: 0.5rem;">Manage Users</a>
                </div>
            `;
            return;
        }

        // Render sales reps using original function
        renderSalesReps(salesReps);

        // Setup search functionality
        const searchInput = document.getElementById('srSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                filterSalesReps(e.target.value, salesReps);
            });
        }

    } catch (error) {
        console.error('[PM] Error loading sales reps:', error);
        const grid = document.getElementById('salesRepsGrid');
        if (grid) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-danger);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">❌</div>
                    <p><strong>Error Loading Sales Representatives</strong></p>
                    <p>${error.message}</p>
                    <button onclick="loadSalesRepsInModal()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: var(--primary-500); color: white; border: none; border-radius: 0.5rem; cursor: pointer;">Retry</button>
                </div>
            `;
        }
    }
}

// Render Sales Reps (original function)
function renderSalesReps(salesReps) {
    const grid = document.getElementById('salesRepsGrid');
    if (!grid) return;

    grid.innerHTML = '';

    salesReps.forEach(rep => {
        const repCard = document.createElement('div');
        repCard.className = 'sr-card';
        repCard.style.cssText = `
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        `;
        
        repCard.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.2rem;">
                    ${(rep.full_name || rep.email).charAt(0).toUpperCase()}
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">
                        ${rep.full_name || rep.email}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">
                        ${rep.email}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-dim);">
                        📍 ${rep.branch || 'No branch specified'}
                    </div>
                </div>
            </div>
        `;
        
        repCard.addEventListener('click', () => selectSalesRepForAssignment(rep.id, rep.full_name || rep.email, rep.branch));
        repCard.addEventListener('mouseenter', () => {
            repCard.style.borderColor = 'var(--primary-400)';
            repCard.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
            repCard.style.transform = 'translateY(-2px)';
        });
        repCard.addEventListener('mouseleave', () => {
            repCard.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            repCard.style.backgroundColor = 'rgba(15, 23, 42, 0.9)';
            repCard.style.transform = 'translateY(0)';
        });
        
        grid.appendChild(repCard);
    });
}

// Filter Sales Reps (original function)
function filterSalesReps(searchTerm, salesReps) {
    const grid = document.getElementById('salesRepsGrid');
    if (!grid) return;

    const filteredReps = salesReps.filter(rep => {
        const fullName = (rep.full_name || '').toLowerCase();
        const email = (rep.email || '').toLowerCase();
        const branch = (rep.branch || '').toLowerCase();
        const search = searchTerm.toLowerCase();

        return fullName.includes(search) ||
               email.includes(search) ||
               branch.includes(search);
    });

    if (filteredReps.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-secondary);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔍</div>
                <p><strong>No matches found</strong></p>
                <p>Try a different search term.</p>
            </div>
        `;
        return;
    }

    renderSalesReps(filteredReps);
}

// Populate the sales rep modal with data
function populateSalesRepModal(salesReps) {
    const container = document.getElementById('salesRepsGrid'); // Use original container ID
    if (!container) {
        console.error('[Assignment] Sales rep grid container not found');
        return;
    }
    
    container.innerHTML = '';
    
    salesReps.forEach(rep => {
        const repCard = document.createElement('div');
        repCard.className = 'sr-card';
        repCard.style.cssText = `
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        `;
        
        repCard.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.2rem;">
                    ${(rep.full_name || rep.email).charAt(0).toUpperCase()}
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">
                        ${rep.full_name || rep.email}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">
                        ${rep.email}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-dim);">
                        📍 ${rep.branch || 'No branch specified'}
                    </div>
                </div>
            </div>
        `;
        
        repCard.addEventListener('click', () => selectSalesRepForAssignment(rep.id, rep.full_name || rep.email, rep.branch));
        repCard.addEventListener('mouseenter', () => {
            repCard.style.borderColor = 'var(--primary-400)';
            repCard.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
            repCard.style.transform = 'translateY(-2px)';
        });
        repCard.addEventListener('mouseleave', () => {
            repCard.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            repCard.style.backgroundColor = 'rgba(15, 23, 42, 0.9)';
            repCard.style.transform = 'translateY(0)';
        });
        
        container.appendChild(repCard);
    });
}

// Select Sales Rep for Assignment (using original function name)
function selectSalesRepForAssignment(salesRepId, salesRepName, salesRepBranch) {
    console.log('[PM] selectSalesRep called with:', salesRepId, salesRepName, salesRepBranch);
    
    selectedSalesRepId = salesRepId;
    selectedSalesRepName = salesRepName; // Make sure to store the name
    
    console.log('[PM] State after selection:', {
        selectedSalesRepId: selectedSalesRepId,
        selectedSalesRepName: selectedSalesRepName
    });
    
    // Close the modal
    closeSalesRepModal();
    
    // Start project selection using original function
    startProjectSelectionMode(salesRepName);
}

// Close Sales Rep Modal
function closeSalesRepModal() {
    const modal = document.getElementById('salesRepModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Start project selection mode
function startProjectSelection() {
    console.log('[Assignment] Starting project selection mode');
    assignmentState.isSelectingProjects = true;
    
    // Create status bar
    createAssignmentStatusBar();
    
    // Enable project selection checkboxes
    enableProjectSelection();
}

// Create assignment status bar
function createAssignmentStatusBar() {
    // Remove existing status bar
    const existing = document.getElementById('assignmentStatusBar');
    if (existing) existing.remove();
    
    const statusBar = document.createElement('div');
    statusBar.id = 'assignmentStatusBar';
    statusBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #10b981;
        color: white;
        padding: 1rem;
        text-align: center;
        font-weight: 500;
        z-index: 1000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    `;
    
    statusBar.innerHTML = `
        📋 Assignment Mode: Selected Sales Rep <strong>${assignmentState.selectedSalesRepName}</strong> 
        | Selected Projects: <span id="selectedProjectCount">0</span>
        <button onclick="cancelAssignment()" style="margin-left: 1rem; background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;">Cancel</button>
        <button onclick="proceedWithAssignment()" style="margin-left: 0.5rem; background: white; color: #10b981; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-weight: 500;">Assign Selected</button>
    `;
    
    document.body.appendChild(statusBar);
}

// Enable project selection checkboxes
function enableProjectSelection() {
    const tableBody = document.getElementById('pm-table-body');
    if (!tableBody) return;
    
    const rows = tableBody.querySelectorAll('tr[data-project]');
    rows.forEach((row) => {
        // Add selection checkbox if it doesn't exist
        let checkbox = row.querySelector('.project-select-checkbox');
        if (!checkbox) {
            const firstCell = row.querySelector('td');
            if (firstCell) {
                checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'project-select-checkbox';
                checkbox.style.marginRight = '0.5rem';
                checkbox.addEventListener('change', (e) => handleProjectSelection(e, row));
                firstCell.insertBefore(checkbox, firstCell.firstChild);
            }
        }
        
        // Add visual indication for selection mode
        row.style.cursor = 'pointer';
    });
}

// Start Project Selection Mode (original function)
function startProjectSelectionMode(salesRepName) {
    console.log('[PM] startProjectSelectionMode called with salesRepName:', salesRepName);
    console.log('[PM] selectedSalesRepId at start:', selectedSalesRepId);
    
    isProjectSelectionMode = true;
    
    // Show project selection banner and controls
    showProjectSelectionBanner(salesRepName);
    
    // Add checkboxes to project rows
    addProjectCheckboxes();
    
    // Show bulk action buttons
    showBulkActionButtons('assign', salesRepName);
}

// Show project selection banner
function showProjectSelectionBanner(salesRepName) {
    const existingBanner = document.getElementById('selectionBanner');
    if (existingBanner) {
        existingBanner.remove();
    }
    
    // Find the bulk assign button bar to modify it
    const bulkAssignBar = document.getElementById('bulkAssignButtonBar');
    if (!bulkAssignBar) {
        console.error('[PM] Bulk assign button bar not found');
        return;
    }
    
    // Hide the original button and replace with inline status
    const originalButton = bulkAssignBar.querySelector('button');
    if (originalButton) {
        originalButton.style.display = 'none';
    }
    
    // Create inline assignment status
    const statusContainer = document.createElement('div');
    statusContainer.id = 'inlineAssignmentStatus';
    statusContainer.style.cssText = `
        display: flex;
        align-items: center;
        gap: 1rem;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 0.875rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 500;
        font-size: 0.95rem;
        box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
    `;
    
    statusContainer.innerHTML = `
        <span>📋 Assignment Mode: Selected Sales Rep <strong>${salesRepName}</strong></span>
        <span>| Selected Projects: <span id="selectedCount" style="font-weight: 700;">0</span></span>
        <button onclick="exitProjectSelectionMode()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;">Cancel</button>
    `;
    
    // Create assign projects button
    const assignButton = document.createElement('button');
    assignButton.id = 'inlineAssignButton';
    assignButton.className = 'btn-primary';
    assignButton.disabled = true;
    assignButton.style.cssText = `
        padding: 0.875rem 1.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.95rem;
        font-weight: 700;
        border-radius: 0.75rem;
        background: var(--orange-500);
        border: 1px solid var(--orange-500);
        color: #000;
        cursor: pointer;
        opacity: 0.5;
        transition: all 0.2s ease;
        white-space: nowrap;
    `;
    
    assignButton.innerHTML = `
        <span>✓</span>
        <span>Assign Projects (<span id="assignButtonCount">0</span>)</span>
    `;
    
    assignButton.addEventListener('click', function() {
        if (!this.disabled) {
            proceedWithBulkAssignment();
        }
    });
    
    // Add to the button bar
    bulkAssignBar.appendChild(statusContainer);
    bulkAssignBar.appendChild(assignButton);
    
    // Store references for later updates
    window.inlineAssignButton = assignButton;
    
    // Force an immediate update to ensure the count displays correctly
    setTimeout(() => {
        updateSelectedCount();
    }, 50);
}

// Add checkboxes to project rows
function addProjectCheckboxes() {
    const rows = document.querySelectorAll('#pm-table-body tr[data-project]');
    
    rows.forEach((row, index) => {
        // Skip if checkbox already exists
        if (row.querySelector('.project-checkbox')) return;
        
        try {
            const projectData = JSON.parse(decodeURIComponent(row.dataset.project));
            const projectId = projectData.id;
            
            // Create checkbox
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'project-checkbox';
            checkbox.style.cssText = 'width: 18px; height: 18px; cursor: pointer; margin-right: 0.5rem;';
            checkbox.dataset.projectId = projectId;
            
            // Add to first cell
            const firstCell = row.cells[0];
            if (firstCell) {
                firstCell.insertBefore(checkbox, firstCell.firstChild);
            }
            
            // Add event listener
            checkbox.addEventListener('change', function(e) {
                console.log('[PM] Checkbox changed for project:', projectId, 'checked:', e.target.checked);
                toggleProjectSelection(projectId, e.target.checked);
                
                // Make sure to update the display count immediately
                updateSelectedCount();
            });
            
        } catch (error) {
            console.error('[PM] Error adding checkbox to row:', error);
        }
    });
}

// Toggle project selection
function toggleProjectSelection(projectId, isSelected) {
    console.log('[PM] Toggle project selection called:', projectId, isSelected, typeof projectId);
    
    // Convert to number to ensure consistency
    const numericId = parseInt(projectId);
    console.log('[PM] Converted ID:', numericId);
    
    if (isSelected) {
        selectedProjects.add(numericId);
        console.log('[PM] Added project to selection');
    } else {
        selectedProjects.delete(numericId);
        console.log('[PM] Removed project from selection');
    }
    
    console.log('[PM] Updated selectedProjects size:', selectedProjects.size);
    console.log('[PM] Updated selectedProjects array:', Array.from(selectedProjects));
    
    // Force update of the display count
    updateSelectedCount();
    
    console.log('[PM] Called updateSelectedCount()');
}

// Update selected count display
function updateSelectedCount() {
    console.log('[PM] updateSelectedCount called');
    
    // Use a small delay to ensure DOM elements are ready
    setTimeout(() => {
        const countElement = document.getElementById('selectedCount');
        const assignButtonCountElement = document.getElementById('assignButtonCount');
        const inlineAssignButton = window.inlineAssignButton;
        
        const count = selectedProjects.size;
        console.log('[PM] Current selectedProjects size:', count);
        console.log('[PM] selectedCount element found:', !!countElement);
        console.log('[PM] assignButtonCount element found:', !!assignButtonCountElement);
        console.log('[PM] inlineAssignButton found:', !!inlineAssignButton);
        
        if (countElement) {
            console.log('[PM] Updating selectedCount to:', count);
            countElement.textContent = count;
            countElement.style.color = count > 0 ? '#10b981' : 'white';
        } else {
            console.error('[PM] selectedCount element not found!');
        }
        
        if (assignButtonCountElement) {
            console.log('[PM] Updating assignButtonCount to:', count);
            assignButtonCountElement.textContent = count;
        } else {
            console.error('[PM] assignButtonCount element not found!');
        }
        
        // Update assign button state
        if (inlineAssignButton) {
            console.log('[PM] Updating inline assign button state');
            if (count > 0) {
                inlineAssignButton.disabled = false;
                inlineAssignButton.style.opacity = '1';
                inlineAssignButton.style.cursor = 'pointer';
                inlineAssignButton.style.transform = 'none';
                console.log('[PM] Button enabled for', count, 'projects');
            } else {
                inlineAssignButton.disabled = true;
                inlineAssignButton.style.opacity = '0.5';
                inlineAssignButton.style.cursor = 'not-allowed';
                console.log('[PM] Button disabled - no projects selected');
            }
        } else {
            console.error('[PM] inlineAssignButton not found!');
        }
    }, 10); // Small delay to ensure DOM is ready
}

// Show bulk action buttons - Updated to not show floating buttons since we use inline
function showBulkActionButtons(action, salesRepName = '') {
    // Remove any existing floating buttons since we now use inline design
    const existingButtons = document.getElementById('bulkActionButtons');
    if (existingButtons) {
        existingButtons.remove();
    }
    
    // The inline buttons are created in showProjectSelectionBanner instead
    console.log('[PM] Using inline button design, no floating buttons needed');
}

// Exit project selection mode
function exitProjectSelectionMode() {
    console.log('[PM] Exiting project selection mode');
    
    // Reset all state variables
    isProjectSelectionMode = false;
    isBulkUnassignMode = false;
    selectedProjects.clear();
    selectedSalesRepId = null;
    selectedSalesRepName = null;
    
    console.log('[PM] State after exit:', {
        isProjectSelectionMode: isProjectSelectionMode,
        selectedProjects: selectedProjects.size,
        selectedSalesRepId: selectedSalesRepId
    });
    
    // Remove inline status and restore original button
    const inlineStatus = document.getElementById('inlineAssignmentStatus');
    const inlineButton = document.getElementById('inlineAssignButton');
    const bulkAssignBar = document.getElementById('bulkAssignButtonBar');
    
    if (inlineStatus) {
        inlineStatus.remove();
        console.log('[PM] Removed inline assignment status');
    }
    
    if (inlineButton) {
        inlineButton.remove();
        console.log('[PM] Removed inline assign button');
    }
    
    // Restore original bulk assign button
    if (bulkAssignBar) {
        const originalButton = bulkAssignBar.querySelector('button');
        if (originalButton) {
            originalButton.style.display = 'inline-flex';
            console.log('[PM] Restored original bulk assign button');
        }
    }
    
    // Remove any other UI elements
    const banner = document.getElementById('selectionBanner');
    const buttons = document.getElementById('bulkActionButtons');
    
    if (banner) {
        banner.remove();
        console.log('[PM] Removed selection banner');
    }
    if (buttons) {
        buttons.remove();
        console.log('[PM] Removed bulk action buttons');
    }
    
    // Remove checkboxes
    const checkboxes = document.querySelectorAll('.project-checkbox');
    checkboxes.forEach(checkbox => checkbox.remove());
    console.log('[PM] Removed', checkboxes.length, 'checkboxes');
    
    // Clear button reference
    window.inlineAssignButton = null;
    
    console.log('[PM] Successfully exited project selection mode');
}

// Proceed with bulk assignment
async function proceedWithBulkAssignment() {
    console.log('[PM] ===== proceedWithBulkAssignment START =====');
    console.log('[PM] selectedSalesRepId:', selectedSalesRepId);
    console.log('[PM] selectedSalesRepName:', selectedSalesRepName);
    console.log('[PM] selectedProjects:', Array.from(selectedProjects));
    
    if (selectedProjects.size === 0) {
        console.log('[PM] No projects selected - showing warning modal');
        showNotificationModal('Warning', 'Please select at least one project to assign.', 'warning');
        return;
    }
    
    if (!selectedSalesRepId) {
        console.log('[PM] No sales rep selected - showing error modal');
        showNotificationModal('Error', 'No sales representative selected.', 'error');
        return;
    }
    
    const repName = selectedSalesRepName || `Sales Rep #${selectedSalesRepId}`;
    
    console.log('[PM] Proceeding with direct assignment (no confirmation)');
    console.log('[PM] Rep name:', repName);
    console.log('[PM] Project count:', selectedProjects.size);
    
    // Proceed directly with assignment (no confirmation modal)
    try {
        const assignmentData = {
            sales_rep_id: selectedSalesRepId,
            project_ids: Array.from(selectedProjects)
        };
        
        console.log('[PM] Sending assignment data:', assignmentData);
        
        const response = await fetch(`${_B}/api/v1/projects/bulk-assign`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(assignmentData)
        });
        
        console.log('[PM] Assignment response status:', response.status);
        
        if (response.ok) {
            const result = await response.json();
            console.log('[PM] Assignment successful:', result);
            
            showNotificationModal(
                'Success', 
                `Successfully assigned ${selectedProjects.size} project(s) to ${repName}!`, 
                'success'
            );
            
            // Exit selection mode and reload projects
            exitProjectSelectionMode();
            loadProjects();
            
        } else {
            const errorData = await response.json();
            console.log('[PM] Assignment failed with error:', errorData);
            throw new Error(errorData.message || 'Assignment failed');
        }
        
    } catch (error) {
        console.error('[PM] Assignment exception:', error);
        showNotificationModal('Assignment Failed', 'Assignment failed: ' + error.message, 'error');
    }
    
    console.log('[PM] ===== proceedWithBulkAssignment END =====');
}

console.log('[PM] All functions loaded successfully');

// Additional missing functions for compatibility

// Start bulk unassign mode
function startBulkUnassign() {
    console.log('[PM] Starting bulk unassign mode');
    isBulkUnassignMode = true;
    isProjectSelectionMode = true;
    
    // Show project selection banner
    showBulkUnassignBanner();
    
    // Add checkboxes to project rows
    addProjectCheckboxes();
    
    // Show bulk unassign buttons
    showBulkUnassignButtons();
}

// Show bulk unassign banner
function showBulkUnassignBanner() {
    const existingBanner = document.getElementById('selectionBanner');
    if (existingBanner) {
        existingBanner.remove();
    }
    
    const banner = document.createElement('div');
    banner.id = 'selectionBanner';
    banner.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
        padding: 1rem;
        text-align: center;
        font-weight: 500;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(220, 38, 38, 0.3);
    `;
    
    banner.innerHTML = `
        🗑️ Unassign Mode: Selected Projects: <span id="selectedCount">0</span>
        <button onclick="exitProjectSelectionMode()" style="margin-left: 1rem; background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">Cancel</button>
    `;
    
    document.body.appendChild(banner);
}

// Show bulk unassign buttons
function showBulkUnassignButtons() {
    const existingButtons = document.getElementById('bulkActionButtons');
    if (existingButtons) {
        existingButtons.remove();
    }
    
    const buttonsContainer = document.createElement('div');
    buttonsContainer.id = 'bulkActionButtons';
    buttonsContainer.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        padding: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        z-index: 1000;
    `;
    
    buttonsContainer.innerHTML = `
        <div style="color: var(--text-secondary); font-size: 0.9rem;">
            <span id="proceedCount">0</span> project(s) selected
        </div>
        <button id="proceedBtn" class="btn-secondary" data-can-click="false" style="padding: 0.75rem 2rem; opacity: 0.5; cursor: not-allowed; background: #dc2626; color: white;">
            <span>Unassign Projects (<span id="proceedCount">0</span>)</span>
        </button>
    `;
    
    document.body.appendChild(buttonsContainer);
    
    // Add click handler for unassign
    const proceedBtn = document.getElementById('proceedBtn');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function(e) {
            console.log('[PM] Unassign button clicked!');
            const canClick = this.getAttribute('data-can-click') === 'true';
            
            if (canClick) {
                console.log('[PM] Proceeding with bulk unassignment');
                proceedWithBulkUnassignment();
            } else {
                console.log('[PM] Button not clickable');
            }
        });
    }
    
    // Update button state using the new inline design
    updateSelectedCount();
}

// Proceed with bulk unassignment
async function proceedWithBulkUnassignment() {
    console.log('[PM] proceedWithBulkUnassignment called');
    console.log('[PM] selectedProjects:', Array.from(selectedProjects));
    
    if (selectedProjects.size === 0) {
        showNotificationModal('Warning', 'Please select at least one project to unassign.', 'warning');
        return;
    }
    
    // Proceed directly with unassignment (no confirmation)
    try {
        const unassignmentData = {
            project_ids: Array.from(selectedProjects)
        };
        
        console.log('[PM] Sending unassignment data:', unassignmentData);
        
        const response = await fetch(`${_B}/api/v1/projects/bulk-unassign`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(unassignmentData)
        });
        
        console.log('[PM] Unassignment response status:', response.status);
        
        if (response.ok) {
            const result = await response.json();
            showNotificationModal(
                'Success',
                `Successfully unassigned ${selectedProjects.size} project(s)!`,
                'success'
            );
            
            // Exit selection mode and reload projects
            exitProjectSelectionMode();
            loadProjects();
            loadCounts();
            
        } else {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Unassignment failed');
        }
        
    } catch (error) {
        console.error('[PM] Unassignment failed:', error);
        showNotificationModal('Unassignment Failed', 'Unassignment failed: ' + error.message, 'error');
    }
}

// ============================================================================
// CUSTOM MODAL FUNCTIONS (Replace browser alerts)
// ============================================================================

// Show notification modal (replaces alert)
function showNotificationModal(title, message, type = 'info') {
    // Remove existing notification modal
    const existing = document.getElementById('notificationModal');
    if (existing) {
        existing.remove();
    }
    
    // Create modal overlay
    const modal = document.createElement('div');
    modal.id = 'notificationModal';
    modal.className = 'modal-overlay';
    modal.style.cssText = `
        display: flex;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease;
    `;
    
    // Define colors based on type
    const colors = {
        success: { bg: 'rgba(16, 185, 129, 0.1)', border: '#10b981', text: '#10b981', icon: '✓' },
        error: { bg: 'rgba(239, 68, 68, 0.1)', border: '#ef4444', text: '#ef4444', icon: '✕' },
        warning: { bg: 'rgba(245, 158, 11, 0.1)', border: '#f59e0b', text: '#f59e0b', icon: '⚠' },
        info: { bg: 'rgba(59, 130, 246, 0.1)', border: '#3b82f6', text: '#3b82f6', icon: 'ℹ' }
    };
    
    const color = colors[type] || colors.info;
    
    // Create modal content
    modal.innerHTML = `
        <div style="
            background: var(--bg-card, #1e293b);
            border: 2px solid ${color.border};
            border-radius: 1rem;
            max-width: 500px;
            width: 90%;
            animation: slideInUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        ">
            <div style="
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 2rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                background: ${color.bg};
            ">
                <div style="
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    background: ${color.border};
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 1.5rem;
                    font-weight: bold;
                    flex-shrink: 0;
                ">
                    ${color.icon}
                </div>
                <div style="flex: 1;">
                    <h2 style="
                        margin: 0 0 0.5rem 0;
                        color: ${color.text};
                        font-size: 1.25rem;
                        font-weight: 700;
                    ">
                        ${title}
                    </h2>
                    <p style="
                        margin: 0;
                        color: var(--text-primary, white);
                        font-size: 1rem;
                        line-height: 1.5;
                    ">
                        ${message}
                    </p>
                </div>
            </div>
            <div style="
                display: flex;
                justify-content: flex-end;
                padding: 1.5rem;
                gap: 1rem;
            ">
                <button onclick="closeNotificationModal()" style="
                    background: ${color.border};
                    color: white;
                    border: none;
                    padding: 0.75rem 2rem;
                    border-radius: 0.5rem;
                    font-size: 0.95rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                ">
                    OK
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Auto-focus the OK button
    setTimeout(() => {
        const okButton = modal.querySelector('button');
        if (okButton) okButton.focus();
    }, 100);
}

// Show confirmation modal (replaces confirm)
function showConfirmationModal(title, message, onConfirm, onCancel = null) {
    // Remove existing confirmation modal
    const existing = document.getElementById('confirmationModal');
    if (existing) {
        existing.remove();
    }
    
    // Create modal overlay
    const modal = document.createElement('div');
    modal.id = 'confirmationModal';
    modal.className = 'modal-overlay';
    modal.style.cssText = `
        display: flex;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease;
    `;
    
    // Create modal content
    modal.innerHTML = `
        <div style="
            background: var(--bg-card, #1e293b);
            border: 2px solid #3b82f6;
            border-radius: 1rem;
            max-width: 500px;
            width: 90%;
            animation: slideInUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        ">
            <div style="
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 2rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            ">
                <div style="
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    background: #3b82f6;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 1.5rem;
                    font-weight: bold;
                    flex-shrink: 0;
                ">
                    ?
                </div>
                <div style="flex: 1;">
                    <h2 style="
                        margin: 0 0 0.5rem 0;
                        color: var(--text-primary, white);
                        font-size: 1.25rem;
                        font-weight: 700;
                    ">
                        ${title}
                    </h2>
                    <p style="
                        margin: 0;
                        color: var(--text-secondary, #9ca3af);
                        font-size: 1rem;
                        line-height: 1.5;
                    ">
                        ${message}
                    </p>
                </div>
            </div>
            <div style="
                display: flex;
                justify-content: flex-end;
                padding: 1.5rem;
                gap: 1rem;
            ">
                <button id="cancelConfirmBtn" style="
                    background: rgba(107, 114, 128, 0.2);
                    border: 1px solid rgba(107, 114, 128, 0.4);
                    color: var(--text-primary, white);
                    padding: 0.75rem 1.5rem;
                    border-radius: 0.5rem;
                    font-size: 0.95rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                ">
                    Cancel
                </button>
                <button id="confirmBtn" style="
                    background: #3b82f6;
                    color: white;
                    border: none;
                    padding: 0.75rem 2rem;
                    border-radius: 0.5rem;
                    font-size: 0.95rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                ">
                    OK
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add event listeners with proper event handling
    const confirmBtn = modal.querySelector('#confirmBtn');
    const cancelBtn = modal.querySelector('#cancelConfirmBtn');
    
    // Prevent the modal from closing immediately
    modal.addEventListener('click', (e) => {
        // Only close if clicking the overlay background, not the modal content
        if (e.target === modal) {
            console.log('[Modal] Clicked overlay - cancelling');
            closeConfirmationModal();
            if (onCancel) onCancel();
        }
    });
    
    confirmBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('[Modal] Confirm button clicked');
        closeConfirmationModal();
        // Small delay to ensure modal closes before executing callback
        setTimeout(() => {
            if (onConfirm) onConfirm();
        }, 100);
    });
    
    cancelBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('[Modal] Cancel button clicked');
        closeConfirmationModal();
        if (onCancel) onCancel();
    });
    
    // Handle escape key
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            console.log('[Modal] Escape key pressed');
            closeConfirmationModal();
            if (onCancel) onCancel();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    
    document.addEventListener('keydown', handleEscape);
    
    // Auto-focus the confirm button after a delay
    setTimeout(() => {
        if (confirmBtn) {
            confirmBtn.focus();
            console.log('[Modal] Focused confirm button');
        }
    }, 300);
    
    console.log('[Modal] Confirmation modal created and displayed');
}

// Close notification modal
function closeNotificationModal() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.style.animation = 'fadeOut 0.2s ease';
        setTimeout(() => {
            modal.remove();
        }, 200);
    }
}

// Close confirmation modal
function closeConfirmationModal() {
    console.log('[Modal] Closing confirmation modal');
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            if (modal.parentNode) {
                modal.remove();
                console.log('[Modal] Confirmation modal removed');
            }
        }, 300);
    }
}

// Add CSS animations
if (!document.getElementById('modalAnimations')) {
    const style = document.createElement('style');
    style.id = 'modalAnimations';
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
}

console.log('[PM] Custom modal system loaded');