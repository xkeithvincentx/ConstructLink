-- Migration: Add category-specific stock threshold columns
-- Purpose: Replace hardcoded low stock threshold (10) with category-specific thresholds
-- Author: System Generated
-- Date: 2025-10-28
-- Issue: Hardcoded threshold flags 97% of assets as low stock (meaningless)
-- Solution: Category-specific thresholds with intelligent defaults

-- Add threshold columns to categories table
ALTER TABLE categories
ADD COLUMN low_stock_threshold INT DEFAULT 3
    COMMENT 'Quantity threshold to trigger low stock alert (category-specific)',
ADD COLUMN critical_stock_threshold INT DEFAULT 1
    COMMENT 'Quantity threshold to trigger critical stock alert (immediate reorder)',
ADD COLUMN threshold_type ENUM('absolute', 'percentage') DEFAULT 'absolute'
    COMMENT 'Threshold calculation method (absolute quantity or percentage of max stock)';

-- Set intelligent defaults based on category type
UPDATE categories
SET low_stock_threshold = CASE
        WHEN is_consumable = 1 THEN 5  -- Consumables: alert at 5 units
        ELSE 2                          -- Tools/Equipment: alert at 2 units
    END,
    critical_stock_threshold = CASE
        WHEN is_consumable = 1 THEN 2  -- Consumables: critical at 2 units
        ELSE 1                          -- Tools/Equipment: critical at 1 unit
    END;

-- Add index for performance on warehouse queries
CREATE INDEX idx_categories_thresholds ON categories(is_consumable, low_stock_threshold, critical_stock_threshold);

-- Verification query (to be run after migration)
-- SELECT
--     name,
--     is_consumable,
--     low_stock_threshold,
--     critical_stock_threshold,
--     threshold_type
-- FROM categories
-- ORDER BY is_consumable DESC, name;
