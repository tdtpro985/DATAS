// ---------- Material Colors Map for Consistency ----------
const MATERIAL_COLORS = {
    'DRBs': '#3B82F6',
    'Sheet Pile': '#10B981',
    'Wide Flange': '#F59E0B',
    'Angle Bars': '#6366F1',
    'Channel Bars': '#EC4899',
    'GI/BI Pipes': '#8B5CF6',
    'MS Plate': '#F97316',
    'Angle Plate': '#06B6D4',
    'Unknown': '#94a3b8'
};

// ---------- Target vs Progress — driven by global period tab ----------
async function loadTargetChart(kpiData) {
    const wrapper = document.getElementById('target-chart-wrapper');
    if (!wrapper) return;

    try {
        const target = TARGETS[currentPeriod] || TARGETS['monthly'];
        // Use provided data if available, otherwise fetch
        const data = kpiData || await fetchJSON(getApiUrl('/kpi'));
        const progress = (data && data.data) ? (data.data.projects_encoded || 0) : 0;
        const pct = target > 0 ? Math.min((progress / target) * 100, 150) : 0;
        const displayPct = Math.round(pct);

        // Determine status
        let statusLabel, statusColor;
        if (pct >= 100) {
            statusLabel = '🎯 Target Exceeded!';
            statusColor = '#4CAF50';
        } else if (pct >= 70) {
            statusLabel = '✅ On Track';
            statusColor = '#FF9800';
        } else {
            statusLabel = '⚠️ Behind Target';
            statusColor = '#f44336';
        }

        const barColor = pct >= 100 ? '#4CAF50' : pct >= 70 ? '#FF9800' : '#f44336';

        wrapper.innerHTML =
            '<div class="gauge-container">' +
            '<div class="gauge-header">' +
            '<span class="gauge-pct" style="color:' + barColor + '">' + displayPct + '%</span>' +
            '<span class="gauge-status" style="color:' + statusColor + '">' + statusLabel + '</span>' +
            '</div>' +
            '<div class="gauge-bar-track">' +
            '<div class="gauge-bar-fill" style="width:' + Math.min(pct, 100) + '%;background:' + barColor + '"></div>' +
            '</div>' +
            '<div class="gauge-stats">' +
            '<span>Encoded: <strong>' + formatNumber(progress) + '</strong></span>' +
            '<span>Target: <strong>' + formatNumber(target) + '</strong></span>' +
            '</div>' +
            '</div>';
    } catch (err) {
        console.error("Error loading target chart:", err);
        wrapper.innerHTML = '<div class="loading-text" style="color:var(--danger)">Error loading data</div>';
    }
}


// ---------- Pie Chart (material breakdown, filtered by period) ----------
var _pieLoading = false;

