<?php
/**
 * POST /api/upload/photo.php?student_id=123
 * Upload a profile photo for a student.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher   = requireAuth();
$studentId = (int)($_GET['student_id'] ?? 0);
if (!$studentId) jsonResponse(false, 'student_id is required.', [], 400);

$pdo = getDBConnection();
requireStudentAccess($studentId, $teacher);
ensureUploadDirs();

if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'No photo uploaded or upload error.', [], 400);
}

$file    = $_FILES['photo'];
$tmpPath = $file['tmp_name'];
$fileSize = $file['size'];

if ($fileSize > 5 * 1024 * 1024) {
    jsonResponse(false, 'Photo too large. Maximum size is 5 MB.', [], 413);
}

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);

if (!in_array($mimeType, ALLOWED_PHOTO_MIMES, true)) {
    jsonResponse(false, 'File type not allowed. Accepted: JPEG, PNG, GIF, WebP.', [], 415);
}

$ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$storedName = bin2hex(random_bytes(16)) . '.' . $ext;
$destPath   = PHOTO_DIR . $storedName;

if (!move_uploaded_file($tmpPath, $destPath)) {
    jsonResponse(false, 'Could not save photo. Please try again.', [], 500);
}
chmod($destPath, 0640);

// Remove old photo if it exists
$old = $pdo->prepare('SELECT profile_photo FROM students WHERE id = ?');
$old->execute([$studentId]);
$oldPhoto = $old->fetchColumn();
if ($oldPhoto && is_file(PHOTO_DIR . $oldPhoto)) {
    unlink(PHOTO_DIR . $oldPhoto);
}

$pdo->prepare('UPDATE students SET profile_photo = ? WHERE id = ?')
    ->execute([$storedName, $studentId]);

jsonResponse(true, 'Profile photo updated.', ['photo_filename' => $storedName]);
