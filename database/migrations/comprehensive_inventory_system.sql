-- ConstructLink™ Comprehensive Construction Inventory Classification System
-- Eliminates redundancy and creates intelligent auto-naming
-- Research-based on 2025 construction industry standards

-- =====================================================
-- Drop existing subtype tables to recreate with better structure
-- =====================================================
DROP TABLE IF EXISTS asset_extended_properties;
DROP TABLE IF EXISTS asset_specification_templates;
DROP TABLE IF EXISTS asset_subtypes;
DROP TABLE IF EXISTS asset_equipment_types;

-- =====================================================
-- Equipment Types (Main functional categories)
-- =====================================================
CREATE TABLE IF NOT EXISTS equipment_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL, -- Links to categories (used by procurement)
    name VARCHAR(100) NOT NULL, -- e.g., "Drill", "Grinder", "Welder"
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_active (is_active)
);

-- =====================================================
-- Equipment Subtypes (Specific variations with materials/power)
-- =====================================================
CREATE TABLE IF NOT EXISTS equipment_subtypes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_type_id INT NOT NULL,
    subtype_name VARCHAR(100) NOT NULL, -- e.g., "Electric", "Pneumatic", "Cordless"
    material_type VARCHAR(50), -- e.g., "Metal", "Wood", "Concrete", "Masonry"
    power_source VARCHAR(50), -- e.g., "Cordless", "Corded", "Pneumatic", "Manual"
    size_category VARCHAR(50), -- e.g., "4.5-inch", "Heavy-duty", "Compact"
    application_area TEXT, -- What it's used for
    technical_specs JSON, -- Specifications template
    discipline_tags JSON, -- Applicable disciplines
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id) ON DELETE CASCADE,
    INDEX idx_equipment_type (equipment_type_id),
    INDEX idx_active (is_active)
);

-- =====================================================
-- Asset Extended Properties (Technical specifications)
-- =====================================================
CREATE TABLE IF NOT EXISTS asset_extended_properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    property_name VARCHAR(50) NOT NULL,
    property_value TEXT,
    property_unit VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asset_property (asset_id, property_name)
);

-- =====================================================
-- Update assets table structure
-- =====================================================
ALTER TABLE assets 
    DROP FOREIGN KEY IF EXISTS fk_assets_equipment_type,
    DROP FOREIGN KEY IF EXISTS fk_assets_subtype,
    DROP COLUMN IF EXISTS equipment_type_id,
    DROP COLUMN IF EXISTS subtype_id;

ALTER TABLE assets 
    ADD COLUMN equipment_type_id INT NULL AFTER category_id,
    ADD COLUMN subtype_id INT NULL AFTER equipment_type_id,
    ADD COLUMN generated_name VARCHAR(255) NULL AFTER name,
    ADD COLUMN name_components JSON NULL AFTER generated_name,
    ADD FOREIGN KEY fk_assets_equipment_type (equipment_type_id) REFERENCES equipment_types(id) ON DELETE SET NULL,
    ADD FOREIGN KEY fk_assets_subtype (subtype_id) REFERENCES equipment_subtypes(id) ON DELETE SET NULL;

-- =====================================================
-- Insert comprehensive equipment types by category
-- =====================================================

-- WELDING EQUIPMENT (Category 13 - Welding Equipment)
INSERT IGNORE INTO equipment_types (category_id, name, description) VALUES
(13, 'Arc Welder', 'Electric arc welding machines'),
(13, 'Gas Welder', 'Gas-powered welding and cutting equipment'),
(13, 'Plasma Cutter', 'Plasma arc cutting machines'),
(13, 'Spot Welder', 'Resistance spot welding machines'),
(13, 'Multi-Process Welder', 'Versatile welding machines supporting multiple processes');

-- POWER TOOLS (Category 4 - Power Tools)
INSERT IGNORE INTO equipment_types (category_id, name, description) VALUES
(4, 'Drill', 'Drilling and boring tools'),
(4, 'Grinder', 'Grinding and surface preparation tools'),
(4, 'Saw', 'Power cutting saws'),
(4, 'Sander', 'Surface finishing and smoothing tools'),
(4, 'Impact Tool', 'Impact and demolition tools'),
(4, 'Router', 'Routing and shaping tools'),
(4, 'Nail Gun', 'Pneumatic and electric nail guns');

