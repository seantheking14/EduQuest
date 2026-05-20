-- ============================================================
-- TEACHER GAME ASSIGNMENTS (per-student)
-- Tracks which extra games a teacher has enabled for a specific student.
-- Default games are always available; this table only controls
-- the non-default (extra) activities per student.
-- ============================================================

USE eduquest;

CREATE TABLE IF NOT EXISTS teacher_assigned_games (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED    NOT NULL,
    student_id      INT UNSIGNED    NOT NULL,       -- specific student
    game_id         VARCHAR(100)    NOT NULL,       -- matches activity id in JS BANK
    is_enabled      TINYINT(1)      DEFAULT 1,      -- 1 = enabled for this student
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_teacher_student_game (teacher_id, student_id, game_id),
    CONSTRAINT fk_tag_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_tag_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE
);
