<?php
/**
 * Production Configuration Template for DATAS Dashboard
 * 
 * Copy this to config.php and update values for your production environment
 * NEVER commit config.php to version control - add to .gitignore
 */

// Database Configuration - UPDATE THESE VALUES
define('DB_HOST', 'localhost');
define('DB_NAME', 'datas_db');
define('DB_USER', 'datas_user');
define('DB_PASS', 'CHANGE_THIS_PASSWORD');  // Use strong password
define('DB_CHARSET', 'utf8mb4');

// Security Configuration - PRODUCTION SETTINGS
define('SESSION_TIMEOUT', 28800); // 8 hours in seconds
define('SESSION_IDLE_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('DEBUG_MODE', false);  // NEVER set to true in production
define('APP_ENV', 'production');

// Password Requirements - SECURITY HARDENING
define('MIN_PASSWORD_LENGTH', 12);
define('BCRYPT_COST', 12); // Increase to 14 for higher security

// Rate Limiting - ANTI-BRUTE FORCE
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes
define('MAX_2FA_ATTEMPTS', 3);
define('2FA_ATTEMPT_WINDOW', 900); // 15 minutes

// File Upload - SECURITY SETTINGS
define('MAX_FILE_SIZE', 10485760); // 10MB
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('ALLOWED_IMAGE_MIMES', ['image/jpeg', 'image/png', 'image/webp']);

// CORS Configuration - UPDATE WITH YOUR DOMAIN
define('CORS_ORIGIN', 'https://yourdomain.com'); // UPDATE THIS

// Pagination
define('DEFAULT_PAGE_SIZE', 50);
define('MAX_PAGE_SIZE', 500);

// Application URLs - UPDATE THESE
define('APP_NAME', 'TDT Powersteel DATAS');
define('APP_URL', 'https://yourdomain.com'); // UPDATE THIS
define('ADMIN_EMAIL', 'admin@yourdomain.com'); // UPDATE THIS

// Logging Configuration
define('LOG_ERRORS', true);
define('LOG_DIR', __DIR__ . '/logs');
define('LOG_LEVEL', 'ERROR'); // ERROR, WARNING, INFO, DEBUG

// Backup Configuration
define('BACKUP_DIR', '/backups/datas');
define('BACKUP_RETENTION_DAYS', 30);

// Email Configuration (if needed)
define('MAIL_HOST', 'smtp.yourdomain.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@yourdomain.com');
define('MAIL_PASSWORD', 'your_email_password');
define('MAIL_FROM_NAME', 'TDT Powersteel System');

// Performance Settings
define('CACHE_ENABLED', true);
define('CACHE_TTL', 300); // 5 minutes

// API Rate Limiting
define('API_RATE_LIMIT', 1000); // requests per hour per IP
define('API_BURST_LIMIT', 50); // requests per minute per IP

// Security Headers
define('SECURITY_HEADERS', [
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data:;"
]);

// Database Connection Pool Settings
define('DB_POOL_MIN', 2);
define('DB_POOL_MAX', 20);
define('DB_TIMEOUT', 30);

// Error Reporting - PRODUCTION SAFE
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

// Create required directories
$required_dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/logs',
    __DIR__ . '/cache'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set error log location
ini_set('error_log', LOG_DIR . '/php_errors.log');

/**
 * PRODUCTION SECURITY CHECKLIST:
 * 
 * [✓] Update all database credentials
 * [✓] Set strong passwords (min 20 chars, mixed case, numbers, symbols)
 * [✓] Update CORS_ORIGIN with your actual domain
 * [✓] Update APP_URL with your actual domain
 * [✓] Set DEBUG_MODE to false
 * [✓] Configure email settings if needed
 * [✓] Set up SSL certificate
 * [✓] Configure firewall rules
 * [✓] Set up regular backups
 * [✓] Enable security headers
 * [✓] Test all functionality after deployment
 * [✓] Set up monitoring and alerting
 * [✓] Configure log rotation
 * [✓] Update admin passwords immediately
 * [✓] Enable fail2ban or similar intrusion prevention
 * [✓] Keep system updated (PHP, MySQL, Apache)
 */