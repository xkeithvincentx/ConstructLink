-- ConstructLink Equipment Classification System Update
-- Comprehensive brands, equipment types, and subtypes for multi-disciplinary construction

-- ==================================================
-- BRANDS - Construction Industry Comprehensive List
-- ==================================================

-- Power Tool Brands
INSERT IGNORE INTO asset_brands (official_name, variations, country, website, quality_tier) VALUES
('Hilti', '["HILTI","Hilty","Hiltie"]', 'Liechtenstein', 'https://www.hilti.com', 'premium'),
('Festool', '["FESTOOL","Festo","Festol"]', 'Germany', 'https://www.festool.com', 'premium'),
('Ridgid', '["RIDGID","Ridge","Rigid"]', 'USA', 'https://www.ridgid.com', 'mid-range'),
('Ryobi', '["RYOBI","Ryoby","Rioba"]', 'Japan', 'https://www.ryobitools.com', 'budget'),
('Porter-Cable', '["PORTER-CABLE","Porter Cable","PC"]', 'USA', 'https://www.portercable.com', 'mid-range'),
('Black & Decker', '["BLACK+DECKER","B&D","BD"]', 'USA', 'https://www.blackanddecker.com', 'budget'),
('Craftsman', '["CRAFTSMAN","Craftsmen"]', 'USA', 'https://www.craftsman.com', 'mid-range'),

-- Heavy Equipment Brands
('Komatsu', '["KOMATSU","Komats","Komatzu"]', 'Japan', 'https://www.komatsu.com', 'premium'),
('Volvo Construction', '["VOLVO","Volvo CE"]', 'Sweden', 'https://www.volvoce.com', 'premium'),
('JCB', '["J.C.B","JCB Limited"]', 'UK', 'https://www.jcb.com', 'premium'),
('Liebherr', '["LIEBHERR","Liebher"]', 'Switzerland', 'https://www.liebherr.com', 'premium'),
('Hyundai Heavy', '["HYUNDAI","Hyundai CE"]', 'South Korea', 'https://www.hyundai-ce.com', 'mid-range'),
('Doosan', '["DOOSAN","Dosan"]', 'South Korea', 'https://www.doosan.com', 'mid-range'),
('Case Construction', '["CASE","Case CE"]', 'USA', 'https://www.casece.com', 'mid-range'),
('New Holland', '["NEW HOLLAND","NH"]', 'Italy', 'https://www.newholland.com', 'mid-range'),

-- IT Equipment Brands
('Dell', '["DELL","Del"]', 'USA', 'https://www.dell.com', 'premium'),
('HP', '["HEWLETT PACKARD","Hewlett-Packard","HPE"]', 'USA', 'https://www.hp.com', 'premium'),
('Lenovo', '["LENOVO","Lenova"]', 'China', 'https://www.lenovo.com', 'premium'),
('ASUS', '["ASUS","Azus","Azuz"]', 'Taiwan', 'https://www.asus.com', 'premium'),
('Acer', '["ACER","Acer Inc"]', 'Taiwan', 'https://www.acer.com', 'mid-range'),
('Apple', '["APPLE","Mac","MacBook"]', 'USA', 'https://www.apple.com', 'premium'),
('MSI', '["MSI","Micro-Star"]', 'Taiwan', 'https://www.msi.com', 'premium'),

-- Welding Equipment Brands
('Miller Electric', '["MILLER","Miller Welders"]', 'USA', 'https://www.millerwelds.com', 'premium'),
('ESAB', '["ESAB","Esab Welding"]', 'Sweden', 'https://www.esab.com', 'premium'),
('Hobart', '["HOBART","Hobart Welders"]', 'USA', 'https://www.hobartbrothers.com', 'mid-range'),
('Everlast', '["EVERLAST","Everlast Power"]', 'USA', 'https://www.everlastgenerators.com', 'mid-range'),

