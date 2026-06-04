/* ============================================================
   my-projects.js — My Projects Page (Sales Rep)
   ============================================================ */

let currentView = 'assigned';
let currentPage = 1;
let currentFilters = {
    search: '',
    region: '',
    status: ''
};
let selectedProjectId = null;
let _detailsProject = null;

const _B = (typeof BASE !== 'undefined') ? BASE : '/new-dashboard';
const userId = (typeof CURRENT_USER_ID !== 'undefined') ? CURRENT_USER_ID : 0;

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize role manager
    await RoleManager.init();
    
    // Get current view from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentView = urlParams.get('view') || 'assigned';
    
    // Load initial data
    loadProjects();
    loadCounts();
    
    // Filter handlers
    document.getElementById('searchInput').addEventListener('input', debounce(() => {
        currentFilters.search = document.getElementById('searchInput').value;
        currentPage = 1;
        loadProjects();
    }, 500));
    
    document.getElementById('regionFilter').addEventListener('change', () => {
        currentFilters.region = document.getElementById('regionFilter').value;
        currentPage = 1;
        loadProjects();
    });
    
    document.getElementById('statusFilter').addEventListener('change', () => {
        currentFilters.status = document.getElementById('statusFilter').value;
        currentPage = 1;
        loadProjects();
    });

    // Yes/No button handlers for sales tracking
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('yes-no-btn')) {
            const field = e.target.dataset.field;
            const value = e.target.dataset.value;
            
            // Remove active class from siblings
            const siblings = e.target.parentElement.querySelectorAll('.yes-no-btn');
            siblings.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            e.target.classList.add('active');
            
            // Update hidden input
            const hiddenInput = document.getElementById(field);
            if (hiddenInput) {
                hiddenInput.value = value;
            }
        }
    });
});

// Load projects
async function loadProjects() {
    const tbody = document.getElementById('pm-table-body');
    
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>';
    
    try {
        // Determine endpoint based on view
        let endpoint = currentView === 'assigned' 
            ? `${_B}/api/v1/projects/assigned`
            : `${_B}/api/v1/projects/processed`;
        
        const params = new URLSearchParams({
            page: currentPage,
            size: 20,
            sales_rep_id: userId,
            ...currentFilters
        });
        
        const res = await fetch(`${endpoint}?${params}`, { credentials: 'include' });
        if (!res.ok) throw new Error('Failed to load projects');
        
        const data = await res.json();
        const projects = data.projects || [];
        
        // Render table body
        if (projects.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-dim);">No projects found</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-danger);">Error loading projects</td></tr>';
    }
}

