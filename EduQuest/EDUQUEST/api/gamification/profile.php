<?php
/**
 * GET /api/gamification/profile.php
 * Returns the gamification profile for the authenticated student.
 * Includes XP, level, team, egg stage, streak, achievements, and settings.
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
    // Resolve student_id from users.id
    $stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $user['role'] === 'student' ? $user['id'] : 0]);
    $studentRow = $stmt->fetch();

    if (!$studentRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
        exit;
    }

    $studentId = (int) $studentRow['id'];

    // ── Ensure gamification profile exists ──
    $stmt = $db->prepare('SELECT * FROM student_gamification WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        $stmt = $db->prepare('INSERT INTO student_gamification (student_id) VALUES (:sid)');
        $stmt->execute([':sid' => $studentId]);
        $stmt = $db->prepare('SELECT * FROM student_gamification WHERE student_id = :sid');
        $stmt->execute([':sid' => $studentId]);
        $profile = $stmt->fetch();
    }

    // ── Update streak ──
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($profile['last_activity_date'] === $yesterday && $profile['last_activity_date'] !== $today) {
        // Continue streak
        $newStreak = (int) $profile['streak_days'] + 1;
        $longestStreak = max((int) $profile['longest_streak'], $newStreak);
        $stmt = $db->prepare('UPDATE student_gamification SET streak_days = :streak, longest_streak = :longest, last_activity_date = :today WHERE student_id = :sid');
        $stmt->execute([':streak' => $newStreak, ':longest' => $longestStreak, ':today' => $today, ':sid' => $studentId]);
        $profile['streak_days'] = $newStreak;
        $profile['longest_streak'] = $longestStreak;
    } elseif ($profile['last_activity_date'] !== $today && $profile['last_activity_date'] !== $yesterday) {
        // Reset streak
        $stmt = $db->prepare('UPDATE student_gamification SET streak_days = 1, last_activity_date = :today WHERE student_id = :sid');
        $stmt->execute([':today' => $today, ':sid' => $studentId]);
        $profile['streak_days'] = 1;
    } elseif ($profile['last_activity_date'] === null) {
        $stmt = $db->prepare('UPDATE student_gamification SET streak_days = 1, last_activity_date = :today WHERE student_id = :sid');
        $stmt->execute([':today' => $today, ':sid' => $studentId]);
        $profile['streak_days'] = 1;
    }

    // Reset daily XP if new day
    if ($profile['daily_xp_date'] !== $today) {
        $stmt = $db->prepare('UPDATE student_gamification SET daily_xp_earned = 0, daily_xp_date = :today WHERE student_id = :sid');
        $stmt->execute([':today' => $today, ':sid' => $studentId]);
        $profile['daily_xp_earned'] = 0;
    }

    // ── Calculate level from XP ──
    $totalXp = (int) $profile['total_xp'];
    $level = calculateLevel($totalXp);
    $nextLevelXp = xpForLevel($level + 1);
    $currentLevelXp = xpForLevel($level);
    $xpProgress = $totalXp - $currentLevelXp;
    $xpNeeded = $nextLevelXp - $currentLevelXp;

    // Update level if changed
    if ($level !== (int) $profile['current_level']) {
        $stmt = $db->prepare('UPDATE student_gamification SET current_level = :lvl WHERE student_id = :sid');
        $stmt->execute([':lvl' => $level, ':sid' => $studentId]);
    }

    // ── Calculate egg stage from level ──
    $eggStage = calculateEggStage($level);
    if ($eggStage !== (int) $profile['egg_stage']) {
        $stmt = $db->prepare('UPDATE student_gamification SET egg_stage = :stage WHERE student_id = :sid');
        $stmt->execute([':stage' => $eggStage, ':sid' => $studentId]);
    }

    // ── Get recent achievements ──
    $stmt = $db->prepare("
        SELECT a.title, a.description, a.icon, a.category, a.badge_color,
               sa.is_unlocked, sa.progress, sa.unlocked_at, a.target_value
        FROM student_achievements sa
        JOIN achievements a ON a.id = sa.achievement_id
        WHERE sa.student_id = :sid
        ORDER BY sa.unlocked_at DESC, sa.updated_at DESC
        LIMIT 6
    ");
    $stmt->execute([':sid' => $studentId]);
    $recentAchievements = $stmt->fetchAll();

    // ── Get achievement counts ──
    $stmt = $db->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(is_unlocked) AS unlocked
        FROM student_achievements
        WHERE student_id = :sid
    ");
    $stmt->execute([':sid' => $studentId]);
    $achCounts = $stmt->fetch();

    // ── Total available achievements ──
    $stmt = $db->prepare("SELECT COUNT(*) AS total FROM achievements WHERE is_active = 1");
    $stmt->execute();
    $totalAch = $stmt->fetch();

    // ── Recent XP transactions ──
    $stmt = $db->prepare("
        SELECT xp_amount, source_type, description, created_at
        FROM xp_transactions
        WHERE student_id = :sid
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([':sid' => $studentId]);
    $recentXp = $stmt->fetchAll();

    // ── Get gamification settings (teacher/course defaults) ──
    // Find the student's teacher
    $stmt = $db->prepare('SELECT teacher_id FROM students WHERE id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $studentData = $stmt->fetch();
    $teacherId = $studentData ? (int) $studentData['teacher_id'] : null;

    $settings = getGamificationSettings($db, $teacherId, $studentId);

    // ── Get claimed rewards count ──
    $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM student_rewards WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $rewardCount = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'data'    => [
            'profile' => [
                'studentId'      => $studentId,
                'totalXp'        => $totalXp,
                'level'          => $level,
                'team'           => $profile['team'],
                'petName'        => $profile['pet_name'] ?? null,
                'eggStage'       => $eggStage,
                'streakDays'     => (int) $profile['streak_days'],
                'longestStreak'  => (int) $profile['longest_streak'],
                'dailyXpEarned'  => (int) $profile['daily_xp_earned'],
                'xpProgress'     => $xpProgress,
                'xpNeeded'       => $xpNeeded,
                'nextLevelXp'    => $nextLevelXp,
                'currentLevelXp' => $currentLevelXp,
            ],
            'eggEvolution' => [
                'stage'       => $eggStage,
                'stageName'   => getEggStageName($eggStage),
                'nextStage'   => $eggStage < 5 ? getEggStageName($eggStage + 1) : null,
                'levelNeeded' => getLevelForEggStage($eggStage + 1),
            ],
            'achievements' => [
                'unlocked' => (int) ($achCounts['unlocked'] ?? 0),
                'total'    => (int) ($totalAch['total'] ?? 0),
                'recent'   => $recentAchievements,
            ],
            'recentXp'     => $recentXp,
            'rewardsCount' => (int) ($rewardCount['cnt'] ?? 0),
            'settings'     => $settings,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load gamification profile.']);
}

// ── Helper functions ──

function calculateLevel(int $xp): int {
    $level = 1;
    while (xpForLevel($level + 1) <= $xp) {
        $level++;
    }
    return $level;
}

function xpForLevel(int $level): int {
    // Quadratic curve: each level needs more XP
    // Level 1: 0, Level 2: 100, Level 3: 250, Level 4: 450, ...
    if ($level <= 1) return 0;
    return (int) (50 * pow($level - 1, 2) + 50 * ($level - 1));
}

function calculateEggStage(int $level): int {
    if ($level >= 20) return 5; // Dragon
    if ($level >= 12) return 4; // Creature
    if ($level >= 7)  return 3; // Hatchling
    if ($level >= 3)  return 2; // Cracking
    return 1;                   // Egg
}

function getEggStageName(int $stage): string {
    $names = [1 => 'Egg', 2 => 'Cracking Egg', 3 => 'Hatchling', 4 => 'Young Creature', 5 => 'Mythical Guardian'];
    return $names[$stage] ?? 'Unknown';
}

function getLevelForEggStage(int $stage): ?int {
    $levels = [2 => 3, 3 => 7, 4 => 12, 5 => 20];
    return $levels[$stage] ?? null;
}

function getGamificationSettings(PDO $db, ?int $teacherId, ?int $studentId = null): array {
    $defaults = [
        'achievementsEnabled'     => true,
        'leaderboardMode'         => 'disabled',
        'leaderboardTopN'         => 5,
        'eggEvolutionEnabled'     => true,
        'teamsEnabled'            => true,
        'dailyChallengesEnabled'  => true,
        'streaksEnabled'          => true,
        'maxDailyXp'              => 500,
        'notificationFrequency'   => 'important',
        'animationLevel'          => 'reduced',
        'xpMultiplier'            => 1.0,
        'difficultyLevel'         => 'moderate',
        'quizTimerSeconds'        => 30,
        'gameTimerSeconds'        => 30,
        'showGameScore'           => true,
    ];

    if (!$teacherId) return $defaults;

    $stmt = $db->prepare('SELECT * FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL LIMIT 1');
    $stmt->execute([':tid' => $teacherId]);
    $row = $stmt->fetch();

    if (!$row) {
        $settings = $defaults;
    } else {
        $settings = [
            'achievementsEnabled'     => (bool) $row['achievements_enabled'],
            'leaderboardMode'         => $row['leaderboard_mode'],
            'leaderboardTopN'         => (int) $row['leaderboard_top_n'],
            'eggEvolutionEnabled'     => (bool) $row['egg_evolution_enabled'],
            'teamsEnabled'            => (bool) $row['teams_enabled'],
            'dailyChallengesEnabled'  => (bool) $row['daily_challenges_enabled'],
            'streaksEnabled'          => (bool) $row['streaks_enabled'],
            'maxDailyXp'              => (int) $row['max_daily_xp'],
            'notificationFrequency'   => $row['notification_frequency'],
            'animationLevel'          => $row['animation_level'],
            'xpMultiplier'            => (float) $row['xp_multiplier'],
            'difficultyLevel'         => $row['difficulty_level'],
            'quizTimerSeconds'        => (int) $row['quiz_timer_seconds'],
            'gameTimerSeconds'        => (int) $row['game_timer_seconds'],
            'showGameScore'           => (bool) ($row['show_game_score'] ?? 1),
        ];
    }

    // ── Merge per-student overrides ──
    if ($studentId && $teacherId) {
        try {
            $stmt = $db->prepare('SELECT setting_key, setting_value FROM student_settings_overrides WHERE student_id = :sid AND teacher_id = :tid');
            $stmt->execute([':sid' => $studentId, ':tid' => $teacherId]);
            $overrides = $stmt->fetchAll();

            // Map DB keys to settings array keys
            $keyMap = [
                'leaderboard_mode'       => 'leaderboardMode',
                'leaderboard_top_n'      => 'leaderboardTopN',
                'xp_multiplier'          => 'xpMultiplier',
                'max_daily_xp'           => 'maxDailyXp',
                'difficulty_level'       => 'difficultyLevel',
                'animation_level'        => 'animationLevel',
                'notification_frequency' => 'notificationFrequency',
                'quiz_timer_seconds'     => 'quizTimerSeconds',
                'game_timer_seconds'     => 'gameTimerSeconds',
                'show_game_score'        => 'showGameScore',
            ];

            foreach ($overrides as $ovr) {
                $dbKey = $ovr['setting_key'];
                if (!isset($keyMap[$dbKey])) continue;
                $settKey = $keyMap[$dbKey];

                // Cast to the correct type based on the setting
                switch ($settKey) {
                    case 'leaderboardTopN':
                    case 'maxDailyXp':
                    case 'quizTimerSeconds':
                    case 'gameTimerSeconds':
                        $settings[$settKey] = (int) $ovr['setting_value'];
                        break;
                    case 'showGameScore':
                        $settings[$settKey] = (bool)(int) $ovr['setting_value'];
                        break;
                    case 'xpMultiplier':
                        $settings[$settKey] = (float) $ovr['setting_value'];
                        break;
                    default:
                        $settings[$settKey] = $ovr['setting_value'];
                        break;
                }
            }
        } catch (Exception $e) {
            // Table may not exist yet; continue with global settings
        }
    }

    return $settings;
}
