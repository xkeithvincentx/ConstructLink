-- ==================================================================================
-- ConstructLink: Comprehensive Construction Equipment Classification Update
-- ==================================================================================
-- Date: 2025-01-13
-- Description: Add missing equipment types and subtypes for multi-discipline construction
-- Includes: Painting Tools, Plumbing, Fire Protection, HVAC, and Consumables
-- Developed by: Ranoa Digital Solutions
-- ==================================================================================

-- ==================================================================================
-- 1. ADD NEW CATEGORIES
-- ==================================================================================

-- Painting Tools Category
INSERT IGNORE INTO `categories` (
    `name`, `iso_code`, `description`, `is_consumable`, `generates_assets`, `asset_type`,
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'Painting Tools',
    'PT',
    'Painting and surface finishing tools and equipment',
    0, 1, 'capital', 0, 0.00,
    'Painting tools and equipment for surface preparation and coating application.'
);

-- Plumbing Materials Category
INSERT IGNORE INTO `categories` (
    `name`, `iso_code`, `description`, `is_consumable`, `generates_assets`, `asset_type`,
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'Plumbing Materials',
    'PL',
    'Plumbing pipes, fittings, fixtures, and materials',
    1, 1, 'inventory', 0, 0.00,
    'Plumbing materials and fixtures tracked as inventory, expensed when consumed.'
);

-- Fire Protection Category
INSERT IGNORE INTO `categories` (
    `name`, `iso_code`, `description`, `is_consumable`, `generates_assets`, `asset_type`,
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'Fire Protection',
    'FP',
    'Fire safety equipment and protection systems',
    0, 1, 'capital', 0, 0.00,
    'Fire protection equipment including extinguishers, hoses, sprinklers, and detection systems.'
);

-- HVAC/Mechanical Category
INSERT IGNORE INTO `categories` (
    `name`, `iso_code`, `description`, `is_consumable`, `generates_assets`, `asset_type`,
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'HVAC Equipment',
    'HV',
    'Heating, ventilation, and air conditioning equipment and materials',
    1, 1, 'inventory', 0, 0.00,
    'HVAC equipment and materials for climate control systems.'
);

-- ==================================================================================
-- 2. PAINTING TOOLS - Equipment Types and Subtypes
-- ==================================================================================

-- Get category ID for Painting Tools
SET @painting_cat_id = (SELECT id FROM categories WHERE iso_code = 'PT' OR name = 'Painting Tools' LIMIT 1);

-- Paint Brush Equipment Type
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@painting_cat_id, 'Paint Brush', 'Manual paint application brushes', 1);

SET @paint_brush_id = (SELECT id FROM equipment_types WHERE name = 'Paint Brush' AND category_id = @painting_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@paint_brush_id, 'Wall Brush', 'Natural/Synthetic Bristle', '4-6 inch', 'Large flat surfaces, walls, ceilings',
 '{"bristle_type": "Mixed", "handle": "Wooden/Plastic", "edge": "Flat"}',
 '["Painting", "Interior", "Walls"]'),
(@paint_brush_id, 'Trim Brush', 'Fine Synthetic Bristle', '1-3 inch', 'Trim work, edges, detailed areas',
 '{"bristle_type": "Synthetic", "handle": "Ergonomic", "edge": "Tapered"}',
 '["Painting", "Trim", "Detail Work"]'),
(@paint_brush_id, 'Angled Brush', 'Synthetic Bristle', '2-3 inch', 'Cutting in, corners, window frames',
 '{"bristle_type": "Synthetic", "handle": "Angled", "edge": "Angled"}',
 '["Painting", "Cutting", "Precision"]'),
(@paint_brush_id, 'Chip Brush', 'Natural Bristle', '1-4 inch', 'Touch-ups, glue application, cleanup',
 '{"bristle_type": "Natural", "handle": "Basic", "usage": "Disposable"}',
 '["Touch-up", "General Purpose", "Disposable"]');

-- Paint Roller Equipment Type
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@painting_cat_id, 'Paint Roller', 'Paint roller frames and covers', 1);

SET @paint_roller_id = (SELECT id FROM equipment_types WHERE name = 'Paint Roller' AND category_id = @painting_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@paint_roller_id, 'Standard Roller', 'Synthetic Fabric', '9 inch', 'Walls and ceilings, smooth to semi-rough surfaces',
 '{"nap_size": "3/8-1/2 inch", "core": "Plastic", "width": "9 inch"}',
 '["Painting", "Walls", "Interior"]'),
(@paint_roller_id, 'Mini Roller', 'Foam/Fabric', '4-6 inch', 'Small areas, trim, tight spaces',
 '{"nap_size": "1/4-3/8 inch", "core": "Plastic", "width": "4-6 inch"}',
 '["Painting", "Detail", "Small Areas"]'),
(@paint_roller_id, 'Texture Roller', 'Thick Fabric', '9 inch', 'Rough surfaces, stucco, textured walls',
 '{"nap_size": "3/4-1 inch", "core": "Heavy Duty", "width": "9 inch"}',
 '["Painting", "Texture", "Rough Surfaces"]'),
