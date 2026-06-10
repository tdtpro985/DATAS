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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=23">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <style>
        :root {
            --form-bg: rgba(20, 24, 32, 0.65);
            --form-border: rgba(248, 113, 113, 0.2);
            --input-bg: rgba(30, 36, 48, 0.85);
            --input-border: rgba(248, 113, 113, 0.2);
            --input-focus: rgba(248, 113, 113, 0.35);
        }

        html, body {
            overflow: hidden;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Hide sidebar for full screen experience */
        .sidebar {
            display: none !important;
        }
        
        /* Hide any header if present */
        .header {
            display: none !important;
        }

        /* Full height layout - keep original width */
        .dashboard { 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
            padding: 0.1rem; 
            overflow: hidden;
            margin-left: 0;
        }
        
        .card { 
            flex: 1; 
            display: flex;
            flex-direction: column;
            height: calc(100vh - 0.2rem);
            overflow: hidden;
            padding: 0.3rem;
            margin: 0;
        }

        /* Ultra-compact page header */
        .page-header {
            margin-bottom: 0.3rem;
            padding-bottom: 0.3rem;
            border-bottom: 1px solid rgba(248, 113, 113, 0.15);
            flex-shrink: 0;
        }
        .page-header h1 {
            font-size: 1.1rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-primary);
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }
        .page-header p {
            margin: 0.2rem 0 0;
            color: var(--text-secondary);
            font-size: 0.7rem;
            line-height: 1.2;
        }

        /* Ultra-compact step indicator */
        .step-indicator {
            display: flex;
            gap: 0.3rem;
            justify-content: center;
            margin-bottom: 0.3rem;
            padding: 0.3rem 0;
            border-bottom: 1px solid rgba(248, 113, 113, 0.18);
            flex-shrink: 0;
        }
        .step-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(30, 36, 48, 0.9);
            border: 2px solid rgba(248, 113, 113, 0.2);
            font-weight: 700;
            font-size: 0.7rem;
            color: var(--text-secondary);
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }
        .step-badge.active {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            border-color: #f87171;
            color: white;
            box-shadow: 0 2px 12px rgba(248, 113, 113, 0.35);
            transform: scale(1.04);
        }
        .step-badge.completed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: #10b981;
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        .step-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, rgba(248, 113, 113, 0.22) 0%, rgba(248, 113, 113, 0.08) 100%);
            top: 14px;
            left: 50%;
            z-index: -1;
        }

        /* Form container with minimal scroll */
        form {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: calc(100% - 4rem); /* Account for header and step indicator */
        }

        .form-step { 
            display: none; 
            opacity: 0; 
            flex: 1;
            overflow-y: auto;
            padding-right: 0.3rem;
            height: 100%;
        }
        .form-step.active { 
            display: flex;
            flex-direction: column;
            animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            opacity: 1;
        }
        @keyframes slideIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* Ultra-compact form sections */
        .form-section {
            background: var(--form-bg);
            border: 1px solid var(--form-border);
            border-radius: 6px;
            padding: 0.5rem;
            margin-bottom: 0.3rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
            transition: all 0.3s ease;
        }
        .form-section:hover {
            border-color: rgba(248, 113, 113, 0.4);
            box-shadow: 0 8px 24px rgba(248, 113, 113, 0.1);
        }

        .form-section h2 {
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            line-height: 1.2;
        }
        .form-section h2::before {
            content: '';
            width: 2px;
            height: 14px;
            background: linear-gradient(180deg, #f87171 0%, #ef4444 100%);
            border-radius: 1px;
        }

        /* Ultra-compact grid system */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.4rem;
            align-items: start;
        }
        
        /* Fixed 3-column layout override */
        .form-grid[style*="grid-template-columns: 1fr 1fr 1fr"] {
            grid-template-columns: 1fr 1fr 1fr !important;
            gap: 1rem;
        }
        
        /* Responsive breakpoints for fixed 3-column layout */
        @media (max-width: 1024px) {
            .form-grid[style*="grid-template-columns: 1fr 1fr 1fr"] {
                grid-template-columns: 1fr 1fr !important;
                gap: 0.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .form-grid[style*="grid-template-columns: 1fr 1fr 1fr"] {
                grid-template-columns: 1fr !important;
                gap: 0.6rem;
            }
        }
        
        /* Ultra-compact form groups */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-height: 42px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
            margin-bottom: 0.25rem;
            line-height: 1.1;
        }
        .form-group label::after {
            content: attr(data-required);
            color: #ef4444;
        }

        .form-group input,
        .form-group select {
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 6px;
            padding: 0.4rem 0.6rem;
            color: var(--text-primary);
            font-size: 0.8rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            outline: none;
            min-height: 32px;
            line-height: 1.2;
            font-weight: 500;
        }
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
            font-weight: 400;
        }
        .form-group input:hover,
        .form-group select:hover {
            border-color: rgba(248, 113, 113, 0.35);
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--input-focus);
            box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.1);
            background: rgba(30, 36, 48, 0.95);
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23f87171' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.4rem center;
            background-size: 10px;
            padding-right: 1.5rem;
        }

        /* Ultra-compact form buttons */
        .form-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
            padding: 0.4rem;
            border-top: 1px solid rgba(248, 113, 113, 0.15);
            flex-wrap: wrap;
            background: var(--form-bg);
            border-radius: 4px;
            flex-shrink: 0;
        }

        .btn-primary, .btn-secondary, .btn-success {
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            outline: none;
            line-height: 1.2;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(248, 113, 113, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(248, 113, 113, 0.35);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Responsive breakpoints */
        @media (min-width: 1200px) {
            .form-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
        }

        @media (min-width: 768px) and (max-width: 1199px) {
            .form-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.8rem;
            }
        }

        @media (min-width: 480px) and (max-width: 767px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.6rem;
            }
        }

        /* Mobile ultra-compact adjustments */
        @media (max-width: 479px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 0.3rem;
            }
            
            .dashboard {
                padding: 0.1rem;
            }
            
            .card {
                padding: 0.4rem;
            }
            
            .form-section {
                padding: 0.6rem;
                margin-bottom: 0.3rem;
            }
            
            .form-group {
                min-height: 45px;
            }
            
            .form-group input,
            .form-group select {
                min-height: 28px;
                padding: 0.3rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .btn-primary, .btn-secondary, .btn-success {
                padding: 0.3rem 0.8rem;
                font-size: 0.7rem;
            }
            
            .form-buttons {
                padding: 0.4rem;
                margin-top: 0.5rem;
            }
        }

        /* Scrollbar styling */
        .form-step::-webkit-scrollbar {
            width: 6px;
        }
        .form-step::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }
        .form-step::-webkit-scrollbar-thumb {
            background: rgba(248, 113, 113, 0.3);
            border-radius: 3px;
        }
        .form-step::-webkit-scrollbar-thumb:hover {
            background: rgba(248, 113, 113, 0.5);
        }

        /* Special styling for material details step */
        .form-step[data-step="3"] .form-section p {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-size: 0.7rem;
            line-height: 1.3;
        }

        /* Hide empty form groups on mobile */
        @media (max-width: 479px) {
            .form-group:empty {
                display: none;
            }
        }

        /* File Upload Styles */
        .upload-area {
            border: 2px dashed rgba(248, 113, 113, 0.3);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: rgba(248, 113, 113, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            margin-bottom: 1rem;
        }

        .upload-area:hover {
            border-color: rgba(248, 113, 113, 0.5);
            background: rgba(248, 113, 113, 0.08);
        }

        .upload-area.dragover {
            border-color: #f87171;
            background: rgba(248, 113, 113, 0.12);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .upload-text h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .upload-text p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .upload-browse {
            color: #f87171;
            font-weight: 600;
            text-decoration: underline;
            cursor: pointer;
        }

        .upload-browse:hover {
            color: #ef4444;
        }

        /* File List Styles */
        .file-list {
            background: rgba(30, 36, 48, 0.5);
            border: 1px solid rgba(248, 113, 113, 0.2);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            background: rgba(248, 113, 113, 0.05);
            border: 1px solid rgba(248, 113, 113, 0.1);
            border-radius: 4px;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .file-item:hover {
            background: rgba(248, 113, 113, 0.08);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .file-icon {
            font-size: 1.2rem;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.2rem;
        }

        .file-size {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .file-remove {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 4px;
            padding: 0.3rem 0.5rem;
            color: #fca5a5;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .file-remove:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
        }

        /* Upload Progress Styles */
        .upload-progress {
            background: rgba(30, 36, 48, 0.5);
            border: 1px solid rgba(248, 113, 113, 0.2);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #f87171, #ef4444);
            border-radius: 4px;
            transition: width 0.3s ease;
            width: 0%;
        }

        .progress-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-align: center;
        }

        /* Mobile adjustments for upload area */
        @media (max-width: 479px) {
            .upload-area {
                padding: 1.5rem 1rem;
            }
            
            .upload-icon {
                font-size: 2.5rem;
                margin-bottom: 0.8rem;
            }
            
            .upload-text h3 {
                font-size: 0.9rem;
            }
            
            .upload-text p {
                font-size: 0.75rem;
            }
            
            .file-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .file-info {
                width: 100%;
            }
            
            .file-remove {
                align-self: flex-end;
            }
        }
    </style>
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
                        <!-- Column 1: Left -->
                        <div class="form-group">
                            <label for="publishedDate" data-required=" *">Published Date</label>
                            <input type="date" id="publishedDate" name="published_date" required placeholder="mm/dd/yyyy">
                        </div>
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
                            <label for="contractId" data-required=" *">Contract ID</label>
                            <input type="text" id="contractId" name="contract_id" maxlength="8" placeholder="8 chars (L/N)" required>
                        </div>
                        <div class="form-group">
                            <label for="contractorName" data-required=" *">Contractor Name</label>
                            <input type="text" id="contractorName" name="contractor_name" required placeholder="Full contractor name">
                        </div>
                        <div class="form-group">
                            <label for="contactPerson">Contact Person</label>
                            <input type="text" id="contactPerson" name="contact_person" placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label for="contactNumber" data-required=" *">Contact Number</label>
                            <input type="text" id="contactNumber" name="contact_number" placeholder="(555) 123-4567" required>
                        </div>

                        <!-- Column 2: Center -->
                        <div class="form-group">
                            <label for="contractCountry" data-required=" *">Country</label>
                            <input type="text" id="contractCountry" name="contract_country" 
                                   list="countryList" placeholder="Type or select country" required value="Philippines">
                            <datalist id="countryList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="contractRegion" data-required=" *">Region</label>
                            <input type="text" id="contractRegion" name="contract_region" 
                                   list="contractRegionList" placeholder="Type or select region" required>
                            <datalist id="contractRegionList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
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
                            <label for="contractCity" data-required=" *">City</label>
                            <input type="text" id="contractCity" name="contract_city" 
                                   list="contractCityList" placeholder="Type or select city" required>
                            <datalist id="contractCityList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group"></div>
                        <div class="form-group"></div>

                        <!-- Column 3: Right -->
                        <div class="form-group">
                            <label for="contractBarangay">Barangay</label>
                            <input type="text" id="contractBarangay" name="contract_barangay" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label for="contractStreet">Street</label>
                            <input type="text" id="contractStreet" name="contract_street" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label for="contractBlkLot">Blk/Lot#</label>
                            <input type="text" id="contractBlkLot" name="contract_blk_lot" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label for="contractCoords">Coordinates</label>
                            <input type="text" id="contractCoords" name="contract_coords" placeholder="e.g. 14.5994,120.9842">
                        </div>
                        <div class="form-group"></div>
                        <div class="form-group"></div>
                        
                        <!-- Hidden conditional fields -->
                        <div class="form-group" id="philgepsNoticeGroup" style="display: none; grid-column: 1 / -1;">
                            <label for="philgepsNotice" data-required=" *">Notice Reference Number</label>
                            <input type="text" id="philgepsNotice" name="notice_reference_number" 
                                   maxlength="5" pattern="[0-9]{5}" placeholder="12345" 
                                   title="Enter exactly 5 digits">
                        </div>
                        
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
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <!-- Column 1: Left -->
                        <div class="form-group">
                            <label for="projectName" data-required=" *">Project Name</label>
                            <input type="text" id="projectName" name="project_name" required placeholder="Full project name">
                        </div>
                        <div class="form-group">
                            <label for="projectId">Project ID</label>
                            <input type="text" id="projectId" name="project_id" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label for="projectValue" data-required=" *">Project Value (Amount)</label>
                            <input type="number" id="projectValue" name="project_value" min="0" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="completionRate" data-required=" *">Completion Rate (%)</label>
                            <input type="number" id="completionRate" name="completion_rate" min="0" max="100" step="0.01" required placeholder="0-100">
                        </div>

                        <!-- Column 2: Center -->
                        <div class="form-group">
                            <label for="projectCountry" data-required=" *">Country</label>
                            <input type="text" id="projectCountry" name="project_country" 
                                   list="projectCountryList" placeholder="Type or select country" required value="Philippines">
                            <datalist id="projectCountryList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="projectRegion" data-required=" *">Region</label>
                            <input type="text" id="projectRegion" name="project_region" 
                                   list="projectRegionList" placeholder="Type or select region" required>
                            <datalist id="projectRegionList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
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
                            <label for="projectCity" data-required=" *">City</label>
                            <input type="text" id="projectCity" name="project_city" 
                                   list="projectCityList" placeholder="Type or select city" required>
                            <datalist id="projectCityList">
                                <!-- Options will be populated dynamically -->
                            </datalist>
                        </div>

                        <!-- Column 3: Right -->
                        <div class="form-group">
                            <label for="projectBarangay">Barangay</label>
                            <input type="text" id="projectBarangay" name="project_barangay" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label for="projectStreet">Street</label>
                            <input type="text" id="projectStreet" name="project_street" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label for="projectBlkLot">Blk/Lot#</label>
                            <input type="text" id="projectBlkLot" name="project_blk_lot" placeholder="Optional">
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

<script>
const BASE = '<?= $base ?>';
</script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/encode-priority.js?v=1"></script>
</body>
</html>
