-- ConstructLink™ Database Migration
-- Add 'in_transit' status to assets table for better transfer tracking
-- Migration Date: 2025-01-19

SET SQL_MODE = "STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO";

-- Check if assets table exists before proceeding
SELECT COUNT(*) as table_exists 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'assets';

-- Only proceed if assets table exists
-- Note: Run the ALTER statement below only if the above query returns 1

-- Add 'in_transit' to the assets status ENUM
-- This will allow assets to be marked as in transit during transfers
ALTER TABLE `assets` 
MODIFY COLUMN `status` ENUM(
    'available',
    'in_use', 
    'borrowed',
    'under_maintenance',
    'retired',
    'disposed',
    'in_transit'
) NOT NULL DEFAULT 'available';

-- Verify the change
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_DEFAULT,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'assets' 
  AND COLUMN_NAME = 'status';

-- Check current status distribution (only if assets table has data)
SELECT 
    status,
    COUNT(*) as count
FROM assets 
GROUP BY status 
ORDER BY count DESC;

-- Display success message
SELECT 'in_transit status successfully added to assets table' as migration_status;