<?php
/**
 * GET /api/analytics/summary.php
 * Returns aggregated statistics for the authenticated teacher's dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireAuth();
if (!in_array($user['role'], ['teacher', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Teacher access required.']);
    exit;
}

$pdo = getDBConnection();

// requireAuth() already overrides $user['id'] to teachers.id for FK use.
$teacherId = $user['id'];

// ── 1. Student overview ──────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN ap.adhd_type = 'combined' THEN 1 ELSE 0 END)             AS combined,
            SUM(CASE WHEN ap.adhd_type = 'predominantly_inattentive' THEN 1 ELSE 0 END) AS inattentive,
            SUM(CASE WHEN ap.adhd_type = 'hyperactive_impulsive' THEN 1 ELSE 0 END) AS hyperactive,
            SUM(CASE WHEN ap.adhd_type IS NULL THEN 1 ELSE 0 END)                  AS unspecified
     FROM students s
     LEFT JOIN adhd_profiles ap ON ap.student_id = s.id
     WHERE s.teacher_id = :tid AND s.is_active = 1"
);
$stmt->execute([':tid' => $teacherId]);
$studentStats = $stmt->fetch();

// ── 2. Severity distribution ─────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT ap.severity, COUNT(*) AS cnt
     FROM students s
     JOIN adhd_profiles ap ON ap.student_id = s.id
     WHERE s.teacher_id = :tid AND s.is_active = 1
     GROUP BY ap.severity
     ORDER BY FIELD(ap.severity, 'mild', 'moderate', 'severe')"
);
$stmt->execute([':tid' => $teacherId]);
$severityRows = $stmt->fetchAll();
$severity = [];
foreach ($severityRows as $row) {
    $severity[$row['severity']] = (int)$row['cnt'];
}

// ── 3. Top comorbid conditions ───────────────────────────────
$stmt = $pdo->prepare(
    "SELECT cc.condition_name, COUNT(*) AS cnt
     FROM students s
     JOIN comorbid_conditions cc ON cc.student_id = s.id
     WHERE s.teacher_id = :tid AND s.is_active = 1 AND cc.is_current = 1
     GROUP BY cc.condition_name
     ORDER BY cnt DESC
     LIMIT 8"
);
$stmt->execute([':tid' => $teacherId]);
$comorbidities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 4. Accommodation categories in use ──────────────────────
$stmt = $pdo->prepare(
    "SELECT a.category, COUNT(*) AS cnt
     FROM students s
     JOIN accommodations a ON a.student_id = s.id
     WHERE s.teacher_id = :tid AND s.is_active = 1 AND a.is_active = 1
     GROUP BY a.category
     ORDER BY cnt DESC"
);
$stmt->execute([':tid' => $teacherId]);
$accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 5. Grade level distribution ──────────────────────────────
$stmt = $pdo->prepare(
    "SELECT grade_level, COUNT(*) AS cnt
     FROM students
     WHERE teacher_id = :tid AND is_active = 1 AND grade_level IS NOT NULL
     GROUP BY grade_level
     ORDER BY grade_level ASC"
);
$stmt->execute([':tid' => $teacherId]);
$gradeLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 6. Courses overview ──────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total_courses,
            SUM(cm.module_count)    AS total_modules,
            SUM(cmat.material_count) AS total_materials,
            SUM(ce.enrolled_count)  AS total_enrollments
     FROM courses c
     LEFT JOIN (
         SELECT course_id, COUNT(*) AS module_count FROM course_modules GROUP BY course_id
     ) cm ON cm.course_id = c.id
     LEFT JOIN (
         SELECT course_id, COUNT(*) AS material_count FROM course_materials GROUP BY course_id
     ) cmat ON cmat.course_id = c.id
     LEFT JOIN (
         SELECT course_id, COUNT(*) AS enrolled_count FROM course_enrollments GROUP BY course_id
     ) ce ON ce.course_id = c.id
     WHERE c.teacher_id = :tid AND c.is_active = 1"
);
$stmt->execute([':tid' => $teacherId]);
$courseStats = $stmt->fetch();

// ── 7. Recent students (last 5 added) ───────────────────────
$stmt = $pdo->prepare(
    "SELECT s.id, s.first_name, s.last_name, s.grade_level, s.created_at,
            ap.adhd_type, ap.severity
     FROM students s
     LEFT JOIN adhd_profiles ap ON ap.student_id = s.id
     WHERE s.teacher_id = :tid AND s.is_active = 1
     ORDER BY s.created_at DESC
     LIMIT 5"
);
$stmt->execute([':tid' => $teacherId]);
$recentStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 8. Students with active medications ─────────────────────
$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT s.id) AS cnt
     FROM students s
     JOIN medications m ON m.student_id = s.id
     WHERE s.teacher_id = :tid AND s.is_active = 1
       AND (m.end_date IS NULL OR m.end_date >= CURDATE())"
);
$stmt->execute([':tid' => $teacherId]);
$medRow = $stmt->fetch();

echo json_encode([
    'success' => true,
    'data' => [
        'students' => [
            'total'        => (int)$studentStats['total'],
            'combined'     => (int)$studentStats['combined'],
            'inattentive'  => (int)$studentStats['inattentive'],
            'hyperactive'  => (int)$studentStats['hyperactive'],
            'unspecified'  => (int)$studentStats['unspecified'],
            'onMedication' => (int)$medRow['cnt'],
        ],
        'severity'       => $severity,
        'comorbidities'  => $comorbidities,
        'accommodations' => $accommodations,
        'gradeLevels'    => $gradeLevels,
        'courses' => [
            'total'       => (int)$courseStats['total_courses'],
            'modules'     => (int)$courseStats['total_modules'],
            'materials'   => (int)$courseStats['total_materials'],
            'enrollments' => (int)$courseStats['total_enrollments'],
        ],
        'recentStudents' => $recentStudents,
    ],
]);
