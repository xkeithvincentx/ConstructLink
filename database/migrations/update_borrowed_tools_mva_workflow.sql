-- Migration: Update borrowed_tools table for MVA workflow
-- Date: 2024-01-XX
-- Description: Update borrowed_tools table to support Maker-Verifier-Authorizer workflow

-- Update status enum to support MVA workflow
ALTER TABLE `borrowed_tools` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Borrowed','Returned','Overdue','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Add new fields for MVA workflow
ALTER TABLE `borrowed_tools` 
ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `issued_by`,
ADD COLUMN `verification_date` timestamp NULL DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `approved_by` int(11) DEFAULT NULL AFTER `verification_date`,
ADD COLUMN `approval_date` timestamp NULL DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `borrowed_by` int(11) DEFAULT NULL AFTER `approval_date`,
ADD COLUMN `borrowed_date` timestamp NULL DEFAULT NULL AFTER `borrowed_by`,
ADD COLUMN `returned_by` int(11) DEFAULT NULL AFTER `borrowed_date`,
ADD COLUMN `return_date` timestamp NULL DEFAULT NULL AFTER `returned_by`;

-- Add foreign key constraints for new fields
ALTER TABLE `borrowed_tools` 
ADD CONSTRAINT `borrowed_tools_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `borrowed_tools_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `borrowed_tools_ibfk_5` FOREIGN KEY (`borrowed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `borrowed_tools_ibfk_6` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for new fields
ALTER TABLE `borrowed_tools` 
ADD KEY `verified_by` (`verified_by`),
ADD KEY `approved_by` (`approved_by`),
ADD KEY `borrowed_by` (`borrowed_by`),
ADD KEY `returned_by` (`returned_by`),
ADD KEY `verification_date` (`verification_date`),
ADD KEY `approval_date` (`approval_date`),
ADD KEY `borrowed_date` (`borrowed_date`),
ADD KEY `return_date` (`return_date`);

-- Update existing borrowed_tools to use new status values
UPDATE `borrowed_tools` SET `status` = 'Borrowed' WHERE `status` = 'borrowed';
UPDATE `borrowed_tools` SET `status` = 'Returned' WHERE `status` = 'returned';
UPDATE `borrowed_tools` SET `status` = 'Overdue' WHERE `status` = 'overdue';

-- Update the status enum to remove old values
ALTER TABLE `borrowed_tools` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Borrowed','Returned','Overdue','Canceled') NOT NULL DEFAULT 'Pending Verification'; 