# BIR Form 2307 Integration Guide

## Overview

The BIR Form 2307 (Certificate of Creditable Tax Withheld at Source) integration allows ConstructLink to automatically generate tax withholding certificates for procurement orders. This form is required by the Bureau of Internal Revenue (BIR) in the Philippines for tracking and reporting income payments subject to expanded withholding tax.

## Features

### ✅ Vendor Management Enhancement
- Support for different vendor types (Company, Sole Proprietor, Partnership, etc.)
- TIN (Taxpayer Identification Number) tracking
- Separate name fields for sole proprietors
- ZIP code and RDO code support

### ✅ ATC Code Management
- Complete ATC (Alphanumeric Tax Code) database with rates
- Automatic EWT (Expanded Withholding Tax) calculation
- Purchase type categorization (Goods, Services, Rental, Professional Services)
- Support for mixed purchase types in single procurement orders

### ✅ Form Generation
- Automatic BIR 2307 form generation from procurement orders
- Quarterly period determination
- Proper vendor name handling based on vendor type
- Income payments grouped by ATC code
- Official BIR form format compliance

### ✅ Print Preview
- High-quality print preview matching official BIR format
- A4 paper size optimization
- TIN box formatting
- Barcode placeholder for form tracking

## Implementation Details

### Database Schema Changes

#### 1. Vendors Table Updates
```sql
ALTER TABLE vendors ADD COLUMN vendor_type ENUM('Company', 'Sole Proprietor', 'Partnership', 'Cooperative', 'Government') DEFAULT 'Company';
ALTER TABLE vendors ADD COLUMN tin VARCHAR(20);
ALTER TABLE vendors ADD COLUMN first_name VARCHAR(100);
ALTER TABLE vendors ADD COLUMN middle_name VARCHAR(100); 
ALTER TABLE vendors ADD COLUMN last_name VARCHAR(100);
ALTER TABLE vendors ADD COLUMN registered_name VARCHAR(255);
ALTER TABLE vendors ADD COLUMN rdo_code VARCHAR(10);
ALTER TABLE vendors ADD COLUMN zip_code VARCHAR(10);
```

#### 2. New Tables Created
- `atc_codes` - ATC code definitions with rates and categories
- `bir_2307_forms` - Generated form storage and tracking
- `bir_2307_config` - Configuration settings
- `bir_2307_audit_log` - Audit trail for form actions

#### 3. Procurement Items Enhancement
```sql
ALTER TABLE procurement_items ADD COLUMN purchase_type ENUM('Goods', 'Services', 'Rental', 'Professional Services', 'Mixed', 'Other') DEFAULT 'Goods';
ALTER TABLE procurement_items ADD COLUMN atc_code_id INT(11);
ALTER TABLE procurement_items ADD COLUMN ewt_rate DECIMAL(5,2);
ALTER TABLE procurement_items ADD COLUMN ewt_amount DECIMAL(15,2);
```

### Key Components

#### Models
- **AtcCodeModel.php** - Manages ATC codes and EWT calculations
- **Bir2307Model.php** - Handles BIR 2307 form generation and management
- **VendorModel.php** - Enhanced with BIR-specific fields
- **ProcurementItemModel.php** - Updated with purchase types and ATC codes

#### Controllers
- **Bir2307Controller.php** - Handles all BIR 2307 operations

#### Views
- **print-preview.php** - Official BIR form layout
- **view.php** - Form details display

#### Database Migration
- **add_bir_2307_support.sql** - Complete database schema updates

## Usage Instructions

### 1. Initial Setup

#### Run Database Migration
Execute the migration script to add BIR 2307 support:
```bash
mysql -u username -p database_name < database/migrations/add_bir_2307_support.sql
```

#### Configure ATC Codes
The migration includes common ATC codes. You can add more through the database or create an admin interface.

### 2. Vendor Setup

#### For Companies:
- Set `vendor_type` to "Company"
- Fill in `name` and `registered_name`
- Provide TIN in XXX-XXX-XXX-XXX format

#### For Sole Proprietors:
- Set `vendor_type` to "Sole Proprietor"
- Fill in `first_name`, `middle_name`, `last_name`
- Optionally set `registered_name` for business name
- Provide TIN

### 3. Procurement Order Processing

#### Setting Purchase Types
When creating procurement orders, ensure each item has:
- Appropriate `purchase_type` (Goods, Services, Rental, etc.)
- Corresponding ATC code (automatic based on purchase type if not specified)

