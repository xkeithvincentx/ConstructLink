-- Add ISO codes to discipline management system
-- This enables dynamic discipline handling without hard-coded mappings

-- Add iso_code field to asset_disciplines table
ALTER TABLE `asset_disciplines` 
ADD COLUMN `iso_code` varchar(2) DEFAULT NULL COMMENT 'ISO 55000:2024 2-character discipline code' 
AFTER `code`;

-- Add index for performance
ALTER TABLE `asset_disciplines` 
ADD INDEX `idx_iso_code` (`iso_code`);

-- Update existing disciplines with their ISO codes
UPDATE `asset_disciplines` SET `iso_code` = 'CV' WHERE `name` = 'Civil Engineering';
UPDATE `asset_disciplines` SET `iso_code` = 'ST' WHERE `name` = 'Structural Engineering';
UPDATE `asset_disciplines` SET `iso_code` = 'ME' WHERE `name` = 'Mechanical Engineering';
UPDATE `asset_disciplines` SET `iso_code` = 'EL' WHERE `name` = 'Electrical Engineering';
UPDATE `asset_disciplines` SET `iso_code` = 'AR' WHERE `name` = 'Architectural';
UPDATE `asset_disciplines` SET `iso_code` = 'PL' WHERE `name` = 'Plumbing';
UPDATE `asset_disciplines` SET `iso_code` = 'HV' WHERE `name` = 'HVAC Systems';
UPDATE `asset_disciplines` SET `iso_code` = 'GE' WHERE `name` = 'Geotechnical';
UPDATE `asset_disciplines` SET `iso_code` = 'SU' WHERE `name` = 'Surveying';
UPDATE `asset_disciplines` SET `iso_code` = 'EN' WHERE `name` = 'Environmental';
UPDATE `asset_disciplines` SET `iso_code` = 'SA' WHERE `name` = 'Safety & Health';
UPDATE `asset_disciplines` SET `iso_code` = 'FP' WHERE `name` LIKE 'Fire%';
UPDATE `asset_disciplines` SET `iso_code` = 'TC' WHERE `name` LIKE 'Telecom%';
UPDATE `asset_disciplines` SET `iso_code` = 'LA' WHERE `name` LIKE 'Landscap%';
UPDATE `asset_disciplines` SET `iso_code` = 'QC' WHERE `name` LIKE 'Quality%';
UPDATE `asset_disciplines` SET `iso_code` = 'GN' WHERE `name` LIKE 'General%';

-- Set default 'GN' for any remaining disciplines without ISO codes
UPDATE `asset_disciplines` SET `iso_code` = 'GN' WHERE `iso_code` IS NULL;