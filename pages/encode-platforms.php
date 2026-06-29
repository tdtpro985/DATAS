<?php
/* ============================================================
   pages/encode-platforms.php — Platform Leads Encoding Form
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
    <title>Encode Platform Leads — TDT Powersteel</title>
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
    <link rel="stylesheet" href="<?= $base ?>/static/css/encode-platforms.css?v=1">
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">
    <div class="card animate-fadeInUp">
        <div class="page-header">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                <div>
                    <h1>Encode Platform Leads</h1>
                    <p>Enter platform lead information. Required fields: Source, Contact Person, Contact Number, and Email Address.</p>
                </div>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= $base ?>/platforms'" style="padding: 0.4rem 0.8rem; font-size: 0.7rem;">
                    ← Back to Platform Leads
                </button>
            </div>
        </div>

        <div class="form-container">
            <div class="success-message" id="successMessage">
                Platform lead has been successfully encoded!
            </div>

            <form id="platformForm">
                <div class="form-content">
                    <div class="form-section">
                        <h2>Platform Lead Information</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="source" data-required=" *">Source</label>
                                <input type="text" id="source" name="source" required placeholder="Type source (e.g., Facebook, DPWH, BCI)" list="sourceSuggestions" autocomplete="off">
                                <datalist id="sourceSuggestions">
                                </datalist>
                            </div>
                            
                            <div class="form-group">
                                <label for="contactPerson" data-required=" *">Contact Person</label>
                                <input type="text" id="contactPerson" name="contact_person" required placeholder="Full name of contact person">
                            </div>
                            
                            <div class="form-group">
                                <label for="contactNumber" data-required=" *">Contact Number</label>
                                <input type="text" id="contactNumber" name="contact_number" required placeholder="Telephone or cellphone number">
                            </div>
                            
                            <div class="form-group">
                                <label for="emailAddress" data-required=" *">Email Address</label>
                                <input type="email" id="emailAddress" name="email_address" required placeholder="email@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="companyName">Company Name</label>
                                <input type="text" id="companyName" name="company_name" placeholder="Optional company name">
                            </div>
                            
                            <div class="form-group">
                                <label for="companyLocation">Company Location</label>
                                <input type="text" id="companyLocation" name="company_location" placeholder="Optional location">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="materialsQuantity">List of Materials and Quantity</label>
                                <textarea id="materialsQuantity" name="materials_quantity" placeholder="Optional: List materials and quantities needed..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" id="clearBtn">Clear Form</button>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <span>💾</span>
                        Save Platform Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal-overlay" id="errorModal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2>Error</h2>
            <button class="modal-close" onclick="closeErrorModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="errorMessage">An error occurred.</p>
        </div>
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid rgba(0,0,0,0.08);">
            <button type="button" class="btn btn-primary" onclick="closeErrorModal()">
                OK
            </button>
        </div>
    </div>
</div>



<script>
const BASE = '<?= $base ?>';
</script>
<script src="<?= $base ?>/static/js/encode-platforms.js?v=1"></script>
</body>
</html>