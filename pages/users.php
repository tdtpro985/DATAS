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

if ($role !== 'superadmin') {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Core Styles -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/components.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/users.css?v=2">
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<!-- Content goes directly inside ap-main (opened by sidebar.php) -->
<div class="um-page dashboard">
    <div class="um-panel card animate-fadeInUp">
        <!-- Page Header -->
        <div class="um-header">
            <div>
                <h1 class="um-title">👥 User Management</h1>
                <p class="um-subtitle">Create, edit, and delete system users</p>
            </div>
            <button class="btn btn-primary" id="addUserBtn">
                <span>+</span> Create User
            </button>
        </div>

        <!-- Search -->
        <div class="um-search">
            <input type="text" id="userSearch" placeholder="Search by name or email...">
        </div>

        <!-- User Groups Grid -->
        <div id="usersList">
            <div class="um-loading">
                <div class="um-spinner"></div>
                <p>Loading users...</p>
            </div>
        </div>
    </div>
</div>

</div><!-- /.ap-main -->
</div><!-- /.ap-shell -->

<!-- User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <h2 id="userModalTitle">Create User</h2>
            <button class="modal-close" id="closeUserModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" name="id">

                <div class="form-group">
                    <label for="userEmail">Email *</label>
                    <input type="email" id="userEmail" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="userFullName">Full Name *</label>
                    <input type="text" id="userFullName" name="full_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="userRole">Role *</label>
                    <select id="userRole" name="role" class="form-control" required onchange="toggleBranchField()">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                        <option value="encoder">Encoder</option>
                    </select>
                </div>

                <div class="form-group" id="branchGroup" style="display:none;">
                    <label for="userBranch">Branch</label>
                    <select id="userBranch" name="branch" class="form-control">
                        <option value="">Select Branch</option>
                        <option value="TDT Manila">TDT Manila</option>
                        <option value="TDT Cagayan De Oro">TDT Cagayan De Oro</option>
                        <option value="TDT Cebu">TDT Cebu</option>
                        <option value="TDT Davao">TDT Davao</option>
                        <option value="TDT General Santos">TDT General Santos</option>
                        <option value="TDT Ilocos">TDT Ilocos</option>
                        <option value="TDT Iloilo">TDT Iloilo</option>
                        <option value="TDT Isabela">TDT Isabela</option>
                        <option value="TDT Legazpi">TDT Legazpi</option>
                        <option value="PS Manila">PS Manila</option>
                        <option value="PS Laug">PS Laug</option>
                        <option value="PS Batangas">PS Batangas</option>
                    </select>
                </div>

                <div class="form-group" id="passwordGroup">
                    <label for="userPassword">Password</label>
                    <div class="password-field">
                        <input type="password" id="userPassword" name="password" class="form-control">
                        <button type="button" id="togglePassword" class="password-toggle" aria-label="Toggle Password Visibility">👁️</button>
                    </div>
                    <small class="form-hint">Minimum 8 characters for new accounts or when changing password</small>
                </div>

                <div class="form-group" id="passwordGroupConfirm">
                    <label for="userPasswordConfirm">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="userPasswordConfirm" name="password_confirm" class="form-control">
                        <button type="button" id="togglePasswordConfirm" class="password-toggle" aria-label="Toggle Confirm Password Visibility">👁️</button>
                    </div>
                    <small class="form-hint">Re-enter password to confirm</small>
                </div>

                <div id="userFormError" class="empty-state" style="display:none; padding:0.5rem; margin-top:0.5rem;"></div>
            </form>
        </div>
        <div class="modal-footer" style="justify-content:flex-end; gap:0.75rem;">
            <button class="btn btn-secondary" id="cancelUserBtn">Cancel</button>
            <button class="btn btn-primary" id="saveUserBtn">Save</button>
        </div>
    </div>
</div>

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/users.js?v=4"></script>
</body>
</html>
