-- ============================================================
-- TEACHER DEFAULT GAME SETTINGS (teacher-level)
-- Controls whether built-in predetermined My Quests games are
-- enabled for all students under a teacher.
-- ============================================================

USE eduquest;

CREATE TABLE IF NOT EXISTS teacher_default_game_settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED    NOT NULL,
    game_id         VARCHAR(100)    NOT NULL,
    is_enabled      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_teacher_game (teacher_id, game_id),
    KEY idx_teacher_id (teacher_id)
);