async function loadPieChart() {
    if (_pieLoading) return;
    _pieLoading = true;
    try {
        const data = await fetchJSON(getApiUrl('/charts/pie?group_by=material'));
        if (!data || !data.slices || data.slices.length === 0) {
            // Destroy stale chart so the previous month's pie doesn't linger
            var canvas = document.getElementById('pie-chart');
            if (canvas) {
                let existingChart = Chart.getChart(canvas);
                if (existingChart) existingChart.destroy();
                if (pieChart) { try { pieChart.destroy(); } catch (e) { } pieChart = null; }
                // Optionally show a "no data" message on the canvas
                var ctx2 = canvas.getContext('2d');
                ctx2.clearRect(0, 0, canvas.width, canvas.height);
                ctx2.fillStyle = 'rgba(255,255,255,0.35)';
                ctx2.font = 'bold 14px Inter, sans-serif';
                ctx2.textAlign = 'center';
                ctx2.textBaseline = 'middle';
                ctx2.fillText('No data for this period', canvas.width / 2, canvas.height / 2);
            }
            return;
        }

        var canvas = document.getElementById('pie-chart');
        if (!canvas) return;

        var rawLabels = data.slices.map(function (s) { return s.label || 'Unknown'; });
        var rawValues = data.slices.map(function (s) { return parseFloat(s.value || s.count || 0); });

        var palette = [
            '#10B981', '#3B82F6', '#F59E0B', '#6366F1', '#EC4899',
            '#8B5CF6', '#F97316', '#06B6D4', '#EF4444', '#14B8A6'
        ];

        let groupedItems = rawLabels.map((l, i) => ({ l: l, v: rawValues[i] }));

        // SORT ALPHABETICALLY for consistent slice positions regardless of filter
        groupedItems.sort((a, b) => a.l.localeCompare(b.l));

        var labels = groupedItems.map(x => x.l);
        var values = groupedItems.map(x => x.v);

        // Assign colors based on fixed map or fallback to palette
        var colors = labels.map((l, i) => MATERIAL_COLORS[l] || palette[i % palette.length]);

        // CRITICAL: Robust destruction using Chart.getChart
        let existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }
        if (pieChart) {
            try { pieChart.destroy(); } catch (e) { }
            pieChart = null;
        }

        var ctx = canvas.getContext('2d');
        pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#0f172a',
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                // Increase radius back to 65% for desktop and 50% for mobile
                radius: window.innerWidth < 768 ? '50%' : '65%',
                // Radial padding for balanced label spread
                layout: { padding: { top: 44, bottom: 44, left: 100, right: 100 } },
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false }, // handled by custom afterDraw below
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                if (total === 0) return ' ' + context.label + ': 0';
                                const pct = Math.round(context.raw / total * 100);
                                return ' ' + context.label + ': ' + formatCurrency(context.raw) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels, {
                id: 'radialLabels',
                afterDraw(chart) {
                    const { ctx, height } = chart;
                    const meta = chart.getDatasetMeta(0);
                    const dataset = chart.data.datasets[0];
                    const total = dataset.data.reduce((a, b) => a + b, 0);

                    if (total === 0 || !meta.data.length) return;

                    const cx = meta.data[0].x;
                    const cy = meta.data[0].y;
                    const outerR = meta.data[0].outerRadius;

                    const colors = dataset.backgroundColor;

                    // Collect visible slices
                    const items = [];
                    meta.data.forEach((arc, i) => {
                        const pct = dataset.data[i] / total;
                        if (pct < 0.005) return;
                        const mid = arc.startAngle + (arc.endAngle - arc.startAngle) / 2;
                        // departure point: further out so adjacent lines separate before bending
                        const depR = outerR + 32;
                        items.push({
                            pct, mid,
                            label:    chart.data.labels[i],
                            value:    dataset.data[i],
                            color:    colors[i],
                            dotX:     cx + Math.cos(mid) * outerR,
                            dotY:     cy + Math.sin(mid) * outerR,
                            depX:     cx + Math.cos(mid) * depR,
                            depY:     cy + Math.sin(mid) * depR,
                            naturalY: cy + Math.sin(mid) * depR,
                        });
                    });

                    if (!items.length) return;

                    // Split into left / right, sort each top → bottom
                    const left  = items.filter(p => Math.cos(p.mid) <= 0).sort((a, b) => a.naturalY - b.naturalY);
                    const right = items.filter(p => Math.cos(p.mid) >  0).sort((a, b) => a.naturalY - b.naturalY);

                    // Spread labels vertically — no overlaps, clamped within canvas
                    const ROW_H = 36, PAD = 14;
                    function spread(group) {
                        if (!group.length) return;
                        group.forEach(p => { p.ly = p.naturalY; });
                        for (let i = 1; i < group.length; i++) {
                            if (group[i].ly < group[i - 1].ly + ROW_H)
                                group[i].ly = group[i - 1].ly + ROW_H;
                        }
                        if (group[group.length - 1].ly > height - PAD) {
                            group[group.length - 1].ly = height - PAD;
                            for (let i = group.length - 2; i >= 0; i--) {
                                if (group[i].ly > group[i + 1].ly - ROW_H)
                                    group[i].ly = group[i + 1].ly - ROW_H;
                            }
                        }
                        if (group[0].ly < PAD) {
                            group[0].ly = PAD;
                            for (let i = 1; i < group.length; i++) {
                                if (group[i].ly < group[i - 1].ly + ROW_H)
                                    group[i].ly = group[i - 1].ly + ROW_H;
                            }
                        }
                    }

                    spread(left);
                    spread(right);

                    // Label anchor columns — wide enough to give lines room to spread
                    const COL_DIST = outerR + 60;
                    left.forEach(p  => { p.lx = cx - COL_DIST; });
                    right.forEach(p => { p.lx = cx + COL_DIST; });

                    // Render: color-coded line (dot → departure → label) + text
                    [...left, ...right].forEach(p => {
                        const isLeft = Math.cos(p.mid) <= 0;
                        const align  = isLeft ? 'right' : 'left';
                        const textX  = isLeft ? p.lx - 5 : p.lx + 5;

                        ctx.save();

                        // Color-coded line matching slice color
                        ctx.beginPath();
                        ctx.moveTo(p.dotX, p.dotY);
                        ctx.lineTo(p.depX, p.depY);   // radial segment (separates lines)
                        ctx.lineTo(p.lx, p.ly);        // bend to label column
                        ctx.strokeStyle = p.color;
                        ctx.lineWidth   = 1.2;
                        ctx.globalAlpha = 0.55;
                        ctx.stroke();
                        ctx.globalAlpha = 1;

                        // Small dot at departure for visual anchor
                        ctx.beginPath();
                        ctx.arc(p.depX, p.depY, 2.5, 0, Math.PI * 2);
                        ctx.fillStyle = p.color;
                        ctx.fill();

                        // Label name
                        ctx.textAlign    = align;
                        ctx.textBaseline = 'bottom';
                        ctx.fillStyle    = '#ffffff';
                        ctx.font         = 'bold 11px Inter, sans-serif';
                        ctx.fillText(p.label, textX, p.ly);

                        // Value + pct in slice color
                        ctx.textBaseline = 'top';
                        ctx.fillStyle    = p.color;
                        ctx.font         = '10px Inter, sans-serif';
                        ctx.fillText(formatCurrency(p.value) + ' (' + Math.round(p.pct * 100) + '%)', textX, p.ly + 1);

                        ctx.restore();
                    });
                }
            }]
        });

    } catch (err) {
        console.error("Pie chart error:", err);
        displayPieError('No material breakdown data available');
    } finally {
        _pieLoading = false;
    }
}

