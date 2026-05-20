<?php
/**
 * Super Admin API – Behavioral Logs Fetch
 * GET  /EDUQUEST/api/super_admin_logs_fetch.php
 *
 * Query params (all optional):
 *   student_name  – partial name filter (LIKE)
 *   log_type      – 'engagement' or 'self_regulation'
 *   date_from     – Y-m-d
 *   date_to       – Y-m-d
 *
 * Returns JSON:
 * {
 *   logs: [ { student_name, indicator_key, log_type, indicator_value,
 *             session_date, logged_by, teacher_name, created_at }, ... ],
 *   engagement_summary: {
 *     <indicator_key>: { avg, max_value, max_student, min_value, min_student }
 *   }
 * }
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

// ── Database ───────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';

// ── Allowed filter values ──────────────────────────────────────────────────────
$allowedLogTypes = ['engagement', 'self_regulation'];

// ── Collect and sanitize input ─────────────────────────────────────────────────
$studentName = trim($_GET['student_name'] ?? '');
$logType     = trim($_GET['log_type']     ?? '');
$dateFrom    = trim($_GET['date_from']    ?? '');
$dateTo      = trim($_GET['date_to']      ?? '');

// Validate log_type if provided
if ($logType !== '' && !in_array($logType, $allowedLogTypes, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid log_type.']);
    exit;
}

// Validate dates if provided
if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $dateFrom = '';
}
if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $dateTo = '';
}

// ── Build logs query ───────────────────────────────────────────────────────────
try {
    $db = getDBConnection();

    $conditions = [];
    $params     = [];

    if ($logType !== '') {
        $conditions[] = 'bl.log_type = :log_type';
        $params[':log_type'] = $logType;
    }
    if ($dateFrom !== '') {
        $conditions[] = 'bl.session_date >= :date_from';
        $params[':date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $conditions[] = 'bl.session_date <= :date_to';
        $params[':date_to'] = $dateTo;
    }
    if ($studentName !== '') {
        $conditions[] = "CONCAT(s.first_name,' ',s.last_name) LIKE :student_name";
        $params[':student_name'] = '%' . $studentName . '%';
    }

    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Check if source column exists (backward compatibility) - do this BEFORE main query
    $hasSourceColumn = false;
    try {
        $testStmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'behavioral_logs' AND COLUMN_NAME = 'source'");
        $testStmt->execute();
        $hasSourceColumn = $testStmt->fetchColumn() > 0;
    } catch (Exception $e) {
        $hasSourceColumn = false;
    }

    // Build SELECT clause based on whether source column exists
    $sourceSelect = $hasSourceColumn ? "COALESCE(bl.source, 'other') AS source," : "'other' AS source,";

    $sql = "
        SELECT
            bl.id,
            CONCAT(s.first_name, ' ', s.last_name)   AS student_name,
            bl.log_type,
            bl.indicator_key,
            bl.indicator_value,
            {$sourceSelect}
            bl.session_date,
            bl.logged_by,
            CASE
                WHEN bl.logged_by = 'teacher' AND t.id IS NOT NULL
                THEN CONCAT(t.first_name, ' ', t.last_name)
                ELSE NULL
            END                                       AS teacher_name,
            bl.created_at
        FROM behavioral_logs bl
        JOIN students s ON s.id = bl.student_id
        LEFT JOIN teachers t ON t.id = bl.teacher_id
        {$where}
        ORDER BY bl.session_date DESC, bl.created_at DESC
        LIMIT 2000
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // ── Engagement summary ─────────────────────────────────────────────────────
    $engagementKeys = [
        'task_completion_rate',
        'time_on_task',
        'module_attempt_frequency',
        'response_rate',
        'exp_accumulation_rate',
    ];

    $engagementSummary = [];
    
    $sources = $hasSourceColumn ? ['quiz', 'activity'] : ['other'];

    foreach ($engagementKeys as $ek) {
        $engagementSummary[$ek] = [];
        
        foreach ($sources as $src) {
            $sourceFilter = $hasSourceColumn ? "AND bl.source = :src" : "";
            $sourceParam = $hasSourceColumn ? [':src' => $src] : [];
            
            // Only numeric values are meaningful for avg/min/max
            $sumSql = "
                SELECT
                    AVG(CAST(bl.indicator_value AS DECIMAL(12,4)))  AS avg_val,
                    MAX(CAST(bl.indicator_value AS DECIMAL(12,4)))  AS max_val,
                    MIN(CAST(bl.indicator_value AS DECIMAL(12,4)))  AS min_val
                FROM behavioral_logs bl
                WHERE bl.log_type = 'engagement'
                  AND bl.indicator_key = :ek
                  {$sourceFilter}
                  AND bl.indicator_value REGEXP '^[0-9]+(\\.[0-9]+)?$'
            ";
            $sumStmt = $db->prepare($sumSql);
            $execParams = array_merge([':ek' => $ek], $sourceParam);
            $sumStmt->execute($execParams);
            $agg = $sumStmt->fetch();

            if ($agg && $agg['avg_val'] !== null) {
                // Student with max value
                $hiStmt = $db->prepare("
                    SELECT CONCAT(s.first_name,' ',s.last_name) AS sname,
                           CAST(bl.indicator_value AS DECIMAL(12,4)) AS val
                    FROM behavioral_logs bl
                    JOIN students s ON s.id = bl.student_id
                    WHERE bl.log_type = 'engagement'
                      AND bl.indicator_key = :ek
                      {$sourceFilter}
                      AND bl.indicator_value REGEXP '^[0-9]+(\\\\.[0-9]+)?$'
                    ORDER BY val DESC
                    LIMIT 1
                ");
                $hiStmt->execute($execParams);
                $hiRow = $hiStmt->fetch();

                $loStmt = $db->prepare("
                    SELECT CONCAT(s.first_name,' ',s.last_name) AS sname,
                           CAST(bl.indicator_value AS DECIMAL(12,4)) AS val
                    FROM behavioral_logs bl
                    JOIN students s ON s.id = bl.student_id
                    WHERE bl.log_type = 'engagement'
                      AND bl.indicator_key = :ek
                      {$sourceFilter}
                      AND bl.indicator_value REGEXP '^[0-9]+(\\\\.[0-9]+)?$'
                    ORDER BY val ASC
                    LIMIT 1
                ");
                $loStmt->execute($execParams);
                $loRow = $loStmt->fetch();

                $engagementSummary[$ek][$src] = [
                    'avg'         => round((float) $agg['avg_val'], 4),
                    'max_value'   => $hiRow ? $hiRow['val']   : null,
                    'max_student' => $hiRow ? $hiRow['sname'] : null,
                    'min_value'   => $loRow ? $loRow['val']   : null,
                    'min_student' => $loRow ? $loRow['sname'] : null,
                ];
            } else {
                $engagementSummary[$ek][$src] = [
                    'avg' => null, 'max_value' => null, 'max_student' => null,
                    'min_value' => null, 'min_student' => null,
                ];
            }
        }
    }

    // Format dates for output in Philippines time (GMT+8)
    $manilaTz = new DateTimeZone('Asia/Manila');
    $utcTz = new DateTimeZone('UTC');
    $formattedLogs = array_map(function ($row) use ($manilaTz, $utcTz) {
        $createdAtOut = $row['created_at'];
        if (!empty($row['created_at'])) {
            try {
                $createdAt = new DateTime($row['created_at'], $utcTz);
                $createdAt->setTimezone($manilaTz);
                $createdAtOut = $createdAt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $createdAtOut = $row['created_at'];
            }
        }

        return [
            'student_name'    => $row['student_name'],
            'log_type'        => $row['log_type'],
            'indicator_key'   => $row['indicator_key'],
            'indicator_value' => $row['indicator_value'],
            'source'          => $row['source'],
            'session_date'    => $row['session_date'],
            'logged_by'       => $row['logged_by'],
            'teacher_name'    => $row['teacher_name'],
            'created_at'      => $createdAtOut,
        ];
    }, $logs);

    echo json_encode([
        'success'            => true,
        'logs'               => $formattedLogs,
        'engagement_summary' => $engagementSummary,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('super_admin_logs_fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
