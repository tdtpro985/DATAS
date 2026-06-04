<?php
/**
 * Test Comprehensive Priority Alert Data
 * 
 * This script creates a priority project with complete data to test the enhanced modal display
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

try {
    $db = getDB();
    
    echo "<h1>🧪 Comprehensive Priority Data Test</h1>";
    
    // Create a priority project with ALL fields populated
    $stmt = $db->prepare("
        INSERT INTO projects (
            contractor_id,
            project_id,
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
            contract_country,
            contract_region,
            contract_province,
            contract_city,
            contract_barangay,
            contract_street,
            contract_blk_lot,
            contract_coordinates,
            project_country,
            project_region,
            project_province,
            project_city,
            project_barangay,
            project_street,
            project_blk_lot,
            project_coordinates,
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
            :contractor_id,
            :project_id,
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
            :contract_country,
            :contract_region,
            :contract_province,
            :contract_city,
            :contract_barangay,
            :contract_street,
            :contract_blk_lot,
            :contract_coordinates,
            :project_country,
            :project_region,
            :project_province,
            :project_city,
            :project_barangay,
            :project_street,
            :project_blk_lot,
            :project_coordinates,
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
        ':contractor_id' => 'CONTR-2024-001',
        ':project_id' => 'PROJ-PRI-2024-001',
        ':contractor_name' => 'PREMIUM STEEL CONSTRUCTION CORPORATION',
        ':contact_person' => 'Engr. Maria Elena Santos-Cruz',
        ':contact_number' => '+63 917 555 0123 / +63 2 8555 1234',
        ':project_name' => '🚨 PRIORITY: Metro Manila Bridge Reinforcement & Seismic Retrofitting Project - Phase 1 (Critical Infrastructure)',
        ':project_value' => 125000000.00,
        ':source' => 'DPWH',
        ':publication_date' => date('Y-m-d'),
        ':address' => 'Comprehensive Project Address Here',
        ':region' => 'NCR',
        ':city_province' => 'Manila City',
        
        // Contract Location
        ':contract_country' => 'PH',
        ':contract_region' => 'NCR',
        ':contract_province' => 'Metro Manila',
        ':contract_city' => 'Manila',
        ':contract_barangay' => 'Ermita',
        ':contract_street' => 'Roxas Boulevard corner Kalaw Street',
        ':contract_blk_lot' => 'Block 15, Lot 8-12',
        ':contract_coordinates' => '14.5794, 120.9767',
        
        // Project Location  
        ':project_country' => 'PH',
        ':project_region' => 'NCR',
        ':project_province' => 'Metro Manila',
        ':project_city' => 'Quezon City',
        ':project_barangay' => 'Bagumbayan',
        ':project_street' => 'EDSA Extension Bridge Area',
        ':project_blk_lot' => 'Bridge Section B1-B5',
        ':project_coordinates' => '14.6760, 121.0437',
        
        // Materials
        ':sheet_pile_type' => 'PSM 280 Cold Formed Steel',
        ':sheet_pile_amount' => 35000000.00,
        ':drbs' => 'Deformed Reinforcing Bars - Grade 40, Various Sizes (10mm-25mm)',
        ':drbs_value' => 28000000.00,
        ':accomplishment_rate' => 25.50,
        ':ms_plate' => 15000000.00,
        ':angle_bars' => 8500000.00,
        ':channel_bars' => 12000000.00,
        ':wide_flange' => 18000000.00,
        ':gi_bi' => 7500000.00
    ];
    
    $stmt->execute($testData);
    $projectId = $db->lastInsertId();
    
    echo "<div style='background: #d1fae5; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #10b981; margin: 1rem 0;'>";
    echo "<strong>✅ Comprehensive Priority Project Created!</strong><br>";
    echo "<strong>Project ID:</strong> {$projectId}<br>";
    echo "<strong>Project Name:</strong> " . substr($testData[':project_name'], 0, 80) . "...<br>";
    echo "<strong>Status:</strong> PRIORITY<br>";
    echo "<strong>Value:</strong> ₱" . number_format($testData[':project_value'], 2) . "<br>";
    echo "<strong>Accomplishment Rate:</strong> {$testData[':accomplishment_rate']}%";
    echo "</div>";
    
    echo "<h2>📊 Complete Project Data Overview</h2>";
    
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0;'>";
    
    // Project Information Column
    echo "<div style='background: #f3f4f6; padding: 1rem; border-radius: 8px;'>";
    echo "<h3 style='color: #374151; margin-bottom: 0.5rem;'>📋 Project Information</h3>";
    echo "<p><strong>Contractor ID:</strong> {$testData[':contractor_id']}</p>";
    echo "<p><strong>Project ID:</strong> {$testData[':project_id']}</p>";
    echo "<p><strong>Source:</strong> {$testData[':source']}</p>";
    echo "<p><strong>Publication Date:</strong> {$testData[':publication_date']}</p>";
    echo "<p><strong>Contact Person:</strong> {$testData[':contact_person']}</p>";
    echo "<p><strong>Contact Number:</strong> {$testData[':contact_number']}</p>";
    echo "</div>";
    
    // Materials Information Column
    echo "<div style='background: #f3f4f6; padding: 1rem; border-radius: 8px;'>";
    echo "<h3 style='color: #374151; margin-bottom: 0.5rem;'>🔧 Materials Information</h3>";
    echo "<p><strong>Sheet Pile Type:</strong> {$testData[':sheet_pile_type']}</p>";
    echo "<p><strong>Sheet Pile Value:</strong> ₱" . number_format($testData[':sheet_pile_amount'], 2) . "</p>";
    echo "<p><strong>DRBs Value:</strong> ₱" . number_format($testData[':drbs_value'], 2) . "</p>";
    echo "<p><strong>MS Plate:</strong> ₱" . number_format($testData[':ms_plate'], 2) . "</p>";
    echo "<p><strong>Angle Bars:</strong> ₱" . number_format($testData[':angle_bars'], 2) . "</p>";
    echo "<p><strong>Wide Flange:</strong> ₱" . number_format($testData[':wide_flange'], 2) . "</p>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<h2>📍 Location Details</h2>";
    echo "<div style='background: #eff6ff; padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 1rem 0;'>";
    echo "<p><strong>Contract Location:</strong> {$testData[':contract_region']}, {$testData[':contract_province']}, {$testData[':contract_city']}, {$testData[':contract_barangay']}</p>";
    echo "<p><strong>Contract Address:</strong> {$testData[':contract_street']}, {$testData[':contract_blk_lot']}</p>";
    echo "<p><strong>Project Location:</strong> {$testData[':project_region']}, {$testData[':project_province']}, {$testData[':project_city']}, {$testData[':project_barangay']}</p>";
    echo "<p><strong>Project Address:</strong> {$testData[':project_street']}, {$testData[':project_blk_lot']}</p>";
    echo "<p><strong>Coordinates:</strong> {$testData[':project_coordinates']}</p>";
    echo "</div>";
    
    echo "<h2>🎯 Test the Enhanced Priority Alert</h2>";
    echo "<ol>";
    echo "<li><strong>Go to Dashboard:</strong> <a href='./reports' target='_blank' style='background: #ff8000; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Open Dashboard</a></li>";
    echo "<li><strong>Wait for Alert:</strong> The priority alert should appear within 10 seconds</li>";
    echo "<li><strong>Check Pictures Modal:</strong> First modal shows images (if any uploaded)</li>";
    echo "<li><strong>Check Data Modal:</strong> Second modal shows ALL project details in organized sections</li>";
    echo "<li><strong>Verify Data:</strong> All fields should be populated with comprehensive information</li>";
    echo "</ol>";
    
    echo "<h2>🧹 Cleanup After Testing</h2>";
    echo "<div style='background: #fef3c7; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 1rem 0;'>";
    echo "<p><strong>To remove this test project after testing:</strong></p>";
    echo "<code style='background: #f3f4f6; padding: 0.5rem; border-radius: 4px; display: block; margin: 0.5rem 0;'>";
    echo "DELETE FROM priority_alerts WHERE project_id = {$projectId};<br>";
    echo "DELETE FROM projects WHERE id = {$projectId};";
    echo "</code>";
    echo "</div>";
    
    echo "<h2>🔍 What You Should See</h2>";
    echo "<div style='background: #dbeafe; padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 1rem 0;'>";
    echo "<h3>Enhanced Data Modal Features:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Project Section:</strong> Name, ID, Value, Status, Source, Publication Date</li>";
    echo "<li>✅ <strong>Contractor Section:</strong> Company, ID, Contact Person, Phone Number</li>";
    echo "<li>✅ <strong>Location Section:</strong> Project/Contract locations, Address, Coordinates</li>";
    echo "<li>✅ <strong>Materials Section:</strong> All steel materials with values, Accomplishment rate</li>";
    echo "<li>✅ <strong>Visual Design:</strong> Organized sections, color coding, proper spacing</li>";
    echo "<li>✅ <strong>Mobile Responsive:</strong> Adapts to different screen sizes</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 8px; border-left: 4px solid #ef4444; margin: 1rem 0;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>