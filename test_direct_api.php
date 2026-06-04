<?php
/**
 * Direct API Test
 * This script directly includes and tests the sales-reps API
 */

echo "<h2>🔧 Direct API Test</h2>";

// Start session
session_start();

echo "<h3>Session Check</h3>";
if (empty($_SESSION['user'])) {
    echo "<p style='color: red;'>❌ No session found. Please login first.</p>";
    echo "<p><a href='pages/login.php'>Login here</a></p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Session found: " . $_SESSION['user']['full_name'] . " (" . $_SESSION['user']['role'] . ")</p>";
}

echo "<h3>Direct API Include Test</h3>";

// Capture the output
ob_start();

try {
    // Set up fake request environment
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/DATAS/api/v1/users/sales-reps';
    
    // Include the sales-reps API directly
    include __DIR__ . '/api/users/sales-reps.php';
    
    $output = ob_get_contents();
    
} catch (Exception $e) {
    $output = json_encode(['error' => $e->getMessage()]);
}

ob_end_clean();

echo "<p>API Response:</p>";
echo "<pre style='background: #f5f5f5; padding: 1rem; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars($output);
echo "</pre>";

// Try to decode JSON
$jsonData = json_decode($output, true);
if ($jsonData) {
    echo "<p>Parsed JSON:</p>";
    echo "<pre>";
    print_r($jsonData);
    echo "</pre>";
}

echo "<hr>";
echo "<p><a href='pages/projects-management.php'>← Back to Project Management</a></p>";
?>