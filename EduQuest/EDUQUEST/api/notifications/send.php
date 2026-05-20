<?php
/**
 * send_notification() — reusable helper
 *
 * Include this file via require_once; it must NOT be called via HTTP.
 * It simply inserts one row into the notifications table.
 * Failures are swallowed so they never break the calling script.
 *
 * Parameters:
 *   $pdo            PDO       Active database connection
 *   $recipient_id   int       teachers.id  OR  students.id  (profile-table ID)
 *   $recipient_role string    'teacher' | 'student'
 *   $message        string    Notification text (max 500 chars)
 *   $link           string|null  Optional URL the user is sent to on click
 *
 * Usage example:
 *
 *   require_once __DIR__ . '/../api/notifications/send.php';
 *   send_notification(
 *       $pdo,
 *       $student_id,           // students.id
 *       'student',
 *       'A new activity has been posted!',
 *       'student_dashboard.php?tab=activities'
 *   );
 *
 * To notify a teacher (uses teachers.id):
 *   send_notification($pdo, $teacher_id, 'teacher', 'A student submitted work.');
 */

if (!function_exists('send_notification')) {
    function send_notification(
        PDO     $pdo,
        int     $recipient_id,
        string  $recipient_role,
        string  $message,
        ?string $link = null
    ): void {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO notifications (recipient_id, recipient_role, message, link)
                 VALUES (:rid, :role, :message, :link)'
            );
            $stmt->execute([
                ':rid'     => $recipient_id,
                ':role'    => $recipient_role,
                ':message' => mb_substr($message, 0, 500),
                ':link'    => $link !== null ? mb_substr($link, 0, 255) : null,
            ]);
        } catch (Exception $e) {
            // Fail silently — notification errors must never break the caller.
        }
    }
}
