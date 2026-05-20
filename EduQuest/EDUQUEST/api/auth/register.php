<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register.php
 * Handles registration for both teachers and students
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
require_once __DIR__ . '/../../utils/Email.php';

try {
    // Get JSON payload
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON payload');
    }

    // ============================================================
    // INPUT VALIDATION
    // ============================================================

    $email = trim($input['email'] ?? '');
    $firstName = trim($input['firstName'] ?? $input['first_name'] ?? '');
    $lastName = trim($input['lastName'] ?? $input['last_name'] ?? '');
    $password = $input['password'] ?? '';
    $passwordConfirm = $input['passwordConfirm'] ?? $input['password_confirm'] ?? '';
    $role = strtolower(trim($input['role'] ?? ''));

    // Validate required fields
    $errors = [];
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($firstName)) {
        $errors['firstName'] = 'First name is required';
    } elseif (strlen($firstName) < 2) {
        $errors['firstName'] = 'First name must be at least 2 characters';
    }

    if (empty($lastName)) {
        $errors['lastName'] = 'Last name is required';
    } elseif (strlen($lastName) < 2) {
        $errors['lastName'] = 'Last name must be at least 2 characters';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif ($password !== $passwordConfirm) {
        $errors['passwordConfirm'] = 'Passwords do not match';
    } else {
        $passwordValidation = validatePassword($password);
        if (!$passwordValidation['valid']) {
            $errors['password'] = is_array($passwordValidation['errors'])
                ? $passwordValidation['errors'][0]
                : $passwordValidation['errors'];
        }
    }

    if (empty($role)) {
        $errors['role'] = 'Role is required';
    } elseif (!in_array($role, ['student', 'teacher'], true)) {
        $errors['role'] = 'Role must be either "student" or "teacher"';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ]);
        exit;
    }

    // ============================================================
    // DATABASE CHECKS & USER CREATION
    // ============================================================

    $db = getDBConnection();

    // Check if email already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email)');
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        logAuthEvent('register_duplicate_email', ['email' => $email]);
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'This email is already registered',
            'errors' => ['email' => 'Email already in use'],
        ]);
        exit;
    }

    // ============================================================
    // TEACHER WHITELIST CHECK
    // ============================================================

    if ($role === 'teacher') {
        $wlStmt = $db->prepare(
            'SELECT id FROM teacher_whitelist WHERE LOWER(email) = LOWER(:email) LIMIT 1'
        );
        $wlStmt->execute([':email' => $email]);
        if (!$wlStmt->fetch()) {
            logAuthEvent('register_teacher_not_whitelisted', ['email' => $email]);
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Your email is not authorized for teacher registration. Please contact your school administrator.',
                'errors'  => ['email' => 'Email not authorized for teacher registration'],
            ]);
            exit;
        }
    }

    // Hash password
    $passwordHash = hashPassword($password);

    // ============================================================
    // CREATE USER ACCOUNT (NOT YET VERIFIED)
    // ============================================================

    $db->beginTransaction();

    try {
        // Create user in users table
        $stmt = $db->prepare(
            "INSERT INTO users (email, password_hash, first_name, last_name, role, is_active, email_verified)
             VALUES (:email, :password_hash, :first_name, :last_name, :role, 0, 0)"
        );

        $stmt->execute([
            ':email' => strtolower($email),
            ':password_hash' => $passwordHash,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':role' => $role,
        ]);

        $userId = $db->lastInsertId();

        // Create profile entry (teachers or students table)
        if ($role === 'teacher') {
            $stmt = $db->prepare(
                "INSERT INTO teachers (user_id, first_name, last_name, email, role)
                 VALUES (:user_id, :first_name, :last_name, :email, 'teacher')"
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':email' => strtolower($email),
            ]);
        } else {
            // For students, teacher_id is optional for self-registration
            $stmt = $db->prepare(
                "INSERT INTO students (user_id, first_name, last_name)
                 VALUES (:user_id, :first_name, :last_name)"
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
            ]);
        }

        // ============================================================
        // GENERATE AND STORE EMAIL VERIFICATION TOKEN
        // ============================================================

        $verificationToken = generateSecureToken();
        $tokenHash = hashToken($verificationToken);
        $expiresAt = date('Y-m-d H:i:s', time() + EMAIL_VERIFICATION_EXPIRY);

        $stmt = $db->prepare(
            "INSERT INTO email_verification_tokens (user_id, token, expires_at)
             VALUES (:user_id, :token, :expires_at)"
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':token' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);

        $db->commit();

        // ============================================================
        // SEND VERIFICATION EMAIL
        // ============================================================

        $verificationUrl = getBaseUrl() . '/auth/verify-email.html?token=' . urlencode($verificationToken) . '&email=' . urlencode($email);

        $emailResult = sendVerificationEmail($email, $firstName . ' ' . $lastName, $verificationToken, $verificationUrl);

        if (!$emailResult['success']) {
            logAuthEvent('register_email_failed', [
                'user_id' => $userId,
                'email' => $email,
                'error' => $emailResult['message'],
            ]);
            // Note: Email failure doesn't block account creation. User can request resend later.
        }

        // Log successful registration
        logAuthEvent('user_registered', [
            'user_id' => $userId,
            'email' => $email,
            'role' => $role,
        ]);

        // ============================================================
        // SUCCESS RESPONSE
        // ============================================================

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Account created! Please check your email to verify your address.',
            'data' => [
                'userId' => $userId,
                'email' => $email,
                'role' => $role,
                'requiresVerification' => true,
                'emailSent' => $emailResult['success'] ?? false,
            ],
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    logAuthEvent('registration_error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during registration. Please try again.',
    ]);
}
