-- Migration: Add 'In Transit' status to transfers workflow
-- Date: 2025-01-XX
-- Description: Add dispatch step between Approved and Received

-- Add 'In Transit' status to enum and dispatch-related fields
ALTER TABLE `transfers`
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','In Transit','Received','Completed','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Add dispatch tracking fields
ALTER TABLE `transfers`
ADD COLUMN `dispatched_by` int(11) DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `dispatch_date` timestamp NULL DEFAULT NULL AFTER `approval_date`,
ADD COLUMN `dispatch_notes` text DEFAULT NULL AFTER `dispatch_date`;

-- Add foreign key constraint for dispatched_by
ALTER TABLE `transfers`
ADD CONSTRAINT `transfers_ibfk_dispatch` FOREIGN KEY (`dispatched_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add index for dispatched_by
ALTER TABLE `transfers`
ADD KEY `dispatched_by` (`dispatched_by`),
ADD KEY `dispatch_date` (`dispatch_date`);
