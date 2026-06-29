/* ============================================================
   projects.js — Projects Table View Page JavaScript
   ============================================================ */

// Prevent duplicate initialization
if (typeof ProjectsPage !== 'undefined') {
    console.warn('[PROJECTS] ProjectsPage already defined, skipping redefinition');
} else {

// Projects Page JavaScript
const ProjectsPage = {
    type: null, // Will be set in init()
    currentPage: 1,
    pageSize: 50,
    totalProjects: 0,
    allProjects: [],
    filteredProjects: [],

    async init() {
        // Set type from window.PROJECT_TYPE (must be set AFTER the script that defines window.PROJECT_TYPE)
        this.type = window.PROJECT_TYPE || 'all';
        console.log('[PROJECTS] Initialized with type:', this.type);
        
        // Initialize role manager
        await RoleManager.init();
        
        // Validate session
        const user = await Auth.checkAuth();
        if (!user) return;

        // Apply superadmin settings if available
        await this.loadSettingsPageSize();

        // Load projects
        await this.loadProjects();

        // Setup event listeners
        this.setupEventListeners();

        console.log('[PROJECTS] Page initialized');
    },

    async loadSettingsPageSize() {
        try {
            const res = await fetch(`${BASE}/api/v1/users/settings`, {
                credentials: 'include'
            });

            if (!res.ok) return;

            const data = await res.json();
            const pageSizeSetting = data.settings?.items_per_page?.value;
            const pageSize = parseInt(pageSizeSetting, 10);

            if (!Number.isNaN(pageSize) && pageSize > 0) {
                this.pageSize = Math.min(Math.max(pageSize, 1), 500);
            }
        } catch (err) {
            console.warn('[PROJECTS] Could not load settings page size:', err);
        }
    },

    setupEventListeners() {
        // Search
        document.getElementById('search-input').addEventListener('input', (e) => {
            this.filterProjects();
        });

        // Region filter
        document.getElementById('region-filter').addEventListener('change', () => {
            this.filterProjects();
        });

        // Source filter
        document.getElementById('source-filter').addEventListener('change', () => {
            this.filterProjects();
        });

        // Sort filter
        document.getElementById('sort-filter').addEventListener('change', () => {
            this.filterProjects();
        });

    },

    async loadProjects() {
        try {
            // Fetch projects with type filter
            // Pass the type parameter to API for server-side filtering
            let apiUrl = `${BASE}/api/v1/projects`;
            
            // Add type parameter if specified
            if (this.type && this.type !== 'all') {
                apiUrl += `?type=${encodeURIComponent(this.type)}`;
            }

            console.log('[PROJECTS] Fetching from API:', apiUrl);
            console.log('[PROJECTS] Type filter:', this.type);

            const response = await fetch(apiUrl, {
                credentials: 'include'
            });

            if (!response.ok) throw new Error('Failed to load projects');

            const data = await response.json();
            
            console.log('[PROJECTS] API returned:', data.total, 'projects');
            console.log('[PROJECTS] Sample project (if any):', data.projects[0]);
            
            // IMPORTANT: Filter out archived and illegitimate projects
            // API should already exclude these, but double-check client-side
            this.allProjects = (data.projects || []).filter(p => {
                // Exclude archived projects
                if (p.archived_at) return false;
                
                // Exclude illegitimate projects (is_actual_project = 'no')
                if (p.is_actual_project === 'no') return false;
                
                return true;
            });
            
            this.totalProjects = this.allProjects.length;
            
            console.log('[PROJECTS] After client-side filtering:', this.totalProjects, 'projects');

            // NOTE: Filtering by type is now done server-side via API
            // No need for additional client-side filtering by type
            // The allProjects already contains only the filtered projects from API

            // Update summary cards
            this.updateSummaryCards();

            // Populate region filter
            this.populateRegionFilter();

            // Populate source filter
            this.populateSourceFilter();

            // Initial filter
            this.filterProjects();

        } catch (error) {
            console.error('[PROJECTS] Load error:', error);
            this.showError('Failed to load projects. Please try again.');
        }
    },

    updateSummaryCards() {
        // Check if user is sales_rep
        const userRole = document.body.dataset.role;
        const isSalesRep = userRole === 'sales_rep';
        
        if (isSalesRep) {
            // Cards show stats for all visible projects (already filtered - no archived, no illegitimate)
            const activeProjects = this.allProjects;

            const uniqueContractors = new Set(
                activeProjects
                    .map(p => (p.contractor_name || '').trim())
                    .filter(name => name.length > 0)
            );
            const pipelineValue = activeProjects.reduce((sum, p) => {
                return sum + (parseFloat(p.project_value) || 0);
            }, 0);
            const nonPriorityCount = activeProjects.filter(p =>
                String(p.status || '').trim().toLowerCase() !== 'priority'
            ).length;
            const priorityCount = activeProjects.filter(p =>
                String(p.status || '').trim().toLowerCase() === 'priority'
            ).length;

            document.getElementById('myTotalProjects').textContent = activeProjects.length.toLocaleString();
            document.getElementById('myTotalContractors').textContent = uniqueContractors.size.toLocaleString();
            document.getElementById('myPipelineValue').textContent = this.formatShortCurrency(pipelineValue);
            document.getElementById('myNonPriorityProjects').textContent = nonPriorityCount.toLocaleString();
            document.getElementById('myPriorityProjects').textContent = priorityCount.toLocaleString();
        } else {
            // Admin/Other roles - allProjects is already filtered (no archived, no illegitimate)
            const activeProjects = this.allProjects;
            
            // Total Projects
            document.getElementById('totalProjects').textContent = activeProjects.length.toLocaleString();

            // Total Unique Contractors (trimmed, non-empty only - matches SQL logic)
            const uniqueContractors = new Set(
                activeProjects
                    .map(p => (p.contractor_name || '').trim())
                    .filter(name => name.length > 0)
            );
            document.getElementById('totalContractors').textContent = uniqueContractors.size.toLocaleString();

            // Pipeline Value (sum of all project values)
            const pipelineValue = activeProjects.reduce((sum, p) => {
                return sum + (parseFloat(p.project_value) || 0);
            }, 0);
            document.getElementById('pipelineValue').textContent = this.formatShortCurrency(pipelineValue);
        }
    },

    formatShortCurrency(value) {
        if (value >= 1000000000) {
            return '₱' + (value / 1000000000).toFixed(1) + 'B';
        } else if (value >= 1000000) {
            return '₱' + (value / 1000000).toFixed(1) + 'M';
        } else if (value >= 1000) {
            return '₱' + (value / 1000).toFixed(1) + 'K';
        } else {
            return '₱' + value.toFixed(2);
        }
    },

    populateRegionFilter() {
        const regions = [...new Set(this.allProjects.map(p => p.project_region || p.region).filter(Boolean))];
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

    populateSourceFilter() {
        const sources = [...new Set(this.allProjects.map(p => (p.source || '').trim()).filter(Boolean))];
        sources.sort();

        const select = document.getElementById('source-filter');
        const currentValue = select.value;

        select.innerHTML = '<option value="">All Sources</option>';
        sources.forEach(source => {
            const option = document.createElement('option');
            option.value = source;
            option.textContent = source;
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
            // Search filter
            const matchesSearch = !searchTerm || 
                (project.contractor_name || '').toLowerCase().includes(searchTerm) ||
                (project.project_name || '').toLowerCase().includes(searchTerm) ||
                (project.project_region || project.region || '').toLowerCase().includes(searchTerm) ||
                (project.project_id || '').toLowerCase().includes(searchTerm) ||
                (project.contractor_id || '').toLowerCase().includes(searchTerm);

            // Region filter
            const matchesRegion = !regionFilter || (project.project_region || project.region) === regionFilter;

            // Source filter
            const matchesSource = !sourceFilter || project.source === sourceFilter;

            return matchesSearch && matchesRegion && matchesSource;
        });

        // Apply sorting
        this.sortProjects(sortFilter);

        this.currentPage = 1;
        this.renderProjects();
    },

    sortProjects(sortBy) {
        if (!sortBy) sortBy = 'publication_date_desc';

        const parts = sortBy.split('_');
        const direction = parts[parts.length - 1];
        const field = parts.slice(0, -1).join('_');
        const isAsc = direction === 'asc';

        this.filteredProjects.sort((a, b) => {
            let valueA, valueB;

            switch (field) {
                case 'publication_date':
                case 'publication':
                    valueA = new Date(a.publication_date || a.published_date || a.published_at || 0);
                    valueB = new Date(b.publication_date || b.published_date || b.published_at || 0);
                    break;
                case 'created_at':
                case 'created':
                    valueA = new Date(a.created_at || 0);
                    valueB = new Date(b.created_at || 0);
                    break;
                case 'contractor_name':
                case 'contractor':
                    valueA = (a.contractor_name || '').toLowerCase();
                    valueB = (b.contractor_name || '').toLowerCase();
                    break;
                case 'project_name':
                case 'project':
                    valueA = (a.project_name || '').toLowerCase();
                    valueB = (b.project_name || '').toLowerCase();
                    break;
                case 'project_value':
                case 'value':
                    valueA = parseFloat(a.project_value || 0);
                    valueB = parseFloat(b.project_value || 0);
                    break;
                case 'region':
                    valueA = (a.project_region || a.region || '').toLowerCase();
                    valueB = (b.project_region || b.region || '').toLowerCase();
                    break;
                case 'status':
                    valueA = (a.status || '').toLowerCase();
                    valueB = (b.status || '').toLowerCase();
                    break;
                case 'tracking_status':
                case 'tracking':
                    valueA = (a.sales_tracking_status || a.tracking_status || 'Not Started').toLowerCase();
                    valueB = (b.sales_tracking_status || b.tracking_status || 'Not Started').toLowerCase();
                    // Custom order for tracking status
                    const statusOrder = { 'not started': 0, 'in progress': 1, 'complete': 2 };
                    valueA = statusOrder[valueA] ?? 0;
                    valueB = statusOrder[valueB] ?? 0;
                    break;
                default:
                    return 0;
            }

            // Handle different data types
            if (typeof valueA === 'string' && typeof valueB === 'string') {
                return isAsc ? valueA.localeCompare(valueB) : valueB.localeCompare(valueA);
            } else if (typeof valueA === 'number' && typeof valueB === 'number') {
                return isAsc ? valueA - valueB : valueB - valueA;
            } else if (valueA instanceof Date && valueB instanceof Date) {
                return isAsc ? valueA - valueB : valueB - valueA;
            }

            return 0;
        });
    },

    renderProjects() {
        const tbody = document.getElementById('projects-tbody');
        const start = (this.currentPage - 1) * this.pageSize;
        const end = start + this.pageSize;
        const pageProjects = this.filteredProjects.slice(start, end);

        if (this.filteredProjects.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3>No Projects Found</h3>
                        <p>Try adjusting your search or filters</p>
                    </td>
                </tr>
            `;
            this.updatePagination();
            return;
        }

        tbody.innerHTML = pageProjects.map((project, index) => {
            // Format publication date
            let dateStr = '—';
            if (project.publication_date) {
                const dt = new Date(project.publication_date);
                dateStr = dt.toLocaleDateString('en-PH', {
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric'
                });
            }
            
            const value = project.project_value !== null && project.project_value !== undefined
                ? formatCurrency(project.project_value)
                : '—';
            const status = project.status || '—';
            const statusClass = this.getStatusClass(status);
            
            // Get sales tracking status
            const trackingStatus = project.sales_tracking_status || project.tracking_status || 'Not Started';
            const trackingBadge = this.getTrackingBadge(trackingStatus);

            return `
                <tr data-project-id="${project.id}" onclick="ProjectsPage.viewProject(${project.id})" style="cursor: pointer;">
                    <td title="${this.escapeHtml(project.contractor_name)}">${this.escapeHtml(project.contractor_name || '—')}</td>
                    <td title="${this.escapeHtml(project.project_name)}">${this.escapeHtml(project.project_name || '—')}</td>
                    <td>${this.escapeHtml(project.project_region || project.region || '—')}</td>
                    <td>${this.escapeHtml(project.source || '—')}</td>
                    <td style="text-align: center;"><span class="status-circle ${statusClass}"></span></td>
                    <td class="col-value">${value}</td>
                    <td class="col-tracking">${trackingBadge}</td>
                    <td class="col-date">${dateStr}</td>
                </tr>
            `;
        }).join('');

        this.updatePagination();
    },

    updatePagination() {
        const total = this.filteredProjects.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const start = (this.currentPage - 1) * this.pageSize + 1;
        const end = Math.min(this.currentPage * this.pageSize, total);

        // Update info
        document.getElementById('pagination-info').textContent = 
            total > 0 ? `Showing ${start}-${end} of ${total} projects` : 'No projects to display';

        // Update controls
        const controls = document.getElementById('pagination-controls');
        controls.innerHTML = '';

        if (totalPages <= 1) return;

        // Previous button
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

        // Page numbers
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

        // Next button
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

    getTrackingBadge(status) {
        const lower = String(status).trim().toLowerCase();
        if (lower === 'complete') {
            return '<span class="tracking-badge complete">Complete</span>';
        } else if (lower === 'in progress') {
            return '<span class="tracking-badge in-progress">In Progress</span>';
        } else {
            return '<span class="tracking-badge not-started">Not Started</span>';
        }
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

    buildCompleteAddress(type, project) {
        // Build complete address from individual components
        const prefix = type === 'contract' ? 'contract_' : 'project_';
        
        const components = [
            project[prefix + 'blk_lot'],
            project[prefix + 'street'],
            project[prefix + 'barangay'],
            this.getFullLocationName(project[prefix + 'city'], 'city'),
            this.getFullLocationName(project[prefix + 'province'], 'province'),
            this.getFullLocationName(project[prefix + 'region'], 'region'),
            this.getFullLocationName(project[prefix + 'country'], 'country')
        ].filter(component => component && component.trim() !== '');
        
        return components.length > 0 ? components.join(', ') : null;
    },

    getFullLocationName(codeOrName, type) {
        if (!codeOrName) return null;
        
        // If it's already a full name (longer than 10 chars or contains spaces after first word), return as-is
        const trimmed = String(codeOrName).trim();
        const words = trimmed.split(' ');
        if (trimmed.length > 10 || words.length > 1) {
            return trimmed;
        }
        
        // Otherwise, try to map the code to full name
        // Location code mappings - convert acronyms to full names
        const locationMappings = {
            // Countries
            'PH': 'Philippines',
            'Philippines': 'Philippines',
            
            // Regions
            'NCR': 'National Capital Region (NCR)',
            'CAR': 'Cordillera Administrative Region (CAR)',
            'I': 'Ilocos Region (Region I)',
            'II': 'Cagayan Valley (Region II)',
            'III': 'Central Luzon (Region III)',
            'IV-A': 'CALABARZON (Region IV-A)',
            'IV-B': 'MIMAROPA (Region IV-B)',
            'V': 'Bicol Region (Region V)',
            'VI': 'Western Visayas (Region VI)',
            'VII': 'Central Visayas (Region VII)',
            'VIII': 'Eastern Visayas (Region VIII)',
            'IX': 'Zamboanga Peninsula (Region IX)',
            'X': 'Northern Mindanao (Region X)',
            'XI': 'Davao Region (Region XI)',
            'XII': 'SOCCSKSARGEN (Region XII)',
            'XIII': 'Caraga (Region XIII)',
            'BARMM': 'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)',
            
            // Common Provinces
            'NEG': 'Negros Occidental',
            'CEB': 'Cebu',
            'ILO': 'Iloilo',
            'BAT': 'Batangas',
            'CAV': 'Cavite',
            'LAG': 'Laguna',
            'RIZ': 'Rizal',
            'BUL': 'Bulacan',
            'PAM': 'Pampanga',
            'TAR': 'Tarlac',
            'NE': 'Nueva Ecija',
            'ZAM': 'Zambales',
            'QUE': 'Quezon',
            'ALB': 'Albay',
            'CAS': 'Camarines Sur',
            'CAN': 'Camarines Norte',
            'SOR': 'Sorsogon',
            'MAS': 'Masbate',
            'CAT': 'Catanduanes',
            'ILN': 'Ilocos Norte',
            'ILS': 'Ilocos Sur',
            'LU': 'La Union',
            'PAN': 'Pangasinan',
            'ISA': 'Isabela',
            'CAG': 'Cagayan',
            'NV': 'Nueva Vizcaya',
            'QUI': 'Quirino',
            'BAT': 'Batanes',
            'AUR': 'Aurora',
            'KAL': 'Kalinga',
            'MAR': 'Marinduque',
            'OCC': 'Occidental Mindoro',
            'ORI': 'Oriental Mindoro',
            'PAL': 'Palawan',
            'ROM': 'Romblon',
            'AKL': 'Aklan',
            'ANT': 'Antique',
            'CAP': 'Capiz',
            'GUI': 'Guimaras',
            'BOH': 'Bohol',
            'SIQ': 'Siquijor',
            'BIL': 'Biliran',
            'EAS': 'Eastern Samar',
            'LEY': 'Leyte',
            'NOR': 'Northern Samar',
            'SAM': 'Samar',
            'SOU': 'Southern Leyte',
            'ZAN': 'Zamboanga del Norte',
            'ZAS': 'Zamboanga del Sur',
            'ZSI': 'Zamboanga Sibugay',
            'BUK': 'Bukidnon',
            'CAM': 'Camiguin',
            'LAN': 'Lanao del Norte',
            'MIS': 'Misamis Occidental',
            'MOR': 'Misamis Oriental',
            'COM': 'Compostela Valley',
            'DAO': 'Davao del Norte',
            'DAS': 'Davao del Sur',
            'DAV': 'Davao Oriental',
            'COT': 'Cotabato',
            'SAR': 'Sarangani',
            'SCO': 'South Cotabato',
            'SUL': 'Sultan Kudarat',
            'AGU': 'Agusan del Norte',
            'AGS': 'Agusan del Sur',
            'DIN': 'Dinagat Islands',
            'SUR': 'Surigao del Norte',
            'SUS': 'Surigao del Sur',
            'ABR': 'Abra',
            'APY': 'Apayao',
            'BEN': 'Benguet',
            'IFU': 'Ifugao',
            'KAL': 'Kalinga',
            'MOU': 'Mountain Province',
            'BAS': 'Basilan',
            'MAG': 'Maguindanao',
            'TAW': 'Tawi-Tawi',
            
            // Common Cities
            'BAC': 'Bacolod City',
            'ILO': 'Iloilo City',
            'CEB': 'Cebu City',
            'DAV': 'Davao City',
            'MNL': 'Manila',
            'QC': 'Quezon City',
            'CCN': 'Caloocan',
            'LAS': 'Las Piñas',
            'MAK': 'Makati',
            'MAL': 'Malabon',
            'MAN': 'Mandaluyong',
            'MAR': 'Marikina',
            'MUN': 'Muntinlupa',
            'NAV': 'Navotas',
            'PAR': 'Parañaque',
            'PAS': 'Pasay',
            'PAT': 'Pateros',
            'SJU': 'San Juan',
            'TAF': 'Taguig',
            'VAL': 'Valenzuela',
            'PSG': 'Pasig',
            'LIG': 'Ligao City',
            'LEG': 'Legazpi City',
            'NAG': 'Naga City',
            'TAB': 'Tabuk City',
            'TABACO': 'Tabaco City',
            'SOL': 'Sorsogon City',
            'MAS': 'Masbate City',
            'VIG': 'Vigan City',
            'LAO': 'Laoag City',
            'SFE': 'San Fernando City',
            'BAG': 'Baguio City',
            'TUG': 'Tuguegarao City',
            'ILG': 'Ilagan City',
            'CAB': 'Cabanatuan City',
            'SJO': 'San Jose City',
            'TRL': 'Tarlac City',
            'OLO': 'Olongapo City',
            'BAL': 'Balanga City',
            'BAT': 'Batangas City',
            'LIP': 'Lipa City',
            'TAN': 'Tanauan City',
            'CAV': 'Cavite City',
            'DAS': 'Dasmariñas City',
            'IMS': 'Imus City',
            'BCR': 'Bacoor City',
            'CAL': 'Calamba City',
            'STA': 'Santa Rosa City',
            'BIN': 'Biñan City',
            'SPE': 'San Pedro City',
            'CBY': 'Cabuyao City',
            'ANT': 'Antipolo City',
            'TAY': 'Taytay',
            'CAI': 'Cainta',
            'MOR': 'Morong',
            'TER': 'Teresa',
            'BIC': 'Binangonan',
            'PIL': 'Pililla',
            'JAL': 'Jala-jala',
            'TNY': 'Tanay',
            'BAR': 'Baras',
            'ROD': 'Rodriguez',
            'SMA': 'San Mateo'
        };
        
        // Try to find mapping for the code
        const mapped = locationMappings[trimmed];
        return mapped || trimmed; // Return mapped value or original if not found
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
    },

    viewProject(projectId) {
        const project = this.allProjects.find(p => p.id === projectId);
        if (!project) return;

        // Debug: Log the project data to see what fields are available
        console.log('[DEBUG] Project data:', project);
        console.log('[DEBUG] Materials data:', {
            drbs_value: project.drbs_value,
            sheet_pile_amount: project.sheet_pile_amount,
            ms_plate: project.ms_plate,
            angle_bars: project.angle_bars
        });
        console.log('[DEBUG] Contract country:', project.contract_country);
        console.log('[DEBUG] Contract region:', project.contract_region);
        console.log('[DEBUG] Contract province:', project.contract_province);
        console.log('[DEBUG] Contract city:', project.contract_city);
        console.log('[DEBUG] Project region:', project.project_region);
        console.log('[DEBUG] Address field:', project.address);
        console.log('[DEBUG] City Province field:', project.city_province);

        const modal = document.getElementById('detailsModal');
        const modalBody = document.getElementById('detailsModalBody');

        // Format values
        const value = project.project_value !== null && project.project_value !== undefined
            ? formatCurrency(project.project_value)
            : '—';
        
        const dateTime = project.created_at 
            ? new Date(project.created_at).toLocaleString('en-PH', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            })
            : '—';

        modalBody.innerHTML = `
            <!-- Project Images (priority only) -->
            <div id="projectImagesSection" style="display:none; margin-bottom: 1.25rem;">
                <div class="detail-section-title" style="margin-bottom:0.75rem;">📷 Project Images</div>
                <div id="projectImagesGrid" style="
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                    gap: 0.6rem;
                "></div>
            </div>

            <!-- First Group -->
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

            <!-- Contractor Location Details -->
            <div class="detail-section">
                <div class="detail-section-title">📍 Contractor Location</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Country</div>
                        <div class="detail-value">${this.escapeHtml(this.getFullLocationName(project.contract_country, 'country') || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Region</div>
                        <div class="detail-value">${this.escapeHtml(this.getFullLocationName(project.contract_region, 'region') || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Province</div>
                        <div class="detail-value">${this.escapeHtml(this.getFullLocationName(project.contract_province, 'province') || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">City</div>
                        <div class="detail-value">${this.escapeHtml(this.getFullLocationName(project.contract_city, 'city') || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Barangay</div>
                        <div class="detail-value">${this.escapeHtml(project.contract_barangay || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Street</div>
                        <div class="detail-value">${this.escapeHtml(project.contract_street || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">BLK/LOT#</div>
                        <div class="detail-value">${this.escapeHtml(project.contract_blk_lot || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Coordinates</div>
                        <div class="detail-value">${this.escapeHtml(project.contract_coordinates || '—')}</div>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <div class="detail-label">Complete Address</div>
                        <div class="detail-value">${this.escapeHtml(project.address || this.buildCompleteAddress('contract', project) || '—')}</div>
                    </div>
                </div>
            </div>

            <!-- Second Group -->
            <div class="detail-section">
                <div class="detail-section-title">🏗️ Project Details</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Project ID</div>
                        <div class="detail-value">${this.escapeHtml(project.project_id || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Project Name</div>
                        <div class="detail-value">${this.escapeHtml(project.project_name || '—')}</div>
                    </div>
                </div>
            </div>

            <!-- Project Location Details -->
            <div class="detail-section">
                <div class="detail-section-title">📍 Project Location</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Country</div>
                        <div class="detail-value">${this.escapeHtml(this.getFullLocationName(project.project_country, 'country') || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Region</div>
                        <div class="detail-value">${this.escapeHtml(this.getFullLocationName(project.project_region || project.region, 'region') || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Province</div>
                        <div class="detail-value">${this.escapeHtml(this.getFullLocationName(project.project_province, 'province') || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">City</div>
                        <div class="detail-value">${this.escapeHtml(this.getFullLocationName(project.project_city, 'city') || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Barangay</div>
                        <div class="detail-value">${this.escapeHtml(project.project_barangay || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Street</div>
                        <div class="detail-value">${this.escapeHtml(project.project_street || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">BLK/LOT#</div>
                        <div class="detail-value">${this.escapeHtml(project.project_blk_lot || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Coordinates</div>
                        <div class="detail-value">${this.escapeHtml(project.project_coordinates || '—')}</div>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <div class="detail-label">Complete Address</div>
                        <div class="detail-value">${this.escapeHtml(this.buildCompleteAddress('project', project) || this.getFullLocationName(project.city_province, 'city') || this.getFullLocationName(project.region, 'region') || '—')}</div>
                    </div>
                </div>
            </div>

            <!-- Project Value and Status -->
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
                            <span class="status-badge ${this.getStatusClass(project.status)}">${this.escapeHtml(project.status || '—')}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Third Group - Materials -->
            <div class="detail-section">
                <div class="detail-section-title">🔧 Materials</div>
                <div class="detail-grid">
                    ${project.status && project.status.toLowerCase() === 'priority' ? `
                    <!-- Priority Project Materials -->
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <div class="detail-label">Sheet Pile Type</div>
                        <div class="detail-value">${this.escapeHtml(project.sheet_pile_type || '—')}</div>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <div class="detail-label">DRBs Type</div>
                        <div class="detail-value">${this.escapeHtml(project.drbs || '—')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">DRBS (Amount)</div>
                        <div class="detail-value">${project.drbs_value ? formatCurrency(project.drbs_value) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Sheet Pile (Amount)</div>
                        <div class="detail-value">${project.sheet_pile_amount ? formatCurrency(project.sheet_pile_amount) : '—'}</div>
                    </div>
                    ` : `
                    <!-- Non-Priority Project Materials -->
                    <div class="detail-item">
                        <div class="detail-label">DRBS (Amount)</div>
                        <div class="detail-value">${project.drbs_value ? formatCurrency(project.drbs_value) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Sheet Pile (Amount)</div>
                        <div class="detail-value">${project.sheet_pile_amount ? formatCurrency(project.sheet_pile_amount) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">MS Plate (Amount)</div>
                        <div class="detail-value">${project.ms_plate ? formatCurrency(project.ms_plate) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Angle Bars (Amount)</div>
                        <div class="detail-value">${project.angle_bars ? formatCurrency(project.angle_bars) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Channel Bars (Amount)</div>
                        <div class="detail-value">${project.channel_bars ? formatCurrency(project.channel_bars) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Wide Flange (Amount)</div>
                        <div class="detail-value">${project.wide_flange ? formatCurrency(project.wide_flange) : '—'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">GI/BI (Amount)</div>
                        <div class="detail-value">${project.gi_bi ? formatCurrency(project.gi_bi) : '—'}</div>
                    </div>
                    `}
                </div>
            </div>

            <!-- Sales tracking has moved to Project Management -->
            ${(project.sales_tracking_status || project.tracking_status)
                ? `<div class="detail-section">
                    <div class="detail-section-title">📊 Sales Tracking</div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Tracking Status</div>
                            <div class="detail-value">
                                <span class="tracking-badge ${(project.sales_tracking_status || project.tracking_status || '').toLowerCase().replace(/\s+/g,'-')}">
                                    ${this.escapeHtml(project.sales_tracking_status || project.tracking_status || 'Not Started')}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Sales Rep</div>
                            <div class="detail-value">${this.escapeHtml(project.sales_rep_name || '—')}</div>
                        </div>
                    </div>
                    <p style="margin:0.75rem 0 0; font-size:0.78rem; color:var(--text-muted);">
                        📌 To update sales tracking, go to <strong>Project Management</strong>.
                    </p>
                   </div>`
                : ''
            }
        `;

        // Store current project ID for future use
        modal.dataset.projectId = projectId;
        modal.dataset.assignedTo = project.assigned_to || '';
        
        // Setup yes/no button handlers with progressive validation
        setTimeout(() => {
            // Role-based: closeModalBtn always needs a handler
            const closeModalBtn = document.getElementById('closeModalBtn');
            if (closeModalBtn) {
                // Remove any previous listener by cloning
                const newClose = closeModalBtn.cloneNode(true);
                closeModalBtn.parentNode.replaceChild(newClose, closeModalBtn);
                newClose.addEventListener('click', () => {
                    document.getElementById('detailsModal')?.classList.remove('active');
                });
            }
        }, 100);
        
        // Show/Hide Archive Button based on user role and project archive status
        const archiveBtn = document.getElementById('archiveBtn');
        const userRole = document.body.dataset.role || '';
        
        if (archiveBtn && (userRole === 'admin' || userRole === 'superadmin')) {
            const isArchived = project.archived_at !== null && project.archived_at !== undefined;
            
            if (isArchived) {
                archiveBtn.innerHTML = '📤 Restore Project';
                archiveBtn.className = 'btn-action btn-secondary';
                archiveBtn.title = `Archived on ${project.archived_at}`;
            } else {
                archiveBtn.innerHTML = '🗄️ Archive Project';
                archiveBtn.className = 'btn-action btn-delete';
                archiveBtn.title = 'Move project to archive';
            }
            
            // Store project ID for archive function
            modal.dataset.projectId = projectId;
        }
        
        // Store project ID for other functions
        modal.dataset.projectId = projectId;
        
        modal.classList.add('active');

        // Fetch and render project images at the top
        this.loadProjectImages(projectId);
    },

    async loadProjectImages(projectId) {
        try {
            const res = await fetch(`${BASE}/api/v1/projects/${projectId}/images`, {
                credentials: 'include'
            });
            if (!res.ok) return;
            const data = await res.json();
            const images = data.images || [];
            if (images.length === 0) return;

            const section = document.getElementById('projectImagesSection');
            const grid    = document.getElementById('projectImagesGrid');
            if (!section || !grid) return;

            grid.innerHTML = images.map(img => `
                <a href="${BASE}/${img.file_path}" target="_blank" style="
                    display: block; border-radius: 8px; overflow: hidden;
                    border: 1px solid rgba(0,0,0,0.08);
                    aspect-ratio: 1 / 1;
                    background: #111;
                ">
                    <img src="${BASE}/${img.file_path}" alt="Project image"
                        style="width:100%; height:100%; object-fit:cover; display:block;"
                        loading="lazy"
                        onerror="this.parentElement.style.display='none'">
                </a>
            `).join('');
            section.style.display = 'block';
        } catch (e) {
            // silently fail — images are optional
        }
    },
    
    async loadSalesReps() {
        try {
            const response = await fetch(BASE + '/api/v1/users/sales-reps', {
                credentials: 'include'
            });
            
            if (!response.ok) {
                console.error('[PROJECTS] Sales reps API error:', response.status);
                return;
            }
            
            const result = await response.json();
            console.log('[PROJECTS] Sales reps response:', result);
            
            const select = document.getElementById('sales-rep-select');
            const branchInput = document.getElementById('branch-input');
            
            if (!select) return;
            
            select.innerHTML = '<option value="">Select SR...</option>';
            
            // The API returns data in result.data, not result.sales_reps
            const salesReps = (result.data || []).slice().sort((a, b) =>
                (a.full_name || '').localeCompare(b.full_name || '')
            );
            
            salesReps.forEach(sr => {
                const option = document.createElement('option');
                option.value = sr.id;
                option.textContent = sr.full_name;
                option.dataset.branch = sr.branch || 'N/A';
                select.appendChild(option);
            });
            
            console.log('[PROJECTS] Added', salesReps.length, 'sales reps to dropdown');
            
            // Auto-fill branch when SR is selected
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (branchInput && selectedOption) {
                    branchInput.value = selectedOption.dataset.branch || '';
                }
            });
        } catch (error) {
            console.error('[PROJECTS] Load sales reps error:', error);
        }
    },

    async loadSalesTrackingData(projectId) {
        try {
            console.log('[PROJECTS] Loading sales tracking data for project:', projectId);
            
            const url = BASE + `/api/v1/projects/${projectId}/sales-tracking`;
            console.log('[PROJECTS] Fetching from URL:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            console.log('[PROJECTS] Response status:', response.status, response.statusText);
            
            if (!response.ok) {
                // Log the full response for debugging
                const errorText = await response.text();
                console.error('[PROJECTS] Sales tracking API error:', response.status);
                console.error('[PROJECTS] Error response body:', errorText);
                
                // Try to parse as JSON if possible
                try {
                    const errorJson = JSON.parse(errorText);
                    console.error('[PROJECTS] Error detail:', errorJson.detail || errorJson);
                } catch (e) {
                    console.error('[PROJECTS] Raw error:', errorText);
                }
                
                // Don't show toast - just log the error
                // The modal can still be used even if tracking data fails to load
                return;
            }
            
            const result = await response.json();
            console.log('[PROJECTS] Sales tracking response:', result);
            
            if (result.exists && result.data) {
                const data = result.data;
                
                console.log('[PROJECTS] Sales tracking data exists, restoring...');
                
                // Restore button states
                this.restoreButtonStates(data);
                
                // Restore form field values
                this.restoreFormFields(data);
                
                console.log('[PROJECTS] ✓ Sales tracking data restored successfully');
            } else {
                console.log('[PROJECTS] No existing sales tracking data found');
            }
            
        } catch (error) {
            console.error('[PROJECTS] Load sales tracking error:', error);
            console.error('[PROJECTS] Error stack:', error.stack);
            // Don't show toast - silent failure is acceptable for optional data
        }
    },

    restoreButtonStates(data) {
        console.log('[PROJECTS] Starting restoreButtonStates with data:', data);
        
        // First, check if yes-no buttons exist in DOM
        const allButtons = document.querySelectorAll('.yes-no-btn');
        console.log('[PROJECTS] Found ' + allButtons.length + ' yes-no buttons in DOM');
        
        if (allButtons.length === 0) {
            console.error('[PROJECTS] No yes-no buttons found in DOM! Modal might not be ready.');
            // Retry after a short delay
            setTimeout(() => {
                console.log('[PROJECTS] Retrying button state restoration...');
                this.restoreButtonStates(data);
            }, 200);
            return;
        }
        
        // First, clear all button states - no defaults
        allButtons.forEach(btn => {
            btn.classList.remove('active', 'yes', 'no');
        });
        
        // Restore yes/no button states only if there's actual saved data
        const fields = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
        
        fields.forEach(field => {
            const value = data[field];
            console.log(`[PROJECTS] Field ${field} value:`, value, typeof value);
            
            // Only set button state if there's a definitive true/false value (not null/undefined)
            if (value === true) {
                const button = document.querySelector(`.yes-no-btn[data-field="${field}"][data-value="yes"]`);
                if (button) {
                    button.classList.add('active', 'yes');
                    console.log(`[PROJECTS] ✓ Restored button: ${field} = yes`);
                } else {
                    console.error(`[PROJECTS] ✗ Button not found: ${field} = yes`);
                }
            } else if (value === false) {
                const button = document.querySelector(`.yes-no-btn[data-field="${field}"][data-value="no"]`);
                if (button) {
                    button.classList.add('active', 'no');
                    console.log(`[PROJECTS] ✓ Restored button: ${field} = no`);
                } else {
                    console.error(`[PROJECTS] ✗ Button not found: ${field} = no`);
                }
            } else {
                // value is null/undefined - leave buttons unselected
                console.log(`[PROJECTS] Field ${field} is unset - leaving buttons unselected`);
            }
        });
        
        // Update field states after restoring buttons
        this.updateFieldStates();
        console.log('[PROJECTS] Button state restoration complete');
    },

    restoreFormFields(data) {
        // Restore sales rep selection
        const salesRepSelect = document.getElementById('sales-rep-select');
        if (salesRepSelect && data.sales_rep_id) {
            salesRepSelect.value = data.sales_rep_id;
            
            // Trigger change event to update branch
            const event = new Event('change');
            salesRepSelect.dispatchEvent(event);
        }
        
        // Restore branch (if not auto-filled from sales rep)
        const branchInput = document.getElementById('branch-input');
        if (branchInput && data.branch) {
            branchInput.value = data.branch;
        }
        
        // Restore W/L amount
        const wlAmountInput = document.getElementById('wl-amount-input');
        if (wlAmountInput) {
            wlAmountInput.value = data.wa_amount || '';
        }
        
        // Restore remarks
        const remarksTextarea = document.getElementById('remarks-textarea');
        if (remarksTextarea) {
            remarksTextarea.value = data.notes || data.remarks || '';
        }
    },

    getSalesTrackingStatus(project) {
        // Use the status from API if available
        if (project.sales_tracking_status) {
            return project.sales_tracking_status;
        }
        
        // Fallback: Check if project has sales tracking data
        if (!project.sales_tracking) {
            return 'Not Started';
        }
        
        const tracking = project.sales_tracking;
        
        // Count filled fields (excluding remarks which is optional)
        const fields = ['contacted', 'quoted', 'sales_qualified', 'to_win', 'wa_amount'];
        const filledFields = fields.filter(field => {
            const value = tracking[field];
            return value !== null && value !== undefined && value !== '';
        });
        
        if (filledFields.length === 0) {
            return 'Not Started';
        } else if (filledFields.length === fields.length) {
            return 'Complete';
        } else {
            return 'In Progress';
        }
    },

    getTrackingBadge(status) {
        const statusClass = status.toLowerCase().replace(/\s+/g, '-');
        return `<span class="tracking-badge ${statusClass}">${status}</span>`;
    },

    setupRoleBasedVisibility(userRole) {
        // Hide/show elements based on role access
        document.querySelectorAll('[data-role-access]').forEach(element => {
            const allowedRoles = element.dataset.roleAccess.split(',');
            if (!allowedRoles.includes(userRole)) {
                element.style.display = 'none';
            } else {
                element.style.display = '';
            }
        });
    },

    setupProgressiveFields() {
        const fieldOrder = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
        
        // Clear all button states first - no defaults
        document.querySelectorAll('.yes-no-btn').forEach(btn => {
            btn.classList.remove('active', 'yes', 'no');
        });
        
        // Initially disable all fields except the first one
        this.updateFieldStates();
        
        // Setup button handlers
        document.querySelectorAll('.yes-no-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const field = e.target.dataset.field;
                const value = e.target.dataset.value;
                
                // Check if this field is allowed to be clicked
                if (!this.isFieldEnabled(field)) {
                    this.showModernNotification('Please complete the previous fields first', 'warning');
                    return;
                }
                
                // Update button states
                const buttons = document.querySelectorAll(`.yes-no-btn[data-field="${field}"]`);
                buttons.forEach(b => {
                    b.classList.remove('active');
                    b.classList.remove('yes', 'no'); // Remove old classes
                });
                
                // Add active class and value class
                e.target.classList.add('active');
                e.target.classList.add(value); // Add 'yes' or 'no' class
                
                // Show/hide W/L Amount required asterisk based on "To Win" selection
                if (field === 'to_win') {
                    const wlAmountRequired = document.getElementById('wl-amount-required');
                    if (wlAmountRequired) {
                        if (value === 'yes') {
                            wlAmountRequired.style.display = 'inline';
                        } else {
                            wlAmountRequired.style.display = 'none';
                        }
                    }
                }
                
                console.log(`[PROJECTS] Button clicked: ${field} = ${value}`);
                console.log(`[PROJECTS] Button classes:`, e.target.classList.toString());
                
                // Update field states after selection
                this.updateFieldStates();
            });
        });
    },

    isFieldEnabled(field) {
        const fieldOrder = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
        const currentIndex = fieldOrder.indexOf(field);
        
        if (currentIndex === 0) return true; // First field is always enabled
        
        // Check if all previous fields are filled
        for (let i = 0; i < currentIndex; i++) {
            const prevField = fieldOrder[i];
            const hasSelection = document.querySelector(`.yes-no-btn[data-field="${prevField}"].active`);
            if (!hasSelection) return false;
        }
        
        return true;
    },

    updateFieldStates() {
        const fieldOrder = ['contacted', 'quoted', 'sales_qualified', 'to_win'];
        
        fieldOrder.forEach(field => {
            const buttons = document.querySelectorAll(`.yes-no-btn[data-field="${field}"]`);
            const isEnabled = this.isFieldEnabled(field);
            
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
        
        // W/L Amount and Remarks are always enabled - user can fill them anytime
        const wlAmountInput = document.getElementById('wl-amount-input');
        const remarksTextarea = document.getElementById('remarks-textarea');
        
        if (wlAmountInput) {
            wlAmountInput.disabled = false;
            wlAmountInput.style.opacity = '1';
        }
        
        if (remarksTextarea) {
            remarksTextarea.disabled = false;
            remarksTextarea.style.opacity = '1';
        }
    },

    showModernNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelector('.modern-notification');
        if (existing) existing.remove();
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `modern-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    ${type === 'warning' ? '⚠️' : type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️'}
                </div>
                <div class="notification-message">${message}</div>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 4000);
        
        // Animate in
        setTimeout(() => notification.classList.add('show'), 10);
    },

    async saveSalesTracking(projectId) {
        // Collect sales tracking data
        const toWin = document.querySelector('.yes-no-btn[data-field="to_win"].active')?.dataset.value;
        const sql = document.querySelector('.yes-no-btn[data-field="sales_qualified"].active')?.dataset.value;
        const contacted = document.querySelector('.yes-no-btn[data-field="contacted"].active')?.dataset.value;
        const quoted = document.querySelector('.yes-no-btn[data-field="quoted"].active')?.dataset.value;
        const salesRepId = document.getElementById('sales-rep-select')?.value;
        const branch = document.getElementById('branch-input')?.value;
        const wlAmount = document.getElementById('wl-amount-input')?.value;
        const remarks = document.getElementById('remarks-textarea')?.value;
        
        // Progressive validation
        const errors = [];
        
        // Always required fields
        if (!salesRepId) {
            errors.push('Please select a Sales Representative');
        }
        
        if (!branch || branch.trim() === '') {
            errors.push('Please enter Branch information');
        }
        
        if (!remarks || remarks.trim() === '') {
            errors.push('Please enter Remarks');
        }
        
        // Check if at least one field has been filled (exclude remarks which is checked separately)
        if (!contacted && !quoted && !sql && !toWin && (!wlAmount || wlAmount === '' || parseFloat(wlAmount) <= 0)) {
            this.showModernNotification('Please fill at least one field to save', 'warning');
            return;
        }
        
        // Validate progressive order
        if (quoted && !contacted) {
            errors.push('Please fill "Contacted" first before "Quoted"');
        }
        if (sql && (!contacted || !quoted)) {
            errors.push('Please fill "Contacted" and "Quoted" first before "Sales Qualified Leads"');
        }
        if (toWin && (!contacted || !quoted || !sql)) {
            errors.push('Please fill previous fields first before "To Win"');
        }
        
        // W/L Amount is required if "To Win" is "Yes"
        if (toWin === 'yes' && (!wlAmount || parseFloat(wlAmount) <= 0)) {
            errors.push('W/L Amount is required when "To Win" is Yes');
        }
        
        if (errors.length > 0) {
            this.showModernNotification(errors[0], 'warning');
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
        
        console.log('[PROJECTS] Saving sales tracking data:', data);
        console.log('[PROJECTS] API URL:', BASE + `/api/v1/projects/${projectId}/sales-tracking`);
        
        try {
            // Show loading state
            const saveBtn = document.querySelector('.btn-save') || document.querySelector('button[onclick="saveSalesTracking()"]');
            let originalText = '💾 Save Sales Tracking';
            
            if (saveBtn) {
                originalText = saveBtn.textContent;
                saveBtn.textContent = 'Saving...';
                saveBtn.disabled = true;
            }
            
            const response = await fetch(BASE + `/api/v1/projects/${projectId}/sales-tracking`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            
            if (response.ok) {
                // Show Actual Project modal
                this.showActualProjectModal(projectId);
                
                // Restore button state
                if (saveBtn) {
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                }
            } else {
                const errorData = await response.json();
                throw new Error(errorData.detail || 'Failed to save sales tracking');
            }
            
        } catch (error) {
            console.error('[PROJECTS] Save sales tracking error:', error);
            this.showModernNotification('Failed to save sales tracking. Please try again.', 'error');
            
            // Restore button state on error
            const saveBtn = document.querySelector('.btn-save') || document.querySelector('button[onclick="saveSalesTracking()"]');
            if (saveBtn) {
                saveBtn.textContent = '💾 Save Sales Tracking';
                saveBtn.disabled = false;
            }
        }
    },

    editProject(projectId) {
        ModalSystem.info('Edit functionality will be available soon.');
    },
    
    showActualProjectModal(projectId) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay active';
        // Use CSS class styling instead of inline styles to respect sidebar positioning
        
        const modalBox = document.createElement('div');
        modalBox.style.cssText = 'background: #FFFFFF; padding: 2rem; border-radius: 1rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid rgba(0,0,0,0.08); animation: slideUp 0.3s;';
        
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
                <small style="display: block; margin-top: 0.75rem; color: rgba(0,0,0,0.3); font-size: 0.75rem; text-align: center;">
                    Select "No" if this is spam, duplicate, or invalid.
                </small>
            </div>
            
            <div style="display: flex; gap: 0.75rem; justify-content: center;">
                <button id="actualProjectSaveBtn" disabled style="padding: 0.75rem 2rem; background: #ff8c00; color: #000000; border: none; border-radius: 0.5rem; cursor: not-allowed; font-weight: 600; opacity: 0.5; transition: all 0.2s;">
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
                console.log('[ACTUAL PROJECT] API URL:', BASE + `/api/v1/projects/${projectId}/actual-project`);
                
                const response = await fetch(BASE + `/api/v1/projects/${projectId}/actual-project`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ is_actual_project: selectedValue })
                });
                
                console.log('[ACTUAL PROJECT] Response status:', response.status);
                
                if (response.ok) {
                    const result = await response.json();
                    console.log('[ACTUAL PROJECT] Success:', result);
                    
                    this.showModernNotification('Project status saved successfully!', 'success');
                    
                    // Wait a bit before closing
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    // Remove overlay
                    overlay.remove();
                    
                    // Close details modal and reload after notification shows
                    setTimeout(() => {
                        const detailsModal = document.getElementById('detailsModal');
                        if (detailsModal) {
                            detailsModal.classList.remove('active');
                        }
                        
                        // Reload projects
                        this.loadProjects();
                    }, 1500);
                } else {
                    const errorData = await response.json();
                    console.error('[ACTUAL PROJECT] Error response:', errorData);
                    throw new Error(errorData.detail || 'Failed to save');
                }
            } catch (error) {
                console.error('[ACTUAL PROJECT] Error saving:', error);
                this.showModernNotification('Failed to save. Please try again.', 'error');
                saveBtn.textContent = 'Save';
                saveBtn.disabled = false;
                saveBtn.style.opacity = '1';
            }
        });
    },

    async deleteProject(projectId) {
        const project = this.allProjects.find(p => p.id === projectId);
        if (!project) return;

        const confirmed = await ModalSystem.confirm({
            title: 'Delete Project',
            message: `Are you sure you want to delete this project?\n\nContractor: ${project.contractor_name}\nProject: ${project.project_name}\n\nThis action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            type: 'danger'
        });
        if (!confirmed) return;

        try {
            const response = await fetch(BASE + `/api/v1/projects/${projectId}`, {
                method: 'DELETE',
                credentials: 'include'
            });

            if (response.ok) {
                ModalSystem.success('Project deleted successfully!');
                this.loadProjects();
            } else {
                throw new Error('Failed to delete project');
            }
        } catch (error) {
            console.error('[PROJECTS] Delete error:', error);
            ModalSystem.error('Failed to delete project. Please try again.');
        }
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    ProjectsPage.init();
    
    // Modal close handlers
    const modal = document.getElementById('detailsModal');
    
    // Close modal on overlay click
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            modal.classList.remove('active');
        }
    });
    
    // Archive button click handler
    const archiveBtn = document.getElementById('archiveBtn');
    if (archiveBtn) {
        archiveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleProjectArchive();
        });
    }
    
    // Edit Project button click handler
    const editProjectBtn = document.getElementById('editProjectBtn');
    if (editProjectBtn) {
        editProjectBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            editProject();
        });
    }
});

// Global functions for modal controls
function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function openAssignModal() {
    ModalSystem.info('Assignment functionality will be implemented soon.');
}

function openTrackingModal() {
    ModalSystem.info('Sales tracking functionality will be implemented soon.');
}

/**
 * Edit project — opens section picker modal
 */
function editProject() {
    const modal = document.getElementById('detailsModal');
    const projectId = modal?.dataset?.projectId;
    if (!projectId) return;

    let project = null;
    if (ProjectsPage && ProjectsPage.allProjects) {
        project = ProjectsPage.allProjects.find(p => p.id == projectId);
    } else if (window.currentProjectsData && window.currentProjectsData.projects) {
        project = window.currentProjectsData.projects.find(p => p.id == projectId);
    }
    if (!project) { ModalSystem.error('Project not found'); return; }
    if (project.archived_at) { ModalSystem.warning('Cannot edit archived projects.'); return; }

    const isPriority = String(project.status || '').trim().toLowerCase() === 'priority';

    const editModal = document.getElementById('editOptionsModal');
    if (editModal) {
        editModal.dataset.projectId = projectId;
        editModal.dataset.isPriority = isPriority;
        const picturesOption = document.getElementById('editPicturesOption');
        if (picturesOption) picturesOption.style.display = isPriority ? 'flex' : 'none';
    }

    closeDetailsModal();
    setTimeout(() => { if (editModal) editModal.classList.add('active'); }, 150);
}

function saveSalesTracking() {
    const modal = document.getElementById('detailsModal');
    const projectId = parseInt(modal.dataset.projectId);
    if (projectId) {
        ProjectsPage.saveSalesTracking(projectId);
    }
}

// ============================================================================
// ARCHIVE FUNCTIONALITY FOR PROJECTS.JS
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
    if (ProjectsPage && ProjectsPage.allProjects) {
        project = ProjectsPage.allProjects.find(p => p.id == projectId);
    } else if (window.currentProjectsData && window.currentProjectsData.projects) {
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
        const response = await fetch(`${BASE}/api/v1/projects/archive`, {
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
            
            // Reload the entire page
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
        color: #000000;
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
            background: #FFFFFF;
            border: 1px solid rgba(0,0,0,0.08);
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
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #000000; margin-bottom: 0.5rem;">
                    ${title}
                </h3>
                <p style="color: #1F2937; line-height: 1.5;">
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
                    color: #000000;
                    font-size: 0.9rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                ">Cancel</button>
                
                <button class="confirm-action-btn" style="
                    flex: 1;
                    padding: 0.75rem 1.5rem;
                    background: ${iconColor};
                    border: 1px solid ${iconColor};
                    border-radius: 0.75rem;
                    color: #000000;
                    font-size: 0.9rem;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.2s ease;
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

} // End of ProjectsPage wrapper
