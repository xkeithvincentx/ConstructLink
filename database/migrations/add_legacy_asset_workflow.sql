-- ConstructLink™ Legacy Asset Workflow Enhancement
-- Migration: Add columns for legacy asset entry workflow
-- Date: 2024-07-24

-- Add workflow tracking columns to assets table
ALTER TABLE `assets` 
ADD COLUMN `asset_source` ENUM('procurement', 'legacy', 'client_supplied', 'manual') DEFAULT 'manual' 
    COMMENT 'How the asset entered the system' AFTER `is_client_supplied`,
ADD COLUMN `sub_location` VARCHAR(100) DEFAULT NULL 
    COMMENT 'Specific location within project (Warehouse, Tool Room, etc.)' AFTER `location`,
ADD COLUMN `workflow_status` ENUM('draft', 'pending_verification', 'pending_authorization', 'approved') DEFAULT 'approved' 
    COMMENT 'MVA workflow status for legacy assets' AFTER `asset_source`,
ADD COLUMN `made_by` INT(11) DEFAULT NULL 
    COMMENT 'User who created the asset (Maker in MVA)' AFTER `workflow_status`,
ADD COLUMN `verified_by` INT(11) DEFAULT NULL 
    COMMENT 'User who verified the asset (Verifier in MVA)' AFTER `made_by`,
ADD COLUMN `authorized_by` INT(11) DEFAULT NULL 
    COMMENT 'User who authorized the asset (Authorizer in MVA)' AFTER `verified_by`,
ADD COLUMN `verification_date` TIMESTAMP NULL DEFAULT NULL 
    COMMENT 'When the asset was verified' AFTER `authorized_by`,
ADD COLUMN `authorization_date` TIMESTAMP NULL DEFAULT NULL 
    COMMENT 'When the asset was authorized' AFTER `verification_date`;

-- Add foreign key constraints for workflow users
ALTER TABLE `assets` 
ADD CONSTRAINT `assets_made_by_fk` FOREIGN KEY (`made_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `assets_verified_by_fk` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `assets_authorized_by_fk` FOREIGN KEY (`authorized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for performance
ALTER TABLE `assets` 
ADD INDEX `idx_asset_source` (`asset_source`),
ADD INDEX `idx_sub_location` (`sub_location`),
ADD INDEX `idx_workflow_status` (`workflow_status`),
ADD INDEX `idx_made_by` (`made_by`),
ADD INDEX `idx_verified_by` (`verified_by`),
ADD INDEX `idx_authorized_by` (`authorized_by`);

-- Add composite index for workflow queries
ALTER TABLE `assets` 
ADD INDEX `idx_workflow_complete` (`workflow_status`, `asset_source`, `project_id`);

-- Update existing assets to have proper workflow status (all approved since they already exist)
UPDATE `assets` SET 
    `workflow_status` = 'approved',
    `asset_source` = CASE 
        WHEN `procurement_order_id` IS NOT NULL THEN 'procurement'
        WHEN `is_client_supplied` = 1 THEN 'client_supplied'  
        ELSE 'manual'
    END;

-- Add comment to table for documentation
ALTER TABLE `assets` COMMENT = 'Asset inventory with legacy entry workflow support';