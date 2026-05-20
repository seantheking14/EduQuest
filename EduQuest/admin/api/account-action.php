<?php
/**
 * Admin Account Action API
 * POST /admin/api/account-action.php
 *
 * Handles account lifecycle actions on teacher/student users:
 *   deactivate, reactivate, suspend, archive, unarchive, force_password_reset
 *
 * Requires: active admin session ($_SESSION['admin_id'], role='admin')
 * Returns:  JSON { success, user_id, new_status, message }
 */

header('Content-Type: application/json');

// ── Session guard ──────────────────────────────────────────────────────────────
session_start();

if (
    empty($_SESSION['admin_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit;
}

$adminId = (int) $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── DB helper ─────────────────────────────────────────────────────────────────
function getAdminDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=eduquest;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

// ── Parse input ───────────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

$action   = trim($input['action']   ?? '');
$userId   = (int) ($input['user_id'] ?? 0);
$reason   = trim($input['reason']   ?? '');
$csrfToken = trim($input['csrf_token'] ?? '');

// ── CSRF validation ────────────────────────────────────────────────────────────
if (
    empty($csrfToken) ||
    empty($_SESSION['admin_csrf_token']) ||
    !hash_equals($_SESSION['admin_csrf_token'], $csrfToken)
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// ── Input validation ──────────────────────────────────────────────────────────
$allowedActions = [
    'deactivate',
    'reactivate',
    'suspend',
    'archive',
    'unarchive',
    'force_password_reset',
];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user_id']);
    exit;
}

// ── Suspend-specific fields ───────────────────────────────────────────────────
$suspendedUntil = null;

if ($action === 'suspend') {
    $durationDays = (int) ($input['duration_days'] ?? 0);
    $customDate   = trim($input['custom_date'] ?? '');

    if ($customDate !== '') {
        // Validate custom date
        $parsed = DateTime::createFromFormat('Y-m-d', $customDate, new DateTimeZone('UTC'));
        if (!$parsed || $parsed <= new DateTime('now', new DateTimeZone('UTC'))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Suspension end date must be in the future']);
            exit;
        }
        $suspendedUntil = $parsed->format('Y-m-d 23:59:59');
    } elseif ($durationDays > 0 && $durationDays <= 365) {
        $suspendedUntil = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Suspension requires a valid duration']);
        exit;
    }

    if (empty($reason)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Suspension reason is required']);
        exit;
    }
}

// ── Load target user ──────────────────────────────────────────────────────────
try {
    $db = getAdminDB();

    $stmt = $db->prepare(
        "SELECT u.id, u.email, u.account_status, u.is_active, u.role,
                COALESCE(t.first_name, s.first_name, '') AS first_name,
                COALESCE(t.last_name,  s.last_name,  '') AS last_name,
                t.id AS teacher_profile_id,
                s.id AS student_profile_id
         FROM users u
         LEFT JOIN teachers t ON t.user_id = u.id
         LEFT JOIN students s ON s.user_id = u.id
         WHERE u.id = :uid
         LIMIT 1"
    );
    $stmt->execute([':uid' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $targetName  = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
    $targetRole  = $user['teacher_profile_id'] ? 'Teacher' : 'Student';
    $currentStatus = $user['account_status'];

    // ── Guard: some actions only make sense for certain statuses ──────────────
    $statusChecks = [
        'deactivate'           => ['active', 'suspended'],
        'reactivate'           => ['inactive', 'suspended', 'archived'],
        'suspend'              => ['active', 'inactive'],
        'archive'              => ['active', 'inactive', 'suspended'],
        'unarchive'            => ['archived'],
        'force_password_reset' => ['active', 'inactive', 'suspended'],
    ];

    if (
        isset($statusChecks[$action]) &&
        !in_array($currentStatus, $statusChecks[$action], true)
    ) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => "Cannot perform '{$action}' on an account with status '{$currentStatus}'",
        ]);
        exit;
    }

    // ── Execute action ────────────────────────────────────────────────────────
    $newStatus    = $currentStatus;
    $metaJson     = null;

    switch ($action) {

        case 'deactivate':
            $db->prepare(
                "UPDATE users
                 SET    is_active = 0, account_status = 'inactive',
                        suspended_until = NULL, suspension_reason = NULL,
                        updated_at = NOW()
                 WHERE  id = :uid"
            )->execute([':uid' => $userId]);
            $newStatus = 'inactive';
            break;

        case 'reactivate':
            $db->prepare(
                "UPDATE users
                 SET    is_active = 1, account_status = 'active',
                        suspended_until = NULL, suspension_reason = NULL,
                        updated_at = NOW()
                 WHERE  id = :uid"
            )->execute([':uid' => $userId]);
            $newStatus = 'active';
            break;

        case 'suspend':
            $db->prepare(
                "UPDATE users
                 SET    is_active = 0, account_status = 'suspended',
                        suspended_until = :until, suspension_reason = :reason,
                        updated_at = NOW()
                 WHERE  id = :uid"
            )->execute([
                ':until'  => $suspendedUntil,
                ':reason' => $reason,
                ':uid'    => $userId,
            ]);
            $newStatus = 'suspended';
            $metaJson  = json_encode([
                'suspended_until'  => $suspendedUntil,
                'suspension_reason'=> $reason,
            ]);
            break;

        case 'archive':
            $db->prepare(
                "UPDATE users
                 SET    is_active = 0, account_status = 'archived',
                        suspended_until = NULL, suspension_reason = NULL,
                        updated_at = NOW()
                 WHERE  id = :uid"
            )->execute([':uid' => $userId]);
            $newStatus = 'archived';
            break;

        case 'unarchive':
            $db->prepare(
                "UPDATE users
                 SET    is_active = 1, account_status = 'active',
                        updated_at = NOW()
                 WHERE  id = :uid"
            )->execute([':uid' => $userId]);
            $newStatus = 'active';
            break;

        case 'force_password_reset':
            $db->prepare(
                "UPDATE users
                 SET    force_password_reset = 1, updated_at = NOW()
                 WHERE  id = :uid"
            )->execute([':uid' => $userId]);
            // Status unchanged — just flag set
            break;
    }

    // ── Write audit log ───────────────────────────────────────────────────────
    $db->prepare(
        "INSERT INTO admin_audit_log
            (admin_id, action, target_user_id, target_role, target_email, target_name, reason, metadata_json)
         VALUES
            (:admin_id, :action, :target_uid, :role, :email, :name, :reason, :meta)"
    )->execute([
        ':admin_id'   => $adminId,
        ':action'     => $action,
        ':target_uid' => $userId,
        ':role'       => $targetRole,
        ':email'      => $user['email'],
        ':name'       => $targetName,
        ':reason'     => $reason ?: null,
        ':meta'       => $metaJson,
    ]);

    // ── Success response ──────────────────────────────────────────────────────
    $messages = [
        'deactivate'           => "Account deactivated — {$targetName} can no longer log in.",
        'reactivate'           => "Account reactivated — {$targetName} can now log in.",
        'suspend'              => "Account suspended until " . date('M j, Y', strtotime($suspendedUntil)) . ".",
        'archive'              => "Account archived.",
        'unarchive'            => "Account unarchived and set to active.",
        'force_password_reset' => "Password reset flag set — {$targetName} will be prompted on next login.",
    ];

    http_response_code(200);
    echo json_encode([
        'success'        => true,
        'user_id'        => $userId,
        'new_status'     => $newStatus,
        'suspended_until'=> $suspendedUntil,
        'message'        => $messages[$action] ?? 'Action completed.',
    ]);

} catch (PDOException $e) {
    error_log('Admin account-action DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