-- HAND TOOLS (Category 5 - Hand Tools)  
INSERT IGNORE INTO equipment_types (category_id, name, description) VALUES
(5, 'Hammer', 'Striking tools'),
(5, 'Wrench', 'Gripping and turning tools'),
(5, 'Screwdriver', 'Screw insertion and removal tools'),
(5, 'Pliers', 'Gripping and bending tools'),
(5, 'Hand Saw', 'Manual cutting tools'),
(5, 'Measuring Tool', 'Measurement and marking tools'),
(5, 'Trowel', 'Finishing and spreading tools');

-- HEAVY EQUIPMENT (Category 3 - Heavy Equipment)
INSERT IGNORE INTO equipment_types (category_id, name, description) VALUES
(3, 'Excavator', 'Earth moving equipment'),
(3, 'Bulldozer', 'Earth moving and grading equipment'),
(3, 'Loader', 'Material handling equipment'),
(3, 'Crane', 'Lifting and positioning equipment'),
(3, 'Compactor', 'Soil and asphalt compaction equipment');

-- CONSTRUCTION VEHICLES (Category 7 - Construction Vehicles)
INSERT IGNORE INTO equipment_types (category_id, name, description) VALUES
(7, 'Truck', 'Transport and utility vehicles'),
(7, 'Van', 'Service and transport vehicles'),
(7, 'Trailer', 'Towing and transport equipment');

-- SAFETY EQUIPMENT (Category 8 - Safety Equipment)
INSERT IGNORE INTO equipment_types (category_id, name, description) VALUES
(8, 'Hard Hat', 'Head protection equipment'),
(8, 'Safety Harness', 'Fall protection equipment'),
(8, 'Safety Glasses', 'Eye protection equipment'),
(8, 'Respirator', 'Breathing protection equipment');

-- =====================================================
-- Insert comprehensive equipment subtypes
-- =====================================================

