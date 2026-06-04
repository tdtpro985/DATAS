/* ============================================================
   TDT Powersteel Dashboard — Main Application Logic v3.0 (Role-Based)
   ============================================================ */

// ---------- Globals & Constants ----------
// API base path is defined in utils.js and injected by reports.php.
// Do not redeclare it here to avoid duplicate declaration errors.
const ROTATION_INTERVAL = 20000;
const MAP_ROTATION_INTERVAL = 12000;
const DATA_REFRESH_INTERVAL = 30000;  // All sync intervals aligned to 30 seconds

const ALLOWED_REGIONS = [
    "Metropolitan Manila",
    "Central Luzon (Region III)",
    "Ilocos Region (Region I)",
    "Cagayan Valley (Region II)",
    "CALABARZON (Region IV-A)",
    "Bicol Region (Region V)",
    "Western Visayas (Region VI)",
    "Central Visayas (Region VII)",
    "Davao Region (Region XI)"
];

const TARGETS = {
    daily: 30,
    weekly: 150,
    monthly: 600
};

// ---------- Global State ----------
let currentPeriod = 'daily';
let currentRegion = 'all';
let rotatingIndex = 0;
let rotatingData = [];
let rotationTimer = null;
let progressTimer = null;
let pieChart = null;
let targetChart = null;
let phMap = null;

let mapRotationIndex = -1;
let mapRotationTimer = null;
let isMapHovered = false;

// ---------- Audio System ----------
const AudioManager = {
    isUnlocked: false,
    ctx: null,
    buffer: null,

    init() {
        this.onUserGestureBound = this.onUserGesture.bind(this);
        ['click', 'keydown', 'touchstart'].forEach(evt => {
            document.addEventListener(evt, this.onUserGestureBound);
        });
        console.log('[AUDIO] Gesture listeners registered.');
    },

    async loadBuffer() {
        try {
            const response = await fetch((typeof BASE !== 'undefined' ? BASE : '/new-dashboard') + '/static/audio/priority-alert.mp3');
            const arrayBuffer = await response.arrayBuffer();
            this.buffer = await this.ctx.decodeAudioData(arrayBuffer);
            console.log('[AUDIO] Buffer loaded successfully.');
        } catch (e) {
            console.warn('[AUDIO] Buffer load failed:', e);
        }
    },

    async unlock() {
        try {
            if (!this.ctx) {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                this.ctx = new AudioContext();
            }

            if (this.ctx.state === 'suspended') {
                await this.ctx.resume();
            }

            if (!this.buffer) {
                await this.loadBuffer();
            }

            if (this.ctx.state === 'running' && this.buffer) {
                this.isUnlocked = true;
                console.log('[AUDIO] Audio fully unlocked.');
                this.hideBanner();
                this.removeListeners();
            }
        } catch (e) {
            console.warn('[AUDIO] Unlock error:', e);
        }
    },

    onUserGesture() {
        this.unlock();
    },

    removeListeners() {
        ['click', 'keydown', 'touchstart'].forEach(evt => {
            document.removeEventListener(evt, this.onUserGestureBound);
        });
    },

    hideBanner() {
        const banner = document.getElementById('audio-unlock-banner');
        if (banner) {
            banner.style.opacity = '0';
            setTimeout(() => { banner.style.display = 'none'; }, 300);
        }
    },

    playNotification() {
        try {
            if (this.isUnlocked && this.ctx?.state === 'running' && this.buffer) {
                const source = this.ctx.createBufferSource();
                source.buffer = this.buffer;
                const gainNode = this.ctx.createGain();
                gainNode.gain.value = 1.0;
                source.connect(gainNode);
                gainNode.connect(this.ctx.destination);
                source.start(0);
                console.log('[AUDIO] Notification played via Web Audio.');
            } else if (!this.isUnlocked) {
                const fallbackAudio = new Audio((typeof BASE !== 'undefined' ? BASE : '/new-dashboard') + '/static/audio/priority-alert.mp3');
                fallbackAudio.volume = 1.0;
                fallbackAudio.play().catch(e => console.log('[AUDIO] Fallback blocked:', e));
            }
        } catch (err) {
            console.error('[AUDIO] Play error:', err);
        }
    }
};

