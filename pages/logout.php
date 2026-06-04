<?php
/* ============================================================
   pages/logout.php — Logout Handler
   ============================================================
   Destroys the session and redirects to login page.
   ============================================================ */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Compute base path
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

// Update last_activity before removing session (so we keep "last seen" timestamp)
if (!empty($_SESSION['user']['id'])) {
    try {
        require_once __DIR__ . '/../api/db.php';
        $db = getDB();
        $sessionId = session_id();
        
        // Update the session's last_activity to NOW before deleting
        // This preserves the "last seen" timestamp without failing logout.
        $stmt = $db->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW() 
            WHERE user_id = :user_id AND session_id = :session_id
        ");
        $stmt->execute([
            ':user_id' => $_SESSION['user']['id'],
            ':session_id' => $sessionId
        ]);
    } catch (Exception $e) {
        error_log('Logout cleanup error: ' . $e->getMessage());
    }
}

// Destroy PHP session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params['path'], 
        $params['domain'],
        $params['secure'], 
        $params['httponly']
    );
}
session_destroy();

// Redirect to login page
header('Location: ' . $base . '/login');
exit;
