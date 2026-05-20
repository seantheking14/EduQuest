<?php
/**
 * Global application constants and helpers
 */

// Prevent PHP errors from leaking HTML into JSON API responses
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('APP_NAME', 'EduQuest');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR',           BASE_PATH . '/uploads/documents/');
define('PHOTO_DIR',            BASE_PATH . '/uploads/photos/');
define('COURSE_MATERIAL_DIR',  BASE_PATH . '/uploads/course-materials/');
define('SUBMISSION_DIR',       BASE_PATH . '/uploads/submissions/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);   // 10 MB
define('SESSION_LIFETIME', 60 * 60 * 8);      // 8 hours in seconds

// Allowed MIME types for document uploads
// Note: Windows/XAMPP finfo may return 'application/zip' for .docx/.xlsx
// and 'application/octet-stream' for .doc/.xls — normalizeMime() handles this.
define('ALLOWED_DOC_MIMES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png',
    'image/tiff',
    'image/webp',
]);

/**
 * Normalise the MIME type detected by finfo.
 * On Windows, finfo often returns 'application/zip' for modern Office formats
 * (.docx, .xlsx, .pptx) and 'application/octet-stream' for legacy formats.
 * We use the file extension as a secondary hint only after finfo detection.
 */
function normalizeMime(string $finfoMime, string $ext): string {
    $ext = strtolower(ltrim($ext, '.'));
    $officeZip = [
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];
    $officeOctet = [
        'doc'  => 'application/msword',
        'xls'  => 'application/vnd.ms-excel',
        'ppt'  => 'application/vnd.ms-powerpoint',
    ];
    if ($finfoMime === 'application/zip' && isset($officeZip[$ext])) {
        return $officeZip[$ext];
    }
    if (in_array($finfoMime, ['application/octet-stream', 'application/x-cfb'], true) && isset($officeOctet[$ext])) {
        return $officeOctet[$ext];
    }
    return $finfoMime;
}

// Secret key for HMAC-signing short-lived preview tokens (Office Online Viewer)
define('PREVIEW_SECRET', 'eq_preview_k8z4R!mW#xL9pQ2v');

// Allowed MIME types for profile photos
define('ALLOWED_PHOTO_MIMES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

/**
 * Send a JSON response and terminate execution.
 */
function jsonResponse(bool $success, string $message, array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Sanitize a string for safe output/storage.
 */
function sanitizeString(?string $input): string {
    return trim(strip_tags((string)($input ?? '')));
}

/**
 * Validate an email address.
 */
function isValidEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate a cryptographically secure random token.
 */
function generateToken(int $bytes = 64): string {
    return bin2hex(random_bytes($bytes));
}

/**
 * Ensure required upload directories exist.
 */
function ensureUploadDirs(): void {
    foreach ([UPLOAD_DIR, PHOTO_DIR, COURSE_MATERIAL_DIR, SUBMISSION_DIR] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
    }
}
