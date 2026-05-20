<?php
/**
 * Student Activity Attempt Tracking
 * Tracks start and completion of teacher-created activities
 *
 * POST   action=start                        — start an activity attempt
 * POST   action=complete                     — complete an activity attempt
 * POST   action=abandon                      — abandon an activity attempt
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../log_behavior.php';

$user = requireStudent();
$db   = getDBConnection();
$userId = (int) $user['id'];

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'start':    startActivityAttempt($body); break;
        case 'complete': completeActivityAttempt($body); break;
        case 'abandon':  abandonActivityAttempt($body); break;
        default: jsonResponse(false, 'Invalid action.', [], 400);
    }
}

/* ═══════════════════════════════════════════════════════════
   START — begin a teacher activity attempt
   ═══════════════════════════════════════════════════════════ */
function startActivityAttempt($body) {
    global $db, $userId;

    $activityId = (int) ($body['activity_id'] ?? 0);
    if ($activityId <= 0) jsonResponse(false, 'Activity ID required.', [], 400);

    // Get student ID
    $stmt = $db->prepare("SELECT id FROM students WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();

    if (!$row) jsonResponse(false, 'Student not found.', [], 404);
    $studentId = (int) $row['id'];

    // Verify activity exists
    $stmt = $db->prepare("SELECT id FROM teacher_activities WHERE id = :id AND is_active = 1 LIMIT 1");
    $stmt->execute([':id' => $activityId]);
    if (!$stmt->fetch()) jsonResponse(false, 'Activity not found.', [], 404);

    try {
        // Get current attempt number
        $stmt = $db->prepare("
            SELECT COUNT(*) + 1 as attempt_num FROM teacher_activity_attempts
            WHERE student_id = :sid AND activity_id = :aid
        ");
        $stmt->execute([':sid' => $studentId, ':aid' => $activityId]);
        $attemptNum = (int) $stmt->fetch()['attempt_num'];

        // Create new attempt record
        $stmt = $db->prepare("
            INSERT INTO teacher_activity_attempts 
            (student_id, activity_id, attempt_number, started_at)
            VALUES (:sid, :aid, :attempt, NOW())
        ");
        $stmt->execute([
            ':sid' => $studentId,
            ':aid' => $activityId,
            ':attempt' => $attemptNum
        ]);

        $attemptId = (int) $db->lastInsertId();

        jsonResponse(true, 'Attempt started.', ['attempt_id' => $attemptId], 201);
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to start attempt: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   COMPLETE — finish a teacher activity attempt
   ═══════════════════════════════════════════════════════════ */
function completeActivityAttempt($body) {
    global $db;

    $attemptId = (int) ($body['attempt_id'] ?? 0);
    $score = (int) ($body['score'] ?? 0);
    $maxScore = (int) ($body['max_score'] ?? 100);
    $timeSpentSec = (int) ($body['time_spent_sec'] ?? 0);
    $xpEarned = (int) ($body['xp_earned'] ?? 0);
    $answers = $body['answers_json'] ?? null;

    if ($attemptId <= 0) jsonResponse(false, 'Attempt ID required.', [], 400);

    try {
        // Get activity settings
        $stmt = $db->prepare("SELECT pass_percentage, student_id FROM teacher_activity_attempts WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $attemptId]);
        $attempt = $stmt->fetch();

        if (!$attempt) jsonResponse(false, 'Attempt not found.', [], 404);
        $studentIdForLog = (int) $attempt['student_id'];

        // Calculate percentage and pass status
        $percentage = $maxScore > 0 ? ($score / $maxScore) * 100 : 0;

        // Get the activity's pass percentage requirement
        $stmt = $db->prepare("
            SELECT a.pass_percentage FROM teacher_activity_attempts taa
            JOIN teacher_activities a ON a.id = taa.activity_id
            WHERE taa.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $attemptId]);
        $actRow = $stmt->fetch();
        $passPercentage = (int) ($actRow['pass_percentage'] ?? 70);
        $passed = $percentage >= $passPercentage ? 1 : 0;

        // Update attempt
        $stmt = $db->prepare("
            UPDATE teacher_activity_attempts SET
            score = :score, max_score = :max_score, percentage = :percentage,
            passed = :passed, time_spent_sec = :time_spent, xp_earned = :xp,
            answers_json = :answers, completed_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':score' => $score,
            ':max_score' => $maxScore,
            ':percentage' => $percentage,
            ':passed' => $passed,
            ':time_spent' => $timeSpentSec,
            ':xp' => $xpEarned,
            ':answers' => $answers ? json_encode($answers) : null,
            ':id' => $attemptId
        ]);

        // ── Log behavioral engagement indicators ────────────────────────────────────
        log_behavior($db, $studentIdForLog, 'engagement', 'task_completion_rate', (string) round($percentage, 2), 'system', null, null, 'activity');
        if ($timeSpentSec > 0) {
            log_behavior($db, $studentIdForLog, 'engagement', 'time_on_task', (string) $timeSpentSec, 'system', null, null, 'activity');
        }
        if ($xpEarned > 0) {
            log_behavior($db, $studentIdForLog, 'engagement', 'exp_accumulation_rate', (string) $xpEarned, 'system', null, null, 'activity');
        }

        jsonResponse(true, 'Attempt completed.', [
            'attempt_id' => $attemptId,
            'percentage' => round($percentage, 2),
            'passed' => (bool) $passed,
            'xp_earned' => $xpEarned
        ]);
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to complete attempt: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   ABANDON — mark an attempt as abandoned
   ═══════════════════════════════════════════════════════════ */
function abandonActivityAttempt($body) {
    global $db;

    $attemptId = (int) ($body['attempt_id'] ?? 0);
    if ($attemptId <= 0) return;

    try {
        $db->prepare("
            UPDATE teacher_activity_attempts SET
            completed_at = NOW()
            WHERE id = :id AND completed_at IS NULL
        ")->execute([':id' => $attemptId]);
    } catch (Exception $e) {
        // Silent fail
    }
}
