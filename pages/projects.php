<?php
/* ============================================================
   pages/projects.php — Projects Table View
   ============================================================
   Displays all encoded projects in a table format.
   Supports filtering by type: non-priority or priority
   Accessible by all authenticated users.
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

// Get type parameter (non-priority or priority)
$type = $_GET['type'] ?? 'all';
$isPriority = ($type === 'priority');
$isNonPriority = ($type === 'non-priority');

$pageTitle = $isPriority ? 'Priority Projects' : ($isNonPriority ? 'Non-Priority Projects' : 'All Projects');
$pageIcon = $isPriority ? '⭐' : ($isNonPriority ? '📋' : '📁');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | TDT Powersteel SILEP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Base styles -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/tables.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/roles.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=24">
    <link rel="stylesheet" href="<?= $base ?>/static/css/credits-modal.css?v=3">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg-dark);
            background-image:
                radial-gradient(circle at 15% 25%, rgba(255,152,0,0.05) 0%, transparent 50%),
                radial-gradient(circle at 85% 75%, rgba(139,92,246,0.05) 0%, transparent 50%);
            min-height: 100vh;
            font-family: var(--font);
            color: var(--text-primary);
            overflow-x: hidden;
        }
        
        html {
            overflow-x: hidden;
        }

        /* ── Projects Page Layout ── */
        .projects-container {
            width: 100%;
            max-width: 100%;
            padding: 1.5rem 1.5rem;
            box-sizing: border-box;
            overflow-x: hidden;
        }

        /* ── Summary Cards ── */
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
            background: linear-gradient(90deg, var(--orange-500), rgba(255, 152, 0, 0.5));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            border-color: rgba(255, 152, 0, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 128, 0, 0.15);
        }

        .summary-card:hover::before {
            transform: scaleX(1);
        }

        .summary-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.15), rgba(255, 152, 0, 0.05));
            border: 1px solid rgba(255, 152, 0, 0.2);
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

        /* ── Search & Filter Bar ── */
        .projects-toolbar {
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
            border-color: var(--orange-500);
            box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.15);
        }

        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
        }

        .filter-select {
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            background: #0f172a;
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

        .filter-select:focus {
            border-color: var(--orange-500);
        }

        .btn-refresh {
            padding: 0.75rem 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-refresh:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* ── Projects Table Card ── */
        .projects-card {
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
            background: rgba(255, 128, 0, 0.3);
            border-radius: 4px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 128, 0, 0.5);
        }

        .projects-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .projects-table thead {
            background: rgba(255, 255, 255, 0.03);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .projects-table th {
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
        
        .projects-table th:nth-child(1) { width: 16%; } /* Contractor */
        .projects-table th:nth-child(2) { width: 20%; } /* Project Name */
        .projects-table th:nth-child(3) { width: 10%; } /* Region */
        .projects-table th:nth-child(4) { width: 8%; } /* Source */
        .projects-table th:nth-child(5) { width: 10%; } /* Status */
        .projects-table th:nth-child(6) { width: 11%; } /* Value */
        .projects-table th:nth-child(7) { width: 13%; } /* Sales Tracking */
        .projects-table th:nth-child(8) { width: 12%; } /* Date & Time */

        .projects-table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.2s;
            cursor: pointer;
        }

        .projects-table tbody tr:hover {
            background: rgba(255, 128, 0, 0.08);
        }
        
        .projects-table tbody tr:active {
            background: rgba(255, 128, 0, 0.12);
        }

        .projects-table td {
            padding: 1rem;
            font-size: 0.875rem;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .projects-table td:nth-child(1) { width: 16%; } /* Contractor */
        .projects-table td:nth-child(2) { width: 20%; } /* Project Name */
        .projects-table td:nth-child(3) { width: 10%; } /* Region */
        .projects-table td:nth-child(4) { width: 8%; } /* Source */
        .projects-table td:nth-child(5) { width: 10%; } /* Status */
        .projects-table td:nth-child(6) { width: 11%; } /* Value */
        .projects-table td:nth-child(7) { width: 13%; } /* Sales Tracking */
        .projects-table td:nth-child(8) { width: 12%; } /* Date & Time */

        .projects-table td.col-value {
            text-align: right;
            font-weight: 600;
            color: #34d399;
        }

        .projects-table td.col-tracking {
            text-align: center;
        }

        .projects-table td.col-date {
            text-align: right;
            color: var(--text-secondary);
            font-size: 0.8rem;
            white-space: normal;
            line-height: 1.4;
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
        
        .yes-no-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .yes-no-btn {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .yes-no-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .yes-no-btn.active {
            background: var(--orange-500) !important;
            border-color: var(--orange-500) !important;
            color: #000 !important;
            font-weight: 700;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(255, 128, 0, 0.3);
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
        
        /* ── Project Details Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
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
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideInUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
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
        
        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        .modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
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
            background: var(--orange-500);
            border-color: var(--orange-500);
            color: #000;
        }
        
        .btn-save:hover {
            background: var(--orange-600);
            box-shadow: 0 4px 16px rgba(255, 128, 0, 0.4);
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
        
        .btn-warning {
            border-color: rgba(251, 191, 36, 0.3);
            background: rgba(251, 191, 36, 0.1);
            color: #fbbf24;
        }
        
        .btn-warning:hover {
            background: rgba(251, 191, 36, 0.2);
            border-color: rgba(251, 191, 36, 0.5);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
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

        /* ── Sales Tracking Status Badges ── */
        .tracking-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .tracking-badge.not-started {
            background: rgba(107, 114, 128, 0.15);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        .tracking-badge.in-progress {
            background: rgba(251, 191, 36, 0.15);
            color: #fcd34d;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .tracking-badge.complete {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* ── Empty State ── */
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

        /* ── Loading State ── */
        .loading-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 128, 0, 0.2);
            border-top-color: var(--orange-500);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ── Pagination ── */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pagination-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background: var(--orange-500);
            border-color: var(--orange-500);
            color: #000;
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

        .modern-notification.info {
            border-left: 4px solid var(--orange-500);
        }

        .notification-content {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
        }

        .notification-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .notification-message {
            flex: 1;
            color: var(--text-primary);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .notification-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.25rem;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .notification-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        /* ── Sales Tracking Form Enhancements ── */
        .yes-no-btn.disabled {
            opacity: 0.4 !important;
            cursor: not-allowed !important;
            pointer-events: none;
        }

        .sales-form-input:disabled,
        .sales-form-textarea:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: rgba(15, 23, 42, 0.4);
        }

        @media (max-width: 1200px) {
            .projects-container {
                padding: 1.5rem 1rem;
            }
            
            .projects-table {
                min-width: 800px;
            }
        }

        @media (max-width: 768px) {
            .projects-container {
                padding: 1rem 0.75rem;
            }

            .projects-toolbar {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
            }

            .search-box {
                min-width: 100%;
            }

            .table-wrapper {
                max-height: calc(100vh - 400px);
            }
            
            .projects-table {
                min-width: 700px;
                font-size: 0.8rem;
            }
            
            .projects-table th,
            .projects-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<main class="projects-container">
    <!-- Summary Cards -->
    <?php if ($role === 'sales_rep'): ?>
    <!-- Sales Rep Dashboard -->
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--orange-500); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>👤</span>My Projects
        </h2>
        <div class="summary-cards" id="mySummaryCards">
            <div class="summary-card">
                <div class="summary-card-icon">📋</div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Assigned to Me</div>
                    <div class="summary-card-value" id="myTotalProjects">—</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon">🏗️</div>
                <div class="summary-card-content">
                    <div class="summary-card-label">My Contractors</div>
                    <div class="summary-card-value" id="myTotalContractors">—</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon">💵</div>
                <div class="summary-card-content">
                    <div class="summary-card-label">My Pipeline Value</div>
                    <div class="summary-card-value" id="myPipelineValue">—</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon">📄</div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Non-Priority</div>
                    <div class="summary-card-value" id="myNonPriorityProjects">—</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon">⭐</div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Priority</div>
                    <div class="summary-card-value" id="myPriorityProjects">—</div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Admin/Other Roles Dashboard -->
    <div class="summary-cards" id="summaryCards">
        <div class="summary-card">
            <div class="summary-card-icon">📊</div>
            <div class="summary-card-content">
                <div class="summary-card-label">Total Projects</div>
                <div class="summary-card-value" id="totalProjects">—</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-card-icon">🏢</div>
            <div class="summary-card-content">
                <div class="summary-card-label">Total Contractors</div>
                <div class="summary-card-value" id="totalContractors">—</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-card-icon">💰</div>
            <div class="summary-card-content">
                <div class="summary-card-label">Pipeline Value</div>
                <div class="summary-card-value" id="pipelineValue">—</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="projects-toolbar">
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Search by contractor, project name, or region...">
        </div>
        <select id="region-filter" class="filter-select">
            <option value="">All Regions</option>
        </select>
        <select id="source-filter" class="filter-select">
            <option value="">All Sources</option>
            <option value="DPWH">DPWH</option>
            <option value="Private">Private</option>
            <option value="BCI">BCI</option>
            <option value="PHILGEPS">PHILGEPS</option>
            <option value="Other">Other</option>
        </select>
        <select id="sort-filter" class="filter-select">
            <option value="publication_date_desc">📅 Newest First</option>
            <option value="publication_date_asc">📅 Oldest First</option>
            <option value="contractor_name_asc">🏢 Contractor A-Z</option>
            <option value="contractor_name_desc">🏢 Contractor Z-A</option>
            <option value="project_name_asc">📋 Project A-Z</option>
            <option value="project_name_desc">📋 Project Z-A</option>
            <option value="project_value_desc">💰 Highest Value</option>
            <option value="project_value_asc">💰 Lowest Value</option>
            <option value="region_asc">📍 Region A-Z</option>
            <option value="region_desc">📍 Region Z-A</option>
            <option value="status_asc">📊 Status A-Z</option>
            <option value="tracking_status_desc">📈 Tracking Status</option>
        </select>
        <button class="btn-refresh" id="refresh-btn">
            <span>🔄</span>
            <span>Refresh</span>
        </button>
    </div>

    <!-- Projects Table -->
    <div class="projects-card">
        <!-- Status Legend -->
        <?php if ($type === 'priority'): ?>
        <div class="status-legend">
            <div class="status-legend-item">
                <span class="status-circle priority"></span>
                <span>Priority</span>
            </div>
        </div>
        <?php else: ?>
        <div class="status-legend">
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
        <?php endif; ?>
        
        <div class="table-wrapper">
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>Contractor</th>
                        <th>Project Name</th>
                        <th>Region</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th class="col-value">Value (₱)</th>
                        <th>Sales Tracking</th>
                        <th class="col-date">Published Date</th>
                    </tr>
                </thead>
                <tbody id="projects-tbody">
                    <tr>
                        <td colspan="8" class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading projects...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <div class="pagination-info" id="pagination-info">
                Showing 0 of 0 projects
            </div>
            <div class="pagination-controls" id="pagination-controls">
                <!-- Pagination buttons will be inserted here -->
            </div>
        </div>
    </div>
</main>

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
            <!-- Edit Button -->
            <button type="button" class="btn-action btn-primary" 
                    id="editProjectBtn">
                ✏️ Edit Project
            </button>
            
            <!-- Clear Sales Tracking Button (Superadmin, Admin, Sales Rep only) -->
            <button type="button" class="btn-action btn-warning role-only--admin role-only--superadmin role-only--sales_rep" 
                    id="clearTrackingBtn">
                🗑️ Clear Tracking
            </button>
            
            <!-- Archive/Restore Button -->
            <button type="button" class="btn-action btn-delete" 
                    id="archiveBtn">
                🗄️ Archive Project
            </button>
            
            <button type="button" class="btn-action btn-primary btn-save" id="saveTrackingBtn">💾 Save Sales Tracking</button>
            
            <button type="button" class="btn-action btn-secondary" id="closeModalBtn">Close</button>
        </div>
    </div>
</div>

<!-- Edit Options Modal -->
<div class="modal-overlay" id="editOptionsModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2>✏️ Edit Project</h2>
            <button class="modal-close" onclick="closeEditOptionsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin: 0 0 1.5rem; color: var(--text-secondary); text-align: center;">
                Select which section you want to edit:
            </p>
            
            <div style="display: grid; gap: 1rem;">
                <!-- Contract Details -->
                <button class="edit-option-card" onclick="editSection('contract')">
                    <div class="edit-option-icon">📋</div>
                    <div class="edit-option-content">
                        <div class="edit-option-title">Contract Details</div>
                        <div class="edit-option-desc">Contract ID, Name, Person, Number, Published Date</div>
                    </div>
                    <div class="edit-option-arrow">→</div>
                </button>
                
                <!-- Project Details -->
                <button class="edit-option-card" onclick="editSection('project')">
                    <div class="edit-option-icon">🏗️</div>
                    <div class="edit-option-content">
                        <div class="edit-option-title">Project Details</div>
                        <div class="edit-option-desc">Project ID, Name, Location, Address, Coordinates</div>
                    </div>
                    <div class="edit-option-arrow">→</div>
                </button>
                
                <!-- Materials -->
                <button class="edit-option-card" onclick="editSection('materials')">
                    <div class="edit-option-icon">🔩</div>
                    <div class="edit-option-content">
                        <div class="edit-option-title">Materials</div>
                        <div class="edit-option-desc">Steel Bars, Beams, Tubes, GI Sheets, etc.</div>
                    </div>
                    <div class="edit-option-arrow">→</div>
                </button>
                
                <!-- Pictures (Priority only) -->
                <button class="edit-option-card" id="editPicturesOption" onclick="editSection('pictures')" style="display: none;">
                    <div class="edit-option-icon">📸</div>
                    <div class="edit-option-content">
                        <div class="edit-option-title">Pictures</div>
                        <div class="edit-option-desc">Upload or manage project images</div>
                    </div>
                    <div class="edit-option-arrow">→</div>
                </button>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action btn-secondary" onclick="closeEditOptionsModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- Edit Section Modal -->
<div class="modal-overlay" id="editSectionModal">
    <div class="modal-content modal-large" style="max-width: 900px;">
        <div class="modal-header">
            <h2 id="editSectionTitle">✏️ Edit</h2>
            <button class="modal-close" onclick="closeEditSectionModal()">&times;</button>
        </div>
        <div class="modal-body" id="editSectionBody">
            <!-- Dynamic form content will be loaded here -->
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action btn-secondary" onclick="closeEditSectionModal()">Cancel</button>
            <button type="button" class="btn-action btn-primary" onclick="saveEditSection()">💾 Save Changes</button>
        </div>
    </div>
</div>

<style>
    .edit-option-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        background: rgba(255, 255, 255, 0.03);
        border: 2px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 100%;
        text-align: left;
    }
    
    .edit-option-card:hover {
        background: rgba(255, 152, 0, 0.1);
        border-color: rgba(255, 152, 0, 0.3);
        transform: translateX(4px);
    }
    
    .edit-option-icon {
        font-size: 2rem;
        flex-shrink: 0;
    }
    
    .edit-option-content {
        flex: 1;
    }
    
    .edit-option-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }
    
    .edit-option-desc {
        font-size: 0.85rem;
        color: var(--text-secondary);
        line-height: 1.3;
    }
    
    .edit-option-arrow {
        font-size: 1.5rem;
        color: var(--text-muted);
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    
    .edit-option-card:hover .edit-option-arrow {
        transform: translateX(4px);
        color: var(--orange-500);
    }
    
    /* Fix modal positioning and z-index to appear above sidebar */
    #editOptionsModal,
    #editSectionModal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        z-index: 10000 !important;
        margin: 0 !important;
    }
    
    #editOptionsModal .modal-content,
    #editSectionModal .modal-content {
        position: relative;
        z-index: 10001 !important;
        margin: auto;
    }
</style>

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

                    <!-- Sales Qualified Leads -->
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

                    <!-- WA Amount -->
                    <div class="detail-item">
                        <div class="detail-label">WA Amount (₱)</div>
                        <input type="number" id="wa_amount" name="wa_amount" class="form-control" placeholder="0.00" step="0.01" min="0">
                    </div>

                    <!-- Remarks -->
                    <div class="detail-item">
                        <div class="detail-label">Remarks</div>
                        <textarea id="remarks" name="remarks" class="form-control" placeholder="Enter remarks..." rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-secondary" onclick="closeTrackingModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="saveTracking()">Save Tracking</button>
        </div>
    </div>
</div>

</div> <!-- /.ap-main -->
</div> <!-- /.ap-shell -->
<div class="ap-sidebar-overlay" id="ap-sidebar-overlay"></div>

<script>const BASE = '<?= $base ?>';</script>
<script src="<?= $base ?>/static/js/auth.js?v=2"></script>
<script src="<?= $base ?>/static/js/utils.js?v=2"></script>
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
<script src="<?= $base ?>/static/js/roles.js?v=2"></script>
<script>window.PROJECT_TYPE = '<?= $type ?>';</script>
<script src="<?= $base ?>/static/js/projects.js?v=10"></script>
<script src="<?= $base ?>/static/js/projects-sales-tracking.js?v=4"></script>

</body>
</html>
 
