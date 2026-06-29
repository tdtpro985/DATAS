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
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/components.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">

    <link rel="stylesheet" href="<?= $base ?>/static/css/sales-reps.css?v=1">
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

<script src="<?= $base ?>/static/js/modal-system.js?v=1"></script>
<script src="<?= $base ?>/static/js/sales-reps.js?v=4"></script>
<script>
const BASE = '<?= $base ?>';
</script>
</body>
</html>