// Get table row
function getTableRow(p) {
    const date = p.assigned_at 
        ? new Date(p.assigned_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
        : new Date(p.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    const value = (p.project_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    const statusClass = (p.status || '').toLowerCase().replace(/\s+/g, '-');
    const lastContact = p.last_contact_date 
        ? new Date(p.last_contact_date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
        : '—';
    
    return `<tr data-project="${encodeURIComponent(JSON.stringify(p))}" style="cursor:pointer">
        <td>${date}</td>
        <td style="font-weight:500;">${p.contractor_name || '—'}</td>
        <td>${p.project_name || '—'}</td>
        <td>${p.region || '—'}</td>
        <td style="text-align:right;">₱${value}</td>
        <td><span class="status-badge status-${statusClass}">${p.status || '—'}</span></td>
        <td>${lastContact}</td>
    </tr>`;
}

// Row click handler
function rowClickHandler(e) {
    const row = e.currentTarget;
    try {
        const dataAttr = row && row.dataset && row.dataset.project ? row.dataset.project : null;
        const project = dataAttr ? JSON.parse(decodeURIComponent(dataAttr)) : null;
        if (project && project.id) openDetailsModal(project.id, row);
    } catch (err) {
        console.error('Row click handler error:', err);
    }
}

// Open details modal
function openDetailsModal(projectId, rowEl) {
    try {
        const dataAttr = rowEl && rowEl.dataset && rowEl.dataset.project ? rowEl.dataset.project : null;
        const project = dataAttr ? JSON.parse(decodeURIComponent(dataAttr)) : null;
        _detailsProject = project || { id: projectId };

        const modal = document.getElementById('detailsModal');
        const body = document.getElementById('detailsModalBody');
        if (!modal || !body) return;

        // Format values (same as projects.js)
        const value = _detailsProject.project_value !== null && _detailsProject.project_value !== undefined
            ? '₱' + (_detailsProject.project_value).toLocaleString('en-PH', { minimumFractionDigits: 2 })
            : '—';
        
        const dateTime = _detailsProject.created_at 
            ? new Date(_detailsProject.created_at).toLocaleString('en-PH', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            })
            : '—';

        const assignedDate = _detailsProject.assigned_at 
            ? new Date(_detailsProject.assigned_at).toLocaleString('en-PH', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            })
            : '—';

        // Use EXACT same HTML structure as projects.js
        body.innerHTML = `
            <!-- Contractor Section -->
            <div class="detail-section">
                <div class="detail-section-title">Contractor Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Contractor Name</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.contractor_name || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Person</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.contact_person || '—').replace(/"/g, '')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.contact_number || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Address</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.address || '—')}</div>
                    </div>
                </div>
            </div>

            <!-- Project Section -->
            <div class="detail-section">
                <div class="detail-section-title">Project Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Project Name</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.project_name || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Region</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.region || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">City/Province</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.city_province || _detailsProject.city || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Source</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.source || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-${(_detailsProject.status||'').toLowerCase().replace(/\s+/g,'-')}">${escapeHtml(_detailsProject.status || '—')}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Project Value</div>
                        <div class="detail-value large">${value}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Publication Date</div>
                        <div class="detail-value">${_detailsProject.publication_date || _detailsProject.published_date || '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Encoded On</div>
                        <div class="detail-value">${dateTime}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Assigned On</div>
                        <div class="detail-value">${assignedDate}</div>
                    </div>
                </div>
            </div>

            <!-- Materials Section -->
            <div class="detail-section">
                <div class="detail-section-title">Materials</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Sheet Pile</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.sheet_pile_type || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Sheet Pile Amount</div>
                        <div class="detail-value">${_detailsProject.sheet_pile_amount ? '₱' + (_detailsProject.sheet_pile_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">DRBs</div>
                        <div class="detail-value">${escapeHtml(_detailsProject.drbs || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">DRBs Value</div>
                        <div class="detail-value">${_detailsProject.drbs_value ? '₱' + (_detailsProject.drbs_value).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">MS Plate</div>
                        <div class="detail-value">${_detailsProject.ms_plate ? '₱' + (_detailsProject.ms_plate).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Angle Bars</div>
                        <div class="detail-value">${_detailsProject.angle_bars ? '₱' + (_detailsProject.angle_bars).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Channel Bars</div>
                        <div class="detail-value">${_detailsProject.channel_bars ? '₱' + (_detailsProject.channel_bars).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Wide Flange</div>
                        <div class="detail-value">${_detailsProject.wide_flange ? '₱' + (_detailsProject.wide_flange).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">GI/BI</div>
                        <div class="detail-value">${_detailsProject.gi_bi ? '₱' + (_detailsProject.gi_bi).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Accomplishment Rate</div>
                        <div class="detail-value">${_detailsProject.accomplishment_rate ? _detailsProject.accomplishment_rate + '%' : '—'}</div>
                    </div>
                </div>
            </div>

            <!-- Sales Tracking Section (Hidden for Encoders) -->
            <div class="sales-tracking-section" data-role-access="superadmin,admin,sales_rep">
                <div class="sales-tracking-title">Sales Tracking</div>
                <div class="sales-form-grid">
                    <!-- Left Column -->
                    <div class="sales-form-group">
                        <label class="sales-form-label">Contacted <span style="color: #ff7070;">*</span></label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn yes" data-field="contacted" data-value="yes" data-required="true">Yes</button>
                            <button type="button" class="yes-no-btn no" data-field="contacted" data-value="no" data-required="true">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Quoted <span style="color: #ff7070;">*</span></label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn yes" data-field="quoted" data-value="yes" data-required="true">Yes</button>
                            <button type="button" class="yes-no-btn no" data-field="quoted" data-value="no" data-required="true">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Sales Qualified Leads <span style="color: #ff7070;">*</span></label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn yes" data-field="sql" data-value="yes" data-required="true">Yes</button>
                            <button type="button" class="yes-no-btn no" data-field="sql" data-value="no" data-required="true">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">To Win <span style="color: #ff7070;">*</span></label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn yes" data-field="to_win" data-value="yes" data-required="true">Yes</button>
                            <button type="button" class="yes-no-btn no" data-field="to_win" data-value="no" data-required="true">No</button>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="sales-form-group">
                        <label class="sales-form-label">Sales Representative <span style="color: #ff7070;">*</span></label>
                        <input type="text" class="sales-form-input" id="sales-rep-display" readonly value="${escapeHtml(_detailsProject.assigned_to_name || '—')}" style="background: rgba(255, 255, 255, 0.05); cursor: not-allowed;">
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Branch <span style="color: #ff7070;">*</span></label>
                        <input type="text" class="sales-form-input" id="branch-display" readonly value="${escapeHtml(_detailsProject.assigned_to_branch || '—')}" style="background: rgba(255, 255, 255, 0.05); cursor: not-allowed;">
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">WA Amount (₱) <span style="color: #ff7070;">*</span></label>
                        <input type="number" class="sales-form-input" id="wa-amount-input" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Remarks <span style="color: #ff7070;">*</span></label>
                        <textarea class="sales-form-textarea" id="remarks-textarea" placeholder="Enter remarks..." required></textarea>
                    </div>
                </div>
            </div>
        `;

        // Add action buttons to modal-actions div
        const modalActions = modal.querySelector('.modal-actions');
        if (modalActions) {
            modalActions.innerHTML = `<button class="btn-action btn-primary" id="detailsSaveBtn"><span>Save Changes</span></button>`;
        }

        // Attach event listeners
        setTimeout(() => {
            // Yes/No button handlers
            document.querySelectorAll('.yes-no-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const field = this.dataset.field;
                    const buttons = document.querySelectorAll(`.yes-no-btn[data-field="${field}"]`);
                    buttons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Save button handler
            const saveBtn = document.getElementById('detailsSaveBtn');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    saveTrackingFromModal();
                });
            }

            // Load existing tracking data if available
            if (_detailsProject.is_processed == 1) {
                loadTrackingData(_detailsProject.id);
            }
        }, 0);

        modal.classList.add('active');
    } catch (err) {
        console.error('Error opening details modal:', err);
    }
}

