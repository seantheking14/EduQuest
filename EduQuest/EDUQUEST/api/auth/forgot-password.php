<?php
/**
 * Forgot Password Endpoint
 * POST /api/auth/forgot-password.php
 * Sends a password reset link to the user's email
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
require_once __DIR__ . '/../../utils/Email.php';

try {
    // Get email from request
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON payload');
    }

    $email = trim($input['email'] ?? '');

    // ============================================================
    // INPUT VALIDATION
    // ============================================================

    if (empty($email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email is required',
        ]);
        exit;
    }

    if (!validateEmail($email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format',
        ]);
        exit;
    }

    // ============================================================
    // IMPORTANT: Don't reveal whether email exists or not
    // Just return generic success message
    // This prevents account enumeration attacks
    // ============================================================

    $db = getDBConnection();

    // Check if user exists
    $stmt = $db->prepare('SELECT id, first_name, last_name, email FROM users WHERE LOWER(email) = LOWER(:email)');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Always return success, but only send email if user exists
    $successResponse = [
        'success' => true,
        'message' => 'If an account with this email exists, a password reset link has been sent.',
    ];

    if ($user) {
        // ============================================================
        // GENERATE OTP (6-digit code)
        // ============================================================

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        error_log('DEBUG FORGOT: Generated OTP: ' . $otp);
        error_log('DEBUG FORGOT: OTP length: ' . strlen($otp));
        error_log('DEBUG FORGOT: OTP type: ' . gettype($otp));
        
        $otpHash = hashToken($otp);
        error_log('DEBUG FORGOT: OTP Hash: ' . $otpHash);
        $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY); // 1 hour

        // Delete any existing unexpired OTP for this user
        $stmt = $db->prepare(
            "DELETE FROM password_reset_tokens 
             WHERE user_id = :user_id AND expires_at > NOW() AND used_at IS NULL"
        );
        $stmt->execute([':user_id' => $user['id']]);

        // Store the OTP as a token
        $stmt = $db->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at)
             VALUES (:user_id, :token, :expires_at)"
        );

        $stmt->execute([
            ':user_id' => $user['id'],
            ':token' => $otpHash,
            ':expires_at' => $expiresAt,
        ]);

        // ============================================================
        // SEND OTP EMAIL
        // ============================================================

        $emailResult = sendPasswordResetEmail(
            $user['email'],
            $user['first_name'] . ' ' . $user['last_name'],
            $otp,  // Send the actual OTP (6 digits), not a link
            ''     // No URL needed for OTP
        );

        if ($emailResult['success']) {
            error_log('Password reset OTP sent successfully to: ' . $email);
        } else {
            error_log('Password reset OTP email failed for: ' . $email . ' - ' . $emailResult['message']);
        }
    } else {
        // Log the attempt (security monitoring)
        error_log('Password reset requested for unknown email: ' . $email);
    }

    // ============================================================
    // RESPONSE (always generic, don't leak whether email exists)
    // ============================================================

    http_response_code(200);
    echo json_encode($successResponse);

} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.',
    ]);
}
