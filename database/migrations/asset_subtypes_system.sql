-- ConstructLink™ Hierarchical Asset Classification System
-- Comprehensive subtypes and specifications for construction equipment
-- Based on ISO 55000:2024 standards and industry best practices

-- =====================================================
-- Asset Type Categories (Equipment Classification)
-- =====================================================
CREATE TABLE IF NOT EXISTS asset_type_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(3) NOT NULL UNIQUE,  -- EQ, TO, VE, etc.
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- Asset Equipment Types (Main Types within Categories)
-- =====================================================
CREATE TABLE IF NOT EXISTS asset_equipment_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_code (category_id, code)
);

-- =====================================================
-- Asset Subtypes (Specific types within Equipment Types)
-- =====================================================
CREATE TABLE IF NOT EXISTS asset_subtypes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_type_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(15) NOT NULL,
    technical_name VARCHAR(150),
    description TEXT,
    discipline_tags JSON, -- Array of applicable disciplines
    specifications_template JSON, -- Template for common specifications
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_type_id) REFERENCES asset_equipment_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_type_code (equipment_type_id, code)
);

-- =====================================================
-- Asset Specification Templates (Common specs by subtype)
-- =====================================================
CREATE TABLE IF NOT EXISTS asset_specification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subtype_id INT NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    field_label VARCHAR(100) NOT NULL,
    field_type ENUM('text', 'number', 'select', 'multiselect', 'boolean') NOT NULL,
    field_options JSON, -- For select/multiselect fields
    is_required BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    unit VARCHAR(20), -- For measurements (V, A, mm, etc.)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subtype_id) REFERENCES asset_subtypes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subtype_field (subtype_id, field_name)
);

-- =====================================================
-- Asset Extended Properties (Actual specification values)
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
-- Insert Base Asset Type Categories
-- =====================================================
INSERT IGNORE INTO asset_type_categories (code, name, description) VALUES
('EQ', 'Equipment', 'Heavy machinery, specialized equipment, test equipment'),
('TO', 'Tools', 'Hand tools, power tools, measuring instruments'),
('VE', 'Vehicles', 'Construction vehicles, transport vehicles'),
('IN', 'Infrastructure', 'Site infrastructure, temporary structures'),
('SA', 'Safety Equipment', 'PPE, safety systems, emergency equipment'),
('IT', 'Information Technology', 'Computing, communication, software'),
('FU', 'Furniture & Fixtures', 'Office furniture, site furniture'),
('MA', 'Materials', 'Consumable supplies, bulk materials');

-- =====================================================
-- Insert Equipment Types
-- =====================================================

-- WELDING EQUIPMENT TYPES
INSERT IGNORE INTO asset_equipment_types (category_id, name, code, description) VALUES
(13, 'Arc Welding Equipment', 'ARC', 'Arc welding machines and related equipment'),
(13, 'Gas Welding Equipment', 'GAS', 'Gas welding and cutting equipment'),
(13, 'Specialty Welding Equipment', 'SPEC', 'Specialized welding processes and equipment'),
(13, 'Welding Accessories', 'ACC', 'Welding accessories and consumables');

-- POWER TOOLS TYPES (assuming Power Tools category ID)
INSERT IGNORE INTO asset_equipment_types (category_id, name, code, description) VALUES
(4, 'Grinding Tools', 'GRIND', 'Grinding and abrasive tools'),
(4, 'Drilling Tools', 'DRILL', 'Drilling and boring tools'),
(4, 'Sanding Tools', 'SAND', 'Surface preparation and finishing tools'),
(4, 'Cutting Tools', 'CUT', 'Power cutting tools'),
(4, 'Impact Tools', 'IMPACT', 'Impact and demolition tools');

-- HAND TOOLS TYPES
INSERT IGNORE INTO asset_equipment_types (category_id, name, code, description) VALUES
(5, 'Measuring Tools', 'MEAS', 'Precision measuring instruments'),
(5, 'Cutting Hand Tools', 'CUT-H', 'Manual cutting tools'),
(5, 'Assembly Tools', 'ASSY', 'Assembly and fastening tools'),
(5, 'Specialty Hand Tools', 'SPEC-H', 'Specialized manual tools');

-- =====================================================
-- Insert Welding Subtypes (Comprehensive)
-- =====================================================

-- Get the Arc Welding Equipment type ID
SET @arc_welding_id = (SELECT id FROM asset_equipment_types WHERE code = 'ARC' AND category_id = 13);

