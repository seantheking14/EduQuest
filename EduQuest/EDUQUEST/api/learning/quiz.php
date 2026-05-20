<?php
/**
 * GET  /api/learning/quiz.php?quizId=X     — Get quiz questions
 * POST /api/learning/quiz.php              — Submit quiz answers
 *
 * POST Body: { quizId, answers: { questionId: answerId }, timeSpent }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../log_behavior.php';

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

// ── GET: Return quiz questions (without correct answers) ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $quizId = isset($_GET['quizId']) ? (int) $_GET['quizId'] : 0;

    if ($quizId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'quizId is required.']);
        exit;
    }

    try {
        // Get quiz
        $stmt = $db->prepare('SELECT q.*, l.title AS lesson_title, l.icon AS lesson_icon, s.title AS subject_title, s.color AS subject_color FROM quizzes q JOIN lessons l ON l.id = q.lesson_id JOIN subjects s ON s.id = l.subject_id WHERE q.id = :qid AND q.is_active = 1');
        $stmt->execute([':qid' => $quizId]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Quiz not found.']);
            exit;
        }

        // Check attempt limit
        $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM student_quiz_attempts WHERE student_id = :stid AND quiz_id = :qid');
        $stmt->execute([':stid' => $studentId, ':qid' => $quizId]);
        $attemptCount = (int) $stmt->fetch()['cnt'];

        if ((int) $quiz['max_attempts'] > 0 && $attemptCount >= (int) $quiz['max_attempts']) {
            // Check if already passed
            $stmt = $db->prepare('SELECT MAX(passed) AS ever_passed FROM student_quiz_attempts WHERE student_id = :stid AND quiz_id = :qid');
            $stmt->execute([':stid' => $studentId, ':qid' => $quizId]);
            $passed = (bool) $stmt->fetch()['ever_passed'];

            if (!$passed) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Maximum attempts reached.']);
                exit;
            }
        }

        // Get questions
        $stmt = $db->prepare('SELECT id, question_order, question_text, question_type, illustration, points FROM quiz_questions WHERE quiz_id = :qid ORDER BY question_order ASC');
        $stmt->execute([':qid' => $quizId]);
        $questions = $stmt->fetchAll();

        // Get answers (without revealing correct)
        $questionsWithAnswers = [];
        foreach ($questions as $q) {
            $stmt = $db->prepare('SELECT id, answer_text, answer_order FROM quiz_answers WHERE question_id = :qid ORDER BY answer_order ASC');
            $stmt->execute([':qid' => $q['id']]);
            $answers = $stmt->fetchAll();

            $questionsWithAnswers[] = [
                'id'            => (int) $q['id'],
                'questionOrder' => (int) $q['question_order'],
                'questionText'  => $q['question_text'],
                'questionType'  => $q['question_type'],
                'illustration'  => $q['illustration'],
                'points'        => (int) $q['points'],
                'answers'       => array_map(function ($a) {
                    return [
                        'id'   => (int) $a['id'],
                        'text' => $a['answer_text'],
                    ];
                }, $answers),
            ];
        }

        echo json_encode([
            'success' => true,
            'data'    => [
                'quiz' => [
                    'id'             => (int) $quiz['id'],
                    'title'          => $quiz['title'],
                    'description'    => $quiz['description'],
                    'passPercentage' => (int) $quiz['pass_percentage'],
                    'xpReward'       => (int) $quiz['xp_reward'],
                    'maxAttempts'    => (int) $quiz['max_attempts'],
                    'timeLimit'      => (int) $quiz['time_limit_sec'],
                    'lessonTitle'    => $quiz['lesson_title'],
                    'lessonIcon'     => $quiz['lesson_icon'],
                    'subjectTitle'   => $quiz['subject_title'],
                    'subjectColor'   => $quiz['subject_color'],
                ],
                'questions'     => $questionsWithAnswers,
                'attemptNumber' => $attemptCount + 1,
            ],
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load quiz.']);
    }
    exit;
}

// ── POST: Submit quiz answers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
        exit;
    }

    $quizId   = (int) ($data['quizId'] ?? 0);
    $answers  = $data['answers'] ?? [];   // { questionId: answerId }
    $timeSpent = (int) ($data['timeSpent'] ?? 0);

    if ($quizId <= 0 || empty($answers)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'quizId and answers are required.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Get quiz
        $stmt = $db->prepare('SELECT q.*, l.id AS lesson_id, l.title AS lesson_title, l.subject_id FROM quizzes q JOIN lessons l ON l.id = q.lesson_id WHERE q.id = :qid');
        $stmt->execute([':qid' => $quizId]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Quiz not found.']);
            exit;
        }

        // Check attempt count
        $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM student_quiz_attempts WHERE student_id = :stid AND quiz_id = :qid');
        $stmt->execute([':stid' => $studentId, ':qid' => $quizId]);
        $attemptNum = (int) $stmt->fetch()['cnt'] + 1;

        if ((int) $quiz['max_attempts'] > 0 && $attemptNum > (int) $quiz['max_attempts']) {
            $db->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Maximum attempts reached.']);
            exit;
        }

        // Grade the quiz
        $stmt = $db->prepare('SELECT qq.id, qq.points, qa.id AS correct_answer_id FROM quiz_questions qq JOIN quiz_answers qa ON qa.question_id = qq.id AND qa.is_correct = 1 WHERE qq.quiz_id = :qid');
        $stmt->execute([':qid' => $quizId]);
        $correctMap = [];
        $pointsMap = [];
        foreach ($stmt->fetchAll() as $row) {
            $correctMap[(int) $row['id']] = (int) $row['correct_answer_id'];
            $pointsMap[(int) $row['id']] = (int) $row['points'];
        }

        $score = 0;
        $maxScore = 0;
        $results = [];

        foreach ($correctMap as $qId => $correctAnswerId) {
            $maxScore += $pointsMap[$qId];
            $studentAnswer = isset($answers[$qId]) ? (int) $answers[$qId] : (isset($answers[(string)$qId]) ? (int) $answers[(string)$qId] : 0);
            $isCorrect = ($studentAnswer === $correctAnswerId);
            if ($isCorrect) {
                $score += $pointsMap[$qId];
            }

            // Get explanation
            $stmt = $db->prepare('SELECT explanation FROM quiz_questions WHERE id = :qid');
            $stmt->execute([':qid' => $qId]);
            $explanation = $stmt->fetch()['explanation'] ?? '';

            // Get correct answer text
            $stmt = $db->prepare('SELECT answer_text FROM quiz_answers WHERE id = :aid');
            $stmt->execute([':aid' => $correctAnswerId]);
            $correctText = $stmt->fetch()['answer_text'] ?? '';

            $results[] = [
                'questionId'    => $qId,
                'correct'       => $isCorrect,
                'correctAnswer' => $correctText,
                'explanation'   => $explanation,
            ];
        }

        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;
        $passed = $percentage >= (int) $quiz['pass_percentage'];

        // Check if first time passing
        $stmt = $db->prepare('SELECT MAX(passed) AS ever_passed FROM student_quiz_attempts WHERE student_id = :stid AND quiz_id = :qid');
        $stmt->execute([':stid' => $studentId, ':qid' => $quizId]);
        $wasPreviouslyPassed = (bool) ($stmt->fetch()['ever_passed'] ?? 0);

        // XP: only award on first pass
        $xpAwarded = 0;
        if ($passed && !$wasPreviouslyPassed) {
            $xpAwarded = (int) $quiz['xp_reward'];

            // Performance bonus
            if ($percentage >= 100) $xpAwarded = (int) round($xpAwarded * 1.5);
            elseif ($percentage >= 90) $xpAwarded = (int) round($xpAwarded * 1.25);

            // Record XP
            $db->prepare("INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description) VALUES (:sid, :xp, 'quiz', :qid, :desc)")
               ->execute([':sid' => $studentId, ':xp' => $xpAwarded, ':qid' => $quizId, ':desc' => 'Quiz passed: ' . $quiz['title'] . ' (' . round($percentage) . '%)']);

            $db->prepare('UPDATE student_gamification SET total_xp = total_xp + :xp WHERE student_id = :sid')
               ->execute([':xp' => $xpAwarded, ':sid' => $studentId]);

            // Update subject XP
            $db->prepare('UPDATE student_subject_progress SET total_xp_earned = total_xp_earned + :xp WHERE student_id = :stid AND subject_id = :sid')
               ->execute([':xp' => $xpAwarded, ':stid' => $studentId, ':sid' => (int) $quiz['subject_id']]);
        }

        // Save attempt
        $db->prepare("
            INSERT INTO student_quiz_attempts (student_id, quiz_id, attempt_number, score, max_score, percentage, passed, time_spent_sec, xp_earned, answers_json, completed_at)
            VALUES (:stid, :qid, :anum, :score, :max, :pct, :pass, :time, :xp, :ans, NOW())
        ")->execute([
            ':stid'  => $studentId,
            ':qid'   => $quizId,
            ':anum'  => $attemptNum,
            ':score' => $score,
            ':max'   => $maxScore,
            ':pct'   => $percentage,
            ':pass'  => $passed ? 1 : 0,
            ':time'  => $timeSpent,
            ':xp'    => $xpAwarded,
            ':ans'   => json_encode($answers),
        ]);

        $db->commit();

        // ── Log behavioral engagement indicators ────────────────────────────────────
        log_behavior($db, $studentId, 'engagement', 'task_completion_rate', (string) $percentage, 'system', null, null, 'quiz');
        if ($timeSpent > 0) {
            log_behavior($db, $studentId, 'engagement', 'time_on_task', (string) $timeSpent, 'system', null, null, 'quiz');
        }
        $totalQuestions = count($correctMap);
        if ($totalQuestions > 0) {
            $responseRatePct = round((count($answers) / $totalQuestions) * 100, 2);
            log_behavior($db, $studentId, 'engagement', 'response_rate', (string) $responseRatePct, 'system', null, null, 'quiz');
        }
        if ($xpAwarded > 0) {
            log_behavior($db, $studentId, 'engagement', 'exp_accumulation_rate', (string) $xpAwarded, 'system', null, null, 'quiz');
        }
        log_behavior($db, $studentId, 'engagement', 'module_attempt_frequency', (string) $attemptNum, 'system', null, null, 'quiz');

        $message = $passed
            ? 'Quiz passed! ' . round($percentage) . '% correct.'
            : 'Quiz not passed. You need ' . $quiz['pass_percentage'] . '% to pass.';

        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => [
                'score'        => $score,
                'maxScore'     => $maxScore,
                'percentage'   => $percentage,
                'passed'       => $passed,
                'xpAwarded'    => $xpAwarded,
                'attemptNumber'=> $attemptNum,
                'results'      => $results,
            ],
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to submit quiz.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
