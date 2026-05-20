<?php
/**
 * GET /api/upload/public-preview.php?doc_id=X&token=...&expires=...
 *
 * Serves a stored document using a short-lived HMAC token instead of
 * session authentication. This allows external services like Microsoft
 * Office Online Viewer to fetch the file directly.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

$docId   = (int) ($_GET['doc_id'] ?? 0);
$token   = $_GET['token'] ?? '';
$expires = (int) ($_GET['expires'] ?? 0);

if (!$docId || !$token || !$expires) {
    http_response_code(400);
    exit('Missing parameters.');
}

// Check expiry
if (time() > $expires) {
    http_response_code(403);
    exit('Token expired.');
}

// Validate HMAC
$expected = hash_hmac('sha256', "$docId:$expires", PREVIEW_SECRET);
if (!hash_equals($expected, $token)) {
    http_response_code(403);
    exit('Invalid token.');
}

$pdo  = getDBConnection();
$stmt = $pdo->prepare('
    SELECT stored_filename, original_filename, mime_type, file_size
    FROM student_documents
    WHERE id = ?
');
$stmt->execute([$docId]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    exit('Document not found.');
}

$filePath = UPLOAD_DIR . $doc['stored_filename'];
if (!is_file($filePath)) {
    http_response_code(404);
    exit('File missing from server.');
}

$mime = normalizeMime($doc['mime_type'], $doc['original_filename']);

header('Content-Type: ' . $mime);
header('Content-Length: ' . $doc['file_size']);
header('Content-Disposition: inline; filename="' . rawurlencode($doc['original_filename']) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
// Office Online Viewer requires CORS
header('Access-Control-Allow-Origin: *');
readfile($filePath);
exit;
