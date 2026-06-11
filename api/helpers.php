<?php
/* ============================================================
   helpers.php — Shared API Utilities
   ============================================================
   - JSON response helpers
   - Session auth check
   - Input sanitization
   ============================================================ */

// ── Start session if not already started ──────────────────
if (session_status() === PHP_SESSION_NONE) {
    // Load config for session timeout settings
    if (!defined('SESSION_TIMEOUT')) {
        require_once __DIR__ . '/../config.php';
    }
    
    // Set session timeout settings before starting session
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
    
    // Configure session cookie settings for security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// ── Check session timeout ──────────────────────────────
if (!defined('SESSION_IDLE_TIMEOUT')) {
    require_once __DIR__ . '/../config.php';
}

if (isset($_SESSION['user'])) {
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    $currentTime = time();
    
    if ($lastActivity > 0 && ($currentTime - $lastActivity) > SESSION_IDLE_TIMEOUT) {
        // Session has been idle too long, destroy it
        session_destroy();
        session_start();
        // Clear the user from session
        unset($_SESSION['user']);
    }
    
    $_SESSION['last_activity'] = $currentTime;
}

// ── Security and CORS headers ──────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'');
header('Access-Control-Allow-Credentials: true');

// CORS: Allow requests from same origin (Fixed for security)
$allowedOrigin = defined('CORS_ORIGIN') ? CORS_ORIGIN : 'http://localhost';

// For API requests, always allow credentials
header('Access-Control-Allow-Credentials: true');

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // If there's an origin header, validate it matches allowed origin
    $requestOrigin = $_SERVER['HTTP_ORIGIN'];
    if ($requestOrigin === $allowedOrigin) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    } else {
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    }
} else {
    // No origin header, use configured origin
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send a JSON response and exit.
 */
function jsonResponse(mixed $data, int $status = 200): void {
    // Clean ALL output buffers to prevent corrupted JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send a JSON error response and exit.
 */
function jsonError(string $message, int $status = 400): void {
    jsonResponse(['detail' => $message], $status);
}

/**
 * Require an authenticated session. Returns the session user array.
 * Sends 401 and exits if not authenticated.
 */
function requireAuth(): array {
    // Debug logging in debug mode
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log('requireAuth() - Session ID: ' . session_id());
        error_log('requireAuth() - Session data: ' . json_encode($_SESSION));
        error_log('requireAuth() - Has user: ' . (empty($_SESSION['user']) ? 'NO' : 'YES'));
    }
    
    if (empty($_SESSION['user'])) {
        jsonError('Not authenticated', 401);
    }
    return $_SESSION['user'];
}

/**
 * Require a specific role (or array of roles).
 * Sends 403 and exits if the user doesn't have the required role.
 */
function requireRole(string|array $roles): array {
    $user = requireAuth();
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $allowed, true)) {
        jsonError('Forbidden: insufficient permissions', 403);
    }
    return $user;
}

/**
 * Get the request body as a decoded JSON object/array.
 * Returns null if the body is empty or invalid JSON.
 */
function getJsonBody(): ?array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) return null;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Get a query parameter, trimmed. Returns null if not set or empty.
 */
function qp(string $key, mixed $default = null): mixed {
    $val = $_GET[$key] ?? $default;
    return is_string($val) ? trim($val) : $val;
}

/**
 * Hash a password using bcrypt.
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a bcrypt hash.
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Generate a cryptographically secure random token.
 */
