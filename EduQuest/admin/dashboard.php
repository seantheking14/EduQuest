<?php
/**
 * Admin Dashboard
 * Protected by PHP session guard – redirects to login.php if no valid admin session.
 */

// ── Session guard ─────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');

session_start();

if (
    empty($_SESSION['admin_id']) ||
    empty($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: login.php');
    exit;
}

// Optional: session timeout (8 hours idle)
define('SESSION_TIMEOUT', 8 * 60 * 60);
if (!empty($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['admin_login_time'] = time(); // Refresh on activity

$adminName  = htmlspecialchars($_SESSION['admin_name']  ?? 'Admin');
$adminEmail = htmlspecialchars($_SESSION['admin_email'] ?? '');

// ── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'eduquest');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

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

// ── Fetch all registered users (teachers + students) ─────────────────────────
$users      = [];
$dbError    = '';
$totalCount = 0;

try {
    $db = getAdminDB();

    /**
     * Enhanced UNION query: exposes users.id (user_id), profile.id (profile_id),
     * full name, account_status, and suspension details so the admin can act on
     * accounts without additional lookups.
     *
     * Teachers: linked via teachers.user_id → users.id
     * Students: linked via students.user_id → users.id
     */
    $sql = "
        SELECT
            t.id                                             AS profile_id,
            u.id                                             AS user_id,
            CONCAT(t.first_name, ' ', t.last_name)          AS full_name,
            t.email                                          AS email,
            'Teacher'                                        AS role,
            t.created_at                                     AS registered_at,
            COALESCE(u.account_status, 'active')             AS account_status,
            u.suspended_until,
            u.suspension_reason,
            COALESCE(u.force_password_reset, 0)              AS force_password_reset
        FROM teachers t
        JOIN users u ON u.id = t.user_id

        UNION ALL

        SELECT
            s.id                                             AS profile_id,
            u.id                                             AS user_id,
            CONCAT(s.first_name, ' ', s.last_name)          AS full_name,
            u.email                                          AS email,
            'Student'                                        AS role,
            s.created_at                                     AS registered_at,
            COALESCE(u.account_status, 'active')             AS account_status,
            u.suspended_until,
            u.suspension_reason,
            COALESCE(u.force_password_reset, 0)              AS force_password_reset
        FROM students s
        JOIN users u ON u.id = s.user_id

        ORDER BY registered_at DESC
    ";

    $stmt   = $db->query($sql);
    $users  = $stmt->fetchAll();
    $totalCount = count($users);

} catch (PDOException $e) {
    error_log('Admin dashboard DB error: ' . $e->getMessage());
    $dbError = 'Unable to load user data. Please try again later.';
}

// ── Summary counts ────────────────────────────────────────────────────────────
$teacherCount   = 0;
$studentCount   = 0;
$activeCount2   = 0; // active users (reuse $activeCount for sessions below)
$suspendedCount = 0;
$archivedCount  = 0;
foreach ($users as $u) {
    if ($u['role'] === 'Teacher') $teacherCount++;
    else $studentCount++;
    $st = $u['account_status'] ?? 'active';
    if ($st === 'active')    $activeCount2++;
    elseif ($st === 'suspended') $suspendedCount++;
    elseif ($st === 'archived')  $archivedCount++;
}

// ── Fetch currently active sessions (teacher + student) ───────────────────────
$activeSessions  = [];
$activeError     = '';

$activeCount     = 0;

try {
    $db = getAdminDB();

    $stmt = $db->query("
        SELECT
            u.id          AS user_id,
            u.email       AS email,
            u.first_name  AS first_name,
            u.last_name   AS last_name,
            u.role        AS role,
            us.ip_address AS ip_address,
            us.created_at AS login_at,
            us.expires_at AS expires_at,
            us.user_agent AS user_agent
        FROM user_sessions us
        JOIN users u ON u.id = us.user_id
        WHERE us.expires_at > NOW()
          AND u.role IN ('teacher', 'student')
        ORDER BY us.created_at DESC
    ");
    $activeSessions = $stmt->fetchAll();
    $activeCount    = count($activeSessions);

} catch (PDOException $e) {
    error_log('Admin dashboard active sessions error: ' . $e->getMessage());
    $activeError = 'Unable to load active session data.';
}

// ── Whitelist POST actions (add / remove) ─────────────────────────────────────
$wlMessage     = '';
$wlMessageType = '';

if (empty($_SESSION['wl_csrf'])) {
    $_SESSION['wl_csrf'] = bin2hex(random_bytes(32));
}
$wlCsrfToken = $_SESSION['wl_csrf'];

// CSRF token for AJAX account-management API calls
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
$adminCsrfToken = $_SESSION['admin_csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wl_action'])) {
    if (empty($_POST['wl_csrf']) || !hash_equals($_SESSION['wl_csrf'], $_POST['wl_csrf'])) {
        $wlMessage = 'Invalid form submission. Please try again.';
        $wlMessageType = 'error';
    } else {
        $wlAction = $_POST['wl_action'];

        if ($wlAction === 'add') {
            $wlEmail = strtolower(trim($_POST['wl_email'] ?? ''));
            $wlNotes = trim($_POST['wl_notes'] ?? '');

            if (empty($wlEmail) || !filter_var($wlEmail, FILTER_VALIDATE_EMAIL)) {
                $wlMessage = 'A valid email address is required.';
                $wlMessageType = 'error';
            } elseif (mb_strlen($wlEmail) > 150) {
                $wlMessage = 'Email address must not exceed 150 characters.';
                $wlMessageType = 'error';
            } else {
                try {
                    $db  = getAdminDB();
                    $chk = $db->prepare('SELECT id FROM teacher_whitelist WHERE email = :email LIMIT 1');
                    $chk->execute([':email' => $wlEmail]);
                    if ($chk->fetch()) {
                        $wlMessage = 'That email is already on the whitelist.';
                        $wlMessageType = 'error';
                    } else {
                        $ins = $db->prepare(
                            'INSERT INTO teacher_whitelist (email, notes, added_by) VALUES (:email, :notes, :added_by)'
                        );
                        $ins->execute([
                            ':email'    => $wlEmail,
                            ':notes'    => $wlNotes !== '' ? $wlNotes : null,
                            ':added_by' => $_SESSION['admin_id'],
                        ]);
                        $wlMessage = htmlspecialchars($wlEmail) . ' has been added to the teacher whitelist.';
                        $wlMessageType = 'success';
                    }
                } catch (PDOException $e) {
                    error_log('Whitelist add error: ' . $e->getMessage());
                    $wlMessage = 'A database error occurred. Please try again.';
                    $wlMessageType = 'error';
                }
            }
        } elseif ($wlAction === 'remove') {
            $wlId = (int)($_POST['wl_id'] ?? 0);
            if ($wlId < 1) {
                $wlMessage = 'Invalid entry.';
                $wlMessageType = 'error';
            } else {
                try {
                    $db  = getAdminDB();
                    $del = $db->prepare('DELETE FROM teacher_whitelist WHERE id = :id');
                    $del->execute([':id' => $wlId]);
                    $wlMessage = 'Entry removed from the teacher whitelist.';
                    $wlMessageType = 'success';
                } catch (PDOException $e) {
                    error_log('Whitelist remove error: ' . $e->getMessage());
                    $wlMessage = 'A database error occurred. Please try again.';
                    $wlMessageType = 'error';
                }
            }
        }
    }
}

// ── Fetch whitelist entries ───────────────────────────────────────────────────
$whitelist      = [];
$whitelistError = '';
try {
    $db   = getAdminDB();
    $stmt = $db->query(
        'SELECT tw.id, tw.email, tw.notes, tw.added_at, a.full_name AS added_by_name
         FROM teacher_whitelist tw
         LEFT JOIN admins a ON a.id = tw.added_by
         ORDER BY tw.added_at DESC'
    );
    $whitelist = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Whitelist fetch error: ' . $e->getMessage());
    $whitelistError = 'Unable to load whitelist data.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard – EduQuest</title>
    <style>
        /* ── Reset & Base ───────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ────────────────────────────────────── */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #0f172a;
            color: #cbd5e1;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid #1e293b;
        }
        .sidebar-brand .brand-logo {
            font-size: 1.1rem;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sidebar-brand .brand-logo .logo-icon {
            width: 32px;
            height: 32px;
            background: #2563eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .sidebar-brand .brand-sub {
            font-size: 0.7rem;
            color: #475569;
            margin-top: 0.2rem;
            padding-left: 2.5rem;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0.75rem 0;
            flex: 1;
        }
        .sidebar-nav-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            padding: 0.75rem 1.25rem 0.25rem;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 1.25rem;
            color: #94a3b8;
            font-size: 0.875rem;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255,255,255,0.05);
            color: #f8fafc;
            border-left-color: #3b82f6;
        }
        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid #1e293b;
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.75rem;
        }
        .sidebar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .sidebar-user-info { overflow: hidden; }
        .sidebar-user-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user-role {
            font-size: 0.7rem;
            color: #475569;
        }
        .btn-logout {
            width: 100%;
            padding: 0.5rem 0.75rem;
            background: rgba(239,68,68,0.15);
            color: #fca5a5;
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: background 0.15s;
        }
        .btn-logout:hover {
            background: rgba(239,68,68,0.25);
            text-decoration: none;
        }

        /* ── Main content ───────────────────────────────── */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* ── Top bar ────────────────────────────────────── */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.875rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .topbar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
        }
        .topbar-meta {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* ── Content area ───────────────────────────────── */
        .content {
            padding: 1.75rem 2rem 3rem;
        }

        /* ── Welcome banner ─────────────────────────────── */
        .welcome-banner {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            border-radius: 12px;
            padding: 1.5rem 2rem;
            color: #fff;
            margin-bottom: 1.75rem;
        }
        .welcome-banner h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .welcome-banner p {
            color: rgba(255,255,255,0.75);
            font-size: 0.875rem;
        }

        /* ── Stats row ──────────────────────────────────── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.75rem;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .stat-icon.blue  { background: #eff6ff; }
        .stat-icon.green { background: #f0fdf4; }
        .stat-icon.amber { background: #fffbeb; }
        .stat-icon.purple{ background: #faf5ff; }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        /* ── Table card ─────────────────────────────────── */
        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .table-card-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* ── Search ─────────────────────────────────────── */
        .search-wrapper {
            position: relative;
        }
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.875rem;
            pointer-events: none;
        }
        #searchInput {
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            color: #1e293b;
            background: #f8fafc;
            outline: none;
            width: 260px;
            transition: border-color 0.15s;
        }
        #searchInput:focus {
            border-color: #3b82f6;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }

        /* ── Data table ─────────────────────────────────── */
        .table-wrapper { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        thead th {
            padding: 0.75rem 1.25rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        tbody td {
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f8fafc; }

        /* ── Role badge ─────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-teacher {
            background: #eff6ff;
            color: #1d4ed8;
        }
        .badge-student {
            background: #f0fdf4;
            color: #15803d;
        }

        /* ── Empty / error states ───────────────────────── */
        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #94a3b8;
        }
        .empty-state .empty-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }
        .empty-state p { font-size: 0.875rem; }

        .alert-error {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.3);
            color: #b91c1c;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        /* ── Table footer ───────────────────────────────── */
        .table-footer {
            padding: 0.875rem 1.5rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* ── Online dot ─────────────────────────────────── */
        .online-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            margin-right: 0.35rem;
            animation: pulse 2s infinite;
            vertical-align: middle;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }

        /* ── Responsive ─────────────────────────────────── */
        @media (max-width: 900px) {
            .sidebar { display: none; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            #searchInput { width: 100%; }
            .table-card-header { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 540px) {
            .stats-row { grid-template-columns: 1fr; }
            .content { padding: 1rem; }
            .topbar { padding: 0.75rem 1rem; }
        }

        /* ── Account status badges ───────────────────────── */
        .status-badge {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.2rem 0.6rem; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600; white-space: nowrap;
        }
        .status-active   { background: rgba(34,197,94,0.12);  color: #166534; }
        .status-inactive { background: rgba(100,116,139,0.12); color: #475569; }
        .status-suspended{ background: rgba(245,158,11,0.14);  color: #92400e; }
        .status-archived { background: rgba(99,102,241,0.12);  color: #3730a3; }

        /* ── Status filter tabs ──────────────────────────── */
        .filter-tabs {
            display: flex; gap: 0.4rem; flex-wrap: wrap;
            padding: 1rem 1.5rem 0;
        }
        .filter-tab {
            padding: 0.3rem 0.85rem; border-radius: 20px;
            border: 1px solid #e2e8f0; background: #f8fafc;
            font-size: 0.78rem; font-weight: 500; color: #64748b;
            cursor: pointer; transition: all 0.15s;
        }
        .filter-tab:hover { background: #f1f5f9; }
        .filter-tab.active {
            background: #2563eb; color: #fff;
            border-color: #2563eb;
        }
        .filter-tab .tab-count {
            display: inline-block; margin-left: 0.25rem;
            background: rgba(255,255,255,0.25);
            border-radius: 10px; padding: 0 0.35rem;
            font-size: 0.7rem;
        }
        .filter-tab:not(.active) .tab-count {
            background: rgba(0,0,0,0.07);
        }

        /* ── Action menu per row ─────────────────────────── */
        .action-menu { position: relative; display: inline-block; }
        .action-trigger {
            background: none; border: 1px solid #e2e8f0;
            border-radius: 6px; padding: 0.2rem 0.55rem;
            cursor: pointer; font-size: 1rem; color: #64748b;
            line-height: 1.4; transition: background 0.12s;
        }
        .action-trigger:hover { background: #f1f5f9; }
        .action-dropdown {
            display: none; position: absolute; right: 0; top: calc(100% + 4px);
            background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.10); min-width: 190px;
            z-index: 200; overflow: hidden;
        }
        .action-menu.open .action-dropdown { display: block; }
        .action-dropdown button {
            display: block; width: 100%; text-align: left;
            padding: 0.55rem 1rem; background: none; border: none;
            font-size: 0.83rem; color: #1e293b; cursor: pointer;
            transition: background 0.12s;
        }
        .action-dropdown button:hover { background: #f8fafc; }
        .action-dropdown .action-sep {
            height: 1px; background: #f1f5f9; margin: 0.2rem 0;
        }
        .action-dropdown .action-danger { color: #dc2626 !important; }
        .action-dropdown .action-danger:hover { background: rgba(239,68,68,0.06) !important; }

        /* ── Modals ──────────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            z-index: 9999; display: flex; align-items: center; justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.hidden { display: none; }
        .modal-box {
            background: #fff; border-radius: 14px; width: 100%;
            max-width: 460px; box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            animation: modalIn 0.18s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            padding: 1.25rem 1.5rem 0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-title { font-size: 1rem; font-weight: 700; color: #1e293b; }
        .modal-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.2rem; color: #94a3b8; padding: 0.2rem;
            transition: color 0.12s;
        }
        .modal-close:hover { color: #1e293b; }
        .modal-body { padding: 1rem 1.5rem; font-size: 0.875rem; color: #475569; }
        .modal-body p { margin: 0 0 0.75rem; }
        .modal-body p:last-child { margin-bottom: 0; }
        .modal-body .warn-box {
            background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.35);
            border-radius: 8px; padding: 0.75rem 1rem; color: #92400e;
            font-size: 0.82rem; margin-bottom: 0.75rem;
        }
        .modal-body .danger-box {
            background: rgba(239,68,68,0.07); border: 1px solid rgba(239,68,68,0.3);
            border-radius: 8px; padding: 0.75rem 1rem; color: #b91c1c;
            font-size: 0.82rem; margin-bottom: 0.75rem;
        }
        .modal-label {
            display: block; font-size: 0.75rem; font-weight: 600;
            color: #64748b; margin-bottom: 0.3rem; margin-top: 0.75rem;
        }
        .modal-input, .modal-select, .modal-textarea {
            width: 100%; padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 0.875rem; color: #1e293b; background: #f8fafc;
            box-sizing: border-box; outline: none;
            transition: border-color 0.15s;
        }
        .modal-input:focus, .modal-select:focus, .modal-textarea:focus {
            border-color: #2563eb;
        }
        .modal-textarea { resize: vertical; min-height: 80px; }
        .modal-footer {
            padding: 1rem 1.5rem 1.25rem;
            display: flex; gap: 0.6rem; justify-content: flex-end;
        }
        .btn-modal-cancel {
            padding: 0.5rem 1.1rem; background: #f1f5f9;
            border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 0.875rem; font-weight: 500; color: #475569;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-modal-cancel:hover { background: #e2e8f0; }
        .btn-modal-confirm {
            padding: 0.5rem 1.25rem; background: #2563eb;
            border: none; border-radius: 8px;
            font-size: 0.875rem; font-weight: 600; color: #fff;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-modal-confirm:hover { background: #1d4ed8; }
        .btn-modal-confirm:disabled { background: #93c5fd; cursor: not-allowed; }
        .btn-modal-danger {
            padding: 0.5rem 1.25rem; background: #dc2626;
            border: none; border-radius: 8px;
            font-size: 0.875rem; font-weight: 600; color: #fff;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-modal-danger:hover { background: #b91c1c; }
        .btn-modal-danger:disabled { background: #fca5a5; cursor: not-allowed; }

        /* ── Toast notification ──────────────────────────── */
        #admin-toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            padding: 0.75rem 1.25rem; border-radius: 10px;
            font-size: 0.875rem; font-weight: 500; color: #fff;
            z-index: 99999; opacity: 0; pointer-events: none;
            transform: translateY(8px);
            transition: opacity 0.25s, transform 0.25s;
            max-width: 360px;
        }
        #admin-toast.show {
            opacity: 1; pointer-events: auto; transform: translateY(0);
        }
        #admin-toast.toast-success { background: #16a34a; }
        #admin-toast.toast-error   { background: #dc2626; }
    </style>
</head>
<body>

    <!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
    <aside class="sidebar" role="navigation" aria-label="Admin navigation">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <div class="logo-icon">&#9881;</div>
                EduQuest Admin
            </div>
            <div class="brand-sub">Administration Portal</div>
        </div>

        <ul class="sidebar-nav">
            <li class="sidebar-nav-label">Management</li>
            <li>
                <a href="dashboard.php" class="active">
                    &#127968; Dashboard
                </a>
            </li>
            <li>
                <a href="#whitelist" onclick="document.getElementById('whitelistSection').scrollIntoView({behavior:'smooth'});return false;">
                    &#9989; Teacher Whitelist
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?= mb_strtoupper(mb_substr($adminName, 0, 1)) ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= $adminName ?></div>
                    <div class="sidebar-user-role">Administrator</div>
                </div>
            </div>
            <a href="logout.php" class="btn-logout">&#x2192; Sign Out</a>
        </div>
    </aside>

    <!-- ── Main ────────────────────────────────────────────────────────────── -->
    <div class="main">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-meta">
                Signed in as <strong><?= $adminEmail ?></strong>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Welcome banner -->
            <div class="welcome-banner">
                <h2>Welcome, <?= $adminName ?>!</h2>
                <p>Here is an overview of all registered EduQuest users.</p>
            </div>

            <!-- Stats row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue">&#128100;</div>
                    <div>
                        <div class="stat-value"><?= $totalCount ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">&#9989;</div>
                    <div>
                        <div class="stat-value"><?= $activeCount2 ?></div>
                        <div class="stat-label">Active</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber">&#9203;</div>
                    <div>
                        <div class="stat-value"><?= $suspendedCount ?></div>
                        <div class="stat-label">Suspended</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">&#128308;</div>
                    <div>
                        <div class="stat-value"><?= $activeCount ?></div>
                        <div class="stat-label">Active Sessions</div>
                    </div>
                </div>
            </div>

            <!-- ── Active sessions table ──────────────────────────────────── -->
            <div class="table-card" style="margin-bottom: 1.75rem;">
                <div class="table-card-header">
                    <h3>
                        <span class="online-dot"></span>
                        Currently Active Sessions
                        <span style="font-size:0.75rem;font-weight:400;color:#64748b;margin-left:0.4rem;">(non-expired tokens)</span>
                    </h3>
                    <div style="font-size:0.75rem;color:#64748b;">
                        Auto-refreshes on page reload
                    </div>
                </div>

                <?php if ($activeError !== ''): ?>
                    <div style="padding:1rem 1.5rem;">
                        <div class="alert-error"><?= htmlspecialchars($activeError) ?></div>
                    </div>
                <?php endif; ?>

                <div class="table-wrapper">
                    <table aria-label="Active user sessions">
                        <thead>
                            <tr>
                                <th style="width:3rem">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Logged In At</th>
                                <th>Session Expires</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activeSessions)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-icon">&#128311;</div>
                                            <p>No active sessions right now.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activeSessions as $i => $s): ?>
                                    <?php
                                        $tz        = new DateTimeZone('UTC');
                                        $loginAt   = (new DateTime($s['login_at'],   $tz))->format('M j, Y g:i A');
                                        $expiresAt = (new DateTime($s['expires_at'], $tz))->format('M j, Y g:i A');
                                        $roleLabel = ucfirst(htmlspecialchars($s['role']));
                                        $roleBadge = $s['role'] === 'teacher' ? 'badge-teacher' : 'badge-student';
                                        $name      = htmlspecialchars(trim($s['first_name'] . ' ' . $s['last_name']));
                                        $ip        = htmlspecialchars($s['ip_address'] ?? '—');
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= $name ?: '—' ?></td>
                                        <td><?= htmlspecialchars($s['email']) ?></td>
                                        <td>
                                            <span class="badge <?= $roleBadge ?>">
                                                <?= $s['role'] === 'teacher' ? '&#128104;&#8205;&#127979;' : '&#127979;' ?>
                                                <?= $roleLabel ?>
                                            </span>
                                        </td>
                                        <td><?= $loginAt ?> UTC</td>
                                        <td><?= $expiresAt ?> UTC</td>
                                        <td style="font-family:monospace;font-size:0.8rem;"><?= $ip ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($activeSessions)): ?>
                    <div class="table-footer">
                        <?= $activeCount ?> active session<?= $activeCount !== 1 ? 's' : '' ?> right now
                    </div>
                <?php endif; ?>
            </div>

            <!-- Users table -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>Registered EduQuest Users</h3>
                    <div class="search-wrapper">
                        <span class="search-icon">&#128269;</span>
                        <input
                            type="search"
                            id="searchInput"
                            placeholder="Search by name or email&hellip;"
                            aria-label="Filter users"
                            autocomplete="off"
                        >
                    </div>
                </div>

                <!-- Status filter tabs -->
                <?php
                    $nonArchived = $totalCount - $archivedCount;
                ?>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="non-archived">
                        All <span class="tab-count"><?= $nonArchived ?></span>
                    </button>
                    <button class="filter-tab" data-filter="active">
                        Active <span class="tab-count"><?= $activeCount2 ?></span>
                    </button>
                    <button class="filter-tab" data-filter="inactive">
                        Inactive <span class="tab-count"><?= $totalCount - $activeCount2 - $suspendedCount - $archivedCount ?></span>
                    </button>
                    <button class="filter-tab" data-filter="suspended">
                        Suspended <span class="tab-count"><?= $suspendedCount ?></span>
                    </button>
                    <button class="filter-tab" data-filter="archived">
                        Archived <span class="tab-count"><?= $archivedCount ?></span>
                    </button>
                </div>

                <?php if ($dbError !== ''): ?>
                    <div style="padding: 1rem 1.5rem;">
                        <div class="alert-error"><?= htmlspecialchars($dbError) ?></div>
                    </div>
                <?php endif; ?>

                <div class="table-wrapper" style="margin-top:0.75rem;">
                    <table id="usersTable" aria-label="Registered users">
                        <thead>
                            <tr>
                                <th style="width:3rem">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Date Registered</th>
                                <th style="width:4.5rem; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-icon">&#128100;</div>
                                            <p>No users have registered yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $i => $u):
                                    $status     = $u['account_status'] ?? 'active';
                                    $userId     = (int) $u['user_id'];
                                    $profileId  = (int) $u['profile_id'];
                                    $role       = $u['role'];
                                    $email      = htmlspecialchars($u['email']);
                                    $name       = htmlspecialchars(trim($u['full_name']) ?: '—');
                                    $suspUntil  = $u['suspended_until'] ? htmlspecialchars($u['suspended_until']) : '';
                                    $suspReason = htmlspecialchars($u['suspension_reason'] ?? '');
                                    $fpr        = (int)($u['force_password_reset'] ?? 0);

                                    // Status badge
                                    $badgeClass = 'status-' . $status;
                                    $badgeLabel = ucfirst($status);
                                    if ($status === 'suspended' && $suspUntil) {
                                        $suspDisplay = date('M j, Y', strtotime($suspUntil));
                                        $badgeLabel  = "Suspended until {$suspDisplay}";
                                    }

                                    $dt = new DateTime($u['registered_at'], new DateTimeZone('UTC'));
                                    $regDate = htmlspecialchars($dt->format('M j, Y') . ' UTC');
                                ?>
                                <tr
                                    data-user-id="<?= $userId ?>"
                                    data-profile-id="<?= $profileId ?>"
                                    data-role="<?= htmlspecialchars($role) ?>"
                                    data-email="<?= htmlspecialchars(strtolower($u['email'])) ?>"
                                    data-name="<?= htmlspecialchars(strtolower(trim($u['full_name']))) ?>"
                                    data-status="<?= htmlspecialchars($status) ?>"
                                    data-suspended-until="<?= $suspUntil ?>"
                                    data-suspension-reason="<?= $suspReason ?>"
                                >
                                    <td class="row-num"><?= $i + 1 ?></td>
                                    <td style="font-weight:500;"><?= $name ?></td>
                                    <td style="font-size:0.85rem;color:#475569;"><?= $email ?></td>
                                    <td>
                                        <?php if ($role === 'Teacher'): ?>
                                            <span class="badge badge-teacher">&#128104;&#8205;&#127979; Teacher</span>
                                        <?php else: ?>
                                            <span class="badge badge-student">&#127979; Student</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $badgeClass ?>" id="status-badge-<?= $userId ?>">
                                            <?= htmlspecialchars($badgeLabel) ?>
                                        </span>
                                        <?php if ($fpr): ?>
                                            <span class="status-badge" style="background:rgba(99,102,241,0.10);color:#4338ca;margin-left:0.25rem;font-size:0.67rem;">
                                                &#128274; Reset Required
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:0.82rem;color:#64748b;"><?= $regDate ?></td>
                                    <td style="text-align:center;">
                                        <div class="action-menu" id="amenu-<?= $userId ?>">
                                            <button
                                                class="action-trigger"
                                                onclick="toggleActionMenu(<?= $userId ?>)"
                                                aria-label="Actions for <?= $name ?>"
                                                title="Actions">&#8942;</button>
                                            <div class="action-dropdown">
                                                <?php if ($status === 'active' || $status === 'inactive'): ?>
                                                    <?php if ($status === 'active'): ?>
                                                        <button onclick="openSimpleModal('deactivate',<?= $userId ?>,'<?= addslashes($name) ?>','<?= addslashes($email) ?>')">
                                                            &#128683; Deactivate
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($status === 'inactive'): ?>
                                                        <button onclick="openSimpleModal('reactivate',<?= $userId ?>,'<?= addslashes($name) ?>','<?= addslashes($email) ?>')">
                                                            &#9989; Reactivate
                                                        </button>
                                                    <?php endif; ?>
                                                    <button onclick="openSuspendModal(<?= $userId ?>,'<?= addslashes($name) ?>','<?= addslashes($email) ?>')">
                                                        &#9203; Suspend&hellip;
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($status === 'suspended'): ?>
                                                    <button onclick="openSimpleModal('reactivate',<?= $userId ?>,'<?= addslashes($name) ?>','<?= addslashes($email) ?>')">
                                                        &#9989; Lift Suspension
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($status !== 'archived'): ?>
                                                    <button onclick="openSimpleModal('archive',<?= $userId ?>,'<?= addslashes($name) ?>','<?= addslashes($email) ?>')">
                                                        &#128451; Archive
                                                    </button>
                                                    <button onclick="openForceResetModal(<?= $userId ?>,'<?= addslashes($name) ?>','<?= addslashes($email) ?>')">
                                                        &#128274; Force Password Reset
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($status === 'archived'): ?>
                                                    <button onclick="openSimpleModal('unarchive',<?= $userId ?>,'<?= addslashes($name) ?>','<?= addslashes($email) ?>')">
                                                        &#128257; Unarchive
                                                    </button>
                                                <?php endif; ?>
                                                <div class="action-sep"></div>
                                                <button class="action-danger"
                                                    onclick="openDeleteModal(<?= $userId ?>,'<?= addslashes($name) ?>','<?= addslashes($email) ?>','<?= addslashes($role) ?>')">
                                                    &#128465; Delete Account&hellip;
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer" id="tableFooter">
                    Showing <span id="visibleCount"><?= $totalCount - $archivedCount ?></span>
                    of <?= $totalCount ?> users
                    <span id="archivedNote" style="margin-left:0.5rem;color:#94a3b8;">(<?= $archivedCount ?> archived, hidden by default)</span>
                </div>
            </div>

            <!-- ── Teacher Whitelist Management ───────────────────────────── -->
            <div class="table-card" id="whitelistSection" style="margin-top: 1.75rem;">
                <div class="table-card-header">
                    <h3>&#9989; Teacher Whitelist</h3>
                    <span style="font-size:0.8rem;color:#64748b;">Only whitelisted emails may register or log in as a teacher.</span>
                </div>

                <?php if ($wlMessage !== ''): ?>
                    <div style="padding: 0.75rem 1.5rem 0;">
                        <div class="<?= $wlMessageType === 'success' ? 'alert-success' : 'alert-error' ?>"
                             style="border-radius:8px;padding:0.7rem 1rem;font-size:0.875rem;
                                    <?= $wlMessageType === 'success'
                                        ? 'background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.35);color:#166534;'
                                        : 'background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);color:#b91c1c;' ?>">
                            <?= $wlMessage ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add email form -->
                <form method="POST" action="dashboard.php#whitelistSection"
                      style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; display:flex; gap:0.75rem; flex-wrap:wrap; align-items:flex-end;">
                    <input type="hidden" name="wl_action" value="add">
                    <input type="hidden" name="wl_csrf"   value="<?= htmlspecialchars($wlCsrfToken) ?>">

                    <div style="flex:1;min-width:220px;">
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#64748b;margin-bottom:0.3rem;">
                            Teacher Email <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="email" name="wl_email" required maxlength="150"
                               placeholder="teacher@school.edu"
                               style="width:100%;padding:0.55rem 0.8rem;border:1px solid #e2e8f0;border-radius:7px;
                                      font-size:0.875rem;color:#1e293b;background:#f8fafc;outline:none;">
                    </div>
                    <div style="flex:1.5;min-width:220px;">
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#64748b;margin-bottom:0.3rem;">
                            Notes <span style="color:#94a3b8;">(optional)</span>
                        </label>
                        <input type="text" name="wl_notes" maxlength="255"
                               placeholder="e.g. Math dept, Grade 5"
                               style="width:100%;padding:0.55rem 0.8rem;border:1px solid #e2e8f0;border-radius:7px;
                                      font-size:0.875rem;color:#1e293b;background:#f8fafc;outline:none;">
                    </div>
                    <div>
                        <button type="submit"
                                style="padding:0.55rem 1.25rem;background:#2563eb;color:#fff;border:none;
                                       border-radius:7px;font-size:0.875rem;font-weight:600;cursor:pointer;
                                       white-space:nowrap;transition:background 0.15s;"
                                onmouseover="this.style.background='#1d4ed8'"
                                onmouseout="this.style.background='#2563eb'">
                            &#43; Add to Whitelist
                        </button>
                    </div>
                </form>

                <?php if ($whitelistError !== ''): ?>
                    <div style="padding:1rem 1.5rem;">
                        <div class="alert-error"><?= htmlspecialchars($whitelistError) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Whitelist table -->
                <div class="table-wrapper">
                    <table aria-label="Teacher whitelist">
                        <thead>
                            <tr>
                                <th style="width:3rem">#</th>
                                <th>Email Address</th>
                                <th>Notes</th>
                                <th>Added By</th>
                                <th>Date Added</th>
                                <th style="width:6rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($whitelist)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-icon">&#9989;</div>
                                            <p>No emails on the whitelist yet. Add one above.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($whitelist as $wi => $wrow): ?>
                                    <tr>
                                        <td><?= $wi + 1 ?></td>
                                        <td style="font-weight:500;"><?= htmlspecialchars($wrow['email']) ?></td>
                                        <td style="color:#64748b;font-size:0.8rem;">
                                            <?= $wrow['notes'] !== null ? htmlspecialchars($wrow['notes']) : '<span style="color:#cbd5e1;">—</span>' ?>
                                        </td>
                                        <td style="font-size:0.8rem;color:#64748b;">
                                            <?= $wrow['added_by_name'] ? htmlspecialchars($wrow['added_by_name']) : '<span style="color:#cbd5e1;">—</span>' ?>
                                        </td>
                                        <td style="font-size:0.8rem;">
                                            <?php
                                                $wlDt = new DateTime($wrow['added_at'], new DateTimeZone('UTC'));
                                                echo htmlspecialchars($wlDt->format('M j, Y g:i A') . ' UTC');
                                            ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="dashboard.php#whitelistSection"
                                                  onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($wrow['email'])) ?> from the whitelist? This will block their teacher access immediately.');">
                                                <input type="hidden" name="wl_action" value="remove">
                                                <input type="hidden" name="wl_csrf"   value="<?= htmlspecialchars($wlCsrfToken) ?>">
                                                <input type="hidden" name="wl_id"     value="<?= (int)$wrow['id'] ?>">
                                                <button type="submit"
                                                        style="padding:0.3rem 0.7rem;background:rgba(239,68,68,0.1);
                                                               color:#dc2626;border:1px solid rgba(239,68,68,0.3);
                                                               border-radius:6px;font-size:0.75rem;cursor:pointer;
                                                               transition:background 0.15s;"
                                                        onmouseover="this.style.background='rgba(239,68,68,0.2)'"
                                                        onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                                                    Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($whitelist)): ?>
                    <div class="table-footer">
                        <?= count($whitelist) ?> authorised teacher email<?= count($whitelist) !== 1 ? 's' : '' ?>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /.content -->
    </div><!-- /.main -->

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        'use strict';

        // ── CSRF token (injected from PHP session) ─────────────────────────────
        var CSRF = <?= json_encode($adminCsrfToken) ?>;

        // ── Toast ──────────────────────────────────────────────────────────────
        function showToast(msg, type) {
            var t = document.getElementById('admin-toast');
            t.textContent = msg;
            t.className   = 'show toast-' + (type || 'success');
            clearTimeout(t._tid);
            t._tid = setTimeout(function () { t.className = ''; }, 3500);
        }

        // ── Action menu toggle ─────────────────────────────────────────────────
        window.toggleActionMenu = function (userId) {
            var menu = document.getElementById('amenu-' + userId);
            if (!menu) return;
            var isOpen = menu.classList.contains('open');
            // Close all open menus first
            document.querySelectorAll('.action-menu.open').forEach(function (m) {
                m.classList.remove('open');
            });
            if (!isOpen) menu.classList.add('open');
        };

        // Close menus on outside click
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.action-menu')) {
                document.querySelectorAll('.action-menu.open').forEach(function (m) {
                    m.classList.remove('open');
                });
            }
        });

        // ── Modal helpers ──────────────────────────────────────────────────────
        function showModal(id) {
            document.querySelectorAll('.modal-box').forEach(function (m) {
                m.style.display = 'none';
            });
            var overlay = document.getElementById('modal-overlay');
            var box     = document.getElementById(id);
            if (!overlay || !box) return;
            overlay.classList.remove('hidden');
            box.style.display = '';
        }

        function closeAllModals() {
            document.getElementById('modal-overlay').classList.add('hidden');
            document.querySelectorAll('.modal-box').forEach(function (m) {
                m.style.display = 'none';
            });
        }

        window.closeModal = closeAllModals;

        // ── Live search + status filter ────────────────────────────────────────
        var searchInput  = document.getElementById('searchInput');
        var tableBody    = document.getElementById('tableBody');
        var visibleCount = document.getElementById('visibleCount');
        var archivedNote = document.getElementById('archivedNote');
        var activeFilter = 'non-archived';

        function applyFilters() {
            var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var rows  = tableBody ? tableBody.querySelectorAll('tr[data-user-id]') : [];
            var shown = 0;

            rows.forEach(function (row) {
                var status = row.getAttribute('data-status') || 'active';
                var email  = row.getAttribute('data-email') || '';
                var name   = row.getAttribute('data-name')  || '';

                var statusMatch = false;
                if (activeFilter === 'non-archived') {
                    statusMatch = (status !== 'archived');
                } else if (activeFilter === 'all') {
                    statusMatch = true;
                } else {
                    statusMatch = (status === activeFilter);
                }

                var textMatch = !query ||
                    email.indexOf(query) !== -1 ||
                    name.indexOf(query)  !== -1;

                var visible = statusMatch && textMatch;
                row.style.display = visible ? '' : 'none';
                if (visible) shown++;
            });

            if (visibleCount) visibleCount.textContent = shown;
            if (archivedNote) {
                archivedNote.style.display = (activeFilter === 'non-archived') ? '' : 'none';
            }

            // Re-number visible rows
            var num = 1;
            rows.forEach(function (row) {
                if (row.style.display === 'none') return;
                var cell = row.querySelector('.row-num');
                if (cell) cell.textContent = num++;
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }

        // Filter tab buttons
        document.querySelectorAll('.filter-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.filter-tab').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');
                activeFilter = btn.getAttribute('data-filter');
                applyFilters();
            });
        });

        // Apply default filter on load (hide archived)
        applyFilters();

        // ── Generic API call ───────────────────────────────────────────────────
        function apiPost(endpoint, body) {
            body.csrf_token = CSRF;
            return fetch(endpoint, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(body),
            }).then(function (r) { return r.json(); });
        }

        // ── Update row status badge in-place ───────────────────────────────────
        function updateRowStatus(userId, newStatus, suspendedUntil) {
            var row = tableBody.querySelector('tr[data-user-id="' + userId + '"]');
            if (!row) return;

            row.setAttribute('data-status', newStatus);

            var badge    = document.getElementById('status-badge-' + userId);
            var classMap = {
                active:    'status-active',
                inactive:  'status-inactive',
                suspended: 'status-suspended',
                archived:  'status-archived',
            };

            if (badge) {
                badge.className = 'status-badge ' + (classMap[newStatus] || '');
                var label = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                if (newStatus === 'suspended' && suspendedUntil) {
                    var d = new Date(suspendedUntil);
                    label = 'Suspended until ' + d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
                }
                badge.textContent = label;
            }

            // Rebuild action dropdown to reflect new status
            var menu = document.getElementById('amenu-' + userId);
            if (menu) {
                var dropdown = menu.querySelector('.action-dropdown');
                if (dropdown) {
                    var name  = row.getAttribute('data-name');
                    var email = row.getAttribute('data-email');
                    var role  = row.getAttribute('data-role');
                    dropdown.innerHTML = buildActionDropdown(userId, name, email, role, newStatus);
                }
            }

            // Re-apply current filter (may need to hide/show the row)
            applyFilters();
        }

        function buildActionDropdown(userId, name, email, role, status) {
            var esc = function (s) { return (s || '').replace(/'/g, "\\'"); };
            var html = '';

            if (status === 'active' || status === 'inactive') {
                if (status === 'active') {
                    html += '<button onclick="openSimpleModal(\'deactivate\',' + userId + ',\'' + esc(name) + '\',\'' + esc(email) + '\')">&#128683; Deactivate</button>';
                }
                if (status === 'inactive') {
                    html += '<button onclick="openSimpleModal(\'reactivate\',' + userId + ',\'' + esc(name) + '\',\'' + esc(email) + '\')">&#9989; Reactivate</button>';
                }
                html += '<button onclick="openSuspendModal(' + userId + ',\'' + esc(name) + '\',\'' + esc(email) + '\')">&#9203; Suspend&hellip;</button>';
            }

            if (status === 'suspended') {
                html += '<button onclick="openSimpleModal(\'reactivate\',' + userId + ',\'' + esc(name) + '\',\'' + esc(email) + '\')">&#9989; Lift Suspension</button>';
            }

            if (status !== 'archived') {
                html += '<button onclick="openSimpleModal(\'archive\',' + userId + ',\'' + esc(name) + '\',\'' + esc(email) + '\')">&#128451; Archive</button>';
                html += '<button onclick="openForceResetModal(' + userId + ',\'' + esc(name) + '\',\'' + esc(email) + '\')">&#128274; Force Password Reset</button>';
            }

            if (status === 'archived') {
                html += '<button onclick="openSimpleModal(\'unarchive\',' + userId + ',\'' + esc(name) + '\',\'' + esc(email) + '\')">&#128257; Unarchive</button>';
            }

            html += '<div class="action-sep"></div>';
            html += '<button class="action-danger" onclick="openDeleteModal(' + userId + ',\'' + esc(name) + '\',\'' + esc(email) + '\',\'' + esc(role) + '\')">&#128465; Delete Account&hellip;</button>';
            return html;
        }

        // ── Simple confirm modal (deactivate / reactivate / archive / unarchive) ─
        var _simpleAction, _simpleUserId, _simpleName, _simpleEmail;

        window.openSimpleModal = function (action, userId, name, email) {
            _simpleAction = action;
            _simpleUserId = userId;
            _simpleName   = name;
            _simpleEmail  = email;

            document.querySelectorAll('.action-menu.open').forEach(function (m) { m.classList.remove('open'); });

            var cfg = {
                deactivate: {
                    title: 'Deactivate Account',
                    body:  'Deactivating <strong>' + name + '</strong> will revoke login access immediately. All data is preserved and the account can be reactivated at any time.',
                    btn:   'Deactivate',
                    cls:   'btn-modal-confirm',
                },
                reactivate: {
                    title: 'Reactivate Account',
                    body:  'Reactivating <strong>' + name + '</strong> will restore login access immediately.',
                    btn:   'Reactivate',
                    cls:   'btn-modal-confirm',
                },
                archive: {
                    title: 'Archive Account',
                    body:  'Archiving <strong>' + name + '</strong> will revoke login access and hide the account from standard views. The account can be unarchived later.',
                    btn:   'Archive',
                    cls:   'btn-modal-confirm',
                },
                unarchive: {
                    title: 'Unarchive Account',
                    body:  'Unarchiving <strong>' + name + '</strong> will restore the account to active status and re-enable login access.',
                    btn:   'Unarchive',
                    cls:   'btn-modal-confirm',
                },
            };

            var c = cfg[action];
            if (!c) return;

            document.getElementById('simple-modal-title').textContent = c.title;
            document.getElementById('simple-modal-body').innerHTML    = '<p>' + c.body + '</p>';
            var confirmBtn = document.getElementById('simple-modal-confirm');
            confirmBtn.textContent = c.btn;
            confirmBtn.className   = c.cls;

            showModal('modal-simple');
        };

        document.getElementById('simple-modal-confirm').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            apiPost('api/account-action.php', {
                action:  _simpleAction,
                user_id: _simpleUserId,
            }).then(function (res) {
                closeAllModals();
                if (res.success) {
                    updateRowStatus(_simpleUserId, res.new_status, res.suspended_until);
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message || 'Action failed.', 'error');
                }
            }).catch(function () {
                showToast('Request failed. Check your connection.', 'error');
            }).finally(function () { btn.disabled = false; });
        });

        // ── Suspend modal ──────────────────────────────────────────────────────
        var _suspendUserId;

        window.openSuspendModal = function (userId, name, email) {
            _suspendUserId = userId;
            document.querySelectorAll('.action-menu.open').forEach(function (m) { m.classList.remove('open'); });
            document.getElementById('suspend-modal-name').textContent  = name;
            document.getElementById('suspend-modal-email').textContent = email;
            document.getElementById('suspend-duration').value          = '7';
            document.getElementById('suspend-custom-date').value       = '';
            document.getElementById('suspend-custom-wrap').style.display = 'none';
            document.getElementById('suspend-reason').value            = '';
            showModal('modal-suspend');
        };

        document.getElementById('suspend-duration').addEventListener('change', function () {
            document.getElementById('suspend-custom-wrap').style.display =
                this.value === 'custom' ? '' : 'none';
        });

        document.getElementById('suspend-modal-confirm').addEventListener('click', function () {
            var btn      = this;
            var dur      = document.getElementById('suspend-duration').value;
            var custom   = document.getElementById('suspend-custom-date').value;
            var reason   = document.getElementById('suspend-reason').value.trim();

            if (!reason) {
                document.getElementById('suspend-reason').focus();
                showToast('Please provide a suspension reason.', 'error');
                return;
            }

            var body = {
                action:  'suspend',
                user_id: _suspendUserId,
                reason:  reason,
            };

            if (dur === 'custom') {
                if (!custom) {
                    showToast('Please select a custom end date.', 'error');
                    return;
                }
                body.custom_date = custom;
            } else {
                body.duration_days = parseInt(dur, 10);
            }

            btn.disabled = true;
            apiPost('api/account-action.php', body).then(function (res) {
                closeAllModals();
                if (res.success) {
                    updateRowStatus(_suspendUserId, 'suspended', res.suspended_until);
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message || 'Suspension failed.', 'error');
                }
            }).catch(function () {
                showToast('Request failed. Check your connection.', 'error');
            }).finally(function () { btn.disabled = false; });
        });

        // ── Force password reset modal ─────────────────────────────────────────
        var _fprUserId;

        window.openForceResetModal = function (userId, name, email) {
            _fprUserId = userId;
            document.querySelectorAll('.action-menu.open').forEach(function (m) { m.classList.remove('open'); });
            document.getElementById('fpr-modal-name').textContent  = name;
            document.getElementById('fpr-modal-email').textContent = email;
            showModal('modal-force-reset');
        };

        document.getElementById('fpr-modal-confirm').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            apiPost('api/account-action.php', {
                action:  'force_password_reset',
                user_id: _fprUserId,
            }).then(function (res) {
                closeAllModals();
                if (res.success) {
                    showToast(res.message, 'success');
                    // Mark the row with the reset-required badge
                    var row = tableBody.querySelector('tr[data-user-id="' + _fprUserId + '"]');
                    if (row) {
                        var td = row.cells[4];
                        if (td && !td.querySelector('.fpr-badge')) {
                            var b = document.createElement('span');
                            b.className = 'status-badge fpr-badge';
                            b.style.cssText = 'background:rgba(99,102,241,0.10);color:#4338ca;margin-left:.25rem;font-size:.67rem;';
                            b.textContent = '\uD83D\uDD12 Reset Required';
                            td.appendChild(b);
                        }
                    }
                } else {
                    showToast(res.message || 'Action failed.', 'error');
                }
            }).catch(function () {
                showToast('Request failed. Check your connection.', 'error');
            }).finally(function () { btn.disabled = false; });
        });

        // ── Delete modal ───────────────────────────────────────────────────────
        var _delUserId, _delName, _delEmail, _delRole;

        window.openDeleteModal = function (userId, name, email, role) {
            _delUserId = userId;
            _delName   = name;
            _delEmail  = email;
            _delRole   = role;

            document.querySelectorAll('.action-menu.open').forEach(function (m) { m.classList.remove('open'); });
            document.getElementById('del1-name').textContent  = name;
            document.getElementById('del1-email').textContent = email;
            document.getElementById('del1-warning').style.display = 'none';
            document.getElementById('del1-loading').style.display = '';
            showModal('modal-delete-1');

            // Fetch linked record counts
            apiPost('api/account-delete.php', {
                user_id: userId,
                check:   true,
            }).then(function (res) {
                document.getElementById('del1-loading').style.display = 'none';
                if (res.warning) {
                    var wb = document.getElementById('del1-warning');
                    wb.textContent = res.warning;
                    wb.style.display = '';
                }
            }).catch(function () {
                document.getElementById('del1-loading').style.display = 'none';
            });
        };

        document.getElementById('del1-continue').addEventListener('click', function () {
            closeAllModals();
            document.getElementById('del2-name').textContent  = _delName;
            document.getElementById('del2-email').textContent = _delEmail;
            document.getElementById('del2-confirm-input').value = '';
            document.getElementById('del2-submit').disabled = true;
            showModal('modal-delete-2');
        });

        document.getElementById('del2-confirm-input').addEventListener('input', function () {
            document.getElementById('del2-submit').disabled = (this.value !== 'DELETE');
        });

        document.getElementById('del2-submit').addEventListener('click', function () {
            var btn = this;
            if (document.getElementById('del2-confirm-input').value !== 'DELETE') return;
            btn.disabled = true;

            apiPost('api/account-delete.php', {
                user_id: _delUserId,
                confirm: 'DELETE',
            }).then(function (res) {
                closeAllModals();
                if (res.success) {
                    // Remove row from table
                    var row = tableBody.querySelector('tr[data-user-id="' + _delUserId + '"]');
                    if (row) row.remove();
                    applyFilters();
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message || 'Deletion failed.', 'error');
                }
            }).catch(function () {
                showToast('Request failed. Check your connection.', 'error');
            }).finally(function () { btn.disabled = false; });
        });

    });
    </script>
    <!-- ── Account management modals ──────────────────────────────────────── -->
    <div id="modal-overlay" class="modal-overlay hidden" onclick="if(event.target===this)closeModal()">

        <!-- Simple confirm modal (deactivate / reactivate / archive / unarchive) -->
        <div id="modal-simple" class="modal-box" style="display:none;">
            <div class="modal-header">
                <span class="modal-title" id="simple-modal-title">Confirm Action</span>
                <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body" id="simple-modal-body"></div>
            <div class="modal-footer">
                <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-modal-confirm" id="simple-modal-confirm">Confirm</button>
            </div>
        </div>

        <!-- Suspend modal -->
        <div id="modal-suspend" class="modal-box" style="display:none;">
            <div class="modal-header">
                <span class="modal-title">Suspend Account</span>
                <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p>
                    Suspending <strong id="suspend-modal-name"></strong>
                    (<span id="suspend-modal-email" style="font-size:0.8rem;color:#64748b;"></span>)
                    will revoke login access until the suspension expires or is manually lifted.
                </p>
                <label class="modal-label" for="suspend-duration">Suspension Duration</label>
                <select id="suspend-duration" class="modal-select">
                    <option value="1">1 day</option>
                    <option value="3">3 days</option>
                    <option value="7" selected>7 days</option>
                    <option value="14">14 days</option>
                    <option value="30">30 days</option>
                    <option value="custom">Custom end date&hellip;</option>
                </select>
                <div id="suspend-custom-wrap" style="display:none;">
                    <label class="modal-label" for="suspend-custom-date">End Date</label>
                    <input type="date" id="suspend-custom-date" class="modal-input"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <label class="modal-label" for="suspend-reason">
                    Reason <span style="color:#ef4444;">*</span>
                </label>
                <textarea id="suspend-reason" class="modal-textarea"
                          placeholder="Briefly describe the reason for suspension&hellip;"
                          maxlength="500"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-modal-confirm" id="suspend-modal-confirm">Suspend</button>
            </div>
        </div>

        <!-- Force password reset modal -->
        <div id="modal-force-reset" class="modal-box" style="display:none;">
            <div class="modal-header">
                <span class="modal-title">Force Password Reset</span>
                <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p>
                    Force a password reset for <strong id="fpr-modal-name"></strong>
                    (<span id="fpr-modal-email" style="font-size:0.8rem;color:#64748b;"></span>).
                </p>
                <p>On their next login, they will be required to set a new password before continuing.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-modal-confirm" id="fpr-modal-confirm">&#128274; Force Reset</button>
            </div>
        </div>

        <!-- Delete modal — Step 1: warning + IEP count -->
        <div id="modal-delete-1" class="modal-box" style="display:none;">
            <div class="modal-header">
                <span class="modal-title" style="color:#dc2626;">&#9888; Delete Account</span>
                <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p>
                    You are about to permanently delete the account for
                    <strong id="del1-name"></strong>
                    (<span id="del1-email" style="font-size:0.8rem;color:#64748b;"></span>).
                </p>
                <p id="del1-loading" style="color:#94a3b8;font-size:0.82rem;">Checking linked records&hellip;</p>
                <div id="del1-warning" class="danger-box" style="display:none;"></div>
                <p style="margin-top:0.5rem;">
                    <strong>This action is permanent and cannot be undone.</strong>
                    All associated sessions, progress, and profile data will be deleted.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-modal-danger" id="del1-continue">I Understand, Continue &rarr;</button>
            </div>
        </div>

        <!-- Delete modal — Step 2: type DELETE to confirm -->
        <div id="modal-delete-2" class="modal-box" style="display:none;">
            <div class="modal-header">
                <span class="modal-title" style="color:#dc2626;">&#128465; Confirm Permanent Deletion</span>
                <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="danger-box">
                    Deleting <strong id="del2-name"></strong>
                    (<span id="del2-email" style="font-size:0.8rem;"></span>)
                    will permanently erase all records. There is no recovery.
                </div>
                <label class="modal-label" for="del2-confirm-input">
                    Type <strong>DELETE</strong> to confirm
                </label>
                <input type="text" id="del2-confirm-input" class="modal-input"
                       placeholder="DELETE" autocomplete="off" spellcheck="false">
            </div>
            <div class="modal-footer">
                <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-modal-danger" id="del2-submit" disabled>
                    &#128465; Permanently Delete
                </button>
            </div>
        </div>

    </div><!-- /#modal-overlay -->

    <!-- Toast -->
    <div id="admin-toast"></div>

</body>
</html>