INSERT IGNORE INTO asset_subtypes (equipment_type_id, name, code, technical_name, description, discipline_tags, specifications_template) VALUES
(@arc_welding_id, 'MIG Welding Machine', 'MIG', 'Metal Inert Gas Welding (GMAW)', 'Gas Metal Arc Welding using consumable wire electrode', 
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'), 
 JSON_OBJECT('voltage', 'Voltage Range', 'current', 'Current Range', 'wire_diameter', 'Wire Diameter Range', 'gas_type', 'Shielding Gas Type')),

(@arc_welding_id, 'TIG Welding Machine', 'TIG', 'Tungsten Inert Gas Welding (GTAW)', 'Gas Tungsten Arc Welding using non-consumable tungsten electrode', 
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'), 
 JSON_OBJECT('voltage', 'Voltage Range', 'current', 'Current Range', 'tungsten_type', 'Tungsten Electrode Type', 'gas_type', 'Shielding Gas Type')),

(@arc_welding_id, 'Stick Welding Machine', 'STICK', 'Shielded Metal Arc Welding (SMAW)', 'Manual arc welding using flux-coated consumable electrode', 
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'), 
 JSON_OBJECT('voltage', 'Voltage Range', 'current', 'Current Range', 'electrode_size', 'Electrode Size Range', 'duty_cycle', 'Duty Cycle')),

(@arc_welding_id, 'Flux-Cored Welding Machine', 'FCAW', 'Flux-Cored Arc Welding', 'Arc welding using tubular wire filled with flux', 
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'), 
 JSON_OBJECT('voltage', 'Voltage Range', 'current', 'Current Range', 'wire_diameter', 'Wire Diameter Range', 'gas_type', 'Shielding Gas')),

(@arc_welding_id, 'Multi-Process Welder', 'MULTI', 'Multi-Process Welding Machine', 'Welding machine capable of MIG, TIG, and Stick welding', 
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'), 
 JSON_OBJECT('processes', 'Welding Processes', 'voltage', 'Voltage Range', 'current', 'Current Range', 'digital_display', 'Digital Display'));

-- Get the Gas Welding Equipment type ID
SET @gas_welding_id = (SELECT id FROM asset_equipment_types WHERE code = 'GAS' AND category_id = 13);

INSERT IGNORE INTO asset_subtypes (equipment_type_id, name, code, technical_name, description, discipline_tags, specifications_template) VALUES
(@gas_welding_id, 'Oxy-Acetylene Welder', 'OXY-ACE', 'Oxy-Acetylene Gas Welding', 'Gas welding using oxygen and acetylene', 
 JSON_ARRAY('Mechanical Engineering', 'Plumbing'), 
 JSON_OBJECT('oxygen_pressure', 'Oxygen Pressure', 'acetylene_pressure', 'Acetylene Pressure', 'torch_size', 'Torch Size Range')),

(@gas_welding_id, 'Plasma Cutter', 'PLASMA', 'Plasma Arc Cutting', 'Cutting using ionized gas plasma', 
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'), 
 JSON_OBJECT('current_range', 'Current Range', 'cut_thickness', 'Maximum Cut Thickness', 'air_pressure', 'Air Pressure Requirement'));

-- Get the Specialty Welding Equipment type ID
SET @spec_welding_id = (SELECT id FROM asset_equipment_types WHERE code = 'SPEC' AND category_id = 13);

INSERT IGNORE INTO asset_subtypes (equipment_type_id, name, code, technical_name, description, discipline_tags, specifications_template) VALUES
(@spec_welding_id, 'Spot Welder', 'SPOT', 'Resistance Spot Welding', 'Welding using electrical resistance and pressure', 
 JSON_ARRAY('Mechanical Engineering'), 
 JSON_OBJECT('current_range', 'Current Range', 'electrode_force', 'Electrode Force', 'throat_depth', 'Throat Depth')),

(@spec_welding_id, 'Submerged Arc Welder', 'SAW', 'Submerged Arc Welding', 'Arc welding under a layer of granular flux', 
 JSON_ARRAY('Structural Engineering'), 
 JSON_OBJECT('current_range', 'Current Range', 'wire_diameter', 'Wire Diameter', 'flux_type', 'Flux Type'));

-- =====================================================
-- Insert Grinder Subtypes
-- =====================================================

-- Get the Grinding Tools type ID
SET @grinding_id = (SELECT id FROM asset_equipment_types WHERE code = 'GRIND' AND category_id = 4);

INSERT IGNORE INTO asset_subtypes (equipment_type_id, name, code, technical_name, description, discipline_tags, specifications_template) VALUES
(@grinding_id, 'Angle Grinder', 'ANGLE', 'Angle Grinder', 'Portable grinder with rotating disc at right angle', 
 JSON_ARRAY('Mechanical Engineering', 'Structural Engineering'), 
 JSON_OBJECT('disc_size', 'Disc Diameter', 'power_rating', 'Power Rating', 'speed_rpm', 'No-Load Speed', 'spindle_thread', 'Spindle Thread')),

