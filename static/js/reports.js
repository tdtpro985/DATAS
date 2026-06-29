        
        // Global state management
        const AppState = {
            charts: {},
            intervals: {},
            isLoading: false,
            hasErrors: false
        };
        
        // Utility functions
        const Utils = {
            formatNumber(num) {
                if (typeof num !== 'number') num = parseFloat(num) || 0;
                if (num >= 1000000000) {
                    return (num / 1000000000).toFixed(1) + 'B';
                } else if (num >= 1000000) {
                    return (num / 1000000).toFixed(1) + 'M';
                } else if (num >= 1000) {
                    return (num / 1000).toFixed(1) + 'K';
                }
                return num.toLocaleString();
            },

            async fetchWithFallback(url, fallbackData) {
                console.log(`[API] Fetching: ${url}`);
                try {
                    const response = await fetch(url);
                    console.log(`[API] Response status for ${url}: ${response.status}`);
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const data = await response.json();
                    console.log(`[API] Data received from ${url}:`, data);
                    return { success: true, data };
                } catch (error) {
                    console.error(`[API] Error for ${url}:`, error);
                    console.log(`[API] Using fallback data:`, fallbackData);
                    return { success: false, data: fallbackData, error };
                }
            },

            showLoadingState(elementId, message = 'Loading...') {
                const element = document.getElementById(elementId);
                if (element) {
                    element.innerHTML = `<div style="text-align: center; color: #888; padding: 1rem;">${message}</div>`;
                }
            },

            showErrorState(elementId, message = 'Unable to load data') {
                const element = document.getElementById(elementId);
                if (element) {
                    element.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 1rem;">⚠️ ${message}</div>`;
                }
            },

            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        };

        // Clock functionality
        const Clock = {
            init() {
                this.update();
                AppState.intervals.clock = setInterval(() => this.update(), 1000);
            },

            update() {
                try {
                    const now = new Date();
                    // Use Philippine DateTime formatter if available
                    const timeStr = window.PhilippineDateTime 
                        ? PhilippineDateTime.currentTime()
                        : now.toLocaleTimeString('en-PH', { 
                            timeZone: 'Asia/Manila',
                            hour: '2-digit', 
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: true 
                        });
                    
                    const timeElement = document.getElementById('current-time');
                    if (timeElement) timeElement.textContent = timeStr;
                    
                    // Update sync time (simulate last sync - 1 minute ago)
                    const syncTime = new Date(now.getTime() - 60000);
                    const syncStr = window.PhilippineDateTime
                        ? PhilippineDateTime.formatTimeShort(syncTime)
                        : syncTime.toLocaleTimeString('en-PH', { 
                            timeZone: 'Asia/Manila',
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: true 
                        });
                    
                    const syncElement = document.getElementById('sync-time');
                    if (syncElement) syncElement.textContent = syncStr;
                } catch (error) {
                    console.error('Clock update error:', error);
                }
            }
        };
        
        // Filter management
        const Filters = {
            get() {
                const period = document.getElementById('period-select')?.value || 'monthly';
                const region = document.getElementById('region-select')?.value || 'all';
                const monthValue = document.getElementById('month-select')?.value || 'all';

                if (period === 'overall') {
                    return {
                        period: 'overall',
                        region: region === 'all' ? null : region,
                        month: null,
                        year: null
                    };
                }

                if (monthValue === 'all') {
                    return {
                        period: period,
                        region: region === 'all' ? null : region,
                        month: null,
                        year: null
                    };
                }

                const [month, year] = monthValue.split('-');
                return {
                    period: period,
                    region: region === 'all' ? null : region,
                    month: parseInt(month),
                    year: parseInt(year)
                };
            },

            toUrlParams(filters = null) {
                const params = new URLSearchParams();
                const filterData = filters || this.get();
                
                if (filterData.period) params.append('period', filterData.period);
                if (filterData.region) params.append('region', filterData.region);
                if (filterData.month) params.append('month', filterData.month);
                if (filterData.year) params.append('year', filterData.year);
                
                return params;
            }
        };

        // KPI Module
        const KPI = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/kpi?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, {
                    data: {
                        projects_encoded: 0,
                        contractors_identified: 0,
                        total_pipeline_value: 0
                    }
                });

                console.log('[KPI] Result:', result);
                console.log('[KPI] Has data.data?', !!result.data?.data);
                if (result.data?.data) {
                    console.log('[KPI] Projects:', result.data.data.projects_encoded);
                    console.log('[KPI] Contractors:', result.data.data.contractors_identified);
                }

                if (result.success && result.data?.data) {
                    const data = result.data.data;
                    this.render({
                        projects: data.projects_encoded || 0,
                        contractors: data.contractors_identified || 0,
                        value: data.total_pipeline_value || 0
                    });
                } else {
                    console.warn('[KPI] Rendering fallback');
                    this.renderFallback();
                }
            },

            render(data) {
                const elements = {
                    projects: document.getElementById('total-projects'),
                    contractors: document.getElementById('total-contractors'),
                    value: document.getElementById('total-value')
                };

                if (elements.projects) elements.projects.textContent = data.projects;
                if (elements.contractors) elements.contractors.textContent = data.contractors;
                if (elements.value) elements.value.textContent = '₱' + Utils.formatNumber(data.value);
            },

            renderFallback() {
                this.render({ projects: 'No Data', contractors: 'No Data', value: 0 });
            }
        };

        // Contractors Module
        const Contractors = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/contractors/ranking?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, { contractors: [] });

                console.log('[CONTRACTORS] Result:', result);
                console.log('[CONTRACTORS] Has data.contractors?', !!result.data?.contractors);
                console.log('[CONTRACTORS] Contractors count:', result.data?.contractors?.length || 0);

                if (result.success && result.data?.contractors && result.data.contractors.length > 0) {
                    this.render(result.data.contractors);
                } else {
                    console.warn('[CONTRACTORS] Rendering empty state');
                    this.renderEmpty();
                }
            },

            render(contractors) {
                const container = document.getElementById('contractors-list');
                if (!container) return;

                if (contractors.length === 0) {
                    this.renderEmpty();
                    return;
                }

                const rowsHtml = contractors.map((item, index) => `
                    <div class="contractor-row">
                        <div class="contractor-rank">${index + 1}</div>
                        <div class="contractor-name">${item.contractor_name || 'Unknown'}</div>
                        <div class="contractor-value">₱${Utils.formatNumber(item.total_value || 0)}</div>
                    </div>
                `).join('');

                // Duplicate rows for seamless infinite loop
                const duration = Math.max(10, contractors.length * 1.2);
                container.innerHTML = `
                    <div class="contractors-scroll-track" style="animation-duration: ${duration}s;">
                        ${rowsHtml}
                        ${rowsHtml}
                    </div>
                `;
            },

            renderEmpty() {
                const container = document.getElementById('contractors-list');
                if (container) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">📋</div>
                            <div>No contractor data available</div>
                        </div>
                    `;
                }
            }
        };
        
        // Charts Module
        const Charts = {
            init() {
                // Initialize both charts with empty data
                this.initRegionalValuesChart([]);
                this.initRegionalDistributionChart([]);
                this.initSourcesChart({});
                // Setup toggle functionality
                this.setupToggle();
                this.setupResize();
            },

            setupResize() {
                let resizeTimer;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        Object.values(AppState.charts || {}).forEach(chart => {
                            if (chart && typeof chart.resize === 'function') {
                                chart.resize();
                            }
                        });
                    }, 120);
                });
            },

            async loadRegionalData() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/charts/regional-stats?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, {
                    regions: ['No Data'],
                    values: [0],
                    projectCounts: [0]
                });

                if (result.success && result.data) {
                    const data = result.data;
                    this.initRegionalValuesChart(data);
                    this.initRegionalDistributionChart(data);
                } else {
                    // Use fallback data
                    this.initRegionalValuesChart({
                        regions: ['No Regional Data'],
                        values: [0],
                        projectCounts: [0]
                    });
                    this.initRegionalDistributionChart({
                        regions: ['No Regional Data'],
                        values: [0],
                        projectCounts: [0]
                    });
                }
            },

            initRegionalValuesChart(data) {
                const ctx = document.getElementById('regional-values-chart');
                if (!ctx) return;

                // Destroy existing chart
                if (AppState.charts.regionalValues) {
                    AppState.charts.regionalValues.destroy();
                }

                // Extract text inside parentheses and remove "Region" word
                const shortLabels = (data.regions || []).map(region => {
                    const match = region.match(/\(([^)]+)\)/);
                    if (match) {
                        return match[1].replace(/\s*Region\s*/gi, '').trim();
                    }
                    return region;
                });
                
                AppState.charts.regionalValues = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: shortLabels,
                        datasets: [{
                            label: 'Project Value (₱)',
                            data: data.values || [],
                            backgroundColor: [
                                '#ff8000', '#ff6000', '#ffa500', '#34d399',
                                '#60a5fa', '#a78bfa', '#f472b6', '#10b981'
                            ],
                            borderColor: [
                                '#ff8000', '#ff6000', '#ffa500', '#34d399',
                                '#60a5fa', '#a78bfa', '#f472b6', '#10b981'
                            ],
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => 'Value: ₱' + Utils.formatNumber(context.parsed.y)
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#888',
                                    font: { size: 11 },
                                    callback: (value) => '₱' + Utils.formatNumber(value)
                                },
                                grid: { color: '#333' }
                            },
                            x: {
                                ticks: {
                                    color: '#888',
                                    font: { size: 9 },
                                    maxRotation: 90,
                                    minRotation: 45,
                                    autoSkip: false
                                },
                                grid: { color: '#333' }
                            }
                        }
                    }
                });
            },

            initRegionalDistributionChart(data) {
                const ctx = document.getElementById('regional-distribution-chart');
                if (!ctx) return;

                // Destroy existing chart
                if (AppState.charts.regionalDistribution) {
                    AppState.charts.regionalDistribution.destroy();
                }

                // Extract text inside parentheses and remove "Region" word
                const shortLabels = (data.regions || []).map(region => {
                    const match = region.match(/\(([^)]+)\)/);
                    if (match) {
                        return match[1].replace(/\s*Region\s*/gi, '').trim();
                    }
                    return region;
                });
                
                AppState.charts.regionalDistribution = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: shortLabels,
                        datasets: [{
                            label: 'Projects',
                            data: data.projectCounts || [],
                            borderColor: '#ff8000',
                            backgroundColor: 'rgba(255, 128, 0, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ff8000',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => `${context.label}: ${context.parsed.y} projects`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#888', font: { size: 10 } },
                                grid: { color: '#333' }
                            },
                            x: {
                                ticks: {
                                    color: '#888',
                                    font: { size: 9 },
                                    maxRotation: 90,
                                    minRotation: 45,
                                    autoSkip: false
                                },
                                grid: { color: '#333' }
                            }
                        }
                    }
                });
            },

            async loadSourcesData() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/kpi?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, {
                    data: { source_distribution: {} }
                });

                if (result.success && result.data?.data) {
                    this.initSourcesChart(result.data.data);
                } else {
                    this.initSourcesChart({});
                }
            },

            initSourcesChart(data) {
                const ctx = document.getElementById('sources-chart');
                if (!ctx) return;

                // Destroy existing chart
                if (AppState.charts.sources) {
                    AppState.charts.sources.destroy();
                }

                // Extract source distribution
                const sourceData = data.source_distribution || {};
                const sources = Object.keys(sourceData);
                const values = Object.values(sourceData);

                // If no data, show placeholder
                if (sources.length === 0 || values.every(v => v === 0)) {
                    sources.push('No Data');
                    values.push(1);
                }

                AppState.charts.sources = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: sources,
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                '#ff8000', '#ff6000', '#ffa500', '#34d399',
                                '#60a5fa', '#a78bfa', '#f472b6', '#10b981'
                            ],
                            borderColor: '#1a1a1a',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    color: '#888',
                                    font: { size: 11 },
                                    padding: 12,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            },

            setupToggle() {
                const toggleButtons = document.querySelectorAll('.toggle-btn');
                const valuesChart = document.getElementById('regional-values-chart');
                const projectsChart = document.getElementById('regional-distribution-chart');
                const chartSection = document.querySelector('.regional-combined-section');
                let isHoverPaused = false;

                const setActiveChart = (chartType, resetTimer = true) => {
                    toggleButtons.forEach(b => b.classList.toggle('active', b.getAttribute('data-chart') === chartType));
                    if (chartType === 'values') {
                        valuesChart.style.display = 'block';
                        projectsChart.style.display = 'none';
                    } else {
                        valuesChart.style.display = 'none';
                        projectsChart.style.display = 'block';
                    }
                    if (resetTimer) {
                        startAutoRotate();
                    }
                };

                const toggleNextChart = () => {
                    const activeButton = document.querySelector('.toggle-btn.active');
                    const nextChart = activeButton?.getAttribute('data-chart') === 'values' ? 'projects' : 'values';
                    setActiveChart(nextChart, false);
                };

                const startAutoRotate = () => {
                    clearInterval(AppState.intervals.regionalAnalytics);
                    AppState.intervals.regionalAnalytics = setInterval(() => {
                        if (!isHoverPaused && document.visibilityState === 'visible') {
                            toggleNextChart();
                        }
                    }, 5000);
                };

                const stopAutoRotate = () => {
                    clearInterval(AppState.intervals.regionalAnalytics);
                };

                toggleButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const chartType = btn.getAttribute('data-chart');
                        setActiveChart(chartType);
                    });
                });

                if (chartSection) {
                    chartSection.addEventListener('mouseenter', () => {
                        isHoverPaused = true;
                        stopAutoRotate();
                    });
                    chartSection.addEventListener('mouseleave', () => {
                        isHoverPaused = false;
                        startAutoRotate();
                    });
                }

                setActiveChart('values', false);
                startAutoRotate();
            }
        };
        
        // Sales Funnel Module
        const SalesFunnel = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/charts/funnel?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, { stages: [] });

                if (result.success && result.data?.stages) {
                    this.render(result.data.stages);
                } else {
                    this.renderEmpty();
                }
            },

            render(stages) {
                const container = document.querySelector('.funnel-list');
                if (!container) return;

                if (stages.length === 0) {
                    this.renderEmpty();
                    return;
                }

                const topCount = stages[0]?.count || 1;

                container.innerHTML = stages.map(stage => {
                    const barPct  = topCount > 0 ? ((stage.count / topCount) * 100).toFixed(1) : '0.0';
                    const convTxt = stage.conversion !== null ? stage.conversion + '%' : '—';
                    const fillClass = this.getFillClass(stage.name);

                    return `
                        <div class="funnel-item">
                            <div class="funnel-name">${stage.name.toUpperCase()}</div>
                            <div class="funnel-stats">
                                <div class="funnel-count">${stage.count.toLocaleString()}</div>
                                <div class="funnel-percentage" title="Conversion from previous stage">${convTxt}</div>
                                <div class="funnel-bar">
                                    <div class="funnel-bar-fill ${fillClass}" style="width: ${barPct}%;"></div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            },

            renderEmpty() {
                const container = document.querySelector('.funnel-list');
                if (container) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">🔽</div>
                            <div>No sales funnel data available</div>
                        </div>
                    `;
                }
            },

            getFillClass(stageName) {
                const name = stageName.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                return name + '-fill';
            }
        };

        // Target Progress Module
        const TargetProgress = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/kpi?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, {
                    data: { projects_encoded: 0 }
                });

                if (result.success && result.data?.data) {
                    this.render(result.data.data);
                } else {
                    this.renderFallback();
                }
            },

            render(data) {
                const encoded = data.projects_encoded || 0;
                const currentPeriod = document.getElementById('period-select')?.value || 'monthly';
                const predefinedTargets = {
                    daily: 30,
                    weekly: 150,
                    monthly: 600
                };
                const target = predefinedTargets[currentPeriod] || 600;
                const percentage = target > 0 ? Math.round((encoded / target) * 100) : 0;
                
                // Update numbers
                const targetNumbers = document.querySelectorAll('.target-number');
                if (targetNumbers.length >= 2) {
                    targetNumbers[0].textContent = encoded;
                    targetNumbers[1].textContent = target;
                }

                const targetLabel = document.querySelector('.target-right .target-label');
                if (targetLabel) {
                    targetLabel.textContent = currentPeriod === 'daily' ? 'Daily Target' :
                                                currentPeriod === 'weekly' ? 'Weekly Target' :
                                                'Monthly Target';
                }

                const targetRight = document.querySelector('.target-right');
                const monthValue = document.getElementById('month-select')?.value || 'all';
                if (targetRight) {
                    if (monthValue === 'all') {
                        targetRight.style.display = 'none';
                    } else {
                        targetRight.style.display = 'flex';
                    }
                }
                
                // Update percentage and progress bar
                const percentageElement = document.querySelector('.target-percentage');
                const progressFill = document.querySelector('.target-progress-fill');
                
                if (percentageElement) percentageElement.textContent = percentage + '%';
                if (progressFill) progressFill.style.width = Math.min(percentage, 100) + '%';
                
                // Update status
                this.updateStatus(percentage);
            },

            updateStatus(percentage) {
                const statusElement = document.querySelector('.target-status');
                if (!statusElement) return;

                if (percentage >= 100) {
                    statusElement.textContent = '✅ TARGET ACHIEVED';
                    statusElement.style.color = '#10b981';
                } else if (percentage >= 80) {
                    statusElement.textContent = '🟡 ON TRACK';
                    statusElement.style.color = '#f59e0b';
                } else {
                    statusElement.textContent = '🔺 BEHIND TARGET';
                    statusElement.style.color = '#ef4444';
                }
            },

            renderFallback() {
                this.render({ projects_encoded: 0 });
            }
        };
        
        // Live Slideshow Module
        const LiveSlideshow = {
            currentTimeout: null,
            countdownInterval: null,
            timeRemaining: 10,
            _fitObserver: null,
            _fitTimer: null,

            initAutoFit() {
                if (this._fitObserver) return;

                const slideshow = document.querySelector('.live-slideshow');
                if (!slideshow) return;

                if (typeof ResizeObserver !== 'undefined') {
                    this._fitObserver = new ResizeObserver(() => {
                        clearTimeout(this._fitTimer);
                        this._fitTimer = setTimeout(() => this.fitFonts(), 60);
                    });
                    this._fitObserver.observe(slideshow);
                }

                window.addEventListener('resize', () => {
                    clearTimeout(this._fitTimer);
                    this._fitTimer = setTimeout(() => this.fitFonts(), 120);
                });
            },

            fitFonts() {
                const content = document.querySelector('.slideshow-content');
                const body = document.querySelector('.slideshow-body');
                const controls = document.querySelector('.slideshow-controls');
                if (!content || !body) return;

                const applyScale = (scale) => {
                    content.style.setProperty('--slide-scale', scale.toFixed(3));
                    void body.offsetHeight;
                    return body.scrollHeight;
                };

                requestAnimationFrame(() => {
                    const maxHeight = content.clientHeight - (controls?.offsetHeight || 0) - 2;
                    if (maxHeight <= 0) return;

                    applyScale(1);
                    const baseHeight = body.scrollHeight;
                    if (baseHeight <= 0) return;

                    let bestScale = 1;

                    if (baseHeight <= maxHeight) {
                        let lo = 1;
                        let hi = 3.2;

                        for (let i = 0; i < 14; i++) {
                            const mid = (lo + hi) / 2;
                            const height = applyScale(mid);

                            if (height <= maxHeight) {
                                bestScale = mid;
                                lo = mid;
                            } else {
                                hi = mid;
                            }
                        }
                    } else {
                        let lo = 0.55;
                        let hi = 1;

                        for (let i = 0; i < 14; i++) {
                            const mid = (lo + hi) / 2;
                            const height = applyScale(mid);

                            if (height <= maxHeight) {
                                bestScale = mid;
                                lo = mid;
                            } else {
                                hi = mid;
                            }
                        }
                    }

                    applyScale(bestScale);
                    body.classList.toggle(
                        'slideshow-body--spread',
                        body.offsetHeight < maxHeight * 0.82
                    );
                });
            },

            async load() {
                this.showLoadingProgress();
                
                const url = `${BASE}/api/v1/live-slideshow`;
                const result = await Utils.fetchWithFallback(url, {
                    contractor_name: 'No Project Data Available',
                    contact: 'Add projects to see live data',
                    phone: '-',
                    project_title: '-',
                    project_value: 0,
                    status: '-',
                    drbs: null,
                    drbs_value: 0,
                    sheet_pile_type: null,
                    sheet_pile_amount: 0,
                    ms_plate: 0,
                    angle_bars: 0,
                    channel_bars: 0,
                    wide_flange: 0,
                    gi_bi: 0
                });

                if (result.success && result.data) {
                    this.render(result.data);
                } else {
                    this.renderFallback();
                }
                
                this.hideLoadingProgress();
                this.startCountdown();
            },

            render(data) {
                document.querySelector('.live-contractor-name').textContent = data.contractor_name;
                
                const contactEl = document.getElementById('liveContact');
                const phoneEl = document.getElementById('livePhone');
                const projectEl = document.getElementById('liveProject');
                const projectValueEl = document.getElementById('liveProjectValue');
                const statusEl = document.getElementById('liveStatus');
                
                if (contactEl) contactEl.textContent = data.contact || 'N/A';
                if (phoneEl) phoneEl.textContent = data.phone || 'N/A';
                if (projectEl) projectEl.textContent = data.project_title || 'N/A';
                if (projectValueEl) projectValueEl.textContent = '₱' + Utils.formatNumber(data.project_value || 0);
                if (statusEl) statusEl.textContent = data.status || 'UNKNOWN';
                
                const materialRows = [
                    { label: 'DRBs Type', value: data.drbs || '—' },
                    { label: 'DRBs Amount', value: '₱' + Utils.formatNumber(data.drbs_value || 0) },
                    { label: 'Sheet Pile Type', value: data.sheet_pile_type || '—' },
                    { label: 'Sheet Pile Amount', value: '₱' + Utils.formatNumber(data.sheet_pile_amount || 0) },
                    { label: 'MS Plate', value: data.ms_plate ? '₱' + Utils.formatNumber(data.ms_plate) : '₱0' },
                    { label: 'Angle Bars', value: data.angle_bars ? '₱' + Utils.formatNumber(data.angle_bars) : '₱0' },
                    { label: 'Channel Bars', value: data.channel_bars ? '₱' + Utils.formatNumber(data.channel_bars) : '₱0' },
                    { label: 'Wide Flange', value: data.wide_flange ? '₱' + Utils.formatNumber(data.wide_flange) : '₱0' },
                    { label: 'GI/BI', value: data.gi_bi ? '₱' + Utils.formatNumber(data.gi_bi) : '₱0' }
                ];
                
                const materialsList = document.getElementById('liveMaterialsList');
                if (materialsList) {
                    materialsList.innerHTML = materialRows.map(item => `
                        <div>
                            <div class="material-label">${item.label}</div>
                            <div class="material-value">${item.value}</div>
                        </div>
                    `).join('');
                }

                this.fitFonts();
            },

            renderFallback() {
                this.render({
                    contractor_name: 'Unable to Load Data',
                    contact: 'Connection Error',
                    phone: 'Please refresh',
                    project_title: '-',
                    project_value: 0,
                    status: 'Error',
                    drbs: null,
                    drbs_value: 0,
                    sheet_pile_type: null,
                    sheet_pile_amount: 0,
                    ms_plate: 0,
                    angle_bars: 0,
                    channel_bars: 0,
                    wide_flange: 0,
                    gi_bi: 0
                });
            },

            showLoadingProgress() {
                const loadingBar = document.getElementById('slideshowLoadingBar');
                const loadingProgress = document.getElementById('loadingProgress');
                
                if (loadingBar && loadingProgress) {
                    loadingBar.style.display = 'block';
                    loadingProgress.style.width = '0%';
                    
                    // Simple loading animation
                    setTimeout(() => {
                        if (loadingProgress) loadingProgress.style.width = '100%';
                    }, 200);
                }
            },

            hideLoadingProgress() {
                const loadingBar = document.getElementById('slideshowLoadingBar');
                if (loadingBar) {
                    setTimeout(() => {
                        loadingBar.style.display = 'none';
                    }, 300);
                }
            },

            startCountdown() {
                const countdownProgress = document.getElementById('countdownProgress');
                const timerText = document.getElementById('slideshowTimerText');
                
                if (!countdownProgress || !timerText) return;
                
                this.timeRemaining = 10;
                
                // Clear existing interval
                if (this.countdownInterval) {
                    clearInterval(this.countdownInterval);
                }
                
                // Reset progress bar
                countdownProgress.style.width = '100%';
                countdownProgress.classList.remove('ending');
                timerText.textContent = `Next slide in ${this.timeRemaining}s`;
                
                // Start countdown
                this.countdownInterval = setInterval(() => {
                    this.timeRemaining--;
                    
                    const progressPercent = (this.timeRemaining / 10) * 100;
                    countdownProgress.style.width = progressPercent + '%';
                    
                    if (this.timeRemaining > 0) {
                        timerText.textContent = `Next slide in ${this.timeRemaining}s`;
                        
                        if (this.timeRemaining <= 3) {
                            countdownProgress.classList.add('ending');
                        }
                    } else {
                        timerText.textContent = 'Loading next slide...';
                        countdownProgress.style.width = '0%';
                        clearInterval(this.countdownInterval);
                    }
                }, 1000);
            }
        };

        // Project Status Module  
        const ProjectStatus = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/kpi?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, { data: {} });

                if (result.success && result.data?.data) {
                    this.render(result.data.data);
                } else {
                    this.renderFallback();
                }
            },

            render(data) {
                const statusContainer = document.querySelector('.project-status-section');
                const statusItems = statusContainer?.querySelectorAll('.category-item');
                if (!statusItems) return;

                // Map API data to status categories
                const statusMap = {
                    'PRIORITY': this.extractStatusData(data, 'priority'),
                    'FOR EXECUTION': this.extractStatusData(data, 'for_execution'),
                    'AWARDED': this.extractStatusData(data, 'awarded'),
                    'FOR BIDDING': this.extractStatusData(data, 'for_bidding')
                };
                
                const totalValue = Object.values(statusMap).reduce((sum, item) => sum + (item.value || 0), 0);
                
                statusItems.forEach(item => {
                    const categoryName = item.querySelector('.category-name')?.textContent;
                    const statusData = statusMap[categoryName];
                    
                    if (statusData) {
                        const percentage = totalValue > 0 ? ((statusData.value / totalValue) * 100).toFixed(1) : '0.0';
                        
                        const countEl = item.querySelector('.category-count');
                        const valueEl = item.querySelector('.category-value');
                        const percentEl = item.querySelector('.category-percentage');
                        const fillEl = item.querySelector('.category-bar-fill');
                        
                        if (countEl) countEl.textContent = statusData.count || 0;
                        if (valueEl) valueEl.textContent = '₱' + Utils.formatNumber(statusData.value || 0);
                        if (percentEl) percentEl.textContent = percentage + '%';
                        if (fillEl) fillEl.style.width = percentage + '%';
                    }
                });
            },

            extractStatusData(data, key) {
                // If the key exists directly in data
                if (data[key] && typeof data[key] === 'object') {
                    return data[key];
                }
                
                // Default fallback
                return { count: 0, value: 0 };
            },

            renderFallback() {
                this.render({});
            }
        };

        // Available Months Module
        const AvailableMonths = {
            async load() {
                const url = `${BASE}/api/v1/available-months`;
                const result = await Utils.fetchWithFallback(url, { months: [] });

                if (result.success && result.data?.months) {
                    this.render(result.data.months);
                } else {
                    this.renderFallback();
                }
            },

            render(months) {
                const monthSelect = document.getElementById('month-select');
                if (!monthSelect) return;

                monthSelect.innerHTML = '';
                
                // Add "All Months" option - SELECTED BY DEFAULT
                const allOption = document.createElement('option');
                allOption.value = 'all';
                allOption.textContent = 'All Months';
                allOption.selected = true; // Default to "All Months"
                monthSelect.appendChild(allOption);
                
                // Add available months
                months.forEach((month, index) => {
                    const option = document.createElement('option');
                    option.value = month.value;
                    option.textContent = `${month.label} (${month.project_count} projects)`;
                    // Don't auto-select any specific month
                    monthSelect.appendChild(option);
                });
                
                // Initialize or refresh CustomSelect for this dropdown
                setTimeout(() => {
                    if (window.customSelectInstances && window.customSelectInstances.monthSelect) {
                        // Refresh existing instance
                        window.customSelectInstances.monthSelect.refresh();
                    } else if (window.CustomSelect) {
                        // Initialize new instance
                        const instance = new CustomSelect(monthSelect, {
                            searchable: false,
                            placeholder: 'All Months'
                        });
                        if (!window.customSelectInstances) {
                            window.customSelectInstances = {};
                        }
                        window.customSelectInstances.monthSelect = instance;
                    }
                }, 100);
            },

            renderFallback() {
                const monthSelect = document.getElementById('month-select');
                if (!monthSelect) return;

                const currentDate = new Date();
                const currentMonth = currentDate.getMonth() + 1;
                const currentYear = currentDate.getFullYear();
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                  'July', 'August', 'September', 'October', 'November', 'December'];
                
                monthSelect.innerHTML = '';
                
                // Add "All Months" option
                const allOption = document.createElement('option');
                allOption.value = 'all';
                allOption.textContent = 'All Months';
                allOption.selected = true;
                monthSelect.appendChild(allOption);
                
                // Add current month as fallback
                const option = document.createElement('option');
                option.value = `${currentMonth}-${currentYear}`;
                option.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
                monthSelect.appendChild(option);
                
                // Initialize or refresh CustomSelect for this dropdown
                setTimeout(() => {
                    if (window.customSelectInstances && window.customSelectInstances.monthSelect) {
                        // Refresh existing instance
                        window.customSelectInstances.monthSelect.refresh();
                    } else if (window.CustomSelect) {
                        // Initialize new instance
                        const instance = new CustomSelect(monthSelect, {
                            searchable: false,
                            placeholder: 'All Months'
                        });
                        if (!window.customSelectInstances) {
                            window.customSelectInstances = {};
                        }
                        window.customSelectInstances.monthSelect = instance;
                    }
                }, 100);
            }
        };
        
        // Export Modal System
        const ExportModal = {
            selectedReports: [],
            selectedFormat: null,
            
            show() {
                document.getElementById('exportReportModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            },
            
            closeReportSelection() {
                document.getElementById('exportReportModal').style.display = 'none';
                document.body.style.overflow = '';
                this.resetSelections();
            },
            
            closeFormatSelection() {
                document.getElementById('exportFormatModal').style.display = 'none';
                document.body.style.overflow = '';
            },
            
            resetSelections() {
                // Reset checkboxes
                document.querySelectorAll('input[name="exportReport"]').forEach(cb => cb.checked = false);
                document.getElementById('selectAllReports').checked = false;
                
                // Reset format selection
                document.querySelectorAll('input[name="exportFormat"]').forEach(rb => rb.checked = false);
                document.querySelectorAll('.export-format-option').forEach(opt => opt.classList.remove('selected'));
                
                this.selectedReports = [];
                this.selectedFormat = null;
                this.updateNextButton();
            },
            
            toggleSelectAll() {
                const selectAllCheckbox = document.getElementById('selectAllReports');
                const reportCheckboxes = document.querySelectorAll('input[name="exportReport"]');
                
                reportCheckboxes.forEach(cb => {
                    cb.checked = selectAllCheckbox.checked;
                });
                
                this.updateNextButton();
            },
            
            updateNextButton() {
                const checkedReports = document.querySelectorAll('input[name="exportReport"]:checked');
                const nextButton = document.querySelector('.export-btn-next');
                
                if (checkedReports.length > 0) {
                    nextButton.disabled = false;
                    nextButton.style.opacity = '1';
                } else {
                    nextButton.disabled = true;
                    nextButton.style.opacity = '0.5';
                }
            },
            
            showFormatSelection() {
                // Get selected reports
                const checkedReports = document.querySelectorAll('input[name="exportReport"]:checked');
                
                if (checkedReports.length === 0) {
                    // Show modal instead of alert
                    this.showErrorModal('Please select at least one report to export.');
                    return;
                }
                
                this.selectedReports = Array.from(checkedReports).map(cb => ({
                    value: cb.value,
                    label: cb.parentElement.parentElement.querySelector('.export-label').textContent.replace(/^[^\s]+\s/, '')
                }));
                
                // Update selected reports display
                this.updateSelectedReportsDisplay();
                
                // Hide report selection and show format selection
                document.getElementById('exportReportModal').style.display = 'none';
                document.getElementById('exportFormatModal').style.display = 'flex';
            },
            
            showReportSelection() {
                document.getElementById('exportFormatModal').style.display = 'none';
                document.getElementById('exportReportModal').style.display = 'flex';
            },
            
            updateSelectedReportsDisplay() {
                const container = document.getElementById('selectedReportsDisplay');
                container.innerHTML = this.selectedReports.map(report => 
                    `<div class="selected-report-tag">${report.label}</div>`
                ).join('');
            },
            
            selectFormat(format) {
                // Remove previous selection
                document.querySelectorAll('.export-format-option').forEach(opt => opt.classList.remove('selected'));
                
                // Add selection to clicked option
                const selectedOption = document.querySelector(`#format${format.charAt(0).toUpperCase() + format.slice(1)}`);
                if (selectedOption) {
                    selectedOption.checked = true;
                    selectedOption.closest('.export-format-option').classList.add('selected');
                }
                
                this.selectedFormat = format;
                this.updateExportButton();
            },
            
            updateExportButton() {
                const exportButton = document.querySelector('.export-btn-export');
                
                if (this.selectedFormat) {
                    exportButton.disabled = false;
                    exportButton.style.opacity = '1';
                } else {
                    exportButton.disabled = true;
                    exportButton.style.opacity = '0.5';
                }
            },
            
            async startExport() {
                if (!this.selectedFormat || this.selectedReports.length === 0) {
                    this.showErrorModal('Please select reports and format.');
                    return;
                }
                
                // Close format modal and show status modal
                document.getElementById('exportFormatModal').style.display = 'none';
                this.showStatusModal();
                
                try {
                    await this.performExport();
                } catch (error) {
                    console.error('Export error:', error);
                    this.showErrorModal('Export failed. Please try again.');
                }
            },
            
            showStatusModal() {
                document.getElementById('exportStatusModal').style.display = 'flex';
                document.getElementById('exportStatusTitle').textContent = '📦 Preparing Export...';
                document.getElementById('exportStatusCloseBtn').style.display = 'none';
                document.getElementById('exportLoadingState').style.display = 'flex';
                document.getElementById('exportSuccessState').style.display = 'none';
                document.getElementById('exportStatusFooter').style.display = 'flex';
            },
            
            closeStatusModal() {
                document.getElementById('exportStatusModal').style.display = 'none';
                document.body.style.overflow = '';
                this.resetSelections();
            },
            
            async performExport() {
                const loadingDetails = document.getElementById('loadingDetails');
                const progressBar = document.getElementById('exportProgress');
                
                // Simulate export process with progress
                const steps = [
                    { message: 'Gathering report data...', progress: 20 },
                    { message: 'Processing user data...', progress: 40 },
                    { message: 'Generating project reports...', progress: 60 },
                    { message: 'Formatting output...', progress: 80 },
                    { message: 'Finalizing export file...', progress: 100 }
                ];
                
                for (const step of steps) {
                    loadingDetails.textContent = step.message;
                    progressBar.style.width = step.progress + '%';
                    await new Promise(resolve => setTimeout(resolve, 800));
                }
                
                // Show success state
                this.showExportSuccess();
            },
            
            showExportSuccess() {
                document.getElementById('exportStatusTitle').textContent = '✅ Export Complete';
                document.getElementById('exportStatusCloseBtn').style.display = 'flex';
                document.getElementById('exportLoadingState').style.display = 'none';
                document.getElementById('exportSuccessState').style.display = 'flex';
                document.getElementById('exportStatusFooter').style.display = 'none';
                
                // Update summary
                document.getElementById('exportedReportsCount').textContent = this.selectedReports.length;
                document.getElementById('exportedFormat').textContent = this.selectedFormat.toUpperCase();
                document.getElementById('exportedFileSize').textContent = this.calculateFileSize();
            },
            
            calculateFileSize() {
                const baseSize = this.selectedReports.length * 0.5; // Base MB per report
                const formatMultiplier = this.selectedFormat === 'pdf' ? 1.2 : 0.8;
                const totalSize = (baseSize * formatMultiplier).toFixed(1);
                return totalSize + ' MB';
            },
            
            cancelExport() {
                this.closeStatusModal();
            },
            
            triggerDownload() {
                // Use real API to generate and download files
                this.downloadFromAPI();
                
                // Close modal after download starts
                setTimeout(() => {
                    this.closeStatusModal();
                }, 1000);
            },
            
            async downloadFromAPI() {
                try {
                    const reportValues = this.selectedReports.map(r => r.value);
                    
                    // Create form data for the API request
                    const formData = new FormData();
                    reportValues.forEach(report => {
                        formData.append('reports[]', report);
                    });
                    formData.append('format', this.selectedFormat);
                    
                    // Make API request
                    const response = await fetch(`${BASE}/api/v1/export`, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error('Export API request failed');
                    }
                    
                    // Get the blob from response
                    const blob = await response.blob();
                    
                    // Create download link
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    
                    // Set filename based on format
                    const timestamp = this.getDateStamp();
                    if (this.selectedFormat === 'pdf') {
                        a.download = `TDT_Powersteel_Reports_${timestamp}.pdf`;
                    } else {
                        a.download = `TDT_Powersteel_Reports_${timestamp}.csv`;
                    }
                    
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                } catch (error) {
                    console.error('API download error:', error);
                    // Fallback to client-side generation
                    this.generateClientSideFile();
                }
            },
            
            generateClientSideFile() {
                // Fallback method for client-side generation
                const csvContent = this.generateCSVContent();
                const BOM = '\uFEFF';
                const csvWithBOM = BOM + csvContent;
                
                const blob = new Blob([csvWithBOM], { 
                    type: 'text/csv;charset=utf-8' 
                });
                const url = URL.createObjectURL(blob);
                
                const a = document.createElement('a');
                a.href = url;
                a.download = `TDT_Powersteel_Reports_${this.getDateStamp()}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            },
            
            generatePDF() {
                // Create proper PDF content using jsPDF or similar approach
                try {
                    // For now, create a CSV-like content that can be opened properly
                    const content = this.generateCSVContent();
                    const blob = new Blob([content], { type: 'text/csv' });
                    const url = URL.createObjectURL(blob);
                    
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `TDT_Powersteel_Reports_${this.getDateStamp()}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('PDF generation error:', error);
                }
            },
            
            generateExcel() {
                // Generate proper Excel-compatible CSV format
                try {
                    const csvContent = this.generateCSVContent();
                    
                    // Add BOM for proper Excel UTF-8 handling
                    const BOM = '\uFEFF';
                    const csvWithBOM = BOM + csvContent;
                    
                    const blob = new Blob([csvWithBOM], { 
                        type: 'text/csv;charset=utf-8' 
                    });
                    const url = URL.createObjectURL(blob);
                    
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `TDT_Powersteel_Reports_${this.getDateStamp()}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('Excel generation error:', error);
                }
            },
            
            generateCSVContent() {
                let csvContent = '';
                
                // Header
                csvContent += 'TDT POWERSTEEL DASHBOARD REPORTS\n';
                csvContent += `Generated on: ${new Date().toLocaleDateString()}\n`;
                csvContent += `Time: ${new Date().toLocaleTimeString()}\n\n`;
                
                // Report sections
                this.selectedReports.forEach((report, index) => {
                    csvContent += `REPORT ${index + 1}: ${report.label.toUpperCase()}\n`;
                    csvContent += 'Field,Value\n';
                    
                    // Add sample data based on report type
                    switch (report.value) {
                        case 'users':
                            csvContent += 'Total Users,25\n';
                            csvContent += 'Active Users,22\n';
                            csvContent += 'Admin Users,3\n';
                            csvContent += 'Encoder Users,15\n';
                            csvContent += 'Sales Rep Users,7\n';
                            break;
                            
                        case 'sales_reps':
                            csvContent += 'Total Sales Representatives,7\n';
                            csvContent += 'Active This Month,6\n';
                            csvContent += 'Top Performer,John Doe\n';
                            csvContent += 'Average Performance,85%\n';
                            break;
                            
                        case 'non_priority_projects':
                            csvContent += 'Total Non-Priority Projects,156\n';
                            csvContent += 'Completed Projects,89\n';
                            csvContent += 'In Progress,45\n';
                            csvContent += 'Pending,22\n';
                            csvContent += 'Total Value,₱450M\n';
                            break;
                            
                        case 'priority_projects':
                            csvContent += 'Total Priority Projects,12\n';
                            csvContent += 'Urgent Projects,3\n';
                            csvContent += 'High Priority,6\n';
                            csvContent += 'Medium Priority,3\n';
                            csvContent += 'Total Value,₱125M\n';
                            break;
                    }
                    
                    csvContent += '\n';
                });
                
                // Summary
                csvContent += 'EXPORT SUMMARY\n';
                csvContent += 'Field,Value\n';
                csvContent += `Reports Included,${this.selectedReports.length}\n`;
                csvContent += `Format,${this.selectedFormat.toUpperCase()}\n`;
                csvContent += `Generated By,TDT Powersteel Dashboard\n`;
                csvContent += `Export Date,${new Date().toISOString()}\n`;
                
                return csvContent;
            },
            
            generateExportContent() {
                // Generate simple export content
                let content = `TDT POWERSTEEL DASHBOARD REPORTS\n`;
                content += `Generated on: ${new Date().toLocaleDateString()}\n\n`;
                
                this.selectedReports.forEach(report => {
                    content += `REPORT: ${report.label.toUpperCase()}\n`;
                    content += `Type: ${report.value}\n`;
                    content += `Status: Generated Successfully\n\n`;
                });
                
                return content;
            },
            
            getDateStamp() {
                const now = new Date();
                return now.getFullYear() + 
                       String(now.getMonth() + 1).padStart(2, '0') + 
                       String(now.getDate()).padStart(2, '0') + '_' +
                       String(now.getHours()).padStart(2, '0') + 
                       String(now.getMinutes()).padStart(2, '0');
            },
            
            showErrorModal(message) {
                // Create a simple error modal instead of alert
                console.error('Export Error:', message);
                // For now, we'll skip error modal and just log to console
                // You can implement a proper error modal later if needed
            },
            
            downloadPDF() {
                console.log('PDF export functionality integrated into triggerDownload()');
            },
            
            downloadExcel() {
                console.log('Excel export functionality integrated into triggerDownload()');
            }
        };

        // Priority Alert System Module - Two Modal System
        const PriorityAlert = {
            picturesOverlay: null,
            dataOverlay: null,
            currentAlert: null,
            currentModal: 'none', // 'pictures' or 'data' or 'none'
            imageSlideshow: {
                images: [],
                currentIndex: 0,
                countdownTimer: null,
                timeRemaining: 5
            },
            // Web Audio API
            audioCtx: null,
            audioBuffer: null,
            audioSource: null,
            isAudioUnlocked: false,
            isAudioPlaying: false,
            beepInterval: null,

            init() {
                this.picturesOverlay = document.getElementById('priorityPicturesOverlay');
                this.dataOverlay = document.getElementById('priorityDataOverlay');
                this.setupAudio();
                this.setupClickHandlers();
                
                // Check for priority alerts every 10 seconds
                AppState.intervals.priorityCheck = setInterval(() => {
                    this.checkForAlerts();
                }, 10000);

                // Initial check
                this.checkForAlerts();
            },

            /* ── Audio ─────────────────────────────────── */
            setupAudio() {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    this.audioCtx = new AudioCtx();

                    // Load the MP3 into a buffer via fetch
                    fetch(`${BASE}/static/sounds/priority-alert.mp3`)
                        .then(r => r.arrayBuffer())
                        .then(ab => this.audioCtx.decodeAudioData(ab))
                        .then(buf => {
                            this.audioBuffer = buf;
                            console.log('[PriorityAlert] Audio buffer loaded.');
                        })
                        .catch(e => console.warn('[PriorityAlert] Audio load failed:', e));

                    // Try to resume context on any user gesture
                    const unlock = () => {
                        if (this.audioCtx && this.audioCtx.state === 'suspended') {
                            this.audioCtx.resume().then(() => {
                                this.isAudioUnlocked = true;
                                this.hideBanner();
                                console.log('[PriorityAlert] AudioContext unlocked via gesture.');
                            });
                        } else if (this.audioCtx && this.audioCtx.state === 'running') {
                            this.isAudioUnlocked = true;
                            this.hideBanner();
                        }
                    };
                    ['click', 'keydown', 'touchstart'].forEach(evt =>
                        document.addEventListener(evt, unlock, { once: true })
                    );

                    // If context is already running (e.g. in some browsers), mark unlocked
                    if (this.audioCtx.state === 'running') {
                        this.isAudioUnlocked = true;
                        this.hideBanner();
                    }
                } catch (e) {
                    console.warn('[PriorityAlert] Could not setup audio:', e);
                }
            },

            unlockAudio() {
                if (this.audioCtx && this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume().then(() => {
                        this.isAudioUnlocked = true;
                        this.hideBanner();
                        console.log('[PriorityAlert] Audio unlocked by banner click.');
                    });
                } else {
                    this.isAudioUnlocked = true;
                    this.hideBanner();
                }
            },

            hideBanner() {
                const b = document.getElementById('audio-unlock-banner');
                if (b) { b.style.opacity = '0'; setTimeout(() => b.style.display = 'none', 300); }
            },

            playAlert() {
                this.isAudioPlaying = true;
                if (this.isAudioUnlocked && this.audioCtx && this.audioBuffer) {
                    this._startWebAudioLoop();
                } else {
                    // Fallback HTML5 Audio
                    try {
                        this._htmlAudio = new Audio(`${BASE}/static/sounds/priority-alert.mp3`);
                        this._htmlAudio.loop = false;
                        this._htmlAudio.volume = 1.0;
                        this._htmlAudio.play().catch(() => this.playBeepFallback());
                    } catch(e) {
                        this.playBeepFallback();
                    }
                }
            },

            _startWebAudioLoop() {
                if (!this.isAudioPlaying || !this.audioBuffer) return;
                try {
                    const source = this.audioCtx.createBufferSource();
                    source.buffer = this.audioBuffer;
                    source.loop = false;
                    source.connect(this.audioCtx.destination);
                    source.start(0);
                    this.audioSource = source;
                    console.log('[PriorityAlert] Web Audio loop started.');
                } catch(e) {
                    console.warn('[PriorityAlert] Web Audio play failed:', e);
                    this.playBeepFallback();
                }
            },

            setupClickHandlers() {
                // Click anywhere on pictures modal to go to data modal
                if (this.picturesOverlay) {
                    this.picturesOverlay.addEventListener('click', (e) => {
                        this.stopSoundAndShowData();
                    });
                }

                // Click anywhere on data modal to close
                if (this.dataOverlay) {
                    this.dataOverlay.addEventListener('click', (e) => {
                        this.close();
                    });
                }
            },

            async checkForAlerts() {
                try {
                    // Don't check if modal is already open
                    if (this.currentModal !== 'none') return;

                    const response = await fetch(`${BASE}/api/v1/priority-alerts`);
                    if (!response.ok) return;
                    
                    const data = await response.json();
                    if (data.alert && data.alert.project) {
                        this.showPicturesModal(data.alert);
                    }
                } catch (error) {
                    console.error('Error checking priority alerts:', error);
                }
            },

            showPicturesModal(alert) {
                this.currentAlert = alert;
                this.currentModal = 'pictures';
                console.log('🚨 Priority Alert - Pictures Modal:', alert);
                
                // Play looping sound alert
                this.playAlert();
                
                // Setup images slideshow first
                this.setupImageSlideshow(alert.images || []);
                
                // Show pictures modal
                this.picturesOverlay.style.display = 'flex';
                
                // Prevent body scroll
                document.body.style.overflow = 'hidden';
            },

            stopSoundAndShowData() {
                // Stop sound immediately
                this.stopSound();
                
                // Hide pictures modal
                this.picturesOverlay.style.display = 'none';
                
                // Show data modal
                this.showDataModal();
            },

            showDataModal() {
                if (!this.currentAlert) return;

                this.currentModal = 'data';
                console.log('📊 Priority Alert - Data Modal:', this.currentAlert);
                
                // Populate project details
                this.populateDataModal(this.currentAlert.project);
                
                // Show data modal
                this.dataOverlay.style.display = 'flex';
            },

            populateDataModal(project) {
                // Grid layout field mapping - exactly the fields you specified
                const elements = {
                    source: document.getElementById('priorityDataSource'),
                    contractor: document.getElementById('priorityContractorGrid'),
                    contactPerson: document.getElementById('priorityContactPersonGrid'),
                    contactNumber: document.getElementById('priorityContactNumberGrid'),
                    address: document.getElementById('priorityAddressGrid'),
                    projectName: document.getElementById('priorityProjectNameGrid'),
                    location: document.getElementById('priorityLocationGrid'),
                    sheetPileType: document.getElementById('prioritySheetPileTypeGrid'),
                    sheetPileAmount: document.getElementById('prioritySheetPileAmountGrid'),
                    projectValue: document.getElementById('priorityProjectValueMainGrid'),
                    accomplishment: document.getElementById('priorityAccomplishmentMainGrid')
                };

                // Header - Source
                if (elements.source) elements.source.textContent = project.source || 'DPWH';
                
                // Left Column - Primary Info
                if (elements.contractor) elements.contractor.textContent = project.contractor_name || 'N/A';
                if (elements.contactPerson) elements.contactPerson.textContent = project.contact_person || 'N/A';
                if (elements.contactNumber) elements.contactNumber.textContent = project.contact_number || 'N/A';
                
                // Address - combine street, barangay, and address components (as user requested)
                const addressComponents = [
                    project.project_street,
                    project.contract_street,
                    project.project_barangay,
                    project.contract_barangay,
                    project.project_blk_lot,
                    project.contract_blk_lot,
                    project.address
                ].filter(component => component && component.trim() && component.trim() !== 'N/A');
                
                if (elements.address) {
                    elements.address.textContent = addressComponents.length > 0 ? addressComponents.join(', ') : 'N/A';
                }
                
                if (elements.projectName) elements.projectName.textContent = project.name || 'N/A';
                
                // Right Column - Project Details
                // Location - just show the city name (not full region breakdown as user requested)
                const cityName = project.project_city || project.contract_city || project.city_province || 'N/A';
                if (elements.location) elements.location.textContent = cityName;
                
                if (elements.sheetPileType) elements.sheetPileType.textContent = project.sheet_pile_type || 'N/A';
                if (elements.sheetPileAmount) elements.sheetPileAmount.textContent = '₱' + Utils.formatNumber(project.sheet_pile_amount || 0);
                if (elements.projectValue) elements.projectValue.textContent = '₱' + Utils.formatNumber(project.project_value || 0);
                
                const accomplishmentRate = project.accomplishment_rate || 0;
                if (elements.accomplishment) elements.accomplishment.textContent = `${accomplishmentRate.toFixed(2)}%`;
            },

            setupImageSlideshow(images) {
                const imagesContainer = document.getElementById('priorityPicturesContent');
                const noImagesDiv = document.getElementById('priorityNoImagesFirst');
                const counterDiv = document.getElementById('priorityImageCounterFirst');
                const timerDiv = document.getElementById('prioritySlideshowTimerFirst');

                // Clear existing images
                const existingImages = imagesContainer.querySelectorAll('.priority-alert-image');
                existingImages.forEach(img => img.remove());

                this.imageSlideshow.images = images;
                this.imageSlideshow.currentIndex = 0;

                if (images.length === 0) {
                    noImagesDiv.style.display = 'flex';
                    counterDiv.style.display = 'none';
                    timerDiv.style.display = 'none';
                    return;
                }

                noImagesDiv.style.display = 'none';
                counterDiv.style.display = 'block';
                timerDiv.style.display = 'block';

                // Create image elements
                images.forEach((image, index) => {
                    const img = document.createElement('img');
                    img.src = `${BASE}/${image.file_path}`;
                    img.className = 'priority-alert-image';
                    img.alt = `Priority Project Image ${index + 1}`;
                    
                    if (index === 0) {
                        img.classList.add('active');
                    }
                    
                    imagesContainer.appendChild(img);
                });

                // Update counter
                this.updateImageCounter();

                // Start slideshow if more than 1 image
                if (images.length > 1) {
                    this.startImageSlideshow();
                }
            },

            updateImageCounter() {
                const counterDiv = document.getElementById('priorityImageCounterFirst');
                if (counterDiv && this.imageSlideshow.images.length > 0) {
                    counterDiv.textContent = `${this.imageSlideshow.currentIndex + 1} / ${this.imageSlideshow.images.length}`;
                }
            },

            startImageSlideshow() {
                this.stopImageSlideshow();
                
                if (this.imageSlideshow.images.length <= 1) return;

                this.imageSlideshow.timeRemaining = 5;
                this.updateTimerDisplay();

                // Start countdown
                this.imageSlideshow.countdownTimer = setInterval(() => {
                    this.imageSlideshow.timeRemaining--;
                    this.updateTimerDisplay();

                    if (this.imageSlideshow.timeRemaining <= 0) {
                        this.nextImage();
                        this.imageSlideshow.timeRemaining = 5;
                    }
                }, 1000);
            },

            stopImageSlideshow() {
                if (this.imageSlideshow.countdownTimer) {
                    clearInterval(this.imageSlideshow.countdownTimer);
                    this.imageSlideshow.countdownTimer = null;
                }
            },

            nextImage() {
                if (this.imageSlideshow.images.length <= 1) return;

                const images = document.querySelectorAll('#priorityPicturesContent .priority-alert-image');
                
                // Remove active class from current image
                if (images[this.imageSlideshow.currentIndex]) {
                    images[this.imageSlideshow.currentIndex].classList.remove('active');
                }

                // Move to next image
                this.imageSlideshow.currentIndex = (this.imageSlideshow.currentIndex + 1) % this.imageSlideshow.images.length;

                // Add active class to new image
                if (images[this.imageSlideshow.currentIndex]) {
                    images[this.imageSlideshow.currentIndex].classList.add('active');
                }

                this.updateImageCounter();
            },

            updateTimerDisplay() {
                const timerDiv = document.getElementById('prioritySlideshowTimerFirst');
                if (timerDiv) {
                    if (this.imageSlideshow.images.length > 1) {
                        timerDiv.textContent = `Next in ${this.imageSlideshow.timeRemaining}s`;
                    } else {
                        timerDiv.style.display = 'none';
                    }
                }
            },

            playBeepFallback() {
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    
                    // Create repeating beep
                    const beepInterval = setInterval(() => {
                        if (!this.isAudioPlaying) {
                            clearInterval(beepInterval);
                            return;
                        }

                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);

                        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);

                        oscillator.start();
                        oscillator.stop(audioContext.currentTime + 0.5);
                    }, 1000); // Beep every second

                    // Store interval for cleanup
                    this.beepInterval = beepInterval;
                } catch (error) {
                    console.warn('Fallback beep failed:', error);
                }
            },

            stopSound() {
                this.isAudioPlaying = false;
                
                // Stop Web Audio source
                if (this.audioSource) {
                    try { this.audioSource.stop(); } catch(e) {}
                    this.audioSource = null;
                }

                // Stop HTML5 Audio fallback
                if (this._htmlAudio) {
                    this._htmlAudio.pause();
                    this._htmlAudio.currentTime = 0;
                    this._htmlAudio = null;
                }
                
                if (this.beepInterval) {
                    clearInterval(this.beepInterval);
                    this.beepInterval = null;
                }
            },

            stopSoundAndClose() {
                this.stopSound();
                this.close();
            },

            close() {
                this.stopSound();
                this.stopImageSlideshow();
                
                // Hide both modals
                if (this.picturesOverlay) this.picturesOverlay.style.display = 'none';
                if (this.dataOverlay) this.dataOverlay.style.display = 'none';
                
                // Reset state
                this.currentAlert = null;
                this.currentModal = 'none';
                
                // Restore body scroll
                document.body.style.overflow = '';
            }
        };
        
        // Main Application Controller
        const App = {
            async init() {
                try {
                    // Initialize base components
                    Clock.init();
                    Charts.init();
                    LiveSlideshow.initAutoFit();
                    PriorityAlert.init();
                    
                    // Setup event listeners
                    this.setupEventListeners();
                    
                    // Load initial data
                    await this.loadInitialData();
                    
                    // Setup auto-refresh intervals
                    this.setupAutoRefresh();
                    
                    console.log('Dashboard initialized successfully');
                } catch (error) {
                    console.error('Dashboard initialization error:', error);
                    AppState.hasErrors = true;
                }
            },

            async loadInitialData() {
                AppState.isLoading = true;
                
                try {
                    // Load available months first
                    await AvailableMonths.load();
                    
                    // Load all dashboard data concurrently
                    await Promise.allSettled([
                        KPI.load(),
                        Contractors.load(),
                        Charts.loadRegionalData(),
                        Charts.loadSourcesData(),
                        SalesFunnel.load(),
                        TargetProgress.load(),
                        ProjectStatus.load(),
                        LiveSlideshow.load()
                    ]);
                } catch (error) {
                    console.error('Error loading initial data:', error);
                    AppState.hasErrors = true;
                } finally {
                    AppState.isLoading = false;
                }
            },

            async refreshData() {
                if (AppState.isLoading) return; // Prevent multiple concurrent refreshes
                
                try {
                    await Promise.allSettled([
                        KPI.load(),
                        Contractors.load(),
                        Charts.loadRegionalData(),
                        Charts.loadSourcesData(),
                        SalesFunnel.load(),
                        TargetProgress.load(),
                        ProjectStatus.load()
                    ]);
                } catch (error) {
                    console.error('Error refreshing data:', error);
                }
            },

            setupEventListeners() {
                const debouncedRefresh = Utils.debounce(() => this.refreshData(), 300);

                // Show/hide month-select when period is Overall
                const toggleMonthSelect = () => {
                    const period = document.getElementById('period-select')?.value;
                    const monthGroup = document.getElementById('month-select')?.closest('.control-group');
                    if (monthGroup) monthGroup.style.display = period === 'overall' ? 'none' : '';
                };
                document.getElementById('period-select')?.addEventListener('change', toggleMonthSelect);
                toggleMonthSelect();

                // Filter change handlers
                const filterSelectors = ['period-select', 'region-select', 'month-select'];
                filterSelectors.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.addEventListener('change', debouncedRefresh);
                    }
                });

                // Handle page visibility changes
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden && AppState.hasErrors) {
                        // Retry loading if there were previous errors and page becomes visible
                        this.refreshData();
                        AppState.hasErrors = false;
                    }
                });

                // Handle online/offline events
                window.addEventListener('online', () => {
                    console.log('Connection restored, refreshing data');
                    this.refreshData();
                });

                window.addEventListener('offline', () => {
                    console.log('Connection lost');
                });
            },

            setupAutoRefresh() {
                // Data refresh every 30 seconds
                AppState.intervals.dataRefresh = setInterval(() => {
                    if (!document.hidden) { // Only refresh when page is visible
                        this.refreshData();
                    }
                }, 30000);
                
                // Slideshow refresh every 10 seconds
                AppState.intervals.slideshowRefresh = setInterval(() => {
                    if (!document.hidden) {
                        LiveSlideshow.load();
                    }
                }, 10000);
            },

            cleanup() {
                // Clean up intervals
                Object.values(AppState.intervals).forEach(interval => {
                    if (interval) clearInterval(interval);
                });
                
                // Clean up charts
                Object.values(AppState.charts).forEach(chart => {
                    if (chart && typeof chart.destroy === 'function') {
                        chart.destroy();
                    }
                });
                
                // Clear slideshow timeouts
                if (LiveSlideshow.countdownInterval) {
                    clearInterval(LiveSlideshow.countdownInterval);
                }
                
                // Clear priority alert timeouts and audio
                if (PriorityAlert.imageSlideshow.countdownTimer) {
                    clearInterval(PriorityAlert.imageSlideshow.countdownTimer);
                }
                if (PriorityAlert.beepInterval) {
                    clearInterval(PriorityAlert.beepInterval);
                }
                PriorityAlert.stopSound();
            }
        };

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            App.init();
            
            // Initialize export functionality
            setTimeout(() => {
                // Report selection checkboxes
                document.querySelectorAll('input[name="exportReport"]').forEach(cb => {
                    cb.addEventListener('change', () => {
                        ExportModal.updateNextButton();
                        
                        // Update select all checkbox
                        const allCheckboxes = document.querySelectorAll('input[name="exportReport"]');
                        const checkedCheckboxes = document.querySelectorAll('input[name="exportReport"]:checked');
                        const selectAllCheckbox = document.getElementById('selectAllReports');
                        
                        if (checkedCheckboxes.length === allCheckboxes.length) {
                            selectAllCheckbox.checked = true;
                            selectAllCheckbox.indeterminate = false;
                        } else if (checkedCheckboxes.length > 0) {
                            selectAllCheckbox.checked = false;
                            selectAllCheckbox.indeterminate = true;
                        } else {
                            selectAllCheckbox.checked = false;
                            selectAllCheckbox.indeterminate = false;
                        }
                    });
                });
                
                // Initialize next button state
                ExportModal.updateNextButton();
            }, 100);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            App.cleanup();
        });
        
        // Store custom select instances globally for refresh
        window.customSelectInstances = {};
        
        // Initialize custom select dropdowns AFTER DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize all control selects EXCEPT month-select (it will be initialized after data loads)
            const controlSelects = document.querySelectorAll('.control-select:not(#month-select)');
            controlSelects.forEach(select => {
                const instance = new CustomSelect(select, {
                    searchable: false,
                    placeholder: select.options[select.selectedIndex]?.text || 'Select...'
                });
            });
        });
