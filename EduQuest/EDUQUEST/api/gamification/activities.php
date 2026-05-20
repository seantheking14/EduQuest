<?php
/**
 * Teacher Activity CRUD API
 *
 * GET    ?action=list                         — list teacher's activities
 * GET    ?action=get&id=X                     — get single activity with items
 * GET    ?action=results&id=X                 — get activity attempt analytics
 * GET    ?action=default-games                — get predetermined game toggles
 * POST   action=create                        — create activity + items
 * POST   action=update                        — update activity + items
 * POST   action=delete                        — delete activity
 * POST   action=duplicate                     — duplicate an activity
 * POST   action=toggle                        — toggle active/inactive
 * POST   action=assign                        — assign activity to course/students
 * POST   action=save-default-games            — save predetermined game toggles
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireTeacher();
$db   = getDBConnection();
$teacherId = (int) $user['id'];

$action = $_GET['action'] ?? ($_POST['action'] ?? (json_decode(file_get_contents('php://input'), true)['action'] ?? ''));

// ── GET routes ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') { listActivities(); }
    elseif ($action === 'get') { getActivity(); }
    elseif ($action === 'results') { getActivityResults(); }
    elseif ($action === 'default-games') { getDefaultGamesSettings(); }
    else { jsonResponse(false, 'Invalid action.', [], 400); }
}

// ── POST routes ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!$action && isset($body['action'])) $action = $body['action'];

    switch ($action) {
        case 'create':    createActivity($body); break;
        case 'update':    updateActivity($body); break;
        case 'delete':    deleteActivity($body); break;
        case 'duplicate': duplicateActivity($body); break;
        case 'toggle':    toggleActivity($body); break;
        case 'assign':    assignActivity($body); break;
        case 'save-default-games': saveDefaultGamesSettings($body); break;
        default: jsonResponse(false, 'Invalid action.', [], 400);
    }
}

function getDefaultGameCatalog() {
    return [
        ['id' => 'teacher-activities', 'subject' => 'teacher',  'title' => 'Teacher Assigned Activities (Top of My Quests)'],
        ['id' => 'math-sort-asc',      'subject' => 'math',     'title' => 'Arrange Numbers Up'],
        ['id' => 'math-compare',       'subject' => 'math',     'title' => 'Compare Numbers'],
        ['id' => 'math-ordinal',       'subject' => 'math',     'title' => 'Ordinal Numbers'],
        ['id' => 'math-truefalse',     'subject' => 'math',     'title' => 'Math True/False'],
        ['id' => 'math-pairs',         'subject' => 'math',     'title' => 'Math Match Pairs'],
        ['id' => 'eng-build-cvc',      'subject' => 'english',  'title' => 'Build CVC Words'],
        ['id' => 'eng-read-cvc',       'subject' => 'english',  'title' => 'Read CVC Words'],
        ['id' => 'eng-sentences',      'subject' => 'english',  'title' => 'Sentence Completion'],
        ['id' => 'eng-truefalse',      'subject' => 'english',  'title' => 'English True/False'],
        ['id' => 'eng-pairs',          'subject' => 'english',  'title' => 'English Match Pairs'],
        ['id' => 'sc-living',          'subject' => 'selfcare', 'title' => 'Living or Non-Living'],
        ['id' => 'sc-food',            'subject' => 'selfcare', 'title' => 'Healthy or Junk Food'],
        ['id' => 'sc-eating-habits',   'subject' => 'selfcare', 'title' => 'Good Eating Habits'],
        ['id' => 'sc-truefalse',       'subject' => 'selfcare', 'title' => 'Self-Care True/False'],
        ['id' => 'sc-pairs',           'subject' => 'selfcare', 'title' => 'Self-Care Match Pairs'],
    ];
}

function ensureTeacherDefaultGameSettingsTable() {
    global $db;
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
}

function getDefaultGamesSettings() {
    global $db, $teacherId;

    $catalog = getDefaultGameCatalog();
    ensureTeacherDefaultGameSettingsTable();

    $stmt = $db->prepare('SELECT game_id, is_enabled FROM teacher_default_game_settings WHERE teacher_id = :tid');
    $stmt->execute([':tid' => $teacherId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $enabledMap = [];
    foreach ($rows as $row) {
        $enabledMap[$row['game_id']] = ((int) $row['is_enabled']) === 1;
    }

    $games = array_map(function($game) use ($enabledMap) {
        $game['is_enabled'] = array_key_exists($game['id'], $enabledMap) ? $enabledMap[$game['id']] : true;
        return $game;
    }, $catalog);

    jsonResponse(true, 'Default games settings loaded.', ['games' => $games]);
}

function saveDefaultGamesSettings($body) {
    global $db, $teacherId;

    $games = $body['games'] ?? null;
    if (!is_array($games)) {
        jsonResponse(false, 'Games payload is required.', [], 400);
    }

    $catalog = getDefaultGameCatalog();
    $validIds = array_flip(array_map(function($g) { return $g['id']; }, $catalog));

    ensureTeacherDefaultGameSettingsTable();

    try {
        $db->beginTransaction();

        $stmt = $db->prepare('
            INSERT INTO teacher_default_game_settings (teacher_id, game_id, is_enabled)
            VALUES (:tid, :gid, :enabled)
            ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)
        ');

        $saved = 0;
        foreach ($games as $entry) {
            $gid = (string) ($entry['id'] ?? '');
            if (!$gid || !isset($validIds[$gid])) continue;
            $enabled = !empty($entry['is_enabled']) ? 1 : 0;
            $stmt->execute([
                ':tid' => $teacherId,
                ':gid' => $gid,
                ':enabled' => $enabled,
            ]);
            $saved++;
        }

        $db->commit();
        jsonResponse(true, 'Default game settings saved.', ['saved_count' => $saved]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to save default game settings: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   LIST — all activities for this teacher
   ═══════════════════════════════════════════════════════════ */
