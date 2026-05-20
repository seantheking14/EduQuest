<?php
/**
 * attempt_gate.php — server-side attempt gating helpers
 *
 * can_attempt(PDO, int, string, int) : array
 *   Returns ['allowed' => bool, 'reason' => string, 'assignment_id' => int|null,
 *            'attempts_used' => int, 'max_attempts' => int, 'due_date' => string|null]
 *
 * start_attempt(PDO, int, string, int, int|null) : int
 *   Inserts a new attempt row and returns the new attempt ID.
 *
 * $type is one of: 'quiz' | 'game'
 * $content_id is teacher_quizzes.id  or  games.id
 */

function can_attempt(PDO $pdo, int $student_id, string $type, int $content_id): array {
    $base = [
        'allowed'        => true,
        'reason'         => '',
        'assignment_id'  => null,
        'attempts_used'  => 0,
        'max_attempts'   => 0,
        'due_date'       => null,
    ];

    if ($type === 'quiz') {
        // Look up the assignment row for this student (direct or via course enrollment)
        $stmt = $pdo->prepare("
            SELECT tqa.id, tqa.due_date, tqa.max_attempts AS assign_max,
                   tq.max_attempts AS quiz_max
            FROM teacher_quiz_assignments tqa
            JOIN teacher_quizzes tq ON tq.id = tqa.quiz_id
            WHERE tqa.quiz_id = :qid
              AND (
                  tqa.student_id = :sid
                  OR (tqa.student_id IS NULL AND tqa.course_id IN (
                      SELECT course_id FROM course_enrollments WHERE student_id = :sid2
                  ))
              )
            ORDER BY tqa.student_id DESC
            LIMIT 1
        ");
        $stmt->execute([':qid' => $content_id, ':sid' => $student_id, ':sid2' => $student_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Count completed (non-abandoned) attempts
        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) FROM teacher_quiz_attempts
            WHERE student_id = :sid AND quiz_id = :qid AND is_abandoned = 0
        ");
        $stmt2->execute([':sid' => $student_id, ':qid' => $content_id]);
        $used = (int) $stmt2->fetchColumn();

        if ($assignment) {
            $assignId = (int) $assignment['id'];
            $dueDate  = $assignment['due_date'];
            // Per-assignment override wins; fall back to quiz-level max
            $maxAttempts = (int) $assignment['assign_max'] > 0
                ? (int) $assignment['assign_max']
                : (int) $assignment['quiz_max'];

            $base['assignment_id'] = $assignId;
            $base['due_date']      = $dueDate;
            $base['attempts_used'] = $used;
            $base['max_attempts']  = $maxAttempts;

            if ($dueDate && date('Y-m-d') > $dueDate) {
                $base['allowed'] = false;
                $base['reason']  = 'Due date has passed.';
                return $base;
            }
            if ($maxAttempts > 0 && $used >= $maxAttempts) {
                $base['allowed'] = false;
                $base['reason']  = 'Maximum attempts reached.';
                return $base;
            }
        } else {
            // No assignment — check quiz-level max_attempts directly
            $stmt3 = $pdo->prepare("SELECT max_attempts FROM teacher_quizzes WHERE id = :qid");
            $stmt3->execute([':qid' => $content_id]);
            $row = $stmt3->fetch(PDO::FETCH_ASSOC);
            $maxAttempts = $row ? (int) $row['max_attempts'] : 0;

            $base['attempts_used'] = $used;
            $base['max_attempts']  = $maxAttempts;

            if ($maxAttempts > 0 && $used >= $maxAttempts) {
                $base['allowed'] = false;
                $base['reason']  = 'Maximum attempts reached.';
                return $base;
            }
        }

    } elseif ($type === 'game') {
        // Look up game assignment for this student
        $stmt = $pdo->prepare("
            SELECT id, due_date, max_attempts
            FROM game_assignments
            WHERE game_id = :gid AND student_id = :sid
            LIMIT 1
        ");
        $stmt->execute([':gid' => $content_id, ':sid' => $student_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            // No assignment — game is freely playable
            return $base;
        }

        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) FROM game_attempts
            WHERE student_id = :sid AND game_id = :gid AND is_abandoned = 0
        ");
        $stmt2->execute([':sid' => $student_id, ':gid' => $content_id]);
        $used = (int) $stmt2->fetchColumn();

        $dueDate     = $assignment['due_date'];
        $maxAttempts = (int) $assignment['max_attempts'];

        $base['assignment_id'] = (int) $assignment['id'];
        $base['due_date']      = $dueDate;
        $base['attempts_used'] = $used;
        $base['max_attempts']  = $maxAttempts;

        if ($dueDate && date('Y-m-d') > $dueDate) {
            $base['allowed'] = false;
            $base['reason']  = 'Due date has passed.';
            return $base;
        }
        if ($maxAttempts > 0 && $used >= $maxAttempts) {
            $base['allowed'] = false;
            $base['reason']  = 'Maximum attempts reached.';
            return $base;
        }
    }

    return $base;
}

/**
 * Creates a new attempt row and returns its ID.
 *
 * For 'quiz': inserts into teacher_quiz_attempts (started_at = now, no completed_at yet).
 * For 'game': inserts into game_attempts.
 */
function start_attempt(PDO $pdo, int $student_id, string $type, int $content_id, ?int $assignment_id): int {
    if ($type === 'quiz') {
        // Determine attempt_number
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM teacher_quiz_attempts
            WHERE student_id = :sid AND quiz_id = :qid
        ");
        $stmt->execute([':sid' => $student_id, ':qid' => $content_id]);
        $attemptNum = (int) $stmt->fetchColumn() + 1;

        $ins = $pdo->prepare("
            INSERT INTO teacher_quiz_attempts
                (student_id, quiz_id, assignment_id, attempt_number, score, max_score,
                 percentage, passed, time_spent_sec, xp_earned, started_at)
            VALUES (:sid, :qid, :aid, :num, 0, 0, 0.00, 0, 0, 0, NOW())
        ");
        $ins->execute([
            ':sid' => $student_id,
            ':qid' => $content_id,
            ':aid' => $assignment_id,
            ':num' => $attemptNum,
        ]);
        return (int) $pdo->lastInsertId();

    } elseif ($type === 'game') {
        $ins = $pdo->prepare("
            INSERT INTO game_attempts
                (student_id, game_id, assignment_id, score, max_score,
                 percentage, xp_earned, time_spent_sec, started_at)
            VALUES (:sid, :gid, :aid, 0, 0, 0.00, 0, 0, NOW())
        ");
        $ins->execute([
            ':sid' => $student_id,
            ':gid' => $content_id,
            ':aid' => $assignment_id,
        ]);
        return (int) $pdo->lastInsertId();
    }

    throw new InvalidArgumentException("Unknown attempt type: $type");
}
