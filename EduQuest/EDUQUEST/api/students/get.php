<?php
/**
 * GET /api/students/get.php?id=123
 * Return full profile for a single student (ADHD, comorbidities, meds, accommodations, docs).
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher   = requireAuth();
$studentId = (int)($_GET['id'] ?? 0);
if (!$studentId) jsonResponse(false, 'Student ID is required.', [], 400);

try {
    $pdo     = getDBConnection();
    $student = requireStudentAccess($studentId, $teacher);

    // Fetch linked user email (if account exists)
    if (!empty($student['user_id'])) {
        $userStmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $userStmt->execute([$student['user_id']]);
        $userRow = $userStmt->fetch();
        $student['student_email'] = $userRow ? $userRow['email'] : null;
    } else {
        $student['student_email'] = null;
    }

    // ADHD Profile
    $adhdStmt = $pdo->prepare('SELECT * FROM adhd_profiles WHERE student_id = ?');
    $adhdStmt->execute([$studentId]);
    $adhdProfile = $adhdStmt->fetch() ?: null;

    // Comorbid Conditions
    $condStmt = $pdo->prepare('SELECT * FROM comorbid_conditions WHERE student_id = ? ORDER BY condition_name');
    $condStmt->execute([$studentId]);
    $conditions = $condStmt->fetchAll();

    // Medications
    $medStmt = $pdo->prepare('SELECT * FROM medications WHERE student_id = ? ORDER BY is_current DESC, medication_name');
    $medStmt->execute([$studentId]);
    $medications = $medStmt->fetchAll();

    // Accommodations
    $acStmt = $pdo->prepare('SELECT * FROM accommodations WHERE student_id = ? ORDER BY category, title');
    $acStmt->execute([$studentId]);
    $accommodations = $acStmt->fetchAll();

    // Teacher Notes (latest 10)
    $noteStmt = $pdo->prepare('
        SELECT tn.*, CONCAT(t.first_name, \' \', t.last_name) AS teacher_name
        FROM teacher_notes tn
        JOIN teachers t ON t.id = tn.teacher_id
        WHERE tn.student_id = ?
        ORDER BY tn.note_date DESC, tn.created_at DESC
        LIMIT 10
    ');
    $noteStmt->execute([$studentId]);
    $notes = $noteStmt->fetchAll();

    // Documents — qualify all column names to avoid ambiguity with the teachers JOIN
    $docStmt = $pdo->prepare('
        SELECT sd.id, sd.document_type, sd.title, sd.original_filename,
               sd.file_size, sd.mime_type, sd.notes, sd.uploaded_at,
               CONCAT(t.first_name, \' \', t.last_name) AS uploaded_by_name
        FROM student_documents sd
        JOIN teachers t ON t.id = sd.uploaded_by
        WHERE sd.student_id = ?
        ORDER BY sd.uploaded_at DESC
    ');
    $docStmt->execute([$studentId]);
    $documents = $docStmt->fetchAll();

    // Remove sensitive stored filename from listing
    foreach ($documents as &$doc) { unset($doc['stored_filename']); }
    unset($doc);

} catch (Exception $e) {
    jsonResponse(false, 'Failed to load student profile. Please try again.', [], 500);
}

jsonResponse(true, 'Student profile retrieved.', [
    'student'           => $student,
    'adhd_profile'      => $adhdProfile,
    'comorbid_conditions' => $conditions,
    'medications'       => $medications,
    'accommodations'    => $accommodations,
    'recent_notes'      => $notes,
    'documents'         => $documents,
]);
