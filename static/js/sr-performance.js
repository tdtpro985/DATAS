'use strict';

const State = {
    reps: [],
    summary: {},
    hasTimingData: false,
    filters: { date_from: '', date_to: '', branch: '' },
    sortBy: 'avg_days_full_cycle',
    sortDir: 'asc', // fastest first
};

/* ── Helpers ── */
function fmt(n) { return (n === null || n === undefined) ? '—' : Number(n).toLocaleString(); }
function fmtMoney(n) {
    if (!n || n === 0) return '₱0';
    if (n >= 1_000_000_000) return '₱' + (n / 1_000_000_000).toFixed(2) + 'B';
    if (n >= 1_000_000)     return '₱' + (n / 1_000_000).toFixed(2) + 'M';
    if (n >= 1_000)         return '₱' + (n / 1_000).toFixed(1) + 'K';
    return '₱' + Number(n).toLocaleString();
}
function fmtPct(n) { return (n || 0).toFixed(1) + '%'; }
function fmtDays(n) {
    if (n === null || n === undefined) return '—';
    if (n === 0) return '< 1h';
    if (n < 1) return Math.round(n * 24) + 'h';
    return n.toFixed(1) + 'd';
}
function speedBadge(days) {
    if (days === null || days === undefined) return '<span style="color:var(--text-muted);font-size:0.75rem;">No data</span>';
    const cls = days <= 7 ? 'badge-success' : days <= 30 ? 'badge-warning' : 'badge-danger';
    return `<span class="badge ${cls}">${fmtDays(days)}</span>`;
}
function rankBadge(r) {
    if (r === 1) return '<span class="rank-badge rank-1">🥇 1st</span>';
    if (r === 2) return '<span class="rank-badge rank-2">🥈 2nd</span>';
    if (r === 3) return '<span class="rank-badge rank-3">🥉 3rd</span>';
    return `<span class="rank-badge rank-n">#${r}</span>`;
}
function winBadge(rate) {
    const cls = rate >= 50 ? 'badge-success' : rate >= 25 ? 'badge-warning' : 'badge-danger';
    return `<span class="badge ${cls}">${fmtPct(rate)}</span>`;
}
function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

/* ── Fetch ── */
async function fetchData() {
    showLoading(true);
    const qs = new URLSearchParams();
    if (State.filters.date_from) qs.set('date_from', State.filters.date_from);
    if (State.filters.date_to)   qs.set('date_to',   State.filters.date_to);
    if (State.filters.branch)    qs.set('branch',     State.filters.branch);

    try {
        const res = await fetch(`${BASE}/api/v1/users/sr-performance?${qs}`);
        if (!res.ok) throw new Error('API error ' + res.status);
        const data = await res.json();
        State.reps          = data.reps    || [];
        State.summary       = data.summary || {};
        State.hasTimingData = data.summary?.has_timing_data || false;
        populateBranchFilter(data.branches || []);
        updateTimingUI();
        renderKPIs();
        renderTable();
    } catch (err) {
        console.error('SR Performance fetch error:', err);
        showError('Failed to load performance data. Please try again.');
    } finally {
        showLoading(false);
    }
}

function updateTimingUI() {
    const notice = document.getElementById('timingNotice');
    if (notice) notice.style.display = State.hasTimingData ? 'none' : 'flex';
    // Show/hide timing columns in table header
    document.querySelectorAll('.col-timing').forEach(el => {
        el.style.display = State.hasTimingData ? '' : 'none';
    });
}

/* ── Branch filter populate ── */
function populateBranchFilter(branches) {
    const sel = document.getElementById('filterBranch');
    if (!sel) return;
    const cur = sel.value;
    sel.innerHTML = '<option value="">All Branches</option>';
    branches.forEach(b => {
        const o = document.createElement('option');
        o.value = b; o.textContent = b;
        if (b === cur) o.selected = true;
        sel.appendChild(o);
    });
}

/* ── KPIs ── */
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

