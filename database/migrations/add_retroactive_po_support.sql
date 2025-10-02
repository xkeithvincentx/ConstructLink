-- Migration: Add Retroactive PO Support
-- Date: 2025-01-07
-- Description: Add minimal fields to support post-purchase PO documentation

-- Add retroactive flag to procurement_orders table
ALTER TABLE `procurement_orders` 
ADD COLUMN `is_retroactive` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Flag for post-purchase documentation' AFTER `notes`;

-- Add simple reason field
ALTER TABLE `procurement_orders` 
ADD COLUMN `retroactive_reason` TEXT NULL 
COMMENT 'Reason for retroactive documentation' AFTER `is_retroactive`;

-- Add index for performance on retroactive queries
ALTER TABLE `procurement_orders` 
ADD KEY `is_retroactive` (`is_retroactive`);

-- Update fillable array comment in model (reminder for developer)
-- Remember to update ProcurementOrderModel fillable array to include:
-- 'is_retroactive', 'retroactive_reason'