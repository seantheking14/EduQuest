<?php
/**
 * Password-Change OTP API
 *
 * POST { "action": "send_otp" }
 *   — Generates a 6-digit OTP, stores a bcrypt hash in `password_change_otps`,
 *     sends it to the authenticated student's email, and returns a masked
 *     version of the email so the UI can display it.
 *
 * Requires: Authorization: Bearer <token>  (student role)
 * Rate limiting: one OTP per 60 seconds; max 5 sends per 30 minutes per user.
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
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/Email.php';

$user = requireStudent();
$db   = getDBConnection();

// ── Ensure the OTP table exists ──────────────────────────────────────────────
$db->exec("
    CREATE TABLE IF NOT EXISTS password_change_otps (
        id          INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
        user_id     INT UNSIGNED        NOT NULL,
        otp_hash    VARCHAR(255)        NOT NULL,
        attempts    TINYINT UNSIGNED    NOT NULL DEFAULT 0,
        expires_at  TIMESTAMP           NOT NULL,
        used_at     TIMESTAMP           NULL DEFAULT NULL,
        created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || ($body['action'] ?? '') !== 'send_otp') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$userId = (int) $user['id'];

// ── Rate-limit: no resend within 60 seconds ───────────────────────────────
$stmt = $db->prepare("
    SELECT created_at FROM password_change_otps
    WHERE user_id = :uid AND used_at IS NULL AND expires_at > NOW()
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$latest = $stmt->fetch();

if ($latest) {
    $secondsAgo = time() - strtotime($latest['created_at']);
    if ($secondsAgo < 60) {
        $wait = 60 - $secondsAgo;
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => "Please wait {$wait} seconds before requesting a new code.",
        ]);
        exit;
    }
}

// ── Rate-limit: max 5 sends per 30 minutes ────────────────────────────────
$stmt = $db->prepare("
    SELECT COUNT(*) AS cnt FROM password_change_otps
    WHERE user_id = :uid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
");
$stmt->execute([':uid' => $userId]);
if ((int) $stmt->fetchColumn() >= 5) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many OTP requests. Please try again in 30 minutes.',
    ]);
    exit;
}

// ── Invalidate previous unused OTPs for this user ─────────────────────────
$db->prepare("DELETE FROM password_change_otps WHERE user_id = :uid")
   ->execute([':uid' => $userId]);

// ── Generate & store OTP ──────────────────────────────────────────────────
$otp     = sprintf('%06d', random_int(0, 999999));
$otpHash = password_hash($otp, PASSWORD_BCRYPT);

$stmt = $db->prepare("
    INSERT INTO password_change_otps (user_id, otp_hash, expires_at)
    VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
");
$stmt->execute([':uid' => $userId, ':hash' => $otpHash]);

// ── Fetch user's email & name ─────────────────────────────────────────────
$stmt = $db->prepare('SELECT email, first_name, last_name FROM users WHERE id = :uid LIMIT 1');
$stmt->execute([':uid' => $userId]);
$userRow = $stmt->fetch();

if (!$userRow) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'User record not found.']);
    exit;
}

$email    = $userRow['email'];
$name     = trim($userRow['first_name'] . ' ' . $userRow['last_name']);

// ── Build email HTML ──────────────────────────────────────────────────────
$templatePath = __DIR__ . '/../../emails/password-change-otp.html';
if (file_exists($templatePath)) {
    $html = file_get_contents($templatePath);
    $html = str_replace(['{NAME}', '{OTP_CODE}', '{EXPIRY_MINUTES}'], [$name, $otp, '10'], $html);
} else {
    // Inline fallback
    $html = "
    <div style='font-family:sans-serif;max-width:500px;margin:0 auto;padding:24px'>
        <h2 style='color:#6d28d9'>🔐 EduQuest Password Change</h2>
        <p>Hi {$name},</p>
        <p>Use the code below to verify your password change request:</p>
        <div style='font-size:40px;font-weight:bold;letter-spacing:10px;color:#6d28d9;
                    background:#f5f3ff;border-radius:12px;padding:20px;text-align:center;
                    margin:24px 0'>{$otp}</div>
        <p>This code expires in <strong>10 minutes</strong>.</p>
        <p style='color:#6b7280;font-size:13px'>If you did not request this, you can safely ignore this email.</p>
    </div>";
}

// ── Send email ────────────────────────────────────────────────────────────
$result = sendEmail(
    $email,
    $name,
    '🔐 EduQuest — Your Password Change Code',
    $html,
    "EduQuest Password Change Code\n\nHi {$name},\n\nYour verification code is: {$otp}\n\nThis code expires in 10 minutes.\n\nIf you did not request this, ignore this email."
);

if (!$result['success']) {
    // Clean up the stored OTP so the user can try again
    $db->prepare("DELETE FROM password_change_otps WHERE user_id = :uid")
       ->execute([':uid' => $userId]);

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    exit;
}

// ── Return masked email for display ──────────────────────────────────────
$atPos      = strrpos($email, '@');
$localPart  = substr($email, 0, $atPos);
$domain     = substr($email, $atPos);
$masked     = strlen($localPart) <= 2
    ? str_repeat('*', strlen($localPart)) . $domain
    : substr($localPart, 0, 2) . str_repeat('*', max(1, strlen($localPart) - 2)) . $domain;

echo json_encode([
    'success'      => true,
    'message'      => 'OTP sent successfully.',
    'maskedEmail'  => $masked,
]);
