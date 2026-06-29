/* ============================================================
   projects-sales-tracking.js — Sales Tracking Functions
   ============================================================ */

// Save Sales Tracking
async function saveSalesTracking() {
    const modal = document.getElementById('detailsModal');
    const projectId = modal?.dataset?.projectId;
    
    if (!projectId) {
        ModalSystem.error('Project ID not found');
        return;
    }
    
    // Get yes/no button values
    const getYesNoValue = (fieldName) => {
        const yesBtn = document.querySelector(`.yes-no-btn[data-field="${fieldName}"][data-value="yes"]`);
        const noBtn = document.querySelector(`.yes-no-btn[data-field="${fieldName}"][data-value="no"]`);
        
        if (yesBtn?.classList.contains('active')) return true;
        if (noBtn?.classList.contains('active')) return false;
        return null;
    };
    
    // Build payload
    const payload = {
        contacted: getYesNoValue('contacted'),
        quoted: getYesNoValue('quoted'),
        sales_qualified: getYesNoValue('sales_qualified'),
        to_win: getYesNoValue('to_win'),
        wa_amount: document.getElementById('wl-amount-input')?.value || null,
        remarks: document.getElementById('remarks-textarea')?.value || null,
        sales_rep_id: document.getElementById('sales-rep-select')?.value || null,
        branch: document.getElementById('branch-input')?.value || null
    };
    
    if (payload.wa_amount) {
        payload.wa_amount = parseFloat(payload.wa_amount);
    }
    if (payload.sales_rep_id) {
        payload.sales_rep_id = parseInt(payload.sales_rep_id);
    }
    
    console.log('[SALES TRACKING] Saving:', payload);
    
    // Update button state
    const saveBtn = document.getElementById('saveTrackingBtn');
    const originalText = saveBtn?.textContent;
    if (saveBtn) {
        saveBtn.textContent = '💾 Saving...';
        saveBtn.disabled = true;
    }
    
    try {
        const response = await fetch(`${BASE}/api/v1/projects/${projectId}/sales-tracking`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        
        console.log('[SALES TRACKING] Response status:', response.status);
        
        if (!response.ok) {
            let errorMessage = 'Failed to save sales tracking';
            
            try {
                // Try to parse as JSON
                const error = await response.json();
                errorMessage = error.detail || error.message || errorMessage;
            } catch (e) {
                // If not JSON, try to get text
                try {
                    const errorText = await response.text();
                    console.error('[SALES TRACKING] Error response:', errorText);
                    errorMessage = errorText.substring(0, 100) || errorMessage;
                } catch (e2) {
                    console.error('[SALES TRACKING] Could not read error response');
                }
            }
            
            throw new Error(errorMessage);
        }
        
        const result = await response.json();
        console.log('[SALES TRACKING] Saved:', result);
        
        ModalSystem.success('Sales tracking saved successfully');
        
        // Show Actual Project modal before reloading
        if (typeof ProjectsPage !== 'undefined' && ProjectsPage.showActualProjectModal) {
            closeDetailsModal();
            ProjectsPage.showActualProjectModal(parseInt(projectId));
        } else {
            // Fallback: just reload
            // Reload projects list
            if (typeof ProjectsPage !== 'undefined' && ProjectsPage.loadProjects) {
                await ProjectsPage.loadProjects();
            }
            
            // Close and reopen modal to refresh
            closeDetailsModal();
            setTimeout(() => {
                if (typeof ProjectsPage !== 'undefined' && ProjectsPage.viewProject) {
                    ProjectsPage.viewProject(parseInt(projectId));
                }
            }, 500);
        }
        
    } catch (error) {
        console.error('[SALES TRACKING] Save error:', error);
        console.error('[SALES TRACKING] Error stack:', error.stack);
        ModalSystem.error(error.message || 'Failed to save sales tracking');
    } finally {
        if (saveBtn) {
            saveBtn.textContent = originalText || '💾 Save Sales Tracking';
            saveBtn.disabled = false;
        }
    }
}

// Clear Sales Tracking
async function clearSalesTracking(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const modal = document.getElementById('detailsModal');
    const projectId = modal?.dataset?.projectId;
    
    if (!projectId) {
        ModalSystem.error('Project ID not found');
        return;
    }
    
    const confirmed = await showClearTrackingConfirm();
    if (!confirmed) return;
    
    try {
        const response = await fetch(`${BASE}/api/v1/projects/${projectId}/sales-tracking`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.detail || 'Failed to clear');
        }
        
        ModalSystem.success('Sales tracking cleared');
        
        if (typeof ProjectsPage !== 'undefined' && ProjectsPage.loadProjects) {
            await ProjectsPage.loadProjects();
        }
        
        closeDetailsModal();
        
    } catch (error) {
        console.error('[CLEAR] Error:', error);
        ModalSystem.error('Failed to clear: ' + error.message);
    }
}

