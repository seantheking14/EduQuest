<?php
/**
 * GET /api/learning/subjects.php
 * Returns all active subjects with the student's progress for each.
 * Subjects unlock sequentially: first is always active, next unlocks when previous is completed.
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

try {
    // Resolve student_id
    $stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $user['id']]);
    $studentRow = $stmt->fetch();

    if (!$studentRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
        exit;
    }

    $studentId = (int) $studentRow['id'];

    // Get all active subjects
    $stmt = $db->prepare('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order ASC');
    $stmt->execute();
    $subjects = $stmt->fetchAll();

    // Get student progress for each subject
    $stmt = $db->prepare('SELECT * FROM student_subject_progress WHERE student_id = :sid');
    $stmt->execute([':sid' => $studentId]);
    $progressRows = $stmt->fetchAll();
    $progressMap = [];
    foreach ($progressRows as $p) {
        $progressMap[(int) $p['subject_id']] = $p;
    }

    // Ensure sequential unlock: first subject always active
    $result = [];
    $previousCompleted = true; // first subject is always unlockable

    foreach ($subjects as $subj) {
        $sid = (int) $subj['id'];
        $progress = $progressMap[$sid] ?? null;

        // Count lessons and completed lessons
        $stmt = $db->prepare('SELECT COUNT(*) AS total FROM lessons WHERE subject_id = :sid AND is_active = 1');
        $stmt->execute([':sid' => $sid]);
        $totalLessons = (int) $stmt->fetch()['total'];

        $stmt = $db->prepare("
            SELECT COUNT(*) AS done FROM student_lesson_progress slp
            JOIN lessons l ON l.id = slp.lesson_id
            WHERE slp.student_id = :stid AND l.subject_id = :sid AND slp.status = 'completed'
        ");
        $stmt->execute([':stid' => $studentId, ':sid' => $sid]);
        $completedLessons = (int) $stmt->fetch()['done'];

        // Determine status
        $status = 'locked';
        if ($progress) {
            $status = $progress['status'];
        } elseif ($previousCompleted) {
            // Auto-initialize first available subject as active
            $status = 'active';
            $db->prepare('INSERT INTO student_subject_progress (student_id, subject_id, status, started_at) VALUES (:stid, :sid, :st, NOW())')
               ->execute([':stid' => $studentId, ':sid' => $sid, ':st' => 'active']);
        }

        $result[] = [
            'id'               => $sid,
            'slug'             => $subj['slug'],
            'title'            => $subj['title'],
            'description'      => $subj['description'],
            'icon'             => $subj['icon'],
            'color'            => $subj['color'],
            'bgColor'          => $subj['bg_color'],
            'status'           => $status,
            'totalLessons'     => $totalLessons,
            'completedLessons' => $completedLessons,
            'totalXpEarned'    => (int) ($progress['total_xp_earned'] ?? 0),
            'startedAt'        => $progress['started_at'] ?? null,
            'completedAt'      => $progress['completed_at'] ?? null,
        ];

        $previousCompleted = ($status === 'completed');
    }

    echo json_encode([
        'success' => true,
        'data'    => $result,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load subjects.']);
}
