<?php
/**
 * Authentication Middleware
 * Validates session tokens and enforces role-based access control
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../utils/Security.php';

/**
 * Enforce authentication - validates session token
 * Returns user data or sends 401 and exits
 */
function requireAuth(): array {
    $sessionToken = getSessionToken();

    if (!$sessionToken) {
        // Try remember-me cookie before giving up
        $user = tryRememberMeLogin();
        if ($user) {
            return $user;
        }

        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in.',
        ]);
        exit;
    }

    $user = validateSessionToken($sessionToken);

    if (!$user) {
        // Session expired — try remember-me cookie
        $user = tryRememberMeLogin();
        if ($user) {
            return $user;
        }

        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session expired or invalid. Please log in again.',
        ]);
        exit;
    }

    // For teacher-role users, resolve the teachers.id (profile ID) so that
    // $teacher['id'] matches the FK used by students/courses/documents tables.
    // users.id ≠ teachers.id — only teachers.id satisfies those foreign keys.
    if ($user['role'] === 'teacher') {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare('SELECT id FROM teachers WHERE user_id = :uid LIMIT 1');
            $stmt->execute([':uid' => $user['id']]);
            $row = $stmt->fetch();
            if ($row) {
                $user['userId'] = $user['id'];  // keep users.id for reference
                $user['id']     = (int) $row['id']; // override with teachers.id
            }
        } catch (Exception $e) {
            // fall through — id stays as users.id, downstream FK will still fail
            // but this prevents a blank 500 masking the real problem
        }
    }

    return $user;
}

/**
 * Enforce authentication for a specific role
 * @param string $role 'teacher', 'student', or 'admin'
 * @return array User data
 */
function requireAuthRole(string $role): array {
    $user = requireAuth();

    // Admin can access everything
    if ($user['role'] === 'admin') {
        return $user;
    }

    // Check if user has the required role
    if ($user['role'] !== $role) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. This resource is only available to ' . ucfirst($role) . 's.',
        ]);
        exit;
    }

    return $user;
}

/**
 * Enforce teacher-only access (teacher or admin)
 * @return array User data
 */
function requireTeacher(): array {
    $user = requireAuth();

    if (!in_array($user['role'], ['teacher', 'admin'], true)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Teacher access is required.',
        ]);
        exit;
    }

    return $user;
}

/**
 * Enforce student-only access
 * @return array User data
 */
function requireStudent(): array {
    $user = requireAuth();

    if ($user['role'] !== 'student') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Student access is required.',
        ]);
        exit;
    }

    return $user;
}

/**
 * Extract the session token from the Authorization header (Bearer) or cookie
 * @return string|null Session token or null if not found
 */
function getSessionToken(): ?string {
    // Check Authorization header (Bearer token)
    // Apache/XAMPP may expose it under different keys
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? null;

    // Fallback: read directly from Apache request headers
    if (!$authHeader && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    }

    if ($authHeader) {
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
    }

    // Fall back to cookie
    if (!empty($_COOKIE[SESSION_COOKIE_NAME])) {
        return $_COOKIE[SESSION_COOKIE_NAME];
    }

    return null;
}

/**
 * Validate a session token and return user data
 * @param string $sessionToken The session token to validate
 * @return array|null User data if valid, null otherwise
 */
function validateSessionToken(string $sessionToken): ?array {
    try {
        $db = getDBConnection();

        // Hash the token for database lookup
        $tokenHash = hashToken($sessionToken);

        // Clean up expired sessions
        $stmt = $db->prepare('DELETE FROM user_sessions WHERE expires_at < NOW()');
        $stmt->execute();

        // Find the session
        $stmt = $db->prepare(
            "SELECT u.id, u.email, u.first_name, u.last_name, u.role, u.is_active,
                    us.user_id, us.expires_at
             FROM user_sessions us
             JOIN users u ON u.id = us.user_id
             WHERE us.session_token = :token AND us.expires_at > NOW() AND u.is_active = 1"
        );

        $stmt->execute([':token' => $tokenHash]);
        $session = $stmt->fetch();

        if (!$session) {
            return null;
        }

        return [
            'id' => $session['id'],
            'userId' => $session['user_id'],
            'email' => $session['email'],
            'firstName' => $session['first_name'],
            'lastName' => $session['last_name'],
            'role' => $session['role'],
            'isActive' => $session['is_active'],
            'expiresAt' => $session['expires_at'],
        ];

    } catch (Exception $e) {
        return null;
    }
}

