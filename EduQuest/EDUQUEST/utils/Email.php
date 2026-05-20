<?php
/**
 * Email Utility for SMTP-based Email Sending
 * Uses PHPMailer or similar library (or native PHP as fallback)
 */

require_once __DIR__ . '/../config/smtp.php';
require_once __DIR__ . '/Security.php';

/**
 * Send an email
 * @param string $toEmail Recipient email
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $htmlBody HTML body
 * @param string|null $textBody Plain text body (optional)
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $textBody = null
): array {
    // Validate email
    if (!validateEmail($toEmail)) {
        return ['success' => false, 'message' => 'Invalid recipient email'];
    }

    // Use SMTP client directly
    require_once __DIR__ . '/SMTPClient.php';
    return sendEmailViaSMTP($toEmail, $toName, $subject, $htmlBody, $textBody);
}

/**
 * Send email via PHPMailer (if available)
 * @param string $toEmail Recipient email
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $htmlBody HTML body
 * @param string|null $textBody Plain text body
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmailViaPHPMailer(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $textBody = null
): array {
    try {
        $config = getSMTPConfig();

        $phpMailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
        if (!class_exists($phpMailerClass)) {
            return ['success' => false, 'message' => 'PHPMailer library is not available'];
        }

        $mail = new $phpMailerClass(true);

        // Server settings
        if (!empty($config['encryption'])) {
            if ($config['encryption'] === 'ssl') {
                $mail->SMTPSecure = 'ssl';
            } else {
                $mail->SMTPSecure = 'tls';
            }
        }

        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Port = $config['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];

        // Sender
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addReplyTo($config['from_email'], $config['from_name']);

        // Recipient
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        if ($textBody) {
            $mail->AltBody = $textBody;
        }

        // Send
        if ($mail->send()) {
            logAuthEvent('email_sent', ['to' => $toEmail, 'subject' => $subject]);
            return ['success' => true, 'message' => 'Email sent successfully'];
        }

        logAuthEvent('email_failed', ['to' => $toEmail, 'subject' => $subject, 'error' => $mail->ErrorInfo]);
        return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
    } catch (Exception $e) {
        logAuthEvent('email_error', ['to' => $toEmail, 'error' => $e->getMessage()]);
        return ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
    }
}

/**
 * Send email via native PHP mail() with SMTP settings
 * (This is a fallback and less reliable than PHPMailer)
 * @param string $toEmail Recipient email
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $htmlBody HTML body
 * @param string|null $textBody Plain text body
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmailViaPhpMail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $textBody = null
): array {
    try {
        $config = getSMTPConfig();

        // Set SMTP headers for Windows/Linux
        ini_set('SMTP', $config['host']);
        ini_set('smtp_port', $config['port']);

        // Prepare headers
        $headers = [
            'From' => $config['from_email'],
            'Reply-To' => $config['from_email'],
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Mailer' => 'EduQuest',
        ];

        // Build headers string
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }

        // Send mail
        if (mail($toEmail, $subject, $htmlBody, $headerString)) {
            logAuthEvent('email_sent', ['to' => $toEmail, 'subject' => $subject]);
            return ['success' => true, 'message' => 'Email sent successfully'];
        }

        logAuthEvent('email_failed', ['to' => $toEmail, 'subject' => $subject]);
        return ['success' => false, 'message' => 'Failed to send email via mail()'];
    } catch (Exception $e) {
        logAuthEvent('email_error', ['to' => $toEmail, 'error' => $e->getMessage()]);
        return ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
    }
}

/**
 * Send email verification link
 * @param string $toEmail User email
 * @param string $toName User name
 * @param string $verificationToken Verification token
 * @param string $verificationUrl Full URL to verification page
 * @return array ['success' => bool, 'message' => string]
 */
function sendVerificationEmail(
    string $toEmail,
    string $toName,
    string $verificationToken,
    string $verificationUrl
): array {
    // Load or build HTML template
    $htmlBody = getEmailTemplate('verification', [
        '{NAME}' => htmlspecialchars($toName),
        '{VERIFICATION_URL}' => $verificationUrl,
        '{EXPIRY_HOURS}' => EMAIL_VERIFICATION_EXPIRY / 3600,
        '{BASE_URL}' => getBaseUrl(),
    ]);

    return sendEmail(
        $toEmail,
        $toName,
        'Verify Your EduQuest Account',
        $htmlBody
    );
}

