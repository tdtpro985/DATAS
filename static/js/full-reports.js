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

    charts: {
        projectsByRegion: null,
        projectsByStatus: null,
        projectsBySource: null,
        salesFunnel: null,
        materialBreakdown: null,
        encodingPerformance: null,
        monthlyTrends: null
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

        // Apply date range filter (based on publication_date)
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
                if (!p.publication_date) return false;
                const projectDate = new Date(p.publication_date);
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

        // Group by source
        const bySource = {};
        projects.forEach(p => {
            const source = p.source || 'Unknown';
            bySource[source] = (bySource[source] || 0) + 1;
        });

        // Group by month for trends (based on publication_date)
        const byMonth = {};
        projects.forEach(p => {
            if (!p.publication_date) return;
            const date = new Date(p.publication_date);
            const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            byMonth[monthKey] = (byMonth[monthKey] || 0) + 1;
        });

        const html = `
            <div class="chart-container">
                <div class="chart-title">Projects by Region</div>
                <canvas id="chartProjectsByRegion"></canvas>
            </div>

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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="chart-container">
                    <div class="chart-title">Projects by Status</div>
                    <canvas id="chartProjectsByStatus"></canvas>
                </div>
                <div class="chart-container">
                    <div class="chart-title">Projects by Source</div>
                    <canvas id="chartProjectsBySource"></canvas>
                </div>
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

            <div class="chart-container">
                <div class="chart-title">Monthly Trends</div>
                <canvas id="chartMonthlyTrends"></canvas>
            </div>
        `;

        document.getElementById('projectAnalytics').innerHTML = html;

        // Render charts after DOM update
        setTimeout(() => {
            this.renderProjectsByRegionChart(byRegion);
            this.renderProjectsByStatusChart(byStatus);
            this.renderProjectsBySourceChart(bySource);
            this.renderMonthlyTrendsChart(byMonth);
        }, 100);
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

            <div class="chart-container">
                <div class="chart-title">Sales Tracking Funnel</div>
                <canvas id="chartSalesFunnel"></canvas>
            </div>
        `;

        document.getElementById('salesPerformance').innerHTML = html;

        // Render chart after DOM update
        setTimeout(() => {
            this.renderSalesFunnelChart(trackingCounts);
        }, 100);
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

        const materialData = {
            'Sheet Pile': totalSheetPile,
            'MS Plate': totalMSPlate,
            'Angle Bars': totalAngleBars,
            'Channel Bars': totalChannelBars,
            'Wide Flange': totalWideFlange,
            'GI/BI': totalGIBI
        };

        const html = `
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Sheet Pile</div>
                    <div class="stat-value">${totalSheetPile.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="stat-sublabel">Total amount</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">DRBS Value</div>
                    <div class="stat-value">₱${totalDRBS.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="stat-sublabel">Total value</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">MS Plate</div>
                    <div class="stat-value">${totalMSPlate.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="stat-sublabel">Total tons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Angle Bars</div>
                    <div class="stat-value">${totalAngleBars.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="stat-sublabel">Total tons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Channel Bars</div>
                    <div class="stat-value">${totalChannelBars.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="stat-sublabel">Total tons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Wide Flange</div>
                    <div class="stat-value">${totalWideFlange.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="stat-sublabel">Total tons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">GI/BI</div>
                    <div class="stat-value">${totalGIBI.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="stat-sublabel">Total sheets</div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">Material Requirements Breakdown</div>
                <canvas id="chartMaterialBreakdown"></canvas>
            </div>
        `;

        document.getElementById('materialRequirements').innerHTML = html;

        // Render chart after DOM update
        setTimeout(() => {
            this.renderMaterialBreakdownChart(materialData);
        }, 100);
    },

    renderEncodingPerformance() {
        const projects = this.getFilteredProjects();
        const encoders = this.data.users.filter(u => u.role === 'encoder' || u.role === 'admin' || u.role === 'superadmin');

        // Group by encoder with full name
        const byEncoder = {};
        projects.forEach(p => {
            const encoderId = p.encoded_by;
            if (!encoderId) return;
            
            const encoder = encoders.find(e => e.id === encoderId);
            const encoderName = encoder ? (encoder.full_name || encoder.email || `User #${encoderId}`) : `Unknown User #${encoderId}`;
            
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
    },

    // Chart Rendering Methods
    renderProjectsByRegionChart(byRegion) {
        const ctx = document.getElementById('chartProjectsByRegion');
        if (!ctx) return;

        // Destroy existing chart
        if (this.charts.projectsByRegion) {
            this.charts.projectsByRegion.destroy();
        }

        const sortedData = Object.entries(byRegion).sort((a, b) => b[1].count - a[1].count).slice(0, 10);
        const labels = sortedData.map(([region]) => region);
        const counts = sortedData.map(([, data]) => data.count);

        this.charts.projectsByRegion = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Project Count',
                    data: counts,
                    backgroundColor: 'rgba(249, 115, 22, 0.7)',
                    borderColor: 'rgba(249, 115, 22, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#f97316',
                        bodyColor: '#f1f5f9',
                        borderColor: '#f97316',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: '#94a3b8', maxRotation: 45, minRotation: 45 },
                        grid: { display: false }
                    }
                }
            }
        });
    },

    renderProjectsByStatusChart(byStatus) {
        const ctx = document.getElementById('chartProjectsByStatus');
        if (!ctx) return;

        if (this.charts.projectsByStatus) {
            this.charts.projectsByStatus.destroy();
        }

        const labels = Object.keys(byStatus);
        const counts = Object.values(byStatus);
        const colors = [
            'rgba(239, 68, 68, 0.8)',   // red
            'rgba(249, 115, 22, 0.8)',  // orange
            'rgba(34, 197, 94, 0.8)',   // green
            'rgba(59, 130, 246, 0.8)',  // blue
            'rgba(168, 85, 247, 0.8)',  // purple
            'rgba(236, 72, 153, 0.8)'   // pink
        ];

        this.charts.projectsByStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors,
                    borderColor: '#1e293b',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#94a3b8', font: { size: 11 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#f97316',
                        bodyColor: '#f1f5f9',
                        borderColor: '#f97316',
                        borderWidth: 1
                    }
                }
            }
        });
    },

    renderProjectsBySourceChart(bySource) {
        const ctx = document.getElementById('chartProjectsBySource');
        if (!ctx) return;

        if (this.charts.projectsBySource) {
            this.charts.projectsBySource.destroy();
        }

        const sortedData = Object.entries(bySource).sort((a, b) => b[1] - a[1]).slice(0, 8);
        const labels = sortedData.map(([source]) => source);
        const counts = sortedData.map(([, count]) => count);

        this.charts.projectsBySource = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Project Count',
                    data: counts,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#f97316',
                        bodyColor: '#f1f5f9',
                        borderColor: '#f97316',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    y: {
                        ticks: { color: '#94a3b8' },
                        grid: { display: false }
                    }
                }
            }
        });
    },

    renderSalesFunnelChart(trackingCounts) {
        const ctx = document.getElementById('chartSalesFunnel');
        if (!ctx) return;

        if (this.charts.salesFunnel) {
            this.charts.salesFunnel.destroy();
        }

        const labels = Object.keys(trackingCounts);
        const counts = Object.values(trackingCounts);

        this.charts.salesFunnel = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Project Count',
                    data: counts,
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(249, 115, 22, 0.7)',
                        'rgba(34, 197, 94, 0.7)'
                    ],
                    borderColor: [
                        'rgba(239, 68, 68, 1)',
                        'rgba(249, 115, 22, 1)',
                        'rgba(34, 197, 94, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2.5,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#f97316',
                        bodyColor: '#f1f5f9',
                        borderColor: '#f97316',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    y: {
                        ticks: { color: '#94a3b8', font: { size: 13 } },
                        grid: { display: false }
                    }
                }
            }
        });
    },

    renderMaterialBreakdownChart(materialData) {
        const ctx = document.getElementById('chartMaterialBreakdown');
        if (!ctx) return;

        if (this.charts.materialBreakdown) {
            this.charts.materialBreakdown.destroy();
        }

        const labels = Object.keys(materialData);
        const values = Object.values(materialData);

        this.charts.materialBreakdown = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Amount',
                    data: values,
                    backgroundColor: 'rgba(168, 85, 247, 0.7)',
                    borderColor: 'rgba(168, 85, 247, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#f97316',
                        bodyColor: '#f1f5f9',
                        borderColor: '#f97316',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { display: false }
                    }
                }
            }
        });
    },

    renderMonthlyTrendsChart(byMonth) {
        const ctx = document.getElementById('chartMonthlyTrends');
        if (!ctx) return;

        if (this.charts.monthlyTrends) {
            this.charts.monthlyTrends.destroy();
        }

        const sortedMonths = Object.keys(byMonth).sort();
        const counts = sortedMonths.map(month => byMonth[month]);

        this.charts.monthlyTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: sortedMonths,
                datasets: [{
                    label: 'Projects Published',
                    data: counts,
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    borderColor: 'rgba(249, 115, 22, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(249, 115, 22, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2.5,
                plugins: {
                    legend: {
                        display: true,
                        labels: { color: '#94a3b8' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#f97316',
                        bodyColor: '#f1f5f9',
                        borderColor: '#f97316',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { display: false }
                    }
                }
            }
        });
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