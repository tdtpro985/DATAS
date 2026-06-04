// ---------- KPI (Summary — 3 cards, filtered by period) ----------
async function loadKPI() {
    const apiUrl = getApiUrl('/kpi');
    console.log('[KPI] Fetching from:', apiUrl);
    const data = await fetchJSON(apiUrl);
    console.log('[KPI] Response:', data);
    if (!data || !data.data) {
        console.warn('[KPI] No data returned');
        return null;
    }

    const d = data.data;
    console.log('[KPI] Setting values - projects:', d.projects_encoded, 'contractors:', d.contractors_identified, 'value:', d.total_pipeline_value);
    
    const projectsEl = document.getElementById('kpi-projects-val');
    const contractorsEl = document.getElementById('kpi-contractors-val');
    const pipelineEl = document.getElementById('kpi-pipeline-val');
    
    console.log('[KPI] DOM elements found:', !!projectsEl, !!contractorsEl, !!pipelineEl);
    
    if (projectsEl) projectsEl.textContent = formatNumber(d.projects_encoded);
    if (contractorsEl) contractorsEl.textContent = formatNumber(d.contractors_identified);
    if (pipelineEl) pipelineEl.textContent = formatCurrency(d.total_pipeline_value);

    // Load category breakdown table
    loadCategoryTable(data);
    return data;
}

function loadCategoryTable(kpiData) {
    const tbody = document.getElementById('kpi-detail-body');
    if (!kpiData || !kpiData.data) return;

    const d = kpiData.data;
    const total = d.total_pipeline_value || 1;

    const statuses = [];
    for (const [key, val] of Object.entries(d)) {
        if (typeof val === 'object' && val !== null && val.count !== undefined) {
            statuses.push({
                name: key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); }),
                count: val.count || 0,
                value: val.value || 0,
                pct: total > 0 ? ((val.value || 0) / total * 100) : 0
            });
        }
    }

    if (statuses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="loading-text">No data</td></tr>';
        return;
    }

    tbody.innerHTML = statuses.map(function (s) {
        return '<tr>' +
            '<td data-label="Status"><span class="status-badge ' + getStatusClass(s.name) + '">' + s.name + '</span></td>' +
            '<td data-label="Count">' + formatNumber(s.count) + '</td>' +
            '<td data-label="Value (₱)" class="value-cell">' + formatCurrency(s.value) + '</td>' +
            '<td data-label="%">' + s.pct.toFixed(1) + '% <span class="pct-bar" style="width:' + Math.min(s.pct, 100) * 0.5 + 'px"></span></td>' +
            '</tr>';
    }).join('');
}


// ---------- Contractor List (filtered by period) ----------
let lastRankingData = null;

async function loadRanking() {
    const data = await fetchJSON(getApiUrl('/contractors/ranking?page=1&size=500'));
    const tbody = document.getElementById('ranking-body');
    if (!data || !data.contractors) {
        if (!lastRankingData) {
            tbody.innerHTML = '<tr><td colspan="4" class="loading-text">No data</td></tr>';
        }
        return;
    }

    // Compare with last data to avoid flickering/resetting scroll if nothing changed
    const currentDataStr = JSON.stringify(data.contractors);
    if (lastRankingData === currentDataStr) {
        return;
    }
    lastRankingData = currentDataStr;

    if (data.contractors.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="loading-text">No data</td></tr>';
        return;
    }

    const rows = data.contractors.map(function (c, i) {
        /**
         * Update Status button — conditionally rendered based on role and module availability.
         * Only shown when:
         *   1. The StatusUpdate module has been loaded and initialized (typeof check guards
         *      against the module not being present for non-privileged roles).
         *   2. The current user's role has the 'update_status' permission (sales_rep, superadmin).
         * For all other roles (admin, encoder, unknown), updateBtn is an empty string and
         * the action-cell column is rendered empty, preserving the 4-column table structure.
         */
        const updateBtn = (typeof StatusUpdate !== 'undefined' && RoleManager.can('update_status'))
            ? `<button class="update-status-btn"
                       data-project-id="${c.project_id}"
                       data-project-name="${c.project_name || ''}"
                       data-current-status="${c.status || ''}"
                       aria-label="Update status for ${c.contractor_name}">
                   ✏️
               </button>`
            : '';

        return '<tr>' +
            '<td data-label="#" class="rank-num">' + (i + 1) + '</td>' +
            '<td data-label="Contractor" title="' + (c.contractor_name || '') + '">' +
                (c.contractor_name || '') + '</td>' +
            '<td data-label="Value (₱)" class="value-cell">' +
                formatCurrency(c.total_value) + '</td>' +
            '<td class="action-cell">' + updateBtn + '</td>' +
            '</tr>';
    }).join('');

    tbody.innerHTML = rows;
    delete tbody.dataset.doubled; // Reset scroll logic state for new data

    // Start/Restart auto-scrolling after a double-rAF so the browser
    // has time to reflow the newly injected rows before measuring heights.
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            startContractorScroll();
        });
    });
}

