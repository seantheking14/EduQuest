<?php
/**
 * Admin Registration
 * Accessible only via direct URL: /admin/register.php
 * NOT linked from the main login screen.
 */

// ── Configuration ────────────────────────────────────────────────────────────
define('ADMIN_REGISTRATION_KEY', 'EduQuest@AdminKey2025!');   // Change before production
define('DB_HOST',    'mysql-24e761da-mymail-4f8f.j.aivencloud.com');
define('DB_PORT',    '18185');
define('DB_NAME',    'defaultdb');
define('DB_USER',    'avnadmin');
define('DB_PASS',    'AVNS_Rd0v9GCE8ryXi3vCnWc');
define('DB_CHARSET', 'utf8mb4');

session_start();

// Redirect if already logged in as admin
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors   = [];
$success  = false;
$formData = ['full_name' => '', 'email' => ''];

// ── Database helper ──────────────────────────────────────────────────────────
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

// ── Process POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['admin_reg_csrf'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $fullName   = trim($_POST['full_name'] ?? '');
        $email      = strtolower(trim($_POST['email'] ?? ''));
        $password   = $_POST['password'] ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';
        $regKey     = $_POST['registration_key'] ?? '';

        // Preserve safe form values for re-display
        $formData = ['full_name' => htmlspecialchars($fullName), 'email' => htmlspecialchars($email)];

        // ── Validation ───────────────────────────────────────────────────────
        if (empty($fullName)) {
            $errors[] = 'Full name is required.';
        } elseif (mb_strlen($fullName) > 200) {
            $errors[] = 'Full name must not exceed 200 characters.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        } elseif (!preg_match('/[\W_]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        if ($password !== $confirmPwd) {
            $errors[] = 'Passwords do not match.';
        }

        // Secret key check (constant-time comparison to prevent timing attacks)
        if (!hash_equals(ADMIN_REGISTRATION_KEY, $regKey)) {
            $errors[] = 'Invalid admin registration key.';
        }

        // ── Database operations ───────────────────────────────────────────────
        if (empty($errors)) {
            try {
                $db = getAdminDB();

                // Check for duplicate email
                $stmt = $db->prepare('SELECT id FROM admins WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    $errors[] = 'An account with this email address already exists.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                    $stmt = $db->prepare(
                        'INSERT INTO admins (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)'
                    );
                    $stmt->execute([
                        ':full_name'     => $fullName,
                        ':email'         => $email,
                        ':password_hash' => $passwordHash,
                    ]);

                    // Registration succeeded – redirect to login
                    session_unset();
                    session_destroy();
                    header('Location: login.php?registered=1');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Admin registration DB error: ' . $e->getMessage());
                $errors[] = 'A database error occurred. Please try again.';
            }
        }
    }
}

