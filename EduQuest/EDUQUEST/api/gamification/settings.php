<?php
/**
 * GET/POST /api/gamification/settings.php
 * Teacher-controlled gamification settings.
 *
 * GET:  Returns current settings for the teacher (global or per-course)
 * POST: Updates settings
 *
 * Query: ?courseId=X (optional, for per-course settings)
 * POST Body: { achievementsEnabled, leaderboardMode, leaderboardTopN, ... }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher = requireTeacher();
$db      = getDBConnection();
$tid     = (int) $teacher['id'];
$courseId = isset($_GET['courseId']) ? (int) $_GET['courseId'] : null;

// ── GET: Return current settings ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->prepare(
            $courseId
                ? 'SELECT * FROM gamification_settings WHERE teacher_id = :tid AND course_id = :cid LIMIT 1'
                : 'SELECT * FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL LIMIT 1'
        );
        $params = [':tid' => $tid];
        if ($courseId) $params[':cid'] = $courseId;
        $stmt->execute($params);
        $row = $stmt->fetch();

        $defaults = [
            'xpMultiplier'           => 1.0,
            'difficultyLevel'        => 'moderate',
            'achievementsEnabled'    => true,
            'leaderboardMode'        => 'disabled',
            'leaderboardTopN'        => 5,
            'eggEvolutionEnabled'    => true,
            'teamsEnabled'           => true,
            'dailyChallengesEnabled' => true,
            'streaksEnabled'         => true,
            'maxDailyXp'             => 500,
            'notificationFrequency'  => 'important',
            'animationLevel'         => 'reduced',
            'quizTimerSeconds'       => 30,
            'gameTimerSeconds'       => 30,
            'showGameScore'          => true,
        ];

        if ($row) {
            $settings = [
                'xpMultiplier'           => (float) $row['xp_multiplier'],
                'difficultyLevel'        => $row['difficulty_level'],
                'achievementsEnabled'    => (bool) $row['achievements_enabled'],
                'leaderboardMode'        => $row['leaderboard_mode'],
                'leaderboardTopN'        => (int) $row['leaderboard_top_n'],
                'eggEvolutionEnabled'    => (bool) $row['egg_evolution_enabled'],
                'teamsEnabled'           => (bool) $row['teams_enabled'],
                'dailyChallengesEnabled' => (bool) $row['daily_challenges_enabled'],
                'streaksEnabled'         => (bool) $row['streaks_enabled'],
                'maxDailyXp'             => (int) $row['max_daily_xp'],
                'notificationFrequency'  => $row['notification_frequency'],
                'animationLevel'         => $row['animation_level'],
                'quizTimerSeconds'       => (int) $row['quiz_timer_seconds'],
                'gameTimerSeconds'       => (int) $row['game_timer_seconds'],
                'showGameScore'          => (bool) ($row['show_game_score'] ?? 1),
            ];
        } else {
            $settings = $defaults;
        }

        // Get student overview for this teacher
        $stmt = $db->prepare("
            SELECT
                COUNT(DISTINCT sg.student_id) AS active_students,
                ROUND(AVG(sg.total_xp)) AS avg_xp,
                MAX(sg.total_xp) AS max_xp,
                ROUND(AVG(sg.current_level), 1) AS avg_level,
                SUM(CASE WHEN sg.team = 'fire' THEN 1 ELSE 0 END) AS fire_count,
                SUM(CASE WHEN sg.team = 'water' THEN 1 ELSE 0 END) AS water_count,
                SUM(CASE WHEN sg.team = 'grass' THEN 1 ELSE 0 END) AS grass_count,
                SUM(CASE WHEN sg.team IS NULL THEN 1 ELSE 0 END) AS no_team_count
            FROM student_gamification sg
            JOIN students s ON s.id = sg.student_id AND s.is_active = 1
            WHERE s.teacher_id = :tid
        ");
        $stmt->execute([':tid' => $tid]);
        $overview = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'data'    => [
                'settings' => $settings,
                'overview' => [
                    'activeStudents' => (int) ($overview['active_students'] ?? 0),
                    'avgXp'          => (int) ($overview['avg_xp'] ?? 0),
                    'maxXp'          => (int) ($overview['max_xp'] ?? 0),
                    'avgLevel'       => (float) ($overview['avg_level'] ?? 0),
                    'teamCounts'     => [
                        'fire'  => (int) ($overview['fire_count'] ?? 0),
                        'water' => (int) ($overview['water_count'] ?? 0),
                        'grass' => (int) ($overview['grass_count'] ?? 0),
                        'none'  => (int) ($overview['no_team_count'] ?? 0),
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load settings.']);
    }
    exit;
}

// ── POST: Update settings ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
        exit;
    }

    // Validate inputs
    $validLeaderboardModes = ['enabled', 'top_only', 'individual', 'disabled'];
    $validDifficulties     = ['easy', 'moderate', 'challenging'];
    $validNotifFreqs       = ['all', 'important', 'minimal'];
    $validAnimLevels       = ['full', 'reduced', 'none'];

    $xpMultiplier       = max(0.1, min(3.0, (float) ($data['xpMultiplier'] ?? 1.0)));
    $difficultyLevel    = in_array($data['difficultyLevel'] ?? '', $validDifficulties, true)
                            ? $data['difficultyLevel'] : 'moderate';
    $achievementsEnabled = isset($data['achievementsEnabled']) ? (int) (bool) $data['achievementsEnabled'] : 1;
    $leaderboardMode    = in_array($data['leaderboardMode'] ?? '', $validLeaderboardModes, true)
                            ? $data['leaderboardMode'] : 'disabled';
    $leaderboardTopN    = max(1, min(20, (int) ($data['leaderboardTopN'] ?? 5)));
    $eggEvolutionEnabled = isset($data['eggEvolutionEnabled']) ? (int) (bool) $data['eggEvolutionEnabled'] : 1;
    $teamsEnabled       = isset($data['teamsEnabled']) ? (int) (bool) $data['teamsEnabled'] : 1;
    $dailyChallengesEnabled = isset($data['dailyChallengesEnabled']) ? (int) (bool) $data['dailyChallengesEnabled'] : 1;
    $streaksEnabled     = isset($data['streaksEnabled']) ? (int) (bool) $data['streaksEnabled'] : 1;
    $maxDailyXp         = max(50, min(5000, (int) ($data['maxDailyXp'] ?? 500)));
    $notifFreq          = in_array($data['notificationFrequency'] ?? '', $validNotifFreqs, true)
                            ? $data['notificationFrequency'] : 'important';
    $animLevel          = in_array($data['animationLevel'] ?? '', $validAnimLevels, true)
                            ? $data['animationLevel'] : 'reduced';
    $quizTimerSeconds   = max(0, min(120, (int) ($data['quizTimerSeconds'] ?? 30)));
    $gameTimerSeconds   = max(0, min(120, (int) ($data['gameTimerSeconds'] ?? 30)));
    $showGameScore      = isset($data['showGameScore']) ? (int) (bool) $data['showGameScore'] : 1;

    try {
        // Upsert settings
        $stmt = $db->prepare(
            $courseId
                ? 'SELECT id FROM gamification_settings WHERE teacher_id = :tid AND course_id = :cid'
                : 'SELECT id FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL'
        );
        $params = [':tid' => $tid];
        if ($courseId) $params[':cid'] = $courseId;
        $stmt->execute($params);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $db->prepare("
                UPDATE gamification_settings SET
                    xp_multiplier = :xpm, difficulty_level = :diff,
                    achievements_enabled = :ach, leaderboard_mode = :lbm,
                    leaderboard_top_n = :lbn, egg_evolution_enabled = :egg,
                    teams_enabled = :teams, daily_challenges_enabled = :daily,
                    streaks_enabled = :streaks, max_daily_xp = :maxdxp,
                    notification_frequency = :notif, animation_level = :anim,
                    quiz_timer_seconds = :qts, game_timer_seconds = :gts,
                    show_game_score = :sgs
                WHERE id = :id
            ");
            $stmt->execute([
                ':xpm'     => $xpMultiplier,
                ':diff'    => $difficultyLevel,
                ':ach'     => $achievementsEnabled,
                ':lbm'     => $leaderboardMode,
                ':lbn'     => $leaderboardTopN,
                ':egg'     => $eggEvolutionEnabled,
                ':teams'   => $teamsEnabled,
                ':daily'   => $dailyChallengesEnabled,
                ':streaks' => $streaksEnabled,
                ':maxdxp'  => $maxDailyXp,
                ':notif'   => $notifFreq,
                ':anim'    => $animLevel,
                ':qts'     => $quizTimerSeconds,
                ':gts'     => $gameTimerSeconds,
                ':sgs'     => $showGameScore,
                ':id'      => $existing['id'],
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO gamification_settings
                (teacher_id, course_id, xp_multiplier, difficulty_level,
                 achievements_enabled, leaderboard_mode, leaderboard_top_n,
                 egg_evolution_enabled, teams_enabled, daily_challenges_enabled,
                 streaks_enabled, max_daily_xp, notification_frequency, animation_level,
                 quiz_timer_seconds, game_timer_seconds, show_game_score)
                VALUES (:tid, :cid, :xpm, :diff, :ach, :lbm, :lbn, :egg, :teams, :daily, :streaks, :maxdxp, :notif, :anim, :qts, :gts, :sgs)
            ");
            $stmt->execute([
                ':tid'     => $tid,
                ':cid'     => $courseId,
                ':xpm'     => $xpMultiplier,
                ':diff'    => $difficultyLevel,
                ':ach'     => $achievementsEnabled,
                ':lbm'     => $leaderboardMode,
                ':lbn'     => $leaderboardTopN,
                ':egg'     => $eggEvolutionEnabled,
                ':teams'   => $teamsEnabled,
                ':daily'   => $dailyChallengesEnabled,
                ':streaks' => $streaksEnabled,
                ':maxdxp'  => $maxDailyXp,
                ':notif'   => $notifFreq,
                ':anim'    => $animLevel,
                ':qts'     => $quizTimerSeconds,
                ':gts'     => $gameTimerSeconds,
                ':sgs'     => $showGameScore,
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Gamification settings saved.',
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save settings.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
