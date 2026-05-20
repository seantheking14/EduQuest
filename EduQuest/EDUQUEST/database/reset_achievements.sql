-- ============================================================
-- RESET & REFRESH: Achievement Definitions + Student Progress
-- Run this to:
--   1. Add "Notable Newcomer" achievement (first-time login badge)
--   2. Clear ALL student_achievements so everyone starts fresh
--   3. Re-seed achievements with clean definitions
-- ============================================================

USE eduquest;

-- ── 1. Clear student achievement progress (fresh start) ──
DELETE FROM student_achievements;

-- ── 2. Clear existing seed achievements and re-insert ──
DELETE FROM achievements WHERE teacher_id IS NULL;

-- ── 3. Insert updated achievement definitions ──
INSERT INTO achievements (teacher_id, title, description, icon, category, achievement_type, target_value, target_metric, xp_reward, badge_color, sort_order) VALUES
-- Welcome / First-time
(NULL, 'Notable Newcomer',    'Welcome to EduQuest! You took your first step.',  '🌟', 'special',   'count',     1,  'first_login',       25,  '#fbbf24', 0),
-- Quests / Mini-games
(NULL, 'First Steps',         'Complete your first quest or mini-game',          '🌱', 'milestone', 'count',     1,  'quests_completed',  50,  '#10b981', 1),
(NULL, 'Quest Apprentice',    'Complete 5 quests or mini-games',                 '⚔️', 'milestone', 'count',     5,  'quests_completed',  100, '#3b82f6', 2),
(NULL, 'Quest Adventurer',    'Complete 10 quests or mini-games',                '🗺️', 'milestone', 'count',     10, 'quests_completed',  200, '#8b5cf6', 3),
(NULL, 'Quest Master',        'Complete 25 quests or mini-games',                '🏆', 'milestone', 'count',     25, 'quests_completed',  500, '#f59e0b', 4),
-- XP milestones
(NULL, 'XP Hunter',           'Earn 500 total XP',                               '⚡', 'milestone', 'threshold', 500,   'total_xp',       75,  '#8b5cf6', 5),
(NULL, 'XP Champion',         'Earn 2,000 total XP',                             '💎', 'milestone', 'threshold', 2000,  'total_xp',       200, '#ec4899', 6),
(NULL, 'XP Legend',           'Earn 5,000 total XP',                             '👑', 'milestone', 'threshold', 5000,  'total_xp',       400, '#fbbf24', 7),
(NULL, 'XP Overlord',        'Earn 10,000 total XP',                            '🔱', 'milestone', 'threshold', 10000, 'total_xp',       750, '#f59e0b', 8),
-- Streaks
(NULL, 'Streak Starter',      'Maintain a 3-day streak',                         '🔥', 'streak',    'streak',    3,  'streak_days',       50,  '#ef4444', 9),
(NULL, 'On Fire',             'Maintain a 7-day streak',                         '🔥', 'streak',    'streak',    7,  'streak_days',       150, '#ef4444', 10),
(NULL, 'Unstoppable',         'Maintain a 30-day streak',                        '🌟', 'streak',    'streak',    30, 'streak_days',       500, '#fbbf24', 11),
-- Academic
(NULL, 'Perfect Score',       'Get 100% on any activity',                        '💯', 'academic',  'count',     1,  'perfect_scores',    100, '#10b981', 12),
(NULL, 'Bookworm',            'Complete 10 reading activities',                  '📚', 'academic',  'count',     10, 'reading_completed', 200, '#6366f1', 13),
-- Social / Team
(NULL, 'Team Player',         'Join a team',                                     '🤝', 'social',    'count',     1,  'team_joined',       25,  '#06b6d4', 14),
-- Daily Challenges
(NULL, 'Early Bird',          'Complete a daily challenge',                      '🌅', 'milestone', 'count',     1,  'daily_completed',   50,  '#f97316', 15),
(NULL, 'Daily Devotee',       'Complete 10 daily challenges',                    '📅', 'milestone', 'count',     10, 'daily_completed',   200, '#f97316', 16),
-- Level milestones
(NULL, 'Level Up!',           'Reach Level 5',                                   '📈', 'milestone', 'threshold', 5,  'current_level',     100, '#8b5cf6', 17),
(NULL, 'High Achiever',       'Reach Level 10',                                  '🎯', 'milestone', 'threshold', 10, 'current_level',     250, '#ec4899', 18),
-- Egg evolution
(NULL, 'Egg Hatcher',         'Your egg started cracking!',                      '🥚', 'milestone', 'threshold', 2,  'egg_stage',         75,  '#fbbf24', 19),
(NULL, 'Proud Parent',        'Your hatchling emerged!',                         '🐣', 'milestone', 'threshold', 3,  'egg_stage',         150, '#10b981', 20),
(NULL, 'Full Evolution',      'Reach the final evolution stage',                 '🐉', 'milestone', 'threshold', 5,  'egg_stage',         500, '#fbbf24', 21);
