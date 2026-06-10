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
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.detail || error.message || 'Failed to save');
        }
        
        const result = await response.json();
        console.log('[SALES TRACKING] Saved:', result);
        
        if (typeof Toast !== 'undefined') {
            Toast.success('Sales tracking saved successfully');
        }
        
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
        
    } catch (error) {
        console.error('[SALES TRACKING] Save error:', error);
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
    // Prevent default and stop propagation
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }
    
    const modal = document.getElementById('detailsModal');
    const projectId = modal?.dataset?.projectId;
    
    if (!projectId) {
        if (typeof Toast !== 'undefined') {
            Toast.error('Project ID not found');
        }
        return;
    }
    
    // Show confirmation
    const confirmed = await showClearTrackingConfirm();
    if (!confirmed) return;
    
    try {
        const response = await fetch(`${BASE}/api/v1/projects/${projectId}/sales-tracking`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        // Get response text first to debug
        const responseText = await response.text();
        console.log('[SALES TRACKING] DELETE response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('[SALES TRACKING] JSON parse error:', parseError);
            throw new Error('Invalid response from server');
        }
        
        if (!response.ok) {
            throw new Error(result.detail || result.message || 'Failed to clear');
        }
        
        console.log('[SALES TRACKING] Cleared successfully');
        
        if (typeof Toast !== 'undefined') {
            Toast.success('Sales tracking cleared successfully');
        }
        
        // Reload projects list
        if (typeof ProjectsPage !== 'undefined' && ProjectsPage.loadProjects) {
            await ProjectsPage.loadProjects();
        }
        
        // Close modal
        closeDetailsModal();
        
    } catch (error) {
        console.error('[SALES TRACKING] Clear error:', error);
        if (typeof Toast !== 'undefined') {
            Toast.error(error.message || 'Failed to clear sales tracking');
        } else {
            alert('Error: ' + (error.message || 'Failed to clear sales tracking'));
        }
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
    
    // Redirect to edit page
    window.location.href = `${BASE}/projects/edit/${projectId}`;
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
