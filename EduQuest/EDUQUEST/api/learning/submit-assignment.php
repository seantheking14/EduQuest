<?php
/**
 * POST /api/learning/submit-assignment.php
 * Student uploads a file submission for an assignment-type course material.
 *
 * Expects multipart/form-data with:
 *   materialId (int)   — the assignment course_material id
 *   file       (file)  — the uploaded file
 *   notes      (string, optional) — student notes
 *
 * GET /api/learning/submit-assignment.php?materialId=X
 * Returns the student's current submission for the given assignment (if any).
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

ensureUploadDirs();

$user = requireAuth();
$db   = getDBConnection();

// Resolve student id
$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['role'] === 'student' ? $user['id'] : 0]);
$studentRow = $stmt->fetch();

if (!$studentRow) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$studentId = (int) $studentRow['id'];

// ─── GET: Return current submission status ───
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    $materialId = (int) ($_GET['materialId'] ?? 0);
    if (!$materialId) {
        echo json_encode(['success' => false, 'message' => 'materialId required.']);
        exit;
    }

    // Verify it's an assignment the student is enrolled in
    $check = $db->prepare('
        SELECT mat.id
        FROM course_materials mat
        JOIN course_enrollments ce ON ce.course_id = mat.course_id AND ce.student_id = ?
        WHERE mat.id = ? AND mat.material_type = \'assignment\'
    ');
    $check->execute([$studentId, $materialId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found or not enrolled.']);
        exit;
    }

    $stmt = $db->prepare('
        SELECT id, original_filename, file_size, mime_type, notes, status, grade, feedback, submitted_at, graded_at
        FROM assignment_submissions
        WHERE material_id = ? AND student_id = ?
        LIMIT 1
    ');
    $stmt->execute([$materialId, $studentId]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => $sub ? [
            'id'              => (int) $sub['id'],
            'originalFilename' => $sub['original_filename'],
            'fileSize'        => (int) $sub['file_size'],
            'mimeType'        => $sub['mime_type'],
            'notes'           => $sub['notes'],
            'status'          => $sub['status'],
            'grade'           => $sub['grade'],
            'feedback'        => $sub['feedback'],
            'submittedAt'     => $sub['submitted_at'],
            'gradedAt'        => $sub['graded_at'],
        ] : null,
    ]);
    exit;
}

// ─── POST: Upload submission ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

header('Content-Type: application/json');

$materialId = (int) ($_POST['materialId'] ?? 0);
$notes      = trim($_POST['notes'] ?? '');

if (!$materialId) {
    echo json_encode(['success' => false, 'message' => 'materialId is required.']);
    exit;
}

// Verify the material is an assignment and student is enrolled
$check = $db->prepare('
    SELECT mat.id, mat.course_id, mat.title
    FROM course_materials mat
    JOIN course_enrollments ce ON ce.course_id = mat.course_id AND ce.student_id = ?
    WHERE mat.id = ? AND mat.material_type = \'assignment\' AND mat.is_visible = 1
');
$check->execute([$studentId, $materialId]);
$assignment = $check->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    echo json_encode(['success' => false, 'message' => 'Assignment not found or you are not enrolled.']);
    exit;
}

// Check file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'Please select a file to upload.']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server maximum size.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form maximum size.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp directory.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
    ];
    $msg = $errors[$file['error']] ?? 'Upload error (code ' . $file['error'] . ').';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

if ($file['size'] > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File exceeds the 10 MB limit.']);
    exit;
}

// Validate file extension
$origName = basename($file['name']);
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowed  = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif','txt','csv','mp4','mp3','zip','rar'];

if (!in_array($ext, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed: .' . $ext]);
    exit;
}

// Detect MIME
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$mime  = normalizeMime($mime, $ext);

// Generate stored filename
$storedName = 'sub_' . bin2hex(random_bytes(16)) . '.' . $ext;
$destPath   = SUBMISSION_DIR . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file. Please try again.']);
    exit;
}

try {
    // Check if a previous submission exists
    $prev = $db->prepare('SELECT id, stored_filename FROM assignment_submissions WHERE material_id = ? AND student_id = ?');
    $prev->execute([$materialId, $studentId]);
    $existing = $prev->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Delete old file
        $oldPath = SUBMISSION_DIR . $existing['stored_filename'];
        if ($existing['stored_filename'] && is_file($oldPath)) {
            unlink($oldPath);
        }

        // Update
        $stmt = $db->prepare('
            UPDATE assignment_submissions
            SET original_filename = ?, stored_filename = ?, file_size = ?, mime_type = ?,
                notes = ?, status = \'submitted\', grade = NULL, feedback = NULL,
                submitted_at = NOW(), graded_at = NULL
            WHERE id = ?
        ');
        $stmt->execute([$origName, $storedName, $file['size'], $mime, $notes, (int) $existing['id']]);
        $subId = (int) $existing['id'];
    } else {
        // Insert
        $stmt = $db->prepare('
            INSERT INTO assignment_submissions
                (material_id, student_id, original_filename, stored_filename, file_size, mime_type, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$materialId, $studentId, $origName, $storedName, $file['size'], $mime, $notes]);
        $subId = (int) $db->lastInsertId();
    }

    // ── Notify teacher ────────────────────────────────────
    require_once __DIR__ . '/../notifications/send.php';
    $tRow = $db->prepare('SELECT teacher_id, first_name, last_name FROM students WHERE id = ? LIMIT 1');
    $tRow->execute([$studentId]);
    $studentInfo = $tRow->fetch();
    if ($studentInfo && $studentInfo['teacher_id']) {
        $sName = trim($studentInfo['first_name'] . ' ' . $studentInfo['last_name']) ?: 'A student';
        send_notification($db, (int) $studentInfo['teacher_id'], 'teacher',
            $sName . ' submitted an assignment: ' . $assignment['title'],
            'students.php');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Assignment submitted successfully!',
        'data'    => [
            'id'              => $subId,
            'originalFilename' => $origName,
            'fileSize'        => (int) $file['size'],
            'mimeType'        => $mime,
            'status'          => 'submitted',
            'submittedAt'     => date('Y-m-d H:i:s'),
        ],
    ]);

} catch (Exception $e) {
    // Clean up uploaded file on DB error
    if (is_file($destPath)) unlink($destPath);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save submission.']);
}
