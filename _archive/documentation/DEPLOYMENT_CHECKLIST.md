# BIR Form 2307 Integration - Deployment Checklist

## ✅ Pre-Deployment Steps

### 1. Database Migration
Run the following SQL migration to add BIR 2307 support:

```bash
mysql -u [username] -p [database_name] < database/migrations/add_bir_2307_support.sql
```

**What this adds:**
- ✅ Vendor table enhancements (vendor_type, TIN, name fields, etc.)
- ✅ ATC codes table with 30+ pre-populated tax codes
- ✅ BIR 2307 forms table for form storage
- ✅ Procurement items enhancements (purchase_type, ATC codes)
- ✅ Configuration and audit tables

### 2. File Verification
Ensure all new files are in place:

**Models:**
- ✅ `/models/AtcCodeModel.php`
- ✅ `/models/Bir2307Model.php`

**Controllers:**
- ✅ `/controllers/Bir2307Controller.php`

**Views:**
- ✅ `/views/bir2307/print-preview.php`
- ✅ `/views/bir2307/view.php`

**Documentation:**
- ✅ `/BIR_2307_INTEGRATION_GUIDE.md`

### 3. Code Updates Applied
- ✅ `/index.php` - Added new models to autoload list
- ✅ `/routes.php` - Added BIR 2307 routes
- ✅ `/models/VendorModel.php` - Enhanced with BIR fields
- ✅ `/models/ProcurementItemModel.php` - Added purchase types and ATC support
- ✅ `/views/procurement-orders/view.php` - Added BIR 2307 generation button

## ✅ Post-Deployment Setup

### 1. Update Existing Vendors
For each vendor in your system, you should add:
- **TIN (Tax Identification Number)** in XXX-XXX-XXX-XXX format
- **Vendor Type**: Company, Sole Proprietor, Partnership, etc.
- **For Sole Proprietors**: Add first_name, middle_name, last_name
- **ZIP Code** for addresses

### 2. Configure Company Information
Verify `/config/company.php` has correct:
- Company TIN: `007-608-972-000` (update if different)
- Complete address information
- Contact details

### 3. Test the Integration

#### Test Case 1: Basic Form Generation
1. Create or find a procurement order with status "Paid", "Completed", or "Received"
2. Ensure the vendor has a TIN assigned
3. Navigate to the procurement order view page
4. Look for "Generate BIR 2307" button (should appear for Finance Officer, Procurement Officer, or System Admin)
5. Click to generate the form
6. Verify form details are correct

#### Test Case 2: Print Preview
1. From a generated BIR 2307 form, click "Print Form"
2. Verify the print preview matches official BIR format:
   - ✅ TIN boxes are properly formatted
   - ✅ Vendor name displays correctly (company vs individual)
   - ✅ Quarter and period are accurate
   - ✅ Income payments table shows ATC codes and amounts
   - ✅ Total calculations are correct

#### Test Case 3: Mixed Purchase Types
1. Create a procurement order with different item types:
   - Some items as "Goods" (should use WC156 - 1%)
   - Some items as "Services" (should use WC157 - 2%)
   - Some items as "Rental" (should use WC030 - 5%)
2. Generate BIR 2307 and verify each ATC code appears with correct rates

## ✅ System Features Verified

### Vendor Management
- ✅ Support for Company vs Sole Proprietor
- ✅ TIN field with validation
- ✅ Individual name fields for sole proprietors
- ✅ Business name vs individual name handling

### ATC Code System
- ✅ 30+ common ATC codes pre-populated
- ✅ Automatic rate assignment based on purchase type
- ✅ EWT calculation: base amount × ATC rate
- ✅ VAT inclusion/exclusion logic

### Form Generation
- ✅ Automatic quarter determination from PO date
- ✅ Vendor name logic (company name vs individual)
- ✅ Income payments grouped by ATC code
- ✅ Multiple items aggregated correctly
- ✅ Official BIR form layout and formatting

### Security & Access
- ✅ Role-based access control
- ✅ Audit trail for all form actions
- ✅ Form status workflow (Generated → Printed → Submitted)
- ✅ Form numbering system (2307-YYYY-NNNNN)

## ✅ Troubleshooting

### Common Issues

**"Generate BIR 2307" button doesn't appear:**
- Check user role (must be System Admin, Finance Officer, or Procurement Officer)
- Verify procurement order status (must be Paid, Completed, or Received)
- Run database migration if bir_2307_generated column is missing

**"Class not found" errors:**
- Verify models are added to `/index.php` autoload list
- Check file permissions on new files
- Ensure database migration completed successfully

**TIN formatting issues:**
- TIN should be in XXX-XXX-XXX-XXX format
- Update vendor records with proper TIN format
- Check company TIN in `/config/company.php`

**Print preview formatting problems:**
- Ensure modern browser with CSS3 support
- Check print media queries are enabled
- Verify company information is complete

### Rollback Plan
If issues occur, you can:
1. Comment out BIR routes in `/routes.php`
2. Remove new models from `/index.php` autoload
3. Hide BIR button in procurement order view
4. Database changes are additive, so existing functionality won't break

## ✅ Maintenance

### Regular Tasks
- **Quarterly**: Generate batch BIR 2307 forms for filing period
- **Annually**: Archive old forms and update ATC codes if BIR rates change
- **As needed**: Add new vendors with proper TIN and vendor type

### Backup Considerations
Include in regular backups:
- `atc_codes` table (tax code definitions)
- `bir_2307_forms` table (generated forms)
- `bir_2307_config` table (configuration)
- `bir_2307_audit_log` table (audit trail)

The BIR Form 2307 integration is now ready for production use! 🎉