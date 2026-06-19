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

    <style>
        /* Full-width page — sits inside sidebar's .ap-main (no nested ap-shell) */
        .ap-main .dashboard.sr-page {
            display: block;
            width: 100%;
            max-width: none;
            padding: 0;
            margin: 0;
            min-height: auto;
            background: transparent;
        }

        .sr-container {
            width: 100%;
            max-width: none;
            padding: 1.75rem 2rem 2.5rem;
            box-sizing: border-box;
        }

        /* Header + toolbar */
        .sr-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.5rem;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .sr-title {
            margin: 0;
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .sr-subtitle {
            margin: 0.5rem 0 0;
            color: var(--text-secondary);
            font-size: 0.9375rem;
        }

        .sr-toolbar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
        }

        .sr-search {
            position: relative;
            flex: 1;
            min-width: 220px;
        }

        .sr-search input {
            width: 100%;
            padding: 0.875rem 1.25rem 0.875rem 3rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.875rem;
            color: var(--text-primary);
            font-size: 0.9375rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .sr-search input:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 128, 0, 0.4);
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 128, 0, 0.1);
        }

        .sr-search::before {
            content: '🔍';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            opacity: 0.6;
            pointer-events: none;
        }

        /* Branch Grid — fills available width */
        .sr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
            gap: 1.25rem;
            width: 100%;
        }

        /* Branch Card */
        .sr-branch {
            background: linear-gradient(135deg, rgba(26, 29, 35, 0.95), rgba(17, 20, 26, 0.98));
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .sr-branch::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff8000, #ffa500);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .sr-branch:hover {
            border-color: rgba(255, 128, 0, 0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .sr-branch:hover::before {
            transform: scaleX(1);
        }

        .sr-branch-name {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sr-badge {
            background: rgba(255, 128, 0, 0.15);
            color: #ff8000;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid rgba(255, 128, 0, 0.3);
        }

        .sr-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.875rem;
            margin: 1.25rem 0;
        }

        .sr-stat {
            text-align: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 0.625rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .sr-stat-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .sr-stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .sr-hint {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .sr-arrow {
            color: #ff8000;
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }

        .sr-branch:hover .sr-arrow {
            transform: translateX(6px);
        }

        /* Expanded branch section */
        .sr-expanded {
            background: linear-gradient(135deg, rgba(26, 29, 35, 0.95), rgba(17, 20, 26, 0.98));
            border: 1px solid rgba(255, 128, 0, 0.2);
            border-radius: 1rem;
            padding: 1.75rem;
            margin-top: 1.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        .sr-expanded-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sr-expanded-header h2 {
            margin: 0;
            font-size: 1.375rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .sr-expanded-header p {
            margin: 0.35rem 0 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .sr-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
            gap: 1rem;
            width: 100%;
        }

        .sr-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.875rem;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .sr-card:hover {
            border-color: rgba(255, 128, 0, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }

        .sr-card .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff8000, #ffa500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 800;
            color: #000;
            flex-shrink: 0;
        }

        .sr-card h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sr-card .email-label {
            margin: 0.25rem 0 0;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sr-card .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.8125rem;
        }

        .sr-card .info-row:last-of-type {
            border-bottom: none;
        }

        .sr-card .info-label {
            color: var(--text-muted);
            font-weight: 500;
            flex-shrink: 0;
        }

        .sr-card .info-value {
            color: var(--text-primary);
            font-weight: 600;
            text-align: right;
            min-width: 0;
        }

        .sr-card .branch-pill {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background: rgba(255, 128, 0, 0.12);
            color: #ff8000;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .sr-card .sr-footer {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sr-card .sr-footer .btn {
            flex: 1;
        }

        /* Loading/Empty States */
        .sr-loading, .sr-empty {
            text-align: center;
            padding: 4rem 2rem;
        }

        .sr-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: #ff8000;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .sr-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .sr-empty h3 {
            margin: 0 0 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .sr-empty p {
            margin: 0;
            color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sr-container {
                padding: 1.25rem 1rem 2rem;
            }

            .sr-header {
                flex-direction: column;
                align-items: stretch;
            }

            .sr-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .sr-title {
                font-size: 1.5rem;
            }

            .sr-stats {
                grid-template-columns: 1fr;
            }

            .sr-expanded-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body data-role="<?= $role ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard sr-page">
    <div class="sr-container">
        <div class="sr-header">
            <div>
                <h1 class="sr-title">👤 Sales Representatives</h1>
                <p class="sr-subtitle">Manage sales representative accounts by branch</p>
            </div>
            <?php if ($role === 'superadmin'): ?>
            <button class="btn btn-primary" id="addSalesRepBtn">
                <span>+</span> Add Sales Rep
            </button>
            <?php endif; ?>
        </div>

        <div class="sr-toolbar">
            <div class="sr-search">
                <input type="text" id="searchInput" placeholder="Search by name or email...">
            </div>
        </div>

        <div id="branchesContainer">
            <div class="sr-loading">
                <div class="sr-spinner"></div>
                <p>Loading sales representatives...</p>
            </div>
        </div>

        <div id="expandedSection" style="display:none;"></div>
    </div>
</div>

    </div><!-- .ap-main -->
</div><!-- .ap-shell -->

<!-- Add/Edit Modal -->
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
                        <button type="button" id="togglePwd" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:0.25rem;">👁️</button>
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

<!-- Map Modal -->
<div class="modal-overlay" id="mapModal" style="z-index:100000;">
    <div class="modal-content" style="max-width:900px;">
        <div class="modal-header">
            <h2>📍 Location Map</h2>
            <button class="modal-close" id="closeMapModal">&times;</button>
        </div>
        <div class="modal-body">
            <div id="map" style="width:100%;height:400px;border-radius:0.75rem;"></div>
        </div>
        <div class="modal-footer" style="justify-content:flex-end;">
            <button class="btn btn-secondary" id="closeMapBtn">Close</button>
        </div>
    </div>
</div>

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/sales-reps.js?v=3"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay[id]').forEach(function(el) {
        if (el.parentNode !== document.body) document.body.appendChild(el);
    });
});
</script>
</body>
</html>
