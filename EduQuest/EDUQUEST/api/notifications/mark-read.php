<?php
/**
 * Notifications Mark Read
 * POST /api/notifications/mark-read.php
 *
 * Marks one or all notifications as read for the authenticated user.
 *
 * JSON body (optional):
 *   { "notification_id": 42 }   ← mark only this notification
 *   {}                           ← mark ALL notifications as read
 *
 * Response:
 *   { "success": true }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

try {
    $user = requireAuth();
    $db   = getDBConnection();

    // ── Resolve profile-table ID ──────────────────────────────
    if ($user['role'] === 'student') {
        $stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $user['id']]);
        $row         = $stmt->fetch();
        $recipientId = $row ? (int) $row['id'] : (int) $user['id'];
    } else {
        $recipientId = (int) $user['id'];
    }

    $recipientRole = ($user['role'] === 'admin') ? 'teacher' : $user['role'];

    // ── Parse optional notification_id ────────────────────────
    $input          = json_decode(file_get_contents('php://input'), true) ?? [];
    $notificationId = isset($input['notification_id']) ? (int) $input['notification_id'] : 0;

    if ($notificationId > 0) {
        // Mark a single notification — must belong to this user
        $stmt = $db->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE id = :id AND recipient_id = :rid AND recipient_role = :role'
        );
        $stmt->execute([
            ':id'   => $notificationId,
            ':rid'  => $recipientId,
            ':role' => $recipientRole,
        ]);
    } else {
        // Mark ALL unread notifications for this user
        $stmt = $db->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE recipient_id = :rid AND recipient_role = :role AND is_read = 0'
        );
        $stmt->execute([':rid' => $recipientId, ':role' => $recipientRole]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
