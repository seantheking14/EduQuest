<?php
/**
 * Notifications Fetch
 * GET /api/notifications/fetch.php
 *
 * Returns the 20 most recent notifications for the authenticated user,
 * plus an unread count. Called by the frontend every 30 seconds.
 *
 * Response:
 *   { success, unread_count, notifications: [{ id, message, link, is_read, created_at, created_at_raw }] }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    // requireAuth() overrides $user['id'] with teachers.id for teachers.
    // For students, $user['id'] is still users.id — look up students.id.
    if ($user['role'] === 'student') {
        $stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $user['id']]);
        $row         = $stmt->fetch();
        $recipientId = $row ? (int) $row['id'] : (int) $user['id'];
    } else {
        // teacher / admin  — $user['id'] is already teachers.id
        $recipientId = (int) $user['id'];
    }

    // admins share the teacher role bucket in notifications
    $recipientRole = ($user['role'] === 'admin') ? 'teacher' : $user['role'];

    // ── Unread count ──────────────────────────────────────────
    $cntStmt = $db->prepare(
        'SELECT COUNT(*) FROM notifications
         WHERE recipient_id = :rid AND recipient_role = :role AND is_read = 0'
    );
    $cntStmt->execute([':rid' => $recipientId, ':role' => $recipientRole]);
    $unreadCount = (int) $cntStmt->fetchColumn();

    // ── Fetch last 20 notifications ───────────────────────────
    $listStmt = $db->prepare(
        'SELECT id, message, link, is_read, created_at
         FROM notifications
         WHERE recipient_id = :rid AND recipient_role = :role
         ORDER BY created_at DESC
         LIMIT 20'
    );
    $listStmt->execute([':rid' => $recipientId, ':role' => $recipientRole]);
    $rows = $listStmt->fetchAll();

    $notifications = [];
    foreach ($rows as $row) {
        // Convert UTC timestamp to GMT+8 (Philippines)
        $timestamp = strtotime($row['created_at']);
        // Create DateTime in UTC, then convert to GMT+8
        $dateUTC = new DateTime($row['created_at'], new DateTimeZone('UTC'));
        $dateUTC->setTimezone(new DateTimeZone('Asia/Manila')); // GMT+8
        
        $notifications[] = [
            'id'             => (int) $row['id'],
            'message'        => $row['message'],
            'link'           => $row['link'],
            'is_read'        => (int) $row['is_read'],
            // Formatted for display in GMT+8 (PHP "M d, Y g:i A")
            'created_at'     => $dateUTC->format('M d, Y g:i A'),
            // Raw timestamp for JavaScript relative-time calculation (in GMT+8)
            'created_at_raw' => $dateUTC->format('Y-m-d H:i:s'),
        ];
    }

    echo json_encode([
        'success'       => true,
        'unread_count'  => $unreadCount,
        'notifications' => $notifications,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
