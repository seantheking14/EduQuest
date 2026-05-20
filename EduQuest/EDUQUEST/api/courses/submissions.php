<?php
/**
 * GET  /api/courses/submissions.php?materialId=X
 *      Returns all student submissions for an assignment.
 *
 * POST /api/courses/submissions.php   { action: 'grade', submissionId, grade, feedback }
 *      Teacher grades/returns a submission.
 *
 * GET  /api/courses/submissions.php?action=download&submissionId=X
 *      Download a student's submitted file.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher = requireAuth();
$db      = getDBConnection();

// ─── Download a submission file ───
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'download') {
    $subId = (int) ($_GET['submissionId'] ?? 0);
    if (!$subId) { http_response_code(400); exit('submissionId required.'); }

    // Verify teacher owns the course
    $stmt = $db->prepare('
        SELECT s.stored_filename, s.original_filename, s.mime_type, s.file_size
        FROM assignment_submissions s
        JOIN course_materials mat ON mat.id = s.material_id
        JOIN courses c ON c.id = mat.course_id
        WHERE s.id = ? AND c.teacher_id = ?
    ');
    $stmt->execute([$subId, $teacher['id']]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub || !$sub['stored_filename']) {
        http_response_code(404);
        exit('Submission not found.');
    }

    $filePath = SUBMISSION_DIR . $sub['stored_filename'];
    if (!is_file($filePath)) {
        http_response_code(404);
        exit('File missing from server.');
    }

    $mime = $sub['mime_type'] ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . rawurlencode($sub['original_filename']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private');
    readfile($filePath);
    exit;
}

// ─── GET: List submissions for an assignment ───
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    $materialId = (int) ($_GET['materialId'] ?? 0);
    if (!$materialId) {
        echo json_encode(['success' => false, 'message' => 'materialId required.']);
        exit;
    }

    // Verify teacher owns this course
    $check = $db->prepare('
        SELECT mat.id
        FROM course_materials mat
        JOIN courses c ON c.id = mat.course_id
        WHERE mat.id = ? AND c.teacher_id = ? AND mat.material_type = \'assignment\'
    ');
    $check->execute([$materialId, $teacher['id']]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found.']);
        exit;
    }

    // Get all submissions + student info
    $stmt = $db->prepare("
        SELECT s.id, s.original_filename, s.file_size, s.mime_type,
               s.notes, s.status, s.grade, s.feedback,
               s.submitted_at, s.graded_at,
               st.id AS student_id,
               CONCAT(u.first_name, ' ', u.last_name) AS student_name
        FROM assignment_submissions s
        JOIN students st ON st.id = s.student_id
        JOIN users u ON u.id = st.user_id
        WHERE s.material_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$materialId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count enrolled students for context
    $enrolled = $db->prepare('
        SELECT COUNT(*) as cnt
        FROM course_enrollments ce
        JOIN course_materials mat ON mat.course_id = ce.course_id
        WHERE mat.id = ?
    ');
    $enrolled->execute([$materialId]);
    $enrolledCount = (int) $enrolled->fetch()['cnt'];

    $result = [];
    foreach ($submissions as $sub) {
        $result[] = [
            'id'              => (int) $sub['id'],
            'studentId'       => (int) $sub['student_id'],
            'studentName'     => $sub['student_name'],
            'originalFilename' => $sub['original_filename'],
            'fileSize'        => (int) $sub['file_size'],
            'mimeType'        => $sub['mime_type'],
            'notes'           => $sub['notes'],
            'status'          => $sub['status'],
            'grade'           => $sub['grade'],
            'feedback'        => $sub['feedback'],
            'submittedAt'     => $sub['submitted_at'],
            'gradedAt'        => $sub['graded_at'],
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'submissions'   => $result,
            'enrolledCount' => $enrolledCount,
            'submittedCount' => count($result),
        ],
    ]);
    exit;
}

// ─── POST: Grade a submission ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    $action       = $body['action'] ?? '';
    $submissionId = (int) ($body['submissionId'] ?? 0);

    if ($action !== 'grade' || !$submissionId) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    $grade    = isset($body['grade']) && $body['grade'] !== '' ? (float) $body['grade'] : null;
    $feedback = trim($body['feedback'] ?? '');

    // Verify teacher owns the course
    $check = $db->prepare('
        SELECT s.id
        FROM assignment_submissions s
        JOIN course_materials mat ON mat.id = s.material_id
        JOIN courses c ON c.id = mat.course_id
        WHERE s.id = ? AND c.teacher_id = ?
    ');
    $check->execute([$submissionId, $teacher['id']]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Submission not found.']);
        exit;
    }

    $stmt = $db->prepare('
        UPDATE assignment_submissions
        SET grade = ?, feedback = ?, status = \'graded\', graded_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$grade, $feedback, $submissionId]);

    echo json_encode([
        'success' => true,
        'message' => 'Submission graded successfully.',
    ]);
    exit;
}

http_response_code(405);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
