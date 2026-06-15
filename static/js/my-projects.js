/* ============================================================
   my-projects.js — My Projects Page (Sales Rep)
   Modal is identical to Admin/Superadmin projects-management modal.
   ============================================================ */

let currentView = 'assigned';
let currentPage = 1;
let currentFilters = { search: '', region: '', status: '' };
let selectedProjectId = null;
let _detailsProject = null;

const _B = (typeof BASE !== 'undefined') ? BASE : '/new-dashboard';
const userId = (typeof CURRENT_USER_ID !== 'undefined') ? CURRENT_USER_ID : 0;

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await RoleManager.init();

    const urlParams = new URLSearchParams(window.location.search);
    currentView = urlParams.get('view') || 'assigned';

    loadProjects();
    loadCounts();

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
});

// ── Load Projects ─────────────────────────────────────────────────────────────
async function loadProjects() {
    const tbody = document.getElementById('pm-table-body');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>';

    try {
        const endpoint = currentView === 'assigned'
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

        if (projects.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-dim);">No projects found</td></tr>';
        } else {
            tbody.innerHTML = projects.map(p => getTableRow(p)).join('');
            setTimeout(() => {
                document.querySelectorAll('#pm-table-body tr[data-project]').forEach(r => {
                    r.removeEventListener('click', rowClickHandler);
                    r.addEventListener('click', rowClickHandler);
                });
            }, 0);
        }

        renderPagination(data.total, data.size);

    } catch (err) {
        console.error('Error loading projects:', err);
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-danger);">Error loading projects</td></tr>';
    }
}

// ── Table Row ─────────────────────────────────────────────────────────────────
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
        <td style="font-weight:500;">${escapeHtml(p.contractor_name || '—')}</td>
        <td>${escapeHtml(p.project_name || '—')}</td>
        <td>${escapeHtml(p.region || '—')}</td>
        <td style="text-align:right;">₱${value}</td>
        <td><span class="status-badge status-${statusClass}">${escapeHtml(p.status || '—')}</span></td>
        <td>${lastContact}</td>
    </tr>`;
}

function rowClickHandler(e) {
    const row = e.currentTarget;
    try {
        const project = row.dataset.project ? JSON.parse(decodeURIComponent(row.dataset.project)) : null;
        if (project && project.id) openDetailsModal(project.id, row);
    } catch (err) {
        console.error('Row click handler error:', err);
    }
}

// ── Details Modal — identical structure to Admin/Superadmin ───────────────────
function openDetailsModal(projectId, rowEl) {
    try {
        const project = rowEl?.dataset?.project
            ? JSON.parse(decodeURIComponent(rowEl.dataset.project))
            : null;
        _detailsProject = project || { id: projectId };
        viewProjectSR(projectId);
    } catch (err) {
        console.error('Error opening details modal:', err);
    }
}

function viewProjectSR(projectId) {
    const project = _detailsProject;
    if (!project) return;

    const modal    = document.getElementById('detailsModal');
    const modalBody = document.getElementById('detailsModalBody');
    if (!modal || !modalBody) return;

    const value = (project.project_value || 0).toLocaleString('en-PH', {
        style: 'currency', currency: 'PHP', minimumFractionDigits: 2
    });

    // Exact same HTML as Admin/Superadmin viewProject()
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
                    <div class="detail-value">${escapeHtml(String(project.project_id || project.id || '—'))}</div>
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

        <!-- Sales Tracking — exact copy of Admin modal -->
        <div class="sales-tracking-section">
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

                <!-- SR/Branch: read-only display for sales_rep -->
                <div class="sales-form-group">
                    <label class="sales-form-label">Sales Representative</label>
                    <input type="text" class="sales-form-input" id="sales-rep-display" readonly
                           value="${escapeHtml(project.assigned_to_name || project.sales_rep_name || '—')}"
                           style="background:rgba(255,255,255,0.05);cursor:not-allowed;">
                </div>

                <div class="sales-form-group">
                    <label class="sales-form-label">Branch</label>
                    <input type="text" class="sales-form-input" id="branch-input" readonly
                           value="${escapeHtml(project.assigned_to_branch || project.tracking_branch || '—')}"
                           style="background:rgba(255,255,255,0.05);cursor:not-allowed;">
                </div>

                <div class="sales-form-group">
                    <label class="sales-form-label">W/L Amount (₱) <span id="wl-amount-required" style="color:#ff7070;display:none;">*</span></label>
                    <input type="number" class="sales-form-input" id="wl-amount-input" placeholder="0.00" step="0.01" min="0">
                </div>

                <div class="sales-form-group">
                    <label class="sales-form-label">Remarks <span style="color:#ff7070;">*</span></label>
                    <textarea class="sales-form-textarea" id="remarks-textarea" placeholder="Enter remarks..."></textarea>
                </div>
            </div>
        </div>
    `;

    // Action buttons — same as Admin but no Archive btn
    const modalActions = modal.querySelector('.modal-actions');
    if (modalActions) {
        modalActions.innerHTML = `
            <button type="button" class="btn-action btn-secondary" onclick="closeDetailsModal()">Close</button>
            <button type="button" class="btn-action btn-primary" id="saveTrackingBtnSR">💾 Save Sales Tracking</button>
        `;
        document.getElementById('saveTrackingBtnSR')
            .addEventListener('click', saveSalesTrackingSR);
    }

    modal.dataset.projectId = projectId;
    modal.dataset.assignedTo = project.assigned_to || '';
    modal.classList.add('active');

    // Setup — exact same flow as Admin's setupProjectModalSalesTracking
    setTimeout(() => {
        setupProgressiveFieldsSR();
        loadSalesTrackingDataSR(projectId);
    }, 0);
}

