-- Enhanced Discipline Validation Rules
-- Adds context-aware discipline validation rules that work with the improved scoring system

-- Add new validation rules for better discipline handling
INSERT INTO `asset_validation_rules` (`rule_name`, `rule_type`, `field_name`, `validation_logic`, `error_message`, `severity`, `applies_to_roles`) VALUES

-- Critical equipment discipline requirements
('critical_equipment_discipline_required', 'logic', 'discipline_tags', '{"check_critical_equipment_discipline": true}', 'Critical equipment should have appropriate discipline assignment', 'warning', '["Site Inventory Clerk", "Project Manager"]'),

-- Sub-discipline consistency check
('sub_discipline_consistency', 'logic', 'discipline_tags', '{"check_sub_discipline_parent": true}', 'Sub-discipline should have corresponding main discipline', 'warning', '["Site Inventory Clerk", "Project Manager"]'),

-- Brand field completeness for tracked equipment
('brand_for_trackable_equipment', 'completeness', 'brand_id', '{"required_for_equipment_types": ["generator", "compressor", "welding", "cutting", "motor"]}', 'Brand should be specified for trackable equipment', 'warning', '["Site Inventory Clerk", "Project Manager"]'),

-- Model specification for complex equipment
('model_for_complex_equipment', 'completeness', 'model', '{"required_for_equipment_types": ["generator", "compressor", "pump", "motor", "transformer"]}', 'Model should be specified for complex equipment', 'info', '["Site Inventory Clerk", "Project Manager"]'),

-- Serial number for valuable equipment
('serial_for_valuable_equipment', 'completeness', 'serial_number', '{"required_for_cost_above": 50000}', 'Serial number recommended for valuable equipment', 'info', '["Site Inventory Clerk", "Project Manager"]'),

-- Equipment-specific quantity validation
('reasonable_quantity_check', 'logic', 'quantity', '{"check_quantity_vs_equipment": true}', 'Quantity seems unusual for this equipment type', 'info', '["Site Inventory Clerk", "Project Manager"]');

-- Update existing discipline rule to be more flexible for basic equipment
UPDATE `asset_validation_rules` 
SET `validation_logic` = '{"check_discipline_equipment_match": true, "flexible_for_basic": true}',
    `severity` = 'info'
WHERE `rule_name` = 'discipline_equipment_match';

-- Add equipment context to cost validation
UPDATE `asset_validation_rules` 
SET `validation_logic` = '{"min": 100, "max": 1000000, "check_against_category": true, "check_equipment_context": true}'
WHERE `rule_name` = 'cost_reasonableness';