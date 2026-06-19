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
    source: '',
    sort: 'publication_date_desc' // Default to newest published first
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
    
    const sortFilter = document.getElementById('sortFilter');
    if (sortFilter) {
        sortFilter.addEventListener('change', () => {
            currentFilters.sort = document.getElementById('sortFilter').value;
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
        
        // Special handling for archived view
        if (currentView === 'archived') {
            endpoint = `${_B}/api/v1/projects/archived`;
        }
        const params = new URLSearchParams({
            page: currentPage,
            size: 20,
            ...currentFilters
        });
        
        const res = await fetch(`${endpoint}?${params}`, { credentials: 'include' });
        if (!res.ok) throw new Error('Failed to load projects');
        
        const data = await res.json();
        let projects = data.projects || [];
        
        // Sort by selected field and order
        const sortValue = currentFilters.sort || 'publication_date_desc';
        
        // Parse sort value (e.g., "publication_date_desc" -> field: "publication_date", order: "desc")
        let sortField = 'publication_date';
        let sortOrder = 'desc';
        
        if (sortValue.includes('_')) {
            const parts = sortValue.split('_');
            sortOrder = parts[parts.length - 1]; // Last part is order (asc/desc)
            sortField = parts.slice(0, -1).join('_'); // Everything before last part is field name
        } else {
            // Backward compatibility: if just "desc" or "asc", use publication_date
            sortOrder = sortValue;
        }
        
        projects.sort((a, b) => {
            let dateA, dateB;
            
            if (sortField === 'publication_date') {
                dateA = new Date(a.publication_date || a.published_date || a.published_at || 0);
                dateB = new Date(b.publication_date || b.published_date || b.published_at || 0);
            } else if (sortField === 'created_at') {
                dateA = new Date(a.created_at || 0);
                dateB = new Date(b.created_at || 0);
            } else if (sortField === 'archived_at') {
                dateA = new Date(a.archived_at || 0);
                dateB = new Date(b.archived_at || 0);
            }
            
            return sortOrder === 'desc' ? dateB - dateA : dateA - dateB;
        });
        
        // Store projects data globally for viewProject function
        window.currentProjectsData = data;
        
        // Populate filters dynamically from project data
        populateRegionFilter(projects);
        populateSourceFilter(projects);
        
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

// Populate source filter dynamically from project data
function populateSourceFilter(projects) {
    const sourceFilter = document.getElementById('sourceFilter');
    if (!sourceFilter) return;
    
    // Preserve current selection
    const currentValue = sourceFilter.value;
    
    // Collect unique sources from projects
    const sources = new Set();
    projects.forEach(p => {
        if (p.source && p.source.trim()) {
            sources.add(p.source.trim());
        }
    });
    
    // Sort sources alphabetically
    const sortedSources = [...sources].sort();
    
    // Rebuild options
    sourceFilter.innerHTML = '<option value="">All Sources</option>';
    sortedSources.forEach(source => {
        const option = document.createElement('option');
        option.value = source;
        option.textContent = source;
        sourceFilter.appendChild(option);
    });
    
    // Restore previous selection if it still exists
    if (currentValue) {
        sourceFilter.value = currentValue;
    }
}

// Get table headers based on view
function getTableHeaders() {
    const commonHeaders = `
        <th>Published Date</th>
        <th>Contractor</th>
        <th>Project Name</th>
        <th>Region</th>
        <th>₱</th>
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
        case 'archived':
            return `<tr>${commonHeaders}<th>Assigned To</th><th>Archived Date</th></tr>`;
        default:
            return `<tr>${commonHeaders}<th>Actions</th></tr>`;
    }
}

// Get table row based on view
function getTableRow(p) {
    const published = p.publication_date || p.published_date || p.published_at || null;
    const publishedFormatted = published 
        ? (window.PhilippineDateTime 
            ? PhilippineDateTime.formatDateShort(published)
            : new Date(published).toLocaleDateString('en-PH', { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric' }))
        : '—';
    const value = formatCurrency(p.project_value);
    const statusClass = (p.status || '').toLowerCase().replace(/\s+/g, '-');
    
    // Get tracking status from API data
    const trackingStatus = p.tracking_status || 'Not Started';
    const trackingStatusClass = trackingStatus.toLowerCase().replace(/\s+/g, '-');
    
    const commonCells = `
        <td>${publishedFormatted}</td>
        <td style="font-weight:500;">${p.contractor_name || '—'}</td>
        <td>${p.project_name || '—'}</td>
        <td>${p.region || '—'}</td>
        <td>${value}</td>
        <td><span class="status-circle ${statusClass}"></span></td>
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
            
        case 'archived':
            const archivedDate = p.archived_at 
                ? (window.PhilippineDateTime 
                    ? PhilippineDateTime.formatDateShort(p.archived_at)
                    : new Date(p.archived_at).toLocaleDateString('en-PH', { 
                        timeZone: 'Asia/Manila',
                        month: 'short', 
                        day: 'numeric', 
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }))
                : '—';
            return `<tr data-project="${encodeURIComponent(JSON.stringify(p))}" style="cursor:pointer; opacity:0.7;">
                ${commonCells}
                <td>${p.assigned_to_name || '—'}</td>
                <td style="color: #ef4444;">🗄️ ${archivedDate}</td>
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

    const value = formatCurrency(project.project_value);
    
    // Complete modal content matching projects.js
    modalBody.innerHTML = `
        <div class="detail-section">
            <div class="detail-section-title">📋 Basic Information</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Published Date</div>
                    <div class="detail-value">${project.publication_date || '—'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Source</div>
                    <div class="detail-value">${escapeHtml(project.source || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contract ID</div>
                    <div class="detail-value">${escapeHtml(project.contractor_id || project.contract_id || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contractor Name</div>
                    <div class="detail-value">${escapeHtml(project.contractor_name || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contact Person</div>
                    <div class="detail-value">${escapeHtml(project.contact_person || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contact Number</div>
                    <div class="detail-value">${escapeHtml(project.contact_number || '—')}</div>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-section-title">📍 Contractor Location</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Country</div>
                    <div class="detail-value">${escapeHtml(project.contract_country || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Region</div>
                    <div class="detail-value">${escapeHtml(project.contract_region || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Province</div>
                    <div class="detail-value">${escapeHtml(project.contract_province || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">City</div>
                    <div class="detail-value">${escapeHtml(project.contract_city || '—')}</div>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-section-title">🏗️ Project Details</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Project ID</div>
                    <div class="detail-value">${escapeHtml(project.project_id || project.id || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Project Name</div>
                    <div class="detail-value">${escapeHtml(project.project_name || '—')}</div>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-section-title">📍 Project Location</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Country</div>
                    <div class="detail-value">${escapeHtml(project.project_country || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Region</div>
                    <div class="detail-value">${escapeHtml(project.project_region || project.region || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Province</div>
                    <div class="detail-value">${escapeHtml(project.project_province || '—')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">City</div>
                    <div class="detail-value">${escapeHtml(project.project_city || '—')}</div>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-section-title">💰 Project Information</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Project Value</div>
                    <div class="detail-value large">${value}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Project Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-${(project.status || '').toLowerCase().replace(/\s+/g, '-')}">${escapeHtml(project.status || '—')}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Tracking Section -->
        <div class="sales-tracking-section" data-role-access="superadmin,admin,sales_rep">
            <div class="sales-tracking-title">📊 Sales Tracking</div>
            <div class="sales-form-grid">
                <div class="sales-form-group">
                    <label class="sales-form-label">Contacted</label>
                    <div class="yes-no-buttons">
                        <button type="button" class="yes-no-btn" data-field="contacted" data-value="yes">Yes</button>
                        <button type="button" class="yes-no-btn" data-field="contacted" data-value="no">No</button>
                    </div>
                </div>
                
                <div class="sales-form-group">
                    <label class="sales-form-label">Quoted</label>
                    <div class="yes-no-buttons">
                        <button type="button" class="yes-no-btn" data-field="quoted" data-value="yes">Yes</button>
                        <button type="button" class="yes-no-btn" data-field="quoted" data-value="no">No</button>
                    </div>
                </div>
                
                <div class="sales-form-group">
                    <label class="sales-form-label">Sales Qualified Leads</label>
                    <div class="yes-no-buttons">
                        <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="yes">Yes</button>
                        <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="no">No</button>
                    </div>
                </div>
                
                <div class="sales-form-group">
                    <label class="sales-form-label">To Win</label>
                    <div class="yes-no-buttons">
                        <button type="button" class="yes-no-btn" data-field="to_win" data-value="yes">Yes</button>
                        <button type="button" class="yes-no-btn" data-field="to_win" data-value="no">No</button>
                    </div>
                </div>
                
                <div class="sales-form-group" data-role-access="superadmin,admin">
                    <label class="sales-form-label">Sales Representative <span style="color: #ff7070;">*</span></label>
                    <select class="sales-form-select" id="sales-rep-select">
                        <option value="">Select SR...</option>
                    </select>
                </div>
                
                <div class="sales-form-group" data-role-access="superadmin,admin">
                    <label class="sales-form-label">Branch <span style="color: #ff7070;">*</span></label>
                    <input type="text" class="sales-form-input" id="branch-input" readonly placeholder="Auto-filled from SR">
                </div>
                
                <div class="sales-form-group">
                    <label class="sales-form-label">W/L Amount (₱) <span id="wl-amount-required" style="color: #ff7070; display: none;">*</span></label>
                    <input type="number" class="sales-form-input" id="wl-amount-input" placeholder="0.00" step="0.01" min="0">
                </div>
                
                <div class="sales-form-group">
                    <label class="sales-form-label">Remarks <span style="color: #ff7070;">*</span></label>
                    <textarea class="sales-form-textarea" id="remarks-textarea" placeholder="Enter remarks..."></textarea>
                </div>
            </div>
        </div>
    `;

    // Show/Hide Archive Button based on user role and project archive status
    const archiveBtn = document.getElementById('archiveBtn');
    const userRole = document.body.dataset.role || '';
    
    if (archiveBtn && (userRole === 'admin' || userRole === 'superadmin')) {
        const isArchived = project.archived_at !== null && project.archived_at !== undefined;
        
        // Remove old click handler and add fresh one
        const newArchiveBtn = archiveBtn.cloneNode(true);
        archiveBtn.parentNode.replaceChild(newArchiveBtn, archiveBtn);
        
        // Reset button state
        newArchiveBtn.disabled = false;
        
        if (isArchived) {
            newArchiveBtn.innerHTML = '📤 Restore Project';
            newArchiveBtn.className = 'btn-action btn-secondary';
            newArchiveBtn.title = `Archived on ${project.archived_at}`;
        } else {
            newArchiveBtn.innerHTML = '🗄️ Archive Project';
            newArchiveBtn.className = 'btn-action btn-delete';
            newArchiveBtn.title = 'Move project to archive';
        }
        
        newArchiveBtn.style.display = 'inline-flex';
        
        // Add click handler
        newArchiveBtn.addEventListener('click', toggleProjectArchive);
    }

    modal.dataset.projectId = projectId;
    modal.dataset.assignedTo = project.assigned_to || '';
    modal.classList.add('active');
    
    // Setup sales tracking functionality (matching projects.js)
    setTimeout(() => {
        setupProjectModalSalesTracking(projectId);
    }, 0);
}

// ============================================================================
// CLEAN ASSIGNMENT FUNCTIONALITY
// ============================================================================

// Open Sales Rep Selection Modal (simplified - no location params needed)
async function openSalesRepModal() {
    console.log('[PM] openSalesRepModal called');
    
    const modal = document.getElementById('salesRepModal');
    if (!modal) {
        console.error('[PM] Sales Rep modal element not found');
        return;
    }
    
    // Reset state
    selectedProjects.clear();
    selectedSalesRepId = null;
    isProjectSelectionMode = false;
    
    try {
        // Load sales reps (no location filtering needed)
        await loadSalesRepsInModal();
        
        // Show modal
        modal.classList.add('active');
        console.log('[PM] Modal opened successfully');
    } catch (error) {
        console.error('[PM] Error in openSalesRepModal:', error);
    }
}

// Extract location data from visible projects for recommendations
function extractLocationDataFromProjects() {
    console.log('[PM] Extracting location data from projects...');
    
    const projectRows = document.querySelectorAll('#pm-table-body tr[data-project]');
    console.log('[PM] Found project rows:', projectRows.length);
    
    const locations = {
        regions: new Set(),
        provinces: new Set(),
        cities: new Set()
    };
    
    projectRows.forEach((row, index) => {
        try {
            // Get project data from data attribute
            const projectJson = row.getAttribute('data-project');
            if (projectJson) {
                const project = JSON.parse(decodeURIComponent(projectJson));
                
                // Extract region
                if (project.region && project.region !== '—' && project.region !== '-') {
                    locations.regions.add(project.region);
                }
                
                // Extract province from various fields
                const province = project.project_province || project.province || project.city_province;
                if (province && province !== '—' && province !== '-') {
                    locations.provinces.add(province);
                }
                
                // Extract city
                const city = project.project_city || project.city;
                if (city && city !== '—' && city !== '-') {
                    locations.cities.add(city);
                }
                
                console.log(`[PM] Project ${index + 1}:`, {
                    region: project.region,
                    province: province,
                    city: city
                });
            }
        } catch (e) {
            console.error('[PM] Error parsing project data:', e);
        }
    });
    
    const result = {
        region: Array.from(locations.regions)[0] || '',
        province: Array.from(locations.provinces)[0] || '',
        city: Array.from(locations.cities)[0] || '',
        // Debug info
        allRegions: Array.from(locations.regions),
        allProvinces: Array.from(locations.provinces),
        allCities: Array.from(locations.cities)
    };
    
    console.log('[PM] Extracted location data:', result);
    return result;
}

// Load Sales Reps in Modal (simple - no filtering)
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

        const response = await fetch(`${_B}/api/v1/users/sales-reps`, { credentials: 'include' });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        const salesReps = data.users || data.data || data || [];

        if (salesReps.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">👤</div>
                    <p><strong>No Sales Representatives</strong></p>
                    <p>Please create sales representatives to enable project assignment.</p>
                </div>
            `;
            return;
        }

        // Render simple list (no recommendations at this stage)
        renderSalesRepsSimple(salesReps);

    } catch (error) {
        console.error('[PM] Error loading sales reps:', error);
        const grid = document.getElementById('salesRepsGrid');
        if (grid) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-danger);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">❌</div>
                    <p><strong>Error Loading Sales Representatives</strong></p>
                    <p>${error.message}</p>
                </div>
            `;
        }
    }
}

// Simple SR rendering (no recommendations)
function renderSalesRepsSimple(salesReps) {
    const grid = document.getElementById('salesRepsGrid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    salesReps.forEach(rep => {
        const repCard = document.createElement('div');
        repCard.style.cssText = `
            background: rgba(15, 23, 42, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        `;
        
        repCard.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.5rem;">
                    ${(rep.full_name || rep.email).charAt(0).toUpperCase()}
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-primary);">
                        ${rep.full_name || rep.email}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        📧 ${rep.email}
                    </div>
                    <div style="font-size: 0.875rem; color: #fbbf24; font-weight: 600;">
                        📍 ${rep.branch || 'No branch'}
                    </div>
                </div>
            </div>
        `;
        
        repCard.addEventListener('click', () => selectSalesRepForAssignment(rep.id, rep.full_name || rep.email, rep.branch));
        repCard.addEventListener('mouseenter', () => {
            repCard.style.borderColor = '#3b82f6';
            repCard.style.transform = 'translateY(-2px)';
            repCard.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.3)';
        });
        repCard.addEventListener('mouseleave', () => {
            repCard.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            repCard.style.transform = 'translateY(0)';
            repCard.style.boxShadow = 'none';
        });
        
        grid.appendChild(repCard);
    });
}

// Render Sales Reps (original function WITH RECOMMENDATIONS)
function renderSalesReps(salesReps) {
    const grid = document.getElementById('salesRepsGrid');
    if (!grid) return;

    grid.innerHTML = '';
    
    // Separate recommended and other reps
    const recommendedReps = salesReps.filter(rep => rep.is_suggested && rep.match_score >= 85);
    const otherReps = salesReps.filter(rep => !rep.is_suggested || rep.match_score < 85);

    // Add recommendations header if there are recommended reps
    if (recommendedReps.length > 0) {
        const recommendationsHeader = document.createElement('div');
        recommendationsHeader.style.cssText = `
            grid-column: 1 / -1;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.15), rgba(245, 158, 11, 0.1));
            border: 2px solid rgba(251, 191, 36, 0.3);
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        `;
        recommendationsHeader.innerHTML = `
            <span style="font-size: 1.5rem;">✨</span>
            <div>
                <div style="font-weight: 700; color: #fbbf24; font-size: 1rem; margin-bottom: 0.25rem;">
                    Recommended Sales Representatives
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                    ${recommendedReps.length} sales ${recommendedReps.length === 1 ? 'rep' : 'reps'} matched based on branch location and project province
                </div>
            </div>
        `;
        grid.appendChild(recommendationsHeader);
    }

    // Render recommended reps first
    recommendedReps.forEach(rep => renderSalesRepCard(rep, grid, true));
    
    // Add separator if there are both recommended and other reps
    if (recommendedReps.length > 0 && otherReps.length > 0) {
        const separator = document.createElement('div');
        separator.style.cssText = `
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0;
        `;
        separator.innerHTML = `
            <div style="flex: 1; height: 1px; background: rgba(255, 255, 255, 0.1);"></div>
            <span style="color: var(--text-muted); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                Other Sales Representatives
            </span>
            <div style="flex: 1; height: 1px; background: rgba(255, 255, 255, 0.1);"></div>
        `;
        grid.appendChild(separator);
    }
    
    // Render other reps
    otherReps.forEach(rep => renderSalesRepCard(rep, grid, false));
}

// Helper function to render individual sales rep card
function renderSalesRepCard(rep, container, isRecommended) {
    const repCard = document.createElement('div');
    repCard.className = 'sr-card' + (isRecommended ? ' recommended' : '');
    
    // Different styling for recommended reps
    const baseStyle = `
        background: rgba(15, 23, 42, 0.9);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    `;
    
    const recommendedStyle = `
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.05));
        border: 2px solid rgba(251, 191, 36, 0.4);
        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.15);
    `;
    
    repCard.style.cssText = isRecommended ? baseStyle + recommendedStyle : baseStyle;
    
    // Show recommendation badge and match reason
    const recommendationBadge = isRecommended ? `
        <div style="position: absolute; top: -10px; right: -10px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #000; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 999px; box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4); z-index: 10; text-transform: uppercase; letter-spacing: 0.025em;">
            ⭐ ${rep.match_score}% Match
        </div>
    ` : '';
    
    const matchReason = (isRecommended && rep.match_reason) ? `
        <div style="margin-top: 0.75rem; padding: 0.5rem 0.75rem; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.2); border-radius: 0.5rem; font-size: 0.75rem; color: #fbbf24; font-weight: 600;">
            💡 ${rep.match_reason}
        </div>
    ` : '';
    
    const workloadInfo = rep.assigned_count !== undefined ? `
        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-dim); display: flex; align-items: center; gap: 0.5rem;">
            📊 ${rep.assigned_count} ${rep.assigned_count === 1 ? 'project' : 'projects'} assigned
        </div>
    ` : '';
    
    const onlineStatus = rep.is_online ? `
        <div style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.5rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 999px; font-size: 0.7rem; color: #6ee7b7; font-weight: 600; margin-top: 0.5rem;">
            <span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%; display: inline-block; animation: pulse 2s ease-in-out infinite;"></span>
            Online
        </div>
    ` : '';
    
    repCard.innerHTML = `
        ${recommendationBadge}
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, ${isRecommended ? '#fbbf24, #f59e0b' : '#3b82f6, #1d4ed8'}); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: ${isRecommended ? '#000' : 'white'}; font-weight: 700; font-size: 1.5rem; box-shadow: 0 4px 12px ${isRecommended ? 'rgba(251, 191, 36, 0.3)' : 'rgba(59, 130, 246, 0.3)'};">
                ${(rep.full_name || rep.email).charAt(0).toUpperCase()}
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-primary); margin-bottom: 0.35rem;">
                    ${rep.full_name || rep.email}
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">
                    📧 ${rep.email}
                </div>
                <div style="font-size: 0.875rem; color: ${isRecommended ? '#fbbf24' : 'var(--text-dim)'}; font-weight: ${isRecommended ? '600' : '400'};">
                    📍 ${rep.branch || 'No branch specified'}
                </div>
                ${workloadInfo}
                ${onlineStatus}
            </div>
        </div>
        ${matchReason}
    `;
    
    repCard.addEventListener('click', () => selectSalesRepForAssignment(rep.id, rep.full_name || rep.email, rep.branch));
    repCard.addEventListener('mouseenter', () => {
        if (isRecommended) {
            repCard.style.borderColor = '#f59e0b';
            repCard.style.backgroundColor = 'rgba(251, 191, 36, 0.15)';
            repCard.style.transform = 'translateY(-4px)';
            repCard.style.boxShadow = '0 8px 24px rgba(251, 191, 36, 0.25)';
        } else {
            repCard.style.borderColor = 'var(--primary-400)';
            repCard.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
            repCard.style.transform = 'translateY(-2px)';
        }
    });
    repCard.addEventListener('mouseleave', () => {
        if (isRecommended) {
            repCard.style.borderColor = 'rgba(251, 191, 36, 0.4)';
            repCard.style.backgroundColor = 'linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.05))';
            repCard.style.transform = 'translateY(0)';
            repCard.style.boxShadow = '0 4px 12px rgba(251, 191, 36, 0.15)';
        } else {
            repCard.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            repCard.style.backgroundColor = 'rgba(15, 23, 42, 0.9)';
            repCard.style.transform = 'translateY(0)';
        }
    });
    
    container.appendChild(repCard);
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

// Populate the sales rep modal with data (using new render function)
function populateSalesRepModal(salesReps) {
    const container = document.getElementById('salesRepsGrid');
    if (!container) {
        console.error('[Assignment] Sales rep grid container not found');
        return;
    }
    
    // Use the enhanced renderSalesReps function
    renderSalesReps(salesReps);
}

// Select Sales Rep for Assignment (using original function name)
function selectSalesRepForAssignment(salesRepId, salesRepName, salesRepBranch) {
    console.log('[PM] selectSalesRep called with:', salesRepId, salesRepName, salesRepBranch);
    
    selectedSalesRepId = salesRepId;
    selectedSalesRepName = salesRepName;
    
    console.log('[PM] State after selection:', {
        selectedSalesRepId: selectedSalesRepId,
        selectedSalesRepName: selectedSalesRepName,
        selectedSalesRepBranch: salesRepBranch
    });
    
    // Close the modal
    closeSalesRepModal();
    
    // Start project selection with branch-based recommendations
    startProjectSelectionMode(salesRepName, salesRepBranch);
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

// Start Project Selection Mode (with recommendations)
function startProjectSelectionMode(salesRepName, salesRepBranch) {
    console.log('[PM] startProjectSelectionMode called with:', salesRepName, salesRepBranch);
    console.log('[PM] selectedSalesRepId at start:', selectedSalesRepId);
    
    isProjectSelectionMode = true;
    
    // Show project selection banner and controls
    showProjectSelectionBanner(salesRepName);
    
    // Add checkboxes to project rows WITH recommendations
    addProjectCheckboxesWithRecommendations(salesRepBranch);
    
    // Show bulk action buttons
    showBulkActionButtons('assign', salesRepName);
}

// Add checkboxes with recommendations based on SR branch
function addProjectCheckboxesWithRecommendations(salesRepBranch) {
    const tbody = document.getElementById('pm-table-body');
    if (!tbody) return;
    
    const rows = Array.from(tbody.querySelectorAll('tr[data-project]'));
    
    // Calculate match scores for all projects
    const projectsWithScores = rows.map(row => {
        const projectJson = row.getAttribute('data-project');
        let matchScore = 0;
        let isRecommended = false;
        
        if (projectJson && salesRepBranch) {
            try {
                const project = JSON.parse(decodeURIComponent(projectJson));
                const result = calculateProjectMatch(project, salesRepBranch);
                isRecommended = result.isRecommended;
                matchScore = result.matchScore;
            } catch (e) {
                console.error('[PM] Error parsing project:', e);
            }
        }
        
        return { row, matchScore, isRecommended };
    });
    
    // Sort: recommended first (by score DESC), then others
    projectsWithScores.sort((a, b) => {
        if (a.isRecommended && !b.isRecommended) return -1;
        if (!a.isRecommended && b.isRecommended) return 1;
        if (a.isRecommended && b.isRecommended) return b.matchScore - a.matchScore;
        return 0;
    });
    
    // Clear tbody and re-add in sorted order
    tbody.innerHTML = '';
    
    projectsWithScores.forEach(({ row, matchScore, isRecommended }) => {
        // Add recommended styling
        if (isRecommended) {
            row.style.background = 'linear-gradient(135deg, rgba(251, 191, 36, 0.15), rgba(245, 158, 11, 0.1))';
            row.style.border = '2px solid rgba(251, 191, 36, 0.3)';
            
            // Add badge to first cell
            const firstCell = row.querySelector('td');
            if (firstCell && !firstCell.querySelector('.match-badge')) {
                const badge = document.createElement('span');
                badge.className = 'match-badge';
                badge.style.cssText = `
                    background: linear-gradient(135deg, #fbbf24, #f59e0b);
                    color: #000;
                    font-size: 0.7rem;
                    font-weight: 700;
                    padding: 0.25rem 0.5rem;
                    border-radius: 999px;
                    margin-right: 0.5rem;
                    display: inline-block;
                `;
                badge.textContent = `⭐ ${matchScore}%`;
                firstCell.insertBefore(badge, firstCell.firstChild);
            }
        }
        
        // Add checkbox
        const firstCell = row.querySelector('td');
        if (firstCell && !row.querySelector('.bulk-select-checkbox')) {
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'bulk-select-checkbox';
            checkbox.style.cssText = `
                width: 18px;
                height: 18px;
                margin-right: 0.75rem;
                cursor: pointer;
            `;
            
            checkbox.addEventListener('change', () => {
                const projectData = JSON.parse(decodeURIComponent(row.getAttribute('data-project')));
                const projectId = projectData.id;
                if (checkbox.checked) {
                    selectedProjects.add(projectId);
                } else {
                    selectedProjects.delete(projectId);
                }
                updateSelectedCount();
            });
            
            firstCell.insertBefore(checkbox, firstCell.firstChild);
        }
        
        // Add back to tbody
        tbody.appendChild(row);
    });
}

// Calculate if project matches SR branch
function calculateProjectMatch(project, salesRepBranch) {
    const branch = (salesRepBranch || '').toLowerCase();
    const province = (project.project_province || project.province || project.city_province || '').toLowerCase();
    const region = (project.region || '').toLowerCase();
    const city = (project.project_city || project.city || '').toLowerCase();
    
    let matchScore = 0;
    let isRecommended = false;
    
    // Province match (highest priority)
    if (province && branch.includes(province)) {
        matchScore = 100;
        isRecommended = true;
    } else if (province && province.includes(branch)) {
        matchScore = 95;
        isRecommended = true;
    }
    
    // Region match
    if (matchScore === 0 && region && branch.includes(region)) {
        matchScore = 85;
        isRecommended = true;
    }
    
    // City match
    if (matchScore === 0 && city && branch.includes(city)) {
        matchScore = 75;
        isRecommended = true;
    }
    
    // Special NCR/Manila handling
    const isNCRProject = region.includes('ncr') || region.includes('manila') || region.includes('national capital') ||
                         province.includes('manila') || province.includes('metro manila');
    const isNCRBranch = branch.includes('manila') || branch.includes('ncr') || branch.includes('metro manila') ||
                        branch.includes('makati') || branch.includes('quezon') || branch.includes('taguig');
    
    if (isNCRProject && isNCRBranch) {
        matchScore = Math.max(matchScore, 90);
        isRecommended = true;
    }
    
    // Special Cebu handling
    if ((province.includes('cebu') || region.includes('cebu')) && branch.includes('cebu')) {
        matchScore = 100;
        isRecommended = true;
    }
    
    // Special Davao handling
    if ((province.includes('davao') || region.includes('davao')) && branch.includes('davao')) {
        matchScore = 100;
        isRecommended = true;
    }
    
    return { matchScore, isRecommended };
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
        <span>📋 Assignment Mode: Selected Sales Rep <strong style="color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,0.4);">${salesRepName}</strong></span>
        <span style="color: rgba(255,255,255,0.95);">| Selected Projects: <span id="selectedCount" style="font-weight: 900; color: #fff; background: rgba(0,0,0,0.25); padding: 0.15rem 0.6rem; border-radius: 0.375rem; font-size: 1.05rem; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">0</span></span>
        <button onclick="exitProjectSelectionMode()" style="background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600;">Cancel</button>
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
    
    // If in unassign mode, use the unassign counter function
    if (isBulkUnassignMode) {
        updateUnassignSelectedCount();
        return;
    }
    
    const count = selectedProjects.size;
    console.log('[PM] Current selectedProjects size:', count);
    
    // Update all count elements
    const countElements = document.querySelectorAll('#selectedCount');
    countElements.forEach(el => {
        el.textContent = count;
        el.style.color = '#fff';
        el.style.background = count > 0 ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.25)';
    });
    
    const assignButtonCountElement = document.getElementById('assignButtonCount');
    if (assignButtonCountElement) {
        assignButtonCountElement.textContent = count;
    }
    
    // Update assign button state
    const inlineAssignButton = window.inlineAssignButton || document.getElementById('inlineAssignButton');
    if (inlineAssignButton) {
        if (count > 0) {
            inlineAssignButton.disabled = false;
            inlineAssignButton.style.opacity = '1';
            inlineAssignButton.style.cursor = 'pointer';
        } else {
            inlineAssignButton.disabled = true;
            inlineAssignButton.style.opacity = '0.5';
            inlineAssignButton.style.cursor = 'not-allowed';
        }
    }
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
    
    // Remove match badges and clear row styling
    const matchBadges = document.querySelectorAll('.match-badge');
    matchBadges.forEach(badge => badge.remove());
    
    const rows = document.querySelectorAll('#pm-table-body tr[data-project]');
    rows.forEach(row => {
        row.style.background = '';
        row.style.border = '';
    });
    
    // Clear button reference
    window.inlineAssignButton = null;
    
    // Reload projects to restore original table state
    loadProjects();
    
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

// Populate region filter dynamically from project data
function populateRegionFilter(projects) {
    const regionFilter = document.getElementById('regionFilter');
    if (!regionFilter) return;
    
    console.log('[PM] populateRegionFilter called with', projects.length, 'projects');
    
    // Preserve current selection
    const currentValue = regionFilter.value;
    
    // Collect unique regions from projects
    const regions = new Set();
    projects.forEach(p => {
        console.log('[PM] Project region:', p.region);
        if (p.region && p.region.trim() && p.region !== '—') {
            regions.add(p.region.trim());
        }
    });
    
    console.log('[PM] Found regions:', Array.from(regions));
    
    // Sort regions alphabetically
    const sortedRegions = [...regions].sort();
    
    // Rebuild options
    regionFilter.innerHTML = '<option value="">All Regions</option>';
    sortedRegions.forEach(region => {
        const option = document.createElement('option');
        option.value = region;
        option.textContent = region;
        regionFilter.appendChild(option);
    });
    
    console.log('[PM] Region filter populated with', sortedRegions.length, 'regions');
    
    // Restore previous selection if it still exists
    if (currentValue) {
        regionFilter.value = currentValue;
    }
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
    
    // Hide the "Bulk Unassign Projects" button bar
    const bulkUnassignBar = document.getElementById('bulkUnassignButtonBar');
    if (bulkUnassignBar) {
        bulkUnassignBar.style.display = 'none';
    }
    
    const buttonsContainer = document.createElement('div');
    buttonsContainer.id = 'bulkActionButtons';
    buttonsContainer.style.cssText = `
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        background: rgba(220, 38, 38, 0.1);
        border: 2px solid rgba(220, 38, 38, 0.3);
        border-radius: 0.75rem;
        margin-bottom: 1rem;
    `;
    
    buttonsContainer.innerHTML = `
        <div style="flex: 1;">
            <div style="font-weight: 700; color: #dc2626; font-size: 1.1rem; margin-bottom: 0.25rem;">
                🗑️ Unassign Mode: <span id="proceedCount" style="color: #ef4444;">0</span> project(s) selected
            </div>
            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                Select projects below to remove their sales rep assignments
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button onclick="exitProjectSelectionMode()" class="btn-secondary" style="padding: 0.75rem 1.5rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);">
                Cancel
            </button>
            <button id="proceedBtn" class="btn-delete" data-can-click="false" style="padding: 0.75rem 2rem; background: #dc2626; border-color: #dc2626; color: white; opacity: 0.5; cursor: not-allowed;">
                Unassign Projects (<span id="proceedBtnCount">0</span>)
            </button>
        </div>
    `;
    
    // Insert before the filters section
    const filtersSection = document.querySelector('.pm-filters');
    if (filtersSection) {
        filtersSection.parentNode.insertBefore(buttonsContainer, filtersSection);
    }
    
    // Add click handler for unassign
    const proceedBtn = document.getElementById('proceedBtn');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[PM] Unassign button clicked!');
            const canClick = this.getAttribute('data-can-click') === 'true';
            
            if (canClick && selectedProjects.size > 0) {
                console.log('[PM] Proceeding with bulk unassignment');
                proceedWithBulkUnassignment();
            } else {
                console.log('[PM] Button not clickable or no projects selected');
            }
        });
    }
    
    // Update button state
    updateUnassignSelectedCount();
}

// Update unassign selected count
function updateUnassignSelectedCount() {
    const count = selectedProjects.size;
    const countElements = document.querySelectorAll('#proceedCount, #proceedBtnCount');
    const proceedBtn = document.getElementById('proceedBtn');
    
    countElements.forEach(el => {
        if (el) el.textContent = count;
    });
    
    if (proceedBtn) {
        if (count > 0) {
            proceedBtn.disabled = false;
            proceedBtn.style.opacity = '1';
            proceedBtn.style.cursor = 'pointer';
            proceedBtn.setAttribute('data-can-click', 'true');
        } else {
            proceedBtn.disabled = true;
            proceedBtn.style.opacity = '0.5';
            proceedBtn.style.cursor = 'not-allowed';
            proceedBtn.setAttribute('data-can-click', 'false');
        }
    }
}

// Proceed with bulk unassignment
async function proceedWithBulkUnassignment() {
    console.log('[PM] proceedWithBulkUnassignment called');
    console.log('[PM] selectedProjects:', Array.from(selectedProjects));
    
    if (selectedProjects.size === 0) {
        showNotificationModal('Warning', 'Please select at least one project to unassign.', 'warning');
        return;
    }
    
    // Show confirmation modal
    const confirmed = await showConfirmationModal(
        'Confirm Bulk Unassignment',
        `Are you sure you want to unassign ${selectedProjects.size} project(s)? This will remove sales rep assignments.`,
        'warning'
    );
    
    if (!confirmed) {
        return;
    }
    
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
            
            // Reload page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
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
    if (existing) existing.remove();
    
    const colors = {
        success: { bg: 'rgba(16,185,129,0.1)',  border: '#10b981', text: '#10b981', icon: '✓' },
        error:   { bg: 'rgba(239,68,68,0.1)',   border: '#ef4444', text: '#ef4444', icon: '✕' },
        warning: { bg: 'rgba(245,158,11,0.1)',  border: '#f59e0b', text: '#f59e0b', icon: '⚠' },
        info:    { bg: 'rgba(59,130,246,0.1)',  border: '#3b82f6', text: '#3b82f6', icon: 'ℹ' }
    };
    const color = colors[type] || colors.info;

    const modal = document.createElement('div');
    modal.id = 'notificationModal';
    // Use a unique class — NOT modal-overlay — to avoid CSS display:none conflicts
    modal.className = 'pm-notification-overlay';
    modal.style.cssText = `
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(4px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease;
    `;

    modal.innerHTML = `
        <div style="
            background: #1e293b;
            border: 2px solid ${color.border};
            border-radius: 1rem;
            max-width: 500px; width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: slideInUp 0.3s ease;
            overflow: hidden;
        ">
            <div style="
                display: flex; align-items: center; gap: 1rem;
                padding: 1.75rem 2rem;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                background: ${color.bg};
            ">
                <div style="
                    width: 44px; height: 44px; border-radius: 50%;
                    background: ${color.border};
                    display: flex; align-items: center; justify-content: center;
                    color: #fff; font-size: 1.4rem; font-weight: 700; flex-shrink: 0;
                ">${color.icon}</div>
                <div style="flex:1;">
                    <h3 style="margin:0 0 0.35rem; color:${color.text}; font-size:1.1rem; font-weight:700;">${title}</h3>
                    <p style="margin:0; color:#fff; font-size:0.95rem; line-height:1.5;">${message}</p>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; padding:1.25rem 2rem; gap:0.75rem;">
                <button id="notifOkBtn" style="
                    background:${color.border}; color:#fff; border:none;
                    padding:0.65rem 2rem; border-radius:0.5rem;
                    font-size:0.95rem; font-weight:600; cursor:pointer;
                ">OK</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Close on OK
    modal.querySelector('#notifOkBtn').addEventListener('click', (e) => {
        e.stopPropagation();
        closeNotificationModal();
    });

    // Close on backdrop click (but NOT on content click)
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeNotificationModal();
    });

    // Focus OK button
    setTimeout(() => modal.querySelector('#notifOkBtn')?.focus(), 50);
}

// Show confirmation modal (replaces confirm)
function showConfirmationModal(title, message, onConfirm, onCancel = null) {
    const existing = document.getElementById('confirmationModal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'confirmationModal';
    modal.className = 'pm-notification-overlay';
    modal.style.cssText = `
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(4px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease;
    `;

    modal.innerHTML = `
        <div style="
            background: #1e293b;
            border: 2px solid #3b82f6;
            border-radius: 1rem;
            max-width: 500px; width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            overflow: hidden;
        ">
            <div style="display:flex; align-items:center; gap:1rem; padding:1.75rem 2rem; border-bottom:1px solid rgba(255,255,255,0.1);">
                <div style="width:44px; height:44px; border-radius:50%; background:#3b82f6; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.4rem; font-weight:700; flex-shrink:0;">?</div>
                <div style="flex:1;">
                    <h3 style="margin:0 0 0.35rem; color:#fff; font-size:1.1rem; font-weight:700;">${title}</h3>
                    <p style="margin:0; color:#9ca3af; font-size:0.95rem; line-height:1.5;">${message}</p>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; padding:1.25rem 2rem; gap:0.75rem;">
                <button id="cancelConfirmBtn" style="background:rgba(107,114,128,0.2); border:1px solid rgba(107,114,128,0.4); color:#fff; padding:0.65rem 1.5rem; border-radius:0.5rem; font-size:0.95rem; font-weight:600; cursor:pointer;">Cancel</button>
                <button id="confirmBtn" style="background:#3b82f6; color:#fff; border:none; padding:0.65rem 2rem; border-radius:0.5rem; font-size:0.95rem; font-weight:600; cursor:pointer;">OK</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    modal.querySelector('#confirmBtn').addEventListener('click', (e) => {
        e.stopPropagation();
        closeConfirmationModal();
        setTimeout(() => { if (onConfirm) onConfirm(); }, 100);
    });
    modal.querySelector('#cancelConfirmBtn').addEventListener('click', (e) => {
        e.stopPropagation();
        closeConfirmationModal();
        if (onCancel) onCancel();
    });
    modal.addEventListener('click', (e) => {
        if (e.target === modal) { closeConfirmationModal(); if (onCancel) onCancel(); }
    });

    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            closeConfirmationModal();
            if (onCancel) onCancel();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);

    setTimeout(() => modal.querySelector('#confirmBtn')?.focus(), 100);
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

// ============================================================================
// ARCHIVE FUNCTIONALITY  
// ============================================================================

/**
 * Toggle project archive/restore
 */
async function toggleProjectArchive() {
    const modal = document.getElementById('detailsModal');
    const projectId = modal?.dataset?.projectId;
    
    if (!projectId) {
        console.error('No project ID found');
        return;
    }
    
    // Find the project to check current archive status
    let project = null;
    if (window.currentProjectsData && window.currentProjectsData.projects) {
        project = window.currentProjectsData.projects.find(p => p.id == projectId);
    }
    
    if (!project) {
        console.error('Project not found');
        return;
    }
    
    const isArchived = project.archived_at !== null && project.archived_at !== undefined;
    const action = isArchived ? 'restore' : 'archive';
    const actionText = isArchived ? 'Restore' : 'Archive';
    
    // Show confirmation dialog
    const confirmed = await showConfirmationModal(
        `${actionText} Project`,
        `Are you sure you want to ${action} "${project.project_name || 'this project'}"?`,
        isArchived ? 'warning' : 'danger'
    );
    
    if (!confirmed) return;
    
    try {
        // Show loading state
        const archiveBtn = document.getElementById('archiveBtn');
        if (archiveBtn) {
            archiveBtn.innerHTML = '⏳ Processing...';
            archiveBtn.disabled = true;
        }
        
        // Call archive API
        const method = isArchived ? 'PUT' : 'POST';
        const response = await fetch(`${_B}/api/v1/projects/archive`, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({ project_id: parseInt(projectId) })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Show success notification
            showNotificationToast(
                `Project ${isArchived ? 'restored' : 'archived'} successfully`,
                'success'
            );
            
            // Reload the page to refresh everything
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            
        } else {
            throw new Error(result.message || `Failed to ${action} project`);
        }
        
    } catch (error) {
        console.error('Archive error:', error);
        
        // Show error notification
        showNotificationToast(
            `Failed to ${action} project: ${error.message}`,
            'error'
        );
        
        // Reset button state
        const archiveBtn = document.getElementById('archiveBtn');
        if (archiveBtn) {
            archiveBtn.innerHTML = isArchived ? '📤 Restore Project' : '🗄️ Archive Project';
            archiveBtn.disabled = false;
        }
    }
}

/**
 * Show notification toast
 */
function showNotificationToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        max-width: 400px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 0.75rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        font-weight: 600;
        font-size: 0.9rem;
    `;
    
    // Add icon based on type
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    toast.innerHTML = `${icon} ${message}`;
    
    // Add to document
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 5 seconds
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 5000);
}

/**
 * Show confirmation modal
 */
function showConfirmationModal(title, message, type = 'warning') {
    return new Promise((resolve) => {
        // Create modal elements
        const overlay = document.createElement('div');
        overlay.className = 'confirmation-modal-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 100001;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.2s ease;
        `;
        
        const modal = document.createElement('div');
        modal.style.cssText = `
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: slideInUp 0.3s ease;
            position: relative;
            z-index: 100002;
        `;
        
        const iconColor = type === 'danger' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6';
        const icon = type === 'danger' ? '🗑️' : type === 'warning' ? '⚠️' : 'ℹ️';
        
        modal.innerHTML = `
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">${icon}</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
                    ${title}
                </h3>
                <p style="color: var(--text-secondary); line-height: 1.5;">
                    ${message}
                </p>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button class="confirm-cancel-btn" style="
                    flex: 1;
                    padding: 0.75rem 1.5rem;
                    background: rgba(107, 114, 128, 0.2);
                    border: 1px solid rgba(107, 114, 128, 0.4);
                    border-radius: 0.75rem;
                    color: var(--text-primary);
                    font-size: 0.9rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    font-family: var(--font);
                ">Cancel</button>
                
                <button class="confirm-action-btn" style="
                    flex: 1;
                    padding: 0.75rem 1.5rem;
                    background: ${iconColor};
                    border: 1px solid ${iconColor};
                    border-radius: 0.75rem;
                    color: white;
                    font-size: 0.9rem;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    font-family: var(--font);
                ">${title}</button>
            </div>
        `;
        
        // Add event listeners
        const cancelBtn = modal.querySelector('.confirm-cancel-btn');
        const actionBtn = modal.querySelector('.confirm-action-btn');
        
        cancelBtn.addEventListener('click', () => {
            overlay.remove();
            resolve(false);
        });
        
        actionBtn.addEventListener('click', () => {
            overlay.remove();
            resolve(true);
        });
        
        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
                resolve(false);
            }
        });
        
        // Add hover effects
        cancelBtn.addEventListener('mouseenter', () => {
            cancelBtn.style.background = 'rgba(107, 114, 128, 0.3)';
        });
        cancelBtn.addEventListener('mouseleave', () => {
            cancelBtn.style.background = 'rgba(107, 114, 128, 0.2)';
        });
        
        actionBtn.addEventListener('mouseenter', () => {
            actionBtn.style.transform = 'translateY(-2px)';
            actionBtn.style.boxShadow = `0 4px 12px ${iconColor}40`;
        });
        actionBtn.addEventListener('mouseleave', () => {
            actionBtn.style.transform = 'translateY(0)';
            actionBtn.style.boxShadow = 'none';
        });
        
        // Add to document
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
    });
}


// ============================================================================
// SALES TRACKING FUNCTIONS (Copied from projects.js)
// ============================================================================

async function setupProjectModalSalesTracking(projectId) {
    // Setup yes/no buttons
    setupProgressiveFieldsPM();
    
    // Load sales reps for admin/superadmin
    const userRole = document.body.dataset.role;
    if (userRole === 'admin' || userRole === 'superadmin') {
        await loadSalesRepsPM();
    }

    // Apply role-based visibility
    applyRoleVisibilityPM(userRole);

    // Admin: hide tracking section if project is assigned
    if (userRole === 'admin') {
        const modal = document.getElementById('detailsModal');
        const assignedTo = modal?.dataset?.assignedTo || '';
        const isAssigned = assignedTo !== '' && assignedTo !== '0' && assignedTo !== 'null';
        const trackingSection = document.querySelector('.sales-tracking-section');
        const saveBtn = document.querySelector('button[onclick="saveSalesTracking()"]');
        if (isAssigned) {
            if (trackingSection) trackingSection.style.display = 'none';
            if (saveBtn)        saveBtn.style.display = 'none';
        } else {
            if (trackingSection) trackingSection.style.display = '';
            if (saveBtn)        saveBtn.style.display = '';
        }
    }
    
    // Load existing sales tracking data
    await loadSalesTrackingDataPM(projectId);
}

function applyRoleVisibilityPM(userRole) {
    document.querySelectorAll('[data-role-access]').forEach(el => {
        const allowed = el.dataset.roleAccess.split(',').map(r => r.trim());
        el.style.display = allowed.includes(userRole) ? '' : 'none';
    });
}

function setupProgressiveFieldsPM() {
    const body = document.getElementById('detailsModalBody');
    if (!body) return;

    // Clear all button states first
    body.querySelectorAll('.yes-no-btn').forEach(btn => {
        btn.classList.remove('active', 'yes', 'no');
    });
    
    // Update field states
    updateFieldStatesPM();
    
    // Setup button handlers
    body.querySelectorAll('.yes-no-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const field = e.target.dataset.field;
            const value = e.target.dataset.value;
            
            // Check if field is enabled
            if (!isFieldEnabledPM(field)) {
                showNotificationModal('Warning', 'Please complete the previous fields first', 'warning');
                return;
            }
            
            // Update button states
            const buttons = body.querySelectorAll(`.yes-no-btn[data-field="${field}"]`);
            buttons.forEach(b => {
                b.classList.remove('active', 'yes', 'no');
            });
            
            e.target.classList.add('active', value);
            
            // Show/hide W/L Amount required
            if (field === 'to_win') {
                const wlAmountRequired = document.getElementById('wl-amount-required');
                if (wlAmountRequired) {
                    wlAmountRequired.style.display = value === 'yes' ? 'inline' : 'none';
                }
            }
            
            updateFieldStatesPM();
        });
    });
}

function isFieldEnabledPM(field) {
    const body = document.getElementById('detailsModalBody');
    const fieldOrder = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
    const currentIndex = fieldOrder.indexOf(field);
    if (currentIndex === 0) return true;
    for (let i = 0; i < currentIndex; i++) {
        const prevField = fieldOrder[i];
        const hasSelection = body?.querySelector(`.yes-no-btn[data-field="${prevField}"].active`);
        if (!hasSelection) return false;
    }
    return true;
}

function updateFieldStatesPM() {
    const body = document.getElementById('detailsModalBody');
    if (!body) return;
    const fieldOrder = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
    fieldOrder.forEach(field => {
        const buttons = body.querySelectorAll(`.yes-no-btn[data-field="${field}"]`);
        const isEnabled = isFieldEnabledPM(field);
        buttons.forEach(btn => {
            if (isEnabled) {
                btn.classList.remove('disabled');
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            } else {
                btn.classList.add('disabled');
                btn.style.opacity = '0.4';
                btn.style.cursor = 'not-allowed';
            }
        });
    });
}

async function loadSalesRepsPM() {
    try {
        const response = await fetch(`${_B}/api/v1/users/sales-reps`, {
            credentials: 'include'
        });
        
        if (!response.ok) return;
        
        const result = await response.json();
        const select = document.getElementById('sales-rep-select');
        const branchInput = document.getElementById('branch-input');
        
        if (!select) return;
        
        select.innerHTML = '<option value="">Select SR...</option>';
        
        const salesReps = (result.data || result.users || []).slice().sort((a, b) =>
            (a.full_name || '').localeCompare(b.full_name || '')
        );
        
        salesReps.forEach(sr => {
            const option = document.createElement('option');
            option.value = sr.id;
            option.textContent = sr.full_name;
            option.dataset.branch = sr.branch || 'N/A';
            select.appendChild(option);
        });
        
        // Auto-fill branch when SR is selected
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (branchInput && selectedOption) {
                branchInput.value = selectedOption.dataset.branch || '';
            }
        });

        // Auto-select current logged-in user if they are in the list (default for admin role)
        // Prefer body data-user-id (set server-side) over localStorage for reliability
        const bodyUserId = parseInt(document.body.dataset.userId || '0');
        const cachedUser = typeof Auth !== 'undefined' ? Auth.getUser() : null;
        const currentUserId = bodyUserId || (cachedUser ? parseInt(cachedUser.id) : 0);
        if (currentUserId) {
            const matchingOption = Array.from(select.options).find(o => parseInt(o.value) === currentUserId);
            if (matchingOption) {
                select.value = matchingOption.value;
                // Dispatch change to trigger branch auto-fill
                select.dispatchEvent(new Event('change'));
            }
        }
    } catch (error) {
        console.error('Load sales reps error:', error);
    }
}

async function loadSalesTrackingDataPM(projectId) {
    try {
        const response = await fetch(`${_B}/api/v1/projects/${projectId}/sales-tracking`, {
            method: 'GET',
            credentials: 'include'
        });
        
        if (!response.ok) return;
        
        const result = await response.json();
        
        if (result.exists && result.data) {
            const data = result.data;
            const body = document.getElementById('detailsModalBody');

            // Restore button states scoped to detailsModalBody only
            const fields = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
            fields.forEach(field => {
                const value = data[field];
                // Clear & enable all buttons for this field
                body.querySelectorAll(`.yes-no-btn[data-field="${field}"]`).forEach(b => {
                    b.classList.remove('active', 'yes', 'no', 'disabled');
                    b.style.opacity = '1';
                    b.style.cursor = 'pointer';
                });
                if (value === true) {
                    const btn = body.querySelector(`.yes-no-btn[data-field="${field}"][data-value="yes"]`);
                    if (btn) btn.classList.add('active', 'yes');
                } else if (value === false) {
                    const btn = body.querySelector(`.yes-no-btn[data-field="${field}"][data-value="no"]`);
                    if (btn) btn.classList.add('active', 'no');
                }
            });

            // Enable ALL buttons in modal (no progressive locking when loading existing data)
            body.querySelectorAll('.yes-no-btn').forEach(b => {
                b.classList.remove('disabled');
                b.style.opacity = '1';
                b.style.cursor = 'pointer';
            });
            
            // Restore form fields
            const salesRepSelect = document.getElementById('sales-rep-select');
            if (salesRepSelect && data.sales_rep_id) {
                salesRepSelect.value = data.sales_rep_id;
                salesRepSelect.dispatchEvent(new Event('change'));
            }
            
            const branchInput = document.getElementById('branch-input');
            if (branchInput && data.branch) {
                branchInput.value = data.branch;
            }
            
            const wlAmountInput = document.getElementById('wl-amount-input');
            if (wlAmountInput && data.wa_amount) {
                wlAmountInput.value = data.wa_amount;
            }
            
            const remarksTextarea = document.getElementById('remarks-textarea');
            if (remarksTextarea && data.notes) {
                remarksTextarea.value = data.notes;
            }
            // Do NOT call updateFieldStatesPM() here — it would re-dim the buttons
        }
    } catch (error) {
        console.error('Load sales tracking error:', error);
    }
}

function showActualProjectModalPM(projectId) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000; animation: fadeIn 0.2s;';
    
    const modalBox = document.createElement('div');
    modalBox.style.cssText = 'background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 2rem; border-radius: 1rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); animation: slideUp 0.3s;';
    
    modalBox.innerHTML = `
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">⚠️</div>
            <h2 style="color: #ff8c00; font-size: 1.5rem; margin: 0 0 0.5rem 0;">Actual Project</h2>
            <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin: 0;">Is this a legitimate project?</p>
        </div>
        
        <div style="background: rgba(255, 128, 0, 0.1); border: 2px solid rgba(255, 128, 0, 0.3); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
            <div class="yes-no-buttons" style="display: flex; gap: 1rem; justify-content: center;">
                <button type="button" class="actual-project-btn" data-value="yes" style="flex: 1; padding: 0.75rem 1.5rem; border: 2px solid rgba(34, 197, 94, 0.5); background: rgba(34, 197, 94, 0.1); color: #22c55e; border-radius: 0.5rem; cursor: pointer; font-weight: 600; transition: all 0.2s;">Yes</button>
                <button type="button" class="actual-project-btn" data-value="no" style="flex: 1; padding: 0.75rem 1.5rem; border: 2px solid rgba(239, 68, 68, 0.5); background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 0.5rem; cursor: pointer; font-weight: 600; transition: all 0.2s;">No</button>
            </div>
            <small style="display: block; margin-top: 0.75rem; color: rgba(255,255,255,0.6); font-size: 0.75rem; text-align: center;">
                Select "No" if this is spam, duplicate, or invalid.
            </small>
        </div>
        
        <div style="display: flex; gap: 0.75rem; justify-content: center;">
            <button id="actualProjectSaveBtn" disabled style="padding: 0.75rem 2rem; background: #ff8c00; color: white; border: none; border-radius: 0.5rem; cursor: not-allowed; font-weight: 600; opacity: 0.5; transition: all 0.2s;">
                Save
            </button>
        </div>
    `;
    
    overlay.appendChild(modalBox);
    document.body.appendChild(overlay);
    
    let selectedValue = null;
    const saveBtn = modalBox.querySelector('#actualProjectSaveBtn');
    
    // Prevent overlay clicks from closing
    overlay.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    
    // Button click handlers
    modalBox.querySelectorAll('.actual-project-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            selectedValue = btn.dataset.value;
            
            console.log('[ACTUAL PROJECT] Selected:', selectedValue);
            
            // Update button states - keep visible
            modalBox.querySelectorAll('.actual-project-btn').forEach(b => {
                b.style.opacity = '0.4';
                b.style.transform = 'scale(1)';
                b.style.borderWidth = '2px';
            });
            btn.style.opacity = '1';
            btn.style.transform = 'scale(1.05)';
            btn.style.borderWidth = '3px';
            btn.style.boxShadow = '0 0 20px ' + (selectedValue === 'yes' ? 'rgba(34, 197, 94, 0.5)' : 'rgba(239, 68, 68, 0.5)');
            
            // Enable save button
            saveBtn.disabled = false;
            saveBtn.style.cursor = 'pointer';
            saveBtn.style.opacity = '1';
        });
        
        // Hover effects
        btn.addEventListener('mouseenter', () => {
            if (!btn.style.transform.includes('1.05')) {
                btn.style.transform = 'scale(1.02)';
            }
        });
        btn.addEventListener('mouseleave', () => {
            if (!btn.style.transform.includes('1.05')) {
                btn.style.transform = 'scale(1)';
            }
        });
    });
    
    // Save button handler
    saveBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (!selectedValue) {
            console.error('[ACTUAL PROJECT] No value selected');
            return;
        }
        
        console.log('[ACTUAL PROJECT] Saving:', selectedValue);
        
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
        saveBtn.style.opacity = '0.7';
        
        try {
            console.log('[ACTUAL PROJECT] API URL:', `${_B}/api/v1/projects/${projectId}/actual-project`);
            
            const response = await fetch(`${_B}/api/v1/projects/${projectId}/actual-project`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ is_actual_project: selectedValue })
            });
            
            console.log('[ACTUAL PROJECT] Response status:', response.status);
            
            if (response.ok) {
                const result = await response.json();
                console.log('[ACTUAL PROJECT] Success:', result);
                
                showNotificationModal('Success', 'Project status saved successfully!', 'success');
                
                // Wait a bit before closing
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // Remove overlay
                overlay.remove();
                
                // Close details modal and reload after notification shows
                setTimeout(() => {
                    closeDetailsModal();
                    
                    // Reload projects
                    loadProjects();
                }, 1500);
            } else {
                const errorData = await response.json();
                console.error('[ACTUAL PROJECT] Error response:', errorData);
                throw new Error(errorData.detail || 'Failed to save');
            }
        } catch (error) {
            console.error('[ACTUAL PROJECT] Error saving:', error);
            showNotificationModal('Error', 'Failed to save. Please try again.', 'error');
            saveBtn.textContent = 'Save';
            saveBtn.disabled = false;
            saveBtn.style.opacity = '1';
        }
    });
}

async function saveSalesTracking() {
    const modal = document.getElementById('detailsModal');
    const projectId = parseInt(modal.dataset.projectId);
    
    if (!projectId) return;
    
    // Collect data
    const body = document.getElementById('detailsModalBody');
    const toWin = body.querySelector('.yes-no-btn[data-field="to_win"].active')?.dataset.value;
    const sql = body.querySelector('.yes-no-btn[data-field="sales_qualified"].active')?.dataset.value;
    const contacted = body.querySelector('.yes-no-btn[data-field="contacted"].active')?.dataset.value;
    const quoted = body.querySelector('.yes-no-btn[data-field="quoted"].active')?.dataset.value;
    const salesRepId = document.getElementById('sales-rep-select')?.value;
    const branch = document.getElementById('branch-input')?.value;
    const wlAmount = document.getElementById('wl-amount-input')?.value;
    const remarks = document.getElementById('remarks-textarea')?.value;
    
    // Validation
    const errors = [];
    
    if (!salesRepId) errors.push('Please select a Sales Representative');
    if (!branch || branch.trim() === '') errors.push('Please enter Branch information');
    if (!remarks || remarks.trim() === '') errors.push('Please enter Remarks');
    
    if (toWin === 'yes' && (!wlAmount || parseFloat(wlAmount) <= 0)) {
        errors.push('W/L Amount is required when "To Win" is Yes');
    }
    
    if (errors.length > 0) {
        showNotificationModal('Validation Error', errors[0], 'warning');
        return;
    }
    
    const data = {
        contacted: contacted === 'yes' ? true : (contacted === 'no' ? false : null),
        quoted: quoted === 'yes' ? true : (quoted === 'no' ? false : null),
        sales_qualified: sql === 'yes' ? true : (sql === 'no' ? false : null),
        to_win: toWin === 'yes' ? true : (toWin === 'no' ? false : null),
        sales_rep_id: salesRepId ? parseInt(salesRepId) : null,
        branch: branch || null,
        wa_amount: wlAmount ? parseFloat(wlAmount) : null,
        remarks: remarks ? remarks.trim() : null
    };
    
    try {
        const saveBtn = document.querySelector('button[onclick="saveSalesTracking()"]');
        if (saveBtn) {
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
        }
        
        const response = await fetch(`${_B}/api/v1/projects/${projectId}/sales-tracking`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            // Show Actual Project modal
            showActualProjectModalPM(projectId);
            
            if (saveBtn) {
                saveBtn.textContent = '💾 Save Sales Tracking';
                saveBtn.disabled = false;
            }
        } else {
            const errorData = await response.json();
            throw new Error(errorData.detail || 'Failed to save sales tracking');
        }
        
    } catch (error) {
        console.error('Save sales tracking error:', error);
        showNotificationModal('Error', 'Failed to save sales tracking. Please try again.', 'error');
        
        const saveBtn = document.querySelector('button[onclick="saveSalesTracking()"]');
        if (saveBtn) {
            saveBtn.textContent = '💾 Save Sales Tracking';
            saveBtn.disabled = false;
        }
    }
}
