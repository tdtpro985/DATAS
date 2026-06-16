<?php
/* ============================================================
   pages/encode.php — Encode Form Selector
   ============================================================
   Landing page for choosing between Non-Priority and Priority encoding.
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
    <title>Data Entry — TDT Powersteel</title>
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
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">
    <style>
        .encode-selector-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .encode-option-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .encode-option-card:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .option-icon {
            font-size: 3rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        .option-icon.priority {
            background: rgba(248, 113, 113, 0.15);
            color: #fca5a5;
        }
        .option-icon.nonpro {
            background: rgba(96, 165, 250, 0.15);
            color: #93c5fd;
        }
        .option-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .option-desc {
            color: var(--text-secondary);
            line-height: 1.6;
            flex-grow: 1;
        }
        .option-steps {
            font-size: 0.85rem;
            color: var(--text-secondary);
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        .step-count {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
        }
    </style>
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">
    <div class="card animate-fadeInUp" style="grid-column: 1 / -1;">
        <div style="margin-bottom:var(--sp-4);">
            <h1 style="font-size:var(--text-2xl); font-weight:800; margin:0; color:var(--text-primary);">Data Entry Portal</h1>
            <p style="margin:0.75rem 0 0; color:var(--text-secondary); max-width:720px; line-height:1.6;">
                Choose the type of project you'd like to encode. Each form type has 3 simple steps to complete.
            </p>
        </div>

        <div class="encode-selector-grid">
            <!-- Non-Priority Option -->
            <a href="<?= $base ?>/encode/non-priority" class="encode-option-card">
                <div class="option-icon nonpro">📋</div>
                <div class="option-title">Non-Priority Project</div>
                <div class="option-desc">
                    Encode standard projects with contract details, project specifications, and material amounts.
                </div>
                <div class="option-steps">
                    <span class="step-count">3 Steps</span>
                    <div style="margin-top:0.75rem; font-size:0.8rem;">
                        Step 1: Contract Details<br>
                        Step 2: Project Details<br>
                        Step 3: Material Details
                    </div>
                </div>
            </a>

            <!-- Priority Option -->
            <a href="<?= $base ?>/encode/priority" class="encode-option-card">
                <div class="option-icon priority">⭐</div>
                <div class="option-title">Priority Project</div>
                <div class="option-desc">
                    Encode high-priority projects with contractor details, project completion rates, and material specifications.
                </div>
                <div class="option-steps">
                    <span class="step-count">3 Steps</span>
                    <div style="margin-top:0.75rem; font-size:0.8rem;">
                        Step 1: Contractor Details<br>
                        Step 2: Project Details<br>
                        Step 3: Material Details
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
</body>
</html>
