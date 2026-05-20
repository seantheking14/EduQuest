<?php
/**
 * Login Endpoint
 * POST /api/auth/login.php
 * Authenticates both teachers and students and issues a session token
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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
    // Get credentials from JSON body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON payload');
    }

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $rememberMe = (bool)($input['rememberMe'] ?? false);

    // ============================================================
    // BRUTE FORCE PROTECTION
    // ============================================================

    $ipAddress = getClientIP();
    $bruteForceCheck = checkBruteForceProtection($email, $ipAddress);

    if ($bruteForceCheck['locked_out']) {
        logSecurityEvent('login_blocked_bruteforce', ['email' => $email, 'ip' => $ipAddress]);
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => ACCOUNT_LOCKED_ERROR,
            'retryAfter' => $bruteForceCheck['retry_after'],
        ]);
        exit;
    }

    // ============================================================
    // INPUT VALIDATION
    // ============================================================

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required',
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
    // LOOKUP USER
    // ============================================================

    $db = getDBConnection();

    $stmt = $db->prepare(
        "SELECT id, email, password_hash, first_name, last_name, role, is_active, email_verified,
                account_status, suspended_until, force_password_reset
         FROM users
         WHERE LOWER(email) = LOWER(:email)"
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // ============================================================
    // TEACHER WHITELIST CHECK
    // Runs before password_verify to prevent unauthorised teacher access.
    // Uses a generic error so as not to reveal the reason for rejection.
    // ============================================================

    if ($user && strtolower($user['role']) === 'teacher') {
        $wlStmt = $db->prepare(
            'SELECT id FROM teacher_whitelist WHERE LOWER(email) = LOWER(:email) LIMIT 1'
        );
        $wlStmt->execute([':email' => $email]);
        if (!$wlStmt->fetch()) {
            recordLoginAttempt($email, false, $ipAddress);
            logAuthEvent('login_teacher_not_whitelisted', ['email' => $email, 'ip' => $ipAddress]);
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => GENERIC_LOGIN_ERROR,
            ]);
            exit;
        }
    }

    // ============================================================
    // VERIFY PASSWORD (constant-time comparison)
    // ============================================================

    $passwordValid = $user && verifyPassword($password, $user['password_hash']);

    if (!$passwordValid) {
        // Record failed attempt
        recordLoginAttempt($email, false, $ipAddress);

        logAuthEvent('login_failed', ['email' => $email, 'ip' => $ipAddress]);

        // Generic error message (don't reveal whether email or password is wrong)
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => GENERIC_LOGIN_ERROR,
        ]);
        exit;
    }

    // ============================================================
    // CHECK ACCOUNT STATUS
    // ============================================================

    if (!$user['email_verified']) {
        logAuthEvent('login_unverified_email', ['user_id' => $user['id'], 'email' => $email]);
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => ACCOUNT_NOT_VERIFIED_ERROR,
            'data' => [
                'requiresVerification' => true,
                'email' => $email,
            ],
        ]);
        exit;
    }

    // ── Auto-lift expired suspensions ────────────────────────────────────────
    if (isset($user['account_status']) && $user['account_status'] === 'suspended') {
        $suspendedUntil = $user['suspended_until'];
        $isExpired = $suspendedUntil !== null && strtotime($suspendedUntil) < time();

        if ($isExpired) {
            // Suspension has elapsed — automatically restore the account
            $liftStmt = $db->prepare(
                "UPDATE users
                 SET    account_status = 'active', is_active = 1,
                        suspended_until = NULL, suspension_reason = NULL
                 WHERE  id = :id"
            );
            $liftStmt->execute([':id' => $user['id']]);
            $user['account_status'] = 'active';
            $user['is_active']      = 1;
            logAuthEvent('suspension_auto_lifted', ['user_id' => $user['id'], 'email' => $email]);
        } else {
            $until = $suspendedUntil
                ? ' until ' . date('F j, Y', strtotime($suspendedUntil))
                : '';
            logAuthEvent('login_suspended_account', ['user_id' => $user['id'], 'email' => $email]);
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => "Your account has been temporarily suspended{$until}. Please contact your administrator.",
            ]);
            exit;
        }
    }

    // ── Block archived accounts ────────────────────────────────────────────────
    if (isset($user['account_status']) && $user['account_status'] === 'archived') {
        logAuthEvent('login_archived_account', ['user_id' => $user['id'], 'email' => $email]);
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => ACCOUNT_DISABLED_ERROR,
        ]);
        exit;
    }

    if (!$user['is_active']) {
        logAuthEvent('login_disabled_account', ['user_id' => $user['id'], 'email' => $email]);
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => ACCOUNT_DISABLED_ERROR,
        ]);
        exit;
    }

    // ============================================================
    // VALIDATE REQUESTED ROLE
    // ============================================================

    $requestedRole = strtolower(trim($input['role'] ?? ''));
    $userRole = strtolower($user['role']);

    // Debug log
    error_log("Login attempt - requested_role: '$requestedRole', user_role: '$userRole', input keys: " . implode(',', array_keys($input)));

    // Role must be specified and must match user's actual role
    if (empty($requestedRole)) {
        logAuthEvent('login_missing_role', [
            'user_id' => $user['id'],
            'email' => $email,
            'received_input' => $input,
        ]);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please select your role (teacher or student)',
        ]);
        exit;
    }

    if ($requestedRole !== $userRole) {
        logAuthEvent('login_role_mismatch', [
            'user_id' => $user['id'],
            'email' => $email,
            'requested_role' => $requestedRole,
            'actual_role' => $userRole,
        ]);
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You can only login as a ' . $userRole . '. Your account is registered as a ' . $userRole . '.',
        ]);
        exit;
    }

    // ============================================================
    // CREATE SESSION
    // ============================================================

    $sessionToken = generateSecureToken();
    $sessionExpires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    $ipAddress = getClientIP();
    $userAgent = getUserAgent();

    $rememberToken = null;
    if ($rememberMe) {
        $rememberToken = generateSecureToken();
    }

    $db->beginTransaction();

    try {
        // Create session record
        $rememberExpires = $rememberMe
            ? date('Y-m-d H:i:s', time() + REMEMBER_ME_LIFETIME)
            : null;

        $stmt = $db->prepare(
            "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, remember_token, expires_at)
             VALUES (:user_id, :session_token, :ip_address, :user_agent, :remember_token, :expires_at)"
        );

        $stmt->execute([
            ':user_id' => $user['id'],
            ':session_token' => hashToken($sessionToken),
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
            ':remember_token' => $rememberToken ? hashToken($rememberToken) : null,
            ':expires_at' => $rememberMe ? $rememberExpires : $sessionExpires,
        ]);

        // Update last login time
        $stmt = $db->prepare(
            "UPDATE users SET last_login = NOW(), last_login_ip = :ip WHERE id = :user_id"
        );
        $stmt->execute([
            ':user_id' => $user['id'],
            ':ip' => $ipAddress,
        ]);

        $db->commit();

        // ============================================================
        // SET COOKIES
        // ============================================================

        setSessionCookie($sessionToken);

        if ($rememberMe && $rememberToken) {
            setRememberMeCookie($rememberToken);
        }

        // Record successful login
        recordLoginAttempt($email, true, $ipAddress);

        logAuthEvent('login_success', [
            'user_id' => $user['id'],
            'email' => $email,
            'role' => $user['role'],
            'ip' => $ipAddress,
        ]);

        // ============================================================
        // FETCH ROLE-SPECIFIC PROFILE
        // ============================================================

        $profile = null;
        if ($user['role'] === 'teacher' || $user['role'] === 'admin') {
            $stmt = $db->prepare(
                "SELECT id, first_name, last_name, school_name, department
                 FROM teachers WHERE user_id = :uid"
            );
            $stmt->execute([':uid' => $user['id']]);
            $profile = $stmt->fetch() ?: null;
        } elseif ($user['role'] === 'student') {
            $stmt = $db->prepare(
                "SELECT id, first_name, last_name, grade_level, profile_photo
                 FROM students WHERE user_id = :uid LIMIT 1"
            );
            $stmt->execute([':uid' => $user['id']]);
            $profile = $stmt->fetch() ?: null;
        }

        // ============================================================
        // DETERMINE REDIRECT BASED ON ROLE
        // ============================================================

        $redirectUrl = '';
        if ($user['role'] === 'teacher' || $user['role'] === 'admin') {
            $redirectUrl = '../../EDUQUEST/teacher-dashboard/dashboard.php';
        } else {
            $redirectUrl = '../../student-dashboard/dashboard/dashboard.html';
        }

        // ============================================================
        // SUCCESS RESPONSE
        // ============================================================

        $responseData = [
            'token' => $sessionToken,
            'user' => [
                'id'        => $user['id'],
                'email'     => $user['email'],
                'firstName' => $user['first_name'],
                'lastName'  => $user['last_name'],
                'role'      => $user['role'],
            ],
            'redirectUrl' => $redirectUrl,
            'expiresAt'   => $sessionExpires,
            'rememberMe'  => $rememberMe,
            'forcePasswordReset' => !empty($user['force_password_reset']),
        ];

        // Attach role-specific profile (camelCase keys for JS consumers)
        if ($profile) {
            if ($user['role'] === 'teacher' || $user['role'] === 'admin') {
                $responseData['teacher'] = [
                    'id'         => $profile['id'],
                    'first_name' => $profile['first_name'],
                    'last_name'  => $profile['last_name'],
                    'schoolName' => $profile['school_name'],
                    'department' => $profile['department'],
                ];
            } else {
                $responseData['student'] = [
                    'id'          => $profile['id'],
                    'first_name'  => $profile['first_name'],
                    'last_name'   => $profile['last_name'],
                    'gradeLevel'  => $profile['grade_level'],
                    'profilePhoto'=> $profile['profile_photo'],
                ];
            }
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'data'    => $responseData,
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    logAuthEvent('login_error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during login. Please try again.',
    ]);
}
