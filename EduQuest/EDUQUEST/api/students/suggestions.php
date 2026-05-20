<?php
/**
 * GET /api/students/suggestions.php
 *
 * Returns self-registered students that have NOT yet been linked to any
 * teacher (students.teacher_id IS NULL). Teachers use this list to
 * "claim" / add registered students to their class.
 *
 * Query params:
 *   ?search=Name      – filter by first/last name or email
 *   ?page=1           – pagination (default 1)
 *   ?per_page=20      – results per page (max 50)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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

$teacher = requireAuth();
$pdo     = getDBConnection();

$search  = sanitizeString($_GET['search']  ?? '');
$page    = max(1, (int)($_GET['page']      ?? 1));
$perPage = min(50, max(1, (int)($_GET['per_page'] ?? 20)));
$offset  = ($page - 1) * $perPage;

// Only students that self-registered (have a user_id) but no teacher yet
$where  = 'WHERE s.teacher_id IS NULL AND s.is_active = 1 AND s.user_id IS NOT NULL';
$params = [];

if ($search !== '') {
    $where   .= ' AND (s.first_name LIKE ? OR s.last_name LIKE ? OR u.email LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Count
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM students s
    JOIN users u ON u.id = s.user_id AND u.is_active = 1 AND u.email_verified = 1
    $where
");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// Fetch
$listStmt = $pdo->prepare("
    SELECT s.id, s.user_id, s.first_name, s.last_name,
           s.grade_level, s.school_name, s.profile_photo,
           u.email, u.created_at AS registered_at
    FROM students s
    JOIN users u ON u.id = s.user_id AND u.is_active = 1 AND u.email_verified = 1
    $where
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$listStmt->execute($params);
$students = $listStmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(true, 'Registered students retrieved.', [
    'students'   => $students,
    'pagination' => [
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int) ceil($total / $perPage),
    ],
]);
