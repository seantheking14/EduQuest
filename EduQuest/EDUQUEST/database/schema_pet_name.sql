-- ============================================================
-- Migration: Add pet_name column to student_gamification
-- Run once against the eduquest database.
-- ============================================================

ALTER TABLE student_gamification
    ADD COLUMN pet_name VARCHAR(32) NULL DEFAULT NULL AFTER egg_type
        COMMENT 'Student-chosen display name for their pet companion';
