<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

header('Content-Type: application/json');

$teacher  = requireAuth();
$pdo      = getDBConnection();

$search   = sanitizeString($_GET['search']   ?? '');
$page     = max(1, (int)($_GET['page']     ?? 1));
$perPage  = min(50,  max(1, (int)($_GET['per_page'] ?? 20)));
$offset   = ($page - 1) * $perPage;

$where    = 'c.teacher_id = ? AND c.is_active = 1';
$params   = [$teacher['id']];

if ($search) {
    $where   .= ' AND (c.title LIKE ? OR c.subject LIKE ? OR c.grade_level LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM courses c WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT
        c.*,
        (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id)  AS student_count,
        (SELECT COUNT(*) FROM course_modules     cm WHERE cm.course_id = c.id)  AS module_count,
        (SELECT COUNT(*) FROM course_materials   mat WHERE mat.course_id = c.id) AS material_count
    FROM courses c
    WHERE $where
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(true, 'Courses retrieved.', [
    'courses'     => $courses,
    'total'       => $total,
    'page'        => $page,
    'per_page'    => $perPage,
    'total_pages' => (int)ceil($total / $perPage),
]);
