<?php
/**
 * DELETE /api/upload/delete-document.php?doc_id=123
 * Deletes a document from the database and from disk.
 * Only the teacher who uploaded the document (or owns the student) may delete it.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher = requireAuth();
$docId   = (int)($_GET['doc_id'] ?? 0);
if (!$docId) {
    jsonResponse(false, 'doc_id query param is required.', [], 400);
}

$pdo = getDBConnection();

// Fetch the document and verify the teacher owns the student
$stmt = $pdo->prepare('
    SELECT d.id, d.stored_filename, d.student_id
    FROM student_documents d
    JOIN students s ON s.id = d.student_id
    WHERE d.id = :did AND s.teacher_id = :tid
    LIMIT 1
');
$stmt->execute([':did' => $docId, ':tid' => $teacher['id']]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    jsonResponse(false, 'Document not found or access denied.', [], 404);
}

// Delete the file from disk
$filePath = UPLOAD_DIR . $doc['stored_filename'];
if (file_exists($filePath)) {
    unlink($filePath);
}

// Delete from database
$del = $pdo->prepare('DELETE FROM student_documents WHERE id = :did');
$del->execute([':did' => $docId]);

jsonResponse(true, 'Document deleted successfully.');
