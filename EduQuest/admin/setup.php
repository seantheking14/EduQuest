<?php
/**
 * Admin Account Setup – ONE-TIME USE
 * Navigate to this file once to seed the admin account, then DELETE it.
 *
 * URL: /admin/setup.php
 */

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'eduquest');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Credentials to seed ───────────────────────────────────────────────────────
$adminFullName = 'EduQuest Admin';
$adminEmail    = 'eduquestadmin@gmail.com';
$adminPassword = 'Educationalquest01!';

// ─────────────────────────────────────────────────────────────────────────────

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

    // Ensure the admins table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name     VARCHAR(200)   NOT NULL,
            email         VARCHAR(255)   NOT NULL UNIQUE,
            password_hash VARCHAR(255)   NOT NULL,
            created_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Check if account already exists
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => strtolower($adminEmail)]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update password in case it changed
        $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE admins SET password_hash = :hash, full_name = :name WHERE email = :email');
        $stmt->execute([':hash' => $hash, ':name' => $adminFullName, ':email' => strtolower($adminEmail)]);
        $message = 'Admin account updated successfully.';
    } else {
        $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            'INSERT INTO admins (full_name, email, password_hash) VALUES (:name, :email, :hash)'
        );
        $stmt->execute([':name' => $adminFullName, ':email' => strtolower($adminEmail), ':hash' => $hash]);
        $message = 'Admin account created successfully.';
    }

    $status = 'success';

} catch (PDOException $e) {
    $message = 'Database error: ' . $e->getMessage();
    $status  = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Setup – EduQuest</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2.5rem 2rem;
            max-width: 460px;
            width: 100%;
            text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        .msg {
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.4); color: #86efac; }
        .error   { background: rgba(239,68,68,0.15);  border: 1px solid rgba(239,68,68,0.4);  color: #fca5a5; }
        .details {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: left;
            margin: 1rem 0;
            line-height: 1.8;
        }
        .details strong { color: #e2e8f0; }
        a.btn {
            display: inline-block;
            margin-top: 1.25rem;
            padding: 0.65rem 1.5rem;
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }
        a.btn:hover { background: #1d4ed8; }
        .warning {
            font-size: 0.75rem;
            color: #f59e0b;
            margin-top: 1.25rem;
            padding: 0.6rem 0.75rem;
            background: rgba(245,158,11,0.1);
            border: 1px solid rgba(245,158,11,0.3);
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($status === 'success'): ?>
            <div class="icon">&#10003;</div>
            <h1>Setup Complete</h1>
            <div class="msg success"><?= htmlspecialchars($message) ?></div>
            <div class="details">
                <div><strong>Email:</strong> <?= htmlspecialchars($adminEmail) ?></div>
                <div><strong>Password:</strong> (as configured)</div>
            </div>
            <a href="login.php" class="btn">Go to Admin Login &rarr;</a>
        <?php else: ?>
            <div class="icon">&#10007;</div>
            <h1>Setup Failed</h1>
            <div class="msg error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="warning">
            &#9888; Delete or restrict access to <strong>setup.php</strong> after use.
        </div>
    </div>
</body>
</html>
