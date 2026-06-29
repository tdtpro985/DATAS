<?php
/* ============================================================
   check-assignments.php
   ============================================================
   Checks which projects are assigned to Dennis and Melody
   ============================================================ */

require_once __DIR__ . '/api/db.php';

$db = getDB();

echo "<h1>Project Assignment Check - Dennis vs Melody</h1>\n";
echo "<pre>\n";

// Dennis Espinar = ID 6
// Melody Nool = ID 10

echo "=== DENNIS ESPINAR (ID: 6) ===\n\n";

// Projects assigned to Dennis in projects table
$stmt = $db->prepare("
    SELECT 
        p.id,
        p.project_name,
        p.contractor_name,
        p.assigned_to,
        u.full_name as assigned_to_name,
        p.assigned_at
    FROM projects p
    LEFT JOIN users u ON p.assigned_to = u.id
    WHERE p.assigned_to = 6
    AND p.archived_at IS NULL
    ORDER BY p.id
");
$stmt->execute();
$dennisProjects = $stmt->fetchAll();

echo "Projects in 'projects' table (assigned_to = 6): " . count($dennisProjects) . "\n";
foreach ($dennisProjects as $p) {
    echo "  - Project #{$p['id']}: {$p['project_name']} (Contractor: {$p['contractor_name']})\n";
    echo "    Assigned to: {$p['assigned_to_name']} on {$p['assigned_at']}\n";
}

// Check sales_tracking for Dennis
echo "\n\nSales tracking records for Dennis:\n";
$stmt = $db->prepare("
    SELECT 
        st.id,
        st.project_id,
        st.sales_rep_id,
        u.full_name as sales_rep_name,
        p.project_name,
        p.assigned_to as project_assigned_to,
        u2.full_name as project_assigned_to_name
    FROM sales_tracking st
    LEFT JOIN users u ON st.sales_rep_id = u.id
    LEFT JOIN projects p ON st.project_id = p.id
    LEFT JOIN users u2 ON p.assigned_to = u2.id
    WHERE st.sales_rep_id = 6
    ORDER BY st.project_id
");
$stmt->execute();
$dennisSalesTracking = $stmt->fetchAll();

echo "Sales tracking records (sales_rep_id = 6): " . count($dennisSalesTracking) . "\n";
foreach ($dennisSalesTracking as $st) {
    $mismatch = ($st['project_assigned_to'] != $st['sales_rep_id']) ? " ⚠️ MISMATCH!" : "";
    echo "  - ST #{$st['id']}, Project #{$st['project_id']}: {$st['project_name']}\n";
    echo "    Sales Tracking Rep: {$st['sales_rep_name']} (ID: {$st['sales_rep_id']})\n";
    echo "    Project Assigned To: {$st['project_assigned_to_name']} (ID: {$st['project_assigned_to']}){$mismatch}\n";
}

echo "\n\n=== MELODY NOOL (ID: 10) ===\n\n";

// Projects assigned to Melody in projects table
$stmt = $db->prepare("
    SELECT 
        p.id,
        p.project_name,
        p.contractor_name,
        p.assigned_to,
        u.full_name as assigned_to_name,
        p.assigned_at
    FROM projects p
    LEFT JOIN users u ON p.assigned_to = u.id
    WHERE p.assigned_to = 10
    AND p.archived_at IS NULL
    ORDER BY p.id
");
$stmt->execute();
$melodyProjects = $stmt->fetchAll();

echo "Projects in 'projects' table (assigned_to = 10): " . count($melodyProjects) . "\n";
foreach ($melodyProjects as $p) {
    echo "  - Project #{$p['id']}: {$p['project_name']} (Contractor: {$p['contractor_name']})\n";
    echo "    Assigned to: {$p['assigned_to_name']} on {$p['assigned_at']}\n";
}

// Check sales_tracking for Melody
echo "\n\nSales tracking records for Melody:\n";
$stmt = $db->prepare("
    SELECT 
        st.id,
        st.project_id,
        st.sales_rep_id,
        u.full_name as sales_rep_name,
        p.project_name,
        p.assigned_to as project_assigned_to,
        u2.full_name as project_assigned_to_name
    FROM sales_tracking st
    LEFT JOIN users u ON st.sales_rep_id = u.id
    LEFT JOIN projects p ON st.project_id = p.id
    LEFT JOIN users u2 ON p.assigned_to = u2.id
    WHERE st.sales_rep_id = 10
    ORDER BY st.project_id
");
$stmt->execute();
$melodySalesTracking = $stmt->fetchAll();

echo "Sales tracking records (sales_rep_id = 10): " . count($melodySalesTracking) . "\n";
foreach ($melodySalesTracking as $st) {
    $mismatch = ($st['project_assigned_to'] != $st['sales_rep_id']) ? " ⚠️ MISMATCH!" : "";
    echo "  - ST #{$st['id']}, Project #{$st['project_id']}: {$st['project_name']}\n";
    echo "    Sales Tracking Rep: {$st['sales_rep_name']} (ID: {$st['sales_rep_id']})\n";
    echo "    Project Assigned To: {$st['project_assigned_to_name']} (ID: {$st['project_assigned_to']}){$mismatch}\n";
}

echo "\n\n=== SUMMARY ===\n\n";
echo "Dennis Projects (projects.assigned_to): " . count($dennisProjects) . "\n";
echo "Dennis Sales Tracking Records: " . count($dennisSalesTracking) . "\n";
echo "Melody Projects (projects.assigned_to): " . count($melodyProjects) . "\n";
echo "Melody Sales Tracking Records: " . count($melodySalesTracking) . "\n";

echo "\n</pre>\n";
