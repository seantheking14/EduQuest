<?php
/**
 * POST /api/students/link.php
 *
 * Links a self-registered student (teacher_id IS NULL) to the
 * authenticated teacher. Optionally enrolls the student in a course.
 *
 * Body JSON:
 *   { "student_id": 5 }                     – just link
 *   { "student_id": 5, "course_id": 2 }     – link + enroll in course
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

$teacher = requireAuth();
$pdo     = getDBConnection();

$input     = json_decode(file_get_contents('php://input'), true);
$studentId = (int)($input['student_id'] ?? 0);
$courseId   = (int)($input['course_id']  ?? 0);

if (!$studentId) {
    jsonResponse(false, 'student_id is required.', [], 422);
}

// Verify the student exists, is unlinked, and self-registered
$stmt = $pdo->prepare('
    SELECT s.id, s.first_name, s.last_name
    FROM students s
    WHERE s.id = ? AND s.teacher_id IS NULL AND s.is_active = 1 AND s.user_id IS NOT NULL
');
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    jsonResponse(false, 'Student not found or already linked to a teacher.', [], 404);
}

$pdo->beginTransaction();

try {
    // Link student to teacher
    $upd = $pdo->prepare('UPDATE students SET teacher_id = ? WHERE id = ?');
    $upd->execute([$teacher['id'], $studentId]);

    // Optional: enroll in course
    $enrolled = false;
    if ($courseId) {
        // Verify teacher owns the course
        $chk = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND teacher_id = ? AND is_active = 1');
        $chk->execute([$courseId, $teacher['id']]);
        if ($chk->fetch()) {
            $ins = $pdo->prepare('INSERT IGNORE INTO course_enrollments (course_id, student_id) VALUES (?, ?)');
            $ins->execute([$courseId, $studentId]);
            $enrolled = true;
        }
    }

    $pdo->commit();

    jsonResponse(true, 'Student linked successfully.', [
        'student_id'   => $studentId,
        'student_name' => $student['first_name'] . ' ' . $student['last_name'],
        'enrolled'     => $enrolled,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(false, 'Failed to link student. Please try again.', [], 500);
}
