<?php
/**
 * GET /api/analytics/grades.php
 * Returns grade analytics data for the authenticated teacher's students.
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

$teacher = requireTeacher();
$tid = (int) $teacher['id'];

try {
    $db = getDBConnection();

    // ── 1. Overall summary stats ────────────────────────────
    $stmt = $db->prepare("
        SELECT
            COUNT(*)                                           AS total_grades,
            COUNT(DISTINCT student_id)                         AS students_graded,
            ROUND(AVG(score / max_score * 100), 1)             AS class_average,
            ROUND(MAX(score / max_score * 100), 1)             AS highest_pct,
            ROUND(MIN(score / max_score * 100), 1)             AS lowest_pct
        FROM student_grades
        WHERE teacher_id = :tid
    ");
    $stmt->execute([':tid' => $tid]);
    $summary = $stmt->fetch();

    // ── 2. Average by assessment type ───────────────────────
    $stmt = $db->prepare("
        SELECT
            assessment_type,
            COUNT(*)                                   AS count,
            ROUND(AVG(score / max_score * 100), 1)     AS avg_pct
        FROM student_grades
        WHERE teacher_id = :tid
        GROUP BY assessment_type
        ORDER BY avg_pct DESC
    ");
    $stmt->execute([':tid' => $tid]);
    $byType = $stmt->fetchAll();

    // ── 3. Grade distribution (letter-grade buckets) ────────
    $stmt = $db->prepare("
        SELECT
            CASE
                WHEN (score / max_score * 100) >= 90 THEN 'A'
                WHEN (score / max_score * 100) >= 80 THEN 'B'
                WHEN (score / max_score * 100) >= 70 THEN 'C'
                WHEN (score / max_score * 100) >= 60 THEN 'D'
                ELSE 'F'
            END AS letter,
            COUNT(*) AS cnt
        FROM student_grades
        WHERE teacher_id = :tid
        GROUP BY letter
        ORDER BY FIELD(letter, 'A','B','C','D','F')
    ");
    $stmt->execute([':tid' => $tid]);
    $distribution = $stmt->fetchAll();

    // ── 4. Monthly trend (avg % per month) ──────────────────
    $stmt = $db->prepare("
        SELECT
            DATE_FORMAT(graded_at, '%Y-%m')            AS month,
            ROUND(AVG(score / max_score * 100), 1)     AS avg_pct,
            COUNT(*)                                   AS count
        FROM student_grades
        WHERE teacher_id = :tid
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute([':tid' => $tid]);
    $trend = $stmt->fetchAll();

    // ── 5. Per-student averages (top & bottom) ──────────────
    $stmt = $db->prepare("
        SELECT
            s.id,
            s.first_name,
            s.last_name,
            s.grade_level,
            COUNT(g.id)                                AS assessments,
            ROUND(AVG(g.score / g.max_score * 100), 1) AS avg_pct
        FROM student_grades g
        JOIN students s ON s.id = g.student_id
        WHERE g.teacher_id = :tid AND s.is_active = 1
        GROUP BY s.id
        ORDER BY avg_pct DESC
    ");
    $stmt->execute([':tid' => $tid]);
    $studentAverages = $stmt->fetchAll();

    // ── 6. Recent grades ────────────────────────────────────
    $stmt = $db->prepare("
        SELECT
            g.id,
            g.assessment_name,
            g.assessment_type,
            g.score,
            g.max_score,
            ROUND(g.score / g.max_score * 100, 1) AS pct,
            g.graded_at,
            s.first_name,
            s.last_name
        FROM student_grades g
        JOIN students s ON s.id = g.student_id
        WHERE g.teacher_id = :tid
        ORDER BY g.graded_at DESC, g.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([':tid' => $tid]);
    $recent = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'summary'          => $summary,
            'byType'           => $byType,
            'distribution'     => $distribution,
            'trend'            => $trend,
            'studentAverages'  => $studentAverages,
            'recentGrades'     => $recent,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load grade analytics.',
    ]);
}
