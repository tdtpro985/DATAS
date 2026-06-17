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

    <style>
        /* ── User Card Styles ── */
        .user-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.01) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .user-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--orange-500), rgba(255, 152, 0, 0.5));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .user-card:hover {
            border-color: rgba(255, 152, 0, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 128, 0, 0.15);
        }

        .user-card:hover::before {
            transform: scaleX(1);
        }

        .user-card .avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--orange-500), #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            color: #000;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(255, 128, 0, 0.3);
        }

        .user-card h3 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-card .email {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-secondary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-card .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        /* Vertical dividers between rows — each row is a column */
        .user-card .meta-row + .meta-row {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .user-card .meta-value {
            padding-left: 1rem;
            border-left: 1px solid rgba(255, 255, 255, 0.06);
        }

        .user-card .meta-row:last-of-type {
            border-bottom: none;
        }

        .user-card .meta-label {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .user-card .meta-value {
            font-size: 0.8rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .user-card .meta-value.branch-tag {
            background: rgba(255, 128, 0, 0.1);
            color: var(--orange-400);
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
        }

        .user-card .card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .user-card .card-actions button {
            flex: 1;
            min-width: 0;
        }

        /* ── Role Group Card ── */
        .role-group-card {
            background: linear-gradient(135deg, rgba(26, 29, 35, 0.95) 0%, rgba(17, 20, 26, 0.98) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            padding: 1.75rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: border-color 0.3s, box-shadow 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .role-group-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--orange-500), rgba(255, 152, 0, 0.5));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .role-group-card:hover {
            border-color: rgba(255, 152, 0, 0.3);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.3), 0 0 30px rgba(255, 128, 0, 0.15);
            transform: translateY(-2px);
        }

        .role-group-card:hover::before {
            transform: scaleX(1);
        }

        .role-group-card h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .role-group-card .user-count {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            background: rgba(255, 128, 0, 0.15);
            color: var(--orange-500);
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .role-group-card .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .role-group-card .stat-item {
            text-align: center;
        }

        .role-group-card .stat-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }

        .role-group-card .stat-value {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .role-group-card .expand-hint {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .role-group-card .expand-hint .arrow {
            font-size: 1.1rem;
            color: var(--orange-500);
            transition: transform 0.3s;
        }

        /* ── Expanded User List ── */
        .expanded-section {
            background: rgba(255, 128, 0, 0.05);
            border: 1px solid var(--orange-500, #FF7A00);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .expanded-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .expanded-header h2 {
            margin: 0 0 0.25rem;
            font-size: 1.375rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .expanded-header p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .expanded-header .close-btn {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.5rem;
            color: var(--text-primary);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .expanded-header .close-btn:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        /* ── Password Field ── */
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
            transition: color 0.2s;
            z-index: 10;
            padding: 0.25rem;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .password-field .form-control {
            padding-right: 3rem;
        }

        .form-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .users-grid {
                grid-template-columns: 1fr;
            }

            .role-group-card .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }

            .expanded-section {
                margin-top: 1rem;
                padding: 1rem;
            }

            .user-card .card-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="ap-shell">
    <div class="ap-main">
        <div class="dashboard">
            <div class="card animate-fadeInUp">
                <!-- Page Header -->
                <div class="section-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <h2 class="card-title" style="margin:0; font-size:1.375rem;">👥 User Management</h2>
                        <p style="margin:0.35rem 0 0; color:var(--text-secondary); font-size:0.9rem;">
                            Create, edit, and delete system users
                        </p>
                    </div>
                    <button class="btn btn-primary" id="addUserBtn">
                        <span>+</span> Create User
                    </button>
                </div>

                <!-- Search -->
                <div class="toolbar" style="margin-bottom:1.5rem;">
                    <div class="search-box">
                        <input type="text" id="userSearch" placeholder="Search by name or email...">
                    </div>
                </div>

                <!-- User Groups Grid -->
                <div id="usersList">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Loading users...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
<script src="<?= $base ?>/static/js/users.js?v=2"></script>
</body>
</html>
</write_to_file>