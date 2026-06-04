<?php
/* ============================================================
   PUT    /api/v1/users/{id}   — update user
   DELETE /api/v1/users/{id}   — delete user
   ============================================================
   Admin / superadmin only.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireRole(['admin', 'superadmin']);

$db = getDB();

// Extract user ID from URL — router sets it as a query param
$userId = (int) ($_GET['id'] ?? 0);
if ($userId <= 0) {
    jsonError('Invalid user ID', 400);
}

// ── PUT: update user ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $body = getJsonBody();
        if (!$body) jsonError('Request body required', 400);

        // Verify user exists
        $check = $db->prepare('SELECT id, email FROM users WHERE id = :id LIMIT 1');
        $check->execute([':id' => $userId]);
        $existing = $check->fetch();
        if (!$existing) jsonError('User not found', 404);

        $email    = trim($body['email']     ?? $existing['email']);
        $fullName = trim($body['full_name'] ?? '');
        $role     = trim($body['role']      ?? '');
        $password = $body['password']       ?? null;
        $reset2fa = !empty($body['reset_2fa']);

        if (empty($email)) jsonError('Email is required', 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email format', 422);

        $validRoles = ['superadmin', 'admin', 'encoder', 'sales_rep'];
        if (!empty($role) && !in_array($role, $validRoles, true)) {
            jsonError('Invalid role', 422);
        }

        // Check email uniqueness if changed
        if ($email !== $existing['email']) {
            $dup = $db->prepare('SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1');
            $dup->execute([':e' => $email, ':id' => $userId]);
            if ($dup->fetch()) jsonError('Email already exists', 409);
        }

        // Build update dynamically
        $sets   = ['email = :email'];
        $params = [':email' => $email, ':id' => $userId];

        if ($fullName !== '') {
            $sets[] = 'full_name = :full_name';
            $params[':full_name'] = $fullName;
        }
        if (!empty($role)) {
            $sets[] = 'role = :role';
            $params[':role'] = $role;
        }
        if (!empty($password)) {
            if (strlen($password) < 6) jsonError('Password must be at least 6 characters', 422);
            $sets[] = 'password_hash = :hash';
            $params[':hash'] = hashPassword($password);
        }
        if ($reset2fa) {
            $sets[] = 'totp_secret = NULL';
        }
        // Clear reset_requested flag when admin edits the user
        $sets[] = 'reset_requested = 0';

        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $db->prepare($sql)->execute($params);

        jsonResponse(['id' => $userId, 'message' => 'User updated.']);
    } catch (Exception $e) {
        error_log('PUT /api/v1/users/{id} error: ' . $e->getMessage());
        jsonError('Failed to update user: ' . $e->getMessage(), 500);
    }
}

// ── DELETE: delete user ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $check = $db->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
    $check->execute([':id' => $userId]);
    if (!$check->fetch()) jsonError('User not found', 404);

    $db->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);
    jsonResponse(['message' => 'User deleted.']);
}

jsonError('Method not allowed', 405);