// Show confirmation modal for clearing
function showClearTrackingConfirm() {
    return new Promise((resolve) => {
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'clear-tracking-confirm-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        `;
        
        // Create modal
        const confirmModal = document.createElement('div');
        confirmModal.className = 'clear-tracking-confirm-modal';
        confirmModal.style.cssText = `
            background: #FFFFFF;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 1rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        `;
        
        confirmModal.innerHTML = `
            <div style="padding: 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.08);">
                <h2 style="margin: 0; font-size: 1.25rem; color: #fbbf24; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span> Clear Sales Tracking
                </h2>
            </div>
            <div style="padding: 1.5rem;">
                <p style="color: #6B7280; line-height: 1.6; margin: 0;">
                    Are you sure you want to clear all sales tracking data for this project?
                    <br><br>
                    <strong style="color: #f87171;">This action cannot be undone.</strong>
                </p>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid rgba(0,0,0,0.08);">
                <button class="clear-confirm-cancel" style="padding: 0.75rem 1.5rem; background: rgba(107, 114, 128, 0.2); border: 1px solid rgba(107, 114, 128, 0.4); border-radius: 0.75rem; color: #374151; font-size: 0.9rem; font-weight: 600; cursor: pointer;">
                    Cancel
                </button>
                <button class="clear-confirm-ok" style="padding: 0.75rem 1.5rem; background: #fbbf24; border: 1px solid #fbbf24; border-radius: 0.75rem; color: #000; font-size: 0.9rem; font-weight: 700; cursor: pointer;">
                    Clear Tracking
                </button>
            </div>
        `;
        
        overlay.appendChild(confirmModal);
        document.body.appendChild(overlay);
        
        let resolved = false;
        
        // Cancel button
        const cancelBtn = confirmModal.querySelector('.clear-confirm-cancel');
        cancelBtn.addEventListener('click', () => {
            if (!resolved) {
                resolved = true;
                overlay.remove();
                resolve(false);
            }
        });
        
        // OK button
        const okBtn = confirmModal.querySelector('.clear-confirm-ok');
        okBtn.addEventListener('click', () => {
            if (!resolved) {
                resolved = true;
                overlay.remove();
                resolve(true);
            }
        });
        
        // Overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay && !resolved) {
                resolved = true;
                overlay.remove();
                resolve(false);
            }
        });
        
        // ESC key
        const escHandler = (e) => {
            if (e.key === 'Escape' && !resolved) {
                resolved = true;
                overlay.remove();
                resolve(false);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    });
}

// Edit Project
function editProject() {
    const modal = document.getElementById('detailsModal');
    const projectId = modal?.dataset?.projectId;
    
    if (!projectId) {
        if (typeof Toast !== 'undefined') {
            Toast.error('Project ID not found');
        }
        return;
    }
    
    // Find the project to determine priority status
    let project = null;
    if (typeof ProjectsPage !== 'undefined' && ProjectsPage.allProjects) {
        project = ProjectsPage.allProjects.find(p => p.id == projectId);
    } else if (window.currentProjectsData && window.currentProjectsData.projects) {
        project = window.currentProjectsData.projects.find(p => p.id == projectId);
    }
    
    if (!project) {
        console.error('[PROJECTS] Project not found');
        ModalSystem.error('Project not found');
        return;
    }
    
    // Check if project is archived
    if (project.archived_at) {
        ModalSystem.warning('Cannot edit archived projects. Please restore first.');
        return;
    }
    
    // Store project info in edit modal
    const editModal = document.getElementById('editOptionsModal');
    if (editModal) {
        editModal.dataset.projectId = projectId;
        const isPriority = String(project.status || '').trim().toLowerCase() === 'priority';
        editModal.dataset.isPriority = isPriority;
        
        // Show/hide Pictures option based on priority
        const picturesOption = document.getElementById('editPicturesOption');
        if (picturesOption) {
            picturesOption.style.display = isPriority ? 'flex' : 'none';
        }
    }
    
    // Close details modal and open edit options modal
    closeDetailsModal();
    setTimeout(() => {
        if (editModal) {
            editModal.classList.add('active');
        }
    }, 200);
}

