<?php
/**
 * Verify Rebuild - Quick Verification Script
 * This script verifies that all components of the rebuilt assignment system are working
 */

session_start();
$success = true;
$issues = [];

echo "<style>
body { font-family: system-ui; max-width: 800px; margin: 0 auto; padding: 2rem; line-height: 1.6; }
.check { margin: 0.5rem 0; }
.success { color: #10b981; }
.error { color: #ef4444; }
.warning { color: #f59e0b; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
.btn { background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
</style>";

echo "<h1>🔍 Rebuild Verification</h1>";

// Check 1: Session
echo "<h2>1. Session Check</h2>";
if (!empty($_SESSION['user'])) {
    echo "<div class='check success'>✅ User logged in: {$_SESSION['user']['full_name']} ({$_SESSION['user']['role']})</div>";
    
    if (!in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
        echo "<div class='check warning'>⚠️ User role cannot test assignment functionality</div>";
        $issues[] = "User role is not admin/superadmin";
    }
} else {
    echo "<div class='check error'>❌ No user session found</div>";
    $success = false;
    $issues[] = "User not logged in";
}

// Check 2: API Files
echo "<h2>2. API Files Check</h2>";
$apiFiles = [
    'api/users/sales-reps.php' => 'Sales Reps API',
    'api/projects/bulk-assign.php' => 'Bulk Assignment API',
    'api/helpers.php' => 'Helper Functions',
    'api/router.php' => 'API Router'
];

foreach ($apiFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<div class='check success'>✅ {$description} exists</div>";
    } else {
        echo "<div class='check error'>❌ {$description} missing: {$file}</div>";
        $success = false;
        $issues[] = "{$description} file missing";
    }
}

// Check 3: Database Connection
echo "<h2>3. Database Check</h2>";
try {
    require_once __DIR__ . '/api/db.php';
    $db = getDB();
    echo "<div class='check success'>✅ Database connection successful</div>";
    
    // Check tables
    $tables = ['users', 'projects'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<div class='check success'>✅ Table '{$table}' exists</div>";
        } else {
            echo "<div class='check error'>❌ Table '{$table}' missing</div>";
            $success = false;
            $issues[] = "Database table '{$table}' missing";
        }
    }
    
    // Check sales reps
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'sales_rep'");
    $stmt->execute();
    $salesRepCount = $stmt->fetch()['count'];
    echo "<div class='check success'>✅ Sales representatives in database: {$salesRepCount}</div>";
    
    if ($salesRepCount == 0) {
        echo "<div class='check warning'>⚠️ No sales reps found - assignment won't work</div>";
        $issues[] = "No sales representatives found";
    }
    
} catch (Exception $e) {
    echo "<div class='check error'>❌ Database error: " . $e->getMessage() . "</div>";
    $success = false;
    $issues[] = "Database connection failed";
}

// Check 4: JavaScript Files
echo "<h2>4. JavaScript Files Check</h2>";
$jsFiles = [
    'static/js/projects-management.js' => 'Project Management JS'
];

foreach ($jsFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<div class='check success'>✅ {$description} exists</div>";
        
        // Check for key functions
        $content = file_get_contents(__DIR__ . '/' . $file);
        $functions = [
            'openSalesRepModal' => 'Sales Rep Modal Function',
            'loadSalesRepresentatives' => 'Load Sales Reps Function',
            'selectSalesRep' => 'Select Sales Rep Function',
            'proceedWithAssignment' => 'Assignment Function',
            'assignmentState' => 'Assignment State Object'
        ];
        
        foreach ($functions as $func => $desc) {
            if (strpos($content, $func) !== false) {
                echo "<div class='check success'>✅ {$desc} found</div>";
            } else {
                echo "<div class='check error'>❌ {$desc} missing</div>";
                $success = false;
                $issues[] = "{$desc} not found in JavaScript";
            }
        }
    } else {
        echo "<div class='check error'>❌ {$description} missing</div>";
        $success = false;
        $issues[] = "{$description} file missing";
    }
}

// Check 5: Modal HTML
echo "<h2>5. Modal HTML Check</h2>";
if (file_exists(__DIR__ . '/pages/projects-management.php')) {
    $content = file_get_contents(__DIR__ . '/pages/projects-management.php');
    if (strpos($content, 'salesRepModal') !== false) {
        echo "<div class='check success'>✅ Sales Rep Modal HTML found</div>";
    } else {
        echo "<div class='check error'>❌ Sales Rep Modal HTML missing</div>";
        $success = false;
        $issues[] = "Sales Rep Modal HTML missing";
    }
    
    if (strpos($content, 'salesRepList') !== false) {
        echo "<div class='check success'>✅ Sales Rep List container found</div>";
    } else {
        echo "<div class='check error'>❌ Sales Rep List container missing</div>";
        $success = false;
        $issues[] = "Sales Rep List container missing";
    }
} else {
    echo "<div class='check error'>❌ Projects management page missing</div>";
    $success = false;
    $issues[] = "Projects management page missing";
}

// Final Result
echo "<h2>📋 Summary</h2>";
if ($success && empty($issues)) {
    echo "<div style='background: #d1fae5; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h3 style='color: #10b981; margin: 0 0 0.5rem;'>✅ Rebuild Verification Successful!</h3>";
    echo "<p>All components are in place and ready for testing.</p>";
    echo "</div>";
    
    echo "<h3>🧪 Next Steps</h3>";
    echo "<ol>";
    echo "<li><a href='test_rebuilt_assignment.php' class='btn'>Run Rebuilt System Test</a></li>";
    echo "<li><a href='pages/projects-management.php?view=unassigned' class='btn'>Test Live Interface</a></li>";
    echo "</ol>";
} else {
    echo "<div style='background: #fee2e2; border: 1px solid #ef4444; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h3 style='color: #ef4444; margin: 0 0 0.5rem;'>❌ Issues Found</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>{$issue}</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='test_rebuilt_assignment.php'>← Test Rebuilt System</a> | <a href='pages/projects-management.php'>Project Management →</a></p>";
?>