/* ============================================================
   full-reports.js — Comprehensive Statistical Reports
   ============================================================ */

const FullReports = {
    filters: {
        dateRange: 'month',
        region: '',
        status: '',
        source: ''
    },
    
    data: {
        projects: [],
        contractors: [],
        salesTracking: [],
        users: []
    },

    async init() {
        // Validate session
        const user = await Auth.checkAuth();
        if (!user) return;

        // Setup filter listeners
        this.setupFilters();

        // Load all data
        await this.loadAllData();

        // Render all sections
        this.renderAllSections();

        console.log('[FULL REPORTS] Initialized');
    },

    setupFilters() {
        document.getElementById('dateRange').addEventListener('change', (e) => {
            this.filters.dateRange = e.target.value;
            this.renderAllSections();
        });

        document.getElementById('regionFilter').addEventListener('change', (e) => {
            this.filters.region = e.target.value;
            this.renderAllSections();
        });

        document.getElementById('statusFilter').addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.renderAllSections();
        });

        document.getElementById('sourceFilter').addEventListener('change', (e) => {
            this.filters.source = e.target.value;
            this.renderAllSections();
        });
    },

    async loadAllData() {
        try {
            // Load projects (all non-archived)
            const projectsRes = await fetch(`${BASE}/api/v1/projects?size=10000`, {
                credentials: 'include'
            });
            const projectsData = await projectsRes.json();
            this.data.projects = projectsData.projects || [];

            // Load contractors ranking
            const contractorsRes = await fetch(`${BASE}/api/v1/contractors/ranking`, {
                credentials: 'include'
            });
            const contractorsData = await contractorsRes.json();
            this.data.contractors = contractorsData.data?.contractors || [];

            // Load users
            const usersRes = await fetch(`${BASE}/api/v1/users`, {
                credentials: 'include'
            });
            const usersData = await usersRes.json();
            this.data.users = usersData.users || [];

            // Populate filter dropdowns
            this.populateFilters();

        } catch (error) {
            console.error('[FULL REPORTS] Load error:', error);
            Toast.error('Failed to load report data');
        }
    },

    populateFilters() {
        // Populate regions
        const regions = [...new Set(this.data.projects.map(p => p.project_region || p.region).filter(Boolean))];
        regions.sort();
        const regionSelect = document.getElementById('regionFilter');
        regions.forEach(region => {
            const option = document.createElement('option');
            option.value = region;
            option.textContent = region;
            regionSelect.appendChild(option);
        });

        // Populate sources
        const sources = [...new Set(this.data.projects.map(p => p.source).filter(Boolean))];
        sources.sort();
        const sourceSelect = document.getElementById('sourceFilter');
        sources.forEach(source => {
            const option = document.createElement('option');
            option.value = source;
            option.textContent = source;
            sourceSelect.appendChild(option);
        });
    },

    getFilteredProjects() {
        let filtered = this.data.projects;

        // Apply region filter
        if (this.filters.region) {
            filtered = filtered.filter(p => (p.project_region || p.region) === this.filters.region);
        }

        // Apply status filter
        if (this.filters.status) {
            filtered = filtered.filter(p => p.status === this.filters.status);
        }

        // Apply source filter
        if (this.filters.source) {
            filtered = filtered.filter(p => p.source === this.filters.source);
        }

        // Apply date range filter
        if (this.filters.dateRange !== 'all') {
            const now = new Date();
            const filterDate = new Date();

            switch (this.filters.dateRange) {
                case 'today':
                    filterDate.setHours(0, 0, 0, 0);
                    break;
                case 'week':
                    filterDate.setDate(now.getDate() - 7);
                    break;
                case 'month':
                    filterDate.setMonth(now.getMonth() - 1);
                    break;
                case 'quarter':
                    filterDate.setMonth(now.getMonth() - 3);
                    break;
                case 'year':
                    filterDate.setFullYear(now.getFullYear() - 1);
                    break;
            }

            filtered = filtered.filter(p => {
                const projectDate = new Date(p.created_at || p.publication_date);
                return projectDate >= filterDate;
            });
        }

        return filtered;
    },

    renderAllSections() {
        this.renderExecutiveSummary();
        this.renderProjectAnalytics();
        this.renderContractorAnalytics();
        this.renderSalesPerformance();
        this.renderGeographicAnalysis();
        this.renderMaterialRequirements();
        this.renderEncodingPerformance();
    },

    renderExecutiveSummary() {
        const projects = this.getFilteredProjects();
        const totalValue = projects.reduce((sum, p) => sum + (parseFloat(p.project_value) || 0), 0);
        const avgValue = projects.length > 0 ? totalValue / projects.length : 0;
        const uniqueContractors = new Set(projects.map(p => p.contractor_name).filter(Boolean));

        const statusCounts = {};
        projects.forEach(p => {
            const status = p.status || 'Unknown';
            statusCounts[status] = (statusCounts[status] || 0) + 1;
        });

        const sourceCounts = {};
        projects.forEach(p => {
            const source = p.source || 'Unknown';
            sourceCounts[source] = (sourceCounts[source] || 0) + 1;
        });

        const html = `
            <div class="stat-card">
                <div class="stat-label">Total Projects</div>
                <div class="stat-value">${projects.length.toLocaleString()}</div>
                <div class="stat-sublabel">Active projects in system</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Contractors</div>
                <div class="stat-value">${uniqueContractors.size.toLocaleString()}</div>
                <div class="stat-sublabel">Unique contractors</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pipeline Value</div>
                <div class="stat-value">₱${this.formatNumber(totalValue)}</div>
                <div class="stat-sublabel">Total project value</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Average Project Value</div>
                <div class="stat-value">₱${this.formatNumber(avgValue)}</div>
                <div class="stat-sublabel">Per project average</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Priority Projects</div>
                <div class="stat-value">${(statusCounts['Priority'] || 0).toLocaleString()}</div>
                <div class="stat-sublabel">${((statusCounts['Priority'] || 0) / projects.length * 100).toFixed(1)}% of total</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Awarded Projects</div>
                <div class="stat-value">${(statusCounts['Awarded'] || 0).toLocaleString()}</div>
                <div class="stat-sublabel">${((statusCounts['Awarded'] || 0) / projects.length * 100).toFixed(1)}% of total</div>
            </div>
        `;

        document.getElementById('executiveSummary').innerHTML = html;
    },

    renderProjectAnalytics() {
        const projects = this.getFilteredProjects();

        // Group by region
        const byRegion = {};
        projects.forEach(p => {
            const region = p.project_region || p.region || 'Unknown';
            if (!byRegion[region]) {
                byRegion[region] = { count: 0, value: 0 };
            }
            byRegion[region].count++;
            byRegion[region].value += parseFloat(p.project_value) || 0;
        });

        const regionRows = Object.entries(byRegion)
            .sort((a, b) => b[1].value - a[1].value)
            .map(([region, data]) => `
                <tr>
                    <td>${this.escapeHtml(region)}</td>
                    <td>${data.count.toLocaleString()}</td>
                    <td>₱${this.formatNumber(data.value)}</td>
                    <td>₱${this.formatNumber(data.value / data.count)}</td>
                </tr>
            `).join('');

        // Group by status
        const byStatus = {};
        projects.forEach(p => {
            const status = p.status || 'Unknown';
            byStatus[status] = (byStatus[status] || 0) + 1;
        });

        const statusRows = Object.entries(byStatus)
            .sort((a, b) => b[1] - a[1])
            .map(([status, count]) => `
                <tr>
                    <td>${this.escapeHtml(status)}</td>
                    <td>${count.toLocaleString()}</td>
                    <td>${(count / projects.length * 100).toFixed(2)}%</td>
                </tr>
            `).join('');

        const html = `
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Region</th>
                            <th>Project Count</th>
                            <th>Total Value</th>
                            <th>Average Value</th>
                        </tr>
                    </thead>
                    <tbody>${regionRows || '<tr><td colspan="4" style="text-align:center;color:var(--text-secondary);">No data available</td></tr>'}</tbody>
                </table>
            </div>

            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>${statusRows || '<tr><td colspan="3" style="text-align:center;color:var(--text-secondary);">No data available</td></tr>'}</tbody>
                </table>
            </div>
        `;

        document.getElementById('projectAnalytics').innerHTML = html;
    },

    renderContractorAnalytics() {
        const projects = this.getFilteredProjects();

        // Group by contractor
        const byContractor = {};
        projects.forEach(p => {
            const name = p.contractor_name || 'Unknown';
            if (!byContractor[name]) {
                byContractor[name] = { count: 0, value: 0 };
            }
            byContractor[name].count++;
            byContractor[name].value += parseFloat(p.project_value) || 0;
        });

        const contractorRows = Object.entries(byContractor)
            .sort((a, b) => b[1].value - a[1].value)
            .slice(0, 50) // Top 50
            .map(([name, data], index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${this.escapeHtml(name)}</td>
                    <td>${data.count.toLocaleString()}</td>
                    <td>₱${this.formatNumber(data.value)}</td>
                    <td>₱${this.formatNumber(data.value / data.count)}</td>
                </tr>
            `).join('');

        const html = `
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Contractor Name</th>
                            <th>Project Count</th>
                            <th>Total Value</th>
                            <th>Average Value</th>
                        </tr>
                    </thead>
                    <tbody>${contractorRows || '<tr><td colspan="5" style="text-align:center;color:var(--text-secondary);">No data available</td></tr>'}</tbody>
                </table>
            </div>
        `;

        document.getElementById('contractorAnalytics').innerHTML = html;
    },

    renderSalesPerformance() {
        const projects = this.getFilteredProjects();

        // Count by sales tracking status
        const trackingCounts = {
            'Not Started': 0,
            'In Progress': 0,
            'Complete': 0
        };

        projects.forEach(p => {
            const status = p.sales_tracking_status || 'Not Started';
            trackingCounts[status] = (trackingCounts[status] || 0) + 1;
        });

        const total = projects.length;
        const html = `
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Not Started</div>
                    <div class="stat-value">${trackingCounts['Not Started'].toLocaleString()}</div>
                    <div class="stat-sublabel">${(trackingCounts['Not Started'] / total * 100).toFixed(1)}% of projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">In Progress</div>
                    <div class="stat-value">${trackingCounts['In Progress'].toLocaleString()}</div>
                    <div class="stat-sublabel">${(trackingCounts['In Progress'] / total * 100).toFixed(1)}% of projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Complete</div>
                    <div class="stat-value">${trackingCounts['Complete'].toLocaleString()}</div>
                    <div class="stat-sublabel">${(trackingCounts['Complete'] / total * 100).toFixed(1)}% of projects</div>
                </div>
            </div>
        `;

        document.getElementById('salesPerformance').innerHTML = html;
    },

    renderGeographicAnalysis() {
        const projects = this.getFilteredProjects();

        // Group by province
        const byProvince = {};
        projects.forEach(p => {
            const province = p.project_province || 'Unknown';
            if (!byProvince[province]) {
                byProvince[province] = { count: 0, value: 0 };
            }
            byProvince[province].count++;
            byProvince[province].value += parseFloat(p.project_value) || 0;
        });

        const provinceRows = Object.entries(byProvince)
            .sort((a, b) => b[1].count - a[1].count)
            .slice(0, 20) // Top 20
            .map(([province, data]) => `
                <tr>
                    <td>${this.escapeHtml(province)}</td>
                    <td>${data.count.toLocaleString()}</td>
                    <td>₱${this.formatNumber(data.value)}</td>
                </tr>
            `).join('');

        const html = `
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Province</th>
                            <th>Project Count</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>
                    <tbody>${provinceRows || '<tr><td colspan="3" style="text-align:center;color:var(--text-secondary);">No data available</td></tr>'}</tbody>
                </table>
            </div>
        `;

        document.getElementById('geographicAnalysis').innerHTML = html;
    },

    renderMaterialRequirements() {
        const projects = this.getFilteredProjects();

        let totalSheetPile = 0;
        let totalDRBS = 0;
        let totalMSPlate = 0;
        let totalAngleBars = 0;
        let totalChannelBars = 0;
        let totalWideFlange = 0;
        let totalGIBI = 0;

        projects.forEach(p => {
            totalSheetPile += parseFloat(p.sheet_pile_amount) || 0;
            totalDRBS += parseFloat(p.drbs_value) || 0;
            totalMSPlate += parseFloat(p.ms_plate) || 0;
            totalAngleBars += parseFloat(p.angle_bars) || 0;
            totalChannelBars += parseFloat(p.channel_bars) || 0;
            totalWideFlange += parseFloat(p.wide_flange) || 0;
            totalGIBI += parseFloat(p.gi_bi) || 0;
        });

        const html = `
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Sheet Pile</div>
                    <div class="stat-value">${this.formatNumber(totalSheetPile)}</div>
                    <div class="stat-sublabel">Total amount</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">DRBS Value</div>
                    <div class="stat-value">₱${this.formatNumber(totalDRBS)}</div>
                    <div class="stat-sublabel">Total value</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">MS Plate</div>
                    <div class="stat-value">${this.formatNumber(totalMSPlate)}</div>
                    <div class="stat-sublabel">Total tons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Angle Bars</div>
                    <div class="stat-value">${this.formatNumber(totalAngleBars)}</div>
                    <div class="stat-sublabel">Total tons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Channel Bars</div>
                    <div class="stat-value">${this.formatNumber(totalChannelBars)}</div>
                    <div class="stat-sublabel">Total tons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Wide Flange</div>
                    <div class="stat-value">${this.formatNumber(totalWideFlange)}</div>
                    <div class="stat-sublabel">Total tons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">GI/BI</div>
                    <div class="stat-value">${this.formatNumber(totalGIBI)}</div>
                    <div class="stat-sublabel">Total sheets</div>
                </div>
            </div>
        `;

        document.getElementById('materialRequirements').innerHTML = html;
    },

    renderEncodingPerformance() {
        const projects = this.getFilteredProjects();
        const encoders = this.data.users.filter(u => u.role === 'encoder' || u.role === 'admin' || u.role === 'superadmin');

        // Group by encoder
        const byEncoder = {};
        projects.forEach(p => {
            const encoderId = p.encoded_by;
            if (!encoderId) return;
            
            const encoder = encoders.find(e => e.id === encoderId);
            const encoderName = encoder ? encoder.full_name : `User #${encoderId}`;
            
            if (!byEncoder[encoderName]) {
                byEncoder[encoderName] = 0;
            }
            byEncoder[encoderName]++;
        });

        const encoderRows = Object.entries(byEncoder)
            .sort((a, b) => b[1] - a[1])
            .map(([name, count], index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${this.escapeHtml(name)}</td>
                    <td>${count.toLocaleString()}</td>
                    <td>${(count / projects.length * 100).toFixed(2)}%</td>
                </tr>
            `).join('');

        const html = `
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Encoder Name</th>
                            <th>Projects Encoded</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>${encoderRows || '<tr><td colspan="4" style="text-align:center;color:var(--text-secondary);">No data available</td></tr>'}</tbody>
                </table>
            </div>
        `;

        document.getElementById('encodingPerformance').innerHTML = html;
    },

    formatNumber(num) {
        if (num >= 1000000000) {
            return (num / 1000000000).toFixed(2) + 'B';
        } else if (num >= 1000000) {
            return (num / 1000000).toFixed(2) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(2) + 'K';
        } else {
            return num.toFixed(2);
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
    }
};

// Export functionality
function exportReport() {
    Toast.info('Export functionality coming soon');
    // TODO: Implement CSV/PDF export
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    FullReports.init();
});
understood