<?php
/**
 * GET /api/gamification/rewards.php
 * Returns available virtual rewards and student's claimed rewards.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireAuth();
$db   = getDBConnection();

try {
    // Resolve student_id
    $stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $user['role'] === 'student' ? $user['id'] : 0]);
    $studentRow = $stmt->fetch();

    if (!$studentRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
        exit;
    }
    $studentId = (int) $studentRow['id'];

    // Get student's current XP
    $stmt = $db->prepare('SELECT total_xp FROM student_gamification WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $profile = $stmt->fetch();
    $totalXp = $profile ? (int) $profile['total_xp'] : 0;

    // Get all rewards with claim status
    $stmt = $db->prepare("
        SELECT
            vr.*,
            sr.claimed_at,
            CASE WHEN sr.id IS NOT NULL THEN 1 ELSE 0 END AS is_claimed
        FROM virtual_rewards vr
        LEFT JOIN student_rewards sr ON sr.reward_id = vr.id AND sr.student_id = :sid
        WHERE vr.is_active = 1
        ORDER BY vr.milestone_xp ASC, vr.xp_cost ASC
    ");
    $stmt->execute([':sid' => $studentId]);
    $rewards = $stmt->fetchAll();

    $result = [];
    foreach ($rewards as $r) {
        $canClaim = false;
        if (!(bool) $r['is_claimed']) {
            if ($r['milestone_xp'] !== null) {
                $canClaim = $totalXp >= (int) $r['milestone_xp'];
            } elseif ((int) $r['xp_cost'] > 0) {
                $canClaim = $totalXp >= (int) $r['xp_cost'];
            }
        }

        $result[] = [
            'id'          => (int) $r['id'],
            'title'       => $r['title'],
            'description' => $r['description'],
            'type'        => $r['reward_type'],
            'icon'        => $r['icon'],
            'xpCost'      => (int) $r['xp_cost'],
            'milestoneXp' => $r['milestone_xp'] !== null ? (int) $r['milestone_xp'] : null,
            'isClaimed'   => (bool) $r['is_claimed'],
            'claimedAt'   => $r['claimed_at'],
            'canClaim'    => $canClaim,
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'rewards' => $result,
            'totalXp' => $totalXp,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load rewards.']);
}
