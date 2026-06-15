<?php
/* ============================================================
   GET /api/v1/projects/{id}/images
   Returns all images for a given project.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$projectId = (int)($_GET['id'] ?? 0);
if (!$projectId) {
    jsonError('Project ID required', 400);
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT id, file_path, created_at
        FROM project_images
        WHERE project_id = :project_id
        ORDER BY created_at ASC
    ");
    $stmt->execute([':project_id' => $projectId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        'images' => array_map(fn($img) => [
            'id'         => (int) $img['id'],
            'file_path'  => $img['file_path'],
            'created_at' => $img['created_at'],
        ], $images)
    ]);

} catch (Exception $e) {
    error_log('Project images API error: ' . $e->getMessage());
    jsonResponse(['images' => []]);
}
