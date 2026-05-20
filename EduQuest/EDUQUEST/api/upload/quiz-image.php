<?php
/**
 * POST /api/upload/quiz-image.php
 * Upload an image for a quiz question or answer.
 *
 * Form fields:
 *   file - the image (multipart/form-data)
 *
 * Returns: { success, message, data: { url, filename } }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireTeacher();

$uploadDir = BASE_PATH . '/uploads/quiz-images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0750, true);
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'No file uploaded or upload error.', [], 400);
}

$file     = $_FILES['file'];
$tmpPath  = $file['tmp_name'];
$origName = basename($file['name']);
$fileSize = $file['size'];

// Max 5 MB for images
if ($fileSize > 5 * 1024 * 1024) {
    jsonResponse(false, 'Image too large. Maximum size is 5 MB.', [], 413);
}

// MIME validation
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($tmpPath);

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
if (!in_array($mime, $allowedMimes, true)) {
    jsonResponse(false, 'Invalid file type. Only JPEG, PNG, GIF, WebP images are allowed.', [], 415);
}

// Generate safe filename
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowedExts, true)) {
    $ext = 'jpg'; // fallback
}

$newFilename = bin2hex(random_bytes(16)) . '.' . $ext;
$destPath = $uploadDir . $newFilename;

if (!move_uploaded_file($tmpPath, $destPath)) {
    jsonResponse(false, 'Failed to save file.', [], 500);
}

chmod($destPath, 0640);

// Build a root-relative URL so the image resolves correctly from any page depth.
// dirname() three times: quiz-image.php → upload → api → EDUQUEST (web root of this app)
$scriptSegments = array_values(array_filter(explode('/', dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))))));
$urlBase = '/' . implode('/', array_map('rawurlencode', $scriptSegments));
$url = $urlBase . '/uploads/quiz-images/' . $newFilename;

jsonResponse(true, 'Image uploaded.', [
    'url'      => $url,
    'filename' => $newFilename,
]);
