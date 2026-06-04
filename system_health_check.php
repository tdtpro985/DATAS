<?php
/**
 * System Health Check - Comprehensive Bug Verification
 * 
 * This script verifies all the major bug fixes have been applied correctly
 * Access via: http://localhost/DATAS/system_health_check.php
 */

echo "<!DOCTYPE html><html><head><title>System Health Check</title>";
echo "<style>body{font-family:Arial;margin:20px;} .pass{color:green;} .fail{color:red;} .warn{color:orange;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;}</style>";
echo "</head><body>";

echo "<h1>🏥 DATAS System Health Check</h1>";
echo "<p>Verifying all critical bug fixes...</p><hr>";

$errors = 0;
$warnings = 0;
$passes = 0;

function test_result($name, $status, $message, $details = '') {
    global $errors, $warnings, $passes;
    
    $icon = $status === 'pass' ? '✅' : ($status === 'fail' ? '❌' : '⚠️');
    $class = $status === 'pass' ? 'pass' : ($status === 'fail' ? 'fail' : 'warn');
    
    echo "<div class='$class'><strong>$icon $name:</strong> $message</div>";
    if ($details) echo "<pre>$details</pre>";
    echo "<br>";
    
    if ($status === 'pass') $passes++;
    elseif ($status === 'fail') $errors++;
    else $warnings++;
}

// Test 1: Database Configuration
echo "<h2>📊 Database Configuration Tests</h2>";

try {
    require_once __DIR__ . '/config.php';
    
    $expectedDbName = 'datas_db';
    if (DB_NAME === $expectedDbName) {
        test_result('Database Name Configuration', 'pass', "Database name correctly set to: " . DB_NAME);
    } else {
        test_result('Database Name Configuration', 'fail', "Database name mismatch. Expected: $expectedDbName, Got: " . DB_NAME);
    }
    
    // Test CORS Configuration
    if (CORS_ORIGIN !== '*') {
        test_result('CORS Security Configuration', 'pass', "CORS origin properly configured: " . CORS_ORIGIN);
    } else {
        test_result('CORS Security Configuration', 'warn', "CORS origin set to '*' - potential security issue");
    }
    
} catch (Exception $e) {
    test_result('Configuration File', 'fail', $e->getMessage());
}

// Test 2: Database Connection
echo "<h2>🔌 Database Connection Test</h2>";

try {
    require_once __DIR__ . '/api/db.php';
    $db = getDB();
    test_result('Database Connection', 'pass', 'Successfully connected to database');
    
    // Test table existence
    $tables = ['projects', 'sales_tracking', 'users', 'locations'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            test_result("Table: $table", 'pass', "Table exists");
        } else {
            test_result("Table: $table", 'fail', "Table missing");
        }
    }
    
} catch (Exception $e) {
    test_result('Database Connection', 'fail', $e->getMessage());
}

// Test 3: Sales Tracking Column Structure
echo "<h2>📈 Sales Tracking Schema Test</h2>";

try {
    $stmt = $db->query("DESCRIBE sales_tracking");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedColumns = ['contacted', 'quoted', 'sales_qualified', 'to_win', 'wa_amount', 'tracking_status'];
    foreach ($expectedColumns as $col) {
        if (in_array($col, $columns)) {
            test_result("Column: $col", 'pass', "Column exists in sales_tracking table");
        } else {
            test_result("Column: $col", 'fail', "Column missing from sales_tracking table - run migration!");
        }
    }
    
    // Check for old 'sql' column
    if (in_array('sql', $columns)) {
        test_result("Legacy SQL Column", 'warn', "Old 'sql' column still exists - should be removed after migration");
    } else {
        test_result("Legacy SQL Column", 'pass', "Old 'sql' column properly removed");
    }
    
} catch (Exception $e) {
    test_result('Sales Tracking Schema', 'fail', $e->getMessage());
}

// Test 4: API Endpoints Test
echo "<h2>🌐 API Endpoints Test</h2>";

