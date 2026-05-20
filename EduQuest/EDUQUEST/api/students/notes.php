<?php
/**
 * POST /api/students/notes.php?student_id=123
 * Add a teacher observation/progress note for a student.
 *
 * GET /api/students/notes.php?student_id=123
 * List all notes for a student.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher   = requireAuth();
$studentId = (int)($_GET['student_id'] ?? 0);
if (!$studentId) jsonResponse(false, 'student_id is required.', [], 400);

$pdo = getDBConnection();
requireStudentAccess($studentId, $teacher);

// --- GET: list notes ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 20)));
    $offset  = ($page - 1) * $perPage;

    $total = (int) $pdo->prepare('SELECT COUNT(*) FROM teacher_notes WHERE student_id = ?')
                        ->execute([$studentId]) ? 0 : 0;
    $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM teacher_notes WHERE student_id = ?');
    $cntStmt->execute([$studentId]);
    $total = (int) $cntStmt->fetchColumn();

    $stmt = $pdo->prepare('
        SELECT tn.*, CONCAT(t.first_name, \' \', t.last_name) AS teacher_name
        FROM teacher_notes tn
        JOIN teachers t ON t.id = tn.teacher_id
        WHERE tn.student_id = ?
        ORDER BY tn.note_date DESC, tn.created_at DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$studentId, $perPage, $offset]);
    $notes = $stmt->fetchAll();

    jsonResponse(true, 'Notes retrieved.', [
        'notes'      => $notes,
        'pagination' => [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / $perPage),
        ],
    ]);
}

// --- POST: add a note ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $content = sanitizeString($body['content'] ?? '');
    $noteDate = sanitizeString($body['note_date'] ?? date('Y-m-d'));
    $noteType = sanitizeString($body['note_type'] ?? 'general');
    $subject  = sanitizeString($body['subject_area'] ?? '');
    $isPrivate = (int)(bool)($body['is_private'] ?? false);

    if (!$content) jsonResponse(false, 'Note content is required.', [], 400);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $noteDate)) $noteDate = date('Y-m-d');

    $allowedTypes = ['observation','progress','incident','meeting','general'];
    if (!in_array($noteType, $allowedTypes, true)) $noteType = 'general';

    $ins = $pdo->prepare('
        INSERT INTO teacher_notes
            (student_id, teacher_id, note_date, note_type, subject_area, content, is_private)
        VALUES (?,?,?,?,?,?,?)
    ');
    $ins->execute([
        $studentId, $teacher['id'], $noteDate, $noteType,
        $subject ?: null, $content, $isPrivate,
    ]);

    jsonResponse(true, 'Note added successfully.', ['note_id' => (int) $pdo->lastInsertId()], 201);
}

jsonResponse(false, 'Method not allowed.', [], 405);
