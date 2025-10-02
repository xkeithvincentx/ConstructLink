-- ConstructLink™ Comprehensive Construction Industry Data
-- Real-world brands, disciplines, and asset types for construction management
-- Based on industry standards and actual construction practices
-- Author: Claude Code Assistant
-- Version: 2.0.0

USE constructlink_db;

-- ================================================================================
-- COMPREHENSIVE CONSTRUCTION DISCIPLINES HIERARCHY
-- Based on Construction Industry Institute (CII) and international standards
-- ================================================================================

-- Clear existing discipline data (optional - remove if you want to keep existing)
-- DELETE FROM asset_discipline_mappings;
-- DELETE FROM asset_disciplines WHERE parent_id IS NOT NULL;
-- DELETE FROM asset_disciplines WHERE parent_id IS NULL;

-- Insert comprehensive main disciplines
INSERT INTO asset_disciplines (code, name, description, iso_classification, display_order) VALUES
-- Core Construction Disciplines
('CIVIL', 'Civil Engineering', 'Structural and infrastructure engineering including foundations, roads, bridges', 'ISO 12006-2:2015', 1),
('STRUCT', 'Structural Engineering', 'Building structures, steel and concrete frameworks, load-bearing systems', 'ISO 12006-2:2015', 2),
('ARCH', 'Architectural', 'Building design, finishes, facades, interior systems', 'ISO 12006-2:2015', 3),
('MECH', 'Mechanical Engineering', 'Mechanical systems, equipment installation and maintenance', 'ISO 12006-2:2015', 4),
('ELEC', 'Electrical Engineering', 'Electrical systems, power distribution, lighting, communications', 'ISO 12006-2:2015', 5),
('PLUMB', 'Plumbing', 'Water supply, drainage, sewage systems, pipes and fixtures', 'ISO 12006-2:2015', 6),
('HVAC', 'HVAC Systems', 'Heating, ventilation, air conditioning, climate control', 'ISO 12006-2:2015', 7),

-- Specialized Construction Disciplines
('GEOTECH', 'Geotechnical', 'Soil mechanics, foundations, earthworks, ground improvement', 'ISO 14688', 8),
('SURVEY', 'Surveying', 'Land surveying, setting out, topographic mapping, GPS systems', 'ISO 17123', 9),
('ENV', 'Environmental', 'Environmental compliance, waste management, remediation', 'ISO 14001', 10),
('SAFETY', 'Safety & Health', 'Occupational health and safety, personal protective equipment', 'ISO 45001', 11),
('QUALITY', 'Quality Control', 'Quality assurance, testing, inspection, compliance', 'ISO 9001', 12),

-- Specialized Trades
('MASONRY', 'Masonry', 'Brickwork, blockwork, stone work, concrete masonry units', null, 13),
('CARPENTRY', 'Carpentry', 'Formwork, framing, finish carpentry, wooden structures', null, 14),
('ROOFING', 'Roofing', 'Roof systems, waterproofing, insulation, gutters', null, 15),
('GLAZING', 'Glazing', 'Windows, curtain walls, glass installation, sealing', null, 16),
('FLOORING', 'Flooring', 'Floor finishes, underlayments, specialized flooring systems', null, 17),
('PAINTING', 'Painting & Finishes', 'Protective coatings, decorative finishes, surface preparation', null, 18),
('LAND', 'Landscaping', 'Site preparation, irrigation, hardscaping, plantings', null, 19),

-- Infrastructure & Utilities
('ROADS', 'Roads & Highways', 'Pavement, traffic systems, road marking, signage', null, 20),
('BRIDGES', 'Bridges', 'Bridge construction, cable systems, expansion joints', null, 21),
('TUNNELS', 'Tunnels & Underground', 'Underground construction, ventilation, support systems', null, 22),
('WATER', 'Water Systems', 'Water treatment, distribution, storage, pumping systems', null, 23),
('SEWER', 'Sewerage', 'Sewage collection, treatment, stormwater management', null, 24),
('TELECOM', 'Telecommunications', 'Data networks, fiber optics, communication infrastructure', null, 25),

-- Marine & Specialized
('MARINE', 'Marine Construction', 'Ports, harbors, underwater construction, coastal works', null, 26),
('RAILWAY', 'Railway', 'Track laying, signaling, railway infrastructure', null, 27),
('AIRPORT', 'Airport Construction', 'Runways, terminal buildings, specialized airport systems', null, 28),

-- Support Functions
('LOGISTICS', 'Construction Logistics', 'Material handling, site logistics, temporary facilities', null, 29),
('IT_CONST', 'Construction IT', 'Construction software, BIM, project management systems', null, 30),
('GENERAL', 'General Construction', 'Multi-trade work, general contracting, site preparation', null, 31);

-- Insert detailed sub-disciplines
-- Civil Engineering Sub-disciplines
INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'CIVIL_FOUND', 'Foundations', 'Deep foundations, shallow foundations, piling, underpinning', id, 1 FROM asset_disciplines WHERE code = 'CIVIL';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'CIVIL_EARTH', 'Earthworks', 'Excavation, grading, embankments, retaining walls', id, 2 FROM asset_disciplines WHERE code = 'CIVIL';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'CIVIL_CONC', 'Concrete Works', 'Concrete mixing, placing, finishing, precast elements', id, 3 FROM asset_disciplines WHERE code = 'CIVIL';