// ---------- Priority Projects Polling ----------
const ProjectPolling = {
    lastTimestamp: 0,
    queue: [],
    isShowing: false,
    interval: null,

    init() {
        this.check(true);
        this.interval = setInterval(() => this.check(false), 8000);
        console.log('[POLLING] Priority polling initialized.');
    },

    async check(isInitialLoad = false) {
        try {
            const res = await fetch(API + '/latest_priority');
            if (!res.ok) return;
            const data = await res.json();

            if (isInitialLoad) {
                this.lastTimestamp = data.timestamp;
                return;
            }

            if (data?.timestamp > this.lastTimestamp) {
                this.lastTimestamp = data.timestamp;
                if (data.new_arrivals?.length > 0) {
                    data.new_arrivals.forEach(item => this.queue.push(item));
                    this.processQueue();
                }
            }
        } catch (e) {
            console.error("[POLLING] Failed to poll priority projects:", e);
        }
    },

    processQueue() {
        if (this.isShowing || this.queue.length === 0) return;
        const nextData = this.queue.shift();
        this.isShowing = true;
        this.showPopup(nextData, () => {
            this.isShowing = false;
            setTimeout(() => this.processQueue(), 500);
        });
    },

    showPopup(data, onComplete) {
        if (!data) {
            onComplete?.();
            return;
        }

        AudioManager.playNotification();

        // Populate Popup Data
        const fields = {
            'priority-source': data['Source'],
            'priority-contractor': data['Contractor'],
            'priority-person': data['Contact Person'],
            'priority-number': data['Contact Number'],
            'priority-name': data['Project Name'],
            'priority-sptype': data['Sheet Pile Type'],
            'priority-bottom-accom': data['Accomplishment Rate']
        };

        Object.entries(fields).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val || '—';
        });

        // Combined Location
        const locId = 'priority-location-combined';
        const locParts = [data['City'], data['Province']].filter(Boolean).join(', ');
        const locFull = [data['Region'], locParts].filter(Boolean).join('<br>');
        document.getElementById(locId).innerHTML = locFull || '—';

        // Address (with newlines)
        const addrEl = document.getElementById('priority-address');
        if (addrEl) addrEl.innerHTML = (data['Address'] || '—').replace(/\n/g, '<br>');

        // Currency Formatting
        const formatPHP = (val) => {
            const num = parseFloat(String(val || '0').replace(/,/g, '').replace(/[PHP₱]/g, ''));
            return !isNaN(num) && num > 0 ? 
                new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(num) : '—';
        };

        document.getElementById('priority-spamount').textContent = formatPHP(data['Sheet Pile Amount']);
        document.getElementById('priority-drbs-value').textContent = formatPHP(data['DRBs Value']);
        document.getElementById('priority-value').textContent = formatPHP(data['Value']);

        // DRBs
        const drbsEl = document.getElementById('priority-drbs');
        if (drbsEl) drbsEl.innerHTML = (data['DRBs'] || '—').replace(/\n/g, '<br>');

        // Status Logic
        this.updatePopupStatus(data);
        this.renderGallery(data.Pictures || []);

        // Show Overlay
        const overlay = document.getElementById('priority-popup-overlay');
        if (overlay) {
            overlay.classList.add('active');
            this.startPopupCountdown(overlay, onComplete);
        }
    },

    renderGallery(pictures) {
        const gallery = document.getElementById('priority-gallery');
        const grid = document.getElementById('priority-gallery-grid');
        const empty = document.getElementById('priority-gallery-empty');

        if (!gallery || !grid || !empty) return;

        const urls = Array.isArray(pictures) ? pictures.filter(Boolean) : [];
        grid.innerHTML = '';

        if (!urls.length) {
            gallery.hidden = false;
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';
        gallery.hidden = false;

        const maxVisible = 6;
        const visibleUrls = urls.slice(0, maxVisible);
        const remainingCount = urls.length - maxVisible;

        visibleUrls.forEach((url, index) => {
            const item = document.createElement('div');
            item.className = 'priority-gallery-item';
            const img = document.createElement('img');
            img.src = url;
            img.alt = `Priority photo ${index + 1}`;
            img.onerror = () => { img.style.display = 'none'; };
            item.appendChild(img);

            if (index === maxVisible - 1 && remainingCount > 0) {
                item.dataset.overlay = `+${remainingCount} more`;
            }

            grid.appendChild(item);
        });
    },

    updatePopupStatus(data) {
        const isTruthy = (v) => ['yes', 'y', 'true', '1', 'x', 'checked', 'ok'].includes(String(v || '').trim().toLowerCase());
        const explicitStatus = String(data['Status'] || '').trim();
        
        let label = '', value = '';
        if (explicitStatus) { label = 'Status:'; value = explicitStatus; }
        else if (isTruthy(data['Win'])) { label = 'Win:'; value = data['Win']; }
        else if (isTruthy(data['Quoted'])) { label = 'Quoted:'; value = data['Quoted']; }
        else if (isTruthy(data['Contacted'])) { label = 'Contacted:'; value = data['Contacted']; }
        else if (isTruthy(data['Sales Qualified Leads'])) { label = 'Sales Qualified Leads:'; value = data['Sales Qualified Leads']; }
        else if (isTruthy(data['Not Sales Qualified'])) { label = 'Not Sales Qualified:'; value = data['Not Sales Qualified']; }

        const row = document.getElementById('priority-status-row');
        const lbl = document.getElementById('priority-status-label');
        const val = document.getElementById('priority-status');

        if (row && lbl && val) {
            if (label) {
                lbl.textContent = label + ' ';
                val.textContent = explicitStatus ? value : value.toUpperCase();
                row.style.display = 'block';
            } else {
                row.style.display = 'none';
            }
        }
    },

    startPopupCountdown(overlay, onComplete) {
        const progress = document.getElementById('priority-timer-progress');
        const timerText = document.getElementById('priority-timer-text');
        const closeBtn = document.getElementById('priority-close');

        if (progress) progress.style.width = '100%';
        if (timerText) timerText.textContent = 'Closing in 60s';
        if (closeBtn) closeBtn.disabled = false; // Enabled immediately for better UX

        let secondsLeft = 60;
        const countdown = setInterval(() => {
            secondsLeft--;
            if (progress) progress.style.width = `${(secondsLeft / 60) * 100}%`;
            if (timerText) timerText.textContent = `Closing in ${secondsLeft}s`;

            if (secondsLeft <= 0) {
                clearInterval(countdown);
                if (timerText) timerText.textContent = 'Closing...';
                overlay.classList.remove('active');
                onComplete?.();
            }
        }, 1000);

        // Replace button to clear old listeners and set up new close listener
        const newBtn = closeBtn.cloneNode(true);
        closeBtn.parentNode.replaceChild(newBtn, closeBtn);
        newBtn.disabled = false; 
        newBtn.addEventListener('click', () => {
            console.log('[POLLING] Popup dismissed by user.');
            clearInterval(countdown);
            overlay.classList.remove('active');
            onComplete?.();
        });
    }
};

