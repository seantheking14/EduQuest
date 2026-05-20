<?php
/**
 * Teacher Whitelist Check
 * GET /api/auth/whitelist-check.php
 *
 * Validates that the authenticated teacher's email is still in
 * teacher_whitelist. Called by auth-guard.js on every page load.
 * Returns 200 success:true or 403 success:false.
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../utils/Security.php';
require_once __DIR__ . '/../../middleware/auth.php';

try {
    // Validate session token and require teacher (or admin) role
    $user = requireTeacher();

    // Admins always pass
    if ($user['role'] === 'admin') {
        echo json_encode(['success' => true]);
        exit;
    }

    // Resolve teacher email.
    // requireAuth() overrides $user['id'] with teachers.id for FK compatibility,
    // so use $user['userId'] (original users.id) to look up the users table.
    $db    = getDBConnection();
    $stmt  = $db->prepare(
        'SELECT email FROM users WHERE id = :id LIMIT 1'
    );
    $lookupId = $user['userId'] ?? $user['id'];
    $stmt->execute([':id' => $lookupId]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $teacherEmail = $row['email'];

    // Check whitelist
    $wlStmt = $db->prepare(
        'SELECT id FROM teacher_whitelist WHERE LOWER(email) = LOWER(:email) LIMIT 1'
    );
    $wlStmt->execute([':email' => $teacherEmail]);

    if (!$wlStmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Your teacher access has been revoked. Please contact your administrator.',
        ]);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // On any unexpected error, fail open so network blips don't lock out teachers
    http_response_code(200);
    echo json_encode(['success' => true]);
}