// Edit specific section
function editSection(section) {
    const editModal = document.getElementById('editOptionsModal');
    const projectId = editModal?.dataset?.projectId;
    const isPriority = editModal?.dataset?.isPriority === 'true';
    
    if (!projectId) {
        if (typeof Toast !== 'undefined') {
            Toast.error('Project ID not found');
        }
        return;
    }
    
    // Get project data
    let project = null;
    if (typeof ProjectsPage !== 'undefined' && ProjectsPage.allProjects) {
        project = ProjectsPage.allProjects.find(p => p.id == projectId);
    } else if (window.currentProjectsData && window.currentProjectsData.projects) {
        project = window.currentProjectsData.projects.find(p => p.id == projectId);
    }
    
    if (!project) {
        ModalSystem.error('Project not found');
        return;
    }
    
    // Close edit options modal
    closeEditOptionsModal();
    
    // Open edit section modal with appropriate form
    setTimeout(() => {
        openEditSectionModal(section, project);
    }, 200);
}

// Open edit section modal with form
function openEditSectionModal(section, project) {
    const modal = document.getElementById('editSectionModal');
    const title = document.getElementById('editSectionTitle');
    const body = document.getElementById('editSectionBody');
    
    if (!modal || !title || !body) return;
    
    // Store section and project ID
    modal.dataset.section = section;
    modal.dataset.projectId = project.id;
    
    // Set title and generate form based on section
    switch(section) {
        case 'contract':
            title.innerHTML = '📋 Edit Contract Details';
            body.innerHTML = generateContractForm(project);
            break;
        case 'project':
            title.innerHTML = '🏗️ Edit Project Details';
            body.innerHTML = generateProjectForm(project);
            break;
        case 'materials':
            title.innerHTML = '🔩 Edit Materials';
            body.innerHTML = generateMaterialsForm(project);
            break;
        case 'pictures':
            title.innerHTML = '📸 Edit Pictures';
            body.innerHTML = generatePicturesForm(project);
            break;
    }
    
    // Show modal
    modal.classList.add('active');
}

// Generate Contract Details form
function generateContractForm(project) {
    return `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Published Date</label>
                <input type="date" class="form-control" id="edit_publication_date" value="${project.publication_date || ''}" />
            </div>
            <div class="form-group">
                <label>Source</label>
                <input type="text" class="form-control" id="edit_source" value="${project.source || ''}" />
            </div>
            <div class="form-group">
                <label>Contractor ID</label>
                <input type="text" class="form-control" id="edit_contractor_id" value="${project.contractor_id || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Contractor Name</label>
                <input type="text" class="form-control" id="edit_contractor_name" value="${project.contractor_name || ''}" />
            </div>
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" class="form-control" id="edit_contact_person" value="${project.contact_person || ''}" />
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" class="form-control" id="edit_contact_number" value="${project.contact_number || ''}" placeholder="0919 123-4567" />
            </div>
        </div>
        
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(0,0,0,0.06);">
            <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--orange-500); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                📍 Contractor Location
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" class="form-control" id="edit_contract_country" value="${project.contract_country || ''}" placeholder="Optional" />
                </div>
                <div class="form-group">
                    <label>Region</label>
                    <input type="text" class="form-control" id="edit_contract_region" value="${project.contract_region || ''}" placeholder="Optional" />
                </div>
                <div class="form-group">
                    <label>Province</label>
                    <input type="text" class="form-control" id="edit_contract_province" value="${project.contract_province || ''}" placeholder="Optional" />
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" class="form-control" id="edit_contract_city" value="${project.contract_city || ''}" placeholder="Optional" />
                </div>
                <div class="form-group">
                    <label>Barangay</label>
                    <input type="text" class="form-control" id="edit_contract_barangay" value="${project.contract_barangay || ''}" placeholder="Optional" />
                </div>
                <div class="form-group">
                    <label>Street</label>
                    <input type="text" class="form-control" id="edit_contract_street" value="${project.contract_street || ''}" placeholder="Optional" />
                </div>
                <div class="form-group">
                    <label>Blk/Lot #</label>
                    <input type="text" class="form-control" id="edit_contract_blk_lot" value="${project.contract_blk_lot || ''}" placeholder="Optional" />
                </div>
                <div class="form-group">
                    <label>Coordinates</label>
                    <input type="text" class="form-control" id="edit_contract_coordinates" value="${project.contract_coordinates || ''}" placeholder="e.g. 14.5994,120.9842" />
                </div>
            </div>
        </div>
    `;
}

