<?php
/**
 * my_attempts.php — Student fetches their own attempt history
 *
 * GET ?type=quiz&quiz_id=X      → attempt rows for that quiz
 * GET ?type=game&game_type=X    → attempt rows for that game type
 * GET ?type=game_list           → summary counts by game_type
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireAuth();
$db   = getDBConnection();

// Resolve student_id
$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) jsonResponse(false, 'Student profile not found.', [], 404);
$studentId = (int) $student['id'];

$type = sanitizeString($_GET['type'] ?? '');

// ── Quiz history ────────────────────────────────────────────────────────────
if ($type === 'quiz') {
    $quizId = (int) ($_GET['quiz_id'] ?? 0);
    if ($quizId <= 0) jsonResponse(false, 'quiz_id required.', [], 400);

    $stmt = $db->prepare("
        SELECT ta.id,
               ta.attempt_number,
               ta.score,
               ta.max_score,
               ta.percentage,
               ta.passed,
               ta.xp_earned,
               ta.time_spent_sec,
               ta.completed_at,
               ta.is_abandoned
        FROM teacher_quiz_attempts ta
        WHERE ta.quiz_id    = :qid
          AND ta.student_id = :sid
        ORDER BY ta.started_at DESC
    ");
    $stmt->execute([':qid' => $quizId, ':sid' => $studentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['id']             = (int)  $r['id'];
        $r['attempt_number'] = (int)  $r['attempt_number'];
        $r['score']          = (int)  $r['score'];
        $r['max_score']      = (int)  $r['max_score'];
        $r['percentage']     = $r['percentage'] !== null ? (float) $r['percentage'] : null;
        $r['passed']         = (bool) $r['passed'];
        $r['xp_earned']      = (int)  $r['xp_earned'];
        $r['time_spent_sec'] = (int)  $r['time_spent_sec'];
        $r['is_abandoned']   = (bool) $r['is_abandoned'];
    }
    unset($r);

    jsonResponse(true, 'OK', ['attempts' => $rows]);

// ── Single game type history ─────────────────────────────────────────────────
} elseif ($type === 'game') {
    $gameType = sanitizeString($_GET['game_type'] ?? '');
    if (!$gameType) jsonResponse(false, 'game_type required.', [], 400);

    $stmt = $db->prepare("
        SELECT gat.id,
               gat.score,
               gat.max_score,
               gat.percentage,
               gat.xp_earned,
               gat.time_spent_sec,
               gat.status,
               gat.completed_at
        FROM game_attempts gat
        JOIN game_assignments ga ON ga.id = gat.assignment_id
        JOIN games g             ON g.id  = ga.game_id
        WHERE ga.student_id = :sid
          AND g.game_type   = :gt
        ORDER BY gat.started_at DESC
        LIMIT 50
    ");
    $stmt->execute([':sid' => $studentId, ':gt' => $gameType]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['id']             = (int)  $r['id'];
        $r['score']          = (int)  $r['score'];
        $r['max_score']      = (int)  $r['max_score'];
        $r['percentage']     = $r['percentage'] !== null ? (float) $r['percentage'] : null;
        $r['xp_earned']      = (int)  $r['xp_earned'];
        $r['time_spent_sec'] = (int)  $r['time_spent_sec'];
    }
    unset($r);

    jsonResponse(true, 'OK', ['attempts' => $rows]);

// ── All game types summary ────────────────────────────────────────────────────
} elseif ($type === 'game_list') {
    $stmt = $db->prepare("
        SELECT g.game_type,
               g.game_name,
               COUNT(gat.id)                                                AS total_plays,
               SUM(CASE WHEN gat.status = 'completed' THEN 1 ELSE 0 END)   AS completed_plays,
               MAX(gat.percentage)                                          AS best_score,
               MAX(gat.completed_at)                                        AS last_played
        FROM game_assignments ga
        JOIN games g ON g.id = ga.game_id
        LEFT JOIN game_attempts gat ON gat.assignment_id = ga.id
        WHERE ga.student_id = :sid
        GROUP BY g.game_type, g.game_name
    ");
    $stmt->execute([':sid' => $studentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $r) {
        $result[$r['game_type']] = [
            'game_type'       => $r['game_type'],
            'game_name'       => $r['game_name'],
            'total_plays'     => (int) $r['total_plays'],
            'completed_plays' => (int) $r['completed_plays'],
            'best_score'      => $r['best_score'] !== null ? (float) $r['best_score'] : null,
            'last_played'     => $r['last_played'],
        ];
    }

    jsonResponse(true, 'OK', ['games' => $result]);

} else {
    jsonResponse(false, 'Invalid type. Use quiz, game, or game_list.', [], 400);
}
