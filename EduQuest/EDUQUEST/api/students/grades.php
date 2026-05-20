<?php
/**
 * POST   /api/students/grades.php          – Create a new grade
 * GET    /api/students/grades.php?student_id=N – List grades for a student
 * DELETE /api/students/grades.php?id=N      – Delete a grade
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../middleware/auth.php';

$teacher = requireTeacher();
$tid = (int) $teacher['id'];
$db  = getDBConnection();

// ── GET: list grades ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : null;

        $sql = "
            SELECT g.*, s.first_name, s.last_name
            FROM student_grades g
            JOIN students s ON s.id = g.student_id
            WHERE g.teacher_id = :tid
        ";
        $params = [':tid' => $tid];

        if ($studentId) {
            $sql .= " AND g.student_id = :sid";
            $params[':sid'] = $studentId;
        }
        $sql .= " ORDER BY g.graded_at DESC, g.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(true, 'Grades loaded.', ['grades' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to load grades.', [], 500);
    }
}

// ── POST: create grade ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(false, 'Invalid JSON payload.', [], 400);

        $studentId      = (int) ($input['student_id'] ?? 0);
        $courseId        = !empty($input['course_id']) ? (int) $input['course_id'] : null;
        $assessmentName = sanitizeString($input['assessment_name'] ?? '');
        $assessmentType = $input['assessment_type'] ?? 'assignment';
        $score          = isset($input['score']) ? (float) $input['score'] : null;
        $maxScore       = isset($input['max_score']) ? (float) $input['max_score'] : 100;
        $gradedAt       = $input['graded_at'] ?? date('Y-m-d');
        $remarks        = sanitizeString($input['remarks'] ?? '');

        // Validation
        $errors = [];
        if (!$studentId)      $errors[] = 'Student is required.';
        if (!$assessmentName) $errors[] = 'Assessment name is required.';
        if ($score === null || $score < 0)  $errors[] = 'Score must be 0 or greater.';
        if ($maxScore <= 0)   $errors[] = 'Max score must be greater than 0.';
        if ($score > $maxScore) $errors[] = 'Score cannot exceed max score.';

        $validTypes = ['quiz','exam','assignment','project','participation','final'];
        if (!in_array($assessmentType, $validTypes, true)) $errors[] = 'Invalid assessment type.';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $gradedAt)) $errors[] = 'Invalid date format.';

        if ($errors) jsonResponse(false, implode(' ', $errors), ['errors' => $errors], 422);

        // Verify student belongs to this teacher
        $stmt = $db->prepare("SELECT id FROM students WHERE id = :sid AND teacher_id = :tid AND is_active = 1");
        $stmt->execute([':sid' => $studentId, ':tid' => $tid]);
        if (!$stmt->fetch()) jsonResponse(false, 'Student not found or does not belong to you.', [], 404);

        // Insert
        $stmt = $db->prepare("
            INSERT INTO student_grades
                (student_id, course_id, teacher_id, assessment_name, assessment_type, score, max_score, graded_at, remarks)
            VALUES (:sid, :cid, :tid, :name, :type, :score, :max, :date, :remarks)
        ");
        $stmt->execute([
            ':sid'     => $studentId,
            ':cid'     => $courseId,
            ':tid'     => $tid,
            ':name'    => $assessmentName,
            ':type'    => $assessmentType,
            ':score'   => $score,
            ':max'     => $maxScore,
            ':date'    => $gradedAt,
            ':remarks' => $remarks ?: null,
        ]);

        $gradeId = (int) $db->lastInsertId();
        $pct = round(($score / $maxScore) * 100, 1);

        // ── Award gamification XP for this grade ──
        $xpResult = awardGradeXp($db, $studentId, $gradeId, $assessmentType, $assessmentName, $score, $maxScore, $pct, $courseId);

        // ── Notify student ────────────────────────────────────
        require_once __DIR__ . '/../notifications/send.php';
        send_notification($db, $studentId, 'student',
            'Your grade for "' . $assessmentName . '" has been posted: ' . $pct . '%',
            '../../student-dashboard/grades/grades.html');

        jsonResponse(true, 'Grade saved.', [
            'grade_id'   => $gradeId,
            'percentage' => $pct,
            'gamification' => $xpResult,
        ], 201);

    } catch (Exception $e) {
        jsonResponse(false, 'Failed to save grade.', [], 500);
    }
}

/**
 * Award XP when a teacher records a grade for a student.
 */