// ---------- Auto-Scroll Logic for Contractor List ----------
let contractorScrollInterval = null;

function startContractorScroll() {
    const wrapper = document.querySelector('#ranking-section .table-wrapper');
    const tbody = document.getElementById('ranking-body');
    if (!wrapper || !tbody) return;

    // Clear existing interval
    if (contractorScrollInterval) {
        clearInterval(contractorScrollInterval);
        contractorScrollInterval = null;
    }

    // Measure height - if we are hidden (e.g. tablet switch), height is 0.
    // However, if we're hidden, we skip the measurement and wait for the next trigger.
    const containerHeight = wrapper.clientHeight;
    if (containerHeight === 0) return;

    // Always start from top
    wrapper.scrollTop = 0;

    // Use a custom property to avoid infinite doubling on multiple calls
    if (!tbody.dataset.doubled) {
        const originalContentHeight = tbody.scrollHeight;
        const rowCount = tbody.querySelectorAll('tr').length;

        // If it overflows OR if there are enough items to justify a scroll (e.g. > 10)
        // This ensures shorter lists still behave like a live feed.
        if (originalContentHeight > containerHeight || rowCount > 10) {
            tbody.dataset.doubled = "true";
            tbody.dataset.originalHeight = originalContentHeight;
            
            const originalHTML = tbody.innerHTML;
            
            // Calculate how many times we need to append the html to comfortably fill 
            // the container plus original content, allowing seamless scroll.
            // Target total height >= originalContentHeight + containerHeight
            // Since we know originalContentHeight, we can just copy it N times.
            // We need: (N + 1) * originalContentHeight >= originalContentHeight + containerHeight
            // N * originalContentHeight >= containerHeight
            // N = Math.ceil(containerHeight / originalContentHeight)
            // But to be perfectly safe against fractional pixel rounding and exactly-matching 
            // bounds, it's best to add + 1.
            const requiredCopies = Math.max(1, Math.ceil(containerHeight / originalContentHeight) + 1);
            
            for (let i = 0; i < requiredCopies; i++) {
                tbody.innerHTML += originalHTML;
            }
        } else {
            return; // No scroll needed for very tiny lists
        }
    }

    const originalHeight = parseInt(tbody.dataset.originalHeight);
    const speed = 1;        // Pixels per tick
    const intervalTime = 50; // ms per tick

    contractorScrollInterval = setInterval(() => {
        if (wrapper.matches(':hover')) return;

        wrapper.scrollTop += speed;

        // Seamless loop back to top
        if (wrapper.scrollTop >= originalHeight) {
            wrapper.scrollTop = 0;
        }
    }, intervalTime);
}


// ---------- Rotating Card (filtered by period, highest value first) ----------
async function loadRotatingCard() {
    const data = await fetchJSON(getApiUrl('/contractors/rotating-card'));
    if (!data || !data.contractors || data.contractors.length === 0) {
        // Show empty state cleanly
        rotatingData = [];
        document.getElementById('rc-name').textContent = 'No data for this period';
        document.getElementById('rc-contact').textContent = '—';
        document.getElementById('rc-phone').textContent = '—';
        document.getElementById('rc-project').textContent = '—';
        document.getElementById('rc-value').textContent = '—';
        document.getElementById('rc-status').textContent = '—';
        document.getElementById('card-counter').textContent = '0 / 0';
        // Clear stale item tags from previous month
        const itemsContainer = document.getElementById('rc-items');
        if (itemsContainer) itemsContainer.innerHTML = '';
        return;
    }

    // Data already sorted by value DESC from API
    // Only reset if first load
    const isFirstLoad = (rotatingData.length === 0);
    rotatingData = data.contractors;

    if (isFirstLoad) {
        rotatingIndex = 0;
        startRotation();
    } else {
        // Ensure index is valid for new data length
        rotatingIndex = rotatingIndex % rotatingData.length;
    }

    renderCard();
}

