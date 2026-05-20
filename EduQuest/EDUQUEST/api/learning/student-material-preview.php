<?php
/**
 * GET /api/learning/student-material-preview.php?id=X
 * Stream a course material file inline for enrolled students.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user       = requireAuth();
$materialId = (int) ($_GET['id'] ?? 0);

if (!$materialId) {
    http_response_code(400);
    exit('Material ID required.');
}

$pdo = getDBConnection();

// Resolve student id
$stmt = $pdo->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['role'] === 'student' ? $user['id'] : 0]);
$studentRow = $stmt->fetch();

if (!$studentRow) {
    http_response_code(403);
    exit('Access denied.');
}

$studentId = (int) $studentRow['id'];

// Get material only if student is enrolled in the course and material is visible
$stmt = $pdo->prepare('
    SELECT mat.stored_filename, mat.original_filename, mat.mime_type, mat.file_size
    FROM course_materials mat
    JOIN courses c ON c.id = mat.course_id
    JOIN course_enrollments ce ON ce.course_id = c.id AND ce.student_id = ?
    WHERE mat.id = ? AND mat.material_type = \'file\' AND mat.is_visible = 1
');
$stmt->execute([$studentId, $materialId]);
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
