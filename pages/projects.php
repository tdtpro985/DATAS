<?php
/* ============================================================
   pages/projects.php — Projects Table View
   ============================================================
   Displays all encoded projects in a table format.
   Supports filtering by type: non-priority or priority
   Accessible by all authenticated users.
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

// Get type parameter (non-priority or priority)
$type = $_GET['type'] ?? 'all';
$isPriority = ($type === 'priority');
$isNonPriority = ($type === 'non-priority');

$pageTitle = $isPriority ? 'Priority Projects' : ($isNonPriority ? 'Non-Priority Projects' : 'All Projects');
$pageIcon = $isPriority ? '⭐' : ($isNonPriority ? '📋' : '📁');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Core Styles -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/components.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/projects.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">
</head>
<body data-role="<?= htmlspecialchars($role) ?>" data-user-id="<?= (int)($_SESSION['user']['id'] ?? 0) ?>">
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="projects-container">
    <!-- Page Header -->
    <div class="page-header">
        <h2>
            <span style="font-size: 1.75rem;"><?= $pageIcon ?></span>
            <?= htmlspecialchars($pageTitle) ?>
        </h2>
        <p>View and manage all <?= strtolower($pageTitle) ?> in the system</p>
    </div>
    <!-- Summary Cards -->
    <?php if ($role === 'sales_rep'): ?>
    <div style="margin-bottom: 2rem;">
        <div class="summary-cards" id="mySummaryCards">
            <div class="summary-card"><div class="summary-card-icon">📋</div><div class="summary-card-content"><div class="summary-card-label">Total Projects</div><div class="summary-card-value" id="myTotalProjects">—</div></div></div>
            <div class="summary-card"><div class="summary-card-icon">🏗️</div><div class="summary-card-content"><div class="summary-card-label">Contractors</div><div class="summary-card-value" id="myTotalContractors">—</div></div></div>
            <div class="summary-card"><div class="summary-card-icon">💵</div><div class="summary-card-content"><div class="summary-card-label">Pipeline Value</div><div class="summary-card-value" id="myPipelineValue">—</div></div></div>
            <div class="summary-card"><div class="summary-card-icon">📄</div><div class="summary-card-content"><div class="summary-card-label">Non-Priority</div><div class="summary-card-value" id="myNonPriorityProjects">—</div></div></div>
            <div class="summary-card"><div class="summary-card-icon">⭐</div><div class="summary-card-content"><div class="summary-card-label">Priority</div><div class="summary-card-value" id="myPriorityProjects">—</div></div></div>
        </div>
    </div>
    <?php else: ?>
    <div class="summary-cards" id="summaryCards">
        <div class="summary-card"><div class="summary-card-icon">📊</div><div class="summary-card-content"><div class="summary-card-label">Total Projects</div><div class="summary-card-value" id="totalProjects">—</div></div></div>
        <div class="summary-card"><div class="summary-card-icon">🏢</div><div class="summary-card-content"><div class="summary-card-label">Total Contractors</div><div class="summary-card-value" id="totalContractors">—</div></div></div>
        <div class="summary-card"><div class="summary-card-icon">💰</div><div class="summary-card-content"><div class="summary-card-label">Pipeline Value</div><div class="summary-card-value" id="pipelineValue">—</div></div></div>
    </div>
    <?php endif; ?>
    <!-- Toolbar -->
    <div class="projects-toolbar">
        <div class="search-box"><input type="text" id="search-input" placeholder="Search by contractor, project name, or region..."></div>
        <select id="region-filter" class="filter-select"><option value="">All Regions</option></select>
        <select id="source-filter" class="filter-select"><option value="">All Sources</option></select>
        <select id="sort-filter" class="filter-select">
            <optgroup label="📅 BY PUBLISHED DATE">
                <option value="publication_date_desc">NEWEST FIRST</option>
                <option value="publication_date_asc">OLDEST FIRST</option>
            </optgroup>
            <optgroup label="📝 BY ENCODED DATE">
                <option value="created_at_desc">NEWEST FIRST</option>
                <option value="created_at_asc">OLDEST FIRST</option>
            </optgroup>
            <optgroup label="🏢 BY CONTRACTOR">
                <option value="contractor_name_asc">A TO Z</option>
                <option value="contractor_name_desc">Z TO A</option>
            </optgroup>
            <optgroup label="📋 BY PROJECT NAME">
                <option value="project_name_asc">A TO Z</option>
                <option value="project_name_desc">Z TO A</option>
            </optgroup>
            <optgroup label="💰 BY VALUE">
                <option value="project_value_desc">HIGHEST FIRST</option>
                <option value="project_value_asc">LOWEST FIRST</option>
            </optgroup>
            <optgroup label="📍 BY REGION">
                <option value="region_asc">A TO Z</option>
                <option value="region_desc">Z TO A</option>
            </optgroup>
        </select>
    </div>
    <!-- Projects Table -->
    <div class="projects-card">
        <?php if ($type === 'priority'): ?>
        <div class="status-legend"><div class="status-legend-item"><span class="status-circle priority"></span><span>Priority</span></div></div>
        <?php else: ?>
        <div class="status-legend">
            <div class="status-legend-item"><span class="status-circle awarded"></span><span>Awarded</span></div>
            <div class="status-legend-item"><span class="status-circle for-execution"></span><span>For Execution</span></div>
            <div class="status-legend-item"><span class="status-circle for-bidding"></span><span>For Bidding</span></div>
        </div>
        <?php endif; ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Contractor</th><th>Project Name</th><th>Region</th><th>Source</th><th>Status</th><th class="col-value">₱</th><th>Sales Tracking</th><th class="col-date">Published Date</th></tr></thead>
                <tbody id="projects-tbody"><tr><td colspan="8" class="loading-state"><div class="loading-spinner"></div><p>Loading projects...</p></td></tr></tbody>
            </table>
        </div>
        <div class="pagination" id="pagination">
            <div class="pagination-info" id="pagination-info">Showing 0 of 0 projects</div>
            <div class="pagination-controls" id="pagination-controls"></div>
        </div>
    </div>
</main>
</div> <!-- /.ap-main -->
</div> <!-- /.ap-shell -->
<!-- ── Modals ── -->
<div class="modal-overlay" id="detailsModal" style="z-index:100000;">
    <div class="modal-content">
        <div class="modal-header"><h2>📋 Project Details</h2><button class="modal-close" onclick="closeDetailsModal()">×</button></div>
        <div class="modal-body" id="detailsModalBody"></div>
        <div class="modal-actions">
            <?php if ($role !== 'sales_rep'): ?>
            <button type="button" class="btn-action btn-primary" id="editProjectBtn">✏️ Edit Project</button>
            <button type="button" class="btn-action btn-delete" id="archiveBtn">🗄️ Archive Project</button>
            <?php endif; ?>
            <button type="button" class="btn-action btn-secondary" id="closeModalBtn">Close</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="editOptionsModal" style="z-index:100000;">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header"><h2>✏️ Edit Project</h2><button class="modal-close" onclick="closeEditOptionsModal()">&times;</button></div>
        <div class="modal-body">
            <p style="margin: 0 0 1.25rem; color: var(--text-secondary); text-align: center; font-size: 0.875rem;">Select which section you want to edit:</p>
            <div class="edit-options-grid">
                <button class="edit-option-card" onclick="editSection('contract')">
                    <div class="edit-option-icon">📋</div><div class="edit-option-content"><div class="edit-option-title">Contract Details</div><div class="edit-option-desc">Contract ID, Name, Person, Number, Published Date</div></div>
                    <div class="edit-option-arrow">→</div>
                </button>
                <button class="edit-option-card" onclick="editSection('project')">
                    <div class="edit-option-icon">🏗️</div><div class="edit-option-content"><div class="edit-option-title">Project Details</div><div class="edit-option-desc">Project ID, Name, Location, Address, Coordinates</div></div>
                    <div class="edit-option-arrow">→</div>
                </button>
                <button class="edit-option-card" onclick="editSection('materials')">
                    <div class="edit-option-icon">🔩</div><div class="edit-option-content"><div class="edit-option-title">Materials</div><div class="edit-option-desc">Steel Bars, Beams, Tubes, GI Sheets, etc.</div></div>
                    <div class="edit-option-arrow">→</div>
                </button>
                <button class="edit-option-card" id="editPicturesOption" onclick="editSection('pictures')" style="display: none;">
                    <div class="edit-option-icon">📸</div><div class="edit-option-content"><div class="edit-option-title">Pictures</div><div class="edit-option-desc">Upload or manage project images</div></div>
                    <div class="edit-option-arrow">→</div>
                </button>
            </div>
        </div>
        <div class="modal-actions"><button type="button" class="btn-action btn-secondary" onclick="closeEditOptionsModal()">Cancel</button></div>
    </div>
</div>
<div class="modal-overlay" id="editSectionModal" style="z-index:100000;">
    <div class="modal-content modal-large" style="max-width: 900px;">
        <div class="modal-header"><h2 id="editSectionTitle">✏️ Edit</h2><button class="modal-close" onclick="closeEditSectionModal()">&times;</button></div>
        <div class="modal-body" id="editSectionBody"></div>
        <div class="modal-actions">
            <button type="button" class="btn-action btn-secondary" onclick="closeEditSectionModal()">Cancel</button>
            <button type="button" class="btn-action btn-primary" onclick="saveEditSection()">💾 Save Changes</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="assignModal" style="z-index:100000;">
    <div class="modal-content">
        <div class="modal-header"><h2>Assign Project to Sales Rep</h2><button class="modal-close" onclick="closeAssignModal()">&times;</button></div>
        <div class="modal-body">
            <p style="margin: 0 0 1rem; color: var(--text-secondary);">Project: <strong id="assign-project-name">—</strong></p>
            <div class="form-group"><label>Select Sales Representative</label><select id="salesRepSelect" class="form-control"><option value="">Loading...</option></select></div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-secondary" onclick="closeAssignModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="confirmAssign()">Assign</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="trackingModal" style="z-index:100000;">
    <div class="modal-content modal-large">
        <div class="modal-header"><h2>📊 Sales Tracking</h2><button class="modal-close" onclick="closeTrackingModal()">×</button></div>
        <div class="modal-body">
            <p style="margin: 0 0 1.5rem; color: var(--text-secondary);">Project: <strong id="tracking-project-name">—</strong></p>
            <div class="detail-section">
                <div class="detail-section-title">👤 Sales Representative Information</div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Sales Representative</div><div class="detail-value" id="sales_rep_name_display">—</div></div>
                    <div class="detail-item"><div class="detail-label">Branch</div><div class="detail-value" id="branch_display">—</div></div>
                </div>
            </div>
            <div class="detail-section">
                <div class="detail-section-title">📋 Sales Tracking Questions</div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Contacted?</div><div class="yes-no-buttons"><button type="button" class="yes-no-btn" data-field="contacted" data-value="yes">Yes</button><button type="button" class="yes-no-btn" data-field="contacted" data-value="no">No</button></div><input type="hidden" id="contacted" name="contacted"></div>
                    <div class="detail-item"><div class="detail-label">Quoted?</div><div class="yes-no-buttons"><button type="button" class="yes-no-btn" data-field="quoted" data-value="yes">Yes</button><button type="button" class="yes-no-btn" data-field="quoted" data-value="no">No</button></div><input type="hidden" id="quoted" name="quoted"></div>
                    <div class="detail-item"><div class="detail-label">Sales Qualified Lead?</div><div class="yes-no-buttons"><button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="yes">Yes</button><button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="no">No</button></div><input type="hidden" id="sales_qualified" name="sales_qualified"></div>
                    <div class="detail-item"><div class="detail-label">To Win?</div><div class="yes-no-buttons"><button type="button" class="yes-no-btn" data-field="to_win" data-value="yes">Yes</button><button type="button" class="yes-no-btn" data-field="to_win" data-value="no">No</button></div><input type="hidden" id="to_win" name="to_win"></div>
                    <div class="detail-item"><div class="detail-label">WA Amount (₱)</div><input type="number" id="wa_amount" name="wa_amount" class="form-control" placeholder="0.00" step="0.01" min="0"></div>
                    <div class="detail-item"><div class="detail-label">Remarks</div><textarea id="remarks" name="remarks" class="form-control" placeholder="Enter remarks..." rows="3"></textarea></div>
                </div>
            </div>
        </div>
        <div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeTrackingModal()">Cancel</button><button type="button" class="btn-primary" onclick="saveTracking()">Save Tracking</button></div>
    </div>
</div>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/utils.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/roles.js?v=2"></script>
<script src="<?= $base ?>/static/js/projects.js?v=18"></script>
<script src="<?= $base ?>/static/js/projects-sales-tracking.js?v=6"></script>
<script>
const BASE = '<?= $base ?>';
</script>
<script>
window.PROJECT_TYPE = '<?= $type ?>';
</script>
</body>
</html>