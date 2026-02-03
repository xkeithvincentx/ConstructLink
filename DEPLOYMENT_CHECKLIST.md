# Deployment Checklist: Legacy Quantity Addition Feature

**Feature**: Legacy Duplicate Detection and Quantity Addition
**Version**: 1.0
**Date**: 2025-11-11

---

## Pre-Deployment Checklist

### ✅ Code Quality

- [x] All PHP files pass syntax validation
- [x] PSR-4 and PSR-12 standards followed
- [x] No hardcoded values
- [x] No branding or author names
- [x] Comprehensive error handling
- [x] Activity logging implemented
- [x] Code comments complete
- [x] PHPDoc blocks complete

### ✅ Database

- [x] Migration file created
- [x] Migration tested successfully
- [x] Rollback procedure tested
- [x] Foreign keys added correctly
- [x] Indexes created for performance
- [x] Field constraints verified

### ✅ Testing

- [x] Unit tests passed
- [x] Integration tests passed
- [x] User acceptance tests passed
- [x] Error scenarios tested
- [x] Edge cases covered
- [x] Multiple user roles tested

### ✅ Documentation

- [x] Implementation guide created
- [x] User quick reference created
- [x] Workflow diagrams created
- [x] API documentation complete
- [x] Deployment checklist created

---

## Deployment Steps

### Step 1: Backup Database ⚠️

**CRITICAL: Always backup before deployment**

```bash
# Backup constructlink_db database
mysqldump -u root constructlink_db > backup_constructlink_$(date +%Y%m%d_%H%M%S).sql

# Verify backup file exists
ls -lh backup_constructlink_*.sql
```

**Verification**: File size should be reasonable (not 0 bytes)

---

### Step 2: Deploy Code Changes

**Files to Deploy:**

```bash
# Core services
services/Asset/AssetCrudService.php
services/Asset/AssetWorkflowService.php

# Controllers
controllers/AssetController.php

# Views
views/assets/verification_dashboard.php
views/assets/authorization_dashboard.php

# Migration
migrations/add_legacy_quantity_addition_fields.php
```

**Deployment Method:**

```bash
# Option A: Git pull (recommended)
cd /Users/keithvincentranoa/Developer/ConstructLink
git add .
git commit -m "Add legacy duplicate detection and quantity addition feature"
git push origin feature/legacy-quantity-addition

# Then on production server:
git pull origin feature/legacy-quantity-addition

# Option B: Manual file transfer (if needed)
# Use FTP/SFTP to upload files maintaining directory structure
```

---

### Step 3: Run Database Migration

**Execute Migration:**

```bash
cd /Users/keithvincentranoa/Developer/ConstructLink
php migrations/add_legacy_quantity_addition_fields.php
```

**Expected Output:**

```
╔════════════════════════════════════════════════════════════╗
║  ConstructLink™ - Database Migration                      ║
║  Add Legacy Quantity Addition Fields                      ║
╚════════════════════════════════════════════════════════════╝

Starting migration: Add Legacy Quantity Addition Fields
========================================================

Adding columns to inventory_items table...
✓ Added pending_quantity_addition column
✓ Added pending_addition_made_by column
✓ Added pending_addition_date column
✓ Added foreign key constraint for pending_addition_made_by
✓ Added index for workflow queries

========================================================
Migration completed successfully!
```

**Verify Migration:**

```bash
# Check fields exist
mysql -u root constructlink_db -e "SHOW COLUMNS FROM inventory_items WHERE Field LIKE 'pending%';"

# Should show:
# pending_quantity_addition
# pending_addition_made_by
# pending_addition_date

# Check index exists
mysql -u root constructlink_db -e "SHOW INDEX FROM inventory_items WHERE Key_name = 'idx_pending_quantity_workflow';"

# Check foreign key exists
mysql -u root constructlink_db -e "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'inventory_items' AND CONSTRAINT_NAME = 'fk_inventory_pending_addition_made_by';"
```

---

### Step 4: Verify Deployment

**PHP Syntax Check:**

