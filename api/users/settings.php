<?php
/* ============================================================
   api/users/settings.php — Superadmin Settings API
   ============================================================
   GET    /api/v1/users/settings       — Get all settings
   PUT    /api/v1/users/settings       — Update settings
   POST   /api/v1/users/settings       — Special actions
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

    // If maintenance_mode was updated, write to a marker file too
    if (isset($input['settings']['maintenance_mode'])) {
        $maintenanceFile = __DIR__ . '/../../maintenance.flag';
        if ($input['settings']['maintenance_mode']) {
            file_put_contents($maintenanceFile, time());
        } else {
            if (file_exists($maintenanceFile)) {
                unlink($maintenanceFile);
            }
        }
    }

    logAdminActivity($pdo, 'Updated system settings: ' . implode(', ', $updated));

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'message' => count($updated) . ' setting(s) updated successfully',
    ]);
}

// ── POST: Special actions ─────────────────────────────────
function handlePostSettings(PDO $pdo): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'clear-cache':
            handleClearCache($pdo);
            break;

        case 'check-db':
            handleCheckDb($pdo);
            break;

        case 'optimize-tables':
            handleOptimizeTables($pdo);
            break;

        case 'toggle-maintenance':
            handleToggleMaintenance($pdo, $input);
            break;

        case 'export-database':
            handleExportDatabase($pdo);
            break;

        case 'export-data':
            handleExportData($pdo);
            break;

        default:
            http_response_code(400);
            echo json_encode(['detail' => 'Unknown action: ' . $action]);
            break;
    }
}

// ── Clear cache ───────────────────────────────────────────
function handleClearCache(PDO $pdo): void {
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
}

// ── Check database health ─────────────────────────────────
function handleCheckDb(PDO $pdo): void {
    $tables = $pdo->query("SHOW TABLE STATUS")->fetchAll(PDO::FETCH_ASSOC);
    $totalRows = 0;
    $totalSize = 0;
    $dbInfo = [];
    foreach ($tables as $table) {
        $rows = (int)$table['Rows'];
        $size = $table['Data_length'] + $table['Index_length'];
        $totalRows += $rows;
        $totalSize += $size;
        $dbInfo[] = [
            'name' => $table['Name'],
            'engine' => $table['Engine'],
            'rows' => $rows,
            'size_mb' => round($size / 1024 / 1024, 2),
            'collation' => $table['Collation'],
        ];
    }
    echo json_encode([
        'success' => true,
        'total_tables' => count($tables),
        'total_rows' => $totalRows,
        'total_size_mb' => round($totalSize / 1024 / 1024, 2),
        'tables' => $dbInfo,
    ]);
}

// ── Optimize tables ──────────────────────────────────────
function handleOptimizeTables(PDO $pdo): void {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $optimized = 0;
    foreach ($tables as $table) {
        $pdo->exec("OPTIMIZE TABLE `$table`");
        $optimized++;
    }
    logAdminActivity($pdo, 'Optimized ' . $optimized . ' database tables');
    echo json_encode(['success' => true, 'message' => $optimized . ' tables optimized']);
}

// ── Toggle maintenance mode ──────────────────────────────
function handleToggleMaintenance(PDO $pdo, array $input): void {
    $enabled = !empty($input['enabled']);
    $file = __DIR__ . '/../../maintenance.flag';
    
    if ($enabled) {
        file_put_contents($file, time());
        $pdo->prepare("UPDATE system_settings SET setting_value = '1', updated_at = NOW() WHERE setting_key = 'maintenance_mode'")
             ->execute();
        logAdminActivity($pdo, 'Maintenance mode ENABLED');
    } else {
        if (file_exists($file)) unlink($file);
        $pdo->prepare("UPDATE system_settings SET setting_value = '0', updated_at = NOW() WHERE setting_key = 'maintenance_mode'")
             ->execute();
        logAdminActivity($pdo, 'Maintenance mode DISABLED');
    }
    
    echo json_encode(['success' => true, 'maintenance_enabled' => $enabled]);
}

// ── Export entire database as .sql ────────────────────────
function handleExportDatabase(PDO $pdo): void {
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $sql = "-- DATAS Database Export\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: " . DB_NAME . "\n";
    $sql .= "-- PHP Version: " . phpversion() . "\n\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "SET AUTOCOMMIT = 0;\n";
    $sql .= "START TRANSACTION;\n";
    $sql .= "SET time_zone = '+08:00';\n\n";

    foreach ($tables as $table) {
        // Drop table if exists
        $sql .= "-- Structure for table `$table`\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create table
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $sql .= $create['Create Table'] . ";\n\n";
        
        // Get data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $colNames = '`' . implode('`, `', $columns) . '`';
            
            $sql .= "-- Data for table `$table` (" . count($rows) . " rows)\n";
            
            // Batch insert in chunks of 500
            $chunks = array_chunk($rows, 500);
            foreach ($chunks as $chunk) {
                $sql .= "INSERT INTO `$table` ($colNames) VALUES\n";
                $values = [];
                foreach ($chunk as $row) {
                    $escaped = [];
                    foreach ($row as $v) {
                        if ($v === null) {
                            $escaped[] = 'NULL';
                        } else {
                            $escaped[] = "'" . str_replace(["'", "\\"], ["''", "\\\\"], $v) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $escaped) . ')';
                }
                $sql .= implode(",\n", $values) . ";\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "COMMIT;\n";
    
    // Return as base64 or raw - let's use raw JSON
    echo json_encode([
        'success' => true,
        'filename' => 'datas_db_backup_' . date('Y-m-d_His') . '.sql',
        'content' => base64_encode($sql),
        'size_bytes' => strlen($sql),
    ]);
}

// ── Export specific data tables as .sql ──────────────────
function handleExportData(PDO $pdo): void {
    // Only export data tables (not system tables)
    $dataTables = ['projects', 'projects_priority', 'platforms', 'users', 'activity_logs', 
                   'sales_reps', 'user_sessions', 'system_settings'];
    
    $existing = [];
    foreach ($dataTables as $t) {
        $r = $pdo->query("SHOW TABLES LIKE '$t'")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($r)) $existing[] = $t;
    }
    
    $sql = "-- DATAS Data Export\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: " . DB_NAME . "\n\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "START TRANSACTION;\n";
    $sql .= "SET time_zone = '+08:00';\n\n";

    foreach ($existing as $table) {
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $colNames = '`' . implode('`, `', $columns) . '`';
            
            $sql .= "-- Data for table `$table` (" . count($rows) . " rows)\n";
            
            $chunks = array_chunk($rows, 500);
            foreach ($chunks as $chunk) {
                $sql .= "INSERT INTO `$table` ($colNames) VALUES\n";
                $values = [];
                foreach ($chunk as $row) {
                    $escaped = [];
                    foreach ($row as $v) {
                        if ($v === null) {
                            $escaped[] = 'NULL';
                        } else {
                            $escaped[] = "'" . str_replace(["'", "\\"], ["''", "\\\\"], $v) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $escaped) . ')';
                }
                $sql .= implode(",\n", $values) . ";\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "COMMIT;\n";
    
    echo json_encode([
        'success' => true,
        'filename' => 'datas_data_export_' . date('Y-m-d_His') . '.sql',
        'content' => base64_encode($sql),
        'size_bytes' => strlen($sql),
    ]);
}

// ── Helper: Get system information ─────────────────────────
function getSystemInfo(PDO $pdo): array {
    $phpVersion = phpversion();
    $phpExtensions = get_loaded_extensions();
    
    $dbVersion = $pdo->query("SELECT VERSION() as v")->fetch()['v'] ?? 'Unknown';
    $dbSize = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                            FROM information_schema.tables 
                            WHERE table_schema = '" . DB_NAME . "'")->fetch()['size_mb'] ?? 0;
    
    $activeSessions = 0;
    $sessionDir = session_save_path();
    if ($sessionDir && is_dir($sessionDir)) {
        $sessionFiles = glob($sessionDir . '/sess_*');
        $activeSessions = count($sessionFiles);
    }

    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $serverProtocol = $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown';
    $appVersion = defined('APP_VERSION') ? APP_VERSION : 'Unknown';
    $appEnv = defined('APP_ENV') ? APP_ENV : 'Unknown';
    $debugMode = defined('DEBUG_MODE') ? DEBUG_MODE : false;

    // Check maintenance file
    $maintenanceActive = file_exists(__DIR__ . '/../../maintenance.flag');

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
        'maintenance_file_active' => $maintenanceActive,
        'php_extensions' => array_slice($phpExtensions, 0, 20),
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