function renderCard() {
    if (rotatingData.length === 0) return;
    var c = rotatingData[rotatingIndex];
    var content = document.getElementById('rotating-content');

    content.classList.add('fade-out');

    setTimeout(function () {
        document.getElementById('rc-name').textContent = c.contractor_name || '—';
        document.getElementById('rc-contact').textContent = c.contact_person || '—';
        document.getElementById('rc-phone').textContent = c.contact_number || '—';
        document.getElementById('rc-project').textContent = c.project_name || '—';
        document.getElementById('rc-value').textContent = formatCurrency(c.value_php);
        document.getElementById('rc-status').innerHTML =
            '<span class="status-badge ' + getStatusClass(c.status) + '">' + (c.status || '—') + '</span>';
        document.getElementById('card-counter').textContent =
            (rotatingIndex + 1) + ' / ' + rotatingData.length + ' · ' + (ROTATION_INTERVAL / 1000) + 's';

        // Render items (Limit to top 6 to prevent overflow)
        const itemsContainer = document.getElementById('rc-items');
        if (itemsContainer) {
            if (c.items && c.items.length > 0) {
                const MAX_ITEMS = 10;
                const displayItems = c.items.slice(0, MAX_ITEMS);
                let html = displayItems.map(item => {
                    // Ultra-strict parsing: Strip everything except numbers, decimal, and minus sign
                    const raw = String(item.value || '0').replace(/[^0-9.-]+/g, "");
                    const val = parseFloat(raw);

                    // If parsing fails or yields nothing, fallback to raw or 0.00
                    const formattedVal = isNaN(val) ? (item.value || '0.00') :
                        '₱' + val.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    return `<div class="item-tag">
                        <span class="item-label">${item.label}</span>
                        <span class="item-value">${formattedVal}</span>
                    </div>`;
                }).join('');


                if (c.items.length > MAX_ITEMS) {
                    html += `<div class="item-tag" style="background:transparent;border:none;color:var(--text-secondary);">+${c.items.length - MAX_ITEMS} more</div>`;
                }
                itemsContainer.innerHTML = html;
            } else {
                itemsContainer.innerHTML = '';
            }
        }

        content.classList.remove('fade-out');

        // Auto-fit font size after content update
        requestAnimationFrame(() => {
            fitRotatingCardContent();
        });
    }, 350);
}

/**
 * Dynamically scales down font sizes in the Live Contractor card 
 * if content overflows its container.
 */
function fitRotatingCardContent() {
    const content = document.getElementById('rotating-content');
    if (!content) return;

    // Reset to defaults first to measure correctly
    content.style.removeProperty('--rc-name-size');
    content.style.removeProperty('--rc-detail-size');

    // Small delay to let the browser layout catch up after property removal
    requestAnimationFrame(() => {
        let nameSize = 100;
        let detailSize = 100;
        const MIN_SIZE = 55; // Allow to go slightly smaller if needed
        const STEP = 5;

        // Iteratively shrink until it fits or reaches minimum
        // We use a safety counter to avoid infinite loops
        let safety = 0;
        while (content.scrollHeight > content.clientHeight && safety < 20) {
            safety++;
            if (nameSize > MIN_SIZE) nameSize -= STEP;
            else if (detailSize > MIN_SIZE) detailSize -= STEP;
            else break;

            const nameClamp = `clamp(0.7rem, ${2.2 * (nameSize / 100)}vw, ${2.7 * (nameSize / 100)}rem)`;
            const detailClamp = `clamp(0.6rem, ${1 * (detailSize / 100)}vw, ${1.25 * (detailSize / 100)}rem)`;

            content.style.setProperty('--rc-name-size', nameClamp);
            content.style.setProperty('--rc-detail-size', detailClamp);
        }

        if (safety > 0) {
            console.log(`[RotatingCard] Scaled to ${nameSize}% to fit content.`);
        }
    });
}

// Ensure it fits on window resize
window.addEventListener('resize', () => {
    fitRotatingCardContent();
});

function startRotation() {
    if (rotationTimer) clearInterval(rotationTimer);
    if (progressTimer) clearInterval(progressTimer);

    var progressBar = document.getElementById('rc-progress');
    var counterEl = document.getElementById('card-counter');
    var elapsed = 0;
    var step = 100;

    progressTimer = setInterval(function () {
        elapsed += step;
        var progress = (elapsed / ROTATION_INTERVAL);
        progressBar.style.width = (progress * 100) + '%';

        // Update numeric countdown every second
        if (elapsed % 1000 === 0) {
            var remaining = Math.ceil((ROTATION_INTERVAL - elapsed) / 1000);
            if (counterEl && rotatingData.length > 0) {
                counterEl.textContent = (rotatingIndex + 1) + ' / ' + rotatingData.length + ' · ' + remaining + 's';
            }
        }
    }, step);

    rotationTimer = setInterval(function () {
        rotatingIndex = (rotatingIndex + 1) % rotatingData.length;
        renderCard();
        elapsed = 0;
        progressBar.style.width = '0%';
    }, ROTATION_INTERVAL);
}

