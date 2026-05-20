<?php
/**
 * Email Verification Endpoint
 * POST /api/auth/verify-email.php
 * Verifies a user's email address using a token
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
require_once __DIR__ . '/../../utils/BruteForceProtection.php';

try {
    // Get token from query params or POST body
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = trim($input['token'] ?? $_GET['token'] ?? '');
    $email = trim($input['email'] ?? $_GET['email'] ?? '');

    if (empty($token)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Verification token is required',
        ]);
        exit;
    }

    if (empty($email) || !validateEmail($email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Valid email is required',
        ]);
        exit;
    }

    $db = getDBConnection();

    // ============================================================
    // FIND USER BY EMAIL
    // ============================================================

    $stmt = $db->prepare('SELECT id, email_verified, is_active FROM users WHERE LOWER(email) = LOWER(:email)');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        logSecurityEvent('verify_email_user_not_found', ['email' => $email]);
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found',
        ]);
        exit;
    }

    // Check if already verified
    if ($user['email_verified']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email is already verified',
        ]);
        exit;
    }

    // ============================================================
    // VERIFY TOKEN
    // ============================================================

    $tokenHash = hashToken($token);

    $stmt = $db->prepare(
        "SELECT id FROM email_verification_tokens
         WHERE user_id = :user_id AND token = :token AND expires_at > NOW()"
    );
    $stmt->execute([
        ':user_id' => $user['id'],
        ':token' => $tokenHash,
    ]);

    $tokenRecord = $stmt->fetch();

    if (!$tokenRecord) {
        logSecurityEvent('verify_email_token_invalid', ['email' => $email]);
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired verification token',
        ]);
        exit;
    }

    // ============================================================
    // MARK EMAIL AS VERIFIED AND ACTIVATE ACCOUNT
    // ============================================================

    $db->beginTransaction();

    try {
        // Update user to mark email as verified and activate account
        $stmt = $db->prepare(
            "UPDATE users
             SET email_verified = 1, is_active = 1, email_verified_at = NOW()
             WHERE id = :user_id"
        );
        $stmt->execute([':user_id' => $user['id']]);

        // Delete the verification token (it's been used)
        $stmt = $db->prepare(
            "DELETE FROM email_verification_tokens WHERE user_id = :user_id"
        );
        $stmt->execute([':user_id' => $user['id']]);

        $db->commit();

        logAuthEvent('email_verified', ['user_id' => $user['id'], 'email' => $email]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully! You can now log in.',
            'data' => [
                'userId' => $user['id'],
                'email' => $email,
                'verified' => true,
            ],
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    logAuthEvent('email_verification_error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during email verification',
    ]);
}