// Close details modal
function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    if (modal) modal.classList.remove('active');
    _detailsProject = null;
}

// Load tracking data into the modal form
async function loadTrackingData(projectId) {
    try {
        const res = await fetch(`${_B}/api/v1/projects/${projectId}/sales-tracking`, { credentials: 'include' });
        if (res.ok) {
            const data = await res.json();
            if (data.exists && data.tracking) {
                const t = data.tracking;
                
                // Set Yes/No buttons
                if (t.contacted) {
                    const btn = document.querySelector(`.yes-no-btn[data-field="contacted"][data-value="${t.contacted}"]`);
                    if (btn) btn.classList.add('active');
                }
                
                if (t.quoted) {
                    const btn = document.querySelector(`.yes-no-btn[data-field="quoted"][data-value="${t.quoted}"]`);
                    if (btn) btn.classList.add('active');
                }
                
                if (t.sql) {
                    const btn = document.querySelector(`.yes-no-btn[data-field="sql"][data-value="${t.sql}"]`);
                    if (btn) btn.classList.add('active');
                }
                
                if (t.to_win) {
                    const btn = document.querySelector(`.yes-no-btn[data-field="to_win"][data-value="${t.to_win}"]`);
                    if (btn) btn.classList.add('active');
                }
                
                // Set other fields
                const waAmountInput = document.getElementById('wa-amount-input');
                const remarksTextarea = document.getElementById('remarks-textarea');
                
                if (waAmountInput) waAmountInput.value = t.wa_amount || '';
                if (remarksTextarea) remarksTextarea.value = t.remarks || '';
            }
        }
    } catch (err) {
        console.error('Error loading tracking data:', err);
    }
}