(@paint_roller_id, 'Foam Roller', 'High-Density Foam', '4-9 inch', 'Smooth surfaces, doors, cabinets',
 '{"material": "Foam", "finish": "Smooth", "width": "Variable"}',
 '["Painting", "Smooth Finish", "Cabinets"]');

-- Paint Sprayer Equipment Type
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@painting_cat_id, 'Paint Sprayer', 'Power paint application equipment', 1);

SET @paint_sprayer_id = (SELECT id FROM equipment_types WHERE name = 'Paint Sprayer' AND category_id = @painting_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `power_source`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@paint_sprayer_id, 'Airless Sprayer', 'Steel/Aluminum', 'Electric', 'Professional', 'Large projects, exterior painting, high-volume application',
 '{"pressure": "2000-3000 PSI", "flow_rate": "0.5+ GPM", "hose_length": "25-50 ft"}',
 '["Painting", "Exterior", "Commercial"]'),
(@paint_sprayer_id, 'HVLP Sprayer', 'Aluminum/Plastic', 'Electric/Compressed Air', 'Standard', 'Fine finish work, furniture, cabinets, automotive',
 '{"pressure": "Low", "transfer_efficiency": "65-90%", "tip_size": "Variable"}',
 '["Painting", "Fine Finish", "Interior"]'),
(@paint_sprayer_id, 'Compressed Air Sprayer', 'Aluminum', 'Compressed Air', 'Professional', 'Industrial painting, automotive, large surface coverage',
 '{"pressure": "40-80 PSI", "cup_size": "1-2 qt", "pattern": "Adjustable"}',
 '["Industrial", "Automotive", "Professional"]'),
(@paint_sprayer_id, 'Handheld Electric Sprayer', 'Plastic', 'Electric/Battery', 'Portable', 'Small projects, touch-ups, DIY applications',
 '{"power": "300-500W", "capacity": "1-1.5 qt", "weight": "Lightweight"}',
 '["DIY", "Touch-up", "Small Projects"]');

-- Caulking Gun Equipment Type
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@painting_cat_id, 'Caulking Gun', 'Sealant and adhesive application tools', 1);

SET @caulk_gun_id = (SELECT id FROM equipment_types WHERE name = 'Caulking Gun' AND category_id = @painting_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `power_source`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@caulk_gun_id, 'Manual Caulking Gun', 'Steel/Aluminum', 'Manual', 'Standard', 'General sealing, caulking, adhesive application',
 '{"thrust_ratio": "10:1-18:1", "tube_size": "10-12 oz", "type": "Ratchet/Smooth Rod"}',
 '["Sealing", "General Purpose", "Manual"]'),
(@caulk_gun_id, 'Pneumatic Caulking Gun', 'Aluminum', 'Compressed Air', 'Professional', 'High-volume sealing, commercial applications',
 '{"pressure": "80-120 PSI", "flow": "Continuous", "capacity": "10-30 oz"}',
 '["Professional", "High Volume", "Commercial"]'),
(@caulk_gun_id, 'Battery Caulking Gun', 'Aluminum/Plastic', 'Battery', 'Cordless', 'Continuous application, reduced fatigue, professional use',
 '{"voltage": "18-20V", "speed": "Variable", "capacity": "10-12 oz"}',
 '["Cordless", "Professional", "Ergonomic"]');

-- Putty Knife & Scraper Equipment Type
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@painting_cat_id, 'Putty Knife', 'Surface preparation and finishing tools', 1);

SET @putty_knife_id = (SELECT id FROM equipment_types WHERE name = 'Putty Knife' AND category_id = @painting_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@putty_knife_id, 'Flexible Putty Knife', 'Stainless Steel Blade', '2-6 inch', 'Filling cracks, applying filler, smoothing compound',
 '{"blade": "Flexible", "handle": "Wood/Plastic", "width": "Variable"}',
 '["Surface Prep", "Filling", "Smoothing"]'),
(@putty_knife_id, 'Stiff Scraper', 'Carbon Steel Blade', '3-6 inch', 'Paint removal, heavy scraping, surface preparation',
 '{"blade": "Rigid", "handle": "Heavy Duty", "edge": "Sharp"}',
 '["Scraping", "Paint Removal", "Prep"]'),
(@putty_knife_id, 'Drywall Knife', 'Stainless Steel', '6-12 inch', 'Drywall finishing, joint compound application, taping',
 '{"blade": "Wide/Flexible", "handle": "Ergonomic", "width": "6-12 inch"}',
 '["Drywall", "Finishing", "Joint Compound"]');

-- Paint Tray & Accessories
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@painting_cat_id, 'Paint Tray', 'Paint holding and rolling accessories', 1);

SET @paint_tray_id = (SELECT id FROM equipment_types WHERE name = 'Paint Tray' AND category_id = @painting_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@paint_tray_id, 'Standard Paint Tray', 'Plastic', '9 inch', 'Roller painting, paint loading, general use',
 '{"size": "9 inch roller", "depth": "Standard", "material": "Recyclable plastic"}',
 '["Painting", "Rolling", "Standard"]'),
(@paint_tray_id, 'Deep Well Paint Tray', 'Heavy-Duty Plastic', '9 inch', 'Thick paints, high-capacity loading',
 '{"size": "9 inch roller", "depth": "Deep", "capacity": "High"}',
 '["Painting", "Thick Paint", "High Capacity"]'),