/**
 * Try to auto-login using the remember-me cookie
 * @return array|null User data if remember token is valid, null otherwise
 */
function tryRememberMeLogin(): ?array {
    $rememberToken = $_COOKIE[REMEMBER_COOKIE_NAME] ?? null;
    if (!$rememberToken) {
        return null;
    }
    return validateRememberToken($rememberToken);
}

/**
 * Validate a remember-me cookie and create a new session
 * @param string $rememberToken The remember token
 * @return array|null New session data if valid, null otherwise
 */
function validateRememberToken(string $rememberToken): ?array {
    try {
        $db = getDBConnection();
        $tokenHash = hashToken($rememberToken);

        // Find session with remember token
        $stmt = $db->prepare(
            "SELECT u.id, u.email, u.first_name, u.last_name, u.role, u.is_active, us.user_id
             FROM user_sessions us
             JOIN users u ON u.id = us.user_id
             WHERE us.remember_token = :token AND us.expires_at > NOW() AND u.is_active = 1"
        );

        $stmt->execute([':token' => $tokenHash]);
        $session = $stmt->fetch();

        if (!$session) {
            return null;
        }

        // Create a new session
        $newSessionToken = generateSecureToken();
        $newTokenHash = hashToken($newSessionToken);

        $stmt = $db->prepare(
            "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
             VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at)"
        );

        $expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

        $stmt->execute([
            ':user_id' => $session['user_id'],
            ':session_token' => $newTokenHash,
            ':ip_address' => getClientIP(),
            ':user_agent' => getUserAgent(),
            ':expires_at' => $expires,
        ]);

        // Set session cookie
        setSessionCookie($newSessionToken);

        return [
            'id' => $session['id'],
            'userId' => $session['user_id'],
            'email' => $session['email'],
            'firstName' => $session['first_name'],
            'lastName' => $session['last_name'],
            'role' => $session['role'],
            'isActive' => $session['is_active'],
            'sessionToken' => $newSessionToken,
        ];

    } catch (Exception $e) {
        return null;
    }
}

/**
 * Check if current user is the teacher of a specific student
 * @param int $teacherId Teacher ID to check
 * @param int $studentId Student ID to check
 * @return bool True if teacher owns student
 */
function isTeacherOfStudent(int $teacherId, int $studentId): bool {
    try {
        $db = getDBConnection();

        $stmt = $db->prepare(
            "SELECT id FROM students WHERE id = :student_id AND teacher_id = :teacher_id"
        );

        $stmt->execute([
            ':student_id' => $studentId,
            ':teacher_id' => $teacherId,
        ]);

        return (bool)$stmt->fetch();

    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get user ID from current session
 * @return int|null User ID or null if not authenticated
 */
function getCurrentUserId(): ?int {
    $sessionToken = getSessionToken();
    if (!$sessionToken) {
        return null;
    }

    $user = validateSessionToken($sessionToken);
    return $user ? $user['userId'] : null;
}

/**
 * Check if authenticated user has admin role
 * @return bool True if admin
 */
function isAdmin(): bool {
    $sessionToken = getSessionToken();
    if (!$sessionToken) {
        return false;
    }

    $user = validateSessionToken($sessionToken);
    return $user && $user['role'] === 'admin';
}

/**
 * Verify the authenticated teacher owns the given student.
 * Terminates with 404 JSON if not found/not owned.
 *
 * @param int   $studentId  Student primary-key ID
 * @param array $teacher    Array returned by requireAuth()
 * @return array            Student row
 */
function requireStudentAccess(int $studentId, array $teacher): array {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare(
        'SELECT * FROM students WHERE id = :sid AND teacher_id = :tid LIMIT 1'
    );
    $stmt->execute([':sid' => $studentId, ':tid' => $teacher['id']]);
    $student = $stmt->fetch();
    if (!$student) {
        jsonResponse(false, 'Student not found or access denied.', [], 404);
    }
    return $student;
}
