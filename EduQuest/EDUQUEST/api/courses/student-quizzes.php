<?php
/**
 * Student Quiz API — take & submit teacher-created quizzes
 *
 * GET  ?action=list                    — list assigned quizzes for student
 * GET  ?action=get&id=X               — get quiz questions (no correct answers)
 * POST action=submit                  — submit answers, get grade
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireAuth();
$db   = getDBConnection();

// Resolve student
$stmt = $db->prepare('SELECT id, teacher_id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) jsonResponse(false, 'Student profile not found.', [], 404);

$studentId = (int) $student['id'];
$teacherId = (int) $student['teacher_id'];

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') { listStudentQuizzes(); }
    elseif ($action === 'get') { getStudentQuiz(); }
    elseif ($action === 'review') { reviewAttempt(); }
    else { jsonResponse(false, 'Invalid action.', [], 400); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';
    if ($action === 'submit') { submitQuiz($body); }
    else { jsonResponse(false, 'Invalid action.', [], 400); }
}

/* ═══════════════════════════════════════════════════════════
   LIST — quizzes assigned to this student
   ═══════════════════════════════════════════════════════════ */
function listStudentQuizzes() {
    global $db, $studentId, $teacherId;

    // Find quizzes the student can access:
    // - explicitly assigned to student
    // - assigned to one of their enrolled courses (student_id IS NULL)
    // - or no assignments exist for that quiz (global default visibility)
    $sql = "SELECT q.id, q.title, q.description, q.instructions,
                q.pass_percentage, q.max_attempts, q.time_limit_sec, q.xp_reward,
                q.shuffle_questions, q.shuffle_answers, q.is_active,
                q.created_at, q.updated_at,
                c.title AS course_title, c.id AS course_id,
                (SELECT COUNT(*) FROM teacher_quiz_questions WHERE quiz_id = q.id) AS question_count,
                (SELECT COUNT(*) FROM teacher_quiz_attempts WHERE quiz_id = q.id AND student_id = :sid1) AS my_attempts,
                (SELECT MAX(percentage) FROM teacher_quiz_attempts WHERE quiz_id = q.id AND student_id = :sid2) AS best_score,
                (SELECT MAX(passed) FROM teacher_quiz_attempts WHERE quiz_id = q.id AND student_id = :sid3) AS ever_passed,
                (
                    SELECT MIN(a.due_date)
                    FROM teacher_quiz_assignments a
                    WHERE a.quiz_id = q.id
                      AND (
                          a.student_id = :sid4
                          OR (a.student_id IS NULL AND a.course_id IN (
                              SELECT course_id FROM course_enrollments WHERE student_id = :sid5
                          ))
                      )
                ) AS due_date
            FROM teacher_quizzes q
            LEFT JOIN courses c ON c.id = q.course_id
            WHERE q.is_active = 1
              AND q.teacher_id = :tid
              AND (
                  EXISTS (
                      SELECT 1
                      FROM teacher_quiz_assignments a
                      WHERE a.quiz_id = q.id
                        AND (
                            a.student_id = :sid6
                            OR (a.student_id IS NULL AND a.course_id IN (
                                SELECT course_id FROM course_enrollments WHERE student_id = :sid7
                            ))
                        )
                  )
                  OR NOT EXISTS (
                      SELECT 1 FROM teacher_quiz_assignments a2 WHERE a2.quiz_id = q.id
                  )
              )
            ORDER BY q.updated_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':sid1' => $studentId,
        ':sid2' => $studentId,
        ':sid3' => $studentId,
        ':tid'  => $teacherId,
        ':sid4' => $studentId,
        ':sid5' => $studentId,
        ':sid6' => $studentId,
        ':sid7' => $studentId,
    ]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'Quizzes fetched.', ['quizzes' => $quizzes]);
}

/* ═══════════════════════════════════════════════════════════
   GET — quiz questions for taking (no correct answers)
   ═══════════════════════════════════════════════════════════ */
