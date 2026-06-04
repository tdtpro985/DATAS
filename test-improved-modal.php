<?php
/**
 * Test Improved Modal Design - Enhanced Version
 * 
 * Tests the improved priority alert modal with better design,
 * enhanced database handling, and user-requested fixes
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

try {
    $db = getDB();
    
    echo "<h1>🎨 Testing Enhanced Priority Alert System</h1>";
    
    // Clean up any existing test projects first
    $cleanupStmt = $db->prepare("DELETE FROM projects WHERE contractor_name = 'TEST CONTRACTOR ENHANCED'");
    $cleanupStmt->execute();
    
    $cleanupAlertsStmt = $db->prepare("DELETE FROM priority_alerts WHERE project_id IN (SELECT id FROM projects WHERE contractor_name = 'TEST CONTRACTOR ENHANCED')");
    $cleanupAlertsStmt->execute();
    
    // Create a comprehensive test priority project with all fields
    $stmt = $db->prepare("
        INSERT INTO projects (
            contractor_name,
            contact_person,
            contact_number,
            project_name,
            project_value,
            status,
            source,
            publication_date,
            address,
            region,
            city_province,
            project_city,
            project_street,
            project_barangay,
            contract_city,
            contract_street,
            contract_barangay,
            project_blk_lot,
            contract_blk_lot,
            sheet_pile_type,
            sheet_pile_amount,
            drbs,
            drbs_value,
            accomplishment_rate,
            ms_plate,
            angle_bars,
            channel_bars,
            wide_flange,
            gi_bi,
            created_at,
            updated_at
        ) VALUES (
            :contractor_name,
            :contact_person,
            :contact_number,
            :project_name,
            :project_value,
            'PRIORITY',
            :source,
            :publication_date,
            :address,
            :region,
            :city_province,
            :project_city,
            :project_street,
            :project_barangay,
            :contract_city,
            :contract_street,
            :contract_barangay,
            :project_blk_lot,
            :contract_blk_lot,
            :sheet_pile_type,
            :sheet_pile_amount,
            :drbs,
            :drbs_value,
            :accomplishment_rate,
            :ms_plate,
            :angle_bars,
            :channel_bars,
            :wide_flange,
            :gi_bi,
            NOW(),
            NOW()
        )
    ");
    
    $testData = [
        ':contractor_name' => 'TEST CONTRACTOR ENHANCED',
        ':contact_person' => 'Engr. Maria Santos',
        ':contact_number' => '+63-917-123-4567',
        ':project_name' => 'ENHANCED PRIORITY TESTING PROJECT - MODAL DESIGN VERIFICATION',
        ':project_value' => 15750000.00,
        ':source' => 'DPWH',
        ':publication_date' => date('Y-m-d'),
        ':address' => 'Legacy Address Field',
        ':region' => 'NCR',
        ':city_province' => 'MET',
        ':project_city' => 'Manila',
        ':project_street' => '123 Rizal Avenue',
        ':project_barangay' => 'Barangay San Miguel',
        ':contract_city' => 'Quezon City',
        ':contract_street' => '456 EDSA Corner Shaw Boulevard',
        ':contract_barangay' => 'Barangay Kapitolyo',
        ':project_blk_lot' => 'Block 5 Lot 10',
        ':contract_blk_lot' => 'Unit 2B',
        ':sheet_pile_type' => 'Steel Grade A992',
        ':sheet_pile_amount' => 2500000.00,
        ':drbs' => 'Category III Bridge Construction',
        ':drbs_value' => 1200000.00,
        ':accomplishment_rate' => 67.50,
        ':ms_plate' => 850000.00,
        ':angle_bars' => 320000.00,
        ':channel_bars' => 180000.00,
        ':wide_flange' => 450000.00,
        ':gi_bi' => 95000.00
    ];
    
    $stmt->execute($testData);
    $projectId = $db->lastInsertId();
    
    echo "<div style='background: #d1fae5; padding: 2rem; border-radius: 12px; border-left: 6px solid #10b981; margin: 1.5rem 0; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);'>";
    echo "<strong style='color: #065f46; font-size: 1.2rem;'>✅ Enhanced Priority Test Project Created!</strong><br><br>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;'>";
    echo "<div><strong style='color: #047857;'>Project ID:</strong> {$projectId}</div>";
    echo "<div><strong style='color: #047857;'>Source:</strong> {$testData[':source']}</div>";
    echo "<div style='grid-column: 1 / -1;'><strong style='color: #047857;'>Project Name:</strong> {$testData[':project_name']}</div>";
    echo "<div><strong style='color: #047857;'>Contractor:</strong> {$testData[':contractor_name']}</div>";
    echo "<div><strong style='color: #047857;'>Contact Person:</strong> {$testData[':contact_person']}</div>";
    echo "<div><strong style='color: #047857;'>Contact Number:</strong> {$testData[':contact_number']}</div>";
    echo "<div><strong style='color: #047857;'>Project Value:</strong> ₱" . number_format($testData[':project_value'], 2) . "</div>";
    echo "<div><strong style='color: #047857;'>City (Location):</strong> {$testData[':project_city']}</div>";
    echo "<div><strong style='color: #047857;'>Accomplishment:</strong> {$testData[':accomplishment_rate']}%</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<h2 style='color: #ff8000; border-bottom: 2px solid #ff8000; padding-bottom: 0.5rem;'>🎯 Test the Enhanced Modal System</h2>";
    echo "<div style='background: #fff7ed; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ff8000; margin: 1rem 0;'>";
    echo "<ol style='color: #9a3412; line-height: 1.8;'>";
    echo "<li><strong>Go to Dashboard:</strong> <a href='./reports' target='_blank' style='background: #ff8000; color: white; padding: 0.7rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 600; margin-left: 0.5rem;'>🚀 Open Dashboard</a></li>";
    echo "<li><strong>Wait for Alert:</strong> Should appear within 10 seconds automatically</li>";
    echo "<li><strong>Pictures Modal:</strong> Click anywhere to proceed (sound should stop immediately)</li>";
    echo "<li><strong>Data Modal:</strong> Verify the improved layout and data display</li>";
    echo "<li><strong>Check Fields:</strong> Location shows city only, Address combines components</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2 style='color: #3b82f6; border-bottom: 2px solid #3b82f6; padding-bottom: 0.5rem;'>✨ Enhanced Features</h2>";
    echo "<div style='background: #eff6ff; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 1rem 0;'>";
    
    echo "<h3 style='color: #1e40af; margin-bottom: 1rem;'>🎨 Design Improvements:</h3>";
    echo "<ul style='color: #1e3a8a; line-height: 1.6;'>";
    echo "<li>✅ <strong>Better Modal Size:</strong> 90vw x 85vh with max-width 1200px</li>";
    echo "<li>✅ <strong>Improved Styling:</strong> Enhanced gradients, shadows, and spacing</li>";
    echo "<li>✅ <strong>Better Typography:</strong> Inter font family, improved readability</li>";
    echo "<li>✅ <strong>Enhanced Fields:</strong> Better padding, hover effects, visual hierarchy</li>";
    echo "<li>✅ <strong>Hidden Click Indicator:</strong> Removed as requested</li>";
    echo "<li>✅ <strong>Responsive Design:</strong> Mobile-optimized layout</li>";
    echo "</ul>";
    
    echo "<h3 style='color: #1e40af; margin: 1.5rem 0 1rem 0;'>🔧 Data Logic Improvements:</h3>";
    echo "<ul style='color: #1e3a8a; line-height: 1.6;'>";
    echo "<li>✅ <strong>Location Field:</strong> Shows only city name ('{$testData[':project_city']}')</li>";
    echo "<li>✅ <strong>Address Field:</strong> Combines street + barangay + blk/lot components</li>";
    echo "<li>✅ <strong>Better Field Mapping:</strong> Improved data extraction and display</li>";
    echo "<li>✅ <strong>Null Handling:</strong> Better fallbacks for missing data</li>";
    echo "</ul>";
    
    echo "<h3 style='color: #1e40af; margin: 1.5rem 0 1rem 0;'>🗄️ Database Enhancements:</h3>";
    echo "<ul style='color: #1e3a8a; line-height: 1.6;'>";
    echo "<li>✅ <strong>Connection Testing:</strong> Proper timeout and error handling</li>";
    echo "<li>✅ <strong>Table Existence Checks:</strong> Verifies tables before queries</li>";
    echo "<li>✅ <strong>Auto-Table Creation:</strong> Creates priority_alerts if missing</li>";
    echo "<li>✅ <strong>Enhanced Error Logging:</strong> Detailed error messages for debugging</li>";
    echo "<li>✅ <strong>Graceful Degradation:</strong> Continues even if images table is missing</li>";
    echo "<li>✅ <strong>Duplicate Prevention:</strong> ON DUPLICATE KEY UPDATE for alerts</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2 style='color: #059669; border-bottom: 2px solid #059669; padding-bottom: 0.5rem;'>📊 Required Fields Verification</h2>";
    echo "<div style='background: #f0fdfa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #059669; margin: 1rem 0;'>";
    echo "<p style='color: #065f46; font-weight: 600; margin-bottom: 1rem;'>The following fields will be displayed in the priority alert modal:</p>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; color: #047857; font-size: 0.95rem;'>";
    echo "<div>✅ <strong>Contractor:</strong> {$testData[':contractor_name']}</div>";
    echo "<div>✅ <strong>Contact Person:</strong> {$testData[':contact_person']}</div>";
    echo "<div>✅ <strong>Contact Number:</strong> {$testData[':contact_number']}</div>";
    echo "<div>✅ <strong>Address:</strong> Combined street + barangay + blk/lot</div>";
    echo "<div style='grid-column: 1 / -1;'>✅ <strong>Project Name:</strong> {$testData[':project_name']}</div>";
    echo "<div>✅ <strong>Location:</strong> {$testData[':project_city']} (city only)</div>";
    echo "<div>✅ <strong>Sheet Pile Type:</strong> {$testData[':sheet_pile_type']}</div>";
    echo "<div>✅ <strong>Sheet Pile Amount:</strong> ₱" . number_format($testData[':sheet_pile_amount'], 2) . "</div>";
    echo "<div>✅ <strong>Project Value:</strong> ₱" . number_format($testData[':project_value'], 2) . "</div>";
    echo "<div>✅ <strong>Accomplishment Rate:</strong> {$testData[':accomplishment_rate']}%</div>";
    echo "</div>";
    echo "<div style='margin-top: 1rem; padding: 1rem; background: rgba(6, 95, 70, 0.1); border-radius: 6px;'>";
    echo "<strong style='color: #065f46;'>Expected Combined Address:</strong><br>";
    echo "<span style='color: #047857; font-family: monospace;'>'123 Rizal Avenue, Barangay San Miguel, Block 5 Lot 10, 456 EDSA Corner Shaw Boulevard, Barangay Kapitolyo, Unit 2B, Legacy Address Field'</span>";
    echo "</div>";
    echo "</div>";
    
    echo "<h2 style='color: #dc2626; border-bottom: 2px solid #dc2626; padding-bottom: 0.5rem;'>🧹 Cleanup</h2>";
    echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 8px; border-left: 4px solid #dc2626; margin: 1rem 0;'>";
    echo "<p style='color: #991b1b; margin-bottom: 1rem;'>To remove this test project and cleanup:</p>";
    echo "<code style='background: #f3f4f6; padding: 1rem; border-radius: 6px; display: block; margin: 0.5rem 0; color: #374151; font-family: monospace; line-height: 1.5;'>";
    echo "DELETE FROM priority_alerts WHERE project_id = {$projectId};<br>";
    echo "DELETE FROM projects WHERE id = {$projectId};<br>";
    echo "-- Or simply run this script again (it auto-cleans)";
    echo "</code>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ef4444; margin: 1rem 0;'>";
    echo "<strong style='color: #dc2626; font-size: 1.1rem;'>❌ Error:</strong><br>";
    echo "<p style='color: #991b1b; margin-top: 0.5rem;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: #7f1d1d; font-size: 0.9rem; margin-top: 1rem;'><strong>File:</strong> " . $e->getFile() . " <strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>