<?php
/* ============================================================
   pages/encode/non-priority.php — Non-Priority Form (3 Steps)
   ============================================================
   Step 1: Contract Details
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
    <title>Encode Non-Priority Project — TDT Powersteel</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=7">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=23">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">

    <link rel="stylesheet" href="<?= $base ?>/static/css/encode-non-priority.css?v=1">
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/../sidebar.php'; ?>

<div class="dashboard">
    <div class="card animate-fadeInUp" style="grid-column: 1 / -1;">
        <div class="page-header">
            <h1>Encode Non-Priority Project</h1>
            <p>Complete all 3 steps to encode a new non-priority project.</p>
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
            </div>
        </div>

        <form id="encodeForm" novalidate>
            <!-- Step 1: Contract Details -->
            <div class="form-step active" data-step="1">
                <div class="form-section">
                    <h2>Contract Details</h2>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <!-- Row 1 -->
                        <div class="form-group">
                            <label for="publicationDate" data-required=" *">Published Date</label>
                            <input type="date" id="publicationDate" name="publication_date" required>
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
                            </select>
                        </div>
                        <div class="form-group" id="philgepsNoticeGroup" style="display: none;">
                            <label for="philgepsNotice" data-required=" *">Notice Reference Number</label>
                            <input type="text" id="philgepsNotice" name="notice_reference_number" 
                                   maxlength="5" pattern="[0-9]{5}" placeholder="12345" 
                                   title="Enter exactly 5 digits">
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
                            <label for="contractId">Contract ID</label>
                            <input type="text" id="contractId" name="contract_id" maxlength="15" placeholder="Up to 15 chars (optional)">
                        </div>
                        <div class="form-group">
                            <label for="contractProvince" data-required=" *">Province</label>
                            <input type="text" id="contractProvince" name="contract_province" 
                                   list="provinceList" placeholder="Type or select province" required>
                            <datalist id="provinceList">
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
                                   list="cityList" placeholder="Type or select city" required>
                            <datalist id="cityList">
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
                        
                        <!-- Row 4: Project Status, City, Coordinates -->
                        <div class="form-group">
                            <label for="projectStatus" data-required=" *">Project Status</label>
                            <select id="projectStatus" name="project_status" required>
                                <option value="">Select status</option>
                                <option value="For Execution">For Execution</option>
                                <option value="For Bidding">For Bidding</option>
                                <option value="Awarded">Awarded</option>
                            </select>
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
                    <p style="color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.8rem;">Enter amounts for materials used in this project. Leave blank if not applicable.</p>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <!-- Row 1 -->
                        <div class="form-group">
                            <label for="drbs">DRBs (Amount)</label>
                            <input type="number" id="drbs" name="drbs" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="sheetPile">Sheet Pile (Amount)</label>
                            <input type="number" id="sheetPile" name="sheet_pile" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="msPlate">MS Plate (Amount)</label>
                            <input type="number" id="msPlate" name="ms_plate" min="0" step="0.01" placeholder="0.00">
                        </div>
                        
                        <!-- Row 2 -->
                        <div class="form-group">
                            <label for="angleBars">Angle Bars (Amount)</label>
                            <input type="number" id="angleBars" name="angle_bars" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="channelBars">Channel Bars (Amount)</label>
                            <input type="number" id="channelBars" name="channel_bars" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="wideFlange">Wide Flange (Amount)</label>
                            <input type="number" id="wideFlange" name="wide_flange" min="0" step="0.01" placeholder="0.00">
                        </div>
                        
                        <!-- Row 3 -->
                        <div class="form-group">
                            <label for="giBi">GI/BI (Amount)</label>
                            <input type="number" id="giBi" name="gi_bi" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group"></div>
                        <div class="form-group"></div>
                    </div>
                </div>
                
                <!-- Form Buttons for Step 3 -->
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="prevBtn3">← Previous Step</button>
                    <button type="submit" class="btn-success" id="submitBtn">✓ Submit Project</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/encode-non-priority.js?v=3"></script>
<script>
const BASE = '<?= $base ?>';
</script>
</body>
</html>
