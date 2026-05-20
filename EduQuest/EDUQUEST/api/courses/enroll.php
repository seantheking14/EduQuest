<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

header('Content-Type: application/json');

$teacher = requireAuth();
$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = sanitizeString($body['action'] ?? $_GET['action'] ?? '');
$pdo     = getDBConnection();

function courseOwnedEnroll(PDO $pdo, int $courseId, int $teacherId): bool {
    $s = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND teacher_id = ? AND is_active = 1');
    $s->execute([$courseId, $teacherId]);
    return (bool)$s->fetch();
}

switch ($action) {

    case 'list':
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (int)($body['course_id'] ?? 0);
        if (!$courseId) jsonResponse(false, 'course_id required.', [], 400);
        if (!courseOwnedEnroll($pdo, $courseId, $teacher['id'])) jsonResponse(false, 'Course not found.', [], 404);

        // All enrolled students
        $enr = $pdo->prepare('
            SELECT s.id, s.first_name, s.last_name, s.grade_level, s.school_name, s.profile_photo, ce.enrolled_at
            FROM course_enrollments ce JOIN students s ON s.id = ce.student_id
            WHERE ce.course_id = ? AND s.is_active = 1
            ORDER BY s.last_name, s.first_name
        ');
        $enr->execute([$courseId]);
        $enrolled = $enr->fetchAll(PDO::FETCH_ASSOC);

        // All teacher's students NOT yet enrolled, for the picker
        $avail = $pdo->prepare('
            SELECT s.id, s.first_name, s.last_name, s.grade_level, s.school_name
            FROM students s
            WHERE s.teacher_id = ? AND s.is_active = 1
              AND s.id NOT IN (
                  SELECT student_id FROM course_enrollments WHERE course_id = ?
              )
            ORDER BY s.last_name, s.first_name
        ');
        $avail->execute([$teacher['id'], $courseId]);
        $available = $avail->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(true, 'Enrollment data retrieved.', [
            'enrolled'  => $enrolled,
            'available' => $available,
        ]);
        break;

    case 'enroll':
        $courseId   = (int)($body['course_id']  ?? 0);
        $studentIds = $body['student_ids'] ?? [];
        if (!$courseId || empty($studentIds)) jsonResponse(false, 'course_id and student_ids[] required.', [], 422);
        if (!courseOwnedEnroll($pdo, $courseId, $teacher['id'])) jsonResponse(false, 'Course not found.', [], 404);

        $ins = $pdo->prepare('INSERT IGNORE INTO course_enrollments (course_id, student_id) VALUES (?,?)');
        foreach ($studentIds as $sid) {
            // Verify student belongs to this teacher
            $chk = $pdo->prepare('SELECT id FROM students WHERE id = ? AND teacher_id = ? AND is_active = 1');
            $chk->execute([(int)$sid, $teacher['id']]);
            if ($chk->fetch()) {
                $ins->execute([$courseId, (int)$sid]);
            }
        }
        jsonResponse(true, 'Students enrolled.', []);
        break;

    case 'unenroll':
        $courseId  = (int)($body['course_id']  ?? 0);
        $studentId = (int)($body['student_id'] ?? 0);
        if (!$courseId || !$studentId) jsonResponse(false, 'course_id and student_id required.', [], 422);
        if (!courseOwnedEnroll($pdo, $courseId, $teacher['id'])) jsonResponse(false, 'Course not found.', [], 404);

        $pdo->prepare('DELETE FROM course_enrollments WHERE course_id = ? AND student_id = ?')
            ->execute([$courseId, $studentId]);
        jsonResponse(true, 'Student removed from course.', []);
        break;

    default:
        jsonResponse(false, 'Unknown action. Use: list|enroll|unenroll', [], 400);
}
