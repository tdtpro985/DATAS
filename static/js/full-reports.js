/* ============================================================
   full-reports.js — Comprehensive Statistical Reports (v3)
   ============================================================ */

const FullReports = {
    filters: {
        period: 'monthly', // 'daily', 'weekly', 'semi-monthly', 'monthly', 'quarterly', 'yearly'
        dateMode: 'published', // 'published' or 'encoded'
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

    // Sort state for each table section
    sortState: {
        regionTable:      { col: 'value', dir: 'desc' },
        statusTable:      { col: 'count', dir: 'desc' },
        contractorTable:  { col: 'value', dir: 'desc' },
        geographicTable:  { col: 'count', dir: 'desc' },
        encoderTable:     { col: 'total', dir: 'desc' },
    },

    // ── Reusable sort helper ──
    sortData(arr, key, dir) {
        return [...arr].sort((a, b) => {
            let av = a[key], bv = b[key];
            if (av === null || av === undefined) av = -Infinity;
            if (bv === null || bv === undefined) bv = -Infinity;
            if (typeof av === 'string') av = av.toLowerCase();
            if (typeof bv === 'string') bv = bv.toLowerCase();
            const mult = dir === 'desc' ? -1 : 1;
            if (av < bv) return -1 * mult;
            if (av > bv) return  1 * mult;
            return 0;
        });
    },

    // ── Sort icon helper ──
    sortIcon(section, col) {
        const st = this.sortState[section];
        if (!st || st.col !== col) return '';
        return st.dir === 'asc' ? ' ▲' : ' ▼';
    },

    // ── Toggle sort and re-render section ──
    toggleSort(section, col, renderFunc) {
        const st = this.sortState[section];
        if (!st) return;
        if (st.col === col) {
            st.dir = st.dir === 'desc' ? 'asc' : 'desc';
        } else {
            st.col = col;
            st.dir = 'desc';
        }
        renderFunc.call(this);
    },

    async init() {
        const user = await Auth.checkAuth();
        if (!user) return;

        this.setupFilters();
        await this.loadAllData();
        this.renderAllSections();

        console.log('[FULL REPORTS] Initialized');
    },

    // ── Get the current date range based on the selected period ──
    getPeriodDateRange(period) {
        const now = new Date();
        // Use Philippine timezone
        const pht = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
        const y = pht.getFullYear();
        const m = pht.getMonth(); // 0-indexed
        const d = pht.getDate();
        
        const pad = (n) => String(n).padStart(2, '0');
        const formatDate = (date) => {
            return date.getFullYear() + '-' + pad(date.getMonth()+1) + '-' + pad(date.getDate());
        };
        
        const ranges = {
            'daily': () => {
                const from = new Date(y, m, d);
                const to = new Date(y, m, d);
                return { from: formatDate(from), to: formatDate(to), label: formatDate(from) };
            },
            'weekly': () => {
                // Get current week (Mon-Sun)
                const dayOfWeek = pht.getDay(); // 0=Sun
                const monOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
                const from = new Date(y, m, d + monOffset);
                const to = new Date(y, m, d + monOffset + 6);
                return {
                    from: formatDate(from),
                    to: formatDate(to),
                    label: `${formatDate(from)} to ${formatDate(to)}`
                };
            },
            'semi-monthly': () => {
                if (d <= 15) {
                    return {
                        from: formatDate(new Date(y, m, 1)),
                        to: formatDate(new Date(y, m, 15)),
                        label: `${formatDate(new Date(y, m, 1))} to ${formatDate(new Date(y, m, 15))}`
                    };
                } else {
                    const lastDay = new Date(y, m + 1, 0).getDate();
                    return {
                        from: formatDate(new Date(y, m, 16)),
                        to: formatDate(new Date(y, m, lastDay)),
                        label: `${formatDate(new Date(y, m, 16))} to ${formatDate(new Date(y, m, lastDay))}`
                    };
                }
            },
            'monthly': () => {
                const lastDay = new Date(y, m + 1, 0).getDate();
                return {
                    from: formatDate(new Date(y, m, 1)),
                    to: formatDate(new Date(y, m, lastDay)),
                    label: `${y}-${pad(m+1)}`
                };
            },
            'quarterly': () => {
                const q = Math.floor(m / 3) * 3; // 0, 3, 6, 9
                const qEnd = new Date(y, q + 3, 0).getDate();
                return {
                    from: formatDate(new Date(y, q, 1)),
                    to: formatDate(new Date(y, q + 2, qEnd)),
                    label: `Q${(q/3)+1} ${y}`
                };
            },
            'yearly': () => {
                return {
                    from: formatDate(new Date(y, 0, 1)),
                    to: formatDate(new Date(y, 11, 31)),
                    label: `${y}`
                };
            }
        };
        
        return (ranges[period] || ranges['monthly'])();
    },

    setupFilters() {
        // Period selector
        document.getElementById('periodSelect').addEventListener('change', (e) => {
            this.filters.period = e.target.value;
            this.updatePeriodLabel();
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

        // Date mode toggle (Published / Encoded)
        const pubBtn = document.getElementById('dateTogglePublished');
        const encBtn = document.getElementById('dateToggleEncoded');
        if (pubBtn && encBtn) {
            const activate = (mode) => {
                if (mode === 'published') {
                    pubBtn.style.background = 'var(--primary)';
                    pubBtn.style.color = '#fff';
                    pubBtn.style.fontWeight = '700';
                    encBtn.style.background = 'transparent';
                    encBtn.style.color = 'var(--text-secondary)';
                    encBtn.style.fontWeight = '600';
                } else {
                    encBtn.style.background = 'var(--primary)';
                    encBtn.style.color = '#fff';
                    encBtn.style.fontWeight = '700';
                    pubBtn.style.background = 'transparent';
                    pubBtn.style.color = 'var(--text-secondary)';
                    pubBtn.style.fontWeight = '600';
                }
                this.filters.dateMode = mode;
                this.renderAllSections();
            };
            pubBtn.addEventListener('click', () => activate('published'));
            encBtn.addEventListener('click', () => activate('encoded'));
        }

        // Export modal toggle (Published / Encoded) - syncs with main filter date mode
        const expPub = document.getElementById('exportTogglePub');
        const expEnc = document.getElementById('exportToggleEnc');
        if (expPub && expEnc) {
            const syncExportToggle = () => {
                const mode = this.filters.dateMode;
                if (mode === 'published') {
                    expPub.style.background = 'var(--primary)';
                    expPub.style.color = '#fff';
                    expEnc.style.background = 'transparent';
                    expEnc.style.color = 'var(--text-secondary)';
                } else {
                    expEnc.style.background = 'var(--primary)';
                    expEnc.style.color = '#fff';
                    expPub.style.background = 'transparent';
                    expPub.style.color = 'var(--text-secondary)';
                }
            };
            
            const expActivate = (mode) => {
                this.filters.dateMode = mode;
                syncExportToggle();
            };
            
            expPub.addEventListener('click', () => expActivate('published'));
            expEnc.addEventListener('click', () => expActivate('encoded'));
            this._syncExportToggle = syncExportToggle;
        }

        // Export button
        const exportBtn = document.getElementById('btnExportReport');
        if (exportBtn) {
            exportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openExportModal();
            });
        }

        // Export period selector change
        const expPeriod = document.getElementById('exportPeriodSelect');
        if (expPeriod) {
            expPeriod.addEventListener('change', () => {
                if (typeof FullReports !== 'undefined') {
                    FullReports.updateExportPeriodRange();
                }
            });
        }

        // Escape key closes export modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('exportModal');
                if (modal && modal.style.visibility === 'visible') {
                    closeExportModal();
                }
            }
        });

        // Initial period label
        this.updatePeriodLabel();
    },

    updatePeriodLabel() {
        const range = this.getPeriodDateRange(this.filters.period);
        const el = document.getElementById('periodRangeLabel');
        if (el) {
            const periodNames = {
                daily: 'Daily', weekly: 'Weekly', 'semi-monthly': 'Semi-Monthly',
                monthly: 'Monthly', quarterly: 'Quarterly', yearly: 'Yearly'
            };
            el.innerHTML = `<span style="color:var(--primary);font-weight:700;">${periodNames[this.filters.period] || 'Monthly'}</span><br><span style="font-size:0.7rem;">${range.label}</span>`;
        }
    },

    updateExportPeriodRange() {
        const period = document.getElementById('exportPeriodSelect').value;
        const range = this.getPeriodDateRange(period);
        const el = document.getElementById('exportPeriodRangeText');
        if (el) el.textContent = range.label;
    },

    async loadAllData() {
        try {
            const projectsRes = await fetch(`${BASE}/api/v1/projects?size=10000`, { credentials: 'include' });
            const projectsData = await projectsRes.json();
            this.data.projects = projectsData.projects || [];

            const contractorsRes = await fetch(`${BASE}/api/v1/contractors/ranking`, { credentials: 'include' });
            const contractorsData = await contractorsRes.json();
            this.data.contractors = contractorsData.data?.contractors || [];

            const usersRes = await fetch(`${BASE}/api/v1/users`, { credentials: 'include' });
            const usersData = await usersRes.json();
            this.data.users = Array.isArray(usersData) ? usersData : (usersData.users || []);

            this.populateFilters();
            this.updatePeriodLabel();
        } catch (error) {
            console.error('[FULL REPORTS] Load error:', error);
            if (typeof Toast !== 'undefined' && Toast.error) {
                Toast.error('Failed to load report data');
            }
        }
    },

    populateFilters() {
        const regions = [...new Set(this.data.projects.map(p => p.project_region || p.region).filter(Boolean))];
        regions.sort();
        const regionSelect = document.getElementById('regionFilter');
        regions.forEach(r => { const o = document.createElement('option'); o.value = r; o.textContent = r; regionSelect.appendChild(o); });

        const sources = [...new Set(this.data.projects.map(p => p.source).filter(Boolean))];
        sources.sort();
        const sourceSelect = document.getElementById('sourceFilter');
        sources.forEach(s => { const o = document.createElement('option'); o.value = s; o.textContent = s; sourceSelect.appendChild(o); });
    },

    // ── Parses a date string to midnight PHT (as epoch ms) ──
    phDateMs(dateStr) {
        if (!dateStr) return null;
        const d = dateStr.includes(' ') ? dateStr.split(' ')[0] : dateStr;
        const parsed = Date.parse(d + 'T00:00:00+08:00');
        return isNaN(parsed) ? null : parsed;
    },

    getFilteredProjects() {
        let filtered = this.data.projects;
        if (this.filters.region) filtered = filtered.filter(p => (p.project_region || p.region) === this.filters.region);
        if (this.filters.status) filtered = filtered.filter(p => p.status === this.filters.status);
        if (this.filters.source) filtered = filtered.filter(p => p.source === this.filters.source);

        // Apply period-based date range filter
        const range = this.getPeriodDateRange(this.filters.period);
        const fromMs = this.phDateMs(range.from);
        const toMs   = this.phDateMs(range.to) + 86400000; // end of day
        const mode = this.filters.dateMode;
        
        filtered = filtered.filter(p => {
            const dStr = mode === 'published' ? p.publication_date : (p.created_at || p.publication_date);
            const pdMs = this.phDateMs(dStr);
            if (pdMs === null) return false;
            return pdMs >= fromMs && pdMs < toMs;
        });
        
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
        projects.forEach(p => { const s = p.status || 'Unknown'; statusCounts[s] = (statusCounts[s]||0)+1; });
        document.getElementById('executiveSummary').innerHTML = `
            <div class="stat-card"><div class="stat-label">Total Projects</div><div class="stat-value">${projects.length.toLocaleString()}</div><div class="stat-sublabel">Active projects in system</div></div>
            <div class="stat-card"><div class="stat-label">Total Contractors</div><div class="stat-value">${uniqueContractors.size.toLocaleString()}</div><div class="stat-sublabel">Unique contractors</div></div>
            <div class="stat-card"><div class="stat-label">Pipeline Value</div><div class="stat-value">₱${this.formatNumber(totalValue)}</div><div class="stat-sublabel">Total project value</div></div>
            <div class="stat-card"><div class="stat-label">Average Project Value</div><div class="stat-value">₱${this.formatNumber(avgValue)}</div><div class="stat-sublabel">Per project average</div></div>
            <div class="stat-card"><div class="stat-label">Priority Projects</div><div class="stat-value">${(statusCounts.Priority||0).toLocaleString()}</div><div class="stat-sublabel">${projects.length > 0 ? ((statusCounts.Priority||0)/projects.length*100).toFixed(1) : '0.0'}% of total</div></div>
            <div class="stat-card"><div class="stat-label">Awarded Projects</div><div class="stat-value">${(statusCounts.Awarded||0).toLocaleString()}</div><div class="stat-sublabel">${projects.length > 0 ? ((statusCounts.Awarded||0)/projects.length*100).toFixed(1) : '0.0'}% of total</div></div>`;
    },

    /* ── Project Analytics ── */
    renderProjectAnalytics() {
        const projects = this.getFilteredProjects();
        const byRegion = {}, byStatus = {}, bySource = {}, byMonth = {};
        projects.forEach(p => {
            const region = p.project_region || p.region || 'Unknown';
            if (!byRegion[region]) byRegion[region] = { count:0, value:0 };
            byRegion[region].count++; byRegion[region].value += parseFloat(p.project_value)||0;

            const status = p.status || 'Unknown';
            byStatus[status] = (byStatus[status]||0)+1;

            const source = p.source || 'Unknown';
            bySource[source] = (bySource[source]||0)+1;

            if (p.publication_date) {
                const d = new Date(p.publication_date);
                const mk = d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');
                byMonth[mk] = (byMonth[mk]||0)+1;
            }
        });

        let regionData = Object.entries(byRegion).map(([region, d]) => ({ region, count: d.count, value: d.value, avg: d.count > 0 ? d.value/d.count : 0 }));
        regionData = this.sortData(regionData, this.sortState.regionTable.col, this.sortState.regionTable.dir);

        let statusData = Object.entries(byStatus).map(([status, count]) => ({ status, count, pct: projects.length > 0 ? (count/projects.length*100) : 0 }));
        statusData = this.sortData(statusData, this.sortState.statusTable.col, this.sortState.statusTable.dir);

        document.getElementById('projectAnalytics').innerHTML = `
            <div class="chart-container"><div class="chart-title">Projects by Region</div><canvas id="chartProjectsByRegion"></canvas></div>
            <div class="data-table-wrapper"><table class="data-table"><thead><tr>
                <th onclick="FullReports.toggleSort('regionTable','region',FullReports.renderProjectAnalytics)" style="cursor:pointer">Region${this.sortIcon('regionTable','region')}</th>
                <th onclick="FullReports.toggleSort('regionTable','count',FullReports.renderProjectAnalytics)" style="cursor:pointer">Project Count${this.sortIcon('regionTable','count')}</th>
                <th onclick="FullReports.toggleSort('regionTable','value',FullReports.renderProjectAnalytics)" style="cursor:pointer">Total Value${this.sortIcon('regionTable','value')}</th>
                <th onclick="FullReports.toggleSort('regionTable','avg',FullReports.renderProjectAnalytics)" style="cursor:pointer">Average Value${this.sortIcon('regionTable','avg')}</th>
            </tr></thead><tbody>${regionData.map(d=>'<tr><td>'+this.escapeHtml(d.region)+'</td><td>'+d.count.toLocaleString()+'</td><td>₱'+this.formatNumber(d.value)+'</td><td>₱'+this.formatNumber(d.avg)+'</td></tr>').join('')||'<tr><td colspan="4" style="text-align:center;color:var(--text-secondary);">No data</td></tr>'}</tbody></table></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
                <div class="chart-container"><div class="chart-title">Projects by Status</div><canvas id="chartProjectsByStatus"></canvas></div>
                <div class="chart-container"><div class="chart-title">Projects by Source</div><canvas id="chartProjectsBySource"></canvas></div>
            </div>
            <div class="data-table-wrapper"><table class="data-table"><thead><tr>
                <th onclick="FullReports.toggleSort('statusTable','status',FullReports.renderProjectAnalytics)" style="cursor:pointer">Status${this.sortIcon('statusTable','status')}</th>
                <th onclick="FullReports.toggleSort('statusTable','count',FullReports.renderProjectAnalytics)" style="cursor:pointer">Count${this.sortIcon('statusTable','count')}</th>
                <th onclick="FullReports.toggleSort('statusTable','pct',FullReports.renderProjectAnalytics)" style="cursor:pointer">Percentage${this.sortIcon('statusTable','pct')}</th>
            </tr></thead><tbody>${statusData.map(d=>'<tr><td>'+this.escapeHtml(d.status)+'</td><td>'+d.count.toLocaleString()+'</td><td>'+d.pct.toFixed(2)+'%</td></tr>').join('')||'<tr><td colspan="3" style="text-align:center;color:var(--text-secondary);">No data</td></tr>'}</tbody></table></div>
            <div class="chart-container"><div class="chart-title">Monthly Trends</div><canvas id="chartMonthlyTrends"></canvas></div>`;

        setTimeout(() => {
            this.renderProjectsByRegionChart(byRegion);
            this.renderProjectsByStatusChart(byStatus);
            this.renderProjectsBySourceChart(bySource);
            this.renderMonthlyTrendsChart(byMonth);
        }, 100);
    },

    /* ── Contractor Analytics ── */
    renderContractorAnalytics() {
        const projects = this.getFilteredProjects();
        const byContractor = {};
        projects.forEach(p => {
            const name = p.contractor_name || 'Unknown';
            if (!byContractor[name]) byContractor[name] = { count:0, value:0, sources:new Set(), regions:new Set() };
            byContractor[name].count++;
            byContractor[name].value += parseFloat(p.project_value)||0;
            if (p.source) byContractor[name].sources.add(p.source);
            const r = p.project_region||p.region; if (r) byContractor[name].regions.add(r);
        });

        let contractors = Object.entries(byContractor).map(([name, d]) => ({
            name, count: d.count, value: d.value, avgValue: d.count > 0 ? d.value/d.count : 0,
            sources: Array.from(d.sources).join(', '), regions: Array.from(d.regions).join(', ')
        }));
        contractors = this.sortData(contractors, this.sortState.contractorTable.col, this.sortState.contractorTable.dir);

        if (!this.contractorPagination) this.contractorPagination = { currentPage:1, pageSize:10 };
        const totalPages = Math.max(1, Math.ceil(contractors.length / this.contractorPagination.pageSize));
        const startIdx = (this.contractorPagination.currentPage-1)*this.contractorPagination.pageSize;
        const pageData = contractors.slice(startIdx, startIdx+this.contractorPagination.pageSize);

        document.getElementById('contractorAnalytics').innerHTML = `
            <div class="data-table-wrapper"><table class="data-table"><thead><tr>
                <th onclick="FullReports.toggleSort('contractorTable','name',FullReports.renderContractorAnalytics)" style="cursor:pointer">Contractor${this.sortIcon('contractorTable','name')}</th>
                <th>Source</th><th>Region</th>
                <th onclick="FullReports.toggleSort('contractorTable','count',FullReports.renderContractorAnalytics)" style="cursor:pointer">Count${this.sortIcon('contractorTable','count')}</th>
                <th onclick="FullReports.toggleSort('contractorTable','value',FullReports.renderContractorAnalytics)" style="cursor:pointer">Total Value${this.sortIcon('contractorTable','value')}</th>
                <th onclick="FullReports.toggleSort('contractorTable','avgValue',FullReports.renderContractorAnalytics)" style="cursor:pointer">Avg Value${this.sortIcon('contractorTable','avgValue')}</th>
            </tr></thead><tbody>${pageData.map(c=>'<tr><td>'+this.escapeHtml(c.name)+'</td><td>'+this.escapeHtml(c.sources||'N/A')+'</td><td>'+this.escapeHtml(c.regions||'N/A')+'</td><td>'+c.count.toLocaleString()+'</td><td>₱'+this.formatNumber(c.value)+'</td><td>₱'+this.formatNumber(c.avgValue)+'</td></tr>').join('')||'<tr><td colspan="6" style="text-align:center;color:var(--text-secondary);">No data</td></tr>'}</tbody></table>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem;background:var(--bg-card);border-top:1px solid var(--border-color);">
                <div style="color:var(--text-secondary);font-size:0.85rem;">${contractors.length > 0 ? 'Showing '+(startIdx+1)+'-'+Math.min(startIdx+this.contractorPagination.pageSize,contractors.length)+' of '+contractors.length : 'No results'}</div>
                <div style="display:flex;gap:0.5rem;">
                    <button onclick="FullReports.changeContractorPage(${this.contractorPagination.currentPage-1})" ${this.contractorPagination.currentPage===1?'disabled':''} style="padding:0.4rem 0.8rem;background:var(--primary);color:white;border:none;border-radius:4px;cursor:pointer;font-size:0.85rem;${this.contractorPagination.currentPage===1?'opacity:0.5;cursor:not-allowed;':''}">Previous</button>
                    <span style="display:flex;align-items:center;padding:0 1rem;color:var(--text-primary);font-weight:600;font-size:0.85rem;">Page ${this.contractorPagination.currentPage} of ${Math.max(1, totalPages)}</span>
                    <button onclick="FullReports.changeContractorPage(${this.contractorPagination.currentPage+1})" ${this.contractorPagination.currentPage>=totalPages?'disabled':''} style="padding:0.4rem 0.8rem;background:var(--primary);color:white;border:none;border-radius:4px;cursor:pointer;font-size:0.85rem;${this.contractorPagination.currentPage>=totalPages?'opacity:0.5;cursor:not-allowed;':''}">Next</button>
                </div>
            </div></div>`;
    },

    changeContractorPage(newPage) {
        const projects = this.getFilteredProjects();
        const byContractor = {};
        projects.forEach(p => {
            const name = p.contractor_name || 'Unknown';
            if (!byContractor[name]) byContractor[name] = { count:0, value:0, sources:new Set(), regions:new Set() };
            byContractor[name].count++;
            byContractor[name].value += parseFloat(p.project_value)||0;
            if (p.source) byContractor[name].sources.add(p.source);
            const r = p.project_region||p.region; if (r) byContractor[name].regions.add(r);
        });
        const totalPages = Math.max(1, Math.ceil(Object.keys(byContractor).length / this.contractorPagination.pageSize));
        if (newPage >= 1 && newPage <= totalPages) {
            this.contractorPagination.currentPage = newPage;
            this.renderContractorAnalytics();
        }
    },

    /* ── Sales Performance ── */
    renderSalesPerformance() {
        const projects = this.getFilteredProjects();
        const trackingCounts = { 'Not Started':0, 'In Progress':0, 'Complete':0 };
        projects.forEach(p => { const s = p.sales_tracking_status||'Not Started'; trackingCounts[s] = (trackingCounts[s]||0)+1; });
        const total = projects.length || 1;
        document.getElementById('salesPerformance').innerHTML = `
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-label">Not Started</div><div class="stat-value">${trackingCounts['Not Started'].toLocaleString()}</div><div class="stat-sublabel">${(trackingCounts['Not Started']/total*100).toFixed(1)}%</div></div>
                <div class="stat-card"><div class="stat-label">In Progress</div><div class="stat-value">${trackingCounts['In Progress'].toLocaleString()}</div><div class="stat-sublabel">${(trackingCounts['In Progress']/total*100).toFixed(1)}%</div></div>
                <div class="stat-card"><div class="stat-label">Complete</div><div class="stat-value">${trackingCounts['Complete'].toLocaleString()}</div><div class="stat-sublabel">${(trackingCounts['Complete']/total*100).toFixed(1)}%</div></div>
            </div>
            <div class="chart-container"><div class="chart-title">Sales Tracking Funnel</div><canvas id="chartSalesFunnel"></canvas></div>`;
        setTimeout(() => this.renderSalesFunnelChart(trackingCounts), 100);
    },

    /* ── Geographic Analysis ── */
    renderGeographicAnalysis() {
        const projects = this.getFilteredProjects();
        const byProvince = {};
        projects.forEach(p => {
            const province = p.project_province||'Unknown';
            if (!byProvince[province]) byProvince[province] = { count:0, value:0 };
            byProvince[province].count++;
            byProvince[province].value += parseFloat(p.project_value)||0;
        });

        let geoData = Object.entries(byProvince).map(([province, d]) => ({ province, count: d.count, value: d.value }));
        geoData = this.sortData(geoData, this.sortState.geographicTable.col, this.sortState.geographicTable.dir).slice(0, 20);

        document.getElementById('geographicAnalysis').innerHTML = `
            <div class="data-table-wrapper"><table class="data-table"><thead><tr>
                <th onclick="FullReports.toggleSort('geographicTable','province',FullReports.renderGeographicAnalysis)" style="cursor:pointer">Province${this.sortIcon('geographicTable','province')}</th>
                <th onclick="FullReports.toggleSort('geographicTable','count',FullReports.renderGeographicAnalysis)" style="cursor:pointer">Count${this.sortIcon('geographicTable','count')}</th>
                <th onclick="FullReports.toggleSort('geographicTable','value',FullReports.renderGeographicAnalysis)" style="cursor:pointer">Total Value${this.sortIcon('geographicTable','value')}</th>
            </tr></thead><tbody>${geoData.map(d=>'<tr><td>'+this.escapeHtml(d.province)+'</td><td>'+d.count.toLocaleString()+'</td><td>₱'+this.formatNumber(d.value)+'</td></tr>').join('')||'<tr><td colspan="3" style="text-align:center;color:var(--text-secondary);">No data</td></tr>'}</tbody></table></div>`;
    },

    /* ── Material Requirements ── */
    renderMaterialRequirements() {
        const projects = this.getFilteredProjects();
        let totals = { 'Sheet Pile':0, 'MS Plate':0, 'Angle Bars':0, 'Channel Bars':0, 'Wide Flange':0, 'GI/BI':0 };
        projects.forEach(p => {
            totals['Sheet Pile']  += parseFloat(p.sheet_pile_amount)||0;
            totals['MS Plate']    += parseFloat(p.ms_plate)||0;
            totals['Angle Bars']  += parseFloat(p.angle_bars)||0;
            totals['Channel Bars']+= parseFloat(p.channel_bars)||0;
            totals['Wide Flange'] += parseFloat(p.wide_flange)||0;
            totals['GI/BI']       += parseFloat(p.gi_bi)||0;
        });
        document.getElementById('materialRequirements').innerHTML = `
            <div class="stats-grid">${Object.entries(totals).map(([k,v])=>'<div class="stat-card"><div class="stat-label">'+k+'</div><div class="stat-value">₱'+v.toLocaleString('en-US',{minimumFractionDigits:0,maximumFractionDigits:0})+'</div><div class="stat-sublabel">Total value</div></div>').join('')}</div>
            <div class="chart-container"><div class="chart-title">Material Requirements Breakdown</div><canvas id="chartMaterialBreakdown"></canvas></div>`;
        setTimeout(() => this.renderMaterialBreakdownChart(totals), 100);
    },

    /* ── Encoding Performance ── */
    renderEncodingPerformance() {
        const projects = this.getFilteredProjects();
        const userMap = {};
        this.data.users.forEach(u => userMap[u.id] = u);
        const byEncoder = {};
        projects.forEach(p => {
            const eid = p.encoded_by;
            let name = eid ? (userMap[eid] ? (userMap[eid].full_name||userMap[eid].email||'User '+eid) : 'Unknown User #'+eid) : 'No Encoder Assigned';
            if (!byEncoder[name]) byEncoder[name] = { total:0, legitimate:0, illegitimate:0 };
            byEncoder[name].total++;
            if (p.is_illegitimate||p.illegitimate) byEncoder[name].illegitimate++;
            else byEncoder[name].legitimate++;
        });

        let encoderData = Object.entries(byEncoder).map(([name, s]) => ({ name, total: s.total, legitimate: s.legitimate, illegitimate: s.illegitimate, pct: projects.length > 0 ? (s.total/projects.length*100) : 0 }));
        encoderData = this.sortData(encoderData, this.sortState.encoderTable.col, this.sortState.encoderTable.dir);

        document.getElementById('encodingPerformance').innerHTML = `
            <div class="data-table-wrapper"><table class="data-table"><thead><tr>
                <th onclick="FullReports.toggleSort('encoderTable','name',FullReports.renderEncodingPerformance)" style="cursor:pointer">Encoder${this.sortIcon('encoderTable','name')}</th>
                <th onclick="FullReports.toggleSort('encoderTable','total',FullReports.renderEncodingPerformance)" style="cursor:pointer">Total${this.sortIcon('encoderTable','total')}</th>
                <th onclick="FullReports.toggleSort('encoderTable','legitimate',FullReports.renderEncodingPerformance)" style="cursor:pointer">Legitimate${this.sortIcon('encoderTable','legitimate')}</th>
                <th onclick="FullReports.toggleSort('encoderTable','illegitimate',FullReports.renderEncodingPerformance)" style="cursor:pointer">Illegitimate${this.sortIcon('encoderTable','illegitimate')}</th>
                <th onclick="FullReports.toggleSort('encoderTable','pct',FullReports.renderEncodingPerformance)" style="cursor:pointer">Percentage${this.sortIcon('encoderTable','pct')}</th>
            </tr></thead><tbody>${encoderData.map(d=>'<tr><td>'+this.escapeHtml(d.name)+'</td><td>'+d.total.toLocaleString()+'</td><td style="color:#10b981;">'+d.legitimate.toLocaleString()+'</td><td style="color:#ef4444;">'+d.illegitimate.toLocaleString()+'</td><td>'+d.pct.toFixed(2)+'%</td></tr>').join('')||'<tr><td colspan="5" style="text-align:center;color:var(--text-secondary);">No data</td></tr>'}</tbody></table></div>`;
    },

    formatNumber(num) {
        if (num === null || num === undefined || isNaN(num)) return '0.00';
        if (num >= 1e9) return (num/1e9).toFixed(2)+'B';
        if (num >= 1e6) return (num/1e6).toFixed(2)+'M';
        if (num >= 1e3) return (num/1e3).toFixed(2)+'K';
        return num.toFixed(2);
    },

    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    },

    // ── Charts ──
    renderProjectsByRegionChart(byRegion) {
        const ctx = document.getElementById('chartProjectsByRegion');
        if (!ctx) return;
        if (this.charts.projectsByRegion) this.charts.projectsByRegion.destroy();
        const sorted = Object.entries(byRegion).sort((a,b)=>b[1].count-a[1].count).slice(0,10);
        this.charts.projectsByRegion = new Chart(ctx, {
            type:'bar', data:{ labels: sorted.map(([r])=>r), datasets:[{ label:'Project Count', data: sorted.map(([,d])=>d.count), backgroundColor:'rgba(249,115,22,0.7)', borderColor:'rgba(249,115,22,1)', borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:2, plugins:{ legend:{display:false}, tooltip:{backgroundColor:'rgba(30,41,59,0.95)',titleColor:'#f97316',bodyColor:'#f1f5f9',borderColor:'#f97316',borderWidth:1} }, scales:{ y:{ beginAtZero:true, ticks:{color:'#94a3b8'}, grid:{color:'rgba(255,255,255,0.05)'} }, x:{ ticks:{color:'#94a3b8',maxRotation:45}, grid:{display:false} } } }
        });
    },

    renderProjectsByStatusChart(byStatus) {
        const ctx = document.getElementById('chartProjectsByStatus');
        if (!ctx) return;
        if (this.charts.projectsByStatus) this.charts.projectsByStatus.destroy();
        const colors = ['rgba(239,68,68,0.8)','rgba(249,115,22,0.8)','rgba(34,197,94,0.8)','rgba(59,130,246,0.8)','rgba(168,85,247,0.8)','rgba(236,72,153,0.8)'];
        this.charts.projectsByStatus = new Chart(ctx, {
            type:'doughnut', data:{ labels:Object.keys(byStatus), datasets:[{ data:Object.values(byStatus), backgroundColor:colors, borderColor:'#1e293b', borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:1.5, plugins:{ legend:{position:'right',labels:{color:'#94a3b8',font:{size:11}}}, tooltip:{backgroundColor:'rgba(30,41,59,0.95)',titleColor:'#f97316',bodyColor:'#f1f5f9',borderColor:'#f97316',borderWidth:1} } }
        });
    },

    renderProjectsBySourceChart(bySource) {
        const ctx = document.getElementById('chartProjectsBySource');
        if (!ctx) return;
        if (this.charts.projectsBySource) this.charts.projectsBySource.destroy();
        const sorted = Object.entries(bySource).sort((a,b)=>b[1]-a[1]).slice(0,8);
        this.charts.projectsBySource = new Chart(ctx, {
            type:'bar', data:{ labels:sorted.map(([s])=>s), datasets:[{ label:'Project Count', data:sorted.map(([,c])=>c), backgroundColor:'rgba(59,130,246,0.7)', borderColor:'rgba(59,130,246,1)', borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:1.5, indexAxis:'y', plugins:{ legend:{display:false}, tooltip:{backgroundColor:'rgba(30,41,59,0.95)',titleColor:'#f97316',bodyColor:'#f1f5f9',borderColor:'#f97316',borderWidth:1} }, scales:{ x:{ beginAtZero:true, ticks:{color:'#94a3b8'}, grid:{color:'rgba(255,255,255,0.05)'} }, y:{ ticks:{color:'#94a3b8'}, grid:{display:false} } } }
        });
    },

    renderSalesFunnelChart(trackingCounts) {
        const ctx = document.getElementById('chartSalesFunnel');
        if (!ctx) return;
        if (this.charts.salesFunnel) this.charts.salesFunnel.destroy();
        this.charts.salesFunnel = new Chart(ctx, {
            type:'bar', data:{ labels:Object.keys(trackingCounts), datasets:[{ label:'Project Count', data:Object.values(trackingCounts), backgroundColor:['rgba(239,68,68,0.7)','rgba(249,115,22,0.7)','rgba(34,197,94,0.7)'], borderColor:['rgba(239,68,68,1)','rgba(249,115,22,1)','rgba(34,197,94,1)'], borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:2.5, indexAxis:'y', plugins:{ legend:{display:false}, tooltip:{backgroundColor:'rgba(30,41,59,0.95)',titleColor:'#f97316',bodyColor:'#f1f5f9',borderColor:'#f97316',borderWidth:1} }, scales:{ x:{ beginAtZero:true, ticks:{color:'#94a3b8'}, grid:{color:'rgba(255,255,255,0.05)'} }, y:{ ticks:{color:'#94a3b8',font:{size:13}}, grid:{display:false} } } }
        });
    },

    renderMaterialBreakdownChart(materialData) {
        const ctx = document.getElementById('chartMaterialBreakdown');
        if (!ctx) return;
        if (this.charts.materialBreakdown) this.charts.materialBreakdown.destroy();
        this.charts.materialBreakdown = new Chart(ctx, {
            type:'bar', data:{ labels:Object.keys(materialData), datasets:[{ label:'Total Amount', data:Object.values(materialData), backgroundColor:'rgba(168,85,247,0.7)', borderColor:'rgba(168,85,247,1)', borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:2, plugins:{ legend:{display:false}, tooltip:{backgroundColor:'rgba(30,41,59,0.95)',titleColor:'#f97316',bodyColor:'#f1f5f9',borderColor:'#f97316',borderWidth:1} }, scales:{ y:{ beginAtZero:true, ticks:{color:'#94a3b8'}, grid:{color:'rgba(255,255,255,0.05)'} }, x:{ ticks:{color:'#94a3b8'}, grid:{display:false} } } }
        });
    },

    renderMonthlyTrendsChart(byMonth) {
        const ctx = document.getElementById('chartMonthlyTrends');
        if (!ctx) return;
        if (this.charts.monthlyTrends) this.charts.monthlyTrends.destroy();
        const sorted = Object.keys(byMonth).sort();
        this.charts.monthlyTrends = new Chart(ctx, {
            type:'line', data:{ labels:sorted, datasets:[{ label:'Projects Published', data:sorted.map(m=>byMonth[m]), backgroundColor:'rgba(249,115,22,0.1)', borderColor:'rgba(249,115,22,1)', borderWidth:3, fill:true, tension:0.4, pointRadius:5, pointBackgroundColor:'rgba(249,115,22,1)', pointBorderColor:'#fff', pointBorderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:2.5, plugins:{ legend:{display:true,labels:{color:'#94a3b8'}}, tooltip:{backgroundColor:'rgba(30,41,59,0.95)',titleColor:'#f97316',bodyColor:'#f1f5f9',borderColor:'#f97316',borderWidth:1} }, scales:{ y:{ beginAtZero:true, ticks:{color:'#94a3b8'}, grid:{color:'rgba(255,255,255,0.05)'} }, x:{ ticks:{color:'#94a3b8'}, grid:{display:false} } } }
        });
    },

    // ── Helper: convert array of arrays to worksheet ──
    _makeSheet(data, headers) {
        const wsData = [headers, ...data];
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const colWidths = headers.map((h, i) => {
            const maxLen = Math.max(
                h ? h.toString().length : 10,
                ...data.map(r => (r[i] ? r[i].toString().length : 0))
            );
            return { wch: Math.min(Math.max(maxLen + 2, 12), 40) };
        });
        ws['!cols'] = colWidths;
        return ws;
    },

    // ── Export Report (Multiple Excel Sheets) ──
    exportReport() {
        const errEl = document.getElementById('exportDateError');
        
        const expPeriodSelect = document.getElementById('exportPeriodSelect');
        const exportPeriod = expPeriodSelect ? expPeriodSelect.value : 'monthly';
        const range = this.getPeriodDateRange(exportPeriod);
        
        const expPub = document.getElementById('exportTogglePub');
        const mode = expPub && expPub.style.background === 'var(--primary)' ? 'published' : 'encoded';

        // Get selected sections
        const checkboxes = document.querySelectorAll('.export-section:checked');
        if (checkboxes.length === 0) {
            errEl.textContent = 'Please select at least one section to export.';
            errEl.style.display = 'block';
            return;
        }
        errEl.style.display = 'none';

        // Prepare filtered data
        const origFilters = { period: this.filters.period, dateMode: this.filters.dateMode, region: this.filters.region, status: this.filters.status, source: this.filters.source };
        this.filters.period = exportPeriod;
        this.filters.dateMode = mode;
        const projects = this.getFilteredProjects();

        if (projects.length === 0) {
            errEl.textContent = 'No data found for this period. Please select a period with data.';
            errEl.style.display = 'block';
            return;
        }

        // ── Create workbook ──
        const wb = XLSX.utils.book_new();

        // ── SHEET 1: Summary ──
        const periodNames = { daily: 'Daily', weekly: 'Weekly', 'semi-monthly': 'Semi-Monthly', monthly: 'Monthly', quarterly: 'Quarterly', yearly: 'Yearly' };
        const summaryData = [
            ['TDT POWERSTEEL CORPORATION'],
            ['Full Report Export'],
            [`Period: ${periodNames[exportPeriod] || 'Monthly'} (${range.label})`],
            [`Date Basis: ${mode === 'published' ? 'Published Date' : 'Encoded Date'}`],
            [`Generated: ${new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`],
            [`Total Projects: ${projects.length}`],
            [],
            ['SECTION', 'STATUS'],
            ...Array.from(checkboxes).map(cb => {
                const labels = { executive: 'Executive Summary', projects: 'Project Analytics', contractors: 'Contractor Analytics', sales: 'Sales Performance', geographic: 'Geographic Distribution', material: 'Material Requirements', encoding: 'Encoding Performance' };
                return [labels[cb.value] || cb.value, '✓ Included'];
            })
        ];
        const wsCover = XLSX.utils.aoa_to_sheet(summaryData);
        wsCover['!cols'] = [{ wch: 40 }, { wch: 20 }];
        XLSX.utils.book_append_sheet(wb, wsCover, 'Summary');

        // ── Build sheets per section ──
        checkboxes.forEach(cb => {
            const section = cb.value;
            switch (section) {
                case 'executive': {
                    const totalValue = projects.reduce((s, p) => s + (parseFloat(p.project_value) || 0), 0);
                    const avgValue = projects.length > 0 ? totalValue / projects.length : 0;
                    const uniqueContractors = new Set(projects.map(p => p.contractor_name).filter(Boolean));
                    const statusCounts = {};
                    projects.forEach(p => { const s = p.status || 'Unknown'; statusCounts[s] = (statusCounts[s]||0)+1; });
                    const data = [
                        ['Total Projects', projects.length],
                        ['Total Contractors', uniqueContractors.size],
                        ['Pipeline Value (₱)', `₱${this.formatExportNumber(totalValue)}`],
                        ['Average Project Value (₱)', `₱${this.formatExportNumber(avgValue)}`],
                        ['Priority Projects', statusCounts.Priority||0],
                        ['Awarded Projects', statusCounts.Awarded||0]
                    ];
                    const ws = this._makeSheet(data, ['Metric', 'Value']);
                    XLSX.utils.book_append_sheet(wb, ws, 'Executive');
                    break;
                }
                case 'projects': {
                    const byRegion = {}, byStatus = {};
                    projects.forEach(p => {
                        const r = p.project_region || p.region || 'Unknown';
                        if (!byRegion[r]) byRegion[r] = { count:0, value:0 };
                        byRegion[r].count++; byRegion[r].value += parseFloat(p.project_value)||0;
                        const s = p.status || 'Unknown';
                        byStatus[s] = (byStatus[s]||0)+1;
                    });
                    const regionRows = Object.entries(byRegion).sort((a,b) => b[1].value - a[1].value).map(([r,d], i) => [i+1, r, d.count, this.formatExportNumber(d.value), d.count > 0 ? this.formatExportNumber(d.value/d.count) : '0.00']);
                    const statusRows = Object.entries(byStatus).sort((a,b) => b[1] - a[1]).map(([s,c]) => [s, c, projects.length > 0 ? (c/projects.length*100).toFixed(2)+'%' : '0.00%']);
                    const allData = [
                        ['PROJECTS BY REGION', '', '', ''],
                        ['#', 'Region', 'Count', 'Total Value', 'Avg Value'],
                        ...regionRows, [],
                        ['PROJECTS BY STATUS', '', ''],
                        ['Status', 'Count', 'Percentage'],
                        ...statusRows
                    ];
                    const ws = XLSX.utils.aoa_to_sheet(allData);
                    ws['!cols'] = [{ wch: 6 }, { wch: 30 }, { wch: 12 }, { wch: 18 }, { wch: 18 }];
                    XLSX.utils.book_append_sheet(wb, ws, 'Projects');
                    break;
                }
                case 'contractors': {
                    const byCont = {};
                    projects.forEach(p => {
                        const n = p.contractor_name || 'Unknown';
                        if (!byCont[n]) byCont[n] = { count:0, value:0, sources:new Set(), regions:new Set() };
                        byCont[n].count++;
                        byCont[n].value += parseFloat(p.project_value)||0;
                        if (p.source) byCont[n].sources.add(p.source);
                        const r = p.project_region||p.region; if (r) byCont[n].regions.add(r);
                    });
                    const data = Object.entries(byCont).sort((a,b) => b[1].value - a[1].value).map(([n,d], i) => [i+1, n, Array.from(d.sources).join(', '), Array.from(d.regions).join(', '), d.count, this.formatExportNumber(d.value), d.count > 0 ? this.formatExportNumber(d.value/d.count) : '0.00']);
                    const ws = this._makeSheet(data, ['#', 'Contractor', 'Sources', 'Regions', 'Projects', 'Total Value', 'Avg Value']);
                    XLSX.utils.book_append_sheet(wb, ws, 'Contractors');
                    break;
                }
                case 'sales': {
                    const tc = { 'Not Started':0, 'In Progress':0, 'Complete':0 };
                    projects.forEach(p => { const s = p.sales_tracking_status||'Not Started'; tc[s] = (tc[s]||0)+1; });
                    const data = Object.entries(tc).map(([s,c]) => [s, c, projects.length > 0 ? (c/projects.length*100).toFixed(1)+'%' : '0.0%']);
                    const ws = this._makeSheet(data, ['Status', 'Count', 'Percentage']);
                    XLSX.utils.book_append_sheet(wb, ws, 'Sales');
                    break;
                }
                case 'geographic': {
                    const byProv = {};
                    projects.forEach(p => {
                        const prov = p.project_province||'Unknown';
                        if (!byProv[prov]) byProv[prov] = { count:0, value:0 };
                        byProv[prov].count++;
                        byProv[prov].value += parseFloat(p.project_value)||0;
                    });
                    const data = Object.entries(byProv).sort((a,b) => b[1].count - a[1].count).slice(0, 20).map(([p,d], i) => [i+1, p, d.count, this.formatExportNumber(d.value)]);
                    const ws = this._makeSheet(data, ['#', 'Province', 'Count', 'Total Value']);
                    XLSX.utils.book_append_sheet(wb, ws, 'Geographic');
                    break;
                }
                case 'material': {
                    let totals = { 'Sheet Pile':0, 'MS Plate':0, 'Angle Bars':0, 'Channel Bars':0, 'Wide Flange':0, 'GI/BI':0 };
                    projects.forEach(p => {
                        totals['Sheet Pile']  += parseFloat(p.sheet_pile_amount)||0;
                        totals['MS Plate']    += parseFloat(p.ms_plate)||0;
                        totals['Angle Bars']  += parseFloat(p.angle_bars)||0;
                        totals['Channel Bars']+= parseFloat(p.channel_bars)||0;
                        totals['Wide Flange'] += parseFloat(p.wide_flange)||0;
                        totals['GI/BI']       += parseFloat(p.gi_bi)||0;
                    });
                    const data = Object.entries(totals).map(([k,v]) => [k, `₱${v.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`]);
                    const ws = this._makeSheet(data, ['Material', 'Total Value']);
                    XLSX.utils.book_append_sheet(wb, ws, 'Materials');
                    break;
                }
                case 'encoding': {
                    const userMap = {};
                    this.data.users.forEach(u => userMap[u.id] = u);
                    const byEnc = {};
                    projects.forEach(p => {
                        const eid = p.encoded_by;
                        let name = eid ? (userMap[eid] ? (userMap[eid].full_name||userMap[eid].email||'User '+eid) : 'Unknown User #'+eid) : 'No Encoder Assigned';
                        if (!byEnc[name]) byEnc[name] = { total:0, legitimate:0, illegitimate:0 };
                        byEnc[name].total++;
                        if (p.is_illegitimate||p.illegitimate) byEnc[name].illegitimate++;
                        else byEnc[name].legitimate++;
                    });
                    const data = Object.entries(byEnc).sort((a,b) => b[1].total - a[1].total).map(([n,s], i) => [i+1, n, s.total, s.legitimate, s.illegitimate, projects.length > 0 ? (s.total/projects.length*100).toFixed(2)+'%' : '0.00%']);
                    const ws = this._makeSheet(data, ['#', 'Encoder', 'Total', 'Legitimate', 'Illegitimate', 'Share']);
                    XLSX.utils.book_append_sheet(wb, ws, 'Encoding');
                    break;
                }
            }
        });

        // ── Write file and download ──
        const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array', bookSST: false });
        const blob = new Blob([wbout], { type: 'application/octet-stream' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `TDT_Report_${exportPeriod}_${range.from}_to_${range.to}.xlsx`;
        link.click();
        URL.revokeObjectURL(link.href);

        Object.assign(this.filters, origFilters);
        closeExportModal();
        
        if (typeof Toast !== 'undefined' && Toast.success) {
            Toast.success('Report exported as Excel with multiple sheets!');
        }
    },

    formatExportNumber(num) {
        if (num === null || num === undefined || isNaN(num)) return '0.00';
        return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
};

document.addEventListener('DOMContentLoaded', () => { FullReports.init(); });