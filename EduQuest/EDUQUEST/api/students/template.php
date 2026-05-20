<?php
/**
 * GET /api/students/template.php
 * Download a blank CSV template with headers and one example row.
 * Teachers fill this in and re-upload via the import page.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireAuth();  // Must be logged in

$filename = 'eduquest_student_import_template.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');

// BOM for Excel UTF-8 recognition
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// ── Column headers ──
fputcsv($out, [
    'first_name',
    'last_name',
    'date_of_birth',
    'gender',
    'grade_level',
    'student_id_number',
    'school_name',
    'parent_guardian_name',
    'parent_guardian_email',
    'parent_guardian_phone',
    'emergency_contact',
    'emergency_phone',
    'notes',
    // ADHD
    'adhd_type',
    'adhd_severity',
    'diagnosis_date',
    'diagnosing_professional',
    'inattention_rating',
    'hyperactivity_rating',
    'impulsivity_rating',
    // Challenges (1=yes, 0=no)
    'has_reading_difficulty',
    'has_writing_difficulty',
    'has_math_difficulty',
    'has_focus_difficulty',
    'has_organization_difficulty',
    'has_time_management_difficulty',
    'has_working_memory_issues',
    'has_emotional_regulation_issues',
    'iep_in_place',
    'section_504_in_place',
    'adhd_notes',
    // Multi-value columns — use ";;" between entries, "|" between fields
    'comorbid_conditions',
    'medications',
    'accommodations',
]);

// ── Example row ──
fputcsv($out, [
    'Jane',                         // first_name
    'Smith',                        // last_name
    '2014-03-15',                   // date_of_birth (YYYY-MM-DD)
    'female',                       // gender: male | female | non_binary | prefer_not_to_say
    'Grade 5',                      // grade_level
    'STU-2024-001',                 // student_id_number
    'Sunshine Academy',             // school_name
    'Mary Smith',                   // parent_guardian_name
    'mary.smith@email.com',         // parent_guardian_email
    '+1-555-0100',                  // parent_guardian_phone
    'John Smith',                   // emergency_contact
    '+1-555-0101',                  // emergency_phone
    'Prefers visual instructions',  // notes
    // ADHD block
    'combined_presentation',        // adhd_type: predominantly_inattentive | predominantly_hyperactive_impulsive | combined_presentation | other_specified | unspecified
    'moderate',                     // adhd_severity: mild | moderate | severe
    '2021-06-10',                   // diagnosis_date (YYYY-MM-DD)
    'Dr. A. Johnson',               // diagnosing_professional
    '4',                            // inattention_rating (1-5)
    '3',                            // hyperactivity_rating (1-5)
    '3',                            // impulsivity_rating (1-5)
    // Challenges: 1=yes, 0=no
    '1',  // has_reading_difficulty
    '1',  // has_writing_difficulty
    '0',  // has_math_difficulty
    '1',  // has_focus_difficulty
    '1',  // has_organization_difficulty
    '1',  // has_time_management_difficulty
    '1',  // has_working_memory_issues
    '0',  // has_emotional_regulation_issues
    '1',  // iep_in_place
    '0',  // section_504_in_place
    'Currently on IEP review cycle',  // adhd_notes
    // Comorbid conditions: Name|category|severity  -- separate multiple with ;;
    // Categories: neurodevelopmental|mood_disorder|anxiety_disorder|learning_disability|behavioral_disorder|sleep_disorder|sensory_processing|other
    'Generalized Anxiety Disorder|anxiety_disorder|moderate;;Dyslexia|learning_disability|mild',
    // Medications: Name|dosage|frequency -- separate multiple with ;;
    'Methylphenidate|10mg|Once daily in morning;;Sertraline|25mg|Once daily',
    // Accommodations: Title|category -- separate multiple with ;;
    // Categories: instructional|assessment|environmental|behavioral|technology|social_emotional|other
    'Extended time on tests (1.5x)|assessment;;Preferential seating near teacher|environmental;;Use of text-to-speech software|technology',
]);

// ── Instruction row ──
fputcsv($out, [
    '--- INSTRUCTIONS BELOW - DELETE THIS ROW BEFORE UPLOADING ---',
]);
fputcsv($out, [
    'FIELD',
    'ALLOWED VALUES / FORMAT',
]);
$instructions = [
    ['first_name / last_name',           'Required. Plain text.'],
    ['date_of_birth',                    'YYYY-MM-DD format. e.g. 2014-03-15'],
    ['gender',                           'male | female | non_binary | prefer_not_to_say | (leave blank)'],
    ['adhd_type',                        'predominantly_inattentive | predominantly_hyperactive_impulsive | combined_presentation | other_specified | unspecified'],
    ['adhd_severity',                    'mild | moderate | severe'],
    ['inattention/hyperactivity/impulsivity_rating', '1 (rarely) to 5 (very often). Leave blank if unknown.'],
    ['has_* flags / iep_in_place / section_504_in_place', '1 = Yes, 0 = No'],
    ['comorbid_conditions',              'Format: Name|category|severity  — separate multiple with ;;  e.g.  Anxiety Disorder|anxiety_disorder|moderate;;Dyslexia|learning_disability|mild'],
    ['medications',                      'Format: MedName|dosage|frequency — separate multiple with ;;'],
    ['accommodations',                   'Format: Title|category — separate multiple with ;;  Categories: instructional|assessment|environmental|behavioral|technology|social_emotional|other'],
    ['Condition categories',             'neurodevelopmental | mood_disorder | anxiety_disorder | learning_disability | behavioral_disorder | sleep_disorder | sensory_processing | other'],
];
foreach ($instructions as $row) {
    fputcsv($out, $row);
}

fclose($out);
exit;
