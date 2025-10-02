-- Business Category Taxonomy Seed Data
-- Date: 2024-01-11  
-- Description: Seed database with comprehensive business-aligned category structure
-- Run AFTER: add_business_category_classification.sql

-- ================================================================================
-- CAPITAL ASSETS (Asset-Generating, Depreciable Equipment)
-- ================================================================================

-- Heavy Equipment & Machinery
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'Heavy Equipment & Machinery', 
    'Large construction equipment, machinery, and vehicles', 
    0, 1, 'capital', 1, 5000.00,
    'Capital assets subject to depreciation. Includes excavators, cranes, bulldozers, and other heavy construction machinery.'
);

-- Power Tools & Equipment
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'Power Tools & Equipment', 
    'Electric and pneumatic power tools and equipment', 
    0, 1, 'capital', 1, 500.00,
    'Depreciable power tools and equipment. Items below threshold may be expensed immediately if auto_expense_below_threshold is enabled.'
);

-- Hand Tools & Instruments  
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `depreciation_applicable`, `capitalization_threshold`, `business_description`, `auto_expense_below_threshold`
) VALUES (
    'Hand Tools & Instruments', 
    'Manual tools, measuring instruments, and small equipment', 
    0, 1, 'capital', 1, 200.00,
    'Small tools and instruments. Items below $200 can be automatically expensed for simplified accounting.',
    1
);

-- Safety Equipment (Non-consumable PPE)
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'PPE Equipment', 
    'Personal protective equipment - reusable items', 
    0, 1, 'capital', 1, 100.00,
    'Reusable personal protective equipment such as hard hats, safety harnesses, respirators, safety boots.'
);

-- IT & Communication Equipment
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'IT & Communication Equipment', 
    'Computers, tablets, radios, and communication devices', 
    0, 1, 'capital', 1, 300.00,
    'Information technology and communication equipment subject to depreciation.'
);

-- Office Equipment & Furniture
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `depreciation_applicable`, `capitalization_threshold`, `business_description`
) VALUES (
    'Office Equipment & Furniture', 
    'Office furniture, equipment, and fixtures', 
    0, 1, 'capital', 1, 500.00,
    'Office assets including desks, chairs, filing cabinets, and office equipment.'
);

-- ================================================================================
-- INVENTORY & MATERIALS (Asset-Generating, Cost of Goods Sold)
-- ================================================================================

-- Raw Materials - Parent Category
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `capitalization_threshold`, `business_description`
) VALUES (
    'Raw Materials', 
    'Construction materials and components', 
    1, 1, 'inventory', 0.00,
    'Construction materials tracked as inventory assets, expensed when consumed.'
);

-- Get the parent ID for subcategories
SET @raw_materials_id = LAST_INSERT_ID();

-- Raw Materials Subcategories
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `parent_id`, `capitalization_threshold`, `business_description`
) VALUES 
(
    'Concrete & Cement', 
    'Concrete, cement, aggregates, and related materials', 
    1, 1, 'inventory', @raw_materials_id, 0.00,
    'Concrete and cement materials for construction projects.'
),
(
    'Steel & Metal Materials', 
    'Structural steel, rebar, metal sheets, pipes, and fittings', 
    1, 1, 'inventory', @raw_materials_id, 0.00,
    'Steel and metal construction materials.'
),
(
    'Electrical Components', 
    'Wiring, conduits, switches, outlets, and electrical materials', 
    1, 1, 'inventory', @raw_materials_id, 0.00,
    'Electrical components and materials for construction.'
),
(
    'Plumbing Materials', 
    'Pipes, fittings, valves, and plumbing components', 
    1, 1, 'inventory', @raw_materials_id, 0.00,
    'Plumbing materials and components.'
);

-- Consumable Supplies - Parent Category  
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `capitalization_threshold`, `business_description`
) VALUES (
    'Consumable Supplies', 
    'Consumable materials and supplies', 
    1, 1, 'inventory', 0.00,
    'Consumable supplies tracked as inventory, expensed when used.'
);

SET @consumable_supplies_id = LAST_INSERT_ID();

-- Consumable Supplies Subcategories
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `parent_id`, `capitalization_threshold`, `business_description`
) VALUES 
(
    'PPE Consumables', 
    'Disposable personal protective equipment', 
    1, 1, 'inventory', @consumable_supplies_id, 0.00,
    'Disposable PPE items: gloves, masks, disposable coveralls, safety glasses.'
),
(
    'Office Supplies', 
    'Stationery, paper, and office consumables', 
    1, 1, 'inventory', @consumable_supplies_id, 0.00,
    'Office supplies and stationery items.'
),
(
    'Cleaning Supplies', 
    'Cleaning materials and janitorial supplies', 
    1, 1, 'inventory', @consumable_supplies_id, 0.00,
    'Cleaning and janitorial supplies.'
),
(
    'Maintenance Supplies', 
    'Consumable maintenance materials and spare parts', 
    1, 1, 'inventory', @consumable_supplies_id, 0.00,
    'Consumable maintenance materials, lubricants, filters, and spare parts.'
);

