/* ============================================================
   sr-performance.js — SR Performance Report Page
   ============================================================ */

'use strict';

/* ── State ───────────────────────────────────────────────── */
const State = {
    reps: [],
    summary: {},
    filters: { month: '', year: new Date().getFullYear(), region: '' },
    sortBy: 'win_count',
    sortDir: 'desc',
};

/* ── DOM refs ────────────────────────────────────────────── */
const DOM = {
    filterMonth:  () => document.getElementById('filterMonth'),
    filterYear:   () => document.getElementById('filterYear'),
    filterRegion: () => document.getElementById('filterRegion'),
    searchInput:  () => document.getElementById('searchInput'),
    tableBody:    () => document.getElementById('srTableBody'),
    noData:       () => document.getElementById('noData'),
    loadingRow:   () => document.getElementById('loadingRow'),

    /* KPI summary cards */
    kpiReps:       () => document.getElementById('kpiReps'),
    kpiAssigned:   () => document.getElementById('kpiAssigned'),
    kpiWins:       () => document.getElementById('kpiWins'),
    kpiWinAmount:  () => document.getElementById('kpiWinAmount'),
    kpiPipeline:   () => document.getElementById('kpiPipeline'),
    kpiContacted:  () => document.getElementById('kpiContacted'),
    kpiSql:        () => document.getElementById('kpiSql'),
    kpiQuoted:     () => document.getElementById('kpiQuoted'),
};

/* ── Helpers ─────────────────────────────────────────────── */
function fmt(n) {
    if (n === null || n === undefined) return '—';
    return Number(n).toLocaleString();
}

function fmtMoney(n) {
    if (!n || n === 0) return '₱0';
    if (n >= 1_000_000_000) return '₱' + (n / 1_000_000_000).toFixed(2) + 'B';
    if (n >= 1_000_000)     return '₱' + (n / 1_000_000).toFixed(2) + 'M';
    if (n >= 1_000)         return '₱' + (n / 1_000).toFixed(1) + 'K';
    return '₱' + Number(n).toLocaleString();
}

function fmtPct(n) {
    return (n || 0).toFixed(1) + '%';
}

function pctBarColor(pct) {
    if (pct >= 70) return '#10B981';
    if (pct >= 40) return '#F59E0B';
    return '#EF4444';
}

function rankBadge(rank) {
    if (rank === 1) return '<span class="rank-badge rank-1">🥇 1st</span>';
    if (rank === 2) return '<span class="rank-badge rank-2">🥈 2nd</span>';
    if (rank === 3) return '<span class="rank-badge rank-3">🥉 3rd</span>';
    return `<span class="rank-badge rank-n">#${rank}</span>`;
}

function winRateBadge(rate) {
    const cls = rate >= 50 ? 'badge-success' : rate >= 25 ? 'badge-warning' : 'badge-danger';
    return `<span class="badge ${cls}">${fmtPct(rate)}</span>`;
}

function timeAgo(dateStr) {
    if (!dateStr) return '—';
    const diff = (Date.now() - new Date(dateStr)) / 1000;
    if (diff < 60)           return 'Just now';
    if (diff < 3600)         return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400)        return Math.floor(diff / 3600) + 'h ago';
    if (diff < 86400 * 7)    return Math.floor(diff / 86400) + 'd ago';
    return new Date(dateStr).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

/* ── Fetch ───────────────────────────────────────────────── */
async function fetchData() {
    showLoading(true);

    const qs = new URLSearchParams();
    if (State.filters.month)  qs.set('month',  State.filters.month);
    if (State.filters.year)   qs.set('year',   State.filters.year);
    if (State.filters.region) qs.set('region', State.filters.region);

    try {
        const res = await fetch(`${BASE}/api/v1/users/sr-performance?${qs}`);
        if (!res.ok) throw new Error('API error ' + res.status);
        const data = await res.json();

        State.reps    = data.reps    || [];
        State.summary = data.summary || {};

        renderKPIs();
        renderTable();
    } catch (err) {
        console.error('SR Performance fetch error:', err);
        showError('Failed to load performance data. Please try again.');
    } finally {
        showLoading(false);
    }
}

