<?php
/* ============================================================
   pages/login.php — Serve the login page
   ============================================================
   If the user is already logged in, redirect to the dashboard.
   Otherwise outputs login page HTML directly.
   ============================================================ */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Compute the app base path dynamically so asset URLs work whether
// the app is served from / or a subdirectory like /new-dashboard/.
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir; // e.g. "" (root) or "/new-dashboard"

// Already logged in — redirect based on role
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';
    if ($role === 'admin' || $role === 'superadmin') {
        header('Location: ' . $base . '/admin');
    } elseif ($role === 'encoder') {
        header('Location: ' . $base . '/encode');
    } else {
        header('Location: ' . $base . '/');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/login.css?v=2">
</head>
<body>

    <!-- ── Page loader — fades out after assets load ── -->
    <div id="pageLoader" class="page-loader">
        <div class="spinner"></div>
    </div>

    <div class="login-container">
        <div class="login-card">

            <!-- Logo + tagline -->
            <div class="logo-section">
                <img src="<?= $base ?>/static/images/Logo_tdt.png" alt="TDT Powersteel"
                    onerror="this.style.display='none'">
                <p>Sign in to access your dashboard</p>
            </div>

            <!-- Error message box -->
            <div id="errorBox" class="error-message" role="alert" aria-live="assertive"></div>

            <!-- Admin portal redirect link (shown only when admin tries to log in here) -->
            <a id="adminPortalLink" href="<?= $base ?>/admin/login">Go to Admin Portal →</a>

            <!-- ── Login form ── -->
            <form id="loginForm" novalidate>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email"
                            placeholder="Enter your email"
                            required autocomplete="email"
                            aria-required="true">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password"
                            placeholder="••••••••"
                            required autocomplete="current-password"
                            aria-required="true">
                        <!-- Password visibility toggle -->
                        <span class="password-toggle" id="togglePassword"
                            role="button" tabindex="0" aria-label="Show password">
                            <!-- Eye open -->
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <!-- Eye closed -->
                            <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <button type="submit" id="submitBtn" aria-label="Sign in">
                    <span id="btnText">Sign In</span>
                    <div id="loader" class="loader"></div>
                </button>

            </form>

            <!-- Forgot password link -->
            <div style="text-align:center; margin-top:1rem;">
                <a href="#" id="forgotPasswordLink">Forgot Password?</a>
            </div>

            <!-- ── MFA step (hidden until needed) ── -->
            <div id="mfaStep" style="display:none; margin-top:1.5rem;">
                <h3>Two-Factor Authentication</h3>
                <p id="mfaInstructions">Enter the 6-digit code from your authenticator app.</p>

                <!-- QR code (shown only on first-time 2FA setup) -->
                <div id="qrCodeContainer" style="display:none; text-align:center; margin-bottom:1.5rem;">
                    <img id="qrCodeImg" src="" alt="QR Code for authenticator setup">
                    <p style="color:#B0BEC5; font-size:0.82rem; margin-top:0.75rem;">
                        Scan with Google Authenticator or Authy, then enter the code below.
                    </p>
                </div>

                <form id="mfaForm" novalidate>
                    <div class="form-group">
                        <div class="input-wrapper" style="display:flex; justify-content:center;">
                            <input type="text" id="totpCode" name="totp_code"
                                placeholder="000 000" maxlength="7"
                                required autocomplete="one-time-code"
                                aria-label="6-digit authentication code"
                                inputmode="numeric">
                        </div>
                    </div>
                    <input type="hidden" id="setupSecret" name="setup_secret">

                    <button type="submit" id="submitMfaBtn">
                        <span id="mfaBtnText">Verify &amp; Sign In</span>
                        <div id="mfaLoader" class="loader"></div>
                    </button>

                    <div style="text-align:center; margin-top:1rem;">
                        <a href="#" id="backToLoginLink">← Back to login</a>
                    </div>
                </form>
            </div>

        </div>
        <!-- /.login-card -->

        <p class="login-footer">
            TDT Powersteel &mdash; SILEP v3 &nbsp;|&nbsp;
            <a href="<?= $base ?>/admin/login" class="login-link">Admin Portal</a>
        </p>
    </div>
    <!-- /.login-container -->

    <!-- ── Forgot password modal ── -->
    <div id="resetModal" class="reset-modal" style="display:none;" role="dialog"
        aria-modal="true" aria-labelledby="resetModalTitle">
        <div class="reset-modal-content">
            <h2 id="resetModalTitle">Reset Password</h2>
            <p>Enter your email to request a password reset from your administrator.</p>

            <div id="resetErrorBox" class="error-message" role="alert"></div>
            <div id="resetSuccessBox" class="success-message" style="display:none;"></div>

            <form id="resetForm" novalidate>
                <div class="form-group">
                    <label for="resetEmail">Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="resetEmail" name="email"
                            placeholder="Enter your email" required>
                    </div>
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1.25rem;">
                    <button type="button" id="cancelResetBtn" style="flex:1;">Cancel</button>
                    <button type="submit" id="submitResetBtn" style="flex:1; margin-top:0;">
                        <span id="resetBtnText">Request Reset</span>
                        <div id="resetLoader" class="loader"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>const BASE = '<?= $base ?>';</script>
    <script src="<?= $base ?>/static/js/login.js?v=6"></script>
</body>
</html>
