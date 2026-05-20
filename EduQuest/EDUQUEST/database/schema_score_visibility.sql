-- ═══════════════════════════════════════════════════════════
-- Score Visibility Migration
-- Adds teacher-controlled show_score flags to:
--   • teacher_quizzes         (per-quiz setting)
--   • gamification_settings   (global activity setting)
--
-- Run once against the eduquest database.
-- ═══════════════════════════════════════════════════════════

USE eduquest;

-- Per-quiz: teacher chooses whether students see their score/percentage
ALTER TABLE teacher_quizzes
    ADD COLUMN IF NOT EXISTS show_score TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1 = students see their score after completing; 0 = hidden'
        AFTER xp_reward;

-- Global activity setting: teacher chooses whether students see game scores
ALTER TABLE gamification_settings
    ADD COLUMN IF NOT EXISTS show_game_score TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1 = students see their score after activities; 0 = hidden'
        AFTER game_timer_seconds;