(@paint_tray_id, 'Metal Paint Tray', 'Galvanized Steel', '9 inch', 'Professional use, durability, solvent resistance',
 '{"size": "9 inch roller", "material": "Steel", "finish": "Galvanized"}',
 '["Professional", "Durable", "Solvent Safe"]');

-- ==================================================================================
-- 3. PLUMBING MATERIALS - Equipment Types and Subtypes
-- ==================================================================================

SET @plumbing_cat_id = (SELECT id FROM categories WHERE iso_code = 'PL' OR name = 'Plumbing Materials' LIMIT 1);

-- PVC Pipe
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@plumbing_cat_id, 'PVC Pipe', 'Polyvinyl chloride piping material', 1);

SET @pvc_pipe_id = (SELECT id FROM equipment_types WHERE name = 'PVC Pipe' AND category_id = @plumbing_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@pvc_pipe_id, 'PVC Schedule 40', 'PVC', '1/2 - 6 inch', 'Cold water supply, drainage, irrigation',
 '{"schedule": "40", "pressure_rating": "Standard", "color": "White"}',
 '["Plumbing", "Water Supply", "Drainage"]'),
(@pvc_pipe_id, 'PVC Schedule 80', 'PVC', '1/2 - 6 inch', 'High-pressure applications, industrial use',
 '{"schedule": "80", "pressure_rating": "High", "color": "Gray"}',
 '["Plumbing", "High Pressure", "Industrial"]'),
(@pvc_pipe_id, 'PVC DWV', 'PVC', '1-1/2 - 4 inch', 'Drain, waste, and vent systems',
 '{"type": "DWV", "wall_thickness": "Thin", "color": "White"}',
 '["Plumbing", "Drainage", "Venting"]');

-- Pipe Fittings
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@plumbing_cat_id, 'Pipe Fitting', 'Pipe connection fittings and couplings', 1);

SET @fitting_id = (SELECT id FROM equipment_types WHERE name = 'Pipe Fitting' AND category_id = @plumbing_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@fitting_id, 'PVC Elbow', 'PVC', '1/2 - 4 inch', 'Direction changes, 90° and 45° bends',
 '{"angles": "90°/45°", "connection": "Slip/Threaded", "schedule": "40/80"}',
 '["Plumbing", "Fittings", "Direction Change"]'),
(@fitting_id, 'PVC Tee', 'PVC', '1/2 - 4 inch', 'Branch connections, three-way splits',
 '{"type": "Tee", "connection": "Slip/Threaded", "configuration": "Equal/Reducing"}',
 '["Plumbing", "Fittings", "Branch"]'),
(@fitting_id, 'PVC Coupling', 'PVC', '1/2 - 4 inch', 'Straight pipe connections, repairs',
 '{"type": "Coupling", "connection": "Slip", "repair": "Yes"}',
 '["Plumbing", "Fittings", "Connection"]'),
(@fitting_id, 'PVC Reducer', 'PVC', '3/4 - 4 inch', 'Pipe size transitions, flow reduction',
 '{"type": "Reducer", "configuration": "Bushing/Coupling", "sizes": "Variable"}',
 '["Plumbing", "Fittings", "Size Change"]');

-- Valves
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@plumbing_cat_id, 'Valve', 'Flow control and shut-off valves', 1);

SET @valve_id = (SELECT id FROM equipment_types WHERE name = 'Valve' AND category_id = @plumbing_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@valve_id, 'Ball Valve', 'Brass/PVC/Stainless', '1/2 - 4 inch', 'Quick shut-off, full flow control',
 '{"type": "Ball", "operation": "Quarter-turn", "flow": "Full bore"}',
 '["Plumbing", "Shut-off", "Control"]'),
(@valve_id, 'Gate Valve', 'Brass/Cast Iron', '1/2 - 6 inch', 'Main line shut-off, minimal pressure drop',
 '{"type": "Gate", "operation": "Multi-turn", "pressure_drop": "Minimal"}',
 '["Plumbing", "Main Line", "Shut-off"]'),
(@valve_id, 'Check Valve', 'Brass/PVC', '1/2 - 4 inch', 'Prevent backflow, one-way flow',
 '{"type": "Check", "operation": "Automatic", "direction": "One-way"}',
 '["Plumbing", "Backflow Prevention", "Safety"]'),
(@valve_id, 'Globe Valve', 'Brass/Bronze', '1/2 - 2 inch', 'Flow regulation, throttling',
 '{"type": "Globe", "operation": "Multi-turn", "control": "Fine adjustment"}',
 '["Plumbing", "Flow Control", "Regulation"]');

-- Plumbing Fixtures
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@plumbing_cat_id, 'Plumbing Fixture', 'Faucets, drains, and fixture components', 1);

SET @fixture_id = (SELECT id FROM equipment_types WHERE name = 'Plumbing Fixture' AND category_id = @plumbing_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@fixture_id, 'Sink Faucet', 'Chrome/Stainless', 'Standard', 'Kitchen and bathroom sinks',
 '{"type": "Faucet", "handles": "Single/Dual", "finish": "Chrome/Brushed"}',
 '["Plumbing", "Fixtures", "Faucet"]'),
