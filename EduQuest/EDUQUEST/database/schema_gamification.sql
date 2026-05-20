-- ============================================================
-- GAMIFICATION SYSTEM Migration
-- Adds XP, achievements, teams, leaderboards, and egg evolution
-- Run this against the eduquest database.
-- ============================================================

USE eduquest;

-- ============================================================
-- GAMIFICATION SETTINGS (teacher-controlled per course)
-- ============================================================
CREATE TABLE IF NOT EXISTS gamification_settings (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id              INT UNSIGNED    NOT NULL,
    course_id               INT UNSIGNED    NULL,        -- NULL = global defaults for this teacher

    -- XP & Difficulty
    xp_multiplier           DECIMAL(3,2)    DEFAULT 1.00,  -- scale XP rewards
    difficulty_level        ENUM('easy','moderate','challenging') DEFAULT 'moderate',

    -- Feature toggles
    achievements_enabled    TINYINT(1)      DEFAULT 1,
    leaderboard_mode        ENUM('enabled','top_only','individual','disabled') DEFAULT 'disabled',
    leaderboard_top_n       TINYINT UNSIGNED DEFAULT 5,     -- show only top N if top_only
    egg_evolution_enabled   TINYINT(1)      DEFAULT 1,
    teams_enabled           TINYINT(1)      DEFAULT 1,
    daily_challenges_enabled TINYINT(1)     DEFAULT 1,
    streaks_enabled         TINYINT(1)      DEFAULT 1,

    -- Pacing & overstimulation controls
    max_daily_xp            INT UNSIGNED    DEFAULT 500,    -- cap daily XP to avoid burnout
    notification_frequency  ENUM('all','important','minimal') DEFAULT 'important',
    animation_level         ENUM('full','reduced','none')   DEFAULT 'reduced',

    -- Timer settings (seconds per question / round, 0 = no timer)
    quiz_timer_seconds      INT UNSIGNED    DEFAULT 30,
    game_timer_seconds      INT UNSIGNED    DEFAULT 30,

    created_at              TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_teacher_course (teacher_id, course_id),
    CONSTRAINT fk_gs_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_gs_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE
);

-- ============================================================
-- STUDENT GAMIFICATION PROFILES
-- ============================================================
CREATE TABLE IF NOT EXISTS student_gamification (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id          INT UNSIGNED    NOT NULL UNIQUE,
    total_xp            INT UNSIGNED    DEFAULT 0,
    current_level       TINYINT UNSIGNED DEFAULT 1,
    team                ENUM('fire','water','grass') NULL,       -- chosen team
    egg_type            ENUM('fire','water','grass') NULL,       -- chosen starter egg element
    egg_stage           TINYINT UNSIGNED DEFAULT 1,              -- 1-5 evolution stages
    streak_days         INT UNSIGNED    DEFAULT 0,
    longest_streak      INT UNSIGNED    DEFAULT 0,
    last_activity_date  DATE            NULL,
    daily_xp_earned     INT UNSIGNED    DEFAULT 0,               -- resets daily
    daily_xp_date       DATE            NULL,                    -- date of daily_xp_earned

    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_sg_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- XP TRANSACTION LOG (audit trail)
-- ============================================================
CREATE TABLE IF NOT EXISTS xp_transactions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    xp_amount       INT             NOT NULL,             -- can be negative for corrections
    source_type     ENUM('quest','quiz','activity','achievement','daily_challenge','streak_bonus','teacher_award','correction') NOT NULL,
    source_id       INT UNSIGNED    NULL,                  -- FK to quest/quiz/achievement
    description     VARCHAR(500)    NOT NULL,
    course_id       INT UNSIGNED    NULL,
    teacher_id      INT UNSIGNED    NULL,                  -- who awarded (if teacher_award)

    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_xpt_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_xpt_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE SET NULL,
    CONSTRAINT fk_xpt_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE SET NULL,
    INDEX idx_xpt_student (student_id),
    INDEX idx_xpt_created (created_at)
);

