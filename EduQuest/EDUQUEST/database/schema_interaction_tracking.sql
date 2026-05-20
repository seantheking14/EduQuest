-- ============================================================
-- EduQuest Interaction Tracking Tables
-- Run once against the `eduquest` database.
-- ============================================================

CREATE TABLE IF NOT EXISTS `page_sessions` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `student_id`       INT UNSIGNED    NOT NULL,
  `page_name`        VARCHAR(100)    NOT NULL,
  `session_start`    DATETIME        NOT NULL,
  `session_end`      DATETIME        NULL DEFAULT NULL,
  `duration_seconds` INT UNSIGNED    NULL DEFAULT NULL,
  `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ps_student`  (`student_id`),
  KEY `idx_ps_page`     (`page_name`),
  KEY `idx_ps_start`    (`session_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `question_interactions` (
  `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `student_id`         INT UNSIGNED    NOT NULL,
  `quiz_id`            INT UNSIGNED    NOT NULL,
  `question_id`        INT UNSIGNED    NOT NULL,
  `time_spent_seconds` INT UNSIGNED    NOT NULL DEFAULT 0,
  `attempt_number`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `answered_correctly` TINYINT(1)      NULL DEFAULT NULL,
  `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qi_student`  (`student_id`),
  KEY `idx_qi_quiz`     (`quiz_id`),
  KEY `idx_qi_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `click_events` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `student_id`    INT UNSIGNED  NOT NULL,
  `page_name`     VARCHAR(100)  NOT NULL,
  `element_label` VARCHAR(200)  NOT NULL,
  `click_count`   INT UNSIGNED  NOT NULL DEFAULT 1,
  `session_date`  DATE          NOT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_click_daily` (`student_id`,`page_name`,`element_label`,`session_date`),
  KEY `idx_ce_student` (`student_id`),
  KEY `idx_ce_date`    (`session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hover_events` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `student_id`      INT UNSIGNED  NOT NULL,
  `page_name`       VARCHAR(100)  NOT NULL,
  `element_label`   VARCHAR(200)  NOT NULL,
  `total_hover_ms`  INT UNSIGNED  NOT NULL DEFAULT 0,
  `session_date`    DATE          NOT NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hover_daily` (`student_id`,`page_name`,`element_label`,`session_date`),
  KEY `idx_he_student` (`student_id`),
  KEY `idx_he_date`    (`session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