-- Structural Engineering Sub-disciplines  
INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'STRUCT_STEEL', 'Structural Steel', 'Steel fabrication, welding, erection, connections', id, 1 FROM asset_disciplines WHERE code = 'STRUCT';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'STRUCT_CONC', 'Reinforced Concrete', 'Rebar installation, concrete structures, post-tensioning', id, 2 FROM asset_disciplines WHERE code = 'STRUCT';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'STRUCT_WOOD', 'Wood Frame', 'Timber construction, engineered wood, connections', id, 3 FROM asset_disciplines WHERE code = 'STRUCT';

-- Mechanical Sub-disciplines
INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'MECH_PIPING', 'Piping Systems', 'Process piping, steam systems, compressed air', id, 1 FROM asset_disciplines WHERE code = 'MECH';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'MECH_EQUIP', 'Equipment Installation', 'Machinery installation, alignment, commissioning', id, 2 FROM asset_disciplines WHERE code = 'MECH';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'MECH_FIRE', 'Fire Protection', 'Sprinkler systems, fire pumps, suppression systems', id, 3 FROM asset_disciplines WHERE code = 'MECH';

-- Electrical Sub-disciplines
INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'ELEC_POWER', 'Power Systems', 'High voltage, transformers, switchgear, generators', id, 1 FROM asset_disciplines WHERE code = 'ELEC';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'ELEC_LIGHT', 'Lighting Systems', 'Interior/exterior lighting, controls, emergency lighting', id, 2 FROM asset_disciplines WHERE code = 'ELEC';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'ELEC_CONTROL', 'Control Systems', 'Automation, instrumentation, building management systems', id, 3 FROM asset_disciplines WHERE code = 'ELEC';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'ELEC_COMM', 'Communications', 'Data networks, security systems, audio/visual', id, 4 FROM asset_disciplines WHERE code = 'ELEC';

-- HVAC Sub-disciplines
INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'HVAC_AIR', 'Air Systems', 'Ductwork, air handling units, ventilation', id, 1 FROM asset_disciplines WHERE code = 'HVAC';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'HVAC_HEAT', 'Heating Systems', 'Boilers, radiators, underfloor heating, heat pumps', id, 2 FROM asset_disciplines WHERE code = 'HVAC';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'HVAC_COOL', 'Cooling Systems', 'Chillers, cooling towers, refrigeration', id, 3 FROM asset_disciplines WHERE code = 'HVAC';

-- Plumbing Sub-disciplines
INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'PLUMB_WATER', 'Water Supply', 'Potable water systems, pumps, storage tanks', id, 1 FROM asset_disciplines WHERE code = 'PLUMB';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'PLUMB_DRAIN', 'Drainage', 'Waste water, storm drains, sump pumps', id, 2 FROM asset_disciplines WHERE code = 'PLUMB';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'PLUMB_GAS', 'Gas Systems', 'Natural gas, LPG systems, gas appliances', id, 3 FROM asset_disciplines WHERE code = 'PLUMB';

-- Safety Sub-disciplines
INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'SAFETY_PPE', 'Personal Protective Equipment', 'Hard hats, safety harnesses, protective clothing', id, 1 FROM asset_disciplines WHERE code = 'SAFETY';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'SAFETY_FALL', 'Fall Protection', 'Scaffolding, safety nets, anchor points', id, 2 FROM asset_disciplines WHERE code = 'SAFETY';

INSERT INTO asset_disciplines (code, name, description, parent_id, display_order) 
SELECT 'SAFETY_FIRE', 'Fire Safety', 'Fire extinguishers, emergency equipment, evacuation', id, 3 FROM asset_disciplines WHERE code = 'SAFETY';

-- ================================================================================
-- COMPREHENSIVE REAL-WORLD CONSTRUCTION BRANDS
-- Based on actual market leaders and commonly used brands
-- ================================================================================

-- Clear existing brand data (optional)
-- DELETE FROM asset_brands;

-- Insert comprehensive brand database
INSERT INTO asset_brands (official_name, variations, country, website, quality_tier, common_products, is_verified) VALUES

-- POWER TOOLS - Premium Tier
('Makita', '["makita","MAKITA","Mkita","Makitta","Maquita"]', 'Japan', 'makita.com', 'premium', 
 '["Cordless Drills", "Angle Grinders", "Circular Saws", "Impact Drivers", "Rotary Hammers"]', 1),

('DeWalt', '["dewalt","DEWALT","Dewalt","De Walt","D-Walt","Dwalt"]', 'USA', 'dewalt.com', 'premium',
 '["Power Drills", "Table Saws", "Miter Saws", "Impact Wrenches", "Reciprocating Saws"]', 1),

('Bosch', '["bosch","BOSCH","Bosh","Bosch Professional","Bosch Blue"]', 'Germany', 'bosch.com', 'premium',
 '["Hammer Drills", "Laser Levels", "Oscillating Tools", "Jig Saws", "Angle Grinders"]', 1),

