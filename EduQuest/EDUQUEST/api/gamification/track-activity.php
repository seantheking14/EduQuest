<?php
/**
 * POST /api/gamification/track-activity.php
 * Called by student-facing pages when a student completes an activity.
 * Records the activity, awards XP, and returns any unlocks.
 *
 * Body: {
 *   activityType: "quest"|"quiz"|"activity"|"daily_challenge",
 *   activityId?:   int,
 *   courseId?:      int,
 *   title:         string,   // human-readable label
 *   score?:        float,    // 0-100 (percentage)
 *   maxScore?:     float,
 *   attempts?:     int,      // number of tries
 *   timeSpent?:    int,      // seconds spent on task
 *   responses?:    array     // optional answer data for future analytics
 * }
 *
 * XP formula:
 *   base = lookup by activityType
 *   + performance bonus (score ≥ 80 → +25 %, ≥ 90 → +50 %, 100 → +100 %)
 *   + first-attempt bonus (+15 %)
 *   − repeated-attempt penalty (attempt 3+ → −20 %)
 *   Multiplied by teacher xp_multiplier, capped by daily max.
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

// Only students can track their own activity
if ($user['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only students can track activities.']);
    exit;
}

$db   = getDBConnection();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
    exit;
}

$activityType = strtolower(trim((string) ($data['activityType'] ?? '')));
// Normalize legacy "game" events to quest so DB enum/source_type stay valid.
if ($activityType === 'game') {
    $activityType = 'quest';
}
$activityId   = isset($data['activityId']) ? (int) $data['activityId'] : null;
$courseId      = isset($data['courseId']) ? (int) $data['courseId'] : null;
$title         = trim($data['title'] ?? '');
$score         = isset($data['score']) ? (float) $data['score'] : null;
$maxScore      = isset($data['maxScore']) ? (float) $data['maxScore'] : null;
$attempts      = isset($data['attempts']) ? (int) $data['attempts'] : 1;
$timeSpent     = isset($data['timeSpent']) ? (int) $data['timeSpent'] : null;
$responses     = $data['responses'] ?? null;

$validTypes = ['quest', 'quiz', 'activity', 'daily_challenge'];
if (!in_array($activityType, $validTypes, true) || $title === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'activityType and title are required.']);
    exit;
}

// ── Resolve student_id from user ──
$stmt = $db->prepare('SELECT id, teacher_id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['id']]);
$student = $stmt->fetch();

if (!$student) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
    exit;
}

$studentId = (int) $student['id'];
$teacherId = (int) $student['teacher_id'];

// ── Load teacher settings for toggle enforcement ──
$teacherSettings = null;
if ($teacherId) {
    $stmt = $db->prepare('SELECT * FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL LIMIT 1');
    $stmt->execute([':tid' => $teacherId]);
    $teacherSettings = $stmt->fetch() ?: null;  // fetch() returns false when no row; coerce to null for ?array
}

try {
    $db->beginTransaction();

    // ── 1. Log the activity ──
    $stmt = $db->prepare("
        INSERT INTO student_activity_log
            (student_id, activity_type, activity_id, course_id, title, score, max_score, attempts, time_spent_seconds, responses)
        VALUES (:sid, :type, :aid, :cid, :title, :score, :max, :attempts, :time, :resp)
    ");
    $stmt->execute([
        ':sid'      => $studentId,
        ':type'     => $activityType,
        ':aid'      => $activityId,
        ':cid'      => $courseId,
        ':title'    => $title,
        ':score'    => $score,
        ':max'      => $maxScore,
        ':attempts' => $attempts,
        ':time'     => $timeSpent,
        ':resp'     => $responses ? json_encode($responses) : null,
    ]);
    $logId = (int) $db->lastInsertId();

    // ── 2. Calculate XP to award ──
    $baseXp = getBaseXp($activityType);
    $bonusMultiplier = 1.0;
    $bonusDetails = [];

    // Performance bonus
    $pct = null;
    if ($score !== null && $maxScore !== null && $maxScore > 0) {
        $pct = ($score / $maxScore) * 100;
    } elseif ($score !== null) {
        $pct = $score; // assume 0-100 scale
    }

    if ($pct !== null) {
        if ($pct >= 100) {
            $bonusMultiplier += 1.0;
            $bonusDetails[] = 'Perfect score! +100%';
        } elseif ($pct >= 90) {
            $bonusMultiplier += 0.5;
            $bonusDetails[] = 'Excellent performance! +50%';
        } elseif ($pct >= 80) {
            $bonusMultiplier += 0.25;
            $bonusDetails[] = 'Great job! +25%';
        }
    }

    // Attempt bonuses/penalties
    if ($attempts === 1) {
        $bonusMultiplier += 0.15;
        $bonusDetails[] = 'First try! +15%';
    } elseif ($attempts >= 3) {
        $bonusMultiplier -= 0.2;
        $bonusDetails[] = 'Multiple attempts −20%';
    }

    // Time bonus — quick completion (under half expected time)
    $expectedTime = getExpectedTime($activityType);
    if ($timeSpent !== null && $expectedTime > 0 && $timeSpent < ($expectedTime * 0.5) && $timeSpent > 30) {
        $bonusMultiplier += 0.1;
        $bonusDetails[] = 'Speed bonus! +10%';
    }

    $bonusMultiplier = max(0.1, $bonusMultiplier); // floor at 10 %
    $xpToAward = max(1, (int) round($baseXp * $bonusMultiplier));

    // ── 3. Build description ──
    $desc = "Completed {$activityType}: {$title}";
    if ($pct !== null) {
        $desc .= ' (' . round($pct) . '%)';
    }

    // ── 4. Call the internal award-xp logic ──
    // Instead of duplicating award-xp.php logic, we call it via an internal helper
    $result = internalAwardXp($db, $studentId, $teacherId, $xpToAward, $activityType, $activityId, $desc, $courseId, $teacherSettings);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'data'    => array_merge($result, [
            'activityLogId' => $logId,
            'baseXp'        => $baseXp,
            'bonusDetails'  => $bonusDetails,
            'bonusMultiplier' => round($bonusMultiplier, 2),
            'timeSpent'     => $timeSpent,
            'attempts'      => $attempts,
        ]),
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to track activity.']);
}

// ============================================================
// Helper functions
// ============================================================

function getBaseXp(string $type): int {
    $map = [
        'quest'           => 50,
        'quiz'            => 30,
        'activity'        => 20,
        'daily_challenge' => 25,
    ];
    return $map[$type] ?? 15;
}

function getExpectedTime(string $type): int {
    // Expected seconds per activity type
    $map = [
        'quest'           => 1200, // 20 min
        'quiz'            => 600,  // 10 min
        'activity'        => 900,  // 15 min
        'daily_challenge' => 300,  // 5 min
    ];
    return $map[$type] ?? 600;
}

/**
 * Internal XP award: mirrors award-xp.php logic without HTTP overhead.
 */
