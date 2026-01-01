-- Migration: Add 'competition' type to opportunities table
-- Date: 2026-01-01
-- Description: Extends the type ENUM to include 'competition' for hackathons, challenges, and awards

-- Add 'competition' to the type ENUM if it doesn't already exist
ALTER TABLE opportunities
MODIFY COLUMN type ENUM('scholarship', 'job', 'internship', 'grant', 'competition') NOT NULL;

-- Verify the change
SELECT COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'opportunities'
AND COLUMN_NAME = 'type';
