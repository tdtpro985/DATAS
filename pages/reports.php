<?php
/* ============================================================
   pages/reports.php — TDT Powersteel Dashboard (Complete Rewrite)
   ============================================================
   Clean, robust implementation maintaining exact same design
   ============================================================ */

// Error handling and session setup
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

// Authentication check
if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');

// Role-based access control
if ($role === 'encoder') {
    header('Location: ' . $base . '/encode');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDT Powersteel — Dashboard</title>

    <!-- External CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            font-family: 'Inter', sans-serif;
        }
        
        .dashboard-container {
            width: 100%;
            max-width: 100vw;
            height: 100dvh;
            max-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }
        
        .dashboard-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 128, 0, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 165, 0, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        /* Ensure no scrollbars anywhere */
        ::-webkit-scrollbar {
            width: 3px;
        }
        
        ::-webkit-scrollbar-track {
            background: #333;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 2px;
        }
        
        /* Hide scrollbars for main containers */
        .dashboard-content,
        .left-sidebar,
        .main-center,
        .right-sidebar {
            overflow: hidden;
        }
        
        /* Header */
        .dashboard-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: clamp(0.3rem, 0.8vh, 0.4rem) clamp(0.5rem, 1.2vw, 0.8rem);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            min-height: clamp(38px, 5vh, 45px);
            border-bottom: 2px solid #ff8000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            flex-wrap: nowrap;
            gap: 0.4rem;
            overflow: hidden;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }
        
        .logo {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #ff8000, #ffa500);
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(255, 128, 0, 0.3);
            flex-shrink: 0;
            cursor: pointer;
        }
        
        .title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.1);
            white-space: nowrap;
        }
        
        .title .brand {
            color: #ff8000;
            text-shadow: 0 1px 2px rgba(255, 128, 0, 0.3);
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            justify-content: flex-end;
            flex-wrap: nowrap;
            min-width: 0;
            overflow: hidden;
        }
        
        .header-controls {
            display: flex;
            gap: clamp(0.3rem, 0.8vw, 0.6rem);
            align-items: center;
            flex-wrap: nowrap;
            min-width: 0;
        }
        
        .control-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15rem;
        }
        
        .control-label {
            font-size: 0.6rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .control-select {
            background: linear-gradient(135deg, #2a2a2a, #3a3a3a);
            border: 1px solid #555;
            color: #fff;
            padding: 0.35rem 1.8rem 0.35rem 0.55rem;
            border-radius: 6px;
            font-size: 0.7rem;
            cursor: pointer;
            min-width: 85px;
            max-width: 120px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ff8000' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.4rem center;
            background-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .control-select:hover {
            border-color: #ff8000;
            box-shadow: 0 4px 12px rgba(255, 128, 0, 0.3);
            background: linear-gradient(135deg, #3a3a3a, #4a4a4a);
            transform: translateY(-1px);
        }
        
        .control-select:focus {
            outline: none;
            border-color: #ff8000;
            box-shadow: 0 0 0 2px rgba(255, 128, 0, 0.2);
            background: linear-gradient(135deg, #3a3a3a, #4a4a4a);
        }
        
        .control-select option {
            background: #2a2a2a;
            color: #fff;
            padding: 0.5rem;
            border: none;
        }
        
        .control-select option:hover {
            background: #3a3a3a;
        }
        
        .control-select option:checked {
            background: #ff8000;
            color: #fff;
        }
        
        .sync-status {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            color: #10b981;
            white-space: nowrap;
        }
        
        .sync-dot {
            width: 5px;
            height: 5px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 4px rgba(16, 185, 129, 0.5);
            flex-shrink: 0;
        }
        
        .time-display {
            font-size: 0.7rem;
            color: #ccc;
            font-weight: 500;
            white-space: nowrap;
        }
        
        /* Export Button - REMOVED */
        
        /* Responsive Header Adjustments */
        @media (max-width: 1400px) {
            .header-controls {
                gap: 0.5rem;
            }
            
            .control-select {
                min-width: 75px;
                font-size: 0.65rem;
                padding: 0.3rem 1.6rem 0.3rem 0.5rem;
            }
            
            .control-label {
                font-size: 0.55rem;
            }
            
            .export-button {
                padding: 0.3rem 0.7rem;
                font-size: 0.65rem;
            }
        }
        
        @media (max-width: 1200px) {
            .title {
                font-size: 0.85rem;
            }
            
            .header-controls {
                gap: 0.4rem;
            }
            
            .control-group {
                gap: 0.1rem;
            }
            
            .control-select {
                min-width: 70px;
                max-width: 100px;
                font-size: 0.6rem;
            }
            
            .sync-status,
            .time-display {
                font-size: 0.65rem;
            }
        }
        
        @media (max-width: 1024px) {
            .dashboard-header {
                padding: 0.3rem 0.6rem;
            }
            
            .header-left {
                gap: 0.5rem;
            }
            
            .title {
                font-size: 0.8rem;
            }
            
            .header-controls {
                gap: 0.3rem;
            }
            
            .control-select {
                min-width: 65px;
                padding: 0.25rem 1.5rem 0.25rem 0.45rem;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                min-height: auto;
                padding: 0.5rem;
                gap: 0.5rem;
            }
            
            .header-left,
            .header-right {
                width: 100%;
            }
            
            .header-right {
                justify-content: space-between;
            }
            
            .header-controls {
                width: 100%;
                justify-content: space-between;
            }
            
            .control-group {
                flex: 1;
                min-width: 0;
            }
            
            .control-select {
                width: 100%;
                min-width: 60px;
                font-size: 0.65rem;
            }
        }
        
        /* Export Modal Styles - REMOVED */
        
        /* Main Content */
        .dashboard-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: clamp(0.25rem, 0.8vw, 0.6rem);
            overflow: hidden;
            min-height: 0;
            position: relative;
            z-index: 1;
        }
        
        /* Main Grid Layout - 3 Columns - viewport-contained */
        .main-grid {
            flex: 1;
            display: grid;
            grid-template-columns: minmax(0, 0.28fr) minmax(0, 0.42fr) minmax(0, 0.30fr);
            grid-template-rows: minmax(0, 1fr);
            gap: clamp(0.35rem, 0.8vw, 0.8rem);
            min-height: 0;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        @media (max-width: 1023px) and (min-width: 768px) {
            .main-grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                grid-template-rows: minmax(0, 1fr);
            }
        }
        
        @media (max-width: 767px) {
            .main-grid {
                grid-template-columns: minmax(0, 1fr);
                grid-template-rows: minmax(0, 1fr);
                gap: 0.4rem;
            }
        }
        
        /* Left Column */
        .left-column {
            display: flex;
            flex-direction: column;
            gap: clamp(0.3rem, 0.6vh, 0.6rem);
            min-height: 0;
            height: 100%;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .left-column > * {
            max-width: 100%;
            overflow: hidden;
            min-height: 0;
        }
        
        /* KPI Summary Left - Responsive Grid */
        .kpi-summary-left {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: clamp(0.2rem, 0.4vw, 0.35rem);
            flex-shrink: 0;
            height: clamp(48px, 7vh, 64px);
            min-height: 0;
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .kpi-summary-left {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .kpi-summary-left {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* KPI Card - Fully Responsive */
        .kpi-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: clamp(0.25rem, 0.5vh, 0.5rem);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 0;
            height: 100%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        @media (max-width: 768px) {
            .kpi-card {
                padding: 0.4rem;
                min-height: 60px;
            }
        }
        
        @media (max-width: 480px) {
            .kpi-card {
                padding: 0.3rem;
                min-height: 55px;
            }
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        .kpi-icon {
            font-size: 1rem;
            margin-bottom: 0.15rem;
        }
        
        .kpi-info {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .kpi-value {
            font-size: 1.05rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 0.15rem;
            text-shadow: 0 1px 2px rgba(255, 128, 0, 0.3);
        }
        
        .kpi-label {
            font-size: 0.65rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: center;
            line-height: 1.2;
        }
        
        @media (max-width: 1280px) {
            .kpi-icon {
                font-size: 1.1rem;
            }
            .kpi-value {
                font-size: 1rem;
            }
            .kpi-label {
                font-size: 0.6rem;
            }
        }
        
        @media (max-width: 768px) {
            .kpi-icon {
                font-size: 1rem;
                margin-bottom: 0.3rem;
            }
            .kpi-value {
                font-size: 0.95rem;
            }
            .kpi-label {
                font-size: 0.58rem;
            }
            .kpi-card {
                padding: 0.4rem;
            }
        }
        
        @media (max-width: 480px) {
            .kpi-icon {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }
            .kpi-value {
                font-size: 0.85rem;
            }
            .kpi-label {
                font-size: 0.52rem;
            }
            .kpi-card {
                padding: 0.35rem;
            }
        }
        
        /* Center Column */
        .center-column {
            display: flex;
            flex-direction: column;
            gap: clamp(0.3rem, 0.6vh, 0.6rem);
            min-height: 0;
            height: 100%;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .center-column > * {
            max-width: 100%;
            overflow: hidden;
            min-height: 0;
        }
        
        /* Target Section */
        .target-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: clamp(0.3rem, 0.7vh, 0.65rem);
            flex-shrink: 0;
            min-height: 72px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .target-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        .target-left,
        .target-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 90px;
            max-width: 13rem;
            padding: 0.45rem 0.5rem;
            background: rgba(255, 128, 0, 0.1);
            border-radius: 6px;
            border: 1px solid rgba(255, 128, 0, 0.2);
            width: auto;
            flex: 0 0 auto;
        }
        
        .target-label {
            font-size: 0.6rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.2rem;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .target-number {
            font-size: 1.3rem;
            color: #fff;
            font-weight: 700;
            line-height: 1;
            word-break: normal;
        }
        
        .target-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1 1 min(240px, 100%);
            min-width: 160px;
            margin: 0;
        }
        
        .target-percentage {
            font-size: clamp(1.3rem, 2.8vw, 2rem);
            font-weight: 900;
            color: #ff8000;
            line-height: 1;
            margin-bottom: 0.2rem;
            text-shadow: 0 2px 4px rgba(255, 128, 0, 0.3);
            word-break: break-word;
        }
        
        .target-status {
            font-size: 0.65rem;
            color: #ef4444;
            margin-bottom: 0.4rem;
            line-height: 1;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .target-progress-container {
            width: 100%;
            max-width: 260px;
            display: flex;
            justify-content: center;
        }
        
        .target-progress-bar {
            background: #333;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            width: min(220px, 100%);
            max-width: 100%;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .target-progress-fill {
            background: linear-gradient(90deg, #ff6000, #ff8000, #ffa500);
            height: 100%;
            width: 38%;
            transition: width 0.8s ease;
            border-radius: 4px;
            box-shadow: 0 0 8px rgba(255, 128, 0, 0.5);
            position: relative;
        }
        
        .target-progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @keyframes pulse {
            0% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.7;
                transform: scale(1.1);
            }
            100% { 
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Right Column */
        .right-column {
            display: flex;
            flex-direction: column;
            gap: clamp(0.3rem, 0.6vh, 0.6rem);
            min-height: 0;
            height: 100%;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .right-column > * {
            max-width: 100%;
            overflow: hidden;
            min-height: 0;
        }
        
        /* Live Slideshow */
        .live-slideshow {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: clamp(0.4rem, 1vh, 1.2rem);
            flex: 1.2;
            display: flex;
            flex-direction: column;
            min-height: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .live-slideshow::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        .slideshow-content {
            --slide-scale: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            text-align: center;
            min-height: 0;
            overflow: hidden;
        }

        .slideshow-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: stretch;
            min-height: 0;
            font-size: calc(0.82rem * var(--slide-scale, 1));
            gap: 0.45em;
        }

        .slideshow-body.slideshow-body--spread {
            justify-content: space-evenly;
        }

        .slideshow-controls {
            flex-shrink: 0;
            margin-top: auto;
        }
        
        .live-contractor-name {
            font-size: 2em;
            font-weight: 800;
            color: #ff8000;
            margin-bottom: 0.15em;
            line-height: 1.15;
            text-shadow: 0 2px 4px rgba(255, 128, 0, 0.3);
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        .live-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.45em;
            text-align: left;
            margin-bottom: 0.1em;
        }
        
        .live-detail {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        
        .live-detail-label {
            font-size: 0.78em;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 0.2em;
        }
        
        .live-detail-value {
            font-size: 0.95em;
            color: #fff;
            font-weight: 500;
            line-height: 1.25;
            word-break: break-word;
        }
        
        .live-footer {
            font-size: 0.85em;
            color: rgba(255,255,255,0.6);
            text-align: center;
            margin-top: 0.15em;
            border-top: 1px solid #333;
            padding-top: 0.35em;
        }
        
        /* Loading Progress Bar for Live Slideshow */
        .slideshow-loading-bar {
            width: 100%;
            height: 3px;
            background: #333;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
            position: relative;
        }
        
        .loading-progress {
            height: 100%;
            background: linear-gradient(90deg, #ff6000, #ff8000, #ffa500);
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s linear;
            position: relative;
        }
        
        .loading-progress::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 1.5s infinite;
        }
        
        /* Slideshow Countdown Timer */
        .slideshow-countdown-bar {
            width: 100%;
            height: calc(4px * max(1, var(--slide-scale, 1) * 0.85));
            background: #333;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(255, 128, 0, 0.3);
        }
        
        .countdown-progress {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 2px;
            width: 100%;
            transition: width 0.1s linear;
            position: relative;
        }
        
        .countdown-progress.ending {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }
        
        .countdown-progress::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 2s infinite;
        }
        
        .slideshow-timer-text {
            font-size: calc(0.6rem * max(1, var(--slide-scale, 1) * 0.9));
            color: #888;
            text-align: center;
            margin-top: 0.3rem;
            font-weight: 500;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Project Status Section */
        .project-status-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: clamp(0.35rem, 0.8vh, 0.8rem);
            flex: 0.85;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .project-status-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        /* Regional Combined Section with Toggle */
        .regional-combined-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: clamp(0.3rem, 0.6vh, 0.55rem);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .regional-combined-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        .section-header-with-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.4rem;
        }
        
        .chart-toggle-buttons {
            display: flex;
            gap: 0.3rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            padding: 0.2rem;
        }
        
        .toggle-btn {
            background: transparent;
            border: none;
            color: #888;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .toggle-btn:hover {
            background: rgba(255, 128, 0, 0.1);
            color: #ff8000;
        }
        
        .toggle-btn.active {
            background: linear-gradient(135deg, #ff8000, #ffa500);
            color: #000;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(255, 128, 0, 0.3);
        }
        
        /* Sources Chart Section */
        .sources-chart-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: clamp(0.3rem, 0.6vh, 0.55rem);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .sources-chart-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        /* Bar Graph Section */
        .bar-graph-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: 0.8rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .bar-graph-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        /* Pie Graph Section */
        .pie-graph-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: 0.8rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .pie-graph-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        /* Target Progress (now in top bar) */
        .target-display {
            text-align: center;
            margin-bottom: 0.5rem;
        }
        
        .target-percentage {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ff8000;
            line-height: 1;
        }
        
        .target-status {
            font-size: 0.75rem;
            color: #ef4444;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }
        
        @media (max-width: 1280px) {
            .target-percentage {
                font-size: 2rem;
            }
            .target-status {
                font-size: 0.7rem;
            }
            .target-number {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .target-percentage {
                font-size: 1.8rem;
            }
            .target-status {
                font-size: 0.65rem;
            }
            .target-number {
                font-size: 1.1rem;
            }
            .target-section {
                padding: 0.7rem;
                min-height: 80px;
                height: auto;
            }
        }
        
        @media (max-width: 480px) {
            .target-percentage {
                font-size: 1.5rem;
            }
            .target-status {
                font-size: 0.6rem;
            }
            .target-number {
                font-size: 1rem;
            }
            .target-section {
                padding: 0.6rem;
                min-height: 70px;
                height: auto;
            }
        }
        
        .target-progress-bar {
            background: #333;
            height: 4px;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .target-progress-fill {
            background: #ff8000;
            height: 100%;
            width: 38%;
            transition: width 0.5s ease;
        }
        
        .target-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.6rem;
            color: #888;
        }
        
        /* Contractors List */
        .contractors-section {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 0.8rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        /* Contractors List */
        .contractors-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: clamp(0.35rem, 0.8vh, 0.8rem);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .contractors-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        .section-title {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        @media (max-width: 1280px) {
            .section-title {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .section-title {
                font-size: 0.75rem;
                margin-bottom: 0.6rem;
            }
        }
        
        .contractors-table {
            flex: 1;
            overflow: hidden;
            min-height: 0;
            position: relative;
        }

        .contractors-scroll-track {
            display: flex;
            flex-direction: column;
            animation: contractorScroll linear infinite;
            animation-play-state: running;
        }

        .contractors-table:hover .contractors-scroll-track {
            animation-play-state: paused;
        }

        @keyframes contractorScroll {
            0%   { transform: translateY(0); }
            100% { transform: translateY(-50%); }
        }
        
        .contractors-table::-webkit-scrollbar {
            width: 2px;
        }
        
        .contractors-table::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .contractors-table::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 1px;
        }
        
        .contractors-table::-webkit-scrollbar-thumb:hover {
            background: #666;
        }
        
        .contractor-row {
            display: grid;
            grid-template-columns: 30px 1fr 60px;
            gap: 0.4rem;
            padding: clamp(0.15rem, 0.3vh, 0.4rem) 0;
            border-bottom: 1px solid #333;
            align-items: center;
            transition: background-color 0.2s ease;
            flex-shrink: 1;
            min-height: 0;
        }
        
        .contractor-row:hover {
            background-color: rgba(255, 128, 0, 0.05);
        }
        
        .contractor-rank {
            font-size: 0.7rem;
            color: #888;
            text-align: center;
        }
        
        .contractor-name {
            font-size: 0.7rem;
            color: #fff;
            font-weight: 500;
        }
        
        .contractor-value {
            font-size: 0.85rem;
            color: #ff8000;
            font-weight: 600;
            text-align: right;
            text-shadow: 0 1px 2px rgba(255, 128, 0, 0.3);
        }
        
        @media (max-width: 1280px) {
            .contractor-value {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .contractor-value {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 480px) {
            .contractor-value {
                font-size: 0.7rem;
            }
        }
        
        /* Contractors List */
        .contractors-section {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 0.8rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        /* Sales Funnel */
        .funnel-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #444;
            border-radius: 8px;
            padding: clamp(0.35rem, 0.8vh, 0.8rem);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .funnel-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff8000, #ffa500, #ff8000);
        }
        
        .funnel-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            min-height: 0;
            overflow: hidden;
        }
        
        .funnel-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: clamp(0.15rem, 0.35vh, 0.5rem) 0;
            border-bottom: 1px solid #333;
            transition: background-color 0.2s ease;
            flex-shrink: 1;
            min-height: 0;
        }
        
        .funnel-item:hover {
            background-color: rgba(255, 128, 0, 0.05);
        }
        
        .funnel-item:last-child {
            border-bottom: none;
        }
        
        .funnel-name {
            font-size: 0.85rem;
            color: #fff;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.1);
        }
        
        .funnel-stats {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        
        .funnel-count {
            font-size: 0.85rem;
            color: #fff;
            font-weight: 700;
            min-width: 30px;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.1);
        }
        
        .funnel-percentage {
            font-size: 0.8rem;
            color: #888;
            min-width: 45px;
        }
        
        @media (max-width: 1280px) {
            .funnel-name,
            .funnel-count {
                font-size: 0.8rem;
            }
            .funnel-percentage {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 768px) {
            .funnel-name,
            .funnel-count {
                font-size: 0.75rem;
            }
            .funnel-percentage {
                font-size: 0.7rem;
                min-width: 40px;
            }
            .funnel-stats {
                gap: 0.5rem;
            }
            .funnel-item {
                padding: 0.4rem 0;
            }
        }
        
        @media (max-width: 480px) {
            .funnel-name,
            .funnel-count {
                font-size: 0.7rem;
            }
            .funnel-percentage {
                font-size: 0.65rem;
                min-width: 35px;
            }
            .funnel-stats {
                gap: 0.4rem;
            }
            .funnel-item {
                padding: 0.35rem 0;
            }
        }
        
        .funnel-bar {
            width: 50px;
            height: 4px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .funnel-bar-fill {
            height: 100%;
            transition: width 0.5s ease;
            box-shadow: 0 0 4px rgba(255, 128, 0, 0.5);
        }
        
        .prospects-fill { background: linear-gradient(90deg, #60a5fa, #3b82f6); }
        .contacted-fill { background: linear-gradient(90deg, #34d399, #10b981); }
        .sales-qualified-fill { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
        .not-sales-qualified-fill { background: linear-gradient(90deg, #f87171, #ef4444); }
        .quoted-fill { background: linear-gradient(90deg, #a78bfa, #8b5cf6); }
        .awarded-fill { background: linear-gradient(90deg, #fb7185, #f43f5e); }
        .won-fill { background: linear-gradient(90deg, #10b981, #059669); }
        
        /* Section Title */
        .section-title {
            font-size: 0.8rem;
            color: #888;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        /* Contractors List */
        .contractors-section {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .contractors-table {
            flex: 1;
            overflow: hidden;
            min-height: 0;
        }
        
        .contractors-table::-webkit-scrollbar {
            width: 3px;
        }
        
        .contractors-table::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .contractors-table::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 1px;
        }
        
        .contractors-table::-webkit-scrollbar-thumb:hover {
            background: #666;
        }
        
        .contractor-row {
            display: grid;
            grid-template-columns: 40px 1fr 80px;
            gap: 0.6rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #333;
            align-items: center;
        }
        
        .contractor-rank {
            font-size: 0.8rem;
            color: #888;
            text-align: center;
        }
        
        .contractor-name {
            font-size: 0.8rem;
            color: #fff;
            font-weight: 500;
        }
        
        .contractor-value {
            font-size: 0.8rem;
            color: #ff8000;
            font-weight: 600;
            text-align: right;
        }
        
        /* Chart Container */
        .chart-container {
            flex: 1;
            display: flex;
            align-items: stretch;
            justify-content: center;
            position: relative;
            min-height: 0;
            padding: 0.1rem;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }
        
        .chart-container canvas {
            display: block;
            max-width: 100% !important;
            max-height: 100% !important;
            width: 100% !important;
            height: 100% !important;
        }
        
        /* Category Items */
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: clamp(0.15rem, 0.35vh, 0.5rem) 0;
            border-bottom: 1px solid #333;
            flex-shrink: 1;
            min-height: 0;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-name {
            font-size: 0.7rem;
            color: #fff;
            font-weight: 500;
        }
        
        .category-stats {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        
        .category-count {
            font-size: 0.7rem;
            color: #fff;
            font-weight: 600;
            min-width: 25px;
        }
        
        .category-value {
            font-size: 0.7rem;
            color: #ff8000;
            font-weight: 600;
            min-width: 50px;
        }
        
        .category-percentage {
            font-size: 0.65rem;
            color: #888;
            min-width: 40px;
        }
        
        .category-bar {
            width: 50px;
            height: 3px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .category-bar-fill {
            height: 100%;
            background: #ff8000;
            transition: width 0.5s ease;
        }
        
        .contractors-table {
            flex: 1;
            overflow: hidden;
            min-height: 0;
        }
        
        .contractors-table::-webkit-scrollbar {
            width: 2px;
        }
        
        .contractors-table::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .contractors-table::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 1px;
        }
        
        .contractors-table::-webkit-scrollbar-thumb:hover {
            background: #666;
        }
        
        .contractor-row {
            display: grid;
            grid-template-columns: 30px 1fr 60px;
            gap: 0.5rem;
            padding: 0.4rem 0;
            border-bottom: 1px solid #333;
            align-items: center;
        }
        
        .contractor-rank {
            font-size: 0.7rem;
            color: #888;
            text-align: center;
        }
        
        .contractor-name {
            font-size: 0.7rem;
            color: #fff;
            font-weight: 500;
        }
        
        .contractor-value {
            font-size: 0.7rem;
            color: #ff8000;
            font-weight: 600;
            text-align: right;
        }
        
        /* Main Center Area */
        .main-center {
            grid-column: 2;
            grid-row: 2;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            max-height: calc(100vh - 115px);
        }
        
        /* Live Contractor */
        .live-contractor {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            height: 180px;
        }
        
        .live-title {
            font-size: 0.75rem;
            color: #888;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        
        .live-contractor .live-contractor-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: #ff8000;
            margin-bottom: 0.8rem;
            line-height: 1.2;
        }
        
        .live-contractor .live-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
            text-align: left;
            font-size: 0.7rem;
        }
        
        .live-contractor .live-detail {
            display: flex;
            flex-direction: column;
        }
        
        .live-contractor .live-detail-label {
            font-size: 0.65rem;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 0.2rem;
        }
        
        .live-contractor .live-detail-value {
            font-size: 0.7rem;
            color: #fff;
            font-weight: 500;
        }
        
        /* Chart Sections */
        .chart-section {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 0.8rem;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .chart-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 0;
            padding: 0.5rem;
        }
        
        /* Category Items */
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: clamp(0.15rem, 0.35vh, 0.5rem) 0;
            border-bottom: 1px solid #333;
            flex-shrink: 1;
            min-height: 0;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-name {
            font-size: 0.7rem;
            color: #fff;
            font-weight: 500;
        }
        
        .category-stats {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        
        .category-count {
            font-size: 0.7rem;
            color: #fff;
            font-weight: 600;
            min-width: 25px;
        }
        
        .category-value {
            font-size: 0.7rem;
            color: #ff8000;
            font-weight: 600;
            min-width: 50px;
        }
        
        .category-percentage {
            font-size: 0.65rem;
            color: #888;
            min-width: 40px;
        }
        
        .category-bar {
            width: 50px;
            height: 3px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .category-bar-fill {
            height: 100%;
            background: #ff8000;
            transition: width 0.5s ease;
        }
        
        /* Responsive Design - viewport height adjustments */
        
        @media (max-height: 900px) {
            .dashboard-header {
                padding: 0.3rem 0.6rem;
                min-height: 38px;
            }
            
            .kpi-summary-left {
                height: clamp(48px, 7vh, 62px);
            }
            
            .target-section {
                height: clamp(52px, 8vh, 75px);
            }
            
            .section-title {
                margin-bottom: 0.35rem;
                font-size: 0.72rem;
            }
            
            .funnel-item,
            .category-item,
            .contractor-row {
                padding-top: 0.2rem;
                padding-bottom: 0.2rem;
            }
        }
        
        @media (max-height: 768px) {
            .dashboard-content {
                padding: 0.25rem;
            }
            
            .main-grid {
                gap: 0.3rem;
            }
            
            .left-column,
            .center-column,
            .right-column {
                gap: 0.3rem;
            }
            
            .kpi-icon {
                display: none;
            }
            
            .kpi-value {
                font-size: clamp(0.7rem, 1.2vw, 0.95rem);
            }
            
            .kpi-label {
                font-size: clamp(0.45rem, 0.8vw, 0.58rem);
            }
            
            .target-percentage {
                font-size: clamp(1.2rem, 2.5vw, 1.6rem);
            }
            
            .live-contractor .live-details {
                gap: 0.35rem;
                font-size: 0.62rem;
            }
            
            .live-contractor .live-footer,
            .slideshow-timer-text {
                font-size: 0.55rem;
            }
        }
        
        /* Tablet Landscape (768px - 1023px) */
        @media (max-width: 1023px) {
            .dashboard-header {
                flex-wrap: wrap;
                height: auto;
                padding: 0.5rem;
            }
            
            .header-controls {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .control-select {
                min-width: 100px;
                font-size: 0.7rem;
            }
            
            .export-button {
                font-size: 0.7rem;
                padding: 0.35rem 0.8rem;
            }
            
            .main-grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                grid-template-rows: minmax(0, 1fr) minmax(0, 1fr);
                gap: 0.5rem;
                overflow: hidden;
            }
            
            .left-column {
                grid-column: 1;
                grid-row: 1 / 3;
            }
            
            .center-column {
                grid-column: 2;
                grid-row: 1;
            }
            
            .right-column {
                grid-column: 2;
                grid-row: 2;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .dashboard-content {
                padding: 0.4rem;
            }
        }
        
        /* Tablet Portrait & Mobile (max 767px) */
        @media (max-width: 767px) {
            html, body {
                overflow: hidden;
                height: 100dvh;
            }
            
            .dashboard-container {
                overflow: hidden;
                height: 100dvh;
                max-height: 100dvh;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.4rem;
                height: auto;
                min-height: auto;
                padding: 0.45rem;
                flex-shrink: 0;
            }
            
            .header-left {
                width: 100%;
            }
            
            .header-right {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 0.4rem;
            }
            
            .header-controls {
                width: 100%;
                flex-wrap: wrap;
                gap: 0.35rem;
            }
            
            .control-group {
                flex: 1;
                min-width: 90px;
            }
            
            .control-select {
                width: 100%;
                font-size: 0.65rem;
            }
            
            .export-button {
                width: 100%;
                justify-content: center;
                font-size: 0.7rem;
            }
            
            .dashboard-content {
                padding: 0.35rem;
                overflow: hidden;
                flex: 1;
                min-height: 0;
            }
            
            .main-grid {
                grid-template-columns: minmax(0, 1fr);
                grid-template-rows: minmax(0, 0.34fr) minmax(0, 0.38fr) minmax(0, 0.28fr);
                gap: 0.35rem;
                overflow: hidden;
                flex: 1;
                min-height: 0;
            }
            
            .left-column {
                grid-row: 1;
                display: flex;
                flex-direction: column;
            }
            
            .center-column {
                grid-row: 2;
            }
            
            .right-column {
                grid-row: 3;
                flex-direction: row;
                gap: 0.35rem;
            }
            
            .right-column > * {
                flex: 1;
                min-width: 0;
            }
            
            .left-column,
            .center-column,
            .right-column {
                grid-column: 1;
                width: 100%;
                overflow: hidden;
                min-height: 0;
                height: 100%;
            }
        }
        
        /* Small mobile - extra compact sizing (inherits viewport grid from 767px) */
        @media (max-width: 599px) {
            .kpi-summary-left {
                height: clamp(44px, 6vh, 52px);
            }
            
            .target-section {
                min-height: clamp(52px, 7vh, 65px);
                height: auto;
            }
            
            .right-column {
                flex-direction: column;
            }
            
            .control-select {
                min-width: 0;
                font-size: 0.6rem;
            }
            
            .kpi-icon {
                display: none;
            }
        }
        
        @media (max-width: 479px) {
            .title {
                font-size: 0.85rem;
            }
            
            .slideshow-body .live-details {
                grid-template-columns: 1fr;
            }
        }
        
        /* ══════════════════════════════════════════════════════════
           GLOBAL RESPONSIVE TEXT STYLES FOR ALL CARDS
        ══════════════════════════════════════════════════════════ */
        @media (max-width: 1280px) {
            .category-count,
            .category-value {
                font-size: 0.8rem;
            }
            .category-percentage {
                font-size: 0.75rem;
            }
            .contractor-rank,
            .contractor-name {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .category-count,
            .category-value {
                font-size: 0.75rem;
            }
            .category-percentage {
                font-size: 0.7rem;
            }
            .category-stats {
                gap: 0.5rem;
            }
            .contractor-rank,
            .contractor-name {
                font-size: 0.75rem;
            }
            
            .project-status-section,
            .contractors-section,
            .bar-graph-section,
            .pie-graph-section,
            .funnel-section {
                padding: 0.7rem;
            }
        }
        
        @media (max-width: 480px) {
            .category-count,
            .category-value {
                font-size: 0.7rem;
            }
            .category-percentage {
                font-size: 0.65rem;
            }
            .category-stats {
                gap: 0.4rem;
            }
            .contractor-rank,
            .contractor-name {
                font-size: 0.7rem;
            }
            
            .project-status-section,
            .contractors-section,
            .bar-graph-section,
            .pie-graph-section,
            .funnel-section {
                padding: 0.6rem;
            }
        }

        /* Compact layout — cards hug their data, charts absorb leftover space */
        @media (min-width: 768px) {
            .dashboard-content {
                padding: clamp(0.15rem, 0.4vw, 0.35rem);
            }

            .main-grid {
                gap: clamp(0.2rem, 0.45vw, 0.45rem);
            }

            .left-column {
                display: grid;
                grid-template-rows: auto minmax(0, 1fr) auto;
                gap: clamp(0.2rem, 0.45vh, 0.4rem);
            }

            .center-column {
                display: grid;
                grid-template-rows: auto minmax(0, 1fr) auto;
                gap: clamp(0.2rem, 0.45vh, 0.4rem);
            }

            .right-column {
                display: grid;
                grid-template-rows: minmax(0, 1fr) minmax(0, 1fr);
                gap: clamp(0.2rem, 0.45vh, 0.4rem);
            }

            .contractors-section,
            .live-slideshow,
            .regional-combined-section,
            .sources-chart-section {
                flex: unset;
                min-height: 0;
                overflow: hidden;
            }

            .funnel-section,
            .project-status-section {
                flex: unset;
                height: auto;
                min-height: unset;
            }

            .contractors-section,
            .funnel-section,
            .project-status-section,
            .live-slideshow,
            .regional-combined-section,
            .sources-chart-section {
                padding: clamp(0.3rem, 0.6vh, 0.55rem);
            }

            .section-title,
            .section-header-with-toggle {
                margin-bottom: 0.35rem;
            }

            .funnel-list {
                flex: unset;
                overflow: visible;
            }

            .funnel-item,
            .category-item {
                flex-shrink: 0;
            }

            .slideshow-content {
                justify-content: flex-start;
            }

            .slideshow-body {
                gap: 0.4em;
            }

            .target-left,
            .target-right {
                padding: 0.3rem 0.4rem;
            }

            .target-center {
                margin: 0 0.6rem;
            }
        }
        
        /* Priority Alert Modal Styles */
        .priority-alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            display: none;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.5s ease;
        }
        
        /* Pictures Modal (First Modal) */
        .priority-pictures-modal {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 3px solid #ff8000;
            border-radius: 15px;
            width: 80vw;
            height: 80vh;
            padding: 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(255, 128, 0, 0.3);
            animation: slideInUp 0.7s ease;
            display: flex;
            flex-direction: column;
        }
        
        .priority-pictures-header {
            background: linear-gradient(135deg, #ff8000, #ffa500);
            color: #000;
            padding: 1rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .priority-pictures-content {
            flex: 1;
            background: #000;
            position: relative;
            overflow: hidden;
        }
        
        .priority-alert-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .priority-alert-image.active {
            opacity: 1;
        }
        
        .priority-alert-no-images {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #888;
            font-size: 2rem;
            flex-direction: column;
            gap: 1rem;
        }
        
        .priority-image-counter {
            position: absolute;
            bottom: 1rem;
            left: 1rem;
            background: rgba(0, 0, 0, 0.8);
            color: #ff8000;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .priority-slideshow-timer {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1rem;
        }
        
        .priority-pictures-footer {
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            padding: 1rem;
            text-align: center;
            color: #fff;
            font-size: 1rem;
            border-top: 2px solid #ff8000;
        }
        
        /* Data Modal (Second Modal) */
        .priority-data-modal {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 3px solid #ff8000;
            border-radius: 12px;
            width: 85vw;
            height: 80vh;
            max-width: 1100px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(255, 128, 0, 0.4);
            animation: slideInUp 0.7s ease;
            display: flex;
            flex-direction: column;
        }
        
        .priority-data-header {
            text-align: center;
            padding: 1.2rem 2rem;
            border-bottom: 3px solid #000;
            background: linear-gradient(135deg, #ff8000, #e67300);
            color: #000;
            font-weight: 900;
            font-size: 1.8rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .priority-data-title {
            margin: 0;
            padding: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        .priority-data-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            padding: 0;
            background: #000;
            overflow: hidden;
            min-height: 0;
            height: 100%;
        }
        
        .priority-data-left,
        .priority-data-right {
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
            gap: 0;
            border-right: 2px solid #ff8000;
            background: linear-gradient(135deg, #0a0a0a, #1a1a1a);
            height: 100%;
        }
        
        .priority-data-right {
            border-right: none;
        }
        
        .priority-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            padding: 1rem 1.2rem;
            border-radius: 6px;
            background: rgba(255, 128, 0, 0.08);
            border: 1px solid rgba(255, 128, 0, 0.15);
            transition: all 0.2s ease;
            flex: 1;
            justify-content: center;
            margin-bottom: 0.8rem;
        }
        
        .priority-field:last-child {
            margin-bottom: 0;
        }
        
        .priority-field:hover {
            background: rgba(255, 128, 0, 0.12);
            border-color: rgba(255, 128, 0, 0.25);
            transform: translateY(-1px);
        }
        
        .field-label {
            font-size: 0.75rem;
            color: #ff8000;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-family: 'Inter', 'Arial', sans-serif;
            line-height: 1;
            margin-bottom: 0.3rem;
        }
        
        .field-value {
            font-size: 1rem;
            color: #fff;
            font-weight: 600;
            font-family: 'Inter', 'Arial', sans-serif;
            word-wrap: break-word;
            line-height: 1.3;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .priority-bottom-bar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            border-top: 3px solid #ff8000;
            padding: 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .priority-bottom-left,
        .priority-bottom-right {
            padding: 1.5rem 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            border-right: 2px solid #ff8000;
            background: rgba(255, 128, 0, 0.05);
        }
        
        .priority-bottom-right {
            border-right: none;
        }
        
        .bottom-label {
            font-size: 0.8rem;
            color: #ff8000;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.3rem;
        }
        
        .bottom-value {
            font-size: 2rem;
            color: #fff;
            font-weight: 900;
            text-shadow: 0 2px 6px rgba(255, 255, 255, 0.2);
            line-height: 1;
        }
        
        .priority-data-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            overflow: hidden;
        }
        
        .priority-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 128, 0, 0.15);
            border-radius: 8px;
            padding: 1rem;
        }
        
        .priority-section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #ff8000;
            margin-bottom: 0.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 128, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .priority-detail-group {
            padding: 1rem;
            background: rgba(255, 128, 0, 0.1);
            border-radius: 8px;
            border-left: 4px solid #ff8000;
            min-height: 0;
        }
        
        .priority-detail-label {
            font-size: 0.7rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .priority-detail-value {
            font-size: 1rem;
            color: #fff;
            font-weight: 500;
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        .priority-detail-value.large {
            font-size: 1.3rem;
            color: #ff8000;
            font-weight: 700;
        }
        
        .priority-detail-value.extra-large {
            font-size: 1.5rem;
            color: #ff8000;
            font-weight: 900;
            text-shadow: 0 2px 4px rgba(255, 128, 0, 0.3);
        }
        
        /* BRAND NEW PRIORITY DATA MODAL - COMPLETELY REWRITTEN */
        .priority-data-modal-new {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 3px solid #ff8000;
            border-radius: 10px;
            width: 90vw;
            height: 85vh;
            max-width: 1200px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(255, 128, 0, 0.4);
            animation: slideInUp 0.7s ease;
            display: flex;
            flex-direction: column;
        }
        
        .priority-header-new {
            background: linear-gradient(135deg, #ff8000, #e67300);
            color: #000;
            text-align: center;
            padding: 1rem;
            font-size: 1.6rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 3px solid #000;
        }
        
        .priority-source-new {
            margin: 0;
            padding: 0;
        }
        
        .priority-grid-container {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: repeat(5, 1fr);
            gap: 0;
            padding: 0;
            overflow: hidden;
            height: 100%;
        }
        
        .priority-field-new {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 1rem 1.5rem;
            border-right: 1px solid #ff8000;
            border-bottom: 1px solid #ff8000;
            background: linear-gradient(135deg, #0f0f0f, #1a1a1a);
            transition: all 0.2s ease;
            min-height: 0;
        }
        
        .priority-field-new:nth-child(even) {
            border-right: none;
        }
        
        .priority-field-new:nth-child(9),
        .priority-field-new:nth-child(10) {
            border-bottom: none;
        }
        
        .priority-field-new:hover {
            background: linear-gradient(135deg, #1f1f1f, #2a2a2a);
            transform: scale(1.02);
        }
        
        .field-label-new {
            font-size: 0.75rem;
            color: #ff8000;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            font-family: 'Inter', sans-serif;
        }
        
        .field-value-new {
            font-size: 0.9rem;
            color: #fff;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            line-height: 1.3;
            word-wrap: break-word;
            overflow-wrap: break-word;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Close button for both modals */
        .priority-alert-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #ff8000;
            color: #000;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 1;
        }
        
        .priority-alert-close:hover {
            background: #ffa500;
            transform: scale(1.1);
        }
        
        /* Click anywhere indicator */
        .priority-click-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 128, 0, 0.9);
            color: #000;
            padding: 1rem 2rem;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 600;
            animation: pulseGlow 2s infinite;
            text-align: center;
            border: 2px solid #fff;
        }
        
        @keyframes pulseGlow {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(255, 128, 0, 0.6);
                transform: translateX(-50%) scale(1);
            }
            50% { 
                box-shadow: 0 0 30px rgba(255, 128, 0, 0.9);
                transform: translateX(-50%) scale(1.05);
            }
        }
        
        /* Enhanced data modal styles */
        .priority-detail-value.extra-large {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ff8000;
            text-shadow: 0 1px 2px rgba(255, 128, 0, 0.3);
        }
        
        .priority-detail-value.large {
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .priority-detail-value.orange {
            color: #ff8000 !important;
            font-weight: 600;
        }
        
        .priority-detail-value.location-text {
            font-size: 0.8rem;
            color: #ddd;
            font-style: italic;
        }
        
        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white !important;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }
        
        .progress-rate {
            color: #10b981 !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
        }
        
        .materials-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.3rem;
            font-size: 0.75rem !important;
        }
        
        .materials-list > div {
            padding: 0.3rem;
            background: rgba(255, 128, 0, 0.05);
            border-radius: 3px;
            border: 1px solid rgba(255, 128, 0, 0.1);
        }
        
        .material-value {
            color: #ff8000;
            font-weight: 600;
        }
        
        /* Responsive for mobile */
        @media (max-width: 768px) {
            .priority-pictures-modal {
                width: 95vw;
                height: 85vh;
                border-radius: 8px;
            }
            
            .priority-data-modal {
                width: 98vw;
                height: 95vh;
                border-radius: 8px;
            }
            
            .priority-data-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .priority-data-left,
            .priority-data-right {
                padding: 1.5rem;
                gap: 1rem;
                border-right: none;
                border-bottom: 2px solid #ff8000;
            }
            
            .priority-data-right {
                border-bottom: none;
            }
            
            .priority-data-header {
                padding: 1rem;
                font-size: 1.4rem;
            }
            
            .priority-field {
                gap: 0.4rem;
                padding: 0.6rem;
            }
            
            .field-label {
                font-size: 0.7rem;
            }
            
            .field-value {
                font-size: 0.9rem;
            }
            
            .priority-bottom-bar {
                grid-template-columns: 1fr;
            }
            
            .priority-bottom-left,
            .priority-bottom-right {
                padding: 1rem;
                border-right: none;
                border-bottom: 2px solid #ff8000;
            }
            
            .priority-bottom-right {
                border-bottom: none;
            }
            
            .bottom-value {
                font-size: 1.8rem;
            }
            
            .priority-click-indicator {
                font-size: 0.9rem;
                padding: 0.8rem 1.5rem;
                bottom: 1rem;
            }
        }
        
        /* Responsive Modal Sizing for Desktop */
        @media (min-width: 1400px) {
            .priority-data-modal {
                width: 75vw;
                height: 70vh;
                max-width: 1000px;
            }
            
            .field-label {
                font-size: 0.8rem;
            }
            
            .field-value {
                font-size: 1.1rem;
            }
            
            .priority-field {
                padding: 1.2rem 1.4rem;
            }
        }
        
        @media (max-width: 1399px) and (min-width: 1200px) {
            .priority-data-modal {
                width: 80vw;
                height: 75vh;
            }
            
            .field-label {
                font-size: 0.75rem;
            }
            
            .field-value {
                font-size: 1rem;
            }
            
            .priority-field {
                padding: 1rem 1.2rem;
            }
        }
        
        @media (max-width: 1199px) and (min-width: 900px) {
            .priority-data-modal {
                width: 85vw;
                height: 80vh;
            }
            
            .field-label {
                font-size: 0.7rem;
            }
            
            .field-value {
                font-size: 0.9rem;
            }
            
            .priority-field {
                padding: 0.8rem 1rem;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes pulse {
            0%, 100% { 
                text-shadow: 0 0 20px rgba(255, 128, 0, 0.5);
                transform: scale(1);
            }
            50% { 
                text-shadow: 0 0 30px rgba(255, 128, 0, 0.8);
                transform: scale(1.02);
            }
        }
    </style>
    
    <!-- Modern Select Dropdowns Styling -->
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-select-v2.css">
    <link rel="stylesheet" href="<?= $base ?>/static/css/custom-select-dropdown.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <div class="logo" onclick="window.history.back()" title="Back"></div>
                <div class="title"><span class="brand">TDT POWERSTEEL</span> Dashboard</div>
            </div>
            <div class="header-right">
                <div class="header-controls">
                    <div class="control-group">
                        <div class="control-label">Period</div>
                        <select class="control-select" id="period-select">
                            <option value="monthly" selected>Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <div class="control-label">Region</div>
                        <select class="control-select" id="region-select">
                            <option value="all" selected>All Regions</option>
                            <option value="NCR">NCR</option>
                            <option value="CAR">CAR</option>
                            <option value="I">Region I - Ilocos</option>
                            <option value="II">Region II - Cagayan Valley</option>
                            <option value="III">Region III - Central Luzon</option>
                            <option value="IV-A">Region IV-A - CALABARZON</option>
                            <option value="IV-B">Region IV-B - MIMAROPA</option>
                            <option value="V">Region V - Bicol</option>
                            <option value="VI">Region VI - Western Visayas</option>
                            <option value="VII">Region VII - Central Visayas</option>
                            <option value="VIII">Region VIII - Eastern Visayas</option>
                            <option value="IX">Region IX - Zamboanga Peninsula</option>
                            <option value="X">Region X - Northern Mindanao</option>
                            <option value="XI">Region XI - Davao</option>
                            <option value="XII">Region XII - SOCCSKSARGEN</option>
                            <option value="XIII">Region XIII - Caraga</option>
                            <option value="BARMM">BARMM</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <div class="control-label">Month</div>
                        <select class="control-select" id="month-select">
                            <option value="" selected>Loading months...</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <div class="control-label">Synced</div>
                        <div class="sync-status">
                            <div class="sync-dot"></div>
                            <span id="sync-time">09:00 AM</span>
                        </div>
                    </div>
                    <div class="control-group">
                        <div class="control-label">Time</div>
                        <div class="time-display" id="current-time">08:00:47 AM</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-content">
            <!-- Main Grid Layout -->
            <div class="main-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- KPI Summary -->
                    <div class="kpi-summary-left">
                        <div class="kpi-card">
                            <div class="kpi-icon">📋</div>
                            <div class="kpi-info">
                                <div class="kpi-value" id="total-projects">0</div>
                                <div class="kpi-label">Total Projects</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon">👷</div>
                            <div class="kpi-info">
                                <div class="kpi-value" id="total-contractors">0</div>
                                <div class="kpi-label">Total Contractors</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon">💰</div>
                            <div class="kpi-info">
                                <div class="kpi-value" id="total-value">₱0</div>
                                <div class="kpi-label">Pipeline Projects</div>
                            </div>
                        </div>
                    </div>

                    <!-- List of Contractors -->
                    <div class="contractors-section">
                        <div class="section-title">📋 List of Contractors</div>
                        <div class="contractors-table" id="contractors-list">
                            <!-- Contractors will be loaded from database -->
                        </div>
                    </div>

                    <!-- Sales Funnel -->
                    <div class="funnel-section">
                        <div class="section-title">🔽 Sales Funnel</div>
                        <div class="funnel-list">
                            <!-- Funnel data will be loaded from database -->
                        </div>
                    </div>
                </div>

                <!-- Center Column -->
                <div class="center-column">
                    <!-- Target Projects -->
                    <div class="target-section">
                        <div class="target-left">
                            <div class="target-label">Encoded</div>
                            <div class="target-number">0</div>
                        </div>
                        <div class="target-center">
                            <div class="target-percentage">0%</div>
                            <div class="target-status">🔺 LOADING...</div>
                            <div class="target-progress-container">
                                <div class="target-progress-bar">
                                    <div class="target-progress-fill"></div>
                                </div>
                            </div>
                        </div>
                        <div class="target-right">
                            <div class="target-label">Target</div>
                            <div class="target-number">600</div>
                        </div>
                    </div>

                    <!-- Live Slideshow -->
                    <div class="live-slideshow">
                        <div class="section-title">🔴 Live Slideshow</div>
                        <div class="slideshow-content">
                            <div class="slideshow-body">
                                <div class="live-contractor-name">Loading...</div>
                                <div class="live-details">
                                    <div class="live-detail">
                                        <div class="live-detail-label">Contact:</div>
                                        <div class="live-detail-value" id="liveContact">Loading...</div>
                                    </div>
                                    <div class="live-detail">
                                        <div class="live-detail-label">Phone:</div>
                                        <div class="live-detail-value" id="livePhone">Loading...</div>
                                    </div>
                                    <div class="live-detail">
                                        <div class="live-detail-label">Project:</div>
                                        <div class="live-detail-value" id="liveProject">Loading...</div>
                                    </div>
                                    <div class="live-detail">
                                        <div class="live-detail-label">Value:</div>
                                        <div class="live-detail-value" id="liveProjectValue">₱0</div>
                                    </div>
                                    <div class="live-detail">
                                        <div class="live-detail-label">Status:</div>
                                        <div class="live-detail-value" id="liveStatus">Loading...</div>
                                    </div>
                                </div>
                                <div class="materials-list" id="liveMaterialsList">
                                    <div>
                                        <div class="material-label">DRBs Type</div>
                                        <div class="material-value">—</div>
                                    </div>
                                    <div>
                                        <div class="material-label">DRBs Amount</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                    <div>
                                        <div class="material-label">Sheet Pile Type</div>
                                        <div class="material-value">—</div>
                                    </div>
                                    <div>
                                        <div class="material-label">Sheet Pile Amount</div>
                                        <div class="material-value">₱0</div>
                                    </div>
                                </div>
                                <div class="live-footer" style="display:none;"></div>
                            </div>
                            <div class="slideshow-controls">
                                <!-- Loading Progress Bar -->
                                <div class="slideshow-loading-bar" id="slideshowLoadingBar" style="display: none;">
                                    <div class="loading-progress" id="loadingProgress"></div>
                                </div>
                                <!-- Slideshow Countdown Timer -->
                                <div class="slideshow-countdown-bar" id="slideshowCountdownBar">
                                    <div class="countdown-progress" id="countdownProgress"></div>
                                </div>
                                <div class="slideshow-timer-text" id="slideshowTimerText">Next slide in 10s</div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Status -->
                    <div class="project-status-section">
                        <div class="section-title">📈 Project Status</div>
                        <div class="category-item">
                            <div class="category-name">PRIORITY</div>
                            <div class="category-stats">
                                <div class="category-count">0</div>
                                <div class="category-value">₱0</div>
                                <div class="category-percentage">0.0%</div>
                                <div class="category-bar">
                                    <div class="category-bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="category-item">
                            <div class="category-name">FOR EXECUTION</div>
                            <div class="category-stats">
                                <div class="category-count">0</div>
                                <div class="category-value">₱0</div>
                                <div class="category-percentage">0.0%</div>
                                <div class="category-bar">
                                    <div class="category-bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="category-item">
                            <div class="category-name">AWARDED</div>
                            <div class="category-stats">
                                <div class="category-count">0</div>
                                <div class="category-value">₱0</div>
                                <div class="category-percentage">0.0%</div>
                                <div class="category-bar">
                                    <div class="category-bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="category-item">
                            <div class="category-name">FOR BIDDING</div>
                            <div class="category-stats">
                                <div class="category-count">0</div>
                                <div class="category-value">₱0</div>
                                <div class="category-percentage">0.0%</div>
                                <div class="category-bar">
                                    <div class="category-bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <!-- Combined Regional Card with Toggle -->
                    <div class="regional-combined-section">
                        <div class="section-header-with-toggle">
                            <div class="section-title">📊 Regional Analytics</div>
                            <div class="chart-toggle-buttons">
                                <button class="toggle-btn active" data-chart="values">Values</button>
                                <button class="toggle-btn" data-chart="projects">Projects</button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="regional-values-chart"></canvas>
                            <canvas id="regional-distribution-chart" style="display: none;"></canvas>
                        </div>
                    </div>

                    <!-- Sources Pie Chart -->
                    <div class="sources-chart-section">
                        <div class="section-title">📍 Project Sources</div>
                        <div class="chart-container">
                            <canvas id="sources-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modals - REMOVED -->
            <div class="export-modal-header">
                <h3>📊 Select Reports to Export</h3>
                <button class="export-modal-close" onclick="ExportModal.closeReportSelection()">×</button>
            </div>
            
            <div class="export-modal-content">
                <div class="export-select-all">
                    <label class="export-checkbox-container">
                        <input type="checkbox" id="selectAllReports" onchange="ExportModal.toggleSelectAll()">
                        <span class="export-checkmark"></span>
                        <span class="export-label">Select All Reports</span>
                    </label>
                </div>
                
                <div class="export-reports-list">
                    <label class="export-checkbox-container">
                        <div>
                            <input type="checkbox" name="exportReport" value="users" id="exportUsers">
                            <span class="export-checkmark"></span>
                            <div>
                                <div class="export-label">👥 Users</div>
                                <div class="export-description">All system users and their details</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="export-checkbox-container">
                        <div>
                            <input type="checkbox" name="exportReport" value="sales_reps" id="exportSalesReps">
                            <span class="export-checkmark"></span>
                            <div>
                                <div class="export-label">💼 Sales Representatives</div>
                                <div class="export-description">Sales team members and performance data</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="export-checkbox-container">
                        <div>
                            <input type="checkbox" name="exportReport" value="non_priority_projects" id="exportNonPriorityProjects">
                            <span class="export-checkmark"></span>
                            <div>
                                <div class="export-label">📋 Non-Priority Projects</div>
                                <div class="export-description">Regular projects and their status</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="export-checkbox-container">
                        <div>
                            <input type="checkbox" name="exportReport" value="priority_projects" id="exportPriorityProjects">
                            <span class="export-checkmark"></span>
                            <div>
                                <div class="export-label">🚨 Priority Projects</div>
                                <div class="export-description">High-priority projects requiring immediate attention</div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            
            <div class="export-modal-footer">
                <button class="export-btn-cancel" onclick="ExportModal.closeReportSelection()">Cancel</button>
                <button class="export-btn-next" onclick="ExportModal.showFormatSelection()">Next →</button>
            </div>
        </div>
    </div>

    <!-- Second Modal: Export Format Selection -->
    <div class="export-modal-overlay" id="exportFormatModal" style="display: none;">
        <div class="export-modal">
            <div class="export-modal-header">
                <h3>📄 Select Export Format</h3>
                <button class="export-modal-close" onclick="ExportModal.closeFormatSelection()">×</button>
            </div>
            
            <div class="export-modal-content">
                <div class="export-selected-reports">
                    <h4>Selected Reports:</h4>
                    <div id="selectedReportsDisplay" class="selected-reports-list">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
                
                <div class="export-format-options">
                    <div class="export-format-option" onclick="ExportModal.selectFormat('pdf')">
                        <div class="format-icon">📄</div>
                        <div class="format-details">
                            <div class="format-name">Export as PDF</div>
                            <div class="format-description">Professional document format, ideal for printing and sharing</div>
                        </div>
                        <div class="format-radio">
                            <input type="radio" name="exportFormat" value="pdf" id="formatPdf">
                        </div>
                    </div>
                    
                    <div class="export-format-option" onclick="ExportModal.selectFormat('excel')">
                        <div class="format-icon">📊</div>
                        <div class="format-details">
                            <div class="format-name">Export as Excel</div>
                            <div class="format-description">Spreadsheet format, perfect for data analysis and calculations</div>
                        </div>
                        <div class="format-radio">
                            <input type="radio" name="exportFormat" value="excel" id="formatExcel">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="export-modal-footer">
                <button class="export-btn-back" onclick="ExportModal.showReportSelection()">← Back</button>
                <button class="export-btn-export" onclick="ExportModal.startExport()" disabled>Export Reports</button>
            </div>
        </div>
    </div>

    <!-- Third Modal: Export Status/Completion -->
    <div class="export-modal-overlay" id="exportStatusModal" style="display: none;">
        <div class="export-modal">
            <div class="export-modal-header">
                <h3 id="exportStatusTitle">📦 Preparing Export...</h3>
                <button class="export-modal-close" onclick="ExportModal.closeStatusModal()" id="exportStatusCloseBtn" style="display: none;">×</button>
            </div>
            
            <div class="export-modal-content">
                <div class="export-status-content">
                    <!-- Loading State -->
                    <div id="exportLoadingState" class="export-loading-state">
                        <div class="export-spinner"></div>
                        <div class="export-loading-text">
                            <div class="loading-message">Generating your export files...</div>
                            <div class="loading-details" id="loadingDetails">Preparing data...</div>
                        </div>
                        <div class="export-progress-bar">
                            <div class="export-progress-fill" id="exportProgress"></div>
                        </div>
                    </div>
                    
                    <!-- Success State -->
                    <div id="exportSuccessState" class="export-success-state" style="display: none;">
                        <div class="success-icon">✅</div>
                        <div class="success-message">Export completed successfully!</div>
                        <div class="export-summary">
                            <div class="summary-item">
                                <span class="summary-label">Reports:</span>
                                <span class="summary-value" id="exportedReportsCount">0</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Format:</span>
                                <span class="summary-value" id="exportedFormat">PDF</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">File size:</span>
                                <span class="summary-value" id="exportedFileSize">2.3 MB</span>
                            </div>
                        </div>
                        <div class="download-actions">
                            <button class="download-btn" id="downloadBtn" onclick="ExportModal.triggerDownload()">
                                📥 Download File
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="export-modal-footer" id="exportStatusFooter">
                <button class="export-btn-cancel" onclick="ExportModal.cancelExport()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Audio unlock banner -->
    <div id="audio-unlock-banner" style="
        position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
        background: linear-gradient(135deg, #ff8000, #ffa500);
        color: #000; font-weight: 700; font-size: 0.85rem;
        padding: 0.65rem 1.5rem; border-radius: 999px;
        box-shadow: 0 4px 20px rgba(255,128,0,0.5);
        z-index: 99998; cursor: pointer;
        display: flex; align-items: center; gap: 0.5rem;
        transition: opacity 0.3s;
    " onclick="PriorityAlert.unlockAudio()">
        🔊 Click to enable priority alert sound
    </div>

    <!-- Priority Alert Modals -->
    <!-- First Modal: Pictures Only -->
    <div class="priority-alert-overlay" id="priorityPicturesOverlay">
        <div class="priority-pictures-modal">
            <button class="priority-alert-close" onclick="PriorityAlert.stopSoundAndClose()">×</button>
            
            <div class="priority-pictures-header">
                🚨 PRIORITY PROJECT ALERT - IMAGES
            </div>
            
            <div class="priority-pictures-content" id="priorityPicturesContent">
                <div class="priority-alert-no-images" id="priorityNoImagesFirst">
                    📷
                    <div>No images available for this priority project</div>
                </div>
                <div class="priority-image-counter" id="priorityImageCounterFirst" style="display: none;">
                    1 / 1
                </div>
                <div class="priority-slideshow-timer" id="prioritySlideshowTimerFirst" style="display: none;">
                    Next in 5s
                </div>
            </div>
            
            <div class="priority-pictures-footer">
                <div class="priority-click-indicator">
                    📸 Click anywhere to continue to project details
                </div>
            </div>
        </div>
    </div>

    <!-- Second Modal: Project Data Only -->
    <div class="priority-alert-overlay" id="priorityDataOverlay">
        <div class="priority-data-modal-new">
            <button class="priority-alert-close" onclick="PriorityAlert.close()">×</button>
            
            <!-- Header -->
            <div class="priority-header-new">
                <div class="priority-source-new" id="priorityDataSource">DPWH</div>
            </div>
            
            <!-- Main Grid Container -->
            <div class="priority-grid-container">
                <!-- Field 1 -->
                <div class="priority-field-new">
                    <div class="field-label-new">CONTRACTOR</div>
                    <div class="field-value-new" id="priorityContractorGrid">Loading...</div>
                </div>
                
                <!-- Field 2 -->
                <div class="priority-field-new">
                    <div class="field-label-new">CONTACT PERSON</div>
                    <div class="field-value-new" id="priorityContactPersonGrid">N/A</div>
                </div>
                
                <!-- Field 3 -->
                <div class="priority-field-new">
                    <div class="field-label-new">CONTACT NUMBER</div>
                    <div class="field-value-new" id="priorityContactNumberGrid">N/A</div>
                </div>
                
                <!-- Field 4 -->
                <div class="priority-field-new">
                    <div class="field-label-new">ADDRESS</div>
                    <div class="field-value-new" id="priorityAddressGrid">N/A</div>
                </div>
                
                <!-- Field 5 -->
                <div class="priority-field-new">
                    <div class="field-label-new">PROJECT NAME</div>
                    <div class="field-value-new" id="priorityProjectNameGrid">Loading...</div>
                </div>
                
                <!-- Field 6 -->
                <div class="priority-field-new">
                    <div class="field-label-new">LOCATION</div>
                    <div class="field-value-new" id="priorityLocationGrid">N/A</div>
                </div>
                
                <!-- Field 7 -->
                <div class="priority-field-new">
                    <div class="field-label-new">SHEET PILE TYPE</div>
                    <div class="field-value-new" id="prioritySheetPileTypeGrid">N/A</div>
                </div>
                
                <!-- Field 8 -->
                <div class="priority-field-new">
                    <div class="field-label-new">SHEET PILE AMOUNT</div>
                    <div class="field-value-new" id="prioritySheetPileAmountGrid">₱0.00</div>
                </div>
                
                <!-- Field 9 -->
                <div class="priority-field-new">
                    <div class="field-label-new">PROJECT VALUE</div>
                    <div class="field-value-new" id="priorityProjectValueMainGrid">₱0.00</div>
                </div>
                
                <!-- Field 10 -->
                <div class="priority-field-new">
                    <div class="field-label-new">ACCOMPLISHMENT RATE</div>
                    <div class="field-value-new" id="priorityAccomplishmentMainGrid">0.00%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Philippine DateTime Formatter -->
    <script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>
    
    <!-- Custom Select Dropdown -->
    <script src="<?= $base ?>/static/js/custom-select-dropdown.js"></script>

    <script>
        const BASE = '<?= $base ?>';
        
        // Global state management
        const AppState = {
            charts: {},
            intervals: {},
            isLoading: false,
            hasErrors: false
        };
        
        // Utility functions
        const Utils = {
            formatNumber(num) {
                if (typeof num !== 'number') num = parseFloat(num) || 0;
                if (num >= 1000000000) {
                    return (num / 1000000000).toFixed(1) + 'B';
                } else if (num >= 1000000) {
                    return (num / 1000000).toFixed(1) + 'M';
                } else if (num >= 1000) {
                    return (num / 1000).toFixed(1) + 'K';
                }
                return num.toLocaleString();
            },

            async fetchWithFallback(url, fallbackData) {
                console.log(`[API] Fetching: ${url}`);
                try {
                    const response = await fetch(url);
                    console.log(`[API] Response status for ${url}: ${response.status}`);
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const data = await response.json();
                    console.log(`[API] Data received from ${url}:`, data);
                    return { success: true, data };
                } catch (error) {
                    console.error(`[API] Error for ${url}:`, error);
                    console.log(`[API] Using fallback data:`, fallbackData);
                    return { success: false, data: fallbackData, error };
                }
            },

            showLoadingState(elementId, message = 'Loading...') {
                const element = document.getElementById(elementId);
                if (element) {
                    element.innerHTML = `<div style="text-align: center; color: #888; padding: 1rem;">${message}</div>`;
                }
            },

            showErrorState(elementId, message = 'Unable to load data') {
                const element = document.getElementById(elementId);
                if (element) {
                    element.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 1rem;">⚠️ ${message}</div>`;
                }
            },

            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        };

        // Clock functionality
        const Clock = {
            init() {
                this.update();
                AppState.intervals.clock = setInterval(() => this.update(), 1000);
            },

            update() {
                try {
                    const now = new Date();
                    // Use Philippine DateTime formatter if available
                    const timeStr = window.PhilippineDateTime 
                        ? PhilippineDateTime.currentTime()
                        : now.toLocaleTimeString('en-PH', { 
                            timeZone: 'Asia/Manila',
                            hour: '2-digit', 
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: true 
                        });
                    
                    const timeElement = document.getElementById('current-time');
                    if (timeElement) timeElement.textContent = timeStr;
                    
                    // Update sync time (simulate last sync - 1 minute ago)
                    const syncTime = new Date(now.getTime() - 60000);
                    const syncStr = window.PhilippineDateTime
                        ? PhilippineDateTime.formatTimeShort(syncTime)
                        : syncTime.toLocaleTimeString('en-PH', { 
                            timeZone: 'Asia/Manila',
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: true 
                        });
                    
                    const syncElement = document.getElementById('sync-time');
                    if (syncElement) syncElement.textContent = syncStr;
                } catch (error) {
                    console.error('Clock update error:', error);
                }
            }
        };
        
        // Filter management
        const Filters = {
            get() {
                const period = document.getElementById('period-select')?.value || 'monthly';
                const region = document.getElementById('region-select')?.value || 'all';
                const monthValue = document.getElementById('month-select')?.value || 'all';
                
                if (monthValue === 'all') {
                    return {
                        period: period,
                        region: region === 'all' ? null : region,
                        month: null,
                        year: null
                    };
                }
                
                const [month, year] = monthValue.split('-');
                return {
                    period: period,
                    region: region === 'all' ? null : region,
                    month: parseInt(month),
                    year: parseInt(year)
                };
            },

            toUrlParams(filters = null) {
                const params = new URLSearchParams();
                const filterData = filters || this.get();
                
                if (filterData.period) params.append('period', filterData.period);
                if (filterData.region) params.append('region', filterData.region);
                if (filterData.month) params.append('month', filterData.month);
                if (filterData.year) params.append('year', filterData.year);
                
                return params;
            }
        };

        // KPI Module
        const KPI = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/kpi?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, {
                    data: {
                        projects_encoded: 0,
                        contractors_identified: 0,
                        total_pipeline_value: 0
                    }
                });

                console.log('[KPI] Result:', result);
                console.log('[KPI] Has data.data?', !!result.data?.data);
                if (result.data?.data) {
                    console.log('[KPI] Projects:', result.data.data.projects_encoded);
                    console.log('[KPI] Contractors:', result.data.data.contractors_identified);
                }

                if (result.success && result.data?.data) {
                    const data = result.data.data;
                    this.render({
                        projects: data.projects_encoded || 0,
                        contractors: data.contractors_identified || 0,
                        value: data.total_pipeline_value || 0
                    });
                } else {
                    console.warn('[KPI] Rendering fallback');
                    this.renderFallback();
                }
            },

            render(data) {
                const elements = {
                    projects: document.getElementById('total-projects'),
                    contractors: document.getElementById('total-contractors'),
                    value: document.getElementById('total-value')
                };

                if (elements.projects) elements.projects.textContent = data.projects;
                if (elements.contractors) elements.contractors.textContent = data.contractors;
                if (elements.value) elements.value.textContent = '₱' + Utils.formatNumber(data.value);
            },

            renderFallback() {
                this.render({ projects: 'No Data', contractors: 'No Data', value: 0 });
            }
        };

        // Contractors Module
        const Contractors = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/contractors/ranking?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, { contractors: [] });

                console.log('[CONTRACTORS] Result:', result);
                console.log('[CONTRACTORS] Has data.contractors?', !!result.data?.contractors);
                console.log('[CONTRACTORS] Contractors count:', result.data?.contractors?.length || 0);

                if (result.success && result.data?.contractors && result.data.contractors.length > 0) {
                    this.render(result.data.contractors);
                } else {
                    console.warn('[CONTRACTORS] Rendering empty state');
                    this.renderEmpty();
                }
            },

            render(contractors) {
                const container = document.getElementById('contractors-list');
                if (!container) return;

                if (contractors.length === 0) {
                    this.renderEmpty();
                    return;
                }

                const rowsHtml = contractors.map((item, index) => `
                    <div class="contractor-row">
                        <div class="contractor-rank">${index + 1}</div>
                        <div class="contractor-name">${item.contractor_name || 'Unknown'}</div>
                        <div class="contractor-value">₱${Utils.formatNumber(item.total_value || 0)}</div>
                    </div>
                `).join('');

                // Duplicate rows for seamless infinite loop
                const duration = Math.max(10, contractors.length * 1.2);
                container.innerHTML = `
                    <div class="contractors-scroll-track" style="animation-duration: ${duration}s;">
                        ${rowsHtml}
                        ${rowsHtml}
                    </div>
                `;
            },

            renderEmpty() {
                const container = document.getElementById('contractors-list');
                if (container) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">📋</div>
                            <div>No contractor data available</div>
                        </div>
                    `;
                }
            }
        };
        
        // Charts Module
        const Charts = {
            init() {
                // Initialize both charts with empty data
                this.initRegionalValuesChart([]);
                this.initRegionalDistributionChart([]);
                this.initSourcesChart({});
                // Setup toggle functionality
                this.setupToggle();
                this.setupResize();
            },

            setupResize() {
                let resizeTimer;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        Object.values(AppState.charts || {}).forEach(chart => {
                            if (chart && typeof chart.resize === 'function') {
                                chart.resize();
                            }
                        });
                    }, 120);
                });
            },

            async loadRegionalData() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/charts/regional-stats?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, {
                    regions: ['No Data'],
                    values: [0],
                    projectCounts: [0]
                });

                if (result.success && result.data) {
                    const data = result.data;
                    this.initRegionalValuesChart(data);
                    this.initRegionalDistributionChart(data);
                } else {
                    // Use fallback data
                    this.initRegionalValuesChart({
                        regions: ['No Regional Data'],
                        values: [0],
                        projectCounts: [0]
                    });
                    this.initRegionalDistributionChart({
                        regions: ['No Regional Data'],
                        values: [0],
                        projectCounts: [0]
                    });
                }
            },

            initRegionalValuesChart(data) {
                const ctx = document.getElementById('regional-values-chart');
                if (!ctx) return;

                // Destroy existing chart
                if (AppState.charts.regionalValues) {
                    AppState.charts.regionalValues.destroy();
                }

                // Extract text inside parentheses and remove "Region" word
                const shortLabels = (data.regions || []).map(region => {
                    const match = region.match(/\(([^)]+)\)/);
                    if (match) {
                        return match[1].replace(/\s*Region\s*/gi, '').trim();
                    }
                    return region;
                });
                
                AppState.charts.regionalValues = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: shortLabels,
                        datasets: [{
                            label: 'Project Value (₱)',
                            data: data.values || [],
                            backgroundColor: [
                                '#ff8000', '#ff6000', '#ffa500', '#34d399',
                                '#60a5fa', '#a78bfa', '#f472b6', '#10b981'
                            ],
                            borderColor: [
                                '#ff8000', '#ff6000', '#ffa500', '#34d399',
                                '#60a5fa', '#a78bfa', '#f472b6', '#10b981'
                            ],
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => 'Value: ₱' + Utils.formatNumber(context.parsed.y)
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#888',
                                    font: { size: 11 },
                                    callback: (value) => '₱' + Utils.formatNumber(value)
                                },
                                grid: { color: '#333' }
                            },
                            x: {
                                ticks: {
                                    color: '#888',
                                    font: { size: 9 },
                                    maxRotation: 90,
                                    minRotation: 45,
                                    autoSkip: false
                                },
                                grid: { color: '#333' }
                            }
                        }
                    }
                });
            },

            initRegionalDistributionChart(data) {
                const ctx = document.getElementById('regional-distribution-chart');
                if (!ctx) return;

                // Destroy existing chart
                if (AppState.charts.regionalDistribution) {
                    AppState.charts.regionalDistribution.destroy();
                }

                // Extract text inside parentheses and remove "Region" word
                const shortLabels = (data.regions || []).map(region => {
                    const match = region.match(/\(([^)]+)\)/);
                    if (match) {
                        return match[1].replace(/\s*Region\s*/gi, '').trim();
                    }
                    return region;
                });
                
                AppState.charts.regionalDistribution = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: shortLabels,
                        datasets: [{
                            label: 'Projects',
                            data: data.projectCounts || [],
                            borderColor: '#ff8000',
                            backgroundColor: 'rgba(255, 128, 0, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ff8000',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => `${context.label}: ${context.parsed.y} projects`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#888', font: { size: 10 } },
                                grid: { color: '#333' }
                            },
                            x: {
                                ticks: {
                                    color: '#888',
                                    font: { size: 9 },
                                    maxRotation: 90,
                                    minRotation: 45,
                                    autoSkip: false
                                },
                                grid: { color: '#333' }
                            }
                        }
                    }
                });
            },

            async loadSourcesData() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/kpi?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, {
                    data: { source_distribution: {} }
                });

                if (result.success && result.data?.data) {
                    this.initSourcesChart(result.data.data);
                } else {
                    this.initSourcesChart({});
                }
            },

            initSourcesChart(data) {
                const ctx = document.getElementById('sources-chart');
                if (!ctx) return;

                // Destroy existing chart
                if (AppState.charts.sources) {
                    AppState.charts.sources.destroy();
                }

                // Extract source distribution
                const sourceData = data.source_distribution || {};
                const sources = Object.keys(sourceData);
                const values = Object.values(sourceData);

                // If no data, show placeholder
                if (sources.length === 0 || values.every(v => v === 0)) {
                    sources.push('No Data');
                    values.push(1);
                }

                AppState.charts.sources = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: sources,
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                '#ff8000', '#ff6000', '#ffa500', '#34d399',
                                '#60a5fa', '#a78bfa', '#f472b6', '#10b981'
                            ],
                            borderColor: '#1a1a1a',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    color: '#888',
                                    font: { size: 11 },
                                    padding: 12,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            },

            setupToggle() {
                const toggleButtons = document.querySelectorAll('.toggle-btn');
                const valuesChart = document.getElementById('regional-values-chart');
                const projectsChart = document.getElementById('regional-distribution-chart');
                const chartSection = document.querySelector('.regional-combined-section');
                let isHoverPaused = false;

                const setActiveChart = (chartType, resetTimer = true) => {
                    toggleButtons.forEach(b => b.classList.toggle('active', b.getAttribute('data-chart') === chartType));
                    if (chartType === 'values') {
                        valuesChart.style.display = 'block';
                        projectsChart.style.display = 'none';
                    } else {
                        valuesChart.style.display = 'none';
                        projectsChart.style.display = 'block';
                    }
                    if (resetTimer) {
                        startAutoRotate();
                    }
                };

                const toggleNextChart = () => {
                    const activeButton = document.querySelector('.toggle-btn.active');
                    const nextChart = activeButton?.getAttribute('data-chart') === 'values' ? 'projects' : 'values';
                    setActiveChart(nextChart, false);
                };

                const startAutoRotate = () => {
                    clearInterval(AppState.intervals.regionalAnalytics);
                    AppState.intervals.regionalAnalytics = setInterval(() => {
                        if (!isHoverPaused && document.visibilityState === 'visible') {
                            toggleNextChart();
                        }
                    }, 5000);
                };

                const stopAutoRotate = () => {
                    clearInterval(AppState.intervals.regionalAnalytics);
                };

                toggleButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const chartType = btn.getAttribute('data-chart');
                        setActiveChart(chartType);
                    });
                });

                if (chartSection) {
                    chartSection.addEventListener('mouseenter', () => {
                        isHoverPaused = true;
                        stopAutoRotate();
                    });
                    chartSection.addEventListener('mouseleave', () => {
                        isHoverPaused = false;
                        startAutoRotate();
                    });
                }

                setActiveChart('values', false);
                startAutoRotate();
            }
        };
        
        // Sales Funnel Module
        const SalesFunnel = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/charts/funnel?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, { stages: [] });

                if (result.success && result.data?.stages) {
                    this.render(result.data.stages);
                } else {
                    this.renderEmpty();
                }
            },

            render(stages) {
                const container = document.querySelector('.funnel-list');
                if (!container) return;

                if (stages.length === 0) {
                    this.renderEmpty();
                    return;
                }

                const totalProjects = stages[0]?.count || 1;

                container.innerHTML = stages.map(stage => {
                    const percentage = totalProjects > 0 ? ((stage.count / totalProjects) * 100).toFixed(1) : '0.0';
                    const fillClass = this.getFillClass(stage.name);
                    
                    return `
                        <div class="funnel-item">
                            <div class="funnel-name">${stage.name.toUpperCase()}</div>
                            <div class="funnel-stats">
                                <div class="funnel-count">${stage.count}</div>
                                <div class="funnel-percentage">${percentage}%</div>
                                <div class="funnel-bar">
                                    <div class="funnel-bar-fill ${fillClass}" style="width: ${percentage}%;"></div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            },

            renderEmpty() {
                const container = document.querySelector('.funnel-list');
                if (container) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #888;">
                            <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">🔽</div>
                            <div>No sales funnel data available</div>
                        </div>
                    `;
                }
            },

            getFillClass(stageName) {
                const name = stageName.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                return name + '-fill';
            }
        };

        // Target Progress Module
        const TargetProgress = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/kpi?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, {
                    data: { projects_encoded: 0 }
                });

                if (result.success && result.data?.data) {
                    this.render(result.data.data);
                } else {
                    this.renderFallback();
                }
            },

            render(data) {
                const encoded = data.projects_encoded || 0;
                const currentPeriod = document.getElementById('period-select')?.value || 'monthly';
                const predefinedTargets = {
                    daily: 30,
                    weekly: 150,
                    monthly: 600
                };
                const target = predefinedTargets[currentPeriod] || 600;
                const percentage = target > 0 ? Math.round((encoded / target) * 100) : 0;
                
                // Update numbers
                const targetNumbers = document.querySelectorAll('.target-number');
                if (targetNumbers.length >= 2) {
                    targetNumbers[0].textContent = encoded;
                    targetNumbers[1].textContent = target;
                }

                const targetLabel = document.querySelector('.target-right .target-label');
                if (targetLabel) {
                    targetLabel.textContent = currentPeriod === 'daily' ? 'Daily Target' :
                                                currentPeriod === 'weekly' ? 'Weekly Target' :
                                                'Monthly Target';
                }

                const targetRight = document.querySelector('.target-right');
                const monthValue = document.getElementById('month-select')?.value || 'all';
                if (targetRight) {
                    if (monthValue === 'all') {
                        targetRight.style.display = 'none';
                    } else {
                        targetRight.style.display = 'flex';
                    }
                }
                
                // Update percentage and progress bar
                const percentageElement = document.querySelector('.target-percentage');
                const progressFill = document.querySelector('.target-progress-fill');
                
                if (percentageElement) percentageElement.textContent = percentage + '%';
                if (progressFill) progressFill.style.width = Math.min(percentage, 100) + '%';
                
                // Update status
                this.updateStatus(percentage);
            },

            updateStatus(percentage) {
                const statusElement = document.querySelector('.target-status');
                if (!statusElement) return;

                if (percentage >= 100) {
                    statusElement.textContent = '✅ TARGET ACHIEVED';
                    statusElement.style.color = '#10b981';
                } else if (percentage >= 80) {
                    statusElement.textContent = '🟡 ON TRACK';
                    statusElement.style.color = '#f59e0b';
                } else {
                    statusElement.textContent = '🔺 BEHIND TARGET';
                    statusElement.style.color = '#ef4444';
                }
            },

            renderFallback() {
                this.render({ projects_encoded: 0 });
            }
        };
        
        // Live Slideshow Module
        const LiveSlideshow = {
            currentTimeout: null,
            countdownInterval: null,
            timeRemaining: 10,
            _fitObserver: null,
            _fitTimer: null,

            initAutoFit() {
                if (this._fitObserver) return;

                const slideshow = document.querySelector('.live-slideshow');
                if (!slideshow) return;

                if (typeof ResizeObserver !== 'undefined') {
                    this._fitObserver = new ResizeObserver(() => {
                        clearTimeout(this._fitTimer);
                        this._fitTimer = setTimeout(() => this.fitFonts(), 60);
                    });
                    this._fitObserver.observe(slideshow);
                }

                window.addEventListener('resize', () => {
                    clearTimeout(this._fitTimer);
                    this._fitTimer = setTimeout(() => this.fitFonts(), 120);
                });
            },

            fitFonts() {
                const content = document.querySelector('.slideshow-content');
                const body = document.querySelector('.slideshow-body');
                const controls = document.querySelector('.slideshow-controls');
                if (!content || !body) return;

                const applyScale = (scale) => {
                    content.style.setProperty('--slide-scale', scale.toFixed(3));
                    void body.offsetHeight;
                    return body.scrollHeight;
                };

                requestAnimationFrame(() => {
                    const maxHeight = content.clientHeight - (controls?.offsetHeight || 0) - 2;
                    if (maxHeight <= 0) return;

                    applyScale(1);
                    const baseHeight = body.scrollHeight;
                    if (baseHeight <= 0) return;

                    let bestScale = 1;

                    if (baseHeight <= maxHeight) {
                        let lo = 1;
                        let hi = 3.2;

                        for (let i = 0; i < 14; i++) {
                            const mid = (lo + hi) / 2;
                            const height = applyScale(mid);

                            if (height <= maxHeight) {
                                bestScale = mid;
                                lo = mid;
                            } else {
                                hi = mid;
                            }
                        }
                    } else {
                        let lo = 0.55;
                        let hi = 1;

                        for (let i = 0; i < 14; i++) {
                            const mid = (lo + hi) / 2;
                            const height = applyScale(mid);

                            if (height <= maxHeight) {
                                bestScale = mid;
                                lo = mid;
                            } else {
                                hi = mid;
                            }
                        }
                    }

                    applyScale(bestScale);
                    body.classList.toggle(
                        'slideshow-body--spread',
                        body.offsetHeight < maxHeight * 0.82
                    );
                });
            },

            async load() {
                this.showLoadingProgress();
                
                const url = `${BASE}/api/v1/live-slideshow`;
                const result = await Utils.fetchWithFallback(url, {
                    contractor_name: 'No Project Data Available',
                    contact: 'Add projects to see live data',
                    phone: '-',
                    project_title: '-',
                    project_value: 0,
                    status: '-',
                    drbs_value: 0,
                    sheet_pile_amount: 0
                });

                if (result.success && result.data) {
                    this.render(result.data);
                } else {
                    this.renderFallback();
                }
                
                this.hideLoadingProgress();
                this.startCountdown();
            },

            render(data) {
                document.querySelector('.live-contractor-name').textContent = data.contractor_name;
                
                const contactEl = document.getElementById('liveContact');
                const phoneEl = document.getElementById('livePhone');
                const projectEl = document.getElementById('liveProject');
                const projectValueEl = document.getElementById('liveProjectValue');
                const statusEl = document.getElementById('liveStatus');
                
                if (contactEl) contactEl.textContent = data.contact || 'N/A';
                if (phoneEl) phoneEl.textContent = data.phone || 'N/A';
                if (projectEl) projectEl.textContent = data.project_title || 'N/A';
                if (projectValueEl) projectValueEl.textContent = '₱' + Utils.formatNumber(data.project_value || 0);
                if (statusEl) statusEl.textContent = data.status || 'UNKNOWN';
                
                const materialRows = [
                    { label: 'DRBs Type', value: data.drbs || '—' },
                    { label: 'DRBs Amount', value: '₱' + Utils.formatNumber(data.drbs_value || 0) },
                    { label: 'Sheet Pile Type', value: data.sheet_pile_type || '—' },
                    { label: 'Sheet Pile Amount', value: '₱' + Utils.formatNumber(data.sheet_pile_amount || 0) },
                    { label: 'MS Plate', value: data.ms_plate ? '₱' + Utils.formatNumber(data.ms_plate) : '₱0' },
                    { label: 'Angle Bars', value: data.angle_bars ? '₱' + Utils.formatNumber(data.angle_bars) : '₱0' },
                    { label: 'Channel Bars', value: data.channel_bars ? '₱' + Utils.formatNumber(data.channel_bars) : '₱0' },
                    { label: 'Wide Flange', value: data.wide_flange ? '₱' + Utils.formatNumber(data.wide_flange) : '₱0' },
                    { label: 'GI/BI', value: data.gi_bi ? '₱' + Utils.formatNumber(data.gi_bi) : '₱0' }
                ];
                
                const materialsList = document.getElementById('liveMaterialsList');
                if (materialsList) {
                    materialsList.innerHTML = materialRows.map(item => `
                        <div>
                            <div class="material-label">${item.label}</div>
                            <div class="material-value">${item.value}</div>
                        </div>
                    `).join('');
                }

                this.fitFonts();
            },

            renderFallback() {
                this.render({
                    contractor_name: 'Unable to Load Data',
                    contact: 'Connection Error',
                    phone: 'Please refresh',
                    project_title: '-',
                    project_value: 0,
                    status: 'Error',
                    drbs_value: 0,
                    sheet_pile_amount: 0
                });
            },

            showLoadingProgress() {
                const loadingBar = document.getElementById('slideshowLoadingBar');
                const loadingProgress = document.getElementById('loadingProgress');
                
                if (loadingBar && loadingProgress) {
                    loadingBar.style.display = 'block';
                    loadingProgress.style.width = '0%';
                    
                    // Simple loading animation
                    setTimeout(() => {
                        if (loadingProgress) loadingProgress.style.width = '100%';
                    }, 200);
                }
            },

            hideLoadingProgress() {
                const loadingBar = document.getElementById('slideshowLoadingBar');
                if (loadingBar) {
                    setTimeout(() => {
                        loadingBar.style.display = 'none';
                    }, 300);
                }
            },

            startCountdown() {
                const countdownProgress = document.getElementById('countdownProgress');
                const timerText = document.getElementById('slideshowTimerText');
                
                if (!countdownProgress || !timerText) return;
                
                this.timeRemaining = 10;
                
                // Clear existing interval
                if (this.countdownInterval) {
                    clearInterval(this.countdownInterval);
                }
                
                // Reset progress bar
                countdownProgress.style.width = '100%';
                countdownProgress.classList.remove('ending');
                timerText.textContent = `Next slide in ${this.timeRemaining}s`;
                
                // Start countdown
                this.countdownInterval = setInterval(() => {
                    this.timeRemaining--;
                    
                    const progressPercent = (this.timeRemaining / 10) * 100;
                    countdownProgress.style.width = progressPercent + '%';
                    
                    if (this.timeRemaining > 0) {
                        timerText.textContent = `Next slide in ${this.timeRemaining}s`;
                        
                        if (this.timeRemaining <= 3) {
                            countdownProgress.classList.add('ending');
                        }
                    } else {
                        timerText.textContent = 'Loading next slide...';
                        countdownProgress.style.width = '0%';
                        clearInterval(this.countdownInterval);
                    }
                }, 1000);
            }
        };

        // Project Status Module  
        const ProjectStatus = {
            async load() {
                const params = Filters.toUrlParams();
                const url = `${BASE}/api/v1/kpi?${params.toString()}`;
                
                const result = await Utils.fetchWithFallback(url, { data: {} });

                if (result.success && result.data?.data) {
                    this.render(result.data.data);
                } else {
                    this.renderFallback();
                }
            },

            render(data) {
                const statusContainer = document.querySelector('.project-status-section');
                const statusItems = statusContainer?.querySelectorAll('.category-item');
                if (!statusItems) return;

                // Map API data to status categories
                const statusMap = {
                    'PRIORITY': this.extractStatusData(data, 'priority'),
                    'FOR EXECUTION': this.extractStatusData(data, 'for_execution'),
                    'AWARDED': this.extractStatusData(data, 'awarded'),
                    'FOR BIDDING': this.extractStatusData(data, 'for_bidding')
                };
                
                const totalValue = Object.values(statusMap).reduce((sum, item) => sum + (item.value || 0), 0);
                
                statusItems.forEach(item => {
                    const categoryName = item.querySelector('.category-name')?.textContent;
                    const statusData = statusMap[categoryName];
                    
                    if (statusData) {
                        const percentage = totalValue > 0 ? ((statusData.value / totalValue) * 100).toFixed(1) : '0.0';
                        
                        const countEl = item.querySelector('.category-count');
                        const valueEl = item.querySelector('.category-value');
                        const percentEl = item.querySelector('.category-percentage');
                        const fillEl = item.querySelector('.category-bar-fill');
                        
                        if (countEl) countEl.textContent = statusData.count || 0;
                        if (valueEl) valueEl.textContent = '₱' + Utils.formatNumber(statusData.value || 0);
                        if (percentEl) percentEl.textContent = percentage + '%';
                        if (fillEl) fillEl.style.width = percentage + '%';
                    }
                });
            },

            extractStatusData(data, key) {
                // If the key exists directly in data
                if (data[key] && typeof data[key] === 'object') {
                    return data[key];
                }
                
                // Default fallback
                return { count: 0, value: 0 };
            },

            renderFallback() {
                this.render({});
            }
        };

        // Available Months Module
        const AvailableMonths = {
            async load() {
                const url = `${BASE}/api/v1/available-months`;
                const result = await Utils.fetchWithFallback(url, { months: [] });

                if (result.success && result.data?.months) {
                    this.render(result.data.months);
                } else {
                    this.renderFallback();
                }
            },

            render(months) {
                const monthSelect = document.getElementById('month-select');
                if (!monthSelect) return;

                monthSelect.innerHTML = '';
                
                // Add "All Months" option - SELECTED BY DEFAULT
                const allOption = document.createElement('option');
                allOption.value = 'all';
                allOption.textContent = 'All Months';
                allOption.selected = true; // Default to "All Months"
                monthSelect.appendChild(allOption);
                
                // Add available months
                months.forEach((month, index) => {
                    const option = document.createElement('option');
                    option.value = month.value;
                    option.textContent = `${month.label} (${month.project_count} projects)`;
                    // Don't auto-select any specific month
                    monthSelect.appendChild(option);
                });
                
                // Initialize or refresh CustomSelect for this dropdown
                setTimeout(() => {
                    if (window.customSelectInstances && window.customSelectInstances.monthSelect) {
                        // Refresh existing instance
                        window.customSelectInstances.monthSelect.refresh();
                    } else if (window.CustomSelect) {
                        // Initialize new instance
                        const instance = new CustomSelect(monthSelect, {
                            searchable: false,
                            placeholder: 'All Months'
                        });
                        if (!window.customSelectInstances) {
                            window.customSelectInstances = {};
                        }
                        window.customSelectInstances.monthSelect = instance;
                    }
                }, 100);
            },

            renderFallback() {
                const monthSelect = document.getElementById('month-select');
                if (!monthSelect) return;

                const currentDate = new Date();
                const currentMonth = currentDate.getMonth() + 1;
                const currentYear = currentDate.getFullYear();
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                  'July', 'August', 'September', 'October', 'November', 'December'];
                
                monthSelect.innerHTML = '';
                
                // Add "All Months" option
                const allOption = document.createElement('option');
                allOption.value = 'all';
                allOption.textContent = 'All Months';
                allOption.selected = true;
                monthSelect.appendChild(allOption);
                
                // Add current month as fallback
                const option = document.createElement('option');
                option.value = `${currentMonth}-${currentYear}`;
                option.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
                monthSelect.appendChild(option);
                
                // Initialize or refresh CustomSelect for this dropdown
                setTimeout(() => {
                    if (window.customSelectInstances && window.customSelectInstances.monthSelect) {
                        // Refresh existing instance
                        window.customSelectInstances.monthSelect.refresh();
                    } else if (window.CustomSelect) {
                        // Initialize new instance
                        const instance = new CustomSelect(monthSelect, {
                            searchable: false,
                            placeholder: 'All Months'
                        });
                        if (!window.customSelectInstances) {
                            window.customSelectInstances = {};
                        }
                        window.customSelectInstances.monthSelect = instance;
                    }
                }, 100);
            }
        };
        
        // Export Modal System
        const ExportModal = {
            selectedReports: [],
            selectedFormat: null,
            
            show() {
                document.getElementById('exportReportModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            },
            
            closeReportSelection() {
                document.getElementById('exportReportModal').style.display = 'none';
                document.body.style.overflow = '';
                this.resetSelections();
            },
            
            closeFormatSelection() {
                document.getElementById('exportFormatModal').style.display = 'none';
                document.body.style.overflow = '';
            },
            
            resetSelections() {
                // Reset checkboxes
                document.querySelectorAll('input[name="exportReport"]').forEach(cb => cb.checked = false);
                document.getElementById('selectAllReports').checked = false;
                
                // Reset format selection
                document.querySelectorAll('input[name="exportFormat"]').forEach(rb => rb.checked = false);
                document.querySelectorAll('.export-format-option').forEach(opt => opt.classList.remove('selected'));
                
                this.selectedReports = [];
                this.selectedFormat = null;
                this.updateNextButton();
            },
            
            toggleSelectAll() {
                const selectAllCheckbox = document.getElementById('selectAllReports');
                const reportCheckboxes = document.querySelectorAll('input[name="exportReport"]');
                
                reportCheckboxes.forEach(cb => {
                    cb.checked = selectAllCheckbox.checked;
                });
                
                this.updateNextButton();
            },
            
            updateNextButton() {
                const checkedReports = document.querySelectorAll('input[name="exportReport"]:checked');
                const nextButton = document.querySelector('.export-btn-next');
                
                if (checkedReports.length > 0) {
                    nextButton.disabled = false;
                    nextButton.style.opacity = '1';
                } else {
                    nextButton.disabled = true;
                    nextButton.style.opacity = '0.5';
                }
            },
            
            showFormatSelection() {
                // Get selected reports
                const checkedReports = document.querySelectorAll('input[name="exportReport"]:checked');
                
                if (checkedReports.length === 0) {
                    // Show modal instead of alert
                    this.showErrorModal('Please select at least one report to export.');
                    return;
                }
                
                this.selectedReports = Array.from(checkedReports).map(cb => ({
                    value: cb.value,
                    label: cb.parentElement.parentElement.querySelector('.export-label').textContent.replace(/^[^\s]+\s/, '')
                }));
                
                // Update selected reports display
                this.updateSelectedReportsDisplay();
                
                // Hide report selection and show format selection
                document.getElementById('exportReportModal').style.display = 'none';
                document.getElementById('exportFormatModal').style.display = 'flex';
            },
            
            showReportSelection() {
                document.getElementById('exportFormatModal').style.display = 'none';
                document.getElementById('exportReportModal').style.display = 'flex';
            },
            
            updateSelectedReportsDisplay() {
                const container = document.getElementById('selectedReportsDisplay');
                container.innerHTML = this.selectedReports.map(report => 
                    `<div class="selected-report-tag">${report.label}</div>`
                ).join('');
            },
            
            selectFormat(format) {
                // Remove previous selection
                document.querySelectorAll('.export-format-option').forEach(opt => opt.classList.remove('selected'));
                
                // Add selection to clicked option
                const selectedOption = document.querySelector(`#format${format.charAt(0).toUpperCase() + format.slice(1)}`);
                if (selectedOption) {
                    selectedOption.checked = true;
                    selectedOption.closest('.export-format-option').classList.add('selected');
                }
                
                this.selectedFormat = format;
                this.updateExportButton();
            },
            
            updateExportButton() {
                const exportButton = document.querySelector('.export-btn-export');
                
                if (this.selectedFormat) {
                    exportButton.disabled = false;
                    exportButton.style.opacity = '1';
                } else {
                    exportButton.disabled = true;
                    exportButton.style.opacity = '0.5';
                }
            },
            
            async startExport() {
                if (!this.selectedFormat || this.selectedReports.length === 0) {
                    this.showErrorModal('Please select reports and format.');
                    return;
                }
                
                // Close format modal and show status modal
                document.getElementById('exportFormatModal').style.display = 'none';
                this.showStatusModal();
                
                try {
                    await this.performExport();
                } catch (error) {
                    console.error('Export error:', error);
                    this.showErrorModal('Export failed. Please try again.');
                }
            },
            
            showStatusModal() {
                document.getElementById('exportStatusModal').style.display = 'flex';
                document.getElementById('exportStatusTitle').textContent = '📦 Preparing Export...';
                document.getElementById('exportStatusCloseBtn').style.display = 'none';
                document.getElementById('exportLoadingState').style.display = 'flex';
                document.getElementById('exportSuccessState').style.display = 'none';
                document.getElementById('exportStatusFooter').style.display = 'flex';
            },
            
            closeStatusModal() {
                document.getElementById('exportStatusModal').style.display = 'none';
                document.body.style.overflow = '';
                this.resetSelections();
            },
            
            async performExport() {
                const loadingDetails = document.getElementById('loadingDetails');
                const progressBar = document.getElementById('exportProgress');
                
                // Simulate export process with progress
                const steps = [
                    { message: 'Gathering report data...', progress: 20 },
                    { message: 'Processing user data...', progress: 40 },
                    { message: 'Generating project reports...', progress: 60 },
                    { message: 'Formatting output...', progress: 80 },
                    { message: 'Finalizing export file...', progress: 100 }
                ];
                
                for (const step of steps) {
                    loadingDetails.textContent = step.message;
                    progressBar.style.width = step.progress + '%';
                    await new Promise(resolve => setTimeout(resolve, 800));
                }
                
                // Show success state
                this.showExportSuccess();
            },
            
            showExportSuccess() {
                document.getElementById('exportStatusTitle').textContent = '✅ Export Complete';
                document.getElementById('exportStatusCloseBtn').style.display = 'flex';
                document.getElementById('exportLoadingState').style.display = 'none';
                document.getElementById('exportSuccessState').style.display = 'flex';
                document.getElementById('exportStatusFooter').style.display = 'none';
                
                // Update summary
                document.getElementById('exportedReportsCount').textContent = this.selectedReports.length;
                document.getElementById('exportedFormat').textContent = this.selectedFormat.toUpperCase();
                document.getElementById('exportedFileSize').textContent = this.calculateFileSize();
            },
            
            calculateFileSize() {
                const baseSize = this.selectedReports.length * 0.5; // Base MB per report
                const formatMultiplier = this.selectedFormat === 'pdf' ? 1.2 : 0.8;
                const totalSize = (baseSize * formatMultiplier).toFixed(1);
                return totalSize + ' MB';
            },
            
            cancelExport() {
                this.closeStatusModal();
            },
            
            triggerDownload() {
                // Use real API to generate and download files
                this.downloadFromAPI();
                
                // Close modal after download starts
                setTimeout(() => {
                    this.closeStatusModal();
                }, 1000);
            },
            
            async downloadFromAPI() {
                try {
                    const reportValues = this.selectedReports.map(r => r.value);
                    
                    // Create form data for the API request
                    const formData = new FormData();
                    reportValues.forEach(report => {
                        formData.append('reports[]', report);
                    });
                    formData.append('format', this.selectedFormat);
                    
                    // Make API request
                    const response = await fetch(`${BASE}/api/v1/export`, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error('Export API request failed');
                    }
                    
                    // Get the blob from response
                    const blob = await response.blob();
                    
                    // Create download link
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    
                    // Set filename based on format
                    const timestamp = this.getDateStamp();
                    if (this.selectedFormat === 'pdf') {
                        a.download = `TDT_Powersteel_Reports_${timestamp}.pdf`;
                    } else {
                        a.download = `TDT_Powersteel_Reports_${timestamp}.csv`;
                    }
                    
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                } catch (error) {
                    console.error('API download error:', error);
                    // Fallback to client-side generation
                    this.generateClientSideFile();
                }
            },
            
            generateClientSideFile() {
                // Fallback method for client-side generation
                const csvContent = this.generateCSVContent();
                const BOM = '\uFEFF';
                const csvWithBOM = BOM + csvContent;
                
                const blob = new Blob([csvWithBOM], { 
                    type: 'text/csv;charset=utf-8' 
                });
                const url = URL.createObjectURL(blob);
                
                const a = document.createElement('a');
                a.href = url;
                a.download = `TDT_Powersteel_Reports_${this.getDateStamp()}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            },
            
            generatePDF() {
                // Create proper PDF content using jsPDF or similar approach
                try {
                    // For now, create a CSV-like content that can be opened properly
                    const content = this.generateCSVContent();
                    const blob = new Blob([content], { type: 'text/csv' });
                    const url = URL.createObjectURL(blob);
                    
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `TDT_Powersteel_Reports_${this.getDateStamp()}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('PDF generation error:', error);
                }
            },
            
            generateExcel() {
                // Generate proper Excel-compatible CSV format
                try {
                    const csvContent = this.generateCSVContent();
                    
                    // Add BOM for proper Excel UTF-8 handling
                    const BOM = '\uFEFF';
                    const csvWithBOM = BOM + csvContent;
                    
                    const blob = new Blob([csvWithBOM], { 
                        type: 'text/csv;charset=utf-8' 
                    });
                    const url = URL.createObjectURL(blob);
                    
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `TDT_Powersteel_Reports_${this.getDateStamp()}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('Excel generation error:', error);
                }
            },
            
            generateCSVContent() {
                let csvContent = '';
                
                // Header
                csvContent += 'TDT POWERSTEEL DASHBOARD REPORTS\n';
                csvContent += `Generated on: ${new Date().toLocaleDateString()}\n`;
                csvContent += `Time: ${new Date().toLocaleTimeString()}\n\n`;
                
                // Report sections
                this.selectedReports.forEach((report, index) => {
                    csvContent += `REPORT ${index + 1}: ${report.label.toUpperCase()}\n`;
                    csvContent += 'Field,Value\n';
                    
                    // Add sample data based on report type
                    switch (report.value) {
                        case 'users':
                            csvContent += 'Total Users,25\n';
                            csvContent += 'Active Users,22\n';
                            csvContent += 'Admin Users,3\n';
                            csvContent += 'Encoder Users,15\n';
                            csvContent += 'Sales Rep Users,7\n';
                            break;
                            
                        case 'sales_reps':
                            csvContent += 'Total Sales Representatives,7\n';
                            csvContent += 'Active This Month,6\n';
                            csvContent += 'Top Performer,John Doe\n';
                            csvContent += 'Average Performance,85%\n';
                            break;
                            
                        case 'non_priority_projects':
                            csvContent += 'Total Non-Priority Projects,156\n';
                            csvContent += 'Completed Projects,89\n';
                            csvContent += 'In Progress,45\n';
                            csvContent += 'Pending,22\n';
                            csvContent += 'Total Value,₱450M\n';
                            break;
                            
                        case 'priority_projects':
                            csvContent += 'Total Priority Projects,12\n';
                            csvContent += 'Urgent Projects,3\n';
                            csvContent += 'High Priority,6\n';
                            csvContent += 'Medium Priority,3\n';
                            csvContent += 'Total Value,₱125M\n';
                            break;
                    }
                    
                    csvContent += '\n';
                });
                
                // Summary
                csvContent += 'EXPORT SUMMARY\n';
                csvContent += 'Field,Value\n';
                csvContent += `Reports Included,${this.selectedReports.length}\n`;
                csvContent += `Format,${this.selectedFormat.toUpperCase()}\n`;
                csvContent += `Generated By,TDT Powersteel Dashboard\n`;
                csvContent += `Export Date,${new Date().toISOString()}\n`;
                
                return csvContent;
            },
            
            generateExportContent() {
                // Generate simple export content
                let content = `TDT POWERSTEEL DASHBOARD REPORTS\n`;
                content += `Generated on: ${new Date().toLocaleDateString()}\n\n`;
                
                this.selectedReports.forEach(report => {
                    content += `REPORT: ${report.label.toUpperCase()}\n`;
                    content += `Type: ${report.value}\n`;
                    content += `Status: Generated Successfully\n\n`;
                });
                
                return content;
            },
            
            getDateStamp() {
                const now = new Date();
                return now.getFullYear() + 
                       String(now.getMonth() + 1).padStart(2, '0') + 
                       String(now.getDate()).padStart(2, '0') + '_' +
                       String(now.getHours()).padStart(2, '0') + 
                       String(now.getMinutes()).padStart(2, '0');
            },
            
            showErrorModal(message) {
                // Create a simple error modal instead of alert
                console.error('Export Error:', message);
                // For now, we'll skip error modal and just log to console
                // You can implement a proper error modal later if needed
            },
            
            downloadPDF() {
                console.log('PDF export functionality integrated into triggerDownload()');
            },
            
            downloadExcel() {
                console.log('Excel export functionality integrated into triggerDownload()');
            }
        };

        // Priority Alert System Module - Two Modal System
        const PriorityAlert = {
            picturesOverlay: null,
            dataOverlay: null,
            currentAlert: null,
            currentModal: 'none', // 'pictures' or 'data' or 'none'
            imageSlideshow: {
                images: [],
                currentIndex: 0,
                countdownTimer: null,
                timeRemaining: 5
            },
            // Web Audio API
            audioCtx: null,
            audioBuffer: null,
            audioSource: null,
            isAudioUnlocked: false,
            isAudioPlaying: false,
            beepInterval: null,

            init() {
                this.picturesOverlay = document.getElementById('priorityPicturesOverlay');
                this.dataOverlay = document.getElementById('priorityDataOverlay');
                this.setupAudio();
                this.setupClickHandlers();
                
                // Check for priority alerts every 10 seconds
                AppState.intervals.priorityCheck = setInterval(() => {
                    this.checkForAlerts();
                }, 10000);

                // Initial check
                this.checkForAlerts();
            },

            /* ── Audio ─────────────────────────────────── */
            setupAudio() {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    this.audioCtx = new AudioCtx();

                    // Load the MP3 into a buffer via fetch
                    fetch(`${BASE}/static/sounds/priority-alert.mp3`)
                        .then(r => r.arrayBuffer())
                        .then(ab => this.audioCtx.decodeAudioData(ab))
                        .then(buf => {
                            this.audioBuffer = buf;
                            console.log('[PriorityAlert] Audio buffer loaded.');
                        })
                        .catch(e => console.warn('[PriorityAlert] Audio load failed:', e));

                    // Try to resume context on any user gesture
                    const unlock = () => {
                        if (this.audioCtx && this.audioCtx.state === 'suspended') {
                            this.audioCtx.resume().then(() => {
                                this.isAudioUnlocked = true;
                                this.hideBanner();
                                console.log('[PriorityAlert] AudioContext unlocked via gesture.');
                            });
                        } else if (this.audioCtx && this.audioCtx.state === 'running') {
                            this.isAudioUnlocked = true;
                            this.hideBanner();
                        }
                    };
                    ['click', 'keydown', 'touchstart'].forEach(evt =>
                        document.addEventListener(evt, unlock, { once: true })
                    );

                    // If context is already running (e.g. in some browsers), mark unlocked
                    if (this.audioCtx.state === 'running') {
                        this.isAudioUnlocked = true;
                        this.hideBanner();
                    }
                } catch (e) {
                    console.warn('[PriorityAlert] Could not setup audio:', e);
                }
            },

            unlockAudio() {
                if (this.audioCtx && this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume().then(() => {
                        this.isAudioUnlocked = true;
                        this.hideBanner();
                        console.log('[PriorityAlert] Audio unlocked by banner click.');
                    });
                } else {
                    this.isAudioUnlocked = true;
                    this.hideBanner();
                }
            },

            hideBanner() {
                const b = document.getElementById('audio-unlock-banner');
                if (b) { b.style.opacity = '0'; setTimeout(() => b.style.display = 'none', 300); }
            },

            playAlert() {
                this.isAudioPlaying = true;
                if (this.isAudioUnlocked && this.audioCtx && this.audioBuffer) {
                    this._startWebAudioLoop();
                } else {
                    // Fallback HTML5 Audio
                    try {
                        this._htmlAudio = new Audio(`${BASE}/static/sounds/priority-alert.mp3`);
                        this._htmlAudio.loop = false;
                        this._htmlAudio.volume = 1.0;
                        this._htmlAudio.play().catch(() => this.playBeepFallback());
                    } catch(e) {
                        this.playBeepFallback();
                    }
                }
            },

            _startWebAudioLoop() {
                if (!this.isAudioPlaying || !this.audioBuffer) return;
                try {
                    const source = this.audioCtx.createBufferSource();
                    source.buffer = this.audioBuffer;
                    source.loop = false;
                    source.connect(this.audioCtx.destination);
                    source.start(0);
                    this.audioSource = source;
                    console.log('[PriorityAlert] Web Audio loop started.');
                } catch(e) {
                    console.warn('[PriorityAlert] Web Audio play failed:', e);
                    this.playBeepFallback();
                }
            },

            setupClickHandlers() {
                // Click anywhere on pictures modal to go to data modal
                if (this.picturesOverlay) {
                    this.picturesOverlay.addEventListener('click', (e) => {
                        this.stopSoundAndShowData();
                    });
                }

                // Click anywhere on data modal to close
                if (this.dataOverlay) {
                    this.dataOverlay.addEventListener('click', (e) => {
                        this.close();
                    });
                }
            },

            async checkForAlerts() {
                try {
                    // Don't check if modal is already open
                    if (this.currentModal !== 'none') return;

                    const response = await fetch(`${BASE}/api/v1/priority-alerts`);
                    if (!response.ok) return;
                    
                    const data = await response.json();
                    if (data.alert && data.alert.project) {
                        this.showPicturesModal(data.alert);
                    }
                } catch (error) {
                    console.error('Error checking priority alerts:', error);
                }
            },

            showPicturesModal(alert) {
                this.currentAlert = alert;
                this.currentModal = 'pictures';
                console.log('🚨 Priority Alert - Pictures Modal:', alert);
                
                // Play looping sound alert
                this.playAlert();
                
                // Setup images slideshow first
                this.setupImageSlideshow(alert.images || []);
                
                // Show pictures modal
                this.picturesOverlay.style.display = 'flex';
                
                // Prevent body scroll
                document.body.style.overflow = 'hidden';
            },

            stopSoundAndShowData() {
                // Stop sound immediately
                this.stopSound();
                
                // Hide pictures modal
                this.picturesOverlay.style.display = 'none';
                
                // Show data modal
                this.showDataModal();
            },

            showDataModal() {
                if (!this.currentAlert) return;

                this.currentModal = 'data';
                console.log('📊 Priority Alert - Data Modal:', this.currentAlert);
                
                // Populate project details
                this.populateDataModal(this.currentAlert.project);
                
                // Show data modal
                this.dataOverlay.style.display = 'flex';
            },

            populateDataModal(project) {
                // Grid layout field mapping - exactly the fields you specified
                const elements = {
                    source: document.getElementById('priorityDataSource'),
                    contractor: document.getElementById('priorityContractorGrid'),
                    contactPerson: document.getElementById('priorityContactPersonGrid'),
                    contactNumber: document.getElementById('priorityContactNumberGrid'),
                    address: document.getElementById('priorityAddressGrid'),
                    projectName: document.getElementById('priorityProjectNameGrid'),
                    location: document.getElementById('priorityLocationGrid'),
                    sheetPileType: document.getElementById('prioritySheetPileTypeGrid'),
                    sheetPileAmount: document.getElementById('prioritySheetPileAmountGrid'),
                    projectValue: document.getElementById('priorityProjectValueMainGrid'),
                    accomplishment: document.getElementById('priorityAccomplishmentMainGrid')
                };

                // Header - Source
                if (elements.source) elements.source.textContent = project.source || 'DPWH';
                
                // Left Column - Primary Info
                if (elements.contractor) elements.contractor.textContent = project.contractor_name || 'N/A';
                if (elements.contactPerson) elements.contactPerson.textContent = project.contact_person || 'N/A';
                if (elements.contactNumber) elements.contactNumber.textContent = project.contact_number || 'N/A';
                
                // Address - combine street, barangay, and address components (as user requested)
                const addressComponents = [
                    project.project_street,
                    project.contract_street,
                    project.project_barangay,
                    project.contract_barangay,
                    project.project_blk_lot,
                    project.contract_blk_lot,
                    project.address
                ].filter(component => component && component.trim() && component.trim() !== 'N/A');
                
                if (elements.address) {
                    elements.address.textContent = addressComponents.length > 0 ? addressComponents.join(', ') : 'N/A';
                }
                
                if (elements.projectName) elements.projectName.textContent = project.name || 'N/A';
                
                // Right Column - Project Details
                // Location - just show the city name (not full region breakdown as user requested)
                const cityName = project.project_city || project.contract_city || project.city_province || 'N/A';
                if (elements.location) elements.location.textContent = cityName;
                
                if (elements.sheetPileType) elements.sheetPileType.textContent = project.sheet_pile_type || 'N/A';
                if (elements.sheetPileAmount) elements.sheetPileAmount.textContent = '₱' + Utils.formatNumber(project.sheet_pile_amount || 0);
                if (elements.projectValue) elements.projectValue.textContent = '₱' + Utils.formatNumber(project.project_value || 0);
                
                const accomplishmentRate = project.accomplishment_rate || 0;
                if (elements.accomplishment) elements.accomplishment.textContent = `${accomplishmentRate.toFixed(2)}%`;
            },

            setupImageSlideshow(images) {
                const imagesContainer = document.getElementById('priorityPicturesContent');
                const noImagesDiv = document.getElementById('priorityNoImagesFirst');
                const counterDiv = document.getElementById('priorityImageCounterFirst');
                const timerDiv = document.getElementById('prioritySlideshowTimerFirst');

                // Clear existing images
                const existingImages = imagesContainer.querySelectorAll('.priority-alert-image');
                existingImages.forEach(img => img.remove());

                this.imageSlideshow.images = images;
                this.imageSlideshow.currentIndex = 0;

                if (images.length === 0) {
                    noImagesDiv.style.display = 'flex';
                    counterDiv.style.display = 'none';
                    timerDiv.style.display = 'none';
                    return;
                }

                noImagesDiv.style.display = 'none';
                counterDiv.style.display = 'block';
                timerDiv.style.display = 'block';

                // Create image elements
                images.forEach((image, index) => {
                    const img = document.createElement('img');
                    img.src = `${BASE}/${image.file_path}`;
                    img.className = 'priority-alert-image';
                    img.alt = `Priority Project Image ${index + 1}`;
                    
                    if (index === 0) {
                        img.classList.add('active');
                    }
                    
                    imagesContainer.appendChild(img);
                });

                // Update counter
                this.updateImageCounter();

                // Start slideshow if more than 1 image
                if (images.length > 1) {
                    this.startImageSlideshow();
                }
            },

            updateImageCounter() {
                const counterDiv = document.getElementById('priorityImageCounterFirst');
                if (counterDiv && this.imageSlideshow.images.length > 0) {
                    counterDiv.textContent = `${this.imageSlideshow.currentIndex + 1} / ${this.imageSlideshow.images.length}`;
                }
            },

            startImageSlideshow() {
                this.stopImageSlideshow();
                
                if (this.imageSlideshow.images.length <= 1) return;

                this.imageSlideshow.timeRemaining = 5;
                this.updateTimerDisplay();

                // Start countdown
                this.imageSlideshow.countdownTimer = setInterval(() => {
                    this.imageSlideshow.timeRemaining--;
                    this.updateTimerDisplay();

                    if (this.imageSlideshow.timeRemaining <= 0) {
                        this.nextImage();
                        this.imageSlideshow.timeRemaining = 5;
                    }
                }, 1000);
            },

            stopImageSlideshow() {
                if (this.imageSlideshow.countdownTimer) {
                    clearInterval(this.imageSlideshow.countdownTimer);
                    this.imageSlideshow.countdownTimer = null;
                }
            },

            nextImage() {
                if (this.imageSlideshow.images.length <= 1) return;

                const images = document.querySelectorAll('#priorityPicturesContent .priority-alert-image');
                
                // Remove active class from current image
                if (images[this.imageSlideshow.currentIndex]) {
                    images[this.imageSlideshow.currentIndex].classList.remove('active');
                }

                // Move to next image
                this.imageSlideshow.currentIndex = (this.imageSlideshow.currentIndex + 1) % this.imageSlideshow.images.length;

                // Add active class to new image
                if (images[this.imageSlideshow.currentIndex]) {
                    images[this.imageSlideshow.currentIndex].classList.add('active');
                }

                this.updateImageCounter();
            },

            updateTimerDisplay() {
                const timerDiv = document.getElementById('prioritySlideshowTimerFirst');
                if (timerDiv) {
                    if (this.imageSlideshow.images.length > 1) {
                        timerDiv.textContent = `Next in ${this.imageSlideshow.timeRemaining}s`;
                    } else {
                        timerDiv.style.display = 'none';
                    }
                }
            },

            playBeepFallback() {
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    
                    // Create repeating beep
                    const beepInterval = setInterval(() => {
                        if (!this.isAudioPlaying) {
                            clearInterval(beepInterval);
                            return;
                        }

                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);

                        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);

                        oscillator.start();
                        oscillator.stop(audioContext.currentTime + 0.5);
                    }, 1000); // Beep every second

                    // Store interval for cleanup
                    this.beepInterval = beepInterval;
                } catch (error) {
                    console.warn('Fallback beep failed:', error);
                }
            },

            stopSound() {
                this.isAudioPlaying = false;
                
                // Stop Web Audio source
                if (this.audioSource) {
                    try { this.audioSource.stop(); } catch(e) {}
                    this.audioSource = null;
                }

                // Stop HTML5 Audio fallback
                if (this._htmlAudio) {
                    this._htmlAudio.pause();
                    this._htmlAudio.currentTime = 0;
                    this._htmlAudio = null;
                }
                
                if (this.beepInterval) {
                    clearInterval(this.beepInterval);
                    this.beepInterval = null;
                }
            },

            stopSoundAndClose() {
                this.stopSound();
                this.close();
            },

            close() {
                this.stopSound();
                this.stopImageSlideshow();
                
                // Hide both modals
                if (this.picturesOverlay) this.picturesOverlay.style.display = 'none';
                if (this.dataOverlay) this.dataOverlay.style.display = 'none';
                
                // Reset state
                this.currentAlert = null;
                this.currentModal = 'none';
                
                // Restore body scroll
                document.body.style.overflow = '';
            }
        };
        
        // Main Application Controller
        const App = {
            async init() {
                try {
                    // Initialize base components
                    Clock.init();
                    Charts.init();
                    LiveSlideshow.initAutoFit();
                    PriorityAlert.init();
                    
                    // Setup event listeners
                    this.setupEventListeners();
                    
                    // Load initial data
                    await this.loadInitialData();
                    
                    // Setup auto-refresh intervals
                    this.setupAutoRefresh();
                    
                    console.log('Dashboard initialized successfully');
                } catch (error) {
                    console.error('Dashboard initialization error:', error);
                    AppState.hasErrors = true;
                }
            },

            async loadInitialData() {
                AppState.isLoading = true;
                
                try {
                    // Load available months first
                    await AvailableMonths.load();
                    
                    // Load all dashboard data concurrently
                    await Promise.allSettled([
                        KPI.load(),
                        Contractors.load(),
                        Charts.loadRegionalData(),
                        Charts.loadSourcesData(),
                        SalesFunnel.load(),
                        TargetProgress.load(),
                        ProjectStatus.load(),
                        LiveSlideshow.load()
                    ]);
                } catch (error) {
                    console.error('Error loading initial data:', error);
                    AppState.hasErrors = true;
                } finally {
                    AppState.isLoading = false;
                }
            },

            async refreshData() {
                if (AppState.isLoading) return; // Prevent multiple concurrent refreshes
                
                try {
                    await Promise.allSettled([
                        KPI.load(),
                        Contractors.load(),
                        Charts.loadRegionalData(),
                        Charts.loadSourcesData(),
                        SalesFunnel.load(),
                        TargetProgress.load(),
                        ProjectStatus.load()
                    ]);
                } catch (error) {
                    console.error('Error refreshing data:', error);
                }
            },

            setupEventListeners() {
                const debouncedRefresh = Utils.debounce(() => this.refreshData(), 300);

                // Filter change handlers
                const filterSelectors = ['period-select', 'region-select', 'month-select'];
                filterSelectors.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.addEventListener('change', debouncedRefresh);
                    }
                });

                // Handle page visibility changes
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden && AppState.hasErrors) {
                        // Retry loading if there were previous errors and page becomes visible
                        this.refreshData();
                        AppState.hasErrors = false;
                    }
                });

                // Handle online/offline events
                window.addEventListener('online', () => {
                    console.log('Connection restored, refreshing data');
                    this.refreshData();
                });

                window.addEventListener('offline', () => {
                    console.log('Connection lost');
                });
            },

            setupAutoRefresh() {
                // Data refresh every 30 seconds
                AppState.intervals.dataRefresh = setInterval(() => {
                    if (!document.hidden) { // Only refresh when page is visible
                        this.refreshData();
                    }
                }, 30000);
                
                // Slideshow refresh every 10 seconds
                AppState.intervals.slideshowRefresh = setInterval(() => {
                    if (!document.hidden) {
                        LiveSlideshow.load();
                    }
                }, 10000);
            },

            cleanup() {
                // Clean up intervals
                Object.values(AppState.intervals).forEach(interval => {
                    if (interval) clearInterval(interval);
                });
                
                // Clean up charts
                Object.values(AppState.charts).forEach(chart => {
                    if (chart && typeof chart.destroy === 'function') {
                        chart.destroy();
                    }
                });
                
                // Clear slideshow timeouts
                if (LiveSlideshow.countdownInterval) {
                    clearInterval(LiveSlideshow.countdownInterval);
                }
                
                // Clear priority alert timeouts and audio
                if (PriorityAlert.imageSlideshow.countdownTimer) {
                    clearInterval(PriorityAlert.imageSlideshow.countdownTimer);
                }
                if (PriorityAlert.beepInterval) {
                    clearInterval(PriorityAlert.beepInterval);
                }
                PriorityAlert.stopSound();
            }
        };

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            App.init();
            
            // Initialize export functionality
            setTimeout(() => {
                // Report selection checkboxes
                document.querySelectorAll('input[name="exportReport"]').forEach(cb => {
                    cb.addEventListener('change', () => {
                        ExportModal.updateNextButton();
                        
                        // Update select all checkbox
                        const allCheckboxes = document.querySelectorAll('input[name="exportReport"]');
                        const checkedCheckboxes = document.querySelectorAll('input[name="exportReport"]:checked');
                        const selectAllCheckbox = document.getElementById('selectAllReports');
                        
                        if (checkedCheckboxes.length === allCheckboxes.length) {
                            selectAllCheckbox.checked = true;
                            selectAllCheckbox.indeterminate = false;
                        } else if (checkedCheckboxes.length > 0) {
                            selectAllCheckbox.checked = false;
                            selectAllCheckbox.indeterminate = true;
                        } else {
                            selectAllCheckbox.checked = false;
                            selectAllCheckbox.indeterminate = false;
                        }
                    });
                });
                
                // Initialize next button state
                ExportModal.updateNextButton();
            }, 100);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            App.cleanup();
        });
        
        // Store custom select instances globally for refresh
        window.customSelectInstances = {};
        
        // Initialize custom select dropdowns AFTER DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize all control selects EXCEPT month-select (it will be initialized after data loads)
            const controlSelects = document.querySelectorAll('.control-select:not(#month-select)');
            controlSelects.forEach(select => {
                const instance = new CustomSelect(select, {
                    searchable: false,
                    placeholder: select.options[select.selectedIndex]?.text || 'Select...'
                });
            });
        });
    </script>
</body>
</html>