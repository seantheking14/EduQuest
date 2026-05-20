<?php
/**
 * SPED Plans API
 *
 * GET  /api/students/plans.php?student_id=X
 *      Returns {iep, itp, profile} for the student (null if not yet created).
 *
 * POST /api/students/plans.php?student_id=X
 *      Body: { type: 'iep'|'itp'|'profile', ...fields }
 *      Upserts the plan record and returns the saved data.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher   = requireAuth();
$studentId = (int)($_GET['student_id'] ?? 0);
if (!$studentId) jsonResponse(false, 'student_id is required.', [], 400);

$pdo = getDBConnection();
requireStudentAccess($studentId, $teacher);

// ── GET ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(true, 'OK', [
        'iep'     => fetchPlan($pdo, 'student_iep',                $studentId),
        'itp'     => fetchPlan($pdo, 'student_itp',                $studentId),
        'profile' => fetchPlan($pdo, 'student_individual_profile',  $studentId),
    ]);
}

// ── POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $type = $body['type'] ?? '';

    switch ($type) {
        case 'iep':     saveIep($pdo, $studentId, $body);     break;
        case 'itp':     saveItp($pdo, $studentId, $body);     break;
        case 'profile': saveSip($pdo, $studentId, $body);     break;
        default:        jsonResponse(false, 'Invalid plan type. Must be iep, itp, or profile.', [], 400);
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);

// ── Helpers ───────────────────────────────────────────────────

function fetchPlan($pdo, $table, $studentId) {
    $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE student_id = :sid LIMIT 1");
    $stmt->execute([':sid' => $studentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function s($body, $key) {
    return isset($body[$key]) && $body[$key] !== '' ? sanitizeString($body[$key]) : null;
}

function saveIep($pdo, $studentId, $body) {
    $sql = '
        INSERT INTO student_iep (
            student_id, entry_method,
            effective_date, review_date, meeting_date,
            disability_classification, sped_category, iep_team,
            plep_academic, plep_functional, plep_social,
            annual_goals, short_term_objectives,
            sped_services, related_services,
            accommodations_notes, modifications_notes,
            regular_ed_percentage, assessment_accommodations,
            transition_services, additional_notes
        ) VALUES (
            :sid, :em,
            :ed, :rd, :md,
            :dc, :sc, :team,
            :pa, :pf, :ps,
            :ag, :sto,
            :ss, :rs,
            :an, :mn,
            :rep, :aa,
            :ts, :notes
        )
        ON DUPLICATE KEY UPDATE
            entry_method              = VALUES(entry_method),
            effective_date            = VALUES(effective_date),
            review_date               = VALUES(review_date),
            meeting_date              = VALUES(meeting_date),
            disability_classification = VALUES(disability_classification),
            sped_category             = VALUES(sped_category),
            iep_team                  = VALUES(iep_team),
            plep_academic             = VALUES(plep_academic),
            plep_functional           = VALUES(plep_functional),
            plep_social               = VALUES(plep_social),
            annual_goals              = VALUES(annual_goals),
            short_term_objectives     = VALUES(short_term_objectives),
            sped_services             = VALUES(sped_services),
            related_services          = VALUES(related_services),
            accommodations_notes      = VALUES(accommodations_notes),
            modifications_notes       = VALUES(modifications_notes),
            regular_ed_percentage     = VALUES(regular_ed_percentage),
            assessment_accommodations = VALUES(assessment_accommodations),
            transition_services       = VALUES(transition_services),
            additional_notes          = VALUES(additional_notes),
            updated_at                = CURRENT_TIMESTAMP
    ';

    $pct = is_numeric($body['regular_ed_percentage'] ?? null)
        ? max(0, min(100, (int)$body['regular_ed_percentage']))
        : null;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sid'   => $studentId,
        ':em'    => 'manual',
        ':ed'    => ($body['effective_date'] ?? '') ?: null,
        ':rd'    => ($body['review_date']    ?? '') ?: null,
        ':md'    => ($body['meeting_date']   ?? '') ?: null,
        ':dc'    => s($body, 'disability_classification'),
        ':sc'    => s($body, 'sped_category'),
        ':team'  => s($body, 'iep_team'),
        ':pa'    => s($body, 'plep_academic'),
        ':pf'    => s($body, 'plep_functional'),
        ':ps'    => s($body, 'plep_social'),
        ':ag'    => s($body, 'annual_goals'),
        ':sto'   => s($body, 'short_term_objectives'),
        ':ss'    => s($body, 'sped_services'),
        ':rs'    => s($body, 'related_services'),
        ':an'    => s($body, 'accommodations_notes'),
        ':mn'    => s($body, 'modifications_notes'),
        ':rep'   => $pct,
        ':aa'    => s($body, 'assessment_accommodations'),
        ':ts'    => s($body, 'transition_services'),
        ':notes' => s($body, 'additional_notes'),
    ]);

    jsonResponse(true, 'IEP saved.', ['plan' => fetchPlan($pdo, 'student_iep', $studentId)]);
}

function saveItp($pdo, $studentId, $body) {
    $sql = '
        INSERT INTO student_itp (
            student_id, entry_method,
            effective_date, graduation_date, disability_category,
            career_interests, assessed_strengths, work_experiences,
            community_experiences, daily_living_skills,
            goal_postsecondary_education, goal_employment,
            goal_independent_living, goal_community,
            services_instruction, services_community,
            services_employment, services_adult_living,
            course_of_study, agency_linkages,
            annual_goals_transition, additional_notes
        ) VALUES (
            :sid, :em,
            :ed, :gd, :dc,
            :ci, :as, :we,
            :ce, :dl,
            :ge, :ge2,
            :gi, :gc,
            :si, :sc,
            :se, :sa,
            :cos, :al,
            :agt, :notes
        )
        ON DUPLICATE KEY UPDATE
            entry_method                 = VALUES(entry_method),
            effective_date               = VALUES(effective_date),
            graduation_date              = VALUES(graduation_date),
            disability_category          = VALUES(disability_category),
            career_interests             = VALUES(career_interests),
            assessed_strengths           = VALUES(assessed_strengths),
            work_experiences             = VALUES(work_experiences),
            community_experiences        = VALUES(community_experiences),
            daily_living_skills          = VALUES(daily_living_skills),
            goal_postsecondary_education = VALUES(goal_postsecondary_education),
            goal_employment              = VALUES(goal_employment),
            goal_independent_living      = VALUES(goal_independent_living),
            goal_community               = VALUES(goal_community),
            services_instruction         = VALUES(services_instruction),
            services_community           = VALUES(services_community),
            services_employment          = VALUES(services_employment),
            services_adult_living        = VALUES(services_adult_living),
            course_of_study              = VALUES(course_of_study),
            agency_linkages              = VALUES(agency_linkages),
            annual_goals_transition      = VALUES(annual_goals_transition),
            additional_notes             = VALUES(additional_notes),
            updated_at                   = CURRENT_TIMESTAMP
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sid'   => $studentId,
        ':em'    => 'manual',
        ':ed'    => ($body['effective_date']   ?? '') ?: null,
        ':gd'    => ($body['graduation_date']  ?? '') ?: null,
        ':dc'    => s($body, 'disability_category'),
        ':ci'    => s($body, 'career_interests'),
        ':as'    => s($body, 'assessed_strengths'),
        ':we'    => s($body, 'work_experiences'),
        ':ce'    => s($body, 'community_experiences'),
        ':dl'    => s($body, 'daily_living_skills'),
        ':ge'    => s($body, 'goal_postsecondary_education'),
        ':ge2'   => s($body, 'goal_employment'),
        ':gi'    => s($body, 'goal_independent_living'),
        ':gc'    => s($body, 'goal_community'),
        ':si'    => s($body, 'services_instruction'),
        ':sc'    => s($body, 'services_community'),
        ':se'    => s($body, 'services_employment'),
        ':sa'    => s($body, 'services_adult_living'),
        ':cos'   => s($body, 'course_of_study'),
        ':al'    => s($body, 'agency_linkages'),
        ':agt'   => s($body, 'annual_goals_transition'),
        ':notes' => s($body, 'additional_notes'),
    ]);

    jsonResponse(true, 'ITP saved.', ['plan' => fetchPlan($pdo, 'student_itp', $studentId)]);
}

function saveSip($pdo, $studentId, $body) {
    $allowedStyles = ['visual','auditory','kinesthetic','mixed','other'];
    $allowedSpan   = ['short','moderate','good','variable'];
    $allowedFam    = ['high','moderate','limited','unknown'];

    $learningStyle = in_array($body['learning_style'] ?? '', $allowedStyles, true)
        ? $body['learning_style'] : 'mixed';
    $attentionSpan = in_array($body['attention_span'] ?? '', $allowedSpan, true)
        ? $body['attention_span'] : 'variable';
    $familySupport = in_array($body['family_support_level'] ?? '', $allowedFam, true)
        ? $body['family_support_level'] : 'unknown';

    $yearsInSped = is_numeric($body['years_in_sped'] ?? null)
        ? max(0, min(20, (int)$body['years_in_sped']))
        : null;

    $sql = '
        INSERT INTO student_individual_profile (
            student_id, entry_method,
            disability_classification, sped_category, years_in_sped,
            preferred_name, preferred_pronouns, primary_language,
            academic_strengths, academic_challenges,
            behavioral_strengths, behavioral_challenges,
            social_strengths, social_challenges,
            learning_style, learning_style_notes, attention_span,
            communication_profile,
            motivators, triggers, calming_strategies, reinforcement_strategies,
            family_support_level, outside_services,
            student_voice, teacher_observations
        ) VALUES (
            :sid, :em,
            :dc, :sc, :yis,
            :pn, :pp, :pl,
            :as, :ac,
            :bs, :bc,
            :ss, :sch,
            :ls, :lsn, :asp,
            :cp,
            :mot, :tri, :calm, :rein,
            :fsl, :outs,
            :sv, :to
        )
        ON DUPLICATE KEY UPDATE
            entry_method              = VALUES(entry_method),
            disability_classification = VALUES(disability_classification),
            sped_category             = VALUES(sped_category),
            years_in_sped             = VALUES(years_in_sped),
            preferred_name            = VALUES(preferred_name),
            preferred_pronouns        = VALUES(preferred_pronouns),
            primary_language          = VALUES(primary_language),
            academic_strengths        = VALUES(academic_strengths),
            academic_challenges       = VALUES(academic_challenges),
            behavioral_strengths      = VALUES(behavioral_strengths),
            behavioral_challenges     = VALUES(behavioral_challenges),
            social_strengths          = VALUES(social_strengths),
            social_challenges         = VALUES(social_challenges),
            learning_style            = VALUES(learning_style),
            learning_style_notes      = VALUES(learning_style_notes),
            attention_span            = VALUES(attention_span),
            communication_profile     = VALUES(communication_profile),
            motivators                = VALUES(motivators),
            triggers                  = VALUES(triggers),
            calming_strategies        = VALUES(calming_strategies),
            reinforcement_strategies  = VALUES(reinforcement_strategies),
            family_support_level      = VALUES(family_support_level),
            outside_services          = VALUES(outside_services),
            student_voice             = VALUES(student_voice),
            teacher_observations      = VALUES(teacher_observations),
            updated_at                = CURRENT_TIMESTAMP
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sid'  => $studentId,
        ':em'   => 'manual',
        ':dc'   => s($body, 'disability_classification'),
        ':sc'   => s($body, 'sped_category'),
        ':yis'  => $yearsInSped,
        ':pn'   => s($body, 'preferred_name'),
        ':pp'   => s($body, 'preferred_pronouns'),
        ':pl'   => s($body, 'primary_language'),
        ':as'   => s($body, 'academic_strengths'),
        ':ac'   => s($body, 'academic_challenges'),
        ':bs'   => s($body, 'behavioral_strengths'),
        ':bc'   => s($body, 'behavioral_challenges'),
        ':ss'   => s($body, 'social_strengths'),
        ':sch'  => s($body, 'social_challenges'),
        ':ls'   => $learningStyle,
        ':lsn'  => s($body, 'learning_style_notes'),
        ':asp'  => $attentionSpan,
        ':cp'   => s($body, 'communication_profile'),
        ':mot'  => s($body, 'motivators'),
        ':tri'  => s($body, 'triggers'),
        ':calm' => s($body, 'calming_strategies'),
        ':rein' => s($body, 'reinforcement_strategies'),
        ':fsl'  => $familySupport,
        ':outs' => s($body, 'outside_services'),
        ':sv'   => s($body, 'student_voice'),
        ':to'   => s($body, 'teacher_observations'),
    ]);

    jsonResponse(true, 'Individual Profile saved.', ['plan' => fetchPlan($pdo, 'student_individual_profile', $studentId)]);
}
