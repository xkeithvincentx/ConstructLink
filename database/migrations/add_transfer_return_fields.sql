-- Migration: Add expected_return and actual_return fields to transfers table
-- Date: 2024-01-01
-- Description: Add support for temporary transfer return tracking

-- Check if the columns already exist before adding them
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'transfers' 
               AND COLUMN_NAME = 'expected_return');

SET @sql = IF(@exist = 0, 
    'ALTER TABLE transfers ADD COLUMN expected_return DATE DEFAULT NULL AFTER transfer_date',
    'SELECT "Column expected_return already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add actual_return column
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'transfers' 
               AND COLUMN_NAME = 'actual_return');

SET @sql = IF(@exist = 0, 
    'ALTER TABLE transfers ADD COLUMN actual_return DATE DEFAULT NULL AFTER expected_return',
    'SELECT "Column actual_return already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for expected_return for better query performance
SET @exist := (SELECT COUNT(*) FROM information_schema.STATISTICS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'transfers' 
               AND INDEX_NAME = 'expected_return');

SET @sql = IF(@exist = 0, 
    'ALTER TABLE transfers ADD INDEX expected_return (expected_return)',
    'SELECT "Index expected_return already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show the updated table structure
DESCRIBE transfers;
