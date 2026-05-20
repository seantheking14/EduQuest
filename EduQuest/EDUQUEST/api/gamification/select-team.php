<?php
/**
 * POST /api/gamification/select-team.php
 * Lets a student choose a team (fire, water, or grass).
 *
 * Body: { team: "fire"|"water"|"grass" }
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
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireStudent();
$db   = getDBConnection();
$data = json_decode(file_get_contents('php://input'), true);

$team = strtolower(trim($data['team'] ?? ''));
$validTeams = ['fire', 'water', 'grass'];

if (!in_array($team, $validTeams, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid team. Choose fire, water, or grass.']);
    exit;
}

try {
    // Resolve student_id
    $stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $user['id']]);
    $studentRow = $stmt->fetch();

    if (!$studentRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
        exit;
    }

    $studentId = (int) $studentRow['id'];

    // Ensure gamification profile exists
    $stmt = $db->prepare('SELECT team FROM student_gamification WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        $stmt = $db->prepare('INSERT INTO student_gamification (student_id, team) VALUES (:sid, :team)');
        $stmt->execute([':sid' => $studentId, ':team' => $team]);
    } elseif ($profile['team']) {
        // Team is locked after initial selection — only teachers can change it
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your team is already set. Only your teacher can change it.']);
        exit;
    } else {
        $stmt = $db->prepare('UPDATE student_gamification SET team = :team WHERE student_id = :sid');
        $stmt->execute([':team' => $team, ':sid' => $studentId]);
    }

    // Check "Team Player" achievement
    $stmt = $db->prepare("SELECT id FROM achievements WHERE target_metric = 'team_joined' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $ach = $stmt->fetch();

    $achievementUnlocked = null;
    if ($ach) {
        $stmt = $db->prepare('SELECT is_unlocked FROM student_achievements WHERE student_id = :sid AND achievement_id = :aid');
        $stmt->execute([':sid' => $studentId, ':aid' => $ach['id']]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $stmt = $db->prepare('INSERT INTO student_achievements (student_id, achievement_id, progress, is_unlocked, unlocked_at) VALUES (:sid, :aid, 1, 1, NOW())');
            $stmt->execute([':sid' => $studentId, ':aid' => $ach['id']]);
            $achievementUnlocked = 'Team Player';
        }
    }

    $teamNames = ['fire' => 'Team Fire 🔥', 'water' => 'Team Water 💧', 'grass' => 'Team Grass 🌿'];

    echo json_encode([
        'success' => true,
        'message' => "Welcome to {$teamNames[$team]}!",
        'data'    => [
            'team'               => $team,
            'teamName'           => $teamNames[$team],
            'achievementUnlocked' => $achievementUnlocked,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to select team.']);
}
