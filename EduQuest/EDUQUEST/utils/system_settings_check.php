<?php
/**
 * System Settings Check Helper
 * ──────────────────────────────────────────────────────────────────────────────
 * Include via require_once in any handler that needs to gate a feature behind
 * a super-admin toggle.
 *
 * Usage in a JSON API handler (pretest initiation, posttest, survey submit):
 * ──────────────────────────────────────────────────────────────────────────────
 *
 *   require_once __DIR__ . '/../utils/system_settings_check.php';
 *
 *   // Pre-test initiation handler
 *   enforceSettingEnabled($pdo, 'pretest_enabled',
 *       'Pre-test assessments are currently disabled by the system administrator.');
 *
 *   // Post-test initiation handler
 *   enforceSettingEnabled($pdo, 'posttest_enabled',
 *       'Post-test assessments are currently disabled by the system administrator.');
 *
 *   // Teacher PSSUQ survey submission handler
 *   enforceSettingEnabled($pdo, 'pssuq_teacher_enabled',
 *       'This survey is currently closed. Please contact your administrator.');
 *
 *   // Student PSSUQ survey submission handler
 *   enforceSettingEnabled($pdo, 'pssuq_student_enabled',
 *       'This survey is currently closed. Please contact your administrator.');
 *
 * ──────────────────────────────────────────────────────────────────────────────
 * The function sends a 403 JSON response and exits if the feature is disabled.
 * It silently allows the request through if the system_settings table does not
 * yet exist (fail-open so existing flows are never broken by a missing table).
 */

/**
 * Check one setting key. Exits with 403 JSON if the setting value is 0.
 *
 * @param PDO    $pdo     Active database connection.
 * @param string $key     One of the four feature toggle keys.
 * @param string $message Error message returned to the client if disabled.
 */
function enforceSettingEnabled(PDO $pdo, string $key, string $message): void {
    // Whitelist the allowed keys to prevent SQL injection via the key param
    $allowed = ['pretest_enabled', 'posttest_enabled', 'pssuq_teacher_enabled', 'pssuq_student_enabled'];
    if (!in_array($key, $allowed, true)) {
        return; // Unknown key — silently pass through
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1'
        );
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false && (int) $row['setting_value'] === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    } catch (PDOException $e) {
        // system_settings table may not exist yet — fail open (do not block)
        error_log('enforceSettingEnabled error: ' . $e->getMessage());
    }
}