// Generate Project Details form
function generateProjectForm(project) {
    const statuses = ['Awarded', 'For Bidding', 'For Execution'];
    const currentStatus = (project.status || 'For Bidding').trim();

    const radioButtons = statuses.map(s => `
        <label style="
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.55rem 1rem;
            border: 1px solid ${currentStatus.toLowerCase() === s.toLowerCase()
                ? (s.toLowerCase() === 'priority' ? '#ff8000' : 'rgba(0,0,0,0.14)')
                : 'rgba(0,0,0,0.06)'};
            border-radius: 8px;
            background: ${currentStatus.toLowerCase() === s.toLowerCase()
                ? (s.toLowerCase() === 'priority' ? 'rgba(255,128,0,0.12)' : 'rgba(0,0,0,0.05)')
                : 'transparent'};
            cursor: pointer; font-size: 0.85rem; font-weight: 600;
            color: ${s.toLowerCase() === 'priority' ? '#ff8000' : 'var(--text-primary)'};
            transition: border-color 0.15s, background 0.15s;
            user-select: none;
        " onclick="selectStatusRadio(this, '${s}')">
            <input type="radio" name="edit_status_radio" value="${s}"
                ${currentStatus.toLowerCase() === s.toLowerCase() ? 'checked' : ''}
                style="display:none;">
            <span style="
                width:14px; height:14px; border-radius:50%;
                border: 2px solid ${currentStatus.toLowerCase() === s.toLowerCase()
                    ? (s.toLowerCase() === 'priority' ? '#ff8000' : 'rgba(0,0,0,0.3)')
                    : 'rgba(0,0,0,0.15)'};
                display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;
            ">
                ${currentStatus.toLowerCase() === s.toLowerCase()
                    ? `<span style="width:6px;height:6px;border-radius:50%;background:${s.toLowerCase() === 'priority' ? '#ff8000' : '#FF7A00'};display:block;"></span>`
                    : ''}
            </span>
            ${s}
        </label>
    `).join('');

    return `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Project ID</label>
                <input type="text" class="form-control" id="edit_project_id" value="${project.project_id || ''}" placeholder="Optional" />
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Project Name</label>
                <input type="text" class="form-control" id="edit_project_name" value="${project.project_name || ''}" />
            </div>

            <!-- Status -->
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Status</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.25rem;">
                    ${radioButtons}
                </div>
                <input type="hidden" id="edit_status" value="${currentStatus}" />
            </div>

            <div class="form-group">
                <label>Country</label>
                <input type="text" class="form-control" id="edit_country" value="${project.project_country || 'Philippines'}" />
            </div>
            <div class="form-group">
                <label>Region</label>
                <input type="text" class="form-control" id="edit_region" value="${project.project_region || project.region || ''}" />
            </div>
            <div class="form-group">
                <label>Province</label>
                <input type="text" class="form-control" id="edit_province" value="${project.project_province || ''}" />
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" class="form-control" id="edit_city" value="${project.project_city || project.city_province || ''}" />
            </div>
            <div class="form-group">
                <label>Barangay</label>
                <input type="text" class="form-control" id="edit_barangay" value="${project.project_barangay || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Street</label>
                <input type="text" class="form-control" id="edit_street" value="${project.project_street || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Bulk/Lot#</label>
                <input type="text" class="form-control" id="edit_bulk_lot" value="${project.project_blk_lot || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Coordinates</label>
                <input type="text" class="form-control" id="edit_coordinates" value="${project.project_coordinates || ''}" placeholder="e.g. 14.5995,120.9842" />
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Complete Address</label>
                <textarea class="form-control" id="edit_complete_address" rows="2">${project.address || ''}</textarea>
            </div>
        </div>
    `;
}

