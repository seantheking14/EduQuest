<?php
/**
 * game_assign.php — Teacher assigns a game to one or more students
 *
 * POST  {game_id, student_ids:[...], max_attempts:0, due_date:"YYYY-MM-DD"|null}
 *
 * Creates / updates game_assignments rows (UPSERT).
 * Verifies teacher owns the listed students before inserting.
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user      = requireTeacher();
$db        = getDBConnection();
$teacherId = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$gameId      = (int) ($body['game_id'] ?? 0);
$studentIds  = array_map('intval', (array) ($body['student_ids'] ?? []));
$maxAttempts = max(0, (int) ($body['max_attempts'] ?? 0));
$dueDate     = !empty($body['due_date']) ? sanitizeString($body['due_date']) : null;

if ($gameId <= 0)        jsonResponse(false, 'game_id required.',    [], 400);
if (empty($studentIds))  jsonResponse(false, 'student_ids required.', [], 400);

// Validate game exists
$gStmt = $db->prepare('SELECT id, name FROM games WHERE id = :id AND is_active = 1');
$gStmt->execute([':id' => $gameId]);
$game = $gStmt->fetch(PDO::FETCH_ASSOC);
if (!$game) jsonResponse(false, 'Game not found.', [], 404);

// Validate all student_ids belong to this teacher
$placeholders = implode(',', array_fill(0, count($studentIds), '?'));
$sStmt = $db->prepare(
    "SELECT id FROM students WHERE id IN ($placeholders) AND teacher_id = ? AND is_active = 1"
);
$sStmt->execute(array_merge($studentIds, [$teacherId]));
$validIds = array_column($sStmt->fetchAll(PDO::FETCH_ASSOC), 'id');

if (count($validIds) !== count($studentIds)) {
    jsonResponse(false, 'One or more students not found or not yours.', [], 403);
}

$db->beginTransaction();
try {
    $upsert = $db->prepare("
        INSERT INTO game_assignments (game_id, student_id, teacher_id, max_attempts, due_date)
        VALUES (:gid, :sid, :tid, :max, :due)
        ON DUPLICATE KEY UPDATE
            teacher_id   = VALUES(teacher_id),
            max_attempts = VALUES(max_attempts),
            due_date     = VALUES(due_date)
    ");
    foreach ($validIds as $sid) {
        $upsert->execute([
            ':gid' => $gameId,
            ':sid' => (int) $sid,
            ':tid' => $teacherId,
            ':max' => $maxAttempts,
            ':due' => $dueDate,
        ]);
    }
    $db->commit();

    // Notify students
    require_once __DIR__ . '/../notifications/send.php';
    foreach ($validIds as $sid) {
        send_notification($db, (int) $sid, 'student',
            'New game assigned: ' . $game['name'],
            '../../student-dashboard/games/' . ($gameId === 1 ? 'word-scramble.html' : 'activity.html')
        );
    }

    jsonResponse(true, 'Game assigned successfully.', ['count' => count($validIds)]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(false, 'Failed to assign game: ' . $e->getMessage(), [], 500);
}
