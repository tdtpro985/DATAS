// ── Load Performance Data from API ──────────────────────────────
let daily30, weekly8, monthly6, bySource, byStatus, byRegion;
let performanceData = null;

async function loadPerformanceData() {
    try {
        const response = await fetch(`${BASE}/api/v1/encoder-performance-data`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('Failed to load performance data');
        }
        
        performanceData = await response.json();
        
        // Extract data
        daily30 = performanceData.daily30 || [];
        weekly8 = performanceData.weekly8 || [];
        monthly6 = performanceData.monthly6 || [];
        bySource = performanceData.bySource || [];
        byStatus = performanceData.byStatus || [];
        byRegion = performanceData.byRegion || [];
        
        // Set quality score
        window.ENCODER_QUALITY_SCORE = performanceData.qualityScore || 0;
        window.ENCODER_QUALITY_COLOR = performanceData.scoreColor || '#ef4444';
        
        // Initialize charts
        initializeCharts();
        
    } catch (error) {
        console.error('Error loading performance data:', error);
        // Show error message to user
        document.body.insertAdjacentHTML('beforeend', `
            <div style="position: fixed; top: 20px; right: 20px; background: #ef4444; color: #111827; padding: 1rem; border-radius: 8px; z-index: 9999;">
                ⚠️ Failed to load performance data
            </div>
        `);
    }
}

function initializeCharts() {
    // Build all charts after data is loaded
    buildDailyChart(30);
    buildTrendChart('weekly');
    // Rebuild inline charts with loaded data
    buildInlineCharts();
}

function buildInlineCharts() {
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: byStatus.map(s => s.status),
            datasets: [{
                data: byStatus.map(s => s.cnt),
                backgroundColor: byStatus.map(s => statusColorMap[s.status] || MUTED),
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 10, boxWidth: 10, font: { size: 10 } }
                },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: 'rgba(0,0,0,0.1)', titleColor: '#111827', bodyColor: '#111827',
                    borderWidth: 1,
                    callbacks: {
                        label: item => ` ${item.label}: ${item.raw} (${Math.round(item.raw * 100 / byStatus.reduce((a,s)=>a+parseInt(s.cnt),0))}%)`
                    }
                }
            }
        }
    });

    // Source Chart
    const sourceCtx = document.getElementById('sourceChart').getContext('2d');
    new Chart(sourceCtx, {
        type: 'bar',
        data: {
            labels: bySource.map(s => s.source),
            datasets: [{
                label: 'Projects',
                data: bySource.map(s => s.cnt),
                backgroundColor: bySource.map(s => sourceColorMap[s.source] || MUTED),
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 60,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: 'rgba(0,0,0,0.1)', titleColor: '#111827', bodyColor: '#111827',
                    borderWidth: 1,
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 },
                     grid: { color: 'rgba(0,0,0,0.04)' } }
            }
        }
    });

    // Region Chart
    const regionCtx = document.getElementById('regionChart').getContext('2d');
    const regionLabels = byRegion.map(r => r.region_name.replace(/Region\s+/i, 'Reg. ').replace(/\s+\(.*?\)/, ''));
    new Chart(regionCtx, {
        type: 'bar',
        data: {
            labels: regionLabels,
            datasets: [{
                label: 'Projects',
                data: byRegion.map(r => r.cnt),
                backgroundColor: byRegion.map((_, i) =>
                    `rgba(96,165,250,${0.85 - i * 0.06})`
                ),
                borderRadius: 4,
                borderSkipped: false,
                maxBarThickness: 20,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: 'rgba(0,0,0,0.1)', titleColor: '#111827', bodyColor: '#111827',
                    borderWidth: 1,
                    callbacks: {
                        title: items => byRegion[items[0].dataIndex].region_name,
                    }
                }
            },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 },
                     grid: { color: 'rgba(0,0,0,0.04)' } },
                y: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });

    // Quality Ring Chart
    const score = window.ENCODER_QUALITY_SCORE ?? 0;
    const color = window.ENCODER_QUALITY_COLOR ?? '#FF7A00';
    const qualityCtx = document.getElementById('qualityRingChart').getContext('2d');
    new Chart(qualityCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [score, 100 - score],
                backgroundColor: [color, 'rgba(0,0,0,0.04)'],
                borderWidth: 0,
                hoverOffset: 0,
            }]
        },
        options: {
            responsive: false,
            cutout: '78%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            events: [],
        }
    });
}

// ── Chart.js global defaults (dark theme) ───────────────────────
Chart.defaults.color = 'rgba(55,65,81,0.75)';
Chart.defaults.borderColor = 'rgba(0,0,0,0.07)';
Chart.defaults.font.family = "'Inter', sans-serif";

const ORANGE   = 'rgba(255,122,0,0.85)';
const ORANGE_L = 'rgba(255,122,0,0.15)';
const BLUE     = 'rgba(96,165,250,0.85)';
const GREEN    = 'rgba(52,211,153,0.85)';
const YELLOW   = 'rgba(251,191,36,0.85)';
const PURPLE   = 'rgba(167,139,250,0.85)';
const MUTED    = 'rgba(148,163,184,0.6)';

const statusColorMap = {
    'For Execution': BLUE,
    'Awarded':       GREEN,
    'For Bidding':   YELLOW,
    'Priority':      ORANGE,
};
const sourceColorMap = {
    'DPWH': ORANGE,
    'BCI':  BLUE,
    'EGOV': PURPLE,
};

function gradientFill(ctx, color) {
    const g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
    g.addColorStop(0, color.replace('0.85', '0.5').replace('0.6', '0.3'));
    g.addColorStop(1, color.replace('0.85', '0.02').replace('0.6', '0.01'));
    return g;
}

