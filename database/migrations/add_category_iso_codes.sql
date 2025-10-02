-- ConstructLink™ Migration: Add ISO Codes to Categories
-- Description: Add iso_code column to categories table for database-driven category code management
-- Created: 2025-09-02

-- Add iso_code column to categories table
ALTER TABLE `categories` 
ADD COLUMN `iso_code` varchar(2) DEFAULT NULL COMMENT 'ISO 55000:2024 2-character category code' 
AFTER `name`;

-- Populate existing categories with appropriate ISO codes based on ISO 55000:2024 standards
-- Equipment: Heavy machinery, specialized tools, test equipment
UPDATE `categories` SET `iso_code` = 'EQ' WHERE `name` LIKE '%equipment%' OR `name` LIKE '%machinery%';
UPDATE `categories` SET `iso_code` = 'EQ' WHERE `name` IN ('Heavy Equipment', 'Test Equipment', 'Specialized Equipment');

-- Tools: Hand tools, power tools, measuring instruments  
UPDATE `categories` SET `iso_code` = 'TO' WHERE `name` LIKE '%tool%' OR `name` LIKE '%instrument%';
UPDATE `categories` SET `iso_code` = 'TO' WHERE `name` IN ('Hand Tools', 'Power Tools', 'Construction Tools', 'Small Tools', 'Measuring Instruments');

-- Vehicles: Transportation assets
UPDATE `categories` SET `iso_code` = 'VE' WHERE `name` LIKE '%vehicle%' OR `name` LIKE '%transport%';
UPDATE `categories` SET `iso_code` = 'VE' WHERE `name` IN ('Vehicles', 'Construction Vehicles', 'Service Vehicles');

-- Infrastructure: Permanent installations
UPDATE `categories` SET `iso_code` = 'IN' WHERE `name` LIKE '%infrastructure%' OR `name` LIKE '%structure%';
UPDATE `categories` SET `iso_code` = 'IN' WHERE `name` IN ('Infrastructure', 'Site Infrastructure', 'Temporary Structures', 'Utilities');

-- Safety Equipment: PPE, safety systems
UPDATE `categories` SET `iso_code` = 'SA' WHERE `name` LIKE '%safety%' OR `name` LIKE '%ppe%' OR `name` LIKE '%protection%';
UPDATE `categories` SET `iso_code` = 'SA' WHERE `name` IN ('Safety Equipment', 'PPE', 'Personal Protective Equipment', 'Emergency Equipment');

-- Information Technology: Computing, communication
UPDATE `categories` SET `iso_code` = 'IT' WHERE `name` LIKE '%computer%' OR `name` LIKE '%it%' OR `name` LIKE '%technology%' OR `name` LIKE '%communication%';
UPDATE `categories` SET `iso_code` = 'IT' WHERE `name` IN ('IT Equipment', 'Computers', 'Communication', 'Software', 'Technology');

-- Furniture & Fixtures: Office furniture, site furniture
UPDATE `categories` SET `iso_code` = 'FU' WHERE `name` LIKE '%furniture%' OR `name` LIKE '%fixture%';
UPDATE `categories` SET `iso_code` = 'FU' WHERE `name` IN ('Furniture', 'Fixtures', 'Office Furniture', 'Site Furniture');

-- Materials: Consumable supplies, electrical supplies, materials
UPDATE `categories` SET `iso_code` = 'MA' WHERE `name` LIKE '%material%' OR `name` LIKE '%supplies%' OR `name` LIKE '%consumable%';
UPDATE `categories` SET `iso_code` = 'MA' WHERE `name` IN ('Materials', 'Consumables', 'Supplies', 'Electrical Supplies', 'Consumable Materials');

-- General: Default for anything not categorized above
UPDATE `categories` SET `iso_code` = 'GN' WHERE `name` IN ('General', 'Miscellaneous', 'Other') OR `iso_code` IS NULL;

-- Ensure no categories are left without an ISO code (fallback to General)
UPDATE `categories` SET `iso_code` = 'GN' WHERE `iso_code` IS NULL;

-- Add index for performance
CREATE INDEX idx_categories_iso_code ON categories(iso_code);

-- Verification query (commented out for production)
-- SELECT id, name, iso_code FROM categories ORDER BY name;