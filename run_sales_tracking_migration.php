<?php
/* ============================================================
   Sales Tracking Migration Runner
   ============================================================
   This script applies the sales tracking migration to add
   the required columns for the sales funnel functionality.
   ============================================================ */

require_once __DIR__ . '/api/db.php';

echo "Sales Tracking Migration Runner\n";
echo "===============================\n\n";

try {
    $db = getDB();
    
    // Check if columns already exist
    $checkStmt = $db->query("SHOW COLUMNS FROM sales_tracking LIKE 'contacted'");
    if ($checkStmt->rowCount() > 0) {
        echo "✅ Sales tracking columns already exist. Migration not needed.\n";
        exit(0);
    }
    
    echo "📋 Adding sales tracking columns...\n";
    
    // Step 1: Add contacted column
    echo "  - Adding 'contacted' column...";
    $db->exec("ALTER TABLE sales_tracking ADD COLUMN contacted ENUM('Yes', 'No') NULL COMMENT 'Has the project been contacted?'");
    echo " ✅\n";
    
    // Step 2: Add quoted column
    echo "  - Adding 'quoted' column...";
    $db->exec("ALTER TABLE sales_tracking ADD COLUMN quoted ENUM('Yes', 'No') NULL COMMENT 'Has a quote been provided?'");
    echo " ✅\n";
    
    // Step 3: Add sales_qualified column
    echo "  - Adding 'sales_qualified' column...";
    $db->exec("ALTER TABLE sales_tracking ADD COLUMN sales_qualified ENUM('Yes', 'No') NULL COMMENT 'Is this a Sales Qualified Lead?'");
    echo " ✅\n";
    
    // Step 4: Add to_win column
    echo "  - Adding 'to_win' column...";
    $db->exec("ALTER TABLE sales_tracking ADD COLUMN to_win ENUM('Yes', 'No') NULL COMMENT 'Is this project won?'");
    echo " ✅\n";
    
    // Step 5: Add wa_amount column
    echo "  - Adding 'wa_amount' column...";
    $db->exec("ALTER TABLE sales_tracking ADD COLUMN wa_amount DECIMAL(18,2) NULL DEFAULT 0.00 COMMENT 'Win/Loss Amount'");
    echo " ✅\n";
    
    // Step 6: Add tracking_status column
    echo "  - Adding 'tracking_status' column...";
    $db->exec("ALTER TABLE sales_tracking ADD COLUMN tracking_status ENUM('Not Started', 'In Progress', 'Complete') NOT NULL DEFAULT 'Not Started' COMMENT 'Sales tracking progress status'");
    echo " ✅\n";
    
    // Step 7: Add branch column (if it doesn't exist)
    $branchCheck = $db->query("SHOW COLUMNS FROM sales_tracking LIKE 'branch'");
    if ($branchCheck->rowCount() === 0) {
        echo "  - Adding 'branch' column...";
        $db->exec("ALTER TABLE sales_tracking ADD COLUMN branch VARCHAR(100) NULL COMMENT 'Sales rep branch'");
        echo " ✅\n";
    }
    
    echo "\n📊 Adding indexes for better performance...\n";
    
    // Add indexes
    $indexes = [
        'idx_sales_tracking_contacted' => 'contacted',
        'idx_sales_tracking_quoted' => 'quoted', 
        'idx_sales_tracking_sales_qualified' => 'sales_qualified',
        'idx_sales_tracking_to_win' => 'to_win',
        'idx_sales_tracking_status' => 'tracking_status'
    ];
    
    foreach ($indexes as $indexName => $column) {
        echo "  - Creating index '$indexName'...";
        try {
            $db->exec("CREATE INDEX $indexName ON sales_tracking($column)");
            echo " ✅\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo " ⚠️ (already exists)\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n🎉 Sales tracking migration completed successfully!\n";
    echo "\nThe following columns have been added to the sales_tracking table:\n";
    echo "  - contacted (Yes/No)\n";
    echo "  - quoted (Yes/No)\n";
    echo "  - sales_qualified (Yes/No)\n";
    echo "  - to_win (Yes/No)\n";
    echo "  - wa_amount (decimal)\n";
    echo "  - tracking_status (Not Started/In Progress/Complete)\n";
    echo "  - branch (varchar)\n";
    echo "\nYou can now use the Sales Tracking Status column in the projects table!\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database connection and try again.\n";
    exit(1);
}