// Save tracking from modal
async function saveTrackingFromModal() {
    // Get values from Yes/No buttons
    const contactedBtn = document.querySelector('.yes-no-btn[data-field="contacted"].active');
    const quotedBtn = document.querySelector('.yes-no-btn[data-field="quoted"].active');
    const sqlBtn = document.querySelector('.yes-no-btn[data-field="sql"].active');
    const toWinBtn = document.querySelector('.yes-no-btn[data-field="to_win"].active');
    
    const contacted = contactedBtn ? contactedBtn.dataset.value : null;
    const quoted = quotedBtn ? quotedBtn.dataset.value : null;
    const sql = sqlBtn ? sqlBtn.dataset.value : null;
    const toWin = toWinBtn ? toWinBtn.dataset.value : null;
    
    const waAmount = document.getElementById('wa-amount-input')?.value;
    const remarks = document.getElementById('remarks-textarea')?.value;
    
    // Validate required fields
    if (!contacted || !quoted || !sql || !toWin) {
        ModalSystem.error('Please answer all Yes/No questions');
        return;
    }
    
    if (!waAmount || parseFloat(waAmount) <= 0) {
        ModalSystem.error('Please enter a valid WA Amount');
        return;
    }
    
    if (!remarks || remarks.trim() === '') {
        ModalSystem.error('Please enter remarks');
        return;
    }
    
    const trackingData = {
        contacted: contacted,
        quoted: quoted,
        sql: sql,
        to_win: toWin,
        wa_amount: parseFloat(waAmount),
        remarks: remarks.trim()
    };
    
    try {
        // Check if tracking exists
        const checkRes = await fetch(`${_B}/api/v1/projects/${_detailsProject.id}/sales-tracking`, { credentials: 'include' });
        const checkData = await checkRes.json();
        const exists = checkData.exists;
        
        const method = exists ? 'PUT' : 'POST';
        const res = await fetch(`${_B}/api/v1/projects/${_detailsProject.id}/sales-tracking`, {
            method: method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(trackingData)
        });
        
        if (res.ok) {
            ModalSystem.success('Sales tracking saved successfully');
            closeDetailsModal();
            loadProjects();
            loadCounts();
        } else {
            const err = await res.json();
            ModalSystem.error(err.detail || err.message || 'Failed to save tracking');
        }
    } catch (err) {
        console.error('Error saving tracking:', err);
        ModalSystem.error('Failed to save tracking');
    }
}

// Load counts for tabs
async function loadCounts() {
    try {
        // Assigned count
        const assignedRes = await fetch(`${_B}/api/v1/projects/assigned?page=1&size=1&sales_rep_id=${userId}`, { credentials: 'include' });
        if (assignedRes.ok) {
            const data = await assignedRes.json();
            const countEl = document.getElementById('assigned-count');
            if (countEl) countEl.textContent = data.total || 0;
        }
        
        // Processed count
        const processedRes = await fetch(`${_B}/api/v1/projects/processed?page=1&size=1&sales_rep_id=${userId}`, { credentials: 'include' });
        if (processedRes.ok) {
            const data = await processedRes.json();
            const countEl = document.getElementById('processed-count');
            if (countEl) countEl.textContent = data.total || 0;
        }
    } catch (err) {
        console.error('Error loading counts:', err);
    }
}

