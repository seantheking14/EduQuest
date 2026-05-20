<?php
/**
 * POST /api/upload/quiz_upload.php
 *
 * Accepts a .csv, .txt, or .docx quiz file, parses it into question
 * objects, and returns them as JSON.  The uploaded file is deleted
 * immediately after parsing — nothing is persisted to disk.
 *
 * Authentication: Bearer token (teacher or admin only).
 *
 * Multipart field: quiz_file
 *
 * Success response:
 *   { "success": true, "question_count": N, "skipped": N, "questions": [...] }
 *
 * Each question object:
 *   { "question": "...", "options": { "A":"", "B":"", "C":"", "D":"" }, "answer": "B" }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireTeacher();

/* ── Validate uploaded file ──────────────────────────────────── */

if (empty($_FILES['quiz_file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file field named "quiz_file" found in the request.']);
    exit;
}

$file    = $_FILES['quiz_file'];
$errCode = (int) $file['error'];

if ($errCode !== UPLOAD_ERR_OK) {
    $errMessages = [
        UPLOAD_ERR_INI_SIZE   => 'The file exceeds the server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the maximum allowed size.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: missing temporary directory.',
        UPLOAD_ERR_CANT_WRITE => 'Server error: could not write the temporary file.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by a server extension.',
    ];
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $errMessages[$errCode] ?? 'Upload failed (code ' . $errCode . ').']);
    exit;
}

$tmpPath  = $file['tmp_name'];
$origName = basename($file['name']);
$size     = (int) $file['size'];

// 5 MB limit
if ($size > 5 * 1024 * 1024) {
    @unlink($tmpPath);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File exceeds the 5 MB maximum.']);
    exit;
}

$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'txt', 'docx'], true)) {
    @unlink($tmpPath);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported file type ".' . htmlspecialchars($ext, ENT_QUOTES, 'UTF-8') . '". Please upload a .csv, .txt, or .docx file.']);
    exit;
}

/* ── Read content ────────────────────────────────────────────── */

$rawText = '';

if ($ext === 'docx') {
    if (!class_exists('ZipArchive')) {
        @unlink($tmpPath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server is missing the ZipArchive extension needed to read .docx files.']);
        exit;
    }

    $zip = new ZipArchive();
    $opened = $zip->open($tmpPath);
    if ($opened !== true) {
        @unlink($tmpPath);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Could not open the .docx file — it may be corrupted or password-protected.']);
        exit;
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
        @unlink($tmpPath);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Could not find content inside the .docx file. Please re-save it as a standard Word document.']);
        exit;
    }

    // Convert paragraph/break tags to newlines, then strip all remaining XML
    $xml     = preg_replace('/<\/w:p>/i', "\n", $xml);
    $xml     = preg_replace('/<w:br[^>]*\/>/i', "\n", $xml);
    $rawText = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');

} else {
    $rawText = file_get_contents($tmpPath);
    if ($rawText === false) {
        @unlink($tmpPath);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Could not read the uploaded file.']);
        exit;
    }
}

// Delete temp file immediately — nothing is persisted
@unlink($tmpPath);

/* ── Parse ───────────────────────────────────────────────────── */

$skipped = 0;

if ($ext === 'csv') {
    $parsed = parseCsvQuiz($rawText, $skipped);
} else {
    $parsed = parseTxtQuiz($rawText, $skipped);
}

if (count($parsed) === 0) {
    http_response_code(422);
    $detail = $skipped > 0
        ? " ({$skipped} question(s) were skipped due to formatting issues.)"
        : ' Please check that the file matches the expected format.';
    echo json_encode(['success' => false, 'message' => 'No valid questions found in the file.' . $detail]);
    exit;
}

echo json_encode([
    'success'        => true,
    'question_count' => count($parsed),
    'skipped'        => $skipped,
    'questions'      => $parsed,
]);

/* ════════════════════════════════════════════════════════════════
   PARSERS
   ════════════════════════════════════════════════════════════════ */

/**
 * Parse a CSV file.
 * Expected header (skipped): question_text,option_a,option_b,option_c,option_d,correct_answer
 */
function parseCsvQuiz(string $raw, int &$skipped): array
{
    $results = [];
    $raw     = preg_replace('/\r\n|\r/', "\n", $raw);
    $lines   = explode("\n", trim($raw));

    // Skip header row
    array_shift($lines);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') { continue; }

        // PHP's str_getcsv handles quoted fields and embedded commas
        $row = str_getcsv($line);
        if (count($row) < 6) { $skipped++; continue; }

        $qText = trim($row[0]);
        $optA  = trim($row[1]);
        $optB  = trim($row[2]);
        $optC  = trim($row[3]);
        $optD  = trim($row[4]);
        $ans   = strtoupper(trim($row[5]));

        if ($qText === '')                         { $skipped++; continue; }
        if (!in_array($ans, ['A','B','C','D'], true)) { $skipped++; continue; }

        // Need at least 2 non-empty options
        $opts = array_filter(['A' => $optA, 'B' => $optB, 'C' => $optC, 'D' => $optD]);
        if (count($opts) < 2)                      { $skipped++; continue; }

        // Correct answer's option must exist and be non-empty
        if (empty($opts[$ans]))                    { $skipped++; continue; }

        $results[] = [
            'question' => $qText,
            'options'  => ['A' => $optA, 'B' => $optB, 'C' => $optC, 'D' => $optD],
            'answer'   => $ans,
        ];
    }

    return $results;
}

/**
 * Parse a plain-text or DOCX-extracted file.
 * Blocks are separated by one or more blank lines.
 * Block format:
 *   Q: question text
 *   A: option text
 *   B: option text
 *   C: option text   (optional)
 *   D: option text   (optional)
 *   Answer: B
 */
function parseTxtQuiz(string $raw, int &$skipped): array
{
    $results = [];
    $raw     = preg_replace('/\r\n|\r/', "\n", $raw);
    // Split into blocks on two or more consecutive newlines
    $blocks  = preg_split('/\n{2,}/', trim($raw));

    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') { continue; }

        $qText   = '';
        $options = [];
        $answer  = '';

        foreach (preg_split('/\n/', $block) as $line) {
            $line = trim($line);
            if ($line === '') { continue; }

            if (preg_match('/^Q:\s*(.+)/i', $line, $m)) {
                $qText = trim($m[1]);
            } elseif (preg_match('/^([A-D]):\s*(.+)/i', $line, $m)) {
                $options[strtoupper($m[1])] = trim($m[2]);
            } elseif (preg_match('/^Answer:\s*([A-D])\b/i', $line, $m)) {
                $answer = strtoupper($m[1]);
            }
        }

        if ($qText === '')                                       { $skipped++; continue; }
        if (count($options) < 2)                                 { $skipped++; continue; }
        if ($answer === '' || !isset($options[$answer]))         { $skipped++; continue; }
        if (trim($options[$answer]) === '')                      { $skipped++; continue; }

        $results[] = [
            'question' => $qText,
            'options'  => [
                'A' => $options['A'] ?? '',
                'B' => $options['B'] ?? '',
                'C' => $options['C'] ?? '',
                'D' => $options['D'] ?? '',
            ],
            'answer' => $answer,
        ];
    }

    return $results;
}
