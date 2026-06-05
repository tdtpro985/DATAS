<?php
/* ============================================================
   Platform Sales Tracking API
   GET - Load tracking data for a platform
   POST - Save platform sales tracking
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

try {
    $user = requireAuth();
} catch (Exception $e) {
    jsonError('Authentication failed: ' . $e->getMessage(), 401);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - Load tracking data
if ($method === 'GET') {
    $platformId = $_GET['platform_id'] ?? null;
    
    if (!$platformId) {
        jsonError('platform_id is required', 400);
    }
    
    try {
        $db = getDB();
        
        // Get tracking data
        $stmt = $db->prepare('
            SELECT contacted, quoted, sales_qualified, to_win, wa_amount, notes
            FROM platform_tracking 
            WHERE platform_id = :platform_id
        ');
        $stmt->execute([':platform_id' => $platformId]);
        $tracking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tracking) {
            jsonResponse($tracking);
        } else {
            // Return empty tracking data
            jsonResponse([
                'contacted' => null,
                'quoted' => null,
                'sales_qualified' => null,
                'to_win' => null,
                'wa_amount' => '0.00',
                'notes' => null
            ]);
        }
        
    } catch (Exception $e) {
        jsonError('Database error: ' . $e->getMessage(), 500);
    }
}

// POST - Save tracking data
if ($method === 'POST') {
    $body = getJsonBody();
    if (!$body) {
        jsonError('Request body is required', 400);
    }

    $platformId = $body['platform_id'] ?? null;

    if (!$platformId) {
        jsonError('platform_id is required', 400);
    }

    try {
        $db = getDB();
        
        // Check if platform exists
        $stmt = $db->prepare('SELECT id FROM platform_leads WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $platformId]);
        if (!$stmt->fetch()) {
            jsonError('Platform not found', 404);
        }
        
        // Prepare tracking data
        $contacted = $body['contacted'] ?? null;
        $quoted = $body['quoted'] ?? null;
        $salesQualified = $body['sales_qualified'] ?? null;
        $toWin = $body['to_win'] ?? null;
        $waAmount = isset($body['wa_amount']) && $body['wa_amount'] !== '' ? (float)$body['wa_amount'] : null;
        $notes = $body['notes'] ?? null;
        
        // Check if tracking record exists
        $stmt = $db->prepare('SELECT id FROM platform_tracking WHERE platform_id = :platform_id');
        $stmt->execute([':platform_id' => $platformId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing record
            $stmt = $db->prepare("
                UPDATE platform_tracking 
                SET contacted = :contacted,
                    quoted = :quoted,
                    sales_qualified = :sales_qualified,
                    to_win = :to_win,
                    wa_amount = :wa_amount,
                    notes = :notes,
                    updated_at = NOW()
                WHERE platform_id = :platform_id
            ");
        } else {
            // Insert new record
            $stmt = $db->prepare("
                INSERT INTO platform_tracking 
                (platform_id, contacted, quoted, sales_qualified, to_win, wa_amount, notes, created_at, updated_at)
                VALUES 
                (:platform_id, :contacted, :quoted, :sales_qualified, :to_win, :wa_amount, :notes, NOW(), NOW())
            ");
        }
        
        $stmt->execute([
            ':platform_id' => $platformId,
            ':contacted' => $contacted,
            ':quoted' => $quoted,
            ':sales_qualified' => $salesQualified,
            ':to_win' => $toWin,
            ':wa_amount' => $waAmount,
            ':notes' => $notes
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Sales tracking saved successfully'
        ]);
        
    } catch (Exception $e) {
        jsonError('Database error: ' . $e->getMessage(), 500);
    }
}

jsonError('Method not allowed', 405);