/**
 * Send password reset link
 * @param string $toEmail User email
 * @param string $toName User name
 * @param string $resetToken Reset token
 * @param string $resetUrl Full URL to reset page
 * @return array ['success' => bool, 'message' => string]
 */
function sendPasswordResetEmail(
    string $toEmail,
    string $toName,
    string $resetToken,
    string $resetUrl = ''
): array {
    // If no reset URL provided, treat resetToken as OTP
    if (empty($resetUrl)) {
        // Send OTP email
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0; font-size: 28px;'>EduQuest</h1>
                <p style='margin: 10px 0 0 0; font-size: 16px;'>Password Reset Request</p>
            </div>
            <div style='background: #f9f9f9; padding: 30px; border: 1px solid #e0e0e0; border-radius: 0 0 8px 8px;'>
                <p style='font-size: 14px; color: #333; margin-top: 0;'>Hi " . htmlspecialchars($toName) . ",</p>
                
                <p style='font-size: 14px; color: #666; line-height: 1.6;'>
                    We received a request to reset your password. Use the code below to verify your identity and create a new password.
                </p>
                
                <div style='background: white; border: 2px solid #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;'>
                    <p style='font-size: 12px; color: #999; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px;'>Your Verification Code:</p>
                    <p style='font-size: 32px; font-weight: bold; color: #667eea; margin: 0; letter-spacing: 4px;'>" . htmlspecialchars($resetToken) . "</p>
                </div>
                
                <p style='font-size: 14px; color: #666; line-height: 1.6;'>
                    This code will expire in 1 hour. If you didn't request a password reset, please ignore this email.
                </p>
                
                <p style='font-size: 12px; color: #999; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;'>
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </div>";
    } else {
        // Send reset link email (legacy)
        $htmlBody = getEmailTemplate('password-reset', [
            '{NAME}' => htmlspecialchars($toName),
            '{RESET_URL}' => $resetUrl,
            '{EXPIRY_HOURS}' => PASSWORD_RESET_EXPIRY / 3600,
        ]);
    }

    return sendEmail(
        $toEmail,
        $toName,
        'Reset Your EduQuest Password',
        $htmlBody
    );
}

/**
 * Send account created notification
 * @param string $toEmail User email
 * @param string $toName User name
 * @param string $role User role (student/teacher)
 * @return array ['success' => bool, 'message' => string]
 */
function sendAccountCreatedEmail(
    string $toEmail,
    string $toName,
    string $role
): array {
    $roleName = ucfirst($role);

    $htmlBody = getEmailTemplate('account-created', [
        '{NAME}' => htmlspecialchars($toName),
        '{ROLE}' => $roleName,
        '{LOGIN_URL}' => getBaseUrl() . '/login.html',
    ]);

    return sendEmail(
        $toEmail,
        $toName,
        'Welcome to EduQuest',
        $htmlBody
    );
}

/**
 * Load an email template with replacements
 * @param string $templateName Template name (without path)
 * @param array $replacements Key-value pairs for template replacement
 * @return string Processed HTML template
 */
function getEmailTemplate(string $templateName, array $replacements = []): string {
    // Map template names to file paths
    $templates = [
        'verification' => EMAIL_VERIFICATION_TEMPLATE,
        'password-reset' => PASSWORD_RESET_TEMPLATE,
        'account-created' => ACCOUNT_CREATED_TEMPLATE,
    ];

    if (!isset($templates[$templateName])) {
        return '<p>Email template not found.</p>';
    }

    $templateFile = $templates[$templateName];

    if (!file_exists($templateFile)) {
        return '<p>Email template file not found.</p>';
    }

    $content = file_get_contents($templateFile);

    // Apply replacements
    $content = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $content
    );

    return $content;
}

/**
 * Get the base URL for email links (e.g. http://localhost/path/to/EduQuest)
 * Automatically detects the project root from the current script path.
 * @return string Base URL including the EduQuest project path
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Detect the EduQuest root from the running script path
    // Scripts live under EDUQUEST/, so strip from /EDUQUEST/ onward
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $pos = strpos($scriptName, '/EDUQUEST/');
    if ($pos !== false) {
        $basePath = substr($scriptName, 0, $pos);
    } else {
        $basePath = '';
    }

    return $protocol . '://' . $host . $basePath;
}

/**
 * Require PHPMailer library if available
 * You can install via: composer require phpmailer/phpmailer
 */
if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
}


