<?php
/**
 * quiz_reset.php — Teacher resets a student's quiz attempts for an assignment
 *
 * POST  { assignment_id }
 *
 * Deletes (or marks abandoned) teacher_quiz_attempts rows linked to the given
 * assignment_id. Verifies the assignment belongs to a student owned by this teacher.
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user      = requireTeacher();
$db        = getDBConnection();
$teacherId = (int) $user['id'];

$body         = json_decode(file_get_contents('php://input'), true) ?? [];
$assignmentId = (int) ($body['assignment_id'] ?? 0);
if ($assignmentId <= 0) jsonResponse(false, 'assignment_id required.', [], 400);

// Verify ownership: assignment must be for a quiz owned by this teacher,
// and the student must belong to this teacher.
$check = $db->prepare("
    SELECT tqa.id
    FROM teacher_quiz_assignments tqa
    JOIN teacher_quizzes tq ON tq.id = tqa.quiz_id
    LEFT JOIN students s ON s.id = tqa.student_id
    WHERE tqa.id = :aid
      AND tq.teacher_id = :tid
      AND (tqa.student_id IS NULL OR s.teacher_id = :tid2)
    LIMIT 1
");
$check->execute([':aid' => $assignmentId, ':tid' => $teacherId, ':tid2' => $teacherId]);
if (!$check->fetch()) jsonResponse(false, 'Assignment not found or access denied.', [], 403);

// Fetch student_id and quiz_id from the assignment so we can delete the right attempts
$aStmt = $db->prepare('SELECT student_id, quiz_id FROM teacher_quiz_assignments WHERE id = :aid');
$aStmt->execute([':aid' => $assignmentId]);
$assignment = $aStmt->fetch(PDO::FETCH_ASSOC);

$del = $db->prepare("
    DELETE FROM teacher_quiz_attempts
    WHERE assignment_id = :aid
       OR (student_id = :sid AND quiz_id = :qid)
");
$del->execute([
    ':aid' => $assignmentId,
    ':sid' => $assignment['student_id'],
    ':qid' => $assignment['quiz_id'],
]);

jsonResponse(true, 'Quiz attempts reset.', ['deleted' => $del->rowCount()]);
