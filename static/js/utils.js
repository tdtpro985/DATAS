// ---------- Utilities ----------

// API base path — uses BASE injected by the PHP page, falls back to '/new-dashboard/api/v1'
// BASE is set as: <script>const BASE = '<?= $base ?>';</script> in each PHP page
const API = (typeof BASE !== 'undefined' ? BASE : '/new-dashboard') + '/api/v1';
function formatCurrency(val) {
    if (val === null || val === undefined) return '₱0';
    const num = Number(val);
    if (num >= 1e9) return '₱' + (num / 1e9).toFixed(1) + 'B';
    if (num >= 1e6) return '₱' + (num / 1e6).toFixed(1) + 'M';
    if (num >= 1e3) return '₱' + (num / 1e3).toFixed(0) + 'K';
    return '₱' + num.toLocaleString();
}

function formatNumber(val) {
    if (val === null || val === undefined) return '0';
    return Number(val).toLocaleString();
}

function getStatusClass(status) {
    if (!status) return 'unknown';
    const s = status.toLowerCase().trim();
    if (s === 'priority') return 'priority';
    if (s === 'win' || s === 'awarded') return 'win';
    if (s === 'quoted' || s === 'for bidding' || s === 'bidding') return 'quoted';
    if (s === 'contacted') return 'contacted';
    if (s === 'prospect') return 'prospect';
    if (s === 'ongoing' || s === 'for execution' || s === 'execution') return 'ongoing';
    if (s === 'completed') return 'completed';
    return 'unknown';
}

async function fetchJSON(url) {
    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return await res.json();
    } catch (err) {
        console.error('Fetch error for ' + url + ':', err);
        return null;
    }
}

/**
 * API Request helper for making authenticated API calls
 * @param {string} endpoint - API endpoint (e.g., '/custom-forms')
 * @param {object} options - Fetch options (method, body, headers, etc.)
 * @returns {Promise} - Response data
 */
async function apiRequest(endpoint, options = {}) {
    const url = API + endpoint;
    
    const defaultOptions = {
        method: options.method || 'GET',
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        },
        credentials: 'same-origin'
    };
    
    if (options.body) {
        defaultOptions.body = options.body;
    }
    
    try {
        const response = await fetch(url, defaultOptions);
        
        // Parse JSON response
        const data = await response.json();
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(data.detail || `HTTP ${response.status}`);
        }
        
        return data;
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

function getApiUrl(path) {
    let url = API + path +
        (path.includes('?') ? '&' : '?') +
        'period=' + currentPeriod;
    const monthSelector = document.getElementById('month-selector');
    if (monthSelector) {
        url += '&month=' + monthSelector.value;
    }
    // Append region filter — 'all' means no filter (backend handles it)
    url += '&region=' + encodeURIComponent(currentRegion) + '&_t=' + new Date().getTime();
    console.log('[API] URL built:', url);
    return url;
}