(@grinding_id, 'Die Grinder', 'DIE', 'Die Grinder', 'High-speed precision grinder for detail work', 
 JSON_ARRAY('Mechanical Engineering'), 
 JSON_OBJECT('collet_size', 'Collet Size', 'speed_rpm', 'Speed Range', 'power_rating', 'Power Rating')),

(@grinding_id, 'Straight Grinder', 'STRAIGHT', 'Straight Grinder', 'Linear grinder for straight grinding operations', 
 JSON_ARRAY('Mechanical Engineering'), 
 JSON_OBJECT('wheel_diameter', 'Wheel Diameter', 'spindle_length', 'Spindle Length', 'power_rating', 'Power Rating')),

(@grinding_id, 'Bench Grinder', 'BENCH', 'Bench Grinder', 'Stationary grinder mounted on workbench', 
 JSON_ARRAY('Mechanical Engineering'), 
 JSON_OBJECT('wheel_diameter', 'Wheel Diameter', 'motor_power', 'Motor Power', 'speed_rpm', 'Operating Speed'));

-- =====================================================
-- Insert Drilling Tool Subtypes
-- =====================================================

-- Get the Drilling Tools type ID
SET @drilling_id = (SELECT id FROM asset_equipment_types WHERE code = 'DRILL' AND category_id = 4);

INSERT IGNORE INTO asset_subtypes (equipment_type_id, name, code, technical_name, description, discipline_tags, specifications_template) VALUES
(@drilling_id, 'Hammer Drill', 'HAMMER', 'Hammer Drill', 'Drill with hammering action for masonry', 
 JSON_ARRAY('Civil Engineering', 'Structural Engineering'), 
 JSON_OBJECT('chuck_size', 'Chuck Capacity', 'power_rating', 'Power Rating', 'impact_rate', 'Impact Rate', 'sds_type', 'SDS Type')),

(@drilling_id, 'Impact Drill', 'IMPACT', 'Impact Drill', 'High-torque drill for heavy-duty applications', 
 JSON_ARRAY('Structural Engineering', 'Mechanical Engineering'), 
 JSON_OBJECT('torque_rating', 'Torque Rating', 'chuck_size', 'Chuck Capacity', 'speed_settings', 'Speed Settings')),

(@drilling_id, 'Rotary Hammer', 'ROTARY', 'Rotary Hammer', 'Heavy-duty hammer drill for concrete', 
 JSON_ARRAY('Civil Engineering', 'Structural Engineering'), 
 JSON_OBJECT('sds_type', 'SDS System', 'impact_energy', 'Impact Energy', 'drilling_diameter', 'Max Drilling Diameter')),

(@drilling_id, 'Core Drill', 'CORE', 'Core Drilling Machine', 'Specialized drill for large diameter holes', 
 JSON_ARRAY('Civil Engineering'), 
 JSON_OBJECT('core_diameter', 'Core Diameter Range', 'drilling_depth', 'Maximum Depth', 'motor_power', 'Motor Power'));

-- =====================================================
-- Insert Sanding Tool Subtypes
-- =====================================================

-- Get the Sanding Tools type ID
SET @sanding_id = (SELECT id FROM asset_equipment_types WHERE code = 'SAND' AND category_id = 4);

INSERT IGNORE INTO asset_subtypes (equipment_type_id, name, code, technical_name, description, discipline_tags, specifications_template) VALUES
(@sanding_id, 'Orbital Sander', 'ORBITAL', 'Orbital Sander', 'Sander with elliptical orbit motion', 
 JSON_ARRAY('Architectural', 'General'), 
 JSON_OBJECT('pad_size', 'Sanding Pad Size', 'orbit_diameter', 'Orbit Diameter', 'speed_opm', 'Orbits Per Minute')),

(@sanding_id, 'Random Orbital Sander', 'RO-SAND', 'Random Orbital Sander', 'Sander with random overlapping circular motion', 
 JSON_ARRAY('Architectural', 'General'), 
 JSON_OBJECT('pad_size', 'Pad Diameter', 'speed_opm', 'Orbits Per Minute', 'dust_collection', 'Dust Collection')),

(@sanding_id, 'Belt Sander', 'BELT', 'Belt Sander', 'Sander using continuous abrasive belt', 
 JSON_ARRAY('Architectural', 'General'), 
 JSON_OBJECT('belt_size', 'Belt Size', 'speed_fpm', 'Belt Speed (FPM)', 'power_rating', 'Power Rating')),

