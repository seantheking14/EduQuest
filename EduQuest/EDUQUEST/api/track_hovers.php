<?php
/**
 * POST /EDUQUEST/api/track_hovers.php
 *
 * Body (JSON): {
 *   user_id, page_name,
 *   hovers: [ { element_label: string, total_hover_ms: int }, ... ]
 * }
 *
 * Upserts rows in hover_events (accumulate total_hover_ms if same student/page/label/date).
 * All inserts/updates wrapped in a single transaction.
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

// ── Validate payload ───────────────────────────────────────────────────────────
$pageName = trim((string) ($body['page_name'] ?? ''));
$hovers   = $body['hovers'] ?? [];

if ($pageName === '' || strlen($pageName) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid page_name.']);
    exit;
}
if (!is_array($hovers) || count($hovers) === 0) {
    echo json_encode(['success' => true]); // nothing to do
    exit;
}

// ── Upsert in transaction ──────────────────────────────────────────────────────
try {
    $db->beginTransaction();

    $upsert = $db->prepare('
        INSERT INTO hover_events (student_id, page_name, element_label, total_hover_ms, session_date)
        VALUES (:sid, :page, :label, :ms, CURDATE())
        ON DUPLICATE KEY UPDATE total_hover_ms = total_hover_ms + VALUES(total_hover_ms)
    ');

    foreach ($hovers as $item) {
        $label = trim((string) ($item['element_label'] ?? ''));
        $ms    = max(0, (int) ($item['total_hover_ms'] ?? 0));

        if ($label === '' || strlen($label) > 200) continue;

        $upsert->execute([
            ':sid'   => $studentId,
            ':page'  => $pageName,
            ':label' => $label,
            ':ms'    => $ms,
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('track_hovers error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
