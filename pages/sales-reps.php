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

// Compute base path
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

// Check authentication
if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

// Only superadmin and admin can access
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
    <title>Sales Representatives - TDT Powersteel</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
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
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=23">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">
</head>
<body data-role="<?= $role ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">
    
    <div class="card animate-fadeInUp" style="grid-column: 1 / -1; margin-bottom: var(--sp-4);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-5);">
            <div>
                <h1 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                    Sales Representatives
                </h1>
                <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                    Manage sales representative accounts by branch
                </p>
            </div>
            <?php if ($role === 'superadmin'): ?>
            <button class="btn-primary" id="addSalesRepBtn" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>+</span> Add Sales Representative
            </button>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: var(--sp-4);">
            <input type="text" id="searchInput" placeholder="Search by name or email..." 
                   style="width: 100%; max-width: 400px; padding: 0.75rem 1rem; background: var(--bg-input); 
                          border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-md); 
                          color: var(--text-primary); font-size: 0.9rem;">
        </div>
        
        <!-- Sales Reps Cards by Branch -->
        <div id="salesRepsContainer">
            <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                Loading...
            </div>
        </div>
    </div>

</div> <!-- .dashboard -->

<!-- Add/Edit Sales Rep Modal -->
<div class="modal-overlay" id="salesRepModal">
    <div class="modal-content" style="max-width: 900px; width: 95%; max-height: 80vh; display: flex; flex-direction: column;">
        <!-- Header - Fixed -->
        <div class="modal-header" style="flex-shrink: 0;">
            <h2 id="modalTitle">Sales Representative Details</h2>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        
        <!-- Body - Scrollable -->
        <div style="flex: 1; overflow-y: auto; overflow-x: hidden; padding: 1.5rem;">
            <form id="salesRepForm">
                <input type="hidden" id="salesRepId" name="id">
                
                <!-- Account Information Section -->
                <div style="background: rgba(255, 255, 255, 0.03); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0 0 1rem; color: var(--orange-500); font-size: 1rem; font-weight: 700;">
                        Account Information
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required placeholder="e.g. john.doe@tdtpowersteel.com">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="fullName">Full Name *</label>
                            <input type="text" id="fullName" name="full_name" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="branch">Branch *</label>
                            <select id="branch" name="branch" required>
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
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Login Status</label>
                            <div id="loginStatusDisplay" style="padding: 0.75rem; background: rgba(0,0,0,0.2); border-radius: 6px;">
                                <span class="badge badge-secondary">⚫ Offline</span>
                            </div>
                        </div>
                        
                        <div class="form-group" id="passwordGroup" style="margin-bottom: 0;">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password">
                            <small class="form-hint">Minimum 8 characters</small>
                        </div>
                        
                        <div class="form-group" id="confirmPasswordGroup" style="margin-bottom: 0;">
                            <label for="confirmPassword">Confirm Password *</label>
                            <input type="password" id="confirmPassword" name="confirm_password">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Account Created</label>
                            <div id="createdAtDisplay" style="padding: 0.75rem; background: rgba(0,0,0,0.2); border-radius: 6px; color: #94a3b8; font-size: 0.875rem;">
                                —
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Total Projects</label>
                            <div id="totalProjectsDisplay" style="padding: 0.75rem; background: rgba(0,0,0,0.2); border-radius: 6px;">
                                <span class="badge badge-info" id="totalProjectsBadge">0 projects</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Projects section removed -->
                
                <div class="error-message" id="formError" style="display: none; margin-top: 1rem;"></div>
            </form>
        </div>
        
        <!-- Footer - Fixed -->
        <div style="flex-shrink: 0; padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
            <?php if ($role === 'superadmin'): ?>
            <!-- Create Mode Buttons -->
            <div id="createButtons" style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
                <button type="button" class="btn-primary" id="submitBtn" onclick="document.getElementById('salesRepForm').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}))">
                    <span id="submitText">Create Sales Rep</span>
                    <span class="loader" id="submitLoader"></span>
                </button>
            </div>
            
            <!-- View/Edit Mode Buttons -->
            <div id="editButtons" style="display: none;">
                <!-- View Mode -->
                <div id="viewModeButtons" style="display: flex; gap: 0.75rem;">
                    <button type="button" class="btn-secondary" onclick="closeModalHandler()" style="flex: 1;">Close</button>
                    <button type="button" class="btn-primary" onclick="enableEdit()" style="flex: 1;">
                        Edit
                    </button>
                    <button type="button" class="btn-danger" onclick="confirmDelete()" style="flex: 1;">
                        Delete
                    </button>
                </div>
                
                <!-- Edit Mode -->
                <div id="editModeButtons" style="display: none; gap: 0.75rem;">
                    <button type="button" class="btn-secondary" onclick="cancelEdit()" style="flex: 1;">Cancel</button>
                    <button type="button" class="btn-primary" onclick="document.getElementById('salesRepForm').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}))" style="flex: 1;">
                        <span id="updateText">Save Changes</span>
                        <span class="loader" id="updateLoader" style="display: none;"></span>
                    </button>
                </div>
            </div>
            <?php else: ?>
            <!-- Admin: Read-only, Close only -->
            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="closeModalHandler()">Close</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2>Confirm Delete</h2>
            <button class="modal-close" id="closeDeleteModal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this sales representative?</p>
            <p><strong id="deleteUserName"></strong></p>
            <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-secondary" id="cancelDeleteBtn">Cancel</button>
            <button type="button" class="btn-danger" id="confirmDeleteBtn">
                <span id="deleteText">Delete</span>
                <span class="loader" id="deleteLoader"></span>
            </button>
        </div>
    </div>
</div>

    </div>

</div> <!-- .dashboard -->
</div> <!-- .ap-main -->
</div> <!-- .ap-shell -->

<script>const BASE = '<?= $base ?>'; const USER_ROLE = '<?= $role ?>';</script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/sales-reps.js?v=13"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay[id], .detail-modal-overlay[id]').forEach(function(el) {
        if (el.parentNode !== document.body) document.body.appendChild(el);
    });
});
</script></body>
</html>