function internalAwardXp(PDO $db, int $studentId, int $teacherId, int $xpAmount, string $sourceType, ?int $sourceId, string $description, ?int $courseId, ?array $settings = null): array {
    // ── Enforce teacher toggles ──
    // Check if daily challenges are disabled
    if ($sourceType === 'daily_challenge' && $settings && !$settings['daily_challenges_enabled']) {
        return [
            'message'          => 'Daily challenges are currently disabled.',
            'xpAwarded'        => 0, 'totalXp' => 0, 'level' => 1, 'eggStage' => 1,
            'leveledUp'        => false, 'eggEvolved' => false,
            'newAchievements'  => [], 'newRewards' => [], 'cappedOut' => false,
            'streakDays'       => 0,
        ];
    }

    // Ensure profile
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

    // Daily cap check
    $today   = date('Y-m-d');
    $dailyXp = (int) $profile['daily_xp_earned'];
    if ($profile['daily_xp_date'] !== $today) $dailyXp = 0;

    $maxDailyXp   = 500;
    $xpMultiplier = 1.0;
    if ($teacherId) {
        $stmt = $db->prepare('SELECT max_daily_xp, xp_multiplier FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL LIMIT 1');
        $stmt->execute([':tid' => $teacherId]);
        $s = $stmt->fetch();
        if ($s) {
            $maxDailyXp   = (int) $s['max_daily_xp'];
            $xpMultiplier = (float) $s['xp_multiplier'];
        }

        // Apply per-student overrides if they exist
        try {
            $stmt = $db->prepare('SELECT setting_key, setting_value FROM student_settings_overrides WHERE student_id = :sid AND teacher_id = :tid');
            $stmt->execute([':sid' => $studentId, ':tid' => $teacherId]);
            $overrides = $stmt->fetchAll();
            foreach ($overrides as $ovr) {
                if ($ovr['setting_key'] === 'max_daily_xp')  $maxDailyXp   = (int) $ovr['setting_value'];
                if ($ovr['setting_key'] === 'xp_multiplier') $xpMultiplier = (float) $ovr['setting_value'];
            }
        } catch (Exception $e) {
            // Table may not exist yet; continue with global settings
        }
    }

    $adjustedXp = (int) round($xpAmount * $xpMultiplier);
    $remaining  = max(0, $maxDailyXp - $dailyXp);
    $adjustedXp = min($adjustedXp, $remaining);

    if ($adjustedXp <= 0) {
        return [
            'message'       => 'Daily XP cap reached. Come back tomorrow!',
            'xpAwarded'     => 0,
            'totalXp'       => (int) $profile['total_xp'],
            'level'         => (int) $profile['current_level'],
            'eggStage'      => (int) $profile['egg_stage'],
            'leveledUp'     => false,
            'eggEvolved'    => false,
            'newAchievements' => [],
            'newRewards'    => [],
            'cappedOut'     => true,
        ];
    }

    // Record transaction
    $stmt = $db->prepare("
        INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description, course_id)
        VALUES (:sid, :xp, :src, :srcid, :desc, :cid)
    ");
    $stmt->execute([
        ':sid'   => $studentId,
        ':xp'    => $adjustedXp,
        ':src'   => $sourceType,
        ':srcid' => $sourceId,
        ':desc'  => $description,
        ':cid'   => $courseId,
    ]);

    // Update profile
    $newTotalXp  = max(0, (int) $profile['total_xp'] + $adjustedXp);
    $newDailyXp  = $dailyXp + $adjustedXp;
    $newLevel    = calcLevel($newTotalXp);
    $newEggStage = calcEgg($newLevel);

    // Update streak
    $streakDays   = (int) $profile['streak_days'];
    $lastActivity = $profile['last_activity_date'];
    if ($lastActivity) {
        $daysDiff = (int) ((strtotime($today) - strtotime($lastActivity)) / 86400);
        if ($daysDiff === 1) {
            $streakDays++;
        } elseif ($daysDiff > 1) {
            $streakDays = 1;
        }
        // daysDiff === 0 → same day, keep streak
    } else {
        $streakDays = 1;
    }

    $stmt = $db->prepare("
        UPDATE student_gamification
        SET total_xp = :xp, current_level = :lvl, egg_stage = :egg,
            daily_xp_earned = :daily, daily_xp_date = :ddate,
            streak_days = :streak, longest_streak = GREATEST(longest_streak, :streak2),
            last_activity_date = :today
        WHERE student_id = :sid
    ");
    $stmt->execute([
        ':xp'      => $newTotalXp,
        ':lvl'     => $newLevel,
        ':egg'     => $newEggStage,
        ':daily'   => $newDailyXp,
        ':ddate'   => $today,
        ':streak'  => $streakDays,
        ':streak2' => $streakDays,
        ':today'   => $today,
        ':sid'     => $studentId,
    ]);

    // Check achievements (respects teacher toggle)
    $achievementsEnabled = true;
    if ($settings && isset($settings['achievements_enabled'])) {
        $achievementsEnabled = (bool) $settings['achievements_enabled'];
    }
    $newAchievements = $achievementsEnabled
        ? checkAchievementsInternal($db, $studentId, $newTotalXp, $newLevel, $newEggStage, $streakDays)
        : [];

    // Check milestone rewards
    $newRewards = checkMilestoneRewardsInternal($db, $studentId, $newTotalXp);

    return [
        'message'          => "+{$adjustedXp} XP earned!",
        'xpAwarded'        => $adjustedXp,
        'totalXp'          => $newTotalXp,
        'level'            => $newLevel,
        'eggStage'         => $newEggStage,
        'dailyXpEarned'    => $newDailyXp,
        'maxDailyXp'       => $maxDailyXp,
        'streakDays'       => $streakDays,
        'leveledUp'        => $newLevel > (int) $profile['current_level'],
        'eggEvolved'       => $newEggStage > (int) $profile['egg_stage'],
        'newAchievements'  => $newAchievements,
        'newRewards'       => $newRewards,
        'cappedOut'        => false,
    ];
}

function calcLevel(int $xp): int {
    $level = 1;
    while (xpForLvl($level + 1) <= $xp) $level++;
    return $level;
}
function xpForLvl(int $l): int {
    return $l <= 1 ? 0 : (int)(50 * pow($l - 1, 2) + 50 * ($l - 1));
}
function calcEgg(int $level): int {
    if ($level >= 20) return 5;
    if ($level >= 12) return 4;
    if ($level >= 7)  return 3;
    if ($level >= 3)  return 2;
    return 1;
}

function checkAchievementsInternal(PDO $db, int $studentId, int $totalXp, int $level, int $eggStage, int $streakDays): array {
    $unlocked = [];
    $stmt = $db->prepare('SELECT * FROM achievements WHERE is_active = 1');
    $stmt->execute();
    $achievements = $stmt->fetchAll();

    foreach ($achievements as $ach) {
        $stmt = $db->prepare('SELECT * FROM student_achievements WHERE student_id = :sid AND achievement_id = :aid');
        $stmt->execute([':sid' => $studentId, ':aid' => $ach['id']]);
        $existing = $stmt->fetch();
        if ($existing && $existing['is_unlocked']) continue;

        $currentValue = 0;
        switch ($ach['target_metric']) {
            case 'total_xp':        $currentValue = $totalXp; break;
            case 'current_level':   $currentValue = $level; break;
            case 'egg_stage':       $currentValue = $eggStage; break;
            case 'streak_days':     $currentValue = $streakDays; break;
            case 'quests_completed':
                $s = $db->prepare("SELECT COUNT(*) AS cnt FROM xp_transactions WHERE student_id = :sid AND source_type = 'quest'");
                $s->execute([':sid' => $studentId]);
                $currentValue = (int) $s->fetch()['cnt'];
                break;
            case 'perfect_scores':
                $s = $db->prepare("SELECT COUNT(*) AS cnt FROM student_activity_log WHERE student_id = :sid AND score = max_score AND score IS NOT NULL");
                $s->execute([':sid' => $studentId]);
                $currentValue = (int) $s->fetch()['cnt'];
                break;
            case 'daily_completed':
                $s = $db->prepare("SELECT COUNT(*) AS cnt FROM xp_transactions WHERE student_id = :sid AND source_type = 'daily_challenge'");
                $s->execute([':sid' => $studentId]);
                $currentValue = (int) $s->fetch()['cnt'];
                break;
            case 'team_joined':
                $s = $db->prepare('SELECT team FROM student_gamification WHERE student_id = :sid');
                $s->execute([':sid' => $studentId]);
                $t = $s->fetch();
                $currentValue = ($t && $t['team']) ? 1 : 0;
                break;
            case 'reading_completed':
                $s = $db->prepare("SELECT COUNT(*) AS cnt FROM student_activity_log WHERE student_id = :sid AND (activity_type = 'quest' OR activity_type = 'activity') AND LOWER(title) LIKE '%reading%'");
                $s->execute([':sid' => $studentId]);
                $currentValue = (int) $s->fetch()['cnt'];
                break;
            default: continue 2;
        }

        $shouldUnlock = $currentValue >= (int) $ach['target_value'];

        if ($existing) {
            $stmt = $db->prepare('UPDATE student_achievements SET progress = :prog, is_unlocked = :u, unlocked_at = IF(:u2, NOW(), NULL) WHERE student_id = :sid AND achievement_id = :aid');
            $stmt->execute([':prog' => min($currentValue, (int) $ach['target_value']), ':u' => $shouldUnlock ? 1 : 0, ':u2' => $shouldUnlock ? 1 : 0, ':sid' => $studentId, ':aid' => $ach['id']]);
        } else {
            $stmt = $db->prepare('INSERT INTO student_achievements (student_id, achievement_id, progress, is_unlocked, unlocked_at) VALUES (:sid, :aid, :prog, :u, IF(:u2, NOW(), NULL))');
            $stmt->execute([':sid' => $studentId, ':aid' => $ach['id'], ':prog' => min($currentValue, (int) $ach['target_value']), ':u' => $shouldUnlock ? 1 : 0, ':u2' => $shouldUnlock ? 1 : 0]);
        }

        if ($shouldUnlock && (!$existing || !$existing['is_unlocked'])) {
            if ((int) $ach['xp_reward'] > 0) {
                $stmt = $db->prepare("INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description) VALUES (:sid, :xp, 'achievement', :aid, :d)");
                $stmt->execute([':sid' => $studentId, ':xp' => $ach['xp_reward'], ':aid' => $ach['id'], ':d' => 'Achievement: ' . $ach['title']]);
                $stmt = $db->prepare('UPDATE student_gamification SET total_xp = total_xp + :xp WHERE student_id = :sid');
                $stmt->execute([':xp' => $ach['xp_reward'], ':sid' => $studentId]);
            }
            $unlocked[] = ['title' => $ach['title'], 'description' => $ach['description'], 'icon' => $ach['icon'], 'xpReward' => (int) $ach['xp_reward'], 'badgeColor' => $ach['badge_color']];
        }
    }
    return $unlocked;
}

function checkMilestoneRewardsInternal(PDO $db, int $studentId, int $totalXp): array {
    $new = [];
    $stmt = $db->prepare('SELECT * FROM virtual_rewards WHERE is_active = 1 AND milestone_xp IS NOT NULL AND milestone_xp <= :xp');
    $stmt->execute([':xp' => $totalXp]);
    foreach ($stmt->fetchAll() as $r) {
        $s = $db->prepare('SELECT id FROM student_rewards WHERE student_id = :sid AND reward_id = :rid');
        $s->execute([':sid' => $studentId, ':rid' => $r['id']]);
        if ($s->fetch()) continue;
        $s2 = $db->prepare('INSERT INTO student_rewards (student_id, reward_id) VALUES (:sid, :rid)');
        $s2->execute([':sid' => $studentId, ':rid' => $r['id']]);
        $new[] = ['title' => $r['title'], 'description' => $r['description'], 'icon' => $r['icon']];
    }
    return $new;
}
