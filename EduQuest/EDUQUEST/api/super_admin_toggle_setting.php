<?php
/**
 * Super Admin API – Toggle System Setting
 * POST /EDUQUEST/api/super_admin_toggle_setting.php
 *
 * POST body:
 *   setting_key   – one of the four allowed keys
 *   setting_value – 0 or 1
 *
 * Returns JSON: { success: true, setting_key: ..., new_value: ... }
 */

header('Content-Type: application/json; charset=utf-8');

// ── Session guard ──────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
session_start();

if (
    empty($_SESSION['super_admin_id']) ||
    ($_SESSION['role'] ?? '') !== 'super_admin'
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── Whitelist of allowed setting keys ─────────────────────────────────────────
const ALLOWED_KEYS = ['pretest_enabled', 'posttest_enabled', 'pssuq_teacher_enabled', 'pssuq_student_enabled'];

$settingKey   = trim($_POST['setting_key']   ?? '');
$settingValue = $_POST['setting_value'] ?? '';

// Validate setting_key against the whitelist
if (!in_array($settingKey, ALLOWED_KEYS, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid setting_key.']);
    exit;
}

// Validate setting_value is 0 or 1
if ($settingValue !== '0' && $settingValue !== '1' && $settingValue !== 0 && $settingValue !== 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'setting_value must be 0 or 1.']);
    exit;
}
$newValue = (int) $settingValue;
$saId     = (int) $_SESSION['super_admin_id'];

// ── Database ───────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';

try {
    $db   = getDBConnection();
    $stmt = $db->prepare(
        'UPDATE system_settings
            SET setting_value = :val,
                updated_by    = :sa_id,
                updated_at    = NOW()
          WHERE setting_key = :key'
    );
    $stmt->execute([
        ':val'   => $newValue,
        ':sa_id' => $saId,
        ':key'   => $settingKey,
    ]);

    if ($stmt->rowCount() === 0) {
        // Row might not exist yet (shouldn't happen after seeding) — insert it
        $ins = $db->prepare(
            'INSERT INTO system_settings (setting_key, setting_value, updated_by)
             VALUES (:key, :val, :sa_id)
             ON DUPLICATE KEY UPDATE
               setting_value = VALUES(setting_value),
               updated_by    = VALUES(updated_by),
               updated_at    = NOW()'
        );
        $ins->execute([':key' => $settingKey, ':val' => $newValue, ':sa_id' => $saId]);
    }

    echo json_encode([
        'success'     => true,
        'setting_key' => $settingKey,
        'new_value'   => $newValue,
    ]);

} catch (PDOException $e) {
    error_log('super_admin_toggle_setting error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
