-- Migration: Add return workflow fields to transfers table
-- Date: 2024-01-15
-- Description: Add comprehensive return workflow tracking to support proper return transit process

-- Add return workflow status tracking fields
ALTER TABLE `transfers` 
ADD COLUMN `return_initiated_by` int(11) DEFAULT NULL AFTER `actual_return`,
ADD COLUMN `return_initiation_date` timestamp NULL DEFAULT NULL AFTER `return_initiated_by`,
ADD COLUMN `return_received_by` int(11) DEFAULT NULL AFTER `return_initiation_date`,
ADD COLUMN `return_receipt_date` timestamp NULL DEFAULT NULL AFTER `return_received_by`,
ADD COLUMN `return_status` enum('not_returned','in_return_transit','returned') NOT NULL DEFAULT 'not_returned' AFTER `return_receipt_date`,
ADD COLUMN `return_notes` text DEFAULT NULL AFTER `return_status`;

-- Add foreign key constraints for return workflow users
ALTER TABLE `transfers` 
ADD CONSTRAINT `transfers_return_initiated_fk` FOREIGN KEY (`return_initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `transfers_return_received_fk` FOREIGN KEY (`return_received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for better query performance on return operations
ALTER TABLE `transfers` 
ADD KEY `return_initiated_by` (`return_initiated_by`),
ADD KEY `return_received_by` (`return_received_by`),
ADD KEY `return_status` (`return_status`),
ADD KEY `return_initiation_date` (`return_initiation_date`),
ADD KEY `return_receipt_date` (`return_receipt_date`);

-- Add composite index for efficient overdue return queries
ALTER TABLE `transfers` 
ADD KEY `return_tracking` (`transfer_type`, `return_status`, `return_initiation_date`);

-- Update existing completed temporary transfers to have proper return status
UPDATE `transfers` 
SET `return_status` = 'returned' 
WHERE `transfer_type` = 'temporary' 
  AND `status` = 'Completed' 
  AND `actual_return` IS NOT NULL;

-- Set not_returned status for temporary transfers that haven't been returned
UPDATE `transfers` 
SET `return_status` = 'not_returned' 
WHERE `transfer_type` = 'temporary' 
  AND `status` = 'Completed' 
  AND `actual_return` IS NULL;

-- Show the updated table structure
DESCRIBE transfers;

-- Display summary of return status distribution
SELECT 
    transfer_type,
    return_status,
    COUNT(*) as count
FROM transfers 
GROUP BY transfer_type, return_status 
ORDER BY transfer_type, return_status;