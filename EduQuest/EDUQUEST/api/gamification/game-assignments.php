<?php
/**
 * GET  /api/gamification/game-assignments.php?student_id=X  – List games for a specific student
 * POST /api/gamification/game-assignments.php               – Toggle a game on/off for a student
 *
 * Teacher-only endpoint. Controls which extra games are visible per student.
 * Default games (9 basic ones) are always enabled and cannot be turned off.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher = requireTeacher();
$tid     = (int) $teacher['id'];
$db      = getDBConnection();

// ── Lightweight migration: ensure table has student_id column ──
try {
    // Check if table exists at all
    $check = $db->query("SHOW TABLES LIKE 'teacher_assigned_games'");
    if ($check->rowCount() === 0) {
        // Table doesn't exist — create fresh
        $db->exec("
            CREATE TABLE teacher_assigned_games (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                teacher_id      INT UNSIGNED    NOT NULL,
                student_id      INT UNSIGNED    NOT NULL,
                game_id         VARCHAR(100)    NOT NULL,
                is_enabled      TINYINT(1)      DEFAULT 1,
                created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_teacher_student_game (teacher_id, student_id, game_id),
                INDEX idx_tag_student (student_id),
                CONSTRAINT fk_tag_teacher FOREIGN KEY (teacher_id)
                    REFERENCES teachers(id) ON DELETE CASCADE,
                CONSTRAINT fk_tag_student FOREIGN KEY (student_id)
                    REFERENCES students(id) ON DELETE CASCADE
            )
        ");
    } else {
        // Table exists — check if student_id column is present
        $cols = $db->query("SHOW COLUMNS FROM teacher_assigned_games LIKE 'student_id'");
        if ($cols->rowCount() === 0) {
            // Old table without student_id — drop and recreate
            $db->exec("DROP TABLE teacher_assigned_games");
            $db->exec("
                CREATE TABLE teacher_assigned_games (
                    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    teacher_id      INT UNSIGNED    NOT NULL,
                    student_id      INT UNSIGNED    NOT NULL,
                    game_id         VARCHAR(100)    NOT NULL,
                    is_enabled      TINYINT(1)      DEFAULT 1,
                    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_teacher_student_game (teacher_id, student_id, game_id),
                    INDEX idx_tag_student (student_id),
                    CONSTRAINT fk_tag_teacher FOREIGN KEY (teacher_id)
                        REFERENCES teachers(id) ON DELETE CASCADE,
                    CONSTRAINT fk_tag_student FOREIGN KEY (student_id)
                        REFERENCES students(id) ON DELETE CASCADE
                )
            ");
        }
    }
} catch (Exception $e) {
    // Migration failed — log but continue (table might still work)
    error_log('Game assignments migration: ' . $e->getMessage());
}

// The 9 default game IDs that are always available
$defaultGameIds = [
    'math-sort-asc',
    'math-compare',
    'math-ordinal',
    'eng-build-cvc',
    'eng-read-cvc',
    'eng-sentences',
    'sc-living',
    'sc-food',
    'sc-eating-habits',
];

// All extra game IDs (non-default) — must match JS BANK ids
$extraGameIds = [
    'math-sort-desc',
    'math-coins',
    'math-numwords',
    'sc-weather',
    'sc-weather-clothes',
    'sc-animals',
    'sc-rawfood',
];

// ── GET: Return assignment state for a specific student ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    if (!$studentId) {
        jsonResponse(false, 'student_id query parameter is required.', [], 422);
    }

    // Verify this student belongs to this teacher
    $stmt = $db->prepare("SELECT id FROM students WHERE id = :sid AND teacher_id = :tid AND is_active = 1 LIMIT 1");
    $stmt->execute([':sid' => $studentId, ':tid' => $tid]);
    if (!$stmt->fetch()) {
        jsonResponse(false, 'Student not found or does not belong to you.', [], 404);
    }

    try {
        $enabledMap = [];
        try {
            $stmt = $db->prepare("SELECT game_id, is_enabled FROM teacher_assigned_games WHERE teacher_id = :tid AND student_id = :sid");
            $stmt->execute([':tid' => $tid, ':sid' => $studentId]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $enabledMap[$row['game_id']] = (bool) $row['is_enabled'];
            }
        } catch (Exception $e) {
            // Table may not exist yet — treat all extras as disabled
        }

        // Build result: defaults are always enabled, extras check DB
        $games = [];
        foreach ($defaultGameIds as $gid) {
            $games[] = ['game_id' => $gid, 'is_default' => true, 'is_enabled' => true];
        }
        foreach ($extraGameIds as $gid) {
            $games[] = [
                'game_id'    => $gid,
                'is_default' => false,
                'is_enabled' => isset($enabledMap[$gid]) ? $enabledMap[$gid] : false,
            ];
        }

        jsonResponse(true, 'Game assignments loaded.', ['games' => $games, 'defaultGameIds' => $defaultGameIds, 'student_id' => $studentId]);
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to load game assignments.', [], 500);
    }
}

// ── POST: Toggle a game on/off for a specific student ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(false, 'Invalid JSON payload.', [], 400);

        $gameId    = isset($input['game_id']) ? trim($input['game_id']) : '';
        $studentId = isset($input['student_id']) ? (int) $input['student_id'] : 0;
        $enabled   = !empty($input['is_enabled']);

        if (!$gameId) {
            jsonResponse(false, 'game_id is required.', [], 422);
        }
        if (!$studentId) {
            jsonResponse(false, 'student_id is required.', [], 422);
        }

        // Verify this student belongs to this teacher
        $stmt = $db->prepare("SELECT id FROM students WHERE id = :sid AND teacher_id = :tid AND is_active = 1 LIMIT 1");
        $stmt->execute([':sid' => $studentId, ':tid' => $tid]);
        if (!$stmt->fetch()) {
            jsonResponse(false, 'Student not found or does not belong to you.', [], 404);
        }

        // Cannot toggle default games
        if (in_array($gameId, $defaultGameIds, true)) {
            jsonResponse(false, 'Default games cannot be toggled. They are always enabled.', [], 422);
        }

        // Validate it's a known extra game
        if (!in_array($gameId, $extraGameIds, true)) {
            jsonResponse(false, 'Unknown game ID.', [], 422);
        }

        // Upsert per-student
        $stmt = $db->prepare("
            INSERT INTO teacher_assigned_games (teacher_id, student_id, game_id, is_enabled)
            VALUES (:tid, :sid, :gid, :en)
            ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            ':tid' => $tid,
            ':sid' => $studentId,
            ':gid' => $gameId,
            ':en'  => $enabled ? 1 : 0,
        ]);

        jsonResponse(true, 'Game assignment updated.', [
            'game_id'    => $gameId,
            'student_id' => $studentId,
            'is_enabled' => $enabled,
        ]);
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to update game assignment.', [], 500);
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
