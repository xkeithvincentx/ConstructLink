-- Migration: Update maintenance table for MVA workflow
-- Date: 2025-01-XX
-- Description: Update maintenance table to support Maker-Verifier-Authorizer workflow

-- First, add temporary status column
ALTER TABLE `maintenance` 
ADD COLUMN `status_temp` enum('Pending Verification','Pending Approval','Approved','in_progress','completed','canceled') NOT NULL DEFAULT 'Pending Verification' AFTER `status`;

-- Add new fields for MVA workflow
ALTER TABLE `maintenance` 
ADD COLUMN `created_by` int(11) DEFAULT NULL AFTER `next_maintenance_date`,
ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `created_by`,
ADD COLUMN `verification_date` timestamp NULL DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `approved_by` int(11) DEFAULT NULL AFTER `verification_date`,
ADD COLUMN `approval_date` timestamp NULL DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `notes` text DEFAULT NULL AFTER `approval_date`;

-- Add foreign key constraints for new fields
ALTER TABLE `maintenance` 
ADD CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `maintenance_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `maintenance_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for new fields
ALTER TABLE `maintenance` 
ADD KEY `created_by` (`created_by`),
ADD KEY `verified_by` (`verified_by`),
ADD KEY `approved_by` (`approved_by`),
ADD KEY `verification_date` (`verification_date`),
ADD KEY `approval_date` (`approval_date`);

-- Copy existing status values to temporary column with mapping
UPDATE `maintenance` SET `status_temp` = 
  CASE 
    WHEN `status` = 'scheduled' THEN 'Approved'
    WHEN `status` = 'in_progress' THEN 'in_progress'
    WHEN `status` = 'completed' THEN 'completed'
    WHEN `status` = 'canceled' THEN 'canceled'
    ELSE 'Pending Verification'
  END;

-- Drop the old status column
ALTER TABLE `maintenance` DROP COLUMN `status`;

-- Rename temporary column to status
ALTER TABLE `maintenance` CHANGE COLUMN `status_temp` `status` enum('Pending Verification','Pending Approval','Approved','in_progress','completed','canceled') NOT NULL DEFAULT 'Pending Verification';