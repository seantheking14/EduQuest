<?php
/**
 * Teacher Quiz CRUD API
 *
 * GET    ?action=list                         — list teacher's quizzes
 * GET    ?action=get&id=X                     — get single quiz with questions
 * GET    ?action=results&id=X                 — get quiz attempt analytics
 * GET    ?action=attempt_detail&attempt_id=X  — get full submission for grading
 * POST   action=create                        — create quiz + questions
 * POST   action=update                        — update quiz + questions
 * POST   action=delete                        — delete quiz
 * POST   action=duplicate                     — duplicate a quiz
 * POST   action=toggle                        — toggle active/inactive
 * POST   action=assign                        — assign quiz to course/students
 * POST   action=grade_override                — teacher override score for an attempt
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if (!function_exists('emitJsonError')) {
    function emitJsonError($message, $status = 500) {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => [],
        ]);
        exit;
    }
}

set_exception_handler(function (Throwable $e) {
    error_log('quizzes.php exception: ' . $e->getMessage());
    emitJsonError('Server error. Please try again.', 500);
});

register_shutdown_function(function () {
    $lastError = error_get_last();
    if (!$lastError) return;

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (in_array($lastError['type'], $fatalTypes, true)) {
        error_log('quizzes.php fatal: ' . $lastError['message'] . ' at ' . $lastError['file'] . ':' . $lastError['line']);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error. Please try again.',
            'data' => [],
        ]);
    }
});

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireTeacher();
$db   = getDBConnection();
$teacherId = (int) $user['id'];

$rawInput = file_get_contents('php://input');
$inputBody = [];
if (is_string($rawInput) && $rawInput !== '') {
    $decodedInput = json_decode($rawInput, true);
    if (is_array($decodedInput)) {
        $inputBody = $decodedInput;
    }
}

$action = $_GET['action'] ?? ($_POST['action'] ?? ($inputBody['action'] ?? ''));

// ── GET routes ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') { listQuizzes(); }
    elseif ($action === 'get') { getQuiz(); }
    elseif ($action === 'results') { getQuizResults(); }
    elseif ($action === 'attempt_detail') { getAttemptDetail(); }
    else { jsonResponse(false, 'Invalid action.', [], 400); }
}

// ── POST routes ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = $inputBody;
    if (!$action && isset($body['action'])) $action = $body['action'];

    switch ($action) {
        case 'create':    createQuiz($body); break;
        case 'update':    updateQuiz($body); break;
        case 'delete':    deleteQuiz($body); break;
        case 'duplicate': duplicateQuiz($body); break;
        case 'toggle':    toggleQuiz($body); break;
        case 'assign':         assignQuiz($body); break;
        case 'reset_attempts': resetQuizAttempts($body); break;
        case 'grade_override':  gradeOverride($body); break;
        default: jsonResponse(false, 'Invalid action.', [], 400);
    }
}

/* ═══════════════════════════════════════════════════════════
   LIST — all quizzes for this teacher
   ═══════════════════════════════════════════════════════════ */
function listQuizzes() {
    global $db, $teacherId;

    $search = sanitizeString($_GET['search'] ?? '');
    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

    $sql = 'SELECT q.*, c.title AS course_title,
            (SELECT COUNT(*) FROM teacher_quiz_questions WHERE quiz_id = q.id) AS question_count,
            (SELECT COUNT(DISTINCT student_id) FROM teacher_quiz_attempts WHERE quiz_id = q.id) AS attempt_count
            FROM teacher_quizzes q
            LEFT JOIN courses c ON c.id = q.course_id
            WHERE q.teacher_id = :tid';
    $params = [':tid' => $teacherId];

    if ($courseId > 0) {
        $sql .= ' AND q.course_id = :cid';
        $params[':cid'] = $courseId;
    }
    if ($search) {
        $sql .= ' AND q.title LIKE :search';
        $params[':search'] = "%$search%";
    }

    $sql .= ' ORDER BY q.updated_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'Quizzes fetched.', ['quizzes' => $quizzes]);
}

/* ═══════════════════════════════════════════════════════════
   GET — single quiz with all questions & answers
   ═══════════════════════════════════════════════════════════ */