(@fixture_id, 'Floor Drain', 'Cast Iron/PVC', '2 - 4 inch', 'Floor drainage, waste water removal',
 '{"type": "Floor Drain", "grate": "Included", "trap": "Built-in"}',
 '["Plumbing", "Drainage", "Floor"]'),
(@fixture_id, 'P-Trap', 'PVC/Chrome', '1-1/4 - 2 inch', 'Drain trap, odor prevention',
 '{"type": "P-Trap", "connection": "Slip joint", "adjustment": "Adjustable"}',
 '["Plumbing", "Trap", "Drainage"]');

-- Plumbing Adhesives & Sealants
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@plumbing_cat_id, 'Plumbing Adhesive', 'Pipe cement, sealants, and tape', 1);

SET @plumb_adhesive_id = (SELECT id FROM equipment_types WHERE name = 'Plumbing Adhesive' AND category_id = @plumbing_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@plumb_adhesive_id, 'PVC Cement', 'Solvent-based', '4 oz - 32 oz', 'PVC pipe bonding, permanent connections',
 '{"type": "Cement", "cure_time": "Fast", "color": "Clear/Blue"}',
 '["Plumbing", "PVC", "Bonding"]'),
(@plumb_adhesive_id, 'Pipe Thread Sealant', 'Paste/Liquid', '1/2 oz - 16 oz', 'Threaded connections, leak prevention',
 '{"type": "Sealant", "temp_range": "-65°F to 400°F", "application": "Brush/Apply"}',
 '["Plumbing", "Threads", "Sealing"]'),
(@plumb_adhesive_id, 'Teflon Tape', 'PTFE', '1/2 - 3/4 inch width', 'Thread sealing, leak prevention',
 '{"type": "Tape", "thickness": "Standard/Heavy", "color": "White/Yellow"}',
 '["Plumbing", "Tape", "Thread Seal"]');

-- ==================================================================================
-- 4. FIRE PROTECTION - Equipment Types and Subtypes
-- ==================================================================================

SET @fire_cat_id = (SELECT id FROM categories WHERE iso_code = 'FP' OR name = 'Fire Protection' LIMIT 1);

-- Fire Extinguishers
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@fire_cat_id, 'Fire Extinguisher', 'Portable fire suppression equipment', 1);

SET @fire_ext_id = (SELECT id FROM equipment_types WHERE name = 'Fire Extinguisher' AND category_id = @fire_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@fire_ext_id, 'ABC Fire Extinguisher', 'Dry Chemical', '5-20 lbs', 'Multi-purpose: wood, paper, flammable liquids, electrical',
 '{"type": "ABC", "agent": "Dry Chemical", "rating": "Variable", "recharge": "Yes"}',
 '["Fire Safety", "Multi-purpose", "ABC"]'),
(@fire_ext_id, 'CO2 Fire Extinguisher', 'Carbon Dioxide', '5-20 lbs', 'Electrical equipment, computer rooms, clean agent',
 '{"type": "CO2", "agent": "Carbon Dioxide", "rating": "BC", "residue": "None"}',
 '["Fire Safety", "Electrical", "Clean Agent"]'),
(@fire_ext_id, 'Water Fire Extinguisher', 'Water', '2.5 gal', 'Class A fires: wood, paper, cloth',
 '{"type": "Water", "agent": "Water", "rating": "A", "pressure": "Stored"}',
 '["Fire Safety", "Class A", "Water"]'),
(@fire_ext_id, 'Foam Fire Extinguisher', 'AFFF Foam', '2.5 gal', 'Flammable liquids, Class A and B fires',
 '{"type": "Foam", "agent": "AFFF", "rating": "AB", "coverage": "Film-forming"}',
 '["Fire Safety", "Foam", "Class AB"]');

-- Fire Hose & Accessories
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@fire_cat_id, 'Fire Hose', 'Fire hose and firefighting accessories', 1);

SET @fire_hose_id = (SELECT id FROM equipment_types WHERE name = 'Fire Hose' AND category_id = @fire_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@fire_hose_id, 'Fire Hose', 'Synthetic Fabric', '1.5 - 2.5 inch', 'Fire suppression, hose reel systems',
 '{"diameter": "1.5-2.5 inch", "length": "50-100 ft", "pressure": "250-300 PSI"}',
 '["Fire Safety", "Hose", "Suppression"]'),
(@fire_hose_id, 'Fire Nozzle', 'Brass/Aluminum', 'Standard', 'Water stream control, spray patterns',
 '{"type": "Nozzle", "pattern": "Straight/Fog", "shutoff": "Ball valve"}',
 '["Fire Safety", "Nozzle", "Control"]'),
(@fire_hose_id, 'Hose Coupling', 'Brass/Aluminum', '1.5 - 2.5 inch', 'Hose connections, quick connect',
 '{"type": "Coupling", "threads": "NST", "material": "Brass"}',
 '["Fire Safety", "Coupling", "Connection"]');

-- Sprinkler System Components
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@fire_cat_id, 'Sprinkler Component', 'Fire sprinkler system parts', 1);

