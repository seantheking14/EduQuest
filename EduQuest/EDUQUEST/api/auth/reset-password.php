<?php
/**
 * Reset Password Endpoint
 * POST /api/auth/reset-password.php
 * Resets the user's password using a valid reset token
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

try {
    // Get data from request
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON payload');
    }

    $email = trim($input['email'] ?? '');
    $token = trim($input['token'] ?? $input['otp'] ?? '');  // Accept both field names
    $newPassword = $input['newPassword'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';

    // ============================================================
    // BASIC INPUT VALIDATION (email and token only)
    // ============================================================

    $errors = [];

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($token)) {
        $errors['token'] = 'Reset token is required';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ]);
        exit;
    }

    // ============================================================
    // VALIDATE TOKEN FIRST (before password validation)
    // ============================================================

    $db = getDBConnection();

    // Find user by email
    $stmt = $db->prepare('SELECT id, email, first_name, last_name FROM users WHERE LOWER(email) = LOWER(:email)');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid reset token or email',
        ]);
        exit;
    }

    // Verify token
    $tokenHash = hashToken($token);

    $stmt = $db->prepare(
        "SELECT id, token, expires_at, used_at FROM password_reset_tokens
         WHERE user_id = :user_id"
    );
    $stmt->execute([':user_id' => $user['id']]);
    $allRecords = $stmt->fetchAll();

    // Now search with the hash
    $stmt = $db->prepare(
        "SELECT id FROM password_reset_tokens
         WHERE user_id = :user_id AND token = :token AND expires_at > NOW() AND used_at IS NULL"
    );
    $stmt->execute([
        ':user_id' => $user['id'],
        ':token' => $tokenHash,
    ]);

    $resetRecord = $stmt->fetch();

    if (!$resetRecord) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired reset token',
        ]);
        exit;
    }



    // ============================================================
    // NOW VALIDATE PASSWORDS (only if token is valid)
    // ============================================================

    $errors = [];

    if (empty($newPassword)) {
        // If password is empty, this is just OTP verification
        // Return 200 to indicate OTP is valid
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'OTP verified successfully',
            'otp_verified' => true,
        ]);
        exit;
    }

    // ============================================================
    // VALIDATE PASSWORD (if non-empty)
    // ============================================================

    $errors = [];

    if (empty($newPassword)) {
        // This case is already handled above
    } elseif ($newPassword !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match';
    } else {
        $passwordValidation = validatePassword($newPassword);
        if (!$passwordValidation['valid']) {
            $errors['newPassword'] = is_array($passwordValidation['errors'])
                ? $passwordValidation['errors'][0]
                : $passwordValidation['errors'];
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ]);
        exit;
    }

    // ============================================================
    // UPDATE PASSWORD AND MARK TOKEN AS USED
    // ============================================================

    try {
        $db->beginTransaction();

        // Hash new password
        $newPasswordHash = hashPassword($newPassword);

        // Update user password
        $stmt = $db->prepare(
            "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :user_id"
        );
        $stmt->execute([
            ':user_id' => $user['id'],
            ':password_hash' => $newPasswordHash,
        ]);

        // Mark token as used
        $stmt = $db->prepare(
            "UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :token_id"
        );
        $stmt->execute([':token_id' => $resetRecord['id']]);

        // Invalidate all existing sessions (force re-login) - optional, may not exist
        try {
            $stmt = $db->prepare(
                "DELETE FROM user_sessions WHERE user_id = :user_id"
            );
            $stmt->execute([':user_id' => $user['id']]);
        } catch (Exception $sessionEx) {
            // user_sessions table may not exist - ignore
        }

        $db->commit();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully. You can now log in with your new password.',
            'data' => [
                'userId' => $user['id'],
                'email' => $email,
                'redirectUrl' => 'login/login.html',
            ],
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log('DEBUG: Outer exception in reset-password: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]
    ]);
    exit;
}