<?php
/* ============================================================
   pages/projects-management.php — Project Management
   ============================================================
   Shows 4 tabs: Unassigned, Assigned, Unprocessed, Processed
   Admin and Superadmin only.
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
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? 'User');

$currentView = $_GET['view'] ?? 'unassigned';

// Access control based on role and view
if ($role === 'sales_rep') {
    // Sales reps can only view unassigned projects
    if ($currentView !== 'unassigned') {
        header('Location: ' . $base . '/projects-management?view=unassigned');
        exit;
    }
} elseif ($role !== 'admin' && $role !== 'superadmin') {
    // Other roles (encoder, etc.) cannot access this page
    header('Location: ' . $base . '/');
    exit;
}

// Only admins and superadmins can view archived projects
if ($currentView === 'archived' && !in_array($role, ['admin', 'superadmin'])) {
    header('Location: ' . $base . '/projects-management?view=unassigned');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/header.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modal-system.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">
    
    <style>
        /* ── Project Details Modal Styling (matching projects.php) ── */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100000 !important;
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.2s ease;
        }
        
        /* Tracking Status Badge Styles */
        .tracking-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            white-space: nowrap;
        }
        
        .tracking-not-started {
            background: rgba(156, 163, 175, 0.1);
            color: #9ca3af;
            border: 1px solid rgba(156, 163, 175, 0.2);
        }
        
        .tracking-in-progress {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(251, 191, 36, 0.2);
        }
        
        .tracking-complete {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .tracking-on-hold {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        /* Center modal in main content area (accounting for sidebar) */
        @media (min-width: 769px) {
            .modal-overlay {
                left: 240px; /* Sidebar width */
            }
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideInUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .modal-content.modal-large {
            max-width: 900px;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            color: var(--orange-500);
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
        
        .detail-value.large {
            font-size: 1.25rem;
            color: #34d399;
        }
        
        .modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
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
        
        .btn-primary {
            background: var(--orange-500);
            border-color: var(--orange-500);
            color: #000;
        }
        
        .btn-primary:hover {
            background: var(--orange-600);
            box-shadow: 0 4px 16px rgba(255, 128, 0, 0.4);
        }
        
        .btn-secondary {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .btn-delete {
            border-color: rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
        }
        
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: var(--font);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: var(--orange-500);
            box-shadow: 0 0 0 2px rgba(255, 128, 0, 0.15);
        }
        
        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23B0BEC5' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
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
        
        /* ── Stats Cards ── */
        .pm-stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .pm-stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .pm-stat-content {
            flex: 1;
        }
        
        .pm-stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .pm-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ── Filters ── */
        .pm-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .pm-search {
            flex: 1;
            min-width: 250px;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .pm-search:focus {
            border-color: var(--orange-500);
            box-shadow: 0 0 0 2px rgba(255, 128, 0, 0.15);
        }
        
        .pm-filter {
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23B0BEC5' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
        }
        
        .pm-filter:focus {
            border-color: var(--orange-500);
        }
        
        /* ── Sales Rep Selection Dropdown Styling ── */
        #bulkSalesRepSelect:hover {
            border-color: rgba(59, 130, 246, 0.6);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        #bulkSalesRepSelect:focus {
            border-color: rgba(59, 130, 246, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        #bulkSalesRepSelect option {
            background: #0f172a;
            color: var(--text-primary);
            padding: 0.75rem;
        }
        
        #bulkSalesRepSelect option:hover {
            background: rgba(59, 130, 246, 0.2);
        }
        
        #proceedBtn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        #proceedBtn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 128, 0, 0.4);
        }
        
        #proceedBtn:not(:disabled):active {
            transform: translateY(0);
        }
        
        /* ── Bulk Action Inline Buttons ── */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* ── Bulk Action Button States ── */
        .bulk-action-btn {
            background: var(--orange-500) !important;
            border: 1px solid var(--orange-500) !important;
            border-radius: 0.75rem !important;
            color: #000 !important;
            padding: 0.875rem 1.5rem !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            box-shadow: 0 4px 16px rgba(255, 128, 0, 0.4) !important;
            transition: all 0.2s ease !important;
            white-space: nowrap !important;
            font-family: var(--font) !important;
        }
        
        .bulk-action-btn.enabled {
            opacity: 1 !important;
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        
        .bulk-action-btn.disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            transform: none !important;
            box-shadow: 0 2px 8px rgba(255, 128, 0, 0.2) !important;
        }
        
        .bulk-action-btn.enabled:hover {
            background: var(--orange-600) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 24px rgba(255, 128, 0, 0.5) !important;
        }
        
        .bulk-action-btn.danger {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
            color: #fff !important;
            box-shadow: 0 4px 16px rgba(220, 38, 38, 0.4) !important;
        }
        
        .bulk-action-btn.danger.enabled:hover {
            background: #b91c1c !important;
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.5) !important;
        }
        
        .bulk-cancel-btn {
            background: rgba(107, 114, 128, 0.2) !important;
            border: 1px solid rgba(107, 114, 128, 0.4) !important;
            border-radius: 0.75rem !important;
            color: var(--text-primary) !important;
            padding: 0.875rem 1.5rem !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            white-space: nowrap !important;
            font-family: var(--font) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .bulk-cancel-btn:hover {
            background: rgba(107, 114, 128, 0.3) !important;
            border-color: rgba(107, 114, 128, 0.6) !important;
            transform: translateY(-1px) !important;
        }

        /* Responsive adjustments for inline buttons */
        @media (max-width: 768px) {
            #bulkAssignButtonBar,
            #bulkUnassignButtonBar {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 1rem !important;
            }
            
            #bulkActionButtons {
                margin-left: 0 !important;
                flex-direction: column !important;
                gap: 0.75rem !important;
                width: 100% !important;
            }
            
            .bulk-action-btn,
            .bulk-cancel-btn {
                width: 100% !important;
                justify-content: center !important;
            }
        }

        /* ── Sales Rep Cards ── */
        .sr-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .sr-card.suggested {
            border-color: #fbbf24;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
        }
        
        .sr-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--orange-500), rgba(255, 152, 0, 0.5));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .sr-card.suggested::before {
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            transform: scaleX(1);
        }
        
        .sr-card:hover {
            border-color: var(--orange-500);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(255, 128, 0, 0.2);
        }
        
        .sr-card.suggested:hover {
            border-color: #f59e0b;
            box-shadow: 0 8px 24px rgba(251, 191, 36, 0.3);
        }
        
        .sr-card:hover::before {
            transform: scaleX(1);
        }
        
        .sr-card-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--orange-500), rgba(255, 152, 0, 0.7));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(255, 128, 0, 0.3);
        }
        
        .sr-card-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .sr-card-branch {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        
        .sr-card-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .sr-card-status.online {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .sr-card-status.offline {
            background: rgba(107, 114, 128, 0.15);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
        
        .sr-card-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        #salesRepsGrid::-webkit-scrollbar {
            width: 8px;
        }
        
        #salesRepsGrid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        
        #salesRepsGrid::-webkit-scrollbar-thumb {
            background: rgba(255, 128, 0, 0.3);
            border-radius: 4px;
        }
        
        #salesRepsGrid::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 128, 0, 0.5);
        }
        
        /* ── Project Suggestions Styling ── */
        .suggestion-badge {
            position: absolute !important;
            top: -8px !important;
            right: -8px !important;
            background: linear-gradient(135deg, #fbbf24, #f59e0b) !important;
            color: #000 !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            padding: 0.25rem 0.5rem !important;
            border-radius: 999px !important;
            box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4) !important;
            z-index: 10 !important;
        }
        
        .project-suggested {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.15), rgba(245, 158, 11, 0.1)) !important;
            border: 2px solid rgba(251, 191, 36, 0.3) !important;
            border-radius: 0.5rem !important;
        }
        
        .project-suggested:hover {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.15)) !important;
            border-color: rgba(251, 191, 36, 0.5) !important;
        }
        
        /* ── Yes/No Buttons ── */
        .yes-no-buttons {
            display: flex;
            gap: 0.75rem;
        }
        
        .yes-no-btn {
            flex: 1;
            padding: 0.875rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: var(--font);
        }
        
        .yes-no-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .yes-no-btn.active {
            background: linear-gradient(135deg, var(--orange-500), rgba(255, 152, 0, 0.8));
            border-color: var(--orange-500);
            color: #000;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(255, 128, 0, 0.3);
        }
        
        .yes-no-btn.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 128, 0, 0.4);
        }

        .yes-no-btn.active.yes {
            background: rgba(16, 185, 129, 0.9) !important;
            border-color: rgba(16, 185, 129, 1) !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }
        
        .yes-no-btn.active.no {
            background: rgba(239, 68, 68, 0.9) !important;
            border-color: rgba(239, 68, 68, 1) !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }

        /* ── Sales Tracking Form (Exact copy from projects.php) ── */
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
            grid-template-rows: repeat(4, auto);
            grid-auto-flow: column;
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

        /* ── Modern Notification System ── */
        .modern-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            transform: translateX(100%);
            transition: all 0.3s ease;
            opacity: 0;
        }

        .modern-notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .modern-notification.success {
            border-left: 4px solid #10b981;
        }

        .modern-notification.warning {
            border-left: 4px solid #f59e0b;
        }

        .modern-notification.error {
            border-left: 4px solid #ef4444;
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
        }

        .notification-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .notification-message {
            flex: 1;
            font-size: 0.9rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .notification-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-secondary);
            cursor: pointer;
            flex-shrink: 0;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .notification-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        /* ── Role-based Visibility ── */
        [data-role-access]:not([data-role-access*="superadmin"]):not([data-role-access*="admin"]):not([data-role-access*="sales_rep"]) {
            display: none !important;
        }
        
        body[data-role="encoder"] [data-role-access]:not([data-role-access*="encoder"]) {
            display: none !important;
        }
        
        body[data-role="sales_rep"] [data-role-access]:not([data-role-access*="sales_rep"]) {
            display: none !important;
        }
        
        /* ── Status Circle ── */
        .status-circle {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid;
        }

        .status-circle.priority {
            background: #ef4444;
            border-color: #fca5a5;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);
        }

        .status-circle.awarded {
            background: #10b981;
            border-color: #6ee7b7;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
        }

        .status-circle.for-execution {
            background: #3b82f6;
            border-color: #93c5fd;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.6);
        }

        .status-circle.for-bidding {
            background: #f59e0b;
            border-color: #fcd34d;
            box-shadow: 0 0 8px rgba(251, 191, 36, 0.6);
        }

        /* ── Status Legend ── */
        .status-legend {
            display: flex;
            gap: 1.5rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .status-legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .status-legend-item .status-circle {
            flex-shrink: 0;
        }
    </style>
</head>

<body data-role="<?= $role ?>" data-user-id="<?= (int)($_SESSION['user']['id'] ?? 0) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard" style="display: block; max-width: 100%; padding: var(--sp-4); box-sizing: border-box;">
    <div class="card animate-fadeInUp" style="max-width: 100%; margin: 0 auto;">
        <div style="margin-bottom: var(--sp-5);">
            <h2 style="font-size: var(--text-2xl); font-weight: 800; margin: 0; color: var(--text-primary);">
                <span style="margin-right: 0.5rem;">📊</span>Project Management
            </h2>
            <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: var(--text-sm);">
                <?php
                $viewTitles = [
                    'unassigned' => 'Projects without assigned sales representative',
                    'assigned' => 'Projects with assigned sales representative',
                    'unprocessed' => 'Projects without sales tracking',
                    'processed' => 'Projects with sales tracking',
                    'archived' => 'Archived projects (admin/superadmin only)'
                ];
                echo $viewTitles[$currentView] ?? 'Manage project assignments and sales tracking';
                ?>
            </p>
        </div>



        <!-- Filters -->
        <div class="pm-filters">
            <input type="text" id="searchInput" placeholder="Search projects..." class="pm-search">
            <select id="regionFilter" class="pm-filter">
                <option value="">All Regions</option>
                <option value="NCR">NCR</option>
                <option value="Region I">Region I</option>
                <option value="Region II">Region II</option>
                <option value="Region III">Region III</option>
                <option value="Region IV-A">Region IV-A</option>
                <option value="Region IV-B">Region IV-B</option>
                <option value="Region V">Region V</option>
                <option value="Region VI">Region VI</option>
                <option value="Region VII">Region VII</option>
                <option value="Region XI">Region XI</option>
            </select>
            <select id="statusFilter" class="pm-filter">
                <option value="">All Status</option>
                <option value="Prospect">Prospect</option>
                <option value="Contacted">Contacted</option>
                <option value="Sales Qualified">Sales Qualified</option>
                <option value="Not Sales Qualified">Not Sales Qualified</option>
                <option value="Quoted">Quoted</option>
                <option value="Awarded">Awarded</option>
                <option value="For Execution">For Execution</option>
                <option value="Priority">Priority</option>
            </select>
            <select id="sourceFilter" class="pm-filter">
                <option value="">All Sources</option>
                <option value="DPWH">DPWH</option>
                <option value="Private">Private</option>
                <option value="BCI">BCI</option>
                <option value="PHILGEPS">PHILGEPS</option>
                <option value="Other">Other</option>
            </select>
            <select id="sortFilter" class="pm-filter">
                <option value="desc">📅 Newest First</option>
                <option value="asc">📅 Oldest First</option>
            </select>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" style="display: none; margin-bottom: 1rem; padding: 1rem; background: rgba(255, 128, 0, 0.1); border: 1px solid rgba(255, 128, 0, 0.3); border-radius: var(--radius-md);">
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <span id="selectedCount" style="color: var(--text-primary); font-weight: 600;">0 selected</span>
                <button class="btn-secondary" onclick="clearSelection()" style="padding: 0.5rem 1rem;">
                    Clear Selection
                </button>
            </div>
        </div>

        <!-- Bulk Assignment Button (shown in unassigned view) -->
        <div id="bulkAssignButtonBar" style="margin-bottom: 1rem; display: <?= $currentView === 'unassigned' && ($role === 'admin' || $role === 'superadmin') ? 'flex' : 'none' ?>; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: space-between;">
            <button class="btn-primary" onclick="openSalesRepModal()" style="padding: 0.875rem 1.75rem; display: inline-flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; font-weight: 700; border-radius: 0.75rem;">
                <span style="font-size: 1.25rem;">👥</span>
                <span>Bulk Assign Projects</span>
            </button>
        </div>

        <!-- Bulk Unassign Button (shown in assigned view) -->
        <div id="bulkUnassignButtonBar" style="margin-bottom: 1rem; display: <?= $currentView === 'assigned' && ($role === 'admin' || $role === 'superadmin') ? 'flex' : 'none' ?>; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <button class="btn-secondary" onclick="startBulkUnassign()" style="padding: 0.875rem 1.75rem; display: inline-flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; font-weight: 700; border-radius: 0.75rem; background: #dc2626; border-color: #dc2626;">
                <span style="font-size: 1.25rem;">❌</span>
                <span>Bulk Unassign Projects</span>
            </button>
        </div>
        <!-- Content Area -->
        <div id="pm-content">
            <!-- Status Legend -->
            <div class="status-legend">
                <div class="status-legend-item">
                    <span class="status-circle priority"></span>
                    <span>Priority</span>
                </div>
                <div class="status-legend-item">
                    <span class="status-circle awarded"></span>
                    <span>Awarded</span>
                </div>
                <div class="status-legend-item">
                    <span class="status-circle for-execution"></span>
                    <span>For Execution</span>
                </div>
                <div class="status-legend-item">
                    <span class="status-circle for-bidding"></span>
                    <span>For Bidding</span>
                </div>
            </div>
            
            <div class="table-wrapper" style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; min-width: 800px;">
                    <thead id="pm-table-head">
                        <!-- Dynamic headers -->
                    </thead>
                    <tbody id="pm-table-body">
                        <tr><td colspan="12" style="text-align:center;padding:2rem;color:var(--text-dim);">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="pm-pagination" class="pagination-controls"></div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Assign Project to Sales Rep</h2>
            <button class="modal-close" onclick="closeAssignModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin: 0 0 1rem; color: var(--text-secondary);">
                Project: <strong id="assign-project-name">—</strong>
            </p>
            <div class="form-group">
                <label>Select Sales Representative</label>
                <select id="salesRepSelect" class="form-control">
                    <option value="">Loading...</option>
                </select>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-secondary" onclick="closeAssignModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="confirmAssign()">Assign</button>
        </div>
    </div>
</div>

<!-- Sales Tracking Modal -->
<div class="modal-overlay" id="trackingModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>📊 Sales Tracking</h2>
            <button class="modal-close" onclick="closeTrackingModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="margin: 0 0 1.5rem; color: var(--text-secondary);">
                Project: <strong id="tracking-project-name">—</strong>
            </p>
            
            <!-- Sales Representative Information Section -->
            <div class="detail-section">
                <div class="detail-section-title">👤 Sales Representative Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Sales Representative</div>
                        <div class="detail-value" id="sales_rep_name_display">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Branch</div>
                        <div class="detail-value" id="branch_display">—</div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Tracking Questions Section -->
            <div class="detail-section">
                <div class="detail-section-title">📋 Sales Tracking Questions</div>
                <div class="detail-grid">
                    <!-- Contacted -->
                    <div class="detail-item">
                        <div class="detail-label">Contacted?</div>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="contacted" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="contacted" data-value="no">No</button>
                        </div>
                        <input type="hidden" id="contacted" name="contacted">
                    </div>

                    <!-- Quoted -->
                    <div class="detail-item">
                        <div class="detail-label">Quoted?</div>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="quoted" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="quoted" data-value="no">No</button>
                        </div>
                        <input type="hidden" id="quoted" name="quoted">
                    </div>

                    <!-- Sales Qualified Lead -->
                    <div class="detail-item">
                        <div class="detail-label">Sales Qualified Lead?</div>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="sales_qualified" data-value="no">No</button>
                        </div>
                        <input type="hidden" id="sales_qualified" name="sales_qualified">
                    </div>

                    <!-- To Win -->
                    <div class="detail-item">
                        <div class="detail-label">To Win?</div>
                        <div class="yes-no-buttons">
                            <button type="button" class="yes-no-btn" data-field="to_win" data-value="yes">Yes</button>
                            <button type="button" class="yes-no-btn" data-field="to_win" data-value="no">No</button>
                        </div>
                        <input type="hidden" id="to_win" name="to_win">
                    </div>
                </div>
            </div>
            
            <!-- Financial Information Section -->
            <div class="detail-section">
                <div class="detail-section-title">💰 Financial Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">WA Amount (₱)</div>
                        <input type="number" id="wa_amount" class="form-control" step="0.01" min="0" placeholder="0.00" style="margin-top: 0.5rem;">
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Remarks</div>
                        <textarea id="remarks" class="form-control" rows="3" placeholder="Enter remarks..." style="margin-top: 0.5rem;"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action btn-secondary" onclick="closeTrackingModal()">Cancel</button>
            <button type="button" class="btn-action btn-primary" onclick="saveTracking()">Save Tracking</button>
        </div>
    </div>
</div>

<!-- Project Details Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📋 Project Details</h2>
            <button class="modal-close" onclick="closeDetailsModal()">×</button>
        </div>
        <div class="modal-body" id="detailsModalBody">
            <!-- Content will be inserted here -->
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-secondary" onclick="closeDetailsModal()">Close</button>
            
            <!-- Archive/Restore Button for Admins and Superadmins -->
            <button type="button" class="btn-delete" 
                    id="archiveBtn" 
                    data-role-access="admin,superadmin"
                    style="display: none;">
                🗄️ Archive Project
            </button>
            
            <button type="button" class="btn-primary" 
                    onclick="saveSalesTracking()" 
                    id="saveTrackingBtn"
                    data-role-access="superadmin,admin,sales_rep">💾 Save Sales Tracking</button>
        </div>
    </div>
</div>

<!-- Sales Rep Selection Modal -->
<div class="modal-overlay" id="salesRepModal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2>👥 Select Sales Representative</h2>
            <button class="modal-close" onclick="closeSalesRepModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="margin: 0 0 1.5rem; color: var(--text-secondary); font-size: 0.95rem;">
                <strong>Step 1:</strong> Select a sales representative below. The system will then show you suggested projects based on their branch location.
            </p>
            
            <!-- Search box for filtering SRs -->
            <div style="margin-bottom: 1.5rem;">
                <input type="text" id="srSearchInput" placeholder="Search by name or branch..." style="width: 100%; padding: 0.75rem 1rem; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; color: var(--text-primary); font-size: 0.9rem; outline: none;">
            </div>
            
            <!-- Sales Reps Grid -->
            <div id="salesRepsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; max-height: 500px; overflow-y: auto; padding: 0.5rem;">
                <!-- SR cards will be inserted here -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏳</div>
                    <p>Loading sales representatives...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>const BASE = '<?= $base ?>';</script>
<script>
// Move all modals to document.body to escape ap-shell stacking context
document.addEventListener('DOMContentLoaded', function() {
    const modalIds = ['assignModal','trackingModal','detailsModal','salesRepModal'];
    modalIds.forEach(function(id) {
        const el = document.getElementById(id);
        if (el && el.parentNode !== document.body) {
            document.body.appendChild(el);
        }
    });
});
</script>
<script src="<?= $base ?>/static/js/modal-system.js?v=1"></script>
<script src="<?= $base ?>/static/js/utils.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/projects-management-clean.js?v=11"></script>

</body>
</html>

<style>
/* Modern Notification System */
.modern-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 300px;
    max-width: 500px;
    background: var(--bg-secondary);
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 10000;
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modern-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.modern-notification.success {
    border-left: 4px solid var(--green-500);
}

.modern-notification.error {
    border-left: 4px solid var(--red-500);
}

.modern-notification.warning {
    border-left: 4px solid var(--orange-500);
}

.modern-notification.info {
    border-left: 4px solid var(--blue-500);
}

.notification-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
}

.notification-icon {
    font-size: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}

.notification-message {
    flex: 1;
    color: var(--text-primary);
    font-size: 14px;
    line-height: 1.4;
    white-space: pre-line;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    flex-shrink: 0;
}

.notification-close:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

/* Tracking badge styles */
.tracking-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.tracking-badge.status-not-started {
    background-color: rgba(107, 114, 128, 0.1);
    color: rgb(107, 114, 128);
}

.tracking-badge.status-in-progress {
    background-color: rgba(245, 158, 11, 0.1);
    color: rgb(245, 158, 11);
}

.tracking-badge.status-complete {
    background-color: rgba(34, 197, 94, 0.1);
    color: rgb(34, 197, 94);
}
</style>