// Open tracking modal
async function openTrackingModal(projectId, projectName, isEdit) {
    selectedProjectId = projectId;
    document.getElementById('tracking-project-name').textContent = projectName;
    
    // Clear form and reset Yes/No buttons
    document.getElementById('wa_amount').value = '';
    document.getElementById('remarks').value = '';
    document.querySelectorAll('.yes-no-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('input[type="hidden"]').forEach(input => input.value = '');
    
    // Get project data to populate SR name and branch
    try {
        // Find the project in the current table to get assigned_to info
        const rows = document.querySelectorAll('#pm-table-body tr[data-project]');
        let projectData = null;
        
        for (const row of rows) {
            try {
                const p = JSON.parse(decodeURIComponent(row.dataset.project));
                if (p.id === projectId) {
                    projectData = p;
                    break;
                }
            } catch (e) {
                console.error('Error parsing project data:', e);
            }
        }
        
        // Populate SR name and branch in display fields
        if (projectData) {
            document.getElementById('sales_rep_name_display').textContent = projectData.assigned_to_name || '—';
            document.getElementById('branch_display').textContent = projectData.branch || '—';
        }
        
        // If editing, load existing tracking
        if (isEdit) {
            const res = await fetch(`${_B}/api/v1/projects/${projectId}/sales-tracking`, { credentials: 'include' });
            if (res.ok) {
                const data = await res.json();
                if (data.exists && data.tracking) {
                    const t = data.tracking;
                    
                    // Set Yes/No buttons
                    if (t.contacted) {
                        const btn = document.querySelector(`.yes-no-btn[data-field="contacted"][data-value="${t.contacted}"]`);
                        if (btn) btn.classList.add('active');
                        document.getElementById('contacted').value = t.contacted;
                    }
                    
                    if (t.quoted) {
                        const btn = document.querySelector(`.yes-no-btn[data-field="quoted"][data-value="${t.quoted}"]`);
                        if (btn) btn.classList.add('active');
                        document.getElementById('quoted').value = t.quoted;
                    }
                    
                    if (t.sales_qualified) {
                        const btn = document.querySelector(`.yes-no-btn[data-field="sales_qualified"][data-value="${t.sales_qualified}"]`);
                        if (btn) btn.classList.add('active');
                        document.getElementById('sales_qualified').value = t.sales_qualified;
                    }
                    
                    if (t.to_win) {
                        const btn = document.querySelector(`.yes-no-btn[data-field="to_win"][data-value="${t.to_win}"]`);
                        if (btn) btn.classList.add('active');
                        document.getElementById('to_win').value = t.to_win;
                    }
                    
                    // Set other fields
                    document.getElementById('wa_amount').value = t.wa_amount || '';
                    document.getElementById('remarks').value = t.remarks || '';
                }
            }
        }
    } catch (err) {
        console.error('Error loading tracking:', err);
    }
    
    document.getElementById('trackingModal').classList.add('active');
}

// Close tracking modal
function closeTrackingModal() {
    document.getElementById('trackingModal').classList.remove('active');
    selectedProjectId = null;
}

// Save tracking
async function saveTracking() {
    // Validate required fields
    const contacted = document.getElementById('contacted').value;
    const quoted = document.getElementById('quoted').value;
    const salesQualified = document.getElementById('sales_qualified').value;
    const toWin = document.getElementById('to_win').value;
    const waAmount = document.getElementById('wa_amount').value;
    const remarks = document.getElementById('remarks').value;
    
    if (!contacted || !quoted || !salesQualified || !toWin) {
        ModalSystem.error('Please answer all Yes/No questions');
        return;
    }
    
    if (!waAmount || parseFloat(waAmount) <= 0) {
        ModalSystem.error('Please enter a valid WA Amount');
        return;
    }
    
    if (!remarks || remarks.trim() === '') {
        ModalSystem.error('Please enter remarks');
        return;
    }
    
    const trackingData = {
        contacted: contacted,
        quoted: quoted,
        sql: sql,
        to_win: toWin,
        wa_amount: parseFloat(waAmount),
        remarks: remarks.trim()
    };
    
    try {
        // Check if tracking exists
        const checkRes = await fetch(`${_B}/api/v1/projects/${selectedProjectId}/sales-tracking`, { credentials: 'include' });
        const checkData = await checkRes.json();
        const exists = checkData.exists;
        
        const method = exists ? 'PUT' : 'POST';
        const res = await fetch(`${_B}/api/v1/projects/${selectedProjectId}/sales-tracking`, {
            method: method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(trackingData)
        });
        
        if (res.ok) {
            ModalSystem.success('Sales tracking saved successfully');
            closeTrackingModal();
            loadProjects();
            loadCounts();
        } else {
            const err = await res.json();
            ModalSystem.error(err.detail || err.message || 'Failed to save tracking');
        }
    } catch (err) {
        console.error('Error saving tracking:', err);
        ModalSystem.error('Failed to save tracking');
    }
}

// Render pagination
function renderPagination(total, size) {
    const container = document.getElementById('pm-pagination');
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
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