-- Safety Equipment Brands
('3M', '["3M","3M Safety","MMM"]', 'USA', 'https://www.3m.com', 'premium'),
('Honeywell', '["HONEYWELL","Honey well"]', 'USA', 'https://www.honeywell.com', 'premium'),
('MSA Safety', '["MSA","Mine Safety"]', 'USA', 'https://www.msasafety.com', 'premium'),
('DuPont', '["DUPONT","Du Pont"]', 'USA', 'https://www.dupont.com', 'premium'),

-- Measuring/Testing Equipment Brands
('Fluke', '["FLUKE","Fluk"]', 'USA', 'https://www.fluke.com', 'premium'),
('Leica', '["LEICA","Leica Geosystems"]', 'Germany', 'https://www.leica-geosystems.com', 'premium'),
('Topcon', '["TOPCON","Topcon Positioning"]', 'Japan', 'https://www.topcon.com', 'premium'),
('Trimble', '["TRIMBLE","Trimble Inc"]', 'USA', 'https://www.trimble.com', 'premium'),
('Klein Tools', '["KLEIN","Klein Tool"]', 'USA', 'https://www.kleintools.com', 'mid-range'),

-- Office Equipment Brands
('Steelcase', '["STEELCASE","Steel case"]', 'USA', 'https://www.steelcase.com', 'premium'),
('Herman Miller', '["HERMAN MILLER","HM"]', 'USA', 'https://www.hermanmiller.com', 'premium'),
('Haworth', '["HAWORTH","Ha worth"]', 'USA', 'https://www.haworth.com', 'mid-range'),
('Canon', '["CANON","Canno"]', 'Japan', 'https://www.canon.com', 'premium'),
('Epson', '["EPSON","Epsn"]', 'Japan', 'https://www.epson.com', 'premium'),

-- Construction Material Brands
('Caterpillar Materials', '["CAT Materials","Cat Mat"]', 'USA', 'https://www.cat.com', 'premium'),
('Holcim', '["HOLCIM","Holcim Group"]', 'Switzerland', 'https://www.holcim.com', 'premium'),
('CEMEX', '["CEMEX","Cemx"]', 'Mexico', 'https://www.cemex.com', 'mid-range'),
('LafargeHolcim', '["LAFARGEHOLCIM","Lafarge"]', 'Switzerland', 'https://www.holcim.com', 'premium');

-- ==================================================
-- EQUIPMENT SUBTYPES - Logical Classifications
-- ==================================================

-- IT Equipment Subtypes (Laptops & Desktops with usage-based classification)
-- First, get the equipment type IDs for laptops and desktops
SET @laptop_type_id = (SELECT id FROM equipment_types WHERE name LIKE '%Laptop%' OR name LIKE '%Notebook%' LIMIT 1);
SET @desktop_type_id = (SELECT id FROM equipment_types WHERE name LIKE '%Desktop%' OR name LIKE '%PC%' OR name LIKE '%Computer%' LIMIT 1);

-- If laptop type doesn't exist, create it
INSERT IGNORE INTO equipment_types (category_id, name, description, is_active) 
SELECT 9, 'Laptop Computer', 'Portable computing devices', 1 
WHERE NOT EXISTS (SELECT 1 FROM equipment_types WHERE name LIKE '%Laptop%');

-- If desktop type doesn't exist, create it  
INSERT IGNORE INTO equipment_types (category_id, name, description, is_active)
SELECT 9, 'Desktop Computer', 'Stationary computing devices', 1
WHERE NOT EXISTS (SELECT 1 FROM equipment_types WHERE name LIKE '%Desktop%');

-- Update variables after potential inserts
SET @laptop_type_id = (SELECT id FROM equipment_types WHERE name LIKE '%Laptop%' LIMIT 1);
SET @desktop_type_id = (SELECT id FROM equipment_types WHERE name LIKE '%Desktop%' LIMIT 1);

