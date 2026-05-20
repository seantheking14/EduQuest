<?php
/**
 * game_abandon.php — Mark an in-progress game attempt as abandoned
 *
 * POST  { attempt_id }
 *
 * Called when the student navigates away mid-game (beforeunload).
 * Sets is_abandoned = 1, completed_at = NOW() for the open attempt row.
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireAuth();
$db   = getDBConnection();

$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) jsonResponse(false, 'Student profile not found.', [], 404);
$studentId = (int) $student['id'];

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$attemptId = (int) ($body['attempt_id'] ?? 0);

if ($attemptId <= 0) jsonResponse(false, 'attempt_id required.', [], 400);

$upd = $db->prepare("
    UPDATE game_attempts
    SET is_abandoned = 1, completed_at = NOW()
    WHERE id = :aid AND student_id = :sid AND completed_at IS NULL
");
$upd->execute([':aid' => $attemptId, ':sid' => $studentId]);

jsonResponse(true, 'Attempt marked as abandoned.');
