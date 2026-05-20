<?php
/**
 * Brute Force & Rate Limiting Protection
 * Tracks login attempts and enforces lockouts
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Security.php';

/**
 * Check if an email/IP combination is locked out
 * @param string $email Email address
 * @param string $ip IP address (optional, uses current IP if not provided)
 * @return array ['locked_out' => bool, 'retry_after' => int (seconds)]
 */
function checkBruteForceProtection(string $email, string $ip = null): array {
    if ($ip === null) {
        $ip = getClientIP();
    }

    $db = getDBConnection();

    try {
        // Check recent failed attempts
        $stmt = $db->prepare(
            "SELECT COUNT(*) as failed_attempts, MAX(attempted_at) as last_attempt
             FROM login_attempts
             WHERE email = :email AND ip_address = :ip AND success = 0
             AND attempted_at > DATE_SUB(NOW(), INTERVAL :window SECOND)"
        );

        $stmt->execute([
            ':email' => strtolower($email),
            ':ip' => $ip,
            ':window' => LOGIN_ATTEMPT_WINDOW,
        ]);

        $result = $stmt->fetch();
        $failedAttempts = $result['failed_attempts'] ?? 0;
        $lastAttempt = $result['last_attempt'] ?? null;

        if ($failedAttempts >= MAX_LOGIN_ATTEMPTS && $lastAttempt) {
            $lastAttemptTime = strtotime($lastAttempt);
            $lockoutUntil = $lastAttemptTime + LOCKOUT_DURATION;
            $now = time();

            if ($now < $lockoutUntil) {
                return [
                    'locked_out' => true,
                    'retry_after' => $lockoutUntil - $now,
                ];
            }
        }

        return [
            'locked_out' => false,
            'retry_after' => 0,
        ];
    } catch (PDOException $e) {
        logSecurityEvent('bruteforce_check_error', ['error' => $e->getMessage()]);
        return ['locked_out' => false, 'retry_after' => 0];
    }
}

/**
 * Record a login attempt
 * @param string $email Email address
 * @param bool $success Whether the login was successful
 * @param string $ip IP address (optional)
 * @return bool True if recorded successfully
 */
function recordLoginAttempt(string $email, bool $success = false, string $ip = null): bool {
    if ($ip === null) {
        $ip = getClientIP();
    }

    $db = getDBConnection();

    try {
        $stmt = $db->prepare(
            "INSERT INTO login_attempts (email, ip_address, success, user_agent)
             VALUES (:email, :ip, :success, :user_agent)"
        );

        $stmt->execute([
            ':email' => strtolower($email),
            ':ip' => $ip,
            ':success' => $success ? 1 : 0,
            ':user_agent' => getUserAgent(),
        ]);

        // Log security event for failed attempts
        if (!$success) {
            logSecurityEvent('failed_login_attempt', [
                'email' => $email,
                'ip' => $ip,
            ]);
        }

        return true;
    } catch (PDOException $e) {
        logSecurityEvent('login_recording_error', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Check if an account is rate-limited for verification attempts
 * @param string $email Email address
 * @return array ['rate_limited' => bool, 'retry_after' => int (seconds)]
 */
function checkVerificationRateLimit(string $email): array {
    // This could use a similar mechanism as brute force, but simpler
    // Could also use session-based tracking
    
    $cacheKey = "verification_attempts_{$email}";
    
    // For now, we'll check database or use a simple rate limiting approach
    // This is a simplified version - you could add a table for this too
    
    return [
        'rate_limited' => false,
        'retry_after' => 0,
    ];
}

/**
 * Log security events to file and database
 * @param string $eventType Type of event
 * @param array $data Event data
 * @return void
 */
function logSecurityEvent(string $eventType, array $data = []): void {
    if (!LOG_AUTH_EVENTS) {
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $userAgent = getUserAgent();

    $logEntry = [
        'timestamp' => $timestamp,
        'event' => $eventType,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'data' => $data,
    ];

    $logMessage = $timestamp . " | " . $eventType . " | " . json_encode($data) . " | IP: " . $ip . "\n";

    // Log to file
    if (!is_dir(dirname(SECURITY_LOG_FILE))) {
        @mkdir(dirname(SECURITY_LOG_FILE), 0755, true);
    }

    @file_put_contents(SECURITY_LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Log authentication events
 * @param string $eventType Type of event (login, logout, register, etc.)
 * @param array $data Event data
 * @param int|null $userId User ID (optional)
 * @return void
 */
function logAuthEvent(string $eventType, array $data = [], ?int $userId = null): void {
    if (!LOG_AUTH_EVENTS) {
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();

    $logEntry = array_merge([
        'timestamp' => $timestamp,
        'event' => $eventType,
        'ip' => $ip,
        'user_id' => $userId,
    ], $data);

    $logMessage = $timestamp . " | " . $eventType . " | " . json_encode($logEntry) . "\n";

    // Log to file
    if (!is_dir(dirname(AUTH_LOG_FILE))) {
        @mkdir(dirname(AUTH_LOG_FILE), 0755, true);
    }

    @file_put_contents(AUTH_LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Clean up old login attempts from the database (for performance)
 * @return bool True if cleanup was successful
 */
function cleanupOldLoginAttempts(): bool {
    $db = getDBConnection();

    try {
        $stmt = $db->prepare(
            "DELETE FROM login_attempts
             WHERE attempted_at < DATE_SUB(NOW(), INTERVAL :days DAY)"
        );

        $stmt->execute([':days' => 7]);  // Keep last 7 days
        return true;
    } catch (PDOException $e) {
        logSecurityEvent('cleanup_error', ['table' => 'login_attempts', 'error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Clean up expired tokens
 * @return bool True if cleanup was successful
 */
function cleanupExpiredTokens(): bool {
    $db = getDBConnection();

    try {
        // Clean verification tokens
        $stmt = $db->prepare(
            "DELETE FROM email_verification_tokens WHERE expires_at < NOW()"
        );
        $stmt->execute();

        // Clean password reset tokens
        $stmt = $db->prepare(
            "DELETE FROM password_reset_tokens WHERE expires_at < NOW()"
        );
        $stmt->execute();

        // Clean sessions
        $stmt = $db->prepare(
            "DELETE FROM user_sessions WHERE expires_at < NOW()"
        );
        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        logSecurityEvent('cleanup_error', ['table' => 'tokens', 'error' => $e->getMessage()]);
        return false;
    }
}
