<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

header('Content-Type: application/json');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$teacher  = requireAuth();
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

$courseId   = isset($body['id']) ? (int)$body['id'] : 0;
$title      = sanitizeString($body['title']       ?? '');
$description= sanitizeString($body['description'] ?? '');
$subject    = sanitizeString($body['subject']     ?? '');
$gradeLevel = sanitizeString($body['grade_level'] ?? '');
$schoolYear = sanitizeString($body['school_year'] ?? '');
$coverColor = sanitizeString($body['cover_color'] ?? '#6366f1');

if (!$title) {
    jsonResponse(false, 'Course title is required.', [], 422);
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $coverColor)) {
    $coverColor = '#6366f1';
}

$pdo = getDBConnection();

if ($courseId) {
    $check = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND teacher_id = ? AND is_active = 1');
    $check->execute([$courseId, $teacher['id']]);
    if (!$check->fetch()) {
        jsonResponse(false, 'Course not found.', [], 404);
    }
    $pdo->prepare('
        UPDATE courses
        SET title=?, description=?, subject=?, grade_level=?, school_year=?, cover_color=?
        WHERE id=?
    ')->execute([$title, $description, $subject, $gradeLevel, $schoolYear, $coverColor, $courseId]);

    jsonResponse(true, 'Course updated.', ['id' => $courseId]);
} else {
    $pdo->prepare('
        INSERT INTO courses (teacher_id, title, description, subject, grade_level, school_year, cover_color)
        VALUES (?,?,?,?,?,?,?)
    ')->execute([$teacher['id'], $title, $description, $subject, $gradeLevel, $schoolYear, $coverColor]);
    $id = (int)$pdo->lastInsertId();

    jsonResponse(true, 'Course created.', ['id' => $id], 201);
}