-- Laptop Subtypes - Usage-based classification
INSERT IGNORE INTO equipment_subtypes 
(equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags) 
VALUES
(@laptop_type_id, 'Office Laptop', 'Plastic/Aluminum', 'Battery/AC', 'Standard', 'General office work, documentation, email, web browsing', 
 '{"min_ram": "8GB", "min_storage": "256GB SSD", "cpu_type": "i5 or equivalent", "screen_size": "13-15 inch"}',
 '["Administrative", "Office Work", "Documentation"]'),
 
(@laptop_type_id, 'Engineering Laptop', 'Aluminum/Carbon', 'Battery/AC', 'Performance', 'CAD work, AutoCAD, engineering calculations, technical documentation',
 '{"min_ram": "16GB", "min_storage": "512GB SSD", "cpu_type": "i7 or equivalent", "gpu": "Dedicated graphics", "screen_size": "15-17 inch"}',
 '["Engineering", "CAD", "Technical", "Design"]'),
 
(@laptop_type_id, 'Rendering Workstation Laptop', 'Aluminum/Magnesium', 'Battery/AC', 'High-Performance', '3D rendering, video editing, complex simulations, BIM modeling',
 '{"min_ram": "32GB", "min_storage": "1TB NVMe SSD", "cpu_type": "i9/Xeon", "gpu": "Professional graphics (Quadro/FirePro)", "screen_size": "15-17 inch", "color_accuracy": "99% sRGB"}',
 '["Rendering", "3D Modeling", "BIM", "Simulation", "Video Editing"]'),
 
(@laptop_type_id, 'Field Laptop', 'Ruggedized', 'Battery/AC', 'Ruggedized', 'On-site data collection, field inspections, outdoor use',
 '{"min_ram": "8GB", "min_storage": "256GB SSD", "durability": "IP65 rated", "screen": "Anti-glare", "battery": "Extended life"}',
 '["Field Work", "Site Inspection", "Data Collection", "Outdoor Use"]');

-- Desktop Subtypes - Usage-based classification  
INSERT IGNORE INTO equipment_subtypes
(equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags)
VALUES
(@desktop_type_id, 'Office Desktop', 'Steel/Plastic', 'AC Power', 'Mini/Small', 'General office work, documentation, basic computing tasks',
 '{"min_ram": "8GB", "min_storage": "256GB SSD", "cpu_type": "i5 or equivalent", "form_factor": "Mini/SFF"}',
 '["Administrative", "Office Work", "Documentation"]'),
 
(@desktop_type_id, 'Engineering Workstation', 'Steel/Aluminum', 'AC Power', 'Mid-Tower', 'CAD workstations, engineering software, technical analysis',
 '{"min_ram": "16GB", "min_storage": "512GB SSD + 1TB HDD", "cpu_type": "i7/Xeon", "gpu": "Professional graphics", "expandability": "High"}',
 '["Engineering", "CAD", "Technical Analysis", "Design"]'),
 
(@desktop_type_id, 'Rendering Workstation', 'Steel/Aluminum', 'AC Power', 'Full-Tower', 'High-end 3D rendering, complex simulations, video production',
 '{"min_ram": "64GB", "min_storage": "2TB NVMe SSD", "cpu_type": "Multi-core Xeon/Threadripper", "gpu": "Multiple professional GPUs", "cooling": "Advanced"}',
 '["Rendering", "3D Modeling", "Video Production", "Simulation", "BIM"]'),
 
(@desktop_type_id, 'Server Desktop', 'Steel', 'AC Power', 'Tower/Rack', 'File server, database server, local network services',
 '{"min_ram": "32GB", "min_storage": "Multiple drive bays", "cpu_type": "Server-grade", "redundancy": "PSU/Storage", "network": "Gigabit+"}',
 '["Server", "Network", "Database", "File Storage"]');

-- ==================================================
-- WELDING EQUIPMENT SUBTYPES
-- ==================================================

SET @arc_welder_id = (SELECT id FROM equipment_types WHERE name LIKE '%Arc%Weld%' OR name = 'Arc Welder' LIMIT 1);
SET @mig_welder_id = (SELECT id FROM equipment_types WHERE name LIKE '%MIG%' OR name LIKE '%GMAW%' LIMIT 1);
SET @tig_welder_id = (SELECT id FROM equipment_types WHERE name LIKE '%TIG%' OR name LIKE '%GTAW%' LIMIT 1);

