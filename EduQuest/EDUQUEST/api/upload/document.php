<?php
/**
 * POST /api/upload/document.php?student_id=123
 * Upload a document (PDF, Word, Excel, image) for a student.
 *
 * Form fields:
 *   file          - the file (multipart/form-data)
 *   document_type - iep | medical_report | psychological_evaluation |
 *                   progress_report | 504_plan | parent_consent | other
 *   title         - human-readable title
 *   notes         - optional notes
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
if (!$studentId) jsonResponse(false, 'student_id query param is required.', [], 400);

$pdo = getDBConnection();
requireStudentAccess($studentId, $teacher);

// Ensure upload directory exists
ensureUploadDirs();

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
    ];
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    jsonResponse(false, $uploadErrors[$code] ?? 'Upload failed.', [], 400);
}

$file         = $_FILES['file'];
$tmpPath      = $file['tmp_name'];
$origName     = basename($file['name']);
$fileSize     = $file['size'];

// Size check
if ($fileSize > MAX_FILE_SIZE) {
    jsonResponse(false, 'File too large. Maximum size is 10 MB.', [], 413);
}

// Sanitize original filename first (needed for extension detection)
$safeOrigName = preg_replace('/[^a-zA-Z0-9._\- ]/', '_', $origName);

// MIME type validation using finfo, with Windows-safe normalisation
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$rawMime  = $finfo->file($tmpPath);
$mimeType = normalizeMime($rawMime, pathinfo($safeOrigName, PATHINFO_EXTENSION));

if (!in_array($mimeType, ALLOWED_DOC_MIMES, true)) {
    jsonResponse(false, 'File type not allowed. Accepted: PDF, Word (.doc/.docx), Excel (.xls/.xlsx), JPEG, PNG, TIFF.', [], 415);
}

// Generate a UUID-based stored filename to prevent path traversal or collisions
$ext          = strtolower(pathinfo($safeOrigName, PATHINFO_EXTENSION));
$storedName   = bin2hex(random_bytes(16)) . '.' . $ext;
$destPath     = UPLOAD_DIR . $storedName;

if (!move_uploaded_file($tmpPath, $destPath)) {
    jsonResponse(false, 'Could not save file. Please try again.', [], 500);
}

// Restrict file permissions
chmod($destPath, 0640);

// Validate document_type
$allowedDocTypes = ['iep','itp','individual_profile','medical_report','psychological_evaluation','progress_report','504_plan','parent_consent','other'];
$docType  = sanitizeString($_POST['document_type'] ?? 'other');
if (!in_array($docType, $allowedDocTypes, true)) $docType = 'other';

$title = sanitizeString($_POST['title'] ?? $safeOrigName);
if (!$title) $title = $safeOrigName;
$notes = sanitizeString($_POST['notes'] ?? '');

$ins = $pdo->prepare('
    INSERT INTO student_documents
        (student_id, uploaded_by, document_type, title, original_filename, stored_filename,
         file_size, mime_type, notes)
    VALUES (?,?,?,?,?,?,?,?,?)
');
$ins->execute([
    $studentId, $teacher['id'], $docType, $title,
    $safeOrigName, $storedName, $fileSize, $mimeType, $notes ?: null,
]);

jsonResponse(true, 'Document uploaded successfully.', [
    'document_id'       => (int) $pdo->lastInsertId(),
    'original_filename' => $safeOrigName,
    'document_type'     => $docType,
    'file_size'         => $fileSize,
    'mime_type'         => $mimeType,
], 201);
