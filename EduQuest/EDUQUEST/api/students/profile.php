<?php
/**
 * Student Profile API (self-service)
 *
 * GET  — Returns the authenticated student's profile (name, email, grade, bio, avatarId)
 * POST — Updates the student's avatar selection
 * PUT  — Updates the student's personal information (firstName, lastName, grade, bio)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/Security.php';

// Only students may access their own profile through this endpoint
$user = requireStudent();
$db   = getDBConnection();

// Resolve the students.id from users.id
$stmt = $db->prepare('SELECT id, first_name, last_name, grade_level, notes, profile_photo FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['id']]);
$student = $stmt->fetch();

if (!$student) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student record not found.']);
    exit;
}

// ── Allowed avatar IDs (whitelist) ──
$allowedAvatars = [
    // Free
    'student', 'nerd', 'cool', 'happy', 'star', 'rocket', 'fox', 'cat', 'dog', 'panda',
    // Level-locked (server doesn't enforce level gate for now — the UI handles it)
    'wizard', 'ninja', 'astronaut', 'dragon', 'unicorn', 'phoenix', 'crown', 'alien',
];

// ────── GET: return profile ──────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'data' => [
            'firstName' => $student['first_name'],
            'lastName'  => $student['last_name'],
            'email'     => $user['email'] ?? null,
            'grade'     => $student['grade_level'] ?: '',
            'bio'       => $student['notes'] ?: '',
            'avatarId'  => $student['profile_photo'] ?: 'student',
        ],
    ]);
    exit;
}

// ────── POST: update avatar OR change password ──────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
        exit;
    }

    $action = $body['action'] ?? 'update_avatar';

    // ── Change password ──────────────────────────────────
    if ($action === 'change_password') {
        $currentPassword = $body['current_password'] ?? '';
        $newPassword     = $body['new_password']     ?? '';
        $otpCode         = trim($body['otp_code']    ?? '');

        if (empty($currentPassword) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current and new password are required.']);
            exit;
        }

        if (empty($otpCode)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Verification code is required.']);
            exit;
        }

        // ── Ensure OTP table exists (may not on first run) ──
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

        // ── Fetch active OTP record ──
        $stmt = $db->prepare("
            SELECT id, otp_hash, attempts FROM password_change_otps
            WHERE user_id = :uid AND used_at IS NULL AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([':uid' => $user['id']]);
        $otpRow = $stmt->fetch();

        if (!$otpRow) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Verification code has expired or was not sent. Please request a new code.']);
            exit;
        }

        // ── Brute-force guard: max 5 attempts ──
        if ((int) $otpRow['attempts'] >= 5) {
            // Invalidate
            $db->prepare("DELETE FROM password_change_otps WHERE id = :id")
               ->execute([':id' => $otpRow['id']]);
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.']);
            exit;
        }

        // ── Verify OTP ──
        if (!password_verify($otpCode, $otpRow['otp_hash'])) {
            // Increment attempts
            $db->prepare("UPDATE password_change_otps SET attempts = attempts + 1 WHERE id = :id")
               ->execute([':id' => $otpRow['id']]);
            $remaining = 5 - ((int) $otpRow['attempts'] + 1);
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Incorrect verification code.' . ($remaining > 0 ? " {$remaining} attempt(s) remaining." : ' Code invalidated.'),
            ]);
            exit;
        }

        // OTP is valid — validate password strength before consuming the OTP

        // ── Validate new password strength ──
        $validation = validatePassword($newPassword);
        if (!$validation['valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
            exit;
        }

        // ── Verify current password ──
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :uid');
        $stmt->execute([':uid' => $user['id']]);
        $row = $stmt->fetch();

        if (!$row || !verifyPassword($currentPassword, $row['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }

        // ── Prevent reusing the same password ──
        if (verifyPassword($newPassword, $row['password_hash'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'New password must be different from current password.']);
            exit;
        }

        // ── All checks passed — mark OTP as used and update password ──
        $db->prepare("UPDATE password_change_otps SET used_at = NOW() WHERE id = :id")
           ->execute([':id' => $otpRow['id']]);

        $newHash = hashPassword($newPassword);
        $stmt = $db->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :uid');
        $stmt->execute([':hash' => $newHash, ':uid' => $user['id']]);

        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
        exit;
    }

    // ── Update avatar (default POST action) ──────────────
    if (empty($body['avatarId'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'avatarId is required.']);
        exit;
    }

    $avatarId = trim($body['avatarId']);

    // Validate against whitelist
    if (!in_array($avatarId, $allowedAvatars, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid avatar selection.']);
        exit;
    }

    // Persist — we reuse the existing profile_photo column
    $update = $db->prepare('UPDATE students SET profile_photo = :avatar, updated_at = NOW() WHERE id = :sid');
    $update->execute([':avatar' => $avatarId, ':sid' => $student['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Avatar updated successfully.',
        'data'    => ['avatarId' => $avatarId],
    ]);
    exit;
}

// ────── PUT: update personal information ──────
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);

    $firstName = isset($body['firstName']) ? trim($body['firstName']) : null;
    $lastName  = isset($body['lastName'])  ? trim($body['lastName'])  : null;
    $grade     = isset($body['grade'])     ? trim($body['grade'])     : null;
    $bio       = isset($body['bio'])       ? trim($body['bio'])       : null;

    // Validation
    $errors = [];
    if ($firstName !== null && $firstName === '') $errors[] = 'First name cannot be empty.';
    if ($lastName !== null && $lastName === '')   $errors[] = 'Last name cannot be empty.';
    if ($bio !== null && mb_strlen($bio) > 500)  $errors[] = 'Bio must be 500 characters or less.';
    if ($grade !== null && mb_strlen($grade) > 20) $errors[] = 'Grade level is too long.';

    if ($errors) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    // Build dynamic update
    $fields = [];
    $params = [':sid' => $student['id']];

    if ($firstName !== null) { $fields[] = 'first_name = :fn'; $params[':fn'] = $firstName; }
    if ($lastName !== null)  { $fields[] = 'last_name = :ln';  $params[':ln'] = $lastName; }
    if ($grade !== null)     { $fields[] = 'grade_level = :gl'; $params[':gl'] = $grade ?: null; }
    if ($bio !== null)       { $fields[] = 'notes = :bio';      $params[':bio'] = $bio ?: null; }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update.']);
        exit;
    }

    $fields[] = 'updated_at = NOW()';
    $sql = 'UPDATE students SET ' . implode(', ', $fields) . ' WHERE id = :sid';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Also update the users table first_name / last_name so they stay in sync
    if ($firstName !== null || $lastName !== null) {
        $uFields = [];
        $uParams = [':uid' => $user['id']];
        if ($firstName !== null) { $uFields[] = 'first_name = :fn'; $uParams[':fn'] = $firstName; }
        if ($lastName !== null)  { $uFields[] = 'last_name = :ln';  $uParams[':ln'] = $lastName; }
        $uSql = 'UPDATE users SET ' . implode(', ', $uFields) . ' WHERE id = :uid';
        $uStmt = $db->prepare($uSql);
        $uStmt->execute($uParams);
    }

    // Return updated data
    $stmt = $db->prepare('SELECT first_name, last_name, grade_level, notes, profile_photo FROM students WHERE id = :sid');
    $stmt->execute([':sid' => $student['id']]);
    $updated = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'data'    => [
            'firstName' => $updated['first_name'],
            'lastName'  => $updated['last_name'],
            'grade'     => $updated['grade_level'] ?: '',
            'bio'       => $updated['notes'] ?: '',
            'avatarId'  => $updated['profile_photo'] ?: 'student',
        ],
    ]);
    exit;
}

// Any other method
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