('Hilti', '["hilti","HILTI","Hlti","Hiltie"]', 'Liechtenstein', 'hilti.com', 'premium',
 '["Hammer Drills", "Core Drills", "Anchoring Systems", "Laser Tools", "Diamond Tools"]', 1),

('Milwaukee', '["milwaukee","MILWAUKEE","Milwakee","Milwauke","Milwaukee Tool"]', 'USA', 'milwaukeetool.com', 'premium',
 '["Impact Drivers", "Pipe Threaders", "Band Saws", "Grinders", "Oscillating Tools"]', 1),

('Festool', '["festool","FESTOOL","Fes tool","Fest tool"]', 'Germany', 'festool.com', 'premium',
 '["Sanders", "Routers", "Track Saws", "Dust Extractors", "Domino Jointers"]', 1),

('Metabo', '["metabo","METABO","Metaboo","Mitabo"]', 'Germany', 'metabo.com', 'premium',
 '["Angle Grinders", "Impact Wrenches", "Magnetic Drills", "Cut-off Machines"]', 1),

-- POWER TOOLS - Standard Tier  
('Stanley', '["stanley","STANLEY","Stanly","Stanley Black & Decker"]', 'USA', 'stanley.com', 'standard',
 '["Hand Tools", "Measuring Tools", "Storage Solutions", "Power Tools"]', 1),

('Black & Decker', '["black and decker","black & decker","BLACK+DECKER","B&D","black decker"]', 'USA', 'blackanddecker.com', 'standard',
 '["Power Drills", "Sanders", "Jig Saws", "Workmates", "Lawn Tools"]', 1),

('Ridgid', '["ridgid","RIDGID","Rigid","Ridge"]', 'USA', 'ridgid.com', 'standard',
 '["Pipe Tools", "Shop Vacuums", "Table Saws", "Plumbing Tools"]', 1),

('Porter-Cable', '["porter cable","porter-cable","PORTER-CABLE","PC"]', 'USA', 'portercable.com', 'standard',
 '["Nail Guns", "Sanders", "Routers", "Compressors"]', 1),

-- POWER TOOLS - Economy Tier
('Ryobi', '["ryobi","RYOBI","Riobi","Ryobe"]', 'Japan', 'ryobitools.com', 'economy',
 '["Cordless Tools", "Outdoor Power Equipment", "Specialty Tools"]', 1),

('Craftsman', '["craftsman","CRAFTSMAN","Crafsman","Craftman"]', 'USA', 'craftsman.com', 'economy',
 '["Hand Tools", "Power Tools", "Tool Storage", "Lawn Equipment"]', 1),

('Harbor Freight Tools', '["harbor freight","chicago electric","central machinery","warrior"]', 'USA', 'harborfreight.com', 'economy',
 '["Power Tools", "Hand Tools", "Shop Equipment", "Automotive Tools"]', 1),

-- HEAVY EQUIPMENT & MACHINERY
('Caterpillar', '["caterpillar","CAT","cat","Caterpiller","Cat Equipment"]', 'USA', 'cat.com', 'premium',
 '["Excavators", "Bulldozers", "Wheel Loaders", "Generators", "Compactors"]', 1),

('Komatsu', '["komatsu","KOMATSU","Komutsu"]', 'Japan', 'komatsu.com', 'premium',
 '["Excavators", "Dump Trucks", "Wheel Loaders", "Bulldozers"]', 1),

('Volvo Construction Equipment', '["volvo","VOLVO","volvo CE","volvo construction"]', 'Sweden', 'volvoce.com', 'premium',
 '["Articulated Haulers", "Excavators", "Wheel Loaders", "Compactors"]', 1),

('JCB', '["jcb","JCB"]', 'UK', 'jcb.com', 'premium',
 '["Backhoe Loaders", "Excavators", "Skid Steers", "Telehandlers"]', 1),

('John Deere', '["john deere","deere","DEERE","john deer"]', 'USA', 'deere.com', 'premium',
 '["Excavators", "Skid Steers", "Utility Tractors", "Compactors"]', 1),

('Bobcat', '["bobcat","BOBCAT"]', 'USA', 'bobcat.com', 'standard',
 '["Skid Steer Loaders", "Excavators", "Telehandlers", "Utility Vehicles"]', 1),

-- CONCRETE & MASONRY
('STIHL', '["stihl","STIHL","Still","Stil"]', 'Germany', 'stihl.com', 'premium',
 '["Concrete Saws", "Cut-off Machines", "Chainsaws", "Blowers"]', 1),

('Husqvarna', '["husqvarna","HUSQVARNA","husquarna","husqvana"]', 'Sweden', 'husqvarana.com', 'premium',
 '["Concrete Saws", "Floor Grinders", "Diamond Tools", "Chainsaws"]', 1),

('Partner', '["partner","PARTNER"]', 'Sweden', 'partner.com', 'standard',
 '["Concrete Saws", "Cut-off Machines", "Floor Saws"]', 1),

