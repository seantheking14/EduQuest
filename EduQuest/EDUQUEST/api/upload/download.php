<?php
/**
 * GET /api/upload/download.php?doc_id=123
 * Securely stream a stored document to the authenticated teacher.
 * Only documents belonging to the teacher's own students are accessible.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher = requireAuth();
$docId   = (int)($_GET['doc_id'] ?? 0);
if (!$docId) jsonResponse(false, 'doc_id is required.', [], 400);

$pdo  = getDBConnection();
$stmt = $pdo->prepare('
    SELECT sd.stored_filename, sd.original_filename, sd.mime_type, sd.file_size,
           s.teacher_id
    FROM student_documents sd
    JOIN students s ON s.id = sd.student_id
    WHERE sd.id = ? AND s.teacher_id = ? AND s.is_active = 1
');
$stmt->execute([$docId, $teacher['id']]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Document not found or access denied.']);
    exit;
}

$filePath = UPLOAD_DIR . $doc['stored_filename'];
if (!is_file($filePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File missing from server.']);
    exit;
}

// Stream the file
$safeDisplayName = rawurlencode($doc['original_filename']);
header('Content-Type: ' . $doc['mime_type']);
header('Content-Length: ' . $doc['file_size']);
header('Content-Disposition: inline; filename="' . $safeDisplayName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($filePath);
exit;
