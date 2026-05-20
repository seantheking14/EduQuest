<?php
/**
 * student_history.php — Teacher fetches a student's quiz & game attempt history
 *
 * GET  ?student_id=X
 *
 * Returns:
 *   {
 *     quiz_assignments: [ { assignment_id, quiz_title, due_date, max_attempts,
 *                           attempts_used, best_score, ever_passed,
 *                           attempts: [ { id, attempt_number, percentage, passed,
 *                                         score, max_score, xp_earned,
 *                                         time_spent_sec, started_at, completed_at,
 *                                         is_abandoned } ] } ],
 *     game_assignments: [ { assignment_id, game_name, game_type, due_date,
 *                           max_attempts, attempts_used, best_score,
 *                           attempts: [ { id, percentage, score, max_score,
 *                                         xp_earned, time_spent_sec,
 *                                         started_at, completed_at,
 *                                         is_abandoned } ] } ]
 *   }
 *
 * Teacher must own the student.
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user      = requireTeacher();
$db        = getDBConnection();
$teacherId = (int) $user['id'];

$studentId = (int) ($_GET['student_id'] ?? 0);
if ($studentId <= 0) jsonResponse(false, 'student_id required.', [], 400);

// Verify teacher owns this student
$sStmt = $db->prepare('SELECT id FROM students WHERE id = :sid AND teacher_id = :tid LIMIT 1');
$sStmt->execute([':sid' => $studentId, ':tid' => $teacherId]);
if (!$sStmt->fetch()) jsonResponse(false, 'Student not found or access denied.', [], 403);

/* ─── Quiz assignments + attempts ─────────────────────────── */
$qaStmt = $db->prepare("
    SELECT tqa.id AS assignment_id,
           tq.title AS quiz_title,
           tqa.due_date,
           tqa.max_attempts,
           (SELECT COUNT(*) FROM teacher_quiz_attempts WHERE assignment_id = tqa.id) AS attempts_used,
           (SELECT MAX(percentage) FROM teacher_quiz_attempts WHERE assignment_id = tqa.id) AS best_score,
           (SELECT MAX(passed)     FROM teacher_quiz_attempts WHERE assignment_id = tqa.id) AS ever_passed
    FROM teacher_quiz_assignments tqa
    JOIN teacher_quizzes tq ON tq.id = tqa.quiz_id
    WHERE tqa.student_id = :sid
    ORDER BY tqa.assigned_at DESC
");
$qaStmt->execute([':sid' => $studentId]);
$quizAssignments = $qaStmt->fetchAll(PDO::FETCH_ASSOC);

// Load individual attempts per assignment
foreach ($quizAssignments as &$qa) {
    $aStmt = $db->prepare("
        SELECT id, attempt_number, score, max_score, percentage, passed,
               xp_earned, time_spent_sec, started_at, completed_at, is_abandoned
        FROM teacher_quiz_attempts
        WHERE assignment_id = :aid
        ORDER BY started_at DESC
    ");
    $aStmt->execute([':aid' => $qa['assignment_id']]);
    $qa['attempts'] = $aStmt->fetchAll(PDO::FETCH_ASSOC);
    // Cast numeric fields
    $qa['assignment_id']  = (int) $qa['assignment_id'];
    $qa['max_attempts']   = (int) $qa['max_attempts'];
    $qa['attempts_used']  = (int) $qa['attempts_used'];
    $qa['best_score']     = $qa['best_score'] !== null ? (float) $qa['best_score'] : null;
    $qa['ever_passed']    = (bool) $qa['ever_passed'];
}
unset($qa);

/* ─── Game assignments + attempts ─────────────────────────── */
$gaStmt = $db->prepare("
    SELECT ga.id AS assignment_id,
           g.name AS game_name,
           g.game_type,
           ga.due_date,
           ga.max_attempts,
           (SELECT COUNT(*) FROM game_attempts WHERE assignment_id = ga.id) AS attempts_used,
           (SELECT MAX(percentage) FROM game_attempts WHERE assignment_id = ga.id) AS best_score
    FROM game_assignments ga
    JOIN games g ON g.id = ga.game_id
    WHERE ga.student_id = :sid
    ORDER BY ga.assigned_at DESC
");
$gaStmt->execute([':sid' => $studentId]);
$gameAssignments = $gaStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($gameAssignments as &$ga) {
    $aStmt = $db->prepare("
        SELECT id, score, max_score, percentage, xp_earned,
               time_spent_sec, started_at, completed_at, is_abandoned
        FROM game_attempts
        WHERE assignment_id = :aid
        ORDER BY started_at DESC
    ");
    $aStmt->execute([':aid' => $ga['assignment_id']]);
    $ga['attempts'] = $aStmt->fetchAll(PDO::FETCH_ASSOC);
    $ga['assignment_id'] = (int) $ga['assignment_id'];
    $ga['max_attempts']  = (int) $ga['max_attempts'];
    $ga['attempts_used'] = (int) $ga['attempts_used'];
    $ga['best_score']    = $ga['best_score'] !== null ? (float) $ga['best_score'] : null;
}
unset($ga);

jsonResponse(true, 'History fetched.', [
    'quiz_assignments' => $quizAssignments,
    'game_assignments' => $gameAssignments,
]);
