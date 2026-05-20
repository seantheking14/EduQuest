<?php
/**
 * Test OTP Comparison - DEBUG ONLY
 * POST /api/auth/test-otp-compare.php
 * Compares the OTP entered by user with what's stored
 * 
 * POST data:
 * {
 *   "email": "user@example.com",
 *   "otp": "123456"
 * }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/Security.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$otp = trim($input['otp'] ?? '');

error_log('DEBUG COMPARE: Email: [' . $email . ']');
error_log('DEBUG COMPARE: OTP: [' . $otp . ']');
error_log('DEBUG COMPARE: OTP length: ' . strlen($otp));
error_log('DEBUG COMPARE: OTP bytes: ' . implode(',', array_map('ord', str_split($otp))));

if (empty($email) || empty($otp)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and OTP required']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Get user
    $stmt = $db->prepare('SELECT id, email FROM users WHERE LOWER(email) = LOWER(:email)');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Hash the OTP user entered
    $userOtpHash = hashToken($otp);
    error_log('DEBUG COMPARE: User OTP hash: ' . $userOtpHash);
    
    // Get tokens from database
    $stmt = $db->prepare(
        "SELECT id, token, expires_at, used_at
         FROM password_reset_tokens
         WHERE user_id = :user_id
         ORDER BY created_at DESC LIMIT 1"
    );
    $stmt->execute([':user_id' => $user['id']]);
    $dbToken = $stmt->fetch();
    
    if (!$dbToken) {
        echo json_encode([
            'success' => false,
            'message' => 'No OTP found in database for this user',
            'email' => $email,
            'user_id' => $user['id'],
        ]);
        exit;
    }
    
    error_log('DEBUG COMPARE: DB token hash: ' . $dbToken['token']);
    
    $isExpired = strtotime($dbToken['expires_at']) < time();
    $isUsed = $dbToken['used_at'] !== null;
    $hashMatches = ($userOtpHash === $dbToken['token']);
    
    error_log('DEBUG COMPARE: Hash matches: ' . ($hashMatches ? 'YES' : 'NO'));
    error_log('DEBUG COMPARE: Is expired: ' . ($isExpired ? 'YES' : 'NO'));
    error_log('DEBUG COMPARE: Is used: ' . ($isUsed ? 'YES' : 'NO'));
    
    http_response_code(200);
    echo json_encode([
        'success' => $hashMatches && !$isExpired && !$isUsed,
        'email' => $email,
        'otp_entered' => str_repeat('*', max(0, strlen($otp) - 1)) . (strlen($otp) > 0 ? substr($otp, -1) : ''),
        'hash_matches' => $hashMatches,
        'is_expired' => $isExpired,
        'is_used' => $isUsed,
        'expires_at' => $dbToken['expires_at'],
        'used_at' => $dbToken['used_at'],
        'user_otp_hash' => $userOtpHash,
        'db_token_hash' => $dbToken['token'],
        'status' => $hashMatches ? 
            ($isExpired ? 'EXPIRED' : ($isUsed ? 'ALREADY USED' : 'VALID')) 
            : 'HASH MISMATCH'
    ]);
    
} catch (Exception $e) {
    error_log('DEBUG COMPARE: Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
