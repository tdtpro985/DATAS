<?php
/* ============================================================
   pages/admin-login.php — Serve the admin login page
   ============================================================
   If already logged in as admin/superadmin, redirect to /admin.
   Otherwise outputs admin login HTML directly.
   ============================================================ */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Compute the app base path dynamically so asset URLs work whether
// the app is served from / or a subdirectory like /new-dashboard/.
// e.g. SCRIPT_NAME = /new-dashboard/api/router.php → base = /new-dashboard
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir; // e.g. "" (root) or "/new-dashboard"

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';
    if ($role === 'admin' || $role === 'superadmin') {
        header('Location: ' . $base . '/admin');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/login.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin_login.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=15">
</head>

<body>
    <!-- Full Page Loader -->
    <div id="pageLoader" class="page-loader">
        <div class="spinner"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <img src="<?= $base ?>/static/images/Logo_tdt.png" alt="TDT Powersteel Admin"
                    style="width:100%; max-width:200px; height:auto; margin:0 auto 1.25rem; display:block;"
                    onerror="this.style.display='none'">
                <span class="admin-portal-badge">Admin Portal</span>
                <p style="margin-top:0.75rem;">Sign in to access the management tools</p>
            </div>

            <div id="errorBox" class="error-message"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="Enter email" required
                            autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required
                            autocomplete="current-password">
                        <span class="password-toggle" id="togglePassword" role="button" aria-label="Show password">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                </path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </span>
                    </div>
                </div>

                <button type="submit" id="submitBtn">
                    <span id="btnText">Sign In to Admin</span>
                    <div id="loader" class="loader"></div>
                </button>
            </form>

            <!-- MFA Step -->
            <div id="mfaStep" style="display: none; margin-top: 1rem;">
                <h3 style="color: var(--text-main); text-align: center; margin-bottom: 1rem;">Two-Factor Authentication
                </h3>
                <p id="mfaInstructions"
                    style="color: var(--text-dim); text-align: center; font-size: 0.875rem; margin-bottom: 1.5rem;">
                    Enter the 6-digit code from your authenticator app.
                </p>

                <div id="qrCodeContainer" style="display: none; text-align: center; margin-bottom: 1.5rem;">
                    <img id="qrCodeImg" src="" alt="QR Code"
                        style="max-width: 200px; border-radius: 8px; border: 4px solid white;">
                    <p style="color: var(--text-main); font-size: 0.85rem; margin-top: 0.75rem; font-weight: 500;">
                        Scan this QR code with Google Authenticator or Authy.
                    </p>
                </div>

                <form id="mfaForm">
                    <div class="form-group">
                        <div class="input-wrapper" style="justify-content: center; display: flex;">
                            <input type="text" id="totpCode" name="totp_code" placeholder="000 000" maxlength="7"
                                style="text-align: center; font-size: 1.5rem; letter-spacing: 0.25rem; padding: 0.75rem; width: 80%;"
                                required autocomplete="one-time-code">
                        </div>
                    </div>

                    <input type="hidden" id="setupSecret" name="setup_secret">

                    <button type="submit" id="submitMfaBtn">
                        <span id="mfaBtnText">Verify & Sign In</span>
                        <div id="mfaLoader" class="loader"></div>
                    </button>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="#" id="backToLoginLink"
                            style="color: var(--text-dim); text-decoration: none; font-size: 0.85rem;">← Back to
                            login</a>
                    </div>
                </form>
            </div>


            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem;">
                <a href="<?= $base ?>/login" class="login-link">← Return to Standard Login</a>
            </div>
        </div>
        <!-- /.login-card -->

        <p class="login-footer">
            TDT Powersteel &mdash; SILEP v1.1 &nbsp;|&nbsp; Admin Portal
        </p>
    </div>
    <!-- /.login-container -->

    <script>const BASE = '<?= $base ?>';</script>
    <script src="<?= $base ?>/static/js/admin_login.js?v=2"></script>
</body>

</html>
