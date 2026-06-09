<?php
/* ============================================================
   pages/users.php — User Management (Admin + Superadmin)
   ============================================================ */
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

if ($role !== 'superadmin' && $role !== 'admin') {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - TDT Powersteel</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=23">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <style>
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: var(--text-secondary);
            transition: color 0.2s ease;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--text-primary);
        }
        
        .form-group input[type="password"],
        .form-group input[type="text"] {
            padding-right: 3rem;
        }
        
        .form-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
    </style>
</head>
<body data-role="<?= $role ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">
    <div class="card animate-fadeInUp" style="grid-column: 1 / -1;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--sp-4);">
            <div>
                <h1 style="font-size:var(--text-2xl); font-weight:800; margin:0; color:var(--text-primary);">User Management</h1>
                <p style="margin:0.5rem 0 0; color:var(--text-secondary);">Create, edit, and delete system users</p>
            </div>
            <button class="btn-primary" id="addUserBtn">+ Create User</button>
        </div>

        <div style="margin-bottom:var(--sp-4);">
            <input type="text" id="userSearch" placeholder="Search by name or email..." style="width:100%; max-width:480px; padding:0.75rem 1rem; background:var(--bg-input); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:var(--text-primary);">
        </div>

        <div id="usersList">
            <div style="text-align:center; padding:3rem; color:var(--text-secondary);">Loading...</div>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="userModalTitle">Create User</h2>
            <button class="modal-close" id="closeUserModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                <div class="form-group">
                    <label for="userEmail">Email *</label>
                    <input type="email" id="userEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="userFullName">Full Name *</label>
                    <input type="text" id="userFullName" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="userRole">Role *</label>
                    <select id="userRole" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                        <option value="encoder">Encoder</option>
                    </select>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label for="userPassword">Password</label>
                    <div class="password-field">
                        <input type="password" id="userPassword" name="password">
                        <button type="button" id="togglePassword" class="password-toggle">👁️</button>
                    </div>
                    <small class="form-hint">Minimum 8 characters for new accounts or when changing password</small>
                </div>
                <div class="form-group" id="passwordGroupConfirm">
                    <label for="userPasswordConfirm">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="userPasswordConfirm" name="password_confirm">
                        <button type="button" id="togglePasswordConfirm" class="password-toggle">👁️</button>
                    </div>
                    <small class="form-hint">Re-enter password to confirm</small>
                </div>
                <div id="userFormError" class="error-text" style="display:none; margin-top:0.5rem;"></div>
            </form>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary" id="cancelUserBtn">Cancel</button>
            <button class="btn-primary" id="saveUserBtn">Save</button>
        </div>
    </div>
</div>

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/users.js?v=1"></script>
</body>
</html>
