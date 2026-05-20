<?php
/**
 * GET  /api/teachers/profile.php  — fetch authenticated teacher's profile
 * POST /api/teachers/profile.php  — update profile or change password
 *   action = "update_profile"  : update name, school, department, phone, bio
 *   action = "change_password" : change password (requires current_password)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/Security.php';

$user = requireAuth();
if (!in_array($user['role'], ['teacher', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Teacher access required.']);
    exit;
}

$pdo = getDBConnection();

// requireAuth() overrides $user['id'] to teachers.id for FK convenience.
// For user_id-based queries we need the original users.id.
$usersId = $user['userId'] ?? $user['id'];

// ────────────────────────────────────────────────────────────
// GET — return full profile
// ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare(
        "SELECT t.id AS teacher_id, t.first_name, t.last_name, t.email,
                t.school_name, t.department, t.role AS teacher_role,
                t.created_at,
                u.id AS user_id, u.email AS login_email
         FROM teachers t
         JOIN users u ON u.id = t.user_id
         WHERE t.user_id = :uid"
    );
    $stmt->execute([':uid' => $usersId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Teacher profile not found.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'teacherId'   => $profile['teacher_id'],
            'userId'      => $profile['user_id'],
            'firstName'   => $profile['first_name'],
            'lastName'    => $profile['last_name'],
            'email'       => $profile['login_email'],
            'schoolName'  => $profile['school_name'],
            'department'  => $profile['department'],
            'role'        => $profile['teacher_role'],
            'memberSince' => $profile['created_at'],
        ],
    ]);
    exit;
}

// ────────────────────────────────────────────────────────────
// POST — update profile or change password
// ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
        exit;
    }

    $action = $input['action'] ?? 'update_profile';

    // ── Change password ──────────────────────────────────────
    if ($action === 'change_password') {
        $currentPassword = $input['current_password'] ?? '';
        $newPassword     = $input['new_password']     ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current and new password are required.']);
            exit;
        }

        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
            exit;
        }

        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :uid");
        $stmt->execute([':uid' => $usersId]);
        $row = $stmt->fetch();

        if (!$row || !verifyPassword($currentPassword, $row['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }

        $newHash = hashPassword($newPassword);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :uid");
        $stmt->execute([':hash' => $newHash, ':uid' => $usersId]);

        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
        exit;
    }

    // ── Update profile ───────────────────────────────────────
    $firstName  = sanitizeString($input['firstName']  ?? '');
    $lastName   = sanitizeString($input['lastName']   ?? '');
    $schoolName = sanitizeString($input['schoolName'] ?? '');
    $department = sanitizeString($input['department'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'First and last name are required.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        // Update teachers table
        $stmt = $pdo->prepare(
            "UPDATE teachers
             SET first_name = :fn, last_name = :ln,
                 school_name = :school, department = :dept,
                 updated_at = NOW()
             WHERE user_id = :uid"
        );
        $stmt->execute([
            ':fn'     => $firstName,
            ':ln'     => $lastName,
            ':school' => $schoolName,
            ':dept'   => $department,
            ':uid'    => $usersId,
        ]);

        // Keep users table in sync
        $stmt = $pdo->prepare(
            "UPDATE users SET first_name = :fn, last_name = :ln, updated_at = NOW()
             WHERE id = :uid"
        );
        $stmt->execute([
            ':fn'  => $firstName,
            ':ln'  => $lastName,
            ':uid' => $usersId,
        ]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
