<?php
/**
 * GET /api/gamification/student-games.php
 * Returns the list of game IDs that the authenticated student is allowed to play.
 * Combines the 9 default games + any extras their teacher assigned specifically to them.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user   = requireStudent();
$userId = (int) $user['id'];
$db     = getDBConnection();

try {
    // Resolve student's id and teacher_id
    $stmt = $db->prepare("SELECT id, teacher_id FROM students WHERE user_id = :uid AND is_active = 1 LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(false, 'Student profile not found.', [], 404);
    }

    $studentId = (int) $row['id'];
    $teacherId = (int) $row['teacher_id'];

    // Default predetermined games (teacher can toggle these on/off)
    $defaultGameIds = [
        'math-sort-asc',
        'math-compare',
        'math-ordinal',
        'math-truefalse',
        'math-pairs',
        'eng-build-cvc',
        'eng-read-cvc',
        'eng-sentences',
        'eng-truefalse',
        'eng-pairs',
        'sc-living',
        'sc-food',
        'sc-eating-habits',
        'sc-truefalse',
        'sc-pairs',
    ];

    // Apply teacher-level default game toggles when available
    $resolvedDefaultGameIds = $defaultGameIds;
    try {
        $db->exec('
            CREATE TABLE IF NOT EXISTS teacher_default_game_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT UNSIGNED NOT NULL,
                game_id VARCHAR(100) NOT NULL,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_teacher_game (teacher_id, game_id),
                KEY idx_teacher_id (teacher_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $stmt = $db->prepare('SELECT game_id, is_enabled FROM teacher_default_game_settings WHERE teacher_id = :tid');
        $stmt->execute([':tid' => $teacherId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settingsMap = [];
        foreach ($rows as $row) {
            $settingsMap[$row['game_id']] = ((int) $row['is_enabled']) === 1;
        }

        $resolvedDefaultGameIds = array_values(array_filter($defaultGameIds, function($gid) use ($settingsMap) {
            return !array_key_exists($gid, $settingsMap) || $settingsMap[$gid] === true;
        }));
    } catch (Exception $e) {
        // If settings table is unavailable, keep defaults enabled.
    }

    // Fetch teacher-enabled extra games for THIS specific student
    $extras = [];
    try {
        $stmt = $db->prepare("SELECT game_id FROM teacher_assigned_games WHERE teacher_id = :tid AND student_id = :sid AND is_enabled = 1");
        $stmt->execute([':tid' => $teacherId, ':sid' => $studentId]);
        $extras = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // Table may not exist yet — just use defaults
    }

    $enabledGameIds = array_values(array_unique(array_merge($resolvedDefaultGameIds, $extras)));

    jsonResponse(true, 'Student games loaded.', [
        'enabled_game_ids' => $enabledGameIds,
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load games.', [], 500);
}
