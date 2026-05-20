-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 08:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eduquest`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodations`
--

CREATE TABLE `accommodations` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `category` enum('instructional','assessment','environmental','behavioral','technology','social_emotional','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(10) DEFAULT '⭐',
  `category` enum('academic','streak','social','milestone','special') DEFAULT 'academic',
  `achievement_type` enum('count','threshold','streak','custom') DEFAULT 'threshold',
  `target_value` int(10) UNSIGNED DEFAULT 1,
  `target_metric` varchar(100) DEFAULT NULL,
  `xp_reward` int(10) UNSIGNED DEFAULT 0,
  `badge_color` varchar(7) DEFAULT '#fbbf24',
  `is_hidden` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `achievements`
--

INSERT INTO `achievements` (`id`, `teacher_id`, `course_id`, `title`, `description`, `icon`, `category`, `achievement_type`, `target_value`, `target_metric`, `xp_reward`, `badge_color`, `is_hidden`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'First Steps', 'Complete your first quest', '🌱', 'milestone', 'count', 1, 'quests_completed', 50, '#10b981', 0, 1, 1, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(2, NULL, NULL, 'Quest Apprentice', 'Complete 5 quests', '⚔️', 'milestone', 'count', 5, 'quests_completed', 100, '#3b82f6', 0, 1, 2, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(3, NULL, NULL, 'Quest Master', 'Complete 25 quests', '🏆', 'milestone', 'count', 25, 'quests_completed', 500, '#f59e0b', 0, 1, 3, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(4, NULL, NULL, 'XP Hunter', 'Earn 1,000 total XP', '⚡', 'milestone', 'threshold', 1000, 'total_xp', 100, '#8b5cf6', 0, 1, 4, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(5, NULL, NULL, 'XP Champion', 'Earn 5,000 total XP', '💎', 'milestone', 'threshold', 5000, 'total_xp', 250, '#ec4899', 0, 1, 5, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(6, NULL, NULL, 'XP Legend', 'Earn 10,000 total XP', '👑', 'milestone', 'threshold', 10000, 'total_xp', 500, '#fbbf24', 0, 1, 6, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(7, NULL, NULL, 'Streak Starter', 'Maintain a 3-day streak', '🔥', 'streak', 'streak', 3, 'streak_days', 50, '#ef4444', 0, 1, 7, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(8, NULL, NULL, 'On Fire', 'Maintain a 7-day streak', '🔥', 'streak', 'streak', 7, 'streak_days', 150, '#ef4444', 0, 1, 8, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(9, NULL, NULL, 'Unstoppable', 'Maintain a 30-day streak', '🌟', 'streak', 'streak', 30, 'streak_days', 500, '#fbbf24', 0, 1, 9, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(10, NULL, NULL, 'Perfect Score', 'Get 100% on any assessment', '💯', 'academic', 'count', 1, 'perfect_scores', 100, '#10b981', 0, 1, 10, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(11, NULL, NULL, 'Bookworm', 'Complete 10 reading activities', '📚', 'academic', 'count', 10, 'reading_completed', 200, '#6366f1', 0, 1, 11, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(12, NULL, NULL, 'Team Player', 'Join a team', '🤝', 'social', 'count', 1, 'team_joined', 25, '#06b6d4', 0, 1, 12, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(13, NULL, NULL, 'Early Bird', 'Complete a daily challenge', '🌅', 'milestone', 'count', 1, 'daily_completed', 50, '#f97316', 0, 1, 13, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(14, NULL, NULL, 'Daily Devotee', 'Complete 10 daily challenges', '📅', 'milestone', 'count', 10, 'daily_completed', 200, '#f97316', 0, 1, 14, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(15, NULL, NULL, 'Level Up!', 'Reach Level 5', '📈', 'milestone', 'threshold', 5, 'current_level', 100, '#8b5cf6', 0, 1, 15, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(16, NULL, NULL, 'Egg Hatcher', 'Evolve your egg to Stage 2', '🥚', 'milestone', 'threshold', 2, 'egg_stage', 75, '#fbbf24', 0, 1, 16, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(17, NULL, NULL, 'Full Evolution', 'Reach the final egg evolution stage', '🐉', 'milestone', 'threshold', 5, 'egg_stage', 500, '#fbbf24', 0, 1, 17, '2026-04-12 07:51:46', '2026-04-12 07:51:46'),
(18, NULL, NULL, 'Notable Newcomer', 'Welcome to EduQuest! You took your first step.', '🌟', 'special', 'count', 1, 'first_login', 25, '#fbbf24', 0, 1, 0, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(21, NULL, NULL, 'Quest Adventurer', 'Complete 10 quests or mini-games', '🗺️', 'milestone', 'count', 10, 'quests_completed', 200, '#8b5cf6', 0, 1, 3, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(26, NULL, NULL, 'XP Overlord', 'Earn 10,000 total XP', '🔱', 'milestone', 'threshold', 10000, 'total_xp', 750, '#f59e0b', 0, 1, 8, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(36, NULL, NULL, 'High Achiever', 'Reach Level 10', '🎯', 'milestone', 'threshold', 10, 'current_level', 250, '#ec4899', 0, 1, 18, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(38, NULL, NULL, 'Proud Parent', 'Your hatchling emerged!', '🐣', 'milestone', 'threshold', 3, 'egg_stage', 150, '#10b981', 0, 1, 20, '2026-05-16 11:55:18', '2026-05-16 11:55:18');

-- --------------------------------------------------------

--
-- Table structure for table `adhd_profiles`
--

CREATE TABLE `adhd_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `adhd_type` enum('predominantly_inattentive','predominantly_hyperactive_impulsive','combined_presentation','other_specified','unspecified') NOT NULL,
  `severity` enum('mild','moderate','severe') DEFAULT 'moderate',
  `diagnosis_date` date DEFAULT NULL,
  `diagnosing_professional` varchar(200) DEFAULT NULL,
  `inattention_rating` tinyint(4) DEFAULT NULL CHECK (`inattention_rating` between 1 and 5),
  `hyperactivity_rating` tinyint(4) DEFAULT NULL CHECK (`hyperactivity_rating` between 1 and 5),
  `impulsivity_rating` tinyint(4) DEFAULT NULL CHECK (`impulsivity_rating` between 1 and 5),
  `has_reading_difficulty` tinyint(1) DEFAULT 0,
  `has_writing_difficulty` tinyint(1) DEFAULT 0,
  `has_math_difficulty` tinyint(1) DEFAULT 0,
  `has_focus_difficulty` tinyint(1) DEFAULT 0,
  `has_organization_difficulty` tinyint(1) DEFAULT 0,
  `has_time_management_difficulty` tinyint(1) DEFAULT 0,
  `has_working_memory_issues` tinyint(1) DEFAULT 0,
  `has_emotional_regulation_issues` tinyint(1) DEFAULT 0,
  `iep_in_place` tinyint(1) DEFAULT 0,
  `section_504_in_place` tinyint(1) DEFAULT 0,
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'EduQuest Admin', 'eduquestadmin@gmail.com', '$2y$10$9EroH3vxIeL82XAPfTPKLOxebSToqKVQb/nuk1QU9DFtohoYN11Oi', '2026-05-02 18:49:53'),
(2, 'Test Admin', 'testadmin@eduquest.test', '$2y$12$195XPcuaFHm6VUBqU1LbcOybNs9URy/Pl7MxrHhuJlwA11OrfTNTG', '2026-05-12 13:53:23');

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_log`
--

CREATE TABLE `admin_audit_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `target_role` varchar(50) DEFAULT NULL,
  `target_email` varchar(255) DEFAULT NULL,
  `target_name` varchar(200) DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `metadata_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_audit_log`
--

INSERT INTO `admin_audit_log` (`id`, `admin_id`, `action`, `target_user_id`, `target_role`, `target_email`, `target_name`, `reason`, `metadata_json`, `created_at`) VALUES
(1, 2, 'deactivate', 45, 'Teacher', 'testteacher@eduquest.test', 'Test Teacher', NULL, NULL, '2026-05-12 14:48:28'),
(2, 2, 'reactivate', 45, 'Teacher', 'testteacher@eduquest.test', 'Test Teacher', NULL, NULL, '2026-05-12 14:48:31'),
(3, 2, 'archive', 45, 'Teacher', 'testteacher@eduquest.test', 'Test Teacher', NULL, NULL, '2026-05-12 14:48:38'),
(4, 2, 'unarchive', 45, 'Teacher', 'testteacher@eduquest.test', 'Test Teacher', NULL, NULL, '2026-05-12 14:49:00'),
(5, 2, 'force_password_reset', 52, 'Student', 'teststudent2@eduquest.test', 'Demo Student', NULL, NULL, '2026-05-15 08:30:02');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_sessions`
--

CREATE TABLE `assessment_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `session_type` enum('pretest','posttest') NOT NULL,
  `status` enum('pending','in_progress','completed','disabled') NOT NULL DEFAULT 'pending',
  `initiated_by` int(10) UNSIGNED NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL COMMENT 'FK ??? course_materials.id (assignment)',
  `student_id` int(10) UNSIGNED NOT NULL COMMENT 'FK ??? students.id',
  `original_filename` varchar(500) DEFAULT NULL,
  `stored_filename` varchar(500) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Optional student notes with the submission',
  `status` enum('submitted','graded','returned') DEFAULT 'submitted',
  `grade` decimal(5,2) DEFAULT NULL COMMENT 'Teacher grade (optional)',
  `feedback` text DEFAULT NULL COMMENT 'Teacher feedback',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `graded_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `behavioral_logs`
--

CREATE TABLE `behavioral_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `log_type` enum('engagement','self_regulation') NOT NULL,
  `indicator_key` varchar(100) NOT NULL,
  `indicator_value` varchar(255) NOT NULL,
  `session_date` date NOT NULL,
  `logged_by` enum('system','teacher') NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `click_events`
--

CREATE TABLE `click_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `page_name` varchar(100) NOT NULL,
  `element_label` varchar(200) NOT NULL,
  `click_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `session_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `click_events`
--

INSERT INTO `click_events` (`id`, `student_id`, `page_name`, `element_label`, `click_count`, `session_date`, `created_at`) VALUES
(1, 37, 'dashboard', '🌟 My Progress', 2, '2026-05-15', '2026-05-15 09:59:23'),
(2, 37, 'dashboard', '📝', 1, '2026-05-15', '2026-05-15 10:11:20'),
(3, 37, 'learning', 'My Progress', 2, '2026-05-15', '2026-05-15 10:11:23'),
(4, 37, 'gamification', '🌟 My Progress', 1, '2026-05-15', '2026-05-15 10:11:24'),
(5, 37, 'gamification', '3 LEVEL Test 🔥 Team Fire 165 / 300 XP to Level 4', 1, '2026-05-15', '2026-05-15 10:11:54'),
(6, 37, 'dashboard', 'My Quests', 2, '2026-05-15', '2026-05-15 12:42:40'),
(8, 37, 'dashboard', 'Learn', 4, '2026-05-15', '2026-05-15 12:54:03'),
(9, 37, 'learning', '🎯 My Quests', 5, '2026-05-15', '2026-05-15 12:54:06'),
(13, 37, 'learning', 'View Material', 1, '2026-05-15', '2026-05-15 13:29:51'),
(14, 37, 'learning', 'Close viewer', 1, '2026-05-15', '2026-05-15 13:29:51'),
(15, 37, 'learning', 'My Quests', 4, '2026-05-15', '2026-05-15 13:29:51'),
(16, 37, 'learning', 'EduQuest 🏠 Home 📚 Learn 🎯 My Quests 📝 Quizzes 🌟 My Progress 🏆 Achievements', 1, '2026-05-15', '2026-05-15 13:31:11'),
(19, 37, 'learning', '► Repo 1 item', 1, '2026-05-15', '2026-05-15 13:32:58'),
(20, 37, 'learning', 'Repo', 1, '2026-05-15', '2026-05-15 13:32:58'),
(21, 37, 'learning', '📋 Assignment 1 📤 Submit Assignment', 1, '2026-05-15', '2026-05-15 13:32:58'),
(22, 37, 'learning', '📤 Submit Assignment', 1, '2026-05-15', '2026-05-15 13:32:58'),
(23, 37, 'learning', 'Learn', 1, '2026-05-15', '2026-05-15 13:33:07'),
(27, 37, 'dashboard', 'EduQuest 🏠 Home 📚 Learn 🎯 My Quests 📝 Quizzes 🌟 My Progress 🏆 Achievements', 1, '2026-05-15', '2026-05-15 15:04:26'),
(28, 37, 'dashboard', 'Quizzes', 1, '2026-05-15', '2026-05-15 15:04:26'),
(30, 37, 'gamification', 'Select Fire Team', 4, '2026-05-15', '2026-05-15 15:04:46'),
(31, 37, 'gamification', '🎯 My Quests', 1, '2026-05-15', '2026-05-15 15:04:46'),
(32, 37, 'dashboard', '🎯 My Quests', 2, '2026-05-15', '2026-05-15 15:09:25'),
(33, 37, 'gamification', 'My Quests', 1, '2026-05-15', '2026-05-15 15:09:33'),
(34, 37, 'gamification', 'Quizzes', 1, '2026-05-15', '2026-05-15 15:09:35'),
(35, 37, 'gamification', 'Select Water Team', 2, '2026-05-15', '2026-05-15 15:10:06'),
(36, 37, 'gamification', 'Select Grass Team', 1, '2026-05-15', '2026-05-15 15:10:06'),
(38, 37, 'gamification', '⚔️ CHOOSE YOUR TEAM Join a team to personalize your adventure!', 1, '2026-05-15', '2026-05-15 15:12:37'),
(39, 37, 'gamification', '📝 Quizzes', 1, '2026-05-15', '2026-05-15 15:12:42'),
(40, 37, 'gamification', '🏠 Home', 1, '2026-05-15', '2026-05-15 15:12:45'),
(43, 37, 'gamification', '🧑‍🎓', 1, '2026-05-15', '2026-05-15 15:16:05'),
(44, 37, 'gamification', 'Profile', 1, '2026-05-15', '2026-05-15 15:16:05'),
(45, 37, 'gamification', 'View all →', 2, '2026-05-15', '2026-05-15 15:20:49'),
(46, 37, 'gamification', 'Home', 1, '2026-05-15', '2026-05-15 15:20:58'),
(47, 37, 'dashboard', '📚 Learn', 1, '2026-05-15', '2026-05-15 15:21:00'),
(49, 37, 'learning', '📝 Quizzes', 1, '2026-05-15', '2026-05-15 15:21:05'),
(50, 37, 'gamification', 'Achievements', 1, '2026-05-15', '2026-05-15 15:21:07'),
(52, 37, 'dashboard', 'Hey, Test! 👋 🌙 Good evening! Time for some quest adventures! Lv 4 133 / 400 XP', 3, '2026-05-15', '2026-05-15 16:07:37'),
(54, 37, 'dashboard', '📝 Quizzes', 1, '2026-05-15', '2026-05-15 16:43:01'),
(55, 37, 'dashboard', 'Claim it!', 3, '2026-05-16', '2026-05-16 12:19:58'),
(56, 37, 'dashboard', 'Awesome!', 1, '2026-05-16', '2026-05-16 12:19:58'),
(57, 37, 'dashboard', 'My Quests', 7, '2026-05-16', '2026-05-16 12:19:58'),
(58, 37, 'dashboard', '🎯 My Quests', 5, '2026-05-16', '2026-05-16 12:20:10'),
(59, 37, 'dashboard', 'Hey, Test! 👋 🌙 Good evening! Time for some quest adventures! Lv 4 133 / 400 XP', 2, '2026-05-16', '2026-05-16 12:20:16'),
(60, 37, 'dashboard', 'Learn', 2, '2026-05-16', '2026-05-16 12:20:17'),
(61, 37, 'learning', '🎯 My Quests', 1, '2026-05-16', '2026-05-16 12:20:19'),
(67, 37, 'dashboard', '🏠 Home 📚 Learn 🎯 My Quests 📝 Quizzes 🌟 My Progress 🏆 Achievements 🥇 Leade', 1, '2026-05-16', '2026-05-16 13:10:19'),
(72, 37, 'gamification', 'My Quests', 3, '2026-05-16', '2026-05-16 15:02:00'),
(74, 37, 'dashboard', 'EduQuest 🏠 Home 📚 Learn 🎯 My Quests 📝 Quizzes 🌟 My Progress 🏆 Achievements', 2, '2026-05-16', '2026-05-16 15:05:15'),
(77, 37, 'learning', 'Home', 1, '2026-05-16', '2026-05-16 15:05:26'),
(79, 37, 'learning', 'My Quests', 1, '2026-05-16', '2026-05-16 15:05:29'),
(80, 37, 'gamification', '🎯 My Quests', 1, '2026-05-16', '2026-05-16 15:05:33'),
(82, 37, 'dashboard', '🎯', 1, '2026-05-16', '2026-05-16 15:31:01'),
(83, 37, 'dashboard', 'My Progress', 1, '2026-05-16', '2026-05-16 17:48:16'),
(84, 37, 'gamification', 'Achievements', 1, '2026-05-16', '2026-05-16 17:48:17'),
(85, 37, 'dashboard', 'Achievements', 1, '2026-05-16', '2026-05-16 17:51:11'),
(87, 37, 'dashboard', 'Leaderboard', 1, '2026-05-16', '2026-05-16 17:51:54'),
(88, 37, 'gamification', '📝 Quizzes', 1, '2026-05-16', '2026-05-16 17:52:26'),
(89, 37, 'gamification', '🏆 Achievements', 1, '2026-05-16', '2026-05-16 17:52:27'),
(90, 37, 'dashboard', 'Claim it!', 6, '2026-05-18', '2026-05-18 07:39:34'),
(91, 37, 'dashboard', 'Awesome!', 1, '2026-05-18', '2026-05-18 07:39:34'),
(92, 37, 'dashboard', 'Learn', 4, '2026-05-18', '2026-05-18 07:39:34'),
(93, 37, 'learning', 'My Quests', 3, '2026-05-18', '2026-05-18 07:39:38'),
(94, 37, 'gamification', 'Quizzes', 3, '2026-05-18', '2026-05-18 07:39:44'),
(95, 37, 'gamification', 'Achievements', 4, '2026-05-18', '2026-05-18 07:39:48'),
(98, 37, 'learning', '🎯', 3, '2026-05-18', '2026-05-18 07:40:02'),
(100, 37, 'gamification', '🏆 Achievements', 4, '2026-05-18', '2026-05-18 07:44:37'),
(104, 37, 'learning', '☁️ ☁️ ⛅ 🗺️ Learning Adventures Explore your course materials and learning resou', 1, '2026-05-18', '2026-05-18 08:01:44'),
(106, 37, 'gamification', '🌟 My Progress Track your growth, evolve your companion, and earn rewards!', 2, '2026-05-18', '2026-05-18 08:05:56'),
(107, 37, 'gamification', '📊 My Progress Overview', 2, '2026-05-18', '2026-05-18 08:06:26'),
(111, 37, 'gamification', '📝 Quizzes', 3, '2026-05-18', '2026-05-18 08:19:37'),
(113, 37, 'dashboard', '🌟 My Progress', 1, '2026-05-18', '2026-05-18 08:19:44'),
(114, 37, 'gamification', '🏠 Home', 3, '2026-05-18', '2026-05-18 08:19:45'),
(118, 37, 'gamification', '🌟 My Progress Track your growth, evolve your companion, and earn rewards! 📊 My', 2, '2026-05-18', '2026-05-18 08:23:05'),
(120, 37, 'gamification', '8 LEVEL Test 🔥 Team Fire 422 / 800 XP to Level 9 🏅 TEAM PROGRESS 🔥 100% 💧 0%', 2, '2026-05-18', '2026-05-18 08:23:35'),
(122, 37, 'gamification', '⚡ Daily XP Progress', 1, '2026-05-18', '2026-05-18 08:24:05'),
(123, 37, 'gamification', '🥚 COMPANION EVOLUTION Iggy Hatchling Level 8 → Next evolution at Level 12 (20%)', 1, '2026-05-18', '2026-05-18 08:24:35'),
(124, 37, 'gamification', '⚔️ YOUR TEAM You\'re a proud member of your team! 🔥 Team Fire Courage & Determin', 1, '2026-05-18', '2026-05-18 08:24:35'),
(125, 37, 'gamification', 'Leaderboard', 1, '2026-05-18', '2026-05-18 08:30:17'),
(127, 37, 'gamification', 'Test', 1, '2026-05-18', '2026-05-18 08:30:54'),
(130, 37, 'learning', 'View Material', 3, '2026-05-18', '2026-05-18 08:35:24'),
(131, 37, 'learning', 'Close viewer', 1, '2026-05-18', '2026-05-18 08:35:24'),
(132, 37, 'learning', '📝 Quizzes', 2, '2026-05-18', '2026-05-18 08:35:24'),
(134, 37, 'dashboard', 'My Quests', 1, '2026-05-18', '2026-05-18 08:44:04'),
(135, 37, 'dashboard', '🏆 Achievements', 1, '2026-05-18', '2026-05-18 08:44:48'),
(136, 37, 'dashboard', 'Hey, Test! 👋 🌤️ Good afternoon! Let’s keep the streak going! Lv 8 422 / 800 XP', 2, '2026-05-18', '2026-05-18 08:45:48'),
(137, 37, 'dashboard', '🌤️ Good afternoon! Let’s keep the streak going!', 2, '2026-05-18', '2026-05-18 08:45:48'),
(138, 37, 'dashboard', '🎯 My Quests', 1, '2026-05-18', '2026-05-18 08:45:52'),
(140, 37, 'dashboard', '📚', 1, '2026-05-18', '2026-05-18 08:48:48'),
(141, 37, 'learning', 'Home', 1, '2026-05-18', '2026-05-18 08:50:25'),
(143, 37, 'dashboard', '📚 Learn', 2, '2026-05-18', '2026-05-18 08:50:30'),
(144, 37, 'learning', '🎯 My Quests', 2, '2026-05-18', '2026-05-18 08:50:31'),
(146, 37, 'learning', 'Quizzes', 1, '2026-05-18', '2026-05-18 08:51:23'),
(151, 37, 'gamification', 'EduQuest 🏠 Home 📚 Learn 🎯 My Quests 📝 Quizzes 🌟 My Progress 🏆 Achievements', 1, '2026-05-18', '2026-05-18 10:15:57'),
(152, 37, 'gamification', 'My Quests', 1, '2026-05-18', '2026-05-18 10:15:58');

-- --------------------------------------------------------

--
-- Table structure for table `comorbid_conditions`
--

CREATE TABLE `comorbid_conditions` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `condition_name` varchar(200) NOT NULL,
  `condition_category` enum('neurodevelopmental','mood_disorder','anxiety_disorder','learning_disability','behavioral_disorder','sleep_disorder','sensory_processing','other') DEFAULT 'other',
  `severity` enum('mild','moderate','severe') DEFAULT NULL,
  `diagnosed_by` varchar(200) DEFAULT NULL,
  `diagnosis_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `grade_level` varchar(30) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `cover_color` varchar(7) DEFAULT '#6366f1',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `teacher_id`, `title`, `description`, `subject`, `grade_level`, `school_year`, `cover_color`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 24, 'Mathematics 1', 'Basic Mathematics', 'Mathematics', 'Grade 3', '2025-2026', '#6366f1', 1, '2026-05-04 15:13:02', '2026-05-04 15:13:02');

-- --------------------------------------------------------

--
-- Table structure for table `course_announcements`
--

CREATE TABLE `course_announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_enrollments`
--

CREATE TABLE `course_enrollments` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_enrollments`
--

INSERT INTO `course_enrollments` (`id`, `course_id`, `student_id`, `enrolled_at`) VALUES
(2, 4, 37, '2026-05-04 15:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `material_type` enum('file','link','text','assignment') DEFAULT 'file',
  `content` text DEFAULT NULL,
  `original_filename` varchar(500) DEFAULT NULL,
  `stored_filename` varchar(500) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `position` int(10) UNSIGNED DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_materials`
--

INSERT INTO `course_materials` (`id`, `module_id`, `course_id`, `title`, `description`, `material_type`, `content`, `original_filename`, `stored_filename`, `file_size`, `mime_type`, `position`, `is_visible`, `due_date`, `created_at`, `updated_at`) VALUES
(16, 4, 4, 'Addition', 'Basic Addition', 'file', NULL, 'IEP-NEW-SKYLER.pdf', 'b934a9fde3924261a7b186f8bbe55ffd.pdf', 583144, 'application/pdf', 0, 1, NULL, '2026-05-04 15:18:34', '2026-05-04 15:18:34'),
(19, 7, 4, 'Assignment 1', '', 'assignment', '', NULL, NULL, NULL, NULL, 0, 1, NULL, '2026-05-15 13:32:04', '2026-05-15 13:32:04');

-- --------------------------------------------------------

--
-- Table structure for table `course_modules`
--

CREATE TABLE `course_modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `position` int(10) UNSIGNED DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_modules`
--

INSERT INTO `course_modules` (`id`, `course_id`, `title`, `description`, `position`, `is_visible`, `created_at`) VALUES
(4, 4, 'Week 1', '', 0, 1, '2026-05-04 15:13:09'),
(7, 4, 'Repo', '', 1, 1, '2026-05-15 13:31:49');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_verification_tokens`
--

INSERT INTO `email_verification_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(44, 53, '7706f70888d707b5442a3c0ccbdc00db1470546872fc3178d2008149995ced60', '2026-05-19 07:27:21', '2026-05-18 07:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_type` varchar(50) NOT NULL COMMENT 'word_scramble | activity',
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `game_type`, `name`, `description`, `is_active`) VALUES
(1, 'word_scramble', 'Word Scramble', 'Unscramble vocabulary words against the clock', 1),
(2, 'activity', 'Activity Game', 'Interactive learning activity game', 1);

-- --------------------------------------------------------

--
-- Table structure for table `game_assignments`
--

CREATE TABLE `game_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `max_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
  `due_date` date DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_attempts`
--

CREATE TABLE `game_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED DEFAULT NULL,
  `score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `max_score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `xp_earned` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `time_spent_sec` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_abandoned` tinyint(1) NOT NULL DEFAULT 0,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_attempts`
--

INSERT INTO `game_attempts` (`id`, `student_id`, `game_id`, `assignment_id`, `score`, `max_score`, `percentage`, `xp_earned`, `time_spent_sec`, `is_abandoned`, `started_at`, `completed_at`) VALUES
(1, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-16 12:20:03', '2026-05-16 12:20:06'),
(2, 37, 2, NULL, 0, 0, 0.00, 0, 0, 0, '2026-05-16 12:23:48', NULL),
(3, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-16 12:45:24', '2026-05-16 12:46:05'),
(4, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-16 12:53:32', '2026-05-16 12:53:42'),
(5, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-16 12:53:43', '2026-05-16 12:53:45'),
(6, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-16 15:00:04', '2026-05-16 15:00:09'),
(7, 37, 2, NULL, 750, 600, 125.00, 94, 38, 0, '2026-05-16 15:02:01', '2026-05-16 15:02:39'),
(8, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-16 15:05:24', '2026-05-16 15:05:25'),
(9, 37, 2, NULL, 850, 800, 106.25, 106, 27, 0, '2026-05-16 15:15:32', '2026-05-16 15:15:59'),
(10, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-16 15:31:11', '2026-05-16 15:31:20'),
(11, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 08:51:32', '2026-05-18 08:51:35'),
(12, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 09:47:46', '2026-05-18 09:47:52'),
(13, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 09:55:15', '2026-05-18 10:04:28'),
(14, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:07:23', '2026-05-18 10:07:27'),
(15, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:07:33', '2026-05-18 10:07:35'),
(16, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:07:40', '2026-05-18 10:07:42'),
(17, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:07:47', '2026-05-18 10:07:50'),
(18, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:07:55', '2026-05-18 10:07:57'),
(19, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:00', '2026-05-18 10:08:04'),
(20, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:09', '2026-05-18 10:08:11'),
(21, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:16', '2026-05-18 10:08:18'),
(22, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:20', '2026-05-18 10:08:21'),
(23, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:22', '2026-05-18 10:08:25'),
(24, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:26', '2026-05-18 10:08:29'),
(25, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:31', '2026-05-18 10:08:33'),
(26, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:35', '2026-05-18 10:08:40'),
(27, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:08:43', '2026-05-18 10:08:46'),
(28, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:12:51', '2026-05-18 10:12:55'),
(29, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:12:58', '2026-05-18 10:13:04'),
(30, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:13:09', '2026-05-18 10:14:28'),
(31, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:16:06', '2026-05-18 10:16:10'),
(32, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:18:41', '2026-05-18 10:29:05'),
(33, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:29:11', '2026-05-18 10:29:19'),
(34, 37, 2, NULL, 0, 0, 0.00, 0, 0, 1, '2026-05-18 10:29:21', '2026-05-18 10:29:25');

-- --------------------------------------------------------

--
-- Table structure for table `gamification_settings`
--

CREATE TABLE `gamification_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `xp_multiplier` decimal(3,2) DEFAULT 1.00,
  `difficulty_level` enum('easy','moderate','challenging') DEFAULT 'moderate',
  `achievements_enabled` tinyint(1) DEFAULT 1,
  `leaderboard_mode` enum('enabled','top_only','disabled') DEFAULT 'disabled',
  `leaderboard_top_n` tinyint(3) UNSIGNED DEFAULT 5,
  `egg_evolution_enabled` tinyint(1) DEFAULT 1,
  `teams_enabled` tinyint(1) DEFAULT 1,
  `daily_challenges_enabled` tinyint(1) DEFAULT 1,
  `streaks_enabled` tinyint(1) DEFAULT 1,
  `max_daily_xp` int(10) UNSIGNED DEFAULT 500,
  `notification_frequency` enum('all','important','minimal') DEFAULT 'important',
  `animation_level` enum('full','reduced','none') DEFAULT 'reduced',
  `quiz_timer_seconds` int(10) UNSIGNED DEFAULT 30,
  `game_timer_seconds` int(10) UNSIGNED DEFAULT 30,
  `show_game_score` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = students see their score after activities; 0 = hidden',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gamification_settings`
--

INSERT INTO `gamification_settings` (`id`, `teacher_id`, `course_id`, `xp_multiplier`, `difficulty_level`, `achievements_enabled`, `leaderboard_mode`, `leaderboard_top_n`, `egg_evolution_enabled`, `teams_enabled`, `daily_challenges_enabled`, `streaks_enabled`, `max_daily_xp`, `notification_frequency`, `animation_level`, `quiz_timer_seconds`, `game_timer_seconds`, `show_game_score`, `created_at`, `updated_at`) VALUES
(3, 24, NULL, 1.00, 'moderate', 1, '', 1, 1, 1, 1, 1, 500, 'important', 'reduced', 30, 30, 1, '2026-05-16 17:51:38', '2026-05-16 17:51:38');

-- --------------------------------------------------------

--
-- Table structure for table `hover_events`
--

CREATE TABLE `hover_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `page_name` varchar(100) NOT NULL,
  `element_label` varchar(200) NOT NULL,
  `total_hover_ms` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `session_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hover_events`
--

INSERT INTO `hover_events` (`id`, `student_id`, `page_name`, `element_label`, `total_hover_ms`, `session_date`, `created_at`) VALUES
(1, 37, 'dashboard', 'Leaderboard', 2380, '2026-05-15', '2026-05-15 10:11:20'),
(2, 37, 'dashboard', 'Trophy Room', 194, '2026-05-15', '2026-05-15 10:11:20'),
(3, 37, 'dashboard', 'Play & Learn', 697, '2026-05-15', '2026-05-15 10:11:20'),
(7, 37, 'dashboard', 'Learning Modules', 928, '2026-05-15', '2026-05-15 12:54:03'),
(9, 37, 'learning', 'View Material', 430, '2026-05-15', '2026-05-15 13:29:51'),
(14, 37, 'gamification', 'Select Fire Team', 1352, '2026-05-15', '2026-05-15 15:04:46'),
(16, 37, 'gamification', 'Select Water Team', 1768, '2026-05-15', '2026-05-15 15:10:06'),
(17, 37, 'gamification', 'Select Grass Team', 645, '2026-05-15', '2026-05-15 15:10:06'),
(30, 37, 'dashboard', 'Trophy Room', 4312, '2026-05-16', '2026-05-16 12:19:58'),
(31, 37, 'dashboard', 'Learning Modules', 2904, '2026-05-16', '2026-05-16 12:19:58'),
(32, 37, 'dashboard', 'Play & Learn', 768, '2026-05-16', '2026-05-16 12:19:58'),
(50, 37, 'dashboard', 'Leaderboard', 9, '2026-05-16', '2026-05-16 15:05:15'),
(61, 37, 'dashboard', 'Trophy Room', 923, '2026-05-18', '2026-05-18 07:39:34'),
(62, 37, 'dashboard', 'Leaderboard', 28, '2026-05-18', '2026-05-18 08:21:00'),
(63, 37, 'dashboard', 'Play & Learn', 416, '2026-05-18', '2026-05-18 08:21:00'),
(64, 37, 'learning', 'View Material', 1036, '2026-05-18', '2026-05-18 08:35:24'),
(65, 37, 'dashboard', 'Learning Modules', 1126, '2026-05-18', '2026-05-18 08:36:14'),
(70, 37, 'dashboard', 'My Progress', 3, '2026-05-18', '2026-05-18 08:50:30');

-- --------------------------------------------------------

--
-- Table structure for table `import_logs`
--

CREATE TABLE `import_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `import_type` enum('csv','document') NOT NULL,
  `filename` varchar(500) NOT NULL,
  `stored_filename` varchar(500) DEFAULT NULL,
  `total_rows` int(10) UNSIGNED DEFAULT 0,
  `success_rows` int(10) UNSIGNED DEFAULT 0,
  `failed_rows` int(10) UNSIGNED DEFAULT 0,
  `error_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`error_details`)),
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `lesson_order` int(10) UNSIGNED DEFAULT 0,
  `difficulty` enum('easy','medium','hard') DEFAULT 'easy',
  `xp_reward` int(10) UNSIGNED DEFAULT 30,
  `estimated_minutes` int(10) UNSIGNED DEFAULT 10,
  `icon` varchar(10) DEFAULT '????',
  `content_type` enum('reading','interactive','video','mixed') DEFAULT 'mixed',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `subject_id`, `title`, `description`, `lesson_order`, `difficulty`, `xp_reward`, `estimated_minutes`, `icon`, `content_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Counting Fun', 'Learn to count from 1 to 20 with fun objects!', 1, 'easy', 25, 8, '????', 'interactive', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(2, 1, 'Addition Adventures', 'Discover how to add numbers together!', 2, 'easy', 30, 10, '???', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(3, 1, 'Subtraction Quest', 'Learn to subtract and find the difference!', 3, 'easy', 30, 10, '???', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(4, 1, 'Shapes Explorer', 'Identify and learn about different shapes!', 4, 'medium', 35, 12, '????', 'interactive', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(5, 1, 'Multiplication Magic', 'Unlock the power of multiplication!', 5, 'medium', 40, 15, '??????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(6, 1, 'Division Discovery', 'Share equally and learn to divide!', 6, 'medium', 40, 15, '???', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(7, 1, 'Fractions Feast', 'Slice pizzas and pies to learn fractions!', 7, 'hard', 50, 18, '????', 'interactive', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(8, 1, 'Math Champion', 'Put all your math skills to the ultimate test!', 8, 'hard', 60, 20, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(9, 2, 'My Feelings', 'Learn to name and understand your emotions!', 1, 'easy', 25, 8, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(10, 2, 'Breathing Buddy', 'Practice calm breathing exercises!', 2, 'easy', 25, 8, '???????', 'interactive', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(11, 2, 'Healthy Habits', 'Discover daily habits that keep you strong!', 3, 'easy', 30, 10, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(12, 2, 'Friendship Garden', 'Learn about being a good friend!', 4, 'medium', 35, 12, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(13, 2, 'Mindfulness Mountain', 'Climb the mountain of mindfulness!', 5, 'medium', 35, 12, '??????', 'interactive', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(14, 2, 'Problem Solving Path', 'Learn steps to solve problems calmly!', 6, 'medium', 40, 15, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(15, 2, 'Self Care Champion', 'Show what you know about taking care of yourself!', 7, 'hard', 50, 18, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(16, 3, 'Alphabet Adventure', 'Meet all 26 letters and their sounds!', 1, 'easy', 25, 8, '????', 'interactive', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(17, 3, 'Sight Words Safari', 'Spot and learn common sight words!', 2, 'easy', 30, 10, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(18, 3, 'Story Time', 'Read short stories and answer fun questions!', 3, 'easy', 30, 10, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(19, 3, 'Spelling Spell', 'Cast spelling spells to form words correctly!', 4, 'medium', 35, 12, '???', 'interactive', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(20, 3, 'Grammar Garden', 'Grow sentences in the grammar garden!', 5, 'medium', 40, 15, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(21, 3, 'Creative Writing', 'Write your own mini stories!', 6, 'hard', 45, 18, '??????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(22, 3, 'Reading Champion', 'Prove your reading skills in the final challenge!', 7, 'hard', 50, 20, '????', 'mixed', 1, '2026-04-18 14:31:25', '2026-04-18 14:31:25'),
(23, 1, 'Counting Fun', 'Learn to count from 1 to 20 with fun objects!', 1, 'easy', 25, 8, '🎯', 'interactive', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(24, 1, 'Addition Adventures', 'Discover how to add numbers together!', 2, 'easy', 30, 10, '➕', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(25, 1, 'Subtraction Quest', 'Learn to subtract and find the difference!', 3, 'easy', 30, 10, '➖', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(26, 1, 'Shapes Explorer', 'Identify and learn about different shapes!', 4, 'medium', 35, 12, '🔷', 'interactive', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(27, 1, 'Multiplication Magic', 'Unlock the power of multiplication!', 5, 'medium', 40, 15, '✖️', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(28, 1, 'Division Discovery', 'Share equally and learn to divide!', 6, 'medium', 40, 15, '➗', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(29, 1, 'Fractions Feast', 'Slice pizzas and pies to learn fractions!', 7, 'hard', 50, 18, '🍕', 'interactive', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(30, 1, 'Math Champion', 'Put all your math skills to the ultimate test!', 8, 'hard', 60, 20, '🏆', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(31, 2, 'My Feelings', 'Learn to name and understand your emotions!', 1, 'easy', 25, 8, '😊', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(32, 2, 'Breathing Buddy', 'Practice calm breathing exercises!', 2, 'easy', 25, 8, '🌬️', 'interactive', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(33, 2, 'Healthy Habits', 'Discover daily habits that keep you strong!', 3, 'easy', 30, 10, '💪', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(34, 2, 'Friendship Garden', 'Learn about being a good friend!', 4, 'medium', 35, 12, '🌻', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(35, 2, 'Mindfulness Mountain', 'Climb the mountain of mindfulness!', 5, 'medium', 35, 12, '⛰️', 'interactive', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(36, 2, 'Problem Solving Path', 'Learn steps to solve problems calmly!', 6, 'medium', 40, 15, '🧩', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(37, 2, 'Self Care Champion', 'Show what you know about taking care of yourself!', 7, 'hard', 50, 18, '🌟', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(38, 3, 'Alphabet Adventure', 'Meet all 26 letters and their sounds!', 1, 'easy', 25, 8, '🔤', 'interactive', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(39, 3, 'Sight Words Safari', 'Spot and learn common sight words!', 2, 'easy', 30, 10, '👀', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(40, 3, 'Story Time', 'Read short stories and answer fun questions!', 3, 'easy', 30, 10, '📚', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(41, 3, 'Spelling Spell', 'Cast spelling spells to form words correctly!', 4, 'medium', 35, 12, '✨', 'interactive', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(42, 3, 'Grammar Garden', 'Grow sentences in the grammar garden!', 5, 'medium', 40, 15, '🌿', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(43, 3, 'Creative Writing', 'Write your own mini stories!', 6, 'hard', 45, 18, '✍️', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(44, 3, 'Reading Champion', 'Prove your reading skills in the final challenge!', 7, 'hard', 50, 20, '🏆', 'mixed', 1, '2026-05-16 11:55:18', '2026-05-16 11:55:18'),
(45, 1, 'Counting Fun', 'Learn to count from 1 to 20 with fun objects!', 1, 'easy', 25, 8, '🎯', 'interactive', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(46, 1, 'Addition Adventures', 'Discover how to add numbers together!', 2, 'easy', 30, 10, '➕', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(47, 1, 'Subtraction Quest', 'Learn to subtract and find the difference!', 3, 'easy', 30, 10, '➖', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(48, 1, 'Shapes Explorer', 'Identify and learn about different shapes!', 4, 'medium', 35, 12, '🔷', 'interactive', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(49, 1, 'Multiplication Magic', 'Unlock the power of multiplication!', 5, 'medium', 40, 15, '✖️', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(50, 1, 'Division Discovery', 'Share equally and learn to divide!', 6, 'medium', 40, 15, '➗', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(51, 1, 'Fractions Feast', 'Slice pizzas and pies to learn fractions!', 7, 'hard', 50, 18, '🍕', 'interactive', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(52, 1, 'Math Champion', 'Put all your math skills to the ultimate test!', 8, 'hard', 60, 20, '🏆', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(53, 2, 'My Feelings', 'Learn to name and understand your emotions!', 1, 'easy', 25, 8, '😊', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(54, 2, 'Breathing Buddy', 'Practice calm breathing exercises!', 2, 'easy', 25, 8, '🌬️', 'interactive', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(55, 2, 'Healthy Habits', 'Discover daily habits that keep you strong!', 3, 'easy', 30, 10, '💪', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(56, 2, 'Friendship Garden', 'Learn about being a good friend!', 4, 'medium', 35, 12, '🌻', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(57, 2, 'Mindfulness Mountain', 'Climb the mountain of mindfulness!', 5, 'medium', 35, 12, '⛰️', 'interactive', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(58, 2, 'Problem Solving Path', 'Learn steps to solve problems calmly!', 6, 'medium', 40, 15, '🧩', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(59, 2, 'Self Care Champion', 'Show what you know about taking care of yourself!', 7, 'hard', 50, 18, '🌟', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(60, 3, 'Alphabet Adventure', 'Meet all 26 letters and their sounds!', 1, 'easy', 25, 8, '🔤', 'interactive', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(61, 3, 'Sight Words Safari', 'Spot and learn common sight words!', 2, 'easy', 30, 10, '👀', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(62, 3, 'Story Time', 'Read short stories and answer fun questions!', 3, 'easy', 30, 10, '📚', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(63, 3, 'Spelling Spell', 'Cast spelling spells to form words correctly!', 4, 'medium', 35, 12, '✨', 'interactive', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(64, 3, 'Grammar Garden', 'Grow sentences in the grammar garden!', 5, 'medium', 40, 15, '🌿', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(65, 3, 'Creative Writing', 'Write your own mini stories!', 6, 'hard', 45, 18, '✍️', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(66, 3, 'Reading Champion', 'Prove your reading skills in the final challenge!', 7, 'hard', 50, 20, '🏆', 'mixed', 1, '2026-05-16 11:55:33', '2026-05-16 11:55:33'),
(67, 1, 'Counting Fun', 'Learn to count from 1 to 20 with fun objects!', 1, 'easy', 25, 8, '🎯', 'interactive', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(68, 1, 'Addition Adventures', 'Discover how to add numbers together!', 2, 'easy', 30, 10, '➕', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(69, 1, 'Subtraction Quest', 'Learn to subtract and find the difference!', 3, 'easy', 30, 10, '➖', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(70, 1, 'Shapes Explorer', 'Identify and learn about different shapes!', 4, 'medium', 35, 12, '🔷', 'interactive', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(71, 1, 'Multiplication Magic', 'Unlock the power of multiplication!', 5, 'medium', 40, 15, '✖️', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(72, 1, 'Division Discovery', 'Share equally and learn to divide!', 6, 'medium', 40, 15, '➗', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(73, 1, 'Fractions Feast', 'Slice pizzas and pies to learn fractions!', 7, 'hard', 50, 18, '🍕', 'interactive', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(74, 1, 'Math Champion', 'Put all your math skills to the ultimate test!', 8, 'hard', 60, 20, '🏆', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(75, 2, 'My Feelings', 'Learn to name and understand your emotions!', 1, 'easy', 25, 8, '😊', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(76, 2, 'Breathing Buddy', 'Practice calm breathing exercises!', 2, 'easy', 25, 8, '🌬️', 'interactive', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(77, 2, 'Healthy Habits', 'Discover daily habits that keep you strong!', 3, 'easy', 30, 10, '💪', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(78, 2, 'Friendship Garden', 'Learn about being a good friend!', 4, 'medium', 35, 12, '🌻', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(79, 2, 'Mindfulness Mountain', 'Climb the mountain of mindfulness!', 5, 'medium', 35, 12, '⛰️', 'interactive', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(80, 2, 'Problem Solving Path', 'Learn steps to solve problems calmly!', 6, 'medium', 40, 15, '🧩', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(81, 2, 'Self Care Champion', 'Show what you know about taking care of yourself!', 7, 'hard', 50, 18, '🌟', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(82, 3, 'Alphabet Adventure', 'Meet all 26 letters and their sounds!', 1, 'easy', 25, 8, '🔤', 'interactive', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(83, 3, 'Sight Words Safari', 'Spot and learn common sight words!', 2, 'easy', 30, 10, '👀', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(84, 3, 'Story Time', 'Read short stories and answer fun questions!', 3, 'easy', 30, 10, '📚', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(85, 3, 'Spelling Spell', 'Cast spelling spells to form words correctly!', 4, 'medium', 35, 12, '✨', 'interactive', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(86, 3, 'Grammar Garden', 'Grow sentences in the grammar garden!', 5, 'medium', 40, 15, '🌿', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(87, 3, 'Creative Writing', 'Write your own mini stories!', 6, 'hard', 45, 18, '✍️', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14'),
(88, 3, 'Reading Champion', 'Prove your reading skills in the final challenge!', 7, 'hard', 50, 20, '🏆', 'mixed', 1, '2026-05-16 11:56:14', '2026-05-16 11:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_content`
--

CREATE TABLE `lesson_content` (
  `id` int(10) UNSIGNED NOT NULL,
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `page_order` int(10) UNSIGNED DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `content_html` text NOT NULL,
  `illustration` varchar(100) DEFAULT NULL,
  `tip_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lesson_content`
--

INSERT INTO `lesson_content` (`id`, `lesson_id`, `page_order`, `title`, `content_html`, `illustration`, `tip_text`, `created_at`) VALUES
(1, 1, 1, 'Welcome to Counting!', '<h2>Let\'s Count Together! ????</h2><p>Counting is one of the most important skills you\'ll ever learn. Numbers are everywhere ??? from the fingers on your hand to the stars in the sky!</p><p>In this lesson, you\'ll learn to count from <strong>1 to 20</strong> using fun objects.</p>', 'illust-stars', 'Fun fact: The number zero was invented in India over 1,500 years ago!', '2026-04-18 14:31:25'),
(2, 1, 2, 'Counting 1 to 5', '<h2>Numbers 1 to 5 ???????</h2><div class=\"count-grid\"><div class=\"count-item\"><span class=\"count-num\">1</span><span class=\"count-obj\">????</span><span>One apple</span></div><div class=\"count-item\"><span class=\"count-num\">2</span><span class=\"count-obj\">????????</span><span>Two apples</span></div><div class=\"count-item\"><span class=\"count-num\">3</span><span class=\"count-obj\">????????????</span><span>Three apples</span></div><div class=\"count-item\"><span class=\"count-num\">4</span><span class=\"count-obj\">????????????????</span><span>Four apples</span></div><div class=\"count-item\"><span class=\"count-num\">5</span><span class=\"count-obj\">????????????????????</span><span>Five apples</span></div></div>', 'illust-apples', 'You have 5 fingers on each hand ??? just like 5 apples!', '2026-04-18 14:31:25'),
(3, 1, 3, 'Counting 6 to 10', '<h2>Numbers 6 to 10 ??????</h2><div class=\"count-grid\"><div class=\"count-item\"><span class=\"count-num\">6</span><span class=\"count-obj\">??????????????????</span><span>Six stars</span></div><div class=\"count-item\"><span class=\"count-num\">7</span><span class=\"count-obj\">?????????????????????</span><span>Seven stars</span></div><div class=\"count-item\"><span class=\"count-num\">8</span><span class=\"count-obj\">????????????????????????</span><span>Eight stars</span></div><div class=\"count-item\"><span class=\"count-num\">9</span><span class=\"count-obj\">???????????????????????????</span><span>Nine stars</span></div><div class=\"count-item\"><span class=\"count-num\">10</span><span class=\"count-obj\">??????????????????????????????</span><span>Ten stars</span></div></div>', 'illust-stars', 'There are 10 digits: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9. Every number is made from these!', '2026-04-18 14:31:25'),
(4, 1, 4, 'Counting to 20', '<h2>All the Way to 20! ????</h2><p>Great job! Now let\'s count higher. After 10, we keep going!</p><div class=\"number-line\"><span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span><span>18</span><span>19</span><span>20</span></div><p>Notice a pattern? After 10, we say the <strong>tens</strong> word first, then add 1-9!</p>', 'illust-numbers', 'If you count all your fingers and toes, you get 20!', '2026-04-18 14:31:25'),
(5, 1, 5, 'Practice Time!', '<h2>Let\'s Practice! ????</h2><p>You\'ve learned to count from 1 to 20. Amazing work!</p><p>Now take the quiz to test your counting skills and earn <strong>XP rewards</strong>!</p><div class=\"lesson-complete-box\"><p>??? Lesson Complete!</p><p>Take the quiz to earn bonus XP!</p></div>', 'illust-celebrate', 'Practice counting objects around your room ??? books, toys, or crayons!', '2026-04-18 14:31:25'),
(6, 9, 1, 'Welcome to Feelings!', '<h2>Understanding Your Feelings ????</h2><p>Everyone has feelings ??? happy, sad, angry, scared, and many more! Feelings are normal and important.</p><p>In this lesson, you\'ll learn to <strong>name your feelings</strong> and understand why they happen.</p>', 'illust-heart', 'It\'s okay to feel any emotion. What matters is how we handle them!', '2026-04-18 14:31:25'),
(7, 9, 2, 'Happy & Sad', '<h2>Happy ???? and Sad ????</h2><div class=\"feeling-cards\"><div class=\"feeling-card happy\"><div class=\"feeling-emoji\">????</div><h3>Happy</h3><p>You feel happy when good things happen ??? like playing with friends or getting a hug!</p></div><div class=\"feeling-card sad\"><div class=\"feeling-emoji\">????</div><h3>Sad</h3><p>You feel sad when something doesn\'t go well ??? like losing a toy or saying goodbye.</p></div></div><p>Both feelings are perfectly normal!</p>', 'illust-feelings', 'When you\'re sad, talking to someone you trust can help you feel better.', '2026-04-18 14:31:25'),
(8, 9, 3, 'Angry & Scared', '<h2>Angry ???? and Scared ????</h2><div class=\"feeling-cards\"><div class=\"feeling-card angry\"><div class=\"feeling-emoji\">????</div><h3>Angry</h3><p>You feel angry when things seem unfair or when someone hurts your feelings.</p></div><div class=\"feeling-card scared\"><div class=\"feeling-emoji\">????</div><h3>Scared</h3><p>You feel scared when something seems dangerous or unknown.</p></div></div><p>It\'s okay to feel these. The important thing is what you <strong>do</strong> with the feeling.</p>', 'illust-feelings', 'Taking 3 deep breaths can help when you feel angry or scared.', '2026-04-18 14:31:25'),
(9, 9, 4, 'Feelings Check-In', '<h2>How Are You Feeling Right Now? ????</h2><p>Take a moment to check in with yourself.</p><div class=\"feelings-wheel\"><div class=\"fw-item\">???? Happy</div><div class=\"fw-item\">???? Sad</div><div class=\"fw-item\">???? Angry</div><div class=\"fw-item\">???? Scared</div><div class=\"fw-item\">???? Calm</div><div class=\"fw-item\">???? Confused</div><div class=\"fw-item\">???? Loved</div><div class=\"fw-item\">???? Frustrated</div></div><p>There\'s no wrong answer! Knowing how you feel is the first step.</p>', 'illust-rainbow', 'You can do a feelings check-in anytime during the day!', '2026-04-18 14:31:25'),
(10, 16, 1, 'The Alphabet Awaits!', '<h2>Welcome to the Alphabet! ????</h2><p>The alphabet has <strong>26 letters</strong>. Each letter has a special sound and shape.</p><p>Let\'s meet them together!</p><div class=\"alphabet-preview\">A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</div>', 'illust-abc', 'The word \"alphabet\" comes from the first two Greek letters: Alpha and Beta!', '2026-04-18 14:31:25'),
(11, 16, 2, 'Letters A to F', '<h2>Meet A, B, C, D, E, F ???</h2><div class=\"letter-cards\"><div class=\"letter-card\"><span class=\"big-letter\">A</span><span class=\"letter-word\">???? Apple</span></div><div class=\"letter-card\"><span class=\"big-letter\">B</span><span class=\"letter-word\">???? Bear</span></div><div class=\"letter-card\"><span class=\"big-letter\">C</span><span class=\"letter-word\">???? Cat</span></div><div class=\"letter-card\"><span class=\"big-letter\">D</span><span class=\"letter-word\">???? Dog</span></div><div class=\"letter-card\"><span class=\"big-letter\">E</span><span class=\"letter-word\">???? Elephant</span></div><div class=\"letter-card\"><span class=\"big-letter\">F</span><span class=\"letter-word\">???? Frog</span></div></div>', 'illust-letters', 'Can you think of another word that starts with each letter?', '2026-04-18 14:31:25'),
(12, 16, 3, 'Letters G to L', '<h2>Meet G, H, I, J, K, L ???</h2><div class=\"letter-cards\"><div class=\"letter-card\"><span class=\"big-letter\">G</span><span class=\"letter-word\">???? Grapes</span></div><div class=\"letter-card\"><span class=\"big-letter\">H</span><span class=\"letter-word\">???? House</span></div><div class=\"letter-card\"><span class=\"big-letter\">I</span><span class=\"letter-word\">???? Ice Cream</span></div><div class=\"letter-card\"><span class=\"big-letter\">J</span><span class=\"letter-word\">???? Juice</span></div><div class=\"letter-card\"><span class=\"big-letter\">K</span><span class=\"letter-word\">???? Kite</span></div><div class=\"letter-card\"><span class=\"big-letter\">L</span><span class=\"letter-word\">???? Lion</span></div></div>', 'illust-letters', 'The letter \"L\" looks like a foot! Can you see it?', '2026-04-18 14:31:25'),
(13, 16, 4, 'The Rest of the Alphabet', '<h2>M all the way to Z! ????</h2><p>You\'re doing amazing! Here are the rest of our letter friends.</p><div class=\"alphabet-grid-full\"><span>M ????</span><span>N ????</span><span>O ????</span><span>P ????</span><span>Q ????</span><span>R ????</span><span>S ???</span><span>T ????</span><span>U ??????</span><span>V ????</span><span>W ????</span><span>X ????</span><span>Y ????</span><span>Z ????</span></div>', 'illust-alphabet', 'The most used letter in English is \"E\"!', '2026-04-18 14:31:25'),
(14, 1, 1, 'Welcome to Counting!', '<h2>Let\'s Count Together! 🎉</h2><p>Counting is one of the most important skills you\'ll ever learn. Numbers are everywhere — from the fingers on your hand to the stars in the sky!</p><p>In this lesson, you\'ll learn to count from <strong>1 to 20</strong> using fun objects.</p>', 'illust-stars', 'Fun fact: The number zero was invented in India over 1,500 years ago!', '2026-05-16 11:55:18'),
(15, 1, 2, 'Counting 1 to 5', '<h2>Numbers 1 to 5 🖐️</h2><div class=\"count-grid\"><div class=\"count-item\"><span class=\"count-num\">1</span><span class=\"count-obj\">🍎</span><span>One apple</span></div><div class=\"count-item\"><span class=\"count-num\">2</span><span class=\"count-obj\">🍎🍎</span><span>Two apples</span></div><div class=\"count-item\"><span class=\"count-num\">3</span><span class=\"count-obj\">🍎🍎🍎</span><span>Three apples</span></div><div class=\"count-item\"><span class=\"count-num\">4</span><span class=\"count-obj\">🍎🍎🍎🍎</span><span>Four apples</span></div><div class=\"count-item\"><span class=\"count-num\">5</span><span class=\"count-obj\">🍎🍎🍎🍎🍎</span><span>Five apples</span></div></div>', 'illust-apples', 'You have 5 fingers on each hand — just like 5 apples!', '2026-05-16 11:55:18'),
(16, 1, 3, 'Counting 6 to 10', '<h2>Numbers 6 to 10 ✋✋</h2><div class=\"count-grid\"><div class=\"count-item\"><span class=\"count-num\">6</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐</span><span>Six stars</span></div><div class=\"count-item\"><span class=\"count-num\">7</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐</span><span>Seven stars</span></div><div class=\"count-item\"><span class=\"count-num\">8</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Eight stars</span></div><div class=\"count-item\"><span class=\"count-num\">9</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Nine stars</span></div><div class=\"count-item\"><span class=\"count-num\">10</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Ten stars</span></div></div>', 'illust-stars', 'There are 10 digits: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9. Every number is made from these!', '2026-05-16 11:55:18'),
(17, 1, 4, 'Counting to 20', '<h2>All the Way to 20! 🎊</h2><p>Great job! Now let\'s count higher. After 10, we keep going!</p><div class=\"number-line\"><span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span><span>18</span><span>19</span><span>20</span></div><p>Notice a pattern? After 10, we say the <strong>tens</strong> word first, then add 1-9!</p>', 'illust-numbers', 'If you count all your fingers and toes, you get 20!', '2026-05-16 11:55:18'),
(18, 1, 5, 'Practice Time!', '<h2>Let\'s Practice! 🎮</h2><p>You\'ve learned to count from 1 to 20. Amazing work!</p><p>Now take the quiz to test your counting skills and earn <strong>XP rewards</strong>!</p><div class=\"lesson-complete-box\"><p>✅ Lesson Complete!</p><p>Take the quiz to earn bonus XP!</p></div>', 'illust-celebrate', 'Practice counting objects around your room — books, toys, or crayons!', '2026-05-16 11:55:18'),
(19, 9, 1, 'Welcome to Feelings!', '<h2>Understanding Your Feelings 💛</h2><p>Everyone has feelings — happy, sad, angry, scared, and many more! Feelings are normal and important.</p><p>In this lesson, you\'ll learn to <strong>name your feelings</strong> and understand why they happen.</p>', 'illust-heart', 'It\'s okay to feel any emotion. What matters is how we handle them!', '2026-05-16 11:55:18'),
(20, 9, 2, 'Happy & Sad', '<h2>Happy 😊 and Sad 😢</h2><div class=\"feeling-cards\"><div class=\"feeling-card happy\"><div class=\"feeling-emoji\">😊</div><h3>Happy</h3><p>You feel happy when good things happen — like playing with friends or getting a hug!</p></div><div class=\"feeling-card sad\"><div class=\"feeling-emoji\">😢</div><h3>Sad</h3><p>You feel sad when something doesn\'t go well — like losing a toy or saying goodbye.</p></div></div><p>Both feelings are perfectly normal!</p>', 'illust-feelings', 'When you\'re sad, talking to someone you trust can help you feel better.', '2026-05-16 11:55:18'),
(21, 9, 3, 'Angry & Scared', '<h2>Angry 😠 and Scared 😨</h2><div class=\"feeling-cards\"><div class=\"feeling-card angry\"><div class=\"feeling-emoji\">😠</div><h3>Angry</h3><p>You feel angry when things seem unfair or when someone hurts your feelings.</p></div><div class=\"feeling-card scared\"><div class=\"feeling-emoji\">😨</div><h3>Scared</h3><p>You feel scared when something seems dangerous or unknown.</p></div></div><p>It\'s okay to feel these. The important thing is what you <strong>do</strong> with the feeling.</p>', 'illust-feelings', 'Taking 3 deep breaths can help when you feel angry or scared.', '2026-05-16 11:55:18'),
(22, 9, 4, 'Feelings Check-In', '<h2>How Are You Feeling Right Now? 🌈</h2><p>Take a moment to check in with yourself.</p><div class=\"feelings-wheel\"><div class=\"fw-item\">😊 Happy</div><div class=\"fw-item\">😢 Sad</div><div class=\"fw-item\">😠 Angry</div><div class=\"fw-item\">😨 Scared</div><div class=\"fw-item\">😌 Calm</div><div class=\"fw-item\">🤔 Confused</div><div class=\"fw-item\">🥰 Loved</div><div class=\"fw-item\">😤 Frustrated</div></div><p>There\'s no wrong answer! Knowing how you feel is the first step.</p>', 'illust-rainbow', 'You can do a feelings check-in anytime during the day!', '2026-05-16 11:55:18'),
(23, 16, 1, 'The Alphabet Awaits!', '<h2>Welcome to the Alphabet! 🔤</h2><p>The alphabet has <strong>26 letters</strong>. Each letter has a special sound and shape.</p><p>Let\'s meet them together!</p><div class=\"alphabet-preview\">A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</div>', 'illust-abc', 'The word \"alphabet\" comes from the first two Greek letters: Alpha and Beta!', '2026-05-16 11:55:18'),
(24, 16, 2, 'Letters A to F', '<h2>Meet A, B, C, D, E, F ✨</h2><div class=\"letter-cards\"><div class=\"letter-card\"><span class=\"big-letter\">A</span><span class=\"letter-word\">🍎 Apple</span></div><div class=\"letter-card\"><span class=\"big-letter\">B</span><span class=\"letter-word\">🐻 Bear</span></div><div class=\"letter-card\"><span class=\"big-letter\">C</span><span class=\"letter-word\">🐱 Cat</span></div><div class=\"letter-card\"><span class=\"big-letter\">D</span><span class=\"letter-word\">🐕 Dog</span></div><div class=\"letter-card\"><span class=\"big-letter\">E</span><span class=\"letter-word\">🐘 Elephant</span></div><div class=\"letter-card\"><span class=\"big-letter\">F</span><span class=\"letter-word\">🐸 Frog</span></div></div>', 'illust-letters', 'Can you think of another word that starts with each letter?', '2026-05-16 11:55:18'),
(25, 16, 3, 'Letters G to L', '<h2>Meet G, H, I, J, K, L ✨</h2><div class=\"letter-cards\"><div class=\"letter-card\"><span class=\"big-letter\">G</span><span class=\"letter-word\">🍇 Grapes</span></div><div class=\"letter-card\"><span class=\"big-letter\">H</span><span class=\"letter-word\">🏠 House</span></div><div class=\"letter-card\"><span class=\"big-letter\">I</span><span class=\"letter-word\">🍦 Ice Cream</span></div><div class=\"letter-card\"><span class=\"big-letter\">J</span><span class=\"letter-word\">🧃 Juice</span></div><div class=\"letter-card\"><span class=\"big-letter\">K</span><span class=\"letter-word\">🪁 Kite</span></div><div class=\"letter-card\"><span class=\"big-letter\">L</span><span class=\"letter-word\">🦁 Lion</span></div></div>', 'illust-letters', 'The letter \"L\" looks like a foot! Can you see it?', '2026-05-16 11:55:18'),
(26, 16, 4, 'The Rest of the Alphabet', '<h2>M all the way to Z! 🎉</h2><p>You\'re doing amazing! Here are the rest of our letter friends.</p><div class=\"alphabet-grid-full\"><span>M 🌙</span><span>N 🥜</span><span>O 🐙</span><span>P 🐧</span><span>Q 👑</span><span>R 🌈</span><span>S ⭐</span><span>T 🐢</span><span>U ☂️</span><span>V 🎻</span><span>W 🐋</span><span>X 🎸</span><span>Y 💛</span><span>Z 🦓</span></div>', 'illust-alphabet', 'The most used letter in English is \"E\"!', '2026-05-16 11:55:18'),
(27, 1, 1, 'Welcome to Counting!', '<h2>Let\'s Count Together! 🎉</h2><p>Counting is one of the most important skills you\'ll ever learn. Numbers are everywhere — from the fingers on your hand to the stars in the sky!</p><p>In this lesson, you\'ll learn to count from <strong>1 to 20</strong> using fun objects.</p>', 'illust-stars', 'Fun fact: The number zero was invented in India over 1,500 years ago!', '2026-05-16 11:55:33'),
(28, 1, 2, 'Counting 1 to 5', '<h2>Numbers 1 to 5 🖐️</h2><div class=\"count-grid\"><div class=\"count-item\"><span class=\"count-num\">1</span><span class=\"count-obj\">🍎</span><span>One apple</span></div><div class=\"count-item\"><span class=\"count-num\">2</span><span class=\"count-obj\">🍎🍎</span><span>Two apples</span></div><div class=\"count-item\"><span class=\"count-num\">3</span><span class=\"count-obj\">🍎🍎🍎</span><span>Three apples</span></div><div class=\"count-item\"><span class=\"count-num\">4</span><span class=\"count-obj\">🍎🍎🍎🍎</span><span>Four apples</span></div><div class=\"count-item\"><span class=\"count-num\">5</span><span class=\"count-obj\">🍎🍎🍎🍎🍎</span><span>Five apples</span></div></div>', 'illust-apples', 'You have 5 fingers on each hand — just like 5 apples!', '2026-05-16 11:55:33'),
(29, 1, 3, 'Counting 6 to 10', '<h2>Numbers 6 to 10 ✋✋</h2><div class=\"count-grid\"><div class=\"count-item\"><span class=\"count-num\">6</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐</span><span>Six stars</span></div><div class=\"count-item\"><span class=\"count-num\">7</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐</span><span>Seven stars</span></div><div class=\"count-item\"><span class=\"count-num\">8</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Eight stars</span></div><div class=\"count-item\"><span class=\"count-num\">9</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Nine stars</span></div><div class=\"count-item\"><span class=\"count-num\">10</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Ten stars</span></div></div>', 'illust-stars', 'There are 10 digits: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9. Every number is made from these!', '2026-05-16 11:55:33'),
(30, 1, 4, 'Counting to 20', '<h2>All the Way to 20! 🎊</h2><p>Great job! Now let\'s count higher. After 10, we keep going!</p><div class=\"number-line\"><span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span><span>18</span><span>19</span><span>20</span></div><p>Notice a pattern? After 10, we say the <strong>tens</strong> word first, then add 1-9!</p>', 'illust-numbers', 'If you count all your fingers and toes, you get 20!', '2026-05-16 11:55:33'),
(31, 1, 5, 'Practice Time!', '<h2>Let\'s Practice! 🎮</h2><p>You\'ve learned to count from 1 to 20. Amazing work!</p><p>Now take the quiz to test your counting skills and earn <strong>XP rewards</strong>!</p><div class=\"lesson-complete-box\"><p>✅ Lesson Complete!</p><p>Take the quiz to earn bonus XP!</p></div>', 'illust-celebrate', 'Practice counting objects around your room — books, toys, or crayons!', '2026-05-16 11:55:33'),
(32, 9, 1, 'Welcome to Feelings!', '<h2>Understanding Your Feelings 💛</h2><p>Everyone has feelings — happy, sad, angry, scared, and many more! Feelings are normal and important.</p><p>In this lesson, you\'ll learn to <strong>name your feelings</strong> and understand why they happen.</p>', 'illust-heart', 'It\'s okay to feel any emotion. What matters is how we handle them!', '2026-05-16 11:55:33'),
(33, 9, 2, 'Happy & Sad', '<h2>Happy 😊 and Sad 😢</h2><div class=\"feeling-cards\"><div class=\"feeling-card happy\"><div class=\"feeling-emoji\">😊</div><h3>Happy</h3><p>You feel happy when good things happen — like playing with friends or getting a hug!</p></div><div class=\"feeling-card sad\"><div class=\"feeling-emoji\">😢</div><h3>Sad</h3><p>You feel sad when something doesn\'t go well — like losing a toy or saying goodbye.</p></div></div><p>Both feelings are perfectly normal!</p>', 'illust-feelings', 'When you\'re sad, talking to someone you trust can help you feel better.', '2026-05-16 11:55:33'),
(34, 9, 3, 'Angry & Scared', '<h2>Angry 😠 and Scared 😨</h2><div class=\"feeling-cards\"><div class=\"feeling-card angry\"><div class=\"feeling-emoji\">😠</div><h3>Angry</h3><p>You feel angry when things seem unfair or when someone hurts your feelings.</p></div><div class=\"feeling-card scared\"><div class=\"feeling-emoji\">😨</div><h3>Scared</h3><p>You feel scared when something seems dangerous or unknown.</p></div></div><p>It\'s okay to feel these. The important thing is what you <strong>do</strong> with the feeling.</p>', 'illust-feelings', 'Taking 3 deep breaths can help when you feel angry or scared.', '2026-05-16 11:55:33'),
(35, 9, 4, 'Feelings Check-In', '<h2>How Are You Feeling Right Now? 🌈</h2><p>Take a moment to check in with yourself.</p><div class=\"feelings-wheel\"><div class=\"fw-item\">😊 Happy</div><div class=\"fw-item\">😢 Sad</div><div class=\"fw-item\">😠 Angry</div><div class=\"fw-item\">😨 Scared</div><div class=\"fw-item\">😌 Calm</div><div class=\"fw-item\">🤔 Confused</div><div class=\"fw-item\">🥰 Loved</div><div class=\"fw-item\">😤 Frustrated</div></div><p>There\'s no wrong answer! Knowing how you feel is the first step.</p>', 'illust-rainbow', 'You can do a feelings check-in anytime during the day!', '2026-05-16 11:55:33'),
(36, 16, 1, 'The Alphabet Awaits!', '<h2>Welcome to the Alphabet! 🔤</h2><p>The alphabet has <strong>26 letters</strong>. Each letter has a special sound and shape.</p><p>Let\'s meet them together!</p><div class=\"alphabet-preview\">A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</div>', 'illust-abc', 'The word \"alphabet\" comes from the first two Greek letters: Alpha and Beta!', '2026-05-16 11:55:33'),
(37, 16, 2, 'Letters A to F', '<h2>Meet A, B, C, D, E, F ✨</h2><div class=\"letter-cards\"><div class=\"letter-card\"><span class=\"big-letter\">A</span><span class=\"letter-word\">🍎 Apple</span></div><div class=\"letter-card\"><span class=\"big-letter\">B</span><span class=\"letter-word\">🐻 Bear</span></div><div class=\"letter-card\"><span class=\"big-letter\">C</span><span class=\"letter-word\">🐱 Cat</span></div><div class=\"letter-card\"><span class=\"big-letter\">D</span><span class=\"letter-word\">🐕 Dog</span></div><div class=\"letter-card\"><span class=\"big-letter\">E</span><span class=\"letter-word\">🐘 Elephant</span></div><div class=\"letter-card\"><span class=\"big-letter\">F</span><span class=\"letter-word\">🐸 Frog</span></div></div>', 'illust-letters', 'Can you think of another word that starts with each letter?', '2026-05-16 11:55:33'),
(38, 16, 3, 'Letters G to L', '<h2>Meet G, H, I, J, K, L ✨</h2><div class=\"letter-cards\"><div class=\"letter-card\"><span class=\"big-letter\">G</span><span class=\"letter-word\">🍇 Grapes</span></div><div class=\"letter-card\"><span class=\"big-letter\">H</span><span class=\"letter-word\">🏠 House</span></div><div class=\"letter-card\"><span class=\"big-letter\">I</span><span class=\"letter-word\">🍦 Ice Cream</span></div><div class=\"letter-card\"><span class=\"big-letter\">J</span><span class=\"letter-word\">🧃 Juice</span></div><div class=\"letter-card\"><span class=\"big-letter\">K</span><span class=\"letter-word\">🪁 Kite</span></div><div class=\"letter-card\"><span class=\"big-letter\">L</span><span class=\"letter-word\">🦁 Lion</span></div></div>', 'illust-letters', 'The letter \"L\" looks like a foot! Can you see it?', '2026-05-16 11:55:33'),
(39, 16, 4, 'The Rest of the Alphabet', '<h2>M all the way to Z! 🎉</h2><p>You\'re doing amazing! Here are the rest of our letter friends.</p><div class=\"alphabet-grid-full\"><span>M 🌙</span><span>N 🥜</span><span>O 🐙</span><span>P 🐧</span><span>Q 👑</span><span>R 🌈</span><span>S ⭐</span><span>T 🐢</span><span>U ☂️</span><span>V 🎻</span><span>W 🐋</span><span>X 🎸</span><span>Y 💛</span><span>Z 🦓</span></div>', 'illust-alphabet', 'The most used letter in English is \"E\"!', '2026-05-16 11:55:33'),
(40, 1, 1, 'Welcome to Counting!', '<h2>Let\'s Count Together! 🎉</h2><p>Counting is one of the most important skills you\'ll ever learn. Numbers are everywhere — from the fingers on your hand to the stars in the sky!</p><p>In this lesson, you\'ll learn to count from <strong>1 to 20</strong> using fun objects.</p>', 'illust-stars', 'Fun fact: The number zero was invented in India over 1,500 years ago!', '2026-05-16 11:56:14'),
(41, 1, 2, 'Counting 1 to 5', '<h2>Numbers 1 to 5 🖐️</h2><div class=\"count-grid\"><div class=\"count-item\"><span class=\"count-num\">1</span><span class=\"count-obj\">🍎</span><span>One apple</span></div><div class=\"count-item\"><span class=\"count-num\">2</span><span class=\"count-obj\">🍎🍎</span><span>Two apples</span></div><div class=\"count-item\"><span class=\"count-num\">3</span><span class=\"count-obj\">🍎🍎🍎</span><span>Three apples</span></div><div class=\"count-item\"><span class=\"count-num\">4</span><span class=\"count-obj\">🍎🍎🍎🍎</span><span>Four apples</span></div><div class=\"count-item\"><span class=\"count-num\">5</span><span class=\"count-obj\">🍎🍎🍎🍎🍎</span><span>Five apples</span></div></div>', 'illust-apples', 'You have 5 fingers on each hand — just like 5 apples!', '2026-05-16 11:56:14'),
(42, 1, 3, 'Counting 6 to 10', '<h2>Numbers 6 to 10 ✋✋</h2><div class=\"count-grid\"><div class=\"count-item\"><span class=\"count-num\">6</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐</span><span>Six stars</span></div><div class=\"count-item\"><span class=\"count-num\">7</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐</span><span>Seven stars</span></div><div class=\"count-item\"><span class=\"count-num\">8</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Eight stars</span></div><div class=\"count-item\"><span class=\"count-num\">9</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Nine stars</span></div><div class=\"count-item\"><span class=\"count-num\">10</span><span class=\"count-obj\">⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Ten stars</span></div></div>', 'illust-stars', 'There are 10 digits: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9. Every number is made from these!', '2026-05-16 11:56:14'),
(43, 1, 4, 'Counting to 20', '<h2>All the Way to 20! 🎊</h2><p>Great job! Now let\'s count higher. After 10, we keep going!</p><div class=\"number-line\"><span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span><span>18</span><span>19</span><span>20</span></div><p>Notice a pattern? After 10, we say the <strong>tens</strong> word first, then add 1-9!</p>', 'illust-numbers', 'If you count all your fingers and toes, you get 20!', '2026-05-16 11:56:14'),
(44, 1, 5, 'Practice Time!', '<h2>Let\'s Practice! 🎮</h2><p>You\'ve learned to count from 1 to 20. Amazing work!</p><p>Now take the quiz to test your counting skills and earn <strong>XP rewards</strong>!</p><div class=\"lesson-complete-box\"><p>✅ Lesson Complete!</p><p>Take the quiz to earn bonus XP!</p></div>', 'illust-celebrate', 'Practice counting objects around your room — books, toys, or crayons!', '2026-05-16 11:56:14'),
(45, 9, 1, 'Welcome to Feelings!', '<h2>Understanding Your Feelings 💛</h2><p>Everyone has feelings — happy, sad, angry, scared, and many more! Feelings are normal and important.</p><p>In this lesson, you\'ll learn to <strong>name your feelings</strong> and understand why they happen.</p>', 'illust-heart', 'It\'s okay to feel any emotion. What matters is how we handle them!', '2026-05-16 11:56:14'),
(46, 9, 2, 'Happy & Sad', '<h2>Happy 😊 and Sad 😢</h2><div class=\"feeling-cards\"><div class=\"feeling-card happy\"><div class=\"feeling-emoji\">😊</div><h3>Happy</h3><p>You feel happy when good things happen — like playing with friends or getting a hug!</p></div><div class=\"feeling-card sad\"><div class=\"feeling-emoji\">😢</div><h3>Sad</h3><p>You feel sad when something doesn\'t go well — like losing a toy or saying goodbye.</p></div></div><p>Both feelings are perfectly normal!</p>', 'illust-feelings', 'When you\'re sad, talking to someone you trust can help you feel better.', '2026-05-16 11:56:14'),
(47, 9, 3, 'Angry & Scared', '<h2>Angry 😠 and Scared 😨</h2><div class=\"feeling-cards\"><div class=\"feeling-card angry\"><div class=\"feeling-emoji\">😠</div><h3>Angry</h3><p>You feel angry when things seem unfair or when someone hurts your feelings.</p></div><div class=\"feeling-card scared\"><div class=\"feeling-emoji\">😨</div><h3>Scared</h3><p>You feel scared when something seems dangerous or unknown.</p></div></div><p>It\'s okay to feel these. The important thing is what you <strong>do</strong> with the feeling.</p>', 'illust-feelings', 'Taking 3 deep breaths can help when you feel angry or scared.', '2026-05-16 11:56:14'),
(48, 9, 4, 'Feelings Check-In', '<h2>How Are You Feeling Right Now? 🌈</h2><p>Take a moment to check in with yourself.</p><div class=\"feelings-wheel\"><div class=\"fw-item\">😊 Happy</div><div class=\"fw-item\">😢 Sad</div><div class=\"fw-item\">😠 Angry</div><div class=\"fw-item\">😨 Scared</div><div class=\"fw-item\">😌 Calm</div><div class=\"fw-item\">🤔 Confused</div><div class=\"fw-item\">🥰 Loved</div><div class=\"fw-item\">😤 Frustrated</div></div><p>There\'s no wrong answer! Knowing how you feel is the first step.</p>', 'illust-rainbow', 'You can do a feelings check-in anytime during the day!', '2026-05-16 11:56:14'),
(49, 16, 1, 'The Alphabet Awaits!', '<h2>Welcome to the Alphabet! 🔤</h2><p>The alphabet has <strong>26 letters</strong>. Each letter has a special sound and shape.</p><p>Let\'s meet them together!</p><div class=\"alphabet-preview\">A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</div>', 'illust-abc', 'The word \"alphabet\" comes from the first two Greek letters: Alpha and Beta!', '2026-05-16 11:56:14'),
(50, 16, 2, 'Letters A to F', '<h2>Meet A, B, C, D, E, F ✨</h2><div class=\"letter-cards\"><div class=\"letter-card\"><span class=\"big-letter\">A</span><span class=\"letter-word\">🍎 Apple</span></div><div class=\"letter-card\"><span class=\"big-letter\">B</span><span class=\"letter-word\">🐻 Bear</span></div><div class=\"letter-card\"><span class=\"big-letter\">C</span><span class=\"letter-word\">🐱 Cat</span></div><div class=\"letter-card\"><span class=\"big-letter\">D</span><span class=\"letter-word\">🐕 Dog</span></div><div class=\"letter-card\"><span class=\"big-letter\">E</span><span class=\"letter-word\">🐘 Elephant</span></div><div class=\"letter-card\"><span class=\"big-letter\">F</span><span class=\"letter-word\">🐸 Frog</span></div></div>', 'illust-letters', 'Can you think of another word that starts with each letter?', '2026-05-16 11:56:14'),
(51, 16, 3, 'Letters G to L', '<h2>Meet G, H, I, J, K, L ✨</h2><div class=\"letter-cards\"><div class=\"letter-card\"><span class=\"big-letter\">G</span><span class=\"letter-word\">🍇 Grapes</span></div><div class=\"letter-card\"><span class=\"big-letter\">H</span><span class=\"letter-word\">🏠 House</span></div><div class=\"letter-card\"><span class=\"big-letter\">I</span><span class=\"letter-word\">🍦 Ice Cream</span></div><div class=\"letter-card\"><span class=\"big-letter\">J</span><span class=\"letter-word\">🧃 Juice</span></div><div class=\"letter-card\"><span class=\"big-letter\">K</span><span class=\"letter-word\">🪁 Kite</span></div><div class=\"letter-card\"><span class=\"big-letter\">L</span><span class=\"letter-word\">🦁 Lion</span></div></div>', 'illust-letters', 'The letter \"L\" looks like a foot! Can you see it?', '2026-05-16 11:56:14'),
(52, 16, 4, 'The Rest of the Alphabet', '<h2>M all the way to Z! 🎉</h2><p>You\'re doing amazing! Here are the rest of our letter friends.</p><div class=\"alphabet-grid-full\"><span>M 🌙</span><span>N 🥜</span><span>O 🐙</span><span>P 🐧</span><span>Q 👑</span><span>R 🌈</span><span>S ⭐</span><span>T 🐢</span><span>U ☂️</span><span>V 🎻</span><span>W 🐋</span><span>X 🎸</span><span>Y 💛</span><span>Z 🦓</span></div>', 'illust-alphabet', 'The most used letter in English is \"E\"!', '2026-05-16 11:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `user_agent` varchar(500) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `success`, `user_agent`, `attempted_at`) VALUES
(40, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '2026-03-25 12:42:58'),
(42, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '2026-03-25 12:44:53'),
(52, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '2026-03-25 12:46:30'),
(53, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '2026-03-25 12:47:24'),
(54, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '2026-03-25 13:10:48'),
(55, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '2026-03-25 15:15:40'),
(61, 'teacher@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-29 09:53:22'),
(63, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-29 10:56:03'),
(64, 'teacher@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-29 14:24:14'),
(65, 'teacher@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 OPR/128.0.0.0', '2026-03-29 14:38:14'),
(68, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-12 07:53:58'),
(69, 'teacher@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-12 08:21:39'),
(70, 'teacher@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 12:36:14'),
(71, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 12:36:37'),
(72, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 13:34:48'),
(73, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 13:39:19'),
(74, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 13:40:16'),
(75, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 13:52:01'),
(76, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 14:05:29'),
(77, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 14:09:27'),
(78, 'student@test.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 15:00:46'),
(79, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-15 13:35:17'),
(80, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 12:48:38'),
(81, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 12:50:48'),
(82, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:27:25'),
(83, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:28:35'),
(84, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:35:50'),
(85, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:36:13'),
(86, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:36:44'),
(87, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:39:46'),
(88, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:42:45'),
(89, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:50:58'),
(90, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 13:53:26'),
(91, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:02:13'),
(92, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:04:38'),
(93, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:05:06'),
(94, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:20:24'),
(95, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:23:41'),
(96, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:43:23'),
(97, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:44:21'),
(98, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:44:52'),
(99, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:45:13'),
(100, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:45:51'),
(101, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:46:10'),
(102, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:56:05'),
(103, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:57:08'),
(104, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 14:57:23'),
(105, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:01:18'),
(106, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:01:43'),
(107, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:29:57'),
(108, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:48:18'),
(109, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:48:49'),
(110, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:49:13'),
(111, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:49:46'),
(112, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:50:15'),
(113, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:51:13'),
(114, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:51:28'),
(115, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-16 15:52:39'),
(116, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 14:52:27'),
(117, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 14:52:54'),
(118, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:00:08'),
(119, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:00:26'),
(120, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:01:37'),
(121, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:03:16'),
(122, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:04:52'),
(123, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:27:12'),
(124, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:29:22'),
(125, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:29:51'),
(126, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:32:46'),
(127, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:48:42'),
(128, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:49:11'),
(129, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:49:54'),
(130, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:50:33'),
(131, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-17 15:52:50'),
(132, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 12:16:52'),
(133, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 12:25:20'),
(134, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 12:49:57'),
(135, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 13:17:21'),
(136, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 13:19:56'),
(137, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 13:21:11'),
(138, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 13:36:10'),
(139, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 13:36:41'),
(140, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 14:38:13'),
(141, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-18 14:58:01'),
(142, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 06:38:34'),
(143, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 06:39:23'),
(144, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 06:41:36'),
(145, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 06:41:53'),
(146, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 06:46:35'),
(147, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 06:47:04'),
(151, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 07:30:12'),
(152, 'stay8comfy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 07:31:19'),
(153, 'stay8comfy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 07:32:16'),
(154, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 07:32:46'),
(156, 'stay8comfy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 07:35:22'),
(159, 'stay8comfy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 07:47:47'),
(161, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 08:05:54'),
(162, 'stay8comfy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 08:06:31'),
(163, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 08:06:52'),
(165, 'stay8comfy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 08:07:26'),
(166, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 08:29:35'),
(168, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 08:33:21'),
(169, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 08:33:49'),
(170, 'zachdomingojavellana@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-19 08:36:27'),
(171, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-22 12:53:47'),
(173, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-22 12:55:10'),
(174, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-22 12:56:00'),
(175, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-22 13:23:08'),
(176, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-22 13:26:06'),
(177, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-22 13:26:26'),
(178, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-22 13:28:06'),
(179, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-23 12:20:06'),
(180, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-23 13:00:30'),
(181, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-23 13:01:56'),
(182, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-23 13:07:26'),
(183, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0', '2026-04-23 13:08:31'),
(184, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-04-25 12:59:10'),
(185, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-04-25 13:24:10'),
(186, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-04-25 13:51:10'),
(210, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 14:34:17'),
(211, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 14:35:04'),
(212, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 14:46:33'),
(213, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:12:18'),
(214, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:14:18'),
(215, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:15:21'),
(216, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:17:05'),
(217, 'teststudent@eduquest.test', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:17:25'),
(218, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:17:31'),
(219, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:17:58'),
(220, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:18:12'),
(221, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-04 15:18:52'),
(222, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 14:26:34'),
(223, 'teacher@test.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 14:51:35'),
(224, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 14:51:48'),
(225, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 15:28:23'),
(226, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 15:37:15'),
(227, 'test@test.com', '::1', 0, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.8328', '2026-05-06 12:22:46'),
(228, 'test@test.com', '::1', 0, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.8328', '2026-05-06 12:23:32'),
(229, 'teacher@test.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 12:56:39'),
(230, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 12:56:55'),
(231, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 13:57:40'),
(232, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 14:24:11'),
(233, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 14:24:35'),
(234, 'teststudent@eduquest.test', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 10:24:00'),
(235, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 10:24:03'),
(236, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 10:41:15'),
(237, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 10:43:25'),
(238, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 10:49:33'),
(239, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 11:12:46'),
(240, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 11:28:37'),
(241, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 12:13:11'),
(242, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 12:52:50'),
(243, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:05:41'),
(244, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:05:45'),
(245, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:21:12'),
(246, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:21:15'),
(247, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:35:31'),
(248, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:35:34'),
(249, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:35:37'),
(250, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:44:47'),
(251, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:44:50'),
(252, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:44:55'),
(253, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 13:44:59'),
(254, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-07 14:10:35'),
(255, 'mayriellej@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 06:35:09'),
(256, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 06:42:16'),
(257, 'mayriellejoy@gmail.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:05:43'),
(258, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:05:46'),
(259, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:10:39'),
(260, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:11:49'),
(261, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:15:44'),
(262, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:25:37'),
(263, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:27:07'),
(264, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:28:53'),
(265, 'mayriellej@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:51:50'),
(266, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 07:52:12'),
(267, 'mayriellejoy@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0', '2026-05-11 08:04:23'),
(268, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-12 14:58:19'),
(269, 'testteacher@test.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-12 15:41:19'),
(270, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-12 15:41:26'),
(271, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-12 15:42:28'),
(272, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-12 16:55:21'),
(273, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-13 14:41:05'),
(274, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-13 14:43:28'),
(275, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-13 17:01:20'),
(276, 'teststudent2@eduquest.test', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-13 18:03:57'),
(277, 'teststudent2@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-13 18:04:11'),
(278, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-13 18:07:12'),
(279, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 08:15:46'),
(280, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 08:19:32'),
(281, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 09:05:46'),
(282, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 09:59:19'),
(283, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 10:00:03'),
(284, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 10:11:14'),
(285, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 12:36:32'),
(286, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 12:42:36'),
(287, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 12:46:25'),
(288, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 12:47:57'),
(289, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 12:48:35'),
(290, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 12:53:59'),
(291, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 12:55:24'),
(292, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 13:22:14'),
(293, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 13:23:43'),
(294, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 13:29:18'),
(295, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 13:31:25'),
(296, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 13:32:23'),
(297, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 14:25:45'),
(298, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 15:04:23'),
(299, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 15:06:27'),
(300, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 15:09:21'),
(301, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 15:26:38'),
(302, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 15:53:35'),
(303, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 16:11:00'),
(304, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 16:42:57'),
(305, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-15 17:32:56'),
(306, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 17:33:52'),
(307, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-16 11:48:33'),
(308, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 11:49:06'),
(309, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.120.0 Chrome/142.0.7444.265 Electron/39.8.8 Safari/537.36', '2026-05-16 11:56:41'),
(310, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:02:53'),
(311, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:19:52'),
(312, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:22:19'),
(313, 'teacher@test.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:22:24'),
(314, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:22:34'),
(315, 'testteacher@eduquest.test', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:22:52'),
(316, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:22:54'),
(317, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:23:44'),
(318, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:45:18'),
(319, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 12:46:35'),
(320, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 13:03:40'),
(321, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 13:10:13'),
(322, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 13:11:07'),
(323, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 13:14:53'),
(324, 'teacher@test.com', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 14:59:07'),
(325, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 14:59:16'),
(326, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 14:59:51'),
(327, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 15:03:20'),
(328, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 15:05:11'),
(329, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 15:15:03'),
(330, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 15:15:24'),
(331, 'testteacher@eduquest.test', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 15:22:34'),
(332, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 15:22:37'),
(333, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 15:30:58'),
(334, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 15:33:19'),
(335, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 17:48:13'),
(336, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 17:50:41'),
(337, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 17:51:09'),
(338, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 17:51:26'),
(339, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 17:51:52'),
(340, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 12:37:39'),
(341, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-18 07:39:21'),
(342, 'teststudent@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-18 10:14:49'),
(343, 'testteacher@eduquest.test', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-18 13:50:39');

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `medication_name` varchar(200) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `prescribing_doctor` varchar(200) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 1,
  `side_effects_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipient_id` int(10) UNSIGNED NOT NULL,
  `recipient_role` enum('teacher','student') NOT NULL,
  `message` varchar(500) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_id`, `recipient_role`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 37, 'student', 'New material uploaded: Addition', '../../student-dashboard/learning/learning.html', 1, '2026-05-04 15:18:34'),
(2, 40, 'student', 'New quiz assigned: Quiz 1', '../quests/take-quiz.html', 1, '2026-05-11 07:23:35'),
(3, 40, 'student', 'New quiz assigned: Quiz 1', '../quests/take-quiz.html', 1, '2026-05-11 07:24:44');

-- --------------------------------------------------------

--
-- Table structure for table `page_sessions`
--

CREATE TABLE `page_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `page_name` varchar(100) NOT NULL,
  `session_start` datetime NOT NULL,
  `session_end` datetime DEFAULT NULL,
  `duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_change_otps`
--

CREATE TABLE `password_change_otps` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_change_otps`
--

INSERT INTO `password_change_otps` (`id`, `user_id`, `otp_hash`, `attempts`, `expires_at`, `used_at`, `created_at`) VALUES
(9, 49, '$2y$10$8lUeiznoQEwhnAb0YlwPsOTU0h5P6XDu56H7ypaFZIjT0eWlXvrh.', 1, '2026-05-07 13:46:17', NULL, '2026-05-07 13:46:00');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_interactions`
--

CREATE TABLE `question_interactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `time_spent_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `attempt_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `answered_correctly` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(10) UNSIGNED NOT NULL,
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `pass_percentage` int(10) UNSIGNED DEFAULT 70,
  `xp_reward` int(10) UNSIGNED DEFAULT 50,
  `max_attempts` int(10) UNSIGNED DEFAULT 3,
  `time_limit_sec` int(10) UNSIGNED DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `lesson_id`, `title`, `description`, `pass_percentage`, `xp_reward`, `max_attempts`, `time_limit_sec`, `is_active`, `created_at`) VALUES
(1, 1, 'Counting Quiz', 'Test your counting skills!', 70, 40, 3, 0, 1, '2026-04-18 14:31:25'),
(2, 9, 'Feelings Quiz', 'Show what you know about feelings!', 70, 40, 3, 0, 1, '2026-04-18 14:31:25'),
(3, 16, 'Alphabet Quiz', 'Test your letter knowledge!', 70, 40, 3, 0, 1, '2026-04-18 14:31:25'),
(4, 1, 'Counting Quiz', 'Test your counting skills!', 70, 40, 3, 0, 1, '2026-05-16 11:55:18'),
(5, 9, 'Feelings Quiz', 'Show what you know about feelings!', 70, 40, 3, 0, 1, '2026-05-16 11:55:18'),
(6, 16, 'Alphabet Quiz', 'Test your letter knowledge!', 70, 40, 3, 0, 1, '2026-05-16 11:55:18'),
(7, 1, 'Counting Quiz', 'Test your counting skills!', 70, 40, 3, 0, 1, '2026-05-16 11:55:33'),
(8, 9, 'Feelings Quiz', 'Show what you know about feelings!', 70, 40, 3, 0, 1, '2026-05-16 11:55:33'),
(9, 16, 'Alphabet Quiz', 'Test your letter knowledge!', 70, 40, 3, 0, 1, '2026-05-16 11:55:33'),
(10, 1, 'Counting Quiz', 'Test your counting skills!', 70, 40, 3, 0, 1, '2026-05-16 11:56:14'),
(11, 9, 'Feelings Quiz', 'Show what you know about feelings!', 70, 40, 3, 0, 1, '2026-05-16 11:56:14'),
(12, 16, 'Alphabet Quiz', 'Test your letter knowledge!', 70, 40, 3, 0, 1, '2026-05-16 11:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `answer_text` varchar(500) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `answer_order` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_answers`
--

INSERT INTO `quiz_answers` (`id`, `question_id`, `answer_text`, `is_correct`, `answer_order`) VALUES
(1, 1, '2', 0, 1),
(2, 1, '3', 1, 2),
(3, 1, '4', 0, 3),
(4, 1, '5', 0, 4),
(5, 2, '6', 0, 1),
(6, 2, '7', 0, 2),
(7, 2, '8', 1, 3),
(8, 2, '9', 0, 4),
(9, 3, '3', 0, 1),
(10, 3, '4', 0, 2),
(11, 3, '5', 1, 3),
(12, 3, '6', 0, 4),
(13, 4, '8', 0, 1),
(14, 4, '9', 1, 2),
(15, 4, '10', 0, 3),
(16, 4, '11', 0, 4),
(17, 5, 'True', 1, 1),
(18, 5, 'False', 0, 2),
(19, 6, '????', 0, 1),
(20, 6, '????', 1, 2),
(21, 6, '????', 0, 3),
(22, 6, '????', 0, 4),
(23, 7, 'Yell louder', 0, 1),
(24, 7, 'Take deep breaths', 1, 2),
(25, 7, 'Hit something', 0, 3),
(26, 7, 'Run away', 0, 4),
(27, 8, 'True', 1, 1),
(28, 8, 'False', 0, 2),
(29, 9, 'Laugh at them', 0, 1),
(30, 9, 'Ignore them', 0, 2),
(31, 9, 'Be kind and listen', 1, 3),
(32, 9, 'Walk away', 0, 4),
(33, 10, 'B', 0, 1),
(34, 10, 'A', 1, 2),
(35, 10, 'P', 0, 3),
(36, 10, 'E', 0, 4),
(37, 11, '24', 0, 1),
(38, 11, '25', 0, 2),
(39, 11, '26', 1, 3),
(40, 11, '30', 0, 4),
(41, 12, 'A', 0, 1),
(42, 12, 'D', 0, 2),
(43, 12, 'C', 1, 3),
(44, 12, 'E', 0, 4),
(45, 13, 'True', 1, 1),
(46, 13, 'False', 0, 2),
(47, 1, '2', 0, 1),
(48, 1, '3', 1, 2),
(49, 1, '4', 0, 3),
(50, 1, '5', 0, 4),
(51, 2, '6', 0, 1),
(52, 2, '7', 0, 2),
(53, 2, '8', 1, 3),
(54, 2, '9', 0, 4),
(55, 3, '3', 0, 1),
(56, 3, '4', 0, 2),
(57, 3, '5', 1, 3),
(58, 3, '6', 0, 4),
(59, 4, '8', 0, 1),
(60, 4, '9', 1, 2),
(61, 4, '10', 0, 3),
(62, 4, '11', 0, 4),
(63, 5, 'True', 1, 1),
(64, 5, 'False', 0, 2),
(65, 6, '😢', 0, 1),
(66, 6, '😊', 1, 2),
(67, 6, '😠', 0, 3),
(68, 6, '😨', 0, 4),
(69, 7, 'Yell louder', 0, 1),
(70, 7, 'Take deep breaths', 1, 2),
(71, 7, 'Hit something', 0, 3),
(72, 7, 'Run away', 0, 4),
(73, 8, 'True', 1, 1),
(74, 8, 'False', 0, 2),
(75, 9, 'Laugh at them', 0, 1),
(76, 9, 'Ignore them', 0, 2),
(77, 9, 'Be kind and listen', 1, 3),
(78, 9, 'Walk away', 0, 4),
(79, 10, 'B', 0, 1),
(80, 10, 'A', 1, 2),
(81, 10, 'P', 0, 3),
(82, 10, 'E', 0, 4),
(83, 11, '24', 0, 1),
(84, 11, '25', 0, 2),
(85, 11, '26', 1, 3),
(86, 11, '30', 0, 4),
(87, 12, 'A', 0, 1),
(88, 12, 'D', 0, 2),
(89, 12, 'C', 1, 3),
(90, 12, 'E', 0, 4),
(91, 13, 'True', 1, 1),
(92, 13, 'False', 0, 2),
(93, 1, '2', 0, 1),
(94, 1, '3', 1, 2),
(95, 1, '4', 0, 3),
(96, 1, '5', 0, 4),
(97, 2, '6', 0, 1),
(98, 2, '7', 0, 2),
(99, 2, '8', 1, 3),
(100, 2, '9', 0, 4),
(101, 3, '3', 0, 1),
(102, 3, '4', 0, 2),
(103, 3, '5', 1, 3),
(104, 3, '6', 0, 4),
(105, 4, '8', 0, 1),
(106, 4, '9', 1, 2),
(107, 4, '10', 0, 3),
(108, 4, '11', 0, 4),
(109, 5, 'True', 1, 1),
(110, 5, 'False', 0, 2),
(111, 6, '😢', 0, 1),
(112, 6, '😊', 1, 2),
(113, 6, '😠', 0, 3),
(114, 6, '😨', 0, 4),
(115, 7, 'Yell louder', 0, 1),
(116, 7, 'Take deep breaths', 1, 2),
(117, 7, 'Hit something', 0, 3),
(118, 7, 'Run away', 0, 4),
(119, 8, 'True', 1, 1),
(120, 8, 'False', 0, 2),
(121, 9, 'Laugh at them', 0, 1),
(122, 9, 'Ignore them', 0, 2),
(123, 9, 'Be kind and listen', 1, 3),
(124, 9, 'Walk away', 0, 4),
(125, 10, 'B', 0, 1),
(126, 10, 'A', 1, 2),
(127, 10, 'P', 0, 3),
(128, 10, 'E', 0, 4),
(129, 11, '24', 0, 1),
(130, 11, '25', 0, 2),
(131, 11, '26', 1, 3),
(132, 11, '30', 0, 4),
(133, 12, 'A', 0, 1),
(134, 12, 'D', 0, 2),
(135, 12, 'C', 1, 3),
(136, 12, 'E', 0, 4),
(137, 13, 'True', 1, 1),
(138, 13, 'False', 0, 2),
(139, 1, '2', 0, 1),
(140, 1, '3', 1, 2),
(141, 1, '4', 0, 3),
(142, 1, '5', 0, 4),
(143, 2, '6', 0, 1),
(144, 2, '7', 0, 2),
(145, 2, '8', 1, 3),
(146, 2, '9', 0, 4),
(147, 3, '3', 0, 1),
(148, 3, '4', 0, 2),
(149, 3, '5', 1, 3),
(150, 3, '6', 0, 4),
(151, 4, '8', 0, 1),
(152, 4, '9', 1, 2),
(153, 4, '10', 0, 3),
(154, 4, '11', 0, 4),
(155, 5, 'True', 1, 1),
(156, 5, 'False', 0, 2),
(157, 6, '😢', 0, 1),
(158, 6, '😊', 1, 2),
(159, 6, '😠', 0, 3),
(160, 6, '😨', 0, 4),
(161, 7, 'Yell louder', 0, 1),
(162, 7, 'Take deep breaths', 1, 2),
(163, 7, 'Hit something', 0, 3),
(164, 7, 'Run away', 0, 4),
(165, 8, 'True', 1, 1),
(166, 8, 'False', 0, 2),
(167, 9, 'Laugh at them', 0, 1),
(168, 9, 'Ignore them', 0, 2),
(169, 9, 'Be kind and listen', 1, 3),
(170, 9, 'Walk away', 0, 4),
(171, 10, 'B', 0, 1),
(172, 10, 'A', 1, 2),
(173, 10, 'P', 0, 3),
(174, 10, 'E', 0, 4),
(175, 11, '24', 0, 1),
(176, 11, '25', 0, 2),
(177, 11, '26', 1, 3),
(178, 11, '30', 0, 4),
(179, 12, 'A', 0, 1),
(180, 12, 'D', 0, 2),
(181, 12, 'C', 1, 3),
(182, 12, 'E', 0, 4),
(183, 13, 'True', 1, 1),
(184, 13, 'False', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `question_order` int(10) UNSIGNED DEFAULT 0,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','fill_blank') DEFAULT 'multiple_choice',
  `illustration` varchar(100) DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `points` int(10) UNSIGNED DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_order`, `question_text`, `question_type`, `illustration`, `explanation`, `points`, `created_at`) VALUES
(1, 1, 1, 'How many apples are here? ????????????', 'multiple_choice', NULL, 'Count each apple: 1, 2, 3. There are 3 apples!', 1, '2026-04-18 14:31:25'),
(2, 1, 2, 'What number comes after 7?', 'multiple_choice', NULL, 'When counting: 6, 7, 8. The answer is 8!', 1, '2026-04-18 14:31:25'),
(3, 1, 3, 'How many stars? ???????????????', 'multiple_choice', NULL, 'Count: 1, 2, 3, 4, 5. There are 5 stars!', 1, '2026-04-18 14:31:25'),
(4, 1, 4, 'What number comes before 10?', 'multiple_choice', NULL, 'Counting: 8, 9, 10. The number before 10 is 9!', 1, '2026-04-18 14:31:25'),
(5, 1, 5, 'True or False: 15 comes after 14', 'true_false', NULL, '14, 15, 16 ??? yes, 15 comes right after 14!', 1, '2026-04-18 14:31:25'),
(6, 2, 1, 'Which emoji shows a HAPPY feeling?', 'multiple_choice', NULL, 'The smiling face ???? shows happiness!', 1, '2026-04-18 14:31:25'),
(7, 2, 2, 'What can help when you feel angry?', 'multiple_choice', NULL, 'Taking deep breaths helps you calm down when angry.', 1, '2026-04-18 14:31:25'),
(8, 2, 3, 'True or False: It\'s okay to feel sad sometimes', 'true_false', NULL, 'Yes! All feelings are normal and okay.', 1, '2026-04-18 14:31:25'),
(9, 2, 4, 'When your friend is sad, what should you do?', 'multiple_choice', NULL, 'Being kind and listening to your friend helps them feel better.', 1, '2026-04-18 14:31:25'),
(10, 3, 1, 'What letter does APPLE start with?', 'multiple_choice', NULL, 'Apple starts with the letter A!', 1, '2026-04-18 14:31:25'),
(11, 3, 2, 'How many letters are in the alphabet?', 'multiple_choice', NULL, 'The English alphabet has 26 letters, from A to Z!', 1, '2026-04-18 14:31:25'),
(12, 3, 3, 'Which letter comes after B?', 'multiple_choice', NULL, 'A, B, C ??? the letter C comes after B!', 1, '2026-04-18 14:31:25'),
(13, 3, 4, 'True or False: The letter Z is the last letter', 'true_false', NULL, 'Yes! Z is the 26th and last letter of the alphabet.', 1, '2026-04-18 14:31:25'),
(14, 1, 1, 'How many apples are here? 🍎🍎🍎', 'multiple_choice', NULL, 'Count each apple: 1, 2, 3. There are 3 apples!', 1, '2026-05-16 11:55:18'),
(15, 1, 2, 'What number comes after 7?', 'multiple_choice', NULL, 'When counting: 6, 7, 8. The answer is 8!', 1, '2026-05-16 11:55:18'),
(16, 1, 3, 'How many stars? ⭐⭐⭐⭐⭐', 'multiple_choice', NULL, 'Count: 1, 2, 3, 4, 5. There are 5 stars!', 1, '2026-05-16 11:55:18'),
(17, 1, 4, 'What number comes before 10?', 'multiple_choice', NULL, 'Counting: 8, 9, 10. The number before 10 is 9!', 1, '2026-05-16 11:55:18'),
(18, 1, 5, 'True or False: 15 comes after 14', 'true_false', NULL, '14, 15, 16 — yes, 15 comes right after 14!', 1, '2026-05-16 11:55:18'),
(19, 2, 1, 'Which emoji shows a HAPPY feeling?', 'multiple_choice', NULL, 'The smiling face 😊 shows happiness!', 1, '2026-05-16 11:55:18'),
(20, 2, 2, 'What can help when you feel angry?', 'multiple_choice', NULL, 'Taking deep breaths helps you calm down when angry.', 1, '2026-05-16 11:55:18'),
(21, 2, 3, 'True or False: It\'s okay to feel sad sometimes', 'true_false', NULL, 'Yes! All feelings are normal and okay.', 1, '2026-05-16 11:55:18'),
(22, 2, 4, 'When your friend is sad, what should you do?', 'multiple_choice', NULL, 'Being kind and listening to your friend helps them feel better.', 1, '2026-05-16 11:55:18'),
(23, 3, 1, 'What letter does APPLE start with?', 'multiple_choice', NULL, 'Apple starts with the letter A!', 1, '2026-05-16 11:55:18'),
(24, 3, 2, 'How many letters are in the alphabet?', 'multiple_choice', NULL, 'The English alphabet has 26 letters, from A to Z!', 1, '2026-05-16 11:55:18'),
(25, 3, 3, 'Which letter comes after B?', 'multiple_choice', NULL, 'A, B, C — the letter C comes after B!', 1, '2026-05-16 11:55:18'),
(26, 3, 4, 'True or False: The letter Z is the last letter', 'true_false', NULL, 'Yes! Z is the 26th and last letter of the alphabet.', 1, '2026-05-16 11:55:18'),
(27, 1, 1, 'How many apples are here? 🍎🍎🍎', 'multiple_choice', NULL, 'Count each apple: 1, 2, 3. There are 3 apples!', 1, '2026-05-16 11:55:33'),
(28, 1, 2, 'What number comes after 7?', 'multiple_choice', NULL, 'When counting: 6, 7, 8. The answer is 8!', 1, '2026-05-16 11:55:33'),
(29, 1, 3, 'How many stars? ⭐⭐⭐⭐⭐', 'multiple_choice', NULL, 'Count: 1, 2, 3, 4, 5. There are 5 stars!', 1, '2026-05-16 11:55:33'),
(30, 1, 4, 'What number comes before 10?', 'multiple_choice', NULL, 'Counting: 8, 9, 10. The number before 10 is 9!', 1, '2026-05-16 11:55:33'),
(31, 1, 5, 'True or False: 15 comes after 14', 'true_false', NULL, '14, 15, 16 — yes, 15 comes right after 14!', 1, '2026-05-16 11:55:33'),
(32, 2, 1, 'Which emoji shows a HAPPY feeling?', 'multiple_choice', NULL, 'The smiling face 😊 shows happiness!', 1, '2026-05-16 11:55:33'),
(33, 2, 2, 'What can help when you feel angry?', 'multiple_choice', NULL, 'Taking deep breaths helps you calm down when angry.', 1, '2026-05-16 11:55:33'),
(34, 2, 3, 'True or False: It\'s okay to feel sad sometimes', 'true_false', NULL, 'Yes! All feelings are normal and okay.', 1, '2026-05-16 11:55:33'),
(35, 2, 4, 'When your friend is sad, what should you do?', 'multiple_choice', NULL, 'Being kind and listening to your friend helps them feel better.', 1, '2026-05-16 11:55:33'),
(36, 3, 1, 'What letter does APPLE start with?', 'multiple_choice', NULL, 'Apple starts with the letter A!', 1, '2026-05-16 11:55:33'),
(37, 3, 2, 'How many letters are in the alphabet?', 'multiple_choice', NULL, 'The English alphabet has 26 letters, from A to Z!', 1, '2026-05-16 11:55:33'),
(38, 3, 3, 'Which letter comes after B?', 'multiple_choice', NULL, 'A, B, C — the letter C comes after B!', 1, '2026-05-16 11:55:33'),
(39, 3, 4, 'True or False: The letter Z is the last letter', 'true_false', NULL, 'Yes! Z is the 26th and last letter of the alphabet.', 1, '2026-05-16 11:55:33'),
(40, 1, 1, 'How many apples are here? 🍎🍎🍎', 'multiple_choice', NULL, 'Count each apple: 1, 2, 3. There are 3 apples!', 1, '2026-05-16 11:56:14'),
(41, 1, 2, 'What number comes after 7?', 'multiple_choice', NULL, 'When counting: 6, 7, 8. The answer is 8!', 1, '2026-05-16 11:56:14'),
(42, 1, 3, 'How many stars? ⭐⭐⭐⭐⭐', 'multiple_choice', NULL, 'Count: 1, 2, 3, 4, 5. There are 5 stars!', 1, '2026-05-16 11:56:14'),
(43, 1, 4, 'What number comes before 10?', 'multiple_choice', NULL, 'Counting: 8, 9, 10. The number before 10 is 9!', 1, '2026-05-16 11:56:14'),
(44, 1, 5, 'True or False: 15 comes after 14', 'true_false', NULL, '14, 15, 16 — yes, 15 comes right after 14!', 1, '2026-05-16 11:56:14'),
(45, 2, 1, 'Which emoji shows a HAPPY feeling?', 'multiple_choice', NULL, 'The smiling face 😊 shows happiness!', 1, '2026-05-16 11:56:14'),
(46, 2, 2, 'What can help when you feel angry?', 'multiple_choice', NULL, 'Taking deep breaths helps you calm down when angry.', 1, '2026-05-16 11:56:14'),
(47, 2, 3, 'True or False: It\'s okay to feel sad sometimes', 'true_false', NULL, 'Yes! All feelings are normal and okay.', 1, '2026-05-16 11:56:14'),
(48, 2, 4, 'When your friend is sad, what should you do?', 'multiple_choice', NULL, 'Being kind and listening to your friend helps them feel better.', 1, '2026-05-16 11:56:14'),
(49, 3, 1, 'What letter does APPLE start with?', 'multiple_choice', NULL, 'Apple starts with the letter A!', 1, '2026-05-16 11:56:14'),
(50, 3, 2, 'How many letters are in the alphabet?', 'multiple_choice', NULL, 'The English alphabet has 26 letters, from A to Z!', 1, '2026-05-16 11:56:14'),
(51, 3, 3, 'Which letter comes after B?', 'multiple_choice', NULL, 'A, B, C — the letter C comes after B!', 1, '2026-05-16 11:56:14'),
(52, 3, 4, 'True or False: The letter Z is the last letter', 'true_false', NULL, 'Yes! Z is the 26th and last letter of the alphabet.', 1, '2026-05-16 11:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','non_binary','prefer_not_to_say') DEFAULT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `student_id_number` varchar(100) DEFAULT NULL,
  `parent_guardian_name` varchar(200) DEFAULT NULL,
  `parent_guardian_email` varchar(255) DEFAULT NULL,
  `parent_guardian_phone` varchar(30) DEFAULT NULL,
  `emergency_contact` varchar(200) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `profile_photo` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `import_source` enum('manual','csv','document') DEFAULT 'manual',
  `import_log_id` int(10) UNSIGNED DEFAULT NULL,
  `is_draft` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `teacher_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `grade_level`, `school_name`, `student_id_number`, `parent_guardian_name`, `parent_guardian_email`, `parent_guardian_phone`, `emergency_contact`, `emergency_phone`, `profile_photo`, `notes`, `is_active`, `import_source`, `import_log_id`, `is_draft`, `created_at`, `updated_at`) VALUES
(37, 46, 24, 'Test', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'manual', NULL, 0, '2026-05-04 14:24:56', '2026-05-04 14:24:56'),
(41, 52, 26, 'Demo', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'manual', NULL, 0, '2026-05-13 17:58:24', '2026-05-13 17:58:24'),
(42, 53, NULL, 'Francis Zachary', 'Domingo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'manual', NULL, 0, '2026-05-18 07:27:21', '2026-05-18 07:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `student_achievements`
--

CREATE TABLE `student_achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `achievement_id` int(10) UNSIGNED NOT NULL,
  `progress` int(10) UNSIGNED DEFAULT 0,
  `is_unlocked` tinyint(1) DEFAULT 0,
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_achievements`
--

INSERT INTO `student_achievements` (`id`, `student_id`, `achievement_id`, `progress`, `is_unlocked`, `unlocked_at`, `notified`, `created_at`, `updated_at`) VALUES
(43, 37, 12, 1, 1, '2026-05-04 14:34:27', 0, '2026-05-04 14:34:27', '2026-05-04 14:34:27'),
(44, 37, 1, 0, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-05 15:29:21'),
(45, 37, 2, 0, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-05 15:29:21'),
(46, 37, 3, 0, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-05 15:29:21'),
(47, 37, 4, 1000, 1, '2026-05-16 15:02:39', 0, '2026-05-05 15:29:21', '2026-05-16 15:02:39'),
(48, 37, 5, 2172, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-16 15:15:59'),
(49, 37, 6, 2172, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-16 15:15:59'),
(50, 37, 7, 2, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-16 15:01:39'),
(51, 37, 8, 2, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-16 15:01:39'),
(52, 37, 9, 2, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-16 15:01:39'),
(53, 37, 10, 1, 1, '2026-05-05 15:29:21', 0, '2026-05-05 15:29:21', '2026-05-05 15:29:21'),
(54, 37, 11, 0, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-05 15:29:21'),
(55, 37, 13, 0, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-05 15:29:21'),
(56, 37, 14, 0, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-05 15:29:21'),
(57, 37, 15, 5, 1, '2026-05-16 15:02:39', 0, '2026-05-05 15:29:21', '2026-05-16 15:02:39'),
(58, 37, 16, 2, 1, '2026-05-15 08:17:35', 0, '2026-05-05 15:29:21', '2026-05-15 08:17:35'),
(59, 37, 17, 3, 0, NULL, 0, '2026-05-05 15:29:21', '2026-05-16 15:15:59'),
(95, 41, 12, 1, 1, '2026-05-13 18:04:36', 0, '2026-05-13 18:04:36', '2026-05-13 18:04:36'),
(98, 37, 21, 0, 0, NULL, 0, '2026-05-16 15:01:39', '2026-05-16 15:01:39'),
(103, 37, 26, 2172, 0, NULL, 0, '2026-05-16 15:01:39', '2026-05-16 15:15:59'),
(113, 37, 36, 7, 0, NULL, 0, '2026-05-16 15:01:39', '2026-05-16 15:15:59'),
(115, 37, 38, 3, 1, '2026-05-16 15:15:59', 0, '2026-05-16 15:01:39', '2026-05-16 15:15:59');

-- --------------------------------------------------------

--
-- Table structure for table `student_activity_log`
--

CREATE TABLE `student_activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `activity_type` enum('quest','quiz','activity','daily_challenge') NOT NULL,
  `activity_id` int(10) UNSIGNED DEFAULT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `max_score` decimal(6,2) DEFAULT NULL,
  `attempts` int(10) UNSIGNED DEFAULT 1,
  `time_spent_seconds` int(10) UNSIGNED DEFAULT NULL,
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responses`)),
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_activity_log`
--

INSERT INTO `student_activity_log` (`id`, `student_id`, `activity_type`, `activity_id`, `course_id`, `title`, `score`, `max_score`, `attempts`, `time_spent_seconds`, `responses`, `completed_at`) VALUES
(16, 37, 'activity', NULL, NULL, 'Arrange Numbers ↑ (math)', 100.00, 100.00, 1, NULL, NULL, '2026-05-05 15:29:21'),
(17, 37, 'activity', NULL, NULL, 'Arrange Numbers ↑ (math)', 100.00, 100.00, 1, NULL, NULL, '2026-05-05 15:31:27'),
(26, 37, 'activity', NULL, NULL, 'Build CVC Words (/Ii/) (english)', 100.00, 100.00, 1, NULL, NULL, '2026-05-13 18:13:52'),
(27, 37, 'activity', NULL, NULL, 'Arrange Numbers ↑ (math)', 100.00, 100.00, 1, NULL, NULL, '2026-05-15 08:17:35'),
(28, 37, 'activity', NULL, NULL, 'Build CVC Words (/Ii/) (english)', 100.00, 100.00, 1, NULL, NULL, '2026-05-15 08:18:45'),
(29, 37, 'activity', NULL, NULL, 'Arrange Numbers ↑ (math)', 100.00, 100.00, 1, NULL, NULL, '2026-05-15 15:05:29'),
(30, 37, 'activity', NULL, NULL, 'Hello (math)', 100.00, 100.00, 1, NULL, NULL, '2026-05-16 15:01:39'),
(31, 37, 'activity', NULL, NULL, 'Arrange Numbers ↑ (math)', 100.00, 100.00, 1, NULL, NULL, '2026-05-16 15:02:39'),
(32, 37, 'activity', NULL, NULL, 'Math True or False? (math)', 88.00, 100.00, 1, NULL, NULL, '2026-05-16 15:15:59');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `document_type` enum('iep','itp','individual_profile','medical_report','psychological_evaluation','progress_report','504_plan','parent_consent','other') DEFAULT 'other',
  `title` varchar(255) NOT NULL,
  `original_filename` varchar(500) NOT NULL,
  `stored_filename` varchar(500) NOT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_gamification`
--

CREATE TABLE `student_gamification` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `total_xp` int(10) UNSIGNED DEFAULT 0,
  `current_level` tinyint(3) UNSIGNED DEFAULT 1,
  `team` enum('fire','water','grass') DEFAULT NULL,
  `egg_type` enum('fire','water','grass') DEFAULT NULL,
  `pet_name` varchar(32) DEFAULT NULL COMMENT 'Student-chosen display name for their pet companion',
  `egg_stage` tinyint(3) UNSIGNED DEFAULT 1,
  `streak_days` int(10) UNSIGNED DEFAULT 0,
  `longest_streak` int(10) UNSIGNED DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `daily_xp_earned` int(10) UNSIGNED DEFAULT 0,
  `daily_xp_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_gamification`
--

INSERT INTO `student_gamification` (`id`, `student_id`, `total_xp`, `current_level`, `team`, `egg_type`, `pet_name`, `egg_stage`, `streak_days`, `longest_streak`, `last_activity_date`, `daily_xp_earned`, `daily_xp_date`, `created_at`, `updated_at`) VALUES
(9, 37, 3222, 8, 'fire', 'fire', NULL, 3, 1, 4, '2026-05-18', 0, '2026-05-18', '2026-05-04 14:34:18', '2026-05-18 07:39:23'),
(13, 41, 0, 1, 'water', 'water', 'Benjamin', 1, 1, 0, '2026-05-13', 0, '2026-05-13', '2026-05-13 18:04:13', '2026-05-13 18:04:36');

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `assessment_name` varchar(255) NOT NULL,
  `assessment_type` enum('quiz','exam','assignment','project','participation','final') NOT NULL DEFAULT 'assignment',
  `score` decimal(5,2) NOT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `graded_at` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_grades`
--

INSERT INTO `student_grades` (`id`, `student_id`, `course_id`, `teacher_id`, `assessment_name`, `assessment_type`, `score`, `max_score`, `graded_at`, `remarks`, `created_at`, `updated_at`) VALUES
(17, 37, NULL, 24, 'Arrange Numbers ↑', 'quiz', 100.00, 100.00, '2026-05-05', 'Auto-recorded from quest: 6/6 correct', '2026-05-05 15:29:21', '2026-05-05 15:29:21'),
(18, 37, NULL, 24, 'Arrange Numbers ↑', 'quiz', 100.00, 100.00, '2026-05-05', 'Auto-recorded from quest: 6/6 correct', '2026-05-05 15:31:27', '2026-05-05 15:31:27'),
(24, 37, NULL, 24, 'Build CVC Words (/Ii/)', 'assignment', 100.00, 100.00, '2026-05-13', 'Auto-recorded from quest: 10/10 correct', '2026-05-13 18:13:52', '2026-05-13 18:13:52'),
(25, 37, NULL, 24, 'Clash of Clans', 'quiz', 1.00, 1.00, '2026-05-13', NULL, '2026-05-13 18:14:07', '2026-05-13 18:14:07'),
(26, 37, NULL, 24, 'Arrange Numbers ↑', 'quiz', 100.00, 100.00, '2026-05-15', 'Auto-recorded from quest: 6/6 correct', '2026-05-15 08:17:35', '2026-05-15 08:17:35'),
(27, 37, NULL, 24, 'Build CVC Words (/Ii/)', 'assignment', 100.00, 100.00, '2026-05-15', 'Auto-recorded from quest: 10/10 correct', '2026-05-15 08:18:45', '2026-05-15 08:18:45'),
(28, 37, NULL, 24, 'Clash of Clans', 'quiz', 0.00, 100.00, '2026-05-15', NULL, '2026-05-15 12:44:49', '2026-05-15 12:44:49'),
(29, 37, NULL, 24, 'Clash of Clans', 'quiz', 1.00, 1.00, '2026-05-15', NULL, '2026-05-15 12:45:51', '2026-05-15 12:45:51'),
(30, 37, NULL, 24, 'Clash of Clans', 'quiz', 1.00, 1.00, '2026-05-15', NULL, '2026-05-15 12:54:12', '2026-05-15 12:54:12'),
(31, 37, NULL, 24, 'Test', 'quiz', 3.00, 3.00, '2026-05-15', NULL, '2026-05-15 13:22:38', '2026-05-15 13:22:38'),
(32, 37, NULL, 24, 'Arrange Numbers ↑', 'quiz', 100.00, 100.00, '2026-05-15', 'Auto-recorded from quest: 6/6 correct', '2026-05-15 15:05:29', '2026-05-15 15:05:29'),
(33, 37, NULL, 24, 'Hello', 'quiz', 100.00, 100.00, '2026-05-16', 'Auto-recorded from quest: 1/1 correct', '2026-05-16 15:01:39', '2026-05-16 15:01:39'),
(34, 37, NULL, 24, 'Arrange Numbers ↑', 'quiz', 100.00, 100.00, '2026-05-16', 'Auto-recorded from quest: 6/6 correct', '2026-05-16 15:02:39', '2026-05-16 15:02:39'),
(35, 37, NULL, 24, 'Math True or False?', 'quiz', 88.00, 100.00, '2026-05-16', 'Auto-recorded from quest: 7/8 correct', '2026-05-16 15:15:59', '2026-05-16 15:15:59');

-- --------------------------------------------------------

--
-- Table structure for table `student_iep`
--

CREATE TABLE `student_iep` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `entry_method` enum('manual','uploaded') DEFAULT 'manual',
  `document_id` int(10) UNSIGNED DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `meeting_date` date DEFAULT NULL,
  `disability_classification` varchar(200) DEFAULT NULL,
  `sped_category` varchar(50) DEFAULT NULL,
  `iep_team` text DEFAULT NULL,
  `plep_academic` text DEFAULT NULL,
  `plep_functional` text DEFAULT NULL,
  `plep_social` text DEFAULT NULL,
  `annual_goals` text DEFAULT NULL,
  `short_term_objectives` text DEFAULT NULL,
  `sped_services` text DEFAULT NULL,
  `related_services` text DEFAULT NULL,
  `accommodations_notes` text DEFAULT NULL,
  `modifications_notes` text DEFAULT NULL,
  `regular_ed_percentage` tinyint(3) UNSIGNED DEFAULT NULL,
  `assessment_accommodations` text DEFAULT NULL,
  `transition_services` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_individual_profile`
--

CREATE TABLE `student_individual_profile` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `entry_method` enum('manual','uploaded') DEFAULT 'manual',
  `document_id` int(10) UNSIGNED DEFAULT NULL,
  `disability_classification` varchar(200) DEFAULT NULL,
  `sped_category` varchar(100) DEFAULT NULL,
  `years_in_sped` tinyint(3) UNSIGNED DEFAULT NULL,
  `preferred_name` varchar(100) DEFAULT NULL,
  `preferred_pronouns` varchar(50) DEFAULT NULL,
  `primary_language` varchar(100) DEFAULT NULL,
  `academic_strengths` text DEFAULT NULL,
  `academic_challenges` text DEFAULT NULL,
  `behavioral_strengths` text DEFAULT NULL,
  `behavioral_challenges` text DEFAULT NULL,
  `social_strengths` text DEFAULT NULL,
  `social_challenges` text DEFAULT NULL,
  `learning_style` enum('visual','auditory','kinesthetic','mixed','other') DEFAULT 'mixed',
  `learning_style_notes` text DEFAULT NULL,
  `attention_span` enum('short','moderate','good','variable') DEFAULT 'variable',
  `communication_profile` text DEFAULT NULL,
  `motivators` text DEFAULT NULL,
  `triggers` text DEFAULT NULL,
  `calming_strategies` text DEFAULT NULL,
  `reinforcement_strategies` text DEFAULT NULL,
  `family_support_level` enum('high','moderate','limited','unknown') DEFAULT 'unknown',
  `outside_services` text DEFAULT NULL,
  `student_voice` text DEFAULT NULL,
  `teacher_observations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_itp`
--

CREATE TABLE `student_itp` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `entry_method` enum('manual','uploaded') DEFAULT 'manual',
  `document_id` int(10) UNSIGNED DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `disability_category` varchar(200) DEFAULT NULL,
  `career_interests` text DEFAULT NULL,
  `assessed_strengths` text DEFAULT NULL,
  `work_experiences` text DEFAULT NULL,
  `community_experiences` text DEFAULT NULL,
  `daily_living_skills` text DEFAULT NULL,
  `goal_postsecondary_education` text DEFAULT NULL,
  `goal_employment` text DEFAULT NULL,
  `goal_independent_living` text DEFAULT NULL,
  `goal_community` text DEFAULT NULL,
  `services_instruction` text DEFAULT NULL,
  `services_community` text DEFAULT NULL,
  `services_employment` text DEFAULT NULL,
  `services_adult_living` text DEFAULT NULL,
  `course_of_study` text DEFAULT NULL,
  `agency_linkages` text DEFAULT NULL,
  `annual_goals_transition` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_lesson_progress`
--

CREATE TABLE `student_lesson_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `status` enum('locked','available','in_progress','completed') DEFAULT 'locked',
  `current_page` int(10) UNSIGNED DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `xp_earned` int(10) UNSIGNED DEFAULT 0,
  `time_spent_sec` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_lesson_progress`
--

INSERT INTO `student_lesson_progress` (`id`, `student_id`, `lesson_id`, `status`, `current_page`, `started_at`, `completed_at`, `xp_earned`, `time_spent_sec`) VALUES
(2, 37, 1, 'available', 0, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_quiz_attempts`
--

CREATE TABLE `student_quiz_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `attempt_number` int(10) UNSIGNED DEFAULT 1,
  `score` int(10) UNSIGNED DEFAULT 0,
  `max_score` int(10) UNSIGNED DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `passed` tinyint(1) DEFAULT 0,
  `time_spent_sec` int(10) UNSIGNED DEFAULT 0,
  `xp_earned` int(10) UNSIGNED DEFAULT 0,
  `answers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers_json`)),
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_rewards`
--

CREATE TABLE `student_rewards` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `reward_id` int(10) UNSIGNED NOT NULL,
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_rewards`
--

INSERT INTO `student_rewards` (`id`, `student_id`, `reward_id`, `claimed_at`) VALUES
(2, 37, 1, '2026-05-15 15:05:29'),
(3, 37, 7, '2026-05-16 15:01:39'),
(4, 37, 13, '2026-05-16 15:01:39'),
(5, 37, 19, '2026-05-16 15:01:39'),
(6, 37, 2, '2026-05-16 15:15:59'),
(7, 37, 8, '2026-05-16 15:15:59'),
(8, 37, 14, '2026-05-16 15:15:59'),
(9, 37, 20, '2026-05-16 15:15:59');

-- --------------------------------------------------------

--
-- Table structure for table `student_settings_overrides`
--

CREATE TABLE `student_settings_overrides` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_settings_overrides`
--

INSERT INTO `student_settings_overrides` (`id`, `student_id`, `teacher_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 13, 15, 'leaderboard_mode', 'disabled', '2026-04-16 14:20:04', '2026-04-16 14:20:04'),
(2, 13, 15, 'xp_multiplier', '2', '2026-04-16 14:20:04', '2026-04-16 14:20:04'),
(3, 13, 15, 'max_daily_xp', '500', '2026-04-16 14:20:04', '2026-04-16 14:20:04'),
(4, 13, 15, 'quiz_timer_seconds', '30', '2026-04-16 14:20:04', '2026-04-16 14:20:04'),
(5, 13, 15, 'game_timer_seconds', '30', '2026-04-16 14:20:04', '2026-04-16 14:20:04'),
(15, 30, 20, 'leaderboard_mode', 'enabled', '2026-04-18 13:36:37', '2026-04-18 13:36:37'),
(16, 30, 20, 'quiz_timer_seconds', '30', '2026-04-18 13:36:37', '2026-04-18 13:36:37'),
(17, 30, 20, 'game_timer_seconds', '25', '2026-04-18 13:36:37', '2026-04-18 13:36:37'),
(19, 36, 23, 'leaderboard_mode', 'enabled', '2026-04-22 13:26:19', '2026-04-22 13:26:19');

-- --------------------------------------------------------

--
-- Table structure for table `student_subject_progress`
--

CREATE TABLE `student_subject_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `status` enum('locked','active','completed') DEFAULT 'locked',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `total_xp_earned` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subject_progress`
--

INSERT INTO `student_subject_progress` (`id`, `student_id`, `subject_id`, `status`, `started_at`, `completed_at`, `total_xp_earned`) VALUES
(2, 37, 1, 'active', '2026-05-13 18:59:24', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(10) DEFAULT '????',
  `color` varchar(7) DEFAULT '#6366f1',
  `bg_color` varchar(7) DEFAULT '#eef2ff',
  `sort_order` int(10) UNSIGNED DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `slug`, `title`, `description`, `icon`, `color`, `bg_color`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'math', 'Math Adventures', 'Explore numbers, shapes, and puzzles in fun math quests!', '????', '#ef4444', '#fef2f2', 1, 1, '2026-04-18 14:31:25'),
(2, 'self_care', 'Self Care Journey', 'Learn about emotions, mindfulness, and healthy habits!', '????', '#10b981', '#ecfdf5', 2, 1, '2026-04-18 14:31:25'),
(3, 'english', 'English Quest', 'Master reading, writing, and storytelling through adventures!', '????', '#3b82f6', '#eff6ff', 3, 1, '2026-04-18 14:31:25');

-- --------------------------------------------------------

--
-- Table structure for table `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admins`
--

INSERT INTO `super_admins` (`id`, `full_name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Test Super Admin', 'testsuperadmin@eduquest.test', '$2y$12$CqsyBiPK7zeNehK.ugY2x.CGhEb8HGK2n29.fCli/5iaFlDI/Ertq', '2026-05-12 14:09:46');

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses`
--

CREATE TABLE `survey_responses` (
  `id` int(10) UNSIGNED NOT NULL,
  `respondent_id` int(10) UNSIGNED NOT NULL,
  `respondent_role` enum('teacher','student') NOT NULL,
  `survey_type` enum('pssuq_teacher','pssuq_student') NOT NULL,
  `responses_json` longtext NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_period` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` tinyint(4) NOT NULL DEFAULT 1,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
(1, 'pretest_enabled', 1, NULL, '2026-05-12 14:07:50'),
(2, 'posttest_enabled', 1, NULL, '2026-05-12 14:07:50'),
(3, 'pssuq_teacher_enabled', 1, NULL, '2026-05-12 14:07:50'),
(4, 'pssuq_student_enabled', 1, NULL, '2026-05-12 14:07:50');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `role` enum('teacher','admin') DEFAULT 'teacher',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `first_name`, `last_name`, `email`, `school_name`, `department`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(24, 45, 'Test', 'Teacher', 'testteacher@eduquest.test', NULL, NULL, 'teacher', 1, '2026-05-04 14:24:56', '2026-05-04 14:24:56'),
(26, 51, 'Demo', 'Teacher', 'testteacher2@eduquest.test', NULL, NULL, 'teacher', 1, '2026-05-13 17:58:24', '2026-05-13 17:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_activities`
--

CREATE TABLE `teacher_activities` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `category` enum('math','english','selfcare') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(10) DEFAULT '?' COMMENT 'emoji icon',
  `activity_type` enum('sort-order','classify','compare','choose','build-word','custom') NOT NULL DEFAULT 'custom',
  `instructions` text DEFAULT NULL COMMENT 'shown during game',
  `rounds` tinyint(3) UNSIGNED DEFAULT 6 COMMENT 'number of questions/rounds',
  `xp_reward` int(10) UNSIGNED NOT NULL DEFAULT 50 COMMENT 'XP given on completion',
  `pass_percentage` tinyint(3) UNSIGNED NOT NULL DEFAULT 70,
  `max_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
  `time_limit_sec` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no limit',
  `cover_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_activities`
--

INSERT INTO `teacher_activities` (`id`, `teacher_id`, `category`, `title`, `description`, `icon`, `activity_type`, `instructions`, `rounds`, `xp_reward`, `pass_percentage`, `max_attempts`, `time_limit_sec`, `cover_image`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 24, 'math', 'Hello', '', '🎮', 'sort-order', '', 6, 50, 70, 0, 0, NULL, 1, '2026-05-18 15:06:56', '2026-05-18 15:06:56');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_activity_assignments`
--

CREATE TABLE `teacher_activity_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `activity_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `student_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = all students in course',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_activity_attempts`
--

CREATE TABLE `teacher_activity_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `activity_id` int(10) UNSIGNED NOT NULL,
  `attempt_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `max_score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `time_spent_sec` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `xp_earned` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `answers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'snapshot of student answers' CHECK (json_valid(`answers_json`)),
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_activity_items`
--

CREATE TABLE `teacher_activity_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `activity_id` int(10) UNSIGNED NOT NULL,
  `item_order` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `item_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'flexible structure based on activity_type' CHECK (json_valid(`item_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_activity_items`
--

INSERT INTO `teacher_activity_items` (`id`, `activity_id`, `item_order`, `item_data`, `created_at`) VALUES
(4, 3, 1, '{\"items\":[5,2,6,7,12]}', '2026-05-18 15:06:56');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assigned_games`
--

CREATE TABLE `teacher_assigned_games` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `game_id` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_default_game_settings`
--

CREATE TABLE `teacher_default_game_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `game_id` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_default_game_settings`
--

INSERT INTO `teacher_default_game_settings` (`id`, `teacher_id`, `game_id`, `is_enabled`, `created_at`, `updated_at`) VALUES
(1, 24, 'math-sort-asc', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(2, 24, 'math-compare', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(3, 24, 'math-ordinal', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(4, 24, 'math-truefalse', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(5, 24, 'math-pairs', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(6, 24, 'eng-build-cvc', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(7, 24, 'eng-read-cvc', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(8, 24, 'eng-sentences', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(9, 24, 'eng-truefalse', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(10, 24, 'eng-pairs', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(11, 24, 'sc-living', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(12, 24, 'sc-food', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(13, 24, 'sc-eating-habits', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(14, 24, 'sc-truefalse', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(15, 24, 'sc-pairs', 1, '2026-05-16 13:09:54', '2026-05-16 14:59:37'),
(31, 0, 'teacher_activities', 1, '2026-05-16 15:04:30', '2026-05-16 15:04:30');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_notes`
--

CREATE TABLE `teacher_notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `note_date` date NOT NULL,
  `note_type` enum('observation','progress','incident','meeting','general') DEFAULT 'general',
  `subject_area` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `is_private` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_quizzes`
--

CREATE TABLE `teacher_quizzes` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `pass_percentage` tinyint(3) UNSIGNED NOT NULL DEFAULT 70,
  `max_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
  `time_limit_sec` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no limit',
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 1,
  `shuffle_answers` tinyint(1) NOT NULL DEFAULT 1,
  `xp_reward` int(10) UNSIGNED NOT NULL DEFAULT 50,
  `show_score` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = students see their score after completing; 0 = hidden',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_quizzes`
--

INSERT INTO `teacher_quizzes` (`id`, `teacher_id`, `course_id`, `title`, `description`, `instructions`, `cover_image`, `pass_percentage`, `max_attempts`, `time_limit_sec`, `shuffle_questions`, `shuffle_answers`, `xp_reward`, `show_score`, `is_active`, `created_at`, `updated_at`) VALUES
(8, 24, NULL, 'Clash of Clans', '', '', NULL, 70, 0, 0, 1, 1, 50, 1, 1, '2026-05-15 12:53:32', '2026-05-15 12:53:32'),
(9, 24, NULL, 'Test', '', '', NULL, 70, 0, 0, 1, 1, 50, 1, 1, '2026-05-15 13:21:08', '2026-05-15 13:21:08');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_quiz_answers`
--

CREATE TABLE `teacher_quiz_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `answer_text` varchar(500) NOT NULL,
  `answer_image` varchar(255) DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `match_target` varchar(500) DEFAULT NULL COMMENT 'For drag_drop: zone label; For matching: paired item',
  `answer_order` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_quiz_answers`
--

INSERT INTO `teacher_quiz_answers` (`id`, `question_id`, `answer_text`, `answer_image`, `is_correct`, `match_target`, `answer_order`) VALUES
(51, 15, 'A giant', '', 0, '', 1),
(52, 15, 'A max fucking giant', '', 1, '', 2),
(53, 15, 'Dave', '', 0, '', 3),
(54, 15, 'A max giant', '', 0, '', 4),
(55, 16, 'Spoon and Fork', '', 1, '', 1),
(56, 17, 'Big', '', 0, 'Tall', 1),
(57, 17, 'Small', '', 0, 'Tiny', 2),
(58, 18, 'Cellphone', '', 0, 'Electronics', 1),
(59, 18, 'Egg', '', 0, 'Food', 2);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_quiz_assignments`
--

CREATE TABLE `teacher_quiz_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `student_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = all students in course',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` date DEFAULT NULL,
  `max_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = inherit from teacher_quizzes.max_attempts'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_quiz_attempts`
--

CREATE TABLE `teacher_quiz_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK teacher_quiz_assignments.id — NULL for unassigned attempts',
  `attempt_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `max_score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `time_spent_sec` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `xp_earned` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `answers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Snapshot of student answers' CHECK (json_valid(`answers_json`)),
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `is_abandoned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_quiz_attempts`
--

INSERT INTO `teacher_quiz_attempts` (`id`, `student_id`, `quiz_id`, `assignment_id`, `attempt_number`, `score`, `max_score`, `percentage`, `passed`, `time_spent_sec`, `xp_earned`, `answers_json`, `started_at`, `completed_at`, `is_abandoned`) VALUES
(8, 37, 8, NULL, 1, 1, 1, 100.00, 1, 2, 75, '{\"15\":52}', '2026-05-15 12:54:12', '2026-05-15 12:54:12', 0),
(9, 37, 9, NULL, 1, 3, 3, 100.00, 1, 18, 75, '{\"16\":\"Spoon and Fork\",\"17\":{\"56\":\"Tall\",\"57\":\"Tiny\"},\"18\":{\"58\":\"Electronics\",\"59\":\"Food\"}}', '2026-05-15 13:22:38', '2026-05-15 13:22:38', 0);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_quiz_questions`
--

CREATE TABLE `teacher_quiz_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `question_order` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `question_type` enum('multiple_choice','fill_blank','drag_drop','matching','choose_from_box') NOT NULL,
  `question_text` text NOT NULL,
  `question_image` varchar(255) DEFAULT NULL COMMENT 'uploaded image path',
  `explanation` text DEFAULT NULL COMMENT 'shown after answering',
  `points` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_quiz_questions`
--

INSERT INTO `teacher_quiz_questions` (`id`, `quiz_id`, `question_order`, `question_type`, `question_text`, `question_image`, `explanation`, `points`, `created_at`) VALUES
(15, 8, 1, 'multiple_choice', 'Who is this?', '/V1.14_idk%20anymore/EduQuest/EDUQUEST/uploads/quiz-images/9587db0810907536ba3206cfe263c528.png', '', 1, '2026-05-15 12:53:32'),
(16, 9, 1, 'fill_blank', 'Utensils used for feeding?', '', '', 1, '2026-05-15 13:21:08'),
(17, 9, 2, 'matching', 'Match the following', '', '', 1, '2026-05-15 13:21:08'),
(18, 9, 3, 'drag_drop', 'Drag and Drop this', '', '', 1, '2026-05-15 13:21:08');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_whitelist`
--

CREATE TABLE `teacher_whitelist` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `added_by` int(10) UNSIGNED DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_whitelist`
--

INSERT INTO `teacher_whitelist` (`id`, `email`, `notes`, `added_by`, `added_at`) VALUES
(1, 'testteacher@eduquest.test', 'test seeder', NULL, '2026-05-04 14:24:56'),
(2, 'mayriellej@gmail.com', 'Mayrielle Joy Latigo', 1, '2026-05-11 06:41:27'),
(4, 'testteacher2@eduquest.test', 'Auto-added by test-accounts.php seeder', NULL, '2026-05-13 17:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('student','teacher','admin') NOT NULL,
  `profile_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `account_status` enum('active','inactive','suspended','archived') NOT NULL DEFAULT 'active',
  `suspended_until` timestamp NULL DEFAULT NULL,
  `suspension_reason` varchar(500) DEFAULT NULL,
  `force_password_reset` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `profile_id`, `is_active`, `account_status`, `suspended_until`, `suspension_reason`, `force_password_reset`, `email_verified`, `email_verified_at`, `last_login`, `last_login_ip`, `created_at`, `updated_at`) VALUES
(45, 'testteacher@eduquest.test', '$2y$12$BRebVnk.ZngIGmhiehWRnu85G9QkF59auSYtJAjdexXdrpF1p1qam', 'Test', 'Teacher', 'teacher', 24, 1, 'active', NULL, NULL, 0, 1, '2026-05-13 17:58:23', '2026-05-18 13:50:39', '::1', '2026-05-04 14:24:56', '2026-05-18 13:50:39'),
(46, 'teststudent@eduquest.test', '$2y$12$lXxWXYv9VJdAL8ppyNFF6.cZ9wVBJ0Y.7cKkhYFBtoNKUnySse0nO', 'Test', 'Student', 'student', 37, 1, 'active', NULL, NULL, 0, 1, '2026-05-13 17:58:24', '2026-05-18 10:14:49', '::1', '2026-05-04 14:24:56', '2026-05-18 10:14:49'),
(49, 'mayriellejoy@gmail.com', '$2y$12$mGnC/YE1bk00ltQicBCrPen4DeoSQfMxzAB3tWP6XAdZD1cEucNgy', 'Mayrielle Joy', 'Latigo', 'student', NULL, 1, 'active', NULL, NULL, 0, 1, '2026-05-07 12:13:05', '2026-05-11 08:04:23', '::1', '2026-05-07 12:12:32', '2026-05-11 08:04:23'),
(50, 'mayriellej@gmail.com', '$2y$12$OFiX6dldoJqIVokd/a97Y.k3Z.PuDzQvx8ALjEM1XDKetZA.kuKTK', 'Mayrielle Joy', 'Latigo', 'teacher', NULL, 1, 'active', NULL, NULL, 0, 1, '2026-05-11 06:42:06', '2026-05-11 07:51:50', '::1', '2026-05-11 06:41:50', '2026-05-11 07:51:50'),
(51, 'testteacher2@eduquest.test', '$2y$12$ZsK5nEBWDK7aEW54mPJEmeD3ZXvZR2FSSuMBQhlGvpOe4PPKiRfuC', 'Demo', 'Teacher', 'teacher', 26, 1, 'active', NULL, NULL, 0, 1, '2026-05-13 17:58:24', NULL, NULL, '2026-05-13 17:58:24', '2026-05-13 17:58:24'),
(52, 'teststudent2@eduquest.test', '$2y$12$bwLFJHi/irJuur/2ztjezeqHfRioqEzxL9CEdCC8L/nKr16PRykpa', 'Demo', 'Student', 'student', 41, 1, 'active', NULL, NULL, 1, 1, '2026-05-13 17:58:24', '2026-05-13 18:04:11', '::1', '2026-05-13 17:58:24', '2026-05-15 08:30:02'),
(53, 'zachdomingojavellana@gmail.com', '$2y$12$muXLliXtOZB4iz/wxxoNl.655zF6fliqGXQDyj6swxIaoyxCKoj9m', 'Francis Zachary', 'Domingo', 'student', NULL, 0, 'active', NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-05-18 07:27:21', '2026-05-18 07:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `remember_token`, `expires_at`, `created_at`) VALUES
(170, 46, 'ade3581967d8997d63e95202685613e25598b2ffd36b5626274cca8e3ff07e90', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '6e497185642b10b99df3d01c60e0762294fd45c75b9d20450d2a54d474a376d7', '2026-06-05 14:24:35', '2026-05-06 14:24:35'),
(263, 45, 'bc628668770f26348fa40189aed2cf40c759955d459477c58f2551a44b4ba32c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-18 21:50:39', '2026-05-18 13:50:39');

-- --------------------------------------------------------

--
-- Table structure for table `virtual_rewards`
--

CREATE TABLE `virtual_rewards` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `reward_type` enum('cosmetic','privilege','certificate','custom') DEFAULT 'cosmetic',
  `icon` varchar(10) DEFAULT '?',
  `xp_cost` int(10) UNSIGNED DEFAULT 0,
  `milestone_xp` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `virtual_rewards`
--

INSERT INTO `virtual_rewards` (`id`, `teacher_id`, `title`, `description`, `reward_type`, `icon`, `xp_cost`, `milestone_xp`, `is_active`, `created_at`) VALUES
(1, NULL, 'Bronze Badge', 'A shiny bronze badge for your profile', 'cosmetic', '🥉', 0, 500, 1, '2026-04-12 07:51:46'),
(2, NULL, 'Silver Badge', 'A gleaming silver badge', 'cosmetic', '🥈', 0, 2000, 1, '2026-04-12 07:51:46'),
(3, NULL, 'Gold Badge', 'The prestigious gold badge', 'cosmetic', '🥇', 0, 5000, 1, '2026-04-12 07:51:46'),
(4, NULL, 'Star Student', 'A special star for outstanding effort', 'certificate', '⭐', 0, 10000, 1, '2026-04-12 07:51:46'),
(5, NULL, 'Custom Avatar', 'Unlock custom avatar options', 'cosmetic', '🎨', 1000, NULL, 1, '2026-04-12 07:51:46'),
(6, NULL, 'Extra Break Time', '5 minutes of extra break time', 'privilege', '⏰', 500, NULL, 1, '2026-04-12 07:51:46'),
(7, NULL, 'Bronze Badge', 'A shiny bronze badge for your profile', 'cosmetic', '🥉', 0, 500, 1, '2026-05-16 11:55:18'),
(8, NULL, 'Silver Badge', 'A gleaming silver badge', 'cosmetic', '🥈', 0, 2000, 1, '2026-05-16 11:55:18'),
(9, NULL, 'Gold Badge', 'The prestigious gold badge', 'cosmetic', '🥇', 0, 5000, 1, '2026-05-16 11:55:18'),
(10, NULL, 'Star Student', 'A special star for outstanding effort', 'certificate', '⭐', 0, 10000, 1, '2026-05-16 11:55:18'),
(11, NULL, 'Custom Avatar', 'Unlock custom avatar options', 'cosmetic', '🎨', 1000, NULL, 1, '2026-05-16 11:55:18'),
(12, NULL, 'Extra Break Time', '5 minutes of extra break time', 'privilege', '⏰', 500, NULL, 1, '2026-05-16 11:55:18'),
(13, NULL, 'Bronze Badge', 'A shiny bronze badge for your profile', 'cosmetic', '🥉', 0, 500, 1, '2026-05-16 11:55:33'),
(14, NULL, 'Silver Badge', 'A gleaming silver badge', 'cosmetic', '🥈', 0, 2000, 1, '2026-05-16 11:55:33'),
(15, NULL, 'Gold Badge', 'The prestigious gold badge', 'cosmetic', '🥇', 0, 5000, 1, '2026-05-16 11:55:33'),
(16, NULL, 'Star Student', 'A special star for outstanding effort', 'certificate', '⭐', 0, 10000, 1, '2026-05-16 11:55:33'),
(17, NULL, 'Custom Avatar', 'Unlock custom avatar options', 'cosmetic', '🎨', 1000, NULL, 1, '2026-05-16 11:55:33'),
(18, NULL, 'Extra Break Time', '5 minutes of extra break time', 'privilege', '⏰', 500, NULL, 1, '2026-05-16 11:55:33'),
(19, NULL, 'Bronze Badge', 'A shiny bronze badge for your profile', 'cosmetic', '🥉', 0, 500, 1, '2026-05-16 11:56:14'),
(20, NULL, 'Silver Badge', 'A gleaming silver badge', 'cosmetic', '🥈', 0, 2000, 1, '2026-05-16 11:56:14'),
(21, NULL, 'Gold Badge', 'The prestigious gold badge', 'cosmetic', '🥇', 0, 5000, 1, '2026-05-16 11:56:14'),
(22, NULL, 'Star Student', 'A special star for outstanding effort', 'certificate', '⭐', 0, 10000, 1, '2026-05-16 11:56:14'),
(23, NULL, 'Custom Avatar', 'Unlock custom avatar options', 'cosmetic', '🎨', 1000, NULL, 1, '2026-05-16 11:56:14'),
(24, NULL, 'Extra Break Time', '5 minutes of extra break time', 'privilege', '⏰', 500, NULL, 1, '2026-05-16 11:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `xp_transactions`
--

CREATE TABLE `xp_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `xp_amount` int(11) NOT NULL,
  `source_type` enum('quest','quiz','activity','achievement','daily_challenge','streak_bonus','teacher_award','correction') NOT NULL,
  `source_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `xp_transactions`
--

INSERT INTO `xp_transactions` (`id`, `student_id`, `xp_amount`, `source_type`, `source_id`, `description`, `course_id`, `teacher_id`, `created_at`) VALUES
(22, 37, 43, 'activity', NULL, 'Completed activity: Arrange Numbers ↑ (math) (100%)', NULL, NULL, '2026-05-05 15:29:21'),
(23, 37, 100, 'achievement', 10, 'Achievement: Perfect Score', NULL, NULL, '2026-05-05 15:29:21'),
(24, 37, 43, 'activity', NULL, 'Completed activity: Arrange Numbers ↑ (math) (100%)', NULL, NULL, '2026-05-05 15:31:27'),
(36, 37, 43, 'activity', NULL, 'Completed activity: Build CVC Words (/Ii/) (english) (100%)', NULL, NULL, '2026-05-13 18:13:52'),
(37, 37, 75, 'quiz', 5, 'Quiz: Clash of Clans', NULL, 24, '2026-05-13 18:14:07'),
(38, 37, 43, 'activity', NULL, 'Completed activity: Arrange Numbers ↑ (math) (100%)', NULL, NULL, '2026-05-15 08:17:35'),
(39, 37, 75, 'achievement', 16, 'Achievement: Egg Hatcher', NULL, NULL, '2026-05-15 08:17:35'),
(40, 37, 43, 'activity', NULL, 'Completed activity: Build CVC Words (/Ii/) (english) (100%)', NULL, NULL, '2026-05-15 08:18:45'),
(41, 37, 75, 'quiz', 7, 'Quiz: Clash of Clans', NULL, 24, '2026-05-15 12:45:51'),
(42, 37, 75, 'quiz', 8, 'Quiz: Clash of Clans', NULL, 24, '2026-05-15 12:54:12'),
(43, 37, 75, 'quiz', 9, 'Quiz: Test', NULL, 24, '2026-05-15 13:22:38'),
(44, 37, 43, 'activity', NULL, 'Completed activity: Arrange Numbers ↑ (math) (100%)', NULL, NULL, '2026-05-15 15:05:29'),
(45, 37, 43, 'activity', NULL, 'Completed activity: Hello (math) (100%)', NULL, NULL, '2026-05-16 15:01:39'),
(46, 37, 75, 'achievement', 23, 'Achievement: XP Hunter', NULL, NULL, '2026-05-16 15:01:39'),
(47, 37, 100, 'achievement', 30, 'Achievement: Perfect Score', NULL, NULL, '2026-05-16 15:01:39'),
(48, 37, 25, 'achievement', 32, 'Achievement: Team Player', NULL, NULL, '2026-05-16 15:01:39'),
(49, 37, 75, 'achievement', 37, 'Achievement: Egg Hatcher', NULL, NULL, '2026-05-16 15:01:39'),
(50, 37, 75, 'achievement', 45, 'Achievement: XP Hunter', NULL, NULL, '2026-05-16 15:01:39'),
(51, 37, 100, 'achievement', 52, 'Achievement: Perfect Score', NULL, NULL, '2026-05-16 15:01:39'),
(52, 37, 25, 'achievement', 54, 'Achievement: Team Player', NULL, NULL, '2026-05-16 15:01:39'),
(53, 37, 75, 'achievement', 59, 'Achievement: Egg Hatcher', NULL, NULL, '2026-05-16 15:01:39'),
(54, 37, 75, 'achievement', 67, 'Achievement: XP Hunter', NULL, NULL, '2026-05-16 15:01:39'),
(55, 37, 100, 'achievement', 74, 'Achievement: Perfect Score', NULL, NULL, '2026-05-16 15:01:39'),
(56, 37, 25, 'achievement', 76, 'Achievement: Team Player', NULL, NULL, '2026-05-16 15:01:39'),
(57, 37, 75, 'achievement', 81, 'Achievement: Egg Hatcher', NULL, NULL, '2026-05-16 15:01:39'),
(58, 37, 43, 'activity', NULL, 'Completed activity: Arrange Numbers ↑ (math) (100%)', NULL, NULL, '2026-05-16 15:02:39'),
(59, 37, 100, 'achievement', 4, 'Achievement: XP Hunter', NULL, NULL, '2026-05-16 15:02:39'),
(60, 37, 100, 'achievement', 15, 'Achievement: Level Up!', NULL, NULL, '2026-05-16 15:02:39'),
(61, 37, 100, 'achievement', 35, 'Achievement: Level Up!', NULL, NULL, '2026-05-16 15:02:39'),
(62, 37, 100, 'achievement', 57, 'Achievement: Level Up!', NULL, NULL, '2026-05-16 15:02:39'),
(63, 37, 100, 'achievement', 79, 'Achievement: Level Up!', NULL, NULL, '2026-05-16 15:02:39'),
(64, 37, 28, 'activity', NULL, 'Completed activity: Math True or False? (math) (88%)', NULL, NULL, '2026-05-16 15:15:59'),
(65, 37, 200, 'achievement', 24, 'Achievement: XP Champion', NULL, NULL, '2026-05-16 15:15:59'),
(66, 37, 150, 'achievement', 38, 'Achievement: Proud Parent', NULL, NULL, '2026-05-16 15:15:59'),
(67, 37, 200, 'achievement', 46, 'Achievement: XP Champion', NULL, NULL, '2026-05-16 15:15:59'),
(68, 37, 150, 'achievement', 60, 'Achievement: Proud Parent', NULL, NULL, '2026-05-16 15:15:59'),
(69, 37, 200, 'achievement', 68, 'Achievement: XP Champion', NULL, NULL, '2026-05-16 15:15:59'),
(70, 37, 150, 'achievement', 82, 'Achievement: Proud Parent', NULL, NULL, '2026-05-16 15:15:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accommodations`
--
ALTER TABLE `accommodations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_accommodations_student` (`student_id`);

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ach_teacher` (`teacher_id`),
  ADD KEY `fk_ach_course` (`course_id`),
  ADD KEY `idx_ach_category` (`category`);

--
-- Indexes for table `adhd_profiles`
--
ALTER TABLE `adhd_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aaudit_admin` (`admin_id`),
  ADD KEY `idx_aaudit_target` (`target_user_id`),
  ADD KEY `idx_aaudit_action` (`action`),
  ADD KEY `idx_aaudit_created` (`created_at`);

--
-- Indexes for table `assessment_sessions`
--
ALTER TABLE `assessment_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_as_teacher` (`initiated_by`),
  ADD KEY `idx_as_student` (`student_id`),
  ADD KEY `idx_as_type` (`session_type`),
  ADD KEY `idx_as_status` (`status`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_material` (`student_id`,`material_id`),
  ADD KEY `idx_sub_material` (`material_id`),
  ADD KEY `idx_sub_student` (`student_id`),
  ADD KEY `idx_sub_status` (`status`);

--
-- Indexes for table `behavioral_logs`
--
ALTER TABLE `behavioral_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bl_teacher` (`teacher_id`),
  ADD KEY `idx_bl_student` (`student_id`),
  ADD KEY `idx_bl_type` (`log_type`),
  ADD KEY `idx_bl_key` (`indicator_key`),
  ADD KEY `idx_bl_date` (`session_date`),
  ADD KEY `idx_bl_logged_by` (`logged_by`);

--
-- Indexes for table `click_events`
--
ALTER TABLE `click_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_click_daily` (`student_id`,`page_name`,`element_label`,`session_date`),
  ADD KEY `idx_ce_student` (`student_id`),
  ADD KEY `idx_ce_date` (`session_date`);

--
-- Indexes for table `comorbid_conditions`
--
ALTER TABLE `comorbid_conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comorbid_student` (`student_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_courses_teacher` (`teacher_id`);

--
-- Indexes for table `course_announcements`
--
ALTER TABLE `course_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ann_teacher` (`teacher_id`),
  ADD KEY `idx_announcements_course` (`course_id`);

--
-- Indexes for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enrollment` (`course_id`,`student_id`),
  ADD KEY `idx_enrollments_course` (`course_id`),
  ADD KEY `idx_enrollments_student` (`student_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_materials_module` (`module_id`),
  ADD KEY `idx_materials_course` (`course_id`);

--
-- Indexes for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_modules_course` (`course_id`);

--
-- Indexes for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_verify_user` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_game_type` (`game_type`);

--
-- Indexes for table `game_assignments`
--
ALTER TABLE `game_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_game_student` (`game_id`,`student_id`),
  ADD KEY `idx_ga_student` (`student_id`),
  ADD KEY `idx_ga_teacher` (`teacher_id`);

--
-- Indexes for table `game_attempts`
--
ALTER TABLE `game_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gat_student` (`student_id`),
  ADD KEY `idx_gat_assignment` (`assignment_id`),
  ADD KEY `fk_gat_game` (`game_id`);

--
-- Indexes for table `gamification_settings`
--
ALTER TABLE `gamification_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teacher_course` (`teacher_id`,`course_id`),
  ADD KEY `fk_gs_course` (`course_id`);

--
-- Indexes for table `hover_events`
--
ALTER TABLE `hover_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_hover_daily` (`student_id`,`page_name`,`element_label`,`session_date`),
  ADD KEY `idx_he_student` (`student_id`),
  ADD KEY `idx_he_date` (`session_date`);

--
-- Indexes for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_import_teacher` (`teacher_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lessons_subject` (`subject_id`),
  ADD KEY `idx_lessons_order` (`subject_id`,`lesson_order`);

--
-- Indexes for table `lesson_content`
--
ALTER TABLE `lesson_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content_lesson` (`lesson_id`),
  ADD KEY `idx_content_order` (`lesson_id`,`page_order`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_ip` (`email`,`ip_address`),
  ADD KEY `idx_attempted` (`attempted_at`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medications_student` (`student_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient` (`recipient_id`,`recipient_role`),
  ADD KEY `idx_recipient_unread` (`recipient_id`,`recipient_role`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `page_sessions`
--
ALTER TABLE `page_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ps_student` (`student_id`),
  ADD KEY `idx_ps_page` (`page_name`),
  ADD KEY `idx_ps_start` (`session_start`);

--
-- Indexes for table `password_change_otps`
--
ALTER TABLE `password_change_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_reset_user` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `question_interactions`
--
ALTER TABLE `question_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qi_student` (`student_id`),
  ADD KEY `idx_qi_quiz` (`quiz_id`),
  ADD KEY `idx_qi_question` (`question_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quizzes_lesson` (`lesson_id`);

--
-- Indexes for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_answers_question` (`question_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_quiz` (`quiz_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_students_user` (`user_id`),
  ADD KEY `idx_students_teacher` (`teacher_id`);

--
-- Indexes for table `student_achievements`
--
ALTER TABLE `student_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_achievement` (`student_id`,`achievement_id`),
  ADD KEY `fk_sa_achievement` (`achievement_id`),
  ADD KEY `idx_sa_unlocked` (`is_unlocked`);

--
-- Indexes for table `student_activity_log`
--
ALTER TABLE `student_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sal_student` (`student_id`),
  ADD KEY `idx_sal_type` (`activity_type`),
  ADD KEY `idx_sal_course` (`course_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_doc_uploader` (`uploaded_by`),
  ADD KEY `idx_docs_student` (`student_id`);

--
-- Indexes for table `student_gamification`
--
ALTER TABLE `student_gamification`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_sg_student` (`student_id`),
  ADD KEY `idx_sg_team` (`team`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_grades_student` (`student_id`),
  ADD KEY `idx_student_grades_teacher` (`teacher_id`),
  ADD KEY `idx_student_grades_course` (`course_id`),
  ADD KEY `idx_student_grades_date` (`graded_at`);

--
-- Indexes for table `student_iep`
--
ALTER TABLE `student_iep`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `fk_iep_doc` (`document_id`);

--
-- Indexes for table `student_individual_profile`
--
ALTER TABLE `student_individual_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `fk_sip_doc` (`document_id`);

--
-- Indexes for table `student_itp`
--
ALTER TABLE `student_itp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `fk_itp_doc` (`document_id`);

--
-- Indexes for table `student_lesson_progress`
--
ALTER TABLE `student_lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_lesson` (`student_id`,`lesson_id`),
  ADD KEY `idx_slp_student` (`student_id`),
  ADD KEY `idx_slp_lesson` (`lesson_id`);

--
-- Indexes for table `student_quiz_attempts`
--
ALTER TABLE `student_quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sqa_student` (`student_id`),
  ADD KEY `idx_sqa_quiz` (`quiz_id`);

--
-- Indexes for table `student_rewards`
--
ALTER TABLE `student_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_reward` (`student_id`,`reward_id`),
  ADD KEY `fk_sr_reward` (`reward_id`);

--
-- Indexes for table `student_settings_overrides`
--
ALTER TABLE `student_settings_overrides`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_setting` (`student_id`,`setting_key`),
  ADD KEY `idx_teacher` (`teacher_id`);

--
-- Indexes for table `student_subject_progress`
--
ALTER TABLE `student_subject_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_subject` (`student_id`,`subject_id`),
  ADD KEY `fk_sp_subject` (`subject_id`),
  ADD KEY `idx_sp_student` (`student_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_sa_email` (`email`);

--
-- Indexes for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sr_respondent` (`respondent_id`),
  ADD KEY `idx_sr_type` (`survey_type`),
  ADD KEY `idx_sr_period` (`academic_period`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `fk_ss_super_admin` (`updated_by`),
  ADD KEY `idx_ss_key` (`setting_key`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_teachers_user` (`user_id`),
  ADD KEY `idx_teachers_email` (`email`);

--
-- Indexes for table `teacher_activities`
--
ALTER TABLE `teacher_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_category` (`teacher_id`,`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `teacher_activity_assignments`
--
ALTER TABLE `teacher_activity_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_activity_teacher` (`activity_id`,`teacher_id`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `teacher_activity_attempts`
--
ALTER TABLE `teacher_activity_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `idx_student_activity` (`student_id`,`activity_id`),
  ADD KEY `idx_completed` (`completed_at`);

--
-- Indexes for table `teacher_activity_items`
--
ALTER TABLE `teacher_activity_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_order` (`activity_id`,`item_order`);

--
-- Indexes for table `teacher_assigned_games`
--
ALTER TABLE `teacher_assigned_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teacher_student_game` (`teacher_id`,`student_id`,`game_id`),
  ADD KEY `idx_tag_student` (`student_id`);

--
-- Indexes for table `teacher_default_game_settings`
--
ALTER TABLE `teacher_default_game_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teacher_game` (`teacher_id`,`game_id`),
  ADD KEY `idx_teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_notes`
--
ALTER TABLE `teacher_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_note_teacher` (`teacher_id`),
  ADD KEY `idx_notes_student` (`student_id`);

--
-- Indexes for table `teacher_quizzes`
--
ALTER TABLE `teacher_quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `teacher_quiz_answers`
--
ALTER TABLE `teacher_quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `teacher_quiz_assignments`
--
ALTER TABLE `teacher_quiz_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_quiz_course` (`quiz_id`,`course_id`);

--
-- Indexes for table `teacher_quiz_attempts`
--
ALTER TABLE `teacher_quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `idx_student_quiz` (`student_id`,`quiz_id`),
  ADD KEY `idx_tqa_assignment` (`assignment_id`);

--
-- Indexes for table `teacher_quiz_questions`
--
ALTER TABLE `teacher_quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_order` (`quiz_id`,`question_order`);

--
-- Indexes for table `teacher_whitelist`
--
ALTER TABLE `teacher_whitelist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_wl_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`),
  ADD KEY `idx_users_account_status` (`account_status`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_user_expires` (`user_id`,`expires_at`),
  ADD KEY `idx_sessions_user` (`user_id`);

--
-- Indexes for table `virtual_rewards`
--
ALTER TABLE `virtual_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vr_teacher` (`teacher_id`);

--
-- Indexes for table `xp_transactions`
--
ALTER TABLE `xp_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_xpt_course` (`course_id`),
  ADD KEY `fk_xpt_teacher` (`teacher_id`),
  ADD KEY `idx_xpt_student` (`student_id`),
  ADD KEY `idx_xpt_created` (`created_at`),
  ADD KEY `idx_xpt_source` (`source_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accommodations`
--
ALTER TABLE `accommodations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `adhd_profiles`
--
ALTER TABLE `adhd_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `assessment_sessions`
--
ALTER TABLE `assessment_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `behavioral_logs`
--
ALTER TABLE `behavioral_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `click_events`
--
ALTER TABLE `click_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `comorbid_conditions`
--
ALTER TABLE `comorbid_conditions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `course_announcements`
--
ALTER TABLE `course_announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `course_modules`
--
ALTER TABLE `course_modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `game_assignments`
--
ALTER TABLE `game_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_attempts`
--
ALTER TABLE `game_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `gamification_settings`
--
ALTER TABLE `gamification_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hover_events`
--
ALTER TABLE `hover_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `lesson_content`
--
ALTER TABLE `lesson_content`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=344;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `page_sessions`
--
ALTER TABLE `page_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_change_otps`
--
ALTER TABLE `password_change_otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `question_interactions`
--
ALTER TABLE `question_interactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=185;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `student_achievements`
--
ALTER TABLE `student_achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `student_activity_log`
--
ALTER TABLE `student_activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `student_gamification`
--
ALTER TABLE `student_gamification`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `student_iep`
--
ALTER TABLE `student_iep`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_individual_profile`
--
ALTER TABLE `student_individual_profile`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_itp`
--
ALTER TABLE `student_itp`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_lesson_progress`
--
ALTER TABLE `student_lesson_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_quiz_attempts`
--
ALTER TABLE `student_quiz_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_rewards`
--
ALTER TABLE `student_rewards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `student_settings_overrides`
--
ALTER TABLE `student_settings_overrides`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `student_subject_progress`
--
ALTER TABLE `student_subject_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `teacher_activities`
--
ALTER TABLE `teacher_activities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_activity_assignments`
--
ALTER TABLE `teacher_activity_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_activity_attempts`
--
ALTER TABLE `teacher_activity_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_activity_items`
--
ALTER TABLE `teacher_activity_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher_assigned_games`
--
ALTER TABLE `teacher_assigned_games`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `teacher_default_game_settings`
--
ALTER TABLE `teacher_default_game_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `teacher_notes`
--
ALTER TABLE `teacher_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_quizzes`
--
ALTER TABLE `teacher_quizzes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teacher_quiz_answers`
--
ALTER TABLE `teacher_quiz_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `teacher_quiz_assignments`
--
ALTER TABLE `teacher_quiz_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_quiz_attempts`
--
ALTER TABLE `teacher_quiz_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teacher_quiz_questions`
--
ALTER TABLE `teacher_quiz_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `teacher_whitelist`
--
ALTER TABLE `teacher_whitelist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT for table `virtual_rewards`
--
ALTER TABLE `virtual_rewards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `xp_transactions`
--
ALTER TABLE `xp_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accommodations`
--
ALTER TABLE `accommodations`
  ADD CONSTRAINT `fk_accommodation_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `fk_ach_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ach_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `adhd_profiles`
--
ALTER TABLE `adhd_profiles`
  ADD CONSTRAINT `fk_adhd_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_sessions`
--
ALTER TABLE `assessment_sessions`
  ADD CONSTRAINT `fk_as_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_as_teacher` FOREIGN KEY (`initiated_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `fk_sub_material` FOREIGN KEY (`material_id`) REFERENCES `course_materials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `behavioral_logs`
--
ALTER TABLE `behavioral_logs`
  ADD CONSTRAINT `fk_bl_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bl_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comorbid_conditions`
--
ALTER TABLE `comorbid_conditions`
  ADD CONSTRAINT `fk_comorbid_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_course_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_announcements`
--
ALTER TABLE `course_announcements`
  ADD CONSTRAINT `fk_ann_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ann_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD CONSTRAINT `fk_enrollment_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `fk_material_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_material_module` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD CONSTRAINT `fk_module_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD CONSTRAINT `fk_verify_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_assignments`
--
ALTER TABLE `game_assignments`
  ADD CONSTRAINT `fk_ga_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ga_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ga_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_attempts`
--
ALTER TABLE `game_attempts`
  ADD CONSTRAINT `fk_gat_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `game_assignments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_gat_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gat_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gamification_settings`
--
ALTER TABLE `gamification_settings`
  ADD CONSTRAINT `fk_gs_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gs_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD CONSTRAINT `fk_import_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `fk_lesson_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_content`
--
ALTER TABLE `lesson_content`
  ADD CONSTRAINT `fk_content_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medications`
--
ALTER TABLE `medications`
  ADD CONSTRAINT `fk_medication_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quiz_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `fk_question_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_achievements`
--
ALTER TABLE `student_achievements`
  ADD CONSTRAINT `fk_sa_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_activity_log`
--
ALTER TABLE `student_activity_log`
  ADD CONSTRAINT `fk_sal_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `fk_doc_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doc_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_gamification`
--
ALTER TABLE `student_gamification`
  ADD CONSTRAINT `fk_sg_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD CONSTRAINT `student_grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_grades_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_grades_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_iep`
--
ALTER TABLE `student_iep`
  ADD CONSTRAINT `fk_iep_doc` FOREIGN KEY (`document_id`) REFERENCES `student_documents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_iep_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_individual_profile`
--
ALTER TABLE `student_individual_profile`
  ADD CONSTRAINT `fk_sip_doc` FOREIGN KEY (`document_id`) REFERENCES `student_documents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sip_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_itp`
--
ALTER TABLE `student_itp`
  ADD CONSTRAINT `fk_itp_doc` FOREIGN KEY (`document_id`) REFERENCES `student_documents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_itp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_lesson_progress`
--
ALTER TABLE `student_lesson_progress`
  ADD CONSTRAINT `fk_slp_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_slp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_quiz_attempts`
--
ALTER TABLE `student_quiz_attempts`
  ADD CONSTRAINT `fk_sqa_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sqa_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_rewards`
--
ALTER TABLE `student_rewards`
  ADD CONSTRAINT `fk_sr_reward` FOREIGN KEY (`reward_id`) REFERENCES `virtual_rewards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_subject_progress`
--
ALTER TABLE `student_subject_progress`
  ADD CONSTRAINT `fk_sp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sp_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_ss_super_admin` FOREIGN KEY (`updated_by`) REFERENCES `super_admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teacher_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_activities`
--
ALTER TABLE `teacher_activities`
  ADD CONSTRAINT `teacher_activities_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_activity_assignments`
--
ALTER TABLE `teacher_activity_assignments`
  ADD CONSTRAINT `teacher_activity_assignments_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `teacher_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_activity_assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_activity_assignments_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_activity_assignments_ibfk_4` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_activity_attempts`
--
ALTER TABLE `teacher_activity_attempts`
  ADD CONSTRAINT `teacher_activity_attempts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_activity_attempts_ibfk_2` FOREIGN KEY (`activity_id`) REFERENCES `teacher_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_activity_items`
--
ALTER TABLE `teacher_activity_items`
  ADD CONSTRAINT `teacher_activity_items_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `teacher_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_assigned_games`
--
ALTER TABLE `teacher_assigned_games`
  ADD CONSTRAINT `fk_tag_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tag_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_notes`
--
ALTER TABLE `teacher_notes`
  ADD CONSTRAINT `fk_note_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_note_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_quizzes`
--
ALTER TABLE `teacher_quizzes`
  ADD CONSTRAINT `teacher_quizzes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_quizzes_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_quiz_answers`
--
ALTER TABLE `teacher_quiz_answers`
  ADD CONSTRAINT `teacher_quiz_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `teacher_quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_quiz_assignments`
--
ALTER TABLE `teacher_quiz_assignments`
  ADD CONSTRAINT `teacher_quiz_assignments_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `teacher_quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_quiz_assignments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_quiz_assignments_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_quiz_attempts`
--
ALTER TABLE `teacher_quiz_attempts`
  ADD CONSTRAINT `teacher_quiz_attempts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `teacher_quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_quiz_questions`
--
ALTER TABLE `teacher_quiz_questions`
  ADD CONSTRAINT `teacher_quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `teacher_quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `virtual_rewards`
--
ALTER TABLE `virtual_rewards`
  ADD CONSTRAINT `fk_vr_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `xp_transactions`
--
ALTER TABLE `xp_transactions`
  ADD CONSTRAINT `fk_xpt_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_xpt_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_xpt_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
