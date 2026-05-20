<?php
/**
 * GET /api/attempt/assignment_activity.php
 * Returns assignment activity summary for the authenticated teacher's dashboard.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$teacher = requireTeacher();
$pdo = getDBConnection();

// Active quiz assignments (today or future due date, or no due date)
$stmtQ = $pdo->prepare(
    "SELECT COUNT(*) FROM teacher_quiz_assignments tqa
     JOIN teacher_quizzes tq ON tq.id = tqa.quiz_id
     WHERE tq.teacher_id = ?
       AND (tqa.due_date IS NULL OR tqa.due_date >= CURDATE())"
);
$stmtQ->execute([$teacher['id']]);
$quizActive = (int)$stmtQ->fetchColumn();

// Active game assignments
$stmtG = $pdo->prepare(
    "SELECT COUNT(*) FROM game_assignments ga
     WHERE ga.teacher_id = ?
       AND (ga.due_date IS NULL OR ga.due_date >= CURDATE())"
);
$stmtG->execute([$teacher['id']]);
$gameActive = (int)$stmtG->fetchColumn();

// Due within 3 days (quiz + game)
$stmtDueQ = $pdo->prepare(
    "SELECT COUNT(*) FROM teacher_quiz_assignments tqa
     JOIN teacher_quizzes tq ON tq.id = tqa.quiz_id
     WHERE tq.teacher_id = ?
       AND tqa.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)"
);
$stmtDueQ->execute([$teacher['id']]);
$dueSoonQ = (int)$stmtDueQ->fetchColumn();

$stmtDueG = $pdo->prepare(
    "SELECT COUNT(*) FROM game_assignments ga
     WHERE ga.teacher_id = ?
       AND ga.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)"
);
$stmtDueG->execute([$teacher['id']]);
$dueSoonG = (int)$stmtDueG->fetchColumn();

// Recent quiz assignments (last 10 rows, ordered by assigned_at desc)
$stmtRecQ = $pdo->prepare(
    "SELECT
        CONCAT(u.first_name, ' ', u.last_name) AS student_name,
        tq.title AS title,
        'quiz' AS type,
        tqa.due_date,
        tqa.max_attempts,
        (SELECT COUNT(*) FROM teacher_quiz_attempts a
         WHERE a.quiz_id = tqa.quiz_id
           AND a.student_id = tqa.student_id
           AND (tqa.id IS NULL OR a.assignment_id = tqa.id OR a.assignment_id IS NULL)) AS attempts_used
     FROM teacher_quiz_assignments tqa
     JOIN teacher_quizzes tq ON tq.id = tqa.quiz_id
     JOIN students s ON s.id = tqa.student_id
     JOIN users u ON u.id = s.user_id
     WHERE tq.teacher_id = ?
       AND (tqa.due_date IS NULL OR tqa.due_date >= CURDATE())
     ORDER BY tqa.id DESC
     LIMIT 5"
);
$stmtRecQ->execute([$teacher['id']]);
$recentQuiz = $stmtRecQ->fetchAll(PDO::FETCH_ASSOC);

// Recent game assignments (last 5)
$stmtRecG = $pdo->prepare(
    "SELECT
        CONCAT(u.first_name, ' ', u.last_name) AS student_name,
        g.name AS title,
        'game' AS type,
        ga.due_date,
        ga.max_attempts,
        (SELECT COUNT(*) FROM game_attempts a
         WHERE a.game_id = ga.game_id
           AND a.student_id = ga.student_id
           AND a.is_abandoned = 0
           AND (ga.id IS NULL OR a.assignment_id = ga.id OR a.assignment_id IS NULL)) AS attempts_used
     FROM game_assignments ga
     JOIN games g ON g.id = ga.game_id
     JOIN students s ON s.id = ga.student_id
     JOIN users u ON u.id = s.user_id
     WHERE ga.teacher_id = ?
       AND (ga.due_date IS NULL OR ga.due_date >= CURDATE())
     ORDER BY ga.id DESC
     LIMIT 5"
);
$stmtRecG->execute([$teacher['id']]);
$recentGame = $stmtRecG->fetchAll(PDO::FETCH_ASSOC);

$recent = array_merge($recentQuiz, $recentGame);
// Sort recent by type deterministically (quiz first, then game)
// Since due_date can be null, just keep merged order

jsonResponse(true, 'OK', [
    'total_active' => $quizActive + $gameActive,
    'quiz_active'  => $quizActive,
    'game_active'  => $gameActive,
    'due_soon'     => $dueSoonQ + $dueSoonG,
    'recent'       => $recent,
]);