-- Small Tools & Accessories
INSERT INTO `categories` (
    `name`, `description`, `is_consumable`, `generates_assets`, `asset_type`, 
    `capitalization_threshold`, `business_description`, `auto_expense_below_threshold`
) VALUES (
    'Small Tools & Accessories', 
    'Small tools and accessories under capitalization threshold', 
    0, 1, 'inventory', 50.00,
    'Small tools and accessories. Items below $50 automatically expensed for simplified tracking.',
    1
);

-- ================================================================================
-- OPERATING EXPENSES (Non-Asset-Generating, Direct Expense)
-- ================================================================================

-- Professional Services - Parent Category
INSERT INTO `categories` (
    `name`, `description`, `generates_assets`, `asset_type`, `expense_category`, 
    `business_description`
) VALUES (
    'Professional Services', 
    'Professional and consulting services', 
    0, 'expense', 'professional_services',
    'Professional services expensed when incurred, no asset generation.'
);

SET @professional_services_id = LAST_INSERT_ID();

-- Professional Services Subcategories
INSERT INTO `categories` (
    `name`, `description`, `generates_assets`, `asset_type`, `expense_category`, 
    `parent_id`, `business_description`
) VALUES 
(
    'Testing & Certification Services', 
    'Material testing, quality assurance, and certification services', 
    0, 'expense', 'professional_services', @professional_services_id,
    'Testing and certification services for quality assurance and compliance.'
),
(
    'Engineering & Consulting', 
    'Engineering consultancy and professional advisory services', 
    0, 'expense', 'professional_services', @professional_services_id,
    'Professional engineering and consulting services.'
),
(
    'Legal & Professional Fees', 
    'Legal services, accounting, and professional fees', 
    0, 'expense', 'professional_services', @professional_services_id,
    'Legal and other professional service fees.'
);

-- Maintenance & Repair Services - Parent Category
INSERT INTO `categories` (
    `name`, `description`, `generates_assets`, `asset_type`, `expense_category`, 
    `business_description`
) VALUES (
    'Maintenance & Repair Services', 
    'Maintenance and repair services for equipment and facilities', 
    0, 'expense', 'maintenance',
    'Maintenance and repair services expensed as operating costs.'
);

SET @maintenance_services_id = LAST_INSERT_ID();

-- Maintenance & Repair Services Subcategories
INSERT INTO `categories` (
    `name`, `description`, `generates_assets`, `asset_type`, `expense_category`, 
    `parent_id`, `business_description`
) VALUES 
(
    'Vehicle Maintenance & Repair', 
    'Vehicle maintenance, repair, and servicing', 
    0, 'expense', 'maintenance', @maintenance_services_id,
    'Vehicle maintenance and repair services.'
),
(
    'Equipment Maintenance & Repair', 
    'Heavy equipment and machinery maintenance and repair', 
    0, 'expense', 'maintenance', @maintenance_services_id,
    'Equipment and machinery maintenance services.'
),
(
    'Building & Facility Maintenance', 
    'Building maintenance, repairs, and facility upkeep', 
    0, 'expense', 'maintenance', @maintenance_services_id,
    'Building and facility maintenance services.'
),
(
    'IT Support & Maintenance', 
    'IT support, software maintenance, and technical services', 
    0, 'expense', 'maintenance', @maintenance_services_id,
    'IT support and maintenance services.'
);

-- Utilities & Operating Costs - Parent Category
INSERT INTO `categories` (
    `name`, `description`, `generates_assets`, `asset_type`, `expense_category`, 
    `business_description`
) VALUES (
    'Utilities & Operating Costs', 
    'Utilities and recurring operating expenses', 
    0, 'expense', 'operating',
    'Recurring operating expenses and utility costs.'
);

SET @utilities_operating_id = LAST_INSERT_ID();

-- Utilities & Operating Costs Subcategories
INSERT INTO `categories` (
    `name`, `description`, `generates_assets`, `asset_type`, `expense_category`, 
    `parent_id`, `business_description`
) VALUES 
(
    'Rental & Lease Payments', 
    'Equipment rental, facility lease, and rental payments', 
    0, 'expense', 'operating', @utilities_operating_id,
    'Rental and lease payments for equipment and facilities.'
),
(
    'Insurance Premiums', 
    'Insurance premiums and coverage costs', 
    0, 'expense', 'operating', @utilities_operating_id,
    'Insurance premiums and coverage costs.'
),
(
    'Utilities', 
    'Electricity, water, gas, and other utility costs', 
    0, 'expense', 'operating', @utilities_operating_id,
    'Utility costs and services.'
),
(
    'Transportation & Logistics', 
    'Transportation, shipping, and logistics services', 
    0, 'expense', 'operating', @utilities_operating_id,
    'Transportation and logistics costs.'
);

