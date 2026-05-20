<?php
/**
 * Teacher API – Behavioral Logs Fetch
 * GET  /EDUQUEST/api/teacher_logs_fetch.php
 *
 * Query params (all optional):
 *   student_id   – int, filter to a specific student (must belong to this teacher)
 *   student_name – partial name filter (LIKE)
 *   log_type     – 'engagement' or 'self_regulation'
 *   date_from    – Y-m-d
 *   date_to      – Y-m-d
 *
 * Returns JSON:
 * {
 *   success: true,
 *   logs: [ { student_name, indicator_key, log_type, indicator_value,
 *             session_date, logged_by, teacher_name, created_at }, ... ],
 *   engagement_summary: { <key>: { avg, max_value, max_student, min_value, min_student } },
 *   students: [ { id, name }, ... ]   // for filter dropdown
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET')     { http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$user      = requireTeacher();
$teacherId = (int) $user['id']; // teachers.id (resolved by requireTeacher via requireAuth)

$db = getDBConnection();

// ── Validate query params ──────────────────────────────────────────────────────
$allowedLogTypes = ['engagement', 'self_regulation'];

$filterStudentId = isset($_GET['student_id']) && ctype_digit((string) $_GET['student_id'])
    ? (int) $_GET['student_id'] : null;
$studentName = trim($_GET['student_name'] ?? '');
$logType     = trim($_GET['log_type']     ?? '');
$dateFrom    = trim($_GET['date_from']    ?? '');
$dateTo      = trim($_GET['date_to']      ?? '');

if ($logType !== '' && !in_array($logType, $allowedLogTypes, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid log_type.']);
    exit;
}
if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = '';
if ($dateTo   !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = '';

// ── Build logs query (scoped to this teacher's students) ───────────────────────
$conditions = ['s.teacher_id = :tid'];
$params     = [':tid' => $teacherId];

if ($filterStudentId !== null) {
    $conditions[] = 'bl.student_id = :sid';
    $params[':sid'] = $filterStudentId;
}
if ($studentName !== '') {
    $conditions[] = "CONCAT(s.first_name, ' ', s.last_name) LIKE :student_name";
    $params[':student_name'] = '%' . $studentName . '%';
}
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

$where = 'WHERE ' . implode(' AND ', $conditions);

// Check if source column exists (backward compatibility) - do this BEFORE main query
$hasSourceColumn = false;
try {
    $testStmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'behavioral_logs' AND COLUMN_NAME = 'source'");
    $testStmt->execute();
    $hasSourceColumn = $testStmt->fetchColumn() > 0;
} catch (Exception $e) {
    $hasSourceColumn = false;
}

try {
    // Build SELECT clause based on whether source column exists
    $sourceSelect = $hasSourceColumn ? "COALESCE(bl.source, 'other') AS source," : "'other' AS source,";
    
    $stmt = $db->prepare("
        SELECT
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
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
            END AS teacher_name,
            bl.created_at
        FROM behavioral_logs bl
        JOIN students s ON s.id = bl.student_id
        LEFT JOIN teachers t ON t.id = bl.teacher_id
        {$where}
        ORDER BY bl.session_date DESC, bl.created_at DESC
        LIMIT 2000
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // ── Engagement summary (scoped to teacher's students, broken down by source) ──────────────────────
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
            
            $aggStmt = $db->prepare("
                SELECT
                    AVG(CAST(bl.indicator_value AS DECIMAL(12,4))) AS avg_val,
                    MAX(CAST(bl.indicator_value AS DECIMAL(12,4))) AS max_val,
                    MIN(CAST(bl.indicator_value AS DECIMAL(12,4))) AS min_val
                FROM behavioral_logs bl
                JOIN students s ON s.id = bl.student_id
                WHERE s.teacher_id = :tid
                  AND bl.log_type = 'engagement'
                  AND bl.indicator_key = :ek
                  {$sourceFilter}
                  AND bl.indicator_value REGEXP '^[0-9]+(\\.[0-9]+)?$'
            ");
            $execParams = array_merge([':tid' => $teacherId, ':ek' => $ek], $sourceParam);
            $aggStmt->execute($execParams);
            $agg = $aggStmt->fetch();

            if ($agg && $agg['avg_val'] !== null) {
                $hiStmt = $db->prepare("
                    SELECT CONCAT(s.first_name, ' ', s.last_name) AS sname,
                           CAST(bl.indicator_value AS DECIMAL(12,4)) AS val
                    FROM behavioral_logs bl
                    JOIN students s ON s.id = bl.student_id
                    WHERE s.teacher_id = :tid
                      AND bl.log_type = 'engagement'
                      AND bl.indicator_key = :ek
                      {$sourceFilter}
                      AND bl.indicator_value REGEXP '^[0-9]+(\\.[0-9]+)?$'
                    ORDER BY val DESC LIMIT 1
                ");
                $hiStmt->execute($execParams);
                $hiRow = $hiStmt->fetch();

                $loStmt = $db->prepare("
                    SELECT CONCAT(s.first_name, ' ', s.last_name) AS sname,
                           CAST(bl.indicator_value AS DECIMAL(12,4)) AS val
                    FROM behavioral_logs bl
                    JOIN students s ON s.id = bl.student_id
                    WHERE s.teacher_id = :tid
                      AND bl.log_type = 'engagement'
                      AND bl.indicator_key = :ek
                      {$sourceFilter}
                      AND bl.indicator_value REGEXP '^[0-9]+(\\.[0-9]+)?$'
                    ORDER BY val ASC LIMIT 1
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

    // ── Student list for filter dropdown ──────────────────────────────────────
    $stuStmt = $db->prepare("
        SELECT id, CONCAT(first_name, ' ', last_name) AS name
        FROM students
        WHERE teacher_id = :tid
        ORDER BY first_name, last_name
    ");
    $stuStmt->execute([':tid' => $teacherId]);
    $studentList = $stuStmt->fetchAll();

    // Format dates in Philippines time (GMT+8)
    $manilaTz = new DateTimeZone('Asia/Manila');
    $utcTz = new DateTimeZone('UTC');
    $formattedLogs = array_map(function ($r) use ($manilaTz, $utcTz) {
        $createdAtOut = $r['created_at'];
        if (!empty($r['created_at'])) {
            try {
                $createdAt = new DateTime($r['created_at'], $utcTz);
                $createdAt->setTimezone($manilaTz);
                $createdAtOut = $createdAt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $createdAtOut = $r['created_at'];
            }
        }

        return [
            'student_name'    => $r['student_name'],
            'log_type'        => $r['log_type'],
            'indicator_key'   => $r['indicator_key'],
            'indicator_value' => $r['indicator_value'],
            'source'          => $r['source'],
            'session_date'    => $r['session_date'],
            'logged_by'       => $r['logged_by'],
            'teacher_name'    => $r['teacher_name'],
            'created_at'      => $createdAtOut,
        ];
    }, $logs);

    echo json_encode([
        'success'            => true,
        'logs'               => $formattedLogs,
        'engagement_summary' => $engagementSummary,
        'students'           => $studentList,
    ]);

} catch (PDOException $e) {
    error_log('teacher_logs_fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
