<?php
/**
 * GET /api/analytics/student-grades.php
 * Returns grade analytics data for the authenticated student's own grades.
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

$user = requireStudent();
$userId = (int) $user['id']; // users.id

try {
    $db = getDBConnection();

    // Resolve students.id from users.id
    $stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $userId]);
    $studentRow = $stmt->fetch();

    if (!$studentRow) {
        echo json_encode([
            'success' => true,
            'data'    => [
                'summary'      => ['total_grades' => 0, 'class_average' => null, 'highest_pct' => null, 'lowest_pct' => null],
                'byType'       => [],
                'distribution' => [],
                'trend'        => [],
                'recentGrades' => [],
            ],
        ]);
        exit;
    }

    $sid = (int) $studentRow['id'];

    // ── 1. Overall summary stats ────────────────────────────
    $stmt = $db->prepare("
        SELECT
            COUNT(*)                                           AS total_grades,
            ROUND(AVG(score / max_score * 100), 1)             AS class_average,
            ROUND(MAX(score / max_score * 100), 1)             AS highest_pct,
            ROUND(MIN(score / max_score * 100), 1)             AS lowest_pct
        FROM student_grades
        WHERE student_id = :sid
    ");
    $stmt->execute([':sid' => $sid]);
    $summary = $stmt->fetch();

    // ── 2. Average by assessment type ───────────────────────
    $stmt = $db->prepare("
        SELECT
            assessment_type,
            COUNT(*)                                   AS count,
            ROUND(AVG(score / max_score * 100), 1)     AS avg_pct
        FROM student_grades
        WHERE student_id = :sid
        GROUP BY assessment_type
        ORDER BY avg_pct DESC
    ");
    $stmt->execute([':sid' => $sid]);
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
        WHERE student_id = :sid
        GROUP BY letter
        ORDER BY FIELD(letter, 'A','B','C','D','F')
    ");
    $stmt->execute([':sid' => $sid]);
    $distribution = $stmt->fetchAll();

    // ── 4. Monthly trend (avg % per month) ──────────────────
    $stmt = $db->prepare("
        SELECT
            DATE_FORMAT(graded_at, '%Y-%m')            AS month,
            ROUND(AVG(score / max_score * 100), 1)     AS avg_pct,
            COUNT(*)                                   AS count
        FROM student_grades
        WHERE student_id = :sid
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute([':sid' => $sid]);
    $trend = $stmt->fetchAll();

    // ── 5. Recent grades ────────────────────────────────────
    $stmt = $db->prepare("
        SELECT
            g.id,
            g.assessment_name,
            g.assessment_type,
            g.score,
            g.max_score,
            ROUND(g.score / g.max_score * 100, 1) AS pct,
            g.graded_at,
            g.remarks
        FROM student_grades g
        WHERE g.student_id = :sid
        ORDER BY g.graded_at DESC, g.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([':sid' => $sid]);
    $recent = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'summary'      => $summary,
            'byType'       => $byType,
            'distribution' => $distribution,
            'trend'        => $trend,
            'recentGrades' => $recent,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load grade analytics.',
    ]);
}
