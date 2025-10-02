-- Migration: Update transfers table for MVA workflow
-- Date: 2024-01-XX
-- Description: Update transfers table to support Maker-Verifier-Authorizer workflow

-- Update status enum to support MVA workflow
ALTER TABLE `transfers` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Received','Completed','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Add new fields for MVA workflow
ALTER TABLE `transfers` 
ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `verification_date` timestamp NULL DEFAULT NULL AFTER `approval_date`,
ADD COLUMN `received_by` int(11) DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `receipt_date` timestamp NULL DEFAULT NULL AFTER `verification_date`,
ADD COLUMN `completed_by` int(11) DEFAULT NULL AFTER `received_by`,
ADD COLUMN `completion_date` timestamp NULL DEFAULT NULL AFTER `receipt_date`;

-- Add foreign key constraints for new fields
ALTER TABLE `transfers` 
ADD CONSTRAINT `transfers_ibfk_6` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `transfers_ibfk_7` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `transfers_ibfk_8` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for new fields
ALTER TABLE `transfers` 
ADD KEY `verified_by` (`verified_by`),
ADD KEY `received_by` (`received_by`),
ADD KEY `completed_by` (`completed_by`),
ADD KEY `verification_date` (`verification_date`),
ADD KEY `receipt_date` (`receipt_date`),
ADD KEY `completion_date` (`completion_date`);

-- Update existing transfers to use new status values
UPDATE `transfers` SET `status` = 'Pending Verification' WHERE `status` = 'pending';
UPDATE `transfers` SET `status` = 'Approved' WHERE `status` = 'approved';
UPDATE `transfers` SET `status` = 'Completed' WHERE `status` = 'completed';
UPDATE `transfers` SET `status` = 'Canceled' WHERE `status` = 'canceled';

-- Update the status enum to remove old values
ALTER TABLE `transfers` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Received','Completed','Canceled') NOT NULL DEFAULT 'Pending Verification'; 