// ---------- Navigation & Filters ----------
const Navigation = {
    init() {
        this.initPeriodTabs();
        this.initRegionFilter();
        this.initMobileNav();
        console.log('[NAV] Navigation initialized.');
    },

    initPeriodTabs() {
        const select = document.getElementById('period-select');
        if (select) {
            currentPeriod = select.value || currentPeriod;
            select.addEventListener('change', (e) => {
                currentPeriod = e.target.value;
                this.onFilterChange();
            });
        }

        const monthSelector = document.getElementById('month-selector');
        if (monthSelector) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const year = new Date().getFullYear();
            const month = new Date().getMonth() + 1;
            monthSelector.innerHTML = months.map((m, i) => `<option value="${i + 1}">${m} ${year}</option>`).join('');
            monthSelector.value = month;
            monthSelector.addEventListener('change', () => SyncManager.refreshAllWithRanking());
        }

        document.querySelectorAll('#period-tabs .toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#period-tabs .toggle-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentPeriod = btn.dataset.period;
                this.onFilterChange();
            });
        });
    },

    initRegionFilter() {
        const select = document.getElementById('region-filter');
        if (!select) return;
        select.addEventListener('change', (e) => {
            currentRegion = e.target.value;
            select.classList.toggle('filter-active', currentRegion !== 'all');
            this.onFilterChange();
        });
    },

    onFilterChange() {
        rotatingData = [];
        rotatingIndex = 0;
        if (typeof resetFunnelState === 'function') resetFunnelState();
        SyncManager.refreshAllWithRanking();
    },

    initMobileNav() {
        const ham = document.getElementById('hamburger-menu'), close = document.getElementById('close-menu'),
              over = document.getElementById('mobile-nav-overlay'), nav = document.getElementById('mobile-nav'),
              items = document.querySelectorAll('.nav-item');

        if (!ham || !nav) return;

        const toggleMenu = (show) => {
            nav.classList.toggle('active', show);
            over.classList.toggle('active', show);
            document.body.style.overflow = show ? 'hidden' : '';
        };

        ham.addEventListener('click', () => toggleMenu(true));
        [close, over].forEach(el => el?.addEventListener('click', () => toggleMenu(false)));

        items.forEach(item => {
            item.addEventListener('click', (e) => {
                const target = item.getAttribute('data-target');
                items.forEach(i => i.classList.remove('active'));
                item.classList.add('active');

                if (window.innerWidth < 1024) {
                    e.preventDefault();
                    this.switchMobileTab(target);
                    toggleMenu(false);
                    window.scrollTo(0, 0);
                }
            });
        });

        this.initScrollObserver(items);
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1024 && !document.body.classList.contains('mobile-tab-mode')) {
                const active = document.querySelector('.nav-item.active')?.getAttribute('data-target') || 'rotating-card';
                this.switchMobileTab(active);
            } else if (window.innerWidth >= 1024) {
                document.body.classList.remove('mobile-tab-mode');
                document.querySelectorAll('.dashboard .card').forEach(s => s.classList.remove('mobile-active-tab'));
            }
        });
    },

    switchMobileTab(targetId) {
        if (window.innerWidth >= 1024) return;
        document.body.classList.add('mobile-tab-mode');
        const sections = document.querySelectorAll('.dashboard .card');
        const performance = ['kpi-section', 'target-section', 'kpi-detail-section'];

        sections.forEach(sec => {
            if (sec.id === 'header') return;
            const show = (targetId === 'performance-group') ? performance.includes(sec.id) : (sec.id === targetId);
            sec.classList.toggle('mobile-active-tab', show);
        });

        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
            if (targetId === 'map-section' && window.phMap) {
                window.phMap.invalidateSize();
                window.renderMapLabelsOverlay?.();
            }
            if (targetId === 'ranking-section' && typeof startContractorScroll === 'function') startContractorScroll();
        }, 150);
    },

    initScrollObserver(navItems) {
        const sections = Array.from(document.querySelectorAll('section.card, header.card'));
        const observer = new IntersectionObserver((entries) => {
            if (window.innerWidth < 1024) return;
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    const link = document.querySelector(`.nav-item[data-target="${id}"]`);
                    if (link) {
                        navItems.forEach(i => i.classList.remove('active'));
                        link.classList.add('active');
                    }
                }
            });
        }, { root: null, rootMargin: '-20% 0px -70% 0px', threshold: 0 });
        sections.forEach(s => observer.observe(s));
    }
};

