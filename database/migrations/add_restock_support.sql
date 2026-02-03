-- ==================================================================================
-- ConstructLink™ Database Migration
-- Migration: Add Restock Support for Consumable Inventory Items
-- Date: 2025-01-13
-- Description: Enable restock workflow for consumable items with MVA approval chain
-- ==================================================================================

-- ==================================================================================
-- 1. ADD RESTOCK FIELDS TO REQUESTS TABLE
-- ==================================================================================

-- Add inventory_item_id column for linking restock requests to existing inventory
ALTER TABLE `requests`
ADD COLUMN `inventory_item_id` INT(11) NULL DEFAULT NULL COMMENT 'FK to inventory_items for restock requests'
AFTER `procurement_id`;

-- Add is_restock flag to identify restock vs new item requests
ALTER TABLE `requests`
ADD COLUMN `is_restock` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Restock existing item, 0 = New item request'
AFTER `inventory_item_id`;

-- ==================================================================================
-- 2. MODIFY REQUEST_TYPE ENUM TO INCLUDE RESTOCK
-- ==================================================================================

-- Check current enum values and add 'Restock' if not present
SET @current_values = (
    SELECT COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'requests'
    AND COLUMN_NAME = 'request_type'
);

-- Add Restock to request_type enum
ALTER TABLE `requests`
MODIFY COLUMN `request_type` ENUM(
    'Material',
    'Tool',
    'Equipment',
    'Service',
    'Petty Cash',
    'Other',
    'Restock'
) NOT NULL COMMENT 'Type of request - Restock for consumable inventory replenishment';

-- ==================================================================================
-- 3. ADD FOREIGN KEY CONSTRAINTS
-- ==================================================================================