-- WELDING EQUIPMENT
('Lincoln Electric', '["lincoln","lincoln electric","LINCOLN","lincoln welder"]', 'USA', 'lincolnelectric.com', 'premium',
 '["Arc Welders", "MIG Welders", "TIG Welders", "Plasma Cutters"]', 1),

('Miller', '["miller","MILLER","miller welder","miller electric"]', 'USA', 'millerwelds.com', 'premium',
 '["Welding Machines", "Plasma Cutters", "Welding Accessories"]', 1),

('ESAB', '["esab","ESAB"]', 'Sweden', 'esab.com', 'premium',
 '["Cutting Equipment", "Welding Consumables", "Automation"]', 1),

-- MEASURING & SURVEYING
('Leica Geosystems', '["leica","LEICA","leica geosystems","lica"]', 'Switzerland', 'leica-geosystems.com', 'premium',
 '["Total Stations", "Laser Scanners", "GNSS Systems", "Laser Levels"]', 1),

('Trimble', '["trimble","TRIMBLE"]', 'USA', 'trimble.com', 'premium',
 '["GPS Systems", "Total Stations", "Laser Levels", "Construction Software"]', 1),

('Topcon', '["topcon","TOPCON"]', 'Japan', 'topcon.com', 'premium',
 '["Total Stations", "Laser Levels", "GPS Systems", "Machine Control"]', 1),

('Spectra Precision', '["spectra","spectra precision","SPECTRA"]', 'USA', 'spectraprecision.com', 'standard',
 '["Laser Levels", "Total Stations", "GPS Systems"]', 1),

-- SAFETY EQUIPMENT
('3M', '["3m","3M","three m"]', 'USA', '3m.com', 'premium',
 '["Safety Harnesses", "Respirators", "Hearing Protection", "Fall Protection"]', 1),

('MSA Safety', '["msa","MSA","MSA Safety"]', 'USA', 'msasafety.com', 'premium',
 '["Hard Hats", "Gas Detectors", "Fall Protection", "Respirators"]', 1),

('Honeywell', '["honeywell","HONEYWELL"]', 'USA', 'honeywell.com', 'premium',
 '["Safety Equipment", "Gas Detection", "Personal Protective Equipment"]', 1),

-- LIFTING & MATERIAL HANDLING
('Genie', '["genie","GENIE"]', 'USA', 'genielift.com', 'premium',
 '["Aerial Work Platforms", "Scissor Lifts", "Boom Lifts", "Material Lifts"]', 1),

('JLG', '["jlg","JLG"]', 'USA', 'jlg.com', 'premium',
 '["Boom Lifts", "Scissor Lifts", "Telehandlers", "Low Level Access"]', 1),

('Manitou', '["manitou","MANITOU"]', 'France', 'manitou.com', 'standard',
 '["Telehandlers", "Aerial Work Platforms", "Forklifts"]', 1),

-- COMPACTION & EARTHMOVING
('Wacker Neuson', '["wacker","wacker neuson","WACKER","neuson"]', 'Germany', 'wackerneuson.com', 'premium',
 '["Plate Compactors", "Rammers", "Rollers", "Excavators"]', 1),

('Weber', '["weber","WEBER","weber mt"]', 'Germany', 'weber-mt.com', 'standard',
 '["Plate Compactors", "Rammers", "Screeds"]', 1),

-- GENERATORS & POWER
('Generac', '["generac","GENERAC"]', 'USA', 'generac.com', 'standard',
 '["Portable Generators", "Standby Generators", "Mobile Generators"]', 1),

('Honda Power Equipment', '["honda","HONDA","honda power"]', 'Japan', 'honda.com', 'premium',
 '["Generators", "Water Pumps", "Pressure Washers", "Engines"]', 1),

('Yamaha', '["yamaha","YAMAHA"]', 'Japan', 'yamaha.com', 'premium',
 '["Generators", "Water Pumps", "Engines"]', 1),

-- PUMPS & WATER EQUIPMENT  
('Grundfos', '["grundfos","GRUNDFOS"]', 'Denmark', 'grundfos.com', 'premium',
 '["Water Pumps", "Sewage Pumps", "Submersible Pumps"]', 1),

('Xylem', '["xylem","XYLEM","flygt","goulds"]', 'USA', 'xylem.com', 'premium',
 '["Water Pumps", "Treatment Systems", "Dewatering Equipment"]', 1),

-- RENTAL & SPECIALTY BRANDS
('United Rentals', '["united rentals","united","UR"]', 'USA', 'unitedrentals.com', 'standard',
 '["Equipment Rental", "Specialized Tools", "Construction Equipment"]', 1),

('Home Depot', '["home depot","HD","homedepot"]', 'USA', 'homedepot.com', 'standard',
 '["Husky Tools", "Ryobi", "Ridgid", "Construction Supplies"]', 1),

-- INTERNATIONAL BRANDS
('Ingersoll Rand', '["ingersoll rand","IR","ingersoll-rand"]', 'USA', 'ingersollrand.com', 'premium',
 '["Air Compressors", "Pneumatic Tools", "Lifting Equipment"]', 1),

