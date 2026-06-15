<?php
/* ============================================================
   pages/my-projects.php — My Projects (Sales Rep View)
   ============================================================
   Shows projects assigned to current sales rep.
   Sales Rep only.
   ============================================================ */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role     = $_SESSION['user']['role']      ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? 'User');
$userId   = $_SESSION['user']['id']        ?? 0;

// Only sales rep can access
if ($role !== 'sales_rep') {
    header('Location: ' . $base . '/');
    exit;
}

$currentView = $_GET['view'] ?? 'non-priority';
$pageTitle = $currentView === 'priority' ? 'Priority Projects' : 'Non-Priority Projects';
$pageIcon  = $currentView === 'priority' ? '⭐' : '📋';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | TDT Powersteel SILEP</title>
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
    <link rel="stylesheet" href="<?= $base ?>/static/css/roles.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modal-system.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    
    <style>
        /* ── Modal Styling (matching projects.php and projects-management.php) ── */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.2s ease;
        }
        
        /* Center modal in main content area (accounting for sidebar) */
        @media (min-width: 769px) {
            .modal-overlay {
                left: 240px; /* Sidebar width */
            }
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideInUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .modal-content.modal-large {
            max-width: 900px;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.25rem;
            color: var(--text-secondary);
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .detail-section {
            margin-bottom: 1.5rem;
        }
        
        .detail-section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--orange-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .detail-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.75rem;
            padding: 1rem;
        }
        
        .detail-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            word-break: break-word;
        }
        
        .detail-value.large {
            font-size: 1.25rem;
            color: #34d399;
        }
        
        .modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        .btn-action {
            padding: 0.75rem 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .btn-primary {
            background: var(--orange-500);
            border-color: var(--orange-500);
            color: #000;
        }
        
        .btn-primary:hover {
            background: var(--orange-600);
            box-shadow: 0 4px 16px rgba(255, 128, 0, 0.4);
        }
        
        .btn-secondary {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* ── Yes/No Buttons ── */
        .yes-no-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .yes-no-btn {
            flex: 1;
            padding: 0.875rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: var(--font);
        }
        
        .yes-no-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .yes-no-btn.active {
            background: linear-gradient(135deg, var(--orange-500), rgba(255, 152, 0, 0.8));
            border-color: var(--orange-500);
            color: #000;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(255, 128, 0, 0.3);
        }
        
        .yes-no-btn.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 128, 0, 0.4);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: var(--font);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: var(--orange-500);
            box-shadow: 0 0 0 2px rgba(255, 128, 0, 0.15);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ── Stats Cards ── */
        .pm-stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.75rem;
            padding: 1rem;
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
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        /* ── Filters ── */
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
        
        /* ── Sales Tracking Form ── */
        .sales-tracking-section {
            background: rgba(255, 128, 0, 0.05);
            border: 1px solid rgba(255, 128, 0, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .sales-tracking-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--orange-500);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sales-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: repeat(4, auto);
            grid-auto-flow: column;
            gap: 1rem;
        }
        
        .sales-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .sales-form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .sales-form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .sales-form-input,
        .sales-form-select,
        .sales-form-textarea {
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: var(--font);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .sales-form-input:focus,
        .sales-form-select:focus,
        .sales-form-textarea:focus {
            border-color: var(--orange-500);
            box-shadow: 0 0 0 2px rgba(255, 128, 0, 0.15);
        }
        
        .sales-form-select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23B0BEC5' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
        }
        
        .sales-form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* ── Tracking Status Badges ── */
        .tracking-badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .tracking-not-started {
            background: rgba(156,163,175,0.12);
            color: #9ca3af;
            border: 1px solid rgba(156,163,175,0.2);
        }
        .tracking-in-progress {
            background: rgba(245,158,11,0.12);
            color: #f59e0b;
            border: 1px solid rgba(245,158,11,0.25);
        }
        .tracking-complete {
            background: rgba(34,197,94,0.12);
            color: #22c55e;
            border: 1px solid rgba(34,197,94,0.25);
        }

        /* ── Yes/No active green/red ── */
        .yes-no-btn.active.yes {
            background: rgba(16,185,129,0.9) !important;
            border-color: rgba(16,185,129,1) !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(16,185,129,0.4);
        }
        .yes-no-btn.active.no {
            background: rgba(239,68,68,0.9) !important;
            border-color: rgba(239,68,68,1) !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(239,68,68,0.4);
        }
        .yes-no-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed !important;
            pointer-events: none;
        }
    </style>
