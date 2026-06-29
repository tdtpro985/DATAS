document.addEventListener('DOMContentLoaded', function() {
    let platforms = [];
    let filteredPlatforms = [];
    let salesRepListenerAttached = false;
    // BASE injected inline
    
    const searchInput = document.getElementById('searchInput');
    const refreshBtn = document.getElementById('refreshBtn');
    const tableBody = document.getElementById('platformsTableBody');
    const totalCountEl = document.getElementById('totalCount');
    const monthlyCountEl = document.getElementById('monthlyCount');
    const companyCountEl = document.getElementById('companyCount');
    const platformDetailsModal = document.getElementById('platformDetailsModal');

    function getPlatformModal() {
        return platformDetailsModal;
    }

    function applyRoleVisibilityPlatform(userRole) {
        getPlatformModal()?.querySelectorAll('[data-role-access]').forEach(el => {
            const allowed = el.dataset.roleAccess.split(',').map(r => r.trim());
            el.style.display = allowed.includes(userRole) ? '' : 'none';
        });
    }

    function isFieldEnabledPlatform(field) {
        const modal = getPlatformModal();
        const fieldOrder = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
        const currentIndex = fieldOrder.indexOf(field);
        if (currentIndex === 0) return true;
        for (let i = 0; i < currentIndex; i++) {
            const prevField = fieldOrder[i];
            const hasSelection = modal?.querySelector(`.yes-no-btn[data-field="${prevField}"].active`);
            if (!hasSelection) return false;
        }
        return true;
    }

    function updateFieldStatesPlatform() {
        const modal = getPlatformModal();
        if (!modal) return;
        const fieldOrder = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
        fieldOrder.forEach(field => {
            const buttons = modal.querySelectorAll(`.yes-no-btn[data-field="${field}"]`);
            const isEnabled = isFieldEnabledPlatform(field);
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

    function setupProgressiveFieldsPlatform() {
        const modal = getPlatformModal();
        if (!modal) return;

        modal.querySelectorAll('.yes-no-btn').forEach(btn => {
            btn.classList.remove('active', 'yes', 'no');
        });

        const wlAmountRequired = document.getElementById('wl-amount-required');
        if (wlAmountRequired) wlAmountRequired.style.display = 'none';

        updateFieldStatesPlatform();
    }

    async function loadSalesRepsPlatform() {
        try {
            const response = await fetch(`${BASE}/api/v1/users/sales-reps`, {
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

            if (!salesRepListenerAttached) {
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (branchInput && selectedOption) {
                        branchInput.value = selectedOption.dataset.branch || '';
                    }
                });
                salesRepListenerAttached = true;
            }

            const bodyUserId = parseInt(document.body.dataset.userId || '0');
            if (bodyUserId) {
                const matchingOption = Array.from(select.options).find(o => parseInt(o.value) === bodyUserId);
                if (matchingOption) {
                    select.value = matchingOption.value;
                    select.dispatchEvent(new Event('change'));
                }
            }
        } catch (error) {
            console.error('Load sales reps error:', error);
        }
    }

    function normalizeTrackingBool(value) {
        if (value === true || value === 1 || value === '1') return true;
        if (value === false || value === 0 || value === '0') return false;
        if (typeof value === 'string') {
            const lower = value.toLowerCase();
            if (lower === 'yes') return true;
            if (lower === 'no') return false;
        }
        return null;
    }

    async function loadSalesTrackingDataPlatform(platformId) {
        try {
            const response = await fetch(`${BASE}/api/v1/platforms/tracking?platform_id=${platformId}`, {
                credentials: 'include'
            });
            if (!response.ok) {
                console.error('Failed to load tracking data:', response.status);
                return;
            }

            const tracking = await response.json();
            const modal = getPlatformModal();
            if (!modal) return;

            const fields = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
            fields.forEach(field => {
                const value = normalizeTrackingBool(tracking[field]);
                modal.querySelectorAll(`.yes-no-btn[data-field="${field}"]`).forEach(b => {
                    b.classList.remove('active', 'yes', 'no', 'disabled');
                    b.style.opacity = '1';
                    b.style.cursor = 'pointer';
                });
                if (value === true) {
                    modal.querySelector(`.yes-no-btn[data-field="${field}"][data-value="yes"]`)?.classList.add('active', 'yes');
                } else if (value === false) {
                    modal.querySelector(`.yes-no-btn[data-field="${field}"][data-value="no"]`)?.classList.add('active', 'no');
                }
            });

            modal.querySelectorAll('.yes-no-btn').forEach(b => {
                b.classList.remove('disabled');
                b.style.opacity = '1';
                b.style.cursor = 'pointer';
            });

            const salesRepSelect = document.getElementById('sales-rep-select');
            if (salesRepSelect && tracking.sales_rep_id) {
                salesRepSelect.value = tracking.sales_rep_id;
                salesRepSelect.dispatchEvent(new Event('change'));
            }

            const branchInput = document.getElementById('branch-input');
            if (branchInput && tracking.branch) {
                branchInput.value = tracking.branch;
            }

            const wlAmountInput = document.getElementById('wl-amount-input');
            if (wlAmountInput) {
                wlAmountInput.value = tracking.wa_amount ?? '';
            }

            const remarksTextarea = document.getElementById('remarks-textarea');
            if (remarksTextarea) {
                remarksTextarea.value = tracking.remarks || tracking.notes || '';
            }

            const toWinValue = modal.querySelector('.yes-no-btn[data-field="to_win"].active')?.dataset.value;
            const wlAmountRequired = document.getElementById('wl-amount-required');
            if (wlAmountRequired) {
                wlAmountRequired.style.display = toWinValue === 'yes' ? 'inline' : 'none';
            }
        } catch (error) {
            console.error('Load sales tracking error:', error);
        }
    }

    async function setupPlatformModalSalesTracking(platformId) {
        setupProgressiveFieldsPlatform();

        const userRole = document.body.dataset.role;
        if (userRole === 'admin' || userRole === 'superadmin') {
            await loadSalesRepsPlatform();
        }

        applyRoleVisibilityPlatform(userRole);
        await loadSalesTrackingDataPlatform(platformId);
    }

    if (platformDetailsModal) {
        platformDetailsModal.addEventListener('click', function(e) {
            const btn = e.target.closest('.yes-no-btn');
            if (!btn || !platformDetailsModal.contains(btn)) return;

            const field = btn.dataset.field;
            const value = btn.dataset.value;

            if (!isFieldEnabledPlatform(field)) {
                showErrorModal('Please complete the previous fields first');
                return;
            }

            platformDetailsModal.querySelectorAll(`.yes-no-btn[data-field="${field}"]`).forEach(b => {
                b.classList.remove('active', 'yes', 'no');
            });
            btn.classList.add('active', value);

            if (field === 'to_win') {
                const wlAmountRequired = document.getElementById('wl-amount-required');
                if (wlAmountRequired) {
                    wlAmountRequired.style.display = value === 'yes' ? 'inline' : 'none';
                }
            }

            updateFieldStatesPlatform();
        });
    }
    
    // Load platform leads data
    async function loadPlatforms() {
        try {
            const response = await fetch(`${BASE}/api/v1/platforms`);
            const data = await response.json();
            
            if (data.success) {
                platforms = data.platforms || [];
                filteredPlatforms = [...platforms];
                updateSummaryCards();
                renderTable();
            } else {
                throw new Error(data.message || 'Failed to load platform leads');
            }
        } catch (error) {
            console.error('Error loading platforms:', error);
            showError('Failed to load platform leads');
        }
    }
    
    // Update summary cards
    function updateSummaryCards() {
        const total = platforms.length;
        const thisMonth = platforms.filter(p => {
            const date = new Date(p.created_at);
            const now = new Date();
            return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();
        }).length;
        const withCompany = platforms.filter(p => p.company_name && p.company_name.trim()).length;
        
        totalCountEl.textContent = total.toLocaleString();
        monthlyCountEl.textContent = thisMonth.toLocaleString();
        companyCountEl.textContent = withCompany.toLocaleString();
    }
    
    // Render table
    function renderTable() {
        if (filteredPlatforms.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <h3>No platform leads found</h3>
                            <p>Start by adding your first platform lead.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tableBody.innerHTML = filteredPlatforms.map(platform => {
            const statusBadge = platform.sales_tracking_status 
                ? `<span class="status-badge status-${platform.sales_tracking_status.toLowerCase().replace(/ /g, '-')}">${escapeHtml(platform.sales_tracking_status)}</span>`
                : '<span style="color: var(--text-muted);">-</span>';
            
            return `
            <tr onclick="viewPlatformDetails(${platform.id})" style="cursor: pointer;">
                <td title="${escapeHtml(platform.source || '')}">${escapeHtml(platform.source || 'N/A')}</td>
                <td title="${escapeHtml(platform.company_name || '')}">${escapeHtml(platform.company_name || 'N/A')}</td>
                <td title="${escapeHtml(platform.contact_person || '')}">${escapeHtml(platform.contact_person || 'N/A')}</td>
                <td title="${escapeHtml(platform.contact_number || '')}">${escapeHtml(platform.contact_number || 'N/A')}</td>
                <td title="${escapeHtml(platform.email_address || '')}">${escapeHtml(platform.email_address || 'N/A')}</td>
                <td title="${escapeHtml(platform.company_location || '')}">${escapeHtml(platform.company_location || 'N/A')}</td>
                <td>${statusBadge}</td>
                <td class="col-date">${formatDate(platform.created_at)}</td>
            </tr>
            `;
        }).join('');
    }
    // Search functionality
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        if (query === '') {
            filteredPlatforms = [...platforms];
        } else {
            filteredPlatforms = platforms.filter(platform => {
                return (
                    (platform.source || '').toLowerCase().includes(query) ||
                    (platform.company_name || '').toLowerCase().includes(query) ||
                    (platform.contact_person || '').toLowerCase().includes(query) ||
                    (platform.contact_number || '').toLowerCase().includes(query) ||
                    (platform.email_address || '').toLowerCase().includes(query) ||
                    (platform.company_location || '').toLowerCase().includes(query)
                );
            });
        }
        
        renderTable();
    });
    
    // Refresh data
    refreshBtn.addEventListener('click', function() {
        refreshBtn.innerHTML = '⏳ Loading...';
        loadPlatforms().finally(() => {
            refreshBtn.innerHTML = '🔄 Refresh';
        });
    });
    
    // View platform details (enhanced with modal)
    window.viewPlatformDetails = async function(platformId) {
        const platform = platforms.find(p => p.id === platformId);
        if (!platform) return;
        
        // Populate modal with platform data
        document.getElementById('detailSource').textContent = platform.source || 'N/A';
        document.getElementById('detailContactPerson').textContent = platform.contact_person || 'N/A';
        document.getElementById('detailContactNumber').textContent = platform.contact_number || 'N/A';
        document.getElementById('detailEmailAddress').textContent = platform.email_address || 'N/A';
        document.getElementById('detailCompanyName').textContent = platform.company_name || 'N/A';
        document.getElementById('detailCompanyLocation').textContent = platform.company_location || 'N/A';
        
        // Handle materials section
        const materialsSection = document.getElementById('materialsSection');
        const materialsContent = document.getElementById('detailMaterials');
        if (platform.materials_quantity && platform.materials_quantity.trim()) {
            materialsSection.style.display = 'block';
            materialsContent.textContent = platform.materials_quantity;
        } else {
            materialsSection.style.display = 'none';
        }
        
        // Format dates
        document.getElementById('detailCreatedAt').textContent = formatDetailDate(platform.created_at);
        document.getElementById('detailUpdatedAt').textContent = formatDetailDate(platform.updated_at);
        
        // Store current platform ID for edit/archive operations
        window.currentPlatformId = platformId;
        
        await setupPlatformModalSalesTracking(platformId);
        
        // Show modal
        document.getElementById('platformDetailsModal').classList.add('active');
    };
    
    // Close platform details modal
    window.closePlatformModal = function() {
        document.getElementById('platformDetailsModal').classList.remove('active');
        window.currentPlatformId = null;
    };
    
    // Edit platform
    window.editPlatform = function() {
        if (!window.currentPlatformId) return;
        
        const platform = platforms.find(p => p.id === window.currentPlatformId);
        if (!platform) return;
        
        // Populate edit form
        document.getElementById('editSource').value = platform.source || '';
        document.getElementById('editContactPerson').value = platform.contact_person || '';
        document.getElementById('editContactNumber').value = platform.contact_number || '';
        document.getElementById('editEmailAddress').value = platform.email_address || '';
        document.getElementById('editCompanyName').value = platform.company_name || '';
        document.getElementById('editCompanyLocation').value = platform.company_location || '';
        document.getElementById('editMaterials').value = platform.materials_quantity || '';
        
        // Close details modal and show edit modal
        closePlatformModal();
        document.getElementById('editPlatformModal').classList.add('active');
    };
    
    // Close edit modal
    window.closeEditModal = function() {
        document.getElementById('editPlatformModal').classList.remove('active');
    };
    
    // Save platform edits
    window.saveEditPlatform = async function() {
        if (!window.currentPlatformId) return;
        
        const form = document.getElementById('editPlatformForm');
        const saveBtn = document.getElementById('saveEditBtn');
        
        // Validate required fields
        const requiredFields = ['editSource', 'editContactPerson', 'editContactNumber', 'editEmailAddress'];
        let isValid = true;
        
        for (const fieldId of requiredFields) {
            const field = document.getElementById(fieldId);
            if (!field.value.trim()) {
                field.style.borderColor = '#ef4444';
                isValid = false;
                setTimeout(() => {
                    field.style.borderColor = '';
                }, 3000);
            }
        }
        
        if (!isValid) {
            showErrorModal('Please fill in all required fields.');
            return;
        }
        
        // Disable save button
        saveBtn.disabled = true;
        saveBtn.innerHTML = '⏳ Saving...';
        
        try {
            const formData = new FormData(form);
            formData.append('platform_id', window.currentPlatformId);
            
            const response = await fetch(BASE + '/api/v1/platforms/update', {
                method: 'POST',
                body: formData
            });
            
            let result;
            try {
                const responseText = await response.text();
                result = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error('Invalid response from server');
            }
            
            if (result.success) {
                showSuccessModal('Platform lead updated successfully!');
                closeEditModal();
                loadPlatforms(); // Refresh data
            } else {
                throw new Error(result.message || 'Failed to update platform lead');
            }
        } catch (error) {
            console.error('Error:', error);
            showErrorModal('Error updating platform lead: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '💾 Save Changes';
        }
    };
    
    // Save platform sales tracking (match Project Management validation)
    window.savePlatformTracking = async function() {
        if (!window.currentPlatformId) return;

        const modal = getPlatformModal();
        const toWin = modal?.querySelector('.yes-no-btn[data-field="to_win"].active')?.dataset.value;
        const sql = modal?.querySelector('.yes-no-btn[data-field="sales_qualified"].active')?.dataset.value;
        const contacted = modal?.querySelector('.yes-no-btn[data-field="contacted"].active')?.dataset.value;
        const quoted = modal?.querySelector('.yes-no-btn[data-field="quoted"].active')?.dataset.value;
        const salesRepId = document.getElementById('sales-rep-select')?.value;
        const branch = document.getElementById('branch-input')?.value;
        const wlAmount = document.getElementById('wl-amount-input')?.value;
        const remarks = document.getElementById('remarks-textarea')?.value;

        const errors = [];
        const userRole = document.body.dataset.role;
        if (userRole === 'admin' || userRole === 'superadmin') {
            if (!salesRepId) errors.push('Please select a Sales Representative');
            if (!branch || branch.trim() === '') errors.push('Please enter Branch information');
        }
        if (!remarks || remarks.trim() === '') errors.push('Please enter Remarks');
        if (toWin === 'yes' && (!wlAmount || parseFloat(wlAmount) <= 0)) {
            errors.push('W/L Amount is required when "To Win" is Yes');
        }

        if (errors.length > 0) {
            showErrorModal(errors[0]);
            return;
        }

        const trackingData = {
            platform_id: window.currentPlatformId,
            contacted: contacted === 'yes' ? true : (contacted === 'no' ? false : null),
            quoted: quoted === 'yes' ? true : (quoted === 'no' ? false : null),
            sales_qualified: sql === 'yes' ? true : (sql === 'no' ? false : null),
            to_win: toWin === 'yes' ? true : (toWin === 'no' ? false : null),
            sales_rep_id: (userRole === 'admin' || userRole === 'superadmin') && salesRepId ? parseInt(salesRepId) : null,
            branch: (userRole === 'admin' || userRole === 'superadmin') ? (branch || null) : null,
            wa_amount: wlAmount ? parseFloat(wlAmount) : null,
            remarks: remarks ? remarks.trim() : null
        };

        const saveBtn = document.getElementById('savePlatformTrackingBtn');
        if (saveBtn) {
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
        }

        try {
            const response = await fetch(`${BASE}/api/v1/platforms/tracking`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(trackingData)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showSuccessModal('Sales tracking saved successfully!');
                loadPlatforms();
            } else {
                throw new Error(result.message || result.detail || 'Failed to save tracking');
            }
        } catch (error) {
            console.error('Error:', error);
            showErrorModal('Error saving sales tracking: ' + error.message);
        } finally {
            if (saveBtn) {
                saveBtn.textContent = '💾 Save Sales Tracking';
                saveBtn.disabled = false;
            }
        }
    };
    
    // Archive platform
    window.archivePlatform = async function() {
        if (!window.currentPlatformId) return;
        
        const platform = platforms.find(p => p.id === window.currentPlatformId);
        if (!platform) return;
        
        // Show confirmation modal instead of confirm dialog
        showConfirmModal(
            `Are you sure you want to archive this platform lead?`,
            `Company: ${platform.company_name || 'N/A'}\nContact: ${platform.contact_person}`,
            async function() {
                const archiveBtn = document.getElementById('archiveBtn');
                archiveBtn.disabled = true;
                archiveBtn.innerHTML = '⏳ Archiving...';
                
                try {
                    const response = await fetch(BASE + '/api/v1/platforms/archive', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            platform_id: window.currentPlatformId
                        })
                    });
                    
                    let result;
                    try {
                        const responseText = await response.text();
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error('Invalid response from server');
                    }
                    
                    if (result.success) {
                        showSuccessModal('Platform lead archived successfully!');
                        closePlatformModal();
                        loadPlatforms(); // Refresh data
                    } else {
                        throw new Error(result.message || 'Failed to archive platform lead');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showErrorModal('Error archiving platform lead: ' + error.message);
                } finally {
                    archiveBtn.disabled = false;
                    archiveBtn.innerHTML = '🗃️ Archive';
                }
            }
        );
    };
    
    // Modal functions
    window.showErrorModal = function(message) {
        showModal('Error', message, 'error');
    };
    
    window.showSuccessModal = function(message) {
        showModal('Success', message, 'success');
    };
    
    window.showConfirmModal = function(title, message, onConfirm) {
        // Create confirm modal dynamically if needed
        showModal(title, message, 'confirm', onConfirm);
    };
    
    // Generic modal function
    function showModal(title, message, type = 'info', onConfirm = null) {
        // Remove existing notification modal if any
        const existingModal = document.getElementById('notificationModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create modal HTML
        const modalHtml = `
            <div class="modal-overlay active" id="notificationModal">
                <div class="modal-content modal-small">
                    <div class="modal-header">
                        <h2>${escapeHtml(title)}</h2>
                        <button class="modal-close" onclick="closeNotificationModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="white-space: pre-wrap;">${escapeHtml(message)}</p>
                    </div>
                    <div class="modal-actions">
                        ${type === 'confirm' ? 
                            `<button type="button" class="btn-action btn-save" onclick="confirmAction()">Yes</button>
                             <button type="button" class="btn-action" onclick="closeNotificationModal()">Cancel</button>` :
                            `<button type="button" class="btn-action btn-save" onclick="closeNotificationModal()">OK</button>`
                        }
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Store confirm callback if needed
        if (type === 'confirm' && onConfirm) {
            window.currentConfirmCallback = onConfirm;
        }
    }
    
    // Close notification modal
    window.closeNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 300);
        }
        window.currentConfirmCallback = null;
    };
    
    // Confirm action
    window.confirmAction = function() {
        if (window.currentConfirmCallback) {
            window.currentConfirmCallback();
        }
        closeNotificationModal();
    };
    
    // Format date for details view
    function formatDetailDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'Invalid date';
        }
    }
    
    // Close modals on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePlatformModal();
            closeEditModal();
        }
    });
    
    // Close modals on overlay click
    document.getElementById('platformDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePlatformModal();
        }
    });
    
    document.getElementById('editPlatformModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
    
    // Utility functions
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }) + '\n' + date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'Invalid date';
        }
    }
    
    function showError(message) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-icon">⚠️</div>
                        <h3>Error</h3>
                        <p>${escapeHtml(message)}</p>
                    </div>
                </td>
            </tr>
        `;
    }
    
    // Initial load
    loadPlatforms();
});
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay[id], .detail-modal-overlay[id]').forEach(function(el) {
        if (el.parentNode !== document.body) document.body.appendChild(el);
    });
});