('Atlas Copco', '["atlas copco","atlas-copco","ATLAS COPCO"]', 'Sweden', 'atlascopco.com', 'premium',
 '["Air Compressors", "Construction Equipment", "Drilling Equipment"]', 1),

-- ASIAN BRANDS
('Hitachi', '["hitachi","HITACHI"]', 'Japan', 'hitachi.com', 'premium',
 '["Excavators", "Power Tools", "Compressors"]', 1),

('Doosan', '["doosan","DOOSAN"]', 'South Korea', 'doosan.com', 'standard',
 '["Excavators", "Wheel Loaders", "Articulated Dump Trucks"]', 1),

('Hyundai Construction Equipment', '["hyundai","HYUNDAI","hyundai construction"]', 'South Korea', 'hce.hyundai.com', 'standard',
 '["Excavators", "Wheel Loaders", "Skid Steers"]', 1);

-- ================================================================================
-- COMPREHENSIVE ASSET TYPES WITH REAL-WORLD SPECIFICATIONS
-- ================================================================================

-- Clear existing asset types (optional)
-- DELETE FROM asset_discipline_mappings;
-- DELETE FROM asset_types;

-- Insert comprehensive asset types
INSERT INTO asset_types (name, category, subcategory, search_keywords, common_misspellings, typical_specifications) VALUES

-- POWER TOOLS - Drilling & Fastening
('Cordless Drill Driver', 'Power Tools', 'Drills & Drivers', 
 'drill,cordless,battery,driver,chuck,torque,lithium', 
 '["cordless dril","drill driver","cordess drill","cordles drill"]',
 '{"voltage": ["12V", "18V", "20V"], "chuck_size": ["3/8\"", "1/2\""], "torque": ["300-1000 in-lbs"], "battery": ["Li-ion"]}'),

('Hammer Drill', 'Power Tools', 'Drills & Drivers',
 'hammer,drill,sds,rotary,masonry,concrete,impact',
 '["hamer drill","hammer dril","hamr drill","hammerdrill"]',
 '{"power": ["7-15 Amp"], "chuck_type": ["SDS-Plus", "SDS-Max", "Keyed"], "impact_rate": ["4000-5500 bpm"]}'),

('Impact Driver', 'Power Tools', 'Drills & Drivers',
 'impact,driver,hex,fastening,screw,bolt,torque',
 '["impack driver","impact drivr","impac driver"]',
 '{"voltage": ["12V", "18V", "20V"], "torque": ["1400-4000 in-lbs"], "chuck": ["1/4\" Hex"]}'),

-- POWER TOOLS - Cutting
('Circular Saw', 'Power Tools', 'Saws',
 'circular,saw,blade,wood,lumber,rip,crosscut',
 '["circler saw","circular","skill saw","circuler saw"]',
 '{"blade_diameter": ["7-1/4\"", "10\"", "12\""], "power": ["15 Amp"], "max_depth": ["2-5/8\"", "3-1/2\""]}'),

('Reciprocating Saw', 'Power Tools', 'Saws',
 'reciprocating,saw,sawzall,demo,demolition,blade',
 '["recip saw","reciprocating","sawzal","sawsall"]',
 '{"stroke_length": ["1-1/8\"", "1-1/4\""], "spm": ["2700-3000"], "power": ["12-15 Amp"]}'),

('Miter Saw', 'Power Tools', 'Saws',
 'miter,saw,chop,compound,sliding,crosscut,angle',
 '["mitre saw","mitar saw","chop saw","compound saw"]',
 '{"blade_diameter": ["8-1/2\"", "10\"", "12\""], "miter_range": ["50° L/R"], "bevel_range": ["45°-48°"]}'),

('Table Saw', 'Power Tools', 'Saws',
 'table,saw,cabinet,contractor,rip,fence,blade',
 '["tabel saw","table","tablesaw"]',
 '{"blade_diameter": ["8-1/4\"", "10\""], "rip_capacity": ["24\"-30\""], "power": ["13-15 Amp"]}'),

('Jigsaw', 'Power Tools', 'Saws',
 'jigsaw,orbital,curves,scroll,blade,variable,speed',
 '["jig saw","gigsaw","jig-saw","jigsw"]',
 '{"stroke_length": ["7/8\"", "1\""], "spm": ["500-3100"], "orbital_action": ["4 settings"]}'),

('Band Saw', 'Power Tools', 'Saws',
 'band,saw,portable,metal,cutting,blade,guide',
 '["bandsaw","band-saw","bansaw"]',
 '{"throat_depth": ["4-1/2\"", "5\""], "blade_speed": ["280-350 fpm"], "cutting_capacity": ["4-3/4\""]}'),

-- POWER TOOLS - Grinding & Sanding
('Angle Grinder', 'Power Tools', 'Grinders',
 'angle,grinder,disc,cutting,grinding,metal,concrete',
 '["grinder","angle grindr","disc grinder","angel grinder"]',
 '{"disc_diameter": ["4-1/2\"", "7\"", "9\""], "power": ["11-15 Amp"], "rpm": ["8500-11000"]}'),

