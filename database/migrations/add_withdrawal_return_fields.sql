-- Migration: Add return tracking fields to withdrawals table
-- Date: 2025-11-06
-- Description: Add fields to track partial returns, condition, and notes

-- Add return tracking columns
ALTER TABLE `withdrawals`
ADD COLUMN `returned_quantity` int(11) DEFAULT NULL AFTER `return_date`,
ADD COLUMN `return_condition` enum('Good','Fair','Damaged','Consumed') DEFAULT NULL AFTER `returned_quantity`,
ADD COLUMN `return_item_notes` text DEFAULT NULL AFTER `return_condition`;

-- Add comments for clarity
ALTER TABLE `withdrawals`
MODIFY COLUMN `returned_quantity` int(11) DEFAULT NULL COMMENT 'Actual quantity returned (supports partial returns)',
MODIFY COLUMN `return_condition` enum('Good','Fair','Damaged','Consumed') DEFAULT NULL COMMENT 'Condition of items when returned',
MODIFY COLUMN `return_item_notes` text DEFAULT NULL COMMENT 'Notes about the returned items';
