<?php
/**
 * POST /api/students/import-document.php
 * Upload a physical student profile document (PDF, Word, image scan).
 * Creates a student record and attaches the document to it.
 * The uploaded file IS the student profile and can be viewed directly.
 *
 * Accepts multipart/form-data fields:
 *   file[]             - one or more files (up to 10)
 *   document_type      - document type for each file (or a single value applied to all)
 *   suggested_name[]   - optional student name hint per file (used as first_name placeholder)
 *
 * Returns: array of created student IDs and document IDs.
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

$teacher = requireAuth();
ensureUploadDirs();

// Normalize $_FILES to always be an array of individual files
$uploadedFiles = [];
if (!empty($_FILES['file'])) {
    $f = $_FILES['file'];
    if (is_array($f['name'])) {
        // Multiple files
        for ($i = 0; $i < count($f['name']); $i++) {
            if ($f['error'][$i] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = [
                    'name'     => $f['name'][$i],
                    'tmp_name' => $f['tmp_name'][$i],
                    'size'     => $f['size'][$i],
                    'error'    => $f['error'][$i],
                ];
            }
        }
    } else {
        // Single file
        if ($f['error'] === UPLOAD_ERR_OK) {
            $uploadedFiles[] = $f;
        }
    }
}

if (empty($uploadedFiles)) {
    jsonResponse(false, 'No files uploaded or all files had errors.', [], 400);
}

if (count($uploadedFiles) > 10) {
    jsonResponse(false, 'You can upload a maximum of 10 files at a time.', [], 400);
}

$allowedDocMimes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png',
    'image/tiff',
    'image/webp',
];

$allowedDocTypes = ['iep','medical_report','psychological_evaluation','progress_report','504_plan','parent_consent','other'];
$docTypeInput    = sanitizeString($_POST['document_type'] ?? 'other');
if (!in_array($docTypeInput, $allowedDocTypes, true)) $docTypeInput = 'other';

$suggestedNames = $_POST['suggested_name'] ?? [];
if (!is_array($suggestedNames)) $suggestedNames = [$suggestedNames];

$pdo     = getDBConnection();
$finfo   = new finfo(FILEINFO_MIME_TYPE);
$results = [];
$errors  = [];

foreach ($uploadedFiles as $idx => $file) {

    $origName = basename($file['name']);
    $tmpPath  = $file['tmp_name'];
    $fileSize = $file['size'];

    // Size check
    if ($fileSize > MAX_FILE_SIZE) {
        $errors[] = ['file' => $origName, 'error' => 'File too large (max 10 MB).'];
        continue;
    }

    // MIME validation with Windows-safe normalisation
    $rawMime  = $finfo->file($tmpPath);
    $mimeType = normalizeMime($rawMime, pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($mimeType, $allowedDocMimes, true)) {
        $errors[] = ['file' => $origName, 'error' => 'File type not allowed. Accepted: PDF, Word (.doc/.docx), JPEG, PNG, TIFF.'];
        continue;
    }

    $ext        = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath   = UPLOAD_DIR . $storedName;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        $errors[] = ['file' => $origName, 'error' => 'Server could not save the file.'];
        continue;
    }
    chmod($destPath, 0640);

    // Derive a placeholder student name from the filename or the suggested_name hint
    $hint = sanitizeString($suggestedNames[$idx] ?? '');
    if (!$hint) {
        // Strip extension and sanitize filename as a name hint
        $hint = sanitizeString(pathinfo($origName, PATHINFO_FILENAME));
        $hint = str_replace(['_', '-', '.'], ' ', $hint);
        $hint = trim($hint);
    }
    // Split into first/last if possible
    $nameParts = explode(' ', $hint, 2);
    $draftFirst = sanitizeString($nameParts[0] ?: 'Unknown');
    $draftLast  = sanitizeString($nameParts[1] ?? 'Student');

    $pdo->beginTransaction();
    try {
        // Create student record from uploaded document
        $sIns = $pdo->prepare('
            INSERT INTO students
                (teacher_id, first_name, last_name, notes, import_source, is_draft)
            VALUES (?, ?, ?, ?, \'document\', 0)
        ');
        $sIns->execute([
            $teacher['id'],
            $draftFirst,
            $draftLast,
            'Profile created from document "' . $origName . '".',
        ]);
        $studentId = (int) $pdo->lastInsertId();

        // Attach the document
        $dIns = $pdo->prepare('
            INSERT INTO student_documents
                (student_id, uploaded_by, document_type, title, original_filename,
                 stored_filename, file_size, mime_type, notes)
            VALUES (?,?,?,?,?,?,?,?,?)
        ');
        $dIns->execute([
            $studentId,
            $teacher['id'],
            $docTypeInput,
            'Source Document: ' . $origName,
            $origName,
            $storedName,
            $fileSize,
            $mimeType,
            'Uploaded as source document.',
        ]);
        $docId = (int) $pdo->lastInsertId();

        // Log the import
        $pdo->prepare('
            INSERT INTO import_logs
                (teacher_id, import_type, filename, stored_filename, total_rows, success_rows)
            VALUES (?,\'document\',?,?,1,1)
        ')->execute([$teacher['id'], $origName, $storedName]);

        $pdo->commit();

        $results[] = [
            'student_id'  => $studentId,
            'document_id' => $docId,
            'filename'    => $origName,
            'student_name' => $draftFirst . ' ' . $draftLast,
            'view_url'    => 'student-view.php?id=' . $studentId,
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        // Clean up the stored file on failure
        if (is_file($destPath)) unlink($destPath);
        $errors[] = ['file' => $origName, 'error' => 'Database error. File was not saved.'];
    }
}

$allFailed = empty($results);
$message   = $allFailed
    ? 'All files failed to import.'
    : count($results) . ' file(s) imported as student profile(s).';

jsonResponse(!$allFailed, $message, [
    'created_profiles' => $results,
    'errors'           => $errors,
], $allFailed ? 422 : 201);
