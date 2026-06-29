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
            const projectsRes = await fetch(`${BASE}/api/v1/projects`, { credentials: 'include' });
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
        this.renderSRPerformance();
        this.renderGeographicAnalysis();
        this.renderMaterialRequirements();
        this.renderEncodingPerformance();
    },

    // ── Smooth scroll to section helper ──
    scrollToSection(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    },

    renderExecutiveSummary() {
        const projects = this.getFilteredProjects();
        const totalValue = projects.reduce((sum, p) => sum + (parseFloat(p.project_value) || 0), 0);
        const avgValue = projects.length > 0 ? totalValue / projects.length : 0;
        const uniqueContractors = new Set(projects.map(p => p.contractor_name).filter(Boolean));
        const statusCounts = {};
        projects.forEach(p => { const s = p.status || 'Unknown'; statusCounts[s] = (statusCounts[s]||0)+1; });
        
        // Calculate data for each section
        // Project Analytics - By Region
        const byRegion = {};
        projects.forEach(p => {
            const region = p.project_region || p.region || 'Unknown';
            if (!byRegion[region]) byRegion[region] = { count:0, value:0 };
            byRegion[region].count++;
            byRegion[region].value += parseFloat(p.project_value)||0;
        });
        const topRegion = Object.entries(byRegion).sort((a,b) => b[1].count - a[1].count)[0];
        const topRegionName = topRegion ? topRegion[0] : 'N/A';
        const topRegionCount = topRegion ? topRegion[1].count : 0;
        
        // Contractor Analytics - Top Contractor
        const byContractor = {};
        projects.forEach(p => {
            const name = p.contractor_name || 'Unknown';
            if (!byContractor[name]) byContractor[name] = { count:0, value:0 };
            byContractor[name].count++;
            byContractor[name].value += parseFloat(p.project_value)||0;
        });
        const topContractor = Object.entries(byContractor).sort((a,b) => b[1].value - a[1].value)[0];
        const topContractorName = topContractor ? topContractor[0] : 'N/A';
        const topContractorValue = topContractor ? topContractor[1].value : 0;
        
        // Sales Performance - By Sales Rep
        const bySalesRep = {};
        projects.forEach(p => {
            const sr = p.sales_rep || 'Unassigned';
            if (!bySalesRep[sr]) bySalesRep[sr] = { count:0, value:0 };
            bySalesRep[sr].count++;
            bySalesRep[sr].value += parseFloat(p.project_value)||0;
        });
        const topSalesRep = Object.entries(bySalesRep).filter(([name]) => name !== 'Unassigned').sort((a,b) => b[1].value - a[1].value)[0];
        const topSalesRepName = topSalesRep ? topSalesRep[0] : 'N/A';
        const topSalesRepValue = topSalesRep ? topSalesRep[1].value : 0;
        
        // Geographic Distribution - Total Regions
        const uniqueRegions = Object.keys(byRegion).filter(r => r !== 'Unknown').length;
        
        // Material Requirements - Total Value of all materials
        let totalMaterialValue = 0;
        projects.forEach(p => {
            totalMaterialValue += parseFloat(p.sheet_pile_amount)||0;
            totalMaterialValue += parseFloat(p.ms_plate)||0;
            totalMaterialValue += parseFloat(p.angle_bars)||0;
            totalMaterialValue += parseFloat(p.channel_bars)||0;
            totalMaterialValue += parseFloat(p.wide_flange)||0;
            totalMaterialValue += parseFloat(p.gi_bi)||0;
        });
        
        // Encoding Performance - Total encoders & avg projects per encoder
        const byEncoder = {};
        projects.forEach(p => {
            const enc = p.encoded_by || 'Unknown';
            byEncoder[enc] = (byEncoder[enc]||0)+1;
        });
        const totalEncoders = Object.keys(byEncoder).filter(e => e !== 'Unknown').length;
        const avgProjectsPerEncoder = totalEncoders > 0 ? projects.length / totalEncoders : 0;
        
        document.getElementById('executiveSummary').innerHTML = `
            <div class="stat-card" onclick="FullReports.scrollToSection('projectAnalyticsSec')" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="stat-label">📊 Project Analytics</div>
                <div class="stat-value">${projects.length.toLocaleString()} Projects</div>
                <div class="stat-sublabel">Top Region: ${this.escapeHtml(topRegionName)} (${topRegionCount}) →</div>
            </div>
            <div class="stat-card" onclick="FullReports.scrollToSection('contractorAnalyticsSec')" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="stat-label">🏢 Contractor Analytics</div>
                <div class="stat-value">${uniqueContractors.size.toLocaleString()} Contractors</div>
                <div class="stat-sublabel">Top: ${this.escapeHtml(topContractorName.substring(0,20))}${topContractorName.length > 20 ? '...' : ''} →</div>
            </div>
            <div class="stat-card" onclick="FullReports.scrollToSection('salesPerformanceSec')" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="stat-label">💼 Sales Performance</div>
                <div class="stat-value">₱${this.formatNumber(totalValue)}</div>
                <div class="stat-sublabel">Top SR: ${this.escapeHtml(topSalesRepName.substring(0,15))}${topSalesRepName.length > 15 ? '...' : ''} →</div>
            </div>
            <div class="stat-card" onclick="FullReports.scrollToSection('srPerformanceSec')" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="stat-label">📊 SR Performance</div>
                <div class="stat-value">${Object.keys(bySalesRep).filter(k => k !== 'Unassigned').length} Sales Reps</div>
                <div class="stat-sublabel">Detailed funnel metrics →</div>
            </div>
            <div class="stat-card" onclick="FullReports.scrollToSection('geographicAnalysisSec')" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="stat-label">🗺️ Geographic Distribution</div>
                <div class="stat-value">${uniqueRegions} Regions</div>
                <div class="stat-sublabel">Avg Value: ₱${this.formatNumber(avgValue)} →</div>
            </div>
            <div class="stat-card" onclick="FullReports.scrollToSection('materialRequirementsSec')" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="stat-label">🔩 Material Requirements</div>
                <div class="stat-value">₱${this.formatNumber(totalMaterialValue)}</div>
                <div class="stat-sublabel">Total material value across all projects →</div>
            </div>
            <div class="stat-card" onclick="FullReports.scrollToSection('encodingPerformanceSec')" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="stat-label">⌨️ Encoding Performance</div>
                <div class="stat-value">${totalEncoders} Encoders</div>
                <div class="stat-sublabel">Avg: ${avgProjectsPerEncoder.toFixed(1)} projects/encoder →</div>
            </div>`;
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
                    <button onclick="FullReports.changeContractorPage(${this.contractorPagination.currentPage-1})" ${this.contractorPagination.currentPage===1?'disabled':''} style="padding:0.4rem 0.8rem;background:var(--primary);color: #111827;border:none;border-radius:4px;cursor:pointer;font-size:0.85rem;${this.contractorPagination.currentPage===1?'opacity:0.5;cursor:not-allowed;':''}">Previous</button>
                    <span style="display:flex;align-items:center;padding:0 1rem;color:var(--text-primary);font-weight:600;font-size:0.85rem;">Page ${this.contractorPagination.currentPage} of ${Math.max(1, totalPages)}</span>
                    <button onclick="FullReports.changeContractorPage(${this.contractorPagination.currentPage+1})" ${this.contractorPagination.currentPage>=totalPages?'disabled':''} style="padding:0.4rem 0.8rem;background:var(--primary);color: #111827;border:none;border-radius:4px;cursor:pointer;font-size:0.85rem;${this.contractorPagination.currentPage>=totalPages?'opacity:0.5;cursor:not-allowed;':''}">Next</button>
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

    /* ── SR Performance Report ── */
    async renderSRPerformance() {
        const container = document.getElementById('srPerformanceReport');
        container.innerHTML = '<div class="loading"><div class="spinner"></div><span>Loading SR Performance data...</span></div>';

        try {
            const range = this.getPeriodDateRange(this.filters.period);
            const params = new URLSearchParams();
            if (range.from) params.set('date_from', range.from);
            if (range.to)   params.set('date_to',   range.to);

            const res = await fetch(`${BASE}/api/v1/users/sr-performance?${params}`, { credentials: 'include' });
            if (!res.ok) throw new Error('Failed to load SR Performance data');
            
            const data = await res.json();
            const reps = data.reps || [];
            const summary = data.summary || {};

            if (reps.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--text-secondary);"><div style="font-size:2.5rem;margin-bottom:0.75rem;">📭</div><div style="font-size:1rem;font-weight:700;color:var(--text-primary);">No SR Performance Data</div><div style="font-size:0.8rem;margin-top:0.35rem;">No sales tracking records found for the selected period.</div></div>';
                return;
            }

            // Build stats grid
            let statsHtml = `<div class="stats-grid">
                <div class="stat-card"><div class="stat-label">Active SRs</div><div class="stat-value">${summary.total_reps || 0}</div><div class="stat-sublabel">with tracked activity</div></div>
                <div class="stat-card"><div class="stat-label">Total Assigned</div><div class="stat-value">${(summary.total_assigned || 0).toLocaleString()}</div><div class="stat-sublabel">projects tracked</div></div>
                <div class="stat-card"><div class="stat-label">Contacted</div><div class="stat-value">${(summary.total_contacted || 0).toLocaleString()}</div><div class="stat-sublabel">clients reached</div></div>
                <div class="stat-card"><div class="stat-label">SQL Yes</div><div class="stat-value">${(summary.total_sql_yes || 0).toLocaleString()}</div><div class="stat-sublabel">qualified leads</div></div>
                <div class="stat-card"><div class="stat-label">Quoted</div><div class="stat-value">${(summary.total_quoted || 0).toLocaleString()}</div><div class="stat-sublabel">proposals sent</div></div>
                <div class="stat-card"><div class="stat-label">Total Wins</div><div class="stat-value">${(summary.total_wins || 0).toLocaleString()}</div><div class="stat-sublabel">closed deals</div></div>
                <div class="stat-card"><div class="stat-label">Win Amount</div><div class="stat-value">₱${this.formatNumber(summary.total_win_amount || 0)}</div><div class="stat-sublabel">total won value</div></div>
                <div class="stat-card"><div class="stat-label">Pipeline Value</div><div class="stat-value">₱${this.formatNumber(summary.total_pipeline_value || 0)}</div><div class="stat-sublabel">total project value</div></div>
            </div>`;

            // Store reps data for modal access
            this.srPerformanceData = reps;

            // Build table rows
            let tableRows = reps.slice(0, 10).map((r, idx) => {
                const rank = idx + 1;
                let rankBadge = `<span class="rank-badge rank-n">${rank}</span>`;
                if (rank === 1) rankBadge = '<span class="rank-badge rank-1">🥇 1</span>';
                else if (rank === 2) rankBadge = '<span class="rank-badge rank-2">🥈 2</span>';
                else if (rank === 3) rankBadge = '<span class="rank-badge rank-3">🥉 3</span>';

                const initial = r.full_name ? r.full_name.charAt(0).toUpperCase() : '?';
                const winRate = r.win_rate || 0;
                let winBadge = 'badge-danger';
                if (winRate >= 30) winBadge = 'badge-success';
                else if (winRate >= 15) winBadge = 'badge-warning';

                const activeAssigned = (r.not_started_count || 0) + (r.in_progress_count || 0);
                const completedCount = r.complete_count || 0;

                // Funnel mini bars
                const maxFunnel = Math.max(r.total_assigned, r.contacted_count, r.sql_yes_count, r.quoted_count, r.win_count, 1);
                const funnelHtml = `<div class="funnel-mini">
                    <div class="funnel-row"><span class="funnel-label">Assigned</span><div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${r.total_assigned/maxFunnel*100}%;background:#3b82f6;"></div></div><span class="funnel-num">${r.total_assigned}</span></div>
                    <div class="funnel-row"><span class="funnel-label">Contacted</span><div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${r.contacted_count/maxFunnel*100}%;background:#8b5cf6;"></div></div><span class="funnel-num">${r.contacted_count}</span></div>
                    <div class="funnel-row"><span class="funnel-label">SQL Yes</span><div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${r.sql_yes_count/maxFunnel*100}%;background:#f59e0b;"></div></div><span class="funnel-num">${r.sql_yes_count}</span></div>
                    <div class="funnel-row"><span class="funnel-label">Quoted</span><div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${r.quoted_count/maxFunnel*100}%;background:#10b981;"></div></div><span class="funnel-num">${r.quoted_count}</span></div>
                    <div class="funnel-row"><span class="funnel-label">Win</span><div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${r.win_count/maxFunnel*100}%;background:#f97316;"></div></div><span class="funnel-num">${r.win_count}</span></div>
                </div>`;

                return `<tr class="sr-clickable-row" onclick="FullReports.showSRDetails(${r.id})" title="Click to view full details">
                    <td data-label="Rank">${rankBadge}</td>
                    <td data-label="Sales Representative"><div class="sr-name-cell"><div class="sr-avatar">${initial}</div><div><div class="sr-name">${this.escapeHtml(r.full_name)}</div><div class="sr-email">${this.escapeHtml(r.email)}</div>${r.branch ? '<div class="sr-branch">'+this.escapeHtml(r.branch)+'</div>' : ''}</div></div></td>
                    <td data-label="Assigned" class="num-cell">${activeAssigned}</td>
                    <td data-label="Funnel Breakdown">${funnelHtml}</td>
                    <td data-label="Completed" class="num-cell">${completedCount}</td>
                    <td data-label="Win Rate" class="num-cell"><span class="badge ${winBadge}">${r.win_count} <span style="opacity:0.7;font-weight:500;">(${winRate.toFixed(1)}%)</span></span></td>
                </tr>`;
            }).join('');

            container.innerHTML = `${statsHtml}
                <div class="data-table-wrapper">
                    <table class="data-table" style="font-size:0.875rem;">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Sales Rep</th>
                                <th class="num-cell">Assigned</th>
                                <th>Funnel Breakdown</th>
                                <th class="num-cell">Completed</th>
                                <th class="num-cell">Win Rate</th>
                            </tr>
                        </thead>
                        <tbody>${tableRows}</tbody>
                    </table>
                </div>`;

            // Add missing styles inline if needed
            if (!document.querySelector('style[data-sr-perf-styles]')) {
                const style = document.createElement('style');
                style.setAttribute('data-sr-perf-styles', 'true');
                style.textContent = `
                    #srPerformanceReport .data-table-wrapper { 
                        overflow-x: auto;
                        overflow-y: visible;
                    }
                    #srPerformanceReport .data-table { 
                        table-layout: auto;
                        width: 100%;
                        white-space: normal;
                    }
                    #srPerformanceReport .data-table th,
                    #srPerformanceReport .data-table td { 
                        text-align: center;
                        vertical-align: middle;
                        padding: 0.8rem 0.5rem;
                        border-right: 1px solid rgba(0,0,0,0.06);
                        word-wrap: break-word;
                        overflow-wrap: break-word;
                        max-width: 250px;
                    }
                    #srPerformanceReport .data-table th:last-child,
                    #srPerformanceReport .data-table td:last-child { 
                        border-right: none; 
                    }
                    #srPerformanceReport .data-table tbody tr { 
                        transition: all 0.2s ease; 
                        cursor: pointer;
                    }
                    #srPerformanceReport .data-table tbody tr:hover { 
                        background: rgba(255,128,0,0.08); 
                        transform: scale(1.002);
                    }
                    
                    /* Column widths - auto based on content */
                    #srPerformanceReport .data-table th:nth-child(1),
                    #srPerformanceReport .data-table td:nth-child(1) { 
                        width: auto;
                        min-width: 60px;
                    }
                    #srPerformanceReport .data-table th:nth-child(2),
                    #srPerformanceReport .data-table td:nth-child(2) { 
                        width: auto;
                        min-width: 180px;
                    }
                    #srPerformanceReport .data-table th:nth-child(3),
                    #srPerformanceReport .data-table td:nth-child(3) { 
                        width: auto;
                        min-width: 80px;
                    }
                    #srPerformanceReport .data-table th:nth-child(4),
                    #srPerformanceReport .data-table td:nth-child(4) { 
                        width: auto;
                        min-width: 200px;
                    }
                    #srPerformanceReport .data-table th:nth-child(5),
                    #srPerformanceReport .data-table td:nth-child(5) { 
                        width: auto;
                        min-width: 100px;
                    }
                    #srPerformanceReport .data-table th:nth-child(6),
                    #srPerformanceReport .data-table td:nth-child(6) {
                        width: auto;
                        min-width: 90px;
                    }
                    #srPerformanceReport .data-table th:nth-child(7),
                    #srPerformanceReport .data-table td:nth-child(7) {
                        width: auto;
                        min-width: 110px;
                    }

                    .sr-clickable-row { cursor: pointer; }
                    .sr-clickable-row:hover .sr-view-btn { opacity: 1; transform: translateX(0); color: var(--orange-400); }
                    .sr-view-btn {
                        display: inline-block;
                        font-size: 0.72rem;
                        font-weight: 700;
                        color: var(--text-secondary);
                        background: rgba(255,128,0,0.08);
                        border: 1px solid rgba(255,128,0,0.18);
                        border-radius: 999px;
                        padding: 0.22rem 0.7rem;
                        white-space: nowrap;
                        opacity: 0.55;
                        transform: translateX(-4px);
                        transition: opacity 0.18s ease, transform 0.18s ease, color 0.18s ease;
                        pointer-events: none;
                    }

                    /* Center all content */
                    #srPerformanceReport .sr-name-cell {
                        display: flex;
                        align-items: center;
                        gap: 0.7rem;
                        justify-content: center;
                        text-align: center;
                    }
                    #srPerformanceReport .funnel-mini {
                        margin: 0 auto;
                    }
                    #srPerformanceReport .num-cell {
                        text-align: center !important;
                    }
                    
                    .rank-badge { display:inline-block; padding:0.18rem 0.55rem; border-radius:999px; font-size:0.72rem; font-weight:700; white-space:nowrap; }
                    .rank-1 { background:rgba(255,193,7,0.15); color:#FFD700; }
                    .rank-2 { background:rgba(192,192,192,0.15); color:#C0C0C0; }
                    .rank-3 { background:rgba(205,127,50,0.15); color:#CD7F32; }
                    .rank-n { background:rgba(0,0,0,0.04); color:var(--text-secondary); }
                    .sr-name-cell { display:flex; align-items:center; gap:0.7rem; }
                    .sr-avatar { width:34px; height:34px; border-radius:50%; background:var(--gradient-primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.95rem; color:#000; flex-shrink:0; }
                    .sr-name { font-weight:700; color:var(--text-primary); line-height:1.2; font-size:0.875rem; }
                    .sr-email { font-size:0.72rem; color:var(--text-muted); }
                    .sr-branch { display:inline-block; margin-top:0.2rem; font-size:0.68rem; background:rgba(255,128,0,0.1); color:var(--orange-400); padding:0.08rem 0.45rem; border-radius:999px; font-weight:700; }
                    .funnel-mini { display:flex; flex-direction:column; gap:0.3rem; min-width:190px; max-width:220px; }
                    .funnel-row { display:flex; align-items:center; gap:0.4rem; }
                    .funnel-label { font-size:0.65rem; color:var(--text-secondary); width:55px; flex-shrink:0; font-weight:600; text-align:left; }
                    .funnel-bar-wrap { flex:1; height:5px; background:rgba(0,0,0,0.08); border-radius:3px; overflow:hidden; }
                    .funnel-bar { height:100%; border-radius:3px; transition:width 0.4s ease; }
                    .funnel-num { font-size:0.68rem; font-weight:700; color:var(--text-primary); width:20px; text-align:right; flex-shrink:0; }
                    .badge { display:inline-block; padding:0.18rem 0.55rem; border-radius:999px; font-size:0.72rem; font-weight:700; white-space:nowrap; }
                    .badge-success { background:rgba(16,185,129,0.15); color:#34d399; }
                    .badge-warning { background:rgba(245,158,11,0.15); color:#fcd34d; }
                    .badge-danger { background:rgba(239,68,68,0.15); color:#f87171; }
                    
                    /* Detail Modal - Exact match with SR Performance */
                    .detail-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.90); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(8px); }
                    .detail-modal-overlay.active { display:flex; }
                    .detail-modal { background:var(--bg-card); border:1px solid rgba(0,0,0,0.09); border-radius:var(--radius-lg); width:95%; max-width:1100px; max-height:88vh; overflow-y:auto; padding:2rem; position:relative; box-shadow:var(--shadow-xl); animation:modalIn 0.18s ease; z-index:1000000; }
                    @keyframes modalIn { from { opacity:0; transform:translateY(12px) scale(0.98); } to { opacity:1; transform:none; } }
                    
                    .modal-close-btn { position:absolute; top:1rem; right:1rem; background:rgba(0,0,0,0.05); border:none; border-radius:50%; width:30px; height:30px; color:var(--text-secondary); font-size:1.1rem; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.15s, color 0.15s; }
                    .modal-close-btn:hover { background:rgba(0,0,0,0.08); color:var(--text-primary); }
                    
                    .modal-sr-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; }
                    .modal-avatar { width:52px; height:52px; border-radius:50%; background:var(--gradient-primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.4rem; color:#000; flex-shrink:0; }
                    .modal-sr-name { font-size:1.2rem; font-weight:800; color:var(--text-primary); }
                    .modal-sr-email { font-size:0.8rem; color:var(--text-muted); margin-top:0.15rem; }
                    .modal-sr-branch { display:inline-block; margin-top:0.3rem; font-size:0.72rem; background:rgba(255,128,0,0.1); color:var(--orange-400); padding:0.1rem 0.55rem; border-radius:999px; font-weight:700; }
                    
                    .modal-section { margin-bottom:1.5rem; }
                    .modal-section-title { font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-secondary); margin-bottom:0.75rem; padding-bottom:0.4rem; border-bottom:1px solid rgba(0,0,0,0.08); }
                    
                    .modal-stat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:0.75rem; }
                    .modal-stat { background:rgba(0,0,0,0.02); border:1px solid rgba(0,0,0,0.07); border-radius:var(--radius-md); padding:0.75rem 1rem; }
                    .modal-stat-label { font-size:0.68rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.06em; }
                    .modal-stat-val { font-size:1.25rem; font-weight:800; color:var(--text-primary); margin-top:0.15rem; }
                    .modal-stat-val.c-green { color:#10B981; }
                    .modal-stat-val.c-blue { color:#3B82F6; }
                    .modal-stat-val.c-yellow { color:#F59E0B; }
                    .modal-stat-val.c-purple { color:#8B5CF6; }
                    .modal-stat-val.c-orange { color:var(--orange-500); }
                    
                    .modal-tracking-row { display:flex; gap:0.6rem; }
                    .modal-track-badge { flex:1; text-align:center; padding:0.6rem; border-radius:var(--radius-md); font-size:0.75rem; font-weight:700; }
                    .modal-track-badge .mtn { font-size:1.4rem; font-weight:800; display:block; }
                    .modal-track-ns { background:rgba(108,117,125,0.15); color:#adb5bd; }
                    .modal-track-ip { background:rgba(59,130,246,0.12); color:#60a5fa; }
                    .modal-track-co { background:rgba(16,185,129,0.12); color:#34d399; }
                    
                    /* ── Assigned Projects table inside SR modal ── */
                    .sr-proj-table { width:100%; border-collapse:collapse; font-size:0.78rem; }
                    .sr-proj-table th { text-align:left; padding:0.45rem 0.6rem; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid rgba(0,0,0,0.08); }
                    .sr-proj-table td { padding:0.5rem 0.6rem; border-bottom:1px solid rgba(0,0,0,0.06); color:var(--text-primary); vertical-align:middle; }
                    .sr-proj-table tr:last-child td { border-bottom:none; }
                    .sr-proj-table tbody tr { cursor:pointer; transition:background 0.12s; }
                    .sr-proj-table tbody tr:hover { background:rgba(255,122,0,0.04); }
                    .sr-proj-table tbody tr:hover .proj-view-btn { opacity:1; }
                    .proj-view-btn { opacity:0; font-size:0.72rem; font-weight:700; color:var(--orange-400); transition:opacity 0.15s; white-space:nowrap; }
                    .stage-pip { display:inline-block; font-size:0.6rem; font-weight:700; padding:0.1rem 0.35rem; border-radius:999px; margin-right:2px; }
                    .stage-pip.c { background:rgba(59,130,246,0.15); color:#60a5fa; }
                    .stage-pip.s { background:rgba(16,185,129,0.15); color:#34d399; }
                    .stage-pip.q { background:rgba(245,158,11,0.15); color:#fbbf24; }
                    .stage-pip.w { background:rgba(249,115,22,0.15); color:#fb923c; }
                    .ts-badge { font-size:0.65rem; font-weight:700; padding:0.15rem 0.5rem; border-radius:999px; }
                    .ts-badge.ns { background:rgba(108,117,125,0.15); color:#adb5bd; }
                    .ts-badge.ip { background:rgba(59,130,246,0.12); color:#60a5fa; }
                    .ts-badge.co { background:rgba(16,185,129,0.12); color:#34d399; }

                    /* ── Project Detail sub-modal content ── */
                    .pd-header { display:flex; align-items:flex-start; gap:1rem; margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid rgba(0,0,0,0.08); }
                    .pd-title { font-size:1.1rem; font-weight:800; color:var(--text-primary); line-height:1.3; }
                    .pd-sub  { font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem; }
                    .pd-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:0.75rem; margin-bottom:1.25rem; }
                    .pd-item { background:rgba(0,0,0,0.02); border:1px solid rgba(0,0,0,0.07); border-radius:8px; padding:0.65rem 0.85rem; }
                    .pd-label { font-size:0.64rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.2rem; }
                    .pd-val   { font-size:0.88rem; font-weight:600; color:var(--text-primary); }
                    .pd-section-title { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-secondary); margin:1rem 0 0.6rem; padding-bottom:0.35rem; border-bottom:1px solid rgba(0,0,0,0.08); }

                    @media (max-width: 768px) {
                        .detail-modal { width:100%; max-width:95vw; padding:1.5rem; }
                        .modal-stat-grid { grid-template-columns:repeat(2,1fr); }
                        .pd-grid { grid-template-columns:1fr; }
                    }

                    @media (max-width: 480px) {
                        .modal-stat-grid { grid-template-columns:1fr; }
                    }
                `;
                document.head.appendChild(style);
            }

        } catch (error) {
            console.error('[FULL REPORTS] SR Performance error:', error);
            container.innerHTML = '<div style="text-align:center;padding:3rem;color:#ef4444;"><div style="font-size:2rem;margin-bottom:0.5rem;">⚠️</div><div>Failed to load SR Performance data</div></div>';
        }
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
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:2, plugins:{ legend:{display:false}, tooltip:{backgroundColor: '#FFFFFF',titleColor:'#f97316',bodyColor: '#111827',borderColor:'#f97316',borderWidth:1} }, scales:{ y:{ beginAtZero:true, ticks:{color:'#374151'}, grid:{color:'rgba(0,0,0,0.04)'} }, x:{ ticks:{color:'#374151',maxRotation:45}, grid:{display:false} } } }
        });
    },

    renderProjectsByStatusChart(byStatus) {
        const ctx = document.getElementById('chartProjectsByStatus');
        if (!ctx) return;
        if (this.charts.projectsByStatus) this.charts.projectsByStatus.destroy();
        const colors = ['rgba(239,68,68,0.8)','rgba(249,115,22,0.8)','rgba(34,197,94,0.8)','rgba(59,130,246,0.8)','rgba(168,85,247,0.8)','rgba(236,72,153,0.8)'];
        this.charts.projectsByStatus = new Chart(ctx, {
            type:'doughnut', data:{ labels:Object.keys(byStatus), datasets:[{ data:Object.values(byStatus), backgroundColor:colors, borderColor: '#FFFFFF', borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:1.5, plugins:{ legend:{position:'right',labels:{color:'#374151',font:{size:11}}}, tooltip:{backgroundColor: '#FFFFFF',titleColor:'#f97316',bodyColor: '#111827',borderColor:'#f97316',borderWidth:1} } }
        });
    },

    renderProjectsBySourceChart(bySource) {
        const ctx = document.getElementById('chartProjectsBySource');
        if (!ctx) return;
        if (this.charts.projectsBySource) this.charts.projectsBySource.destroy();
        const sorted = Object.entries(bySource).sort((a,b)=>b[1]-a[1]).slice(0,8);
        this.charts.projectsBySource = new Chart(ctx, {
            type:'bar', data:{ labels:sorted.map(([s])=>s), datasets:[{ label:'Project Count', data:sorted.map(([,c])=>c), backgroundColor:'rgba(59,130,246,0.7)', borderColor:'rgba(59,130,246,1)', borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:1.5, indexAxis:'y', plugins:{ legend:{display:false}, tooltip:{backgroundColor: '#FFFFFF',titleColor:'#f97316',bodyColor: '#111827',borderColor:'#f97316',borderWidth:1} }, scales:{ x:{ beginAtZero:true, ticks:{color:'#374151'}, grid:{color:'rgba(0,0,0,0.04)'} }, y:{ ticks:{color:'#374151'}, grid:{display:false} } } }
        });
    },

    renderSalesFunnelChart(trackingCounts) {
        const ctx = document.getElementById('chartSalesFunnel');
        if (!ctx) return;
        if (this.charts.salesFunnel) this.charts.salesFunnel.destroy();
        this.charts.salesFunnel = new Chart(ctx, {
            type:'bar', data:{ labels:Object.keys(trackingCounts), datasets:[{ label:'Project Count', data:Object.values(trackingCounts), backgroundColor:['rgba(239,68,68,0.7)','rgba(249,115,22,0.7)','rgba(34,197,94,0.7)'], borderColor:['rgba(239,68,68,1)','rgba(249,115,22,1)','rgba(34,197,94,1)'], borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:2.5, indexAxis:'y', plugins:{ legend:{display:false}, tooltip:{backgroundColor: '#FFFFFF',titleColor:'#f97316',bodyColor: '#111827',borderColor:'#f97316',borderWidth:1} }, scales:{ x:{ beginAtZero:true, ticks:{color:'#374151'}, grid:{color:'rgba(0,0,0,0.04)'} }, y:{ ticks:{color:'#374151',font:{size:13}}, grid:{display:false} } } }
        });
    },

    renderMaterialBreakdownChart(materialData) {
        const ctx = document.getElementById('chartMaterialBreakdown');
        if (!ctx) return;
        if (this.charts.materialBreakdown) this.charts.materialBreakdown.destroy();
        this.charts.materialBreakdown = new Chart(ctx, {
            type:'bar', data:{ labels:Object.keys(materialData), datasets:[{ label:'Total Amount', data:Object.values(materialData), backgroundColor:'rgba(168,85,247,0.7)', borderColor:'rgba(168,85,247,1)', borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:2, plugins:{ legend:{display:false}, tooltip:{backgroundColor: '#FFFFFF',titleColor:'#f97316',bodyColor: '#111827',borderColor:'#f97316',borderWidth:1} }, scales:{ y:{ beginAtZero:true, ticks:{color:'#374151'}, grid:{color:'rgba(0,0,0,0.04)'} }, x:{ ticks:{color:'#374151'}, grid:{display:false} } } }
        });
    },

    renderMonthlyTrendsChart(byMonth) {
        const ctx = document.getElementById('chartMonthlyTrends');
        if (!ctx) return;
        if (this.charts.monthlyTrends) this.charts.monthlyTrends.destroy();
        const sorted = Object.keys(byMonth).sort();
        this.charts.monthlyTrends = new Chart(ctx, {
            type:'line', data:{ labels:sorted, datasets:[{ label:'Projects Published', data:sorted.map(m=>byMonth[m]), backgroundColor:'rgba(249,115,22,0.1)', borderColor:'rgba(249,115,22,1)', borderWidth:3, fill:true, tension:0.4, pointRadius:5, pointBackgroundColor:'rgba(249,115,22,1)', pointBorderColor:'#fff', pointBorderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:true, aspectRatio:2.5, plugins:{ legend:{display:true,labels:{color:'#374151'}}, tooltip:{backgroundColor: '#FFFFFF',titleColor:'#f97316',bodyColor: '#111827',borderColor:'#f97316',borderWidth:1} }, scales:{ y:{ beginAtZero:true, ticks:{color:'#374151'}, grid:{color:'rgba(0,0,0,0.04)'} }, x:{ ticks:{color:'#374151'}, grid:{display:false} } } }
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

        // ── Log export activity to Activity Logs ──
        try {
            const sectionLabels = [];
            checkboxes.forEach(cb => {
                const labels = { executive: 'Executive Summary', projects: 'Project Analytics', contractors: 'Contractor Analytics', sales: 'Sales Performance', geographic: 'Geographic Distribution', material: 'Material Requirements', encoding: 'Encoding Performance' };
                sectionLabels.push(labels[cb.value] || cb.value);
            });
            fetch(`${BASE}/api/v1/log-export`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    period: exportPeriod,
                    dateMode: mode,
                    sections: sectionLabels,
                    projectCount: projects.length
                })
            }).catch(() => {}); // silently fail - don't block the user
        } catch(e) {}

        Object.assign(this.filters, origFilters);
        closeExportModal();
        
        if (typeof Toast !== 'undefined' && Toast.success) {
            Toast.success('Report exported as Excel with multiple sheets!');
        }
    },

    formatExportNumber(num) {
        if (num === null || num === undefined || isNaN(num)) return '0.00';
        return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    // ── SR Performance Modal Functions ──
    async showSRDetails(srId) {
        const overlay = document.getElementById('srDetailModal');

        // Open immediately with loading state
        overlay.style.display = 'flex';
        document.getElementById('srModalAvatar').textContent   = '…';
        document.getElementById('srModalName').textContent     = 'Loading...';
        document.getElementById('srModalEmail').textContent    = '';
        document.getElementById('srModalBranch').style.display = 'none';

        // Wire up close handlers once
        const closeBtn = document.getElementById('closeSRDetailModal');
        if (closeBtn) closeBtn.onclick = () => this.closeSRModal();
        const closeHandler = (e) => {
            if (e.key === 'Escape' || e.target === overlay) {
                this.closeSRModal();
                document.removeEventListener('keydown', closeHandler);
                overlay.removeEventListener('click', closeHandler);
            }
        };
        document.addEventListener('keydown', closeHandler);
        overlay.addEventListener('click', closeHandler);

        // Fetch both APIs in parallel — stats + project detail (all projects, no date filter)
        try {
            const [perfRes, detailRes] = await Promise.all([
                fetch(`${BASE}/api/v1/users/sr-performance?sr_id=${srId}`,        { credentials: 'include' }),
                fetch(`${BASE}/api/v1/users/sr-performance-detail?sr_id=${srId}`, { credentials: 'include' }),
            ]);
            if (!perfRes.ok)   throw new Error('Performance API error ' + perfRes.status);
            if (!detailRes.ok) throw new Error('Detail API error '      + detailRes.status);

            const perfData   = await perfRes.json();
            const detailData = await detailRes.json();
            const rep        = perfData.reps?.[0];
            const projects   = detailData.projects || [];

            if (!rep) {
                document.getElementById('srModalName').textContent = 'Data not found';
                return;
            }

            // Identity
            document.getElementById('srModalAvatar').textContent = rep.full_name ? rep.full_name.charAt(0).toUpperCase() : '?';
            document.getElementById('srModalName').textContent   = rep.full_name || '—';
            document.getElementById('srModalEmail').textContent  = rep.email || '—';
            const branchEl = document.getElementById('srModalBranch');
            if (rep.branch) { branchEl.textContent = rep.branch; branchEl.style.display = 'inline-block'; }

            // Overview
            document.getElementById('srModalAssigned').textContent  = rep.total_assigned    || 0;
            document.getElementById('srModalContacted').textContent = rep.contacted_count   || 0;
            document.getElementById('srModalSqlYes').textContent    = rep.sql_yes_count     || 0;
            document.getElementById('srModalSqlNo').textContent     = rep.sql_no_count      || 0;
            document.getElementById('srModalQuoted').textContent    = rep.quoted_count      || 0;
            document.getElementById('srModalWins').textContent      = rep.win_count         || 0;
            document.getElementById('srModalWinRate').textContent   = (rep.win_rate   || 0).toFixed(1) + '%';
            document.getElementById('srModalWinAmount').textContent = '₱' + this.formatNumber(rep.total_win_amount    || 0);
            document.getElementById('srModalPipeline').textContent  = '₱' + this.formatNumber(rep.total_pipeline_value || 0);

            // Conversion rates
            document.getElementById('srModalContactRate').textContent = (rep.contact_rate || 0).toFixed(1) + '%';
            document.getElementById('srModalSqlRate').textContent     = (rep.sql_rate     || 0).toFixed(1) + '%';
            document.getElementById('srModalQuoteRate').textContent   = (rep.quote_rate   || 0).toFixed(1) + '%';
            document.getElementById('srModalWinRate2').textContent    = (rep.win_rate     || 0).toFixed(1) + '%';

            // Tracking status
            document.getElementById('srModalNotStarted').textContent = rep.not_started_count || 0;
            document.getElementById('srModalInProgress').textContent = rep.in_progress_count || 0;
            document.getElementById('srModalComplete').textContent   = rep.complete_count    || 0;

            // Speed metrics — averages across ALL projects (from detail response, no limit)
            // Values from detail API are in seconds; convert to days for formatDetailedTime
            const secToDay = s => (s !== null && s !== undefined) ? s / 86400 : null;
            document.getElementById('srModalTimingSection').style.display = 'block';
            document.getElementById('srModalCycles').textContent    = detailData.cycle_count || 0;
            document.getElementById('srModalFullCycle').textContent = this.formatDetailedTime(secToDay(detailData.avg_full_cycle_sec));
            document.getElementById('srModalToContact').textContent = this.formatDetailedTime(secToDay(detailData.avg_assign_to_contact));
            document.getElementById('srModalToSql').textContent     = this.formatDetailedTime(secToDay(detailData.avg_contact_to_sql));
            document.getElementById('srModalToQuote').textContent   = this.formatDetailedTime(secToDay(detailData.avg_sql_to_quote));
            document.getElementById('srModalToWin').textContent     = this.formatDetailedTime(secToDay(detailData.avg_quote_to_win));

            // Project list
            this.renderSRProjectsList(projects);

        } catch (e) {
            console.error('[SR Modal] Failed to load SR details:', e);
            document.getElementById('srModalName').textContent = 'Error loading data';
        }
    },

    renderSRProjectsList(projects) {
        const container = document.getElementById('srModalProjectsList');
        if (!projects.length) {
            container.innerHTML = '<div style="color:var(--text-muted);font-size:0.8rem;text-align:center;padding:1rem;">No projects found</div>';
            return;
        }

        const tsClass = s => s === 'Complete' ? 'co' : s === 'In Progress' ? 'ip' : 'ns';

        const isYes = v => String(v ?? '').toLowerCase() === 'yes';
        const rows = projects.map(p => {
            const stages = [
                isYes(p.contacted)       ? '<span class="stage-pip c">C</span>'   : '',
                isYes(p.sales_qualified) ? '<span class="stage-pip s">SQL</span>' : '',
                isYes(p.quoted)          ? '<span class="stage-pip q">Q</span>'   : '',
                isYes(p.to_win)          ? '<span class="stage-pip w">W</span>'   : '',
            ].join('');
            const ts   = p.tracking_status || 'Not Started';
            const val  = p.project_value ? '₱' + this.formatNumber(p.project_value) : '—';
            const name = this.escapeHtml(p.project_name || '—');
            const con  = this.escapeHtml(p.contractor_name || '—');
            return `<tr onclick="FullReports.showProjectDetail(${p.project_id})">
                <td style="font-weight:600;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${name}">${name}</td>
                <td style="color:var(--text-secondary);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${con}">${con}</td>
                <td style="font-weight:700;color:var(--orange-400);">${val}</td>
                <td>${stages || '<span style="color:var(--text-muted);font-size:0.72rem;">—</span>'}</td>
                <td><span class="ts-badge ${tsClass(ts)}">${ts}</span></td>
                <td style="text-align:right;"><span class="proj-view-btn">View →</span></td>
            </tr>`;
        }).join('');

        container.innerHTML = `<table class="sr-proj-table">
            <thead><tr>
                <th>Project</th><th>Contractor</th><th>Value</th><th>Stages</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
    },

    async showProjectDetail(projectId) {
        const modal = document.getElementById('projDetailModal');
        if (!modal) { console.error('[Project Modal] #projDetailModal not found'); return; }

        modal.style.display = 'flex';
        document.getElementById('projDetailContent').innerHTML =
            '<div class="loading"><div class="spinner"></div><span>Loading project...</span></div>';

        // Close on Escape or backdrop click (stop propagation so SR modal stays open)
        const closeHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeProjModal();
                document.removeEventListener('keydown', closeHandler);
                modal.removeEventListener('click', closeHandler);
            }
        };
        modal.addEventListener('click', (e) => {
            if (e.target === modal) { this.closeProjModal(); }
            e.stopPropagation();
        }, { once: true });
        document.addEventListener('keydown', closeHandler);

        try {
            const [projRes, stRes] = await Promise.all([
                fetch(`${BASE}/api/v1/projects?db_id=${projectId}&size=1`, { credentials: 'include' }),
                fetch(`${BASE}/api/v1/projects/${projectId}/sales-tracking`,  { credentials: 'include' }),
            ]);
            const projData = await projRes.json();
            const stData   = await stRes.json();

            const p  = projData.projects?.[0];
            const st = stData.exists ? stData.data : null;

            if (!p) {
                document.getElementById('projDetailContent').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:2rem;">Project not found</p>';
                return;
            }

            const fmt  = v => v != null ? '₱' + this.formatNumber(v) : '—';
            const esc  = v => this.escapeHtml(v || '—');
            const bool = v => v === true ? '<span style="color:#34d399;font-weight:700;">Yes</span>' : v === false ? '<span style="color:#f87171;font-weight:700;">No</span>' : '<span style="color:var(--text-muted);">—</span>';

            // Per-project speed metrics from timestamps
            const parseTs  = s => s ? new Date(s.includes('+') ? s : s.replace(' ', 'T') + '+08:00') : null;
            const diffDays = (a, b) => { const d = parseTs(b) - parseTs(a); return (a && b && d > 0) ? d / 1000 / 86400 : null; };
            const spd = st ? {
                toContact : diffDays(st.assigned_at,        st.contacted_at),
                toSql     : diffDays(st.contacted_at,       st.sales_qualified_at),
                toQuote   : diffDays(st.sales_qualified_at, st.quoted_at),
                toWin     : diffDays(st.quoted_at,          st.to_win_at),
                full      : diffDays(st.assigned_at,        st.to_win_at),
            } : null;

            document.getElementById('projDetailContent').innerHTML = `
                <div class="pd-header">
                    <div style="flex:1;">
                        <div class="pd-title">${esc(p.project_name)}</div>
                        <div class="pd-sub">${esc(p.contractor_name)} · ${p.publication_date || '—'}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:1.3rem;font-weight:800;color:var(--orange-400);">${fmt(p.project_value)}</div>
                        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.15rem;">${esc(p.status)}</div>
                    </div>
                </div>

                <div class="pd-section-title">Basic Information</div>
                <div class="pd-grid">
                    <div class="pd-item"><div class="pd-label">Source</div><div class="pd-val">${esc(p.source)}</div></div>
                    <div class="pd-item"><div class="pd-label">Region</div><div class="pd-val">${esc(p.project_region || p.region)}</div></div>
                    <div class="pd-item"><div class="pd-label">Contact Person</div><div class="pd-val">${esc(p.contact_person)}</div></div>
                    <div class="pd-item"><div class="pd-label">Contact Number</div><div class="pd-val">${esc(p.contact_number)}</div></div>
                    <div class="pd-item"><div class="pd-label">Contractor ID</div><div class="pd-val">${esc(p.contractor_id || p.contract_id)}</div></div>
                    <div class="pd-item"><div class="pd-label">Publication Date</div><div class="pd-val">${esc(p.publication_date)}</div></div>
                </div>

                ${st ? `
                <div class="pd-section-title">Sales Tracking</div>
                <div class="pd-grid">
                    <div class="pd-item"><div class="pd-label">Assigned SR</div><div class="pd-val">${esc(st.sales_rep_name)}</div></div>
                    <div class="pd-item"><div class="pd-label">Branch</div><div class="pd-val">${esc(st.branch)}</div></div>
                    <div class="pd-item"><div class="pd-label">Contacted</div><div class="pd-val">${bool(st.contacted)}</div></div>
                    <div class="pd-item"><div class="pd-label">Sales Qualified</div><div class="pd-val">${bool(st.sales_qualified)}</div></div>
                    <div class="pd-item"><div class="pd-label">Quoted</div><div class="pd-val">${bool(st.quoted)}</div></div>
                    <div class="pd-item"><div class="pd-label">Win</div><div class="pd-val">${bool(st.to_win)}</div></div>
                    <div class="pd-item"><div class="pd-label">W/A Amount</div><div class="pd-val">${fmt(st.wa_amount)}</div></div>
                    <div class="pd-item"><div class="pd-label">Tracking Status</div><div class="pd-val">${esc(st.tracking_status)}</div></div>
                    ${st.notes ? `<div class="pd-item" style="grid-column:1/-1;"><div class="pd-label">Notes</div><div class="pd-val" style="white-space:pre-wrap;font-size:0.82rem;">${esc(st.notes)}</div></div>` : ''}
                </div>

                <div class="pd-section-title">Speed Metrics</div>
                <div class="pd-grid">
                    <div class="pd-item"><div class="pd-label">Assigned → Contacted</div><div class="pd-val">${this.formatDetailedTime(spd?.toContact)}</div></div>
                    <div class="pd-item"><div class="pd-label">Contacted → SQL</div><div class="pd-val">${this.formatDetailedTime(spd?.toSql)}</div></div>
                    <div class="pd-item"><div class="pd-label">SQL → Quoted</div><div class="pd-val">${this.formatDetailedTime(spd?.toQuote)}</div></div>
                    <div class="pd-item"><div class="pd-label">Quoted → Win</div><div class="pd-val">${this.formatDetailedTime(spd?.toWin)}</div></div>
                    <div class="pd-item"><div class="pd-label">Full Cycle</div><div class="pd-val" style="font-weight:700;color:var(--orange-400);">${this.formatDetailedTime(spd?.full)}</div></div>
                </div>` : '<div style="color:var(--text-muted);font-size:0.8rem;padding:0.5rem 0;">No sales tracking record for this project.</div>'}
            `;
        } catch (e) {
            console.error('[Project Modal] Error:', e);
            document.getElementById('projDetailContent').innerHTML = '<p style="color:#f87171;text-align:center;padding:2rem;">Error loading project details</p>';
        }
    },

    closeProjModal() {
        const modal = document.getElementById('projDetailModal');
        if (modal) modal.style.display = 'none';
    },

    closeSRModal() {
        const overlay = document.getElementById('srDetailModal');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },

    // Helper: format days as detailed time (days, hours, minutes, seconds)
    formatDetailedTime(days) {
        if (days === null || days === undefined) return '—';

        const totalSeconds = Math.round(days * 86400);
        if (totalSeconds <= 0) return '—';

        const d = Math.floor(totalSeconds / 86400);
        const h = Math.floor((totalSeconds % 86400) / 3600);
        const m = Math.floor((totalSeconds % 3600) / 60);
        const s = totalSeconds % 60;

        const parts = [];
        if (d > 0) parts.push(`${d}d`);
        if (h > 0) parts.push(`${h}h`);
        if (m > 0) parts.push(`${m}m`);
        if (s > 0) parts.push(`${s}s`);

        return parts.length > 0 ? parts.join(' ') : '—';
    }
};

document.addEventListener('DOMContentLoaded', () => { FullReports.init(); });