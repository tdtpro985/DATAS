<?php
/* ============================================================
   pages/platforms.php — Platform Leads Table View
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
$email    = $_SESSION['user']['email']     ?? '';
$fullName = $_SESSION['user']['full_name'] ?? $email;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Leads | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Base styles -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/roles.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">

    <link rel="stylesheet" href="<?= $base ?>/static/css/platforms.css?v=1">
</head>
<body data-role="<?= htmlspecialchars($role) ?>" data-user-id="<?= (int)($_SESSION['user']['id'] ?? 0) ?>">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="platforms-container">
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-icon">📊</div>
            <div class="summary-card-content">
                <div class="summary-card-label">Total Platform Leads</div>
                <div class="summary-card-value" id="totalCount">Loading...</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-card-icon">📧</div>
            <div class="summary-card-content">
                <div class="summary-card-label">This Month</div>
                <div class="summary-card-value" id="monthlyCount">Loading...</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-card-icon">🏢</div>
            <div class="summary-card-content">
                <div class="summary-card-label">With Companies</div>
                <div class="summary-card-value" id="companyCount">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Search & Actions Toolbar -->
    <div class="platforms-toolbar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search platform leads...">
        </div>
        
        <button class="btn-refresh" id="refreshBtn">
            🔄 Refresh
        </button>
        
        <?php if (in_array($role, ['encoder', 'admin', 'superadmin'], true)): ?>
        <a href="<?= $base ?>/encode-platforms" class="btn-add">
            ➕ Add Platform Lead
        </a>
        <?php endif; ?>
    </div>

    <!-- Platform Leads Table -->
    <div class="platforms-card">
        <div class="table-wrapper">
            <table class="platforms-table" id="platformsTable">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th>Email Address</th>
                        <th>Location</th>
                        <th>Sales Status</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody id="platformsTableBody">
                    <!-- Loading state -->
                    <tr>
                        <td colspan="8">
                            <div class="loading-state">
                                <div class="loading-spinner"></div>
                                <p>Loading platform leads...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Platform Details Modal -->
<div class="modal-overlay" id="platformDetailsModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">📊 Platform Lead Details</h2>
            <button class="modal-close" onclick="closePlatformModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-section">
                <div class="detail-section-title">📋 Lead Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Source</div>
                        <div class="detail-value" id="detailSource">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Person</div>
                        <div class="detail-value" id="detailContactPerson">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value" id="detailContactNumber">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value" id="detailEmailAddress">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Company Name</div>
                        <div class="detail-value" id="detailCompanyName">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Company Location</div>
                        <div class="detail-value" id="detailCompanyLocation">-</div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section" id="materialsSection" style="display: none;">
                <div class="detail-section-title">📦 Materials & Quantity</div>
                <div class="materials-content" id="detailMaterials">
                    -
                </div>
            </div>
            
            <div class="detail-section">
                <div class="detail-section-title">📅 System Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Date Added</div>
                        <div class="detail-value" id="detailCreatedAt">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value" id="detailUpdatedAt">-</div>
                    </div>
                </div>
            </div>
            <!-- Sales Tracking Section (match Project Management) -->
            <div class="sales-tracking-section" data-role-access="superadmin,admin,sales_rep">
                <div class="sales-tracking-title">📊 Sales Tracking</div>
                <div class="sales-form-grid">
                    <div class="sales-form-group">
                        <label class="sales-form-label">Contacted</label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="contacted" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="contacted" data-value="no">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Quoted</label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="quoted" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="quoted" data-value="no">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Sales Qualified Leads</label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="no">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">To Win</label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="to_win" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="to_win" data-value="no">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group" data-role-access="superadmin,admin">
                        <label class="sales-form-label">Sales Representative <span style="color: #ff7070;">*</span></label>
                        <select class="sales-form-select" id="sales-rep-select">
                            <option value="">Select SR...</option>
                        </select>
                    </div>
                    
                    <div class="sales-form-group" data-role-access="superadmin,admin">
                        <label class="sales-form-label">Branch <span style="color: #ff7070;">*</span></label>
                        <input type="text" class="sales-form-input" id="branch-input" readonly placeholder="Auto-filled from SR">
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">W/L Amount (₱) <span id="wl-amount-required" style="color: #ff7070; display: none;">*</span></label>
                        <input type="number" class="sales-form-input" id="wl-amount-input" placeholder="0.00" step="0.01" min="0">
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Remarks <span style="color: #ff7070;">*</span></label>
                        <textarea class="sales-form-textarea" id="remarks-textarea" placeholder="Enter remarks..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action" onclick="closePlatformModal()">
                ✖️ Close
            </button>
            <button type="button" class="btn-action btn-edit" onclick="editPlatform()" id="editBtn">
                ✏️ Edit
            </button>
            <button type="button" class="btn-action btn-archive" onclick="archivePlatform()" id="archiveBtn">
                🗃️ Archive
            </button>
            <button type="button" class="btn-primary"
                    onclick="savePlatformTracking()"
                    id="savePlatformTrackingBtn"
                    data-role-access="superadmin,admin,sales_rep">💾 Save Sales Tracking</button>
        </div>
    </div>
</div>

<!-- Edit Platform Modal -->
<div class="modal-overlay" id="editPlatformModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>✏️ Edit Platform Lead</h2>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editPlatformForm">
                <div class="form-section">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="editSource">Source *</label>
                            <select id="editSource" name="source" required>
                                <option value="">Select source</option>
                                <option value="DPWH">DPWH</option>
                                <option value="BCI">BCI</option>
                                <option value="PHILGEPS">PHILGEPS</option>
                                <option value="EGO">EGO</option>
                                <option value="V">V</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editContactPerson">Contact Person *</label>
                            <input type="text" id="editContactPerson" name="contact_person" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editContactNumber">Contact Number *</label>
                            <input type="text" id="editContactNumber" name="contact_number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editEmailAddress">Email Address *</label>
                            <input type="email" id="editEmailAddress" name="email_address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editCompanyName">Company Name</label>
                            <input type="text" id="editCompanyName" name="company_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="editCompanyLocation">Company Location</label>
                            <input type="text" id="editCompanyLocation" name="company_location">
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="editMaterials">Materials & Quantity</label>
                            <textarea id="editMaterials" name="materials_quantity" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action btn-save" onclick="saveEditPlatform()" id="saveEditBtn">
                💾 Save Changes
            </button>
            <button type="button" class="btn-action" onclick="closeEditModal()">
                ✖️ Cancel
            </button>
        </div>
    </div>
</div>

<script>
const BASE = '<?= $base ?>';
</script>
<script src="<?= $base ?>/static/js/platforms.js?v=1"></script>
</body>
</html>