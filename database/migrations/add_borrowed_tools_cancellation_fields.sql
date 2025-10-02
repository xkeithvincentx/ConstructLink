-- ConstructLink™ Database Migration
-- Migration: Add cancellation fields to borrowed_tools table
-- Date: 2025-01-19
-- Description: Add cancellation tracking fields to support cancellation functionality

-- Add cancellation fields to borrowed_tools table
ALTER TABLE `borrowed_tools` 
ADD COLUMN `canceled_by` int(11) DEFAULT NULL AFTER `return_date`,
ADD COLUMN `cancellation_date` timestamp NULL DEFAULT NULL AFTER `canceled_by`,
ADD COLUMN `cancellation_reason` text AFTER `cancellation_date`;

-- Add foreign key constraint for canceled_by
ALTER TABLE `borrowed_tools` 
ADD CONSTRAINT `borrowed_tools_ibfk_7` FOREIGN KEY (`canceled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add index for canceled_by for better query performance
CREATE INDEX `idx_borrowed_tools_canceled_by` ON `borrowed_tools` (`canceled_by`);

-- Commit the changes
COMMIT;