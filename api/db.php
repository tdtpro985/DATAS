<?php
/* ============================================================
   db.php — Database Connection (PDO)
   ============================================================
   Single shared PDO instance for the entire API.
   Loads configuration from config.php
   ============================================================ */

// Suppress PHP errors from being output — they break JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Load configuration
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

// Start output buffering to catch any stray output
ob_start();

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use emulated prepares to allow binding LIMIT/OFFSET safely on MySQL in this environment
            PDO::ATTR_EMULATE_PREPARES   => true,
            // Add connection timeout and persistence
            PDO::ATTR_TIMEOUT            => 30,
            PDO::ATTR_PERSISTENT         => false,
            // MySQL specific options
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode=''",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // SECURITY: Don't expose database details
            error_log('Database connection error: ' . $e->getMessage());
            
            // Clean output buffer before sending error
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['detail' => 'Database error. Please contact support.']);
            exit;
        }
    }
    return $pdo;
}
