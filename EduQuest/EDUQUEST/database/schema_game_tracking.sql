-- ============================================================
-- Game & Quiz Attempt Tracking — Migration
-- Run once against the `eduquest` database.
-- ============================================================

-- 1. Add per-assignment max_attempts override to existing quiz assignments table
ALTER TABLE `teacher_quiz_assignments`
  ADD COLUMN `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT '0 = inherit from teacher_quizzes.max_attempts' AFTER `due_date`;

-- 2. Link individual quiz attempts to a specific assignment + add abandoned flag
ALTER TABLE `teacher_quiz_attempts`
  ADD COLUMN `assignment_id` INT UNSIGNED DEFAULT NULL
    COMMENT 'FK teacher_quiz_assignments.id — NULL for unassigned attempts' AFTER `quiz_id`,
  ADD COLUMN `is_abandoned` TINYINT(1) NOT NULL DEFAULT 0 AFTER `completed_at`;

-- Add index for fast per-assignment queries
ALTER TABLE `teacher_quiz_attempts`
  ADD KEY `idx_tqa_assignment` (`assignment_id`);

-- 3. Games reference table (word_scramble, activity)
CREATE TABLE IF NOT EXISTS `games` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `game_type`   VARCHAR(50)      NOT NULL COMMENT 'word_scramble | activity',
  `name`        VARCHAR(100)     NOT NULL,
  `description` TEXT             DEFAULT NULL,
  `is_active`   TINYINT(1)       NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_game_type` (`game_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `games` (`game_type`, `name`, `description`) VALUES
  ('word_scramble', 'Word Scramble', 'Unscramble vocabulary words against the clock'),
  ('activity',      'Activity Game', 'Interactive learning activity game');

-- 4. Game assignments — teacher assigns a game to specific students
CREATE TABLE IF NOT EXISTS `game_assignments` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `game_id`      INT UNSIGNED     NOT NULL,
  `student_id`   INT UNSIGNED     NOT NULL,
  `teacher_id`   INT UNSIGNED     NOT NULL,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
  `due_date`     DATE             DEFAULT NULL,
  `assigned_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_game_student` (`game_id`, `student_id`),
  KEY `idx_ga_student` (`student_id`),
  KEY `idx_ga_teacher` (`teacher_id`),
  CONSTRAINT `fk_ga_game`    FOREIGN KEY (`game_id`)    REFERENCES `games`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ga_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ga_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Game attempts — one row per play session
CREATE TABLE IF NOT EXISTS `game_attempts` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `student_id`    INT UNSIGNED     NOT NULL,
  `game_id`       INT UNSIGNED     NOT NULL,
  `assignment_id` INT UNSIGNED     DEFAULT NULL,
  `score`         INT UNSIGNED     NOT NULL DEFAULT 0,
  `max_score`     INT UNSIGNED     NOT NULL DEFAULT 0,
  `percentage`    DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
  `xp_earned`     INT UNSIGNED     NOT NULL DEFAULT 0,
  `time_spent_sec` INT UNSIGNED    NOT NULL DEFAULT 0,
  `is_abandoned`  TINYINT(1)       NOT NULL DEFAULT 0,
  `started_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at`  DATETIME         DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gat_student`    (`student_id`),
  KEY `idx_gat_assignment` (`assignment_id`),
  CONSTRAINT `fk_gat_student`    FOREIGN KEY (`student_id`)    REFERENCES `students`         (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gat_game`       FOREIGN KEY (`game_id`)       REFERENCES `games`            (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gat_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `game_assignments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
