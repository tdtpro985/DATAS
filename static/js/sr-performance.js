'use strict';

const State = {
    reps: [],
    summary: {},
    filters: { date_from: '', date_to: '', branch: '' },
    sortBy: 'win_count',
    sortDir: 'desc',
};

/* ── Helpers ── */
function fmt(n) {
    return (n === null || n === undefined) ? '—' : Number(n).toLocaleString();
}
function fmtMoney(n) {
    if (!n || n === 0) return '₱0';
    if (n >= 1_000_000_000) return '₱' + (n / 1_000_000_000).toFixed(2) + 'B';
    if (n >= 1_000_000)     return '₱' + (n / 1_000_000).toFixed(2) + 'M';
    if (n >= 1_000)         return '₱' + (n / 1_000).toFixed(1) + 'K';
    return '₱' + Number(n).toLocaleString();
}
function fmtPct(n) { return (n || 0).toFixed(1) + '%'; }
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
function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
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
        State.reps    = data.reps    || [];
        State.summary = data.summary || {};
        populateBranchFilter(data.branches || []);
        renderKPIs();
        renderTable();
    } catch (err) {
        console.error('SR Performance fetch error:', err);
        showError('Failed to load performance data. Please try again.');
    } finally {
        showLoading(false);
    }
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

    if (reps.length === 0) {
        if (noData) noData.style.display = 'block';
        return;
    }
    if (noData) noData.style.display = 'none';

    reps.forEach((rep, idx) => {
        const base = rep.total_assigned || 1;
        const cW = Math.round((rep.contacted_count / base) * 100);
        const sW = Math.round((rep.sql_yes_count   / base) * 100);
        const qW = Math.round((rep.quoted_count    / base) * 100);
        const wW = Math.round((rep.win_count       / base) * 100);

        const tr = document.createElement('tr');
        tr.className = 'sr-row';
        tr.dataset.idx = idx;
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
            <td class="num-cell">${winBadge(rep.win_rate)}</td>
            <td class="num-cell money-cell">${fmtMoney(rep.total_win_amount)}</td>
            <td class="num-cell money-cell">${fmtMoney(rep.total_pipeline_value)}</td>
            <td>
                <div class="tracking-badges">
                    <span class="track-badge track-ns" title="Not Started">${rep.not_started_count}</span>
                    <span class="track-badge track-ip" title="In Progress">${rep.in_progress_count}</span>
                    <span class="track-badge track-co" title="Complete">${rep.complete_count}</span>
                </div>
            </td>
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
        if (typeof av === 'string') av = av.toLowerCase();
        if (typeof bv === 'string') bv = bv.toLowerCase();
        if (av < bv) return -1 * dir;
        if (av > bv) return  1 * dir;
        return 0;
    });
}
function setSortColumn(col) {
    State.sortDir = State.sortBy === col ? (State.sortDir === 'desc' ? 'asc' : 'desc') : 'desc';
    State.sortBy  = col;
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

    // Header
    setText('mAvatar', (rep.full_name || '?')[0].toUpperCase());
    document.getElementById('mAvatar').textContent = (rep.full_name || '?')[0].toUpperCase();
    setText('mName',  rep.full_name || '—');
    setText('mEmail', rep.email || '—');
    const br = document.getElementById('mBranch');
    if (rep.branch) { br.textContent = rep.branch; br.style.display = 'inline-block'; }
    else            { br.style.display = 'none'; }

    // Stats
    setText('mAssigned', fmt(rep.total_assigned));
    setText('mWinRate',  fmtPct(rep.win_rate));
    setText('mWinAmt',   fmtMoney(rep.total_win_amount));
    setText('mPipeline', fmtMoney(rep.total_pipeline_value));
    setText('mSql',      fmt(rep.sql_yes_count));
    setText('mSqlNo',    fmt(rep.sql_no_count));

    // Funnel
    const base = rep.total_assigned || 1;
    const funnel = [
        { label: 'Contacted',  count: rep.contacted_count, color: '#3B82F6' },
        { label: 'SQL Yes',    count: rep.sql_yes_count,   color: '#10B981' },
        { label: 'SQL No',     count: rep.sql_no_count,    color: '#EF4444' },
        { label: 'Quoted',     count: rep.quoted_count,    color: '#F59E0B' },
        { label: 'Win',        count: rep.win_count,       color: '#8B5CF6' },
    ];
    const funnelEl = document.getElementById('mFunnel');
    funnelEl.innerHTML = funnel.map(f => {
        const pct = Math.round((f.count / base) * 100);
        return `
            <div class="modal-funnel-row">
                <span class="modal-funnel-label">${f.label}</span>
                <div class="modal-funnel-bar-wrap">
                    <div class="modal-funnel-bar" style="width:${pct}%;background:${f.color};"></div>
                </div>
                <span class="modal-funnel-count">${f.count}</span>
                <span class="modal-funnel-pct">${fmtPct(pct)}</span>
            </div>`;
    }).join('');

    // Tracking status
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
    if (tbody) tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--danger);">⚠️ ${msg}</td></tr>`;
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    // Filters
    document.getElementById('filterDateFrom')?.addEventListener('change', e => {
        State.filters.date_from = e.target.value;
        fetchData();
    });
    document.getElementById('filterDateTo')?.addEventListener('change', e => {
        State.filters.date_to = e.target.value;
        fetchData();
    });
    document.getElementById('filterBranch')?.addEventListener('change', e => {
        State.filters.branch = e.target.value;
        fetchData();
    });

    // Search (client-side only, no re-fetch)
    document.getElementById('searchInput')?.addEventListener('input', renderTable);

    // Sort headers
    document.querySelectorAll('[data-sort]').forEach(th => {
        th.addEventListener('click', () => setSortColumn(th.dataset.sort));
    });

    // Modal close
    document.getElementById('closeDetailModal')?.addEventListener('click', closeDetail);
    document.getElementById('detailModal')?.addEventListener('click', e => {
        if (e.target === document.getElementById('detailModal')) closeDetail();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeDetail();
    });

    fetchData();
});