-- ============================================================
-- ACHIEVEMENT DEFINITIONS (teacher-created or system defaults)
-- ============================================================
CREATE TABLE IF NOT EXISTS achievements (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED    NULL,                  -- NULL = system-wide default
    course_id       INT UNSIGNED    NULL,

    title           VARCHAR(200)    NOT NULL,
    description     TEXT            NOT NULL,
    icon            VARCHAR(10)     DEFAULT '⭐',          -- emoji icon
    category        ENUM('academic','streak','social','milestone','special') DEFAULT 'academic',
    achievement_type ENUM('count','threshold','streak','custom') DEFAULT 'threshold',

    -- Unlock criteria
    target_value    INT UNSIGNED    DEFAULT 1,             -- e.g. complete 5 quests
    target_metric   VARCHAR(100)    NULL,                   -- e.g. 'quests_completed', 'xp_earned'

    -- Rewards
    xp_reward       INT UNSIGNED    DEFAULT 0,
    badge_color     VARCHAR(7)      DEFAULT '#fbbf24',     -- gold default

    is_hidden       TINYINT(1)      DEFAULT 0,             -- surprise achievements
    is_active       TINYINT(1)      DEFAULT 1,
    sort_order      INT UNSIGNED    DEFAULT 0,

    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_ach_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_ach_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE
);

-- ============================================================
-- STUDENT ACHIEVEMENTS (unlocked badges)
-- ============================================================
CREATE TABLE IF NOT EXISTS student_achievements (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    achievement_id  INT UNSIGNED    NOT NULL,
    progress        INT UNSIGNED    DEFAULT 0,             -- current progress toward target
    is_unlocked     TINYINT(1)      DEFAULT 0,
    unlocked_at     TIMESTAMP       NULL,
    notified        TINYINT(1)      DEFAULT 0,             -- has student seen the unlock?

    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_student_achievement (student_id, achievement_id),
    CONSTRAINT fk_sa_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_achievement FOREIGN KEY (achievement_id)
        REFERENCES achievements(id) ON DELETE CASCADE
);

