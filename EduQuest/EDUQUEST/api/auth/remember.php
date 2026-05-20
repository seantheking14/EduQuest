<?php
/**
 * Remember Me Token Validation Endpoint
 * POST /api/auth/remember.php
 * Validates a remember-me cookie and issues a new session
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

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
    // Read the remember cookie
    $rememberToken = $_COOKIE[REMEMBER_COOKIE_NAME] ?? null;

    if (!$rememberToken) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No remember token found']);
        exit;
    }

    $db = getDBConnection();
    $tokenHash = hashToken($rememberToken);

    // Find session with valid remember token
    $stmt = $db->prepare(
        "SELECT u.id, u.email, u.first_name, u.last_name, u.role, u.is_active,
                us.id AS session_id, us.user_id
         FROM user_sessions us
         JOIN users u ON u.id = us.user_id
         WHERE us.remember_token = :token AND us.expires_at > NOW() AND u.is_active = 1"
    );
    $stmt->execute([':token' => $tokenHash]);
    $session = $stmt->fetch();

    if (!$session) {
        // Invalid or expired remember token — clear the cookie
        clearAuthCookies();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Remember token expired or invalid']);
        exit;
    }

    // ── Create a fresh session ──────────────────────────────
    $newSessionToken = generateSecureToken();
    $newRememberToken = generateSecureToken();
    $sessionExpires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    $rememberExpires = date('Y-m-d H:i:s', time() + REMEMBER_ME_LIFETIME);

    $db->beginTransaction();

    try {
        // Delete the old session row (token rotation)
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE id = :sid");
        $stmt->execute([':sid' => $session['session_id']]);

        // Insert a new session with a fresh remember token
        $stmt = $db->prepare(
            "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, remember_token, expires_at)
             VALUES (:user_id, :session_token, :ip_address, :user_agent, :remember_token, :expires_at)"
        );
        $stmt->execute([
            ':user_id'        => $session['user_id'],
            ':session_token'  => hashToken($newSessionToken),
            ':ip_address'     => getClientIP(),
            ':user_agent'     => getUserAgent(),
            ':remember_token' => hashToken($newRememberToken),
            ':expires_at'     => $rememberExpires,
        ]);

        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW(), last_login_ip = :ip WHERE id = :uid");
        $stmt->execute([':uid' => $session['id'], ':ip' => getClientIP()]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    // Set fresh cookies
    setSessionCookie($newSessionToken);
    setRememberMeCookie($newRememberToken);

    // ── Fetch role-specific profile ─────────────────────────
    $profile = null;
    $role = $session['role'];

    if ($role === 'teacher' || $role === 'admin') {
        $stmt = $db->prepare(
            "SELECT id, first_name, last_name, school_name, department
             FROM teachers WHERE user_id = :uid"
        );
        $stmt->execute([':uid' => $session['id']]);
        $profile = $stmt->fetch() ?: null;
    } elseif ($role === 'student') {
        $stmt = $db->prepare(
            "SELECT id, first_name, last_name, grade_level, profile_photo
             FROM students WHERE user_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $session['id']]);
        $profile = $stmt->fetch() ?: null;
    }

    // ── Determine redirect URL ──────────────────────────────
    $redirectUrl = ($role === 'teacher' || $role === 'admin')
        ? '../../EDUQUEST/teacher-dashboard/dashboard.php'
        : '../../student-dashboard/dashboard/dashboard.html';

    // ── Build response ──────────────────────────────────────
    $responseData = [
        'token'       => $newSessionToken,
        'user'        => [
            'id'        => $session['id'],
            'email'     => $session['email'],
            'firstName' => $session['first_name'],
            'lastName'  => $session['last_name'],
            'role'      => $role,
        ],
        'redirectUrl' => $redirectUrl,
        'expiresAt'   => $sessionExpires,
        'rememberMe'  => true,
    ];

    if ($profile) {
        if ($role === 'teacher' || $role === 'admin') {
            $responseData['teacher'] = [
                'id'         => $profile['id'],
                'first_name' => $profile['first_name'],
                'last_name'  => $profile['last_name'],
                'schoolName' => $profile['school_name'],
                'department' => $profile['department'],
            ];
        } else {
            $responseData['student'] = [
                'id'           => $profile['id'],
                'first_name'   => $profile['first_name'],
                'last_name'    => $profile['last_name'],
                'gradeLevel'   => $profile['grade_level'],
                'profilePhoto' => $profile['profile_photo'],
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Auto-login successful',
        'data'    => $responseData,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please log in manually.',
    ]);
}
