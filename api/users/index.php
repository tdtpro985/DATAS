<?php
/* ============================================================
   GET  /api/v1/users   — list all users
   POST /api/v1/users   — create a new user
   ============================================================
   Admin / superadmin only.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../activity-logger.php';

requireRole(['admin', 'superadmin']);

$db = getDB();

// ── GET: list all users ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->query("
            SELECT id, email, full_name, role, branch, reset_requested, created_at
            FROM users
            ORDER BY created_at DESC
        ");
        $users = $stmt->fetchAll();

        $users = array_map(fn($u) => [
            'id'              => (int)  $u['id'],
            'email'           => $u['email'],
            'full_name'       => $u['full_name'],
            'role'            => $u['role'],
            'branch'          => $u['branch'],
            'reset_requested' => (bool) $u['reset_requested'],
            'created_at'      => $u['created_at'],
        ], $users);

        jsonResponse($users);
    } catch (Exception $e) {
        error_log('GET /api/v1/users error: ' . $e->getMessage());
        // SECURITY: Don't expose error details to client
        jsonError('Failed to fetch users', 500);
    }
}

// ── POST: create user OR update user (if id parameter provided) ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $body = getJsonBody();
        if (!$body) jsonError('Request body required', 400);

        // Check if this is an update (id in query parameter)
        $userId = (int) ($_GET['id'] ?? 0);
        $isUpdate = $userId > 0;

        $email     = trim($body['email']      ?? '');
        $fullName  = trim($body['full_name'] ?? '');
        $password  = $body['password']       ?? '';
        $role      = trim($body['role']      ?? 'encoder');
        $branch    = isset($body['branch']) ? (trim($body['branch']) ?: null) : null;

        if (empty($email))  jsonError('Email is required', 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email format', 422);

        $validRoles = ['superadmin', 'admin', 'encoder'];
        if (!in_array($role, $validRoles, true)) jsonError('Invalid role', 422);

        if ($isUpdate) {
            // ── UPDATE USER ──
            // Verify user exists
            $check = $db->prepare('SELECT id, email FROM users WHERE id = :id LIMIT 1');
            $check->execute([':id' => $userId]);
            $existing = $check->fetch();
            if (!$existing) jsonError('User not found', 404);

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
            // Always update branch (allows clearing it too)
            $sets[] = 'branch = :branch';
            $params[':branch'] = $branch;
            if (!empty($password)) {
                // SECURITY: Validate password strength
                $passwordValidation = validatePassword($password);
                if (!$passwordValidation['valid']) {
                    jsonError($passwordValidation['errors'][0], 422);
                }
                $sets[] = 'password_hash = :hash';
                $params[':hash'] = hashPassword($password);
            }

            $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $db->prepare($sql)->execute($params);

            logActivity($db, $_SESSION['user']['id'], ActivityType::USER_UPDATE, EntityType::USER, $userId, "User #{$userId} updated");
            jsonResponse(['id' => $userId, 'message' => 'User updated successfully.']);
        } else {
            // ── CREATE USER ──
            // SECURITY: Validate password strength
            $passwordValidation = validatePassword($password);
            if (!$passwordValidation['valid']) {
                jsonError($passwordValidation['errors'][0], 422);
            }

            // Check email uniqueness
            $check = $db->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
            $check->execute([':e' => $email]);
            if ($check->fetch()) jsonError('Email already exists', 409);

            $hash = hashPassword($password);
            $stmt = $db->prepare("
                INSERT INTO users (email, full_name, password_hash, role, branch)
                VALUES (:email, :full_name, :hash, :role, :branch)
            ");
            $stmt->execute([
                ':email'      => $email,
                ':full_name'  => $fullName,
                ':hash'       => $hash,
                ':role'       => $role,
                ':branch'     => $branch,
            ]);

            $newUserId = (int) $db->lastInsertId();
            logActivity($db, $_SESSION['user']['id'], ActivityType::USER_CREATE, EntityType::USER, $newUserId, "User created: {$email} (role: {$role})");
            jsonResponse(['id' => $newUserId, 'message' => 'User created successfully.'], 201);
        }
    } catch (Exception $e) {
        error_log('POST /api/v1/users error: ' . $e->getMessage());
        // SECURITY: Don't expose error details
        jsonError('Failed to save user', 500);
    }
}

// ── DELETE: delete user ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $userId = (int) ($_GET['id'] ?? 0);
        if ($userId <= 0) {
            jsonError('Invalid user ID', 400);
        }

        $check = $db->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $check->execute([':id' => $userId]);
        if (!$check->fetch()) jsonError('User not found', 404);

        $db->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);
        logActivity($db, $_SESSION['user']['id'], ActivityType::USER_DELETE, EntityType::USER, $userId, "User #{$userId} deleted");
        jsonResponse(['message' => 'User deleted successfully.']);
    } catch (Exception $e) {
        error_log('DELETE /api/v1/users error: ' . $e->getMessage());
        jsonError('Failed to delete user', 500);
    }
}

jsonError('Method not allowed', 405);
