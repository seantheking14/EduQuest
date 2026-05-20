<?php
/**
 * Admin Account Delete API
 * POST /admin/api/account-delete.php
 *
 * Two-step permanent deletion of a teacher or student account.
 *
 * Step 1 — check=1 : Returns a JSON warning with linked record counts.
 *           { success, warning, linked_students, linked_iep_count }
 *
 * Step 2 — confirm="DELETE" : Permanently removes the user and all
 *           associated records via CASCADE. Logs to admin_audit_log.
 *           { success, message, deleted_summary }
 *
 * Requires: active admin session ($_SESSION['admin_id'], role='admin')
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

$userId    = (int) ($input['user_id'] ?? 0);
$check     = !empty($input['check']);        // Step 1: dry-run
$confirm   = trim($input['confirm'] ?? ''); // Step 2: must equal 'DELETE'
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

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user_id']);
    exit;
}

try {
    $db = getAdminDB();

    // ── Load target user ──────────────────────────────────────────────────────
    $stmt = $db->prepare(
        "SELECT u.id, u.email, u.role, u.account_status,
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

    $targetName = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
    $isTeacher  = (bool) $user['teacher_profile_id'];

    // ── Count linked records ──────────────────────────────────────────────────
    $linkedStudentCount = 0;

    if ($isTeacher) {
        $cntStmt = $db->prepare(
            "SELECT COUNT(*) AS cnt
             FROM   students
             WHERE  teacher_id = :tid"
        );
        $cntStmt->execute([':tid' => $user['teacher_profile_id']]);
        $linkedStudentCount = (int) $cntStmt->fetchColumn();
    }

    // ── Step 1: dry-run / warning ──────────────────────────────────────────────
    if ($check) {
        http_response_code(200);
        echo json_encode([
            'success'          => true,
            'target_name'      => $targetName,
            'target_role'      => $isTeacher ? 'Teacher' : 'Student',
            'linked_students'  => $linkedStudentCount,
            'warning'          => $isTeacher && $linkedStudentCount > 0
                ? "This teacher has {$linkedStudentCount} linked student profile"
                  . ($linkedStudentCount !== 1 ? 's' : '')
                  . ". Deleting this account will permanently remove all associated student records. This cannot be undone."
                : null,
        ]);
        exit;
    }

    // ── Step 2: confirm deletion ───────────────────────────────────────────────
    if ($confirm !== 'DELETE') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Type 'DELETE' to confirm permanent deletion"]);
        exit;
    }

    // Snapshot counts before deletion (for audit log)
    $sessionCount = 0;
    $sesStmt = $db->prepare("SELECT COUNT(*) FROM user_sessions WHERE user_id = :uid");
    $sesStmt->execute([':uid' => $userId]);
    $sessionCount = (int) $sesStmt->fetchColumn();

    $deletedSummary = [
        'user_id'         => $userId,
        'email'           => $user['email'],
        'role'            => $isTeacher ? 'Teacher' : 'Student',
        'linked_students' => $linkedStudentCount,
        'sessions_cleared'=> $sessionCount,
    ];

    // Write audit log BEFORE deleting (the user record will be gone after)
    $db->prepare(
        "INSERT INTO admin_audit_log
            (admin_id, action, target_user_id, target_role, target_email, target_name, reason, metadata_json)
         VALUES
            (:admin_id, 'delete', :target_uid, :role, :email, :name, NULL, :meta)"
    )->execute([
        ':admin_id'   => $adminId,
        ':target_uid' => $userId,
        ':role'       => $isTeacher ? 'Teacher' : 'Student',
        ':email'      => $user['email'],
        ':name'       => $targetName,
        ':meta'       => json_encode($deletedSummary),
    ]);

    // Delete the user — FK CASCADE handles:
    //   users → user_sessions (ON DELETE CASCADE)
    //   users → teachers (via fk_teacher_user ON DELETE CASCADE)
    //     teachers → students (via fk_student_teacher ON DELETE CASCADE if set)
    //   users → students (via fk_student_user ON DELETE CASCADE)
    $db->prepare("DELETE FROM users WHERE id = :uid")
       ->execute([':uid' => $userId]);

    http_response_code(200);
    echo json_encode([
        'success'         => true,
        'message'         => "Account for {$targetName} ({$user['email']}) has been permanently deleted.",
        'deleted_summary' => $deletedSummary,
    ]);

} catch (PDOException $e) {
    error_log('Admin account-delete DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
