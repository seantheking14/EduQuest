<?php
/**
 * GET /api/courses/material-preview.php?id=X
 * Stream a course material file inline (for in-browser viewing).
 * Only materials belonging to the teacher's own courses are accessible.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher    = requireAuth();
$materialId = (int) ($_GET['id'] ?? 0);

if (!$materialId) {
    http_response_code(400);
    exit('Material ID required.');
}

$pdo  = getDBConnection();
$stmt = $pdo->prepare('
    SELECT mat.stored_filename, mat.original_filename, mat.mime_type, mat.file_size
    FROM course_materials mat
    JOIN courses c ON c.id = mat.course_id
    WHERE mat.id = ? AND c.teacher_id = ? AND mat.material_type = \'file\'
');
$stmt->execute([$materialId, $teacher['id']]);
$mat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mat || !$mat['stored_filename']) {
    http_response_code(404);
    exit('File not found.');
}

$filePath = COURSE_MATERIAL_DIR . $mat['stored_filename'];
if (!is_file($filePath)) {
    http_response_code(404);
    exit('File missing from server.');
}

$mime = $mat['mime_type'] ?: 'application/octet-stream';
$ext  = pathinfo($mat['original_filename'], PATHINFO_EXTENSION);
$mime = normalizeMime($mime, $ext);

$safeName = rawurlencode($mat['original_filename']);
header('Content-Type: ' . $mime);
header('Content-Length: ' . ($mat['file_size'] ?: filesize($filePath)));
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($filePath);
exit;
