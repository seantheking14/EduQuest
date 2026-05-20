<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

header('Content-Type: application/json');

$teacher  = requireAuth();
$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$courseId = isset($body['id']) ? (int)$body['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if (!$courseId) {
    jsonResponse(false, 'Course ID required.', [], 400);
}

$pdo = getDBConnection();

$check = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND teacher_id = ? AND is_active = 1');
$check->execute([$courseId, $teacher['id']]);
if (!$check->fetch()) {
    jsonResponse(false, 'Course not found.', [], 404);
}

// Soft-delete the course (cascade will handle modules/materials/enrollments)
$pdo->prepare('UPDATE courses SET is_active = 0 WHERE id = ?')->execute([$courseId]);

jsonResponse(true, 'Course deleted.', []);
