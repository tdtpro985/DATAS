<?php
/* ============================================================
   pages/encode/priority.php — Priority Form (3 Steps)
   ============================================================
   Step 1: Contractor Details
   Step 2: Project Details
   Step 3: Material Details
   ============================================================ */
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role     = $_SESSION['user']['role']      ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');
$userId   = $_SESSION['user']['id']        ?? 0;

// Only encoders, admins, and superadmins may access the encode page.
if (!in_array($role, ['encoder', 'admin', 'superadmin'], true)) {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encode Priority Project — TDT Powersteel</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=8">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=25">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">

    <link rel="stylesheet" href="<?= $base ?>/static/css/encode-priority.css?v=1">
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/../sidebar.php'; ?>

<div class="dashboard">
    <div class="card animate-fadeInUp" style="grid-column: 1 / -1;">
        <div class="page-header">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <div>
                    <h1>Encode Priority Project</h1>
                    <p>Complete all 4 steps to encode a high-priority project with pictures.</p>
                </div>
                <button type="button" class="btn-secondary" onclick="window.location.href='<?= $base ?>/encode'" style="padding: 0.4rem 0.8rem; font-size: 0.7rem;">
                    ← Back to Encode
                </button>
            </div>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div style="flex: 1; position: relative;">
                <div class="step-badge active" id="step1Badge">1</div>
                <div class="step-line"></div>
            </div>
            <div style="flex: 1; position: relative;">
                <div class="step-badge" id="step2Badge">2</div>
                <div class="step-line"></div>
            </div>
            <div style="flex: 1; position: relative;">
                <div class="step-badge" id="step3Badge">3</div>
                <div class="step-line"></div>
            </div>
            <div style="flex: 1; position: relative;">
                <div class="step-badge" id="step4Badge">4</div>
            </div>
        </div>

        <form id="encodeForm" novalidate>
            <!-- Step 1: Contractor Details -->
            <div class="form-step active" data-step="1">
                <div class="form-section">
                    <h2>Contractor Details</h2>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <!-- Row 1 -->
                        <div class="form-group">
                            <label for="publishedDate" data-required=" *">Published Date</label>
                            <input type="date" id="publishedDate" name="published_date" required>
                        </div>
                        <div class="form-group">
                            <label for="contractCountry" data-required=" *">Country</label>
                            <input type="text" id="contractCountry" name="contract_country" 
                                   list="countryList" placeholder="Type or select country" required value="Philippines">
                            <datalist id="countryList">
                                <option value="Philippines">
                                <!-- More options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="contractBarangay">Barangay</label>
                            <input type="text" id="contractBarangay" name="contract_barangay" placeholder="Optional">
                        </div>
                        
                        <!-- Row 2 -->
                        <div class="form-group">
                            <label for="source" data-required=" *">Source</label>
                            <select id="source" name="source" required>
                                <option value="">Select source</option>
                                <option value="DPWH">DPWH</option>
                                <option value="BCI">BCI</option>
                                <option value="EGOV">EGOV</option>
                                <option value="PHILGEPS">PHILGEPS</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="contractRegion" data-required=" *">Region</label>
                            <div class="searchable-select-wrapper" id="contractRegionWrapper">
                                <div class="searchable-select-trigger" id="contractRegionTrigger" tabindex="0">
                                    <span class="searchable-select-label" id="contractRegionLabel">Select region</span>
                                    <span class="searchable-select-arrow">▾</span>
                                </div>
                                <div class="searchable-select-dropdown" id="contractRegionDropdown">
                                    <input type="text" class="searchable-select-search" placeholder="Search region..." autocomplete="off" id="contractRegionSearch">
                                    <div class="searchable-select-options" id="contractRegionOptions"></div>
                                </div>
                                <select id="contractRegion" name="contract_region" required style="display:none;"></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="contractStreet">Street</label>
                            <input type="text" id="contractStreet" name="contract_street" placeholder="Optional">
                        </div>
                        
                        <!-- Row 3 -->
                        <div class="form-group">
                            <label for="contractId" data-required=" *">Contract ID</label>
                            <input type="text" id="contractId" name="contract_id" maxlength="15" placeholder="Up to 15 chars" required>
                        </div>
                        <div class="form-group">
                            <label for="contractProvince" data-required=" *">Province</label>
                            <input type="text" id="contractProvince" name="contract_province" 
                                   list="contractProvinceList" placeholder="Type or select province" required>
                            <datalist id="contractProvinceList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="contractBlkLot">Blk/Lot#</label>
                            <input type="text" id="contractBlkLot" name="contract_blk_lot" placeholder="Optional">
                        </div>
                        
                        <!-- Row 4 -->
                        <div class="form-group">
                            <label for="contractorName" data-required=" *">Contractor Name</label>
                            <input type="text" id="contractorName" name="contractor_name" required placeholder="Full contractor name">
                        </div>
                        <div class="form-group">
                            <label for="contractCity" data-required=" *">City</label>
                            <input type="text" id="contractCity" name="contract_city" 
                                   list="contractCityList" placeholder="Type or select city" required>
                            <datalist id="contractCityList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="contractCoords">Coordinates</label>
                            <input type="text" id="contractCoords" name="contract_coords" placeholder="e.g. 14.5994,120.9842">
                        </div>
                        
                        <!-- Row 5 -->
                        <div class="form-group">
                            <label for="contactPerson">Contact Person</label>
                            <input type="text" id="contactPerson" name="contact_person" placeholder="Full name">
                        </div>
                        <div class="form-group"></div>
                        <div class="form-group"></div>
                        
                        <!-- Row 6 -->
                        <div class="form-group">
                            <label for="contactNumber" data-required=" *">Contact Number</label>
                            <input type="text" id="contactNumber" name="contact_number" placeholder="(555) 123-4567" required>
                        </div>
                        <div class="form-group"></div>
                        <div class="form-group"></div>
                        
                        <!-- Hidden field for PHILGEPS notice -->
                        <div class="form-group" id="philgepsNoticeGroup" style="display: none; grid-column: 1 / -1;">
                            <label for="philgepsNotice" data-required=" *">Notice Reference Number</label>
                            <input type="text" id="philgepsNotice" name="notice_reference_number" 
                                   maxlength="5" pattern="[0-9]{5}" placeholder="12345" 
                                   title="Enter exactly 5 digits">
                        </div>
                        
                        <!-- Hidden field for source other -->
                        <div class="form-group" id="sourceOtherGroup" style="display: none; grid-column: 1 / -1;">
                            <label for="sourceOther" data-required=" *">Specify Source</label>
                            <input type="text" id="sourceOther" name="source_other" placeholder="Please specify">
                        </div>
                    </div>
                </div>
                
                <!-- Form Buttons for Step 1 -->
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="prevBtn" style="display:none;">← Previous Step</button>
                    <button type="button" class="btn-primary" id="nextBtn">Next Step →</button>
                </div>
            </div>

            <!-- Step 2: Project Details -->
            <div class="form-step" data-step="2">
                <div class="form-section">
                    <h2>Project Details</h2>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                        <!-- Row 1: Project ID, Country, Barangay -->
                        <div class="form-group">
                            <label for="projectId">Project ID</label>
                            <input type="text" id="projectId" name="project_id" maxlength="100" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label for="projectCountry" data-required=" *">Country</label>
                            <input type="text" id="projectCountry" name="project_country" 
                                   list="projectCountryList" placeholder="Type or select country" required value="Philippines">
                            <datalist id="projectCountryList">
                                <option value="Philippines">
                                <!-- More options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="projectBarangay">Barangay</label>
                            <input type="text" id="projectBarangay" name="project_barangay" placeholder="Optional">
                        </div>
                        
                        <!-- Row 2: Project Name, Region, Street -->
                        <div class="form-group">
                            <label for="projectName" data-required=" *">Project Name</label>
                            <input type="text" id="projectName" name="project_name" required placeholder="Full project name">
                        </div>
                        <div class="form-group">
                            <label for="projectRegion" data-required=" *">Region</label>
                            <div class="searchable-select-wrapper" id="projectRegionWrapper">
                                <div class="searchable-select-trigger" id="projectRegionTrigger" tabindex="0">
                                    <span class="searchable-select-label" id="projectRegionLabel">Select region</span>
                                    <span class="searchable-select-arrow">▾</span>
                                </div>
                                <div class="searchable-select-dropdown" id="projectRegionDropdown">
                                    <input type="text" class="searchable-select-search" placeholder="Search region..." autocomplete="off" id="projectRegionSearch">
                                    <div class="searchable-select-options" id="projectRegionOptions"></div>
                                </div>
                                <select id="projectRegion" name="project_region" required style="display:none;"></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="projectStreet">Street</label>
                            <input type="text" id="projectStreet" name="project_street" placeholder="Optional">
                        </div>
                        
                        <!-- Row 3: Project Value, Province, Blk/Lot# -->
                        <div class="form-group">
                            <label for="projectValue" data-required=" *">Project Value</label>
                            <input type="number" id="projectValue" name="project_value" min="0" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="projectProvince" data-required=" *">Province</label>
                            <input type="text" id="projectProvince" name="project_province" 
                                   list="projectProvinceList" placeholder="Type or select province" required>
                            <datalist id="projectProvinceList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="projectBlkLot">Blk/Lot#</label>
                            <input type="text" id="projectBlkLot" name="project_blk_lot" placeholder="Optional">
                        </div>
                        
                        <!-- Row 4: Completion Rate, City, Coordinates -->
                        <div class="form-group">
                            <label for="completionRate" data-required=" *">Completion Rate (%)</label>
                            <input type="number" id="completionRate" name="completion_rate" min="0" max="100" step="0.01" required placeholder="0-100">
                        </div>
                        <div class="form-group">
                            <label for="projectCity" data-required=" *">City</label>
                            <input type="text" id="projectCity" name="project_city" 
                                   list="projectCityList" placeholder="Type or select city" required>
                            <datalist id="projectCityList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="projectCoords">Coordinates</label>
                            <input type="text" id="projectCoords" name="project_coords" placeholder="e.g. 14.5994,120.9842">
                        </div>
                    </div>
                </div>
                
                <!-- Form Buttons for Step 2 -->
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="prevBtn2">← Previous Step</button>
                    <button type="button" class="btn-primary" id="nextBtn2">Next Step →</button>
                </div>
            </div>

            <!-- Step 3: Material Details -->
            <div class="form-step" data-step="3">
                <div class="form-section">
                    <h2>Material Details</h2>
                    <p style="color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.8rem;">Enter material specifications for this priority project.</p>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <!-- Row 1 -->
                        <div class="form-group">
                            <label for="sheetPileMaterial">Sheet Pile Material</label>
                            <input type="text" id="sheetPileMaterial" name="sheet_pile_material" placeholder="e.g. PSM 280">
                        </div>
                        <div class="form-group">
                            <label for="sheetPileValue">Sheet Pile Value (Amount)</label>
                            <input type="number" id="sheetPileValue" name="sheet_pile_value" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group"></div>
                        
                        <!-- Row 2 -->
                        <div class="form-group">
                            <label for="drbsMaterial">DRBs Material</label>
                            <input type="text" id="drbsMaterial" name="drbs_material" placeholder="Material type">
                        </div>
                        <div class="form-group">
                            <label for="drbsValue">DRBs Value (Amount)</label>
                            <input type="number" id="drbsValue" name="drbs_value" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group"></div>
                    </div>
                </div>
                
                <!-- Form Buttons for Step 3 -->
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="prevBtn3">← Previous Step</button>
                    <button type="button" class="btn-primary" id="nextBtn3">Next Step →</button>
                </div>
            </div>

            <!-- Step 4: Picture Upload -->
            <div class="form-step" data-step="4">
                <div class="form-section">
                    <h2>Picture Upload</h2>
                    <p style="color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.8rem;">Upload project pictures and documentation. Supported formats: JPG, PNG, PDF. Max file size: 10MB per file.</p>
                    
                    <!-- File Upload Area -->
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">📁</div>
                        <div class="upload-text">
                            <h3>Drag & Drop Files Here</h3>
                            <p>or <span class="upload-browse">browse files</span></p>
                        </div>
                        <input type="file" id="fileInput" multiple accept="image/*,.pdf" style="display: none;">
                    </div>
                    
                    <!-- File List -->
                    <div class="file-list" id="fileList" style="display: none;">
                        <h3 style="font-size: 0.8rem; margin-bottom: 0.5rem; color: var(--text-primary);">Selected Files:</h3>
                        <div id="fileItems"></div>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div class="upload-progress" id="uploadProgress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="progress-text" id="progressText">Uploading... 0%</div>
                    </div>
                </div>
                
                <!-- Form Buttons for Step 4 -->
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="prevBtn4">← Previous Step</button>
                    <button type="submit" class="btn-success" id="submitBtn">Submit Project</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/encode-priority.js?v=7"></script>
<script>
const BASE = '<?= $base ?>';
</script>
</body>
</html>
