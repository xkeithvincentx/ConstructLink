-- Migration: Update withdrawals table for MVA workflow
-- Date: 2024-01-XX
-- Description: Update withdrawals table to support Maker-Verifier-Authorizer workflow

-- Update status enum to support MVA workflow
ALTER TABLE `withdrawals` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Released','Returned','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Add new fields for MVA workflow
ALTER TABLE `withdrawals` 
ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `withdrawn_by`,
ADD COLUMN `verification_date` timestamp NULL DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `approved_by` int(11) DEFAULT NULL AFTER `verification_date`,
ADD COLUMN `approval_date` timestamp NULL DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `released_by` int(11) DEFAULT NULL AFTER `approval_date`,
ADD COLUMN `release_date` timestamp NULL DEFAULT NULL AFTER `released_by`,
ADD COLUMN `returned_by` int(11) DEFAULT NULL AFTER `release_date`,
ADD COLUMN `return_date` timestamp NULL DEFAULT NULL AFTER `returned_by`;

-- Add foreign key constraints for new fields
ALTER TABLE `withdrawals` 
ADD CONSTRAINT `withdrawals_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `withdrawals_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `withdrawals_ibfk_6` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `withdrawals_ibfk_7` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for new fields
ALTER TABLE `withdrawals` 
ADD KEY `verified_by` (`verified_by`),
ADD KEY `approved_by` (`approved_by`),
ADD KEY `released_by` (`released_by`),
ADD KEY `returned_by` (`returned_by`),
ADD KEY `verification_date` (`verification_date`),
ADD KEY `approval_date` (`approval_date`),
ADD KEY `release_date` (`release_date`),
ADD KEY `return_date` (`return_date`);

-- Update existing withdrawals to use new status values
UPDATE `withdrawals` SET `status` = 'Pending Verification' WHERE `status` = 'pending';
UPDATE `withdrawals` SET `status` = 'Released' WHERE `status` = 'released';
UPDATE `withdrawals` SET `status` = 'Returned' WHERE `status` = 'returned';
UPDATE `withdrawals` SET `status` = 'Canceled' WHERE `status` = 'canceled';

-- Update the status enum to remove old values
ALTER TABLE `withdrawals` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Released','Returned','Canceled') NOT NULL DEFAULT 'Pending Verification'; 