function displayPieError(message) {
    var canvas = document.getElementById('pie-chart');
    if (!canvas) return;
    var existing = Chart.getChart(canvas);
    if (existing) {
        try { existing.destroy(); } catch (e) { }
    }
    var ctx = canvas.getContext('2d');
    if (!ctx) return;
    var width = canvas.width || canvas.offsetWidth || 300;
    var height = canvas.height || canvas.offsetHeight || 260;
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = 'rgba(255,255,255,0.72)';
    ctx.font = 'bold 14px Inter, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(message, width / 2, height / 2);
}


// ---------- Funnel (filtered by period) ----------
let funnelInitialized = false; 

function resetFunnelState() {
    funnelInitialized = false;
    const container = document.getElementById('funnel-container');
    if (container) container.innerHTML = '';
}

async function loadFunnel() {
    var container = document.getElementById('funnel-container');

    // F1: Show loading placeholder only on first render, not on subsequent syncs
    if (!funnelInitialized && container) {
        container.innerHTML = '<div class="loading-text">Loading funnel...</div>';
    }

    var data = await fetchJSON(getApiUrl('/charts/funnel'));
    if (!container) return;
    if (!data || !data.stages) {
        if (!funnelInitialized) {
            container.innerHTML = '<div class="loading-text">No data available</div>';
        }
        return;
    }

    var maxCount = Math.max.apply(null, data.stages.map(function (s) { return s.count || 1; }));
    maxCount = Math.max(maxCount, 1);

    // F1: On subsequent syncs, update existing segments in-place (no flash)
    if (funnelInitialized) {
        console.log('[FUNNEL] Refreshed at', new Date().toLocaleTimeString());
        
        // Add a temporary subtle flash to the section title or container to indicate sync
        const section = document.getElementById('funnel-section');
        if (section) {
            section.classList.add('syncing-flash');
            setTimeout(() => section.classList.remove('syncing-flash'), 800);
        }

        data.stages.forEach(function (stage) {
            // Use sanitised stage name as data-stage key
            var key = stage.name.replace(/\s+/g, '-').toLowerCase();
            var seg = container.querySelector('[data-stage="' + key + '"]');
            if (!seg) return;

            // Update bar width
            var widthPct = Math.max(((stage.count || 0) / maxCount) * 100, 20);
            seg.style.width = widthPct + '%';

            // Update label text — name · count <small>conv or value</small>
            var labelEl = seg.querySelector('.funnel-label');
            if (labelEl) {
                var convText = (stage.conversion !== null && stage.conversion !== undefined)
                    ? stage.conversion + '% conv.' : '';
                labelEl.innerHTML = stage.name + ' · ' + formatNumber(stage.count) +
                    '<small>' + (convText ? convText : formatCurrency(stage.value)) + '</small>';
            }
        });
        return; // skip full rebuild
    }

    // First render — build all segments with data-stage for future targeting
    container.innerHTML = data.stages.map(function (stage) {
        var key = stage.name.replace(/\s+/g, '-').toLowerCase();
        var widthPct = Math.max(((stage.count || 0) / maxCount) * 100, 20);
        var color = stage.color || '#FF9800';
        var convText = (stage.conversion !== null && stage.conversion !== undefined)
            ? stage.conversion + '% conv.' : '';

        return '<div class="funnel-segment" data-stage="' + key + '" style="' +
            'width:' + widthPct + '%;' +
            'background:linear-gradient(180deg,' + color + 'dd,' + color + ');' +
            'box-shadow:0 1px 0 ' + color + '88,0 2px 4px rgba(0,0,0,0.15);' +
            '">' +
            '<div class="funnel-label">' +
            stage.name + ' · ' + formatNumber(stage.count) +
            '<small>' + (convText ? convText : formatCurrency(stage.value)) + '</small>' +
            '</div></div>';
    }).join('');

    funnelInitialized = true;
}

