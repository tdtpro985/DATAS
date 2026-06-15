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
$pattern = '#/api/v1/users/sales-reps/(\d+)#';
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
   GET - List all sales representatives WITH ENHANCED RECOMMENDATIONS
   ============================================================ */
function handleGet($pdo) {
    // Get optional filters from query parameters
    $region = $_GET['region'] ?? '';
    $province = $_GET['province'] ?? '';
    $city = $_GET['city'] ?? '';
    
    // Get assigned project count for workload balancing
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
            ) as last_seen,
            (
                SELECT COUNT(*) 
                FROM projects 
                WHERE assigned_to = u.id
            ) as assigned_count,
            (
                SELECT COUNT(DISTINCT p.id)
                FROM projects p
                WHERE p.assigned_to = u.id
                   OR p.id IN (SELECT st.project_id FROM sales_tracking st WHERE st.sales_rep_id = u.id)
            ) as total_projects_count
        FROM users u
        WHERE u.role IN ('sales_rep', 'admin')
        ORDER BY u.full_name ASC
    ");
    
    $stmt->execute();
    $salesReps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate match scores with enhanced algorithm
    foreach ($salesReps as &$rep) {
        $rep['is_online'] = (bool)$rep['is_online'];
        $rep['match_score'] = 0;
        $rep['is_suggested'] = false;
        $rep['match_reason'] = '';
        
        $branch = strtolower($rep['branch'] ?? '');
        $regionLower = strtolower($region);
        $provinceLower = strtolower($province);
        $cityLower = strtolower($city);
        
        // Primary matching: Province (most specific and important)
        if (!empty($province)) {
            // Exact province match
            if (strpos($branch, strtolower($province)) !== false) {
                $rep['match_score'] = 100;
                $rep['match_reason'] = 'Branch matches project province';
            }
            // Partial province match (e.g., "Laguna" in "Laguna de Bay Branch")
            elseif (stripos($branch, $province) !== false) {
                $rep['match_score'] = 95;
                $rep['match_reason'] = 'Branch located in project province';
            }
        }
        
        // Secondary matching: Region
        if ($rep['match_score'] === 0 && !empty($region)) {
            if (stripos($branch, $region) !== false) {
                $rep['match_score'] = 85;
                $rep['match_reason'] = 'Branch matches project region';
            }
        }
        
        // Tertiary matching: City
        if ($rep['match_score'] === 0 && !empty($city)) {
            if (stripos($branch, $city) !== false) {
                $rep['match_score'] = 75;
                $rep['match_reason'] = 'Branch matches project city';
            }
        }
        
        // ===== SPECIAL CASES =====
        
        // Manila/NCR special handling (many naming variations)
        $isNCRProject = (
            stripos($regionLower, 'ncr') !== false || 
            stripos($regionLower, 'manila') !== false ||
            stripos($regionLower, 'national capital') !== false ||
            stripos($provinceLower, 'manila') !== false || 
            stripos($provinceLower, 'metro manila') !== false ||
            stripos($cityLower, 'manila') !== false ||
            stripos($cityLower, 'quezon') !== false ||
            stripos($cityLower, 'makati') !== false ||
            stripos($cityLower, 'caloocan') !== false
        );
        
        if ($isNCRProject) {
            if (stripos($branch, 'manila') !== false) {
                $rep['match_score'] = max($rep['match_score'], 100);
                $rep['match_reason'] = 'Manila branch - perfect match for NCR project';
            } elseif (stripos($branch, 'ncr') !== false) {
                $rep['match_score'] = max($rep['match_score'], 100);
                $rep['match_reason'] = 'NCR branch - perfect match';
            } elseif (stripos($branch, 'metro manila') !== false) {
                $rep['match_score'] = max($rep['match_score'], 95);
                $rep['match_reason'] = 'Metro Manila branch';
            } elseif (stripos($branch, 'makati') !== false || stripos($branch, 'quezon') !== false || 
                      stripos($branch, 'taguig') !== false || stripos($branch, 'pasig') !== false) {
                $rep['match_score'] = max($rep['match_score'], 90);
                $rep['match_reason'] = 'Located in NCR area';
            }
        }
        
        // Cebu special cases
        if (stripos($provinceLower, 'cebu') !== false || stripos($cityLower, 'cebu') !== false) {
            if (stripos($branch, 'cebu') !== false) {
                $rep['match_score'] = max($rep['match_score'], 100);
                $rep['match_reason'] = 'Cebu branch - perfect match';
            } elseif (stripos($branch, 'visayas') !== false || stripos($branch, 'central visayas') !== false) {
                $rep['match_score'] = max($rep['match_score'], 85);
                $rep['match_reason'] = 'Central Visayas branch';
            }
        }
        
        // Davao special cases
        if (stripos($provinceLower, 'davao') !== false || stripos($cityLower, 'davao') !== false) {
            if (stripos($branch, 'davao') !== false) {
                $rep['match_score'] = max($rep['match_score'], 100);
                $rep['match_reason'] = 'Davao branch - perfect match';
            } elseif (stripos($branch, 'mindanao') !== false) {
                $rep['match_score'] = max($rep['match_score'], 80);
                $rep['match_reason'] = 'Mindanao branch';
            }
        }
        
        // Workload balancing adjustment (slight boost for less loaded reps)
        if ($rep['match_score'] > 0) {
            $assignedCount = (int)($rep['assigned_count'] ?? 0);
            
            // Give small bonus to reps with fewer assignments
            if ($assignedCount === 0) {
                $rep['match_score'] = min(100, $rep['match_score'] + 5);
                $rep['match_reason'] .= ' (Available)';
            } elseif ($assignedCount < 5) {
                $rep['match_score'] = min(100, $rep['match_score'] + 2);
            }
        }
        
        // Mark as suggested if score > 70 (good match threshold)
        $rep['is_suggested'] = $rep['match_score'] >= 70;
    }
    
    // Sort by match score (highest first), then by workload (fewest first), then by creation date
    usort($salesReps, function($a, $b) {
        if ($a['match_score'] !== $b['match_score']) {
            return $b['match_score'] - $a['match_score'];
        }
        
        // If same score, prefer rep with fewer assignments
        $aAssigned = (int)($a['assigned_count'] ?? 0);
        $bAssigned = (int)($b['assigned_count'] ?? 0);
        if ($aAssigned !== $bAssigned) {
            return $aAssigned - $bAssigned;
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
