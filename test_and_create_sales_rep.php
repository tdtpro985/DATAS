<?php
/**
 * Test and Create Sales Representative
 * This script checks if sales reps exist and creates one if needed
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

echo "<h2>🔍 Sales Representative Test & Creation</h2>";
echo "<hr>";

try {
    $db = getDB();
    
    // Check if sales reps exist
    echo "<h3>1. Checking existing sales representatives...</h3>";
    $stmt = $db->prepare("SELECT id, email, full_name, branch, role FROM users WHERE role = 'sales_rep'");
    $stmt->execute();
    $salesReps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($salesReps) > 0) {
        echo "<p style='color: green;'>✅ Found " . count($salesReps) . " sales representative(s):</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Email</th><th>Full Name</th><th>Branch</th></tr>";
        foreach ($salesReps as $rep) {
            echo "<tr>";
            echo "<td>{$rep['id']}</td>";
            echo "<td>{$rep['email']}</td>";
            echo "<td>{$rep['full_name']}</td>";
            echo "<td>" . ($rep['branch'] ?: 'No branch') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No sales representatives found</p>";
        
        // Create a test sales rep
        echo "<h3>2. Creating test sales representative...</h3>";
        
        $email = 'test_salesrep@tdtpowersteel.com';
        $fullName = 'Test Sales Representative';
        $branch = 'Manila Branch';
        $password = 'password123';
        
        // Check if this email already exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            echo "<p style='color: orange;'>⚠️ Test sales rep with email '$email' already exists but has wrong role</p>";
            
            // Update the role to sales_rep
            $updateStmt = $db->prepare("UPDATE users SET role = 'sales_rep', branch = ? WHERE email = ?");
            $updateStmt->execute([$branch, $email]);
            echo "<p style='color: green;'>✅ Updated user role to sales_rep</p>";
            
        } else {
            // Create new sales rep
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $insertStmt = $db->prepare("
                INSERT INTO users (email, full_name, branch, password_hash, role, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'sales_rep', NOW(), NOW())
            ");
            
            $result = $insertStmt->execute([$email, $fullName, $branch, $hashedPassword]);
            
            if ($result) {
                $newId = $db->lastInsertId();
                echo "<p style='color: green;'>✅ Created test sales representative:</p>";
                echo "<ul>";
                echo "<li><strong>ID:</strong> $newId</li>";
                echo "<li><strong>Email:</strong> $email</li>";
                echo "<li><strong>Password:</strong> $password</li>";
                echo "<li><strong>Full Name:</strong> $fullName</li>";
                echo "<li><strong>Branch:</strong> $branch</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create sales representative</p>";
            }
        }
    }
    
    // Test the API endpoint
    echo "<h3>3. Testing Sales Reps API Endpoint</h3>";
    
    $apiUrl = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/api/v1/users/sales-reps';
    echo "<p>Testing: <code>$apiUrl</code></p>";
    
    // Start a session to simulate authentication
    session_start();
    
    // Check if user is logged in
    if (empty($_SESSION['user'])) {
        echo "<p style='color: orange;'>⚠️ No user session found. Please login first to test the API.</p>";
        echo "<p><a href='pages/login.php'>Login here</a> then refresh this page.</p>";
    } else {
        $userRole = $_SESSION['user']['role'] ?? '';
        echo "<p>Current user: {$_SESSION['user']['full_name']} (Role: $userRole)</p>";
        
        if ($userRole === 'admin' || $userRole === 'superadmin') {
            // Use cURL to test the API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "<p style='color: red;'>❌ cURL Error: $error</p>";
            } elseif ($httpCode == 200) {
                echo "<p style='color: green;'>✅ API Response (HTTP $httpCode):</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                
                $jsonData = json_decode($response, true);
                if ($jsonData && isset($jsonData['users'])) {
                    echo "<p style='color: green;'>✅ API returned " . count($jsonData['users']) . " sales representatives</p>";
                } elseif ($jsonData && isset($jsonData['data'])) {
                    echo "<p style='color: green;'>✅ API returned " . count($jsonData['data']) . " sales representatives</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ API response format might be unexpected</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ API Error (HTTP $httpCode):</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Your role ($userRole) doesn't have permission to access sales reps API</p>";
            echo "<p>Only admin and superadmin can manage sales representatives.</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>4. Next Steps:</h3>";
    echo "<ul>";
    echo "<li>If you see sales reps above, the assignment should work</li>";
    echo "<li>If the API test shows errors, check the error details</li>";
    echo "<li>Make sure you're logged in as admin or superadmin</li>";
    echo "<li><a href='pages/projects-management.php?view=unassigned'>Test Project Assignment</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>