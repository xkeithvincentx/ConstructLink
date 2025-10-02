-- Asset Validation System Migration
-- Creates tables and structures for enhanced asset verification and authorization

-- Asset validation rules table
CREATE TABLE IF NOT EXISTS `asset_validation_rules` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `rule_name` varchar(100) NOT NULL,
    `rule_type` enum('completeness', 'format', 'logic', 'cost', 'duplicate') NOT NULL,
    `field_name` varchar(50) NOT NULL,
    `validation_logic` text NOT NULL COMMENT 'JSON configuration for validation rule',
    `error_message` varchar(255) NOT NULL,
    `severity` enum('error', 'warning', 'info') DEFAULT 'warning',
    `is_active` tinyint(1) DEFAULT 1,
    `applies_to_roles` text COMMENT 'JSON array of roles this applies to',
    `created_at` timestamp DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `rule_name_unique` (`rule_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset verification reviews table
CREATE TABLE IF NOT EXISTS `asset_verification_reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `asset_id` int(11) NOT NULL,
    `reviewer_id` int(11) NOT NULL,
    `review_type` enum('verification', 'authorization') NOT NULL,
    `overall_score` decimal(5,2) DEFAULT NULL COMMENT 'Overall quality score 0-100',
    `completeness_score` decimal(5,2) DEFAULT NULL COMMENT 'Data completeness score',
    `accuracy_score` decimal(5,2) DEFAULT NULL COMMENT 'Data accuracy score',
    `review_status` enum('in_progress', 'completed', 'needs_revision') DEFAULT 'in_progress',
    `review_notes` text,
    `field_reviews` longtext COMMENT 'JSON object with field-by-field review results',
    `validation_results` longtext COMMENT 'JSON object with validation rule results',
    `photos_uploaded` tinyint(1) DEFAULT 0,
    `physical_verification_completed` tinyint(1) DEFAULT 0,
    `location_verified` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `asset_id` (`asset_id`),
    KEY `reviewer_id` (`reviewer_id`),
    KEY `review_type` (`review_type`),
    CONSTRAINT `fk_verification_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_verification_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset review photos table
CREATE TABLE IF NOT EXISTS `asset_review_photos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `review_id` int(11) NOT NULL,
    `asset_id` int(11) NOT NULL,
    `photo_type` enum('asset_overview', 'asset_tag', 'serial_number', 'condition', 'location') NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `original_filename` varchar(255),
    `file_size` int(11),
    `mime_type` varchar(100),
    `description` text,
    `uploaded_by` int(11) NOT NULL,
    `uploaded_at` timestamp DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `review_id` (`review_id`),
    KEY `asset_id` (`asset_id`),
    CONSTRAINT `fk_photo_review` FOREIGN KEY (`review_id`) REFERENCES `asset_verification_reviews` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_photo_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_photo_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset field corrections table
CREATE TABLE IF NOT EXISTS `asset_field_corrections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `review_id` int(11) NOT NULL,
    `asset_id` int(11) NOT NULL,
    `field_name` varchar(50) NOT NULL,
    `original_value` text,
    `suggested_value` text,
    `correction_reason` text,
    `correction_type` enum('automatic', 'manual', 'ai_suggested') DEFAULT 'manual',
    `applied` tinyint(1) DEFAULT 0,
    `applied_by` int(11) DEFAULT NULL,
    `applied_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `review_id` (`review_id`),
    KEY `asset_id` (`asset_id`),
    CONSTRAINT `fk_correction_review` FOREIGN KEY (`review_id`) REFERENCES `asset_verification_reviews` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_correction_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default validation rules
INSERT INTO `asset_validation_rules` (`rule_name`, `rule_type`, `field_name`, `validation_logic`, `error_message`, `severity`, `applies_to_roles`) VALUES
('equipment_type_required', 'completeness', 'equipment_type_id', '{"required": true, "not_empty": true}', 'Equipment type must be selected', 'error', '["Site Inventory Clerk", "Project Manager"]'),
('category_required', 'completeness', 'category_id', '{"required": true, "not_empty": true}', 'Category must be selected', 'error', '["Site Inventory Clerk", "Project Manager"]'),
('project_required', 'completeness', 'project_id', '{"required": true, "not_empty": true}', 'Project must be assigned', 'error', '["Site Inventory Clerk", "Project Manager"]'),
('asset_name_required', 'completeness', 'name', '{"required": true, "min_length": 3}', 'Asset name must be at least 3 characters', 'error', '["Site Inventory Clerk", "Project Manager"]'),
('quantity_valid', 'format', 'quantity', '{"type": "integer", "min": 1, "max": 9999}', 'Quantity must be a number between 1 and 9999', 'error', '["Site Inventory Clerk", "Project Manager"]'),
('serial_format_check', 'format', 'serial_number', '{"pattern": "^[A-Z0-9\\-\\/]{3,50}$"}', 'Serial number should contain only letters, numbers, dashes, and slashes', 'warning', '["Site Inventory Clerk", "Project Manager"]'),
('cost_reasonableness', 'cost', 'acquisition_cost', '{"min": 100, "max": 1000000, "check_against_category": true}', 'Cost seems unusual for this type of equipment', 'warning', '["Project Manager"]'),
('brand_equipment_compatibility', 'logic', 'brand_id', '{"check_brand_makes_equipment": true}', 'Selected brand may not manufacture this type of equipment', 'warning', '["Site Inventory Clerk", "Project Manager"]'),
('discipline_equipment_match', 'logic', 'discipline_tags', '{"check_discipline_equipment_match": true}', 'Equipment type may not typically belong to selected discipline', 'warning', '["Site Inventory Clerk", "Project Manager"]'),
('location_specified', 'completeness', 'location', '{"required": true, "min_length": 2}', 'Current location must be specified', 'warning', '["Site Inventory Clerk", "Project Manager"]'),
('description_quality', 'completeness', 'description', '{"min_length": 10, "avoid_generic": ["item", "tool", "equipment"]}', 'Description should be more detailed and specific', 'info', '["Site Inventory Clerk", "Project Manager"]');