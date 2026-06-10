<?php
/**
 * Clear "In Progress" Sales Tracking Data
 * 
 * This script will reset sales tracking records with status "In Progress"
 * Options:
 * 1. Delete the records completely
 * 2. Reset to "Not Started" (keep project assignment)
 * 3. View records only (no changes)
 */

// Set headers
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load config and database
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Clear In Progress Sales Tracking</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
    h2 { color: #333; border-bottom: 2px solid #ff9800; padding-bottom: 10px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .info { color: #2196F3; }
    .warning { color: #ff9800; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
    th { background-color: #ff9800; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .btn-container { margin: 20px 0; }
    .btn { display: inline-block; padding: 12px 24px; margin: 5px; border-radius: 4px; text-decoration: none; color: white; font-weight: bold; cursor: pointer; border: none; }
    .btn-danger { background-color: #f44336; }
    .btn-danger:hover { background-color: #d32f2f; }
    .btn-warning { background-color: #ff9800; }
    .btn-warning:hover { background-color: #f57c00; }
    .btn-secondary { background-color: #9e9e9e; }
    .btn-secondary:hover { background-color: #757575; }
    .btn-primary { background-color: #2196F3; }
    .btn-primary:hover { background-color: #1976D2; }
    .confirm-box { background: #fff3cd; border: 2px solid #ff9800; padding: 15px; border-radius: 4px; margin: 20px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h2>⚠️ Clear 'In Progress' Sales Tracking Data</h2>";
echo "<hr>";

try {
    $db = getDB();
    echo "<p class='success'>✅ Database connection successful</p>";
    
    // Get action from query parameter
    $action = $_GET['action'] ?? 'view';
    $confirm = $_GET['confirm'] ?? 'no';
    
    // Get all "In Progress" records
    $stmt = $db->query("
        SELECT 
            st.id,
            st.project_id,
            st.sales_rep_id,
            st.tracking_status,
            st.contacted,
            st.quoted,
            st.sales_qualified,
            st.to_win,
            st.wa_amount,
            st.branch,
            st.notes,
            st.created_at,
            st.updated_at,
            p.project_name,
            p.contractor_name,
            u.full_name as sales_rep_name
        FROM sales_tracking st
        LEFT JOIN projects p ON st.project_id = p.id
        LEFT JOIN users u ON st.sales_rep_id = u.id
        WHERE st.tracking_status = 'In Progress'
        ORDER BY st.updated_at DESC
    ");
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($records);
    
    echo "<h3>📊 Found $count 'In Progress' Records</h3>";
    
    if ($count === 0) {
        echo "<p class='info'>ℹ️ No 'In Progress' records found. Database is clean!</p>";
        echo "<p><a href='test-sales-tracking.php' class='btn btn-primary'>View All Sales Tracking</a></p>";
        echo "</div></body></html>";
        exit;
    }
    
    // Display records
    echo "<table>";
    echo "<thead><tr>";
    echo "<th>ID</th>";
    echo "<th>Project ID</th>";
    echo "<th>Project Name</th>";
    echo "<th>Contractor</th>";
    echo "<th>Sales Rep</th>";
    echo "<th>Branch</th>";
    echo "<th>Contacted</th>";
    echo "<th>Quoted</th>";
    echo "<th>SQL</th>";
    echo "<th>To Win</th>";
    echo "<th>W/A Amount</th>";
    echo "<th>Notes</th>";
    echo "<th>Updated</th>";
    echo "</tr></thead>";
    echo "<tbody>";
    
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($record['id']) . "</td>";
        echo "<td>" . htmlspecialchars($record['project_id']) . "</td>";
        echo "<td>" . htmlspecialchars($record['project_name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($record['contractor_name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($record['sales_rep_name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($record['branch'] ?? 'N/A') . "</td>";
        echo "<td>" . ($record['contacted'] ? htmlspecialchars($record['contacted']) : '-') . "</td>";
        echo "<td>" . ($record['quoted'] ? htmlspecialchars($record['quoted']) : '-') . "</td>";
        echo "<td>" . ($record['sales_qualified'] ? htmlspecialchars($record['sales_qualified']) : '-') . "</td>";
        echo "<td>" . ($record['to_win'] ? htmlspecialchars($record['to_win']) : '-') . "</td>";
        echo "<td>₱" . number_format($record['wa_amount'] ?? 0, 2) . "</td>";
        echo "<td>" . htmlspecialchars(substr($record['notes'] ?? '', 0, 30)) . "...</td>";
        echo "<td>" . htmlspecialchars($record['updated_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    // Show action buttons based on current action
    if ($action === 'view') {
        echo "<div class='confirm-box'>";
        echo "<h3>🔧 Choose Action:</h3>";
        echo "<p><strong>Option 1: Delete Records</strong> - Permanently delete all 'In Progress' tracking data</p>";
        echo "<p><strong>Option 2: Reset to 'Not Started'</strong> - Clear progress but keep project assignment to Sales Rep</p>";
        echo "<p><strong>Option 3: View Details</strong> - Show detailed JSON for each record</p>";
        echo "</div>";
        
        echo "<div class='btn-container'>";
        echo "<a href='?action=delete' class='btn btn-danger'>🗑️ Option 1: Delete All ($count records)</a>";
        echo "<a href='?action=reset' class='btn btn-warning'>🔄 Option 2: Reset to Not Started ($count records)</a>";
        echo "<a href='?action=details' class='btn btn-secondary'>📋 Option 3: View Details</a>";
        echo "</div>";
        
    } else if ($action === 'delete' && $confirm === 'no') {
        echo "<div class='confirm-box'>";
        echo "<h3>⚠️ CONFIRM DELETE</h3>";
        echo "<p class='error'>This will permanently delete $count sales tracking records!</p>";
        echo "<p><strong>This action CANNOT be undone!</strong></p>";
        echo "<p>Are you sure you want to continue?</p>";
        echo "</div>";
        
        echo "<div class='btn-container'>";
        echo "<a href='?action=delete&confirm=yes' class='btn btn-danger'>⚠️ YES, DELETE ALL</a>";
        echo "<a href='?action=view' class='btn btn-secondary'>❌ Cancel</a>";
        echo "</div>";
        
    } else if ($action === 'delete' && $confirm === 'yes') {
        // Perform deletion
        echo "<h3>🗑️ Deleting Records...</h3>";
        
        $deleteStmt = $db->prepare("DELETE FROM sales_tracking WHERE tracking_status = 'In Progress'");
        $deleteStmt->execute();
        $deleted = $deleteStmt->rowCount();
        
        echo "<p class='success'>✅ Successfully deleted $deleted records!</p>";
        echo "<p>The projects are now available for fresh sales tracking.</p>";
        
        echo "<div class='btn-container'>";
        echo "<a href='?action=view' class='btn btn-primary'>🔄 Refresh</a>";
        echo "<a href='test-sales-tracking.php' class='btn btn-secondary'>View All Tracking</a>";
        echo "</div>";
        
    } else if ($action === 'reset' && $confirm === 'no') {
        echo "<div class='confirm-box'>";
        echo "<h3>⚠️ CONFIRM RESET</h3>";
        echo "<p class='warning'>This will reset $count sales tracking records to 'Not Started'</p>";
        echo "<p>The following data will be cleared:</p>";
        echo "<ul>";
        echo "<li>Contacted, Quoted, Sales Qualified, To Win (all set to NULL)</li>";
        echo "<li>W/A Amount (set to 0)</li>";
        echo "<li>Notes (cleared)</li>";
        echo "<li>Status changed to 'Not Started'</li>";
        echo "</ul>";
        echo "<p>The following data will be KEPT:</p>";
        echo "<ul>";
        echo "<li>Project assignment (Sales Rep)</li>";
        echo "<li>Branch</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='btn-container'>";
        echo "<a href='?action=reset&confirm=yes' class='btn btn-warning'>🔄 YES, RESET ALL</a>";
        echo "<a href='?action=view' class='btn btn-secondary'>❌ Cancel</a>";
        echo "</div>";
        
    } else if ($action === 'reset' && $confirm === 'yes') {
        // Perform reset
        echo "<h3>🔄 Resetting Records...</h3>";
        
        $resetStmt = $db->prepare("
            UPDATE sales_tracking 
            SET 
                contacted = NULL,
                quoted = NULL,
                sales_qualified = NULL,
                to_win = NULL,
                wa_amount = 0.00,
                notes = NULL,
                tracking_status = 'Not Started',
                updated_at = NOW()
            WHERE tracking_status = 'In Progress'
        ");
        $resetStmt->execute();
        $reset = $resetStmt->rowCount();
        
        echo "<p class='success'>✅ Successfully reset $reset records to 'Not Started'!</p>";
        echo "<p>Project assignments and branches have been preserved.</p>";
        
        echo "<div class='btn-container'>";
        echo "<a href='?action=view' class='btn btn-primary'>🔄 Refresh</a>";
        echo "<a href='test-sales-tracking.php' class='btn btn-secondary'>View All Tracking</a>";
        echo "</div>";
        
    } else if ($action === 'details') {
        echo "<h3>📋 Detailed View</h3>";
        
        foreach ($records as $i => $record) {
            echo "<h4>Record #" . ($i + 1) . " - Project ID: " . htmlspecialchars($record['project_id']) . "</h4>";
            echo "<pre>" . htmlspecialchars(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        }
        
        echo "<div class='btn-container'>";
        echo "<a href='?action=view' class='btn btn-secondary'>⬅️ Back</a>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #999;'>";
echo "Clear In Progress Script | Generated: " . date('Y-m-d H:i:s');
echo "</p>";

echo "</div>";
echo "</body></html>";
?>
