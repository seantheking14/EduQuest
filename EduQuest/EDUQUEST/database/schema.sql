-- ============================================================
-- EDUQUEST Database Schema
-- Student Profile Management System for ADHD & Comorbid Conditions
-- ============================================================

CREATE DATABASE IF NOT EXISTS eduquest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eduquest;

-- ============================================================
-- UNIFIED AUTHENTICATION TABLES
-- ============================================================

-- ------------------------------------------------------------
-- Users Table (unified login for teachers and students)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(255)        NOT NULL UNIQUE,
    password_hash       VARCHAR(255)        NOT NULL,
    first_name          VARCHAR(100)        NOT NULL,
    last_name           VARCHAR(100)        NOT NULL,
    role                ENUM('student','teacher','admin') NOT NULL,
    profile_id          INT UNSIGNED,           -- references teachers.id or students.id
    is_active           TINYINT(1)          DEFAULT 0,  -- 0 until email verified
    email_verified      TINYINT(1)          DEFAULT 0,
    email_verified_at   TIMESTAMP           NULL,
    last_login          TIMESTAMP           NULL,
    last_login_ip       VARCHAR(45),
    created_at          TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Email Verification Tokens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED        NOT NULL,
    token           VARCHAR(255)        NOT NULL UNIQUE,
    expires_at      TIMESTAMP           NOT NULL,
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_verify_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- ------------------------------------------------------------
-- Password Reset Tokens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED        NOT NULL,
    token           VARCHAR(255)        NOT NULL UNIQUE,
    expires_at      TIMESTAMP           NOT NULL,
    used_at         TIMESTAMP           NULL,
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- ------------------------------------------------------------
-- Login Attempts (for brute force protection)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255)        NOT NULL,
    ip_address      VARCHAR(45)         NOT NULL,
    success         TINYINT(1)          DEFAULT 0,
    user_agent      VARCHAR(500),
    attempted_at    TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_ip (email, ip_address),
    INDEX idx_attempted (attempted_at)
);

-- ------------------------------------------------------------
-- User Sessions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_sessions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED        NOT NULL,
    session_token   VARCHAR(255)        NOT NULL UNIQUE,
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(500),
    remember_token  VARCHAR(255),       -- for "remember me" functionality
    expires_at      TIMESTAMP           NOT NULL,
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_session_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_expires (user_id, expires_at)
);

-- ============================================================
-- ORIGINAL PROFILE TABLES (modified to work with users)
-- ============================================================

