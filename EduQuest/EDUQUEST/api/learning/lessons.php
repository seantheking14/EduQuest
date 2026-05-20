<?php
/**
 * GET /api/learning/lessons.php?subjectId=X
 * Returns all lessons for a subject with the student's progress.
 * Lessons unlock sequentially within a subject.
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

$subjectId = isset($_GET['subjectId']) ? (int) $_GET['subjectId'] : 0;
if ($subjectId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'subjectId is required.']);
    exit;
}

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

    // Check subject exists and student has access
    $stmt = $db->prepare('SELECT * FROM subjects WHERE id = :sid AND is_active = 1');
    $stmt->execute([':sid' => $subjectId]);
    $subject = $stmt->fetch();

    if (!$subject) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Subject not found.']);
        exit;
    }

    // Check student's subject progress
    $stmt = $db->prepare('SELECT * FROM student_subject_progress WHERE student_id = :stid AND subject_id = :sid');
    $stmt->execute([':stid' => $studentId, ':sid' => $subjectId]);
    $subjectProgress = $stmt->fetch();

    if (!$subjectProgress || $subjectProgress['status'] === 'locked') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This subject is locked. Complete the previous subject first!']);
        exit;
    }

    // Get all lessons for this subject
    $stmt = $db->prepare('SELECT * FROM lessons WHERE subject_id = :sid AND is_active = 1 ORDER BY lesson_order ASC');
    $stmt->execute([':sid' => $subjectId]);
    $lessons = $stmt->fetchAll();

    // Get student's lesson progress
    $stmt = $db->prepare('SELECT * FROM student_lesson_progress WHERE student_id = :stid');
    $stmt->execute([':stid' => $studentId]);
    $lessonProgress = [];
    foreach ($stmt->fetchAll() as $lp) {
        $lessonProgress[(int) $lp['lesson_id']] = $lp;
    }

    // Check which lessons have quizzes
    $stmt = $db->prepare('SELECT lesson_id, id, title, pass_percentage, xp_reward FROM quizzes WHERE lesson_id IN (SELECT id FROM lessons WHERE subject_id = :sid) AND is_active = 1');
    $stmt->execute([':sid' => $subjectId]);
    $quizMap = [];
    foreach ($stmt->fetchAll() as $q) {
        $quizMap[(int) $q['lesson_id']] = $q;
    }

    // Get best quiz attempts
    $stmt = $db->prepare("
        SELECT quiz_id, MAX(percentage) AS best_score, MAX(passed) AS ever_passed, COUNT(*) AS attempts
        FROM student_quiz_attempts
        WHERE student_id = :stid
        GROUP BY quiz_id
    ");
    $stmt->execute([':stid' => $studentId]);
    $quizAttempts = [];
    foreach ($stmt->fetchAll() as $qa) {
        $quizAttempts[(int) $qa['quiz_id']] = $qa;
    }

    $result = [];
    $previousCompleted = true;

    foreach ($lessons as $lesson) {
        $lid = (int) $lesson['id'];
        $lp = $lessonProgress[$lid] ?? null;

        // Determine status
        $status = 'locked';
        if ($lp) {
            $status = $lp['status'];
        } elseif ($previousCompleted) {
            $status = 'available';
            $db->prepare("INSERT IGNORE INTO student_lesson_progress (student_id, lesson_id, status) VALUES (:stid, :lid, 'available')")
               ->execute([':stid' => $studentId, ':lid' => $lid]);
        }

        // Count lesson pages
        $stmt2 = $db->prepare('SELECT COUNT(*) AS cnt FROM lesson_content WHERE lesson_id = :lid');
        $stmt2->execute([':lid' => $lid]);
        $pageCount = (int) $stmt2->fetch()['cnt'];

        // Quiz info
        $quiz = $quizMap[$lid] ?? null;
        $quizInfo = null;
        if ($quiz) {
            $qa = $quizAttempts[(int) $quiz['id']] ?? null;
            $quizInfo = [
                'id'              => (int) $quiz['id'],
                'title'           => $quiz['title'],
                'passPercentage'  => (int) $quiz['pass_percentage'],
                'xpReward'        => (int) $quiz['xp_reward'],
                'bestScore'       => $qa ? (float) $qa['best_score'] : null,
                'passed'          => $qa ? (bool) $qa['ever_passed'] : false,
                'attempts'        => $qa ? (int) $qa['attempts'] : 0,
            ];
        }

        $result[] = [
            'id'              => $lid,
            'title'           => $lesson['title'],
            'description'     => $lesson['description'],
            'lessonOrder'     => (int) $lesson['lesson_order'],
            'difficulty'      => $lesson['difficulty'],
            'xpReward'        => (int) $lesson['xp_reward'],
            'estimatedMinutes'=> (int) $lesson['estimated_minutes'],
            'icon'            => $lesson['icon'],
            'contentType'     => $lesson['content_type'],
            'status'          => $status,
            'currentPage'     => (int) ($lp['current_page'] ?? 0),
            'totalPages'      => $pageCount,
            'xpEarned'        => (int) ($lp['xp_earned'] ?? 0),
            'timeSpent'       => (int) ($lp['time_spent_sec'] ?? 0),
            'startedAt'       => $lp['started_at'] ?? null,
            'completedAt'     => $lp['completed_at'] ?? null,
            'quiz'            => $quizInfo,
        ];

        $previousCompleted = ($status === 'completed');
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'subject' => [
                'id'          => (int) $subject['id'],
                'slug'        => $subject['slug'],
                'title'       => $subject['title'],
                'description' => $subject['description'],
                'icon'        => $subject['icon'],
                'color'       => $subject['color'],
            ],
            'lessons' => $result,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load lessons.']);
}
