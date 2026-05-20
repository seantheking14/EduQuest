<?php
/**
 * Authentication & Security Configuration
 * Defines settings for the authentication system including passwords, tokens, and security measures.
 */

// ============================================================
// BASE PATH
// ============================================================
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}

// ============================================================
// PASSWORD & HASHING
// ============================================================
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// ============================================================
// EMAIL VERIFICATION
// ============================================================
define('EMAIL_VERIFICATION_EXPIRY', 24 * 60 * 60);  // 24 hours in seconds
define('EMAIL_VERIFICATION_RESEND_COOLDOWN', 5 * 60);  // 5 minutes cooldown between resend requests

// ============================================================
// PASSWORD RESET
// ============================================================
define('PASSWORD_RESET_EXPIRY', 60 * 60);  // 1 hour in seconds
define('PASSWORD_RESET_RESEND_COOLDOWN', 5 * 60);  // 5 minutes cooldown

// ============================================================
// SESSION & COOKIE SETTINGS
// ============================================================
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 8 * 60 * 60);  // 8 hours
}
define('REMEMBER_ME_LIFETIME', 30 * 24 * 60 * 60);  // 30 days
define('SESSION_COOKIE_NAME', 'eduquest_session');
define('REMEMBER_COOKIE_NAME', 'eduquest_remember');
define('SESSION_COOKIE_PATH', '/');
define('SESSION_COOKIE_DOMAIN', '');
define('SESSION_COOKIE_SECURE', false);  // Set to true in production with HTTPS
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SAMESITE', 'Lax');

// ============================================================
// BRUTE FORCE PROTECTION
// ============================================================
define('MAX_LOGIN_ATTEMPTS', 10);
define('LOGIN_ATTEMPT_WINDOW', 15 * 60);  // 15 minutes - reset attempt count after this
define('LOCKOUT_DURATION', 15 * 60);  // 15 minutes lockout
define('MAX_VERIFICATION_ATTEMPTS', 3);
define('VERIFICATION_ATTEMPT_WINDOW', 60 * 60);  // 1 hour

// ============================================================
// TOKEN SETTINGS
// ============================================================
define('TOKEN_LENGTH', 64);  // Secure token length in bytes
define('TOKEN_ALGORITHM', 'sha256');  // Hash algorithm for tokens

// ============================================================
// SECURITY HEADERS
// ============================================================
define('ENABLE_CORS', false);
define('CORS_ALLOWED_ORIGINS', ['http://localhost', 'http://localhost:3000']);

// ============================================================
// LOGIN SECURITY
// ============================================================
define('GENERIC_LOGIN_ERROR', 'Invalid email or password');  // Don't reveal which is wrong
define('ACCOUNT_LOCKED_ERROR', 'Too many login attempts. Please try again later.');
define('ACCOUNT_NOT_VERIFIED_ERROR', 'Please verify your email before logging in.');
define('ACCOUNT_DISABLED_ERROR', 'This account has been disabled.');

// ============================================================
// LOGGING
// ============================================================
define('LOG_AUTH_EVENTS', true);  // Log login attempts, password resets, etc.
define('AUTH_LOG_FILE', BASE_PATH . '/logs/auth.log');
define('SECURITY_LOG_FILE', BASE_PATH . '/logs/security.log');

// ============================================================
// EMAIL SETTINGS (These are overridden by SMTP config, kept for reference)
// ============================================================
define('FROM_EMAIL', 'noreply@eduquest.local');
define('FROM_NAME', 'EduQuest');
