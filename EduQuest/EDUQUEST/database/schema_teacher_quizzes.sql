-- ═══════════════════════════════════════════════════════════
--  Teacher-Created Quizzes & Activities
--  Question types: multiple_choice, fill_blank, drag_drop,
--                  matching, choose_from_box
-- ═══════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS teacher_quizzes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED NOT NULL,
    course_id       INT UNSIGNED DEFAULT NULL,
    title           VARCHAR(255) NOT NULL,
    description     TEXT DEFAULT NULL,
    instructions    TEXT DEFAULT NULL,
    cover_image     VARCHAR(255) DEFAULT NULL,
    pass_percentage TINYINT UNSIGNED NOT NULL DEFAULT 70,
    max_attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
    time_limit_sec  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no limit',
    shuffle_questions TINYINT(1) NOT NULL DEFAULT 1,
    shuffle_answers   TINYINT(1) NOT NULL DEFAULT 1,
    xp_reward       INT UNSIGNED NOT NULL DEFAULT 50,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teacher_quiz_questions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id         INT UNSIGNED NOT NULL,
    question_order  INT UNSIGNED NOT NULL DEFAULT 1,
    question_type   ENUM('multiple_choice','fill_blank','drag_drop','matching','choose_from_box') NOT NULL,
    question_text   TEXT NOT NULL,
    question_image  VARCHAR(255) DEFAULT NULL COMMENT 'uploaded image path',
    explanation     TEXT DEFAULT NULL COMMENT 'shown after answering',
    points          INT UNSIGNED NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES teacher_quizzes(id) ON DELETE CASCADE,
    INDEX idx_quiz_order (quiz_id, question_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- For multiple_choice & choose_from_box: standard answer options
-- For fill_blank: correct answer(s) stored here (is_correct=1 rows are accepted answers)
-- For drag_drop: answer_text = item label, match_target = correct drop zone/position
-- For matching: answer_text = left item, match_target = right item to match with
CREATE TABLE IF NOT EXISTS teacher_quiz_answers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id     INT UNSIGNED NOT NULL,
    answer_text     VARCHAR(500) NOT NULL,
    answer_image    VARCHAR(255) DEFAULT NULL,
    is_correct      TINYINT(1) NOT NULL DEFAULT 0,
    match_target    VARCHAR(500) DEFAULT NULL COMMENT 'For drag_drop: zone label; For matching: paired item',
    answer_order    INT UNSIGNED NOT NULL DEFAULT 1,
    FOREIGN KEY (question_id) REFERENCES teacher_quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teacher_quiz_attempts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED NOT NULL,
    quiz_id         INT UNSIGNED NOT NULL,
    attempt_number  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    score           INT UNSIGNED NOT NULL DEFAULT 0,
    max_score       INT UNSIGNED NOT NULL DEFAULT 0,
    percentage      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    passed          TINYINT(1) NOT NULL DEFAULT 0,
    time_spent_sec  INT UNSIGNED NOT NULL DEFAULT 0,
    xp_earned       INT UNSIGNED NOT NULL DEFAULT 0,
    answers_json    JSON DEFAULT NULL COMMENT 'Snapshot of student answers',
    started_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at    DATETIME DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id)    REFERENCES teacher_quizzes(id) ON DELETE CASCADE,
    INDEX idx_student_quiz (student_id, quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Quiz assignments: which students/courses can see which quizzes
CREATE TABLE IF NOT EXISTS teacher_quiz_assignments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id     INT UNSIGNED NOT NULL,
    course_id   INT UNSIGNED DEFAULT NULL,
    student_id  INT UNSIGNED DEFAULT NULL COMMENT 'NULL = all students in course',
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date    DATE DEFAULT NULL,
    FOREIGN KEY (quiz_id)    REFERENCES teacher_quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(id)         ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id)        ON DELETE CASCADE,
    INDEX idx_quiz_course (quiz_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
