/* ============================================================
   illegitimate-projects.js — Illegitimate Projects Page
   ============================================================ */

const IllegalitimateProjectsPage = {
    type: 'illegitimate',
    currentPage: 1,
    pageSize: 50,
    totalProjects: 0,
    allProjects: [],
    filteredProjects: [],

    async init() {
        const user = await Auth.checkAuth();
        if (!user) return;

        await this.loadProjects();
        this.setupEventListeners();
    },

    setupEventListeners() {
        document.getElementById('search-input').addEventListener('input', () => this.filterProjects());
        document.getElementById('region-filter').addEventListener('change', () => this.filterProjects());
        document.getElementById('source-filter').addEventListener('change', () => this.filterProjects());
        document.getElementById('sort-filter').addEventListener('change', () => this.filterProjects());
        document.getElementById('refresh-btn').addEventListener('click', () => this.loadProjects());
    },

    async loadProjects() {
        try {
            const response = await fetch(BASE + '/api/v1/projects/illegitimate', {
                credentials: 'include'
            });

            if (!response.ok) throw new Error('Failed to load projects');

            const data = await response.json();
            this.allProjects = data.projects || [];
            this.totalProjects = this.allProjects.length;

            this.updateSummaryCards();
            this.populateRegionFilter();
            this.filterProjects();

        } catch (error) {
            console.error('[ILLEGITIMATE] Load error:', error);
            this.showError('Failed to load projects. Please try again.');
        }
    },

    updateSummaryCards() {
        document.getElementById('totalIllegitimate').textContent = this.allProjects.length.toLocaleString();
    },

    populateRegionFilter() {
        const regions = [...new Set(this.allProjects.map(p => p.region).filter(Boolean))];
        regions.sort();

        const select = document.getElementById('region-filter');
        const currentValue = select.value;
        
        select.innerHTML = '<option value="">All Regions</option>';
        regions.forEach(region => {
            const option = document.createElement('option');
            option.value = region;
            option.textContent = region;
            select.appendChild(option);
        });

        select.value = currentValue;
    },

    filterProjects() {
        const searchTerm = document.getElementById('search-input').value.toLowerCase();
        const regionFilter = document.getElementById('region-filter').value;
        const sourceFilter = document.getElementById('source-filter').value;
        const sortFilter = document.getElementById('sort-filter').value;

        this.filteredProjects = this.allProjects.filter(project => {
            const matchesSearch = !searchTerm || 
                (project.contractor_name || '').toLowerCase().includes(searchTerm) ||
                (project.project_name || '').toLowerCase().includes(searchTerm) ||
                (project.region || '').toLowerCase().includes(searchTerm);

            const matchesRegion = !regionFilter || project.region === regionFilter;
            const matchesSource = !sourceFilter || project.source === sourceFilter;

            return matchesSearch && matchesRegion && matchesSource;
        });

        this.sortProjects(sortFilter);
        this.currentPage = 1;
        this.renderProjects();
    },

    sortProjects(sortBy) {
        if (!sortBy) sortBy = 'publication_date_desc';

        const parts = sortBy.split('_');
        const direction = parts[parts.length - 1];
        const isAsc = direction === 'asc';

        this.filteredProjects.sort((a, b) => {
            const valueA = new Date(a.publication_date || 0);
            const valueB = new Date(b.publication_date || 0);
            return isAsc ? valueA - valueB : valueB - valueA;
        });
    },

    renderProjects() {
        const tbody = document.getElementById('projects-tbody');
        const start = (this.currentPage - 1) * this.pageSize;
        const end = start + this.pageSize;
        const pageProjects = this.filteredProjects.slice(start, end);

        // Clear tbody first
        tbody.innerHTML = '';

        if (this.filteredProjects.length === 0) {
            const emptyRow = document.createElement('tr');
            const emptyCell = document.createElement('td');
            emptyCell.colSpan = 8;
            emptyCell.className = 'empty-state';
            emptyCell.innerHTML = `
                <div class="empty-state-icon">📭</div>
                <h3>No Illegitimate Projects Found</h3>
                <p>Try adjusting your search or filters</p>
            `;
            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
            this.updatePagination();
            return;
        }

        // Build each row
        pageProjects.forEach(project => {
            // Format date
            let dateStr = '—';
            if (project.publication_date) {
                const dt = new Date(project.publication_date);
                dateStr = dt.toLocaleDateString('en-PH', {
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric'
                });
            }
            
            // Format value
            const value = project.project_value !== null && project.project_value !== undefined
                ? '₱' + parseFloat(project.project_value).toLocaleString('en-PH', { minimumFractionDigits: 2 })
                : '—';
            
            // Get status
            const status = project.status || '—';
            const statusClass = this.getStatusClass(status);
            
            // Get tracking status
            const trackingStatus = project.tracking_status || 'Not Started';
            const trackingStatusClass = trackingStatus.toLowerCase().replace(/\s+/g, '-');

            // Get field values with fallbacks
            const contractorName = project.contractor_name || '—';
            const projectName = project.project_name || '—';
            const region = project.region || project.project_region || '—';
            const source = project.source || '—';

            // Create row
            const row = document.createElement('tr');
            row.style.cursor = 'pointer';
            row.onclick = () => this.viewProject(project.id);

            // Create cells
            const cellContractor = document.createElement('td');
            cellContractor.style.width = '200px';
            cellContractor.style.padding = '0.75rem';
            cellContractor.style.overflow = 'hidden';
            cellContractor.style.textOverflow = 'ellipsis';
            cellContractor.style.whiteSpace = 'nowrap';
            cellContractor.title = contractorName;
            cellContractor.textContent = contractorName;

            const cellProjectName = document.createElement('td');
            cellProjectName.style.width = '250px';
            cellProjectName.style.padding = '0.75rem';
            cellProjectName.style.overflow = 'hidden';
            cellProjectName.style.textOverflow = 'ellipsis';
            cellProjectName.style.whiteSpace = 'nowrap';
            cellProjectName.title = projectName;
            cellProjectName.textContent = projectName;

            const cellRegion = document.createElement('td');
            cellRegion.style.width = '150px';
            cellRegion.style.padding = '0.75rem';
            cellRegion.textContent = region;

            const cellSource = document.createElement('td');
            cellSource.style.width = '100px';
            cellSource.style.padding = '0.75rem';
            cellSource.textContent = source;

            const cellStatus = document.createElement('td');
            cellStatus.style.width = '80px';
            cellStatus.style.padding = '0.75rem';
            cellStatus.style.textAlign = 'center';
            const statusSpan = document.createElement('span');
            statusSpan.className = 'status-circle ' + statusClass;
            cellStatus.appendChild(statusSpan);

            const cellValue = document.createElement('td');
            cellValue.style.width = '120px';
            cellValue.style.padding = '0.75rem';
            cellValue.style.textAlign = 'right';
            cellValue.style.fontWeight = '600';
            cellValue.style.color = '#34d399';
            cellValue.textContent = value;

            const cellTracking = document.createElement('td');
            cellTracking.style.width = '120px';
            cellTracking.style.padding = '0.75rem';
            cellTracking.style.textAlign = 'center';
            const trackingSpan = document.createElement('span');
            trackingSpan.className = 'tracking-badge tracking-' + trackingStatusClass;
            trackingSpan.textContent = trackingStatus;
            cellTracking.appendChild(trackingSpan);

            const cellDate = document.createElement('td');
            cellDate.style.width = '110px';
            cellDate.style.padding = '0.75rem';
            cellDate.style.textAlign = 'right';
            cellDate.style.color = 'var(--text-secondary)';
            cellDate.style.fontSize = '0.85rem';
            cellDate.textContent = dateStr;

            // Append cells to row
            row.appendChild(cellContractor);
            row.appendChild(cellProjectName);
            row.appendChild(cellRegion);
            row.appendChild(cellSource);
            row.appendChild(cellStatus);
            row.appendChild(cellValue);
            row.appendChild(cellTracking);
            row.appendChild(cellDate);

            // Append row to tbody
            tbody.appendChild(row);
        });

        this.updatePagination();
    },

    updatePagination() {
        const total = this.filteredProjects.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const start = (this.currentPage - 1) * this.pageSize + 1;
        const end = Math.min(this.currentPage * this.pageSize, total);

        document.getElementById('pagination-info').textContent = 
            total > 0 ? `Showing ${start}-${end} of ${total} projects` : 'No projects to display';

        const controls = document.getElementById('pagination-controls');
        controls.innerHTML = '';

        if (totalPages <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.textContent = '← Previous';
        prevBtn.disabled = this.currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.renderProjects();
            }
        });
        controls.appendChild(prevBtn);

        for (let i = 1; i <= Math.min(totalPages, 5); i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'pagination-btn' + (i === this.currentPage ? ' active' : '');
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                this.currentPage = i;
                this.renderProjects();
            });
            controls.appendChild(pageBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.textContent = 'Next →';
        nextBtn.disabled = this.currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (this.currentPage < totalPages) {
                this.currentPage++;
                this.renderProjects();
            }
        });
        controls.appendChild(nextBtn);
    },

    getStatusClass(status) {
        const lower = String(status).trim().toLowerCase();
        if (lower === 'priority') return 'priority';
        if (lower === 'awarded') return 'awarded';
        if (lower === 'for execution') return 'for-execution';
        if (lower === 'for bidding') return 'for-bidding';
        return '';
    },

    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    viewProject(projectId) {
        const project = this.allProjects.find(p => p.id === projectId);
        if (!project) return;

        const modal = document.getElementById('detailsModal');
        const modalBody = document.getElementById('detailsModalBody');
        
        const value = (project.project_value || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 });
        
        modalBody.innerHTML = `
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.5rem;">🚫</span>
                    <div>
                        <div style="font-weight: 700; color: #ef4444; margin-bottom: 0.25rem;">Illegitimate Project</div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">This project has been marked as not legitimate</div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">📋 Basic Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Published Date</div>
                        <div class="detail-value">${project.publication_date || '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Source</div>
                        <div class="detail-value">${this.escapeHtml(project.source || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contract ID</div>
                        <div class="detail-value">${this.escapeHtml(project.contractor_id || project.contract_id || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contractor Name</div>
                        <div class="detail-value">${this.escapeHtml(project.contractor_name || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Person</div>
                        <div class="detail-value">${this.escapeHtml(project.contact_person || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">${this.escapeHtml(project.contact_number || '—')}</div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">📍 Contractor Location</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Country</div>
                        <div class="detail-value">${this.escapeHtml(project.contract_country || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Region</div>
                        <div class="detail-value">${this.escapeHtml(project.contract_region || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Province</div>
                        <div class="detail-value">${this.escapeHtml(project.contract_province || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">City</div>
                        <div class="detail-value">${this.escapeHtml(project.contract_city || '—')}</div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">🏗️ Project Details</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Project ID</div>
                        <div class="detail-value">${this.escapeHtml(project.project_id || project.id || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Project Name</div>
                        <div class="detail-value">${this.escapeHtml(project.project_name || '—')}</div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">📍 Project Location</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Country</div>
                        <div class="detail-value">${this.escapeHtml(project.project_country || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Region</div>
                        <div class="detail-value">${this.escapeHtml(project.project_region || project.region || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Province</div>
                        <div class="detail-value">${this.escapeHtml(project.project_province || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">City</div>
                        <div class="detail-value">${this.escapeHtml(project.project_city || '—')}</div>
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
                        <div class="detail-value">${this.escapeHtml(project.status || '—')}</div>
                    </div>
                </div>
            </div>
        `;

        const modalActions = modal.querySelector('.modal-actions');
        modalActions.innerHTML = `
            <button class="btn-secondary" onclick="closeDetailsModal()">Close</button>
            <button class="btn-primary" onclick="IllegalitimateProjectsPage.restoreProject(${projectId})">✅ Mark as Legitimate</button>
        `;

        modal.classList.add('active');
    },

    async restoreProject(projectId) {
        const confirmed = await ModalSystem.confirm({
            title: 'Mark as Legitimate',
            message: 'Mark this project as legitimate? It will be restored to the normal project list.',
            confirmText: 'Yes, Restore',
            cancelText: 'Cancel',
            type: 'success'
        });
        if (!confirmed) return;

        try {
            const response = await fetch(BASE + `/api/v1/projects/${projectId}/actual-project`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ is_actual_project: 'yes' })
            });

            if (!response.ok) throw new Error('Failed to restore project');

            ModalSystem.success('Project marked as legitimate');
            closeDetailsModal();
            this.loadProjects();
        } catch (error) {
            console.error('[RESTORE] Error:', error);
            ModalSystem.error('Failed to restore project');
        }
    },

    showError(message) {
        const tbody = document.getElementById('projects-tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <h3>Error</h3>
                    <p>${this.escapeHtml(message)}</p>
                </td>
            </tr>
        `;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    IllegalitimateProjectsPage.init();
});

function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function saveSalesTracking() {
    ModalSystem.info('Save sales tracking - Coming soon!');
}
