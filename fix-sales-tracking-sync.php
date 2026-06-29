<?php
/* ============================================================
   fix-sales-tracking-sync.php
   ============================================================
   Fixes synchronization issues between projects.assigned_to
   and sales_tracking.sales_rep_id
   ============================================================ */

require_once __DIR__ . '/api/db.php';

$db = getDB();

echo "<h1>Sales Tracking Synchronization Fix</h1>\n";
echo "<pre>\n";

// Step 1: Find mismatches
echo "Step 1: Finding mismatches between projects.assigned_to and sales_tracking.sales_rep_id...\n\n";

$stmt = $db->query("
    SELECT 
        p.id as project_id,
        p.project_name,
        p.assigned_to as project_assigned_to,
        u1.full_name as assigned_to_name,
        st.sales_rep_id as tracking_sales_rep_id,
        u2.full_name as tracking_sales_rep_name
    FROM projects p
    LEFT JOIN sales_tracking st ON p.id = st.project_id
    LEFT JOIN users u1 ON p.assigned_to = u1.id
    LEFT JOIN users u2 ON st.sales_rep_id = u2.id
    WHERE p.assigned_to IS NOT NULL
    AND st.id IS NOT NULL
    AND p.assigned_to != st.sales_rep_id
    ORDER BY p.id
");

$mismatches = $stmt->fetchAll();

if (empty($mismatches)) {
    echo "✓ No mismatches found! All sales_tracking records are synchronized.\n";
} else {
    echo "✗ Found " . count($mismatches) . " mismatches:\n\n";
    
    foreach ($mismatches as $row) {
        echo "Project ID: {$row['project_id']}\n";
        echo "  Project Name: {$row['project_name']}\n";
        echo "  projects.assigned_to: {$row['project_assigned_to']} ({$row['assigned_to_name']})\n";
        echo "  sales_tracking.sales_rep_id: {$row['tracking_sales_rep_id']} ({$row['tracking_sales_rep_name']})\n";
        echo "  → Will be updated to match projects.assigned_to\n\n";
    }
    
    // Step 2: Fix the mismatches
    echo "\nStep 2: Fixing mismatches...\n\n";
    
    $updateStmt = $db->prepare("
        UPDATE sales_tracking 
        SET sales_rep_id = :correct_sales_rep_id,
            updated_at = NOW()
        WHERE project_id = :project_id
    ");
    
    $fixed = 0;
    foreach ($mismatches as $row) {
        $updateStmt->execute([
            ':correct_sales_rep_id' => $row['project_assigned_to'],
            ':project_id' => $row['project_id']
        ]);
        echo "✓ Fixed project #{$row['project_id']}: Updated sales_rep_id from {$row['tracking_sales_rep_id']} to {$row['project_assigned_to']}\n";
        $fixed++;
    }
    
    echo "\n✓ Fixed {$fixed} records!\n";
}

// Step 3: Find orphaned sales_tracking records (no matching project assignment)
echo "\n\nStep 3: Finding orphaned sales_tracking records...\n\n";

$stmt = $db->query("
    SELECT 
        st.id,
        st.project_id,
        st.sales_rep_id,
        u.full_name as sales_rep_name,
        p.project_name,
        p.assigned_to as project_assigned_to
    FROM sales_tracking st
    LEFT JOIN projects p ON st.project_id = p.id
    LEFT JOIN users u ON st.sales_rep_id = u.id
    WHERE p.assigned_to IS NULL
    OR p.assigned_to = 0
    ORDER BY st.id
");

$orphaned = $stmt->fetchAll();

if (empty($orphaned)) {
    echo "✓ No orphaned sales_tracking records found!\n";
} else {
    echo "✗ Found " . count($orphaned) . " orphaned sales_tracking records:\n\n";
    
    foreach ($orphaned as $row) {
        echo "Sales Tracking ID: {$row['id']}\n";
        echo "  Project ID: {$row['project_id']}\n";
        echo "  Project Name: {$row['project_name']}\n";
        echo "  Sales Rep: {$row['sales_rep_name']} (ID: {$row['sales_rep_id']})\n";
        echo "  projects.assigned_to: " . ($row['project_assigned_to'] ?? 'NULL') . "\n";
        echo "  → This record will be deleted\n\n";
    }
    
    // Optional: Delete orphaned records
    echo "\nStep 4: Cleaning up orphaned records...\n\n";
    
    $deleteStmt = $db->prepare("
        DELETE FROM sales_tracking 
        WHERE project_id IN (
            SELECT id FROM projects WHERE assigned_to IS NULL OR assigned_to = 0
        )
    ");
    
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "✓ Deleted {$deleted} orphaned sales_tracking records!\n";
}

echo "\n\n=== COMPLETE ===\n";
echo "All sales_tracking records are now synchronized with projects.assigned_to!\n";
echo "</pre>\n";