// ---------- Data Synchronization ----------
const SyncManager = {
    init() {
        this.refreshAllWithRanking();
        // Single unified interval — all syncing aligned to DATA_REFRESH_INTERVAL (30s)
        setInterval(() => this.refreshAll(), DATA_REFRESH_INTERVAL);
        console.log('[SYNC] Sync manager initialized. Interval: ' + (DATA_REFRESH_INTERVAL / 1000) + 's');
    },

    async refreshAllWithRanking() {
        const data = await loadKPI();
        await Promise.all([
            loadTargetChart(data), loadRanking(), loadPieChart(),
            loadFunnel(), loadRotatingCard(), updateMap(), this.updateStatus()
        ]);
    },

    async refreshAll() {
        const badge = document.getElementById('sync-badge');
        if (badge) badge.classList.add('syncing');
        const data = await loadKPI();
        await Promise.all([
            loadTargetChart(data), loadRanking(), loadPieChart(),
            loadFunnel(), loadRotatingCard(), updateMap(), this.updateStatus()
        ]);
        if (badge) setTimeout(() => badge.classList.remove('syncing'), 1000);
    },

    lastKnownSync: null,
    async updateStatus() {
        const data = await fetchJSON(API + '/sync-status');
        const el = document.getElementById('last-sync');
        if (el && data?.last_sync?.finished_at) {
            const finished = data.last_sync.finished_at;

            // If sync timestamp changed, data is already being refreshed by refreshAll()
            if (this.lastKnownSync && this.lastKnownSync !== finished) {
                console.log(`[SYNC] New ETL sync detected (${finished}).`);
            }
            this.lastKnownSync = finished;

            const d = new Date(finished.endsWith('Z') ? finished : finished + 'Z');
            el.textContent = 'Synced ' + d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
        } else if (el) {
            el.textContent = 'Live';
        }
    }
};

