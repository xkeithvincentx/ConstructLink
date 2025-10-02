-- Asset Standardization System Migration
-- ISO 55000:2024 Compliant Asset Management Enhancement
-- Date: 2025-01-08
-- Description: Comprehensive asset standardization with multi-disciplinary support

-- ================================================================================
-- SPELLING CORRECTIONS AND STANDARDIZATION
-- ================================================================================

-- Table for spelling corrections with context and learning capability
CREATE TABLE IF NOT EXISTS `asset_spelling_corrections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `incorrect` varchar(100) NOT NULL,
  `correct` varchar(100) NOT NULL,
  `context` varchar(50) DEFAULT 'tool_name' COMMENT 'tool_name, brand, material, category',
  `confidence_score` decimal(3,2) DEFAULT 0.50 COMMENT 'Confidence level 0.00 to 1.00',
  `usage_count` int(11) DEFAULT 0 COMMENT 'Number of times this correction was used',
  `created_by` int(11) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0 COMMENT 'Admin approved correction',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_incorrect` (`incorrect`),
  KEY `idx_context` (`context`),
  KEY `idx_confidence` (`confidence_score`),
  FULLTEXT KEY `idx_fulltext` (`incorrect`, `correct`),
  CONSTRAINT `fk_correction_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================================
-- MULTI-DISCIPLINARY CLASSIFICATION SYSTEM
-- ================================================================================

-- Engineering disciplines and sub-disciplines
CREATE TABLE IF NOT EXISTS `asset_disciplines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `iso_classification` varchar(50) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `fk_discipline_parent` FOREIGN KEY (`parent_id`) REFERENCES `asset_disciplines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asset types with comprehensive categorization
CREATE TABLE IF NOT EXISTS `asset_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL COMMENT 'Power Tools, Hand Tools, Safety Equipment, etc.',
  `subcategory` varchar(50) DEFAULT NULL,
  `search_keywords` text COMMENT 'Comma-separated keywords for search',
  `common_misspellings` json DEFAULT NULL COMMENT 'JSON array of common misspellings',
  `iso_category` varchar(50) DEFAULT NULL,
  `typical_specifications` json DEFAULT NULL COMMENT 'JSON structure of typical specs',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`),
  FULLTEXT KEY `idx_search` (`name`, `search_keywords`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Many-to-many relationship between asset types and disciplines
CREATE TABLE IF NOT EXISTS `asset_discipline_mappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_type_id` int(11) NOT NULL,
  `discipline_id` int(11) NOT NULL,
  `primary_use` tinyint(1) DEFAULT 0 COMMENT 'Is this the primary discipline for this asset',
  `use_description` text COMMENT 'Description of how this asset is used in this discipline',
  `frequency_of_use` enum('daily','weekly','monthly','occasional') DEFAULT 'occasional',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mapping` (`asset_type_id`, `discipline_id`),
  KEY `idx_asset_type` (`asset_type_id`),
  KEY `idx_discipline` (`discipline_id`),
  KEY `idx_primary` (`primary_use`),
  CONSTRAINT `fk_mapping_asset_type` FOREIGN KEY (`asset_type_id`) REFERENCES `asset_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mapping_discipline` FOREIGN KEY (`discipline_id`) REFERENCES `asset_disciplines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================================
-- BRAND STANDARDIZATION
-- ================================================================================

-- Standardized brand names with variations
CREATE TABLE IF NOT EXISTS `asset_brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `official_name` varchar(100) NOT NULL,
  `variations` json DEFAULT NULL COMMENT 'JSON array of name variations',
  `country` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `quality_tier` enum('premium','standard','economy') DEFAULT 'standard',
  `common_products` json DEFAULT NULL COMMENT 'JSON array of common product types',
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_official_name` (`official_name`),
  KEY `idx_quality` (`quality_tier`),
  KEY `idx_verified` (`is_verified`),
  KEY `idx_active` (`is_active`),
  FULLTEXT KEY `idx_brand_search` (`official_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================================
-- SEARCH OPTIMIZATION
-- ================================================================================

