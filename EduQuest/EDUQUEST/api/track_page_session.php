<?php
/**
 * POST /EDUQUEST/api/track_page_session.php
 *
 * Body (JSON): { user_id, page_name, duration_seconds }
 *
 * Validates bearer token session. Resolves students.id from users.id.
 * Inserts one row into page_sessions.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$authUser = requireAuth();

$body = json_decode(file_get_contents('php://input'), true);

// ── Validate posted user_id matches session ────────────────────────────────────
$postedUserId = isset($body['user_id']) ? (int) $body['user_id'] : 0;
if ($postedUserId !== (int) $authUser['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden.']);
    exit;
}

// ── Resolve students.id ────────────────────────────────────────────────────────
$db   = getDBConnection();
$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $authUser['id']]);
$student = $stmt->fetch();

if (!$student) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
    exit;
}
$studentId = (int) $student['id'];

// ── Sanitize inputs ────────────────────────────────────────────────────────────
$pageName        = trim((string) ($body['page_name']        ?? ''));
$durationSeconds = max(0, (int) ($body['duration_seconds'] ?? 0));

if ($pageName === '' || strlen($pageName) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid page_name.']);
    exit;
}

// ── Insert ─────────────────────────────────────────────────────────────────────
try {
    $stmt = $db->prepare('
        INSERT INTO page_sessions
            (student_id, page_name, session_start, session_end, duration_seconds)
        VALUES
            (:sid, :page, DATE_SUB(NOW(), INTERVAL :dur SECOND), NOW(), :dur)
    ');
    $stmt->execute([
        ':sid'  => $studentId,
        ':page' => $pageName,
        ':dur'  => $durationSeconds,
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('track_page_session error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
