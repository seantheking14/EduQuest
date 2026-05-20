<?php
/**
 * GET /api/learning/student-material-download.php?id=X
 * Download a course material file for enrolled students.
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
    SELECT mat.*
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
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found on disk.');
}

$mimeType = $mat['mime_type'] ?: 'application/octet-stream';
$origName = $mat['original_filename'] ?: $mat['stored_filename'];

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . rawurlencode($origName) . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private');
readfile($filePath);
exit;