('Die Grinder', 'Power Tools', 'Grinders',
 'die,grinder,straight,pneumatic,burr,carbide',
 '["dye grinder","di grinder"]',
 '{"collet_size": ["1/4\"", "1/8\""], "rpm": ["25000-30000"], "power_type": ["Electric", "Pneumatic"]}'),

('Bench Grinder', 'Power Tools', 'Grinders',
 'bench,grinder,wheel,sharpening,tool,rest',
 '["bench grindder","benchgrinder"]',
 '{"wheel_diameter": ["6\"", "8\"", "10\""], "power": ["1/3-1 HP"], "rpm": ["3450-3600"]}'),

('Random Orbital Sander', 'Power Tools', 'Sanders',
 'orbital,sander,random,hook,loop,dust,pad',
 '["orbital sandr","random orbit sander","orbit sander"]',
 '{"pad_diameter": ["5\"", "6\""], "orbit_diameter": ["3/32\"", "3/16\""], "opm": ["8000-12000"]}'),

('Belt Sander', 'Power Tools', 'Sanders',
 'belt,sander,track,frame,dust,bag',
 '["belt sandr","beltsander"]',
 '{"belt_size": ["3\"x21\"", "4\"x24\""], "belt_speed": ["1200-1500 fpm"], "power": ["8-12 Amp"]}'),

-- POWER TOOLS - Specialized
('Oscillating Multi-Tool', 'Power Tools', 'Specialty Tools',
 'oscillating,multi,tool,sonicrafter,blade,attachment',
 '["multi tool","multitool","oscillating tool"]',
 '{"oscillation_angle": ["2.8°", "3.2°"], "opm": ["10000-20000"], "attachments": ["Universal"]}'),

('Router', 'Power Tools', 'Routers',
 'router,plunge,fixed,base,collet,bit,template',
 '["ruter","routr","wood router"]',
 '{"power": ["1.5-3.25 HP"], "collet_size": ["1/4\"", "1/2\""], "speed": ["8000-25000 RPM"]}'),

('Biscuit Joiner', 'Power Tools', 'Joiners',
 'biscuit,joiner,plate,slot,fence,depth',
 '["biscit joiner","plate joiner","biscuit jointer"]',
 '{"blade_diameter": ["4\""], "fence_range": ["0-90°"], "slot_sizes": ["#0, #10, #20"]}'),

-- WELDING EQUIPMENT
('MIG Welder', 'Welding Equipment', 'Electric Welders',
 'mig,welder,wire,gas,flux,core,metal,inert',
 '["mig weldr","MIG welder","wire welder"]',
 '{"amperage": ["140-250A"], "voltage": ["115V", "230V"], "wire_diameter": [".024-.045"], "duty_cycle": ["20-60%"]}'),

('TIG Welder', 'Welding Equipment', 'Electric Welders',
 'tig,tungsten,inert,gas,welder,aluminum,stainless',
 '["TIG weldr","tungsten welder","tig welding"]',
 '{"amperage": ["150-250A"], "voltage": ["115V", "230V"], "pulse": ["Available"], "foot_control": ["Optional"]}'),

('Stick Welder', 'Welding Equipment', 'Electric Welders',
 'stick,arc,welder,electrode,rod,smaw',
 '["stick weldr","arc welder","electrode welder"]',
 '{"amperage": ["120-300A"], "voltage": ["230V"], "electrode": [".094-.250"], "duty_cycle": ["20-100%"]}'),

('Plasma Cutter', 'Welding Equipment', 'Cutting Equipment',
 'plasma,cutter,cutting,torch,air,compressor',
 '["plasma cutr","plasma cutting","plazma cutter"]',
 '{"amperage": ["25-80A"], "cut_thickness": ["1/8-1.5"], "air_pressure": ["60-80 PSI"]}'),

-- HEAVY EQUIPMENT
('Mini Excavator', 'Heavy Equipment', 'Excavators',
 'mini,excavator,compact,track,rubber,bucket,thumb',
 '["mini excivator","compact excavator","small excavator"]',
 '{"operating_weight": ["2000-20000 lbs"], "dig_depth": ["6-12 ft"], "bucket_capacity": ["0.04-0.8 cy"]}'),

('Skid Steer Loader', 'Heavy Equipment', 'Loaders',
 'skid,steer,loader,bobcat,attachment,track,wheel',
 '["skidsteer","skid-steer","skid loader"]',
 '{"operating_capacity": ["1000-4000 lbs"], "engine_power": ["50-120 HP"], "lift_height": ["105-140"]}'),

('Backhoe Loader', 'Heavy Equipment', 'Loaders',
 'backhoe,loader,tractor,bucket,stabilizer,4wd',
 '["backo loader","back hoe","tractor loader backhoe"]',
 '{"dig_depth": ["14-21 ft"], "engine_power": ["75-120 HP"], "loader_capacity": ["1.0-1.75 cy"]}'),

('Telehandler', 'Heavy Equipment', 'Material Handling',
 'telehandler,telescopic,forklift,reach,boom,stabilizer',
 '["tele handler","telescopic handler","reach forklift"]',
 '{"lift_capacity": ["5500-12000 lbs"], "lift_height": ["19-55 ft"], "reach": ["15-42 ft"]}'),

