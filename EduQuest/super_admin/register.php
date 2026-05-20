<?php
/**
 * Super Admin Registration
 * Accessible only via direct URL: /super_admin/register.php
 * NOT linked from any teacher, student, or admin interface.
 */

// ── Configuration ─────────────────────────────────────────────────────────────
define('SUPER_ADMIN_REGISTRATION_KEY', 'EduQuest@SuperKey2025!'); // Change before production
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

// Redirect if already logged in as super admin
if (!empty($_SESSION['super_admin_id']) && $_SESSION['role'] === 'super_admin') {
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

// ── Process POST ───────────────────────────────────────────────────────────────
$errors   = [];
$success  = false;
$formData = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['sa_reg_csrf'] ?? '', $_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
        $fullName   = trim($_POST['full_name']          ?? '');
        $email      = strtolower(trim($_POST['email']   ?? ''));
        $password   = $_POST['password']                ?? '';
        $confirmPwd = $_POST['confirm_password']        ?? '';
        $regKey     = $_POST['registration_key']        ?? '';

        $formData = ['full_name' => htmlspecialchars($fullName), 'email' => htmlspecialchars($email)];

        // Validation
        if (empty($fullName) || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 200) {
            $errors[] = 'Full name must be 2–200 characters.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors[] = 'A valid email address is required (max 255 chars).';
        }
        if (mb_strlen($password) < 10) {
            $errors[] = 'Password must be at least 10 characters.';
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must include at least one uppercase letter, one number, and one special character.';
        }
        if ($password !== $confirmPwd) {
            $errors[] = 'Passwords do not match.';
        }
        if (!hash_equals(SUPER_ADMIN_REGISTRATION_KEY, $regKey)) {
            $errors[] = 'Invalid registration key.';
        }

        if (empty($errors)) {
            try {
                $db = getSADB();

                // Check for duplicate email
                $chk = $db->prepare('SELECT id FROM super_admins WHERE email = :email LIMIT 1');
                $chk->execute([':email' => $email]);
                if ($chk->fetch()) {
                    $errors[] = 'An account with that email already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $ins  = $db->prepare(
                        'INSERT INTO super_admins (full_name, email, password_hash) VALUES (:full_name, :email, :hash)'
                    );
                    $ins->execute([
                        ':full_name' => $fullName,
                        ':email'     => $email,
                        ':hash'      => $hash,
                    ]);
                    $success = true;
                }
            } catch (PDOException $e) {
                error_log('Super admin register DB error: ' . $e->getMessage());
                $errors[] = 'A system error occurred. Please try again.';
            }
        }
    }
}

// ── CSRF token ─────────────────────────────────────────────────────────────────
if (empty($_SESSION['sa_reg_csrf'])) {
    $_SESSION['sa_reg_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['sa_reg_csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Super Admin Registration – EduQuest</title>
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
            max-width: 460px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.6);
        }
        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }
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
        .form-group      { margin-bottom: 1.1rem; }
        label { display: block; font-size: 0.8rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.4rem; }
        input[type=text], input[type=email], input[type=password] {
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
        .alert-error   { background: rgba(220,38,38,0.12); border: 1px solid rgba(220,38,38,0.3); color: #fca5a5; }
        .alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3);  color: #86efac; }
        .alert ul { padding-left: 1.25rem; margin-top: 0.4rem; }
        .login-link { text-align: center; margin-top: 1.25rem; font-size: 0.8rem; color: #64748b; }
        .login-link a { color: #818cf8; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

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
        .legal-tab:hover  { background: rgba(99,102,241,.15); color: #fff; }
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
            background: #6366f1; color: #fff; border: none;
            padding: .5rem 1.75rem; border-radius: 6px;
            font-size: .875rem; font-weight: 600; cursor: pointer; transition: background .15s;
        }
        .legal-modal-close-btn:hover { background: #4f46e5; }
        @media (max-width: 480px) {
            .legal-modal-box { max-height: 92vh; border-radius: 10px; }
            .legal-modal-body { padding: 1rem; }
            .legal-tab { padding: .4rem .65rem; font-size: .8rem; }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="badge-super">Super Admin</div>
        <h1>Create Account</h1>
        <p>EduQuest Super Administrator Registration</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Account created successfully. <a href="login.php" style="color:#86efac;font-weight:600;">Sign in &rarr;</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="" novalidate autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />

        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name"
                   value="<?= $formData['full_name'] ?>"
                   maxlength="200" required autocomplete="off" />
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   value="<?= $formData['email'] ?>"
                   maxlength="255" required autocomplete="off" />
        </div>
        <div class="form-group">
            <label for="password">Password <span style="font-weight:400;color:#475569">(min 10 chars, upper + number + symbol)</span></label>
            <input type="password" id="password" name="password"
                   maxlength="200" required autocomplete="new-password" />
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   maxlength="200" required autocomplete="new-password" />
        </div>
        <div class="form-group">
            <label for="registration_key">Super Admin Registration Key</label>
            <input type="password" id="registration_key" name="registration_key"
                   maxlength="100" required autocomplete="off" />
        </div>

        <div class="form-group" style="display:flex;align-items:flex-start;gap:.6rem;">
            <input type="checkbox" id="agreeTerms" name="agreeTerms" required
                   style="margin-top:.2rem;flex-shrink:0;accent-color:#6366f1;width:1rem;height:1rem;">
            <label for="agreeTerms" style="font-size:.875rem;line-height:1.4;cursor:pointer;">
                I agree to the <a href="#" onclick="openLegalModal('tos');return false;" style="color:#818cf8;">Terms of Service</a> and <a href="#" onclick="openLegalModal('pp');return false;" style="color:#818cf8;">Privacy Policy</a>
            </label>
        </div>

        <button type="submit" class="btn">Create Super Admin Account</button>
    </form>
    <?php endif; ?>

    <div class="login-link">
        Already have an account? <a href="login.php">Sign in</a>
    </div>
</div>

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
