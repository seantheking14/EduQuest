<?php
/**
 * POST /EDUQUEST/api/track_question_time.php
 *
 * Body (JSON): {
 *   user_id, quiz_id, question_id, time_spent_seconds,
 *   attempt_number, answered_correctly
 * }
 *
 * Inserts one row into question_interactions.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$authUser = requireAuth();

$body = json_decode(file_get_contents('php://input'), true);

// ── Validate posted user_id matches session ────────────────────────────────────
$postedUserId = isset($body['user_id']) ? (int) $body['user_id'] : 0;
if ($postedUserId !== (int) $authUser['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden.']);
    exit;
}

// ── Resolve students.id ────────────────────────────────────────────────────────
$db   = getDBConnection();
$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $authUser['id']]);
$student = $stmt->fetch();

if (!$student) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
    exit;
}
$studentId = (int) $student['id'];

// ── Sanitize inputs ────────────────────────────────────────────────────────────
$quizId        = max(1, (int) ($body['quiz_id']            ?? 0));
$questionId    = max(1, (int) ($body['question_id']        ?? 0));
$timeSpent     = max(0, (int) ($body['time_spent_seconds'] ?? 0));
$attemptNumber = max(1, (int) ($body['attempt_number']     ?? 1));

// answered_correctly: 1, 0, or NULL
$correctRaw = $body['answered_correctly'] ?? null;
$answeredCorrectly = is_null($correctRaw) ? null : ($correctRaw ? 1 : 0);

if ($quizId <= 0 || $questionId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid quiz_id or question_id.']);
    exit;
}

// ── Insert ─────────────────────────────────────────────────────────────────────
try {
    $stmt = $db->prepare('
        INSERT INTO question_interactions
            (student_id, quiz_id, question_id, time_spent_seconds, attempt_number, answered_correctly)
        VALUES
            (:sid, :qzid, :qid, :ts, :att, :correct)
    ');
    $stmt->execute([
        ':sid'     => $studentId,
        ':qzid'    => $quizId,
        ':qid'     => $questionId,
        ':ts'      => $timeSpent,
        ':att'     => $attemptNumber,
        ':correct' => $answeredCorrectly,
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('track_question_time error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
