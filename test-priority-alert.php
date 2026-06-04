<?php
/**
 * Test Priority Alert System
 * 
 * This script creates a test priority project to demonstrate the alert system.
 * Run this script to trigger a priority alert on the dashboard.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

try {
    $db = getDB();
    
    echo "<h1>🧪 Priority Alert System Test</h1>";
    
    // Create a test priority project
    $stmt = $db->prepare("
        INSERT INTO projects (
            contractor_name,
            contact_person,
            contact_number,
            project_name,
            project_value,
            status,
            region,
            city_province,
            address,
            publication_date,
            sheet_pile_amount,
            drbs_value,
            source,
            contract_country,
            project_country,
            created_at,
            updated_at
        ) VALUES (
            :contractor_name,
            :contact_person,
            :contact_number,
            :project_name,
            :project_value,
            'PRIORITY',
            :region,
            :city_province,
            :address,
            :publication_date,
            :sheet_pile_amount,
            :drbs_value,
            'DPWH',
            'Philippines',
            'Philippines',
            NOW(),
            NOW()
        )
    ");
    
    $testData = [
        ':contractor_name' => 'URGENT CONSTRUCTION CORPORATION',
        ':contact_person' => 'Emergency Project Manager',
        ':contact_number' => '+63 917 URGENT1',
        ':project_name' => '🚨 PRIORITY: Emergency Bridge Repair - Immediate Action Required',
        ':project_value' => 50000000.00,
        ':region' => 'NCR',
        ':city_province' => 'Manila City',
        ':address' => 'Rizal Bridge, Sta. Cruz, Manila',
        ':publication_date' => date('Y-m-d'),
        ':sheet_pile_amount' => 15500000.00,
        ':drbs_value' => 8200000.00
    ];
    
    $stmt->execute($testData);
    $projectId = $db->lastInsertId();
    
    echo "<div style='background: #d1fae5; padding: 1rem; border-radius: 8px; border-left: 4px solid #10b981; margin: 1rem 0;'>";
    echo "<strong>✅ Test Priority Project Created!</strong><br>";
    echo "Project ID: {$projectId}<br>";
    echo "Project Name: {$testData[':project_name']}<br>";
    echo "Status: PRIORITY<br>";
    echo "Value: ₱" . number_format($testData[':project_value'], 2);
    echo "</div>";
    
    // Optional: Add some test images (commented out - you need actual image files)
    /*
    $imageData = [
        'uploads/test/emergency-bridge-1.jpg',
        'uploads/test/emergency-bridge-2.jpg',
        'uploads/test/emergency-bridge-3.jpg'
    ];
    
    foreach ($imageData as $imagePath) {
        $stmt = $db->prepare("
            INSERT INTO project_images (project_id, file_path, created_at) 
            VALUES (:project_id, :file_path, NOW())
        ");
        $stmt->execute([
            ':project_id' => $projectId,
            ':file_path' => $imagePath
        ]);
    }
    
    echo "<div style='background: #dbeafe; padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 1rem 0;'>";
    echo "<strong>📸 Test Images Added!</strong><br>";
    echo "Added " . count($imageData) . " test images to the project.";
    echo "</div>";
    */
    
    echo "<h2>📋 How the Two-Modal System Works:</h2>";
    echo "<ol>";
    echo "<li><strong>First Modal - Pictures:</strong> Shows project images with slideshow (5 seconds each)</li>";
    echo "<li><strong>Sound Alert:</strong> Plays priority-alert.mp3 continuously until clicked</li>";
    echo "<li><strong>Click Pictures Modal:</strong> Stops sound and shows second modal</li>";
    echo "<li><strong>Second Modal - Data:</strong> Shows complete project details (no scroll, fits screen)</li>";
    echo "<li><strong>Click Data Modal:</strong> Closes and returns to dashboard</li>";
    echo "</ol>";
    
    echo "<h2>🎯 Test Steps:</h2>";
    echo "<ol>";
    echo "<li><strong>Go to the dashboard:</strong> <a href='./reports' target='_blank'>Open Dashboard</a></li>";
    echo "<li><strong>Wait up to 10 seconds</strong> for the priority alert to appear</li>";
    echo "<li><strong>First Modal:</strong> Pictures slideshow with looping sound</li>";
    echo "<li><strong>Click anywhere on pictures:</strong> Sound stops, data modal appears</li>";
    echo "<li><strong>Click anywhere on data:</strong> Modal closes, return to dashboard</li>";
    echo "</ol>";
    
    echo "<h2>🔧 Test API Endpoint:</h2>";
    echo "<p>You can also test the API directly:</p>";
    echo "<a href='./api/v1/priority-alerts' target='_blank' style='background: #ff8000; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>";
    echo "Test API: /api/v1/priority-alerts";
    echo "</a>";
    
    echo "<h2>🧹 Cleanup:</h2>";
    echo "<p>To remove this test project:</p>";
    echo "<code style='background: #f3f4f6; padding: 0.5rem; border-radius: 4px; display: block; margin: 0.5rem 0;'>";
    echo "DELETE FROM priority_alerts WHERE project_id = {$projectId};<br>";
    echo "DELETE FROM projects WHERE id = {$projectId};";
    echo "</code>";
    
    echo "<div style='background: #fef3c7; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 1rem 0;'>";
    echo "<strong>⚠️ Note:</strong> This test project will trigger an alert on the dashboard. ";
    echo "If multiple users are viewing the dashboard, they will all see this alert. ";
    echo "Delete the project after testing.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 8px; border-left: 4px solid #ef4444; margin: 1rem 0;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<h3>Possible Issues:</h3>";
    echo "<ul>";
    echo "<li>Database connection failed - check config.php</li>";
    echo "<li>Projects table doesn't exist - run database migration</li>";
    echo "<li>Missing required fields - check database schema</li>";
    echo "</ul>";
}
?>