function listActivities() {
    global $db, $teacherId;

    $search = sanitizeString($_GET['search'] ?? '');
    $category = sanitizeString($_GET['category'] ?? '');

    $sql = 'SELECT a.*, 
            (SELECT COUNT(*) FROM teacher_activity_items WHERE activity_id = a.id) AS item_count,
            (SELECT COUNT(DISTINCT student_id) FROM teacher_activity_attempts WHERE activity_id = a.id) AS attempt_count
            FROM teacher_activities a
            WHERE a.teacher_id = :tid';
    $params = [':tid' => $teacherId];

    if ($category) {
        $sql .= ' AND a.category = :cat';
        $params[':cat'] = $category;
    }
    if ($search) {
        $sql .= ' AND a.title LIKE :search';
        $params[':search'] = "%$search%";
    }

    $sql .= ' ORDER BY a.updated_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'Activities fetched.', ['activities' => $activities]);
}

/* ═══════════════════════════════════════════════════════════
   GET — single activity with all items
   ═══════════════════════════════════════════════════════════ */
function getActivity() {
    global $db, $teacherId;

    $activityId = (int) ($_GET['id'] ?? 0);
    if ($activityId <= 0) jsonResponse(false, 'Activity ID required.', [], 400);

    // Verify ownership
    $stmt = $db->prepare('SELECT * FROM teacher_activities WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $activityId, ':tid' => $teacherId]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) jsonResponse(false, 'Activity not found.', [], 404);

    // Fetch all items
    $stmt = $db->prepare('SELECT * FROM teacher_activity_items WHERE activity_id = :id ORDER BY item_order');
    $stmt->execute([':id' => $activityId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse JSON in each item
    foreach ($items as &$item) {
        $item['item_data'] = json_decode($item['item_data'], true) ?? [];
    }
    unset($item);

    jsonResponse(true, 'Activity fetched.', [
        'activity' => $activity,
        'items' => $items
    ]);
}

/* ═══════════════════════════════════════════════════════════
   CREATE — new activity with items
   ═══════════════════════════════════════════════════════════ */
function createActivity($body) {
    global $db, $teacherId;

    $title = sanitizeString($body['title'] ?? '');
    $description = sanitizeString($body['description'] ?? '');
    $categoryRaw = sanitizeString($body['category'] ?? '');
    $category = normalizeActivityCategory($categoryRaw);
    $icon = sanitizeString($body['icon'] ?? '🎮');
    $activityType = sanitizeString($body['activity_type'] ?? 'choose');
    $instructions = sanitizeString($body['instructions'] ?? '');
    $rounds = (int) ($body['rounds'] ?? 6);
    $xpReward = (int) ($body['xp_reward'] ?? 50);
    $passPercentage = (int) ($body['pass_percentage'] ?? 70);
    $maxAttempts = (int) ($body['max_attempts'] ?? 0);
    $timeLimitSec = (int) ($body['time_limit_sec'] ?? 0);
    $items = $body['items'] ?? [];

    if (!$title || !$category) {
        jsonResponse(false, 'Title and valid category required.', [], 400);
    }
    $allowedTypes = getAllowedActivityTypesByCategory($category);
    if (!in_array($activityType, $allowedTypes, true)) {
        jsonResponse(false, 'Invalid activity type for selected category.', [], 400);
    }

    try {
        $db->beginTransaction();

        // Create activity
        $stmt = $db->prepare('
            INSERT INTO teacher_activities 
            (teacher_id, category, title, description, icon, activity_type, instructions, rounds, xp_reward, pass_percentage, max_attempts, time_limit_sec)
            VALUES (:tid, :cat, :title, :desc, :icon, :type, :instr, :rounds, :xp, :pass, :max_att, :time_limit)
        ');
        $stmt->execute([
            ':tid' => $teacherId,
            ':cat' => $category,
            ':title' => $title,
            ':desc' => $description,
            ':icon' => $icon,
            ':type' => $activityType,
            ':instr' => $instructions,
            ':rounds' => $rounds,
            ':xp' => $xpReward,
            ':pass' => $passPercentage,
            ':max_att' => $maxAttempts,
            ':time_limit' => $timeLimitSec
        ]);

        $activityId = (int) $db->lastInsertId();

        // Insert items
        $itemStmt = $db->prepare('
            INSERT INTO teacher_activity_items (activity_id, item_order, item_data)
            VALUES (:activity_id, :order, :data)
        ');

        foreach ($items as $idx => $itemData) {
            $itemStmt->execute([
                ':activity_id' => $activityId,
                ':order' => ($idx + 1),
                ':data' => json_encode($itemData, JSON_UNESCAPED_UNICODE)
            ]);
        }

        $db->commit();

        jsonResponse(true, 'Activity created successfully.', ['activity_id' => $activityId], 201);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to create activity: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   UPDATE — existing activity + items
   ═══════════════════════════════════════════════════════════ */
function updateActivity($body) {
    global $db, $teacherId;

    $activityId = (int) ($body['id'] ?? 0);
    if ($activityId <= 0) jsonResponse(false, 'Activity ID required.', [], 400);

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM teacher_activities WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $activityId, ':tid' => $teacherId]);
    if (!$stmt->fetch()) jsonResponse(false, 'Activity not found or access denied.', [], 404);

    $title = sanitizeString($body['title'] ?? '');
    $description = sanitizeString($body['description'] ?? '');
    $categoryRaw = sanitizeString($body['category'] ?? '');
    $category = normalizeActivityCategory($categoryRaw);
    $icon = sanitizeString($body['icon'] ?? '🎮');
    $activityType = sanitizeString($body['activity_type'] ?? 'choose');
    $instructions = sanitizeString($body['instructions'] ?? '');
    $rounds = (int) ($body['rounds'] ?? 6);
    $xpReward = (int) ($body['xp_reward'] ?? 50);
    $passPercentage = (int) ($body['pass_percentage'] ?? 70);
    $maxAttempts = (int) ($body['max_attempts'] ?? 0);
    $timeLimitSec = (int) ($body['time_limit_sec'] ?? 0);
    $items = $body['items'] ?? [];

    if (!$title || !$category) {
        jsonResponse(false, 'Title and valid category required.', [], 400);
    }
    $allowedTypes = getAllowedActivityTypesByCategory($category);
    if (!in_array($activityType, $allowedTypes, true)) {
        jsonResponse(false, 'Invalid activity type for selected category.', [], 400);
    }

    try {
        $db->beginTransaction();

        // Update activity
        $stmt = $db->prepare('
            UPDATE teacher_activities SET
            category = :cat, title = :title, description = :desc, icon = :icon,
            activity_type = :type, instructions = :instr, rounds = :rounds,
            xp_reward = :xp, pass_percentage = :pass, max_attempts = :max_att,
            time_limit_sec = :time_limit, updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            ':cat' => $category,
            ':title' => $title,
            ':desc' => $description,
            ':icon' => $icon,
            ':type' => $activityType,
            ':instr' => $instructions,
            ':rounds' => $rounds,
            ':xp' => $xpReward,
            ':pass' => $passPercentage,
            ':max_att' => $maxAttempts,
            ':time_limit' => $timeLimitSec,
            ':id' => $activityId
        ]);

        // Delete old items
        $db->prepare('DELETE FROM teacher_activity_items WHERE activity_id = :id')->execute([':id' => $activityId]);

        // Insert new items
        $itemStmt = $db->prepare('
            INSERT INTO teacher_activity_items (activity_id, item_order, item_data)
            VALUES (:activity_id, :order, :data)
        ');

        foreach ($items as $idx => $itemData) {
            $itemStmt->execute([
                ':activity_id' => $activityId,
                ':order' => ($idx + 1),
                ':data' => json_encode($itemData, JSON_UNESCAPED_UNICODE)
            ]);
        }

        $db->commit();

        jsonResponse(true, 'Activity updated successfully.', ['activity_id' => $activityId]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to update activity: ' . $e->getMessage(), [], 500);
    }
}

function normalizeActivityCategory(string $category): string {
    $c = strtolower(trim($category));
    if ($c === 'self_care' || $c === 'self-care' || $c === 'self care') return 'selfcare';
    if ($c === 'filipino') return 'english';
    if (in_array($c, ['math', 'english', 'selfcare'], true)) return $c;
    return '';
}

function getAllowedActivityTypesByCategory(string $category): array {
    switch ($category) {
        case 'math':
            return ['sort-order', 'compare', 'choose', 'truefalse', 'match-pairs'];
        case 'english':
            return ['build-word', 'choose', 'truefalse', 'match-pairs'];
        case 'selfcare':
            return ['classify', 'choose', 'truefalse', 'match-pairs'];
        default:
            return [];
    }
}

/* ═══════════════════════════════════════════════════════════
   DELETE — activity and all related data
   ═══════════════════════════════════════════════════════════ */
function deleteActivity($body) {
    global $db, $teacherId;

    $activityId = (int) ($body['id'] ?? 0);
    if ($activityId <= 0) jsonResponse(false, 'Activity ID required.', [], 400);

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM teacher_activities WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $activityId, ':tid' => $teacherId]);
    if (!$stmt->fetch()) jsonResponse(false, 'Activity not found or access denied.', [], 404);

    try {
        // Cascade delete handled by foreign keys
        $db->prepare('DELETE FROM teacher_activities WHERE id = :id')->execute([':id' => $activityId]);
        jsonResponse(true, 'Activity deleted successfully.');
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to delete activity: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   DUPLICATE — copy existing activity
   ═══════════════════════════════════════════════════════════ */
function duplicateActivity($body) {
    global $db, $teacherId;

    $activityId = (int) ($body['id'] ?? 0);
    if ($activityId <= 0) jsonResponse(false, 'Activity ID required.', [], 400);

    // Fetch original activity
    $stmt = $db->prepare('SELECT * FROM teacher_activities WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $activityId, ':tid' => $teacherId]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) jsonResponse(false, 'Activity not found.', [], 404);

    try {
        $db->beginTransaction();

        // Create duplicate
        $stmt = $db->prepare('
            INSERT INTO teacher_activities 
            (teacher_id, category, title, description, icon, activity_type, instructions, rounds, xp_reward, pass_percentage, max_attempts, time_limit_sec, is_active)
            SELECT :tid, category, CONCAT(title, " (Copy)"), description, icon, activity_type, instructions, rounds, xp_reward, pass_percentage, max_attempts, time_limit_sec, is_active
            FROM teacher_activities
            WHERE id = :id
        ');
        $stmt->execute([':tid' => $teacherId, ':id' => $activityId]);
        $newActivityId = (int) $db->lastInsertId();

        // Copy all items
        $stmt = $db->prepare('
            INSERT INTO teacher_activity_items (activity_id, item_order, item_data)
            SELECT :new_id, item_order, item_data
            FROM teacher_activity_items
            WHERE activity_id = :id
        ');
        $stmt->execute([':new_id' => $newActivityId, ':id' => $activityId]);

        $db->commit();

        jsonResponse(true, 'Activity duplicated successfully.', ['new_activity_id' => $newActivityId], 201);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to duplicate activity: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   TOGGLE — activate/deactivate activity
   ═══════════════════════════════════════════════════════════ */
function toggleActivity($body) {
    global $db, $teacherId;

    $activityId = (int) ($body['id'] ?? 0);
    $isActive = (int) ($body['is_active'] ?? 0);

    if ($activityId <= 0) jsonResponse(false, 'Activity ID required.', [], 400);

    $stmt = $db->prepare('SELECT id FROM teacher_activities WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $activityId, ':tid' => $teacherId]);
    if (!$stmt->fetch()) jsonResponse(false, 'Activity not found.', [], 404);

    $stmt = $db->prepare('UPDATE teacher_activities SET is_active = :active WHERE id = :id');
    $stmt->execute([':active' => $isActive, ':id' => $activityId]);

    jsonResponse(true, 'Activity toggled successfully.', ['is_active' => (bool) $isActive]);
}

/* ═══════════════════════════════════════════════════════════
   ASSIGN — activity to course or specific students
   ═══════════════════════════════════════════════════════════ */
function assignActivity($body) {
    global $db, $teacherId;

    $activityId = (int) ($body['activity_id'] ?? 0);
    $courseId = isset($body['course_id']) ? (int) $body['course_id'] : null;
    $studentIds = $body['student_ids'] ?? [];

    if ($activityId <= 0) jsonResponse(false, 'Activity ID required.', [], 400);

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM teacher_activities WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $activityId, ':tid' => $teacherId]);
    if (!$stmt->fetch()) jsonResponse(false, 'Activity not found.', [], 404);

    try {
        $db->beginTransaction();

        // Delete existing assignments for this activity
        $db->prepare('DELETE FROM teacher_activity_assignments WHERE activity_id = :id AND teacher_id = :tid')
            ->execute([':id' => $activityId, ':tid' => $teacherId]);

        // Insert new assignments
        $stmt = $db->prepare('
            INSERT INTO teacher_activity_assignments (activity_id, teacher_id, course_id, student_id, assigned_at)
            VALUES (:activity_id, :teacher_id, :course_id, :student_id, NOW())
        ');

        if (!empty($studentIds)) {
            foreach ($studentIds as $studentId) {
                $stmt->execute([
                    ':activity_id' => $activityId,
                    ':teacher_id' => $teacherId,
                    ':course_id' => $courseId,
                    ':student_id' => (int) $studentId
                ]);
            }
        } else {
            // Assign to entire course if no specific students
            $stmt->execute([
                ':activity_id' => $activityId,
                ':teacher_id' => $teacherId,
                ':course_id' => $courseId,
                ':student_id' => null
            ]);
        }

        $db->commit();

        jsonResponse(true, 'Activity assigned successfully.', ['assigned_count' => count($studentIds) ?: 'course-wide']);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to assign activity: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   RESULTS — get analytics for activity attempts
   ═══════════════════════════════════════════════════════════ */
function getActivityResults() {
    global $db, $teacherId;

    $activityId = (int) ($_GET['id'] ?? 0);
    if ($activityId <= 0) jsonResponse(false, 'Activity ID required.', [], 400);

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM teacher_activities WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $activityId, ':tid' => $teacherId]);
    if (!$stmt->fetch()) jsonResponse(false, 'Activity not found.', [], 404);

    // Get all attempts
    $stmt = $db->prepare('
        SELECT 
            taa.id, taa.student_id, s.first_name, s.last_name, 
            taa.attempt_number, taa.score, taa.percentage, taa.passed,
            taa.time_spent_sec, taa.xp_earned, taa.completed_at
        FROM teacher_activity_attempts taa
        JOIN students s ON s.id = taa.student_id
        WHERE taa.activity_id = :id
        ORDER BY taa.completed_at DESC
    ');
    $stmt->execute([':id' => $activityId]);
    $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate stats
    $totalAttempts = count($attempts);
    $passedCount = array_sum(array_column($attempts, 'passed'));
    $avgScore = $totalAttempts > 0 ? array_sum(array_column($attempts, 'percentage')) / $totalAttempts : 0;

    jsonResponse(true, 'Results fetched.', [
        'total_attempts' => $totalAttempts,
        'passed_count' => $passedCount,
        'average_score' => round($avgScore, 2),
        'attempts' => $attempts
    ]);
}

/**
 * Sanitize string input (local wrapper in case global isn't loaded)
 */
if (!function_exists('sanitizeString')) {
    function sanitizeString($str) {
        return trim(htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8'));
    }
}