// Handle status radio visual selection
function selectStatusRadio(label, value) {
    // Update hidden input
    const hidden = document.getElementById('edit_status');
    if (hidden) hidden.value = value;

    // Reset all labels
    document.querySelectorAll('[name="edit_status_radio"]').forEach(radio => {
        const lbl = radio.closest('label');
        if (!lbl) return;
        const isPriority = radio.value.toLowerCase() === 'priority';
        lbl.style.border = '1px solid rgba(0,0,0,0.06)';
        lbl.style.background = 'transparent';
        // Reset dot
        const dot = lbl.querySelector('span > span');
        if (dot) dot.remove();
    });

    // Activate selected label
    const isPriority = value.toLowerCase() === 'priority';
    label.style.border = `1px solid ${isPriority ? '#ff8000' : 'rgba(0,0,0,0.14)'}`;
    label.style.background = isPriority ? 'rgba(255,128,0,0.12)' : 'rgba(0,0,0,0.05)';
    label.querySelector('input').checked = true;
    const ring = label.querySelector('span');
    ring.style.borderColor = isPriority ? '#ff8000' : 'rgba(0,0,0,0.3)';
    // Add inner dot
    const innerDot = document.createElement('span');
    innerDot.style.cssText = `width:6px;height:6px;border-radius:50%;background:${isPriority ? '#ff8000' : '#FF7A00'};display:block;`;
    ring.appendChild(innerDot);
}

// Generate Materials form
function generateMaterialsForm(project) {
    const isPriority = String(project.status || '').trim().toLowerCase() === 'priority';

    if (isPriority) {
        return `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group" style="grid-column:1/-1;">
                <label>Sheet Pile Type</label>
                <input type="text" class="form-control" id="edit_sheet_pile_type" value="${project.sheet_pile_type ?? ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Sheet Pile Amount (₱)</label>
                <input type="number" class="form-control" id="edit_sheet_pile_amount" value="${project.sheet_pile_amount ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>DRBs Type</label>
                <input type="text" class="form-control" id="edit_drbs" value="${project.drbs ?? ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>DRBs Value (₱)</label>
                <input type="number" class="form-control" id="edit_drbs_value" value="${project.drbs_value ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
        </div>`;
    }

    return `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>DRBs Value (₱)</label>
                <input type="number" class="form-control" id="edit_drbs_value" value="${project.drbs_value ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
            <div class="form-group">
                <label>Sheet Pile Amount (₱)</label>
                <input type="number" class="form-control" id="edit_sheet_pile_amount" value="${project.sheet_pile_amount ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
            <div class="form-group">
                <label>MS Plate (₱)</label>
                <input type="number" class="form-control" id="edit_ms_plate" value="${project.ms_plate ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
            <div class="form-group">
                <label>Angle Bars (₱)</label>
                <input type="number" class="form-control" id="edit_angle_bars" value="${project.angle_bars ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
            <div class="form-group">
                <label>Channel Bars (₱)</label>
                <input type="number" class="form-control" id="edit_channel_bars" value="${project.channel_bars ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
            <div class="form-group">
                <label>Wide Flange (₱)</label>
                <input type="number" class="form-control" id="edit_wide_flange" value="${project.wide_flange ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
            <div class="form-group">
                <label>GI/BI (₱)</label>
                <input type="number" class="form-control" id="edit_gi_bi" value="${project.gi_bi ?? ''}" placeholder="0.00" step="0.01" min="0" />
            </div>
        </div>`;
}

// Generate Pictures form
function generatePicturesForm(project) {
    return `
        <div>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Pictures functionality will be implemented here.
            </p>
            <div style="text-align: center; padding: 3rem; background: rgba(0,0,0,0.02); border-radius: 0.5rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📸</div>
                <p style="color: var(--text-muted);">Picture upload feature coming soon</p>
            </div>
        </div>
    `;
}