function generateToken(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

/**
 * Get the current period filter from query params.
 * Defaults to 'monthly' if not set or invalid.
 */
function getPeriod(): string {
    $p = qp('period', 'monthly');
    return in_array($p, ['daily', 'weekly', 'monthly'], true) ? $p : 'monthly';
}

/**
 * Get the current month filter (1-12). Returns null if not set or 'all'.
 */
function getMonth(): ?int {
    $m = qp('month');
    if ($m === null || $m === 'all' || $m === '') {
        return null;
    }
    $m = (int) $m;
    return ($m >= 1 && $m <= 12) ? $m : null;
}

/**
 * Get the current year. Returns null if not set or 'all'.
 */
function getYear(): ?int {
    $y = qp('year');
    if ($y === null || $y === 'all' || $y === '') {
        return null;
    }
    $y = (int) $y;
    return ($y >= 2020 && $y <= 2030) ? $y : null;
}

/**
 * Get the region filter. Returns null if 'all' or not set.
 */
function getRegion(): ?string {
    $r = qp('region', 'all');
    return ($r === 'all' || empty($r)) ? null : $r;
}

/**
 * Build the date range WHERE clause fragment for a given period/month/year.
 * Returns an array: ['sql' => '...', 'params' => [...]]
 */
function buildDateFilter(string $dateColumn = 'publication_date'): array {
    $period = getPeriod();
    $month  = getMonth();
    $year   = getYear();

    // If no month/year specified, return a condition that matches all records
    // INCLUDING those with NULL publication_date
    if ($month === null && $year === null) {
        return [
            'sql'    => "1=1",
            'params' => [],
        ];
    }

    // If only year is specified (no month)
    if ($month === null && $year !== null) {
        return [
            'sql'    => "($dateColumn IS NOT NULL AND YEAR($dateColumn) = :year)",
            'params' => [':year' => $year],
        ];
    }

    // If only month is specified (unlikely, but handle it)
    if ($month !== null && $year === null) {
        // Use current year as default
        $year = (int) date('Y');
    }

    switch ($period) {
        case 'daily':
            return [
                'sql'    => "($dateColumn IS NOT NULL AND DATE($dateColumn) = CURDATE())",
                'params' => [],
            ];
        case 'weekly':
            return [
                'sql'    => "($dateColumn IS NOT NULL AND YEARWEEK($dateColumn, 1) = YEARWEEK(CURDATE(), 1))",
                'params' => [],
            ];
        case 'monthly':
        default:
            return [
                'sql'    => "($dateColumn IS NOT NULL AND MONTH($dateColumn) = :month AND YEAR($dateColumn) = :year)",
                'params' => [':month' => $month, ':year' => $year],
            ];
    }
}

/**
 * Track user session activity.
 * Creates or updates session record in user_sessions table.
 */
function trackSessionActivity(int $userId): void {
    try {
        require_once __DIR__ . '/db.php';
        $db = getDB();
        
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Check if session exists
        $stmt = $db->prepare("
            SELECT id FROM user_sessions 
            WHERE user_id = :user_id AND session_id = :session_id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':session_id' => $sessionId
        ]);
        
        if ($stmt->fetch()) {
            // Update existing session
            $stmt = $db->prepare("
                UPDATE user_sessions 
                SET last_activity = NOW(),
                    ip_address = :ip_address,
                    user_agent = :user_agent
                WHERE user_id = :user_id AND session_id = :session_id
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':session_id' => $sessionId,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent
            ]);
        } else {
            // Create new session
            $stmt = $db->prepare("
                INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity)
                VALUES (:user_id, :session_id, :ip_address, :user_agent, NOW())
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':session_id' => $sessionId,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent
            ]);
        }
    } catch (PDOException $e) {
        // Silently fail if user_sessions table doesn't exist or has different schema
        // This allows login to work even if the migration hasn't been run
        error_log('Session tracking failed: ' . $e->getMessage());
    }
}

/**
 * Check if a user is currently online.
 * A user is considered online if they have activity within the last 5 minutes.
 */
