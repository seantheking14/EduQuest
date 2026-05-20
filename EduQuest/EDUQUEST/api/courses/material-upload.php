<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$teacher = requireAuth();
ensureUploadDirs();

$moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
$title    = sanitizeString($_POST['title'] ?? '');

if (!$moduleId || !$title) {
    jsonResponse(false, 'module_id and title are required.', [], 422);
}

// Verify module belongs to this teacher
$pdo  = getDBConnection();
$stmt = $pdo->prepare('
    SELECT cm.id, cm.course_id
    FROM course_modules cm
    JOIN courses c ON c.id = cm.course_id
    WHERE cm.id = ? AND c.teacher_id = ? AND c.is_active = 1
');
$stmt->execute([$moduleId, $teacher['id']]);
$mod = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mod) {
    jsonResponse(false, 'Module not found.', [], 404);
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'No file uploaded or upload error.', [], 400);
}

$file    = $_FILES['file'];
$tmpPath = $file['tmp_name'];
$origName = basename($file['name']);

if ($file['size'] > MAX_FILE_SIZE) {
    jsonResponse(false, 'File too large. Maximum is 10 MB.', [], 413);
}

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);

$allowedMimes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'text/plain', 'text/csv',
    'video/mp4', 'audio/mpeg',
];

if (!in_array($mimeType, $allowedMimes, true)) {
    jsonResponse(false, 'File type not allowed.', [], 415);
}

// Build a safe stored filename
$ext           = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$storedName    = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
$destPath      = COURSE_MATERIAL_DIR . $storedName;

if (!move_uploaded_file($tmpPath, $destPath)) {
    jsonResponse(false, 'Failed to save the uploaded file.', [], 500);
}

$description = sanitizeString($_POST['description'] ?? '');
$dueDate     = sanitizeString($_POST['due_date']    ?? '');
$dueDate     = ($dueDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) ? $dueDate : null;

$pos = $pdo->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM course_materials WHERE module_id = ?');
$pos->execute([$moduleId]);
$position = (int)$pos->fetchColumn();

$pdo->prepare('
    INSERT INTO course_materials
        (module_id, course_id, title, description, material_type,
         original_filename, stored_filename, file_size, mime_type, position, due_date)
    VALUES (?,?,?,?,\'file\',?,?,?,?,?,?)
')->execute([
    $moduleId, $mod['course_id'], $title, $description,
    $origName, $storedName, (int)$file['size'], $mimeType, $position, $dueDate,
]);
$id = (int)$pdo->lastInsertId();

// ── Notify enrolled students ──────────────────────────────
require_once __DIR__ . '/../notifications/send.php';
$enr = $pdo->prepare('SELECT student_id FROM course_enrollments WHERE course_id = ?');
$enr->execute([$mod['course_id']]);
foreach ($enr->fetchAll(PDO::FETCH_COLUMN) as $sid) {
    send_notification($pdo, (int) $sid, 'student',
        'New material uploaded: ' . $title,
        '../../student-dashboard/learning/learning.html');
}

jsonResponse(true, 'File uploaded.', [
    'id'                => $id,
    'title'             => $title,
    'description'       => $description,
    'material_type'     => 'file',
    'original_filename' => $origName,
    'mime_type'         => $mimeType,
    'file_size'         => (int)$file['size'],
    'due_date'          => $dueDate,
    'is_visible'        => 1,
    'position'          => $position,
], 201);