// Save edit section
async function saveEditSection() {
    const modal = document.getElementById('editSectionModal');
    const section = modal?.dataset?.section;
    const projectId = modal?.dataset?.projectId;
    
    if (!section || !projectId) {
        ModalSystem.error('Invalid data');
        return;
    }
    
    // Collect form data based on section
    const updateData = { id: parseInt(projectId) };
    
    switch(section) {
        case 'contract':
            updateData.publication_date = document.getElementById('edit_publication_date')?.value || null;
            updateData.source = document.getElementById('edit_source')?.value || null;
            updateData.contractor_id = document.getElementById('edit_contractor_id')?.value || null;
            updateData.contractor_name = document.getElementById('edit_contractor_name')?.value || null;
            updateData.contact_person = document.getElementById('edit_contact_person')?.value || null;
            updateData.contact_number = document.getElementById('edit_contact_number')?.value || null;
            // Contractor location fields
            updateData.contract_country = document.getElementById('edit_contract_country')?.value || null;
            updateData.contract_region = document.getElementById('edit_contract_region')?.value || null;
            updateData.contract_province = document.getElementById('edit_contract_province')?.value || null;
            updateData.contract_city = document.getElementById('edit_contract_city')?.value || null;
            updateData.contract_barangay = document.getElementById('edit_contract_barangay')?.value || null;
            updateData.contract_street = document.getElementById('edit_contract_street')?.value || null;
            updateData.contract_blk_lot = document.getElementById('edit_contract_blk_lot')?.value || null;
            updateData.contract_coordinates = document.getElementById('edit_contract_coordinates')?.value || null;
            break;
        case 'project':
            updateData.project_id = document.getElementById('edit_project_id')?.value || null;
            updateData.project_name = document.getElementById('edit_project_name')?.value || null;
            updateData.status = document.getElementById('edit_status')?.value || null;
            updateData.project_country = document.getElementById('edit_country')?.value || null;
            updateData.project_region = document.getElementById('edit_region')?.value || null;
            updateData.project_province = document.getElementById('edit_province')?.value || null;
            updateData.project_city = document.getElementById('edit_city')?.value || null;
            updateData.project_barangay = document.getElementById('edit_barangay')?.value || null;
            updateData.project_street = document.getElementById('edit_street')?.value || null;
            updateData.project_blk_lot = document.getElementById('edit_bulk_lot')?.value || null;
            updateData.project_coordinates = document.getElementById('edit_coordinates')?.value || null;
            updateData.address = document.getElementById('edit_complete_address')?.value || null;
            break;
        case 'materials':
            updateData.drbs_value         = document.getElementById('edit_drbs_value')?.value        || null;
            updateData.sheet_pile_amount  = document.getElementById('edit_sheet_pile_amount')?.value  || null;
            updateData.sheet_pile_type    = document.getElementById('edit_sheet_pile_type')?.value    || null;
            updateData.drbs               = document.getElementById('edit_drbs')?.value               || null;
            updateData.ms_plate           = document.getElementById('edit_ms_plate')?.value           || null;
            updateData.angle_bars         = document.getElementById('edit_angle_bars')?.value         || null;
            updateData.channel_bars       = document.getElementById('edit_channel_bars')?.value       || null;
            updateData.wide_flange        = document.getElementById('edit_wide_flange')?.value        || null;
            updateData.gi_bi              = document.getElementById('edit_gi_bi')?.value              || null;
            break;
        case 'pictures':
            // Pictures functionality to be implemented
            ModalSystem.info('Pictures feature coming soon');
            return;
    }
    
    try {
        // Call update API
        const response = await fetch(`${BASE}/api/v1/projects/${projectId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(updateData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            ModalSystem.success('Project updated successfully');
            
            // Close modal
            closeEditSectionModal();
            
            // Reload projects table without full page refresh
            if (typeof ProjectsPage !== 'undefined' && typeof ProjectsPage.loadProjects === 'function') {
                setTimeout(() => {
                    ProjectsPage.loadProjects();
                }, 500);
            } else {
                // Fallback to full page reload if ProjectsPage not available
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            throw new Error(result.message || 'Failed to update project');
        }
        
    } catch (error) {
        console.error('Update error:', error);
        ModalSystem.error(`Failed to update: ${error.message}`);
    }
}

// Close edit section modal
function closeEditSectionModal() {
    const modal = document.getElementById('editSectionModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close edit options modal
function closeEditOptionsModal() {
    const modal = document.getElementById('editOptionsModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close Details Modal
function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Initialize button event listeners
document.addEventListener('DOMContentLoaded', () => {
    console.log('[SALES TRACKING] Initializing button listeners');
    
    // Use event delegation on document body for all modal buttons
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        
        const btnId = btn.id;
        console.log('[SALES TRACKING] Button clicked:', btnId);
        
        switch(btnId) {
            case 'saveTrackingBtn':
                e.preventDefault();
                e.stopPropagation();
                saveSalesTracking();
                break;
            case 'clearTrackingBtn':
                e.preventDefault();
                e.stopPropagation();
                clearSalesTracking(e);
                break;
            case 'editProjectBtn':
                e.preventDefault();
                e.stopPropagation();
                editProject();
                break;
            case 'closeModalBtn':
                e.preventDefault();
                e.stopPropagation();
                closeDetailsModal();
                break;
        }
    });
});
