<?php
/**
 * Super Admin Dashboard
 * Protected: requires valid super_admin session.
 * Tabs: Behavioral Logs | Registered Users
 */

// ── Session guard ──────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
session_start();

define('SA_SESSION_TIMEOUT', 8 * 60 * 60); // 8 hours idle

if (
    empty($_SESSION['super_admin_id']) ||
    ($_SESSION['role'] ?? '') !== 'super_admin'
) {
    header('Location: login.php');
    exit;
}
if (
    !empty($_SESSION['sa_login_time']) &&
    (time() - $_SESSION['sa_login_time']) > SA_SESSION_TIMEOUT
) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['sa_login_time'] = time();

$saName  = htmlspecialchars($_SESSION['super_admin_name']  ?? 'Super Admin');
$saEmail = htmlspecialchars($_SESSION['super_admin_email'] ?? '');
$saId    = (int) $_SESSION['super_admin_id'];

// ── Database ───────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'eduquest');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

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

// ── TAB 2 DATA: Registered Users ─────────────────────────────────────────────
$registeredUsers = [];
$tab4Error       = '';

try {
    $db = getSADB();

    // Teachers
    $teacherRows = $db->query("
        SELECT
            t.id                                    AS user_id,
            CONCAT(t.first_name,' ',t.last_name)     AS full_name,
            t.email                                  AS email,
            'Teacher'                                AS role,
            t.created_at                             AS registered_at,
            (SELECT COUNT(*) FROM students s2 WHERE s2.teacher_id = t.id) AS student_count,
            NULL                                     AS exp_total
        FROM teachers t
        ORDER BY t.last_name, t.first_name
    ")->fetchAll();

    // Students
    $studentRows = $db->query("
        SELECT
            s.id                                     AS user_id,
            CONCAT(s.first_name,' ',s.last_name)      AS full_name,
            u.email                                   AS email,
            'Student'                                 AS role,
            s.created_at                              AS registered_at,
            NULL                                      AS student_count,
            COALESCE(sg.total_xp, 0)                  AS exp_total
        FROM students s
        LEFT JOIN users u ON u.id = s.user_id
        LEFT JOIN student_gamification sg ON sg.student_id = s.id
        ORDER BY s.last_name, s.first_name
    ")->fetchAll();

    $registeredUsers = array_merge($teacherRows, $studentRows);

} catch (PDOException $e) {
    error_log('Super admin dashboard tab4 error: ' . $e->getMessage());
    $tab4Error = 'Unable to load user data.';
}

// ── Resolve API base path ──────────────────────────────────────────────────────
// API is in EDUQUEST/api/ two directories up from super_admin/
$apiBase = '../EDUQUEST/api';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Super Admin Dashboard – EduQuest</title>
    <style>
        /* ── Reset & Base ─────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ──────────────────────────────────────── */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #0a0f1e;
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
            border-bottom: 1px solid #1a2540;
        }
        .brand-logo {
            font-size: 1rem;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .brand-logo .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .brand-sub { font-size: 0.68rem; color: #334155; margin-top: 0.2rem; padding-left: 2.5rem; }
        .badge-super-sm {
            display: inline-block;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #fff;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            margin-top: 0.5rem;
            margin-left: 2.5rem;
        }
        .sidebar-nav { list-style: none; padding: 0.75rem 0; flex: 1; }
        .sidebar-nav-label {
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #334155;
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
            cursor: pointer;
        }
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(124,58,237,0.12);
            color: #f8fafc;
            border-left-color: #7c3aed;
        }
        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid #1a2540;
        }
        .sidebar-user { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.75rem; }
        .sidebar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .sidebar-user-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user-role { font-size: 0.68rem; color: #475569; }
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
        .btn-logout:hover { background: rgba(239,68,68,0.25); }

        /* ── Main ─────────────────────────────────────────── */
        .main { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.875rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .topbar-title { font-size: 1.2rem; font-weight: 700; color: #0f172a; }
        .topbar-meta  { font-size: 0.78rem; color: #64748b; }
        .content { padding: 1.75rem 2rem 3rem; }

        /* ── Tab nav ──────────────────────────────────────── */
        .tab-nav {
            display: flex;
            gap: 0.25rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.35rem;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 0.55rem 1.1rem;
            border: none;
            background: transparent;
            border-radius: 7px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }
        .tab-btn:hover    { background: #f1f5f9; color: #0f172a; }
        .tab-btn.active   { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #fff; }
        .tab-pane         { display: none; }
        .tab-pane.active  { display: block; }

        /* ── Stat cards ───────────────────────────────────── */
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
        .stat-icon.blue   { background: #eff6ff; }
        .stat-icon.green  { background: #f0fdf4; }
        .stat-icon.amber  { background: #fffbeb; }
        .stat-icon.purple { background: #faf5ff; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: #0f172a; line-height: 1; }
        .stat-label { font-size: 0.78rem; color: #64748b; margin-top: 0.2rem; }

        /* ── Two-column layout ────────────────────────────── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

        /* ── Cards ────────────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .card-header {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .card-header h3 { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
        .card-body      { padding: 0; }

        /* ── Tables ───────────────────────────────────────── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.825rem; }
        .data-table th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.6rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            white-space: nowrap;
        }
        .data-table td {
            padding: 0.65rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #374151;
            vertical-align: middle;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td      { background: #f8fafc; }
        .diff-pos { color: #16a34a; font-weight: 600; }
        .diff-neg { color: #dc2626; font-weight: 600; }
        .empty-msg {
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
            padding: 2rem 1rem;
        }

        /* ── Filter bar (Tab 2) ───────────────────────────── */
        .filter-bar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-end;
        }
        .filter-bar label { display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 0.3rem; }
        .filter-bar input[type=text],
        .filter-bar input[type=date],
        .filter-bar select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.825rem;
            color: #1e293b;
            background: #fff;
            outline: none;
        }
        .filter-bar input:focus,
        .filter-bar select:focus { border-color: #7c3aed; }
        .btn-apply {
            padding: 0.5rem 1.25rem;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.825rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-apply:hover { opacity: 0.9; }

        /* ── Engagement summary (Tab 2) ───────────────────── */
        .engagement-summary {
            margin-top: 1.5rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .engagement-summary-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
        }
        .indicator-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 0;
        }
        .indicator-cell {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
        }
        .indicator-cell:nth-child(even) { background: #fafbfc; }
        .indicator-name { font-size: 0.8rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; text-transform: capitalize; }
        .indicator-row  { font-size: 0.78rem; color: #64748b; display: flex; justify-content: space-between; margin-bottom: 0.2rem; }
        .indicator-row span { color: #1e293b; font-weight: 600; }

        /* ── Toggle controls (Tab 3) ──────────────────────── */
        .settings-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .settings-card-header {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
        }
        .setting-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #f8fafc;
            gap: 1.5rem;
        }
        .setting-row:last-child { border-bottom: none; }
        .setting-info { flex: 1; }
        .setting-label { font-size: 0.9rem; font-weight: 600; color: #1e293b; margin-bottom: 0.25rem; }
        .setting-desc  { font-size: 0.8rem; color: #64748b; line-height: 1.5; }
        .setting-ctrl  { display: flex; flex-direction: column; align-items: flex-end; gap: 0.4rem; flex-shrink: 0; }
        .setting-feedback { font-size: 0.72rem; color: #16a34a; min-height: 1rem; text-align: right; }
        .setting-feedback.err { color: #dc2626; }

        /* Toggle switch */
        .toggle-wrap { display: flex; align-items: center; gap: 0.5rem; }
        .toggle-label { font-size: 0.78rem; font-weight: 600; color: #64748b; min-width: 2.5rem; }
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #cbd5e1;
            border-radius: 26px;
            transition: background 0.2s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.25);
        }
        .toggle-switch input:checked + .toggle-slider { background: #7c3aed; }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(22px); }

        /* ── Search input (Tab 4) ─────────────────────────── */
        .search-wrap {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.875rem 1.25rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .search-wrap label { font-size: 0.8rem; font-weight: 600; color: #64748b; white-space: nowrap; }
        .search-wrap input {
            flex: 1;
            padding: 0.5rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.85rem;
            outline: none;
        }
        .search-wrap input:focus { border-color: #7c3aed; }

        /* ── Role badges ──────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-teacher  { background: #eff6ff; color: #2563eb; }
        .badge-student  { background: #f0fdf4; color: #16a34a; }

        /* ── Spinner ──────────────────────────────────────── */
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(124,58,237,0.2);
            border-top-color: #7c3aed;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hidden { display: none !important; }

        /* ── Alert ────────────────────────────────────────── */
        .alert {
            border-radius: 7px;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .alert-error { background: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.25); color: #dc2626; }

        /* ── Responsive ───────────────────────────────────── */
        @media (max-width: 900px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .two-col   { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .stats-row { grid-template-columns: 1fr; }
            body       { flex-direction: column; }
            .sidebar   { width: 100%; min-height: unset; height: auto; position: static; }
        }
    </style>
</head>
<body>

<!-- ═══ SIDEBAR ════════════════════════════════════════════════════════════ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <div class="logo-icon">&#9670;</div>
            EduQuest
        </div>
        <div class="brand-sub">Digital Portfolio System</div>
        <div class="badge-super-sm">Super Admin</div>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-nav-label">Dashboard</li>
        <li><a href="#" data-tab="logs"          class="tab-link active">&#128203; Behavioral Logs</a></li>
        <li><a href="#" data-tab="users"         class="tab-link">&#128101; Registered Users</a></li>
        <li><a href="#" data-tab="tracker"       class="tab-link">&#128200; Behavioral Summary</a></li>
        <li><a href="#" data-tab="usersummary"   class="tab-link">&#128202; Users Summary</a></li>
    </ul>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= mb_strtoupper(mb_substr($_SESSION['super_admin_name'] ?? 'S', 0, 1)) ?></div>
            <div>
                <div class="sidebar-user-name"><?= $saName ?></div>
                <div class="sidebar-user-role">Super Admin</div>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">Sign Out</a>
    </div>
</aside>

<!-- ═══ MAIN ════════════════════════════════════════════════════════════════ -->
<div class="main">
    <div class="topbar">
        <div class="topbar-title" id="topbar-title">Behavioral Logs</div>
        <div class="topbar-meta"><?= date('F j, Y') ?> &mdash; <?= $saEmail ?></div>
    </div>

    <div class="content">

        <!-- Tab nav -->
        <div class="tab-nav">
            <button class="tab-btn active" data-tab="logs">&#128203; Behavioral Logs</button>
            <button class="tab-btn"        data-tab="users">&#128101; Registered Users</button>
            <button class="tab-btn"        data-tab="tracker">&#128200; Behavioral Summary</button>
            <button class="tab-btn"        data-tab="usersummary">&#128202; Users Summary</button>
        </div>

        <!-- ════════════════════════════════════════════════════
             TAB 1 — Behavioral Indicator Logs
             ════════════════════════════════════════════════════ -->
        <div class="tab-pane active" id="pane-logs">

            <!-- Filter bar -->
            <div class="filter-bar">
                <div>
                    <label for="f-name">Student Name</label>
                    <input type="text" id="f-name" placeholder="Search student…" style="width:180px;" />
                </div>
                <div>
                    <label for="f-type">Log Type</label>
                    <select id="f-type">
                        <option value="">All</option>
                        <option value="engagement">Engagement</option>
                        <option value="self_regulation">Self-Regulation</option>
                    </select>
                </div>
                <div>
                    <label for="f-from">Date From</label>
                    <input type="date" id="f-from" />
                </div>
                <div>
                    <label for="f-to">Date To</label>
                    <input type="date" id="f-to" />
                </div>
                <div>
                    <button class="btn-apply" id="btn-apply-filter">Apply Filter</button>
                </div>
                <span id="logs-spinner" class="spinner hidden"></span>
            </div>

            <!-- Logs table -->
            <div class="card">
                <div class="card-header">
                    <h3>Behavioral Log Entries</h3>
                    <span id="logs-count" style="font-size:0.78rem;color:#64748b;"></span>
                </div>
                <div class="card-body">
                    <div style="overflow-x:auto;">
                    <table class="data-table" id="logs-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Indicator</th>
                                <th>Category</th>
                                <th>Source</th>
                                <th>Value</th>
                                <th>Session Date</th>
                                <th>Logged By</th>
                                <th>Recorded At</th>
                            </tr>
                        </thead>
                        <tbody id="logs-tbody">
                            <tr><td colspan="8" class="empty-msg">Click "Apply Filter" to load logs.</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Engagement summary (populated by JS) -->
            <div class="engagement-summary" id="engagement-summary-wrap" style="display:none;">
                <div class="engagement-summary-header">Engagement Indicator Summary (System-Wide)</div>
                <div class="indicator-grid" id="engagement-summary-grid"></div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             TAB 2 — Registered Users
             ════════════════════════════════════════════════════ -->
        <div class="tab-pane" id="pane-users">
            <?php if ($tab4Error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($tab4Error) ?></div>
            <?php endif; ?>

            <div class="search-wrap">
                <label for="user-search">Search:</label>
                <input type="text" id="user-search" placeholder="Filter by name or email…" />
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>All Registered Users</h3>
                    <span style="font-size:0.78rem;color:#64748b;"><?= count($registeredUsers) ?> total</span>
                </div>
                <div class="card-body">
                    <div style="overflow-x:auto;">
                    <table class="data-table" id="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>EXP / Students</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($registeredUsers)): ?>
                            <tr><td colspan="5" class="empty-msg">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($registeredUsers as $u): ?>
                            <tr data-search="<?= htmlspecialchars(strtolower($u['full_name'] . ' ' . $u['email'])) ?>">
                                <td><?= htmlspecialchars($u['full_name']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="badge badge-<?= strtolower($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                                <td><?= htmlspecialchars(date('M j, Y', strtotime($u['registered_at']))) ?></td>
                                <td>
                                    <?php if ($u['role'] === 'Student'): ?>
                                        <?= (int)($u['exp_total'] ?? 0) ?> XP
                                    <?php else: ?>
                                        <?= (int)($u['student_count'] ?? 0) ?> students
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             TAB 5 — Behavioral Logs Summary (Interaction Tracking)
             ════════════════════════════════════════════════════ -->
        <div class="tab-pane" id="pane-tracker">

            <!-- Section A: Page Time -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header">
                    <h3>&#128200; Page Time Summary</h3>
                    <input type="text" class="summary-filter" data-table="tracker-pages-tbody" placeholder="Filter by student…" style="width:200px;" />
                </div>
                <div class="card-body">
                    <div id="tracker-top-pages" style="margin-bottom:1rem;"></div>
                    <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>Student</th><th>Total Time</th><th>Avg/Visit (s)</th><th>Most Visited Page</th><th>Last Active</th></tr></thead>
                        <tbody id="tracker-pages-tbody"><tr><td colspan="5" class="empty-msg">Loading…</td></tr></tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Section B: Question Engagement -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header">
                    <h3>&#10067; Question Engagement</h3>
                    <input type="text" class="summary-filter" data-table="tracker-questions-tbody" placeholder="Filter by student…" style="width:200px;" />
                </div>
                <div class="card-body">
                    <div id="tracker-hardest-questions" style="margin-bottom:1rem;"></div>
                    <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>Student</th><th>Avg Time (s)</th><th>Fastest (s)</th><th>Slowest (s)</th><th>Attempted</th><th>Correct Rate %</th></tr></thead>
                        <tbody id="tracker-questions-tbody"><tr><td colspan="6" class="empty-msg">Loading…</td></tr></tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Section C: Click Behavior -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header">
                    <h3>&#128432; Click Behavior</h3>
                    <input type="text" class="summary-filter" data-table="tracker-clicks-tbody" placeholder="Filter by student…" style="width:200px;" />
                </div>
                <div class="card-body">
                    <div id="tracker-top-clicks" style="margin-bottom:1rem;"></div>
                    <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>Student</th><th>Total Clicks</th><th>Clicks Today</th><th>Most Clicked</th><th>Most Active Day</th></tr></thead>
                        <tbody id="tracker-clicks-tbody"><tr><td colspan="5" class="empty-msg">Loading…</td></tr></tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Section D: Hover Behavior -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header">
                    <h3>&#128065; Hover Behavior</h3>
                    <input type="text" class="summary-filter" data-table="tracker-hovers-tbody" placeholder="Filter by student…" style="width:200px;" />
                </div>
                <div class="card-body">
                    <div id="tracker-top-hovers" style="margin-bottom:1rem;"></div>
                    <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>Student</th><th>Total Hover (ms)</th><th>Most Hovered</th><th>Least Hovered</th></tr></thead>
                        <tbody id="tracker-hovers-tbody"><tr><td colspan="4" class="empty-msg">Loading…</td></tr></tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Section E: Composite Engagement Score -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header">
                    <h3>&#9889; Combined Engagement Score</h3>
                    <input type="text" class="summary-filter" data-table="tracker-scores-tbody" placeholder="Filter by student…" style="width:200px;" />
                </div>
                <div class="card-body">
                    <p style="font-size:0.78rem;color:#64748b;margin-bottom:0.75rem;">
                        Score is a normalized composite (0–100) weighted: 40% time on page, 30% clicks, 30% questions attempted.
                        This is an engagement indicator only, not an academic performance score.
                    </p>
                    <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>Rank</th><th>Student</th><th>Total Clicks</th><th>Total Time (s)</th><th>Questions Attempted</th><th>Engagement Score</th></tr></thead>
                        <tbody id="tracker-scores-tbody"><tr><td colspan="6" class="empty-msg">Loading…</td></tr></tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             TAB 6 — Registered Users Summary
             ════════════════════════════════════════════════════ -->
        <div class="tab-pane" id="pane-usersummary">

            <!-- Section A: Stat Cards -->
            <div id="ussum-stat-cards" style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
                <div class="loading-msg">Loading overview…</div>
            </div>

            <!-- Section B: Registration Timeline Chart -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header"><h3>&#128197; Monthly Registrations (Past 6 Months)</h3></div>
                <div class="card-body">
                    <div id="ussum-timeline-chart" style="min-height:120px;"></div>
                </div>
            </div>

            <!-- Section C: Teacher Activity -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header">
                    <h3>&#128105;&#8205;&#127979; Teacher Activity</h3>
                    <input type="text" class="summary-filter" data-table="ussum-teachers-tbody" placeholder="Filter by name/email…" style="width:200px;" />
                </div>
                <div class="card-body">
                    <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>Teacher</th><th>Email</th><th>Students</th><th>Materials</th><th>Last Login</th></tr></thead>
                        <tbody id="ussum-teachers-tbody"><tr><td colspan="5" class="empty-msg">Loading…</td></tr></tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Section D: Student Activity -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header">
                    <h3>&#127891; Student Activity</h3>
                    <input type="text" class="summary-filter" data-table="ussum-students-tbody" placeholder="Filter by name/email…" style="width:200px;" />
                </div>
                <div class="card-body">
                    <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>Student</th><th>Email</th><th>Team</th><th>Pet Stage</th><th>XP</th><th>Pre</th><th>Post</th><th>Change</th><th>Materials</th><th>Last Login</th></tr></thead>
                        <tbody id="ussum-students-tbody"><tr><td colspan="10" class="empty-msg">Loading…</td></tr></tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Section E: Inactive Users -->
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="card-header"><h3>&#9888;&#65039; Inactive Students (&gt;7 days)</h3></div>
                <div class="card-body">
                    <div id="ussum-inactive-list"><div class="loading-msg">Loading…</div></div>
                </div>
            </div>
        </div>

    </div><!-- /content -->
</div><!-- /main -->

<script>
(function () {
    'use strict';

    // ── Tab switching ──────────────────────────────────────────────────────────
    const tabTitles = {
        logs:        'Behavioral Indicator Logs',
        users:       'Registered Users',
        tracker:     'Behavioral Logs Summary',
        usersummary: 'Registered Users Summary',
    };

    const trackerLoaded     = {};
    const usersummaryLoaded = {};

    function activateTab(name) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
        document.querySelectorAll('.tab-link').forEach(a => a.classList.toggle('active', a.dataset.tab === name));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.toggle('active', p.id === 'pane-' + name));
        document.getElementById('topbar-title').textContent = tabTitles[name] || 'Dashboard';

        if (name === 'tracker'     && !trackerLoaded.done)     loadTrackerTab();
        if (name === 'usersummary' && !usersummaryLoaded.done) loadUserSummaryTab();
    }

    document.querySelectorAll('[data-tab]').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            activateTab(el.dataset.tab);
        });
    });

    // ── Tab 1: Behavioral Logs fetch ─────────────────────────────────────────────────────
    const API_LOGS = '../EDUQUEST/api/super_admin_logs_fetch.php';

    const INDICATOR_LABELS = {
        task_completion_rate:       'Task Completion Rate',
        time_on_task:               'Time on Task',
        module_attempt_frequency:   'Module Attempt Frequency',
        response_rate:              'Response Rate',
        exp_accumulation_rate:      'EXP Accumulation Rate',
        task_initiation:            'Task Initiation',
        task_persistence:           'Task Persistence',
        consistency_of_completion:  'Consistency of Completion',
        responsiveness_to_feedback: 'Responsiveness to Feedback',
        frustration_management:     'Frustration Management',
    };

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '–';
        return d.innerHTML;
    }

    async function fetchLogs() {
        const params = new URLSearchParams();
        const name = document.getElementById('f-name').value.trim();
        const type = document.getElementById('f-type').value;
        const from = document.getElementById('f-from').value;
        const to   = document.getElementById('f-to').value;

        if (name) params.set('student_name', name);
        if (type) params.set('log_type', type);
        if (from) params.set('date_from', from);
        if (to)   params.set('date_to', to);

        const spinner = document.getElementById('logs-spinner');
        const tbody   = document.getElementById('logs-tbody');
        const countEl = document.getElementById('logs-count');

        spinner.classList.remove('hidden');
        tbody.innerHTML = '<tr><td colspan="8" class="empty-msg">Loading…</td></tr>';

        try {
            const res  = await fetch(API_LOGS + '?' + params.toString());
            const data = await res.json();

            if (!data.success) throw new Error(data.message || 'API error');

            if (!data.logs || data.logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty-msg">No log entries match the selected filters.</td></tr>';
                countEl.textContent = '0 entries';
                renderEngagementSummary(data.engagement_summary || {});
                return;
            }

            countEl.textContent = data.logs.length + ' entries';

            tbody.innerHTML = data.logs.map(r => `
                <tr>
                    <td>${esc(r.student_name)}</td>
                    <td>${esc(INDICATOR_LABELS[r.indicator_key] || r.indicator_key.replace(/_/g,' '))}</td>
                    <td>${esc(r.log_type === 'engagement' ? 'Engagement' : 'Self-Regulation')}</td>
                    <td><span style="background-color:${r.source === 'quiz' ? '#3b82f6' : r.source === 'activity' ? '#10b981' : '#6b7280'}; color:white; padding:2px 6px; border-radius:3px; font-size:0.85em;">${esc((r.source || 'other').toUpperCase())}</span></td>
                    <td>${esc(r.indicator_value)}</td>
                    <td>${esc(r.session_date)}</td>
                    <td>${esc(r.logged_by === 'system' ? 'System' : r.teacher_name)}</td>
                    <td>${esc(r.created_at)}</td>
                </tr>
            `).join('');

            renderEngagementSummary(data.engagement_summary || {});

        } catch (err) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-msg" style="color:#dc2626;">Failed to load logs. Please try again.</td></tr>';
        } finally {
            spinner.classList.add('hidden');
        }
    }

    function renderEngagementSummary(summary) {
        const wrap = document.getElementById('engagement-summary-wrap');
        const grid = document.getElementById('engagement-summary-grid');

        const engKeys = ['task_completion_rate','time_on_task','module_attempt_frequency','response_rate','exp_accumulation_rate'];
        
        // Check if data is nested by source (new format) or flat (old format)
        const isNestedBySource = engKeys.length > 0 && summary[engKeys[0]] && typeof summary[engKeys[0]].quiz !== 'undefined';
        
        const hasData = engKeys.some(k => {
          if (!summary[k]) return false;
          if (isNestedBySource) {
            return Object.values(summary[k]).some(s => s && s.avg !== null);
          } else {
            return summary[k].avg !== null;
          }
        });
        
        if (!hasData) { wrap.style.display = 'none'; return; }

        wrap.style.display = '';
        
        if (isNestedBySource) {
          // New format: nested by source
          const sources = ['quiz', 'activity'];
          grid.innerHTML = engKeys.map(key => {
            const summaryKey = summary[key] || {};
            return sources.map(src => {
              const s   = summaryKey[src] || {};
              const avg = s.avg  != null ? parseFloat(s.avg).toFixed(2)  : '–';
              const hi  = s.max_value != null ? s.max_value  : '–';
              const lo  = s.min_value != null ? s.min_value  : '–';
              const hiS = s.max_student || '–';
              const loS = s.min_student || '–';
              return `
                  <div class="indicator-cell">
                      <div class="indicator-name">${esc(INDICATOR_LABELS[key])} <span style="font-size:0.85em; color:#666;">(${src.toUpperCase()})</span></div>
                      <div class="indicator-row">System avg: <span>${esc(avg)}</span></div>
                      <div class="indicator-row">Highest: <span>${esc(hi)}</span> (${esc(hiS)})</div>
                      <div class="indicator-row">Lowest: <span>${esc(lo)}</span> (${esc(loS)})</div>
                  </div>
              `;
            }).join('');
          }).join('');
        } else {
          // Old format: flat (backward compatibility)
          grid.innerHTML = engKeys.map(key => {
            const s   = summary[key] || {};
            const avg = s.avg  != null ? parseFloat(s.avg).toFixed(2)  : '–';
            const hi  = s.max_value != null ? s.max_value  : '–';
            const lo  = s.min_value != null ? s.min_value  : '–';
            const hiS = s.max_student || '–';
            const loS = s.min_student || '–';
            return `
                <div class="indicator-cell">
                    <div class="indicator-name">${esc(INDICATOR_LABELS[key])}</div>
                    <div class="indicator-row">System avg: <span>${esc(avg)}</span></div>
                    <div class="indicator-row">Highest: <span>${esc(hi)}</span> (${esc(hiS)})</div>
                    <div class="indicator-row">Lowest: <span>${esc(lo)}</span> (${esc(loS)})</div>
                </div>
            `;
          }).join('');
        }
    }

    document.getElementById('btn-apply-filter').addEventListener('click', fetchLogs);

    // ── Tab 2: Client-side user search ────────────────────────────────────────
    document.getElementById('user-search').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('#users-table tbody tr').forEach(row => {
            const searchVal = row.dataset.search || '';
            row.style.display = !q || searchVal.includes(q) ? '' : 'none';
        });
    });

    // ── Shared summary filter ─────────────────────────────────────────────────
    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('summary-filter')) return;
        const tbodyId = e.target.dataset.table;
        const q = e.target.value.toLowerCase().trim();
        if (!tbodyId) return;
        document.querySelectorAll('#' + tbodyId + ' tr').forEach(row => {
            row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // ── Helpers ────────────────────────────────────────────────────────────────
    const API_SUMMARY = '../EDUQUEST/api/super_admin_summary.php';

    async function fetchSection(section) {
        const res = await fetch(API_SUMMARY + '?section=' + section);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    function fmtSeconds(s) {
        s = parseInt(s) || 0;
        if (s < 60) return s + 's';
        return Math.floor(s / 60) + 'm ' + (s % 60) + 's';
    }

    function renderBarList(containerId, items, labelKey, valueKey, unit) {
        const el = document.getElementById(containerId);
        if (!el || !items || items.length === 0) return;
        const max = Math.max(...items.map(i => +i[valueKey] || 0), 1);
        el.innerHTML = '<div style="font-size:0.78rem;font-weight:600;color:#475569;margin-bottom:0.4rem;">Top ' + items.length + ':</div>'
            + items.map(i => {
                const pct = Math.round((+i[valueKey] / max) * 100);
                return `<div style="margin-bottom:0.3rem;">
                    <div style="display:flex;justify-content:space-between;font-size:0.75rem;margin-bottom:2px;">
                        <span>${esc(i[labelKey])}</span><span>${esc(String(i[valueKey]))} ${unit}</span>
                    </div>
                    <div style="background:#e2e8f0;border-radius:3px;height:8px;">
                        <div style="background:#6366f1;width:${pct}%;height:8px;border-radius:3px;"></div>
                    </div>
                </div>`;
            }).join('');
    }

    // ── Tab 5: Tracker / Behavioral Summary ────────────────────────────────────
    async function loadTrackerTab() {
        trackerLoaded.done = true;

        try {
            const [pages, questions, clicks, hovers, scores] = await Promise.all([
                fetchSection('interaction_pages'),
                fetchSection('interaction_questions'),
                fetchSection('interaction_clicks'),
                fetchSection('interaction_hovers'),
                fetchSection('interaction_scores'),
            ]);

            // Section A: pages
            renderBarList('tracker-top-pages', pages.data.top_pages, 'page_name', 'total_seconds', 's');
            document.getElementById('tracker-pages-tbody').innerHTML = (pages.data.students || []).map(r =>
                `<tr><td>${esc(r.student_name)}</td><td>${esc(fmtSeconds(r.total_seconds))}</td><td>${esc(r.avg_seconds_per_visit)}</td><td>${esc(r.most_visited_page || '–')}</td><td>${esc(r.last_active || '–')}</td></tr>`
            ).join('') || '<tr><td colspan="5" class="empty-msg">No data.</td></tr>';

            // Section B: questions
            renderBarList('tracker-hardest-questions', questions.data.hardest_questions || [], 'quiz_name', 'avg_time', 's avg');
            document.getElementById('tracker-questions-tbody').innerHTML = (questions.data.students || []).map(r =>
                `<tr><td>${esc(r.student_name)}</td><td>${esc(r.avg_time)}</td><td>${esc(r.fastest)}</td><td>${esc(r.slowest)}</td><td>${esc(r.total_attempted)}</td><td>${esc(r.correct_rate)}%</td></tr>`
            ).join('') || '<tr><td colspan="6" class="empty-msg">No data.</td></tr>';

            // Section C: clicks
            renderBarList('tracker-top-clicks', clicks.data.top_elements || [], 'element_label', 'total_clicks', 'clicks');
            document.getElementById('tracker-clicks-tbody').innerHTML = (clicks.data.students || []).map(r =>
                `<tr><td>${esc(r.student_name)}</td><td>${esc(r.total_clicks)}</td><td>${esc(r.clicks_today)}</td><td>${esc(r.most_clicked_element || '–')}</td><td>${esc(r.most_active_day || '–')}</td></tr>`
            ).join('') || '<tr><td colspan="5" class="empty-msg">No data.</td></tr>';

            // Section D: hovers
            renderBarList('tracker-top-hovers', hovers.data.top_elements || [], 'element_label', 'total_ms', 'ms');
            document.getElementById('tracker-hovers-tbody').innerHTML = (hovers.data.students || []).map(r =>
                `<tr><td>${esc(r.student_name)}</td><td>${esc(r.total_hover_ms)}</td><td>${esc(r.most_hovered || '–')}</td><td>${esc(r.least_hovered || '–')}</td></tr>`
            ).join('') || '<tr><td colspan="4" class="empty-msg">No data.</td></tr>';

            // Section E: scores
            document.getElementById('tracker-scores-tbody').innerHTML = (scores.data || []).map((r, i) => {
                const score = parseFloat(r.engagement_score) || 0;
                const color = score >= 70 ? '#16a34a' : score >= 40 ? '#d97706' : '#dc2626';
                return `<tr>
                    <td>${i + 1}</td>
                    <td>${esc(r.student_name)}</td>
                    <td>${esc(r.total_clicks)}</td>
                    <td>${esc(r.total_time_seconds)}</td>
                    <td>${esc(r.questions_attempted)}</td>
                    <td><strong style="color:${color}">${score.toFixed(1)}</strong></td>
                </tr>`;
            }).join('') || '<tr><td colspan="6" class="empty-msg">No data.</td></tr>';

        } catch (err) {
            ['tracker-pages-tbody','tracker-questions-tbody','tracker-clicks-tbody','tracker-hovers-tbody','tracker-scores-tbody']
                .forEach(id => { const el = document.getElementById(id); if (el) el.innerHTML = '<tr><td colspan="10" class="empty-msg" style="color:#dc2626;">Failed to load.</td></tr>'; });
        }
    }

    // ── Tab 6: Users Summary ───────────────────────────────────────────────────
    async function loadUserSummaryTab() {
        usersummaryLoaded.done = true;

        try {
            const [overview, timeline, teachers, students, inactive] = await Promise.all([
                fetchSection('overview'),
                fetchSection('timeline'),
                fetchSection('teachers'),
                fetchSection('students'),
                fetchSection('inactive'),
            ]);

            // Section A: stat cards
            const o = overview.data;
            const growthColor = o.growth_pct >= 0 ? '#16a34a' : '#dc2626';
            document.getElementById('ussum-stat-cards').innerHTML = [
                ['&#127891; Total Students', o.total_students, ''],
                ['&#128105;&#8205;&#127979; Total Teachers', o.total_teachers, ''],
                ['&#128197; New This Month', o.new_this_month, `<span style="font-size:0.75rem;color:${growthColor};">(${o.growth_pct >= 0 ? '+' : ''}${o.growth_pct}% vs last month)</span>`],
                ['&#128200; New This Week', o.new_this_week, ''],
            ].map(([label, value, extra]) =>
                `<div style="flex:1;min-width:170px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                    <div style="font-size:0.78rem;color:#64748b;">${label}</div>
                    <div style="font-size:1.6rem;font-weight:700;color:#1e293b;">${esc(String(value))}</div>
                    ${extra}
                </div>`
            ).join('');

            // Section B: timeline bar chart (CSS-only)
            const maxMonthCount = Math.max(...timeline.data.map(m => m.teachers + m.students), 1);
            document.getElementById('ussum-timeline-chart').innerHTML =
                '<div style="display:flex;align-items:flex-end;gap:0.5rem;height:120px;">' +
                timeline.data.map(m => {
                    const total = (m.teachers || 0) + (m.students || 0);
                    const pct = Math.round(total / maxMonthCount * 100);
                    return `<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;">
                        <div style="font-size:0.7rem;color:#475569;">${total}</div>
                        <div style="width:100%;background:#6366f1;border-radius:3px 3px 0 0;height:${pct}%;min-height:4px;" title="T:${m.teachers} S:${m.students}"></div>
                        <div style="font-size:0.68rem;color:#64748b;white-space:nowrap;">${esc(m.month)}</div>
                    </div>`;
                }).join('') + '</div>';

            // Section C: teachers
            document.getElementById('ussum-teachers-tbody').innerHTML = (teachers.data || []).map(r =>
                `<tr><td>${esc(r.teacher_name)}</td><td>${esc(r.email)}</td><td>${esc(r.students_assigned)}</td><td>${esc(r.materials_uploaded)}</td><td>${esc(r.last_login || '–')}</td></tr>`
            ).join('') || '<tr><td colspan="5" class="empty-msg">No teachers.</td></tr>';

            // Section D: students
            document.getElementById('ussum-students-tbody').innerHTML = (students.data || []).map(r => {
                const change = parseInt(r.score_change);
                const changeColor = isNaN(change) ? '' : change > 0 ? 'color:#16a34a;' : change < 0 ? 'color:#dc2626;' : '';
                const changeStr = isNaN(change) ? '–' : (change > 0 ? '+' : '') + change;
                return `<tr>
                    <td>${esc(r.student_name)}</td><td>${esc(r.email)}</td><td>${esc(r.team || '–')}</td>
                    <td>${esc(r.pet_stage)}</td><td>${esc(r.total_exp)}</td>
                    <td>${r.pretest_score != null ? esc(r.pretest_score) : '–'}</td>
                    <td>${r.posttest_score != null ? esc(r.posttest_score) : '–'}</td>
                    <td style="${changeColor}">${changeStr}</td>
                    <td>${esc(r.materials_viewed)}</td><td>${esc(r.last_login || '–')}</td>
                </tr>`;
            }).join('') || '<tr><td colspan="10" class="empty-msg">No students.</td></tr>';

            // Section E: inactive
            const inactiveList = inactive.data || [];
            document.getElementById('ussum-inactive-list').innerHTML = inactiveList.length === 0
                ? '<p style="color:#16a34a;font-size:0.85rem;">&#10004; No inactive students (&gt;7 days).</p>'
                : '<div style="display:flex;flex-wrap:wrap;gap:0.6rem;">' + inactiveList.map(r =>
                    `<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:0.5rem 0.75rem;font-size:0.8rem;">
                        <strong>${esc(r.student_name)}</strong><br>
                        <span style="color:#64748b;">Last login: ${esc(r.last_login || 'Never')}</span><br>
                        <span style="color:#dc2626;">${esc(r.days_inactive)} days inactive</span>
                    </div>`
                  ).join('') + '</div>';

        } catch (err) {
            document.getElementById('ussum-stat-cards').innerHTML = '<p style="color:#dc2626;">Failed to load summary.</p>';
        }
    }

})();
</script>

</body>
</html>
