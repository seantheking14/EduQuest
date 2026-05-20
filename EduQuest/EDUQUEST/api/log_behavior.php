<?php
/**
 * Behavioral Log Helper
 * ──────────────────────────────────────────────────────────────────────────────
 * Include via require_once — do NOT call via HTTP.
 *
 * Usage:
 *   require_once __DIR__ . '/path/to/api/log_behavior.php';
 *
 * System-logged engagement indicator (e.g. after a student submits a module):
 *   log_behavior($pdo, $student_id, 'engagement', 'task_completion_rate',
 *                '100', 'system');
 *
 * Teacher-logged self-regulation indicator (e.g. observation checklist submit):
 *   log_behavior($pdo, $student_id, 'self_regulation', 'task_initiation',
 *                'Yes - initiated independently', 'teacher', $teacher_id);
 */

// ── Thesis-defined indicator keys ─────────────────────────────────────────────
const ALLOWED_BEHAVIORAL_INDICATORS = [
    // Engagement (system-logged)
    'task_completion_rate',
    'time_on_task',
    'module_attempt_frequency',
    'response_rate',
    'exp_accumulation_rate',
    // Self-Regulation (teacher-observed)
    'task_initiation',
    'task_persistence',
    'consistency_of_completion',
    'responsiveness_to_feedback',
    'frustration_management',
];

/**
 * Insert one behavioral log row.
 *
 * @param PDO         $pdo            Active PDO connection.
 * @param int         $student_id     students.id
 * @param string      $log_type       'engagement' or 'self_regulation'
 * @param string      $indicator_key  One of the 10 thesis-defined keys.
 * @param string      $indicator_value Free-text or numeric value.
 * @param string      $logged_by      'system' or 'teacher'
 * @param int|null    $teacher_id     teachers.id (required when logged_by='teacher')
 * @param string|null $session_date   Y-m-d format; defaults to today (UTC) if null.
 * @param string      $source         'quiz', 'activity', or 'other' (default 'other')
 * @return void       Silently returns without inserting on invalid indicator_key.
 */
function log_behavior(
    PDO    $pdo,
    int    $student_id,
    string $log_type,
    string $indicator_key,
    string $indicator_value,
    string $logged_by,
    ?int   $teacher_id   = null,
    ?string $session_date = null,
    string $source       = 'other'
): void {
    // Validate indicator_key against the hardcoded allowed list
    if (!in_array($indicator_key, ALLOWED_BEHAVIORAL_INDICATORS, true)) {
        // Silently ignore unknown indicator keys
        return;
    }

    // Validate log_type
    if (!in_array($log_type, ['engagement', 'self_regulation'], true)) {
        return;
    }

    // Validate logged_by
    if (!in_array($logged_by, ['system', 'teacher'], true)) {
        return;
    }

    // Validate source
    if (!in_array($source, ['quiz', 'activity', 'other'], true)) {
        $source = 'other';
    }

    // Default session_date to today UTC
    if ($session_date === null || $session_date === '') {
        $session_date = gmdate('Y-m-d');
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $session_date)) {
        $session_date = gmdate('Y-m-d');
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO behavioral_logs
                (student_id, log_type, indicator_key, indicator_value, source,
                 session_date, logged_by, teacher_id)
             VALUES
                (:student_id, :log_type, :indicator_key, :indicator_value, :source,
                 :session_date, :logged_by, :teacher_id)'
        );
        $stmt->execute([
            ':student_id'     => $student_id,
            ':log_type'       => $log_type,
            ':indicator_key'  => $indicator_key,
            ':indicator_value'=> $indicator_value,
            ':source'         => $source,
            ':session_date'   => $session_date,
            ':logged_by'      => $logged_by,
            ':teacher_id'     => $teacher_id,
        ]);
    } catch (PDOException $e) {
        // Log the error but never bubble it up; behavioral logging is non-critical
        error_log('log_behavior insert error: ' . $e->getMessage());
    }
}
