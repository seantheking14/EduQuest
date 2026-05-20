<?php
/**
 * POST /api/gamification/assign-team.php
 * Teacher-only endpoint to assign or change a student's team.
 *
 * Body: { student_id: int, team: "fire"|"water"|"grass" }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher = requireTeacher();
$tid     = (int) $teacher['id'];
$db      = getDBConnection();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    jsonResponse(false, 'Invalid JSON payload.', [], 400);
}

$studentId  = isset($data['student_id']) ? (int) $data['student_id'] : 0;
$team       = strtolower(trim($data['team'] ?? ''));
$validTeams = ['fire', 'water', 'grass'];

if (!$studentId) {
    jsonResponse(false, 'student_id is required.', [], 422);
}
if (!in_array($team, $validTeams, true)) {
    jsonResponse(false, 'Invalid team. Choose fire, water, or grass.', [], 400);
}

try {
    // Verify this student belongs to this teacher
    $stmt = $db->prepare("SELECT id FROM students WHERE id = :sid AND teacher_id = :tid AND is_active = 1 LIMIT 1");
    $stmt->execute([':sid' => $studentId, ':tid' => $tid]);
    if (!$stmt->fetch()) {
        jsonResponse(false, 'Student not found or does not belong to you.', [], 404);
    }

    // Ensure gamification profile exists
    $stmt = $db->prepare('SELECT id FROM student_gamification WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        $stmt = $db->prepare('INSERT INTO student_gamification (student_id, team) VALUES (:sid, :team)');
        $stmt->execute([':sid' => $studentId, ':team' => $team]);
    } else {
        $stmt = $db->prepare('UPDATE student_gamification SET team = :team WHERE student_id = :sid');
        $stmt->execute([':team' => $team, ':sid' => $studentId]);
    }

    $teamNames = ['fire' => 'Team Fire 🔥', 'water' => 'Team Water 💧', 'grass' => 'Team Grass 🌿'];

    jsonResponse(true, "Student assigned to {$teamNames[$team]}.", [
        'student_id' => $studentId,
        'team'       => $team,
        'teamName'   => $teamNames[$team],
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to assign team.', [], 500);
}
