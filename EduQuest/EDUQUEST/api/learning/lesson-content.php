<?php
/**
 * GET /api/learning/lesson-content.php?lessonId=X
 * POST /api/learning/lesson-content.php  { lessonId, currentPage, timeSpent }
 *
 * GET:  Returns all pages for a lesson + student progress
 * POST: Updates progress (page reached, time spent), completes lesson if last page
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireAuth();
$db   = getDBConnection();

// Resolve student
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

// ── GET: Return lesson content ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lessonId = isset($_GET['lessonId']) ? (int) $_GET['lessonId'] : 0;

    if ($lessonId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'lessonId is required.']);
        exit;
    }

    try {
        // Get lesson info
        $stmt = $db->prepare('SELECT l.*, s.title AS subject_title, s.slug AS subject_slug, s.icon AS subject_icon, s.color AS subject_color FROM lessons l JOIN subjects s ON s.id = l.subject_id WHERE l.id = :lid AND l.is_active = 1');
        $stmt->execute([':lid' => $lessonId]);
        $lesson = $stmt->fetch();

        if (!$lesson) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Lesson not found.']);
            exit;
        }

        // Check lesson is accessible
        $stmt = $db->prepare("SELECT * FROM student_lesson_progress WHERE student_id = :stid AND lesson_id = :lid");
        $stmt->execute([':stid' => $studentId, ':lid' => $lessonId]);
        $progress = $stmt->fetch();

        if ($progress && $progress['status'] === 'locked') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'This lesson is locked.']);
            exit;
        }

        // Mark as in_progress if available
        if (!$progress) {
            $db->prepare("INSERT INTO student_lesson_progress (student_id, lesson_id, status, started_at) VALUES (:stid, :lid, 'in_progress', NOW())")
               ->execute([':stid' => $studentId, ':lid' => $lessonId]);
        } elseif ($progress['status'] === 'available') {
            $db->prepare("UPDATE student_lesson_progress SET status = 'in_progress', started_at = NOW() WHERE student_id = :stid AND lesson_id = :lid")
               ->execute([':stid' => $studentId, ':lid' => $lessonId]);
        }

        // Get all pages
        $stmt = $db->prepare('SELECT * FROM lesson_content WHERE lesson_id = :lid ORDER BY page_order ASC');
        $stmt->execute([':lid' => $lessonId]);
        $pages = $stmt->fetchAll();

        // Get quiz if exists
        $stmt = $db->prepare('SELECT id, title, pass_percentage, xp_reward, max_attempts, time_limit_sec FROM quizzes WHERE lesson_id = :lid AND is_active = 1 LIMIT 1');
        $stmt->execute([':lid' => $lessonId]);
        $quiz = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'data' => [
                'lesson' => [
                    'id'              => (int) $lesson['id'],
                    'title'           => $lesson['title'],
                    'description'     => $lesson['description'],
                    'difficulty'      => $lesson['difficulty'],
                    'xpReward'        => (int) $lesson['xp_reward'],
                    'estimatedMinutes'=> (int) $lesson['estimated_minutes'],
                    'icon'            => $lesson['icon'],
                    'contentType'     => $lesson['content_type'],
                    'subjectTitle'    => $lesson['subject_title'],
                    'subjectSlug'     => $lesson['subject_slug'],
                    'subjectIcon'     => $lesson['subject_icon'],
                    'subjectColor'    => $lesson['subject_color'],
                ],
                'pages' => array_map(function ($p) {
                    return [
                        'id'           => (int) $p['id'],
                        'pageOrder'    => (int) $p['page_order'],
                        'title'        => $p['title'],
                        'contentHtml'  => $p['content_html'],
                        'illustration' => $p['illustration'],
                        'tipText'      => $p['tip_text'],
                    ];
                }, $pages),
                'progress' => [
                    'currentPage' => (int) ($progress['current_page'] ?? 0),
                    'status'      => $progress ? $progress['status'] : 'in_progress',
                    'timeSpent'   => (int) ($progress['time_spent_sec'] ?? 0),
                ],
                'quiz' => $quiz ? [
                    'id'             => (int) $quiz['id'],
                    'title'          => $quiz['title'],
                    'passPercentage' => (int) $quiz['pass_percentage'],
                    'xpReward'       => (int) $quiz['xp_reward'],
                    'maxAttempts'    => (int) $quiz['max_attempts'],
                    'timeLimit'      => (int) $quiz['time_limit_sec'],
                ] : null,
            ],
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load lesson.']);
    }
    exit;
}

// ── POST: Update progress / complete lesson ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
        exit;
    }

    $lessonId    = (int) ($data['lessonId'] ?? 0);
    $currentPage = (int) ($data['currentPage'] ?? 0);
    $timeSpent   = (int) ($data['timeSpent'] ?? 0);
    $completed   = (bool) ($data['completed'] ?? false);

    if ($lessonId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'lessonId is required.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Get lesson
        $stmt = $db->prepare('SELECT l.*, s.id AS subject_id FROM lessons l JOIN subjects s ON s.id = l.subject_id WHERE l.id = :lid');
        $stmt->execute([':lid' => $lessonId]);
        $lesson = $stmt->fetch();

        if (!$lesson) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Lesson not found.']);
            exit;
        }

        // Get existing progress
        $stmt = $db->prepare('SELECT * FROM student_lesson_progress WHERE student_id = :stid AND lesson_id = :lid');
        $stmt->execute([':stid' => $studentId, ':lid' => $lessonId]);
        $progress = $stmt->fetch();

        $xpAwarded = 0;
        $leveledUp = false;
        $newAchievements = [];

        if ($completed && (!$progress || $progress['status'] !== 'completed')) {
            // Complete the lesson
            $xpAwarded = (int) $lesson['xp_reward'];

            if ($progress) {
                $db->prepare("
                    UPDATE student_lesson_progress
                    SET status = 'completed', current_page = :page, time_spent_sec = time_spent_sec + :time,
                        completed_at = NOW(), xp_earned = :xp
                    WHERE student_id = :stid AND lesson_id = :lid
                ")->execute([':page' => $currentPage, ':time' => $timeSpent, ':xp' => $xpAwarded, ':stid' => $studentId, ':lid' => $lessonId]);
            } else {
                $db->prepare("
                    INSERT INTO student_lesson_progress (student_id, lesson_id, status, current_page, time_spent_sec, started_at, completed_at, xp_earned)
                    VALUES (:stid, :lid, 'completed', :page, :time, NOW(), NOW(), :xp)
                ")->execute([':stid' => $studentId, ':lid' => $lessonId, ':page' => $currentPage, ':time' => $timeSpent, ':xp' => $xpAwarded]);
            }

            // Award XP via gamification system
            if ($xpAwarded > 0) {
                // Record XP transaction
                $db->prepare("
                    INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description)
                    VALUES (:sid, :xp, 'activity', :lid, :desc)
                ")->execute([
                    ':sid'  => $studentId,
                    ':xp'   => $xpAwarded,
                    ':lid'  => $lessonId,
                    ':desc' => 'Completed lesson: ' . $lesson['title'],
                ]);

                // Update gamification profile
                $db->prepare('UPDATE student_gamification SET total_xp = total_xp + :xp WHERE student_id = :sid')
                   ->execute([':xp' => $xpAwarded, ':sid' => $studentId]);

                // Update subject XP
                $db->prepare('UPDATE student_subject_progress SET total_xp_earned = total_xp_earned + :xp WHERE student_id = :stid AND subject_id = :sid')
                   ->execute([':xp' => $xpAwarded, ':stid' => $studentId, ':sid' => (int) $lesson['subject_id']]);
            }

            // Unlock next lesson
            $stmt = $db->prepare('SELECT id FROM lessons WHERE subject_id = :sid AND lesson_order = :ord AND is_active = 1 LIMIT 1');
            $stmt->execute([':sid' => (int) $lesson['subject_id'], ':ord' => (int) $lesson['lesson_order'] + 1]);
            $nextLesson = $stmt->fetch();

            if ($nextLesson) {
                $db->prepare("INSERT IGNORE INTO student_lesson_progress (student_id, lesson_id, status) VALUES (:stid, :lid, 'available')")
                   ->execute([':stid' => $studentId, ':lid' => $nextLesson['id']]);
            } else {
                // No more lessons → check if subject is complete
                $stmt = $db->prepare("
                    SELECT COUNT(*) AS total FROM lessons WHERE subject_id = :sid AND is_active = 1
                ");
                $stmt->execute([':sid' => (int) $lesson['subject_id']]);
                $totalLessons = (int) $stmt->fetch()['total'];

                $stmt = $db->prepare("
                    SELECT COUNT(*) AS done FROM student_lesson_progress slp
                    JOIN lessons l ON l.id = slp.lesson_id
                    WHERE slp.student_id = :stid AND l.subject_id = :sid AND slp.status = 'completed'
                ");
                $stmt->execute([':stid' => $studentId, ':sid' => (int) $lesson['subject_id']]);
                $doneLessons = (int) $stmt->fetch()['done'];

                if ($doneLessons >= $totalLessons) {
                    // Complete subject
                    $db->prepare("UPDATE student_subject_progress SET status = 'completed', completed_at = NOW() WHERE student_id = :stid AND subject_id = :sid")
                       ->execute([':stid' => $studentId, ':sid' => (int) $lesson['subject_id']]);

                    // Unlock next subject
                    $stmt = $db->prepare('SELECT id FROM subjects WHERE sort_order > (SELECT sort_order FROM subjects WHERE id = :sid) AND is_active = 1 ORDER BY sort_order ASC LIMIT 1');
                    $stmt->execute([':sid' => (int) $lesson['subject_id']]);
                    $nextSubject = $stmt->fetch();

                    if ($nextSubject) {
                        $db->prepare("INSERT IGNORE INTO student_subject_progress (student_id, subject_id, status, started_at) VALUES (:stid, :sid, 'active', NOW())")
                           ->execute([':stid' => $studentId, ':sid' => $nextSubject['id']]);
                    }
                }
            }

        } else {
            // Just update page/time
            if ($progress) {
                $db->prepare("
                    UPDATE student_lesson_progress
                    SET current_page = GREATEST(current_page, :page), time_spent_sec = time_spent_sec + :time
                    WHERE student_id = :stid AND lesson_id = :lid
                ")->execute([':page' => $currentPage, ':time' => $timeSpent, ':stid' => $studentId, ':lid' => $lessonId]);
            } else {
                $db->prepare("
                    INSERT INTO student_lesson_progress (student_id, lesson_id, status, current_page, time_spent_sec, started_at)
                    VALUES (:stid, :lid, 'in_progress', :page, :time, NOW())
                ")->execute([':stid' => $studentId, ':lid' => $lessonId, ':page' => $currentPage, ':time' => $timeSpent]);
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => $completed ? 'Lesson completed! +' . $xpAwarded . ' XP' : 'Progress saved.',
            'data'    => [
                'xpAwarded' => $xpAwarded,
                'completed' => $completed,
            ],
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update progress.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
