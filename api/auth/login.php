<?php
/* ============================================================
   POST /api/v1/auth/login
   ============================================================
   Accepts: multipart/form-data  { email, password }
   Returns:
     200 { user: {...} }           — login success
     202 { require_2fa: true }     — TOTP required
     202 { setup_2fa: true, qr_code, secret } — first-time 2FA setup
     401 { detail: "..." }         — bad credentials
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

if (!defined('MAX_LOGIN_ATTEMPTS')) {
    require_once __DIR__ . '/../../config.php';
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonError('Email and password are required', 400);
}

$db = getDB();

// Fetch user by email - use placeholder ID for rate limiting before we know the user
$stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$userIdTemp = $stmt->fetchColumn();
$rateKey = $userIdTemp ? (int)$userIdTemp : crc32($email); // Use user ID if found, else email hash

// SECURITY: Check rate limiting on login attempts
if (!checkRateLimit('login_attempt', $rateKey, MAX_LOGIN_ATTEMPTS, LOGIN_ATTEMPT_WINDOW)) {
    jsonError('Too many login attempts. Please try again later.', 429);
}

// Fetch user by email
$stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !verifyPassword($password, $user['password_hash'])) {
    jsonError('Invalid email or password', 401);
}

// ── 2FA check ─────────────────────────────────────────────
// For now: 2FA is optional. If totp_secret is set, require TOTP.
// We store a pending_user_id in session to complete after TOTP.
if (!empty($user['totp_secret'])) {
    // Store pending auth in session — completed by verify-2fa endpoint
    $_SESSION['pending_2fa_user_id'] = $user['id'];
    jsonResponse(['require_2fa' => true], 202);
}

// ── No 2FA — log in directly ───────────────────────────────
// SECURITY: Regenerate session ID to prevent session fixation
regenerateSessionId();

$_SESSION['user'] = [
    'id'        => $user['id'],
    'email'     => $user['email'],
    'full_name' => $user['full_name'],
    'role'      => $user['role'],
];

// Track session activity
trackSessionActivity($user['id']);

// Update last_login timestamp (if column exists)
try {
    $stmt = $db->prepare('UPDATE users SET updated_at = NOW() WHERE id = :id');
    $stmt->execute([':id' => $user['id']]);
} catch (PDOException $e) {
    // Column might not exist, continue
}

jsonResponse([
    'user' => $_SESSION['user'],
]);

