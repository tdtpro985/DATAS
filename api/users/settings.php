<?php
/* ============================================================
   api/users/settings.php — Superadmin Settings API
   ============================================================
   GET    /api/v1/users/settings       — Get all settings
   PUT    /api/v1/users/settings       — Update settings
   POST   /api/v1/users/settings/clear — Clear system cache
   ============================================================ */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

// Ensure output is clean JSON
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json');

// ── Auth check: only superadmin can access settings ─────────
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['detail' => 'Forbidden: Superadmin access required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

try {
    switch ($method) {
        case 'GET':
            handleGetSettings($pdo);
            break;
        case 'PUT':
            handleUpdateSettings($pdo);
            break;
        case 'POST':
            handlePostSettings($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['detail' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['detail' => 'Server error: ' . $e->getMessage()]);
}

// ── GET: Retrieve all settings ─────────────────────────────
function handleGetSettings(PDO $pdo): void {
    $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type, description, updated_at FROM system_settings ORDER BY setting_key");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $settings = [];
    foreach ($rows as $row) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        
        // Cast value based on type
        if ($row['setting_type'] === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($row['setting_type'] === 'integer') {
            $value = intval($value);
        } elseif ($row['setting_type'] === 'float') {
            $value = floatval($value);
        }

        $settings[$key] = [
            'value' => $value,
            'type' => $row['setting_type'],
            'description' => $row['description'],
            'updated_at' => $row['updated_at'],
        ];
    }

    // Get system info
    $systemInfo = getSystemInfo($pdo);

    echo json_encode([
        'settings' => $settings,
        'system_info' => $systemInfo,
    ]);
}

// ── PUT: Update settings ───────────────────────────────────
function handleUpdateSettings(PDO $pdo): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['settings']) || !is_array($input['settings'])) {
        http_response_code(400);
        echo json_encode(['detail' => 'Invalid request body. Expected { "settings": { "key": "value", ... } }']);
        exit;
    }

    $updated = [];
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = :key");

    foreach ($input['settings'] as $key => $value) {
        // Convert value to string for storage
        if (is_bool($value)) {
            $strValue = $value ? '1' : '0';
        } elseif (is_array($value)) {
            $strValue = json_encode($value);
        } else {
            $strValue = (string) $value;
        }

        $stmt->execute([':key' => $key, ':value' => $strValue]);
        if ($stmt->rowCount() > 0) {
            $updated[] = $key;
        }
    }

    // Log the activity
    logAdminActivity($pdo, 'Updated system settings: ' . implode(', ', $updated));

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'message' => count($updated) . ' setting(s) updated successfully',
    ]);
}

// ── POST: Special actions (clear cache, etc.) ──────────────
function handlePostSettings(PDO $pdo): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'clear-cache':
            // Clear system cache
            $cacheDir = __DIR__ . '/../../cache';
            $cleared = 0;
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        $cleared++;
                    }
                }
            }
            logAdminActivity($pdo, 'Cleared system cache (' . $cleared . ' files)');
            echo json_encode(['success' => true, 'message' => 'Cache cleared (' . $cleared . ' files removed)']);
            break;

        case 'check-db':
            // Database health check
            $tables = $pdo->query("SHOW TABLE STATUS")->fetchAll(PDO::FETCH_ASSOC);
            $dbInfo = [];
            foreach ($tables as $table) {
                $dbInfo[] = [
                    'name' => $table['Name'],
                    'engine' => $table['Engine'],
                    'rows' => (int)$table['Rows'],
                    'size' => $table['Data_length'] + $table['Index_length'],
                    'collation' => $table['Collation'],
                ];
            }
            echo json_encode(['success' => true, 'tables' => $dbInfo]);
            break;

        case 'optimize-tables':
            // Optimize all tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $optimized = 0;
            foreach ($tables as $table) {
                $pdo->exec("OPTIMIZE TABLE `$table`");
                $optimized++;
            }
            logAdminActivity($pdo, 'Optimized ' . $optimized . ' database tables');
            echo json_encode(['success' => true, 'message' => $optimized . ' tables optimized']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['detail' => 'Unknown action: ' . $action]);
            break;
    }
}

// ── Helper: Get system information ─────────────────────────
function getSystemInfo(PDO $pdo): array {
    // PHP info
    $phpVersion = phpversion();
    $phpExtensions = get_loaded_extensions();
    
    // Database info
    $dbVersion = $pdo->query("SELECT VERSION() as v")->fetch()['v'] ?? 'Unknown';
    $dbSize = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                            FROM information_schema.tables 
                            WHERE table_schema = '" . DB_NAME . "'")->fetch()['size_mb'] ?? 0;
    
    // Session info
    $activeSessions = 0;
    $sessionDir = session_save_path();
    if ($sessionDir && is_dir($sessionDir)) {
        $sessionFiles = glob($sessionDir . '/sess_*');
        $activeSessions = count($sessionFiles);
    }

    // Server info
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $serverProtocol = $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown';
    
    // Application info
    $appVersion = defined('APP_VERSION') ? APP_VERSION : 'Unknown';
    $appEnv = defined('APP_ENV') ? APP_ENV : 'Unknown';
    $debugMode = defined('DEBUG_MODE') ? DEBUG_MODE : false;

    return [
        'php_version' => $phpVersion,
        'database_version' => $dbVersion,
        'database_size_mb' => (float)$dbSize,
        'server_software' => $serverSoftware,
        'server_protocol' => $serverProtocol,
        'active_sessions' => $activeSessions,
        'app_version' => $appVersion,
        'app_environment' => $appEnv,
        'debug_mode' => $debugMode,
        'timezone' => defined('APP_TIMEZONE') ? APP_TIMEZONE : date_default_timezone_get(),
        'current_time' => date('Y-m-d H:i:s'),
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        'php_extensions' => array_slice($phpExtensions, 0, 20), // First 20 for brevity
    ];
}

// ── Helper: Log admin activity ─────────────────────────────
function logAdminActivity(PDO $pdo, string $action): void {
    $userId = $_SESSION['user']['id'] ?? null;
    $username = $_SESSION['user']['username'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, username, action, target_type, target_id, details, ip_address, created_at) 
                                VALUES (:user_id, :username, :action, :target_type, :target_id, :details, :ip, NOW())");
        $stmt->execute([
            ':user_id' => $userId,
            ':username' => $username,
            ':action' => $action,
            ':target_type' => 'settings',
            ':target_id' => null,
            ':details' => $action,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    } catch (Exception $e) {
        error_log('Failed to log admin activity: ' . $e->getMessage());
    }
}