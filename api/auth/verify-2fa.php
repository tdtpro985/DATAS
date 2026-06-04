<?php
/* ============================================================
   POST /api/v1/auth/verify-2fa
   ============================================================
   Accepts: multipart/form-data { totp_code, setup_secret? }
   Completes the 2FA login step.

   NOTE: Full TOTP (Google Authenticator) requires a TOTP library.
   For XAMPP without Composer, we implement a lightweight RFC 6238
   TOTP verifier inline — no external dependencies needed.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Must have a pending 2FA session
if (empty($_SESSION['pending_2fa_user_id'])) {
    jsonError('No pending 2FA session', 400);
}

if (!defined('MAX_2FA_ATTEMPTS')) {
    require_once __DIR__ . '/../../config.php';
}

$userId   = (int) $_SESSION['pending_2fa_user_id'];
$totpCode = preg_replace('/\s+/', '', $_POST['totp_code'] ?? '');

if (strlen($totpCode) !== 6 || !ctype_digit($totpCode)) {
    jsonError('Invalid authentication code format', 400);
}

// SECURITY: Check rate limiting for 2FA attempts
if (!checkRateLimit('2fa_attempt', $userId, MAX_2FA_ATTEMPTS, 2FA_ATTEMPT_WINDOW)) {
    jsonError('Too many failed authentication attempts. Please try again later.', 429);
}

$db = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    jsonError('User not found', 404);
}

// ── Inline RFC 6238 TOTP verifier ─────────────────────────
function base32Decode(string $base32): string {
    $base32 = strtoupper($base32);
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $buffer = 0;
    $bitsLeft = 0;
    for ($i = 0; $i < strlen($base32); $i++) {
        $val = strpos($alphabet, $base32[$i]);
        if ($val === false) continue;
        $buffer = ($buffer << 5) | $val;
        $bitsLeft += 5;
        if ($bitsLeft >= 8) {
            $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
            $bitsLeft -= 8;
        }
    }
    return $output;
}

function verifyTotp(string $secret, string $code, int $window = 2): bool {
    $key = base32Decode($secret);
    $time = (int) floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        $t = pack('N*', 0) . pack('N*', $time + $i);
        $hash = hash_hmac('sha1', $t, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $otp = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
             (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        if (str_pad((string) $otp, 6, '0', STR_PAD_LEFT) === $code) {
            return true;
        }
    }
    return false;
}

if (!verifyTotp($user['totp_secret'], $totpCode)) {
    jsonError('Invalid authentication code', 401);
}

// ── TOTP verified — complete login ────────────────────────
// SECURITY: Regenerate session ID to prevent session fixation
unset($_SESSION['pending_2fa_user_id']);
regenerateSessionId();

$_SESSION['user'] = [
    'id'        => $user['id'],
    'email'     => $user['email'],
    'full_name' => $user['full_name'],
    'role'      => $user['role'],
];

jsonResponse(['user' => $_SESSION['user']]);
