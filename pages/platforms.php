<?php
/* ============================================================
   pages/platforms.php — Platform Leads Table View
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

$role     = $_SESSION['user']['role']      ?? '';
$email    = $_SESSION['user']['email']     ?? '';
$fullName = $_SESSION['user']['full_name'] ?? $email;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Leads | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Base styles -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/roles.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-dropdowns.css?v=1">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg-dark);
            background-image:
                radial-gradient(circle at 15% 25%, rgba(139,92,246,0.05) 0%, transparent 50%),
                radial-gradient(circle at 85% 75%, rgba(139,92,246,0.05) 0%, transparent 50%);
            min-height: 100vh;
            font-family: var(--font);
            color: var(--text-primary);
            overflow-x: hidden;
        }
        
        html {
            overflow-x: hidden;
        }

        /* Platform Leads Page Layout */
        .platforms-container {
            width: 100%;
            max-width: 100%;
            padding: 1.5rem 1.5rem;
            box-sizing: border-box;
            overflow-x: hidden;
        }
        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #8b5cf6, rgba(139, 92, 246, 0.5));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            border-color: rgba(139, 92, 246, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.15);
        }

        .summary-card:hover::before {
            transform: scaleX(1);
        }

        .summary-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(139, 92, 246, 0.05));
            border: 1px solid rgba(139, 92, 246, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }

        .summary-card-content {
            flex: 1;
        }

        .summary-card-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .summary-card-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .summary-card-value.loading {
            color: var(--text-muted);
            font-size: 1.5rem;
        }

        /* Search & Filter Bar */
        .platforms-toolbar {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            max-width: 100%;
            box-sizing: border-box;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .search-box input:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15);
        }

        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
        }

        .btn-refresh, .btn-add {
            padding: 0.75rem 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-refresh {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }

        .btn-refresh:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-add {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border-color: #8b5cf6;
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        
        /* Yes/No Toggle Buttons */
        .yes-no-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .yes-no-btn {
            flex: 1;
            padding: 0.625rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: var(--font);
        }
        
        .yes-no-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .yes-no-btn.active {
            background: rgba(16, 185, 129, 0.2) !important;
            border-color: rgba(16, 185, 129, 0.6) !important;
            color: #10b981 !important;
            font-weight: 700;
        }
        
        /* ── Sales Tracking Form ── */
        .sales-tracking-section {
            background: rgba(255, 128, 0, 0.05);
            border: 1px solid rgba(255, 128, 0, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .sales-tracking-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--orange-500);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sales-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .sales-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .sales-form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .sales-form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .sales-form-input,
        .sales-form-select,
        .sales-form-textarea {
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: var(--font);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .sales-form-input:focus,
        .sales-form-select:focus,
        .sales-form-textarea:focus {
            border-color: var(--orange-500);
            box-shadow: 0 0 0 2px rgba(255, 128, 0, 0.15);
        }
        
        .sales-form-select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23B0BEC5' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
        }
        
        .sales-form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Platform Leads Table Card */
        .platforms-card {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 1rem;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        .table-wrapper {
            overflow-x: hidden;
            overflow-y: auto;
            max-height: calc(100vh - 300px);
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }
        
        .table-wrapper::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        
        .table-wrapper::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.3);
            border-radius: 4px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.5);
        }

        .platforms-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .platforms-table thead {
            background: rgba(255, 255, 255, 0.03);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .platforms-table th {
            padding: 1rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            white-space: nowrap;
        }
        
        .platforms-table th:nth-child(1) { width: 12%; } /* Source */
        .platforms-table th:nth-child(2) { width: 18%; } /* Company Name */
        .platforms-table th:nth-child(3) { width: 15%; } /* Contact Person */
        .platforms-table th:nth-child(4) { width: 13%; } /* Contact Number */
        .platforms-table th:nth-child(5) { width: 18%; } /* Email Address */
        .platforms-table th:nth-child(6) { width: 12%; } /* Location */
        .platforms-table th:nth-child(7) { width: 12%; } /* Date Added */

        .platforms-table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.2s;
            cursor: pointer;
        }

        .platforms-table tbody tr:hover {
            background: rgba(139, 92, 246, 0.08);
        }
        
        .platforms-table tbody tr:active {
            background: rgba(139, 92, 246, 0.12);
        }

        .platforms-table td {
            padding: 1rem;
            font-size: 0.875rem;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .platforms-table td.col-date {
            text-align: right;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            font-size: 0.9rem;
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(139, 92, 246, 0.2);
            border-top-color: #8b5cf6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .platforms-container {
                padding: 1rem;
            }
            
            .platforms-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: unset;
            }
            
            .platforms-table th,
            .platforms-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.8rem;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Modal Styles - Centered in Main Content (excluding sidebar) */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 240px; /* Sidebar width */
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.2s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            max-width: 800px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            animation: slideInUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            margin: auto;
        }

        .modal-large {
            max-width: 900px;
        }

        /* Responsive Modal */
        @media (max-width: 768px) {
            .modal-overlay {
                left: 0; /* Full width on mobile (sidebar hidden) */
            }
            
            .modal-content {
                width: 95%;
                max-height: 90vh;
                border-radius: 0.75rem;
            }
            
            .modal-large {
                max-width: 95%;
            }
        }
        
        @media (max-width: 480px) {
            .modal-content {
                width: 98%;
                max-height: 95vh;
                border-radius: 0.5rem;
                padding: 0;
            }
            
            .modal-overlay {
                padding: 0.5rem;
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        @media (max-width: 480px) {
            .modal-header {
                padding: 1rem;
            }
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }
        
        @media (max-width: 480px) {
            .modal-header h2 {
                font-size: 1rem;
            }
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.25rem;
            color: var(--text-secondary);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .detail-section {
            margin-bottom: 1.5rem;
        }

        .detail-section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #8b5cf6;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .detail-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            word-break: break-word;
        }

        .materials-content {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.75rem;
            padding: 1rem;
            font-size: 0.9rem;
            color: var(--text-primary);
            white-space: pre-wrap;
        }

        .modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-save {
            background: #8b5cf6;
            border-color: #8b5cf6;
            color: white;
        }

        .btn-save:hover {
            background: #7c3aed;
            box-shadow: 0 4px 16px rgba(139, 92, 246, 0.4);
        }

        .btn-edit {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }

        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
        }

        .btn-archive {
            border-color: rgba(251, 191, 36, 0.3);
            background: rgba(251, 191, 36, 0.1);
            color: #fcd34d;
        }

        .btn-archive:hover {
            background: rgba(251, 191, 36, 0.2);
            border-color: rgba(251, 191, 36, 0.5);
        }

        /* Form styles in modal */
        .form-section {
            background: rgba(139, 92, 246, 0.05);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: var(--font);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15);
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23B0BEC5' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body data-role="<?= htmlspecialchars($role) ?>">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="platforms-container">
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-icon">📊</div>
            <div class="summary-card-content">
                <div class="summary-card-label">Total Platform Leads</div>
                <div class="summary-card-value" id="totalCount">Loading...</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-card-icon">📧</div>
            <div class="summary-card-content">
                <div class="summary-card-label">This Month</div>
                <div class="summary-card-value" id="monthlyCount">Loading...</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-card-icon">🏢</div>
            <div class="summary-card-content">
                <div class="summary-card-label">With Companies</div>
                <div class="summary-card-value" id="companyCount">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Search & Actions Toolbar -->
    <div class="platforms-toolbar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search platform leads...">
        </div>
        
        <button class="btn-refresh" id="refreshBtn">
            🔄 Refresh
        </button>
        
        <?php if (in_array($role, ['encoder', 'admin', 'superadmin'], true)): ?>
        <a href="<?= $base ?>/encode-platforms" class="btn-add">
            ➕ Add Platform Lead
        </a>
        <?php endif; ?>
    </div>

    <!-- Platform Leads Table -->
    <div class="platforms-card">
        <div class="table-wrapper">
            <table class="platforms-table" id="platformsTable">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th>Email Address</th>
                        <th>Location</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody id="platformsTableBody">
                    <!-- Loading state -->
                    <tr>
                        <td colspan="7">
                            <div class="loading-state">
                                <div class="loading-spinner"></div>
                                <p>Loading platform leads...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Platform Details Modal -->
<div class="modal-overlay" id="platformDetailsModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">📊 Platform Lead Details</h2>
            <button class="modal-close" onclick="closePlatformModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-section">
                <div class="detail-section-title">📋 Lead Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Source</div>
                        <div class="detail-value" id="detailSource">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Person</div>
                        <div class="detail-value" id="detailContactPerson">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value" id="detailContactNumber">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value" id="detailEmailAddress">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Company Name</div>
                        <div class="detail-value" id="detailCompanyName">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Company Location</div>
                        <div class="detail-value" id="detailCompanyLocation">-</div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section" id="materialsSection" style="display: none;">
                <div class="detail-section-title">📦 Materials & Quantity</div>
                <div class="materials-content" id="detailMaterials">
                    -
                </div>
            </div>
            
            <div class="detail-section">
                <div class="detail-section-title">📅 System Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Date Added</div>
                        <div class="detail-value" id="detailCreatedAt">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value" id="detailUpdatedAt">-</div>
                    </div>
                </div>
            </div>
            <!-- Sales Tracking Section -->
            <div class="sales-tracking-section" data-role-access="superadmin,admin,sales_rep">
                <div class="sales-tracking-title">📊 Sales Tracking</div>
                <div class="sales-form-grid">
                    <!-- Row 1 -->
                    <div class="sales-form-group" data-role-access="superadmin,admin">
                        <label class="sales-form-label">Sales Representative <span style="color: #ff7070;">*</span></label>
                        <select class="sales-form-select" id="platform-sales-rep-select">
                            <option value="">Select SR...</option>
                        </select>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Sales Qualified Leads</label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="no">No</button>
                        </div>
                    </div>
                    
                    <!-- Row 2 -->
                    <div class="sales-form-group" data-role-access="superadmin,admin">
                        <label class="sales-form-label">Branch <span style="color: #ff7070;">*</span></label>
                        <input type="text" class="sales-form-input" id="platform-branch-input" readonly placeholder="Auto-filled from SR">
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">To Win</label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="to_win" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="to_win" data-value="no">No</button>
                        </div>
                    </div>
                    
                    <!-- Row 3 -->
                    <div class="sales-form-group">
                        <label class="sales-form-label">Contacted</label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="contacted" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="contacted" data-value="no">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">W/L Amount (₱) <span id="platform-wl-amount-required" style="color: #ff7070; display: none;">*</span></label>
                        <input type="number" class="sales-form-input" id="platform-wl-amount-input" placeholder="0.00" step="0.01" min="0">
                    </div>
                    
                    <!-- Row 4 -->
                    <div class="sales-form-group">
                        <label class="sales-form-label">Quoted</label>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="quoted" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="quoted" data-value="no">No</button>
                        </div>
                    </div>
                    
                    <div class="sales-form-group">
                        <label class="sales-form-label">Remarks <span style="color: #ff7070;">*</span></label>
                        <textarea class="sales-form-textarea" id="platform-remarks-textarea" placeholder="Enter remarks..."></textarea>
                    </div>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                    <button type="button" class="btn-action btn-primary" id="savePlatformTrackingBtn" onclick="savePlatformTracking()" style="flex: 1;">
                        💾 Save Sales Tracking
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action btn-edit" onclick="editPlatform()" id="editBtn">
                ✏️ Edit
            </button>
            <button type="button" class="btn-action btn-archive" onclick="archivePlatform()" id="archiveBtn">
                🗃️ Archive
            </button>
            <button type="button" class="btn-action" onclick="closePlatformModal()">
                ✖️ Close
            </button>
        </div>
    </div>
</div>

<!-- Edit Platform Modal -->
<div class="modal-overlay" id="editPlatformModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>✏️ Edit Platform Lead</h2>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editPlatformForm">
                <div class="form-section">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="editSource">Source *</label>
                            <select id="editSource" name="source" required>
                                <option value="">Select source</option>
                                <option value="DPWH">DPWH</option>
                                <option value="BCI">BCI</option>
                                <option value="PHILGEPS">PHILGEPS</option>
                                <option value="EGO">EGO</option>
                                <option value="V">V</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editContactPerson">Contact Person *</label>
                            <input type="text" id="editContactPerson" name="contact_person" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editContactNumber">Contact Number *</label>
                            <input type="text" id="editContactNumber" name="contact_number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editEmailAddress">Email Address *</label>
                            <input type="email" id="editEmailAddress" name="email_address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editCompanyName">Company Name</label>
                            <input type="text" id="editCompanyName" name="company_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="editCompanyLocation">Company Location</label>
                            <input type="text" id="editCompanyLocation" name="company_location">
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="editMaterials">Materials & Quantity</label>
                            <textarea id="editMaterials" name="materials_quantity" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action btn-save" onclick="saveEditPlatform()" id="saveEditBtn">
                💾 Save Changes
            </button>
            <button type="button" class="btn-action" onclick="closeEditModal()">
                ✖️ Cancel
            </button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let platforms = [];
    let filteredPlatforms = [];
    
    const searchInput = document.getElementById('searchInput');
    const refreshBtn = document.getElementById('refreshBtn');
    const tableBody = document.getElementById('platformsTableBody');
    const totalCountEl = document.getElementById('totalCount');
    const monthlyCountEl = document.getElementById('monthlyCount');
    const companyCountEl = document.getElementById('companyCount');
    
    // Load platform leads data
    async function loadPlatforms() {
        try {
            const response = await fetch('<?= $base ?>/api/platforms');
            const data = await response.json();
            
            if (data.success) {
                platforms = data.platforms || [];
                filteredPlatforms = [...platforms];
                updateSummaryCards();
                renderTable();
            } else {
                throw new Error(data.message || 'Failed to load platform leads');
            }
        } catch (error) {
            console.error('Error loading platforms:', error);
            showError('Failed to load platform leads');
        }
    }
    
    // Update summary cards
    function updateSummaryCards() {
        const total = platforms.length;
        const thisMonth = platforms.filter(p => {
            const date = new Date(p.created_at);
            const now = new Date();
            return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();
        }).length;
        const withCompany = platforms.filter(p => p.company_name && p.company_name.trim()).length;
        
        totalCountEl.textContent = total.toLocaleString();
        monthlyCountEl.textContent = thisMonth.toLocaleString();
        companyCountEl.textContent = withCompany.toLocaleString();
    }
    
    // Render table
    function renderTable() {
        if (filteredPlatforms.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <h3>No platform leads found</h3>
                            <p>Start by adding your first platform lead.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tableBody.innerHTML = filteredPlatforms.map(platform => `
            <tr onclick="viewPlatformDetails(${platform.id})" style="cursor: pointer;">
                <td title="${escapeHtml(platform.source || '')}">${escapeHtml(platform.source || 'N/A')}</td>
                <td title="${escapeHtml(platform.company_name || '')}">${escapeHtml(platform.company_name || 'N/A')}</td>
                <td title="${escapeHtml(platform.contact_person || '')}">${escapeHtml(platform.contact_person || 'N/A')}</td>
                <td title="${escapeHtml(platform.contact_number || '')}">${escapeHtml(platform.contact_number || 'N/A')}</td>
                <td title="${escapeHtml(platform.email_address || '')}">${escapeHtml(platform.email_address || 'N/A')}</td>
                <td title="${escapeHtml(platform.company_location || '')}">${escapeHtml(platform.company_location || 'N/A')}</td>
                <td class="col-date">${formatDate(platform.created_at)}</td>
            </tr>
        `).join('');
    }
    // Search functionality
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        if (query === '') {
            filteredPlatforms = [...platforms];
        } else {
            filteredPlatforms = platforms.filter(platform => {
                return (
                    (platform.source || '').toLowerCase().includes(query) ||
                    (platform.company_name || '').toLowerCase().includes(query) ||
                    (platform.contact_person || '').toLowerCase().includes(query) ||
                    (platform.contact_number || '').toLowerCase().includes(query) ||
                    (platform.email_address || '').toLowerCase().includes(query) ||
                    (platform.company_location || '').toLowerCase().includes(query)
                );
            });
        }
        
        renderTable();
    });
    
    // Refresh data
    refreshBtn.addEventListener('click', function() {
        refreshBtn.innerHTML = '⏳ Loading...';
        loadPlatforms().finally(() => {
            refreshBtn.innerHTML = '🔄 Refresh';
        });
    });
    
    // View platform details (enhanced with modal)
    window.viewPlatformDetails = async function(platformId) {
        const platform = platforms.find(p => p.id === platformId);
        if (!platform) return;
        
        // Populate modal with platform data
        document.getElementById('detailSource').textContent = platform.source || 'N/A';
        document.getElementById('detailContactPerson').textContent = platform.contact_person || 'N/A';
        document.getElementById('detailContactNumber').textContent = platform.contact_number || 'N/A';
        document.getElementById('detailEmailAddress').textContent = platform.email_address || 'N/A';
        document.getElementById('detailCompanyName').textContent = platform.company_name || 'N/A';
        document.getElementById('detailCompanyLocation').textContent = platform.company_location || 'N/A';
        
        // Handle materials section
        const materialsSection = document.getElementById('materialsSection');
        const materialsContent = document.getElementById('detailMaterials');
        if (platform.materials_quantity && platform.materials_quantity.trim()) {
            materialsSection.style.display = 'block';
            materialsContent.textContent = platform.materials_quantity;
        } else {
            materialsSection.style.display = 'none';
        }
        
        // Format dates
        document.getElementById('detailCreatedAt').textContent = formatDetailDate(platform.created_at);
        document.getElementById('detailUpdatedAt').textContent = formatDetailDate(platform.updated_at);
        
        // Store current platform ID for edit/archive operations
        window.currentPlatformId = platformId;
        
        // Load sales reps for dropdown
        try {
            const response = await fetch('<?= $base ?>/api/v1/users/sales-reps');
            const data = await response.json();
            const salesRepsData = data.data || data.users || data || [];
            const select = document.getElementById('platform-sales-rep-select');
            if (select) {
                select.innerHTML = '<option value="">Select SR...</option>';
                salesRepsData.forEach(sr => {
                    const option = document.createElement('option');
                    option.value = sr.id;
                    option.textContent = `${sr.full_name} - ${sr.branch || 'N/A'}`;
                    option.dataset.branch = sr.branch || '';
                    select.appendChild(option);
                });
                
                // Auto-fill branch on SR selection
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const branchInput = document.getElementById('platform-branch-input');
                    if (branchInput) {
                        branchInput.value = selectedOption.dataset.branch || '';
                    }
                });
            }
        } catch (error) {
            console.error('Error loading sales reps:', error);
        }
        
        // Load sales tracking data
        try {
            const response = await fetch(`<?= $base ?>/api/v1/platforms/tracking?platform_id=${platformId}`);
            const tracking = await response.json();
            
            // Reset all Yes/No buttons
            document.querySelectorAll('.yes-no-btn').forEach(btn => btn.classList.remove('active'));
            
            // Set Sales Rep and Branch
            if (tracking.sales_rep_id) {
                const select = document.getElementById('platform-sales-rep-select');
                if (select) {
                    select.value = tracking.sales_rep_id;
                    const selectedOption = select.options[select.selectedIndex];
                    const branchInput = document.getElementById('platform-branch-input');
                    if (branchInput) {
                        branchInput.value = selectedOption.dataset.branch || tracking.branch || '';
                    }
                }
            }
            
            // Set tracked values (convert to lowercase yes/no)
            if (tracking.contacted) {
                const value = tracking.contacted === true || tracking.contacted === 'Yes' || tracking.contacted === 'yes' ? 'yes' : 'no';
                document.querySelector(`.yes-no-btn[data-field="contacted"][data-value="${value}"]`)?.classList.add('active');
            }
            if (tracking.quoted) {
                const value = tracking.quoted === true || tracking.quoted === 'Yes' || tracking.quoted === 'yes' ? 'yes' : 'no';
                document.querySelector(`.yes-no-btn[data-field="quoted"][data-value="${value}"]`)?.classList.add('active');
            }
            if (tracking.sales_qualified) {
                const value = tracking.sales_qualified === true || tracking.sales_qualified === 'Yes' || tracking.sales_qualified === 'yes' ? 'yes' : 'no';
                document.querySelector(`.yes-no-btn[data-field="sales_qualified"][data-value="${value}"]`)?.classList.add('active');
            }
            if (tracking.to_win) {
                const value = tracking.to_win === true || tracking.to_win === 'Yes' || tracking.to_win === 'yes' ? 'yes' : 'no';
                document.querySelector(`.yes-no-btn[data-field="to_win"][data-value="${value}"]`)?.classList.add('active');
            }
            
            // Set WA Amount and Remarks
            const waInput = document.getElementById('platform-wl-amount-input');
            const remarksInput = document.getElementById('platform-remarks-textarea');
            if (waInput) waInput.value = tracking.wa_amount || '0.00';
            if (remarksInput) remarksInput.value = tracking.remarks || tracking.notes || '';
            
        } catch (error) {
            console.error('Error loading tracking data:', error);
        }
        
        // Show modal
        document.getElementById('platformDetailsModal').classList.add('active');
    };
    
    // Close platform details modal
    window.closePlatformModal = function() {
        document.getElementById('platformDetailsModal').classList.remove('active');
        window.currentPlatformId = null;
    };
    
    // Edit platform
    window.editPlatform = function() {
        if (!window.currentPlatformId) return;
        
        const platform = platforms.find(p => p.id === window.currentPlatformId);
        if (!platform) return;
        
        // Populate edit form
        document.getElementById('editSource').value = platform.source || '';
        document.getElementById('editContactPerson').value = platform.contact_person || '';
        document.getElementById('editContactNumber').value = platform.contact_number || '';
        document.getElementById('editEmailAddress').value = platform.email_address || '';
        document.getElementById('editCompanyName').value = platform.company_name || '';
        document.getElementById('editCompanyLocation').value = platform.company_location || '';
        document.getElementById('editMaterials').value = platform.materials_quantity || '';
        
        // Close details modal and show edit modal
        closePlatformModal();
        document.getElementById('editPlatformModal').classList.add('active');
    };
    
    // Close edit modal
    window.closeEditModal = function() {
        document.getElementById('editPlatformModal').classList.remove('active');
    };
    
    // Save platform edits
    window.saveEditPlatform = async function() {
        if (!window.currentPlatformId) return;
        
        const form = document.getElementById('editPlatformForm');
        const saveBtn = document.getElementById('saveEditBtn');
        
        // Validate required fields
        const requiredFields = ['editSource', 'editContactPerson', 'editContactNumber', 'editEmailAddress'];
        let isValid = true;
        
        for (const fieldId of requiredFields) {
            const field = document.getElementById(fieldId);
            if (!field.value.trim()) {
                field.style.borderColor = '#ef4444';
                isValid = false;
                setTimeout(() => {
                    field.style.borderColor = '';
                }, 3000);
            }
        }
        
        if (!isValid) {
            showErrorModal('Please fill in all required fields.');
            return;
        }
        
        // Disable save button
        saveBtn.disabled = true;
        saveBtn.innerHTML = '⏳ Saving...';
        
        try {
            const formData = new FormData(form);
            formData.append('platform_id', window.currentPlatformId);
            
            const response = await fetch('<?= $base ?>/api/v1/platforms/update', {
                method: 'POST',
                body: formData
            });
            
            let result;
            try {
                const responseText = await response.text();
                result = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error('Invalid response from server');
            }
            
            if (result.success) {
                showSuccessModal('Platform lead updated successfully!');
                closeEditModal();
                loadPlatforms(); // Refresh data
            } else {
                throw new Error(result.message || 'Failed to update platform lead');
            }
        } catch (error) {
            console.error('Error:', error);
            showErrorModal('Error updating platform lead: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '💾 Save Changes';
        }
    };
    
    // Save platform sales tracking
    window.savePlatformTracking = async function() {
        if (!window.currentPlatformId) return;
        
        // Get yes/no button values
        const getYesNoValue = (fieldName) => {
            const yesBtn = document.querySelector(`.yes-no-btn[data-field="${fieldName}"][data-value="yes"]`);
            const noBtn = document.querySelector(`.yes-no-btn[data-field="${fieldName}"][data-value="no"]`);
            
            if (yesBtn?.classList.contains('active')) return true;
            if (noBtn?.classList.contains('active')) return false;
            return null;
        };
        
        const trackingData = {
            platform_id: window.currentPlatformId,
            contacted: getYesNoValue('contacted'),
            quoted: getYesNoValue('quoted'),
            sales_qualified: getYesNoValue('sales_qualified'),
            to_win: getYesNoValue('to_win'),
            wa_amount: document.getElementById('platform-wl-amount-input')?.value || null,
            remarks: document.getElementById('platform-remarks-textarea')?.value || null,
            sales_rep_id: document.getElementById('platform-sales-rep-select')?.value || null,
            branch: document.getElementById('platform-branch-input')?.value || null
        };
        
        if (trackingData.wa_amount) {
            trackingData.wa_amount = parseFloat(trackingData.wa_amount);
        }
        if (trackingData.sales_rep_id) {
            trackingData.sales_rep_id = parseInt(trackingData.sales_rep_id);
        }
        
        const saveBtn = document.getElementById('savePlatformTrackingBtn');
        const originalText = saveBtn?.textContent;
        if (saveBtn) {
            saveBtn.textContent = '💾 Saving...';
            saveBtn.disabled = true;
        }
        
        try {
            const response = await fetch('<?= $base ?>/api/v1/platforms/tracking', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(trackingData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (typeof ModalSystem !== 'undefined') {
                    ModalSystem.success('Sales tracking saved successfully!');
                } else {
                    showSuccessModal('Sales tracking saved successfully!');
                }
                loadPlatforms();
            } else {
                throw new Error(result.message || 'Failed to save tracking');
            }
        } catch (error) {
            console.error('Error:', error);
            if (typeof ModalSystem !== 'undefined') {
                ModalSystem.error('Error saving sales tracking: ' + error.message);
            } else {
                showErrorModal('Error saving sales tracking: ' + error.message);
            }
        } finally {
            if (saveBtn) {
                saveBtn.textContent = originalText || '💾 Save Sales Tracking';
                saveBtn.disabled = false;
            }
        }
    };
    
    // Handle yes/no button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('yes-no-btn')) {
            const field = e.target.dataset.field;
            const value = e.target.dataset.value;
            
            // Remove active from siblings
            const siblings = e.target.parentElement.querySelectorAll('.yes-no-btn');
            siblings.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active to clicked button
            e.target.classList.add('active');
        }
    });
    
    // Archive platform
    window.archivePlatform = async function() {
        if (!window.currentPlatformId) return;
        
        const platform = platforms.find(p => p.id === window.currentPlatformId);
        if (!platform) return;
        
        // Show confirmation modal instead of confirm dialog
        showConfirmModal(
            `Are you sure you want to archive this platform lead?`,
            `Company: ${platform.company_name || 'N/A'}\nContact: ${platform.contact_person}`,
            async function() {
                const archiveBtn = document.getElementById('archiveBtn');
                archiveBtn.disabled = true;
                archiveBtn.innerHTML = '⏳ Archiving...';
                
                try {
                    const response = await fetch('<?= $base ?>/api/v1/platforms/archive', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            platform_id: window.currentPlatformId
                        })
                    });
                    
                    let result;
                    try {
                        const responseText = await response.text();
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error('Invalid response from server');
                    }
                    
                    if (result.success) {
                        showSuccessModal('Platform lead archived successfully!');
                        closePlatformModal();
                        loadPlatforms(); // Refresh data
                    } else {
                        throw new Error(result.message || 'Failed to archive platform lead');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showErrorModal('Error archiving platform lead: ' + error.message);
                } finally {
                    archiveBtn.disabled = false;
                    archiveBtn.innerHTML = '🗃️ Archive';
                }
            }
        );
    };
    
    // Modal functions
    window.showErrorModal = function(message) {
        showModal('Error', message, 'error');
    };
    
    window.showSuccessModal = function(message) {
        showModal('Success', message, 'success');
    };
    
    window.showConfirmModal = function(title, message, onConfirm) {
        // Create confirm modal dynamically if needed
        showModal(title, message, 'confirm', onConfirm);
    };
    
    // Generic modal function
    function showModal(title, message, type = 'info', onConfirm = null) {
        // Remove existing notification modal if any
        const existingModal = document.getElementById('notificationModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create modal HTML
        const modalHtml = `
            <div class="modal-overlay active" id="notificationModal">
                <div class="modal-content modal-small">
                    <div class="modal-header">
                        <h2>${escapeHtml(title)}</h2>
                        <button class="modal-close" onclick="closeNotificationModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="white-space: pre-wrap;">${escapeHtml(message)}</p>
                    </div>
                    <div class="modal-actions">
                        ${type === 'confirm' ? 
                            `<button type="button" class="btn-action btn-save" onclick="confirmAction()">Yes</button>
                             <button type="button" class="btn-action" onclick="closeNotificationModal()">Cancel</button>` :
                            `<button type="button" class="btn-action btn-save" onclick="closeNotificationModal()">OK</button>`
                        }
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Store confirm callback if needed
        if (type === 'confirm' && onConfirm) {
            window.currentConfirmCallback = onConfirm;
        }
    }
    
    // Close notification modal
    window.closeNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 300);
        }
        window.currentConfirmCallback = null;
    };
    
    // Confirm action
    window.confirmAction = function() {
        if (window.currentConfirmCallback) {
            window.currentConfirmCallback();
        }
        closeNotificationModal();
    };
    
    // Format date for details view
    function formatDetailDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'Invalid date';
        }
    }
    
    // Close modals on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePlatformModal();
            closeEditModal();
        }
    });
    
    // Close modals on overlay click
    document.getElementById('platformDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePlatformModal();
        }
    });
    
    document.getElementById('editPlatformModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
    
    // Utility functions
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }) + '\n' + date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'Invalid date';
        }
    }
    
    function showError(message) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-icon">⚠️</div>
                        <h3>Error</h3>
                        <p>${escapeHtml(message)}</p>
                    </div>
                </td>
            </tr>
        `;
    }
    
    // Initial load
    loadPlatforms();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay[id], .detail-modal-overlay[id]').forEach(function(el) {
        if (el.parentNode !== document.body) document.body.appendChild(el);
    });
});
</script></body>
</html>