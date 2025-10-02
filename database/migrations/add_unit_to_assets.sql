-- ConstructLink™ Add Unit Field to Assets Table
-- Migration: Add unit of measurement support to assets table
-- Date: 2025-07-24

-- Add unit field to assets table (following the pattern from procurement_items and withdrawals)
ALTER TABLE `assets` 
ADD COLUMN `unit` varchar(50) DEFAULT 'pcs' COMMENT 'Unit of measurement' AFTER `available_quantity`;

-- Add index for performance
ALTER TABLE `assets` 
ADD INDEX `idx_unit` (`unit`);

-- Update existing assets with default unit
UPDATE `assets` SET `unit` = 'pcs' WHERE `unit` IS NULL;

-- Add comment to document this enhancement
ALTER TABLE `assets` COMMENT = 'Asset inventory with legacy entry workflow and unit of measurement support';

-- Success message
SELECT 'Unit field added to assets table successfully!' as message;