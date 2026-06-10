<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

require_once __DIR__ . '/../config.php';

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$userId = $_SESSION['user']['id'] ?? null;
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['username'] ?? 'User');

// Role check
if (!in_array($role, ['superadmin', 'admin', 'sales_rep'])) {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Illegitimate Projects | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/header.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modal-system.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    
    <style>
        /* Page-specific styling */
        .illegitimate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .illegitimate-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .illegitimate-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        .pm-stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.75rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .pm-stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .pm-stat-content {
            flex: 1;
        }
        
        .pm-stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .pm-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .pm-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .pm-search {
            flex: 1;
            min-width: 250px;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .pm-search:focus {
            border-color: var(--orange-500);
            box-shadow: 0 0 0 2px rgba(255, 128, 0, 0.15);
        }
        
        .pm-filter {
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23B0BEC5' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
        }
        
        .pm-filter:focus {
            border-color: var(--orange-500);
        }
        
        .btn-refresh {
            padding: 0.75rem 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-refresh:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .status-legend {
            display: flex;
            gap: 1.5rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .status-circle {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid;
        }

        .status-circle.priority {
            background: #ef4444;
            border-color: #fca5a5;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);
        }

        .status-circle.awarded {
            background: #10b981;
            border-color: #6ee7b7;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
        }

        .status-circle.for-execution {
            background: #3b82f6;
            border-color: #93c5fd;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.6);
        }

        .status-circle.for-bidding {
            background: #f59e0b;
            border-color: #fcd34d;
            box-shadow: 0 0 8px rgba(251, 191, 36, 0.6);
        }
        
        .tracking-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .tracking-not-started {
            background: rgba(107, 114, 128, 0.15);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        .tracking-in-progress {
            background: rgba(251, 191, 36, 0.15);
            color: #fcd34d;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .tracking-complete {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            font-size: 0.9rem;
        }
        
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pagination-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background: var(--orange-500);
            border-color: var(--orange-500);
            color: #000;
        }
    </style>
    <script>
        const BASE = '<?= $base ?>';
    </script>
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php require __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">
    <div id="admin-content-wrapper" style="grid-column: 1 / -1;">
        <div class="card animate-fadeInUp">
            
            <!-- Header -->
            <div class="illegitimate-header">
                <div>
                    <h2 class="illegitimate-title">
                        <span>🚫</span>Illegitimate Projects
                    </h2>
                    <p class="illegitimate-subtitle">Projects marked as not legitimate</p>
                </div>
            </div>
            
            <!-- Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div class="pm-stat-card">
                    <div class="pm-stat-icon">🚫</div>
                    <div class="pm-stat-content">
                        <div class="pm-stat-label">Total Illegitimate</div>
                        <div class="pm-stat-value" id="totalIllegitimate">0</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="pm-filters">
                <input type="text" id="search-input" class="pm-search" placeholder="🔍 Search projects...">
                <select id="region-filter" class="pm-filter">
                    <option value="">All Regions</option>
                </select>
                <select id="source-filter" class="pm-filter">
                    <option value="">All Sources</option>
                    <option value="PHILGEPS">PHILGEPS</option>
                    <option value="DPWH">DPWH</option>
                    <option value="BCI">BCI</option>
                </select>
                <select id="sort-filter" class="pm-filter">
                    <option value="publication_date_desc">Newest First</option>
                    <option value="publication_date_asc">Oldest First</option>
                </select>
                <button id="refresh-btn" class="btn-refresh">🔄 Refresh</button>
            </div>

            <!-- Status Legend -->
            <div class="status-legend">
                <div class="legend-item">
                    <span class="status-circle priority"></span> Priority
                </div>
                <div class="legend-item">
                    <span class="status-circle awarded"></span> Awarded
                </div>
                <div class="legend-item">
                    <span class="status-circle for-execution"></span> For Execution
                </div>
                <div class="legend-item">
                    <span class="status-circle for-bidding"></span> For Bidding
                </div>
            </div>

            <!-- Projects Table -->
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Contractor</th>
                            <th>Project Name</th>
                            <th>Region</th>
                            <th>Source</th>
                            <th style="text-align: center;">Status</th>
                            <th>Project Value</th>
                            <th>Sales Tracking</th>
                            <th>Published Date</th>
                        </tr>
                    </thead>
                    <tbody id="projects-tbody">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <div id="pagination-info" class="pagination-info"></div>
                <div id="pagination-controls" class="pagination-controls"></div>
            </div>
            
        </div>
    </div>
</div>

<!-- Project Details Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Project Details</h2>
            <button class="modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="detailsModalBody">
            <!-- Content will be dynamically inserted -->
        </div>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeDetailsModal()">Close</button>
            <button class="btn-primary" onclick="saveSalesTracking()">💾 Save Sales Tracking</button>
        </div>
    </div>
</div>

<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/role-manager.js"></script>
<script src="<?= $base ?>/static/js/illegitimate-projects.js"></script>
</body>
</html>
