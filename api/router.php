<?php
// Suppress PHP errors from being output — they break JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Clean any existing output buffers and start fresh
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptDir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$appBase    = rtrim(dirname($scriptDir), '/');
$path       = parse_url($requestUri, PHP_URL_PATH);

// Load debug mode from config
if (!defined('DEBUG_MODE')) {
    require_once __DIR__ . '/../config.php';
}

// DEBUG: Log the path parsing steps (only in debug mode)
if (DEBUG_MODE) {
    error_log("=== ROUTER DEBUG ===");
    error_log("REQUEST_URI: " . $requestUri);
    error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
    error_log("scriptDir: " . $scriptDir);
    error_log("appBase: " . $appBase);
    error_log("path (before): " . $path);
}

if ($appBase !== '' && strpos($path, $appBase) === 0) {
    $path = substr($path, strlen($appBase));
    if (DEBUG_MODE) error_log("path (after appBase strip): " . $path);
}
$path   = preg_replace('#^/api/v1(/|$)#', '', $path);
if (DEBUG_MODE) error_log("path (after /api/v1 strip): " . $path);
$path   = trim($path, '/');
if (DEBUG_MODE) {
    error_log("path (final): " . $path);
    error_log("method: " . $_SERVER['REQUEST_METHOD'] ?? 'GET');
    error_log("===================");
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Clean output buffer before routing
ob_clean();

if ($path === 'auth/login')                                              { require __DIR__ . '/auth/login.php'; }
elseif ($path === 'auth/logout')                                         { require __DIR__ . '/auth/logout.php'; }
elseif ($path === 'auth/me')                                             { require __DIR__ . '/auth/me.php'; }
elseif ($path === 'auth/verify-2fa')                                     { require __DIR__ . '/auth/verify-2fa.php'; }
elseif ($path === 'auth/request-password-reset')                         { require __DIR__ . '/auth/request-password-reset.php'; }
elseif ($path === 'kpi')                                                 { require __DIR__ . '/kpi.php'; }
elseif ($path === 'priority-alerts')                                    { require __DIR__ . '/priority-alerts.php'; }
elseif ($path === 'export')                                              { require __DIR__ . '/export.php'; }
elseif ($path === 'contractors/ranking')                                 { require __DIR__ . '/contractors/ranking.php'; }
elseif ($path === 'contractors/rotating-card')                           { require __DIR__ . '/contractors/rotating-card.php'; }
elseif ($path === 'charts/pie')                                          { require __DIR__ . '/charts/pie.php'; }
elseif ($path === 'charts/funnel')                                       { require __DIR__ . '/charts/funnel.php'; }
elseif ($path === 'charts/regional-stats')                               { require __DIR__ . '/charts/regional-stats.php'; }
elseif ($path === 'map/regions')                                         { require __DIR__ . '/map/regions.php'; }
elseif ($path === 'latest_priority')                                     { require __DIR__ . '/latest_priority.php'; }
elseif ($path === 'live-slideshow')                                      { require __DIR__ . '/live-slideshow.php'; }
elseif ($path === 'available-months')                                    { require __DIR__ . '/available-months.php'; }
elseif ($path === 'locations')                                           { require __DIR__ . '/locations.php'; }
elseif ($path === 'sync-status')                                         { require __DIR__ . '/sync-status.php'; }
elseif ($path === 'projects/priority')                                   { require __DIR__ . '/projects/priority.php'; }
elseif ($path === 'projects/unassigned')                                 { require __DIR__ . '/projects/unassigned.php'; }
elseif ($path === 'projects/assigned')                                   { require __DIR__ . '/projects/assigned.php'; }
elseif ($path === 'projects/unprocessed')                                { require __DIR__ . '/projects/unprocessed.php'; }
elseif ($path === 'projects/processed')                                  { require __DIR__ . '/projects/processed.php'; }
elseif ($path === 'projects/upload' && $method === 'POST')               { require __DIR__ . '/projects/upload.php'; }
elseif ($path === 'projects' && $method === 'POST')                      { require __DIR__ . '/projects/index.php'; }
elseif ($path === 'projects' && $method === 'GET')                       { require __DIR__ . '/projects/index.php'; }
elseif ($path === 'projects/my-recent')                                  { require __DIR__ . '/projects/my-recent.php'; }
elseif (preg_match('#^projects/(\d+)/status$#', $path, $m))             { $_GET['id'] = $m[1]; require __DIR__ . '/projects/status.php'; }
elseif (preg_match('#^projects/(\d+)/assign$#', $path, $m))             { $_GET['id'] = $m[1]; require __DIR__ . '/projects/assign.php'; }
elseif (preg_match('#^projects/(\d+)/unassign$#', $path, $m))           { $_GET['id'] = $m[1]; require __DIR__ . '/projects/unassign.php'; }
elseif ($path === 'projects/bulk-assign' && $method === 'POST')          { require __DIR__ . '/projects/bulk-assign.php'; }
elseif ($path === 'projects/bulk-unassign' && $method === 'POST')        { require __DIR__ . '/projects/bulk-unassign.php'; }
elseif ($path === 'projects/archive' && in_array($method, ['POST', 'PUT']))  { require __DIR__ . '/projects/archive.php'; }
elseif ($path === 'projects/archived' && $method === 'GET')                  { require __DIR__ . '/projects/archived.php'; }
elseif (preg_match('#^projects/(\d+)/sales-tracking$#', $path, $m))     { $_GET['id'] = $m[1]; require __DIR__ . '/projects/sales-tracking.php'; }
elseif ($path === 'users' && in_array($method, ['GET','POST'], true))    { require __DIR__ . '/users/index.php'; }
elseif (preg_match('#^users/(\d+)$#', $path, $m))                       { $_GET['id'] = $m[1]; require __DIR__ . '/users/user.php'; }
elseif ($path === 'users/sales-reps-ranking')                            { require __DIR__ . '/users/sales-reps-ranking.php'; }
elseif ($path === 'users/sales-reps' || preg_match('#^users/sales-reps/(\d+)$#', $path)) { require __DIR__ . '/users/sales-reps.php'; }
elseif ($path === 'platforms' && $method === 'GET')                      { require __DIR__ . '/platforms/index.php'; }
elseif ($path === 'platforms/create' && $method === 'POST')              { require __DIR__ . '/platforms/create.php'; }
elseif ($path === 'platforms/update' && $method === 'POST')              { require __DIR__ . '/platforms/update.php'; }
elseif ($path === 'platforms/archive' && $method === 'POST')             { require __DIR__ . '/platforms/archive.php'; }

else {
    if (DEBUG_MODE) error_log("ROUTER: No route matched for path: " . $path);
    ob_clean();
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['detail' => 'Not found']);
}

// Flush output buffer
ob_end_flush();