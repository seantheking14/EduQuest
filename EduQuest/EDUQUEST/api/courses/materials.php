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

function moduleOwnedForMat(PDO $pdo, int $moduleId, int $teacherId): array|false {
    $s = $pdo->prepare('
        SELECT cm.id, cm.course_id
        FROM course_modules cm
        JOIN courses c ON c.id = cm.course_id
        WHERE cm.id = ? AND c.teacher_id = ? AND c.is_active = 1
    ');
    $s->execute([$moduleId, $teacherId]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

function materialOwned(PDO $pdo, int $materialId, int $teacherId): array|false {
    $s = $pdo->prepare('
        SELECT mat.*
        FROM course_materials mat
        JOIN courses c ON c.id = mat.course_id
        WHERE mat.id = ? AND c.teacher_id = ? AND c.is_active = 1
    ');
    $s->execute([$materialId, $teacherId]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

// ── Route ─────────────────────────────────────────────────

switch ($action) {

    case 'create':
        $moduleId    = (int)($body['module_id']     ?? 0);
        $title       = sanitizeString($body['title']         ?? '');
        $description = sanitizeString($body['description']   ?? '');
        $type        = sanitizeString($body['material_type'] ?? 'text');
        $content     = sanitizeString($body['content']       ?? '');
        $dueDate     = sanitizeString($body['due_date']      ?? '');

        $validTypes = ['text', 'link', 'assignment'];
        if (!in_array($type, $validTypes, true)) $type = 'text';
        if (!$moduleId || !$title) jsonResponse(false, 'module_id and title are required.', [], 422);

        $mod = moduleOwnedForMat($pdo, $moduleId, $teacher['id']);
        if (!$mod) jsonResponse(false, 'Module not found.', [], 404);

        if ($type === 'link' && $content && !filter_var($content, FILTER_VALIDATE_URL)) {
            jsonResponse(false, 'The URL provided is not valid.', [], 422);
        }
        $dueDate = ($dueDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) ? $dueDate : null;

        $pos = $pdo->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM course_materials WHERE module_id = ?');
        $pos->execute([$moduleId]);
        $position = (int)$pos->fetchColumn();

        $pdo->prepare('
            INSERT INTO course_materials
                (module_id, course_id, title, description, material_type, content, position, due_date)
            VALUES (?,?,?,?,?,?,?,?)
        ')->execute([$moduleId, $mod['course_id'], $title, $description, $type, $content, $position, $dueDate]);
        $id = (int)$pdo->lastInsertId();

        jsonResponse(true, 'Material created.', [
            'id' => $id, 'title' => $title, 'description' => $description,
            'material_type' => $type, 'content' => $content,
            'due_date' => $dueDate, 'is_visible' => 1, 'position' => $position,
        ], 201);
        break;

    case 'update':
        $materialId  = (int)($body['id']        ?? 0);
        $title       = sanitizeString($body['title']       ?? '');
        $description = sanitizeString($body['description'] ?? '');
        $content     = sanitizeString($body['content']     ?? '');
        $isVisible   = isset($body['is_visible']) ? (int)(bool)$body['is_visible'] : 1;
        $dueDate     = sanitizeString($body['due_date']    ?? '');
        $dueDate     = ($dueDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) ? $dueDate : null;

        if (!$materialId || !$title) jsonResponse(false, 'id and title are required.', [], 422);
        $mat = materialOwned($pdo, $materialId, $teacher['id']);
        if (!$mat) jsonResponse(false, 'Material not found.', [], 404);

        if ($mat['material_type'] === 'link' && $content && !filter_var($content, FILTER_VALIDATE_URL)) {
            jsonResponse(false, 'The URL provided is not valid.', [], 422);
        }

        $pdo->prepare('
            UPDATE course_materials SET title=?, description=?, content=?, is_visible=?, due_date=? WHERE id=?
        ')->execute([$title, $description, $content, $isVisible, $dueDate, $materialId]);
        jsonResponse(true, 'Material updated.', []);
        break;

    case 'delete':
        $materialId = (int)($body['id'] ?? 0);
        if (!$materialId) jsonResponse(false, 'id is required.', [], 422);
        $mat = materialOwned($pdo, $materialId, $teacher['id']);
        if (!$mat) jsonResponse(false, 'Material not found.', [], 404);

        if ($mat['stored_filename']) {
            @unlink(COURSE_MATERIAL_DIR . $mat['stored_filename']);
        }
        $pdo->prepare('DELETE FROM course_materials WHERE id = ?')->execute([$materialId]);
        jsonResponse(true, 'Material deleted.', []);
        break;

    default:
        jsonResponse(false, 'Unknown action. Use: create|update|delete', [], 400);
}