function isUserOnline(int $userId): bool {
    require_once __DIR__ . '/db.php';
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM user_sessions
        WHERE user_id = :user_id
        AND last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute([':user_id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($result['count'] ?? 0) > 0;
}

/**
 * Clean up old sessions (older than 24 hours).
 */
function cleanupOldSessions(): void {
    require_once __DIR__ . '/db.php';
    $db = getDB();
    
    $db->exec("
        DELETE FROM user_sessions
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
}

/**
 * Generate and store a CSRF token in the session.
 * Returns the token to be included in requests.
 */
function generateCsrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(32);
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token from request.
 * Token can be in POST data, JSON body, or X-CSRF-Token header.
 */
function validateCsrfToken(): void {
    if (!defined('CSRF_TOKEN_LIFETIME')) {
        require_once __DIR__ . '/../config.php';
    }
    
    // Skip CSRF validation for GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return;
    }
    
    // Get token from request
    $token = $_POST['csrf_token'] ?? null;
    if (!$token && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        $body = json_decode(file_get_contents('php://input'), true);
        $token = $body['csrf_token'] ?? null;
    }
    $token = $token ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    
    // Validate token
    if (empty($_SESSION['csrf_token']) || empty($token) || $token !== $_SESSION['csrf_token']) {
        jsonError('CSRF token validation failed', 403);
    }
    
    // Check token expiration
    $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
    if (time() - $tokenTime > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token']);
        jsonError('CSRF token expired', 403);
    }
}

/**
 * Check rate limiting for an action (e.g., login attempts, 2FA attempts).
 * Returns true if action is allowed, false if rate limit exceeded.
 */
function checkRateLimit(string $action, int $userId, int $maxAttempts, int $window): bool {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $key = $action . '_' . $userId;
    $now = time();
    
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [];
    }
    
    // Clean old attempts outside the window
    $_SESSION['rate_limits'][$key] = array_filter(
        $_SESSION['rate_limits'][$key],
        fn($time) => $now - $time < $window
    );
    
    // Check if limit exceeded
    if (count($_SESSION['rate_limits'][$key]) >= $maxAttempts) {
        return false;
    }
    
    // Record this attempt
    $_SESSION['rate_limits'][$key][] = $now;
    return true;
}

/**
 * Get remaining rate limit attempts.
 */
function getRateLimitRemaining(string $action, int $userId, int $maxAttempts): int {
    if (!isset($_SESSION['rate_limits'])) {
        return $maxAttempts;
    }
    
    $key = $action . '_' . $userId;
    $attempts = count($_SESSION['rate_limits'][$key] ?? []);
    return max(0, $maxAttempts - $attempts);
}

/**
 * Validate password strength.
 * Returns array with 'valid' bool and 'errors' array if invalid.
 */
function validatePassword(string $password): array {
    if (!defined('MIN_PASSWORD_LENGTH')) {
        require_once __DIR__ . '/../config.php';
    }
    
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => count($errors) === 0,
        'errors' => $errors
    ];
}

/**
 * Regenerate session ID after authentication.
 * Prevents session fixation attacks.
 */
function regenerateSessionId(): void {
    session_regenerate_id(true);
}

/**
 * Escape HTML to prevent XSS.
 * JavaScript version: function escapeHtml(text) { ... }
 */
function escapeHtml(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate MIME type of uploaded file using file signature (magic bytes).
 */
function validateFileMimeType(string $tmpPath, array $allowedMimes): bool {
    if (!is_file($tmpPath)) {
        return false;
    }
    
    // Get file signature
    $handle = fopen($tmpPath, 'r');
    if (!$handle) {
        return false;
    }
    
    $signature = fread($handle, 12);
    fclose($handle);
    
    // Check magic bytes
    $mimeSignatures = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89\x50\x4E\x47"],
        'image/gif' => ["\x47\x49\x46\x38"],  // GIF87a, GIF89a
        'image/webp' => ["\x52\x49\x46\x46"],  // RIFF
    ];
    
    foreach ($allowedMimes as $mime) {
        if (!isset($mimeSignatures[$mime])) {
            continue;
        }
        
        foreach ($mimeSignatures[$mime] as $sig) {
            if (str_starts_with($signature, $sig)) {
                return true;
            }
        }
    }
    
    // Fallback to mime_content_type if available
    if (function_exists('mime_content_type')) {
        $detectedMime = mime_content_type($tmpPath);
        return in_array($detectedMime, $allowedMimes, true);
    }
    
    return false;
}

/**
 * Validate input against a whitelist.
 */
function validateAgainstWhitelist(string $value, array $whitelist): bool {
    return in_array($value, $whitelist, true);
}

