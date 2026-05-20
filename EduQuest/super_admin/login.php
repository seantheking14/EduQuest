<?php
/**
 * Super Admin Login
 * Authenticates against `super_admins` table.
 * On success: stores super_admin session and redirects to dashboard.
 */

// ── Configuration ─────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'eduquest');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
session_start();

// Redirect if already logged in
if (!empty($_SESSION['super_admin_id']) && ($_SESSION['role'] ?? '') === 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

// ── Database helper ────────────────────────────────────────────────────────────
function getSADB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
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
    }
    return $pdo;
}

// ── Brute-force helpers ────────────────────────────────────────────────────────
define('MAX_ATTEMPTS',   5);
define('LOCKOUT_WINDOW', 900); // 15 minutes

function getClientIP(): string {
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        $val = $_SERVER[$key] ?? '';
        if ($val) {
            $ip = trim(explode(',', $val)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function isLockedOut(string $ip): bool {
    $key  = 'sa_attempts_' . md5($ip);
    $data = $_SESSION[$key] ?? ['count' => 0, 'since' => 0];
    if (time() - $data['since'] > LOCKOUT_WINDOW) {
        $_SESSION[$key] = ['count' => 0, 'since' => time()];
        return false;
    }
    return $data['count'] >= MAX_ATTEMPTS;
}

function recordAttempt(string $ip): void {
    $key  = 'sa_attempts_' . md5($ip);
    $data = $_SESSION[$key] ?? ['count' => 0, 'since' => time()];
    if (time() - $data['since'] > LOCKOUT_WINDOW) $data = ['count' => 0, 'since' => time()];
    $data['count']++;
    $_SESSION[$key] = $data;
}

function clearAttempts(string $ip): void {
    unset($_SESSION['sa_attempts_' . md5($ip)]);
}

// ── Process POST ───────────────────────────────────────────────────────────────
$error = '';
$ip    = getClientIP();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['sa_login_csrf'] ?? '', $_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please refresh and try again.';
    } elseif (isLockedOut($ip)) {
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
    } else {
        $email    = strtolower(trim($_POST['email']    ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address format.';
        } else {
            try {
                $db   = getSADB();
                $stmt = $db->prepare(
                    'SELECT id, full_name, email, password_hash FROM super_admins WHERE email = :email LIMIT 1'
                );
                $stmt->execute([':email' => $email]);
                $sa = $stmt->fetch();

                $valid = $sa && password_verify($password, $sa['password_hash']);

                if (!$valid) {
                    recordAttempt($ip);
                    $error = 'Invalid email or password.';
                } else {
                    clearAttempts($ip);
                    session_regenerate_id(true);

                    $_SESSION['super_admin_id']    = (int) $sa['id'];
                    $_SESSION['super_admin_name']  = $sa['full_name'];
                    $_SESSION['super_admin_email'] = $sa['email'];
                    $_SESSION['role']              = 'super_admin';
                    $_SESSION['sa_login_time']     = time();

                    header('Location: dashboard.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Super admin login DB error: ' . $e->getMessage());
                $error = 'A system error occurred. Please try again.';
            }
        }
    }
}

// ── CSRF token ─────────────────────────────────────────────────────────────────
if (empty($_SESSION['sa_login_csrf'])) {
    $_SESSION['sa_login_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['sa_login_csrf'];

$justRegistered = isset($_GET['registered']) && $_GET['registered'] === '1';
$justLoggedOut  = isset($_GET['logged_out'])  && $_GET['logged_out']  === '1';
$sessionTimeout = isset($_GET['timeout'])     && $_GET['timeout']     === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Super Admin Login – EduQuest</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0a0f1e;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            background: #151d30;
            border: 1px solid #1e2d4a;
            border-radius: 12px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.6);
        }
        .card-header { text-align: center; margin-bottom: 2rem; }
        .badge-super {
            display: inline-block;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin-bottom: 0.75rem;
        }
        .card-header h1 { font-size: 1.5rem; font-weight: 700; color: #f1f5f9; margin-bottom: 0.25rem; }
        .card-header p  { font-size: 0.8rem; color: #64748b; }
        .form-group { margin-bottom: 1.1rem; }
        label { display: block; font-size: 0.8rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.4rem; }
        input[type=email], input[type=password] {
            width: 100%;
            padding: 0.65rem 0.875rem;
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 7px;
            color: #f1f5f9;
            font-size: 0.875rem;
            transition: border-color 0.15s;
            outline: none;
        }
        input:focus { border-color: #7c3aed; }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.9; }
        .alert {
            border-radius: 7px;
            padding: 0.875rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .alert-error   { background: rgba(220,38,38,0.12); border: 1px solid rgba(220,38,38,0.3);  color: #fca5a5; }
        .alert-success { background: rgba(34,197,94,0.12);  border: 1px solid rgba(34,197,94,0.3);  color: #86efac; }
        .alert-info    { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.3); color: #93c5fd; }
        .register-link { text-align: center; margin-top: 1.25rem; font-size: 0.8rem; color: #64748b; }
        .register-link a { color: #818cf8; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="badge-super">Super Admin</div>
        <h1>Sign In</h1>
        <p>EduQuest System Administration</p>
    </div>

    <?php if ($justRegistered): ?>
        <div class="alert alert-success">Account created. Please sign in.</div>
    <?php elseif ($justLoggedOut): ?>
        <div class="alert alert-info">You have been signed out.</div>
    <?php elseif ($sessionTimeout): ?>
        <div class="alert alert-info">Your session expired. Please sign in again.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" maxlength="255"
                   required autocomplete="username" />
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   maxlength="200" required autocomplete="current-password" />
        </div>

        <button type="submit" class="btn">Sign In</button>
    </form>

    <div class="register-link">
        Need to create an account? <a href="register.php">Register</a>
    </div>
</div>
</body>
</html>
