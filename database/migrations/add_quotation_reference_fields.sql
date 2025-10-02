-- Migration: Add Quotation Reference Fields
-- Date: 2025-01-08
-- Description: Add quotation number and date fields for enhanced vendor references

-- Add quotation number field to procurement_orders table
ALTER TABLE `procurement_orders` 
ADD COLUMN `quotation_number` VARCHAR(100) NULL 
COMMENT 'Vendor quotation reference number (e.g., QUO-2024-001)' 
AFTER `quote_file`;

-- Add quotation date field to procurement_orders table
ALTER TABLE `procurement_orders` 
ADD COLUMN `quotation_date` DATE NULL 
COMMENT 'Date of vendor quotation for reference purposes' 
AFTER `quotation_number`;

-- Add index for performance on quotation queries
ALTER TABLE `procurement_orders` 
ADD KEY `quotation_number` (`quotation_number`);

-- Add index for quotation date queries
ALTER TABLE `procurement_orders` 
ADD KEY `quotation_date` (`quotation_date`);

-- Update existing records with null values (explicit for clarity)
UPDATE `procurement_orders` 
SET `quotation_number` = NULL, `quotation_date` = NULL 
WHERE `quotation_number` IS NULL OR `quotation_date` IS NULL;

-- Migration notes:
-- 1. quotation_number allows flexible formats: QUO-2024-001, Q-001, 2024-001, etc.
-- 2. quotation_date provides fallback when only date is available
-- 3. Both fields are optional to maintain backward compatibility
-- 4. Print preview will use intelligent display priority:
--    - Priority 1: quotation_number
--    - Priority 2: quotation_date (if no number)
--    - Priority 3: filename (if neither available)