/* ── Table ── */
function renderTable() {
    const tbody = document.getElementById('srTableBody');
    const noData = document.getElementById('noData');
    if (!tbody) return;

    const search = (document.getElementById('searchInput')?.value || '').toLowerCase();
    let reps = State.reps.filter(r =>
        !search ||
        (r.full_name || '').toLowerCase().includes(search) ||
        (r.branch    || '').toLowerCase().includes(search) ||
        (r.email     || '').toLowerCase().includes(search)
    );

    reps = sortReps(reps);
    tbody.innerHTML = '';

    if (reps.length === 0) { if (noData) noData.style.display = 'block'; return; }
    if (noData) noData.style.display = 'none';

    reps.forEach((rep, idx) => {
        const base = rep.total_assigned || 1;
        const cW = Math.round((rep.contacted_count / base) * 100);
        const sW = Math.round((rep.sql_yes_count   / base) * 100);
        const qW = Math.round((rep.quoted_count    / base) * 100);
        const wW = Math.round((rep.win_count       / base) * 100);

        const timingCells = State.hasTimingData ? `
            <td class="num-cell col-timing">${speedBadge(rep.avg_days_full_cycle)}</td>
        ` : '';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${rankBadge(idx + 1)}</td>
            <td>
                <div class="sr-name-cell">
                    <div class="sr-avatar">${(rep.full_name || '?')[0].toUpperCase()}</div>
                    <div>
                        <div class="sr-name">${escHtml(rep.full_name || '—')}</div>
                        <div class="sr-email">${escHtml(rep.email || '')}</div>
                        ${rep.branch ? `<span class="sr-branch">${escHtml(rep.branch)}</span>` : ''}
                    </div>
                </div>
            </td>
            <td class="num-cell">${fmt(rep.total_assigned)}</td>
            <td>
                <div class="funnel-mini">
                    <div class="funnel-row">
                        <span class="funnel-label">Contacted</span>
                        <div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${cW}%;background:#3B82F6;"></div></div>
                        <span class="funnel-num">${rep.contacted_count}</span>
                    </div>
                    <div class="funnel-row">
                        <span class="funnel-label">SQL ✓</span>
                        <div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${sW}%;background:#10B981;"></div></div>
                        <span class="funnel-num">${rep.sql_yes_count}</span>
                    </div>
                    <div class="funnel-row">
                        <span class="funnel-label">Quoted</span>
                        <div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${qW}%;background:#F59E0B;"></div></div>
                        <span class="funnel-num">${rep.quoted_count}</span>
                    </div>
                    <div class="funnel-row">
                        <span class="funnel-label">Win</span>
                        <div class="funnel-bar-wrap"><div class="funnel-bar" style="width:${wW}%;background:#8B5CF6;"></div></div>
                        <span class="funnel-num">${rep.win_count}</span>
                    </div>
                </div>
            </td>
            ${timingCells}
            <td class="num-cell">${winBadge(rep.win_rate)}</td>
        `;
        tr.addEventListener('click', () => openDetail(rep));
        tbody.appendChild(tr);
    });
}

/* ── Sort ── */
function sortReps(reps) {
    const dir = State.sortDir === 'asc' ? 1 : -1;
    return [...reps].sort((a, b) => {
        let av = a[State.sortBy], bv = b[State.sortBy];
        // Nulls always last regardless of direction
        if (av === null && bv === null) return 0;
        if (av === null) return 1;
        if (bv === null) return -1;
        if (typeof av === 'string') av = av.toLowerCase();
        if (typeof bv === 'string') bv = bv.toLowerCase();
        if (av < bv) return -1 * dir;
        if (av > bv) return  1 * dir;
        return 0;
    });
}
function setSortColumn(col) {
    // For timing cols, default sort is asc (fastest first); others default desc
    const timingCols = ['avg_days_to_contact','avg_days_contact_to_sql','avg_days_sql_to_quote','avg_days_quote_to_win','avg_days_full_cycle'];
    if (State.sortBy === col) {
        State.sortDir = State.sortDir === 'desc' ? 'asc' : 'desc';
    } else {
        State.sortBy  = col;
        State.sortDir = timingCols.includes(col) ? 'asc' : 'desc';
    }
    document.querySelectorAll('[data-sort]').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        if (th.dataset.sort === col) th.classList.add('sort-' + State.sortDir);
    });
    renderTable();
}

/* ── Detail Modal ── */
function openDetail(rep) {
    const modal = document.getElementById('detailModal');
    if (!modal) return;

    document.getElementById('mAvatar').textContent = (rep.full_name || '?')[0].toUpperCase();
    setText('mName',  rep.full_name || '—');
    setText('mEmail', rep.email || '—');
    const br = document.getElementById('mBranch');
    if (rep.branch) { br.textContent = rep.branch; br.style.display = 'inline-block'; }
    else            { br.style.display = 'none'; }

    setText('mAssigned', fmt(rep.total_assigned));
    setText('mWinRate',  fmtPct(rep.win_rate));
    setText('mWinAmt',   fmtMoney(rep.total_win_amount));
    setText('mPipeline', fmtMoney(rep.total_pipeline_value));
    setText('mSql',      fmt(rep.sql_yes_count));
    setText('mSqlNo',    fmt(rep.sql_no_count));

    // Funnel
    const base = rep.total_assigned || 1;
    const funnel = [
        { label: 'Contacted', count: rep.contacted_count, color: '#3B82F6', days: rep.avg_days_to_contact,     daysLabel: 'avg days to contact' },
        { label: 'SQL Yes',   count: rep.sql_yes_count,   color: '#10B981', days: rep.avg_days_contact_to_sql, daysLabel: 'avg days contacted → SQL' },
        { label: 'SQL No',    count: rep.sql_no_count,    color: '#EF4444', days: null,                        daysLabel: null },
        { label: 'Quoted',    count: rep.quoted_count,    color: '#F59E0B', days: rep.avg_days_sql_to_quote,   daysLabel: 'avg days SQL → Quoted' },
        { label: 'Win',       count: rep.win_count,       color: '#8B5CF6', days: rep.avg_days_quote_to_win,   daysLabel: 'avg days Quoted → Win' },
    ];
    document.getElementById('mFunnel').innerHTML = funnel.map(f => {
        const pct  = Math.round((f.count / base) * 100);
        const time = (State.hasTimingData && f.days !== null)
            ? `<span style="margin-left:auto;font-size:0.7rem;color:var(--text-muted);">⏱ ${fmtDays(f.days)}</span>` : '';
        return `
            <div class="modal-funnel-row">
                <span class="modal-funnel-label">${f.label}</span>
                <div class="modal-funnel-bar-wrap">
                    <div class="modal-funnel-bar" style="width:${pct}%;background:${f.color};"></div>
                </div>
                <span class="modal-funnel-count">${f.count}</span>
                <span class="modal-funnel-pct">${fmtPct(pct)}</span>
                ${time}
            </div>`;
    }).join('');

    // Timing section
    const timingSection = document.getElementById('mTimingSection');
    if (timingSection) {
        if (State.hasTimingData) {
            timingSection.style.display = 'block';
            setText('mFullCycle',   fmtDays(rep.avg_days_full_cycle));
            setText('mCycles',      fmt(rep.completed_cycles));
            setText('mToContact',   fmtDays(rep.avg_days_to_contact));
            setText('mToSql',       fmtDays(rep.avg_days_contact_to_sql));
            setText('mToQuote',     fmtDays(rep.avg_days_sql_to_quote));
            setText('mToWin',       fmtDays(rep.avg_days_quote_to_win));
        } else {
            timingSection.style.display = 'none';
        }
    }

    setText('mNS', fmt(rep.not_started_count));
    setText('mIP', fmt(rep.in_progress_count));
    setText('mCO', fmt(rep.complete_count));

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDetail() {
    document.getElementById('detailModal')?.classList.remove('active');
    document.body.style.overflow = '';
}

/* ── Loading / Error ── */
function showLoading(on) {
    const row = document.getElementById('loadingRow');
    const body = document.getElementById('srTableBody');
    if (row) row.style.display = on ? 'table-row' : 'none';
    if (body && on) body.innerHTML = '';
}
function showError(msg) {
    const tbody = document.getElementById('srTableBody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--danger);">⚠️ ${msg}</td></tr>`;
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('filterDateFrom')?.addEventListener('change', e => { State.filters.date_from = e.target.value; fetchData(); });
    document.getElementById('filterDateTo')?.addEventListener('change',   e => { State.filters.date_to   = e.target.value; fetchData(); });
    document.getElementById('filterBranch')?.addEventListener('change',   e => { State.filters.branch    = e.target.value; fetchData(); });
    document.getElementById('searchInput')?.addEventListener('input', renderTable);

    document.querySelectorAll('[data-sort]').forEach(th => {
        th.addEventListener('click', () => setSortColumn(th.dataset.sort));
    });

    document.getElementById('closeDetailModal')?.addEventListener('click', closeDetail);
    document.getElementById('detailModal')?.addEventListener('click', e => {
        if (e.target === document.getElementById('detailModal')) closeDetail();
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetail(); });

    fetchData();
});