function awardGradeXp(PDO $db, int $studentId, int $gradeId, string $assessmentType, string $assessmentName, float $score, float $maxScore, float $pct, ?int $courseId): array {
    try {
        // Check if gamification tables exist
        $stmt = $db->query("SHOW TABLES LIKE 'student_gamification'");
        if (!$stmt->fetch()) return ['xpAwarded' => 0, 'message' => 'Gamification not set up'];

        // Ensure gamification profile
        $stmt = $db->prepare('SELECT * FROM student_gamification WHERE student_id = :sid');
        $stmt->execute([':sid' => $studentId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            $stmt = $db->prepare('INSERT INTO student_gamification (student_id) VALUES (:sid)');
            $stmt->execute([':sid' => $studentId]);
            $stmt = $db->prepare('SELECT * FROM student_gamification WHERE student_id = :sid');
            $stmt->execute([':sid' => $studentId]);
            $profile = $stmt->fetch();
        }

        // Base XP by assessment type
        $baseXpMap = [
            'quiz'          => 25,
            'assignment'    => 30,
            'project'       => 60,
            'exam'          => 50,
            'participation' => 15,
            'final'         => 80,
        ];
        $baseXp = $baseXpMap[$assessmentType] ?? 20;

        // Performance multiplier
        $multiplier = 1.0;
        if ($pct >= 100) $multiplier = 2.0;
        elseif ($pct >= 90) $multiplier = 1.5;
        elseif ($pct >= 80) $multiplier = 1.25;
        elseif ($pct >= 70) $multiplier = 1.0;
        elseif ($pct >= 60) $multiplier = 0.75;
        else $multiplier = 0.5;

        $xpToAward = max(1, (int) round($baseXp * $multiplier));

        // Apply teacher XP multiplier
        $stmt = $db->prepare('SELECT teacher_id FROM students WHERE id = :sid');
        $stmt->execute([':sid' => $studentId]);
        $sData = $stmt->fetch();
        $sTeacherId = $sData ? (int) $sData['teacher_id'] : null;

        if ($sTeacherId) {
            $stmt = $db->prepare('SELECT xp_multiplier, max_daily_xp FROM gamification_settings WHERE teacher_id = :tid AND course_id IS NULL LIMIT 1');
            $stmt->execute([':tid' => $sTeacherId]);
            $settings = $stmt->fetch();
            if ($settings) {
                $xpToAward = (int) round($xpToAward * (float) $settings['xp_multiplier']);
            }

            // Apply per-student xp_multiplier override if it exists
            try {
                $stmt = $db->prepare("SELECT setting_value FROM student_settings_overrides WHERE student_id = :sid AND teacher_id = :tid AND setting_key = 'xp_multiplier'");
                $stmt->execute([':sid' => $studentId, ':tid' => $sTeacherId]);
                $ovr = $stmt->fetch();
                if ($ovr) {
                    // Recalculate from base (undo global multiplier, apply override)
                    $globalMult = $settings ? (float) $settings['xp_multiplier'] : 1.0;
                    if ($globalMult > 0) {
                        $baseXp = (int) round($xpToAward / $globalMult);
                        $xpToAward = (int) round($baseXp * (float) $ovr['setting_value']);
                    }
                }
            } catch (Exception $e) {
                // Table may not exist yet; continue with global settings
            }
        }

        // Build description
        $desc = "Grade recorded: {$assessmentName} ({$assessmentType}) — " . round($pct) . '%';
        if ($pct >= 100) $desc .= ' 💯 Perfect!';

        // Record XP transaction
        $stmt = $db->prepare("
            INSERT INTO xp_transactions (student_id, xp_amount, source_type, source_id, description, course_id)
            VALUES (:sid, :xp, 'activity', :gid, :desc, :cid)
        ");
        $stmt->execute([
            ':sid'  => $studentId,
            ':xp'   => $xpToAward,
            ':gid'  => $gradeId,
            ':desc' => $desc,
            ':cid'  => $courseId,
        ]);

        // Update profile
        $newTotalXp  = (int) $profile['total_xp'] + $xpToAward;
        $newLevel    = 1;
        while ((50 * pow($newLevel, 2) + 50 * $newLevel) <= $newTotalXp) $newLevel++;
        $newEggStage = $newLevel >= 20 ? 5 : ($newLevel >= 12 ? 4 : ($newLevel >= 7 ? 3 : ($newLevel >= 3 ? 2 : 1)));

        $today = date('Y-m-d');
        $dailyXp = ($profile['daily_xp_date'] === $today) ? (int) $profile['daily_xp_earned'] + $xpToAward : $xpToAward;

        $stmt = $db->prepare("
            UPDATE student_gamification
            SET total_xp = :xp, current_level = :lvl, egg_stage = :egg,
                daily_xp_earned = :daily, daily_xp_date = :ddate,
                last_activity_date = :today
            WHERE student_id = :sid
        ");
        $stmt->execute([
            ':xp'    => $newTotalXp,
            ':lvl'   => $newLevel,
            ':egg'   => $newEggStage,
            ':daily' => $dailyXp,
            ':ddate' => $today,
            ':today' => $today,
            ':sid'   => $studentId,
        ]);

        return [
            'xpAwarded'  => $xpToAward,
            'totalXp'    => $newTotalXp,
            'level'      => $newLevel,
            'eggStage'   => $newEggStage,
            'leveledUp'  => $newLevel > (int) $profile['current_level'],
            'eggEvolved' => $newEggStage > (int) $profile['egg_stage'],
        ];
    } catch (Exception $e) {
        // Gamification failure should not block grade saving
        return ['xpAwarded' => 0, 'message' => 'Gamification update skipped'];
    }
}

// ── DELETE: remove grade ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $gradeId = (int) ($_GET['id'] ?? 0);
        if (!$gradeId) jsonResponse(false, 'Grade ID is required.', [], 400);

        $stmt = $db->prepare("DELETE FROM student_grades WHERE id = :id AND teacher_id = :tid");
        $stmt->execute([':id' => $gradeId, ':tid' => $tid]);

        if ($stmt->rowCount() === 0) jsonResponse(false, 'Grade not found.', [], 404);

        jsonResponse(true, 'Grade deleted.');
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to delete grade.', [], 500);
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
