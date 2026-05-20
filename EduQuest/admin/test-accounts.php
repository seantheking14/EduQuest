<?php
/**
 * Test Account Seeder – DEVELOPMENT / TESTING ONLY
 *
 * Creates one pre-verified teacher and one pre-verified student account,
 * bypassing the normal email-verification and whitelist flow.
 *
 * ⚠  DELETE THIS FILE before going to production.
 *
 * URL  : /admin/test-accounts.php
 * After running: log in via the normal login page using the credentials below.
 *
 * Teacher  : testteacher@eduquest.test   /  TestTeacher01!
 * Teacher2 : testteacher2@eduquest.test  /  TestTeacher02!
 * Student  : teststudent@eduquest.test   /  TestStudent01!
 * Student2 : teststudent2@eduquest.test  /  TestStudent02!
 */

// ── Guard: block in production by checking a simple flag ─────────────────────
define('ALLOW_TEST_SEEDER', true);   // Set to false (or delete this file) in production

if (!ALLOW_TEST_SEEDER) {
    http_response_code(403);
    echo '<h2>403 Forbidden – test seeder is disabled.</h2>';
    exit;
}

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'eduquest');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Test credentials ──────────────────────────────────────────────────────────
const TEST_TEACHER = [
    'first_name' => 'Test',
    'last_name'  => 'Teacher',
    'email'      => 'testteacher@eduquest.test',
    'password'   => 'TestTeacher01!',
    'role'       => 'teacher',
];

const TEST_TEACHER2 = [
    'first_name' => 'Demo',
    'last_name'  => 'Teacher',
    'email'      => 'testteacher2@eduquest.test',
    'password'   => 'TestTeacher02!',
    'role'       => 'teacher',
];

const TEST_STUDENT = [
    'first_name' => 'Test',
    'last_name'  => 'Student',
    'email'      => 'teststudent@eduquest.test',
    'password'   => 'TestStudent01!',
    'role'       => 'student',
];

const TEST_STUDENT2 = [
    'first_name' => 'Demo',
    'last_name'  => 'Student',
    'email'      => 'teststudent2@eduquest.test',
    'password'   => 'TestStudent02!',
    'role'       => 'student',
];

// ── Run seeder ────────────────────────────────────────────────────────────────
$results = [];

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET SESSION time_zone = '+00:00'");

    // ── 1. Teacher ────────────────────────────────────────────────────────────
    $results['teacher'] = seedTeacher($pdo, TEST_TEACHER);

    // ── 2. Teacher 2 ────────────────────────────────────────────────────────────────
    $results['teacher2'] = seedTeacher($pdo, TEST_TEACHER2);

    // ── 3. Student (linked to Test Teacher) ─────────────────────────────────────
    $results['student'] = seedStudent($pdo, TEST_STUDENT, $results['teacher']['teacher_id'] ?? null);

    // ── 4. Student 2 (linked to Demo Teacher) ─────────────────────────────────
    $results['student2'] = seedStudent($pdo, TEST_STUDENT2, $results['teacher2']['teacher_id'] ?? null);

} catch (PDOException $e) {
    $results['db_error'] = 'Database error: ' . $e->getMessage();
}