SET @sprinkler_id = (SELECT id FROM equipment_types WHERE name = 'Sprinkler Component' AND category_id = @fire_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@sprinkler_id, 'Sprinkler Head', 'Brass/Bronze', '1/2 inch', 'Automatic fire suppression, ceiling/pendant mount',
 '{"type": "Sprinkler Head", "temperature": "135°F-200°F", "coverage": "Variable", "response": "Standard/Quick"}',
 '["Fire Safety", "Sprinkler", "Suppression"]'),
(@sprinkler_id, 'Alarm Valve', 'Cast Iron/Ductile', '2 - 8 inch', 'Water flow detection, alarm activation',
 '{"type": "Alarm Valve", "function": "Wet/Dry", "pressure": "175-300 PSI"}',
 '["Fire Safety", "Alarm", "Detection"]'),
(@sprinkler_id, 'Flow Switch', 'Brass/Plastic', '1 - 4 inch', 'Water flow monitoring, alarm triggering',
 '{"type": "Flow Switch", "activation": "Paddle/Vane", "signal": "Electrical"}',
 '["Fire Safety", "Detection", "Monitoring"]');

-- Fire Detection Equipment
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@fire_cat_id, 'Fire Detection', 'Smoke and heat detection equipment', 1);

SET @fire_detect_id = (SELECT id FROM equipment_types WHERE name = 'Fire Detection' AND category_id = @fire_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `power_source`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@fire_detect_id, 'Smoke Detector', 'Plastic/ABS', 'Battery/AC', 'Standard', 'Early fire detection, residential/commercial',
 '{"type": "Smoke", "sensing": "Ionization/Photoelectric", "alarm": "Built-in"}',
 '["Fire Safety", "Detection", "Smoke"]'),
(@fire_detect_id, 'Heat Detector', 'Metal/Plastic', 'AC/Battery', 'Standard', 'High-temperature detection, kitchens, garages',
 '{"type": "Heat", "activation": "Fixed/Rate-of-rise", "temp_rating": "135°F-200°F"}',
 '["Fire Safety", "Detection", "Heat"]'),
(@fire_detect_id, 'Fire Alarm Panel', 'Metal Enclosure', 'AC Power', 'Control Panel', 'System monitoring and control, addressable',
 '{"type": "Control Panel", "zones": "4-100+", "addressable": "Yes/No"}',
 '["Fire Safety", "Alarm", "Control"]');

-- ==================================================================================
-- 5. HVAC EQUIPMENT - Equipment Types and Subtypes
-- ==================================================================================

SET @hvac_cat_id = (SELECT id FROM categories WHERE iso_code = 'HV' OR name = 'HVAC Equipment' LIMIT 1);

-- Ducting
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@hvac_cat_id, 'Ducting', 'HVAC air distribution ductwork', 1);

SET @ducting_id = (SELECT id FROM equipment_types WHERE name = 'Ducting' AND category_id = @hvac_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@ducting_id, 'Galvanized Duct', 'Galvanized Steel', '4 - 24 inch', 'Main trunk lines, air distribution',
 '{"material": "Galvanized", "gauge": "26-22", "shape": "Round/Rectangular"}',
 '["HVAC", "Ducting", "Air Distribution"]'),
(@ducting_id, 'Flex Duct', 'Metalized Plastic', '4 - 12 inch', 'Branch connections, flexible routing',
 '{"material": "Flexible", "insulation": "R-4.2 to R-8", "length": "25 ft"}',
 '["HVAC", "Flexible", "Branch"]'),
(@ducting_id, 'Duct Fitting', 'Galvanized Steel', 'Variable', 'Elbows, tees, transitions, reducers',
 '{"type": "Fitting", "shapes": "Various", "gauge": "26-22"}',
 '["HVAC", "Fittings", "Connections"]');

-- Insulation
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@hvac_cat_id, 'HVAC Insulation', 'Thermal and acoustic insulation', 1);

SET @insulation_id = (SELECT id FROM equipment_types WHERE name = 'HVAC Insulation' AND category_id = @hvac_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@insulation_id, 'Duct Insulation', 'Fiberglass/Foam', '1 - 2 inch thick', 'Duct wrapping, thermal efficiency',
 '{"material": "Fiberglass", "R-value": "R-4 to R-8", "facing": "FSK/PSK"}',
 '["HVAC", "Insulation", "Thermal"]'),
(@insulation_id, 'Pipe Insulation', 'Foam/Fiberglass', '1/2 - 4 inch', 'Refrigerant lines, condensation prevention',
 '{"material": "Foam", "wall_thickness": "1/2 - 1 inch", "temp_range": "-40°F to 220°F"}',
 '["HVAC", "Pipe", "Condensation Control"]');

-- Grilles & Diffusers
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@hvac_cat_id, 'Air Grille', 'Air supply and return grilles', 1);

SET @grille_id = (SELECT id FROM equipment_types WHERE name = 'Air Grille' AND category_id = @hvac_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@grille_id, 'Supply Grille', 'Aluminum/Steel', '6x6 - 24x24 inch', 'Conditioned air supply, directional airflow',
 '{"type": "Supply", "finish": "White/Aluminum", "deflection": "Adjustable"}',
 '["HVAC", "Supply", "Air Distribution"]'),
