<?php
/* ============================================================
   pages/sales-reps.php — Sales Representatives Management
   ============================================================
   Accessible only by superadmin and admin roles.
   Allows viewing, creating, editing, and deleting sales rep accounts.
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

$role = $_SESSION['user']['role'] ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

if ($role !== 'superadmin' && $role !== 'admin') {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Representatives | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Core Styles -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/components.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">

    <style>
        /* ── Main Container ── */
        .ap-main {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 2rem;
        }

        .dashboard {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard > .card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        /* ── Header Section ── */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .card-title {
            margin: 0;
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-header p {
            margin: 0.5rem 0 0;
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* ── Search Bar ── */
        .toolbar {
            margin-bottom: 2.5rem;
        }

        .search-box {
            position: relative;
            max-width: 100%;
        }

        .search-box input {
            width: 100%;
            padding: 1rem 1.25rem 1rem 3.25rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.875rem;
            color: var(--text-primary);
            font-size: 0.9375rem;
            transition: all 0.3s ease;
        }

        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.125rem;
            opacity: 0.6;
            pointer-events: none;
        }

        /* ── Branch Card ── */
        .sr-branch-card {
            background: linear-gradient(135deg, rgba(26, 29, 35, 0.95) 0%, rgba(17, 20, 26, 0.98) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.75rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .sr-branch-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--orange-500), rgba(255, 152, 0, 0.5));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .sr-branch-card:hover {
            border-color: rgba(255, 152, 0, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 30px rgba(255, 128, 0, 0.1);
            transform: translateY(-4px);
        }

        .sr-branch-card:hover::before {
            transform: scaleX(1);
        }

        .sr-branch-card h3 {
            margin: 0 0 1.25rem;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sr-branch-card .branch-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.85rem;
            background: rgba(255, 128, 0, 0.15);
            color: var(--orange-500);
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            border: 1px solid rgba(255, 128, 0, 0.3);
        }

        .sr-branch-card .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.875rem;
            margin: 1.25rem 0;
        }

        .sr-branch-card .stat-item {
            text-align: center;
            padding: 0.875rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 0.625rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.2s ease;
        }

        .sr-branch-card .stat-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .sr-branch-card .stat-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.375rem;
            font-weight: 600;
        }

        .sr-branch-card .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }

        .sr-branch-card .expand-hint {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 1.125rem;
            padding-top: 1.125rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sr-branch-card .expand-hint .arrow {
            transition: transform 0.3s ease;
            color: var(--orange-500);
            font-size: 1.125rem;
        }

        .sr-branch-card:hover .expand-hint .arrow {
            transform: translateX(4px);
        }

        /* ── Expanded Section ── */
        .sr-expanded {
            background: rgba(255, 128, 0, 0.04);
            border: 1px solid rgba(255, 128, 0, 0.2);
            border-radius: 1rem;
            padding: 2rem;
            margin-top: 2rem;
            animation: slideInUp 0.3s ease;
        }

        .sr-expanded-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.75rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sr-expanded-header h2 {
            margin: 0 0 0.375rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .sr-expanded-header p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* ── Sales Rep Card ── */
        .sr-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.06) 0%, rgba(255, 255, 255, 0.02) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .sr-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--orange-500), rgba(255, 152, 0, 0.5));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .sr-card:hover {
            border-color: rgba(255, 152, 0, 0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3), 0 0 24px rgba(255, 128, 0, 0.12);
        }

        .sr-card:hover::before {
            transform: scaleX(1);
        }

        .sr-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sr-card .avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--orange-500), #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.25rem;
            color: #000;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(255, 128, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .sr-card h3 {
            margin: 0 0 0.25rem;
            font-size: 1.0625rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .sr-card .email-label {
            margin: 0;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .sr-card .email-label::before {
            content: '✉️';
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .sr-card .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            gap: 1rem;
        }

        .sr-card .info-row + .info-row {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .sr-card .info-label {
            font-size: 0.8125rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .sr-card .info-value {
            font-size: 0.875rem;
            color: var(--text-primary);
            font-weight: 600;
            text-align: right;
        }

        .sr-card .info-value.branch-pill {
            background: rgba(255, 128, 0, 0.15);
            color: var(--orange-400);
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            border: 1px solid rgba(255, 128, 0, 0.3);
        }

        .sr-footer {
            display: flex;
            gap: 0.625rem;
            margin-top: 1.125rem;
            padding-top: 1.125rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sr-footer .btn {
            flex: 1;
            min-width: 0;
            padding: 0.625rem 1rem;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        /* ── Grid layout ── */
        .sr-branches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .sr-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
        }

        /* ── Loading & Empty States ── */
        .loading-state,
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: var(--orange-500);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1.5rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin: 0 0 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .empty-state p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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

        /* ── Responsive Design ── */
        @media (max-width: 1024px) {
            .sr-branches-grid,
            .sr-cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard {
                padding: 1.25rem;
            }

            .section-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .card-title {
                font-size: 1.5rem;
            }

            .sr-branches-grid,
            .sr-cards-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .sr-branch-card,
            .sr-card {
                padding: 1.25rem;
            }

            .sr-branch-card .stats-row {
                grid-template-columns: 1fr;
                gap: 0.625rem;
            }

            .sr-expanded {
                padding: 1.25rem;
                margin-top: 1.25rem;
            }

            .sr-footer {
                flex-direction: column;
                gap: 0.5rem;
            }

            #map {
                height: 280px;
            }
        }

        @media (max-width: 480px) {
            .dashboard {
                padding: 1rem;
            }

            .card-title {
                font-size: 1.25rem;
            }

            .sr-branch-card h3,
            .sr-expanded-header h2 {
                font-size: 1.125rem;
            }

            .search-box input {
                padding: 0.75rem 1rem 0.75rem 2.75rem;
                font-size: 0.875rem;
            }
        }

        /* ── Map Modal ── */
        #mapModal .modal-content {
            max-width: 900px;
        }

        #map {
            width: 100%;
            height: 400px;
            border-radius: 0.75rem;
            overflow: hidden;
            z-index: 0;
        }
    </style>
</head>
<body data-role="<?= $role ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="ap-shell">
    <div class="ap-main">
        <div class="dashboard">
            <div class="card animate-fadeInUp">
                <!-- Header -->
                <div class="section-header">
                    <div>
                        <h2 class="card-title">👤 Sales Representatives</h2>
                        <p>Manage sales representative accounts by branch</p>
                    </div>
                    <?php if ($role === 'superadmin'): ?>
                    <button class="btn btn-primary" id="addSalesRepBtn">
                        <span>+</span> Add Sales Rep
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Search -->
                <div class="toolbar">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search by name or email...">
                    </div>
                </div>

                <!-- Branch Cards Grid -->
                <div id="branchesContainer">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Loading sales representatives...</p>
                    </div>
                </div>

                <!-- Expanded Section -->
                <div id="expandedSection" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Add/Edit Modal ── -->
<div class="modal-overlay" id="salesRepModal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2 id="modalTitle">Add Sales Representative</h2>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="salesRepForm">
                <input type="hidden" id="editId">

                <div class="form-group">
                    <label for="fullName">Full Name *</label>
                    <input type="text" id="fullName" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" class="form-control" required>
                </div>

                <div class="form-group" id="passwordGroup">
                    <label for="password">Password</label>
                    <div style="position:relative;">
                        <input type="password" id="password" class="form-control" style="padding-right:3rem;">
                        <button type="button" id="togglePwd" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:0.25rem;transition:color 0.2s;">👁️</button>
                    </div>
                    <small style="font-size:0.75rem;color:var(--text-muted);margin-top:0.25rem;display:block;">Leave blank to keep current password</small>
                </div>

                <div class="form-group">
                    <label for="branch">Branch *</label>
                    <select id="branch" class="form-control" required>
                        <option value="">Select Branch</option>
                        <option value="TDT Manila">TDT Manila</option>
                        <option value="TDT Cagayan De Oro">TDT Cagayan De Oro</option>
                        <option value="TDT Cebu">TDT Cebu</option>
                        <option value="TDT Davao">TDT Davao</option>
                        <option value="TDT General Santos">TDT General Santos</option>
                        <option value="TDT Ilocos">TDT Ilocos</option>
                        <option value="TDT Iloilo">TDT Iloilo</option>
                        <option value="TDT Isabela">TDT Isabela</option>
                        <option value="TDT Legazpi">TDT Legazpi</option>
                        <option value="PS Manila">PS Manila</option>
                        <option value="PS Laug">PS Laug</option>
                        <option value="PS Batangas">PS Batangas</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contactNumber">Contact Number</label>
                    <input type="text" id="contactNumber" class="form-control" placeholder="Optional">
                </div>

                <div id="formError" style="display:none;color:#fca5a5;font-size:0.85rem;padding:0.5rem;background:rgba(239,68,68,0.1);border-radius:0.5rem;margin-top:0.5rem;"></div>
            </form>
        </div>
        <div class="modal-footer" style="justify-content:flex-end; gap:0.75rem;">
            <button class="btn btn-secondary" id="cancelBtn">Cancel</button>
            <button class="btn btn-primary" id="saveBtn">Save</button>
        </div>
    </div>
</div>

<!-- ── Location Map Modal ── -->
<div class="modal-overlay" id="mapModal" style="z-index:100000;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📍 Location Map</h2>
            <button class="modal-close" id="closeMapModal">&times;</button>
        </div>
        <div class="modal-body">
            <div id="map"></div>
        </div>
        <div class="modal-footer" style="justify-content:flex-end;">
            <button class="btn btn-secondary" id="closeMapBtn">Close</button>
        </div>
    </div>
</div>

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/sales-reps.js?v=2"></script>
</body>
</html>
</write_to_file>