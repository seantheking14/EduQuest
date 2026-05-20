<?php
/**
 * Security Utilities
 * Functions for password hashing, token generation, validation, and security checks
 */

require_once __DIR__ . '/../config/auth.php';

/**
 * Hash a password using bcrypt
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches hash
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Check if a password meets security requirements
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'errors' => string[]]
 */
function validatePassword(string $password): array {
    $errors = [];

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }

    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*)";
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}

/**
 * Generate a secure random token
 * @param int $length Length in bytes (defaults to TOKEN_LENGTH)
 * @return string Hex-encoded secure token
 */
function generateSecureToken(int $length = TOKEN_LENGTH): string {
    try {
        return bin2hex(random_bytes($length));
    } catch (Exception $e) {
        // Fallback if random_bytes fails (shouldn't happen in modern PHP)
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}

/**
 * Hash a token for database storage
 * @param string $token Plain token
 * @return string Hashed token
 */
function hashToken(string $token): string {
    return hash(TOKEN_ALGORITHM, $token);
}

/**
 * Verify a token against a stored hash
 * @param string $token Plain token
 * @param string $hash Hashed token
 * @return bool True if token matches hash
 */
function verifyToken(string $token, string $hash): bool {
    return hash_equals(hashToken($token), $hash);
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool True if email is valid
 */
function validateEmail(string $email): bool {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize input string (basic XSS prevention)
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token for forms
 * @return string CSRF token
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if token is valid
 */
function verifyCSRFToken(string $token): bool {
    if (!isset($_SESSION) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get client IP address (handles proxies)
 * @return string Client IP address
 */
function getClientIP(): string {
    // Check for shared internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Handle multiple IPs (take the first one)
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    // Check for remote address
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Validate IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '0.0.0.0';
}

/**
 * Get user agent string
 * @return string User agent
 */
function getUserAgent(): string {
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);
}

/**
 * Set secure session cookie
 * @param string $sessionToken Session token
 * @return void
 */
function setSessionCookie(string $sessionToken): void {
    setcookie(
        SESSION_COOKIE_NAME,
        $sessionToken,
        [
            'expires' => time() + SESSION_LIFETIME,
            'path' => SESSION_COOKIE_PATH,
            'domain' => SESSION_COOKIE_DOMAIN,
            'secure' => SESSION_COOKIE_SECURE,
            'httponly' => SESSION_COOKIE_HTTPONLY,
            'samesite' => SESSION_COOKIE_SAMESITE,
        ]
    );
}

/**
 * Set remember me cookie (longer lived)
 * @param string $rememberToken Remember token
 * @return void
 */
function setRememberMeCookie(string $rememberToken): void {
    setcookie(
        REMEMBER_COOKIE_NAME,
        $rememberToken,
        [
            'expires' => time() + REMEMBER_ME_LIFETIME,
            'path' => SESSION_COOKIE_PATH,
            'domain' => SESSION_COOKIE_DOMAIN,
            'secure' => SESSION_COOKIE_SECURE,
            'httponly' => SESSION_COOKIE_HTTPONLY,
            'samesite' => SESSION_COOKIE_SAMESITE,
        ]
    );
}

/**
 * Clear all authentication cookies
 * @return void
 */
function clearAuthCookies(): void {
    setcookie(SESSION_COOKIE_NAME, '', time() - 3600, SESSION_COOKIE_PATH);
    setcookie(REMEMBER_COOKIE_NAME, '', time() - 3600, SESSION_COOKIE_PATH);
}