#### Automatic EWT Calculation
The system automatically calculates EWT based on:
- Item subtotal
- Selected ATC code rate
- VAT inclusion setting

### 4. BIR 2307 Generation

#### Manual Generation
1. Navigate to completed/paid procurement order
2. Click "Generate BIR 2307" button
3. Form is automatically created with proper period determination
4. Review and print the form

#### Batch Generation
For quarterly processing:
1. Go to BIR 2307 module
2. Select "Batch Generate"
3. Choose quarter and year
4. System generates forms for all eligible procurement orders

### 5. Form Management

#### Form Status Workflow
- **Generated** - Form created and ready for review
- **Printed** - Form has been printed
- **Submitted** - Form submitted to BIR
- **Cancelled** - Form cancelled

#### Print Preview
The print preview generates an exact replica of the official BIR Form 2307:
- Proper TIN box formatting
- Income payments table with ATC codes
- Quarterly period display
- Vendor information (company vs individual)
- Payor information (V Cutamora details)

## Common ATC Codes Included

| Code | Description | Rate | Category |
|------|-------------|------|----------|
| WC156 | Payment to suppliers of goods | 1% | Goods |
| WC157 | Payment to suppliers of services | 2% | Services |
| WC030 | Rental - Machinery and equipment | 5% | Rental |
| WI010 | Professional fees (individuals) | 10% | Professional/Talent Fees |
| WI011 | Professional fees (non-individuals) | 15% | Professional/Talent Fees |

## Technical Notes

### EWT Calculation Logic
```php
$baseAmount = $itemSubtotal;
if ($includeVAT && $atcCode['is_vat_inclusive']) {
    $baseAmount = $itemSubtotal; // VAT already included
} elseif (!$includeVAT && !$atcCode['is_vat_inclusive']) {
    $baseAmount = $itemSubtotal / 1.12; // Exclude 12% VAT
}
$ewtAmount = $baseAmount * ($atcCode['rate'] / 100);
```

### Form Number Generation
- Format: 2307-YYYY-NNNNN
- Auto-incrementing counter
- Configurable prefix and format

### Vendor Name Logic
- **Company**: Uses `name` field
- **Sole Proprietor**: Constructs name from `first_name`, `middle_name`, `last_name`
- **Business Name**: Uses `registered_name` when available

## Security Considerations

### Access Control
- BIR 2307 generation limited to: System Admin, Finance Officer, Procurement Officer
- Form viewing includes Asset Director
- Batch operations restricted to System Admin and Finance Officer

### Audit Trail
All form actions are logged with:
- User ID and timestamp
- Action type (created, printed, submitted, etc.)
- IP address and user agent
- Detailed change information

## Troubleshooting

### Common Issues

#### "ATC Code Not Found"
- Ensure procurement items have purchase types assigned
- Check that corresponding ATC codes exist in database
- Verify ATC codes are active (`is_active = 1`)

#### "Vendor TIN Missing"
- Update vendor record with proper TIN
- TIN should be in XXX-XXX-XXX-XXX format
- Required for BIR 2307 generation

#### "Print Preview Issues"
- Ensure browser supports CSS print media queries
- Check that company information is configured
- Verify form data is properly JSON-encoded

### Database Maintenance

#### Regular Tasks
- Archive old BIR 2307 forms annually
- Update ATC codes when BIR issues new rates
- Clean up audit logs periodically

#### Backup Recommendations
- Include BIR-specific tables in regular backups
- Test restoration of form data
- Maintain copy of ATC code definitions

## Integration Points

### Existing System Impact
- **Vendors**: Enhanced with BIR fields, backward compatible
- **Procurement Orders**: New BIR generation capability
- **Items**: Purchase type classification for tax calculation
- **Print System**: New BIR form template alongside existing PO printing

### Future Enhancements
- BIR 2316 (Annual Income Tax Withheld) integration
- Electronic filing integration
- Multi-company support for different TINs
- Advanced ATC code management interface
- Quarterly reporting dashboard

## Support and Maintenance

### Code Locations
- Models: `/models/AtcCodeModel.php`, `/models/Bir2307Model.php`
- Controllers: `/controllers/Bir2307Controller.php`
- Views: `/views/bir2307/`
- Migration: `/database/migrations/add_bir_2307_support.sql`
- Routes: Added to `/routes.php`

### Configuration
- Company TIN in `/config/company.php`
- Form settings in `bir_2307_config` table
- Role permissions in existing role system

This integration ensures ConstructLink complies with Philippine BIR requirements for tax withholding while maintaining the system's existing workflow and user experience.