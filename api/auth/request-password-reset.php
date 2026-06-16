<?php
/* ============================================================
   POST /api/v1/auth/request-password-reset
   ============================================================
   Accepts: JSON { email }
   Flags the user's account as reset_requested = 1.
   An admin can then set a new password via the admin panel.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../activity-logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$body = getJsonBody();
$email = trim($body['email'] ?? '');

if (empty($email)) {
    jsonError('Email is required', 400);
}

$db = getDB();
$stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    // Don't reveal whether the user exists — return success either way
    jsonResponse(['message' => 'If that email exists, a reset request has been submitted.']);
}

// Flag the account
$db->prepare('UPDATE users SET reset_requested = 1 WHERE id = :id')
   ->execute([':id' => $user['id']]);

logActivity($db, $user['id'], ActivityType::USER_UPDATE, EntityType::USER, $user['id'], "Password reset requested for user #{$user['id']}");

jsonResponse(['message' => 'Password reset requested. Please contact your administrator.']);
