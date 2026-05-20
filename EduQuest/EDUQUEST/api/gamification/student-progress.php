<?php
/**
 * GET /api/gamification/student-progress.php?studentId=X
 * Returns gamification progress for a specific student.
 * Accessible by teachers/admins only — used on the teacher student-view page.
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

if (!in_array($user['role'], ['teacher', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Teachers only.']);
    exit;
}

$studentId = isset($_GET['studentId']) ? (int) $_GET['studentId'] : 0;
if ($studentId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'studentId is required.']);
    exit;
}

$db = getDBConnection();

try {
    // Verify teacher owns this student
    $stmt = $db->prepare('SELECT id FROM students WHERE id = :sid AND teacher_id = :tid');
    $stmt->execute([':sid' => $studentId, ':tid' => $user['id']]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    // Get gamification profile
    $stmt = $db->prepare('SELECT * FROM student_gamification WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        echo json_encode(['success' => true, 'data' => null]);
        exit;
    }

    // Get achievements with progress
    $stmt = $db->prepare("
        SELECT a.title, a.description, a.icon, a.badge_color, a.target_value,
               COALESCE(sa.progress, 0) AS progress, COALESCE(sa.is_unlocked, 0) AS is_unlocked,
               sa.unlocked_at
        FROM achievements a
        LEFT JOIN student_achievements sa ON sa.achievement_id = a.id AND sa.student_id = :sid
        WHERE a.is_active = 1
        ORDER BY sa.is_unlocked DESC, a.sort_order ASC
    ");
    $stmt->execute([':sid' => $studentId]);
    $achievements = $stmt->fetchAll();

    $unlocked = array_filter($achievements, fn($a) => $a['is_unlocked']);
    $totalAch = count($achievements);

    // Get recent activity log
    $stmt = $db->prepare("
        SELECT title, activity_type, score, max_score, attempts, time_spent_seconds, completed_at
        FROM student_activity_log
        WHERE student_id = :sid
        ORDER BY completed_at DESC
        LIMIT 10
    ");
    $stmt->execute([':sid' => $studentId]);
    $activityLog = $stmt->fetchAll();

    // Get recent XP transactions
    $stmt = $db->prepare("
        SELECT xp_amount, source_type, description, created_at
        FROM xp_transactions
        WHERE student_id = :sid
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([':sid' => $studentId]);
    $xpLog = $stmt->fetchAll();

    // Merge activity logs and XP transactions, preferring activity log
    $recentActivity = [];
    foreach ($activityLog as $a) {
        $recentActivity[] = [
            'title'         => $a['title'],
            'activity_type' => $a['activity_type'],
            'score'         => $a['score'],
            'max_score'     => $a['max_score'],
            'xp_amount'     => 0,
            'completed_at'  => $a['completed_at'],
        ];
    }
    // Fill in XP amounts from transactions
    foreach ($xpLog as $x) {
        $recentActivity[] = [
            'title'         => $x['description'],
            'source_type'   => $x['source_type'],
            'score'         => null,
            'max_score'     => null,
            'xp_amount'     => (int) $x['xp_amount'],
            'created_at'    => $x['created_at'],
        ];
    }

    // Sort by date descending, limit 10
    usort($recentActivity, function ($a, $b) {
        $da = $a['completed_at'] ?? $a['created_at'] ?? '';
        $db2 = $b['completed_at'] ?? $b['created_at'] ?? '';
        return strcmp($db2, $da);
    });
    $recentActivity = array_slice($recentActivity, 0, 10);

    echo json_encode([
        'success' => true,
        'data'    => [
            'totalXp'             => (int) $profile['total_xp'],
            'level'               => (int) $profile['current_level'],
            'team'                => $profile['team'],
            'eggStage'            => (int) $profile['egg_stage'],
            'streakDays'          => (int) $profile['streak_days'],
            'longestStreak'       => (int) $profile['longest_streak'],
            'dailyXpEarned'       => (int) $profile['daily_xp_earned'],
            'lastActivityDate'    => $profile['last_activity_date'],
            'achievementsUnlocked' => count($unlocked),
            'achievementsTotal'   => $totalAch,
            'achievements'        => $achievements,
            'recentActivity'      => $recentActivity,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load student gamification data.']);
}
