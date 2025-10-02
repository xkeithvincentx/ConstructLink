-- ConstructLink™ Add QR Tag Status Tracking
-- Migration: Add QR tag lifecycle tracking to assets table
-- Date: 2025-07-24

-- Add QR tag status tracking fields to assets table
ALTER TABLE `assets` 
ADD COLUMN `qr_tag_printed` TIMESTAMP NULL COMMENT 'When QR tag was printed for physical application' AFTER `qr_code`,
ADD COLUMN `qr_tag_applied` TIMESTAMP NULL COMMENT 'When QR tag was physically applied to asset' AFTER `qr_tag_printed`,
ADD COLUMN `qr_tag_verified` TIMESTAMP NULL COMMENT 'When QR tag placement was verified by Site Inventory Clerk' AFTER `qr_tag_applied`,
ADD COLUMN `qr_tag_applied_by` INT(11) DEFAULT NULL COMMENT 'User who applied the physical tag' AFTER `qr_tag_verified`,
ADD COLUMN `qr_tag_verified_by` INT(11) DEFAULT NULL COMMENT 'User who verified tag placement' AFTER `qr_tag_applied_by`,
ADD COLUMN `tag_notes` TEXT DEFAULT NULL COMMENT 'Notes about tag application, issues, or replacements' AFTER `qr_tag_verified_by`;

-- Add foreign key constraints for tag tracking users
ALTER TABLE `assets` 
ADD CONSTRAINT `assets_tag_applied_by_fk` FOREIGN KEY (`qr_tag_applied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `assets_tag_verified_by_fk` FOREIGN KEY (`qr_tag_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for performance on tag status queries
ALTER TABLE `assets` 
ADD INDEX `idx_qr_tag_printed` (`qr_tag_printed`),
ADD INDEX `idx_qr_tag_applied` (`qr_tag_applied`),
ADD INDEX `idx_qr_tag_verified` (`qr_tag_verified`),
ADD INDEX `idx_qr_tag_status` (`qr_code`, `qr_tag_printed`, `qr_tag_applied`, `qr_tag_verified`);

-- Create QR tag tracking log table for detailed history
CREATE TABLE IF NOT EXISTS `qr_tag_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `asset_id` int(11) NOT NULL,
    `action` enum('generated','printed','applied','verified','replaced','damaged') NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `previous_qr_code` varchar(255) DEFAULT NULL COMMENT 'Previous QR code if replaced',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `asset_id` (`asset_id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `qr_tag_logs_asset_fk` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `qr_tag_logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='QR tag lifecycle tracking and audit log';

-- Update assets table comment to document QR tracking enhancement
ALTER TABLE `assets` COMMENT = 'Asset inventory with legacy entry workflow, unit support, and QR tag lifecycle tracking';

-- Success message
SELECT 'QR tag status tracking fields added successfully!' as message;