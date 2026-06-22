<?php
/**
 * Configuration file for DATAS Dashboard
 * 
 * Update the values below for your environment
 * This file should NOT be committed to version control
 */

// Set default timezone to Philippine Time (UTC+8)
date_default_timezone_set('Asia/Manila');

// Database Configuration - Production Settings
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'datas_db');
define('DB_USER', 'root');
define('DB_PASS', 'password');// Replace with actual password
define('DB_CHARSET', 'utf8mb4');

// Timezone Configuration
define('APP_TIMEZONE', 'Asia/Manila');
define('DB_TIMEZONE', '+08:00');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_IDLE_TIMEOUT', 0); // idle logout disabled
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('DEBUG_MODE', false);  // Set to true only in development
define('APP_ENV', 'production');
define('APP_VERSION', '20260611110426'); // Update this on every deploy to bust server cache

// Password Requirements
define('MIN_PASSWORD_LENGTH', 12);
define('BCRYPT_COST', 12);

// Rate Limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes
define('MAX_2FA_ATTEMPTS', 3);
define('TWO_FA_ATTEMPT_WINDOW', 900); // 15 minutes

// File Upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('ALLOWED_IMAGE_MIMES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// CORS Configuration - Update with your domain
define('CORS_ORIGIN', 'https://your-domain.com'); // Replace with your actual domain

// Pagination
define('DEFAULT_PAGE_SIZE', 50);
define('MAX_PAGE_SIZE', 500);
