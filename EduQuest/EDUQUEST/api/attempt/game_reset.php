<?php
/**
 * game_reset.php — Teacher resets a student's game attempts for an assignment
 *
 * POST  { assignment_id }
 *
 * Deletes all game_attempts rows for the given assignment_id.
 * Verifies the assignment belongs to the calling teacher's student.
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

// Verify ownership: assignment must belong to a student of this teacher
$check = $db->prepare("
    SELECT ga.id FROM game_assignments ga
    JOIN students s ON s.id = ga.student_id
    WHERE ga.id = :aid AND ga.teacher_id = :tid
    LIMIT 1
");
$check->execute([':aid' => $assignmentId, ':tid' => $teacherId]);
if (!$check->fetch()) jsonResponse(false, 'Assignment not found or access denied.', [], 403);

$del = $db->prepare('DELETE FROM game_attempts WHERE assignment_id = :aid');
$del->execute([':aid' => $assignmentId]);

jsonResponse(true, 'Game attempts reset.', ['deleted' => $del->rowCount()]);
