<?php
/**
 * GET /api/upload/preview-token.php?doc_id=X
 *
 * Generates a short-lived HMAC-signed URL that can be used by external
 * services (e.g. Microsoft Office Online Viewer) to fetch the document
 * without session-based authentication.
 *
 * Requires a valid teacher session. Only documents belonging to the
 * teacher's own students are accessible.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$teacher = requireAuth();
$docId   = (int) ($_GET['doc_id'] ?? 0);

if (!$docId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'doc_id is required.']);
    exit;
}

// Verify the teacher owns this document
$pdo  = getDBConnection();
$stmt = $pdo->prepare('
    SELECT sd.id, sd.original_filename
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

// 5-minute expiry
$expires = time() + 300;
$token   = hash_hmac('sha256', "$docId:$expires", PREVIEW_SECRET);

// Build full public URL to the preview endpoint
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'];

// Derive base path from current script path
// Current: /EDUQUEST/api/upload/preview-token.php → base: /EDUQUEST
$scriptDir = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
$base      = rtrim($scriptDir, '/');

$previewUrl = "$scheme://$host$base/api/upload/public-preview.php"
            . "?doc_id=$docId&token=$token&expires=$expires";

echo json_encode([
    'success'     => true,
    'preview_url' => $previewUrl,
    'expires'     => $expires,
]);
