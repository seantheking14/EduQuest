<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

header('Content-Type: application/json');

$teacher  = requireAuth();
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$courseId) {
    jsonResponse(false, 'Course ID required.', [], 400);
}

$pdo = getDBConnection();

$stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ? AND teacher_id = ? AND is_active = 1');
$stmt->execute([$courseId, $teacher['id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    jsonResponse(false, 'Course not found.', [], 404);
}

// Modules with their materials
$modStmt = $pdo->prepare('SELECT * FROM course_modules WHERE course_id = ? ORDER BY position, id');
$modStmt->execute([$courseId]);
$modules = $modStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($modules as &$mod) {
    $matStmt = $pdo->prepare('SELECT * FROM course_materials WHERE module_id = ? ORDER BY position, id');
    $matStmt->execute([$mod['id']]);
    $mod['materials'] = $matStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($mod);

// Announcements (pinned first, then newest)
$annStmt = $pdo->prepare('
    SELECT * FROM course_announcements
    WHERE course_id = ?
    ORDER BY is_pinned DESC, created_at DESC
');
$annStmt->execute([$courseId]);
$announcements = $annStmt->fetchAll(PDO::FETCH_ASSOC);

// Enrolled students
$enrStmt = $pdo->prepare('
    SELECT s.id, s.first_name, s.last_name, s.grade_level, s.school_name, s.profile_photo, ce.enrolled_at
    FROM course_enrollments ce
    JOIN students s ON s.id = ce.student_id
    WHERE ce.course_id = ? AND s.is_active = 1
    ORDER BY s.last_name, s.first_name
');
$enrStmt->execute([$courseId]);
$students = $enrStmt->fetchAll(PDO::FETCH_ASSOC);

$course['modules']       = $modules;
$course['announcements'] = $announcements;
$course['students']      = $students;

jsonResponse(true, 'Course retrieved.', ['course' => $course]);