(@grille_id, 'Return Grille', 'Aluminum/Steel', '12x12 - 24x24 inch', 'Return air collection, fixed blades',
 '{"type": "Return", "finish": "White/Aluminum", "pattern": "Fixed"}',
 '["HVAC", "Return", "Air Collection"]'),
(@grille_id, 'Ceiling Diffuser', 'Aluminum/Steel', '6 - 24 inch', 'Even air distribution, ceiling mount',
 '{"type": "Diffuser", "pattern": "Square/Round", "throw": "Variable"}',
 '["HVAC", "Diffuser", "Ceiling"]');

-- HVAC Equipment
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@hvac_cat_id, 'Air Conditioning Unit', 'Cooling and climate control equipment', 1);

SET @ac_unit_id = (SELECT id FROM equipment_types WHERE name = 'Air Conditioning Unit' AND category_id = @hvac_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `power_source`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@ac_unit_id, 'Split Type AC', 'Metal/Plastic', 'Electric', '0.5 - 5 Tons', 'Residential and small commercial cooling',
 '{"type": "Split", "refrigerant": "R410A/R32", "energy_rating": "SEER 13-25"}',
 '["HVAC", "Cooling", "Split System"]'),
(@ac_unit_id, 'Package AC Unit', 'Metal', 'Electric', '3 - 25 Tons', 'Commercial cooling, rooftop installation',
 '{"type": "Package", "configuration": "All-in-one", "cooling_only": "Yes/No"}',
 '["HVAC", "Commercial", "Package"]'),
(@ac_unit_id, 'Exhaust Fan', 'Metal/Plastic', 'Electric', '100 - 2000 CFM', 'Ventilation, air extraction, bathrooms/kitchens',
 '{"type": "Exhaust", "CFM": "Variable", "noise": "Low/Standard"}',
 '["HVAC", "Ventilation", "Exhaust"]');

-- ==================================================================================
-- 6. CONSTRUCTION MATERIALS - Add Missing Consumables
-- ==================================================================================

SET @const_mat_cat_id = (SELECT id FROM categories WHERE name = 'Construction Materials' LIMIT 1);

-- Concrete Materials
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@const_mat_cat_id, 'Concrete Mix', 'Ready-mix concrete and cement products', 1);

SET @concrete_id = (SELECT id FROM equipment_types WHERE name = 'Concrete Mix' AND category_id = @const_mat_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@concrete_id, 'Ready-Mix Concrete', 'Portland Cement Mix', 'Cubic Meter', 'Foundations, slabs, structural concrete',
 '{"strength": "3000-5000 PSI", "slump": "4-6 inch", "delivery": "Truck"}',
 '["Civil", "Concrete", "Structural"]'),
(@concrete_id, 'Cement Bags', 'Portland Cement', '40-94 lbs', 'Small pours, repairs, mortar mixing',
 '{"type": "Type I/II", "weight": "40kg/94lb", "coverage": "Variable"}',
 '["Civil", "Masonry", "Cement"]'),
(@concrete_id, 'Concrete Additive', 'Chemical Admixture', 'Liquid/Powder', 'Set retarding, accelerating, water reducing',
 '{"type": "Admixture", "function": "Variable", "dosage": "Per specs"}',
 '["Civil", "Concrete", "Enhancement"]');

-- Rebar & Steel
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@const_mat_cat_id, 'Reinforcing Bar', 'Steel reinforcement bars (rebar)', 1);

SET @rebar_id = (SELECT id FROM equipment_types WHERE name = 'Reinforcing Bar' AND category_id = @const_mat_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@rebar_id, 'Deformed Rebar', 'Carbon Steel', '#3 - #11 (10mm-36mm)', 'Concrete reinforcement, structural support',
 '{"grade": "Grade 40/60", "standard": "ASTM A615", "length": "20-40 ft"}',
 '["Civil", "Structural", "Reinforcement"]'),
(@rebar_id, 'Welded Wire Mesh', 'Steel Wire', '6x6 - 12x12 inch', 'Slab reinforcement, crack control',
 '{"pattern": "6x6 to 12x12", "wire_gauge": "W1.4-W4", "roll": "Variable"}',
 '["Civil", "Mesh", "Slab"]'),
(@rebar_id, 'Tie Wire', 'Annealed Steel', '16-18 gauge', 'Rebar tying, temporary fastening',
 '{"gauge": "16-18", "length": "Spool/Cut", "annealed": "Yes"}',
 '["Civil", "Fastening", "Tie"]');

-- Lumber
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@const_mat_cat_id, 'Lumber', 'Dimensional lumber and wood products', 1);

SET @lumber_id = (SELECT id FROM equipment_types WHERE name = 'Lumber' AND category_id = @const_mat_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@lumber_id, 'Dimensional Lumber', 'Softwood', '2x4 to 2x12', 'Framing, structural support, general construction',
 '{"sizes": "2x4, 2x6, 2x8, 2x10, 2x12", "length": "8-16 ft", "grade": "Select/Common"}',
 '["Civil", "Framing", "Structure"]'),