function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    if (modal) modal.classList.remove('active');
    _detailsProject = null;
}

// ── Progressive Field Locking — exact copy of Admin's setupProgressiveFieldsPM ─
function setupProgressiveFieldsSR() {
    const body = document.getElementById('detailsModalBody');
    if (!body) return;

    body.querySelectorAll('.yes-no-btn').forEach(btn => {
        btn.classList.remove('active', 'yes', 'no');
    });

    updateFieldStatesSR();

    body.querySelectorAll('.yes-no-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const field = e.target.dataset.field;
            const value = e.target.dataset.value;

            if (!isFieldEnabledSR(field)) {
                if (typeof showNotificationModal === 'function') {
                    showNotificationModal('Warning', 'Please complete the previous fields first', 'warning');
                }
                return;
            }

            body.querySelectorAll(`.yes-no-btn[data-field="${field}"]`).forEach(b => {
                b.classList.remove('active', 'yes', 'no');
            });
            e.target.classList.add('active', value);

            if (field === 'to_win') {
                const req = document.getElementById('wl-amount-required');
                if (req) req.style.display = value === 'yes' ? 'inline' : 'none';
            }

            updateFieldStatesSR();
        });
    });
}

function isFieldEnabledSR(field) {
    const body = document.getElementById('detailsModalBody');
    const order = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
    const idx = order.indexOf(field);
    if (idx === 0) return true;
    for (let i = 0; i < idx; i++) {
        if (!body?.querySelector(`.yes-no-btn[data-field="${order[i]}"].active`)) return false;
    }
    return true;
}

function updateFieldStatesSR() {
    const body = document.getElementById('detailsModalBody');
    if (!body) return;
    ['contacted', 'quoted', 'sales_qualified', 'to_win'].forEach(field => {
        const enabled = isFieldEnabledSR(field);
        body.querySelectorAll(`.yes-no-btn[data-field="${field}"]`).forEach(btn => {
            if (enabled) {
                btn.classList.remove('disabled');
                btn.style.opacity = '1';
                btn.style.cursor  = 'pointer';
            } else {
                btn.classList.add('disabled');
                btn.style.opacity = '0.4';
                btn.style.cursor  = 'not-allowed';
            }
        });
    });
}

// ── Load Tracking Data — exact copy of Admin's loadSalesTrackingDataPM ─────────
async function loadSalesTrackingDataSR(projectId) {
    try {
        const response = await fetch(`${_B}/api/v1/projects/${projectId}/sales-tracking`, {
            method: 'GET', credentials: 'include'
        });
        if (!response.ok) return;

        const result = await response.json();
        if (!result.exists || !result.data) return;

        const data = result.data;
        const body = document.getElementById('detailsModalBody');
        if (!body) return;

        // Restore Yes/No buttons (API returns booleans)
        ['contacted', 'quoted', 'sales_qualified', 'to_win'].forEach(field => {
            const val = data[field];
            body.querySelectorAll(`.yes-no-btn[data-field="${field}"]`).forEach(b => {
                b.classList.remove('active', 'yes', 'no', 'disabled');
                b.style.opacity = '1';
                b.style.cursor  = 'pointer';
            });
            if (val === true) {
                const btn = body.querySelector(`.yes-no-btn[data-field="${field}"][data-value="yes"]`);
                if (btn) btn.classList.add('active', 'yes');
            } else if (val === false) {
                const btn = body.querySelector(`.yes-no-btn[data-field="${field}"][data-value="no"]`);
                if (btn) btn.classList.add('active', 'no');
            }
        });

        // Bypass progressive locking when loading existing data
        body.querySelectorAll('.yes-no-btn').forEach(b => {
            b.classList.remove('disabled');
            b.style.opacity = '1';
            b.style.cursor  = 'pointer';
        });

        // Restore SR / Branch (override pre-filled values with saved tracking data)
        const srDisplay = document.getElementById('sales-rep-display');
        if (srDisplay && data.sales_rep_name) srDisplay.value = data.sales_rep_name;

        const branchInput = document.getElementById('branch-input');
        if (branchInput && data.branch) branchInput.value = data.branch;

        // W/L amount asterisk
        const req = document.getElementById('wl-amount-required');
        if (req) req.style.display = (data.to_win === true) ? 'inline' : 'none';

        const wlInput = document.getElementById('wl-amount-input');
        if (wlInput && data.wa_amount) wlInput.value = data.wa_amount;

        const remarksTA = document.getElementById('remarks-textarea');
        if (remarksTA && data.notes) remarksTA.value = data.notes;

        // Do NOT call updateFieldStatesSR() — it would re-dim the buttons

    } catch (err) {
        console.error('Load sales tracking error:', err);
    }
}