(@sanding_id, 'Disc Sander', 'DISC', 'Disc Sander', 'Sander with rotating abrasive disc', 
 JSON_ARRAY('Mechanical Engineering'), 
 JSON_OBJECT('disc_diameter', 'Disc Diameter', 'speed_rpm', 'Disc Speed', 'table_tilt', 'Table Tilt Range'));

-- =====================================================
-- Add subtype_id column to assets table
-- =====================================================
ALTER TABLE assets 
ADD COLUMN equipment_type_id INT NULL AFTER category_id,
ADD COLUMN subtype_id INT NULL AFTER equipment_type_id,
ADD FOREIGN KEY fk_assets_equipment_type (equipment_type_id) REFERENCES asset_equipment_types(id) ON DELETE SET NULL,
ADD FOREIGN KEY fk_assets_subtype (subtype_id) REFERENCES asset_subtypes(id) ON DELETE SET NULL;

-- =====================================================
-- Insert specification templates for welding machines
-- =====================================================

-- MIG Welder specifications
SET @mig_subtype_id = (SELECT id FROM asset_subtypes WHERE code = 'MIG');
INSERT IGNORE INTO asset_specification_templates (subtype_id, field_name, field_label, field_type, field_options, is_required, display_order, unit) VALUES
(@mig_subtype_id, 'voltage_range', 'Input Voltage', 'select', JSON_ARRAY('110V', '220V', '110V/220V', '380V'), TRUE, 1, 'V'),
(@mig_subtype_id, 'output_current', 'Output Current Range', 'text', NULL, TRUE, 2, 'A'),
(@mig_subtype_id, 'wire_diameter', 'Wire Diameter Range', 'text', NULL, FALSE, 3, 'mm'),
(@mig_subtype_id, 'shielding_gas', 'Compatible Shielding Gases', 'multiselect', JSON_ARRAY('CO2', 'Argon', 'Ar/CO2 Mix', 'Ar/O2 Mix'), FALSE, 4, NULL),
(@mig_subtype_id, 'duty_cycle', 'Duty Cycle', 'text', NULL, FALSE, 5, '%'),
(@mig_subtype_id, 'wire_feed_speed', 'Wire Feed Speed', 'text', NULL, FALSE, 6, 'IPM');

-- TIG Welder specifications
SET @tig_subtype_id = (SELECT id FROM asset_subtypes WHERE code = 'TIG');
INSERT IGNORE INTO asset_specification_templates (subtype_id, field_name, field_label, field_type, field_options, is_required, display_order, unit) VALUES
(@tig_subtype_id, 'voltage_range', 'Input Voltage', 'select', JSON_ARRAY('110V', '220V', '110V/220V', '380V'), TRUE, 1, 'V'),
(@tig_subtype_id, 'output_current', 'Output Current Range', 'text', NULL, TRUE, 2, 'A'),
(@tig_subtype_id, 'current_type', 'Current Type', 'select', JSON_ARRAY('AC', 'DC', 'AC/DC'), TRUE, 3, NULL),
(@tig_subtype_id, 'tungsten_diameter', 'Tungsten Electrode Diameter', 'text', NULL, FALSE, 4, 'mm'),
(@tig_subtype_id, 'shielding_gas', 'Shielding Gas Type', 'select', JSON_ARRAY('Argon', 'Helium', 'Ar/He Mix'), FALSE, 5, NULL),
(@tig_subtype_id, 'pulse_capability', 'Pulse Capability', 'boolean', NULL, FALSE, 6, NULL);

-- Angle Grinder specifications
SET @angle_subtype_id = (SELECT id FROM asset_subtypes WHERE code = 'ANGLE');
INSERT IGNORE INTO asset_specification_templates (subtype_id, field_name, field_label, field_type, field_options, is_required, display_order, unit) VALUES
(@angle_subtype_id, 'disc_diameter', 'Disc Diameter', 'select', JSON_ARRAY('100mm (4\")', '115mm (4.5\")', '125mm (5\")', '150mm (6\")', '180mm (7\")', '230mm (9\")', '300mm (12\")'), TRUE, 1, NULL),
(@angle_subtype_id, 'power_rating', 'Power Rating', 'text', NULL, TRUE, 2, 'W'),
(@angle_subtype_id, 'no_load_speed', 'No-Load Speed', 'text', NULL, FALSE, 3, 'RPM'),
(@angle_subtype_id, 'spindle_thread', 'Spindle Thread', 'select', JSON_ARRAY('M10', 'M14', '5/8\"-11', '1/2\"-13'), FALSE, 4, NULL),
(@angle_subtype_id, 'guard_type', 'Guard Type', 'select', JSON_ARRAY('Fixed', 'Adjustable', 'Quick Release'), FALSE, 5, NULL);

COMMIT;