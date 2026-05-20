<?php
/**
 * GET /api/gamification/achievements.php
 * Returns all achievements and student progress for the authenticated student.
 *
 * Query: ?category=all|academic|streak|social|milestone|special
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

$category = $_GET['category'] ?? 'all';
$validCategories = ['all', 'academic', 'streak', 'social', 'milestone', 'special'];
if (!in_array($category, $validCategories, true)) $category = 'all';

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

    // Keep achievements in sync even when XP came from endpoints that don't run unlock checks.
    refreshAchievementProgress($db, $studentId);

    // Get all active achievements with student progress
    // GROUP BY a.id guards against duplicate student_achievement rows
    $query = "
        SELECT
            a.id, a.title, a.description, a.icon, a.category, a.achievement_type,
            a.target_value, a.target_metric, a.xp_reward, a.badge_color,
            a.is_hidden, a.sort_order,
            COALESCE(MAX(sa.progress), 0)    AS progress,
            COALESCE(MAX(sa.is_unlocked), 0) AS is_unlocked,
            MAX(sa.unlocked_at)              AS unlocked_at
        FROM achievements a
        LEFT JOIN student_achievements sa ON sa.achievement_id = a.id AND sa.student_id = :sid
        WHERE a.is_active = 1
    ";
    $params = [':sid' => $studentId];

    if ($category !== 'all') {
        $query .= " AND a.category = :cat";
        $params[':cat'] = $category;
    }

    // Don't show hidden achievements that aren't unlocked
    $query .= " AND (a.is_hidden = 0 OR sa.is_unlocked = 1)";
    $query .= " GROUP BY a.id, a.title, a.description, a.icon, a.category, a.achievement_type,
                         a.target_value, a.target_metric, a.xp_reward, a.badge_color, a.is_hidden, a.sort_order";
    $query .= " ORDER BY MAX(sa.is_unlocked) DESC, a.sort_order ASC, a.id ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $achievements = $stmt->fetchAll();

    // Format output — use achievement id as key to prevent any remaining duplicates
    $result = [];
    $seen   = [];
    $unlockedCount = 0;

    foreach ($achievements as $ach) {
        $achId = (int) $ach['id'];
        if (isset($seen[$achId])) continue;   // skip any remaining duplicates
        $seen[$achId] = true;

        $isUnlocked = (bool) $ach['is_unlocked'];
        if ($isUnlocked) $unlockedCount++;

        $result[] = [
            'id'          => (int) $ach['id'],
            'title'       => $ach['title'],
            'description' => $ach['description'],
            'icon'        => $ach['icon'],
            'category'    => $ach['category'],
            'type'        => $ach['achievement_type'],
            'targetValue' => (int) $ach['target_value'],
            'progress'    => (int) $ach['progress'],
            'progressPct' => (int) $ach['target_value'] > 0
                ? min(100, round((int) $ach['progress'] / (int) $ach['target_value'] * 100))
                : 0,
            'xpReward'    => (int) $ach['xp_reward'],
            'badgeColor'  => $ach['badge_color'],
            'isUnlocked'  => $isUnlocked,
            'unlockedAt'  => $ach['unlocked_at'],
        ];
    }

    // Get total achievements including hidden
    $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM achievements WHERE is_active = 1');
    $stmt->execute();
    $totalAll = (int) $stmt->fetch()['cnt'];

    echo json_encode([
        'success' => true,
        'data'    => [
            'achievements' => $result,
            'unlocked'     => $unlockedCount,
            'total'        => $totalAll,
            'showing'      => count($result),
            'category'     => $category,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load achievements.']);
}

function refreshAchievementProgress(PDO $db, int $studentId): void {
    $stmt = $db->prepare('SELECT total_xp, current_level, egg_stage, streak_days, team FROM student_gamification WHERE student_id = :sid LIMIT 1');
    $stmt->execute([':sid' => $studentId]);
    $profile = $stmt->fetch();
    if (!$profile) {
        $stmt = $db->prepare('INSERT INTO student_gamification (student_id) VALUES (:sid)');
        $stmt->execute([':sid' => $studentId]);
        $profile = [
            'total_xp' => 0,
            'current_level' => 1,
            'egg_stage' => 1,
            'streak_days' => 0,
            'team' => null,
        ];
    }

    $stmt = $db->prepare('SELECT * FROM achievements WHERE is_active = 1');
    $stmt->execute();
    $definitions = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT * FROM student_achievements WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $existingRows = $stmt->fetchAll();
    $existingByAchievement = [];
    foreach ($existingRows as $row) {
        $existingByAchievement[(int) $row['achievement_id']] = $row;
    }

    foreach ($definitions as $ach) {
        $achievementId = (int) $ach['id'];
        $currentValue = calculateAchievementMetric($db, $studentId, $ach['target_metric'], $profile);
        $targetValue = max(1, (int) $ach['target_value']);
        $isUnlocked = $currentValue >= $targetValue ? 1 : 0;
        $progress = min($currentValue, $targetValue);

        $existing = $existingByAchievement[$achievementId] ?? null;
        if ($existing) {
            $wasUnlocked = (int) $existing['is_unlocked'] === 1;

            $stmt = $db->prepare('UPDATE student_achievements
                SET progress = :progress,
                    is_unlocked = :unlocked,
                    unlocked_at = CASE
                        WHEN :newUnlock = 1 THEN NOW()
                        WHEN is_unlocked = 1 THEN unlocked_at
                        ELSE NULL
                    END
                WHERE student_id = :sid AND achievement_id = :aid');
            $stmt->execute([
                ':progress' => $progress,
                ':unlocked' => $isUnlocked,
                ':newUnlock' => (!$wasUnlocked && $isUnlocked === 1) ? 1 : 0,
                ':sid' => $studentId,
                ':aid' => $achievementId,
            ]);

            if (!$wasUnlocked && $isUnlocked === 1) {
                applyAchievementXpReward($db, $studentId, $ach, $profile);
            }
            continue;
        }

        $stmt = $db->prepare('INSERT INTO student_achievements (student_id, achievement_id, progress, is_unlocked, unlocked_at)
                              VALUES (:sid, :aid, :progress, :unlocked, IF(:unlocked2 = 1, NOW(), NULL))');
        $stmt->execute([
            ':sid' => $studentId,
            ':aid' => $achievementId,
            ':progress' => $progress,
            ':unlocked' => $isUnlocked,
            ':unlocked2' => $isUnlocked,
        ]);

        if ($isUnlocked === 1) {
            applyAchievementXpReward($db, $studentId, $ach, $profile);
        }
    }
}

function calculateAchievementMetric(PDO $db, int $studentId, string $metric, array $profile): int {
    switch ($metric) {
        case 'total_xp':
            return (int) ($profile['total_xp'] ?? 0);
        case 'current_level':
            return (int) ($profile['current_level'] ?? 1);
        case 'egg_stage':
            return (int) ($profile['egg_stage'] ?? 1);
        case 'streak_days':
            return (int) ($profile['streak_days'] ?? 0);
        case 'team_joined':
            return !empty($profile['team']) ? 1 : 0;
        case 'first_login':
            return 1;
        case 'quests_completed':
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM xp_transactions WHERE student_id = :sid AND source_type IN ('quest', 'activity')");
            $stmt->execute([':sid' => $studentId]);
            return (int) ($stmt->fetch()['cnt'] ?? 0);
        case 'perfect_scores':
            $count = 0;
            $count += countQuerySafe($db, 'SELECT COUNT(*) AS cnt FROM student_activity_log WHERE student_id = :sid AND score IS NOT NULL AND max_score IS NOT NULL AND max_score > 0 AND score >= max_score', $studentId);
            $count += countQuerySafe($db, 'SELECT COUNT(*) AS cnt FROM student_quiz_attempts WHERE student_id = :sid AND percentage >= 100', $studentId);
            $count += countQuerySafe($db, 'SELECT COUNT(*) AS cnt FROM teacher_quiz_attempts WHERE student_id = :sid AND percentage >= 100', $studentId);
            return $count;
        case 'daily_completed':
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM xp_transactions WHERE student_id = :sid AND source_type = 'daily_challenge'");
            $stmt->execute([':sid' => $studentId]);
            return (int) ($stmt->fetch()['cnt'] ?? 0);
        case 'reading_completed':
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM student_activity_log WHERE student_id = :sid AND LOWER(title) LIKE '%reading%'");
            $stmt->execute([':sid' => $studentId]);
            $activityReads = (int) ($stmt->fetch()['cnt'] ?? 0);

            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM xp_transactions WHERE student_id = :sid AND source_type = 'activity' AND LOWER(description) LIKE '%reading%'");
            $stmt->execute([':sid' => $studentId]);
            $xpReads = (int) ($stmt->fetch()['cnt'] ?? 0);

            return max($activityReads, $xpReads);
        default:
            return 0;
    }
}

function countQuerySafe(PDO $db, string $sql, int $studentId): int {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([':sid' => $studentId]);
        return (int) ($stmt->fetch()['cnt'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

function applyAchievementXpReward(PDO $db, int $studentId, array $achievement, array &$profile): void {
    $xpReward = (int) ($achievement['xp_reward'] ?? 0);
    if ($xpReward <= 0) {
        return;
    }

    $stmt = $db->prepare("INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description)
                          VALUES (:sid, :xp, 'achievement', :aid, :desc)");
    $stmt->execute([
        ':sid' => $studentId,
        ':xp' => $xpReward,
        ':aid' => (int) $achievement['id'],
        ':desc' => 'Achievement unlocked: ' . ($achievement['title'] ?? 'Achievement'),
    ]);

    $profile['total_xp'] = (int) ($profile['total_xp'] ?? 0) + $xpReward;
    $profile['current_level'] = calculateLevelFromXp((int) $profile['total_xp']);
    $profile['egg_stage'] = calculateEggStageFromLevel((int) $profile['current_level']);

    $stmt = $db->prepare('UPDATE student_gamification
                          SET total_xp = :xp, current_level = :lvl, egg_stage = :egg
                          WHERE student_id = :sid');
    $stmt->execute([
        ':xp' => (int) $profile['total_xp'],
        ':lvl' => (int) $profile['current_level'],
        ':egg' => (int) $profile['egg_stage'],
        ':sid' => $studentId,
    ]);
}

function calculateLevelFromXp(int $xp): int {
    $level = 1;
    while (xpForLevelThreshold($level + 1) <= $xp) {
        $level++;
    }
    return $level;
}

function xpForLevelThreshold(int $level): int {
    if ($level <= 1) {
        return 0;
    }
    return (int) (50 * pow($level - 1, 2) + 50 * ($level - 1));
}

function calculateEggStageFromLevel(int $level): int {
    if ($level >= 20) return 5;
    if ($level >= 12) return 4;
    if ($level >= 7) return 3;
    if ($level >= 3) return 2;
    return 1;
}
