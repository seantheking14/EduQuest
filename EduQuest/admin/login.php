<?php
/**
 * Admin Login
 * Authenticates admin users against the standalone `admins` table.
 * On success, creates a PHP session and redirects to admin_dashboard.php
 */

// ── Configuration ────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'eduquest');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Session settings (match system-wide values)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');

session_start();

// ── Regenerate session ID on every login page load to prevent fixation ───────
if (empty($_SESSION['admin_id'])) {
    // Only regenerate if not already an active admin session
}

// Redirect if already logged in
if (!empty($_SESSION['admin_id']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

// ── Database helper ───────────────────────────────────────────────────────────
function getAdminDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        $pdo->exec("SET SESSION time_zone = '+00:00'");
    }
    return $pdo;
}

// ── Brute-force helpers ───────────────────────────────────────────────────────
define('MAX_ATTEMPTS',    5);
define('LOCKOUT_WINDOW',  900);  // 15 minutes in seconds

function getClientIP(): string {
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        $val = $_SERVER[$key] ?? '';
        if ($val) {
            // Take first IP if comma-separated
            $ip = trim(explode(',', $val)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function isLockedOut(string $ip): bool {
    $key   = 'admin_attempts_' . md5($ip);
    $data  = $_SESSION[$key] ?? ['count' => 0, 'since' => 0];
    if (time() - $data['since'] > LOCKOUT_WINDOW) {
        // Window expired – reset
        $_SESSION[$key] = ['count' => 0, 'since' => time()];
        return false;
    }
    return $data['count'] >= MAX_ATTEMPTS;
}

function recordAttempt(string $ip): void {
    $key  = 'admin_attempts_' . md5($ip);
    $data = $_SESSION[$key] ?? ['count' => 0, 'since' => time()];
    if (time() - $data['since'] > LOCKOUT_WINDOW) {
        $data = ['count' => 0, 'since' => time()];
    }
    $data['count']++;
    $_SESSION[$key] = $data;
}

function clearAttempts(string $ip): void {
    unset($_SESSION['admin_attempts_' . md5($ip)]);
}

// ── Process POST ──────────────────────────────────────────────────────────────
$error   = '';
$ip      = getClientIP();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['admin_login_csrf'] ?? '')) {
        $error = 'Invalid form submission. Please refresh and try again.';
    } elseif (isLockedOut($ip)) {
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address format.';
        } else {
            try {
                $db = getAdminDB();

                $stmt = $db->prepare(
                    'SELECT id, full_name, email, password_hash FROM admins WHERE email = :email LIMIT 1'
                );
                $stmt->execute([':email' => $email]);
                $admin = $stmt->fetch();

                // Constant-time comparison
                $passwordValid = $admin && password_verify($password, $admin['password_hash']);

                if (!$passwordValid) {
                    recordAttempt($ip);
                    // Generic message – do not reveal whether email or password was wrong
                    $error = 'Invalid email or password.';
                } else {
                    // ── Successful authentication ─────────────────────────────
                    clearAttempts($ip);

                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['admin_id']    = (int) $admin['id'];
                    $_SESSION['admin_name']  = $admin['full_name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['role']        = 'admin';
                    $_SESSION['admin_login_time'] = time();

                    header('Location: dashboard.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Admin login DB error: ' . $e->getMessage());
                $error = 'A system error occurred. Please try again.';
            }
        }
    }
}

// ── CSRF token ────────────────────────────────────────────────────────────────
if (empty($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_login_csrf'];

// ── Show banner messages ──────────────────────────────────────────────────────
$justRegistered = isset($_GET['registered']) && $_GET['registered'] === '1';
$justLoggedOut  = isset($_GET['logged_out']) && $_GET['logged_out'] === '1';
$sessionTimeout = isset($_GET['timeout'])    && $_GET['timeout']    === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login – EduQuest</title>
    <style>
        /* ── Reset & Base ───────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* ── Card ───────────────────────────────────────── */
        .login-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .logo {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* ── Form ───────────────────────────────────────── */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #94a3b8;
            margin-bottom: 0.4rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #f1f5f9;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.15s;
        }
        .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }

        /* ── Password wrapper ───────────────────────────── */
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 2.5rem;
        }
        .toggle-pwd {
            position: absolute;
            right: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
            font-size: 1rem;
            line-height: 1;
            padding: 0.2rem;
        }
        .toggle-pwd:hover { color: #94a3b8; }

        /* ── Alerts ─────────────────────────────────────── */
        .alert {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
        }
        .alert-error {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.4);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(34,197,94,0.15);
            border: 1px solid rgba(34,197,94,0.4);
            color: #86efac;
        }

        /* ── Submit button ──────────────────────────────── */
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
            margin-top: 0.25rem;
        }
        .btn-login:hover { background: #1d4ed8; }
        .btn-login:disabled { background: #1e40af; opacity: 0.6; cursor: not-allowed; }

        /* ── Footer links ───────────────────────────────── */
        .footer-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        .footer-links a {
            color: #3b82f6;
            text-decoration: none;
        }
        .footer-links a:hover { text-decoration: underline; }

        /* ── Security badge ─────────────────────────────── */
        .security-note {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            color: #475569;
            margin-top: 1.5rem;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo">&#9881;</div>
            <h1>Admin Portal</h1>
            <p>EduQuest Administration</p>
        </div>

        <?php if ($justRegistered): ?>
            <div class="alert alert-success" role="status">
                &#10003; Account created successfully. You can now sign in.
            </div>
        <?php elseif ($justLoggedOut): ?>
            <div class="alert alert-success" role="status">
                &#10003; You have been signed out successfully.
            </div>
        <?php elseif ($sessionTimeout): ?>
            <div class="alert alert-error" role="alert">
                Your session expired due to inactivity. Please sign in again.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="admin@example.com"
                    required
                    autocomplete="email"
                    maxlength="255"
                    value="<?= isset($_POST['email']) ? htmlspecialchars(strtolower(trim($_POST['email']))) : '' ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-pwd" aria-label="Toggle password visibility">&#128065;</button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">Sign In</button>
        </form>

        <div class="footer-links">
            <a href="register.php">Request admin access</a>
        </div>

        <p class="security-note">&#128274; Restricted access &ndash; authorised personnel only.</p>
    </div>

    <script>
    (function () {
        'use strict';

        // Toggle password visibility
        document.querySelector('.toggle-pwd').addEventListener('click', function () {
            var input = document.getElementById('password');
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            this.textContent = isHidden ? '\uD83D\uDE48' : '\uD83D\uDC41';
        });

        // Disable submit button on submit to prevent double-posting
        document.getElementById('loginForm').addEventListener('submit', function () {
            var btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Signing in\u2026';
        });
    })();
    </script>
</body>
</html>
