-- Migration: Add Business Category Classification System
-- Date: 2024-01-11
-- Description: Enhance categories table with business-aligned classification for proper procurement handling

-- ================================================================================
-- PHASE 1: ADD BUSINESS CLASSIFICATION COLUMNS TO CATEGORIES TABLE
-- ================================================================================

-- Add business classification columns (backwards compatible - all default to current behavior)
ALTER TABLE `categories` 
ADD COLUMN `generates_assets` BOOLEAN DEFAULT TRUE 
COMMENT 'Whether procurement items create trackable assets (TRUE maintains current behavior)' AFTER `parent_id`,

ADD COLUMN `asset_type` ENUM('capital', 'inventory', 'expense') DEFAULT 'capital' 
COMMENT 'Accounting classification: capital=depreciable assets, inventory=consumable tracking, expense=immediate cost' AFTER `generates_assets`,

ADD COLUMN `expense_category` VARCHAR(50) DEFAULT NULL 
COMMENT 'For non-asset items: operating, regulatory, professional_services, maintenance, utilities' AFTER `asset_type`,

ADD COLUMN `depreciation_applicable` BOOLEAN DEFAULT FALSE 
COMMENT 'Whether item is subject to depreciation (typically for capital assets)' AFTER `expense_category`,

ADD COLUMN `capitalization_threshold` DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'Minimum unit value to capitalize as asset (0.00 = always capitalize if generates_assets=TRUE)' AFTER `depreciation_applicable`,

ADD COLUMN `business_description` TEXT DEFAULT NULL 
COMMENT 'Business context and usage guidelines for this category' AFTER `capitalization_threshold`,

ADD COLUMN `auto_expense_below_threshold` BOOLEAN DEFAULT FALSE 
COMMENT 'Automatically expense items below capitalization threshold instead of creating assets' AFTER `business_description`;

-- ================================================================================
-- INDEXES FOR PERFORMANCE
-- ================================================================================

-- Add indexes for common query patterns
ALTER TABLE `categories` 
ADD INDEX `idx_generates_assets` (`generates_assets`),
ADD INDEX `idx_asset_type` (`asset_type`),
ADD INDEX `idx_asset_type_generates` (`asset_type`, `generates_assets`),
ADD INDEX `idx_expense_category` (`expense_category`),
ADD INDEX `idx_threshold_check` (`capitalization_threshold`, `generates_assets`);

-- ================================================================================
-- VALIDATE EXISTING DATA (Set appropriate defaults for existing categories)
-- ================================================================================

-- Update existing categories with sensible defaults based on current usage patterns

-- Categories that currently have consumable=1 should be inventory type
UPDATE `categories` 
SET 
    `asset_type` = 'inventory',
    `generates_assets` = TRUE,
    `business_description` = 'Consumable materials tracked as inventory assets'
WHERE `is_consumable` = 1;

-- Categories that currently have consumable=0 should be capital type  
UPDATE `categories` 
SET 
    `asset_type` = 'capital',
    `generates_assets` = TRUE,
    `depreciation_applicable` = TRUE,
    `business_description` = 'Durable equipment and tools tracked as capital assets'
WHERE `is_consumable` = 0;

-- ================================================================================
-- VALIDATION CONSTRAINTS 
-- ================================================================================

-- Add business rule constraints
ALTER TABLE `categories` 
ADD CONSTRAINT `chk_asset_type_generates_assets` 
CHECK (
    (asset_type = 'expense' AND generates_assets = FALSE) OR 
    (asset_type IN ('capital', 'inventory') AND generates_assets = TRUE)
),

ADD CONSTRAINT `chk_expense_category_logic` 
CHECK (
    (generates_assets = TRUE AND expense_category IS NULL) OR
    (generates_assets = FALSE AND expense_category IS NOT NULL)
),

ADD CONSTRAINT `chk_depreciation_logic`
CHECK (
    (asset_type = 'capital' AND generates_assets = TRUE) OR
    (depreciation_applicable = FALSE)
),

ADD CONSTRAINT `chk_capitalization_threshold`
CHECK (capitalization_threshold >= 0);

-- ================================================================================
-- ROLLBACK INSTRUCTIONS
-- ================================================================================

/*
-- ROLLBACK SCRIPT (if needed):

-- Remove constraints
ALTER TABLE `categories` 
DROP CONSTRAINT `chk_asset_type_generates_assets`,
DROP CONSTRAINT `chk_expense_category_logic`, 
DROP CONSTRAINT `chk_depreciation_logic`,
DROP CONSTRAINT `chk_capitalization_threshold`;

-- Remove indexes
ALTER TABLE `categories`
DROP INDEX `idx_generates_assets`,
DROP INDEX `idx_asset_type`,
DROP INDEX `idx_asset_type_generates`,
DROP INDEX `idx_expense_category`,
DROP INDEX `idx_threshold_check`;

-- Remove columns (WARNING: This will lose data)
ALTER TABLE `categories`
DROP COLUMN `auto_expense_below_threshold`,
DROP COLUMN `business_description`, 
DROP COLUMN `capitalization_threshold`,
DROP COLUMN `depreciation_applicable`,
DROP COLUMN `expense_category`,
DROP COLUMN `asset_type`,
DROP COLUMN `generates_assets`,
DROP COLUMN `updated_at`,
DROP COLUMN `created_at`;
*/

-- ================================================================================
-- DEVELOPER NOTES
-- ================================================================================

/*
IMPORTANT: After running this migration, update the following:

1. CategoryModel fillable array to include:
   'generates_assets', 'asset_type', 'expense_category', 'depreciation_applicable', 
   'capitalization_threshold', 'business_description', 'auto_expense_below_threshold'

2. ProcurementOrderModel methods:
   - getItemsEligibleForAssetGeneration() - add generates_assets check
   - Add new method getNonAssetGeneratingItems()

3. AssetModel createAsset() method:
   - Add category business rule validation
   - Check capitalization thresholds

4. Category seeding script should be run after this migration
   - Run: add_business_category_taxonomy_seed.sql

BUSINESS LOGIC REMINDERS:
- generates_assets=TRUE: Item creates trackable asset record
- generates_assets=FALSE: Item is expensed directly, no asset created
- asset_type='capital': Depreciable equipment/tools
- asset_type='inventory': Consumable materials with quantity tracking  
- asset_type='expense': Services, permits, maintenance - no asset generation
- capitalization_threshold: Items below this value can be auto-expensed even if generates_assets=TRUE
*/

-- Migration completed successfully
SELECT 'Business Category Classification migration completed' AS status;