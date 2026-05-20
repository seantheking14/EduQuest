<?php
/**
 * GET /api/gamification/activities-student.php
 * Returns list of teacher-created activities assigned to the authenticated student.
 * Combines with predetermined games available in the student dashboard.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

function normalizeActivityCategory(string $category): string {
    $c = strtolower(trim($category));
    if ($c === 'self_care' || $c === 'self-care' || $c === 'self care') return 'selfcare';
    if ($c === 'filipino') return 'english';
    if (in_array($c, ['math', 'english', 'selfcare'], true)) return $c;
    return 'selfcare';
}

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

    // Fetch teacher-created activities assigned to this student
    $activities = [];
    if ($teacherId > 0) {
        $teacherActivitiesEnabled = true;
        try {
            $stmt = $db->prepare('SELECT game_id, is_enabled FROM teacher_default_game_settings WHERE teacher_id = :tid AND game_id IN (\'teacher-activities\', \'teacher_activities\')');
            $stmt->execute([':tid' => $teacherId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $setting) {
                if ($setting['game_id'] === 'teacher-activities' || $setting['game_id'] === 'teacher_activities') {
                    $teacherActivitiesEnabled = ((int) $setting['is_enabled']) === 1;
                }
            }
        } catch (Exception $e) {
            // If toggles are unavailable, keep teacher activities enabled by default.
        }

        if (!$teacherActivitiesEnabled) {
            jsonResponse(true, 'Activities loaded.', [
                'teacher_activities' => [],
            ]);
        }

        // Each named parameter may only appear once when emulate_prepares=false,
        // so duplicate occurrences are given unique suffixes.
        $sql = '
            SELECT 
                a.id, a.title, a.description, a.icon, a.category, 
                a.activity_type, a.instructions, a.rounds, 
                a.xp_reward, a.pass_percentage, a.max_attempts, 
                a.time_limit_sec, a.is_active,
                (SELECT COUNT(*) FROM teacher_activity_attempts WHERE student_id = :sid1 AND activity_id = a.id) AS attempt_count,
                (SELECT COUNT(*) FROM teacher_activity_attempts WHERE student_id = :sid2 AND activity_id = a.id AND passed = 1) AS passed_count,
                (SELECT MAX(percentage) FROM teacher_activity_attempts WHERE student_id = :sid3 AND activity_id = a.id) AS best_score
            FROM teacher_activities a
            WHERE a.teacher_id = :tid1
              AND a.is_active = 1
              AND (
                  NOT EXISTS (
                      SELECT 1
                      FROM teacher_activity_assignments taa0
                      WHERE taa0.activity_id = a.id
                        AND taa0.teacher_id = :tid2
                  )
                  OR EXISTS (
                      SELECT 1
                      FROM teacher_activity_assignments taa1
                      WHERE taa1.activity_id = a.id
                        AND taa1.teacher_id = :tid3
                        AND (taa1.student_id = :sid4 OR taa1.student_id IS NULL)
                  )
              )
            ORDER BY a.updated_at DESC
        ';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':sid1' => $studentId, ':sid2' => $studentId,
            ':sid3' => $studentId, ':sid4' => $studentId,
            ':tid1' => $teacherId, ':tid2' => $teacherId, ':tid3' => $teacherId,
        ]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch items for each activity
        foreach ($activities as &$activity) {
            $stmt = $db->prepare('
                SELECT item_order, item_data FROM teacher_activity_items 
                WHERE activity_id = :aid 
                ORDER BY item_order
            ');
            $stmt->execute([':aid' => (int) $activity['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $activity['rounds'] = [];
            foreach ($items as $item) {
                $activity['rounds'][] = json_decode($item['item_data'], true) ?? [];
            }

            $activity['category'] = normalizeActivityCategory((string)($activity['category'] ?? ''));
            
            // Mark as custom/teacher-created for frontend differentiation
            $activity['is_custom'] = true;
        }
        unset($activity);
    }

    jsonResponse(true, 'Activities loaded.', [
        'teacher_activities' => $activities,
    ]);

} catch (Exception $e) {
    jsonResponse(false, 'Failed to load activities: ' . $e->getMessage(), [], 500);
}
