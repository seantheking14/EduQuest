<?php
/**
 * POST /api/gamification/onboarding.php
 * First-time student onboarding — saves team + egg selection.
 *
 * Body: { team: "fire"|"water"|"grass", egg: "fire"|"water"|"grass", pet_name?: string }
 *
 * Updates the student_gamification record with chosen team and egg_type,
 * and unlocks the "Team Player" achievement if available.
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

$validOptions = ['fire', 'water', 'grass'];

$team = strtolower(trim($data['team'] ?? ''));
$egg  = strtolower(trim($data['egg']  ?? ''));

// pet_name: optional, max 32 chars, safe characters only
$rawPetName = trim($data['pet_name'] ?? '');
$petName    = null;
if ($rawPetName !== '') {
    // Strip anything outside letters, digits, spaces, hyphens, apostrophes
    $sanitized = preg_replace('/[^\p{L}\p{N} \'\-]/u', '', $rawPetName);
    $sanitized = trim(preg_replace('/\s+/', ' ', $sanitized)); // collapse whitespace
    if (mb_strlen($sanitized) >= 1 && mb_strlen($sanitized) <= 32) {
        $petName = $sanitized;
    }
}

if (!in_array($team, $validOptions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid team. Choose fire, water, or grass.']);
    exit;
}

if (!in_array($egg, $validOptions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid egg. Choose fire, water, or grass.']);
    exit;
}

try {
    // Resolve student_id from user_id
    $stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $user['id']]);
    $studentRow = $stmt->fetch();

    if (!$studentRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
        exit;
    }

    $studentId = (int) $studentRow['id'];

    // Ensure gamification profile exists and update team + egg_type
    $stmt = $db->prepare('SELECT id, team FROM student_gamification WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        $stmt = $db->prepare(
            'INSERT INTO student_gamification (student_id, team, egg_type, pet_name) VALUES (:sid, :team, :egg, :pname)'
        );
        $stmt->execute([':sid' => $studentId, ':team' => $team, ':egg' => $egg, ':pname' => $petName]);
    } else {
        $stmt = $db->prepare(
            'UPDATE student_gamification SET team = :team, egg_type = :egg, pet_name = :pname WHERE student_id = :sid'
        );
        $stmt->execute([':team' => $team, ':egg' => $egg, ':pname' => $petName, ':sid' => $studentId]);
    }

    // Unlock "Team Player" achievement if available
    $achievementUnlocked = null;
    $achievementsUnlocked = [];

    $stmt = $db->prepare(
        "SELECT id, title FROM achievements WHERE target_metric = 'team_joined' AND is_active = 1 LIMIT 1"
    );
    $stmt->execute();
    $ach = $stmt->fetch();

    if ($ach) {
        $stmt = $db->prepare(
            'SELECT is_unlocked FROM student_achievements WHERE student_id = :sid AND achievement_id = :aid'
        );
        $stmt->execute([':sid' => $studentId, ':aid' => $ach['id']]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $stmt = $db->prepare(
                'INSERT INTO student_achievements (student_id, achievement_id, progress, is_unlocked, unlocked_at)
                 VALUES (:sid, :aid, 1, 1, NOW())'
            );
            $stmt->execute([':sid' => $studentId, ':aid' => $ach['id']]);
            $achievementsUnlocked[] = $ach['title'];
        }
    }

    // Unlock "Notable Newcomer" (first_login) achievement if available
    $stmt = $db->prepare(
        "SELECT id, title FROM achievements WHERE target_metric = 'first_login' AND is_active = 1 LIMIT 1"
    );
    $stmt->execute();
    $newcomerAch = $stmt->fetch();

    if ($newcomerAch) {
        $stmt = $db->prepare(
            'SELECT is_unlocked FROM student_achievements WHERE student_id = :sid AND achievement_id = :aid'
        );
        $stmt->execute([':sid' => $studentId, ':aid' => $newcomerAch['id']]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $stmt = $db->prepare(
                'INSERT INTO student_achievements (student_id, achievement_id, progress, is_unlocked, unlocked_at)
                 VALUES (:sid, :aid, 1, 1, NOW())'
            );
            $stmt->execute([':sid' => $studentId, ':aid' => $newcomerAch['id']]);
            $achievementsUnlocked[] = $newcomerAch['title'];
        }
    }

    // For backward compat, keep single achievementUnlocked key
    $achievementUnlocked = count($achievementsUnlocked) > 0
        ? implode(' & ', $achievementsUnlocked)
        : null;

    $teamNames = ['fire' => 'Team Fire 🔥', 'water' => 'Team Water 💧', 'grass' => 'Team Grass 🌿'];
    $eggNames  = ['fire' => 'Fire Egg 🔥',  'water' => 'Water Egg 💧',  'grass' => 'Grass Egg 🌿'];

    echo json_encode([
        'success' => true,
        'message' => "Welcome to {$teamNames[$team]}! Your {$eggNames[$egg]} awaits!",
        'data'    => [
            'team'                => $team,
            'teamName'            => $teamNames[$team],
            'egg'                 => $egg,
            'eggName'             => $eggNames[$egg],
            'petName'             => $petName,
            'achievementUnlocked' => $achievementUnlocked,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to complete onboarding.']);
}
