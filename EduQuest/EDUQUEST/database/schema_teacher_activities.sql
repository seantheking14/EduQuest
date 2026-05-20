-- ═══════════════════════════════════════════════════════════
--  Teacher-Created Activities (Gamified Learning Activities)
--  Supports Math, English, and Self-Care categories
--  Teacher can create custom activities with flexible item structure
-- ═══════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS teacher_activities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED NOT NULL,
    category        ENUM('math', 'english', 'selfcare') NOT NULL,
    title           VARCHAR(255) NOT NULL,
    description     TEXT DEFAULT NULL,
    icon            VARCHAR(10) DEFAULT '🎮' COMMENT 'emoji icon',
    activity_type   ENUM('sort-order', 'classify', 'compare', 'choose', 'build-word', 'custom') NOT NULL DEFAULT 'custom',
    instructions    TEXT DEFAULT NULL COMMENT 'shown during game',
    rounds          TINYINT UNSIGNED DEFAULT 6 COMMENT 'number of questions/rounds',
    xp_reward       INT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'XP given on completion',
    pass_percentage TINYINT UNSIGNED NOT NULL DEFAULT 70,
    max_attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
    time_limit_sec  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no limit',
    cover_image     VARCHAR(255) DEFAULT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_teacher_category (teacher_id, category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity content stored as JSON for flexibility
-- Allows storing different question/item types depending on activity_type
CREATE TABLE IF NOT EXISTS teacher_activity_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id     INT UNSIGNED NOT NULL,
    item_order      INT UNSIGNED NOT NULL DEFAULT 1,
    item_data       JSON NOT NULL COMMENT 'flexible structure based on activity_type',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (activity_id) REFERENCES teacher_activities(id) ON DELETE CASCADE,
    INDEX idx_activity_order (activity_id, item_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity assignments: which students/courses get which activities
CREATE TABLE IF NOT EXISTS teacher_activity_assignments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id     INT UNSIGNED NOT NULL,
    teacher_id      INT UNSIGNED NOT NULL,
    course_id       INT UNSIGNED DEFAULT NULL,
    student_id      INT UNSIGNED DEFAULT NULL COMMENT 'NULL = all students in course',
    assigned_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date        DATE DEFAULT NULL,
    FOREIGN KEY (activity_id)   REFERENCES teacher_activities(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id)    REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)     REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)    REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_activity_teacher (activity_id, teacher_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student activity attempts/progress tracking
CREATE TABLE IF NOT EXISTS teacher_activity_attempts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED NOT NULL,
    activity_id     INT UNSIGNED NOT NULL,
    attempt_number  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    score           INT UNSIGNED NOT NULL DEFAULT 0,
    max_score       INT UNSIGNED NOT NULL DEFAULT 0,
    percentage      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    passed          TINYINT(1) NOT NULL DEFAULT 0,
    time_spent_sec  INT UNSIGNED NOT NULL DEFAULT 0,
    xp_earned       INT UNSIGNED NOT NULL DEFAULT 0,
    answers_json    JSON DEFAULT NULL COMMENT 'snapshot of student answers',
    started_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at    DATETIME DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES teacher_activities(id) ON DELETE CASCADE,
    INDEX idx_student_activity (student_id, activity_id),
    INDEX idx_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
