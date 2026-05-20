<?php
/**
 * GET /api/students/list.php
 * Return all active students belonging to the authenticated teacher.
 * Supports search and pagination via query params:
 *   ?search=Name&page=1&per_page=20
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher = requireAuth();
$pdo     = getDBConnection();

$search  = sanitizeString($_GET['search']   ?? '');
$page    = max(1, (int)($_GET['page']       ?? 1));
$perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
$offset  = ($page - 1) * $perPage;

// Base query
$where  = 'WHERE s.teacher_id = ? AND s.is_active = 1';
$params = [$teacher['id']];

if ($search !== '') {
    $where   .= ' AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id_number LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Count total active
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students s $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// Count inactive students for this teacher
$inactiveStmt = $pdo->prepare("SELECT COUNT(*) FROM students s WHERE s.teacher_id = ? AND s.is_active = 0");
$inactiveStmt->execute([$teacher['id']]);
$inactiveCount = (int) $inactiveStmt->fetchColumn();

// Fetch page
$listStmt = $pdo->prepare("
    SELECT s.id, s.first_name, s.last_name, s.date_of_birth, s.gender,
           s.grade_level, s.school_name, s.student_id_number,
           s.parent_guardian_name, s.parent_guardian_phone,
           s.profile_photo, s.created_at,
           ap.adhd_type, ap.severity AS adhd_severity
    FROM students s
    LEFT JOIN adhd_profiles ap ON ap.student_id = s.id
    $where
    ORDER BY s.last_name ASC, s.first_name ASC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$listStmt->execute($params);
$students = $listStmt->fetchAll();

jsonResponse(true, 'Students retrieved.', [
    'students'       => $students,
    'inactive_count' => $inactiveCount,
    'pagination' => [
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int) ceil($total / $perPage),
    ],
]);
