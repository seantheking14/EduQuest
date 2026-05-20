<?php
/**
 * POST /api/students/import.php
 * Bulk-import students from a CSV file.
 *
 * Expects multipart/form-data:
 *   file      - the CSV file
 *   preview   - "1" to validate and return a preview without saving
 *
 * CSV columns (see /api/students/template.php for the template):
 *   first_name, last_name, date_of_birth, gender, grade_level,
 *   student_id_number, school_name,
 *   parent_guardian_name, parent_guardian_email, parent_guardian_phone,
 *   emergency_contact, emergency_phone, notes,
 *   adhd_type, adhd_severity, diagnosis_date, diagnosing_professional,
 *   inattention_rating, hyperactivity_rating, impulsivity_rating,
 *   has_reading_difficulty, has_writing_difficulty, has_math_difficulty,
 *   has_focus_difficulty, has_organization_difficulty,
 *   has_time_management_difficulty, has_working_memory_issues,
 *   has_emotional_regulation_issues, iep_in_place, section_504_in_place,
 *   adhd_notes,
 *   comorbid_conditions  (pipe-separated: "Name|category|severity" per condition, conditions separated by ";;")
 *   medications          (pipe-separated: "Name|dosage|frequency" per med,  meds separated by ";;")
 *   accommodations       (pipe-separated: "Title|category" per item, items separated by ";;")
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher    = requireAuth();
$previewOnly = (($_REQUEST['preview'] ?? '0') === '1');

// ── File validation ──
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'No file uploaded or upload error.', [], 400);
}

$file     = $_FILES['file'];
$tmpPath  = $file['tmp_name'];
$origName = basename($file['name']);

// Accept CSV and Excel-saved-as-CSV
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);
$allowedMimes = [
    'text/plain', 'text/csv', 'application/csv',
    'application/vnd.ms-excel',   // some systems report CSV this way
    'application/octet-stream',
];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if ($ext !== 'csv' && $ext !== 'txt') {
    jsonResponse(false, 'Only CSV files are accepted. Please use the provided template.', [], 415);
}
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(false, 'File too large. Maximum CSV size is 5 MB.', [], 413);
}

// ── Parse CSV ──
$handle = fopen($tmpPath, 'r');
if (!$handle) jsonResponse(false, 'Could not open uploaded file.', [], 500);

// Read header row
$headers = fgetcsv($handle, 4096, ',');
if (!$headers) {
    fclose($handle);
    jsonResponse(false, 'CSV file appears to be empty.', [], 422);
}

// Normalize headers (trim, lowercase)
$headers = array_map(fn($h) => strtolower(trim((string)$h)), $headers);

// Required columns
$requiredCols = ['first_name', 'last_name'];
$missingCols  = array_diff($requiredCols, $headers);
if ($missingCols) {
    fclose($handle);
    jsonResponse(false, 'CSV is missing required columns: ' . implode(', ', $missingCols) . '. Please use the provided template.', [], 422);
}

// ── Valid enum values ──
$validGenders     = ['male','female','non_binary','prefer_not_to_say',''];
$validAdhdTypes   = ['predominantly_inattentive','predominantly_hyperactive_impulsive','combined_presentation','other_specified','unspecified',''];
$validSeverities  = ['mild','moderate','severe',''];
$validCondCats    = ['neurodevelopmental','mood_disorder','anxiety_disorder','learning_disability','behavioral_disorder','sleep_disorder','sensory_processing','other'];
$validAccomCats   = ['instructional','assessment','environmental','behavioral','technology','social_emotional','other'];

// ── Process rows ──
$rows        = [];
$previewRows = [];
$errors      = [];
$rowNum      = 1;

while (($values = fgetcsv($handle, 4096, ',')) !== false) {
    $rowNum++;

    // Skip blank rows
    if (count(array_filter(array_map('trim', $values))) === 0) continue;

    // Map headers → values (handle rows shorter than header count gracefully)
    $row = [];
    foreach ($headers as $i => $col) {
        $row[$col] = isset($values[$i]) ? trim((string)$values[$i]) : '';
    }

    $rowErrors = [];

    // ── Basic field validation ──
    $firstName = sanitizeString($row['first_name'] ?? '');
    $lastName  = sanitizeString($row['last_name']  ?? '');
    if (!$firstName) $rowErrors[] = 'first_name is required';
    if (!$lastName)  $rowErrors[] = 'last_name is required';

    $dob = sanitizeString($row['date_of_birth'] ?? '');
    if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $rowErrors[] = 'date_of_birth must be YYYY-MM-DD';
        $dob = '';
    }

    $parentEmail = sanitizeString($row['parent_guardian_email'] ?? '');
    if ($parentEmail && !isValidEmail($parentEmail)) {
        $rowErrors[] = 'parent_guardian_email is invalid';
        $parentEmail = '';
    }

    $gender = strtolower(sanitizeString($row['gender'] ?? ''));
    if (!in_array($gender, $validGenders, true)) { $rowErrors[] = "gender '$gender' is invalid"; $gender = ''; }

    // ── ADHD ──
    $adhdType = strtolower(sanitizeString($row['adhd_type'] ?? ''));
    if ($adhdType && !in_array($adhdType, $validAdhdTypes, true)) {
        $rowErrors[] = "adhd_type '$adhdType' is invalid"; $adhdType = 'unspecified';
    }
    $adhdType = $adhdType ?: 'unspecified';

    $adhdSev = strtolower(sanitizeString($row['adhd_severity'] ?? 'moderate'));
    if (!in_array($adhdSev, ['mild','moderate','severe'], true)) $adhdSev = 'moderate';

    $inattRating  = isset($row['inattention_rating'])   && $row['inattention_rating']   !== '' ? (int)$row['inattention_rating']   : null;
    $hyperRating  = isset($row['hyperactivity_rating']) && $row['hyperactivity_rating'] !== '' ? (int)$row['hyperactivity_rating'] : null;
    $impulseRating = isset($row['impulsivity_rating'])  && $row['impulsivity_rating']  !== '' ? (int)$row['impulsivity_rating']  : null;

    foreach ([['inattention_rating', $inattRating], ['hyperactivity_rating', $hyperRating], ['impulsivity_rating', $impulseRating]] as [$rname, $rval]) {
        if ($rval !== null && ($rval < 1 || $rval > 5)) {
            $rowErrors[] = "$rname must be 1–5";
        }
    }

    // ── Parse comorbid_conditions ──
    $conditions = [];
    $condRaw = $row['comorbid_conditions'] ?? '';
    if ($condRaw) {
        foreach (explode(';;', $condRaw) as $condPart) {
            $parts   = explode('|', $condPart);
            $cName   = sanitizeString(trim($parts[0] ?? ''));
            if (!$cName) continue;
            $cCat    = strtolower(trim($parts[1] ?? 'other'));
            if (!in_array($cCat, $validCondCats, true)) $cCat = 'other';
            $cSev    = strtolower(trim($parts[2] ?? ''));
            if (!in_array($cSev, ['mild','moderate','severe'], true)) $cSev = null;
            $conditions[] = ['condition_name' => $cName, 'condition_category' => $cCat, 'severity' => $cSev];
        }
    }

    // ── Parse medications ──
    $medications = [];
    $medRaw = $row['medications'] ?? '';
    if ($medRaw) {
        foreach (explode(';;', $medRaw) as $medPart) {
            $parts   = explode('|', $medPart);
            $mName   = sanitizeString(trim($parts[0] ?? ''));
            if (!$mName) continue;
            $medications[] = [
                'medication_name' => $mName,
                'dosage'          => sanitizeString(trim($parts[1] ?? '')),
                'frequency'       => sanitizeString(trim($parts[2] ?? '')),
            ];
        }
    }

    // ── Parse accommodations ──
    $accommodations = [];
    $acRaw = $row['accommodations'] ?? '';
    if ($acRaw) {
        foreach (explode(';;', $acRaw) as $acPart) {
            $parts   = explode('|', $acPart);
            $aTitle  = sanitizeString(trim($parts[0] ?? ''));
            if (!$aTitle) continue;
            $aCat    = strtolower(trim($parts[1] ?? 'other'));
            if (!in_array($aCat, $validAccomCats, true)) $aCat = 'other';
            $accommodations[] = ['title' => $aTitle, 'category' => $aCat];
        }
    }

    // ── Collect row ──
    $parsedRow = [
        'row_number'  => $rowNum,
        'first_name'  => $firstName,
        'last_name'   => $lastName,
        'date_of_birth'         => $dob ?: null,
        'gender'                => $gender ?: null,
        'grade_level'           => sanitizeString($row['grade_level']  ?? '') ?: null,
        'student_id_number'     => sanitizeString($row['student_id_number'] ?? '') ?: null,
        'school_name'           => sanitizeString($row['school_name']  ?? '') ?: null,
        'parent_guardian_name'  => sanitizeString($row['parent_guardian_name']  ?? '') ?: null,
        'parent_guardian_email' => $parentEmail ?: null,
        'parent_guardian_phone' => sanitizeString($row['parent_guardian_phone'] ?? '') ?: null,
        'emergency_contact'     => sanitizeString($row['emergency_contact'] ?? '') ?: null,
        'emergency_phone'       => sanitizeString($row['emergency_phone']   ?? '') ?: null,
        'notes'                 => sanitizeString($row['notes'] ?? '') ?: null,
        'adhd_profile' => [
            'adhd_type'                     => $adhdType,
            'severity'                      => $adhdSev,
            'diagnosis_date'                => sanitizeString($row['diagnosis_date'] ?? '') ?: null,
            'diagnosing_professional'       => sanitizeString($row['diagnosing_professional'] ?? '') ?: null,
            'inattention_rating'            => $inattRating,
            'hyperactivity_rating'          => $hyperRating,
            'impulsivity_rating'            => $impulseRating,
            'has_reading_difficulty'        => (int)(strtolower($row['has_reading_difficulty']         ?? '') === '1' || strtolower($row['has_reading_difficulty'] ?? '') === 'yes'),
            'has_writing_difficulty'        => (int)(strtolower($row['has_writing_difficulty']         ?? '') === '1' || strtolower($row['has_writing_difficulty'] ?? '') === 'yes'),
            'has_math_difficulty'           => (int)(strtolower($row['has_math_difficulty']            ?? '') === '1' || strtolower($row['has_math_difficulty'] ?? '') === 'yes'),
            'has_focus_difficulty'          => (int)(strtolower($row['has_focus_difficulty']           ?? '') === '1' || strtolower($row['has_focus_difficulty'] ?? '') === 'yes'),
            'has_organization_difficulty'   => (int)(strtolower($row['has_organization_difficulty']    ?? '') === '1' || strtolower($row['has_organization_difficulty'] ?? '') === 'yes'),
            'has_time_management_difficulty'=> (int)(strtolower($row['has_time_management_difficulty'] ?? '') === '1' || strtolower($row['has_time_management_difficulty'] ?? '') === 'yes'),
            'has_working_memory_issues'     => (int)(strtolower($row['has_working_memory_issues']      ?? '') === '1' || strtolower($row['has_working_memory_issues'] ?? '') === 'yes'),
            'has_emotional_regulation_issues' => (int)(strtolower($row['has_emotional_regulation_issues'] ?? '') === '1' || strtolower($row['has_emotional_regulation_issues'] ?? '') === 'yes'),
            'iep_in_place'                  => (int)(strtolower($row['iep_in_place']        ?? '') === '1' || strtolower($row['iep_in_place'] ?? '') === 'yes'),
            'section_504_in_place'          => (int)(strtolower($row['section_504_in_place']?? '') === '1' || strtolower($row['section_504_in_place'] ?? '') === 'yes'),
            'additional_notes'              => sanitizeString($row['adhd_notes'] ?? '') ?: null,
        ],
        'comorbid_conditions' => $conditions,
        'medications'         => $medications,
        'accommodations'      => $accommodations,
        'row_errors'          => $rowErrors,
    ];

    if ($rowErrors) {
        $errors[] = ['row' => $rowNum, 'name' => "$firstName $lastName", 'errors' => $rowErrors];
    }

    $rows[]       = $parsedRow;
    $previewRows[] = [
        'row_number'   => $rowNum,
        'first_name'   => $firstName,
        'last_name'    => $lastName,
        'grade_level'  => $parsedRow['grade_level'],
        'school_name'  => $parsedRow['school_name'],
        'adhd_type'    => $adhdType,
        'adhd_severity'=> $adhdSev,
        'conditions'   => count($conditions),
        'medications'  => count($medications),
        'accommodations' => count($accommodations),
        'has_errors'   => !empty($rowErrors),
        'row_errors'   => $rowErrors,
    ];
}
fclose($handle);

if (empty($rows)) {
    jsonResponse(false, 'No valid data rows found in the CSV.', [], 422);
}

// ── Preview mode — return parsed data without saving ──
if ($previewOnly) {
    jsonResponse(true, 'Preview parsed successfully.', [
        'total'        => count($rows),
        'valid_rows'   => count($rows) - count($errors),
        'error_rows'   => count($errors),
        'preview'      => $previewRows,
        'errors'       => $errors,
    ]);
}

// ── Import mode — save all valid rows ──
$pdo        = getDBConnection();
$successIds = [];
$failedRows = [];

foreach ($rows as $row) {
    if (!empty($row['row_errors'])) {
        $failedRows[] = ['row' => $row['row_number'], 'name' => "{$row['first_name']} {$row['last_name']}", 'errors' => $row['row_errors']];
        continue;
    }

    $pdo->beginTransaction();
    try {
        // 1. Insert student
        $ins = $pdo->prepare('
            INSERT INTO students
                (teacher_id, first_name, last_name, date_of_birth, gender, grade_level,
                 school_name, student_id_number, parent_guardian_name, parent_guardian_email,
                 parent_guardian_phone, emergency_contact, emergency_phone, notes, import_source)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'csv\')
        ');
        $ins->execute([
            $teacher['id'], $row['first_name'], $row['last_name'],
            $row['date_of_birth'], $row['gender'], $row['grade_level'],
            $row['school_name'], $row['student_id_number'],
            $row['parent_guardian_name'], $row['parent_guardian_email'],
            $row['parent_guardian_phone'], $row['emergency_contact'],
            $row['emergency_phone'], $row['notes'],
        ]);
        $studentId = (int) $pdo->lastInsertId();

        // 2. ADHD Profile
        $ap = $row['adhd_profile'];
        $apIns = $pdo->prepare('
            INSERT INTO adhd_profiles
                (student_id, adhd_type, severity, diagnosis_date, diagnosing_professional,
                 inattention_rating, hyperactivity_rating, impulsivity_rating,
                 has_reading_difficulty, has_writing_difficulty, has_math_difficulty,
                 has_focus_difficulty, has_organization_difficulty,
                 has_time_management_difficulty, has_working_memory_issues,
                 has_emotional_regulation_issues, iep_in_place, section_504_in_place,
                 additional_notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ');
        $apIns->execute([
            $studentId, $ap['adhd_type'], $ap['severity'],
            $ap['diagnosis_date'], $ap['diagnosing_professional'],
            $ap['inattention_rating'], $ap['hyperactivity_rating'], $ap['impulsivity_rating'],
            $ap['has_reading_difficulty'], $ap['has_writing_difficulty'], $ap['has_math_difficulty'],
            $ap['has_focus_difficulty'], $ap['has_organization_difficulty'],
            $ap['has_time_management_difficulty'], $ap['has_working_memory_issues'],
            $ap['has_emotional_regulation_issues'], $ap['iep_in_place'], $ap['section_504_in_place'],
            $ap['additional_notes'],
        ]);

        // 3. Comorbid conditions
        if ($row['comorbid_conditions']) {
            $cIns = $pdo->prepare('INSERT INTO comorbid_conditions (student_id, condition_name, condition_category, severity) VALUES (?,?,?,?)');
            foreach ($row['comorbid_conditions'] as $c) {
                $cIns->execute([$studentId, $c['condition_name'], $c['condition_category'], $c['severity']]);
            }
        }

        // 4. Medications
        if ($row['medications']) {
            $mIns = $pdo->prepare('INSERT INTO medications (student_id, medication_name, dosage, frequency) VALUES (?,?,?,?)');
            foreach ($row['medications'] as $m) {
                $mIns->execute([$studentId, $m['medication_name'], $m['dosage'], $m['frequency']]);
            }
        }

        // 5. Accommodations
        if ($row['accommodations']) {
            $aIns = $pdo->prepare('INSERT INTO accommodations (student_id, category, title) VALUES (?,?,?)');
            foreach ($row['accommodations'] as $a) {
                $aIns->execute([$studentId, $a['category'], $a['title']]);
            }
        }

        $pdo->commit();
        $successIds[] = ['student_id' => $studentId, 'name' => "{$row['first_name']} {$row['last_name']}"];

    } catch (Exception $e) {
        $pdo->rollBack();
        $failedRows[] = ['row' => $row['row_number'], 'name' => "{$row['first_name']} {$row['last_name']}", 'errors' => ['Database error — row skipped.']];
    }
}

// Log the import
try {
    $pdo->prepare('
        INSERT INTO import_logs (teacher_id, import_type, filename, total_rows, success_rows, failed_rows, error_details)
        VALUES (?,\'csv\',?,?,?,?,?)
    ')->execute([
        $teacher['id'], $origName,
        count($rows), count($successIds), count($failedRows),
        json_encode($failedRows),
    ]);
} catch (Exception $e) { /* non-fatal */ }

jsonResponse(true, 'Import completed.', [
    'total_rows'    => count($rows),
    'imported'      => count($successIds),
    'failed'        => count($failedRows),
    'students'      => $successIds,
    'failed_rows'   => $failedRows,
]);
