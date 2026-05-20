<?php
/**
 * DELETE /api/students/delete.php?id=123
 * "Archive" a student by unlinking them from the teacher.
 * If the student self-registered (has user_id), they go back to the
 * suggestions pool. If manually created (no user_id), soft-delete them.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'], true)) {
    http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher   = requireAuth();
$studentId = (int)($_GET['id'] ?? 0);
if (!$studentId) jsonResponse(false, 'Student ID is required.', [], 400);

$pdo = getDBConnection();
requireStudentAccess($studentId, $teacher);   // verifies ownership

// Check if student self-registered (has a user account)
$chk = $pdo->prepare('SELECT user_id FROM students WHERE id = ? AND teacher_id = ?');
$chk->execute([$studentId, $teacher['id']]);
$row = $chk->fetch(PDO::FETCH_ASSOC);

if ($row && $row['user_id']) {
    // Self-registered student — unlink from teacher so they go back to suggestions
    $stmt = $pdo->prepare('UPDATE students SET teacher_id = NULL WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$studentId, $teacher['id']]);

    // Also remove from any of this teacher's course enrollments
    $pdo->prepare('
        DELETE ce FROM course_enrollments ce
        JOIN courses c ON c.id = ce.course_id AND c.teacher_id = ?
        WHERE ce.student_id = ?
    ')->execute([$teacher['id'], $studentId]);

    jsonResponse(true, 'Student removed from your class. They can be re-added from the suggestions list.');
} else {
    // Manually created student (no account) — soft-delete as before
    $stmt = $pdo->prepare('UPDATE students SET is_active = 0 WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$studentId, $teacher['id']]);
    jsonResponse(true, 'Student profile archived successfully.');
}
