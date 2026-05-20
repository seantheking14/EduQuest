<?php
/**
 * POST /api/students/create.php
 * Create a new student profile with ADHD details, comorbid conditions,
 * medications, and accommodations in a single atomic request.
 *
 * Expected JSON body: see documentation below.
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
require_once __DIR__ . '/../../utils/Security.php';

$teacher = requireAuth();
$body    = json_decode(file_get_contents('php://input'), true) ?? [];

// ---- Basic student fields ----
$firstName      = sanitizeString($body['first_name']           ?? '');
$lastName       = sanitizeString($body['last_name']            ?? '');
$dob            = sanitizeString($body['date_of_birth']        ?? '');
$gender         = sanitizeString($body['gender']               ?? '');
$gradeLevel     = sanitizeString($body['grade_level']          ?? '');
$schoolName     = sanitizeString($body['school_name']          ?? '');
$studentIdNum   = sanitizeString($body['student_id_number']    ?? '');
$parentName     = sanitizeString($body['parent_guardian_name'] ?? '');
$parentEmail    = sanitizeString($body['parent_guardian_email']?? '');
$parentPhone    = sanitizeString($body['parent_guardian_phone']?? '');
$emergContact   = sanitizeString($body['emergency_contact']    ?? '');
$emergPhone     = sanitizeString($body['emergency_phone']      ?? '');
$notes          = sanitizeString($body['notes']                ?? '');
$studentEmail   = sanitizeString($body['student_email']        ?? '');

// Validation
$errors = [];
if (!$firstName) $errors[] = 'First name is required.';
if (!$lastName)  $errors[] = 'Last name is required.';
if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) $errors[] = 'Date of birth must be YYYY-MM-DD.';
if ($parentEmail && !isValidEmail($parentEmail)) $errors[] = 'Parent/guardian email is invalid.';
if ($studentEmail && !isValidEmail($studentEmail)) $errors[] = 'Student email is invalid.';

$allowedGenders = ['male','female','non_binary','prefer_not_to_say',''];
if (!in_array($gender, $allowedGenders, true)) $errors[] = 'Invalid gender value.';

if ($errors) jsonResponse(false, implode(' ', $errors), [], 422);

$pdo = getDBConnection();
$pdo->beginTransaction();

try {
    // 1. Insert student
    $stmt = $pdo->prepare('
        INSERT INTO students
            (teacher_id, first_name, last_name, date_of_birth, gender, grade_level,
             school_name, student_id_number, parent_guardian_name, parent_guardian_email,
             parent_guardian_phone, emergency_contact, emergency_phone, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ');
    $stmt->execute([
        $teacher['id'], $firstName, $lastName,
        $dob ?: null, $gender ?: null, $gradeLevel ?: null,
        $schoolName ?: null, $studentIdNum ?: null,
        $parentName ?: null, $parentEmail ?: null, $parentPhone ?: null,
        $emergContact ?: null, $emergPhone ?: null, $notes ?: null,
    ]);
    $studentId = (int) $pdo->lastInsertId();

    // 2. ADHD Profile (optional block)
    $adhd = $body['adhd_profile'] ?? null;
    if ($adhd) {
        $allowedTypes = [
            'predominantly_inattentive','predominantly_hyperactive_impulsive',
            'combined_presentation','other_specified','unspecified'
        ];
        $adhdType = sanitizeString($adhd['adhd_type'] ?? 'unspecified');
        if (!in_array($adhdType, $allowedTypes, true)) $adhdType = 'unspecified';

        $severity = sanitizeString($adhd['severity'] ?? 'moderate');
        if (!in_array($severity, ['mild','moderate','severe'], true)) $severity = 'moderate';

        $adhdStmt = $pdo->prepare('
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
        $adhdStmt->execute([
            $studentId,
            $adhdType,
            $severity,
            sanitizeString($adhd['diagnosis_date'] ?? '') ?: null,
            sanitizeString($adhd['diagnosing_professional'] ?? '') ?: null,
            isset($adhd['inattention_rating'])   ? (int)$adhd['inattention_rating']   : null,
            isset($adhd['hyperactivity_rating'])  ? (int)$adhd['hyperactivity_rating']  : null,
            isset($adhd['impulsivity_rating'])    ? (int)$adhd['impulsivity_rating']    : null,
            (int)(bool)($adhd['has_reading_difficulty']         ?? false),
            (int)(bool)($adhd['has_writing_difficulty']         ?? false),
            (int)(bool)($adhd['has_math_difficulty']            ?? false),
            (int)(bool)($adhd['has_focus_difficulty']           ?? false),
            (int)(bool)($adhd['has_organization_difficulty']    ?? false),
            (int)(bool)($adhd['has_time_management_difficulty'] ?? false),
            (int)(bool)($adhd['has_working_memory_issues']      ?? false),
            (int)(bool)($adhd['has_emotional_regulation_issues']?? false),
            (int)(bool)($adhd['iep_in_place']           ?? false),
            (int)(bool)($adhd['section_504_in_place']   ?? false),
            sanitizeString($adhd['additional_notes'] ?? '') ?: null,
        ]);
    }

    // 3. Comorbid Conditions (array)
    $conditions = $body['comorbid_conditions'] ?? [];
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
            if (!in_array($condSev, ['mild','moderate','severe',''], true)) $condSev = null;
            $condStmt->execute([
                $studentId, $condName, $condCat, $condSev ?: null,
                sanitizeString($cond['diagnosed_by']   ?? '') ?: null,
                sanitizeString($cond['diagnosis_date'] ?? '') ?: null,
                (int)(bool)($cond['is_current'] ?? true),
                sanitizeString($cond['notes'] ?? '') ?: null,
            ]);
        }
    }

    // 4. Medications (array)
    $meds = $body['medications'] ?? [];
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
                sanitizeString($med['dosage']              ?? '') ?: null,
                sanitizeString($med['frequency']           ?? '') ?: null,
                sanitizeString($med['prescribing_doctor']  ?? '') ?: null,
                sanitizeString($med['start_date']          ?? '') ?: null,
                sanitizeString($med['end_date']            ?? '') ?: null,
                (int)(bool)($med['is_current'] ?? true),
                sanitizeString($med['side_effects_notes']  ?? '') ?: null,
            ]);
        }
    }

    // 5. Accommodations (array)
    $accoms = $body['accommodations'] ?? [];
    if (is_array($accoms)) {
        $acStmt = $pdo->prepare('
            INSERT INTO accommodations
                (student_id, category, title, description, is_active)
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

    $pdo->commit();

    // 6. Auto-create student login account if email was provided
    $accountCreated = false;
    $accountError   = null;
    $accountEmail   = null;

    if ($studentEmail) {
        try {
            // Check if a user with this email already exists
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)');
            $checkStmt->execute([$studentEmail]);
            $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {
                // Link existing account to this student profile
                $linkStmt = $pdo->prepare('UPDATE students SET user_id = ? WHERE id = ?');
                $linkStmt->execute([$existingUser['id'], $studentId]);
                $accountEmail = $studentEmail;
                $accountError = 'An account with this email already exists and has been linked to this student profile.';
            } else {
                // Create new user account with default password
                $defaultPassword = 'Password01!';
                $passwordHash    = hashPassword($defaultPassword);

                $userStmt = $pdo->prepare('
                    INSERT INTO users (email, password_hash, first_name, last_name, role, is_active, email_verified)
                    VALUES (?, ?, ?, ?, ?, 1, 1)
                ');
                $userStmt->execute([$studentEmail, $passwordHash, $firstName, $lastName, 'student']);
                $newUserId = (int) $pdo->lastInsertId();

                // Link the new user account to the student record
                $linkStmt = $pdo->prepare('UPDATE students SET user_id = ? WHERE id = ?');
                $linkStmt->execute([$newUserId, $studentId]);

                // Update user profile_id to point to this student
                $profileStmt = $pdo->prepare('UPDATE users SET profile_id = ? WHERE id = ?');
                $profileStmt->execute([$studentId, $newUserId]);

                $accountCreated = true;
                $accountEmail   = $studentEmail;
            }
        } catch (Exception $e) {
            // Account creation failed but student was already saved — don't lose the profile
            $accountError = 'Student profile saved but account creation failed. You can add the account later.';
        }
    }

    $responseData = ['student_id' => $studentId];
    if ($accountCreated) {
        $responseData['account_created'] = true;
        $responseData['account_email']   = $accountEmail;
    }
    if ($accountError) {
        $responseData['account_error'] = $accountError;
    }

    jsonResponse(true, 'Student profile created successfully.', $responseData, 201);

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(false, 'Failed to create student profile. Please try again.', [], 500);
}