-- Asset search index for optimized queries
CREATE TABLE IF NOT EXISTS `asset_search_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `search_text` text COMMENT 'Concatenated searchable fields',
  `discipline_tags` varchar(255) DEFAULT NULL,
  `category_hierarchy` varchar(500) DEFAULT NULL,
  `brand_variations` text DEFAULT NULL,
  `phonetic_name` varchar(100) DEFAULT NULL COMMENT 'Soundex/Metaphone for phonetic search',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asset` (`asset_id`),
  KEY `idx_disciplines` (`discipline_tags`),
  KEY `idx_phonetic` (`phonetic_name`),
  KEY `idx_updated` (`last_updated`),
  FULLTEXT KEY `idx_search_text` (`search_text`),
  CONSTRAINT `fk_search_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User search history for learning and analytics
CREATE TABLE IF NOT EXISTS `asset_search_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `search_query` varchar(255) NOT NULL,
  `corrected_query` varchar(255) DEFAULT NULL,
  `selected_result_id` int(11) DEFAULT NULL,
  `result_count` int(11) DEFAULT 0,
  `search_type` enum('manual','voice','barcode','qr') DEFAULT 'manual',
  `search_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_query` (`search_query`),
  KEY `idx_timestamp` (`search_timestamp`),
  KEY `idx_selected` (`selected_result_id`),
  CONSTRAINT `fk_search_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_search_result` FOREIGN KEY (`selected_result_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================================
-- ASSET TEMPLATES AND SPECIFICATIONS
-- ================================================================================

-- Asset templates for standardized creation
CREATE TABLE IF NOT EXISTS `asset_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `asset_type_id` int(11) DEFAULT NULL,
  `template_name` varchar(100) NOT NULL,
  `standard_fields` json NOT NULL COMMENT 'JSON structure of standard fields',
  `required_specifications` json DEFAULT NULL COMMENT 'JSON array of required specs',
  `optional_specifications` json DEFAULT NULL COMMENT 'JSON array of optional specs',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_asset_type` (`asset_type_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `fk_template_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_template_asset_type` FOREIGN KEY (`asset_type_id`) REFERENCES `asset_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================================
-- ENHANCE EXISTING TABLES
-- ================================================================================

-- Add standardization fields to categories table
ALTER TABLE `categories` 
ADD COLUMN IF NOT EXISTS `discipline_tags` varchar(255) DEFAULT NULL COMMENT 'Comma-separated discipline codes',
ADD COLUMN IF NOT EXISTS `iso_classification` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `standard_specifications` json DEFAULT NULL COMMENT 'JSON structure of standard specifications',
ADD COLUMN IF NOT EXISTS `search_keywords` text DEFAULT NULL,
ADD INDEX IF NOT EXISTS `idx_disciplines` (`discipline_tags`);

-- Add standardization fields to assets table
ALTER TABLE `assets`
ADD COLUMN IF NOT EXISTS `standardized_name` varchar(200) DEFAULT NULL COMMENT 'Standardized asset name',
ADD COLUMN IF NOT EXISTS `original_name` varchar(200) DEFAULT NULL COMMENT 'Original name as entered',
ADD COLUMN IF NOT EXISTS `brand_id` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `asset_type_id` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `discipline_tags` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `search_score` decimal(5,2) DEFAULT 0.00 COMMENT 'Search relevance score',
ADD INDEX IF NOT EXISTS `idx_standardized_name` (`standardized_name`),
ADD INDEX IF NOT EXISTS `idx_brand` (`brand_id`),
ADD INDEX IF NOT EXISTS `idx_asset_type` (`asset_type_id`),
ADD CONSTRAINT `fk_asset_brand` FOREIGN KEY (`brand_id`) REFERENCES `asset_brands` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_asset_type` FOREIGN KEY (`asset_type_id`) REFERENCES `asset_types` (`id`) ON DELETE SET NULL;

-- ================================================================================
-- INITIAL DATA SEED
-- ================================================================================

-- Insert main engineering disciplines
INSERT INTO `asset_disciplines` (`code`, `name`, `description`, `display_order`) VALUES
('CIVIL', 'Civil Engineering', 'Structural and infrastructure engineering', 1),
('MECH', 'Mechanical Engineering', 'Mechanical systems and equipment', 2),
('ELEC', 'Electrical Engineering', 'Electrical systems and components', 3),
('PLUMB', 'Plumbing', 'Water supply and drainage systems', 4),
('HVAC', 'HVAC', 'Heating, ventilation, and air conditioning', 5),
('SAFETY', 'Safety', 'Safety equipment and procedures', 6),
('IT', 'Information Technology', 'IT equipment and systems', 7),
('GENERAL', 'General', 'General purpose tools and equipment', 8);

-- Insert sub-disciplines
INSERT INTO `asset_disciplines` (`code`, `name`, `description`, `parent_id`, `display_order`) 
SELECT 'STRUCT', 'Structural', 'Structural engineering', id, 1 FROM `asset_disciplines` WHERE `code` = 'CIVIL';

INSERT INTO `asset_disciplines` (`code`, `name`, `description`, `parent_id`, `display_order`) 
SELECT 'CONCRETE', 'Concrete Works', 'Concrete construction and finishing', id, 2 FROM `asset_disciplines` WHERE `code` = 'CIVIL';

INSERT INTO `asset_disciplines` (`code`, `name`, `description`, `parent_id`, `display_order`) 
SELECT 'STEEL', 'Steel Works', 'Steel fabrication and erection', id, 3 FROM `asset_disciplines` WHERE `code` = 'CIVIL';

INSERT INTO `asset_disciplines` (`code`, `name`, `description`, `parent_id`, `display_order`) 
SELECT 'POWER', 'Power Systems', 'Power generation and distribution', id, 1 FROM `asset_disciplines` WHERE `code` = 'ELEC';

INSERT INTO `asset_disciplines` (`code`, `name`, `description`, `parent_id`, `display_order`) 
SELECT 'CONTROL', 'Control Systems', 'Automation and control systems', id, 2 FROM `asset_disciplines` WHERE `code` = 'ELEC';

-- Insert common asset types
INSERT INTO `asset_types` (`name`, `category`, `subcategory`, `search_keywords`, `common_misspellings`) VALUES
-- Power Tools
('Hammer Drill', 'Power Tools', 'Drills', 'drill,hammer,concrete,masonry,impact,rotary', '["hamer drill","hammer dril","hmr drill","hammerdrill"]'),
('Angle Grinder', 'Power Tools', 'Grinders', 'grinder,cutting,grinding,disc,angle,metal', '["grinder","angle grindr","disc grinder","angel grinder"]'),
('Circular Saw', 'Power Tools', 'Saws', 'saw,cutting,blade,circular,wood,lumber', '["circular","circler saw","skill saw","circular saw"]'),
('Welding Machine', 'Power Tools', 'Welding', 'welding,welder,arc,mig,tig,stick,metal,joining', '["wilding","wilding machine","weld machine","welder machine"]'),
('Impact Driver', 'Power Tools', 'Drivers', 'driver,impact,screw,fastening,bolt', '["impact","impack driver","impact drivr"]'),
('Jigsaw', 'Power Tools', 'Saws', 'jigsaw,cutting,curve,wood,metal', '["jig saw","gigsaw","jig-saw"]'),
('Rotary Hammer', 'Power Tools', 'Drills', 'rotary,hammer,concrete,drilling,demolition', '["rotary hammr","roto hammer"]'),

-- Hand Tools
('Adjustable Wrench', 'Hand Tools', 'Wrenches', 'wrench,spanner,adjustable,pipe,plumbing', '["rench","ajustable wrench","spannar","adjustible wrench"]'),
('Screwdriver Set', 'Hand Tools', 'Drivers', 'screwdriver,phillips,flathead,torx,hex', '["screw driver","scrw driver","scru driver"]'),
('Claw Hammer', 'Hand Tools', 'Hammers', 'hammer,claw,nail,carpentry,framing', '["hamer","hammr","clow hammer"]'),
('Pliers', 'Hand Tools', 'Pliers', 'pliers,grip,cutting,needle,nose,locking', '["plyers","plairs","pliars"]'),
('Socket Set', 'Hand Tools', 'Sockets', 'socket,ratchet,wrench,bolt,nut', '["sockit set","socket","rachet set"]'),
('Level', 'Hand Tools', 'Measuring', 'level,spirit,bubble,laser,alignment,straight', '["levvel","leval","levl"]'),
('Tape Measure', 'Hand Tools', 'Measuring', 'tape,measure,ruler,distance,length,measuring', '["tape measur","measuring tape","tape"]'),

-- Safety Equipment  
('Safety Helmet', 'Safety Equipment', 'Head Protection', 'helmet,hard,hat,head,protection,safety', '["safty helmet","hard hat","helmat"]'),
('Safety Harness', 'Safety Equipment', 'Fall Protection', 'harness,fall,protection,safety,belt,climbing', '["safty harness","harnass","safety harnass"]'),
('Safety Goggles', 'Safety Equipment', 'Eye Protection', 'goggles,glasses,eye,protection,safety,vision', '["gogles","safty glasses","safety gogles"]'),

-- Testing Equipment
('Digital Multimeter', 'Testing Equipment', 'Electrical', 'multimeter,voltage,current,resistance,electrical,test,meter', '["multi meter","multimetre","volt meter","multi-meter"]'),
('Pressure Gauge', 'Testing Equipment', 'Pressure', 'pressure,gauge,psi,bar,measurement,meter', '["guage","presure gauge","pressure guage"]');

-- Insert common brands
INSERT INTO `asset_brands` (`official_name`, `variations`, `country`, `quality_tier`) VALUES
('Makita', '["makita","MAKITA","Mkita","Makitta","Maquita"]', 'Japan', 'premium'),
('DeWalt', '["dewalt","DEWALT","Dewalt","De Walt","D-Walt","Dwalt"]', 'USA', 'premium'),
('Bosch', '["bosch","BOSCH","Bosh","Bosch Professional"]', 'Germany', 'premium'),
('Hilti', '["hilti","HILTI","Hlti","Hiltie"]', 'Liechtenstein', 'premium'),
('Milwaukee', '["milwaukee","MILWAUKEE","Milwakee","Milwauke","Milwaukee Tool"]', 'USA', 'premium'),
('Caterpillar', '["caterpillar","CAT","cat","Caterpiller","Cat Equipment"]', 'USA', 'premium'),
('Stanley', '["stanley","STANLEY","Stanly","Stanley Black & Decker"]', 'USA', 'standard'),
('Black & Decker', '["black and decker","black & decker","BLACK+DECKER","B&D","black decker"]', 'USA', 'standard'),
('Ryobi', '["ryobi","RYOBI","Riobi","Ryobe"]', 'Japan', 'economy'),
('Ridgid', '["ridgid","RIDGID","Rigid","Ridge"]', 'USA', 'standard'),
('Metabo', '["metabo","METABO","Metaboo","Mitabo"]', 'Germany', 'premium'),
('Festool', '["festool","FESTOOL","Fes tool","Fest tool"]', 'Germany', 'premium');

-- Insert common spelling corrections
INSERT INTO `asset_spelling_corrections` (`incorrect`, `correct`, `context`, `confidence_score`, `approved`) VALUES
-- Tool names
('wilding', 'welding', 'tool_name', 0.95, 1),
('wilding machine', 'welding machine', 'tool_name', 0.95, 1),
('dril', 'drill', 'tool_name', 0.90, 1),
('hamer', 'hammer', 'tool_name', 0.90, 1),
('rench', 'wrench', 'tool_name', 0.90, 1),
('scru driver', 'screwdriver', 'tool_name', 0.85, 1),
('grindder', 'grinder', 'tool_name', 0.90, 1),
('levvel', 'level', 'tool_name', 0.85, 1),
('meassuring', 'measuring', 'tool_name', 0.85, 1),
('safty', 'safety', 'tool_name', 0.95, 1),
('guage', 'gauge', 'tool_name', 0.90, 1),
('plyers', 'pliers', 'tool_name', 0.90, 1),
('rachet', 'ratchet', 'tool_name', 0.85, 1),
('helmat', 'helmet', 'tool_name', 0.90, 1),
('harnass', 'harness', 'tool_name', 0.90, 1),
('gogles', 'goggles', 'tool_name', 0.90, 1),
('multi meter', 'multimeter', 'tool_name', 0.85, 1),
('angel grinder', 'angle grinder', 'tool_name', 0.90, 1),
('skill saw', 'circular saw', 'tool_name', 0.75, 1),
('vice grip', 'locking pliers', 'tool_name', 0.80, 1),

-- Materials and categories
('steal', 'steel', 'material', 0.90, 1),
('aluminium', 'aluminum', 'material', 0.85, 1),
('eletrical', 'electrical', 'category', 0.90, 1),
('mechinical', 'mechanical', 'category', 0.90, 1),
('pluming', 'plumbing', 'category', 0.90, 1);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_asset_discipline_primary ON asset_discipline_mappings(asset_type_id, primary_use);
CREATE INDEX IF NOT EXISTS idx_spelling_approved ON asset_spelling_corrections(approved, context);
CREATE INDEX IF NOT EXISTS idx_brand_tier ON asset_brands(quality_tier, is_active);

-- ================================================================================
-- TRIGGERS FOR DATA INTEGRITY
-- ================================================================================

DELIMITER $$

-- Trigger to update search index when asset is modified
CREATE TRIGGER update_search_index_on_asset_change
AFTER UPDATE ON assets
FOR EACH ROW
BEGIN
    -- Update or insert into search index
    INSERT INTO asset_search_index (asset_id, search_text, discipline_tags, brand_variations)
    VALUES (
        NEW.id,
        CONCAT_WS(' ', NEW.name, NEW.standardized_name, NEW.description, NEW.model, NEW.serial_number),
        NEW.discipline_tags,
        (SELECT variations FROM asset_brands WHERE id = NEW.brand_id)
    )
    ON DUPLICATE KEY UPDATE
        search_text = VALUES(search_text),
        discipline_tags = VALUES(discipline_tags),
        brand_variations = VALUES(brand_variations),
        last_updated = CURRENT_TIMESTAMP;
END$$

-- Trigger to track search corrections
CREATE TRIGGER track_spelling_correction_usage
AFTER INSERT ON asset_search_history
FOR EACH ROW
BEGIN
    IF NEW.corrected_query IS NOT NULL AND NEW.corrected_query != NEW.search_query THEN
        UPDATE asset_spelling_corrections 
        SET usage_count = usage_count + 1,
            confidence_score = LEAST(confidence_score + 0.01, 1.00)
        WHERE incorrect = NEW.search_query 
          AND correct = NEW.corrected_query;
    END IF;
END$$

DELIMITER ;

-- ================================================================================
-- STORED PROCEDURES FOR COMPLEX OPERATIONS
-- ================================================================================

DELIMITER $$

-- Procedure to get asset suggestions with fuzzy matching
CREATE PROCEDURE GetAssetSuggestions(
    IN search_term VARCHAR(100),
    IN category_filter VARCHAR(50),
    IN limit_results INT
)
BEGIN
    SELECT DISTINCT
        at.id,
        at.name,
        at.category,
        at.subcategory,
        CASE
            WHEN at.name = search_term THEN 100
            WHEN at.name LIKE CONCAT(search_term, '%') THEN 90
            WHEN at.name LIKE CONCAT('%', search_term, '%') THEN 80
            WHEN JSON_CONTAINS(at.common_misspellings, JSON_QUOTE(search_term)) THEN 70
            WHEN at.search_keywords LIKE CONCAT('%', search_term, '%') THEN 60
            ELSE 50
        END AS relevance_score
    FROM asset_types at
    WHERE at.is_active = 1
      AND (
        at.name LIKE CONCAT('%', search_term, '%')
        OR at.search_keywords LIKE CONCAT('%', search_term, '%')
        OR JSON_CONTAINS(at.common_misspellings, JSON_QUOTE(search_term))
      )
      AND (category_filter IS NULL OR at.category = category_filter)
    ORDER BY relevance_score DESC, at.name
    LIMIT limit_results;
END$$

-- Procedure to standardize asset name
CREATE PROCEDURE StandardizeAssetName(
    IN input_name VARCHAR(200),
    OUT standardized_name VARCHAR(200),
    OUT confidence DECIMAL(3,2)
)
BEGIN
    DECLARE corrected_name VARCHAR(200);
    DECLARE correction_confidence DECIMAL(3,2);
    
    -- First check for exact spelling correction
    SELECT correct, confidence_score INTO corrected_name, correction_confidence
    FROM asset_spelling_corrections
    WHERE incorrect = LOWER(input_name)
      AND approved = 1
      AND context = 'tool_name'
    ORDER BY confidence_score DESC
    LIMIT 1;
    
    IF corrected_name IS NOT NULL THEN
        SET standardized_name = corrected_name;
        SET confidence = correction_confidence;
    ELSE
        -- Check against asset types
        SELECT name INTO standardized_name
        FROM asset_types
        WHERE LOWER(name) = LOWER(input_name)
           OR JSON_CONTAINS(LOWER(common_misspellings), JSON_QUOTE(LOWER(input_name)))
        LIMIT 1;
        
        IF standardized_name IS NOT NULL THEN
            SET confidence = 0.80;
        ELSE
            SET standardized_name = input_name;
            SET confidence = 0.00;
        END IF;
    END IF;
END$$

DELIMITER ;

-- ================================================================================
-- PERMISSIONS GRANTS (Adjust based on your user setup)
-- ================================================================================

-- Grant permissions to application user (replace 'app_user' with your actual user)
-- GRANT SELECT, INSERT, UPDATE ON constructlink_db.asset_spelling_corrections TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON constructlink_db.asset_disciplines TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON constructlink_db.asset_types TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON constructlink_db.asset_discipline_mappings TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON constructlink_db.asset_brands TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON constructlink_db.asset_search_index TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT ON constructlink_db.asset_search_history TO 'app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON constructlink_db.asset_templates TO 'app_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE constructlink_db.GetAssetSuggestions TO 'app_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE constructlink_db.StandardizeAssetName TO 'app_user'@'localhost';

-- ================================================================================
-- MIGRATION COMPLETION
-- ================================================================================

-- Update migration tracking (if you have a migrations table)
-- INSERT INTO migrations (name, executed_at) VALUES ('add_asset_standardization_system', NOW());