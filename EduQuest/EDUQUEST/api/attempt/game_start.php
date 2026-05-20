<?php
/**
 * game_start.php — Student starts a tracked game session
 *
 * POST  { game_type: 'word_scramble' | 'activity' }
 *
 * Returns: { success, data: { attempt_id, allowed, reason } }
 *
 * Called just before the game begins so we have a started_at timestamp.
 * If the student is over their attempt limit this returns allowed:false.
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/attempt_gate.php';

$user = requireAuth();
$db   = getDBConnection();

$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) jsonResponse(false, 'Student profile not found.', [], 404);
$studentId = (int) $student['id'];

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$gameType = sanitizeString($body['game_type'] ?? '');
if (!$gameType) jsonResponse(false, 'game_type required.', [], 400);

// Resolve game
$gStmt = $db->prepare('SELECT id FROM games WHERE game_type = :gt AND is_active = 1 LIMIT 1');
$gStmt->execute([':gt' => $gameType]);
$game = $gStmt->fetch(PDO::FETCH_ASSOC);
if (!$game) {
    // No tracking table — return a dummy attempt_id of 0 so client still works
    jsonResponse(true, 'OK', ['attempt_id' => 0, 'allowed' => true]);
}
$gameId = (int) $game['id'];

// Gate check
$gate = can_attempt($db, $studentId, 'game', $gameId);
if (!$gate['allowed']) {
    jsonResponse(true, 'Blocked', array_merge($gate, ['attempt_id' => null]));
}

// Create the attempt row
$attemptId = start_attempt($db, $studentId, 'game', $gameId, $gate['assignment_id']);
jsonResponse(true, 'Attempt started.', array_merge($gate, ['attempt_id' => $attemptId]));
