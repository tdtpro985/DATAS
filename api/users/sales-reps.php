<?php
/* ============================================================
   api/users/sales-reps.php — Sales Representatives Management API
   ============================================================
   Endpoints:
   - GET    /api/users/sales-reps       - List all sales reps
   - POST   /api/users/sales-reps       - Create new sales rep
   - PUT    /api/users/sales-reps/:id   - Update sales rep
   - DELETE /api/users/sales-reps/:id   - Delete sales rep
   ============================================================ */

// Start output buffering to catch any stray output
ob_start();

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

// Check authentication using helper function
try {
    // Use the helper function for consistent authentication
    $user = requireAuth();
    $role = $user['role'] ?? '';

    // Only superadmin, admin, and sales_rep can view sales reps
    if (!in_array($role, ['superadmin', 'admin', 'sales_rep'], true)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
} catch (Exception $e) {
    // Helper function will handle sending 401 response
    // This catch block is just for safety
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Authentication error: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

// Parse ID from URL if present
$requestUri = $_SERVER['REQUEST_URI'];
$pattern = '#/api/users/sales-reps/(\d+)#';
$userId = null;
if (preg_match($pattern, $requestUri, $matches)) {
    $userId = (int)$matches[1];
}

try {
    // Clean any output that might have been generated
    ob_clean();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        
        case 'POST':
            handlePost($pdo);
            break;
        
        case 'PUT':
            if (!$userId) {
                throw new Exception('User ID required');
            }
            handlePut($pdo, $userId);
            break;
        
        case 'DELETE':
            if (!$userId) {
                throw new Exception('User ID required');
            }
            handleDelete($pdo, $userId);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    // Clean any output before sending error
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Flush the output buffer
ob_end_flush();

/* ============================================================
   GET - List all sales representatives
   ============================================================ */
function handleGet($pdo) {
    // Get optional filters from query parameters
    $region = $_GET['region'] ?? '';
    $province = $_GET['province'] ?? '';
    $city = $_GET['city'] ?? '';
    
    // Simple approach: get all sales reps first, then calculate match scores in PHP
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.email, 
            u.full_name, 
            u.branch, 
            u.totp_secret, 
            u.created_at, 
            u.updated_at,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM user_sessions 
                    WHERE user_id = u.id 
                    AND last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ) THEN 1 
                ELSE 0 
            END as is_online,
            (
                SELECT MAX(last_activity) 
                FROM user_sessions 
                WHERE user_id = u.id
            ) as last_seen
        FROM users u
        WHERE u.role = 'sales_rep'
        ORDER BY u.created_at DESC
    ");
    
    $stmt->execute();
    $salesReps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate match scores in PHP for better debugging
    foreach ($salesReps as &$rep) {
        $rep['is_online'] = (bool)$rep['is_online'];
        $rep['match_score'] = 0;
        $rep['is_suggested'] = false;
        
        $branch = strtolower($rep['branch'] ?? '');
        $regionLower = strtolower($region);
        $provinceLower = strtolower($province);
        $cityLower = strtolower($city);
        
        // Calculate match score
        if (!empty($region) && strpos($branch, strtolower($region)) !== false) {
            $rep['match_score'] = 100;
        } elseif (!empty($province) && strpos($branch, strtolower($province)) !== false) {
            $rep['match_score'] = 90;
        } elseif (!empty($city) && strpos($branch, strtolower($city)) !== false) {
            $rep['match_score'] = 80;
        }
        
        // Special Manila/NCR matching
        if (stripos($regionLower, 'ncr') !== false || stripos($regionLower, 'manila') !== false || 
            stripos($cityLower, 'manila') !== false || stripos($provinceLower, 'manila') !== false) {
            
            if (stripos($branch, 'manila') !== false) {
                $rep['match_score'] = max($rep['match_score'], 100);
            } elseif (stripos($branch, 'ncr') !== false) {
                $rep['match_score'] = max($rep['match_score'], 95);
            } elseif (stripos($branch, 'metro manila') !== false) {
                $rep['match_score'] = max($rep['match_score'], 90);
            } elseif (stripos($branch, 'makati') !== false || stripos($branch, 'quezon') !== false) {
                $rep['match_score'] = max($rep['match_score'], 85);
            }
        }
        
        $rep['is_suggested'] = $rep['match_score'] > 0;
    }
    
    // Sort by match score (highest first), then by creation date
    usort($salesReps, function($a, $b) {
        if ($a['match_score'] !== $b['match_score']) {
            return $b['match_score'] - $a['match_score'];
        }
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    echo json_encode([
        'success' => true,
        'data' => $salesReps,     // Primary response property
        'users' => $salesReps,    // Alternative property for compatibility
        'debug' => [
            'region' => $region,
            'province' => $province, 
            'city' => $city,
            'total_reps' => count($salesReps),
            'suggested_count' => count(array_filter($salesReps, function($rep) { return $rep['is_suggested']; }))
        ]
    ]);
}

/* ============================================================
   POST - Create new sales representative
   ============================================================ */
function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['email']) || empty($input['full_name']) || empty($input['password'])) {
        throw new Exception('Email, full name, and password are required');
    }
    
    if (empty($input['branch'])) {
        throw new Exception('Branch is required');
    }
    
    $email = trim($input['email']);
    $fullName = trim($input['full_name']);
    $branch = trim($input['branch']);
    $password = $input['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Email already exists');
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert new sales rep
    $stmt = $pdo->prepare("
        INSERT INTO users (email, full_name, branch, password_hash, role)
        VALUES (?, ?, ?, ?, 'sales_rep')
    ");
    $stmt->execute([$email, $fullName, $branch, $passwordHash]);
    
    $newId = $pdo->lastInsertId();
    
    // Fetch the created user
    $stmt = $pdo->prepare("
        SELECT id, email, full_name, branch, totp_secret, created_at, updated_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$newId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sales representative created successfully',
        'data' => $user
    ]);
}

/* ============================================================
   PUT - Update sales representative
   ============================================================ */
function handlePut($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if user exists and is a sales rep
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    if ($user['role'] !== 'sales_rep') {
        throw new Exception('User is not a sales representative');
    }
    
    // Validate required fields
    if (empty($input['email']) || empty($input['full_name'])) {
        throw new Exception('Email and full name are required');
    }
    
    if (empty($input['branch'])) {
        throw new Exception('Branch is required');
    }
    
    $email = trim($input['email']);
    $fullName = trim($input['full_name']);
    $branch = trim($input['branch']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Check if email is taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        throw new Exception('Email already exists');
    }
    
    // Update user
    if (!empty($input['password'])) {
        // Update with new password
        if (strlen($input['password']) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        
        $passwordHash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("
            UPDATE users
            SET email = ?, full_name = ?, branch = ?, password_hash = ?
            WHERE id = ?
        ");
        $stmt->execute([$email, $fullName, $branch, $passwordHash, $userId]);
    } else {
        // Update without changing password
        $stmt = $pdo->prepare("
            UPDATE users
            SET email = ?, full_name = ?, branch = ?
            WHERE id = ?
        ");
        $stmt->execute([$email, $fullName, $branch, $userId]);
    }
    
    // Fetch updated user
    $stmt = $pdo->prepare("
        SELECT id, email, full_name, branch, totp_secret, created_at, updated_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sales representative updated successfully',
        'data' => $updatedUser
    ]);
}

/* ============================================================
   DELETE - Delete sales representative
   ============================================================ */
function handleDelete($pdo, $userId) {
    // Check if user exists and is a sales rep
    $stmt = $pdo->prepare("SELECT id, role, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    if ($user['role'] !== 'sales_rep') {
        throw new Exception('User is not a sales representative');
    }
    
    // Prevent deleting yourself
    if ($userId === $_SESSION['user']['id']) {
        throw new Exception('Cannot delete your own account');
    }
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sales representative deleted successfully'
    ]);
}
