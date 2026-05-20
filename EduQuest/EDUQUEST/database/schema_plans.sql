-- ============================================================
-- SPED Plans Migration
-- Run this against the eduquest database to add IEP, ITP,
-- and Individual Profile structured plan support.
-- ============================================================

USE eduquest;

-- ── Extend document_type ENUM to include ITP and Individual Profile ──
ALTER TABLE student_documents
  MODIFY COLUMN document_type ENUM(
    'iep',
    'itp',
    'individual_profile',
    'medical_report',
    'psychological_evaluation',
    'progress_report',
    '504_plan',
    'parent_consent',
    'other'
  ) DEFAULT 'other';

-- ── Individualized Education Program (IEP) ──────────────────
CREATE TABLE IF NOT EXISTS student_iep (
    id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id                INT UNSIGNED    NOT NULL UNIQUE,
    entry_method              ENUM('manual','uploaded') DEFAULT 'manual',
    document_id               INT UNSIGNED    NULL,       -- linked student_documents row if uploaded

    -- Section 1: IEP metadata
    effective_date            DATE,
    review_date               DATE,
    meeting_date              DATE,
    disability_classification VARCHAR(200),
    sped_category             VARCHAR(50),
    iep_team                  TEXT,                       -- comma-separated names/roles

    -- Section 2: Present Level of Educational Performance (PLEP)
    plep_academic             TEXT,
    plep_functional           TEXT,
    plep_social               TEXT,

    -- Section 3: Annual Goals & Objectives
    annual_goals              TEXT,                       -- one goal per line
    short_term_objectives     TEXT,

    -- Section 4: Services
    sped_services             TEXT,                       -- service · frequency · duration · location · provider
    related_services          TEXT,

    -- Section 5: Accommodations & Modifications
    accommodations_notes      TEXT,
    modifications_notes       TEXT,

    -- Section 6: Placement
    regular_ed_percentage     TINYINT UNSIGNED,           -- % time in regular class
    assessment_accommodations TEXT,

    -- Section 7: Transition (required 14+)
    transition_services       TEXT,

    additional_notes          TEXT,

    created_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_iep_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_iep_doc FOREIGN KEY (document_id)
        REFERENCES student_documents(id) ON DELETE SET NULL
);

-- ── Individualized Transition Plan (ITP) ────────────────────
CREATE TABLE IF NOT EXISTS student_itp (
    id                            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id                    INT UNSIGNED    NOT NULL UNIQUE,
    entry_method                  ENUM('manual','uploaded') DEFAULT 'manual',
    document_id                   INT UNSIGNED    NULL,

    -- Section 1: ITP metadata
    effective_date                DATE,
    graduation_date               DATE,
    disability_category           VARCHAR(200),

    -- Section 2: Transition Assessment — Present Levels
    career_interests              TEXT,
    assessed_strengths            TEXT,
    work_experiences              TEXT,
    community_experiences         TEXT,
    daily_living_skills           TEXT,

    -- Section 3: Post-Secondary Goals
    goal_postsecondary_education  TEXT,
    goal_employment               TEXT,
    goal_independent_living       TEXT,
    goal_community                TEXT,

    -- Section 4: Transition Services
    services_instruction          TEXT,
    services_community            TEXT,
    services_employment           TEXT,
    services_adult_living         TEXT,

    -- Section 5: Course of Study / Agency Linkages
    course_of_study               TEXT,
    agency_linkages               TEXT,
    annual_goals_transition       TEXT,

    additional_notes              TEXT,

    created_at                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_itp_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_itp_doc FOREIGN KEY (document_id)
        REFERENCES student_documents(id) ON DELETE SET NULL
);

-- ── Individual Student Profile ───────────────────────────────
CREATE TABLE IF NOT EXISTS student_individual_profile (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id                  INT UNSIGNED    NOT NULL UNIQUE,
    entry_method                ENUM('manual','uploaded') DEFAULT 'manual',
    document_id                 INT UNSIGNED    NULL,

    -- Section 1: Classification
    disability_classification   VARCHAR(200),
    sped_category               VARCHAR(100),
    years_in_sped               TINYINT UNSIGNED,
    preferred_name              VARCHAR(100),
    preferred_pronouns          VARCHAR(50),
    primary_language            VARCHAR(100),

    -- Section 2: Strengths & Challenges
    academic_strengths          TEXT,
    academic_challenges         TEXT,
    behavioral_strengths        TEXT,
    behavioral_challenges       TEXT,
    social_strengths            TEXT,
    social_challenges           TEXT,

    -- Section 3: Learning Profile
    learning_style              ENUM('visual','auditory','kinesthetic','mixed','other') DEFAULT 'mixed',
    learning_style_notes        TEXT,
    attention_span              ENUM('short','moderate','good','variable') DEFAULT 'variable',

    -- Section 4: Communication
    communication_profile       TEXT,

    -- Section 5: Behavioral Profile
    motivators                  TEXT,
    triggers                    TEXT,
    calming_strategies          TEXT,
    reinforcement_strategies    TEXT,

    -- Section 6: Support Network
    family_support_level        ENUM('high','moderate','limited','unknown') DEFAULT 'unknown',
    outside_services            TEXT,

    -- Section 7: Observations
    student_voice               TEXT,
    teacher_observations        TEXT,

    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_sip_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_sip_doc FOREIGN KEY (document_id)
        REFERENCES student_documents(id) ON DELETE SET NULL
);
