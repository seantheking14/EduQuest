<?php
/**
 * GET /api/gamification/leaderboard.php
 * Returns the class leaderboard (if enabled by teacher).
 *
 * Query: ?courseId=X (optional - filter by course enrollment)
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

$courseId = isset($_GET['courseId']) ? (int) $_GET['courseId'] : null;

try {
    // Resolve student and teacher for settings
    $studentId = null;
    $teacherId = null;

    if ($user['role'] === 'student') {
        $stmt = $db->prepare('SELECT id, teacher_id FROM students WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $user['id']]);
        $studentRow = $stmt->fetch();
        if ($studentRow) {
            $studentId = (int) $studentRow['id'];
            $teacherId = $studentRow['teacher_id'] ? (int) $studentRow['teacher_id'] : null;
        }
    } elseif (in_array($user['role'], ['teacher', 'admin'], true)) {
        $teacherId = (int) $user['id'];
    }

    // Check if leaderboard is enabled
    $leaderboardMode = 'disabled';
    $topN = 5;

    if ($teacherId) {
        $stmt = $db->prepare('SELECT leaderboard_mode, leaderboard_top_n FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL LIMIT 1');
        $stmt->execute([':tid' => $teacherId]);
        $settings = $stmt->fetch();
        if ($settings) {
            $leaderboardMode = $settings['leaderboard_mode'];
            $topN = (int) $settings['leaderboard_top_n'];
        }

        // Apply per-student overrides if this is a student request
        if ($studentId) {
            try {
                $stmt = $db->prepare('SELECT setting_key, setting_value FROM student_settings_overrides WHERE student_id = :sid AND teacher_id = :tid');
                $stmt->execute([':sid' => $studentId, ':tid' => $teacherId]);
                $overrides = $stmt->fetchAll();
                foreach ($overrides as $ovr) {
                    if ($ovr['setting_key'] === 'leaderboard_mode')  $leaderboardMode = $ovr['setting_value'];
                    if ($ovr['setting_key'] === 'leaderboard_top_n') $topN = (int) $ovr['setting_value'];
                }
            } catch (Exception $e) {
                // Table may not exist yet; continue with global settings
            }
        }
    }

    if ($leaderboardMode === 'disabled' && $user['role'] === 'student') {
        echo json_encode([
            'success' => true,
            'data'    => [
                'enabled'  => false,
                'message'  => 'Leaderboard is not available for your class.',
                'entries'  => [],
                'teamStats' => [],
            ],
        ]);
        exit;
    }

    // Build leaderboard query
    $query = "
        SELECT
            sg.student_id,
            s.first_name,
            s.last_name,
            sg.total_xp,
            sg.current_level,
            sg.team,
            sg.egg_stage,
            sg.streak_days
        FROM student_gamification sg
        JOIN students s ON s.id = sg.student_id AND s.is_active = 1
    ";

    $params = [];

    if ($courseId) {
        $query .= " JOIN course_enrollments ce ON ce.student_id = s.id AND ce.course_id = :cid";
        $params[':cid'] = $courseId;
    } elseif ($teacherId && $user['role'] !== 'admin') {
        $query .= " AND s.teacher_id = :tid";
        $params[':tid'] = $teacherId;
    }

    $query .= " ORDER BY sg.total_xp DESC";

    if ($leaderboardMode === 'top_only') {
        $query .= " LIMIT :topn";
    }

    $stmt = $db->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    }
    if ($leaderboardMode === 'top_only') {
        $stmt->bindValue(':topn', $topN, PDO::PARAM_INT);
    }
    $stmt->execute();
    $entries = $stmt->fetchAll();

    // Assign ranks
    $ranked = [];
    $rank = 0;
    $prevXp = -1;
    foreach ($entries as $i => $entry) {
        if ((int) $entry['total_xp'] !== $prevXp) {
            $rank = $i + 1;
        }
        $prevXp = (int) $entry['total_xp'];

        $ranked[] = [
            'rank'       => $rank,
            'studentId'  => (int) $entry['student_id'],
            'firstName'  => $entry['first_name'],
            'lastName'   => $entry['last_name'],
            'totalXp'    => (int) $entry['total_xp'],
            'level'      => (int) $entry['current_level'],
            'team'       => $entry['team'],
            'eggStage'   => (int) $entry['egg_stage'],
            'streakDays' => (int) $entry['streak_days'],
            'isCurrentUser' => ($studentId !== null && (int) $entry['student_id'] === $studentId),
        ];
    }

    // In individual mode, only return the current student's entry
    if ($leaderboardMode === 'individual' && $studentId !== null && $user['role'] === 'student') {
        $ranked = array_values(array_filter($ranked, fn($e) => $e['isCurrentUser']));
    }

    // Team stats
    $stmt = $db->prepare("
        SELECT
            sg.team,
            COUNT(*) AS member_count,
            SUM(sg.total_xp) AS team_xp,
            ROUND(AVG(sg.total_xp)) AS avg_xp
        FROM student_gamification sg
        JOIN students s ON s.id = sg.student_id AND s.is_active = 1
        WHERE sg.team IS NOT NULL
        " . ($teacherId && $user['role'] !== 'admin' ? "AND s.teacher_id = :tid" : "") . "
        GROUP BY sg.team
        ORDER BY team_xp DESC
    ");
    if ($teacherId && $user['role'] !== 'admin') {
        $stmt->bindValue(':tid', $teacherId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $teamStats = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'enabled'   => true,
            'mode'      => $leaderboardMode,
            'entries'   => $ranked,
            'teamStats' => $teamStats,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load leaderboard.']);
}