function getQuiz() {
    global $db, $teacherId;

    $quizId = (int) ($_GET['id'] ?? 0);
    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    $stmt = $db->prepare('SELECT * FROM teacher_quizzes WHERE id = :id AND teacher_id = :tid');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) jsonResponse(false, 'Quiz not found.', [], 404);

    // Get questions
    $stmt = $db->prepare('SELECT * FROM teacher_quiz_questions WHERE quiz_id = :qid ORDER BY question_order ASC');
    $stmt->execute([':qid' => $quizId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get answers for each question
    foreach ($questions as &$q) {
        $stmt = $db->prepare('SELECT * FROM teacher_quiz_answers WHERE question_id = :qid ORDER BY answer_order ASC');
        $stmt->execute([':qid' => $q['id']]);
        $q['answers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $quiz['questions'] = $questions;

    // Get assignments — include student name so the indicator panels can display it
    $stmt = $db->prepare(
        'SELECT a.*, c.title AS course_title,
                s.first_name AS student_first_name, s.last_name AS student_last_name
         FROM teacher_quiz_assignments a
         LEFT JOIN courses  c ON c.id = a.course_id
         LEFT JOIN students s ON s.id = a.student_id
         WHERE a.quiz_id = :qid
         ORDER BY (a.student_id IS NULL) DESC, a.id ASC'
    );
    $stmt->execute([':qid' => $quizId]);
    $quiz['assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'Quiz fetched.', ['quiz' => $quiz]);
}

/* ═══════════════════════════════════════════════════════════
   RESULTS — quiz analytics + attempt details
   ═══════════════════════════════════════════════════════════ */
function getQuizResults() {
    global $db, $teacherId;

    $quizId = (int) ($_GET['id'] ?? 0);
    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    $stmt = $db->prepare('SELECT id, title, pass_percentage FROM teacher_quizzes WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) jsonResponse(false, 'Quiz not found.', [], 404);

    // Attempts list (most recent first)
    $stmt = $db->prepare('SELECT
            a.id,
            a.student_id,
            a.attempt_number,
            a.score,
            a.max_score,
            a.percentage,
            a.passed,
            a.time_spent_sec,
            a.xp_earned,
            a.completed_at,
            s.first_name,
            s.last_name,
            s.grade_level
        FROM teacher_quiz_attempts a
        JOIN students s ON s.id = a.student_id
        WHERE a.quiz_id = :qid
          AND s.teacher_id = :tid
        ORDER BY a.completed_at DESC, a.id DESC');
    $stmt->execute([':qid' => $quizId, ':tid' => $teacherId]);
    $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'attemptCount' => count($attempts),
        'studentCount' => 0,
        'passCount' => 0,
        'avgScore' => 0,
        'bestScore' => 0,
    ];

    if (!empty($attempts)) {
        $studentSet = [];
        $sumPct = 0;
        $bestPct = 0;
        $passCount = 0;

        foreach ($attempts as $a) {
            $sid = (int) $a['student_id'];
            $studentSet[$sid] = true;

            $pct = (float) $a['percentage'];
            $sumPct += $pct;
            if ($pct > $bestPct) $bestPct = $pct;
            if ((int) $a['passed'] === 1) $passCount++;
        }

        $summary['studentCount'] = count($studentSet);
        $summary['passCount'] = $passCount;
        $summary['avgScore'] = round($sumPct / count($attempts), 2);
        $summary['bestScore'] = round($bestPct, 2);
    }

    jsonResponse(true, 'Quiz results fetched.', [
        'quiz' => [
            'id' => (int) $quiz['id'],
            'title' => $quiz['title'],
            'passPercentage' => (int) $quiz['pass_percentage'],
        ],
        'summary' => $summary,
        'attempts' => $attempts,
    ]);
}

/* ═══════════════════════════════════════════════════════════
   CREATE — new quiz with questions
   ═══════════════════════════════════════════════════════════ */
function createQuiz($body) {
    global $db, $teacherId;

    $title = sanitizeString($body['title'] ?? '');
    if (!$title) jsonResponse(false, 'Quiz title is required.', [], 422);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO teacher_quizzes
            (teacher_id, course_id, title, description, instructions, pass_percentage, max_attempts, time_limit_sec, shuffle_questions, shuffle_answers, xp_reward, show_score)
            VALUES (:tid, :cid, :title, :desc, :instr, :pass, :attempts, :time, :shufq, :shufa, :xp, :show_score)');

        $stmt->execute([
            ':tid'        => $teacherId,
            ':cid'        => !empty($body['course_id']) ? (int)$body['course_id'] : null,
            ':title'      => $title,
            ':desc'       => sanitizeString($body['description'] ?? ''),
            ':instr'      => sanitizeString($body['instructions'] ?? ''),
            ':pass'       => (int)($body['pass_percentage'] ?? 70),
            ':attempts'   => (int)($body['max_attempts'] ?? 0),
            ':time'       => (int)($body['time_limit_sec'] ?? 0),
            ':shufq'      => (int)($body['shuffle_questions'] ?? 1),
            ':shufa'      => (int)($body['shuffle_answers'] ?? 1),
            ':xp'         => (int)($body['xp_reward'] ?? 50),
            ':show_score' => isset($body['show_score']) ? (int)(bool)$body['show_score'] : 1,
        ]);

        $quizId = (int) $db->lastInsertId();

        // Insert questions
        if (!empty($body['questions']) && is_array($body['questions'])) {
            insertQuestions($quizId, $body['questions']);
        }

        $db->commit();
        jsonResponse(true, 'Quiz created successfully.', ['quizId' => $quizId], 201);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to create quiz: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   UPDATE — quiz + replace questions
   ═══════════════════════════════════════════════════════════ */
function updateQuiz($body) {
    global $db, $teacherId;

    $quizId = (int) ($body['id'] ?? 0);
    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM teacher_quizzes WHERE id = :id AND teacher_id = :tid');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId]);
    if (!$stmt->fetch()) jsonResponse(false, 'Quiz not found.', [], 404);

    $title = sanitizeString($body['title'] ?? '');
    if (!$title) jsonResponse(false, 'Quiz title is required.', [], 422);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('UPDATE teacher_quizzes SET
            course_id = :cid, title = :title, description = :desc, instructions = :instr,
            pass_percentage = :pass, max_attempts = :attempts, time_limit_sec = :time,
            shuffle_questions = :shufq, shuffle_answers = :shufa, xp_reward = :xp,
            show_score = :show_score
            WHERE id = :id AND teacher_id = :tid');

        $stmt->execute([
            ':id'         => $quizId,
            ':tid'        => $teacherId,
            ':cid'        => !empty($body['course_id']) ? (int)$body['course_id'] : null,
            ':title'      => $title,
            ':desc'       => sanitizeString($body['description'] ?? ''),
            ':instr'      => sanitizeString($body['instructions'] ?? ''),
            ':pass'       => (int)($body['pass_percentage'] ?? 70),
            ':attempts'   => (int)($body['max_attempts'] ?? 0),
            ':time'       => (int)($body['time_limit_sec'] ?? 0),
            ':shufq'      => (int)($body['shuffle_questions'] ?? 1),
            ':shufa'      => (int)($body['shuffle_answers'] ?? 1),
            ':xp'         => (int)($body['xp_reward'] ?? 50),
            ':show_score' => isset($body['show_score']) ? (int)(bool)$body['show_score'] : 1,
        ]);

        // Replace questions: delete old, insert new
        if (isset($body['questions']) && is_array($body['questions'])) {
            $db->prepare('DELETE FROM teacher_quiz_questions WHERE quiz_id = :qid')->execute([':qid' => $quizId]);
            insertQuestions($quizId, $body['questions']);
        }

        $db->commit();
        jsonResponse(true, 'Quiz updated successfully.', ['quizId' => $quizId]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to update quiz: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   DELETE
   ═══════════════════════════════════════════════════════════ */
function deleteQuiz($body) {
    global $db, $teacherId;

    $quizId = (int) ($body['id'] ?? 0);
    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    $stmt = $db->prepare('DELETE FROM teacher_quizzes WHERE id = :id AND teacher_id = :tid');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId]);

    if ($stmt->rowCount() === 0) jsonResponse(false, 'Quiz not found.', [], 404);
    jsonResponse(true, 'Quiz deleted.');
}

/* ═══════════════════════════════════════════════════════════
   DUPLICATE
   ═══════════════════════════════════════════════════════════ */
function duplicateQuiz($body) {
    global $db, $teacherId;

    $quizId = (int) ($body['id'] ?? 0);
    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    $stmt = $db->prepare('SELECT * FROM teacher_quizzes WHERE id = :id AND teacher_id = :tid');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) jsonResponse(false, 'Quiz not found.', [], 404);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO teacher_quizzes
            (teacher_id, course_id, title, description, instructions, pass_percentage, max_attempts, time_limit_sec, shuffle_questions, shuffle_answers, xp_reward, show_score)
            VALUES (:tid, :cid, :title, :desc, :instr, :pass, :attempts, :time, :shufq, :shufa, :xp, :show_score)');

        $stmt->execute([
            ':tid'        => $teacherId,
            ':cid'        => $quiz['course_id'],
            ':title'      => $quiz['title'] . ' (Copy)',
            ':desc'       => $quiz['description'],
            ':instr'      => $quiz['instructions'],
            ':pass'       => $quiz['pass_percentage'],
            ':attempts'   => $quiz['max_attempts'],
            ':time'       => $quiz['time_limit_sec'],
            ':shufq'      => $quiz['shuffle_questions'],
            ':shufa'      => $quiz['shuffle_answers'],
            ':xp'         => $quiz['xp_reward'],
            ':show_score' => $quiz['show_score'] ?? 1,
        ]);

        $newQuizId = (int) $db->lastInsertId();

        // Copy questions
        $stmt = $db->prepare('SELECT * FROM teacher_quiz_questions WHERE quiz_id = :qid ORDER BY question_order ASC');
        $stmt->execute([':qid' => $quizId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($questions as $q) {
            $stmt = $db->prepare('INSERT INTO teacher_quiz_questions (quiz_id, question_order, question_type, question_text, question_image, explanation, points) VALUES (:qid, :ord, :type, :text, :img, :expl, :pts)');
            $stmt->execute([
                ':qid'  => $newQuizId,
                ':ord'  => $q['question_order'],
                ':type' => $q['question_type'],
                ':text' => $q['question_text'],
                ':img'  => $q['question_image'],
                ':expl' => $q['explanation'],
                ':pts'  => $q['points'],
            ]);
            $newQId = (int) $db->lastInsertId();

            // Copy answers
            $stmt2 = $db->prepare('SELECT * FROM teacher_quiz_answers WHERE question_id = :qid ORDER BY answer_order ASC');
            $stmt2->execute([':qid' => $q['id']]);
            $answers = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            foreach ($answers as $a) {
                $db->prepare('INSERT INTO teacher_quiz_answers (question_id, answer_text, answer_image, is_correct, match_target, answer_order) VALUES (:qid, :text, :img, :correct, :match, :ord)')
                    ->execute([
                        ':qid'     => $newQId,
                        ':text'    => $a['answer_text'],
                        ':img'     => $a['answer_image'],
                        ':correct' => $a['is_correct'],
                        ':match'   => $a['match_target'],
                        ':ord'     => $a['answer_order'],
                    ]);
            }
        }

        $db->commit();
        jsonResponse(true, 'Quiz duplicated.', ['quizId' => $newQuizId], 201);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to duplicate quiz: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   TOGGLE — active / inactive
   ═══════════════════════════════════════════════════════════ */
function toggleQuiz($body) {
    global $db, $teacherId;

    $quizId = (int) ($body['id'] ?? 0);
    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    $stmt = $db->prepare('UPDATE teacher_quizzes SET is_active = NOT is_active WHERE id = :id AND teacher_id = :tid');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId]);

    if ($stmt->rowCount() === 0) jsonResponse(false, 'Quiz not found.', [], 404);
    jsonResponse(true, 'Quiz status toggled.');
}

/* ═══════════════════════════════════════════════════════════
   ASSIGN — quiz to course / students
   ═══════════════════════════════════════════════════════════ */
function assignQuiz($body) {
    global $db, $teacherId;

    $quizId      = (int) ($body['quiz_id'] ?? 0);
    $courseId    = !empty($body['course_id']) ? (int) $body['course_id'] : null;
    $studentIds  = $body['student_ids'] ?? [];
    $dueDate     = !empty($body['due_date']) ? sanitizeString($body['due_date']) : null;
    $maxAttempts = max(0, (int) ($body['max_attempts'] ?? 0)); // 0 = inherit from quiz

    if ($quizId <= 0) jsonResponse(false, 'Quiz ID required.', [], 400);

    // Verify ownership and fetch quiz title for notifications
    $stmt = $db->prepare('SELECT id, title FROM teacher_quizzes WHERE id = :id AND teacher_id = :tid');
    $stmt->execute([':id' => $quizId, ':tid' => $teacherId]);
    $quizRow = $stmt->fetch();
    if (!$quizRow) jsonResponse(false, 'Quiz not found.', [], 404);
    $quizTitle = $quizRow['title'];

    $db->beginTransaction();
    try {
        // Remove old assignments for this quiz+course combo
        if ($courseId) {
            $db->prepare('DELETE FROM teacher_quiz_assignments WHERE quiz_id = :qid AND course_id = :cid')
               ->execute([':qid' => $quizId, ':cid' => $courseId]);
        }

        if (empty($studentIds)) {
            // Assign to entire course
            $db->prepare('INSERT INTO teacher_quiz_assignments (quiz_id, course_id, due_date, max_attempts) VALUES (:qid, :cid, :due, :max)')
               ->execute([':qid' => $quizId, ':cid' => $courseId, ':due' => $dueDate, ':max' => $maxAttempts]);
        } else {
            foreach ($studentIds as $sid) {
                $db->prepare('INSERT INTO teacher_quiz_assignments (quiz_id, course_id, student_id, due_date, max_attempts) VALUES (:qid, :cid, :sid, :due, :max)')
                   ->execute([':qid' => $quizId, ':cid' => $courseId, ':sid' => (int)$sid, ':due' => $dueDate, ':max' => $maxAttempts]);
            }
        }

        $db->commit();

        // ── Notify assigned students ──────────────────────────
        require_once __DIR__ . '/../notifications/send.php';
        $studentsToNotify = [];
        if (!empty($studentIds)) {
            $studentsToNotify = array_map('intval', $studentIds);
        } elseif ($courseId) {
            $enr = $db->prepare('SELECT student_id FROM course_enrollments WHERE course_id = :cid');
            $enr->execute([':cid' => $courseId]);
            $studentsToNotify = array_column($enr->fetchAll(), 'student_id');
        }
        foreach ($studentsToNotify as $sid) {
            send_notification($db, (int) $sid, 'student',
                'New quiz assigned: ' . $quizTitle,
                '../quests/take-quiz.html');
        }

        jsonResponse(true, 'Quiz assigned successfully.');
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Failed to assign quiz: ' . $e->getMessage(), [], 500);
    }
}

/* ═══════════════════════════════════════════════════════════
   RESET ATTEMPTS — teacher resets a student's quiz attempts
   ═══════════════════════════════════════════════════════════ */
function resetQuizAttempts($body) {
    global $db, $teacherId;

    $assignmentId = (int) ($body['assignment_id'] ?? 0);
    if ($assignmentId <= 0) jsonResponse(false, 'assignment_id required.', [], 400);

    // Verify ownership
    $check = $db->prepare("
        SELECT tqa.id, tqa.student_id, tqa.quiz_id
        FROM teacher_quiz_assignments tqa
        JOIN teacher_quizzes tq ON tq.id = tqa.quiz_id
        WHERE tqa.id = :aid AND tq.teacher_id = :tid
        LIMIT 1
    ");
    $check->execute([':aid' => $assignmentId, ':tid' => $teacherId]);
    $assignment = $check->fetch(PDO::FETCH_ASSOC);
    if (!$assignment) jsonResponse(false, 'Assignment not found or access denied.', [], 403);

    // Delete attempts linked to this assignment AND unlinked attempts for same student+quiz
    $del = $db->prepare("
        DELETE FROM teacher_quiz_attempts
        WHERE (assignment_id = :aid)
           OR (student_id = :sid AND quiz_id = :qid AND assignment_id IS NULL)
    ");
    $del->execute([
        ':aid' => $assignmentId,
        ':sid' => $assignment['student_id'],
        ':qid' => $assignment['quiz_id'],
    ]);

    jsonResponse(true, 'Quiz attempts reset.', ['deleted' => $del->rowCount()]);
}

/* ═══════════════════════════════════════════════════════════
   HELPER: Insert questions + answers into DB
   ═══════════════════════════════════════════════════════════ */
function insertQuestions(int $quizId, array $questions) {
    global $db;

    $validTypes = ['multiple_choice', 'fill_blank', 'drag_drop', 'matching', 'choose_from_box'];

    foreach ($questions as $idx => $q) {
        $type = $q['question_type'] ?? '';
        if (!in_array($type, $validTypes, true)) continue;

        $text = trim($q['question_text'] ?? '');
        if (!$text) continue;

        $stmt = $db->prepare('INSERT INTO teacher_quiz_questions
            (quiz_id, question_order, question_type, question_text, question_image, explanation, points)
            VALUES (:qid, :ord, :type, :text, :img, :expl, :pts)');

        $stmt->execute([
            ':qid'  => $quizId,
            ':ord'  => $idx + 1,
            ':type' => $type,
            ':text' => $text,
            ':img'  => sanitizeString($q['question_image'] ?? ''),
            ':expl' => sanitizeString($q['explanation'] ?? ''),
            ':pts'  => max(1, (int)($q['points'] ?? 1)),
        ]);

        $questionId = (int) $db->lastInsertId();

        // Insert answers
        if (!empty($q['answers']) && is_array($q['answers'])) {
            foreach ($q['answers'] as $aIdx => $a) {
                $ansText = trim($a['answer_text'] ?? '');
                if ($ansText === '') continue;

                $db->prepare('INSERT INTO teacher_quiz_answers
                    (question_id, answer_text, answer_image, is_correct, match_target, answer_order)
                    VALUES (:qid, :text, :img, :correct, :match, :ord)')
                    ->execute([
                        ':qid'     => $questionId,
                        ':text'    => $ansText,
                        ':img'     => sanitizeString($a['answer_image'] ?? ''),
                        ':correct' => (int)($a['is_correct'] ?? 0),
                        ':match'   => sanitizeString($a['match_target'] ?? ''),
                        ':ord'     => $aIdx + 1,
                    ]);
            }
        }
    }
}

/* ═══════════════════════════════════════════════════════════
   GET ATTEMPT DETAIL — full submission for teacher grading
   ═══════════════════════════════════════════════════════════ */
function getAttemptDetail() {
    global $db, $teacherId;

    $attemptId = (int) ($_GET['attempt_id'] ?? 0);
    if ($attemptId <= 0) jsonResponse(false, 'attempt_id required.', [], 400);

    // Fetch attempt + verify teacher ownership via quiz
    $stmt = $db->prepare("
        SELECT a.id, a.student_id, a.quiz_id, a.attempt_number,
               a.score, a.max_score, a.percentage, a.passed,
               a.xp_earned, a.answers_json, a.completed_at,
             TRIM(CONCAT(COALESCE(u.first_name, s.first_name, ''), ' ', COALESCE(u.last_name, s.last_name, ''))) AS student_name,
             s.grade_level,
               q.title AS quiz_title, q.pass_percentage
        FROM teacher_quiz_attempts a
        JOIN teacher_quizzes q ON q.id = a.quiz_id
        JOIN students s ON s.id = a.student_id
        JOIN users u ON u.id = s.user_id
        WHERE a.id = :aid AND q.teacher_id = :tid
        LIMIT 1
    ");
    $stmt->execute([':aid' => $attemptId, ':tid' => $teacherId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) jsonResponse(false, 'Attempt not found or access denied.', [], 403);

    $savedAnswers = json_decode($attempt['answers_json'] ?? '{}', true) ?: [];

    // Detect teacher override markers (keys prefixed _tg_)
    $isTeacherGraded  = isset($savedAnswers['_tg_score']);
    $teacherScore     = $isTeacherGraded ? (int)$savedAnswers['_tg_score']     : null;
    $teacherNotes     = $isTeacherGraded ? ($savedAnswers['_tg_notes'] ?? '')   : '';
    $teacherTimestamp = $isTeacherGraded ? ($savedAnswers['_tg_ts']   ?? '')    : '';

    // Fetch questions
    $qStmt = $db->prepare("
        SELECT id, question_order, question_type, question_text, question_image, explanation, points
        FROM teacher_quiz_questions
        WHERE quiz_id = :qid ORDER BY question_order
    ");
    $qStmt->execute([':qid' => $attempt['quiz_id']]);
    $rawQuestions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all answers for this quiz's questions
    $qIds = array_column($rawQuestions, 'id');
    $ansRows = [];
    if ($qIds) {
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $aStmt = $db->prepare("
            SELECT id, question_id, answer_text, answer_image, is_correct, match_target, answer_order
            FROM teacher_quiz_answers WHERE question_id IN ($placeholders)
            ORDER BY question_id, answer_order
        ");
        $aStmt->execute($qIds);
        foreach ($aStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ansRows[(int)$row['question_id']][] = $row;
        }
    }

    // Build per-question breakdown
    $questions = [];
    foreach ($rawQuestions as $q) {
        $qId      = (int)$q['id'];
        $qType    = $q['question_type'];
        $qAnswers = $ansRows[$qId] ?? [];
        $maxPts   = (int)$q['points'];

        // What the student submitted
        $studentRaw = $savedAnswers[$qId] ?? $savedAnswers[(string)$qId] ?? null;

        // Build display strings and determine correctness
        $studentDisplay = '';
        $correctDisplay = '';
        $isCorrect      = false;
        $ptsAwarded     = 0;

        if ($qType === 'multiple_choice' || $qType === 'choose_from_box') {
            $answerId = (int)$studentRaw;
            foreach ($qAnswers as $a) {
                if ((int)$a['id'] === $answerId) {
                    $studentDisplay = $a['answer_text'];
                    $isCorrect      = (bool)(int)$a['is_correct'];
                    break;
                }
            }
            $correctAnswers = array_filter($qAnswers, fn($a) => (int)$a['is_correct']);
            $correctDisplay = implode(', ', array_column(array_values($correctAnswers), 'answer_text'));

        } elseif ($qType === 'fill_blank') {
            $studentDisplay = is_string($studentRaw) ? $studentRaw : '';
            $correctAnswers = array_filter($qAnswers, fn($a) => (int)$a['is_correct']);
            $correctDisplay = implode(' / ', array_column(array_values($correctAnswers), 'answer_text'));
            // Case-insensitive match
            foreach ($correctAnswers as $ca) {
                if (strtolower(trim($studentDisplay)) === strtolower(trim($ca['answer_text']))) {
                    $isCorrect = true;
                    break;
                }
            }

        } elseif ($qType === 'drag_drop') {
            $submitted = is_array($studentRaw) ? $studentRaw : [];
            $lines = [];
            $correct = true;
            foreach ($qAnswers as $a) {
                $placed = $submitted[(string)$a['id']] ?? ($submitted[$a['id']] ?? null);
                $lines[] = $a['answer_text'] . ' → ' . ($placed ?? '(blank)');
                if ($placed !== $a['match_target']) $correct = false;
            }
            $studentDisplay = implode('; ', $lines);
            $correctLines = array_map(fn($a) => $a['answer_text'] . ' → ' . $a['match_target'], $qAnswers);
            $correctDisplay = implode('; ', $correctLines);
            $isCorrect = $correct && !empty($submitted);

        } elseif ($qType === 'matching') {
            $submitted = is_array($studentRaw) ? $studentRaw : [];
            $lines = [];
            $correct = true;
            foreach ($qAnswers as $a) {
                $placed = $submitted[(string)$a['id']] ?? ($submitted[$a['id']] ?? null);
                $lines[] = $a['answer_text'] . ' ↔ ' . ($placed ?? '(blank)');
                if ($placed !== $a['match_target']) $correct = false;
            }
            $studentDisplay = implode('; ', $lines);
            $correctLines = array_map(fn($a) => $a['answer_text'] . ' ↔ ' . $a['match_target'], $qAnswers);
            $correctDisplay = implode('; ', $correctLines);
            $isCorrect = $correct && !empty($submitted);
        }

        $ptsAwarded = $isCorrect ? $maxPts : 0;

        $questions[] = [
            'id'              => $qId,
            'order'           => (int)$q['question_order'],
            'type'            => $qType,
            'text'            => $q['question_text'],
            'image'           => $q['question_image'] ?? '',
            'explanation'     => $q['explanation'] ?? '',
            'max_points'      => $maxPts,
            'points_awarded'  => $ptsAwarded,
            'student_answer'  => $studentDisplay ?: (is_null($studentRaw) ? '(no answer)' : '(empty)'),
            'correct_answer'  => $correctDisplay,
            'is_correct'      => $isCorrect,
        ];
    }

    jsonResponse(true, 'OK', [
        'attempt' => [
            'id'                 => (int)$attempt['id'],
            'student_id'         => (int)$attempt['student_id'],
            'student_name'       => $attempt['student_name'],
            'grade_level'        => $attempt['grade_level'],
            'quiz_title'         => $attempt['quiz_title'],
            'pass_percentage'    => (float)$attempt['pass_percentage'],
            'attempt_number'     => (int)$attempt['attempt_number'],
            'score'              => (int)$attempt['score'],
            'max_score'          => (int)$attempt['max_score'],
            'percentage'         => round((float)$attempt['percentage'], 1),
            'passed'             => (bool)(int)$attempt['passed'],
            'xp_earned'          => (int)$attempt['xp_earned'],
            'completed_at'       => $attempt['completed_at'],
            'is_teacher_graded'  => $isTeacherGraded,
            'teacher_score'      => $teacherScore,
            'teacher_notes'      => $teacherNotes,
            'teacher_graded_at'  => $teacherTimestamp,
        ],
        'questions' => $questions,
    ]);
}

/* ═══════════════════════════════════════════════════════════
   GRADE OVERRIDE — teacher overrides the final score
   ═══════════════════════════════════════════════════════════ */
function gradeOverride($body) {
    global $db, $teacherId;

    $attemptId = (int)($body['attempt_id'] ?? 0);
    $newScore  = $body['new_score'] ?? null;
    $notes     = trim($body['notes'] ?? '');

    if ($attemptId <= 0) jsonResponse(false, 'attempt_id required.', [], 400);
    if ($newScore === null || $newScore === '') jsonResponse(false, 'new_score required.', [], 400);
    $newScore = (int)$newScore;

    // Verify teacher owns this attempt's quiz
    $stmt = $db->prepare("
        SELECT a.id, a.student_id, a.quiz_id, a.max_score, a.answers_json, a.xp_earned
        FROM teacher_quiz_attempts a
        JOIN teacher_quizzes q ON q.id = a.quiz_id
        WHERE a.id = :aid AND q.teacher_id = :tid
        LIMIT 1
    ");
    $stmt->execute([':aid' => $attemptId, ':tid' => $teacherId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) jsonResponse(false, 'Attempt not found or access denied.', [], 403);

    $maxScore = (int)$attempt['max_score'];
    if ($newScore < 0 || $newScore > $maxScore) {
        jsonResponse(false, "Score must be between 0 and $maxScore.", [], 422);
    }

    // Fetch quiz pass_percentage
    $qStmt = $db->prepare('SELECT pass_percentage FROM teacher_quizzes WHERE id = :qid LIMIT 1');
    $qStmt->execute([':qid' => $attempt['quiz_id']]);
    $quiz = $qStmt->fetch(PDO::FETCH_ASSOC);
    $passPct = (float)($quiz['pass_percentage'] ?? 70);

    $newPct    = $maxScore > 0 ? round($newScore / $maxScore * 100, 2) : 0;
    $newPassed = $newPct >= $passPct ? 1 : 0;

    // Embed override markers into answers_json
    $savedAnswers = json_decode($attempt['answers_json'] ?? '{}', true) ?: [];
    $savedAnswers['_tg_score'] = $newScore;
    $savedAnswers['_tg_notes'] = $notes;
    $savedAnswers['_tg_ts']    = date('Y-m-d H:i:s');
    $newAnswersJson = json_encode($savedAnswers);

    // Update the attempt record
    $upd = $db->prepare("
        UPDATE teacher_quiz_attempts
        SET score = :score, percentage = :pct, passed = :passed, answers_json = :aj
        WHERE id = :aid
    ");
    $upd->execute([
        ':score'  => $newScore,
        ':pct'    => $newPct,
        ':passed' => $newPassed,
        ':aj'     => $newAnswersJson,
        ':aid'    => $attemptId,
    ]);

    jsonResponse(true, 'Grade override saved.', [
        'attempt_id' => $attemptId,
        'new_score'  => $newScore,
        'max_score'  => $maxScore,
        'percentage' => $newPct,
        'passed'     => (bool)$newPassed,
    ]);
}