// ── Helper: seed teacher ──────────────────────────────────────────────────────
function seedTeacher(PDO $pdo, array $data): array {
    $email = strtolower($data['email']);
    $hash  = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    // Ensure teacher_whitelist table exists (may not be imported yet)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS teacher_whitelist (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email      VARCHAR(150) NOT NULL UNIQUE,
            notes      VARCHAR(255) NULL,
            added_by   INT UNSIGNED NULL,
            added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_wl_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->beginTransaction();
    try {
        // 1a. Whitelist the teacher email so login.php passes the whitelist check
        $wl = $pdo->prepare(
            'INSERT IGNORE INTO teacher_whitelist (email, notes) VALUES (:email, :notes)'
        );
        $wl->execute([
            ':email' => $email,
            ':notes' => 'Auto-added by test-accounts.php seeder',
        ]);

        // 1b. users row – pre-verified, active
        $existing = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $existing->execute([':email' => $email]);
        $user = $existing->fetch();

        if ($user) {
            // Reset password & ensure verified
            $upd = $pdo->prepare(
                'UPDATE users
                 SET password_hash = :hash, is_active = 1, email_verified = 1,
                     email_verified_at = NOW(), first_name = :fn, last_name = :ln
                 WHERE id = :id'
            );
            $upd->execute([
                ':hash' => $hash,
                ':fn'   => $data['first_name'],
                ':ln'   => $data['last_name'],
                ':id'   => $user['id'],
            ]);
            $userId = $user['id'];
            $status = 'updated';
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO users
                    (email, password_hash, first_name, last_name, role, is_active, email_verified, email_verified_at)
                 VALUES
                    (:email, :hash, :fn, :ln, :role, 1, 1, NOW())'
            );
            $ins->execute([
                ':email' => $email,
                ':hash'  => $hash,
                ':fn'    => $data['first_name'],
                ':ln'    => $data['last_name'],
                ':role'  => $data['role'],
            ]);
            $userId = (int)$pdo->lastInsertId();
            $status = 'created';
        }

        // 1c. teachers profile row
        $tExisting = $pdo->prepare('SELECT id FROM teachers WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $tExisting->execute([':email' => $email]);
        $teacher = $tExisting->fetch();

        if ($teacher) {
            $teacherId = $teacher['id'];
        } else {
            $tIns = $pdo->prepare(
                'INSERT INTO teachers (user_id, first_name, last_name, email, role)
                 VALUES (:uid, :fn, :ln, :email, :role)'
            );
            $tIns->execute([
                ':uid'   => $userId,
                ':fn'    => $data['first_name'],
                ':ln'    => $data['last_name'],
                ':email' => $email,
                ':role'  => 'teacher',
            ]);
            $teacherId = (int)$pdo->lastInsertId();
        }

        // 1d. Back-fill profile_id on users row
        $pdo->prepare('UPDATE users SET profile_id = :pid WHERE id = :uid')
            ->execute([':pid' => $teacherId, ':uid' => $userId]);

        $pdo->commit();

        return [
            'status'     => $status,
            'user_id'    => $userId,
            'teacher_id' => $teacherId,
            'email'      => $email,
            'password'   => $data['password'],
            'role'       => $data['role'],
        ];

    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}


// ── Helper: seed student ──────────────────────────────────────────────────────
function seedStudent(PDO $pdo, array $data, ?int $teacherId): array {
    $email = strtolower($data['email']);
    $hash  = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    $pdo->beginTransaction();
    try {
        // 2a. users row – pre-verified, active
        $existing = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $existing->execute([':email' => $email]);
        $user = $existing->fetch();

        if ($user) {
            $upd = $pdo->prepare(
                'UPDATE users
                 SET password_hash = :hash, is_active = 1, email_verified = 1,
                     email_verified_at = NOW(), first_name = :fn, last_name = :ln
                 WHERE id = :id'
            );
            $upd->execute([
                ':hash' => $hash,
                ':fn'   => $data['first_name'],
                ':ln'   => $data['last_name'],
                ':id'   => $user['id'],
            ]);
            $userId = $user['id'];
            $status = 'updated';
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO users
                    (email, password_hash, first_name, last_name, role, is_active, email_verified, email_verified_at)
                 VALUES
                    (:email, :hash, :fn, :ln, :role, 1, 1, NOW())'
            );
            $ins->execute([
                ':email' => $email,
                ':hash'  => $hash,
                ':fn'    => $data['first_name'],
                ':ln'    => $data['last_name'],
                ':role'  => $data['role'],
            ]);
            $userId = (int)$pdo->lastInsertId();
            $status = 'created';
        }

        // 2b. students profile row
        $sExisting = $pdo->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
        $sExisting->execute([':uid' => $userId]);
        $student = $sExisting->fetch();

        if ($student) {
            $studentId = $student['id'];
            // Ensure teacher link is set
            if ($teacherId) {
                $pdo->prepare('UPDATE students SET teacher_id = :tid WHERE id = :sid')
                    ->execute([':tid' => $teacherId, ':sid' => $studentId]);
            }
        } else {
            $sIns = $pdo->prepare(
                'INSERT INTO students (user_id, teacher_id, first_name, last_name)
                 VALUES (:uid, :tid, :fn, :ln)'
            );
            $sIns->execute([
                ':uid' => $userId,
                ':tid' => $teacherId,
                ':fn'  => $data['first_name'],
                ':ln'  => $data['last_name'],
            ]);
            $studentId = (int)$pdo->lastInsertId();
        }

        // 2c. Back-fill profile_id on users row
        $pdo->prepare('UPDATE users SET profile_id = :pid WHERE id = :uid')
            ->execute([':pid' => $studentId, ':uid' => $userId]);

        $pdo->commit();

        return [
            'status'     => $status,
            'user_id'    => $userId,
            'student_id' => $studentId,
            'email'      => $email,
            'password'   => $data['password'],
            'role'       => $data['role'],
            'teacher_id' => $teacherId,
        ];

    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Test Account Seeder – EduQuest</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 2.5rem 3rem;
            max-width: 680px;
            width: 100%;
        }
        h1 { font-size: 1.4rem; margin-bottom: .4rem; color: #1e293b; }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
            padding: .75rem 1rem;
            font-size: .875rem;
            color: #92400e;
            margin-bottom: 1.75rem;
        }
        .account-block {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
        }
        .account-block h2 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: .75rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .badge {
            display: inline-block;
            padding: .15rem .55rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .badge-teacher { background: #dbeafe; color: #1d4ed8; }
        .badge-student { background: #dcfce7; color: #166534; }
        .badge-created { background: #d1fae5; color: #065f46; }
        .badge-updated { background: #fef9c3; color: #713f12; }
        .badge-error   { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        td { padding: .4rem .5rem; }
        td:first-child { color: #64748b; width: 40%; }
        td:last-child  { font-family: monospace; font-weight: 600; color: #1e293b; }
        .footer {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #e2e8f0;
            font-size: .8rem;
            color: #94a3b8;
            text-align: center;
        }
        a.btn {
            display: inline-block;
            margin-top: 1rem;
            padding: .5rem 1.25rem;
            background: #3b82f6;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: .875rem;
            font-weight: 500;
        }
        a.btn:hover { background: #2563eb; }
        .error-msg { color: #dc2626; font-size: .875rem; margin-top: .5rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>EduQuest – Test Account Seeder</h1>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
        DEV / TESTING ONLY – Accounts are created with email verification bypassed.
    </p>
    <div class="warning">
        ⚠ <strong>Delete this file before deploying to production.</strong>
        Keeping it live allows anyone to reset these test passwords.
    </div>

    <?php if (isset($results['db_error'])): ?>
        <p class="error-msg"><?= htmlspecialchars($results['db_error']) ?></p>
    <?php else: ?>

        <?php foreach ([
            'teacher'  => 'Teacher',
            'teacher2' => 'Teacher 2',
            'student'  => 'Student',
            'student2' => 'Student 2',
        ] as $key => $label): ?>
            <?php
                $r          = $results[$key] ?? [];
                $roleClass  = str_starts_with($key, 'teacher') ? 'teacher' : 'student';
            ?>
            <div class="account-block">
                <h2>
                    <span class="badge badge-<?= $roleClass ?>"><?= $label ?></span>
                    <?php
                        $s = $r['status'] ?? 'error';
                        $badgeClass = $s === 'error' ? 'badge-error' : ($s === 'updated' ? 'badge-updated' : 'badge-created');
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($s)) ?></span>
                </h2>
                <?php if ($s === 'error'): ?>
                    <p class="error-msg"><?= htmlspecialchars($r['message'] ?? 'Unknown error') ?></p>
                <?php else: ?>
                <table>
                    <tr><td>Email</td><td><?= htmlspecialchars($r['email']) ?></td></tr>
                    <tr><td>Password</td><td><?= htmlspecialchars($r['password']) ?></td></tr>
                    <tr><td>Role</td><td><?= htmlspecialchars($r['role']) ?></td></tr>
                    <tr><td>User ID</td><td><?= (int)($r['user_id'] ?? 0) ?></td></tr>
                    <?php if ($roleClass === 'teacher'): ?>
                    <tr><td>Teacher ID</td><td><?= (int)($r['teacher_id'] ?? 0) ?></td></tr>
                    <tr><td>Whitelist</td><td>Added / already present</td></tr>
                    <?php else: ?>
                    <tr><td>Student ID</td><td><?= (int)($r['student_id'] ?? 0) ?></td></tr>
                    <tr><td>Linked Teacher ID</td><td><?= (int)($r['teacher_id'] ?? 0) ?></td></tr>
                    <?php endif; ?>
                    <tr><td>Email Verified</td><td>Yes (bypassed)</td></tr>
                    <tr><td>Is Active</td><td>Yes</td></tr>
                </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <a class="btn" href="../auth/login/login.html">Go to Login Page</a>

    <?php endif; ?>

    <div class="footer">
        Run this page again at any time to reset all four test account passwords.
    </div>
</div>
</body>
</html>