// ── 1. Daily Bar Chart ──────────────────────────────────────────
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
let dailyChart;

function buildDailyChart(range) {
    if (!daily30 || daily30.length === 0) return;
    
    const data = range === 7 ? daily30.slice(-7) : daily30;
    const today = new Date().toISOString().slice(0, 10);

    if (dailyChart) dailyChart.destroy();
    dailyChart = new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.label),
            datasets: [{
                label: 'Projects',
                data: data.map(d => d.cnt),
                backgroundColor: data.map(d => d.date === today ? ORANGE : 'rgba(255,122,0,0.4)'),
                borderRadius: 5,
                borderSkipped: false,
                maxBarThickness: 32,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: 'rgba(0,0,0,0.12)', titleColor: '#111827', bodyColor: '#111827',
                    borderWidth: 1,
                    callbacks: {
                        title: items => data[items[0].dataIndex].date,
                        label: item => ` ${item.raw} project${item.raw !== 1 ? 's' : ''}`,
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } },
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 },
                     grid: { color: 'rgba(0,0,0,0.04)' } }
            }
        }
    });
}
buildDailyChart(30);

document.getElementById('dailyTabs').addEventListener('click', e => {
    const btn = e.target.closest('.chart-tab');
    if (!btn) return;
    document.querySelectorAll('#dailyTabs .chart-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    buildDailyChart(parseInt(btn.dataset.range));
});

// ── 2. Status Doughnut ──────────────────────────────────────────
(function() {
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: byStatus.map(s => s.status),
            datasets: [{
                data: byStatus.map(s => s.cnt),
                backgroundColor: byStatus.map(s => statusColorMap[s.status] || MUTED),
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 10, boxWidth: 10, font: { size: 10 } }
                },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: 'rgba(0,0,0,0.1)', titleColor: '#111827', bodyColor: '#111827',
                    borderWidth: 1,
                    callbacks: {
                        label: item => ` ${item.label}: ${item.raw} (${Math.round(item.raw * 100 / byStatus.reduce((a,s)=>a+parseInt(s.cnt),0))}%)`
                    }
                }
            }
        }
    });
})();

// ── 3. Source Bar ───────────────────────────────────────────────
(function() {
    const ctx = document.getElementById('sourceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: bySource.map(s => s.source),
            datasets: [{
                label: 'Projects',
                data: bySource.map(s => s.cnt),
                backgroundColor: bySource.map(s => sourceColorMap[s.source] || MUTED),
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 60,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: 'rgba(0,0,0,0.1)', titleColor: '#111827', bodyColor: '#111827',
                    borderWidth: 1,
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 },
                     grid: { color: 'rgba(0,0,0,0.04)' } }
            }
        }
    });
})();

// ── 4. Region Horizontal Bar ─────────────────────────────────────
(function() {
    const ctx = document.getElementById('regionChart').getContext('2d');
    const labels = byRegion.map(r => r.region_name.replace(/Region\s+/i, 'Reg. ').replace(/\s+\(.*?\)/, ''));
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Projects',
                data: byRegion.map(r => r.cnt),
                backgroundColor: byRegion.map((_, i) =>
                    `rgba(96,165,250,${0.85 - i * 0.06})`
                ),
                borderRadius: 4,
                borderSkipped: false,
                maxBarThickness: 20,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: 'rgba(0,0,0,0.1)', titleColor: '#111827', bodyColor: '#111827',
                    borderWidth: 1,
                    callbacks: {
                        title: items => byRegion[items[0].dataIndex].region_name,
                    }
                }
            },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 },
                     grid: { color: 'rgba(0,0,0,0.04)' } },
                y: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
})();

// ── 5. Trend Chart (weekly / monthly) ───────────────────────────
const trendCtx = document.getElementById('trendChart').getContext('2d');
let trendChart;

function buildTrendChart(period) {
    const data   = period === 'monthly' ? monthly6 : weekly8;
    const label  = period === 'monthly' ? 'Monthly' : 'Weekly';

    if (trendChart) trendChart.destroy();

    const gradient = trendCtx.createLinearGradient(0, 0, 0, 180);
    gradient.addColorStop(0, 'rgba(255,122,0,0.35)');
    gradient.addColorStop(1, 'rgba(255,122,0,0.01)');

    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: data.map(d => d.label),
            datasets: [{
                label,
                data: data.map(d => d.cnt),
                borderColor: '#ff7a00',
                borderWidth: 2.5,
                backgroundColor: gradient,
                pointBackgroundColor: '#ff7a00',
                pointBorderColor: '#0f1520',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: 'rgba(0,0,0,0.12)', titleColor: '#111827', bodyColor: '#111827',
                    borderWidth: 1,
                    callbacks: {
                        label: item => ` ${item.raw} project${item.raw !== 1 ? 's' : ''}`
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 },
                     grid: { color: 'rgba(0,0,0,0.04)' } }
            }
        }
    });
}
buildTrendChart('weekly');

document.getElementById('trendTabs').addEventListener('click', e => {
    const btn = e.target.closest('.chart-tab');
    if (!btn) return;
    document.querySelectorAll('#trendTabs .chart-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    buildTrendChart(btn.dataset.period);
});

// ── 6. Quality Ring Chart ───────────────────────────────────────
(function() {
    const score = window.ENCODER_QUALITY_SCORE ?? 0;
    const color = window.ENCODER_QUALITY_COLOR ?? '#FF7A00';
    const ctx = document.getElementById('qualityRingChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [score, 100 - score],
                backgroundColor: [color, 'rgba(0,0,0,0.04)'],
                borderWidth: 0,
                hoverOffset: 0,
            }]
        },
        options: {
            responsive: false,
            cutout: '78%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            events: [],
        }
    });
})();


// ── Initialize on page load ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadPerformanceData();
});