-- Add foreign key to inventory_items with proper CASCADE rules
-- ON DELETE SET NULL: If inventory item is deleted, keep request but clear the link
-- ON UPDATE CASCADE: If inventory item ID changes, update the reference
ALTER TABLE `requests`
ADD CONSTRAINT `fk_requests_inventory_item`
FOREIGN KEY (`inventory_item_id`)
REFERENCES `inventory_items` (`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- ==================================================================================
-- 4. ADD INDEXES FOR PERFORMANCE
-- ==================================================================================

-- Index for filtering restock requests
CREATE INDEX `idx_requests_is_restock` ON `requests` (`is_restock`);

-- Composite index for common restock queries
CREATE INDEX `idx_requests_restock_status` ON `requests` (`is_restock`, `status`);

-- Index for inventory item lookups
CREATE INDEX `idx_requests_inventory_item` ON `requests` (`inventory_item_id`);

-- Composite index for restock workflow queries
CREATE INDEX `idx_requests_restock_workflow` ON `requests` (`is_restock`, `status`, `inventory_item_id`);

-- ==================================================================================
-- 5. ADD COMMENTS FOR DOCUMENTATION
-- ==================================================================================

-- Document the purpose of new columns
ALTER TABLE `requests`
MODIFY COLUMN `inventory_item_id` INT(11) NULL DEFAULT NULL
COMMENT 'Foreign key to inventory_items table. Links restock requests to existing consumable items for quantity replenishment';

ALTER TABLE `requests`
MODIFY COLUMN `is_restock` TINYINT(1) NOT NULL DEFAULT 0
COMMENT 'Restock flag: 1 = Request to add quantity to existing consumable item, 0 = Request for new item procurement';

-- ==================================================================================
-- 6. CREATE VIEW FOR RESTOCK REQUESTS
-- ==================================================================================

-- View: Active Restock Requests with Item Details
CREATE OR REPLACE VIEW `view_active_restock_requests` AS
SELECT
    r.id,
    r.project_id,
    r.request_type,
    r.description,
    r.quantity as requested_quantity,
    r.urgency,
    r.date_needed,
    r.status,
    r.estimated_cost,
    r.inventory_item_id,
    r.created_at,
    p.name as project_name,
    p.code as project_code,
    u_requested.full_name as requested_by_name,
    u_reviewed.full_name as reviewed_by_name,
    u_approved.full_name as approved_by_name,
    -- Inventory item details
    ii.ref as item_ref,
    ii.name as item_name,
    ii.quantity as current_total_quantity,
    ii.available_quantity as current_available_quantity,
    ii.unit,
    c.name as category_name,
    c.is_consumable,
    -- Calculated fields
    (ii.quantity - ii.available_quantity) as consumed_quantity,
    CASE
        WHEN ii.quantity > 0 THEN ROUND((ii.available_quantity / ii.quantity) * 100, 2)
        ELSE 0
    END as stock_level_percentage,
    -- Request workflow status
    CASE
        WHEN r.status = 'Approved' AND r.procurement_id IS NULL THEN 'Ready for Procurement'
        WHEN r.status = 'Approved' AND r.procurement_id IS NOT NULL THEN 'Procurement Created'
        WHEN r.status = 'Procured' THEN 'Awaiting Delivery'
        ELSE r.status
    END as workflow_status
FROM requests r
INNER JOIN inventory_items ii ON r.inventory_item_id = ii.id
LEFT JOIN projects p ON r.project_id = p.id
LEFT JOIN users u_requested ON r.requested_by = u_requested.id
LEFT JOIN users u_reviewed ON r.reviewed_by = u_reviewed.id
LEFT JOIN users u_approved ON r.approved_by = u_approved.id
LEFT JOIN categories c ON ii.category_id = c.id
WHERE r.is_restock = 1
  AND r.status NOT IN ('Declined', 'Canceled')
ORDER BY
    CASE r.urgency
        WHEN 'Critical' THEN 1
        WHEN 'Urgent' THEN 2
        WHEN 'Normal' THEN 3
        ELSE 4
    END,
    r.date_needed ASC,
    r.created_at DESC;

-- ==================================================================================
-- 7. CREATE VIEW FOR LOW STOCK CONSUMABLES
-- ==================================================================================

-- View: Low Stock Consumable Items Requiring Restock
CREATE OR REPLACE VIEW `view_low_stock_consumables` AS
SELECT
    ii.id,
    ii.ref,
    ii.name,
    ii.description,
    ii.quantity as total_quantity,
    ii.available_quantity,
    (ii.quantity - ii.available_quantity) as consumed_quantity,
    ii.unit,
    ii.project_id,
    p.name as project_name,
    p.code as project_code,
    c.id as category_id,
    c.name as category_name,
    c.low_stock_threshold,
    -- Stock level calculation
    CASE
        WHEN ii.quantity > 0 THEN ROUND((ii.available_quantity / ii.quantity) * 100, 2)
        ELSE 0
    END as stock_level_percentage,
    -- Check if active restock request exists
    (SELECT COUNT(*) FROM requests r
     WHERE r.inventory_item_id = ii.id
     AND r.is_restock = 1
     AND r.status IN ('Draft', 'Submitted', 'Reviewed', 'Forwarded', 'Approved', 'Procured')
    ) as active_restock_requests,
    -- Urgency indicator
    CASE
        WHEN ii.available_quantity = 0 THEN 'Critical'
        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.1 THEN 'Critical'
        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.2 THEN 'Urgent'
        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.3 THEN 'Normal'
        ELSE 'Low'
    END as suggested_urgency
FROM inventory_items ii
LEFT JOIN projects p ON ii.project_id = p.id
LEFT JOIN categories c ON ii.category_id = c.id
WHERE c.is_consumable = 1
  AND ii.status = 'available'
  AND (
      -- Below 20% stock level (default threshold)
      (ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.2)
      OR
      -- Below category-specific threshold if set
      (c.low_stock_threshold IS NOT NULL AND ii.available_quantity <= c.low_stock_threshold)
      OR
      -- Completely out of stock
      ii.available_quantity = 0
  )
ORDER BY
    CASE
        WHEN ii.available_quantity = 0 THEN 1
        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.1 THEN 2
        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.2 THEN 3
        ELSE 4
    END,
    ii.available_quantity ASC;

-- ==================================================================================
-- 8. ROLLBACK SCRIPT (COMMENTED OUT - FOR REFERENCE ONLY)
-- ==================================================================================

/*
-- To rollback this migration, run the following commands:

-- Drop views
DROP VIEW IF EXISTS `view_low_stock_consumables`;
DROP VIEW IF EXISTS `view_active_restock_requests`;

-- Drop indexes
DROP INDEX `idx_requests_restock_workflow` ON `requests`;
DROP INDEX `idx_requests_inventory_item` ON `requests`;
DROP INDEX `idx_requests_restock_status` ON `requests`;
DROP INDEX `idx_requests_is_restock` ON `requests`;

-- Drop foreign key
ALTER TABLE `requests` DROP FOREIGN KEY `fk_requests_inventory_item`;

-- Remove Restock from enum (revert to original values)
ALTER TABLE `requests`
MODIFY COLUMN `request_type` ENUM(
    'Material',
    'Tool',
    'Equipment',
    'Service',
    'Petty Cash',
    'Other'
) NOT NULL;

-- Drop columns
ALTER TABLE `requests` DROP COLUMN `is_restock`;
ALTER TABLE `requests` DROP COLUMN `inventory_item_id`;
*/

-- ==================================================================================
-- 9. COMMIT CHANGES
-- ==================================================================================

COMMIT;

-- ==================================================================================
-- END OF MIGRATION
-- ==================================================================================
