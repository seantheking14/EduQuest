<?php
/**
 * Teacher API – Log Self-Regulation Behavior
 * POST /EDUQUEST/api/teacher_logs_log.php
 *
 * Body (JSON):
 * {
 *   student_id:      int,      // students.id
 *   indicator_key:   string,   // one of the 5 self-regulation keys
 *   indicator_value: string,   // free-text observation
 *   session_date:    string    // Y-m-d, optional (defaults to today)
 * }
 *
 * Only accepts the 5 self-regulation indicators (teacher-observed).
 * Engagement indicators are system-logged only.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/log_behavior.php';

$user      = requireTeacher();
$teacherId = (int) $user['id']; // teachers.id

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
    exit;
}

// ── Validate inputs ────────────────────────────────────────────────────────────
$studentId      = isset($body['student_id']) && ctype_digit((string) $body['student_id'])
    ? (int) $body['student_id'] : 0;
$indicatorKey   = trim((string) ($body['indicator_key']   ?? ''));
$indicatorValue = trim((string) ($body['indicator_value'] ?? ''));
$sessionDate    = trim((string) ($body['session_date']    ?? ''));

// Only teacher-observable self-regulation indicators are allowed via this endpoint
$selfRegKeys = [
    'task_initiation',
    'task_persistence',
    'consistency_of_completion',
    'responsiveness_to_feedback',
    'frustration_management',
];

if ($studentId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid student_id.']);
    exit;
}
if (!in_array($indicatorKey, $selfRegKeys, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid indicator_key. Only self-regulation indicators may be logged via this endpoint.']);
    exit;
}
if ($indicatorValue === '' || strlen($indicatorValue) > 255) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'indicator_value is required (max 255 characters).']);
    exit;
}
if ($sessionDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid session_date format. Use Y-m-d.']);
    exit;
}

// ── Verify student belongs to this teacher ─────────────────────────────────────
try {
    $db   = getDBConnection();
    $stmt = $db->prepare('SELECT id FROM students WHERE id = :sid AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':sid' => $studentId, ':tid' => $teacherId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Student not found or does not belong to you.']);
        exit;
    }

    // ── Insert via log_behavior helper ─────────────────────────────────────────
    log_behavior(
        $db,
        $studentId,
        'self_regulation',
        $indicatorKey,
        $indicatorValue,
        'teacher',
        $teacherId,
        $sessionDate ?: null
    );

    echo json_encode(['success' => true, 'message' => 'Behavioral log saved.']);

} catch (PDOException $e) {
    error_log('teacher_logs_log error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
