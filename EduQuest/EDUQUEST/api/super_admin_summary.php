<?php
/**
 * GET /EDUQUEST/api/super_admin_summary.php?section=<name>
 *
 * Sections:
 *   interaction_pages    — Tab 5 Section A: page time summary
 *   interaction_questions — Tab 5 Section B: question engagement
 *   interaction_clicks   — Tab 5 Section C: click behavior
 *   interaction_hovers   — Tab 5 Section D: hover behavior
 *   interaction_scores   — Tab 5 Section E: composite engagement scores
 *   overview             — Tab 6 Section A: registration stat cards
 *   timeline             — Tab 6 Section B: monthly registration timeline
 *   teachers             — Tab 6 Section C: teacher activity table
 *   students             — Tab 6 Section D: student activity table
 *   inactive             — Tab 6 Section E: inactive users alert
 *
 * Returns: { section: "...", data: [...] }
 */

header('Content-Type: application/json; charset=utf-8');

// ── Super Admin session guard ──────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
session_start();

if (
    empty($_SESSION['super_admin_id']) ||
    ($_SESSION['role'] ?? '') !== 'super_admin'
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$allowed = [
    'interaction_pages', 'interaction_questions', 'interaction_clicks',
    'interaction_hovers', 'interaction_scores',
    'overview', 'timeline', 'teachers', 'students', 'inactive',
];

$section = trim($_GET['section'] ?? '');
if (!in_array($section, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid section.']);
    exit;
}

// ── Helper ─────────────────────────────────────────────────────────────────────
function respond(string $section, array $data): void {
    echo json_encode(['section' => $section, 'data' => $data]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// TAB 5 — INTERACTION TRACKING SUMMARIES
// ══════════════════════════════════════════════════════════════════════════════

// ── Section A: Page Time Summary ──────────────────────────────────────────────
if ($section === 'interaction_pages') {
    // Per-student summary
    $students = $db->query("
        SELECT
            CONCAT(s.first_name, ' ', s.last_name)  AS student_name,
            (
                SELECT ps2.page_name
                FROM page_sessions ps2
                WHERE ps2.student_id = s.id
                GROUP BY ps2.page_name
                ORDER BY COUNT(*) DESC
                LIMIT 1
            )                                        AS most_visited_page,
            COALESCE(SUM(ps.duration_seconds), 0)    AS total_seconds,
            CASE WHEN COUNT(ps.id) > 0
                 THEN ROUND(SUM(ps.duration_seconds) / COUNT(ps.id))
                 ELSE 0
            END                                      AS avg_seconds_per_visit,
            DATE_FORMAT(MAX(DATE_ADD(ps.session_end, INTERVAL 8 HOUR)), '%Y-%m-%d %H:%i:%s') AS last_active
        FROM students s
        LEFT JOIN page_sessions ps ON ps.student_id = s.id
        GROUP BY s.id, s.first_name, s.last_name
        ORDER BY total_seconds DESC
    ")->fetchAll();

    // Top 5 pages system-wide
    $topPages = $db->query("
        SELECT
            page_name,
            COUNT(*)                    AS visit_count,
            COALESCE(SUM(duration_seconds), 0) AS total_seconds
        FROM page_sessions
        GROUP BY page_name
        ORDER BY total_seconds DESC
        LIMIT 5
    ")->fetchAll();

    respond($section, ['students' => $students, 'top_pages' => $topPages]);
}

// ── Section B: Question Engagement Summary ─────────────────────────────────────
if ($section === 'interaction_questions') {
    $students = $db->query("
        SELECT
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            ROUND(AVG(qi.time_spent_seconds), 1)   AS avg_time,
            MIN(qi.time_spent_seconds)             AS fastest,
            MAX(qi.time_spent_seconds)             AS slowest,
            COUNT(qi.id)                           AS total_attempted,
            CASE WHEN COUNT(qi.id) > 0
                 THEN ROUND(SUM(qi.answered_correctly) / COUNT(qi.id) * 100, 1)
                 ELSE 0
            END                                    AS correct_rate
        FROM students s
        LEFT JOIN question_interactions qi ON qi.student_id = s.id
        GROUP BY s.id, s.first_name, s.last_name
        ORDER BY total_attempted DESC
    ")->fetchAll();

    // Top 5 hardest questions (highest avg time)
    $hardest = $db->query("
        SELECT
            qi.question_id,
            q.title                             AS quiz_name,
            ROUND(AVG(qi.time_spent_seconds), 1) AS avg_time,
            COUNT(qi.id)                         AS attempt_count
        FROM question_interactions qi
        LEFT JOIN quizzes q ON q.id = qi.quiz_id
        GROUP BY qi.question_id, q.title
        ORDER BY avg_time DESC
        LIMIT 5
    ")->fetchAll();

    respond($section, ['students' => $students, 'hardest_questions' => $hardest]);
}

// ── Section C: Click Behavior Summary ─────────────────────────────────────────
if ($section === 'interaction_clicks') {
    $students = $db->query("
        SELECT
            CONCAT(s.first_name, ' ', s.last_name)  AS student_name,
            COALESCE(SUM(ce.click_count), 0)         AS total_clicks,
            (
                SELECT ce2.element_label
                FROM click_events ce2
                WHERE ce2.student_id = s.id
                GROUP BY ce2.element_label
                ORDER BY SUM(ce2.click_count) DESC
                LIMIT 1
            )                                        AS most_clicked_element,
            COALESCE(SUM(CASE WHEN DATE(DATE_ADD(ce.created_at, INTERVAL 8 HOUR)) = DATE(DATE_ADD(UTC_TIMESTAMP(), INTERVAL 8 HOUR)) THEN ce.click_count ELSE 0 END), 0) AS clicks_today,
            (
                SELECT DATE(DATE_ADD(ce3.created_at, INTERVAL 8 HOUR))
                FROM click_events ce3
                WHERE ce3.student_id = s.id
                GROUP BY DATE(DATE_ADD(ce3.created_at, INTERVAL 8 HOUR))
                ORDER BY SUM(ce3.click_count) DESC
                LIMIT 1
            )                                        AS most_active_day
        FROM students s
        LEFT JOIN click_events ce ON ce.student_id = s.id
        GROUP BY s.id, s.first_name, s.last_name
        ORDER BY total_clicks DESC
    ")->fetchAll();

    // Top 10 most-clicked elements system-wide
    $topClicks = $db->query("
        SELECT
            element_label,
            SUM(click_count) AS total_clicks
        FROM click_events
        GROUP BY element_label
        ORDER BY total_clicks DESC
        LIMIT 10
    ")->fetchAll();

    respond($section, ['students' => $students, 'top_elements' => $topClicks]);
}

// ── Section D: Hover Behavior Summary ─────────────────────────────────────────
if ($section === 'interaction_hovers') {
    $students = $db->query("
        SELECT
            CONCAT(s.first_name, ' ', s.last_name)  AS student_name,
            COALESCE(SUM(he.total_hover_ms), 0)      AS total_hover_ms,
            (
                SELECT he2.element_label
                FROM hover_events he2
                WHERE he2.student_id = s.id
                GROUP BY he2.element_label
                ORDER BY SUM(he2.total_hover_ms) DESC
                LIMIT 1
            )                                        AS most_hovered,
            (
                SELECT he3.element_label
                FROM hover_events he3
                WHERE he3.student_id = s.id
                GROUP BY he3.element_label
                ORDER BY SUM(he3.total_hover_ms) ASC
                LIMIT 1
            )                                        AS least_hovered
        FROM students s
        LEFT JOIN hover_events he ON he.student_id = s.id
        GROUP BY s.id, s.first_name, s.last_name
        ORDER BY total_hover_ms DESC
    ")->fetchAll();

    // Top 5 most-hovered elements system-wide
    $topHovers = $db->query("
        SELECT
            element_label,
            SUM(total_hover_ms) AS total_ms
        FROM hover_events
        GROUP BY element_label
        ORDER BY total_ms DESC
        LIMIT 5
    ")->fetchAll();

    respond($section, ['students' => $students, 'top_elements' => $topHovers]);
}

// ── Section E: Composite Engagement Scores ────────────────────────────────────
if ($section === 'interaction_scores') {
    $rows = $db->query("
        SELECT
            s.id                                    AS student_id,
            CONCAT(s.first_name, ' ', s.last_name)  AS student_name,
            COALESCE(SUM(ce.click_count), 0)         AS total_clicks,
            COALESCE(SUM(ps.duration_seconds), 0)    AS total_time_seconds,
            COALESCE(COUNT(DISTINCT qi.id), 0)       AS questions_attempted
        FROM students s
        LEFT JOIN click_events ce        ON ce.student_id = s.id
        LEFT JOIN page_sessions ps       ON ps.student_id = s.id
        LEFT JOIN question_interactions qi ON qi.student_id = s.id
        GROUP BY s.id, s.first_name, s.last_name
    ")->fetchAll();

    // Compute raw scores
    $maxRaw = 0;
    foreach ($rows as &$r) {
        $r['raw_score'] = ($r['total_clicks'] * 0.3)
                        + ($r['total_time_seconds'] * 0.4)
                        + ($r['questions_attempted'] * 0.3);
        if ($r['raw_score'] > $maxRaw) $maxRaw = $r['raw_score'];
    }
    unset($r);

    // Normalize to 0–100
    foreach ($rows as &$r) {
        $r['engagement_score'] = $maxRaw > 0 ? round($r['raw_score'] / $maxRaw * 100, 1) : 0;
        unset($r['raw_score']); // don't expose intermediate values
    }
    unset($r);

    // Sort descending by score
    usort($rows, function ($a, $b) { return $b['engagement_score'] <=> $a['engagement_score']; });

    respond($section, $rows);
}

// ══════════════════════════════════════════════════════════════════════════════
// TAB 6 — REGISTERED USERS SUMMARY
// ══════════════════════════════════════════════════════════════════════════════

// ── Section A: Registration Overview ──────────────────────────────────────────
if ($section === 'overview') {
    $totalStudents = (int) $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $totalTeachers = (int) $db->query('SELECT COUNT(*) FROM teachers')->fetchColumn();

    $newThisMonth = (int) $db->query("
        SELECT COUNT(*) FROM users
        WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())
    ")->fetchColumn();

    $newThisWeek = (int) $db->query("
        SELECT COUNT(*) FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn();

    // Growth: this month vs last month
    $lastMonth = (int) $db->query("
        SELECT COUNT(*) FROM users
        WHERE YEAR(created_at)  = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
          AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
    ")->fetchColumn();

    $growthPct = $lastMonth > 0
        ? round(($newThisMonth - $lastMonth) / $lastMonth * 100, 1)
        : ($newThisMonth > 0 ? 100.0 : 0.0);

    respond($section, [
        'total_students'   => $totalStudents,
        'total_teachers'   => $totalTeachers,
        'new_this_month'   => $newThisMonth,
        'new_this_week'    => $newThisWeek,
        'growth_pct'       => $growthPct,
        'last_month_count' => $lastMonth,
    ]);
}

// ── Section B: Registration Timeline (past 6 months) ──────────────────────────
if ($section === 'timeline') {
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[] = date('Y-m', strtotime("-{$i} months"));
    }

    $rows = $db->query("
        SELECT
            DATE_FORMAT(u.created_at, '%Y-%m') AS month,
            u.role,
            COUNT(*)                            AS count
        FROM users u
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          AND u.role IN ('student', 'teacher')
        GROUP BY month, u.role
        ORDER BY month
    ")->fetchAll();

    // Pivot into { month, teachers, students }
    $byMonth = [];
    foreach ($months as $m) { $byMonth[$m] = ['month' => $m, 'teachers' => 0, 'students' => 0]; }
    foreach ($rows as $r) {
        if (isset($byMonth[$r['month']])) {
            $byMonth[$r['month']][$r['role'] . 's'] = (int) $r['count'];
        }
    }

    respond($section, array_values($byMonth));
}

// ── Section C: Teacher Activity Summary ───────────────────────────────────────
if ($section === 'teachers') {
    $rows = $db->query("
        SELECT
            CONCAT(t.first_name, ' ', t.last_name)              AS teacher_name,
            t.email,
            (SELECT COUNT(*) FROM students s2 WHERE s2.teacher_id = t.id) AS students_assigned,
            0                                                    AS iep_goals_tagged,
            0                                                    AS assessments_initiated,
            (SELECT COUNT(*) FROM course_materials cm
             JOIN courses c ON c.id = cm.course_id
             WHERE c.teacher_id = t.id)                         AS materials_uploaded,
            u.last_login                                         AS last_login
        FROM teachers t
        LEFT JOIN users u ON u.id = t.user_id
        ORDER BY t.last_name, t.first_name
    ")->fetchAll();

    respond($section, $rows);
}

// ── Section D: Student Activity Summary ───────────────────────────────────────
if ($section === 'students') {
    $rows = $db->query("
        SELECT
            CONCAT(s.first_name, ' ', s.last_name)   AS student_name,
            COALESCE(u.email, '')                     AS email,
            COALESCE(sg.team, '')                     AS team,
            COALESCE(sg.egg_stage, 1)                 AS pet_stage,
            COALESCE(sg.total_xp, 0)                  AS total_exp,
            pre.score                                  AS pretest_score,
            post.score                                 AS posttest_score,
            (post.score - pre.score)                   AS score_change,
            (
                SELECT COUNT(DISTINCT slp.lesson_id)
                FROM student_lesson_progress slp
                WHERE slp.student_id = s.id AND slp.status = 'completed'
            )                                          AS materials_viewed,
            u.last_login                               AS last_login
        FROM students s
        LEFT JOIN users u ON u.id = s.user_id
        LEFT JOIN student_gamification sg ON sg.student_id = s.id
        LEFT JOIN assessment_sessions pre
            ON pre.student_id = s.id AND pre.session_type = 'pretest'  AND pre.status = 'completed'
        LEFT JOIN assessment_sessions post
            ON post.student_id = s.id AND post.session_type = 'posttest' AND post.status = 'completed'
        ORDER BY s.last_name, s.first_name
    ")->fetchAll();

    respond($section, $rows);
}

// ── Section E: Inactive Users ──────────────────────────────────────────────────
if ($section === 'inactive') {
    $rows = $db->query("
        SELECT
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            u.last_login,
            DATEDIFF(NOW(), u.last_login)           AS days_inactive
        FROM students s
        JOIN users u ON u.id = s.user_id
        WHERE u.last_login IS NOT NULL
          AND u.last_login < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY days_inactive DESC
    ")->fetchAll();

    respond($section, $rows);
}
