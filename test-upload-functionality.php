<?php
/**
 * Test Upload Functionality
 * 
 * This script tests the priority project picture upload functionality
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

// Start session for auth
session_start();

echo "<h1>🧪 Priority Project Upload Test</h1>";

// Check if user is logged in (simple bypass for testing)
if (empty($_SESSION['user'])) {
    echo "<p><strong>Note:</strong> You need to be logged in to test upload. <a href='./login'>Login first</a></p>";
}

echo "<div style='background: #f3f4f6; padding: 2rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h2>📁 Upload Test Form</h2>";
echo "<p>This form tests the priority project file upload functionality.</p>";

echo "<form action='#' method='post' enctype='multipart/form-data'>";
echo "<div style='margin-bottom: 1rem;'>";
echo "<label for='project_id'>Test Project ID:</label><br>";
echo "<input type='number' id='project_id' name='project_id' value='1' style='width: 100px; padding: 0.5rem; margin: 0.5rem 0;'>";
echo "<small style='color: #666; display: block;'>Enter an existing project ID to test upload</small>";
echo "</div>";

echo "<div style='margin-bottom: 1rem;'>";
echo "<label for='test_file'>Select Image/PDF File:</label><br>";
echo "<input type='file' id='test_file' name='test_file' accept='image/*,.pdf' style='padding: 0.5rem; margin: 0.5rem 0;'>";
echo "<small style='color: #666; display: block;'>Max 10MB, JPG/PNG/PDF only</small>";
echo "</div>";

echo "<button type='submit' name='test_upload' style='background: #10b981; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;'>Test Upload</button>";
echo "</form>";
echo "</div>";

// Process upload test
if (isset($_POST['test_upload']) && isset($_FILES['test_file'])) {
    echo "<div style='background: #dbeafe; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 1rem 0;'>";
    echo "<h3>📤 Upload Test Results</h3>";
    
    $projectId = (int) $_POST['project_id'];
    $file = $_FILES['test_file'];
    
    echo "<p><strong>Project ID:</strong> $projectId</p>";
    echo "<p><strong>File Name:</strong> " . htmlspecialchars($file['name']) . "</p>";
    echo "<p><strong>File Size:</strong> " . number_format($file['size']) . " bytes</p>";
    echo "<p><strong>File Type:</strong> " . htmlspecialchars($file['type']) . "</p>";
    
    // Simulate the API call
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color: #10b981;'><strong>✅ File upload successful!</strong></p>";
        echo "<p><strong>Temp Path:</strong> " . htmlspecialchars($file['tmp_name']) . "</p>";
        
        // Test API endpoint via JavaScript
        echo "<script>
        async function testAPIUpload() {
            const formData = new FormData();
            const fileInput = document.getElementById('test_file');
            
            if (!fileInput.files[0]) {
                alert('Please select a file first');
                return;
            }
            
            formData.append('file', fileInput.files[0]);
            formData.append('project_id', document.getElementById('project_id').value);
            formData.append('file_type', fileInput.files[0].type.startsWith('image/') ? 'image' : 'document');
            
            try {
                document.getElementById('api-test-result').innerHTML = '<p>⏳ Testing API upload...</p>';
                
                const response = await fetch('./api/v1/projects/upload', {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    document.getElementById('api-test-result').innerHTML = 
                        '<div style=\"background: #d1fae5; padding: 1rem; border-radius: 4px; border-left: 4px solid #10b981;\">' +
                        '<strong>✅ API Upload Success!</strong><br>' +
                        'File ID: ' + data.file.id + '<br>' +
                        'File Path: ' + data.file.file_path + '<br>' +
                        'Message: ' + data.message +
                        '</div>';
                } else {
                    document.getElementById('api-test-result').innerHTML = 
                        '<div style=\"background: #fef2f2; padding: 1rem; border-radius: 4px; border-left: 4px solid #ef4444;\">' +
                        '<strong>❌ API Upload Failed!</strong><br>' +
                        'Error: ' + (data.detail || data.message || 'Unknown error') +
                        '</div>';
                }
            } catch (error) {
                document.getElementById('api-test-result').innerHTML = 
                    '<div style=\"background: #fef2f2; padding: 1rem; border-radius: 4px; border-left: 4px solid #ef4444;\">' +
                    '<strong>❌ Network Error!</strong><br>' +
                    'Error: ' + error.message +
                    '</div>';
            }
        }
        </script>";
        
        echo "<button onclick='testAPIUpload()' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; margin: 0.5rem 0;'>Test API Upload</button>";
        echo "<div id='api-test-result'></div>";
        
    } else {
        echo "<p style='color: #ef4444;'><strong>❌ File upload error:</strong> " . $file['error'] . "</p>";
    }
    echo "</div>";
}

echo "<h2>🔧 System Checks</h2>";

try {
    $db = getDB();
    echo "<div style='background: #d1fae5; padding: 1rem; border-radius: 4px; border-left: 4px solid #10b981; margin: 0.5rem 0;'>";
    echo "<strong>✅ Database Connection:</strong> Working";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 4px; border-left: 4px solid #ef4444; margin: 0.5rem 0;'>";
    echo "<strong>❌ Database Connection:</strong> Failed - " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

// Check upload directory
$uploadDir = __DIR__ . '/uploads/project_photos';
if (is_dir($uploadDir) && is_writable($uploadDir)) {
    echo "<div style='background: #d1fae5; padding: 1rem; border-radius: 4px; border-left: 4px solid #10b981; margin: 0.5rem 0;'>";
    echo "<strong>✅ Upload Directory:</strong> $uploadDir (writable)";
    echo "</div>";
} else {
    echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 4px; border-left: 4px solid #ef4444; margin: 0.5rem 0;'>";
    echo "<strong>❌ Upload Directory:</strong> $uploadDir (not writable or doesn't exist)";
    echo "</div>";
}

// Check API endpoint
$routerFile = __DIR__ . '/api/router.php';
if (file_exists($routerFile)) {
    $routerContent = file_get_contents($routerFile);
    if (strpos($routerContent, "projects/upload") !== false) {
        echo "<div style='background: #d1fae5; padding: 1rem; border-radius: 4px; border-left: 4px solid #10b981; margin: 0.5rem 0;'>";
        echo "<strong>✅ API Route:</strong> /api/v1/projects/upload configured";
        echo "</div>";
    } else {
        echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 4px; border-left: 4px solid #ef4444; margin: 0.5rem 0;'>";
        echo "<strong>❌ API Route:</strong> /api/v1/projects/upload not found in router";
        echo "</div>";
    }
} else {
    echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 4px; border-left: 4px solid #ef4444; margin: 0.5rem 0;'>";
    echo "<strong>❌ Router File:</strong> api/router.php not found";
    echo "</div>";
}

// Check upload handler
$uploadFile = __DIR__ . '/api/projects/upload.php';
if (file_exists($uploadFile)) {
    echo "<div style='background: #d1fae5; padding: 1rem; border-radius: 4px; border-left: 4px solid #10b981; margin: 0.5rem 0;'>";
    echo "<strong>✅ Upload Handler:</strong> api/projects/upload.php exists";
    echo "</div>";
} else {
    echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 4px; border-left: 4px solid #ef4444; margin: 0.5rem 0;'>";
    echo "<strong>❌ Upload Handler:</strong> api/projects/upload.php not found";
    echo "</div>";
}

echo "<h2>📋 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Create a test project:</strong> <a href='./test-priority-alert.php'>Run priority alert test</a> to create a test project</li>";
echo "<li><strong>Test upload:</strong> Use the form above to test file upload with the created project ID</li>";
echo "<li><strong>Test priority form:</strong> <a href='./encode/priority'>Go to Priority Encode Form</a> and test Step 4</li>";
echo "<li><strong>Check dashboard:</strong> <a href='./reports'>View Dashboard</a> for priority alerts</li>";
echo "</ol>";

echo "<div style='background: #fef3c7; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 1rem 0;'>";
echo "<strong>⚠️ Important:</strong> Make sure you're logged in before testing the API upload functionality.";
echo "</div>";
?>