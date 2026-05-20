<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

header('Content-Type: application/json');

$teacher = requireAuth();
$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = sanitizeString($body['action'] ?? $_GET['action'] ?? '');
$pdo     = getDBConnection();

// ── Ownership helpers ──────────────────────────────────────

function courseOwned(PDO $pdo, int $courseId, int $teacherId): bool {
    $s = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND teacher_id = ? AND is_active = 1');
    $s->execute([$courseId, $teacherId]);
    return (bool)$s->fetch();
}

function moduleOwned(PDO $pdo, int $moduleId, int $teacherId): array|false {
    $s = $pdo->prepare('
        SELECT cm.id, cm.course_id
        FROM course_modules cm
        JOIN courses c ON c.id = cm.course_id
        WHERE cm.id = ? AND c.teacher_id = ? AND c.is_active = 1
    ');
    $s->execute([$moduleId, $teacherId]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

// ── Route ─────────────────────────────────────────────────

switch ($action) {

    case 'create':
        $courseId    = (int)($body['course_id']   ?? 0);
        $title       = sanitizeString($body['title']       ?? '');
        $description = sanitizeString($body['description'] ?? '');
        if (!$courseId || !$title) jsonResponse(false, 'course_id and title are required.', [], 422);
        if (!courseOwned($pdo, $courseId, $teacher['id'])) jsonResponse(false, 'Course not found.', [], 404);

        $pos = $pdo->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM course_modules WHERE course_id = ?');
        $pos->execute([$courseId]);
        $position = (int)$pos->fetchColumn();

        $pdo->prepare('INSERT INTO course_modules (course_id, title, description, position) VALUES (?,?,?,?)')
            ->execute([$courseId, $title, $description, $position]);
        $id = (int)$pdo->lastInsertId();

        jsonResponse(true, 'Module created.', [
            'id' => $id, 'title' => $title, 'description' => $description,
            'position' => $position, 'is_visible' => 1, 'materials' => [],
        ], 201);
        break;

    case 'update':
        $moduleId    = (int)($body['id']          ?? 0);
        $title       = sanitizeString($body['title']       ?? '');
        $description = sanitizeString($body['description'] ?? '');
        $isVisible   = isset($body['is_visible']) ? (int)(bool)$body['is_visible'] : 1;
        if (!$moduleId || !$title) jsonResponse(false, 'id and title are required.', [], 422);
        if (!moduleOwned($pdo, $moduleId, $teacher['id'])) jsonResponse(false, 'Module not found.', [], 404);

        $pdo->prepare('UPDATE course_modules SET title=?, description=?, is_visible=? WHERE id=?')
            ->execute([$title, $description, $isVisible, $moduleId]);
        jsonResponse(true, 'Module updated.', []);
        break;

    case 'delete':
        $moduleId = (int)($body['id'] ?? 0);
        if (!$moduleId) jsonResponse(false, 'id is required.', [], 422);
        if (!moduleOwned($pdo, $moduleId, $teacher['id'])) jsonResponse(false, 'Module not found.', [], 404);

        // Clean up stored files before deletion
        $mats = $pdo->prepare('SELECT stored_filename FROM course_materials WHERE module_id = ?');
        $mats->execute([$moduleId]);
        foreach ($mats->fetchAll(PDO::FETCH_COLUMN) as $sf) {
            if ($sf) @unlink(COURSE_MATERIAL_DIR . $sf);
        }
        $pdo->prepare('DELETE FROM course_modules WHERE id = ?')->execute([$moduleId]);
        jsonResponse(true, 'Module deleted.', []);
        break;

    case 'reorder':
        $order = $body['order'] ?? [];
        if (!is_array($order)) jsonResponse(false, 'order must be an array of module IDs.', [], 422);
        foreach ($order as $pos => $mid) {
            if (moduleOwned($pdo, (int)$mid, $teacher['id'])) {
                $pdo->prepare('UPDATE course_modules SET position = ? WHERE id = ?')
                    ->execute([(int)$pos, (int)$mid]);
            }
        }
        jsonResponse(true, 'Order saved.', []);
        break;

    default:
        jsonResponse(false, 'Unknown action. Use: create|update|delete|reorder', [], 400);
}