-- ARC WELDER SUBTYPES
SET @arc_welder_id = (SELECT id FROM equipment_types WHERE name = 'Arc Welder' LIMIT 1);
INSERT IGNORE INTO equipment_subtypes (equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags) VALUES
(@arc_welder_id, 'MIG', 'Metal', 'Electric', 'Portable', 'General metal fabrication and repair', 
 JSON_OBJECT('voltage_range', 'Input voltage options', 'output_current', 'Amperage range', 'wire_diameter', 'Compatible wire sizes', 'shielding_gas', 'Gas compatibility'),
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering')),

(@arc_welder_id, 'TIG', 'Metal', 'Electric', 'Portable', 'Precision welding for thin materials', 
 JSON_OBJECT('voltage_range', 'Input voltage options', 'output_current', 'Amperage range', 'tungsten_type', 'Electrode compatibility', 'current_type', 'AC/DC capability'),
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering')),

(@arc_welder_id, 'Stick', 'Metal', 'Electric', 'Portable', 'General purpose welding and field work', 
 JSON_OBJECT('voltage_range', 'Input voltage options', 'output_current', 'Amperage range', 'electrode_size', 'Stick electrode compatibility', 'duty_cycle', 'Operating capacity'),
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering')),

(@arc_welder_id, 'FCAW', 'Metal', 'Electric', 'Portable', 'High-productivity welding without gas', 
 JSON_OBJECT('voltage_range', 'Input voltage options', 'output_current', 'Amperage range', 'wire_diameter', 'Flux-cored wire sizes', 'wire_feed_speed', 'Feed rate range'),
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'));

-- DRILL SUBTYPES  
SET @drill_id = (SELECT id FROM equipment_types WHERE name = 'Drill' LIMIT 1);
INSERT IGNORE INTO equipment_subtypes (equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags) VALUES
(@drill_id, 'Electric', 'Metal/Wood', 'Corded', 'Standard', 'General drilling applications', 
 JSON_OBJECT('chuck_size', 'Chuck capacity', 'power_rating', 'Motor power', 'speed_range', 'Variable speed range', 'torque_settings', 'Clutch settings'),
 JSON_ARRAY('General', 'Mechanical Engineering')),

(@drill_id, 'Cordless', 'Metal/Wood', 'Battery', 'Compact', 'Portable drilling and fastening', 
 JSON_OBJECT('battery_voltage', 'Battery system', 'chuck_size', 'Chuck capacity', 'torque_rating', 'Max torque', 'speed_settings', 'Speed/torque modes'),
 JSON_ARRAY('General', 'Mechanical Engineering')),

(@drill_id, 'Hammer', 'Concrete/Masonry', 'Corded', 'Heavy-duty', 'Drilling in concrete and masonry', 
 JSON_OBJECT('chuck_type', 'SDS or keyed chuck', 'impact_rate', 'Blows per minute', 'drilling_diameter', 'Max hole diameter', 'power_rating', 'Motor power'),
 JSON_ARRAY('Civil Engineering', 'Structural Engineering')),

(@drill_id, 'Rotary Hammer', 'Concrete', 'Corded', 'Heavy-duty', 'Heavy concrete drilling and chiseling', 
 JSON_OBJECT('sds_type', 'SDS-Plus or SDS-Max', 'impact_energy', 'Joules per blow', 'drilling_diameter', 'Max core diameter', 'modes', 'Drill/hammer/chisel modes'),
 JSON_ARRAY('Civil Engineering', 'Structural Engineering')),

(@drill_id, 'Impact', 'Metal', 'Cordless', 'Compact', 'High-torque fastening applications', 
 JSON_OBJECT('torque_rating', 'Maximum torque', 'impacts_per_minute', 'Impact frequency', 'battery_voltage', 'Battery system', 'bit_holder', 'Bit retention system'),
 JSON_ARRAY('General', 'Mechanical Engineering'));

-- GRINDER SUBTYPES
SET @grinder_id = (SELECT id FROM equipment_types WHERE name = 'Grinder' LIMIT 1);
INSERT IGNORE INTO equipment_subtypes (equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags) VALUES
(@grinder_id, 'Angle', 'Metal/Stone', 'Corded', '4.5-9 inch', 'Cutting and grinding metal and masonry', 
 JSON_OBJECT('disc_diameter', 'Compatible disc sizes', 'power_rating', 'Motor power', 'speed_rpm', 'No-load speed', 'spindle_thread', 'Spindle size and thread'),
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering')),

(@grinder_id, 'Die', 'Metal', 'Pneumatic', 'Compact', 'Precision grinding and finishing', 
 JSON_OBJECT('collet_size', 'Tool holder size', 'speed_rpm', 'Operating speed range', 'air_pressure', 'Required air pressure', 'air_consumption', 'CFM requirements'),
 JSON_ARRAY('Mechanical Engineering')),

(@grinder_id, 'Bench', 'Metal', 'Corded', 'Stationary', 'Workshop grinding and sharpening', 
 JSON_OBJECT('wheel_diameter', 'Grinding wheel size', 'motor_power', 'Motor specifications', 'wheel_width', 'Grinding surface width', 'tool_rest', 'Adjustable tool rest'),
 JSON_ARRAY('Mechanical Engineering')),

(@grinder_id, 'Cut-off', 'Metal/Concrete', 'Corded', 'Portable', 'Precision cutting applications', 
 JSON_OBJECT('blade_diameter', 'Cutting disc size', 'cutting_depth', 'Maximum cut depth', 'power_rating', 'Motor power', 'dust_collection', 'Dust management system'),
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'));

-- SANDER SUBTYPES
SET @sander_id = (SELECT id FROM equipment_types WHERE name = 'Sander' LIMIT 1);
INSERT IGNORE INTO equipment_subtypes (equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags) VALUES
(@sander_id, 'Orbital', 'Wood/Metal', 'Corded', 'Compact', 'Fine finishing and surface preparation', 
 JSON_OBJECT('pad_size', 'Sanding pad dimensions', 'orbit_diameter', 'Orbital motion range', 'speed_opm', 'Orbits per minute', 'dust_collection', 'Dust collection capability'),
 JSON_ARRAY('Architectural', 'General')),

(@sander_id, 'Random Orbital', 'Wood/Metal', 'Corded', 'Standard', 'Smooth finishing without swirl marks', 
 JSON_OBJECT('pad_diameter', 'Sanding pad size', 'speed_opm', 'Variable speed range', 'dust_collection', 'Vacuum compatibility', 'hook_loop', 'Sandpaper attachment system'),
 JSON_ARRAY('Architectural', 'General')),

(@sander_id, 'Belt', 'Wood', 'Corded', 'Heavy-duty', 'Aggressive material removal', 
 JSON_OBJECT('belt_size', 'Sanding belt dimensions', 'belt_speed', 'Belt speed in FPM', 'power_rating', 'Motor power', 'dust_bag', 'Dust collection bag capacity'),
 JSON_ARRAY('Architectural', 'General')),

(@sander_id, 'Detail', 'Wood', 'Corded', 'Compact', 'Tight spaces and detailed work', 
 JSON_OBJECT('pad_shape', 'Triangular/mouse pad shape', 'oscillation_rate', 'Oscillations per minute', 'attachment_system', 'Sandpaper attachment method', 'accessories', 'Included attachments'),
 JSON_ARRAY('Architectural', 'General'));

-- Add more subtypes for other equipment types...

COMMIT;