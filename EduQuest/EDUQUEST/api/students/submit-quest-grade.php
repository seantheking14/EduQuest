<?php
/**
 * POST /api/students/submit-quest-grade.php
 * Auto-records a grade when a student completes a quest/activity.
 * Called by the student game engine — no XP is awarded here
 * (XP is handled separately via track-activity.php).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only authenticated students may submit quest grades
$user = requireStudent();
$userId = (int) $user['id']; // users.id

try {
    $db = getDBConnection();

    // Resolve students.id and teacher_id from the users.id
    $stmt = $db->prepare("SELECT id, teacher_id FROM students WHERE user_id = :uid AND is_active = 1 LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    $studentRow = $stmt->fetch();

    if (!$studentRow) {
        jsonResponse(false, 'Student profile not found.', [], 404);
    }

    $studentId = (int) $studentRow['id'];
    $teacherId = (int) $studentRow['teacher_id'];

    if (!$studentId || !$teacherId) {
        jsonResponse(false, 'Unable to resolve student or teacher record.', [], 422);
    }

    // Parse input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(false, 'Invalid JSON payload.', [], 400);
    }

    $assessmentName = sanitizeString($input['assessment_name'] ?? '');
    $assessmentType = $input['assessment_type'] ?? 'quiz';
    $score          = isset($input['score']) ? (float) $input['score'] : null;
    $maxScore       = isset($input['max_score']) ? (float) $input['max_score'] : 100;
    $remarks        = sanitizeString($input['remarks'] ?? '');

    // Validation
    $errors = [];
    if (!$assessmentName) $errors[] = 'Assessment name is required.';
    if ($score === null || $score < 0) $errors[] = 'Score must be 0 or greater.';
    if ($maxScore <= 0) $errors[] = 'Max score must be greater than 0.';
    if ($score > $maxScore) $errors[] = 'Score cannot exceed max score.';

    $validTypes = ['quiz', 'exam', 'assignment', 'project', 'participation', 'final'];
    if (!in_array($assessmentType, $validTypes, true)) {
        $errors[] = 'Invalid assessment type.';
    }

    if ($errors) {
        jsonResponse(false, implode(' ', $errors), ['errors' => $errors], 422);
    }

    $gradedAt = date('Y-m-d');

    // Insert the grade record
    $stmt = $db->prepare("
        INSERT INTO student_grades
            (student_id, course_id, teacher_id, assessment_name, assessment_type, score, max_score, graded_at, remarks)
        VALUES (:sid, NULL, :tid, :name, :type, :score, :max, :date, :remarks)
    ");
    $stmt->execute([
        ':sid'     => $studentId,
        ':tid'     => $teacherId,
        ':name'    => $assessmentName,
        ':type'    => $assessmentType,
        ':score'   => $score,
        ':max'     => $maxScore,
        ':date'    => $gradedAt,
        ':remarks' => $remarks ?: null,
    ]);

    $gradeId = (int) $db->lastInsertId();
    $pct = round(($score / $maxScore) * 100, 1);

    jsonResponse(true, 'Quest grade recorded.', [
        'grade_id'   => $gradeId,
        'percentage' => $pct,
    ], 201);

} catch (Exception $e) {
    jsonResponse(false, 'Failed to save quest grade.', [], 500);
}
