<?php
/**
 * GET/POST /api/gamification/student-settings-override.php
 * Per-student setting overrides. Teacher-only.
 *
 * GET:  ?student_id=X → returns all overrides for that student
 * POST: { student_id, overrides: { setting_key: value, ... } }
 *       Replaces ALL overrides for that student with the provided set.
 *       Omitted keys are removed (reverted to global).
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

// ── Ensure table exists (smart migration) ──
try {
    $check = $db->query("SHOW TABLES LIKE 'student_settings_overrides'");
    if ($check->rowCount() === 0) {
        $db->exec("
            CREATE TABLE student_settings_overrides (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id    INT UNSIGNED    NOT NULL,
                teacher_id    INT UNSIGNED    NOT NULL,
                setting_key   VARCHAR(50)     NOT NULL,
                setting_value VARCHAR(100)    NOT NULL,
                created_at    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                updated_at    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_student_setting (student_id, setting_key),
                INDEX idx_teacher (teacher_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
} catch (Exception $e) {
    // Table might already exist, continue
}

// Allowed override keys with validation rules
$allowedKeys = [
    'leaderboard_mode'        => ['type' => 'enum', 'values' => ['enabled', 'top_only', 'individual', 'disabled']],
    'leaderboard_top_n'       => ['type' => 'int', 'min' => 1, 'max' => 20],
    'xp_multiplier'           => ['type' => 'float', 'min' => 0.1, 'max' => 3.0],
    'max_daily_xp'            => ['type' => 'int', 'min' => 50, 'max' => 5000],
    'difficulty_level'        => ['type' => 'enum', 'values' => ['easy', 'moderate', 'challenging']],
    'animation_level'         => ['type' => 'enum', 'values' => ['full', 'reduced', 'none']],
    'notification_frequency'  => ['type' => 'enum', 'values' => ['all', 'important', 'minimal']],
    'quiz_timer_seconds'      => ['type' => 'int', 'min' => 0, 'max' => 120],
    'game_timer_seconds'      => ['type' => 'int', 'min' => 0, 'max' => 120],
    'show_game_score'         => ['type' => 'int', 'min' => 0, 'max' => 1],
];

// ── Validate student belongs to this teacher ──
function validateStudentOwnership(PDO $db, int $studentId, int $teacherId): bool {
    $stmt = $db->prepare('SELECT id FROM students WHERE id = :sid AND teacher_id = :tid AND is_active = 1');
    $stmt->execute([':sid' => $studentId, ':tid' => $teacherId]);
    return (bool) $stmt->fetch();
}

// ── GET: Return all overrides for a student ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    if ($studentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'student_id is required.']);
        exit;
    }

    if (!validateStudentOwnership($db, $studentId, $tid)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Student not found or not yours.']);
        exit;
    }

    try {
        $stmt = $db->prepare('SELECT setting_key, setting_value FROM student_settings_overrides WHERE student_id = :sid AND teacher_id = :tid');
        $stmt->execute([':sid' => $studentId, ':tid' => $tid]);
        $rows = $stmt->fetchAll();

        $overrides = [];
        foreach ($rows as $row) {
            $overrides[$row['setting_key']] = $row['setting_value'];
        }

        echo json_encode(['success' => true, 'data' => ['overrides' => $overrides]]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load overrides.']);
    }
    exit;
}

// ── POST: Replace all overrides for a student ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
        exit;
    }

    $studentId = (int) ($data['student_id'] ?? 0);
    $overrides = $data['overrides'] ?? [];

    if ($studentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'student_id is required.']);
        exit;
    }

    if (!is_array($overrides)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'overrides must be an object.']);
        exit;
    }

    if (!validateStudentOwnership($db, $studentId, $tid)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Student not found or not yours.']);
        exit;
    }

    // Validate each override
    $validated = [];
    foreach ($overrides as $key => $value) {
        if (!isset($allowedKeys[$key])) continue; // skip unknown keys

        $rule = $allowedKeys[$key];
        switch ($rule['type']) {
            case 'enum':
                if (in_array((string) $value, $rule['values'], true)) {
                    $validated[$key] = (string) $value;
                }
                break;
            case 'int':
                $v = (int) $value;
                $validated[$key] = (string) max($rule['min'], min($rule['max'], $v));
                break;
            case 'float':
                $v = (float) $value;
                $validated[$key] = (string) max($rule['min'], min($rule['max'], round($v, 2)));
                break;
        }
    }

    try {
        $db->beginTransaction();

        // Delete all existing overrides for this student
        $stmt = $db->prepare('DELETE FROM student_settings_overrides WHERE student_id = :sid AND teacher_id = :tid');
        $stmt->execute([':sid' => $studentId, ':tid' => $tid]);

        // Insert new overrides
        if (!empty($validated)) {
            $stmt = $db->prepare('INSERT INTO student_settings_overrides (student_id, teacher_id, setting_key, setting_value) VALUES (:sid, :tid, :key, :val)');
            foreach ($validated as $key => $val) {
                $stmt->execute([':sid' => $studentId, ':tid' => $tid, ':key' => $key, ':val' => $val]);
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => empty($validated) ? 'All overrides removed — using global settings.' : count($validated) . ' override(s) saved.',
            'data'    => ['overrides' => $validated],
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save overrides.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
