<?php
/**
 * POST /api/gamification/award-xp.php
 * Awards XP to a student. Can be called by teachers or internally.
 *
 * Body: { studentId, xpAmount, sourceType, sourceId?, description, courseId? }
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

$user = requireAuth();
$db   = getDBConnection();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
    exit;
}

$studentId   = (int) ($data['studentId'] ?? 0);
$xpAmount    = (int) ($data['xpAmount'] ?? 0);
$sourceType  = $data['sourceType'] ?? '';
$sourceId    = isset($data['sourceId']) ? (int) $data['sourceId'] : null;
$description = trim($data['description'] ?? '');
$courseId     = isset($data['courseId']) ? (int) $data['courseId'] : null;

$validSources = ['quest', 'quiz', 'activity', 'achievement', 'daily_challenge', 'streak_bonus', 'teacher_award', 'correction'];

if ($studentId <= 0 || $xpAmount === 0 || !in_array($sourceType, $validSources, true) || $description === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields: studentId, xpAmount, sourceType, description.']);
    exit;
}

// Only teachers/admins can award XP directly. Students can call this via internal endpoints.
$teacherId = null;
if ($sourceType === 'teacher_award' || $sourceType === 'correction') {
    if (!in_array($user['role'], ['teacher', 'admin'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only teachers can award XP directly.']);
        exit;
    }
    $teacherId = (int) $user['id'];
}

try {
    $db->beginTransaction();

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

    // ── Check daily XP cap ──
    $today = date('Y-m-d');
    $dailyXp = (int) $profile['daily_xp_earned'];
    if ($profile['daily_xp_date'] !== $today) {
        $dailyXp = 0;
    }

    // Get teacher settings for max daily XP
    $stmt = $db->prepare('SELECT teacher_id FROM students WHERE id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $studentData = $stmt->fetch();
    $sTeacherId = $studentData ? (int) $studentData['teacher_id'] : null;

    $maxDailyXp = 500; // default
    $xpMultiplier = 1.0;
    if ($sTeacherId) {
        $stmt = $db->prepare('SELECT max_daily_xp, xp_multiplier FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL LIMIT 1');
        $stmt->execute([':tid' => $sTeacherId]);
        $settings = $stmt->fetch();
        if ($settings) {
            $maxDailyXp = (int) $settings['max_daily_xp'];
            $xpMultiplier = (float) $settings['xp_multiplier'];
        }

        // Apply per-student overrides if they exist
        try {
            $stmt = $db->prepare('SELECT setting_key, setting_value FROM student_settings_overrides WHERE student_id = :sid AND teacher_id = :tid');
            $stmt->execute([':sid' => $studentId, ':tid' => $sTeacherId]);
            $overrides = $stmt->fetchAll();
            foreach ($overrides as $ovr) {
                if ($ovr['setting_key'] === 'max_daily_xp')  $maxDailyXp   = (int) $ovr['setting_value'];
                if ($ovr['setting_key'] === 'xp_multiplier') $xpMultiplier = (float) $ovr['setting_value'];
            }
        } catch (Exception $e) {
            // Table may not exist yet; continue with global settings
        }
    }

    // Apply multiplier
    $adjustedXp = (int) round($xpAmount * $xpMultiplier);

    // Enforce daily cap (teacher_awards and corrections bypass cap)
    if (!in_array($sourceType, ['teacher_award', 'correction'], true)) {
        $remaining = max(0, $maxDailyXp - $dailyXp);
        if ($adjustedXp > 0) {
            $adjustedXp = min($adjustedXp, $remaining);
        }
        if ($adjustedXp <= 0) {
            $db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Daily XP cap reached. Come back tomorrow!',
                'data' => ['dailyXpEarned' => $dailyXp, 'maxDailyXp' => $maxDailyXp],
            ]);
            exit;
        }
    }

    // ── Record XP transaction ──
    $stmt = $db->prepare("
        INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description, course_id, teacher_id)
        VALUES (:sid, :xp, :src, :srcid, :desc, :cid, :tid)
    ");
    $stmt->execute([
        ':sid'   => $studentId,
        ':xp'    => $adjustedXp,
        ':src'   => $sourceType,
        ':srcid' => $sourceId,
        ':desc'  => $description,
        ':cid'   => $courseId,
        ':tid'   => $teacherId,
    ]);

    // ── Update gamification profile ──
    $newTotalXp = max(0, (int) $profile['total_xp'] + $adjustedXp);
    $newDailyXp = $dailyXp + max(0, $adjustedXp);
    $newLevel   = calculateLevel($newTotalXp);
    $newEggStage = calculateEggStage($newLevel);

    $stmt = $db->prepare("
        UPDATE student_gamification
        SET total_xp = :xp, current_level = :lvl, egg_stage = :egg,
            daily_xp_earned = :daily, daily_xp_date = :ddate,
            last_activity_date = :today
        WHERE student_id = :sid
    ");
    $stmt->execute([
        ':xp'    => $newTotalXp,
        ':lvl'   => $newLevel,
        ':egg'   => $newEggStage,
        ':daily' => $newDailyXp,
        ':ddate' => $today,
        ':today' => $today,
        ':sid'   => $studentId,
    ]);

    // ── Check for new achievement unlocks (non-fatal) ──
    $newAchievements = [];
    try {
        $newAchievements = checkAchievements($db, $studentId, $newTotalXp, $newLevel, $newEggStage, (int) $profile['streak_days']);
    } catch (Exception $achErr) {
        // Don't let achievement checking crash the XP award
    }

    // ── Check for virtual reward milestones (non-fatal) ──
    $newRewards = [];
    try {
        $newRewards = checkMilestoneRewards($db, $studentId, $newTotalXp);
    } catch (Exception $rwdErr) {
        // Don't let reward checking crash the XP award
    }

    $db->commit();

    $nextLevelXp    = xpForLevel($newLevel + 1);
    $currentLevelXp = xpForLevel($newLevel);

    $msgPrefix = $adjustedXp >= 0 ? "+{$adjustedXp} XP earned!" : abs($adjustedXp) . " XP deducted.";

    echo json_encode([
        'success' => true,
        'message' => $msgPrefix,
        'data'    => [
            'xpAwarded'      => $adjustedXp,
            'totalXp'        => $newTotalXp,
            'level'          => $newLevel,
            'eggStage'       => $newEggStage,
            'xpProgress'     => $newTotalXp - $currentLevelXp,
            'xpNeeded'       => $nextLevelXp - $currentLevelXp,
            'dailyXpEarned'  => $newDailyXp,
            'maxDailyXp'     => $maxDailyXp,
            'leveledUp'      => $newLevel > (int) $profile['current_level'],
            'eggEvolved'     => $newEggStage > (int) $profile['egg_stage'],
            'newAchievements' => $newAchievements,
            'newRewards'     => $newRewards,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to award XP.']);
}

// ── Helper functions ──

function calculateLevel(int $xp): int {
    $level = 1;
    while (xpForLevel($level + 1) <= $xp) { $level++; }
    return $level;
}

function xpForLevel(int $level): int {
    if ($level <= 1) return 0;
    return (int) (50 * pow($level - 1, 2) + 50 * ($level - 1));
}

function calculateEggStage(int $level): int {
    if ($level >= 20) return 5;
    if ($level >= 12) return 4;
    if ($level >= 7)  return 3;
    if ($level >= 3)  return 2;
    return 1;
}

function checkAchievements(PDO $db, int $studentId, int $totalXp, int $level, int $eggStage, int $streakDays): array {
    $unlocked = [];

    // Get all active achievements
    $stmt = $db->prepare('SELECT * FROM achievements WHERE is_active = 1');
    $stmt->execute();
    $achievements = $stmt->fetchAll();

    foreach ($achievements as $ach) {
        // Check if already unlocked
        $stmt = $db->prepare('SELECT * FROM student_achievements WHERE student_id = :sid AND achievement_id = :aid');
        $stmt->execute([':sid' => $studentId, ':aid' => $ach['id']]);
        $existing = $stmt->fetch();

        if ($existing && $existing['is_unlocked']) continue;

        $currentValue = 0;
        $metric = $ach['target_metric'];

        switch ($metric) {
            case 'total_xp':
                $currentValue = $totalXp;
                break;
            case 'current_level':
                $currentValue = $level;
                break;
            case 'egg_stage':
                $currentValue = $eggStage;
                break;
            case 'streak_days':
                $currentValue = $streakDays;
                break;
            case 'quests_completed':
                $stmt2 = $db->prepare("SELECT COUNT(*) AS cnt FROM xp_transactions WHERE student_id = :sid AND source_type = 'quest'");
                $stmt2->execute([':sid' => $studentId]);
                $currentValue = (int) $stmt2->fetch()['cnt'];
                break;
            case 'perfect_scores':
                $stmt2 = $db->prepare("SELECT COUNT(*) AS cnt FROM student_activity_log WHERE student_id = :sid AND score = max_score AND score IS NOT NULL");
                $stmt2->execute([':sid' => $studentId]);
                $currentValue = (int) $stmt2->fetch()['cnt'];
                break;
            case 'daily_completed':
                $stmt2 = $db->prepare("SELECT COUNT(*) AS cnt FROM xp_transactions WHERE student_id = :sid AND source_type = 'daily_challenge'");
                $stmt2->execute([':sid' => $studentId]);
                $currentValue = (int) $stmt2->fetch()['cnt'];
                break;
            case 'team_joined':
                $stmt2 = $db->prepare('SELECT team FROM student_gamification WHERE student_id = :sid');
                $stmt2->execute([':sid' => $studentId]);
                $t = $stmt2->fetch();
                $currentValue = ($t && $t['team']) ? 1 : 0;
                break;
            case 'reading_completed':
                $stmt2 = $db->prepare("SELECT COUNT(*) AS cnt FROM student_activity_log WHERE student_id = :sid AND (activity_type = 'quest' OR activity_type = 'activity') AND LOWER(title) LIKE '%reading%'");
                $stmt2->execute([':sid' => $studentId]);
                $currentValue = (int) $stmt2->fetch()['cnt'];
                break;
            case 'first_login':
                // Notable Newcomer — awarded via onboarding, not here
                $currentValue = 0;
                break;
            default:
                continue 2;
        }

        $shouldUnlock = $currentValue >= (int) $ach['target_value'];

        if ($existing) {
            // Update progress
            $stmt = $db->prepare('UPDATE student_achievements SET progress = :prog, is_unlocked = :unlock, unlocked_at = IF(:unlock, NOW(), NULL) WHERE student_id = :sid AND achievement_id = :aid');
            $stmt->execute([':prog' => min($currentValue, (int) $ach['target_value']), ':unlock' => $shouldUnlock ? 1 : 0, ':sid' => $studentId, ':aid' => $ach['id']]);
        } else {
            $stmt = $db->prepare('INSERT INTO student_achievements (student_id, achievement_id, progress, is_unlocked, unlocked_at) VALUES (:sid, :aid, :prog, :unlock, IF(:unlock2, NOW(), NULL))');
            $stmt->execute([':sid' => $studentId, ':aid' => $ach['id'], ':prog' => min($currentValue, (int) $ach['target_value']), ':unlock' => $shouldUnlock ? 1 : 0, ':unlock2' => $shouldUnlock ? 1 : 0]);
        }

        if ($shouldUnlock && (!$existing || !$existing['is_unlocked'])) {
            // Award achievement XP
            if ((int) $ach['xp_reward'] > 0) {
                $stmt = $db->prepare("INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description) VALUES (:sid, :xp, 'achievement', :aid, :desc)");
                $stmt->execute([':sid' => $studentId, ':xp' => $ach['xp_reward'], ':aid' => $ach['id'], ':desc' => 'Achievement unlocked: ' . $ach['title']]);

                $stmt = $db->prepare('UPDATE student_gamification SET total_xp = total_xp + :xp WHERE student_id = :sid');
                $stmt->execute([':xp' => $ach['xp_reward'], ':sid' => $studentId]);
            }

            $unlocked[] = [
                'title'       => $ach['title'],
                'description' => $ach['description'],
                'icon'        => $ach['icon'],
                'xpReward'    => (int) $ach['xp_reward'],
                'badgeColor'  => $ach['badge_color'],
            ];
        }
    }

    return $unlocked;
}

function checkMilestoneRewards(PDO $db, int $studentId, int $totalXp): array {
    $newRewards = [];

    $stmt = $db->prepare('SELECT * FROM virtual_rewards WHERE is_active = 1 AND milestone_xp IS NOT NULL AND milestone_xp <= :xp');
    $stmt->execute([':xp' => $totalXp]);
    $rewards = $stmt->fetchAll();

    foreach ($rewards as $reward) {
        $stmt = $db->prepare('SELECT id FROM student_rewards WHERE student_id = :sid AND reward_id = :rid');
        $stmt->execute([':sid' => $studentId, ':rid' => $reward['id']]);
        if ($stmt->fetch()) continue;

        $stmt = $db->prepare('INSERT INTO student_rewards (student_id, reward_id) VALUES (:sid, :rid)');
        $stmt->execute([':sid' => $studentId, ':rid' => $reward['id']]);

        $newRewards[] = [
            'title'       => $reward['title'],
            'description' => $reward['description'],
            'icon'        => $reward['icon'],
            'type'        => $reward['reward_type'],
        ];
    }

    return $newRewards;
}