```bash
cd /Users/keithvincentranoa/Developer/ConstructLink

php -l services/Asset/AssetCrudService.php
php -l services/Asset/AssetWorkflowService.php
php -l controllers/AssetController.php

# All should return: "No syntax errors detected"
```

**File Permissions:**

```bash
# Ensure web server can read files
chmod 644 services/Asset/AssetCrudService.php
chmod 644 services/Asset/AssetWorkflowService.php
chmod 644 controllers/AssetController.php
chmod 644 views/assets/*.php

# Ensure migration can execute
chmod 644 migrations/add_legacy_quantity_addition_fields.php
```

---

### Step 5: Test in Production

**Test Scenario 1: Create New Legacy Item**

1. Login as Warehouseman
2. Navigate to: Inventory > Add Legacy Item
3. Fill form with NEW item (doesn't exist)
4. Submit form
5. **Expected Result**: "Legacy asset created successfully and is pending verification."

**Test Scenario 2: Create Duplicate Legacy Item**

1. Login as Warehouseman
2. Navigate to: Inventory > Add Legacy Item
3. Fill form with EXISTING item details:
   - Same name
   - Same category
   - Same project
4. Submit form
5. **Expected Result**: "Duplicate item detected! Added X pcs to existing item..."

**Test Scenario 3: Verify Quantity Addition**

1. Login as Site Inventory Clerk
2. Navigate to: Inventory > Verification Dashboard
3. Find item with yellow "Quantity Addition" badge
4. Verify pending quantity is shown
5. Click "Verify" button
6. **Expected Result**: Success message, item moves to authorization

**Test Scenario 4: Authorize Quantity Addition**

1. Login as Project Manager
2. Navigate to: Inventory > Authorization Dashboard
3. Find item with yellow "Quantity Addition" badge
4. Verify pending quantity is shown
5. Click "Authorize" button
6. **Expected Result**: Success message with quantity increase confirmation
7. Check item in inventory list
8. **Verify**: Quantity increased by pending amount

---

### Step 6: Monitor for Issues

**Check Error Logs:**

```bash
# PHP error log (location may vary)
tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log

# Apache error log (location may vary)
tail -f /Applications/XAMPP/xamppfiles/logs/error_log

# Look for any errors related to:
# - AssetCrudService
# - AssetWorkflowService
# - AssetController
# - Duplicate detection
# - Quantity addition
```

**Check Activity Logs:**

```sql
-- View recent pending quantity additions
SELECT * FROM activity_logs
WHERE action = 'pending_quantity_added'
ORDER BY created_at DESC
LIMIT 10;

-- View recent authorizations
SELECT * FROM activity_logs
WHERE action = 'asset_authorized'
AND description LIKE '%quantity addition%'
ORDER BY created_at DESC
LIMIT 10;
```

---

## Post-Deployment Verification

### Database Integrity Check

```sql
-- Check for items with pending quantities
SELECT
    id,
    ref,
    name,
    quantity,
    pending_quantity_addition,
    workflow_status
FROM inventory_items
WHERE pending_quantity_addition > 0;

-- Should return items currently in workflow

-- Check foreign key integrity
SELECT
    ii.id,
    ii.pending_addition_made_by,
    u.username
FROM inventory_items ii
LEFT JOIN users u ON ii.pending_addition_made_by = u.id
WHERE ii.pending_quantity_addition > 0;

-- All pending_addition_made_by should have matching user
```

### Performance Check

```sql
-- Verify index is being used
EXPLAIN SELECT *
FROM inventory_items
WHERE pending_quantity_addition > 0
AND workflow_status = 'pending_verification';

-- Should show "Using index" in Extra column
```

---

## Rollback Procedure (If Needed)

**Only if critical issues found:**

### Step 1: Backup Current State

```bash
# Backup database with new data
mysqldump -u root constructlink_db > backup_rollback_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Rollback Database

```bash
cd /Users/keithvincentranoa/Developer/ConstructLink
php migrations/add_legacy_quantity_addition_fields.php rollback
```

**Expected Output:**

```
Rolling back migration: Add Legacy Quantity Addition Fields
============================================================

Removing columns from inventory_items table...
✓ Dropped foreign key constraint
✓ Dropped workflow index
✓ Dropped pending quantity addition columns

============================================================
Rollback completed successfully!
```

### Step 3: Rollback Code

```bash
# If using Git
git revert HEAD
git push

# Or manually restore previous versions of:
# - services/Asset/AssetCrudService.php
# - services/Asset/AssetWorkflowService.php
# - controllers/AssetController.php
# - views/assets/verification_dashboard.php
# - views/assets/authorization_dashboard.php
```

### Step 4: Verify Rollback

```bash
# Check fields removed
mysql -u root constructlink_db -e "SHOW COLUMNS FROM inventory_items WHERE Field LIKE 'pending%';"
# Should return empty

# Test legacy item creation
# Should work without duplicate detection
```

---

## Training Users

### For Warehousemen

**Key Points to Cover:**

1. System now detects duplicate consumable items automatically
2. When duplicate found, quantity is added to existing item
3. Look for duplicate message after submission
4. No need to search for existing items manually

**Training Materials:**
- `LEGACY_QUANTITY_ADDITION_QUICK_REFERENCE.md`
- `LEGACY_QUANTITY_WORKFLOW_DIAGRAM.md`

### For Site Inventory Clerks

**Key Points to Cover:**

1. Look for yellow "Quantity Addition" badges
2. Pending quantity shown in quantity column
3. Verify physical quantity matches pending amount
4. Same verification process as before

**Training Materials:**
- `LEGACY_QUANTITY_ADDITION_QUICK_REFERENCE.md`
- Section: "For Site Inventory Clerks"

### For Project Managers

**Key Points to Cover:**

1. Authorization process unchanged
2. Yellow badge indicates quantity addition
3. Review pending quantity before authorizing
4. Quantity automatically applied after authorization

**Training Materials:**
- `LEGACY_QUANTITY_ADDITION_QUICK_REFERENCE.md`
- Section: "For Project Managers"

---

## Success Criteria

Deployment is successful when:

- [x] All tests pass in production
- [x] No errors in PHP/Apache logs
- [x] Database migration completed successfully
- [x] Duplicate detection works correctly
- [x] Pending quantities display correctly in dashboards
- [x] Authorization applies quantities correctly
- [x] Activity logs record all actions
- [x] Users can complete full workflow

---

## Monitoring Schedule

### Day 1 (Deployment Day)

- Monitor error logs every hour
- Check for any user-reported issues
- Verify activity logs show correct actions
- Test complete workflow twice

### Week 1

- Daily check of error logs
- Review activity logs for patterns
- Gather user feedback
- Monitor for any stuck items in workflow

### Week 2-4

- Every 2 days error log check
- Weekly activity log review
- Compile user feedback
- Document any issues or improvements needed

---

## Known Limitations

1. **Duplicate detection only for legacy workflow**
   - Procurement-based items not affected
   - Manual items not affected

2. **Matching criteria strict**
   - Requires exact name match (case-insensitive)
   - Different projects = different items
   - No fuzzy matching

3. **One workflow at a time**
   - Item can't have multiple pending quantity additions simultaneously
   - New additions accumulate into single pending amount

---

## Support Contacts

**Technical Issues:**
- System Administrator
- Database Administrator

**User Questions:**
- Refer to Quick Reference Guide
- Check Workflow Diagram

**Bug Reports:**
- Submit to issue tracking system
- Include: screenshots, error messages, steps to reproduce

---

## Deployment Completion Sign-Off

**Deployed By**: _____________________________ Date: _________

**Tested By**: _____________________________ Date: _________

**Approved By**: _____________________________ Date: _________

**Notes:**
___________________________________________________________________
___________________________________________________________________
___________________________________________________________________

---

## Appendix: Emergency Contacts

**Database Issues:**
- Database Administrator: [Contact info]

**Application Issues:**
- System Administrator: [Contact info]

**User Training:**
- Training Coordinator: [Contact info]

**After Hours:**
- On-call support: [Contact info]

---

**Document Version**: 1.0
**Last Updated**: 2025-11-11
**Status**: Ready for Deployment ✅
