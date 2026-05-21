<?php
define('SMTP_HOST',       getenv('SMTP_HOST')       ?: 'smtp.gmail.com');
define('SMTP_PORT',       getenv('SMTP_PORT')        ?: 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME',   getenv('SMTP_USERNAME')    ?: 'eduquestadmin@gmail.com');
define('SMTP_PASSWORD',   getenv('SMTP_PASSWORD')    ?: 'obkwvvuzukzzbghm');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL')  ?: 'eduquestadmin@gmail.com');
define('SMTP_FROM_NAME',  getenv('SMTP_FROM_NAME')   ?: 'EduQuest');

define('USE_MAILTRAP', false);
define('MAILTRAP_HOST', 'send.mailtrap.io');
define('MAILTRAP_PORT', 465);
define('MAILTRAP_ENCRYPTION', 'ssl');
define('MAILTRAP_USERNAME', '');
define('MAILTRAP_PASSWORD', '');
define('MAILTRAP_FROM_EMAIL', 'noreply@eduquest.local');
define('MAILTRAP_FROM_NAME', 'EduQuest');

$isProduction = (
    isset($_SERVER['HTTP_HOST']) &&
    !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8000'], true)
);
define('IS_PRODUCTION', $isProduction);

define('EMAIL_TEMPLATE_DIR', dirname(__DIR__) . '/emails/');
define('EMAIL_VERIFICATION_TEMPLATE', EMAIL_TEMPLATE_DIR . 'email-verification.html');
define('PASSWORD_RESET_TEMPLATE', EMAIL_TEMPLATE_DIR . 'password-reset.html');
define('ACCOUNT_CREATED_TEMPLATE', EMAIL_TEMPLATE_DIR . 'account-created.html');

function getSMTPConfig(): array {
    if (USE_MAILTRAP) {
        return [
            'host'       => MAILTRAP_HOST,
            'port'       => MAILTRAP_PORT,
            'encryption' => MAILTRAP_ENCRYPTION,
            'username'   => MAILTRAP_USERNAME,
            'password'   => MAILTRAP_PASSWORD,
            'from_email' => MAILTRAP_FROM_EMAIL,
            'from_name'  => MAILTRAP_FROM_NAME,
            'is_testing' => true,
        ];
    }

    return [
        'host'       => SMTP_HOST,
        'port'       => SMTP_PORT,
        'encryption' => SMTP_ENCRYPTION,
        'username'   => SMTP_USERNAME,
        'password'   => SMTP_PASSWORD,
        'from_email' => SMTP_FROM_EMAIL,
        'from_name'  => SMTP_FROM_NAME,
        'is_testing' => !IS_PRODUCTION,
    ];
}