-- ============================================================
-- VIRTUAL REWARDS / INVENTORY
-- ============================================================
CREATE TABLE IF NOT EXISTS virtual_rewards (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED    NULL,
    title           VARCHAR(200)    NOT NULL,
    description     TEXT,
    reward_type     ENUM('cosmetic','privilege','certificate','custom') DEFAULT 'cosmetic',
    icon            VARCHAR(10)     DEFAULT '🎁',
    xp_cost         INT UNSIGNED    DEFAULT 0,             -- cost to claim (0 = auto-awarded)
    milestone_xp    INT UNSIGNED    NULL,                   -- auto-award at this total XP
    is_active       TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_vr_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_rewards (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    reward_id       INT UNSIGNED    NOT NULL,
    claimed_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_student_reward (student_id, reward_id),
    CONSTRAINT fk_sr_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_sr_reward FOREIGN KEY (reward_id)
        REFERENCES virtual_rewards(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED: Default system achievements
-- ============================================================

-- ============================================================
-- STUDENT ACTIVITY LOG (tracks task completion, attempts, time-on-task)
-- ============================================================
CREATE TABLE IF NOT EXISTS student_activity_log (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id          INT UNSIGNED    NOT NULL,
    activity_type       ENUM('quest','quiz','activity','daily_challenge') NOT NULL,
    activity_id         INT UNSIGNED    NULL,        -- FK to the source item (quest, quiz, etc.)
    course_id           INT UNSIGNED    NULL,
    title               VARCHAR(255)    NOT NULL,
    score               DECIMAL(6,2)    NULL,        -- student's score
    max_score           DECIMAL(6,2)    NULL,        -- maximum possible score
    attempts            INT UNSIGNED    DEFAULT 1,   -- number of tries
    time_spent_seconds  INT UNSIGNED    NULL,        -- how long the student worked
    responses           JSON            NULL,        -- optional answer/response data
    completed_at        TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sal_student (student_id),
    INDEX idx_sal_type    (activity_type),
    INDEX idx_sal_course  (course_id),
    CONSTRAINT fk_sal_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
INSERT INTO achievements (teacher_id, title, description, icon, category, achievement_type, target_value, target_metric, xp_reward, badge_color, sort_order) VALUES
(NULL, 'Notable Newcomer',    'Welcome to EduQuest! You took your first step.',  '🌟', 'special',   'count',     1,  'first_login',       25,  '#fbbf24', 0),
(NULL, 'First Steps',         'Complete your first quest or mini-game',          '🌱', 'milestone', 'count',     1,  'quests_completed',  50,  '#10b981', 1),
(NULL, 'Quest Apprentice',    'Complete 5 quests or mini-games',                 '⚔️', 'milestone', 'count',     5,  'quests_completed',  100, '#3b82f6', 2),
(NULL, 'Quest Adventurer',    'Complete 10 quests or mini-games',                '🗺️', 'milestone', 'count',     10, 'quests_completed',  200, '#8b5cf6', 3),
(NULL, 'Quest Master',        'Complete 25 quests or mini-games',                '🏆', 'milestone', 'count',     25, 'quests_completed',  500, '#f59e0b', 4),
(NULL, 'XP Hunter',           'Earn 500 total XP',                               '⚡', 'milestone', 'threshold', 500,   'total_xp',       75,  '#8b5cf6', 5),
(NULL, 'XP Champion',         'Earn 2,000 total XP',                             '💎', 'milestone', 'threshold', 2000,  'total_xp',       200, '#ec4899', 6),
(NULL, 'XP Legend',           'Earn 5,000 total XP',                             '👑', 'milestone', 'threshold', 5000,  'total_xp',       400, '#fbbf24', 7),
(NULL, 'XP Overlord',        'Earn 10,000 total XP',                            '🔱', 'milestone', 'threshold', 10000, 'total_xp',       750, '#f59e0b', 8),
(NULL, 'Streak Starter',      'Maintain a 3-day streak',                         '🔥', 'streak',    'streak',    3,  'streak_days',       50,  '#ef4444', 9),
(NULL, 'On Fire',             'Maintain a 7-day streak',                         '🔥', 'streak',    'streak',    7,  'streak_days',       150, '#ef4444', 10),
(NULL, 'Unstoppable',         'Maintain a 30-day streak',                        '🌟', 'streak',    'streak',    30, 'streak_days',       500, '#fbbf24', 11),
(NULL, 'Perfect Score',       'Get 100% on any activity',                        '💯', 'academic',  'count',     1,  'perfect_scores',    100, '#10b981', 12),
(NULL, 'Bookworm',            'Complete 10 reading activities',                  '📚', 'academic',  'count',     10, 'reading_completed', 200, '#6366f1', 13),
(NULL, 'Team Player',         'Join a team',                                     '🤝', 'social',    'count',     1,  'team_joined',       25,  '#06b6d4', 14),
(NULL, 'Early Bird',          'Complete a daily challenge',                      '🌅', 'milestone', 'count',     1,  'daily_completed',   50,  '#f97316', 15),
(NULL, 'Daily Devotee',       'Complete 10 daily challenges',                    '📅', 'milestone', 'count',     10, 'daily_completed',   200, '#f97316', 16),
(NULL, 'Level Up!',           'Reach Level 5',                                   '📈', 'milestone', 'threshold', 5,  'current_level',     100, '#8b5cf6', 17),
(NULL, 'High Achiever',       'Reach Level 10',                                  '🎯', 'milestone', 'threshold', 10, 'current_level',     250, '#ec4899', 18),
(NULL, 'Egg Hatcher',         'Your egg started cracking!',                      '🥚', 'milestone', 'threshold', 2,  'egg_stage',         75,  '#fbbf24', 19),
(NULL, 'Proud Parent',        'Your hatchling emerged!',                         '🐣', 'milestone', 'threshold', 3,  'egg_stage',         150, '#10b981', 20),
(NULL, 'Full Evolution',      'Reach the final evolution stage',                 '🐉', 'milestone', 'threshold', 5,  'egg_stage',         500, '#fbbf24', 21);

-- ============================================================
-- SEED: Default virtual rewards
-- ============================================================
INSERT INTO virtual_rewards (teacher_id, title, description, reward_type, icon, xp_cost, milestone_xp) VALUES
(NULL, 'Bronze Badge',     'A shiny bronze badge for your profile',    'cosmetic',    '🥉', 0, 500),
(NULL, 'Silver Badge',     'A gleaming silver badge',                  'cosmetic',    '🥈', 0, 2000),
(NULL, 'Gold Badge',       'The prestigious gold badge',               'cosmetic',    '🥇', 0, 5000),
(NULL, 'Star Student',     'A special star for outstanding effort',    'certificate', '⭐', 0, 10000),
(NULL, 'Custom Avatar',    'Unlock custom avatar options',             'cosmetic',    '🎨', 1000, NULL),
(NULL, 'Extra Break Time', '5 minutes of extra break time',           'privilege',   '⏰', 500,  NULL);

-- ============================================================
-- INDEXES
-- ============================================================
CREATE INDEX idx_sg_student ON student_gamification(student_id);
CREATE INDEX idx_sg_team ON student_gamification(team);
CREATE INDEX idx_xpt_source ON xp_transactions(source_type);
CREATE INDEX idx_ach_category ON achievements(category);
CREATE INDEX idx_sa_unlocked ON student_achievements(is_unlocked);
