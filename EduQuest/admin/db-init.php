<?php
/**
 * Database Initialization Script
 * Runs all SQL schemas to set up the database
 * 
 * URL: /admin/db-init.php
 */

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'eduquest');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

$messages = [];
$errors = [];

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

    // List of schema files to load
    $schemaFiles = [
        '../EDUQUEST/database/schema_account_management.sql',
        '../EDUQUEST/database/schema_admins.sql',
        '../EDUQUEST/database/schema_assignment_submissions.sql',
        '../EDUQUEST/database/schema_game_assignments.sql',
        '../EDUQUEST/database/schema_game_tracking.sql',
        '../EDUQUEST/database/schema_gamification.sql',
        '../EDUQUEST/database/schema_interaction_tracking.sql',
        '../EDUQUEST/database/schema_learning_modules.sql',
        '../EDUQUEST/database/schema_notifications.sql',
        '../EDUQUEST/database/schema_pet_name.sql',
        '../EDUQUEST/database/schema_plans.sql',
        '../EDUQUEST/database/schema_score_visibility.sql',
        '../EDUQUEST/database/schema_super_admin.sql',
        '../EDUQUEST/database/schema_teacher_quizzes.sql',
        '../EDUQUEST/database/schema_teacher_whitelist.sql',
        '../EDUQUEST/database/schema_teacher_activities.sql',
        '../EDUQUEST/database/schema_teacher_default_game_settings.sql',
    ];

    foreach ($schemaFiles as $file) {
        $path = __DIR__ . '/' . $file;
        
        if (!file_exists($path)) {
            $errors[] = "Schema file not found: $file";
            continue;
        }

        $sql = file_get_contents($path);
        if (!$sql) {
            $errors[] = "Could not read schema file: $file";
            continue;
        }

        // Split into individual statements and execute each one
        $statements = array_filter(array_map('trim', preg_split('/;(?=\s*$|\s*--|\s*\/\*)/m', $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement)) continue;
            
            try {
                $pdo->exec($statement);
                $messages[] = "✓ Executed: " . basename($file);
            } catch (PDOException $e) {
                $errors[] = "Error in " . basename($file) . ": " . $e->getMessage();
            }
        }
    }

    $status = empty($errors) ? 'success' : 'partial';

} catch (PDOException $e) {
    $status = 'error';
    $errors[] = 'Database connection error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Database Initialization – EduQuest</title>
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
            max-width: 600px;
            width: 100%;
        }
        .icon { font-size: 3rem; text-align: center; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; text-align: center; }
        .msg {
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.4); color: #86efac; }
        .error   { background: rgba(239,68,68,0.15);  border: 1px solid rgba(239,68,68,0.4);  color: #fca5a5; }
        .partial { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.4); color: #fcd34d; }
        .log {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1.25rem;
            font-size: 0.85rem;
            color: #94a3b8;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
        }
        .log-item { margin: 0.5rem 0; }
        .log-success { color: #86efac; }
        .log-error { color: #fca5a5; }
        a.btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.65rem 1.5rem;
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            width: 100%;
        }
        a.btn:hover { background: #1d4ed8; }
        .warning {
            font-size: 0.75rem;
            color: #f59e0b;
            margin-top: 1.5rem;
            padding: 0.75rem 1rem;
            background: rgba(245,158,11,0.1);
            border: 1px solid rgba(245,158,11,0.3);
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($status === 'success'): ?>
            <div class="icon">✅</div>
            <h1>Database Setup Complete</h1>
            <div class="msg success">All database schemas have been successfully created!</div>
        <?php elseif ($status === 'partial'): ?>
            <div class="icon">⚠️</div>
            <h1>Database Setup Completed (With Warnings)</h1>
            <div class="msg partial">Some schemas were loaded, but there were issues.</div>
        <?php else: ?>
            <div class="icon">❌</div>
            <h1>Database Setup Failed</h1>
            <div class="msg error">Could not initialize database.</div>
        <?php endif; ?>

        <div class="log">
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="log-item log-success"><?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $err): ?>
                    <div class="log-item log-error"><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="btn">Go to Admin Dashboard &rarr;</a>

        <div class="warning">
            &#9888; You can delete or restrict access to <strong>db-init.php</strong> after confirming the setup.
        </div>
    </div>
</body>
</html>
