-- ============================================================
-- Migration: Add source column to behavioral_logs table
-- Purpose: Track whether engagement logs are from quiz or activity
-- Run this if you have an existing behavioral_logs table
-- ============================================================

USE eduquest;

-- Add source column if it doesn't exist
ALTER TABLE behavioral_logs 
ADD COLUMN source ENUM('quiz','activity','other') DEFAULT 'other' NOT NULL 
AFTER indicator_value;

-- Create index on source column for filtering performance
ALTER TABLE behavioral_logs 
ADD INDEX idx_bl_source (source);

-- Log successful migration
SELECT 'Migration completed: Added source column to behavioral_logs' AS migration_status;