-- ------------------------------------------------------------
-- Teachers Profile
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teachers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED,           -- links to users table
    first_name    VARCHAR(100)        NOT NULL,
    last_name     VARCHAR(100)        NOT NULL,
    email         VARCHAR(255)        NOT NULL UNIQUE,
    school_name   VARCHAR(255),
    department    VARCHAR(150),
    role          ENUM('teacher','admin') DEFAULT 'teacher',
    is_active     TINYINT(1)          DEFAULT 1,
    created_at    TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teacher_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Students
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED,           -- links to users table
    teacher_id          INT UNSIGNED,           -- nullable for self-registration
    first_name          VARCHAR(100)        NOT NULL,
    last_name           VARCHAR(100)        NOT NULL,
    date_of_birth       DATE,
    gender              ENUM('male','female','non_binary','prefer_not_to_say'),
    grade_level         VARCHAR(20),
    school_name         VARCHAR(255),
    student_id_number   VARCHAR(100),          -- school-assigned ID
    parent_guardian_name    VARCHAR(200),
    parent_guardian_email   VARCHAR(255),
    parent_guardian_phone   VARCHAR(30),
    emergency_contact       VARCHAR(200),
    emergency_phone         VARCHAR(30),
    profile_photo       VARCHAR(500),          -- path to uploaded photo
    notes               TEXT,
    is_active           TINYINT(1)          DEFAULT 1,
    created_at          TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- ADHD Profiles  (one per student)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS adhd_profiles (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id          INT UNSIGNED        NOT NULL UNIQUE,
    adhd_type           ENUM(
                            'predominantly_inattentive',
                            'predominantly_hyperactive_impulsive',
                            'combined_presentation',
                            'other_specified',
                            'unspecified'
                        )                   NOT NULL,
    severity            ENUM('mild','moderate','severe') DEFAULT 'moderate',
    diagnosis_date      DATE,
    diagnosing_professional VARCHAR(200),
    -- Core symptom ratings (1=rarely, 5=very often)
    inattention_rating      TINYINT CHECK (inattention_rating BETWEEN 1 AND 5),
    hyperactivity_rating    TINYINT CHECK (hyperactivity_rating BETWEEN 1 AND 5),
    impulsivity_rating      TINYINT CHECK (impulsivity_rating BETWEEN 1 AND 5),
    -- Specific challenges
    has_reading_difficulty      TINYINT(1) DEFAULT 0,
    has_writing_difficulty      TINYINT(1) DEFAULT 0,
    has_math_difficulty         TINYINT(1) DEFAULT 0,
    has_focus_difficulty        TINYINT(1) DEFAULT 0,
    has_organization_difficulty TINYINT(1) DEFAULT 0,
    has_time_management_difficulty TINYINT(1) DEFAULT 0,
    has_working_memory_issues   TINYINT(1) DEFAULT 0,
    has_emotional_regulation_issues TINYINT(1) DEFAULT 0,
    iep_in_place            TINYINT(1) DEFAULT 0,
    section_504_in_place    TINYINT(1) DEFAULT 0,
    additional_notes        TEXT,
    created_at          TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_adhd_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Comorbid Conditions  (many per student)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comorbid_conditions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id          INT UNSIGNED        NOT NULL,
    condition_name      VARCHAR(200)        NOT NULL,
    condition_category  ENUM(
                            'neurodevelopmental',
                            'mood_disorder',
                            'anxiety_disorder',
                            'learning_disability',
                            'behavioral_disorder',
                            'sleep_disorder',
                            'sensory_processing',
                            'other'
                        )                   DEFAULT 'other',
    severity            ENUM('mild','moderate','severe'),
    diagnosed_by        VARCHAR(200),
    diagnosis_date      DATE,
    is_current          TINYINT(1) DEFAULT 1,
    notes               TEXT,
    created_at          TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comorbid_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Medications  (many per student)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    medication_name VARCHAR(200)    NOT NULL,
    dosage          VARCHAR(100),
    frequency       VARCHAR(100),   -- e.g. "Once daily in the morning"
    prescribing_doctor VARCHAR(200),
    start_date      DATE,
    end_date        DATE,
    is_current      TINYINT(1) DEFAULT 1,
    side_effects_notes TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_medication_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Accommodations & Strategies  (many per student)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS accommodations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    category        ENUM(
                        'instructional',
                        'assessment',
                        'environmental',
                        'behavioral',
                        'technology',
                        'social_emotional',
                        'other'
                    )               NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    description     TEXT,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_accommodation_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Teacher Progress Notes / Observations  (many per student)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teacher_notes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    teacher_id      INT UNSIGNED    NOT NULL,
    note_date       DATE            NOT NULL,
    note_type       ENUM('observation','progress','incident','meeting','general') DEFAULT 'general',
    subject_area    VARCHAR(100),
    content         TEXT            NOT NULL,
    is_private      TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_note_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_note_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Uploaded Documents  (IEPs, reports, evaluations, etc.)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    uploaded_by     INT UNSIGNED    NOT NULL,
    document_type   ENUM(
                        'iep',
                        'medical_report',
                        'psychological_evaluation',
                        'progress_report',
                        '504_plan',
                        'parent_consent',
                        'other'
                    )               DEFAULT 'other',
    title           VARCHAR(255)    NOT NULL,
    original_filename VARCHAR(500)  NOT NULL,
    stored_filename VARCHAR(500)    NOT NULL,   -- UUID-based stored name
    file_size       INT UNSIGNED,
    mime_type       VARCHAR(100),
    notes           TEXT,
    uploaded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_doc_uploader FOREIGN KEY (uploaded_by)
        REFERENCES teachers(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Import Logs  (tracks CSV / document-based import batches)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS import_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED    NOT NULL,
    import_type     ENUM('csv','document') NOT NULL,
    filename        VARCHAR(500)    NOT NULL,
    stored_filename VARCHAR(500),               -- for document imports
    total_rows      INT UNSIGNED    DEFAULT 0,
    success_rows    INT UNSIGNED    DEFAULT 0,
    failed_rows     INT UNSIGNED    DEFAULT 0,
    error_details   JSON,                       -- per-row errors
    status          ENUM('pending','completed','failed') DEFAULT 'completed',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE
);

-- Add import tracking to students table
ALTER TABLE students
    ADD COLUMN import_source ENUM('manual','csv','document') DEFAULT 'manual' AFTER is_active,
    ADD COLUMN import_log_id INT UNSIGNED NULL AFTER import_source,
    ADD COLUMN is_draft TINYINT(1) DEFAULT 0 AFTER import_log_id;

-- ------------------------------------------------------------
-- Courses  (Blackboard-style learning materials)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS courses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED    NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    description     TEXT,
    subject         VARCHAR(100),
    grade_level     VARCHAR(30),
    school_year     VARCHAR(20),                   -- e.g. "2025-2026"
    cover_color     VARCHAR(7)      DEFAULT '#6366f1',
    is_active       TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_course_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Course Modules  (units / content areas within a course)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS course_modules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id       INT UNSIGNED    NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    description     TEXT,
    position        INT UNSIGNED    DEFAULT 0,
    is_visible      TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_module_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Course Materials  (files, links, text notes, assignments)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS course_materials (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id           INT UNSIGNED    NOT NULL,
    course_id           INT UNSIGNED    NOT NULL,
    title               VARCHAR(255)    NOT NULL,
    description         TEXT,
    material_type       ENUM('file','link','text','assignment') DEFAULT 'file',
    content             TEXT,                          -- URL for links; body text for text/assignment
    original_filename   VARCHAR(500),
    stored_filename     VARCHAR(500),
    file_size           INT UNSIGNED,
    mime_type           VARCHAR(100),
    position            INT UNSIGNED    DEFAULT 0,
    is_visible          TINYINT(1)      DEFAULT 1,
    due_date            DATE,                          -- for assignments
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_material_module FOREIGN KEY (module_id)
        REFERENCES course_modules(id) ON DELETE CASCADE,
    CONSTRAINT fk_material_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Course Enrollments  (which students are in which course)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS course_enrollments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id   INT UNSIGNED    NOT NULL,
    student_id  INT UNSIGNED    NOT NULL,
    enrolled_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment (course_id, student_id),
    CONSTRAINT fk_enrollment_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Course Announcements
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS course_announcements (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id   INT UNSIGNED    NOT NULL,
    teacher_id  INT UNSIGNED    NOT NULL,
    title       VARCHAR(255)    NOT NULL,
    content     TEXT            NOT NULL,
    is_pinned   TINYINT(1)      DEFAULT 0,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ann_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_ann_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE INDEX idx_courses_teacher       ON courses(teacher_id);
CREATE INDEX idx_modules_course        ON course_modules(course_id);
CREATE INDEX idx_materials_module      ON course_materials(module_id);
CREATE INDEX idx_materials_course      ON course_materials(course_id);
CREATE INDEX idx_enrollments_course    ON course_enrollments(course_id);
CREATE INDEX idx_enrollments_student   ON course_enrollments(student_id);
CREATE INDEX idx_announcements_course  ON course_announcements(course_id);

-- ------------------------------------------------------------
-- Indexes for performance (unified auth + original tables)
-- ------------------------------------------------------------
-- User/Auth table indexes
CREATE INDEX idx_users_email           ON users(email);
CREATE INDEX idx_users_role            ON users(role);
CREATE INDEX idx_users_active          ON users(is_active);
CREATE INDEX idx_teachers_user         ON teachers(user_id);
CREATE INDEX idx_teachers_email        ON teachers(email);
CREATE INDEX idx_students_user         ON students(user_id);
CREATE INDEX idx_students_teacher      ON students(teacher_id);

-- Original profile table indexes
CREATE INDEX idx_comorbid_student      ON comorbid_conditions(student_id);
CREATE INDEX idx_medications_student   ON medications(student_id);
CREATE INDEX idx_accommodations_student ON accommodations(student_id);
CREATE INDEX idx_notes_student         ON teacher_notes(student_id);
CREATE INDEX idx_docs_student          ON student_documents(student_id);
CREATE INDEX idx_sessions_user         ON user_sessions(user_id);

-- Courses/Materials indexes
CREATE INDEX idx_courses_teacher       ON courses(teacher_id);
CREATE INDEX idx_modules_course        ON course_modules(course_id);
CREATE INDEX idx_materials_module      ON course_materials(module_id);
CREATE INDEX idx_materials_course      ON course_materials(course_id);
CREATE INDEX idx_enrollments_course    ON course_enrollments(course_id);
CREATE INDEX idx_enrollments_student   ON course_enrollments(student_id);
CREATE INDEX idx_announcements_course  ON course_announcements(course_id);

-- (Note: old teacher_sessions table removed - replaced by user_sessions)

-- ============================================================
-- SEED DATA: Default admin account
-- Change this password immediately after first login!
-- Password: Admin@1234 (hash created with password_hash('Admin@1234', PASSWORD_BCRYPT))
-- ============================================================
INSERT INTO users (email, password_hash, first_name, last_name, role, is_active, email_verified, email_verified_at)
VALUES (
    'admin@eduquest.local',
    '$2y$12$R9h/cIPz0gi.URNNX3kh2OPST9/PgBkqquzi.Ss7KIUgO2t0jWMSW',
    'Admin',
    'User',
    'admin',
    1,
    1,
    NOW()
);

INSERT INTO teachers (user_id, first_name, last_name, email, role)
VALUES (
    1,
    'Admin',
    'User',
    'admin@eduquest.local',
    'admin'
);