(@lumber_id, 'Plywood', 'Laminated Wood', '4x8 sheets', 'Sheathing, flooring, formwork',
 '{"thickness": "1/4 - 3/4 inch", "grade": "CDX, ACX", "size": "4x8 ft"}',
 '["Civil", "Sheathing", "Panels"]'),
(@lumber_id, 'Form Lumber', 'Softwood', '2x4 to 2x8', 'Concrete formwork, temporary structures',
 '{"sizes": "2x4, 2x6, 2x8", "length": "8-16 ft", "reusable": "Yes"}',
 '["Civil", "Formwork", "Concrete"]');

-- Fasteners
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@const_mat_cat_id, 'Fastener', 'Nails, screws, bolts, and anchors', 1);

SET @fastener_id = (SELECT id FROM equipment_types WHERE name = 'Fastener' AND category_id = @const_mat_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@fastener_id, 'Common Nails', 'Steel', '2d - 60d', 'General framing, wood construction',
 '{"type": "Common", "coating": "Bright/Galvanized", "length": "1 - 6 inch"}',
 '["Civil", "Fastening", "Framing"]'),
(@fastener_id, 'Wood Screws', 'Steel', '#6 - #12', 'Wood joining, deck construction, cabinetry',
 '{"type": "Wood Screw", "head": "Phillips/Square", "length": "1 - 3 inch"}',
 '["Civil", "Fastening", "Wood"]'),
(@fastener_id, 'Concrete Anchors', 'Steel', '1/4 - 3/4 inch', 'Masonry attachment, concrete fastening',
 '{"type": "Wedge/Sleeve", "material": "Steel/Stainless", "load": "Heavy Duty"}',
 '["Civil", "Anchoring", "Concrete"]'),
(@fastener_id, 'Bolts & Nuts', 'Steel', '1/4 - 1 inch', 'Structural connections, heavy-duty fastening',
 '{"type": "Hex Bolt", "grade": "Grade 5/8", "finish": "Zinc/Hot-dip"}',
 '["Civil", "Structural", "Fastening"]');

-- Adhesives & Sealants
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@const_mat_cat_id, 'Construction Adhesive', 'Adhesives, epoxies, and sealants', 1);

SET @adhesive_id = (SELECT id FROM equipment_types WHERE name = 'Construction Adhesive' AND category_id = @const_mat_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@adhesive_id, 'Construction Adhesive', 'Polyurethane/Latex', '10-28 oz tubes', 'General bonding, paneling, subflooring',
 '{"type": "Construction", "base": "Polyurethane", "strength": "Heavy Duty"}',
 '["Civil", "Bonding", "General"]'),
(@adhesive_id, 'Epoxy', 'Two-Part Resin', '1-5 gallon kits', 'Structural bonding, concrete repair, coatings',
 '{"type": "Epoxy", "mix_ratio": "1:1 to 2:1", "cure_time": "Variable"}',
 '["Civil", "Structural", "Repair"]'),
(@adhesive_id, 'Tile Mortar', 'Portland Cement', '50 lb bags', 'Tile installation, thinset bonding',
 '{"type": "Thinset", "modified": "Yes/No", "coverage": "Variable"}',
 '["Civil", "Tiling", "Installation"]'),
(@adhesive_id, 'Grout', 'Cement/Epoxy', '10-25 lb bags', 'Tile joints, crack filling, sealing',
 '{"type": "Grout", "sanded": "Yes/No", "color": "Variable"}',
 '["Civil", "Tiling", "Finishing"]');

-- Waterproofing & Sealants
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@const_mat_cat_id, 'Waterproofing Material', 'Waterproofing membranes and sealants', 1);

SET @waterproof_id = (SELECT id FROM equipment_types WHERE name = 'Waterproofing Material' AND category_id = @const_mat_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@waterproof_id, 'Silicone Sealant', 'Silicone', '10 oz tubes', 'Windows, doors, expansion joints, weatherproofing',
 '{"type": "Silicone", "cure": "Neutral/Acetoxy", "flexibility": "High"}',
 '["Civil", "Sealing", "Weatherproofing"]'),
(@waterproof_id, 'Polyurethane Sealant', 'Polyurethane', '10-20 oz tubes', 'Construction joints, concrete cracks, paintable',
 '{"type": "Polyurethane", "cure": "Moisture", "paintable": "Yes"}',
 '["Civil", "Sealing", "Joints"]'),
(@waterproof_id, 'Waterproofing Membrane', 'Modified Bitumen', 'Rolls', 'Foundation waterproofing, below-grade protection',
 '{"type": "Membrane", "thickness": "60-90 mils", "application": "Torch/Adhesive"}',
 '["Civil", "Waterproofing", "Foundation"]');

-- ==================================================================================
-- 7. ELECTRICAL SUPPLIES - Add Consumable Items
-- ==================================================================================

SET @elec_cat_id = (SELECT id FROM categories WHERE name = 'Electrical Supplies' AND is_consumable = 1 LIMIT 1);

-- Electrical Boxes
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@elec_cat_id, 'Electrical Box', 'Junction boxes, outlet boxes, switch boxes', 1);