function getStudentQuiz() {
    global $db, $studentId, $teacherId;

    $quizId = (int) ($_GET['id'] ?? 0);
    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    // Get quiz only if accessible to this student
    $stmt = $db->prepare('SELECT q.*
        FROM teacher_quizzes q
        WHERE q.id = :id
          AND q.teacher_id = :tid
          AND q.is_active = 1
          AND (
              EXISTS (
                  SELECT 1 FROM teacher_quiz_assignments a
                  WHERE a.quiz_id = q.id
                    AND (
                        a.student_id = :sid1
                        OR (a.student_id IS NULL AND a.course_id IN (
                            SELECT course_id FROM course_enrollments WHERE student_id = :sid2
                        ))
                    )
              )
              OR NOT EXISTS (
                  SELECT 1 FROM teacher_quiz_assignments a2 WHERE a2.quiz_id = q.id
              )
          )
        LIMIT 1');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId, ':sid1' => $studentId, ':sid2' => $studentId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) jsonResponse(false, 'Quiz not found or not assigned to you.', [], 404);

    // Check attempt limit
    $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM teacher_quiz_attempts WHERE student_id = :sid AND quiz_id = :qid');
    $stmt->execute([':sid' => $studentId, ':qid' => $quizId]);
    $attemptCount = (int) $stmt->fetch()['cnt'];

    if ((int) $quiz['max_attempts'] > 0 && $attemptCount >= (int) $quiz['max_attempts']) {
        $stmt = $db->prepare('SELECT MAX(passed) AS ever_passed FROM teacher_quiz_attempts WHERE student_id = :sid AND quiz_id = :qid');
        $stmt->execute([':sid' => $studentId, ':qid' => $quizId]);
        $passed = (bool) $stmt->fetch()['ever_passed'];
        if (!$passed) {
            jsonResponse(false, 'Maximum attempts reached.', [], 403);
        }
    }

    // Get questions
    $stmt = $db->prepare('SELECT id, question_order, question_type, question_text, question_image, points FROM teacher_quiz_questions WHERE quiz_id = :qid ORDER BY question_order ASC');
    $stmt->execute([':qid' => $quizId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Shuffle questions if enabled
    if ((int) $quiz['shuffle_questions']) {
        shuffle($questions);
    }

    $shuffleAnswers = (int) $quiz['shuffle_answers'];

    foreach ($questions as &$q) {
        $type = $q['question_type'];

        if ($type === 'fill_blank') {
            // For fill_blank, no answers sent to client
            $q['answers'] = [];
        } elseif ($type === 'matching') {
            // Send left items and right items separately, right side shuffled
            $stmt = $db->prepare('SELECT id, answer_text, answer_image, match_target FROM teacher_quiz_answers WHERE question_id = :qid ORDER BY answer_order ASC');
            $stmt->execute([':qid' => $q['id']]);
            $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $leftItems = [];
            $rightItems = [];
            foreach ($pairs as $p) {
                $leftItems[] = ['id' => (int)$p['id'], 'text' => $p['answer_text'], 'image' => $p['answer_image']];
                $rightItems[] = $p['match_target'];
            }
            if ($shuffleAnswers) shuffle($rightItems);
            $q['leftItems'] = $leftItems;
            $q['rightItems'] = $rightItems;
            $q['answers'] = [];
        } elseif ($type === 'drag_drop') {
            // Send items and drop zones
            $stmt = $db->prepare('SELECT id, answer_text, answer_image, match_target FROM teacher_quiz_answers WHERE question_id = :qid ORDER BY answer_order ASC');
            $stmt->execute([':qid' => $q['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $dragItems = [];
            $dropZones = [];
            foreach ($items as $item) {
                $dragItems[] = ['id' => (int)$item['id'], 'text' => $item['answer_text'], 'image' => $item['answer_image']];
                if ($item['match_target'] && !in_array($item['match_target'], $dropZones)) {
                    $dropZones[] = $item['match_target'];
                }
            }
            if ($shuffleAnswers) shuffle($dragItems);
            $q['dragItems'] = $dragItems;
            $q['dropZones'] = $dropZones;
            $q['answers'] = [];
        } else {
            // multiple_choice, choose_from_box
            $stmt = $db->prepare('SELECT id, answer_text, answer_image, answer_order FROM teacher_quiz_answers WHERE question_id = :qid ORDER BY answer_order ASC');
            $stmt->execute([':qid' => $q['id']]);
            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($shuffleAnswers) shuffle($answers);

            $q['answers'] = array_map(function($a) {
                return [
                    'id'    => (int)$a['id'],
                    'text'  => $a['answer_text'],
                    'image' => $a['answer_image'],
                ];
            }, $answers);
        }
    }

    jsonResponse(true, 'Quiz loaded.', [
        'quiz' => [
            'id'                => (int) $quiz['id'],
            'title'             => $quiz['title'],
            'description'       => $quiz['description'],
            'instructions'      => $quiz['instructions'],
            'passPercentage'    => (int) $quiz['pass_percentage'],
            'maxAttempts'       => (int) $quiz['max_attempts'],
            'timeLimitSec'      => (int) $quiz['time_limit_sec'],
            'xpReward'          => (int) $quiz['xp_reward'],
            'showScore'         => (bool) ($quiz['show_score'] ?? 1),
            'questionCount'     => count($questions),
            'attemptsSoFar'     => $attemptCount,
        ],
        'questions' => $questions,
    ]);
}

/* ═══════════════════════════════════════════════════════════
   SUBMIT — grade the quiz
   ═══════════════════════════════════════════════════════════ */
function submitQuiz($body) {
    global $db, $studentId, $teacherId;

    $quizId   = (int) ($body['quizId'] ?? 0);
    $answers  = $body['answers'] ?? [];
    $timeSpent = (int) ($body['timeSpent'] ?? 0);

    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    // Get quiz only if accessible to this student
    $stmt = $db->prepare('SELECT q.*
        FROM teacher_quizzes q
        WHERE q.id = :id
          AND q.teacher_id = :tid
          AND q.is_active = 1
          AND (
              EXISTS (
                  SELECT 1 FROM teacher_quiz_assignments a
                  WHERE a.quiz_id = q.id
                    AND (
                        a.student_id = :sid1
                        OR (a.student_id IS NULL AND a.course_id IN (
                            SELECT course_id FROM course_enrollments WHERE student_id = :sid2
                        ))
                    )
              )
              OR NOT EXISTS (
                  SELECT 1 FROM teacher_quiz_assignments a2 WHERE a2.quiz_id = q.id
              )
          )
        LIMIT 1');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId, ':sid1' => $studentId, ':sid2' => $studentId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) jsonResponse(false, 'Quiz not found or not assigned to you.', [], 404);

    // Check attempt limit
    $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM teacher_quiz_attempts WHERE student_id = :sid AND quiz_id = :qid');
    $stmt->execute([':sid' => $studentId, ':qid' => $quizId]);
    $attemptCount = (int) $stmt->fetch()['cnt'];

    if ((int)$quiz['max_attempts'] > 0 && $attemptCount >= (int)$quiz['max_attempts']) {
        jsonResponse(false, 'Maximum attempts reached.', [], 403);
    }

    // Get all questions with correct answers
    $stmt = $db->prepare('SELECT * FROM teacher_quiz_questions WHERE quiz_id = :qid ORDER BY question_order ASC');
    $stmt->execute([':qid' => $quizId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $score = 0;
    $maxScore = 0;
    $results = [];

    foreach ($questions as $q) {
        $qId = (int) $q['id'];
        $points = (int) $q['points'];
        $maxScore += $points;
        $type = $q['question_type'];
        $studentAnswer = $answers[$qId] ?? null;
        $correct = false;

        // Get correct answers
        $stmt = $db->prepare('SELECT * FROM teacher_quiz_answers WHERE question_id = :qid ORDER BY answer_order ASC');
        $stmt->execute([':qid' => $qId]);
        $dbAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        switch ($type) {
            case 'multiple_choice':
                // studentAnswer = answer_id
                $answerId = (int) ($studentAnswer ?? 0);
                foreach ($dbAnswers as $a) {
                    if ((int)$a['id'] === $answerId && (int)$a['is_correct'] === 1) {
                        $correct = true;
                        break;
                    }
                }
                break;

            case 'choose_from_box':
                // studentAnswer = answer_id (selected from box)
                $answerId = (int) ($studentAnswer ?? 0);
                foreach ($dbAnswers as $a) {
                    if ((int)$a['id'] === $answerId && (int)$a['is_correct'] === 1) {
                        $correct = true;
                        break;
                    }
                }
                break;

            case 'fill_blank':
                // studentAnswer = text string
                $studentText = strtolower(trim((string)($studentAnswer ?? '')));
                foreach ($dbAnswers as $a) {
                    if ((int)$a['is_correct'] === 1 && strtolower(trim($a['answer_text'])) === $studentText) {
                        $correct = true;
                        break;
                    }
                }
                break;

            case 'drag_drop':
                // studentAnswer = { answerId: dropZone, ... }
                if (is_array($studentAnswer)) {
                    $allCorrect = true;
                    $matchCount = 0;
                    foreach ($dbAnswers as $a) {
                        $aId = (string) $a['id'];
                        if (isset($studentAnswer[$aId])) {
                            $matchCount++;
                            if (strtolower(trim($studentAnswer[$aId])) !== strtolower(trim($a['match_target']))) {
                                $allCorrect = false;
                            }
                        } else {
                            $allCorrect = false;
                        }
                    }
                    $correct = $allCorrect && $matchCount === count($dbAnswers);
                }
                break;

            case 'matching':
                // studentAnswer = { answerId: matchTarget, ... }
                if (is_array($studentAnswer)) {
                    $allCorrect = true;
                    $matchCount = 0;
                    foreach ($dbAnswers as $a) {
                        $aId = (string) $a['id'];
                        if (isset($studentAnswer[$aId])) {
                            $matchCount++;
                            if (strtolower(trim($studentAnswer[$aId])) !== strtolower(trim($a['match_target']))) {
                                $allCorrect = false;
                            }
                        } else {
                            $allCorrect = false;
                        }
                    }
                    $correct = $allCorrect && $matchCount === count($dbAnswers);
                }
                break;
        }

        if ($correct) $score += $points;

        $results[] = [
            'questionId'    => $qId,
            'correct'       => $correct,
            'pointsEarned'  => $correct ? $points : 0,
            'pointsPossible' => $points,
            'explanation'   => $q['explanation'],
        ];
    }

    $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;
    $passed = $percentage >= (int) $quiz['pass_percentage'];

    // Check if already passed before (for XP)
    $stmt = $db->prepare('SELECT MAX(passed) AS ever_passed FROM teacher_quiz_attempts WHERE student_id = :sid AND quiz_id = :qid');
    $stmt->execute([':sid' => $studentId, ':qid' => $quizId]);
    $alreadyPassed = (bool) ($stmt->fetch()['ever_passed'] ?? false);

    // Calculate XP
    $xpEarned = 0;
    if ($passed && !$alreadyPassed) {
        $xpEarned = (int) $quiz['xp_reward'];
        if ($percentage == 100) $xpEarned = (int) round($xpEarned * 1.5);
        elseif ($percentage >= 90) $xpEarned = (int) round($xpEarned * 1.25);
    }

    // Save attempt
    $stmt = $db->prepare('INSERT INTO teacher_quiz_attempts
        (student_id, quiz_id, attempt_number, score, max_score, percentage, passed, time_spent_sec, xp_earned, answers_json, completed_at)
        VALUES (:sid, :qid, :anum, :score, :max, :pct, :pass, :time, :xp, :ans, NOW())');
    $stmt->execute([
        ':sid'   => $studentId,
        ':qid'   => $quizId,
        ':anum'  => $attemptCount + 1,
        ':score' => $score,
        ':max'   => $maxScore,
        ':pct'   => $percentage,
        ':pass'  => $passed ? 1 : 0,
        ':time'  => $timeSpent,
        ':xp'    => $xpEarned,
        ':ans'   => json_encode($answers),
    ]);

    // Award XP if earned
    if ($xpEarned > 0) {
        try {
            // Update student gamification
            $stmt = $db->prepare('UPDATE student_gamification SET total_xp = total_xp + :xp, daily_xp_earned = daily_xp_earned + :xp2 WHERE student_id = :sid');
            $stmt->execute([':xp' => $xpEarned, ':xp2' => $xpEarned, ':sid' => $studentId]);

            // Record XP transaction
            $db->prepare('INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description, teacher_id) VALUES (:sid, :xp, :src, :srcid, :desc, :tid)')
               ->execute([
                   ':sid'   => $studentId,
                   ':xp'    => $xpEarned,
                   ':src'   => 'quiz',
                   ':srcid' => $quizId,
                   ':desc'  => 'Quiz: ' . $quiz['title'],
                   ':tid'   => $teacherId,
               ]);
        } catch (Exception $e) {
            // Non-fatal XP error
        }
    }

    // Record quiz grade in student_grades for grade analytics visibility
    try {
        $db->prepare('INSERT INTO student_grades
            (student_id, teacher_id, assessment_name, assessment_type, score, max_score, graded_at)
            VALUES (:sid, :tid, :name, :type, :score, :max, NOW())')
           ->execute([
               ':sid'   => $studentId,
               ':tid'   => $teacherId,
               ':name'  => $quiz['title'],
               ':type'  => 'quiz',
               ':score' => $score,
               ':max'   => $maxScore,
           ]);
    } catch (Exception $e) {
        // Non-fatal — grade analytics will be incomplete but quiz result is already saved
    }

    jsonResponse(true, 'Quiz submitted.', [
        'score'         => $score,
        'maxScore'      => $maxScore,
        'percentage'    => $percentage,
        'passed'        => $passed,
        'xpEarned'      => $xpEarned,
        'showScore'     => (bool) ($quiz['show_score'] ?? 1),
        'results'       => ($quiz['show_score'] ?? 1) ? $results : [],
        'attemptNumber' => $attemptCount + 1,
    ]);
}

/* ═══════════════════════════════════════════════════════════
   REVIEW — student views a past attempt with Q&A breakdown
   GET ?action=review&attempt_id=X
   ═══════════════════════════════════════════════════════════ */
function reviewAttempt() {
    global $db, $studentId;

    $attemptId = (int) ($_GET['attempt_id'] ?? 0);
    if ($attemptId <= 0) jsonResponse(false, 'attempt_id required.', [], 400);

    // Fetch attempt — must belong to this student
    $stmt = $db->prepare("
        SELECT ta.*,
               tq.title         AS quiz_title,
               tq.pass_percentage,
               COALESCE(tq.show_score, 1) AS show_score
        FROM teacher_quiz_attempts ta
        JOIN teacher_quizzes tq ON tq.id = ta.quiz_id
        WHERE ta.id = :aid AND ta.student_id = :sid
        LIMIT 1
    ");
    $stmt->execute([':aid' => $attemptId, ':sid' => $studentId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) jsonResponse(false, 'Attempt not found or access denied.', [], 404);

    $quizId       = (int) $attempt['quiz_id'];
    $showScore    = (bool) $attempt['show_score'];
    $savedAnswers = json_decode($attempt['answers_json'] ?? '{}', true) ?: [];

    // Get questions in original order
    $stmt = $db->prepare('SELECT * FROM teacher_quiz_questions WHERE quiz_id = :qid ORDER BY question_order ASC');
    $stmt->execute([':qid' => $quizId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reviewQuestions = [];
    foreach ($questions as $q) {
        $qId  = (int) $q['id'];
        $type = $q['question_type'];

        // Student's saved answer (keyed by either int or string)
        $studentAnswer = $savedAnswers[$qId] ?? $savedAnswers[(string)$qId] ?? null;

        // Fetch answer options for this question
        $stmt = $db->prepare('SELECT * FROM teacher_quiz_answers WHERE question_id = :qid ORDER BY answer_order ASC');
        $stmt->execute([':qid' => $qId]);
        $dbAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $correct        = false;
        $studentDisplay = '(no answer)';
        $correctDisplay = '';

        switch ($type) {
            case 'multiple_choice':
            case 'choose_from_box':
                $answerId    = (int) ($studentAnswer ?? 0);
                $correctAns  = null;
                $selectedAns = null;
                foreach ($dbAnswers as $a) {
                    if ((int) $a['is_correct'] === 1 && !$correctAns) $correctAns  = $a;
                    if ((int) $a['id']         === $answerId)          $selectedAns = $a;
                }
                $correctDisplay = $correctAns  ? $correctAns['answer_text']  : '';
                $studentDisplay = $selectedAns ? $selectedAns['answer_text'] : '(no answer)';
                $correct        = $selectedAns && (int) $selectedAns['is_correct'] === 1;
                break;

            case 'fill_blank':
                $studentDisplay = trim((string) ($studentAnswer ?? '')) ?: '(no answer)';
                $accepted       = array_filter($dbAnswers, fn($a) => (int) $a['is_correct'] === 1);
                $correctDisplay = implode(' / ', array_column(array_values($accepted), 'answer_text'));
                foreach ($accepted as $ca) {
                    if (strtolower(trim($studentDisplay)) === strtolower(trim($ca['answer_text']))) {
                        $correct = true;
                        break;
                    }
                }
                break;

            case 'drag_drop':
            case 'matching':
                if (is_array($studentAnswer)) {
                    $allOk    = true;
                    $stuLines = [];
                    $corLines = [];
                    foreach ($dbAnswers as $a) {
                        $aId    = (string) $a['id'];
                        $target = $a['match_target'];
                        $given  = isset($studentAnswer[$aId]) ? (string) $studentAnswer[$aId] : '(not placed)';
                        $pairOk = strtolower(trim($given)) === strtolower(trim($target));
                        if (!$pairOk) $allOk = false;
                        $icon       = $pairOk ? '✓' : '✗';
                        $stuLines[] = $a['answer_text'] . ' → ' . $given  . ' ' . $icon;
                        $corLines[] = $a['answer_text'] . ' → ' . $target;
                    }
                    $correct        = $allOk;
                    $studentDisplay = implode("\n", $stuLines);
                    $correctDisplay = implode("\n", $corLines);
                } else {
                    $correctDisplay = implode("\n", array_map(
                        fn($a) => $a['answer_text'] . ' → ' . $a['match_target'],
                        $dbAnswers
                    ));
                }
                break;
        }

        $reviewQuestions[] = [
            'id'             => $qId,
            'order'          => (int) $q['question_order'],
            'type'           => $type,
            'text'           => $q['question_text'],
            'image'          => $q['question_image'],
            'explanation'    => $q['explanation'],
            'points'         => (int) $q['points'],
            'correct'        => $correct,
            'student_answer' => $studentDisplay,
            'correct_answer' => $showScore ? $correctDisplay : null,
        ];
    }

    jsonResponse(true, 'Review loaded.', [
        'attempt' => [
            'id'             => (int) $attempt['id'],
            'attempt_number' => (int) $attempt['attempt_number'],
            'quiz_title'     => $attempt['quiz_title'],
            'score'          => (int) $attempt['score'],
            'max_score'      => (int) $attempt['max_score'],
            'percentage'     => (float) $attempt['percentage'],
            'passed'         => (bool) $attempt['passed'],
            'xp_earned'      => (int) $attempt['xp_earned'],
            'time_spent_sec' => (int) $attempt['time_spent_sec'],
            'completed_at'   => $attempt['completed_at'],
            'show_score'     => $showScore,
        ],
        'questions' => $reviewQuestions,
    ]);
}
