<?php
/**
 * Teacher API – Interaction Summary (scoped to teacher's students)
 * GET /EDUQUEST/api/teacher_interaction_summary.php?section=<name>
 *
 * Sections (same response shape as super_admin_summary.php for JS compatibility):
 *   interaction_pages    — top pages by time spent (teacher's students only)
 *   interaction_clicks   — top clicked elements (teacher's students only)
 *   interaction_hovers   — top hovered elements (teacher's students only)
 *   interaction_scores   — composite engagement scores (teacher's students only)
 *
 * Returns: { section: "...", data: ... }
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
$teacherId = (int) $user['id'];

$db = getDBConnection();

$allowed = ['interaction_pages', 'interaction_clicks', 'interaction_hovers', 'interaction_scores'];
$section = trim($_GET['section'] ?? '');

if (!in_array($section, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid section.']);
    exit;
}

function respond_teacher(string $section, $data): void {
    echo json_encode(['section' => $section, 'data' => $data]);
    exit;
}

// ── interaction_pages ────────────────────────────────────────────────────────
if ($section === 'interaction_pages') {
    $stmt = $db->prepare("
        SELECT
            page_name,
            COUNT(*)                               AS visit_count,
            COALESCE(SUM(duration_seconds), 0)     AS total_seconds
        FROM page_sessions
        WHERE student_id IN (SELECT id FROM students WHERE teacher_id = :tid)
        GROUP BY page_name
        ORDER BY total_seconds DESC
        LIMIT 5
    ");
    $stmt->execute([':tid' => $teacherId]);
    $topPages = $stmt->fetchAll();

    respond_teacher($section, ['top_pages' => $topPages]);
}

// ── interaction_clicks ────────────────────────────────────────────────────────
if ($section === 'interaction_clicks') {
    $stmt = $db->prepare("
        SELECT
            element_label,
            SUM(click_count) AS total_clicks
        FROM click_events
        WHERE student_id IN (SELECT id FROM students WHERE teacher_id = :tid)
        GROUP BY element_label
        ORDER BY total_clicks DESC
        LIMIT 10
    ");
    $stmt->execute([':tid' => $teacherId]);
    $topClicks = $stmt->fetchAll();

    respond_teacher($section, ['top_elements' => $topClicks]);
}

// ── interaction_hovers ────────────────────────────────────────────────────────
if ($section === 'interaction_hovers') {
    $stmt = $db->prepare("
        SELECT
            element_label,
            SUM(total_hover_ms) AS total_ms
        FROM hover_events
        WHERE student_id IN (SELECT id FROM students WHERE teacher_id = :tid)
        GROUP BY element_label
        ORDER BY total_ms DESC
        LIMIT 5
    ");
    $stmt->execute([':tid' => $teacherId]);
    $topHovers = $stmt->fetchAll();

    respond_teacher($section, ['top_elements' => $topHovers]);
}

// ── interaction_scores ────────────────────────────────────────────────────────
if ($section === 'interaction_scores') {
    $stmt = $db->prepare("
        SELECT
            s.id                                    AS student_id,
            CONCAT(s.first_name, ' ', s.last_name)  AS student_name,
            COALESCE(SUM(ce.click_count), 0)         AS total_clicks,
            COALESCE(SUM(ps.duration_seconds), 0)    AS total_time_seconds,
            COALESCE(COUNT(DISTINCT qi.id), 0)       AS questions_attempted
        FROM students s
        LEFT JOIN click_events ce          ON ce.student_id = s.id
        LEFT JOIN page_sessions ps         ON ps.student_id = s.id
        LEFT JOIN question_interactions qi ON qi.student_id = s.id
        WHERE s.teacher_id = :tid
        GROUP BY s.id, s.first_name, s.last_name
    ");
    $stmt->execute([':tid' => $teacherId]);
    $rows = $stmt->fetchAll();

    // Compute raw scores
    $maxRaw = 0;
    foreach ($rows as &$r) {
        $r['raw_score'] = ($r['total_clicks'] * 0.3)
                        + ($r['total_time_seconds'] * 0.4)
                        + ($r['questions_attempted'] * 0.3);
        if ($r['raw_score'] > $maxRaw) $maxRaw = $r['raw_score'];
    }
    unset($r);

    // Normalize to 0–100
    foreach ($rows as &$r) {
        $r['engagement_score'] = $maxRaw > 0 ? round($r['raw_score'] / $maxRaw * 100, 1) : 0;
        unset($r['raw_score']);
    }
    unset($r);

    usort($rows, function ($a, $b) { return $b['engagement_score'] <=> $a['engagement_score']; });

    respond_teacher($section, $rows);
}
