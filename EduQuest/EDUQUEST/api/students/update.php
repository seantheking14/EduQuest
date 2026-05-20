<?php
/**
 * PUT /api/students/update.php?id=123
 * Update an existing student's full profile.
 * Accepts the same body structure as create.php.
 * Comorbid conditions, medications, and accommodations are replaced (delete + re-insert).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'], true)) {
    http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher   = requireAuth();
$studentId = (int)($_GET['id'] ?? 0);
if (!$studentId) jsonResponse(false, 'Student ID is required.', [], 400);

$pdo = getDBConnection();
requireStudentAccess($studentId, $teacher);

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$firstName    = sanitizeString($body['first_name']           ?? '');
$lastName     = sanitizeString($body['last_name']            ?? '');
$dob          = sanitizeString($body['date_of_birth']        ?? '');
$gender       = sanitizeString($body['gender']               ?? '');
$gradeLevel   = sanitizeString($body['grade_level']          ?? '');
$schoolName   = sanitizeString($body['school_name']          ?? '');
$studentIdNum = sanitizeString($body['student_id_number']    ?? '');
$parentName   = sanitizeString($body['parent_guardian_name'] ?? '');
$parentEmail  = sanitizeString($body['parent_guardian_email']?? '');
$parentPhone  = sanitizeString($body['parent_guardian_phone']?? '');
$emergContact = sanitizeString($body['emergency_contact']    ?? '');
$emergPhone   = sanitizeString($body['emergency_phone']      ?? '');
$notes        = sanitizeString($body['notes']                ?? '');

$errors = [];
if (!$firstName) $errors[] = 'First name is required.';
if (!$lastName)  $errors[] = 'Last name is required.';
if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) $errors[] = 'Date of birth must be YYYY-MM-DD.';
if ($parentEmail && !isValidEmail($parentEmail)) $errors[] = 'Parent/guardian email is invalid.';
if ($errors) jsonResponse(false, implode(' ', $errors), [], 422);

$pdo->beginTransaction();
try {
    // 1. Update student base record
    $upd = $pdo->prepare('
        UPDATE students SET
            first_name=?, last_name=?, date_of_birth=?, gender=?, grade_level=?,
            school_name=?, student_id_number=?, parent_guardian_name=?,
            parent_guardian_email=?, parent_guardian_phone=?,
            emergency_contact=?, emergency_phone=?, notes=?
        WHERE id=?
    ');
    $upd->execute([
        $firstName, $lastName, $dob ?: null, $gender ?: null, $gradeLevel ?: null,
        $schoolName ?: null, $studentIdNum ?: null, $parentName ?: null,
        $parentEmail ?: null, $parentPhone ?: null,
        $emergContact ?: null, $emergPhone ?: null, $notes ?: null,
        $studentId,
    ]);

    // 2. ADHD Profile — upsert
    $adhd = $body['adhd_profile'] ?? null;
    if ($adhd !== null) {
        $allowedTypes = [
            'predominantly_inattentive','predominantly_hyperactive_impulsive',
            'combined_presentation','other_specified','unspecified'
        ];
        $adhdType = sanitizeString($adhd['adhd_type'] ?? 'unspecified');
        if (!in_array($adhdType, $allowedTypes, true)) $adhdType = 'unspecified';
        $severity = sanitizeString($adhd['severity'] ?? 'moderate');
        if (!in_array($severity, ['mild','moderate','severe'], true)) $severity = 'moderate';

        $upsert = $pdo->prepare('
            INSERT INTO adhd_profiles
                (student_id, adhd_type, severity, diagnosis_date, diagnosing_professional,
                 inattention_rating, hyperactivity_rating, impulsivity_rating,
                 has_reading_difficulty, has_writing_difficulty, has_math_difficulty,
                 has_focus_difficulty, has_organization_difficulty,
                 has_time_management_difficulty, has_working_memory_issues,
                 has_emotional_regulation_issues, iep_in_place, section_504_in_place,
                 additional_notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                adhd_type=VALUES(adhd_type), severity=VALUES(severity),
                diagnosis_date=VALUES(diagnosis_date),
                diagnosing_professional=VALUES(diagnosing_professional),
                inattention_rating=VALUES(inattention_rating),
                hyperactivity_rating=VALUES(hyperactivity_rating),
                impulsivity_rating=VALUES(impulsivity_rating),
                has_reading_difficulty=VALUES(has_reading_difficulty),
                has_writing_difficulty=VALUES(has_writing_difficulty),
                has_math_difficulty=VALUES(has_math_difficulty),
                has_focus_difficulty=VALUES(has_focus_difficulty),
                has_organization_difficulty=VALUES(has_organization_difficulty),
                has_time_management_difficulty=VALUES(has_time_management_difficulty),
                has_working_memory_issues=VALUES(has_working_memory_issues),
                has_emotional_regulation_issues=VALUES(has_emotional_regulation_issues),
                iep_in_place=VALUES(iep_in_place),
                section_504_in_place=VALUES(section_504_in_place),
                additional_notes=VALUES(additional_notes)
        ');
        $upsert->execute([
            $studentId, $adhdType, $severity,
            sanitizeString($adhd['diagnosis_date'] ?? '') ?: null,
            sanitizeString($adhd['diagnosing_professional'] ?? '') ?: null,
            isset($adhd['inattention_rating'])  ? (int)$adhd['inattention_rating']  : null,
            isset($adhd['hyperactivity_rating']) ? (int)$adhd['hyperactivity_rating'] : null,
            isset($adhd['impulsivity_rating'])   ? (int)$adhd['impulsivity_rating']   : null,
            (int)(bool)($adhd['has_reading_difficulty']         ?? false),
            (int)(bool)($adhd['has_writing_difficulty']         ?? false),
            (int)(bool)($adhd['has_math_difficulty']            ?? false),
            (int)(bool)($adhd['has_focus_difficulty']           ?? false),
            (int)(bool)($adhd['has_organization_difficulty']    ?? false),
            (int)(bool)($adhd['has_time_management_difficulty'] ?? false),
            (int)(bool)($adhd['has_working_memory_issues']      ?? false),
            (int)(bool)($adhd['has_emotional_regulation_issues']?? false),
            (int)(bool)($adhd['iep_in_place']         ?? false),
            (int)(bool)($adhd['section_504_in_place'] ?? false),
            sanitizeString($adhd['additional_notes'] ?? '') ?: null,
        ]);
    }

    // 3. Replace comorbid conditions
    if (isset($body['comorbid_conditions'])) {
        $pdo->prepare('DELETE FROM comorbid_conditions WHERE student_id = ?')->execute([$studentId]);
        $conditions = $body['comorbid_conditions'];
        if (is_array($conditions)) {
            $condStmt = $pdo->prepare('
                INSERT INTO comorbid_conditions
                    (student_id, condition_name, condition_category, severity,
                     diagnosed_by, diagnosis_date, is_current, notes)
                VALUES (?,?,?,?,?,?,?,?)
            ');
            $allowedCategories = [
                'neurodevelopmental','mood_disorder','anxiety_disorder',
                'learning_disability','behavioral_disorder','sleep_disorder',
                'sensory_processing','other'
            ];
            foreach ($conditions as $cond) {
                $condName = sanitizeString($cond['condition_name'] ?? '');
                if (!$condName) continue;
                $condCat = sanitizeString($cond['condition_category'] ?? 'other');
                if (!in_array($condCat, $allowedCategories, true)) $condCat = 'other';
                $condSev = sanitizeString($cond['severity'] ?? '');
                $condStmt->execute([
                    $studentId, $condName, $condCat,
                    in_array($condSev, ['mild','moderate','severe'], true) ? $condSev : null,
                    sanitizeString($cond['diagnosed_by']   ?? '') ?: null,
                    sanitizeString($cond['diagnosis_date'] ?? '') ?: null,
                    (int)(bool)($cond['is_current'] ?? true),
                    sanitizeString($cond['notes'] ?? '') ?: null,
                ]);
            }
        }
    }

    // 4. Replace medications
    if (isset($body['medications'])) {
        $pdo->prepare('DELETE FROM medications WHERE student_id = ?')->execute([$studentId]);
        $meds = $body['medications'];
        if (is_array($meds)) {
            $medStmt = $pdo->prepare('
                INSERT INTO medications
                    (student_id, medication_name, dosage, frequency,
                     prescribing_doctor, start_date, end_date, is_current, side_effects_notes)
                VALUES (?,?,?,?,?,?,?,?,?)
            ');
            foreach ($meds as $med) {
                $medName = sanitizeString($med['medication_name'] ?? '');
                if (!$medName) continue;
                $medStmt->execute([
                    $studentId, $medName,
                    sanitizeString($med['dosage']             ?? '') ?: null,
                    sanitizeString($med['frequency']          ?? '') ?: null,
                    sanitizeString($med['prescribing_doctor'] ?? '') ?: null,
                    sanitizeString($med['start_date']         ?? '') ?: null,
                    sanitizeString($med['end_date']           ?? '') ?: null,
                    (int)(bool)($med['is_current'] ?? true),
                    sanitizeString($med['side_effects_notes'] ?? '') ?: null,
                ]);
            }
        }
    }

    // 5. Replace accommodations
    if (isset($body['accommodations'])) {
        $pdo->prepare('DELETE FROM accommodations WHERE student_id = ?')->execute([$studentId]);
        $accoms = $body['accommodations'];
        if (is_array($accoms)) {
            $acStmt = $pdo->prepare('
                INSERT INTO accommodations (student_id, category, title, description, is_active)
                VALUES (?,?,?,?,?)
            ');
            $allowedAccomCats = [
                'instructional','assessment','environmental',
                'behavioral','technology','social_emotional','other'
            ];
            foreach ($accoms as $ac) {
                $acTitle = sanitizeString($ac['title'] ?? '');
                if (!$acTitle) continue;
                $acCat = sanitizeString($ac['category'] ?? 'other');
                if (!in_array($acCat, $allowedAccomCats, true)) $acCat = 'other';
                $acStmt->execute([
                    $studentId, $acCat, $acTitle,
                    sanitizeString($ac['description'] ?? '') ?: null,
                    (int)(bool)($ac['is_active'] ?? true),
                ]);
            }
        }
    }

    $pdo->commit();
    jsonResponse(true, 'Student profile updated successfully.');

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(false, 'Failed to update student profile. Please try again.', [], 500);
}
