<?php
/**
 * Clear PHP OPcache and other caches
 * Run this after deploying new code to production
 * Access via: https://yourdomain.com/clear-cache.php
 */

// Security: Only allow from localhost or authenticated admin
session_start();
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$isAdmin = isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['superadmin', 'admin']);

if (!$isLocalhost && !$isAdmin) {
    http_response_code(403);
    die('Access denied. This script can only be run by administrators.');
}

echo "<!DOCTYPE html><html><head><title>Cache Clear</title>";
echo "<style>body{font-family:sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";
echo "<h1>🧹 Cache Clearing Utility</h1>";

$results = [];

// 1. Clear PHP OPcache (most important for production)
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $results[] = ['✅ PHP OPcache', 'Cleared successfully', 'success'];
    } else {
        $results[] = ['❌ PHP OPcache', 'Failed to clear', 'error'];
    }
} else {
    $results[] = ['ℹ️ PHP OPcache', 'Not enabled on this server', 'info'];
}

// 2. Clear APCu cache (if available)
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        $results[] = ['✅ APCu Cache', 'Cleared successfully', 'success'];
    } else {
        $results[] = ['❌ APCu Cache', 'Failed to clear', 'error'];
    }
} else {
    $results[] = ['ℹ️ APCu Cache', 'Not available', 'info'];
}

// 3. Clear PHP session if requested
if (isset($_GET['clear_session']) && $_GET['clear_session'] === '1') {
    session_destroy();
    session_start();
    $results[] = ['✅ PHP Session', 'Destroyed and restarted', 'success'];
}

// 4. Show current PHP info
$phpVersion = PHP_VERSION;
$opcacheEnabled = function_exists('opcache_get_status') ? 'Yes' : 'No';
$opcacheStatus = function_exists('opcache_get_status') ? opcache_get_status() : null;

echo "<h2>📊 Cache Clear Results</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>Item</th><th>Status</th></tr>";
foreach ($results as $result) {
    echo "<tr class='{$result[2]}'><td><strong>{$result[0]}</strong></td><td>{$result[1]}</td></tr>";
}
echo "</table>";

echo "<h2>🔧 Server Information</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> {$phpVersion}</li>";
echo "<li><strong>OPcache Enabled:</strong> {$opcacheEnabled}</li>";
if ($opcacheStatus && isset($opcacheStatus['opcache_enabled'])) {
    echo "<li><strong>OPcache Status:</strong> " . ($opcacheStatus['opcache_enabled'] ? 'Active' : 'Inactive') . "</li>";
    if (isset($opcacheStatus['opcache_statistics'])) {
        $stats = $opcacheStatus['opcache_statistics'];
        echo "<li><strong>Cached Files:</strong> " . ($stats['num_cached_scripts'] ?? 0) . "</li>";
        echo "<li><strong>Cache Hits:</strong> " . ($stats['hits'] ?? 0) . "</li>";
        echo "<li><strong>Cache Misses:</strong> " . ($stats['misses'] ?? 0) . "</li>";
        $hitRate = ($stats['hits'] ?? 0) / max(1, ($stats['hits'] ?? 0) + ($stats['misses'] ?? 0)) * 100;
        echo "<li><strong>Hit Rate:</strong> " . number_format($hitRate, 2) . "%</li>";
    }
}
echo "</ul>";

echo "<h2>🎯 Next Steps</h2>";
echo "<ol>";
echo "<li>✅ Cache has been cleared on the server</li>";
echo "<li>🔄 <strong>Users should hard refresh:</strong> Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)</li>";
echo "<li>📊 <strong>Test the Reports Dashboard:</strong> <a href='reports'>Go to Reports</a></li>";
echo "<li>🧪 <strong>Test API directly:</strong> <a href='api/v1/kpi' target='_blank'>Open KPI API</a></li>";
echo "</ol>";

echo "<h2>🚨 Troubleshooting</h2>";
echo "<ul>";
echo "<li>If OPcache didn't clear: Restart PHP-FPM or Apache on the server</li>";
echo "<li>If still seeing old code: Check file permissions (should be readable by web server)</li>";
echo "<li>If API returns old data: Clear browser cache completely</li>";
echo "<li><a href='?clear_session=1'>Click here to destroy PHP session</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Timestamp: " . date('Y-m-d H:i:s') . " | Server: " . gethostname() . "</small></p>";
echo "</body></html>";
