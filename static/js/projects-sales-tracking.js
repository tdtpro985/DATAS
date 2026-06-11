/* ============================================================
   projects-sales-tracking.js — Sales Tracking Functions
   ============================================================ */

// Save Sales Tracking
async function saveSalesTracking() {
    const modal = document.getElementById('detailsModal');
    const projectId = modal?.dataset?.projectId;
    
    if (!projectId) {
        if (typeof Toast !== 'undefined') {
            Toast.error('Project ID not found');
        }
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
        
        if (typeof Toast !== 'undefined') {
            Toast.success('Sales tracking saved successfully');
        }
        
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
        if (typeof Toast !== 'undefined') {
            Toast.error(error.message || 'Failed to save sales tracking');
        } else {
            alert('Error: ' + (error.message || 'Failed to save sales tracking'));
        }
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
        alert('Project ID not found');
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
        
        if (typeof Toast !== 'undefined') {
            Toast.success('Sales tracking cleared');
        }
        
        if (typeof ProjectsPage !== 'undefined' && ProjectsPage.loadProjects) {
            await ProjectsPage.loadProjects();
        }
        
        closeDetailsModal();
        
    } catch (error) {
        console.error('[CLEAR] Error:', error);
        alert('Failed to clear: ' + error.message);
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
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        `;
        
        confirmModal.innerHTML = `
            <div style="padding: 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                <h2 style="margin: 0; font-size: 1.25rem; color: #fbbf24; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span> Clear Sales Tracking
                </h2>
            </div>
            <div style="padding: 1.5rem;">
                <p style="color: #94a3b8; line-height: 1.6; margin: 0;">
                    Are you sure you want to clear all sales tracking data for this project?
                    <br><br>
                    <strong style="color: #f87171;">This action cannot be undone.</strong>
                </p>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <button class="clear-confirm-cancel" style="padding: 0.75rem 1.5rem; background: rgba(107, 114, 128, 0.2); border: 1px solid rgba(107, 114, 128, 0.4); border-radius: 0.75rem; color: white; font-size: 0.9rem; font-weight: 600; cursor: pointer;">
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
        if (typeof Toast !== 'undefined') {
            Toast.error('Project not found');
        }
        return;
    }
    
    // Check if project is archived
    if (project.archived_at) {
        if (typeof Toast !== 'undefined') {
            Toast.warning('Cannot edit archived projects. Please restore first.');
        }
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
        if (typeof Toast !== 'undefined') {
            Toast.error('Project not found');
        }
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
                <input type="date" class="form-control" id="edit_published_date" value="${project.published_date || ''}" />
            </div>
            <div class="form-group">
                <label>Source</label>
                <input type="text" class="form-control" id="edit_source" value="${project.source || ''}" />
            </div>
            <div class="form-group">
                <label>Contract ID</label>
                <input type="text" class="form-control" id="edit_contract_id" value="${project.contract_id || ''}" placeholder="Optional" />
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
    `;
}

// Generate Project Details form
function generateProjectForm(project) {
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
            <div class="form-group">
                <label>Country</label>
                <input type="text" class="form-control" id="edit_country" value="${project.country || 'Philippines'}" />
            </div>
            <div class="form-group">
                <label>Region</label>
                <input type="text" class="form-control" id="edit_region" value="${project.region || ''}" />
            </div>
            <div class="form-group">
                <label>Province</label>
                <input type="text" class="form-control" id="edit_province" value="${project.province || ''}" />
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" class="form-control" id="edit_city" value="${project.city || ''}" />
            </div>
            <div class="form-group">
                <label>Barangay</label>
                <input type="text" class="form-control" id="edit_barangay" value="${project.barangay || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Street</label>
                <input type="text" class="form-control" id="edit_street" value="${project.street || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Bulk/Lot#</label>
                <input type="text" class="form-control" id="edit_bulk_lot" value="${project.bulk_lot || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Coordinates</label>
                <input type="text" class="form-control" id="edit_coordinates" value="${project.coordinates || ''}" placeholder="e.g. 14.5995,120.9842" />
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Complete Address</label>
                <textarea class="form-control" id="edit_complete_address" rows="2">${project.complete_address || ''}</textarea>
            </div>
        </div>
    `;
}

// Generate Materials form
function generateMaterialsForm(project) {
    return `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Steel Bars (Rebars)</label>
                <input type="text" class="form-control" id="edit_steel_bars" value="${project.steel_bars || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>H-Beams</label>
                <input type="text" class="form-control" id="edit_h_beams" value="${project.h_beams || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>I-Beams</label>
                <input type="text" class="form-control" id="edit_i_beams" value="${project.i_beams || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>C-Purlins</label>
                <input type="text" class="form-control" id="edit_c_purlins" value="${project.c_purlins || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Square/Rectangular Tubes</label>
                <input type="text" class="form-control" id="edit_square_tubes" value="${project.square_tubes || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Round Pipes</label>
                <input type="text" class="form-control" id="edit_round_pipes" value="${project.round_pipes || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>GI Sheets (Corrugated)</label>
                <input type="text" class="form-control" id="edit_gi_sheets" value="${project.gi_sheets || ''}" placeholder="Optional" />
            </div>
            <div class="form-group">
                <label>Metal Deck/Flooring</label>
                <input type="text" class="form-control" id="edit_metal_deck" value="${project.metal_deck || ''}" placeholder="Optional" />
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Other Materials</label>
                <textarea class="form-control" id="edit_other_materials" rows="2" placeholder="Optional">${project.other_materials || ''}</textarea>
            </div>
        </div>
    `;
}

// Generate Pictures form
function generatePicturesForm(project) {
    return `
        <div>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Pictures functionality will be implemented here.
            </p>
            <div style="text-align: center; padding: 3rem; background: rgba(255,255,255,0.03); border-radius: 0.5rem;">
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
        if (typeof Toast !== 'undefined') {
            Toast.error('Invalid data');
        }
        return;
    }
    
    // Collect form data based on section
    const updateData = { id: parseInt(projectId) };
    
    switch(section) {
        case 'contract':
            updateData.published_date = document.getElementById('edit_published_date')?.value || null;
            updateData.source = document.getElementById('edit_source')?.value || null;
            updateData.contract_id = document.getElementById('edit_contract_id')?.value || null;
            updateData.contractor_name = document.getElementById('edit_contractor_name')?.value || null;
            updateData.contact_person = document.getElementById('edit_contact_person')?.value || null;
            updateData.contact_number = document.getElementById('edit_contact_number')?.value || null;
            break;
        case 'project':
            updateData.project_id = document.getElementById('edit_project_id')?.value || null;
            updateData.project_name = document.getElementById('edit_project_name')?.value || null;
            updateData.country = document.getElementById('edit_country')?.value || null;
            updateData.region = document.getElementById('edit_region')?.value || null;
            updateData.province = document.getElementById('edit_province')?.value || null;
            updateData.city = document.getElementById('edit_city')?.value || null;
            updateData.barangay = document.getElementById('edit_barangay')?.value || null;
            updateData.street = document.getElementById('edit_street')?.value || null;
            updateData.bulk_lot = document.getElementById('edit_bulk_lot')?.value || null;
            updateData.coordinates = document.getElementById('edit_coordinates')?.value || null;
            updateData.complete_address = document.getElementById('edit_complete_address')?.value || null;
            break;
        case 'materials':
            updateData.steel_bars = document.getElementById('edit_steel_bars')?.value || null;
            updateData.h_beams = document.getElementById('edit_h_beams')?.value || null;
            updateData.i_beams = document.getElementById('edit_i_beams')?.value || null;
            updateData.c_purlins = document.getElementById('edit_c_purlins')?.value || null;
            updateData.square_tubes = document.getElementById('edit_square_tubes')?.value || null;
            updateData.round_pipes = document.getElementById('edit_round_pipes')?.value || null;
            updateData.gi_sheets = document.getElementById('edit_gi_sheets')?.value || null;
            updateData.metal_deck = document.getElementById('edit_metal_deck')?.value || null;
            updateData.other_materials = document.getElementById('edit_other_materials')?.value || null;
            break;
        case 'pictures':
            // Pictures functionality to be implemented
            if (typeof Toast !== 'undefined') {
                Toast.info('Pictures feature coming soon');
            }
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
            if (typeof Toast !== 'undefined') {
                Toast.success('Project updated successfully');
            }
            
            // Close modal and reload
            closeEditSectionModal();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(result.message || 'Failed to update project');
        }
        
    } catch (error) {
        console.error('Update error:', error);
        if (typeof Toast !== 'undefined') {
            Toast.error(`Failed to update: ${error.message}`);
        }
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
