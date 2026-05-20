<?php
/**
 * GET /api/students/student-pov.php?student_id=N
 * Teacher-only endpoint: returns everything needed to render a student's
 * full dashboard POV (gamification profile, grades, achievements,
 * recent XP, leaderboard rank, quests/quiz scores).
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
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher   = requireTeacher();
$teacherId = (int) $teacher['id'];
$db        = getDBConnection();

$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
if (!$studentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'student_id is required.']);
    exit;
}

try {
    // ── Verify student belongs to this teacher ──────────────────
    $stmt = $db->prepare('
        SELECT s.*, u.email AS user_email, u.id AS user_id
        FROM students s
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.id = :sid AND s.teacher_id = :tid
        LIMIT 1
    ');
    $stmt->execute([':sid' => $studentId, ':tid' => $teacherId]);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Student not found or access denied.']);
        exit;
    }

    // ── Gamification profile ────────────────────────────────────
    $stmt = $db->prepare('SELECT * FROM student_gamification WHERE student_id = :sid LIMIT 1');
    $stmt->execute([':sid' => $studentId]);
    $gamification = $stmt->fetch();

    $totalXp  = $gamification ? (int) $gamification['total_xp']    : 0;
    $streak   = $gamification ? (int) $gamification['streak_days']  : 0;
    $team     = $gamification ? $gamification['team']               : null;
    $eggType  = $gamification ? ($gamification['egg_type']  ?: null) : null;
    $petName  = $gamification ? ($gamification['pet_name']  ?: null) : null;

    // ── Level / XP helpers (must match profile.php exactly) ────
    function xpForLevel(int $lvl): int {
        if ($lvl <= 1) return 0;
        return (int) (50 * pow($lvl - 1, 2) + 50 * ($lvl - 1));
    }
    function calculateLevel(int $xp): int {
        $l = 1;
        while (xpForLevel($l + 1) <= $xp) { $l++; }
        return $l;
    }
    function calcEggStage(int $level): int {
        if ($level >= 20) return 5;
        if ($level >= 12) return 4;
        if ($level >= 7)  return 3;
        if ($level >= 3)  return 2;
        return 1;
    }

    $level      = calculateLevel($totalXp);
    $xpProgress = $totalXp - xpForLevel($level);
    $xpNeeded   = max(1, xpForLevel($level + 1) - xpForLevel($level));
    $eggStage   = calcEggStage($level);

    // ── Achievements ────────────────────────────────────────────
    $stmt = $db->prepare("
        SELECT a.id, a.title, a.description, a.icon, a.category,
               a.badge_color, a.xp_reward, a.target_value,
               COALESCE(sa.is_unlocked, 0) AS is_unlocked,
               COALESCE(sa.progress, 0)    AS progress,
               sa.unlocked_at
        FROM achievements a
        LEFT JOIN student_achievements sa
               ON sa.achievement_id = a.id AND sa.student_id = :sid
        WHERE a.is_active = 1
        ORDER BY sa.is_unlocked DESC, a.sort_order ASC
    ");
    $stmt->execute([':sid' => $studentId]);
    $achievements = $stmt->fetchAll();

    $unlockedCount = count(array_filter($achievements, fn($a) => $a['is_unlocked']));

    // ── Recent XP transactions ──────────────────────────────────
    $stmt = $db->prepare("
        SELECT xp_amount, source_type, description, created_at
        FROM xp_transactions
        WHERE student_id = :sid
        ORDER BY created_at DESC
        LIMIT 15
    ");
    $stmt->execute([':sid' => $studentId]);
    $recentXp = $stmt->fetchAll();

    // ── Grades ──────────────────────────────────────────────────
    $stmt = $db->prepare("
        SELECT g.*, c.title AS course_title
        FROM student_grades g
        LEFT JOIN courses c ON c.id = g.course_id
        WHERE g.student_id = :sid AND g.teacher_id = :tid
        ORDER BY g.graded_at DESC, g.created_at DESC
        LIMIT 30
    ");
    $stmt->execute([':sid' => $studentId, ':tid' => $teacherId]);
    $rawGrades = $stmt->fetchAll();
    // Compute percentage for each grade (score / max_score * 100)
    $grades = array_map(function ($g) {
        $g['score_pct'] = $g['max_score'] > 0
            ? round((float)$g['score'] / (float)$g['max_score'] * 100, 1)
            : round((float)$g['score'], 1);
        return $g;
    }, $rawGrades);

    // ── Grade summary (use percentages, not raw scores) ──────────
    $gradeSummary = [];
    if (!empty($grades)) {
        $pcts  = array_column($grades, 'score_pct');
        $count = count($pcts);
        $gradeSummary = [
            'count'   => $count,
            'average' => $count > 0 ? round(array_sum($pcts) / $count, 1) : 0,
            'highest' => max($pcts),
            'lowest'  => min($pcts),
        ];
    }

    // ── Leaderboard rank ────────────────────────────────────────
    $stmt = $db->prepare("
        SELECT sg.student_id,
               RANK() OVER (ORDER BY sg.total_xp DESC) AS rank_pos,
               sg.total_xp
        FROM student_gamification sg
        JOIN students s ON s.id = sg.student_id
        WHERE s.teacher_id = :tid
    ");
    $stmt->execute([':tid' => $teacherId]);
    $allRanks   = $stmt->fetchAll();
    $totalStudents = count($allRanks);
    $myRank = null;
    foreach ($allRanks as $r) {
        if ((int) $r['student_id'] === $studentId) {
            $myRank = (int) $r['rank_pos'];
            break;
        }
    }

    // ── Quiz attempts (lesson quizzes + teacher-created quizzes, isolated) ──
    $quizScores  = [];
    $quizTotal   = 0;

    // 1) Lesson quizzes: student_quiz_attempts → quizzes → lessons → subjects
    try {
        $c1 = $db->prepare("SELECT COUNT(*) FROM student_quiz_attempts WHERE student_id = :sid");
        $c1->execute([':sid' => $studentId]);
        $quizTotal += (int) $c1->fetchColumn();

        $s1 = $db->prepare("
            SELECT sqa.score, sqa.max_score, sqa.percentage,
                   sqa.time_spent_sec, sqa.completed_at, sqa.xp_earned, sqa.passed,
                   q.title                    AS quiz_title,
                   COALESCE(s.title, '')      AS subject
            FROM   student_quiz_attempts sqa
            JOIN   quizzes  q  ON q.id = sqa.quiz_id
            JOIN   lessons  l  ON l.id = q.lesson_id
            LEFT JOIN subjects s ON s.id = l.subject_id
            WHERE  sqa.student_id = :sid
            ORDER  BY sqa.completed_at DESC
            LIMIT  20
        ");
        $s1->execute([':sid' => $studentId]);
        $quizScores = $s1->fetchAll();
    } catch (Exception $e) { /* lesson quiz tables unavailable */ }

    // 2) Teacher-created quizzes: teacher_quiz_attempts → teacher_quizzes → courses
    try {
        $c2 = $db->prepare("
            SELECT COUNT(*)
            FROM   teacher_quiz_attempts tqa
            JOIN   teacher_quizzes tq ON tq.id = tqa.quiz_id
            WHERE  tqa.student_id = :sid AND tq.teacher_id = :tid
        ");
        $c2->execute([':sid' => $studentId, ':tid' => $teacherId]);
        $teacherCount = (int) $c2->fetchColumn();
        $quizTotal   += $teacherCount;

        if ($teacherCount > 0) {
            $s2 = $db->prepare("
                SELECT tqa.score, tqa.max_score, tqa.percentage,
                       tqa.time_spent_sec, tqa.completed_at, tqa.xp_earned, tqa.passed,
                       tq.title                      AS quiz_title,
                       COALESCE(c.subject, '')       AS subject
                FROM   teacher_quiz_attempts tqa
                JOIN   teacher_quizzes  tq ON tq.id  = tqa.quiz_id
                LEFT JOIN courses        c  ON c.id  = tq.course_id
                WHERE  tqa.student_id = :sid AND tq.teacher_id = :tid
                ORDER  BY tqa.completed_at DESC
                LIMIT  20
            ");
            $s2->execute([':sid' => $studentId, ':tid' => $teacherId]);
            $teacherRows = $s2->fetchAll();
            $quizScores  = array_merge($quizScores, $teacherRows);
            usort($quizScores, fn($a, $b) =>
                strtotime((string)($b['completed_at'] ?? '0'))
              - strtotime((string)($a['completed_at'] ?? '0'))
            );
            $quizScores = array_slice($quizScores, 0, 20);
        }
    } catch (Exception $e) { /* teacher quiz tables unavailable */ }

    // ── Learning modules (all teacher's courses that have modules) ──
    // Shows everything the teacher uploaded, regardless of enrollment.
    // Assignment submission progress is shown where available.
    $learningProgress = [];
    try {
        // All active courses belonging to this teacher that have at least one visible module
        // NOTE: "mod" is a reserved word in MariaDB — use alias "cm" instead
        $stmt = $db->prepare("
            SELECT DISTINCT c.id, c.title, c.subject,
                   (SELECT 1 FROM course_enrollments ce
                    WHERE ce.course_id = c.id AND ce.student_id = :sid2 LIMIT 1) AS is_enrolled
            FROM courses c
            JOIN course_modules cm ON cm.course_id = c.id AND cm.is_visible = 1
            WHERE c.teacher_id = :tid AND c.is_active = 1
            ORDER BY c.title ASC
            LIMIT 30
        ");
        $stmt->execute([':tid' => $teacherId, ':sid2' => $studentId]);
        $teacherCourses = $stmt->fetchAll();

        foreach ($teacherCourses as $course) {
            $cid = (int) $course['id'];

            // All visible modules for this course with their material counts
            $modStmt = $db->prepare("
                SELECT cm.id, cm.title,
                       COUNT(mat.id)                                              AS total_materials,
                       SUM(CASE WHEN mat.material_type = 'assignment' THEN 1 ELSE 0 END) AS total_assignments
                FROM course_modules cm
                LEFT JOIN course_materials mat
                       ON mat.module_id = cm.id AND mat.is_visible = 1
                WHERE cm.course_id = :cid AND cm.is_visible = 1
                GROUP BY cm.id, cm.title
                ORDER BY cm.position ASC, cm.id ASC
            ");
            $modStmt->execute([':cid' => $cid]);
            $modules = $modStmt->fetchAll();

            $totalAssignments = array_sum(array_column($modules, 'total_assignments'));
            $totalMaterials   = array_sum(array_column($modules, 'total_materials'));

            // Submitted/graded assignments for this student in this course
            $submitted = 0;
            if ($totalAssignments > 0) {
                $subStmt = $db->prepare("
                    SELECT COUNT(asub.id)
                    FROM assignment_submissions asub
                    JOIN course_materials mat ON mat.id = asub.material_id
                    JOIN course_modules cm    ON cm.id  = mat.module_id
                    WHERE cm.course_id = :cid
                      AND asub.student_id = :sid
                      AND asub.status IN ('submitted', 'graded')
                ");
                $subStmt->execute([':cid' => $cid, ':sid' => $studentId]);
                $submitted = (int) $subStmt->fetchColumn();
            }

            // Progress % based on assignments; if none fall back to enrollment
            if ($totalAssignments > 0) {
                $pct = (int) round(($submitted / $totalAssignments) * 100);
                if ($submitted === 0)                     $status = 'not_started';
                elseif ($submitted >= $totalAssignments)  $status = 'completed';
                else                                      $status = 'in_progress';
            } else {
                // No assignments — enrolled = available, not enrolled = pending
                $pct    = 0;
                $status = $course['is_enrolled'] ? 'in_progress' : 'not_started';
            }

            $learningProgress[] = [
                'title'            => $course['title'],
                'subject'          => $course['subject'] ?: '',
                'progress_pct'     => $pct,
                'status'           => $status,
                'xp_earned'        => null,
                'total_modules'    => count($modules),
                'total_materials'  => (int) $totalMaterials,
                'total_assignments'=> (int) $totalAssignments,
                'submitted'        => $submitted,
                'is_enrolled'      => (bool) $course['is_enrolled'],
                'modules'          => array_map(fn($m) => [
                    'title'           => $m['title'],
                    'total_materials' => (int) $m['total_materials'],
                ], $modules),
            ];
        }
    } catch (Exception $e) { /* silently skip */ }

    // ── Gamification settings ────────────────────────────────────
    $settings = [];
    try {
        $stmt = $db->prepare('SELECT * FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL LIMIT 1');
        $stmt->execute([':tid' => $teacherId]);
        $settings = $stmt->fetch() ?: [];
    } catch (Exception $e) {}

    // ── Build response ───────────────────────────────────────────
    echo json_encode([
        'success' => true,
        'data' => [
            'student' => [
                'id'            => $studentId,
                'first_name'    => $student['first_name'],
                'last_name'     => $student['last_name'],
                'grade_level'   => $student['grade_level'],
                'school_name'   => $student['school_name'],
                'profile_photo' => $student['profile_photo'],
                'email'         => $student['user_email'],
                'has_account'   => !empty($student['user_id']),
            ],
            'gamification' => [
                'totalXp'    => $totalXp,
                'level'      => $level,
                'xpProgress' => $xpProgress,
                'xpNeeded'   => max(1, $xpNeeded),
                'streak'     => $streak,
                'eggStage'   => $eggStage,
                'eggType'    => $eggType,
                'petName'    => $petName,
                'team'       => $team,
            ],
            'achievements' => [
                'list'    => $achievements,
                'unlocked'=> $unlockedCount,
                'total'   => count($achievements),
            ],
            'recentXp'        => $recentXp,
            'grades'          => $grades,
            'gradeSummary'    => $gradeSummary,
            'leaderboard'     => [
                'rank'  => $myRank,
                'total' => $totalStudents,
            ],
            'quizTotal'       => $quizTotal,
            'quizScores'      => $quizScores,
            'learningProgress'=> $learningProgress,
            'settings'        => $settings,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