// ── Generate CSRF token ───────────────────────────────────────────────────────
if (empty($_SESSION['admin_reg_csrf'])) {
    $_SESSION['admin_reg_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_reg_csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Registration – EduQuest</title>
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
        .register-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header .logo {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .register-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
        }
        .register-header p {
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
            transition: border-color 0.15s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }
        .form-group .input-hint {
            font-size: 0.75rem;
            color: #475569;
            margin-top: 0.3rem;
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
        .alert-error ul {
            padding-left: 1.2rem;
            margin: 0;
        }
        .alert-error ul li { margin-top: 0.2rem; }

        /* ── Submit button ──────────────────────────────── */
        .btn-register {
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
            margin-top: 0.5rem;
        }
        .btn-register:hover { background: #1d4ed8; }
        .btn-register:disabled { background: #1e40af; opacity: 0.6; cursor: not-allowed; }

        /* ── Footer link ────────────────────────────────── */
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        .login-link a {
            color: #3b82f6;
            text-decoration: none;
        }
        .login-link a:hover { text-decoration: underline; }

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

        /* ── Legal Modal ────────────────────────────────── */
        .legal-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.72);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999; padding: 1rem;
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
        }
        .legal-modal-overlay[hidden] { display: none; }
        .legal-modal-box {
            background: #2f537f; border: 1px solid #5b7de8; border-radius: 12px;
            width: 100%; max-width: 660px; max-height: 85vh;
            display: flex; flex-direction: column;
            box-shadow: 0 25px 60px rgba(0,0,0,0.45);
            animation: legalModalIn .2s ease;
        }
        @keyframes legalModalIn {
            from { opacity: 0; transform: scale(.95) translateY(-10px); }
            to   { opacity: 1; transform: scale(1)  translateY(0); }
        }
        .legal-modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: .8rem 1.25rem; border-bottom: 1px solid #5b7de8; flex-shrink: 0; gap: .5rem;
        }
        .legal-modal-tabs { display: flex; gap: .25rem; }
        .legal-tab {
            background: none; border: none; padding: .45rem 1rem; border-radius: 6px;
            color: #eef2ff; font-size: .875rem; font-weight: 600; cursor: pointer;
            transition: background .15s, color .15s;
        }
        .legal-tab:hover  { background: rgba(59,130,246,.15); color: #fff; }
        .legal-tab.active { background: #5b7de8; color: #fff; }
        .legal-modal-close {
            background: none; border: none; color: #cbd5e1; font-size: 1.5rem;
            cursor: pointer; padding: .1rem .4rem; line-height: 1; border-radius: 4px;
            transition: color .15s, background .15s; margin-left: auto;
        }
        .legal-modal-close:hover { color: #fff; background: rgba(255,255,255,.1); }
        .legal-modal-body {
            overflow-y: auto; padding: 1.35rem 1.5rem; flex: 1;
            color: #f8fafc !important; font-size: .95rem; line-height: 1.85;
        }
        .legal-modal-body a {
            color: #bfdbfe !important; text-decoration: underline;
        }
        .legal-modal-body strong,
        .legal-modal-body em,
        .legal-modal-body span,
        .legal-modal-body p,
        .legal-modal-body ul,
        .legal-modal-body li {
            color: #f8fafc !important;
        }
        .legal-tab-content        { display: none; }
        .legal-tab-content.active { display: block; }
        .legal-modal-body h2 { font-size: 1.2rem; color: #ffffff !important; margin: 0 0 .55rem; font-weight: 700; text-shadow: 0 1px 2px rgba(0,0,0,0.35); }
        .legal-modal-body h3 { font-size: 1rem; color: #f8fafc !important; margin: 1.25rem 0 .4rem; font-weight: 700; text-shadow: 0 1px 2px rgba(0,0,0,0.25); }
        .legal-modal-body p  { margin-bottom: .75rem; }
        .legal-modal-body ul { padding-left: 1.25rem; margin-bottom: .75rem; }
        .legal-modal-body li { margin-bottom: .35rem; }
        .legal-modal-footer {
            padding: .75rem 1.5rem; border-top: 1px solid #2d3561;
            display: flex; justify-content: flex-end; flex-shrink: 0;
        }
        .legal-modal-close-btn {
            background: #3b82f6; color: #fff; border: none;
            padding: .5rem 1.75rem; border-radius: 6px;
            font-size: .875rem; font-weight: 600; cursor: pointer; transition: background .15s;
        }
        .legal-modal-close-btn:hover { background: #2563eb; }
        @media (max-width: 480px) {
            .legal-modal-box { max-height: 92vh; border-radius: 10px; }
            .legal-modal-body { padding: 1rem; }
            .legal-tab { padding: .4rem .65rem; font-size: .8rem; }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <div class="logo">&#9881;</div>
            <h1>Admin Registration</h1>
            <p>EduQuest Administration Portal</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <?php if (count($errors) === 1): ?>
                    <?= htmlspecialchars($errors[0]) ?>
                <?php else: ?>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="registerForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= $formData['full_name'] ?>"
                    placeholder="Enter your full name"
                    required
                    autocomplete="name"
                    maxlength="200"
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= $formData['email'] ?>"
                    placeholder="admin@example.com"
                    required
                    autocomplete="email"
                    maxlength="255"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a strong password"
                        required
                        autocomplete="new-password"
                        minlength="8"
                    >
                    <button type="button" class="toggle-pwd" aria-label="Toggle password visibility" data-target="password">&#128065;</button>
                </div>
                <p class="input-hint">Min. 8 characters, including uppercase, number &amp; special character.</p>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="toggle-pwd" aria-label="Toggle confirm password visibility" data-target="confirm_password">&#128065;</button>
                </div>
            </div>

            <div class="form-group">
                <label for="registration_key">Admin Registration Key</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="registration_key"
                        name="registration_key"
                        placeholder="Enter the secret registration key"
                        required
                        autocomplete="off"
                    >
                    <button type="button" class="toggle-pwd" aria-label="Toggle key visibility" data-target="registration_key">&#128065;</button>
                </div>
            </div>

            <div class="form-group" style="display:flex;align-items:flex-start;gap:.6rem;">
                <input type="checkbox" id="agreeTerms" name="agreeTerms" required
                       style="margin-top:.2rem;flex-shrink:0;accent-color:#3b82f6;width:1rem;height:1rem;">
                <label for="agreeTerms" style="font-size:.875rem;line-height:1.4;cursor:pointer;">
                    I agree to the <a href="#" onclick="openLegalModal('tos');return false;" style="color:#60a5fa;">Terms of Service</a> and <a href="#" onclick="openLegalModal('pp');return false;" style="color:#60a5fa;">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn-register" id="submitBtn">Create Admin Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>

        <p class="security-note">&#128274; This page is restricted to authorised personnel only.</p>
    </div>

    <script>
    (function () {
        'use strict';

        // ── Toggle password visibility ────────────────────
        document.querySelectorAll('.toggle-pwd').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (!input) return;
                var isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                btn.textContent = isHidden ? '\uD83D\uDE48' : '\uD83D\uDC41';
            });
        });

        // ── Client-side validation ────────────────────────
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            var pwd  = document.getElementById('password').value;
            var cpwd = document.getElementById('confirm_password').value;

            if (pwd !== cpwd) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return;
            }

            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').textContent = 'Creating account…';
        });
    })();
    </script>

    <!-- Legal Modal (Terms of Service & Privacy Policy) -->
    <div id="legalModal" class="legal-modal-overlay" role="dialog" aria-modal="true" hidden>
        <div class="legal-modal-box">
            <div class="legal-modal-header">
                <div class="legal-modal-tabs" role="tablist">
                    <button class="legal-tab active" role="tab" data-tab="tos" aria-selected="true">Terms of Service</button>
                    <button class="legal-tab" role="tab" data-tab="pp" aria-selected="false">Privacy Policy</button>
                </div>
                <button class="legal-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="legal-modal-body">
                <div id="legalTabTos" class="legal-tab-content active" role="tabpanel">
                    <h2>Terms of Service</h2>
                    <p><strong>Last Updated: May 2026</strong></p>
                    <p>Welcome to EduQuest. By creating an account and using our platform, you agree to these Terms of Service. Please read them carefully.</p>
                    <h3>1. Acceptance of Terms</h3>
                    <p>By registering for or using EduQuest, you agree to be bound by these Terms. If you are registering on behalf of a school or institution, you represent that you have authority to bind that organisation.</p>
                    <h3>2. Use of the Platform</h3>
                    <p>EduQuest is an educational platform designed to support learning through gamified content, quizzes, and interactive modules. You agree to use the platform only for lawful, educational purposes in accordance with these Terms.</p>
                    <h3>3. Account Responsibilities</h3>
                    <p>You are responsible for: maintaining the confidentiality of your login credentials; all activities that occur under your account; and promptly notifying us of any unauthorised access.</p>
                    <h3>4. Acceptable Use</h3>
                    <p>You agree not to share or transfer your account, post harmful or misleading content, attempt to disrupt platform services, or use automated tools to access the platform without authorisation.</p>
                    <h3>5. Intellectual Property</h3>
                    <p>All content on EduQuest &mdash; including courses, quizzes, and design elements &mdash; is owned by EduQuest or its content providers. You may not reproduce or distribute platform content without prior written permission.</p>
                    <h3>6. Limitation of Liability</h3>
                    <p>EduQuest is provided &ldquo;as is&rdquo; without warranties of any kind. To the fullest extent permitted by law, EduQuest shall not be liable for any indirect, incidental, or consequential damages arising from your use of the platform.</p>
                    <h3>7. Termination</h3>
                    <p>We reserve the right to suspend or terminate accounts that violate these Terms. You may delete your account at any time by contacting your platform administrator.</p>
                    <h3>8. Changes to Terms</h3>
                    <p>We may update these Terms from time to time. Continued use of EduQuest after changes are posted constitutes your acceptance of the revised Terms.</p>
                </div>
                <div id="legalTabPp" class="legal-tab-content" role="tabpanel">
                    <h2>Privacy Policy</h2>
                    <p><strong>Last Updated: May 2026</strong></p>
                    <p>EduQuest is committed to protecting your personal information. This Privacy Policy explains what data we collect, how we use it, and your rights.</p>
                    <h3>1. Information We Collect</h3>
                    <ul>
                        <li><strong>Account data:</strong> name, email address, role, and encrypted password.</li>
                        <li><strong>Activity data:</strong> quiz scores, course progress, game activity, and learning behaviour.</li>
                        <li><strong>Technical data:</strong> browser type, device information, and IP address for security purposes.</li>
                    </ul>
                    <h3>2. How We Use Your Information</h3>
                    <p>Your information is used to provide and personalise your learning experience, track academic progress, send account-related notifications, and improve platform features and security.</p>
                    <h3>3. Information Sharing</h3>
                    <p>We do not sell your personal data. Information may be shared with your teacher or institution as part of the platform&rsquo;s educational function, with service providers bound by confidentiality agreements, or when required by law.</p>
                    <h3>4. Data Security</h3>
                    <p>We implement industry-standard security measures including password hashing, CSRF protection, and encrypted connections. However, no system is completely secure and we cannot guarantee absolute security.</p>
                    <h3>5. Your Rights</h3>
                    <p>You have the right to access, correct, or request deletion of your personal data. To exercise these rights, please contact your platform administrator.</p>
                    <h3>6. Cookies</h3>
                    <p>EduQuest uses session cookies to maintain your login state and provide a seamless experience. These cookies are deleted when you log out or close your browser.</p>
                    <h3>7. Contact</h3>
                    <p>For privacy-related questions or requests, please contact your school or institution administrator, or reach out to the EduQuest support team.</p>
                </div>
            </div>
            <div class="legal-modal-footer">
                <button class="legal-modal-close-btn">Close</button>
            </div>
        </div>
    </div>
    <script>
    (function () {
        'use strict';
        var modal = document.getElementById('legalModal');
        if (!modal) return;
        window.openLegalModal = function (tab) {
            modal.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';
            switchTab(tab || 'tos');
            modal.querySelector('.legal-modal-close').focus();
        };
        function closeLegalModal() {
            modal.setAttribute('hidden', '');
            document.body.style.overflow = '';
        }
        function switchTab(tab) {
            modal.querySelectorAll('.legal-tab').forEach(function (t) {
                var active = t.dataset.tab === tab;
                t.classList.toggle('active', active);
                t.setAttribute('aria-selected', String(active));
            });
            modal.querySelectorAll('.legal-tab-content').forEach(function (c) { c.classList.remove('active'); });
            var panel = document.getElementById(tab === 'tos' ? 'legalTabTos' : 'legalTabPp');
            if (panel) panel.classList.add('active');
        }
        modal.querySelectorAll('.legal-tab').forEach(function (t) {
            t.addEventListener('click', function () { switchTab(this.dataset.tab); });
        });
        modal.querySelector('.legal-modal-close').addEventListener('click', closeLegalModal);
        modal.querySelector('.legal-modal-close-btn').addEventListener('click', closeLegalModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeLegalModal(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hasAttribute('hidden')) closeLegalModal();
        });
    }());
    </script>
</body>
</html>
