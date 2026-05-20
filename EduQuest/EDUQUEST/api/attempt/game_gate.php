<?php
/**
 * game_gate.php — Student checks whether they can play a game
 *
 * GET  ?game_type=word_scramble|activity
 *
 * Returns:
 *   { success, data: { allowed, reason, assignment_id, attempts_used,
 *                      max_attempts, due_date, game_id, game_name } }
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/attempt_gate.php';

$user = requireAuth();
$db   = getDBConnection();

// Resolve student_id
$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) jsonResponse(false, 'Student profile not found.', [], 404);
$studentId = (int) $student['id'];

$gameType = sanitizeString($_GET['game_type'] ?? '');
if (!$gameType) jsonResponse(false, 'game_type required.', [], 400);

// Resolve game_id from game_type
$gStmt = $db->prepare('SELECT id, name FROM games WHERE game_type = :gt AND is_active = 1 LIMIT 1');
$gStmt->execute([':gt' => $gameType]);
$game = $gStmt->fetch(PDO::FETCH_ASSOC);
if (!$game) {
    // No games table entry = freely playable (pre-migration state)
    jsonResponse(true, 'OK', ['allowed' => true, 'game_id' => null, 'game_name' => $gameType]);
}

$gameId = (int) $game['id'];
$gate   = can_attempt($db, $studentId, 'game', $gameId);

jsonResponse(true, 'OK', array_merge($gate, [
    'game_id'   => $gameId,
    'game_name' => $game['name'],
]));
