-- Migration: Add PHILGEPS source and Notice Reference Number field
-- Date: June 2026
-- Description: Adds PHILGEPS as a source option and a notice_reference_number field for PHILGEPS projects

-- Add the notice_reference_number field to projects table
ALTER TABLE `projects` 
ADD COLUMN `notice_reference_number` VARCHAR(5) DEFAULT NULL 
AFTER `source`;

-- Add index for the new field
ALTER TABLE `projects` 
ADD INDEX `idx_notice_reference_number` (`notice_reference_number`);

-- Add a comment to document the field constraints
ALTER TABLE `projects` 
MODIFY COLUMN `notice_reference_number` VARCHAR(5) DEFAULT NULL 
COMMENT 'PHILGEPS Notice Reference Number - 5 digits only, required when source is PHILGEPS';

-- Update existing PHILGEPS records if any (this is safe to run even if no records exist)
-- This is just for documentation purposes, actual PHILGEPS projects should be created with the new field