-- Regulatory & Compliance - Parent Category
INSERT INTO `categories` (
    `name`, `description`, `generates_assets`, `asset_type`, `expense_category`, 
    `business_description`
) VALUES (
    'Regulatory & Compliance', 
    'Permits, licenses, and regulatory compliance costs', 
    0, 'expense', 'regulatory',
    'Regulatory compliance costs and licensing fees.'
);

SET @regulatory_compliance_id = LAST_INSERT_ID();

-- Regulatory & Compliance Subcategories
INSERT INTO `categories` (
    `name`, `description`, `generates_assets`, `asset_type`, `expense_category`, 
    `parent_id`, `business_description`
) VALUES 
(
    'Permits & Licenses', 
    'Construction permits, business licenses, and regulatory approvals', 
    0, 'expense', 'regulatory', @regulatory_compliance_id,
    'Permits, licenses, and regulatory approval costs.'
),
(
    'Environmental Testing', 
    'Environmental impact assessments and testing services', 
    0, 'expense', 'regulatory', @regulatory_compliance_id,
    'Environmental testing and compliance services.'
),
(
    'Safety Inspections', 
    'Safety inspections and compliance audits', 
    0, 'expense', 'regulatory', @regulatory_compliance_id,
    'Safety inspection and audit services.'
),
(
    'Regulatory Compliance Fees', 
    'Government fees, compliance costs, and regulatory assessments', 
    0, 'expense', 'regulatory', @regulatory_compliance_id,
    'Regulatory compliance fees and government assessments.'
);

-- ================================================================================
-- VALIDATION AND COMPLETION
-- ================================================================================

-- Count and validate inserted categories
SELECT 
    COUNT(*) as total_categories_added,
    COUNT(CASE WHEN generates_assets = 1 THEN 1 END) as asset_generating,
    COUNT(CASE WHEN generates_assets = 0 THEN 1 END) as expense_only,
    COUNT(CASE WHEN asset_type = 'capital' THEN 1 END) as capital_categories,
    COUNT(CASE WHEN asset_type = 'inventory' THEN 1 END) as inventory_categories,
    COUNT(CASE WHEN asset_type = 'expense' THEN 1 END) as expense_categories
FROM categories 
WHERE created_at >= NOW() - INTERVAL 5 MINUTE;

-- Show category hierarchy
SELECT 
    CASE WHEN parent_id IS NULL THEN name 
         ELSE CONCAT('  └─ ', name) 
    END as category_hierarchy,
    asset_type,
    CASE WHEN generates_assets = 1 THEN 'Assets' ELSE 'Expense' END as accounting_treatment,
    expense_category,
    capitalization_threshold
FROM categories 
ORDER BY COALESCE(parent_id, id), id;

-- Completion message
SELECT 'Business category taxonomy seeding completed successfully!' as status,
       NOW() as completed_at;

-- ================================================================================
-- USAGE NOTES FOR DEVELOPERS
-- ================================================================================

/*
CATEGORY USAGE GUIDE:

CAPITAL ASSETS (generates_assets=TRUE, asset_type='capital'):
- Heavy Equipment & Machinery: Major construction equipment
- Power Tools & Equipment: Professional power tools  
- Hand Tools & Instruments: Small tools (auto-expense <$200)
- PPE Equipment: Reusable safety equipment
- IT & Communication Equipment: Technology assets
- Office Equipment & Furniture: Office assets

INVENTORY/MATERIALS (generates_assets=TRUE, asset_type='inventory'):
- Raw Materials: Construction materials (concrete, steel, electrical, plumbing)
- Consumable Supplies: PPE consumables, office supplies, cleaning supplies
- Small Tools & Accessories: Minor tools (auto-expense <$50)

OPERATING EXPENSES (generates_assets=FALSE, asset_type='expense'):
- Professional Services: Testing, engineering, legal services
- Maintenance Services: Vehicle, equipment, building, IT maintenance
- Utilities & Operating: Rentals, insurance, utilities, transportation  
- Regulatory & Compliance: Permits, licenses, inspections, compliance fees

PROCUREMENT WORKFLOW:
1. Select appropriate category during procurement
2. System checks generates_assets flag
3. If TRUE: Create asset record after receipt
4. If FALSE: Direct expense allocation
5. Threshold checks apply for auto-expensing

ACCOUNTING INTEGRATION:
- Capital assets: Depreciation schedules
- Inventory: COGS when consumed  
- Expenses: Immediate cost recognition
- Project cost allocation maintained
*/