-- GENERATORS & COMPRESSORS
('Portable Generator', 'Power Equipment', 'Generators',
 'generator,portable,gas,electric,start,wheel,kit',
 '["generater","portible generator","gas generator"]',
 '{"watts": ["3000-10000W"], "runtime": ["8-12 hrs"], "fuel_type": ["Gas", "Dual Fuel"], "outlets": ["120V/240V"]}'),

('Air Compressor', 'Power Equipment', 'Compressors',
 'air,compressor,tank,psi,cfm,single,stage,two',
 '["compresser","air compresser","compressor"]',
 '{"tank_size": ["20-80 gal"], "pressure": ["125-175 PSI"], "cfm": ["5.0-14.0"], "hp": ["1.5-5.0"]}'),

-- CONCRETE EQUIPMENT
('Concrete Mixer', 'Concrete Equipment', 'Mixing',
 'concrete,mixer,cement,portable,drum,electric,gas',
 '["concret mixer","cement mixer","concrete mixr"]',
 '{"capacity": ["3.5-9 cu ft"], "power": ["Electric", "Gas"], "drum_speed": ["28-32 RPM"]}'),

('Concrete Vibrator', 'Concrete Equipment', 'Finishing',
 'concrete,vibrator,immersion,needle,motor,flexible',
 '["concret vibrator","concrete vibrater","vibrator"]',
 '{"diameter": ["1-2.5"], "length": ["6-20 ft"], "frequency": ["10000-12000 vpm"], "power": ["Electric", "Gas"]}'),

('Power Trowel', 'Concrete Equipment', 'Finishing',
 'power,trowel,float,concrete,finishing,walk,behind',
 '["power trowl","concrete trowel","finishing trowel"]',
 '{"blade_diameter": ["24-48"], "engine": ["5.5-13 HP"], "speed": ["100-160 RPM"]}'),

-- MEASURING EQUIPMENT
('Laser Level', 'Measuring Equipment', 'Levels',
 'laser,level,self,leveling,rotary,line,cross,grade',
 '["lazer level","laser levl","laser level"]',
 '{"accuracy": ["±1/8\" at 100 ft"], "range": ["100-2000 ft"], "type": ["Line", "Rotary", "Cross-line"]}'),

('Total Station', 'Surveying Equipment', 'Electronic Instruments',
 'total,station,theodolite,edm,prism,angle,distance',
 '["total statin","totalstation","total-station"]',
 '{"angle_accuracy": ["2-5 seconds"], "distance_accuracy": ["±(2mm+2ppm)"], "range": ["3000-5000m"]}'),

('GPS/GNSS Receiver', 'Surveying Equipment', 'Positioning',
 'gps,gnss,receiver,rtk,base,rover,survey,grade',
 '["GPS reciver","GNSS reciever","GPS unit"]',
 '{"accuracy": ["Sub-centimeter"], "channels": ["220+"], "update_rate": ["20Hz"], "battery": ["8-12 hrs"]}'),

-- SAFETY EQUIPMENT
('Safety Harness', 'Safety Equipment', 'Fall Protection',
 'safety,harness,fall,protection,lanyard,anchor,point',
 '["safty harness","harnass","safety harnass","fall harness"]',
 '{"weight_capacity": ["310-420 lbs"], "d_rings": ["1-4"], "adjustments": ["Chest/Leg"], "standards": ["ANSI Z359"]}'),

('Hard Hat', 'Safety Equipment', 'Head Protection',
 'hard,hat,helmet,safety,suspension,chin,strap,class',
 '["hardhat","hard-hat","safety hat","construction hat"]',
 '{"type": ["Type I", "Type II"], "class": ["G", "E", "C"], "material": ["HDPE", "ABS"], "suspension": ["4-6 point"]}'),

('Safety Glasses', 'Safety Equipment', 'Eye Protection',
 'safety,glasses,goggles,eye,protection,lens,side,shield',
 '["safty glasses","safety gogles","protective glasses"]',
 '{"lens_type": ["Clear", "Tinted", "Anti-fog"], "frame": ["Wraparound", "Side Shield"], "standards": ["ANSI Z87.1"]}');

-- ================================================================================
-- ASSET DISCIPLINE MAPPINGS - Connect asset types to disciplines
-- ================================================================================

-- Power Tools to Multiple Disciplines
INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 1, 'Primary drilling and fastening tool for structural work', 'daily'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'Cordless Drill Driver' AND ad.code = 'CARPENTRY';

INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 0, 'General drilling and fastening across disciplines', 'daily'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'Cordless Drill Driver' AND ad.code IN ('ELEC', 'PLUMB', 'MECH', 'GENERAL');

-- Welding equipment to relevant disciplines
INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 1, 'Primary welding for structural steel work', 'daily'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'MIG Welder' AND ad.code = 'STRUCT_STEEL';

INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 0, 'Piping and mechanical system welding', 'weekly'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'MIG Welder' AND ad.code IN ('MECH_PIPING', 'PLUMB');

-- Heavy equipment to civil and earthwork disciplines
INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 1, 'Primary excavation and earthmoving', 'daily'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'Mini Excavator' AND ad.code = 'CIVIL_EARTH';

INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 0, 'Utility installation and trenching', 'weekly'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'Mini Excavator' AND ad.code IN ('PLUMB', 'ELEC', 'TELECOM');

-- Safety equipment to all disciplines
INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 1, 'Required personal protective equipment', 'daily'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'Safety Harness' AND ad.code = 'SAFETY_FALL';

INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 0, 'Fall protection for elevated work', 'daily'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'Safety Harness' AND ad.code IN ('STRUCT', 'ROOFING', 'GLAZING', 'ELEC');

-- Measuring equipment to surveying and layout disciplines
INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 1, 'Primary instrument for site surveying', 'daily'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'Total Station' AND ad.code = 'SURVEY';

INSERT INTO asset_discipline_mappings (asset_type_id, discipline_id, primary_use, use_description, frequency_of_use)
SELECT at.id, ad.id, 0, 'Layout and positioning for construction', 'weekly'
FROM asset_types at, asset_disciplines ad 
WHERE at.name = 'Total Station' AND ad.code IN ('CIVIL', 'STRUCT');

-- Update brand verification status for well-known brands
UPDATE asset_brands SET is_verified = 1 WHERE official_name IN (
    'Makita', 'DeWalt', 'Bosch', 'Hilti', 'Milwaukee', 'Caterpillar', 
    'Stanley', 'Black & Decker', 'Ridgid', 'Festool', '3M', 'Leica Geosystems'
);

-- ================================================================================
-- ENHANCED SPELLING CORRECTIONS FOR REAL-WORLD USAGE
-- ================================================================================

INSERT INTO asset_spelling_corrections (incorrect, correct, context, confidence_score, approved) VALUES
-- Power Tool Misspellings
('cordless dril', 'cordless drill', 'tool_name', 0.95, 1),
('impack driver', 'impact driver', 'tool_name', 0.95, 1),
('reciprocatin saw', 'reciprocating saw', 'tool_name', 0.90, 1),
('skil saw', 'circular saw', 'tool_name', 0.85, 1),
('sawzal', 'reciprocating saw', 'tool_name', 0.80, 1),
('jig-saw', 'jigsaw', 'tool_name', 0.90, 1),
('orbital sandr', 'orbital sander', 'tool_name', 0.90, 1),
('belt sandr', 'belt sander', 'tool_name', 0.90, 1),
('angle grindr', 'angle grinder', 'tool_name', 0.90, 1),

-- Heavy Equipment Misspellings  
('excivator', 'excavator', 'tool_name', 0.95, 1),
('skidsteer', 'skid steer', 'tool_name', 0.90, 1),
('back hoe', 'backhoe', 'tool_name', 0.90, 1),
('tele handler', 'telehandler', 'tool_name', 0.85, 1),
('generater', 'generator', 'tool_name', 0.95, 1),
('compresser', 'compressor', 'tool_name', 0.95, 1),

-- Brand Misspellings
('makitta', 'makita', 'brand', 0.90, 1),
('de walt', 'dewalt', 'brand', 0.85, 1),
('milwakee', 'milwaukee', 'brand', 0.90, 1),
('caterpiller', 'caterpillar', 'brand', 0.90, 1),
('stanly', 'stanley', 'brand', 0.90, 1),
('ridged', 'ridgid', 'brand', 0.85, 1),
('skilsaw', 'skill saw', 'brand', 0.80, 1),

-- Safety Equipment
('hardhat', 'hard hat', 'tool_name', 0.90, 1),
('safty harness', 'safety harness', 'tool_name', 0.95, 1),
('safty glasses', 'safety glasses', 'tool_name', 0.95, 1),
('harnass', 'harness', 'tool_name', 0.90, 1),
('gogles', 'goggles', 'tool_name', 0.90, 1),

-- Measuring Equipment  
('lazer level', 'laser level', 'tool_name', 0.95, 1),
('total statin', 'total station', 'tool_name', 0.90, 1),
('tranist', 'transit', 'tool_name', 0.85, 1),
('GPS reciver', 'GPS receiver', 'tool_name', 0.90, 1),

-- Discipline/Category Corrections
('eletrical', 'electrical', 'category', 0.95, 1),
('mechinical', 'mechanical', 'category', 0.95, 1),
('pluming', 'plumbing', 'category', 0.95, 1),
('concret', 'concrete', 'category', 0.90, 1),
('structual', 'structural', 'category', 0.90, 1),
('servaying', 'surveying', 'category', 0.90, 1),
('safty', 'safety', 'category', 0.95, 1);

-- Success message
SELECT 'Comprehensive Construction Industry Data Successfully Populated!' AS status,
       (SELECT COUNT(*) FROM asset_disciplines) AS disciplines_count,
       (SELECT COUNT(*) FROM asset_brands) AS brands_count,  
       (SELECT COUNT(*) FROM asset_types) AS asset_types_count,
       (SELECT COUNT(*) FROM asset_discipline_mappings) AS mappings_count,
       (SELECT COUNT(*) FROM asset_spelling_corrections) AS corrections_count;