try {
    // Test critical API endpoints
    $endpoints = [
        'kpi' => '/api/v1/kpi',
        'funnel' => '/api/v1/charts/funnel',
        'available-months' => '/api/v1/available-months'
    ];
    
    foreach ($endpoints as $name => $endpoint) {
        $file = __DIR__ . '/api' . str_replace('/api/v1', '', $endpoint) . '.php';
        if (file_exists($file)) {
            test_result("API: $name", 'pass', "Endpoint file exists: $file");
        } else {
            test_result("API: $name", 'fail', "Endpoint file missing: $file");
        }
    }
    
} catch (Exception $e) {
    test_result('API Endpoints', 'fail', $e->getMessage());
}

// Test 5: Project Status Values
echo "<h2>📋 Project Status Configuration</h2>";

try {
    // Check projects table for new status values
    $stmt = $db->query("SELECT DISTINCT status FROM projects WHERE status IN ('Sales Qualified', 'Not Sales Qualified', 'SQL', 'Not SQL')");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasOldStatuses = in_array('SQL', $statuses) || in_array('Not SQL', $statuses);
    $hasNewStatuses = in_array('Sales Qualified', $statuses) || in_array('Not Sales Qualified', $statuses);
    
    if ($hasNewStatuses && !$hasOldStatuses) {
        test_result('Project Status Values', 'pass', 'Using correct status values');
    } elseif ($hasOldStatuses) {
        test_result('Project Status Values', 'warn', 'Still has old SQL/Not SQL status values in database');
    } else {
        test_result('Project Status Values', 'pass', 'No old status values found');
    }
    
} catch (Exception $e) {
    test_result('Project Status', 'fail', $e->getMessage());
}

// Test 6: File System Structure
echo "<h2>📁 File System Structure Test</h2>";

$criticalFiles = [
    'config.php',
    'api/db.php',
    'api/helpers.php',
    'api/router.php',
    'pages/reports.php',
    'pages/my-projects.php',
    'static/js/my-projects.js',
    'database/schema.sql',
    'database/migration_add_sales_funnel_fields.sql'
];

foreach ($criticalFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        test_result("File: $file", 'pass', "File exists");
    } else {
        test_result("File: $file", 'fail', "File missing: $fullPath");
    }
}

// Test 7: Migration Scripts
echo "<h2>🔧 Migration Scripts Test</h2>";

$migrationFiles = [
    'database/migration_add_sales_funnel_fields.sql',
    'database/migration_step_by_step.sql',
    'run_sales_funnel_migration.php'
];

foreach ($migrationFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        test_result("Migration: $file", 'pass', "Migration file exists");
    } else {
        test_result("Migration: $file", 'fail', "Migration file missing");
    }
}

// Final Summary
echo "<hr><h2>📊 Health Check Summary</h2>";
echo "<div class='pass'><strong>✅ Passes: $passes</strong></div>";
echo "<div class='warn'><strong>⚠️ Warnings: $warnings</strong></div>";
echo "<div class='fail'><strong>❌ Failures: $errors</strong></div>";
echo "<br>";

if ($errors === 0 && $warnings === 0) {
    echo "<div class='pass'><h3>🎉 System Health: EXCELLENT</h3><p>All checks passed! The system is ready for use.</p></div>";
} elseif ($errors === 0) {
    echo "<div class='warn'><h3>😊 System Health: GOOD</h3><p>System is functional but has some minor issues that should be addressed.</p></div>";
} else {
    echo "<div class='fail'><h3>🚨 System Health: NEEDS ATTENTION</h3><p>Critical issues found that need immediate fixing.</p></div>";
}

echo "<hr>";
echo "<h3>🔧 Next Steps:</h3>";
echo "<ul>";
if ($errors > 0) {
    echo "<li><strong>Critical:</strong> Fix all failed tests before using the system</li>";
}
if ($warnings > 0) {
    echo "<li><strong>Recommended:</strong> Address warnings for optimal performance</li>";
}
echo "<li>Run the sales funnel migration if needed: <code>run_sales_funnel_migration.php</code></li>";
echo "<li>Test the system by accessing <a href='pages/reports.php'>Reports Dashboard</a></li>";
echo "<li>Check database consistency with <a href='check_database.php'>Database Check</a></li>";
echo "</ul>";

echo "<p><small>Health check completed at " . date('Y-m-d H:i:s') . "</small></p>";
echo "</body></html>";
?>