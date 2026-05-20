<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';
require_once '../notifications/send.php';

header('Content-Type: application/json');

$teacher = requireAuth();
$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = sanitizeString($body['action'] ?? $_GET['action'] ?? '');
$pdo     = getDBConnection();

function courseOwnedAnn(PDO $pdo, int $courseId, int $teacherId): bool {
    $s = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND teacher_id = ? AND is_active = 1');
    $s->execute([$courseId, $teacherId]);
    return (bool)$s->fetch();
}

function annOwned(PDO $pdo, int $annId, int $teacherId): array|false {
    $s = $pdo->prepare('
        SELECT a.* FROM course_announcements a
        JOIN courses c ON c.id = a.course_id
        WHERE a.id = ? AND c.teacher_id = ? AND c.is_active = 1
    ');
    $s->execute([$annId, $teacherId]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

switch ($action) {

    case 'create':
        $courseId = (int)($body['course_id'] ?? 0);
        $title    = sanitizeString($body['title']   ?? '');
        $content  = sanitizeString($body['content'] ?? '');
        $isPinned = (int)(bool)($body['is_pinned']  ?? false);
        if (!$courseId || !$title || !$content) {
            jsonResponse(false, 'course_id, title, and content are required.', [], 422);
        }
        if (!courseOwnedAnn($pdo, $courseId, $teacher['id'])) {
            jsonResponse(false, 'Course not found.', [], 404);
        }
        $pdo->prepare('
            INSERT INTO course_announcements (course_id, teacher_id, title, content, is_pinned)
            VALUES (?,?,?,?,?)
        ')->execute([$courseId, $teacher['id'], $title, $content, $isPinned]);
        $id = (int)$pdo->lastInsertId();

        // Notify enrolled students that a new announcement is available.
        $studentStmt = $pdo->prepare('SELECT student_id FROM course_enrollments WHERE course_id = ?');
        $studentStmt->execute([$courseId]);
        $studentIds = $studentStmt->fetchAll(PDO::FETCH_COLUMN);
        $notifMessage = 'New announcement: ' . mb_substr($title, 0, 80);
        $notifLink = '/EduQuest/student-dashboard/learning/learning.html';
        foreach ($studentIds as $sid) {
            $sid = (int)$sid;
            if ($sid > 0) {
                send_notification($pdo, $sid, 'student', $notifMessage, $notifLink);
            }
        }

        jsonResponse(true, 'Announcement posted.', [
            'id' => $id, 'title' => $title, 'content' => $content,
            'is_pinned' => $isPinned, 'created_at' => date('Y-m-d H:i:s'),
        ], 201);
        break;

    case 'update':
        $annId    = (int)($body['id']       ?? 0);
        $title    = sanitizeString($body['title']   ?? '');
        $content  = sanitizeString($body['content'] ?? '');
        $isPinned = (int)(bool)($body['is_pinned']  ?? false);
        if (!$annId || !$title || !$content) jsonResponse(false, 'id, title, and content are required.', [], 422);
        if (!annOwned($pdo, $annId, $teacher['id'])) jsonResponse(false, 'Announcement not found.', [], 404);

        $pdo->prepare('UPDATE course_announcements SET title=?, content=?, is_pinned=? WHERE id=?')
            ->execute([$title, $content, $isPinned, $annId]);
        jsonResponse(true, 'Announcement updated.', []);
        break;

    case 'delete':
        $annId = (int)($body['id'] ?? 0);
        if (!$annId) jsonResponse(false, 'id is required.', [], 422);
        if (!annOwned($pdo, $annId, $teacher['id'])) jsonResponse(false, 'Announcement not found.', [], 404);

        $pdo->prepare('DELETE FROM course_announcements WHERE id = ?')->execute([$annId]);
        jsonResponse(true, 'Announcement deleted.', []);
        break;

    default:
        jsonResponse(false, 'Unknown action. Use: create|update|delete', [], 400);
}