// ---------- Main UI Features ----------
const MainUI = {
    init() {
        this.updateClock();
        setInterval(() => this.updateClock(), 1000);
        this.setMonthSelectorVisible(true);
        this.initMapHover();
        this.initResizeHandler();
        console.log('[UI] Main UI features initialized.');
    },

    updateClock() {
        const el = document.getElementById('clock');
        if (el) {
            el.textContent = new Date().toLocaleTimeString('en-PH', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
            });
        }
    },

    setMonthSelectorVisible(visible) {
        const wrapper = document.getElementById('month-control-group');
        if (wrapper) wrapper.classList.toggle('visible', visible);
    },

    initMapHover() {
        const mapEl = document.getElementById('ph-map');
        if (mapEl) {
            mapEl.addEventListener('mouseenter', () => { isMapHovered = true; });
            mapEl.addEventListener('mouseleave', () => { isMapHovered = false; });
        }
    },

    initResizeHandler() {
        let timer;
        window.addEventListener('resize', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                if (pieChart) loadPieChart();
                if (window.phMap) {
                    window.phMap.invalidateSize();
                    updateMap();
                }
            }, 250);
        });
    }
};

// ---------- About / Credits Modal ----------
const AboutModal = {
    init() {
        const btn     = document.getElementById('about-btn');
        const overlay = document.getElementById('about-modal-overlay');
        const closeBtn = document.getElementById('about-modal-close');
        const yearEl  = document.getElementById('about-year');

        if (yearEl) yearEl.textContent = new Date().getFullYear();

        if (!btn || !overlay) return;

        btn.addEventListener('click', () => overlay.classList.add('active'));
        closeBtn?.addEventListener('click', () => overlay.classList.remove('active'));
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('active');
        });

        console.log('[ABOUT] Credits modal initialized.');
    }
};

// ---------- App Initialization ----------
const App = {
    async init() {
        console.log('[APP] Starting Dashboard Application...');

        // 1. Initialize role manager FIRST — sets data-role on body.
        //    All subsequent CSS visibility and JS module init depends on this.
        const role = await RoleManager.init();

        // 2. Core UI modules — always initialize regardless of role
        AudioManager.init();
        Navigation.init();
        ProjectPolling.init();
        MainUI.init();

        // 3. Role-specific modules — only initialize when the role permits
        if (RoleManager.can('update_status')) {
            // sales_rep and superadmin: wire up the Update Status modal
            StatusUpdate.init();
        }

        // 4. Dashboard data sync — only for roles that can view the dashboard
        if (RoleManager.can('view_dashboard')) {
            SyncManager.init();
            if (typeof initMap === 'function') initMap();
        }

        // 5. Superadmin: inject Admin Panel link next to role badge
        if (role === 'superadmin') {
            const badgeContainer = document.getElementById('role-badge-container');
            if (badgeContainer) {
                const adminLink = document.createElement('a');
                adminLink.href = (typeof BASE !== 'undefined' ? BASE : '') + '/admin';
                adminLink.className = 'nav-admin-link';
                adminLink.title = 'Open Admin Panel';
                adminLink.innerHTML = '⚙️ Admin';
                badgeContainer.insertAdjacentElement('afterend', adminLink);
            }
        }

        // 6. Wire logout button
        const logoutBtn = document.getElementById('nav-logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                if (typeof Auth !== 'undefined') {
                    Auth.logout();
                } else {
                    window.location.href = (typeof BASE !== 'undefined' ? BASE : '') + '/login';
                }
            });
        }

        AboutModal.init();
        console.log('[APP] Dashboard Application Ready. Role:', role);
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