</head>

<body data-role="<?= $role ?>" data-user-id="<?= (int)$userId ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard" style="display: block; max-width: 100%; padding: var(--sp-4); box-sizing: border-box;">
    <div class="card animate-fadeInUp" style="max-width: 100%; margin: 0 auto;">
        <div style="margin-bottom: var(--sp-5);">
            <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                <span style="margin-right: 0.5rem;"><?= $pageIcon ?></span><?= $pageTitle ?>
            </h2>
            <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                <?= $currentView === 'priority' ? 'Priority projects for your sales tracking' : 'Non-priority projects for your sales tracking' ?>
            </p>
        </div>

        <!-- View Tabs -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 0;">
            <a href="?view=non-priority"
               style="padding: 0.75rem 1.5rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; border-radius: 0.5rem 0.5rem 0 0; transition: all 0.2s;
                      <?= $currentView !== 'priority' ? 'color: var(--orange-500); border-bottom: 2px solid var(--orange-500); background: rgba(255,128,0,0.08);' : 'color: var(--text-secondary); border-bottom: 2px solid transparent;' ?>">
                📋 Non-Priority
            </a>
            <a href="?view=priority"
               style="padding: 0.75rem 1.5rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; border-radius: 0.5rem 0.5rem 0 0; transition: all 0.2s;
                      <?= $currentView === 'priority' ? 'color: var(--orange-500); border-bottom: 2px solid var(--orange-500); background: rgba(255,128,0,0.08);' : 'color: var(--text-secondary); border-bottom: 2px solid transparent;' ?>">
                ⭐ Priority
            </a>
        </div>

        <!-- Filters -->
        <div class="pm-filters">
            <input type="text" id="searchInput" placeholder="Search projects..." class="pm-search">
            <select id="regionFilter" class="pm-filter">
                <option value="">All Regions</option>
                <option value="NCR">NCR</option>
                <option value="Region I">Region I</option>
                <option value="Region II">Region II</option>
                <option value="Region III">Region III</option>
                <option value="Region IV-A">Region IV-A</option>
                <option value="Region IV-B">Region IV-B</option>
                <option value="Region V">Region V</option>
                <option value="Region VI">Region VI</option>
                <option value="Region VII">Region VII</option>
                <option value="Region XI">Region XI</option>
            </select>
            <select id="statusFilter" class="pm-filter">
                <option value="">All Status</option>
                <option value="Prospect">Prospect</option>
                <option value="Contacted">Contacted</option>
                <option value="Sales Qualified">Sales Qualified</option>
                <option value="Not Sales Qualified">Not Sales Qualified</option>
                <option value="Quoted">Quoted</option>
                <option value="Awarded">Awarded</option>
                <option value="For Execution">For Execution</option>
                <option value="Priority">Priority</option>
            </select>
        </div>

        <!-- Content Area -->
        <div id="pm-content">
            <div class="table-wrapper" style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; min-width: 800px;">
                    <thead>
                        <tr>
                            <th>Date Assigned</th>
                            <th>Contractor</th>
                            <th>Project Name</th>
                            <th>Region</th>
                            <th>Value (₱)</th>
                            <th>Status</th>
                            <th>Sales Tracking</th>
                        </tr>
                    </thead>
                    <tbody id="pm-table-body">
                        <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="pm-pagination" class="pagination-controls"></div>
        </div>
    </div>
</div>

<!-- Project Details Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>📋 Project Details</h2>
            <button class="modal-close" onclick="closeDetailsModal()">×</button>
        </div>
        <div class="modal-body" id="detailsModalBody" style="color:var(--text-primary);">
            <!-- Populated dynamically -->
        </div>
        <div class="modal-actions">
            <!-- buttons are injected dynamically -->
        </div>
    </div>
</div>

<script>
const BASE = '<?= $base ?>';
const CURRENT_USER_ID = <?= $userId ?>;
</script>
<script src="<?= $base ?>/static/js/modal-system.js?v=1"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/roles.js?v=2"></script>
<script src="<?= $base ?>/static/js/my-projects.js?v=7"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay[id], .detail-modal-overlay[id]').forEach(function(el) {
        if (el.parentNode !== document.body) document.body.appendChild(el);
    });
});
</script></body>
</html>
