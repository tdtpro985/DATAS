<?php
/* ============================================================
   pages/projects-management.php — Project Management
   ============================================================
   Shows 4 tabs: Unassigned, Assigned, Unprocessed, Processed
   Admin and Superadmin only.
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

$currentView = $_GET['view'] ?? 'unassigned';

// Access control based on role and view
if ($role === 'sales_rep') {
    // Sales reps can only view unassigned projects
    if ($currentView !== 'unassigned') {
        header('Location: ' . $base . '/projects-management?view=unassigned');
        exit;
    }
} elseif ($role !== 'admin' && $role !== 'superadmin') {
    // Other roles (encoder, etc.) cannot access this page
    header('Location: ' . $base . '/');
    exit;
}

// Only admins and superadmins can view archived projects
if ($currentView === 'archived' && !in_array($role, ['admin', 'superadmin'])) {
    header('Location: ' . $base . '/projects-management?view=unassigned');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/components.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    
    
    <!-- Modern Select Dropdowns Styling -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">
    
    <!-- Philippine DateTime -->
    <script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>
    <link rel="stylesheet" href="<?= $base ?>/static/css/projects-management.css?v=1">
</head>

<body data-role="<?= $role ?>" data-user-id="<?= (int)($_SESSION['user']['id'] ?? 0) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard" style="display: block; max-width: 100%; padding: var(--sp-4); box-sizing: border-box;">
    <div class="card animate-fadeInUp" style="max-width: 100%; margin: 0 auto;">
        <div style="margin-bottom: var(--sp-5);">
            <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                <span style="margin-right: 0.5rem;">📊</span>Project Management
            </h2>
            <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                <?php
                $viewTitles = [
                    'unassigned' => 'Projects without assigned sales representative',
                    'assigned' => 'Projects with assigned sales representative',
                    'unprocessed' => 'Projects without sales tracking',
                    'processed' => 'Projects with sales tracking',
                    'archived' => 'Archived projects (admin/superadmin only)'
                ];
                echo $viewTitles[$currentView] ?? 'Manage project assignments and sales tracking';
                ?>
            </p>
        </div>



        <!-- Filters -->
        <div class="pm-filters">
            <input type="text" id="searchInput" placeholder="Search projects..." class="pm-search">
            <select id="regionFilter" class="pm-filter">
                <option value="">All Regions</option>
            </select>
            <select id="statusFilter" class="pm-filter">
                <option value="">All Status</option>
                <option value="For Execution">For Execution</option>
                <option value="For Bidding">For Bidding</option>
                <option value="Awarded">Awarded</option>
                <option value="Priority">Priority</option>
            </select>
            <select id="sourceFilter" class="pm-filter">
                <option value="">All Sources</option>
            </select>
            <select id="sortFilter" class="pm-filter">
                <optgroup label="📅 By Published Date">
                    <option value="publication_date_desc">Newest First</option>
                    <option value="publication_date_asc">Oldest First</option>
                </optgroup>
                <optgroup label="📝 By Encoded Date">
                    <option value="created_at_desc">Newest First</option>
                    <option value="created_at_asc">Oldest First</option>
                </optgroup>
                <optgroup label="🗄️ By Archived Date">
                    <option value="archived_at_desc">Newest First</option>
                    <option value="archived_at_asc">Oldest First</option>
                </optgroup>
            </select>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" style="display: none; margin-bottom: 1rem; padding: 1rem; background: rgba(255, 128, 0, 0.1); border: 1px solid rgba(255, 128, 0, 0.3); border-radius: var(--radius-md);">
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <span id="selectedCount" style="color: var(--text-primary); font-weight: 600;">0 selected</span>
                <button class="btn-secondary" onclick="clearSelection()" style="padding: 0.5rem 1rem;">
                    Clear Selection
                </button>
            </div>
        </div>

        <!-- Bulk Assignment Button (shown in unassigned view) -->
        <div id="bulkAssignButtonBar" style="margin-bottom: 1rem; display: <?= $currentView === 'unassigned' && ($role === 'admin' || $role === 'superadmin') ? 'flex' : 'none' ?>; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: space-between;">
            <button class="btn-primary" onclick="openSalesRepModal()" style="padding: 0.875rem 1.75rem; display: inline-flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; font-weight: 700; border-radius: 0.75rem;">
                <span style="font-size: 1.25rem;">👥</span>
                <span>Bulk Assign Projects</span>
            </button>
        </div>

        <!-- Bulk Unassign Button (shown in assigned view) -->
        <div id="bulkUnassignButtonBar" style="margin-bottom: 1rem; display: <?= $currentView === 'assigned' && ($role === 'admin' || $role === 'superadmin') ? 'flex' : 'none' ?>; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <button class="btn-secondary" onclick="startBulkUnassign()" style="padding: 0.875rem 1.75rem; display: inline-flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; font-weight: 700; border-radius: 0.75rem; background: #dc2626; border-color: #dc2626;">
                <span style="font-size: 1.25rem;">❌</span>
                <span>Bulk Unassign Projects</span>
            </button>
        </div>
        <!-- Content Area -->
        <div id="pm-content">
            <!-- Status Legend -->
            <div class="status-legend">
                <div class="status-legend-item">
                    <span class="status-circle priority"></span>
                    <span>Priority</span>
                </div>
                <div class="status-legend-item">
                    <span class="status-circle awarded"></span>
                    <span>Awarded</span>
                </div>
                <div class="status-legend-item">
                    <span class="status-circle for-execution"></span>
                    <span>For Execution</span>
                </div>
                <div class="status-legend-item">
                    <span class="status-circle for-bidding"></span>
                    <span>For Bidding</span>
                </div>
            </div>
            
            <div class="table-wrapper" style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; min-width: 800px;">
                    <thead id="pm-table-head">
                        <!-- Dynamic headers -->
                    </thead>
                    <tbody id="pm-table-body">
                        <tr><td colspan="12" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="pm-pagination" class="pagination-controls"></div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Assign Project to Sales Rep</h2>
            <button class="modal-close" onclick="closeAssignModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin: 0 0 1rem; color: var(--text-secondary);">
                Project: <strong id="assign-project-name">—</strong>
            </p>
            <div class="form-group">
                <label>Select Sales Representative</label>
                <select id="salesRepSelect" class="form-control">
                    <option value="">Loading...</option>
                </select>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-secondary" onclick="closeAssignModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="confirmAssign()">Assign</button>
        </div>
    </div>
</div>

<!-- Sales Tracking Modal -->
<div class="modal-overlay" id="trackingModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>📊 Sales Tracking</h2>
            <button class="modal-close" onclick="closeTrackingModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="margin: 0 0 1.5rem; color: var(--text-secondary);">
                Project: <strong id="tracking-project-name">—</strong>
            </p>
            
            <!-- Sales Representative Information Section -->
            <div class="detail-section">
                <div class="detail-section-title">👤 Sales Representative Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Sales Representative</div>
                        <div class="detail-value" id="sales_rep_name_display">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Branch</div>
                        <div class="detail-value" id="branch_display">—</div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Tracking Questions Section -->
            <div class="detail-section">
                <div class="detail-section-title">📋 Sales Tracking Questions</div>
                <div class="detail-grid">
                    <!-- Contacted -->
                    <div class="detail-item">
                        <div class="detail-label">Contacted?</div>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="contacted" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="contacted" data-value="no">No</button>
                        </div>
                        <input type="hidden" id="contacted" name="contacted">
                    </div>

                    <!-- Quoted -->
                    <div class="detail-item">
                        <div class="detail-label">Quoted?</div>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="quoted" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="quoted" data-value="no">No</button>
                        </div>
                        <input type="hidden" id="quoted" name="quoted">
                    </div>

                    <!-- Sales Qualified Lead -->
                    <div class="detail-item">
                        <div class="detail-label">Sales Qualified Lead?</div>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="no">No</button>
                        </div>
                        <input type="hidden" id="sales_qualified" name="sales_qualified">
                    </div>

                    <!-- To Win -->
                    <div class="detail-item">
                        <div class="detail-label">To Win?</div>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="to_win" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="to_win" data-value="no">No</button>
                        </div>
                        <input type="hidden" id="to_win" name="to_win">
                    </div>
                </div>
            </div>
            
            <!-- Financial Information Section -->
            <div class="detail-section">
                <div class="detail-section-title">💰 Financial Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">WA Amount (₱)</div>
                        <input type="number" id="wa_amount" class="form-control" step="0.01" min="0" placeholder="0.00" style="margin-top: 0.5rem;">
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Remarks</div>
                        <textarea id="remarks" class="form-control" rows="3" placeholder="Enter remarks..." style="margin-top: 0.5rem;"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action btn-secondary" onclick="closeTrackingModal()">Cancel</button>
            <button type="button" class="btn-action btn-primary" onclick="saveTracking()">Save Tracking</button>
        </div>
    </div>
</div>

<!-- Project Details Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📋 Project Details</h2>
            <button class="modal-close" onclick="closeDetailsModal()">×</button>
        </div>
        <div class="modal-body" id="detailsModalBody">
            <!-- Content will be inserted here -->
        </div>
        <div class="modal-actions">
            <!-- Destructive actions pushed to the left -->
            <button type="button" class="btn-action btn-delete"
                    id="clearTrackingBtn"
                    data-role-access="admin,superadmin"
                    onclick="clearSalesTracking()"
                    style="display:none;margin-right:auto;">
                🗑️ Clear Sales Tracking
            </button>

            <button type="button" class="btn-action btn-secondary" onclick="closeDetailsModal()">Close</button>

            <button type="button" class="btn-action btn-delete"
                    id="archiveBtn"
                    data-role-access="admin,superadmin"
                    style="display:none;">
                🗄️ Archive Project
            </button>

            <button type="button" class="btn-action btn-primary"
                    onclick="saveSalesTracking()"
                    id="saveTrackingBtn"
                    data-role-access="superadmin,admin,sales_rep">💾 Save Sales Tracking</button>
        </div>
    </div>
</div>

<!-- Sales Rep Selection Modal -->
<div class="modal-overlay" id="salesRepModal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2>👥 Select Sales Representative</h2>
            <button class="modal-close" onclick="closeSalesRepModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="margin: 0 0 1.5rem; color: var(--text-secondary); font-size: 0.95rem;">
                <strong>Step 1:</strong> Select a sales representative below. The system will then show you suggested projects based on their branch location.
            </p>
            
            <!-- Search box for filtering SRs -->
            <div style="margin-bottom: 1.5rem;">
                <input type="text" id="srSearchInput" placeholder="Search by name or branch..." style="width: 100%; padding: 0.75rem 1rem; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; color: var(--text-primary); font-size: 0.9rem; outline: none;">
            </div>
            
            <!-- Sales Reps Grid -->
            <div id="salesRepsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; max-height: 500px; overflow-y: auto; padding: 0.5rem;">
                <!-- SR cards will be inserted here -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏳</div>
                    <p>Loading sales representatives...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/static/js/modal-system.js?v=1"></script>
<script src="<?= $base ?>/static/js/utils.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/projects-management-clean.js?v=12"></script>

<script>
const BASE = '<?= $base ?>';
</script>
</body>
</html>
