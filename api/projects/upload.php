<?php
/* ============================================================
   POST /api/v1/projects/upload
   ============================================================
   Handles file uploads for projects (images and documents)
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../activity-logger.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Require authentication
requireAuth();

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../../uploads/project_photos';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    jsonError('Unable to create upload directory', 500);
}

// Validate required parameters
if (empty($_POST['project_id'])) {
    jsonError('Project ID is required', 400);
}

$projectId = (int) $_POST['project_id'];
$fileType = $_POST['file_type'] ?? 'image'; // 'image' or 'document'

// Check if file was uploaded
if (empty($_FILES['file'])) {
    jsonError('No file uploaded', 400);
}

$file = $_FILES['file'];

// Validate file upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds the maximum allowed size',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds the form maximum size',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    
    $message = $errorMessages[$file['error']] ?? 'Unknown upload error';
    jsonError($message, 400);
}

// Validate file size (10MB max)
$maxSize = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxSize) {
    jsonError('File size exceeds 10MB limit', 400);
}

// Validate file type
$allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$allowedDocumentTypes = ['application/pdf'];
$allowedTypes = array_merge($allowedImageTypes, $allowedDocumentTypes);

if (!in_array($file['type'], $allowedTypes)) {
    jsonError('File type not supported. Only JPG, PNG, WEBP, and PDF files are allowed', 400);
}

// Validate file extension
$pathInfo = pathinfo($file['name']);
$extension = strtolower($pathInfo['extension'] ?? '');
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

if (!in_array($extension, $allowedExtensions)) {
    jsonError('Invalid file extension', 400);
}

// Generate unique filename
$timestamp = time();
$randomBytes = bin2hex(random_bytes(8));
$filename = sprintf('%s_%s_%s.%s', $projectId, $timestamp, $randomBytes, $extension);
$destination = $uploadDir . '/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonError('Failed to save uploaded file', 500);
}

// Store file info in database
try {
    $db = getDB();
    
    // Verify project exists
    $stmt = $db->prepare("SELECT id FROM projects WHERE id = :project_id");
    $stmt->execute([':project_id' => $projectId]);
    
    if (!$stmt->fetch()) {
        // Clean up uploaded file
        unlink($destination);
        jsonError('Project not found', 404);
    }
    
    // Insert file record
    $stmt = $db->prepare("
        INSERT INTO project_images (project_id, file_path, created_at)
        VALUES (:project_id, :file_path, NOW())
    ");
    
    $relativePath = 'uploads/project_photos/' . $filename;
    
    $stmt->execute([
        ':project_id' => $projectId,
        ':file_path' => $relativePath
    ]);
    
    $imageId = $db->lastInsertId();
    
    logActivity($db, $_SESSION['user']['id'], ActivityType::PROJECT_UPDATE, EntityType::PROJECT, $projectId, "File uploaded for project #{$projectId}: {$file['name']}");

    // Return success response
    jsonResponse([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file' => [
            'id' => (int) $imageId,
            'file_path' => $relativePath,
            'original_name' => $file['name'],
            'file_size' => $file['size'],
            'project_id' => $projectId
        ]
    ]);
    
} catch (PDOException $e) {
    // Clean up uploaded file on database error
    if (file_exists($destination)) {
        unlink($destination);
    }
    
    error_log('Project upload DB error: ' . $e->getMessage());
    jsonError('Database error occurred', 500);
} catch (Exception $e) {
    // Clean up uploaded file on any error
    if (file_exists($destination)) {
        unlink($destination);
    }
    
    error_log('Project upload error: ' . $e->getMessage());
    jsonError('Upload failed', 500);
}