// ── Save Tracking — exact copy of Admin's saveSalesTracking, adapted for SR ───
async function saveSalesTrackingSR() {
    const modal     = document.getElementById('detailsModal');
    const projectId = parseInt(modal?.dataset?.projectId || '0');
    if (!projectId) return;

    const body = document.getElementById('detailsModalBody');

    const getVal = (field) => {
        const btn = body.querySelector(`.yes-no-btn[data-field="${field}"].active`);
        return btn ? btn.dataset.value : null;
    };

    const contacted = getVal('contacted');
    const quoted    = getVal('quoted');
    const sql       = getVal('sales_qualified');
    const toWin     = getVal('to_win');
    const wlAmount  = document.getElementById('wl-amount-input')?.value;
    const remarks   = document.getElementById('remarks-textarea')?.value;
    const branch    = document.getElementById('branch-input')?.value;

    // Same validation as Admin
    const errors = [];
    if (!remarks || remarks.trim() === '') errors.push('Please enter Remarks');
    if (toWin === 'yes' && (!wlAmount || parseFloat(wlAmount) <= 0)) {
        errors.push('W/L Amount is required when "To Win" is Yes');
    }

    if (errors.length > 0) {
        if (typeof showNotificationModal === 'function') {
            showNotificationModal('Validation Error', errors[0], 'warning');
        } else { alert(errors[0]); }
        return;
    }

    const payload = {
        contacted:       contacted === 'yes' ? true  : (contacted === 'no' ? false : null),
        quoted:          quoted    === 'yes' ? true  : (quoted    === 'no' ? false : null),
        sales_qualified: sql       === 'yes' ? true  : (sql       === 'no' ? false : null),
        to_win:          toWin     === 'yes' ? true  : (toWin     === 'no' ? false : null),
        sales_rep_id:    _detailsProject?.assigned_to || null,
        branch:          branch && branch !== '—' ? branch : null,
        wa_amount:       wlAmount ? parseFloat(wlAmount) : null,
        remarks:         remarks ? remarks.trim() : null
    };

    const saveBtn = document.getElementById('saveTrackingBtnSR');
    try {
        if (saveBtn) { saveBtn.textContent = 'Saving...'; saveBtn.disabled = true; }

        const response = await fetch(`${_B}/api/v1/projects/${projectId}/sales-tracking`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });

        if (saveBtn) { saveBtn.innerHTML = '💾 Save Sales Tracking'; saveBtn.disabled = false; }

        if (response.ok) {
            if (typeof showNotificationModal === 'function') {
                showNotificationModal('Success', 'Sales tracking saved successfully!', 'success');
                setTimeout(() => { closeDetailsModal(); loadProjects(); loadCounts(); }, 1500);
            } else {
                closeDetailsModal(); loadProjects(); loadCounts();
            }
        } else {
            const err = await response.json();
            if (typeof showNotificationModal === 'function') {
                showNotificationModal('Error', err.detail || err.message || 'Failed to save', 'error');
            } else { alert(err.detail || err.message || 'Failed to save'); }
        }
    } catch (err) {
        console.error('Save sales tracking error:', err);
        if (saveBtn) { saveBtn.innerHTML = '💾 Save Sales Tracking'; saveBtn.disabled = false; }
        if (typeof showNotificationModal === 'function') {
            showNotificationModal('Error', 'Failed to save tracking', 'error');
        }
    }
}

// ── Load Counts ───────────────────────────────────────────────────────────────
async function loadCounts() {
    try {
        const assignedRes = await fetch(
            `${_B}/api/v1/projects/assigned?page=1&size=1&sales_rep_id=${userId}`,
            { credentials: 'include' }
        );
        if (assignedRes.ok) {
            const data = await assignedRes.json();
            const el = document.getElementById('assigned-count');
            if (el) el.textContent = data.total || 0;
        }

        const processedRes = await fetch(
            `${_B}/api/v1/projects/processed?page=1&size=1&sales_rep_id=${userId}`,
            { credentials: 'include' }
        );
        if (processedRes.ok) {
            const data = await processedRes.json();
            const el = document.getElementById('processed-count');
            if (el) el.textContent = data.total || 0;
        }
    } catch (err) {
        console.error('Error loading counts:', err);
    }
}

// ── Pagination ────────────────────────────────────────────────────────────────
function renderPagination(total, size) {
    const container  = document.getElementById('pm-pagination');
    const totalPages = Math.ceil(total / size);

    if (totalPages <= 1) { container.innerHTML = ''; return; }

    let html = `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="goToPage(${currentPage - 1})">Previous</button>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += `<span style="padding:0.5rem;">…</span>`;
        }
    }

    html += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="goToPage(${currentPage + 1})">Next</button>`;
    container.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadProjects();
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
