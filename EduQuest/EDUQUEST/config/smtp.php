<?php
/**
 * SMTP Configuration for Email Sending
 * Configure your email provider settings here
 */

// ============================================================
// SMTP SERVER SETTINGS
// ============================================================

define('SMTP_HOST', 'smtp.gmail.com');  // Your SMTP server
define('SMTP_PORT', 587);               // Port (usually 25, 465, or 587)
define('SMTP_ENCRYPTION', 'tls');       // 'tls' or 'ssl' or false for none
define('SMTP_USERNAME', 'eduquestadmin@gmail.com');         // Your SMTP username (email)
define('SMTP_PASSWORD', 'obkwvvuzukzzbghm');               // Your SMTP app password
define('SMTP_FROM_EMAIL', 'eduquestadmin@gmail.com');
define('SMTP_FROM_NAME', 'EduQuest');

// ============================================================
// FALLBACK / SANDBOX MODE
// ============================================================

// Set to true to use Mailtrap (https://mailtrap.io) for testing emails
// Create a free account and get your credentials
define('USE_MAILTRAP', false);
define('MAILTRAP_HOST', 'send.mailtrap.io');
define('MAILTRAP_PORT', 465);
define('MAILTRAP_ENCRYPTION', 'ssl');
define('MAILTRAP_USERNAME', '');  // Get from Mailtrap dashboard
define('MAILTRAP_PASSWORD', '');  // Get from Mailtrap dashboard
define('MAILTRAP_FROM_EMAIL', 'noreply@eduquest.local');
define('MAILTRAP_FROM_NAME', 'EduQuest');

// ============================================================
// ENVIRONMENT DETECTION
// ============================================================

// Auto-detect environment based on domain/hostname
$isProduction = (
    isset($_SERVER['HTTP_HOST']) && 
    !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8000'], true)
);

// If you want to force a specific mode:
$forceEnvironment = null;  // Set to 'production' or 'development' to override auto-detection

if ($forceEnvironment === 'production') {
    $isProduction = true;
} elseif ($forceEnvironment === 'development') {
    $isProduction = false;
}

define('IS_PRODUCTION', $isProduction);

// ============================================================
// EMAIL TEMPLATE DIRECTORIES
// ============================================================

define('EMAIL_TEMPLATE_DIR', dirname(__DIR__) . '/emails/');
define('EMAIL_VERIFICATION_TEMPLATE', EMAIL_TEMPLATE_DIR . 'email-verification.html');
define('PASSWORD_RESET_TEMPLATE', EMAIL_TEMPLATE_DIR . 'password-reset.html');
define('ACCOUNT_CREATED_TEMPLATE', EMAIL_TEMPLATE_DIR . 'account-created.html');

// ============================================================
// SMTP CONNECTION HELPER
// ============================================================

/**
 * Get SMTP configuration based on environment and settings
 * @return array Configuration array for SMTP
 */
function getSMTPConfig(): array {
    if (USE_MAILTRAP) {
        return [
            'host' => MAILTRAP_HOST,
            'port' => MAILTRAP_PORT,
            'encryption' => MAILTRAP_ENCRYPTION,
            'username' => MAILTRAP_USERNAME,
            'password' => MAILTRAP_PASSWORD,
            'from_email' => MAILTRAP_FROM_EMAIL,
            'from_name' => MAILTRAP_FROM_NAME,
            'is_testing' => true,
        ];
    }

    return [
        'host' => SMTP_HOST,
        'port' => SMTP_PORT,
        'encryption' => SMTP_ENCRYPTION,
        'username' => SMTP_USERNAME,
        'password' => SMTP_PASSWORD,
        'from_email' => SMTP_FROM_EMAIL,
        'from_name' => SMTP_FROM_NAME,
        'is_testing' => !IS_PRODUCTION,
    ];
}
