-- ============================================================
-- EduQuest Super Admin Schema
-- Tables for: super_admins, system_settings, behavioral_logs,
--             assessment_sessions, survey_responses
-- Run AFTER the main schema.sql and schema_admins.sql
-- ============================================================

USE eduquest;

-- ------------------------------------------------------------
-- Super Admins
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS super_admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(200)   NOT NULL,
    email         VARCHAR(255)   NOT NULL UNIQUE,
    password_hash VARCHAR(255)   NOT NULL,
    created_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sa_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- System Settings (global feature toggles)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100)   NOT NULL UNIQUE,
    setting_value TINYINT        NOT NULL DEFAULT 1,
    updated_by    INT UNSIGNED   NULL,
    updated_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ss_super_admin FOREIGN KEY (updated_by)
        REFERENCES super_admins(id) ON DELETE SET NULL,
    INDEX idx_ss_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default settings (enabled = 1)
INSERT IGNORE INTO system_settings (setting_key, setting_value)
VALUES
    ('pretest_enabled',        1),
    ('posttest_enabled',       1),
    ('pssuq_teacher_enabled',  1),
    ('pssuq_student_enabled',  1);

-- ------------------------------------------------------------
-- Behavioral Logs
-- indicator_key uses the ten thesis-defined keys (snake_case)
-- source: 'quiz', 'activity', or 'other' to track engagement by type
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS behavioral_logs (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    log_type        ENUM('engagement','self_regulation') NOT NULL,
    indicator_key   VARCHAR(100)    NOT NULL,
    indicator_value VARCHAR(255)    NOT NULL,
    source          ENUM('quiz','activity','other') DEFAULT 'other' NOT NULL,
    session_date    DATE            NOT NULL,
    logged_by       ENUM('system','teacher') NOT NULL,
    teacher_id      INT UNSIGNED    NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    -- Valid indicator keys (enforced at application layer via log_behavior.php):
    -- Engagement:       task_completion_rate, time_on_task,
    --                   module_attempt_frequency, response_rate,
    --                   exp_accumulation_rate
    -- Self-Regulation:  task_initiation, task_persistence,
    --                   consistency_of_completion, responsiveness_to_feedback,
    --                   frustration_management
    CONSTRAINT fk_bl_student  FOREIGN KEY (student_id) REFERENCES students(id)  ON DELETE CASCADE,
    CONSTRAINT fk_bl_teacher  FOREIGN KEY (teacher_id) REFERENCES teachers(id)  ON DELETE SET NULL,
    INDEX idx_bl_student   (student_id),
    INDEX idx_bl_type      (log_type),
    INDEX idx_bl_key       (indicator_key),
    INDEX idx_bl_date      (session_date),
    INDEX idx_bl_logged_by (logged_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Assessment Sessions (pre-test / post-test)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS assessment_sessions (
    id             INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    student_id     INT UNSIGNED    NOT NULL,
    session_type   ENUM('pretest','posttest') NOT NULL,
    status         ENUM('pending','in_progress','completed','disabled') NOT NULL DEFAULT 'pending',
    initiated_by   INT UNSIGNED    NOT NULL,           -- teacher_id
    score          DECIMAL(5,2)    NULL,
    started_at     TIMESTAMP       NULL,
    completed_at   TIMESTAMP       NULL,
    created_at     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_as_student  FOREIGN KEY (student_id)   REFERENCES students(id)  ON DELETE CASCADE,
    CONSTRAINT fk_as_teacher  FOREIGN KEY (initiated_by) REFERENCES teachers(id)  ON DELETE CASCADE,
    INDEX idx_as_student (student_id),
    INDEX idx_as_type    (session_type),
    INDEX idx_as_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Survey Responses (PSSUQ teacher & student)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS survey_responses (
    id               INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    respondent_id    INT UNSIGNED    NOT NULL,
    respondent_role  ENUM('teacher','student') NOT NULL,
    survey_type      ENUM('pssuq_teacher','pssuq_student') NOT NULL,
    responses_json   LONGTEXT        NOT NULL,
    submitted_at     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    academic_period  VARCHAR(50)     NOT NULL,
    INDEX idx_sr_respondent (respondent_id),
    INDEX idx_sr_type       (survey_type),
    INDEX idx_sr_period     (academic_period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