-- Arc Welder Subtypes
INSERT IGNORE INTO equipment_subtypes
(equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags)
VALUES
(@arc_welder_id, 'Stick Welder - Light Duty', 'Steel/Plastic', 'Single Phase 110V', 'Portable', 'Light fabrication, repair work, thin materials',
 '{"amperage_range": "40-140A", "duty_cycle": "20%", "electrode_size": "1/16-5/32 inch"}',
 '["Fabrication", "Repair", "Light Duty"]'),
 
(@arc_welder_id, 'Stick Welder - Heavy Duty', 'Steel', 'Three Phase 220V/440V', 'Industrial', 'Heavy construction, structural welding, thick materials',
 '{"amperage_range": "40-400A", "duty_cycle": "60-100%", "electrode_size": "1/16-5/16 inch"}',
 '["Construction", "Structural", "Heavy Duty"]');

-- ==================================================
-- POWER TOOL SUBTYPES ENHANCEMENT
-- ==================================================

SET @drill_id = (SELECT id FROM equipment_types WHERE name LIKE '%Drill%' AND name NOT LIKE '%Press%' LIMIT 1);
SET @saw_id = (SELECT id FROM equipment_types WHERE name LIKE '%Saw%' OR name LIKE '%Cut%' LIMIT 1);

-- Drill Subtypes
INSERT IGNORE INTO equipment_subtypes
(equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags)
VALUES
(@drill_id, 'Cordless Drill - Light Duty', 'Plastic/Metal', 'Battery', 'Compact', 'General drilling, light screwing, home improvement',
 '{"voltage": "12V", "chuck_size": "3/8 inch", "torque": "Up to 300 in-lbs", "battery_life": "Basic"}',
 '["General", "Home Improvement", "Light Duty"]'),
 
(@drill_id, 'Cordless Drill - Professional', 'Metal/Composite', 'Battery', 'Standard', 'Professional construction, heavy-duty applications',
 '{"voltage": "18V-20V", "chuck_size": "1/2 inch", "torque": "Up to 800 in-lbs", "battery_life": "Extended"}',
 '["Professional", "Construction", "Heavy Duty"]'),
 
(@drill_id, 'Hammer Drill', 'Metal/Composite', 'Battery/Corded', 'Standard', 'Masonry drilling, concrete work, hammer action',
 '{"voltage": "18V-20V", "chuck_size": "1/2 inch", "hammer_action": "Yes", "impact_rate": "High"}',
 '["Masonry", "Concrete", "Construction"]');

-- ==================================================
-- MEASURING EQUIPMENT SUBTYPES
-- ==================================================

SET @laser_level_id = (SELECT id FROM equipment_types WHERE name LIKE '%Laser%Level%' OR name LIKE '%Level%' LIMIT 1);

INSERT IGNORE INTO equipment_subtypes
(equipment_type_id, subtype_name, material_type, power_source, size_category, application_area, technical_specs, discipline_tags)
VALUES
(@laser_level_id, 'Rotary Laser Level', 'Aluminum/Plastic', 'Battery', 'Professional', 'Site grading, foundation work, large area leveling',
 '{"range": "300-2000ft", "accuracy": "±1/8 inch per 100ft", "self_leveling": "Yes", "rotation_speed": "Variable"}',
 '["Surveying", "Grading", "Foundation", "Site Work"]'),
 
(@laser_level_id, 'Cross Line Laser', 'Plastic/Metal', 'Battery', 'Compact', 'Interior layout, tile work, cabinet installation',
 '{"range": "50-100ft", "accuracy": "±1/8 inch per 30ft", "lines": "Horizontal/Vertical", "mounting": "Tripod/Magnetic"}',
 '["Interior", "Layout", "Installation", "Finishing"]');

COMMIT;