/* ── KPI cards ───────────────────────────────────────────── */
function renderKPIs() {
    const s = State.summary;
    setText('kpiReps',      fmt(s.total_reps));
    setText('kpiAssigned',  fmt(s.total_assigned));
    setText('kpiContacted', fmt(s.total_contacted));
    setText('kpiSql',       fmt(s.total_sql_yes));
    setText('kpiQuoted',    fmt(s.total_quoted));
    setText('kpiWins',      fmt(s.total_wins));
    setText('kpiWinAmount', fmtMoney(s.total_win_amount));
    setText('kpiPipeline',  fmtMoney(s.total_pipeline_value));
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

/* ── Table ───────────────────────────────────────────────── */
function renderTable() {
    const tbody = DOM.tableBody();
    const noData = DOM.noData();
    if (!tbody) return;

    const search = (DOM.searchInput()?.value || '').toLowerCase();

    // Filter
    let reps = State.reps.filter(r => {
        if (!search) return true;
        return (r.full_name || '').toLowerCase().includes(search)
            || (r.branch   || '').toLowerCase().includes(search)
            || (r.email    || '').toLowerCase().includes(search);
    });

    // Sort
    reps = sortReps(reps);

    tbody.innerHTML = '';

    if (reps.length === 0) {
        if (noData) noData.style.display = 'block';
        return;
    }
    if (noData) noData.style.display = 'none';

    reps.forEach((rep, idx) => {
        const rank = idx + 1;

        // Mini funnel bar widths (relative to total_assigned)
        const base = rep.total_assigned || 1;
        const contactW  = Math.round((rep.contacted_count / base) * 100);
        const sqlW      = Math.round((rep.sql_yes_count   / base) * 100);
        const quoteW    = Math.round((rep.quoted_count    / base) * 100);
        const winW      = Math.round((rep.win_count       / base) * 100);

        const tr = document.createElement('tr');
        tr.className = 'sr-row animate-fadeInUp';
        tr.style.animationDelay = (idx * 0.03) + 's';
        tr.innerHTML = `
            <td class="rank-cell">${rankBadge(rank)}</td>
            <td>
                <div class="sr-name-cell">
                    <div class="sr-avatar">${(rep.full_name || '?')[0].toUpperCase()}</div>
                    <div>
                        <div class="sr-name">${escHtml(rep.full_name || '—')}</div>
                        <div class="sr-email">${escHtml(rep.email || '')}</div>
                        ${rep.branch ? `<div class="sr-branch">${escHtml(rep.branch)}</div>` : ''}
                    </div>
                </div>
            </td>
            <td class="num-cell">${fmt(rep.total_assigned)}</td>
            <td>
                <div class="funnel-mini">
                    <div class="funnel-row">
                        <span class="funnel-label">Contacted</span>
                        <div class="funnel-bar-wrap">
                            <div class="funnel-bar" style="width:${contactW}%; background:#3B82F6;"></div>
                        </div>
                        <span class="funnel-num">${rep.contacted_count}</span>
                    </div>
                    <div class="funnel-row">
                        <span class="funnel-label">SQL ✓</span>
                        <div class="funnel-bar-wrap">
                            <div class="funnel-bar" style="width:${sqlW}%; background:#10B981;"></div>
                        </div>
                        <span class="funnel-num">${rep.sql_yes_count}</span>
                    </div>
                    <div class="funnel-row">
                        <span class="funnel-label">Quoted</span>
                        <div class="funnel-bar-wrap">
                            <div class="funnel-bar" style="width:${quoteW}%; background:#F59E0B;"></div>
                        </div>
                        <span class="funnel-num">${rep.quoted_count}</span>
                    </div>
                    <div class="funnel-row">
                        <span class="funnel-label">Win</span>
                        <div class="funnel-bar-wrap">
                            <div class="funnel-bar" style="width:${winW}%; background:#8B5CF6;"></div>
                        </div>
                        <span class="funnel-num">${rep.win_count}</span>
                    </div>
                </div>
            </td>
            <td class="num-cell">${winRateBadge(rep.win_rate)}</td>
            <td class="num-cell money-cell">${fmtMoney(rep.total_win_amount)}</td>
            <td class="num-cell money-cell">${fmtMoney(rep.total_pipeline_value)}</td>
            <td>
                <div class="tracking-badges">
                    <span class="track-badge track-notstarted" title="Not Started">${rep.not_started_count}</span>
                    <span class="track-badge track-inprogress" title="In Progress">${rep.in_progress_count}</span>
                    <span class="track-badge track-complete"   title="Complete">${rep.complete_count}</span>
                </div>
            </td>
            <td class="num-cell time-cell">${timeAgo(rep.last_activity)}</td>
        `;
        tbody.appendChild(tr);
    });
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

/* ── Sorting ─────────────────────────────────────────────── */
function sortReps(reps) {
    const field = State.sortBy;
    const dir   = State.sortDir === 'asc' ? 1 : -1;

    return [...reps].sort((a, b) => {
        let av = a[field], bv = b[field];
        if (typeof av === 'string') av = av.toLowerCase();
        if (typeof bv === 'string') bv = bv.toLowerCase();
        if (av < bv) return -1 * dir;
        if (av > bv) return  1 * dir;
        return 0;
    });
}

function setSortColumn(col) {
    if (State.sortBy === col) {
        State.sortDir = State.sortDir === 'desc' ? 'asc' : 'desc';
    } else {
        State.sortBy  = col;
        State.sortDir = 'desc';
    }
    updateSortHeaders();
    renderTable();
}

function updateSortHeaders() {
    document.querySelectorAll('[data-sort]').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        if (th.dataset.sort === State.sortBy) {
            th.classList.add('sort-' + State.sortDir);
        }
    });
}

/* ── Loading / Error states ──────────────────────────────── */
function showLoading(on) {
    const row = DOM.loadingRow();
    if (row) row.style.display = on ? 'table-row' : 'none';
    const body = DOM.tableBody();
    if (body && on) body.innerHTML = '';
}

function showError(msg) {
    const tbody = DOM.tableBody();
    if (!tbody) return;
    tbody.innerHTML = `
        <tr>
            <td colspan="9" style="text-align:center; padding:3rem; color:var(--danger);">
                ⚠️ ${msg}
            </td>
        </tr>`;
}

/* ── Populate year filter ─────────────────────────────────── */
function populateYearFilter() {
    const sel = DOM.filterYear();
    if (!sel) return;
    const current = new Date().getFullYear();
    sel.innerHTML = '<option value="">All Years</option>';
    for (let y = current; y >= 2023; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        if (y === current) opt.selected = true;
        sel.appendChild(opt);
    }
    State.filters.year = String(current);
}

/* ── Init ────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    populateYearFilter();

    // Filter controls
    DOM.filterMonth()?.addEventListener('change', e => {
        State.filters.month = e.target.value;
        fetchData();
    });
    DOM.filterYear()?.addEventListener('change', e => {
        State.filters.year = e.target.value;
        fetchData();
    });
    DOM.filterRegion()?.addEventListener('change', e => {
        State.filters.region = e.target.value;
        fetchData();
    });

    // Search
    DOM.searchInput()?.addEventListener('input', () => renderTable());

    // Sort headers
    document.querySelectorAll('[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => setSortColumn(th.dataset.sort));
    });

    fetchData();
});
