<?php
/**
 * Logout Endpoint
 * POST /api/auth/logout.php
 * Invalidates the current session
 */

header('Content-Type: application/json');
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
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../utils/Security.php';
require_once __DIR__ . '/../../middleware/auth.php';

try {
    // Get the session token from cookie or header
    $sessionToken = null;

    if (!empty($_COOKIE[SESSION_COOKIE_NAME])) {
        $sessionToken = $_COOKIE[SESSION_COOKIE_NAME];
    } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        // Extract from Bearer token
        preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches);
        if (!empty($matches[1])) {
            $sessionToken = $matches[1];
        }
    }

    $userId = null;

    // If we have a session token, invalidate it
    if ($sessionToken) {
        $db = getDBConnection();
        $tokenHash = hashToken($sessionToken);

        // Find and delete the session
        $stmt = $db->prepare(
            "SELECT user_id FROM user_sessions WHERE session_token = :token"
        );
        $stmt->execute([':token' => $tokenHash]);
        $session = $stmt->fetch();

        if ($session) {
            $userId = $session['user_id'];

            $stmt = $db->prepare(
                "DELETE FROM user_sessions WHERE session_token = :token"
            );
            $stmt->execute([':token' => $tokenHash]);
        }
    }

    // ============================================================
    // CLEAR AUTHENTICATION COOKIES
    // ============================================================

    clearAuthCookies();

    if ($userId) {
        logAuthEvent('logout_success', ['user_id' => $userId]);
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully',
    ]);

} catch (Exception $e) {
    logAuthEvent('logout_error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during logout',
    ]);
}
