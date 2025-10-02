-- ConstructLink™ Add Item-Level Discrepancy Tracking
-- Migration: Add support for item-level discrepancy resolution in procurement orders
-- Date: 2025-07-24
-- Purpose: Enable granular discrepancy tracking and resolution for individual items within multi-item procurement orders

-- Add item-level discrepancy fields to procurement_items table
ALTER TABLE `procurement_items` 
ADD COLUMN `discrepancy_notes` TEXT NULL COMMENT 'Item-specific discrepancy details and resolution notes' AFTER `item_notes`,
ADD COLUMN `discrepancy_type` ENUM('Missing Items','Damaged Items','Wrong Items','Quantity Mismatch','Quality Issues','Other') NULL COMMENT 'Type of discrepancy for this specific item' AFTER `discrepancy_notes`,
ADD COLUMN `discrepancy_resolved_at` TIMESTAMP NULL COMMENT 'When this item discrepancy was resolved' AFTER `discrepancy_type`,
ADD COLUMN `discrepancy_resolved_by` INT(11) NULL COMMENT 'User who resolved this item discrepancy' AFTER `discrepancy_resolved_at`;

-- Add foreign key constraint for discrepancy_resolved_by
ALTER TABLE `procurement_items`
ADD CONSTRAINT `procurement_items_discrepancy_resolver_fk` 
FOREIGN KEY (`discrepancy_resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add procurement_item_id to delivery_tracking table for item-level tracking
ALTER TABLE `delivery_tracking`
ADD COLUMN `procurement_item_id` INT(11) NULL COMMENT 'Link to specific item for item-level discrepancy tracking' AFTER `procurement_order_id`;

-- Add foreign key constraint for procurement_item_id
ALTER TABLE `delivery_tracking`
ADD CONSTRAINT `delivery_tracking_item_fk` 
FOREIGN KEY (`procurement_item_id`) REFERENCES `procurement_items` (`id`) ON DELETE SET NULL;

-- Add indexes for performance
ALTER TABLE `procurement_items` 
ADD INDEX `idx_discrepancy_resolved` (`discrepancy_resolved_at`),
ADD INDEX `idx_discrepancy_type` (`discrepancy_type`),
ADD INDEX `idx_discrepancy_resolver` (`discrepancy_resolved_by`);

ALTER TABLE `delivery_tracking` 
ADD INDEX `idx_procurement_item` (`procurement_item_id`);

-- Update table comments to reflect new functionality
ALTER TABLE `procurement_items` 
COMMENT = 'Individual items within procurement orders with item-level discrepancy tracking';

ALTER TABLE `delivery_tracking` 
COMMENT = 'Delivery tracking with support for both order-level and item-level discrepancy management';

-- Success message
SELECT 'Item-level discrepancy tracking fields added successfully!' as message,
       'procurement_items table enhanced with discrepancy fields' as detail1,
       'delivery_tracking table enhanced with item-level support' as detail2;