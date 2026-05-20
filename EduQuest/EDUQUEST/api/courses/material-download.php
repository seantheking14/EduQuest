<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$teacher    = requireAuth();
$materialId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$materialId) {
    http_response_code(400);
    exit('Material ID required.');
}

$pdo  = getDBConnection();
$stmt = $pdo->prepare('
    SELECT mat.*
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
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found on disk.');
}

$mimeType = $mat['mime_type'] ?: 'application/octet-stream';
$origName = $mat['original_filename'] ?: $mat['stored_filename'];

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . addslashes($origName) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private');
readfile($filePath);
exit;