SET @elec_box_id = (SELECT id FROM equipment_types WHERE name = 'Electrical Box' AND category_id = @elec_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@elec_box_id, 'Junction Box', 'PVC/Metal', '4x4 to 12x12 inch', 'Wire splicing, circuit connections, accessible points',
 '{"material": "PVC/Metal", "size": "Variable", "cover": "Included"}',
 '["Electrical", "Boxes", "Junction"]'),
(@elec_box_id, 'Outlet Box', 'PVC/Metal', 'Single/Double gang', 'Receptacle mounting, wall outlets',
 '{"gang": "1-4", "depth": "Standard/Deep", "mounting": "Old/New work"}',
 '["Electrical", "Boxes", "Outlet"]'),
(@elec_box_id, 'Switch Box', 'PVC/Metal', 'Single/Double gang', 'Switch mounting, wall switches',
 '{"gang": "1-4", "depth": "Standard/Deep", "ears": "Adjustable"}',
 '["Electrical", "Boxes", "Switch"]');

-- Electrical Connectors
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@elec_cat_id, 'Electrical Connector', 'Wire nuts, terminals, and connectors', 1);

SET @elec_conn_id = (SELECT id FROM equipment_types WHERE name = 'Electrical Connector' AND category_id = @elec_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@elec_conn_id, 'Wire Nut', 'Plastic', 'Small-Large', 'Twist-on wire connections, splicing',
 '{"size": "Small/Medium/Large", "color_coded": "Yes", "UL_listed": "Yes"}',
 '["Electrical", "Connectors", "Splicing"]'),
(@elec_conn_id, 'Crimp Connector', 'Copper/Aluminum', '14-10 AWG', 'Permanent wire termination, crimped connections',
 '{"type": "Ring/Spade/Butt", "wire_gauge": "14-10 AWG", "insulated": "Yes/No"}',
 '["Electrical", "Connectors", "Termination"]'),
(@elec_conn_id, 'Terminal Block', 'Plastic/Ceramic', '2-12 positions', 'Multiple wire connections, control panels',
 '{"positions": "Variable", "current": "10-30A", "voltage": "300-600V"}',
 '["Electrical", "Connectors", "Terminal"]');

-- Electrical Tape & Cable Management
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@elec_cat_id, 'Cable Management', 'Electrical tape, cable ties, and management', 1);

SET @cable_mgmt_id = (SELECT id FROM equipment_types WHERE name = 'Cable Management' AND category_id = @elec_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@cable_mgmt_id, 'Electrical Tape', 'Vinyl', '3/4 inch x 60 ft', 'Insulation, wire marking, protection',
 '{"width": "3/4 inch", "length": "60 ft", "voltage": "600V"}',
 '["Electrical", "Insulation", "Tape"]'),
(@cable_mgmt_id, 'Heat Shrink Tubing', 'Polyolefin', '1/8 - 2 inch', 'Wire insulation, strain relief, protection',
 '{"shrink_ratio": "2:1 to 4:1", "temp_rating": "125-200°C", "colors": "Various"}',
 '["Electrical", "Insulation", "Protection"]'),
(@cable_mgmt_id, 'Cable Tie', 'Nylon', '4 - 48 inch', 'Wire bundling, cable organization, securing',
 '{"length": "4-48 inch", "tensile": "18-175 lbs", "color": "Natural/Black"}',
 '["Electrical", "Organization", "Bundling"]');

-- Circuit Breakers
INSERT IGNORE INTO `equipment_types` (`category_id`, `name`, `description`, `is_active`)
VALUES (@elec_cat_id, 'Circuit Breaker', 'Overcurrent protection devices', 1);

SET @breaker_id = (SELECT id FROM equipment_types WHERE name = 'Circuit Breaker' AND category_id = @elec_cat_id LIMIT 1);

INSERT IGNORE INTO `equipment_subtypes`
(`equipment_type_id`, `subtype_name`, `material_type`, `size_category`, `application_area`, `technical_specs`, `discipline_tags`)
VALUES
(@breaker_id, 'Single Pole Breaker', 'Thermoplastic', '15-30A', '120V circuits, lighting, receptacles',
 '{"poles": "1", "voltage": "120V", "amperage": "15-30A", "interrupt": "10kA"}',
 '["Electrical", "Protection", "Circuit"]'),
(@breaker_id, 'Double Pole Breaker', 'Thermoplastic', '20-100A', '240V circuits, appliances, HVAC',
 '{"poles": "2", "voltage": "240V", "amperage": "20-100A", "interrupt": "10kA"}',
 '["Electrical", "Protection", "Heavy Load"]'),
(@breaker_id, 'GFCI Breaker', 'Thermoplastic', '15-30A', 'Ground fault protection, wet locations',
 '{"type": "GFCI", "voltage": "120/240V", "trip": "4-6mA", "test_button": "Yes"}',
 '["Electrical", "Protection", "GFCI"]'),
(@breaker_id, 'AFCI Breaker', 'Thermoplastic', '15-20A', 'Arc fault protection, bedrooms, living areas',
 '{"type": "AFCI", "voltage": "120V", "detection": "Arc fault", "combination": "Yes"}',
 '["Electrical", "Protection", "AFCI"]');

COMMIT;

-- ==================================================================================
-- END OF MIGRATION
-- ==================================================================================
