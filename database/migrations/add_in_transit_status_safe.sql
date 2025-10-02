-- ConstructLink™ Database Migration - Safe Version
-- Add 'in_transit' status to assets table for better transfer tracking
-- Migration Date: 2025-01-19

-- STEP 1: First check if you're connected to the correct database
-- Run this to verify you're in the right database:
SELECT DATABASE() as current_database;

-- STEP 2: Check if assets table exists
-- This should return 1 if the table exists:
SELECT COUNT(*) as assets_table_exists 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'assets';

-- STEP 3: Check current ENUM values for status column
-- This shows you the current status options:
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'assets' 
  AND COLUMN_NAME = 'status';

-- STEP 4: If the above steps look correct, run this ALTER statement:
-- (Only run this if steps 1-3 show the correct database and table)

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

-- STEP 5: Verify the change was successful:
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'assets' 
  AND COLUMN_NAME = 'status';

-- STEP 6: Check current asset status distribution:
SELECT 
    status,
    COUNT(*) as count
FROM assets 
GROUP BY status 
ORDER BY count DESC;