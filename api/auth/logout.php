<?php
/* ============================================================
   POST /api/v1/auth/logout
   ============================================================
   Destroys the session and returns 200.
   ============================================================ */

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../activity-logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Log logout activity
if (!empty($_SESSION['user'])) {
    try {
        $db = getDB();
        logActivity($db, $_SESSION['user']['id'], ActivityType::USER_LOGOUT, EntityType::USER, $_SESSION['user']['id'], "User {$_SESSION['user']['email']} logged out");
    } catch (Exception $e) {
        // Silently fail - logout should not be blocked by logging
    }
}

// Destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

jsonResponse(['message' => 'Logged out successfully']);
