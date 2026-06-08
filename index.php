<?php
/**
 * DATAS Dashboard - Main Entry Point
 */

// Include configuration
require_once 'config.php';

// Simple redirect to login or admin based on session
session_start();

if (isset($_SESSION['user_id'])) {
    // User is logged in, redirect to admin
    header('Location: pages/admin.php');
} else {
    // User not logged in, redirect to login
    header('Location: pages/login.php');
}

exit();
?>