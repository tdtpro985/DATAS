<?php
session_start();
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

if ($role === 'encoder') {
    header('Location: ' . $base . '/encode');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDT Powersteel — Dashboard</title>
    <link rel="icon" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            overflow-x: hidden;
        }

        .dashboard {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: #1e293b;
            padding: 1rem 2rem;
            border-bottom: 2px solid #ff8000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #ff8000, #ffa500);
            border-radius: 6px;
        }

        .title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
        }

        .title .brand {
            color: #ff8000;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .filter-label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        select {
            background: #334155;
            border: 1px solid #475569;
            color: #fff;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ff8000'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.25rem;
        }

        select:hover {
            border-color: #ff8000;
        }

        .time-display {
            font-size: 0.875rem;
            color: #94a3b8;
        }

        /* Main Content */
        .content {
            flex: 1;
            padding: 2rem;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Cards */
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .card-title {
            font-size: 0.875rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border: 1px solid #475569;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: #ff8000;
            margin-bottom: 0.5rem;
        }

        .kpi-label {
            font-size: 0.875rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #334155;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            color: #ff8000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #334155;
            font-size: 0.875rem;
        }

        tr:hover {
            background: #334155;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #334155;
            border-top-color: #ff8000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Error State */
        .error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .content {
                padding: 1rem;
            }

            .filters {
                width: 100%;
            }

            .filter-group {
                flex: 1;
                min-width: 150px;
            }

            .kpi-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <div class="logo"></div>
                <h1 class="title"><span class="brand">TDT</span> Powersteel</h1>
            </div>
            <div class="header-right">
                <div class="filters">
                    <div class="filter-group">
                        <label class="filter-label">Period</label>
                        <select id="period-select">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Month</label>
                        <select id="month-select">
                            <option value="all">All Months</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Region</label>
                        <select id="region-select">
                            <option value="all">All Regions</option>
                            <option value="NCR">NCR</option>
                            <option value="I">Region I</option>
                            <option value="II">Region II</option>
                            <option value="III">Region III</option>
                            <option value="IV-A">Region IV-A</option>
                            <option value="V">Region V</option>
                        </select>
                    </div>
                </div>
                <div class="time-display" id="current-time">--:--:--</div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="content">
            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-value" id="kpi-projects">0</div>
                    <div class="kpi-label">Projects</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" id="kpi-contractors">0</div>
                    <div class="kpi-label">Contractors</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" id="kpi-value">₱0</div>
                    <div class="kpi-label">Total Value</div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Contractors Card -->
                <div class="card">
                    <h2 class="card-title">Top Contractors</h2>
                    <div id="contractors-content">
                        <div class="loading">
                            <div class="spinner"></div>
                            <div>Loading...</div>
                        </div>
                    </div>
                </div>

                <!-- Regional Stats Card -->
                <div class="card">
                    <h2 class="card-title">Regional Stats</h2>
                    <div id="regional-content">
                        <div class="loading">
                            <div class="spinner"></div>
                            <div>Loading...</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const BASE = '<?= $base ?>';
        
        // State
        const state = {
            filters: {
                period: 'monthly',
                month: 'all',
                region: 'all'
            }
        };

        // Utility Functions
        function formatNumber(num) {
            if (num >= 1000000) {
                return '₱' + (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return '₱' + (num / 1000).toFixed(1) + 'K';
            }
            return '₱' + num.toLocaleString();
        }

        async function fetchAPI(endpoint, fallback = {}) {
            try {
                const params = new URLSearchParams();
                if (state.filters.period) params.append('period', state.filters.period);
                if (state.filters.month && state.filters.month !== 'all') {
                    const [month, year] = state.filters.month.split('-');
                    params.append('month', month);
                    params.append('year', year);
                }
                if (state.filters.region && state.filters.region !== 'all') {
                    params.append('region', state.filters.region);
                }

                const url = `${BASE}/api/v1/${endpoint}?${params.toString()}`;
                const response = await fetch(url);
                
                if (!response.ok) {
                    console.error(`API error: ${endpoint}`, response.status);
                    return fallback;
                }
                
                return await response.json();
            } catch (error) {
                console.error(`Fetch error: ${endpoint}`, error);
                return fallback;
            }
        }

        // Load KPI Data
        async function loadKPI() {
            const data = await fetchAPI('kpi', { data: { projects_encoded: 0, contractors_identified: 0, total_pipeline_value: 0 } });
            
            document.getElementById('kpi-projects').textContent = (data.data?.projects_encoded || 0).toLocaleString();
            document.getElementById('kpi-contractors').textContent = (data.data?.contractors_identified || 0).toLocaleString();
            document.getElementById('kpi-value').textContent = formatNumber(data.data?.total_pipeline_value || 0);
        }

        // Load Contractors
        async function loadContractors() {
            const container = document.getElementById('contractors-content');
            container.innerHTML = '<div class="loading"><div class="spinner"></div><div>Loading...</div></div>';

            const data = await fetchAPI('contractors/ranking', { contractors: [] });
            
            if (!data.contractors || data.contractors.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:2rem;">No data available</div>';
                return;
            }

            const top10 = data.contractors.slice(0, 10);
            const html = `
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Contractor</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${top10.map((c, i) => `
                                <tr>
                                    <td>${i + 1}</td>
                                    <td>${c.contractor_name || 'N/A'}</td>
                                    <td>${formatNumber(c.total_value || 0)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            container.innerHTML = html;
        }

        // Load Regional Stats
        async function loadRegionalStats() {
            const container = document.getElementById('regional-content');
            container.innerHTML = '<div class="loading"><div class="spinner"></div><div>Loading...</div></div>';

            const data = await fetchAPI('charts/regional-stats', { regions: [], projectCounts: [], values: [] });
            
            if (!data.regions || data.regions.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:2rem;">No data available</div>';
                return;
            }

            const html = `
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Region</th>
                                <th>Projects</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.regions.map((region, i) => `
                                <tr>
                                    <td>${region}</td>
                                    <td>${data.projectCounts[i] || 0}</td>
                                    <td>${formatNumber(data.values[i] || 0)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            container.innerHTML = html;
        }

        // Load Available Months
        async function loadAvailableMonths() {
            const data = await fetchAPI('available-months', { months: [] });
            const select = document.getElementById('month-select');
            
            select.innerHTML = '<option value="all">All Months</option>';
            
            if (data.months && data.months.length > 0) {
                data.months.forEach((month, index) => {
                    const option = document.createElement('option');
                    option.value = month.value;
                    option.textContent = `${month.label} (${month.project_count})`;
                    if (index === 0) option.selected = true;
                    select.appendChild(option);
                });
            }
        }

        // Refresh All Data
        async function refreshData() {
            await Promise.all([
                loadKPI(),
                loadContractors(),
                loadRegionalStats()
            ]);
        }

        // Update Clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-PH', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('current-time').textContent = timeStr;
        }

        // Event Listeners
        document.getElementById('period-select').addEventListener('change', (e) => {
            state.filters.period = e.target.value;
            refreshData();
        });

        document.getElementById('month-select').addEventListener('change', (e) => {
            state.filters.month = e.target.value;
            refreshData();
        });

        document.getElementById('region-select').addEventListener('change', (e) => {
            state.filters.region = e.target.value;
            refreshData();
        });

        // Initialize
        async function init() {
            updateClock();
            setInterval(updateClock, 1000);
            
            await loadAvailableMonths();
            await refreshData();
            
            // Auto-refresh every 30 seconds
            setInterval(refreshData, 30000);
        }

        // Start app
        init();
    </script>
</body>
</html>
