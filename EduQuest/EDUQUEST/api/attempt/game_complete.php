<?php
/**
 * game_complete.php — Student completes a game session
 *
 * POST  { attempt_id, score, max_score, xp_earned, time_spent_sec }
 *
 * Updates the game_attempts row created by game_start.php.
 * attempt_id is validated to belong to the calling student.
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../log_behavior.php';

$user = requireAuth();
$db   = getDBConnection();

$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) jsonResponse(false, 'Student profile not found.', [], 404);
$studentId = (int) $student['id'];

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$attemptId   = (int) ($body['attempt_id'] ?? 0);
$score       = max(0, (int) ($body['score'] ?? 0));
$maxScore    = max(0, (int) ($body['max_score'] ?? 0));
$xpEarned    = max(0, (int) ($body['xp_earned'] ?? 0));
$timeSpent   = max(0, (int) ($body['time_spent_sec'] ?? 0));

// attempt_id = 0 is the no-tracking sentinel from pre-migration fallback
if ($attemptId === 0) {
    jsonResponse(true, 'No tracking (pre-migration).');
}

if ($attemptId <= 0) jsonResponse(false, 'attempt_id required.', [], 400);

// Validate ownership (server-side)
$check = $db->prepare('SELECT id FROM game_attempts WHERE id = :aid AND student_id = :sid LIMIT 1');
$check->execute([':aid' => $attemptId, ':sid' => $studentId]);
if (!$check->fetch()) jsonResponse(false, 'Attempt not found.', [], 404);

$pct = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;

$upd = $db->prepare("
    UPDATE game_attempts
    SET score         = :score,
        max_score     = :max,
        percentage    = :pct,
        xp_earned     = :xp,
        time_spent_sec= :time,
        completed_at  = NOW(),
        is_abandoned  = 0
    WHERE id = :aid AND student_id = :sid
");
$upd->execute([
    ':score' => $score,
    ':max'   => $maxScore,
    ':pct'   => $pct,
    ':xp'    => $xpEarned,
    ':time'  => $timeSpent,
    ':aid'   => $attemptId,
    ':sid'   => $studentId,
]);

// ── Log behavioral engagement indicators ────────────────────────────────────
log_behavior($db, $studentId, 'engagement', 'task_completion_rate', (string) $pct, 'system', null, null, 'activity');
if ($timeSpent > 0) {
    log_behavior($db, $studentId, 'engagement', 'time_on_task', (string) $timeSpent, 'system', null, null, 'activity');
}
if ($xpEarned > 0) {
    log_behavior($db, $studentId, 'engagement', 'exp_accumulation_rate', (string) $xpEarned, 'system', null, null, 'activity');
}

jsonResponse(true, 'Game attempt recorded.', ['percentage' => $pct]);
