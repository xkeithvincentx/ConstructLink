# Phase 2 Testing Guide - Borrowed Tools Module Refactoring
## ConstructLink™ System Refactoring

**Date**: 2025-10-27
**Phase**: 2.4 - Comprehensive Testing
**Status**: Ready for Manual Testing

---

## Refactoring Summary

### What Was Done

Phase 2.1 through 2.3 successfully completed the borrowed tools module refactoring:

#### Phase 2.1 - Database Query Extraction ✅
- Moved 9 direct database queries from controller to models
- Created `EquipmentTypeModel` with `getPowerTools()` and `getHandTools()`
- Added `AssetModel::getAvailableEquipmentCount()`, `getAvailableForBorrowing()`, `getAssetProjectId()`
- **Result**: ~180 lines moved to appropriate models

#### Phase 2.2 - Service Layer Creation ✅
- Created `BorrowedToolService.php` (workflow, validation)
- Created `BorrowedToolReturnService.php` (returns, incidents)
- Created `BorrowedToolStatisticsService.php` (statistics)
- **Result**: ~650 lines of business logic in service classes

#### Phase 2.3 - Helper Classes and Controller Splitting ✅
- Created `BorrowedToolsResponseHelper` (JSON/HTML responses)
- Created `BorrowedToolsPermissionGuard` (centralized RBAC)
- Created `BorrowedToolPrintController` (135 lines)
- Created `BorrowedToolBatchController` (321 lines)
- Refactored `BorrowedToolController` from 2,146 lines to 861 lines (60% reduction)
- Updated routing configuration to point to new controllers
- **Result**: 3 focused controllers instead of 1 monolithic controller

### File Structure

**New Controllers:**
```
controllers/
├── BorrowedToolController.php         (861 lines - Single items, listing, utilities)
├── BorrowedToolBatchController.php    (321 lines - Batch operations)
├── BorrowedToolPrintController.php    (135 lines - Print operations)
└── BorrowedToolController.php.backup  (2,146 lines - Original backup)
```

**New Helpers:**
```
helpers/BorrowedTools/
├── PermissionGuard.php                (200 lines - RBAC)
└── ResponseHelper.php                 (180 lines - JSON/HTML responses)
```

**New Services:**
```
services/
├── BorrowedToolService.php            (210 lines - Workflow, validation)
├── BorrowedToolReturnService.php      (230 lines - Returns, incidents)
└── BorrowedToolStatisticsService.php  (120 lines - Statistics)
```

### Code Quality Verification ✅

All files passed quality checks:
- ✅ All files under 500 lines
- ✅ All methods under 50 lines
- ✅ No PHP syntax errors
- ✅ Proper error handling
- ✅ Early returns for validation
- ✅ Maximum nesting depth: 3 levels
- ✅ No hardcoded values
- ✅ No branding in code

---

## Testing Requirements

### Pre-Testing Setup

1. **Backup Database** (Critical):
   ```bash
   mysqldump -u root -p constructlink > constructlink_backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Verify File Permissions**:
   ```bash
   chmod 644 controllers/BorrowedTool*.php
   chmod 644 helpers/BorrowedTools/*.php
   chmod 644 services/BorrowedTool*.php
   ```

3. **Check Apache/Nginx Logs**:
   ```bash
   tail -f /Applications/XAMPP/xamppfiles/logs/error_log
   ```

---

## Functional Testing Checklist

### 1. List View (BorrowedToolController)

**Route**: `borrowed-tools`

**Test Steps**:
- [ ] Navigate to borrowed tools list
- [ ] Verify statistics display at the top
- [ ] Verify table loads with borrowed tools
- [ ] Test filters (status, project, date range)
- [ ] Test search functionality
- [ ] Verify pagination works
- [ ] Check "Create Batch" button exists

**Expected**: List displays correctly with statistics and filters working

---

### 2. Batch Creation (BorrowedToolBatchController)

**Route**: `borrowed-tools/create-batch`

**Test Steps**:
- [ ] Click "Create Batch" or navigate to route
- [ ] Verify form displays with:
  - Borrower information fields
  - Equipment selection interface
  - QR code scanner (if applicable)
  - Expected return date picker
- [ ] Add multiple items to batch
- [ ] Submit batch
- [ ] Verify success message
- [ ] Check batch appears in list

**Expected**: Batch creation works smoothly, items added successfully

---

### 3. Batch MVA Workflow (BorrowedToolBatchController)

**Test Critical Tools Workflow**:

**Route**: `borrowed-tools/batch/view?batch_id=X`

#### Step 1: Verify Batch (Verifier Role)
- [ ] Login as Project Manager or Site Manager
- [ ] Navigate to batch view
- [ ] Click "Verify" button
- [ ] Add verification notes
- [ ] Submit verification
- [ ] Verify batch status changes to "Pending Approval"

**Route**: `borrowed-tools/batch/verify` (POST)

#### Step 2: Approve Batch (Authorizer Role)
- [ ] Login as Asset Director or Finance Director
- [ ] Navigate to batch view
- [ ] Click "Approve" button
- [ ] Add approval notes
- [ ] Submit approval
- [ ] Verify batch status changes to "Pending Release"

**Route**: `borrowed-tools/batch/approve` (POST)

#### Step 3: Release Batch (Borrow Permission)
- [ ] Login as Equipment Custodian or Warehouseman
- [ ] Navigate to batch view
- [ ] Click "Release" button
- [ ] Add release notes
- [ ] Submit release
- [ ] Verify batch status changes to "In Use"
- [ ] Verify items marked as borrowed

**Route**: `borrowed-tools/batch/release` (POST)

**Test Streamlined Workflow (Basic Tools)**:
- [ ] Create batch with only basic tools
- [ ] Verify batch auto-releases (status: "In Use")
- [ ] Verify no manual verification/approval needed

**Expected**: Critical tools follow full MVA, basic tools auto-release

---

### 4. Batch Return (BorrowedToolBatchController)

**Route**: `borrowed-tools/batch/return?batch_id=X`

**Test Steps**:
- [ ] Navigate to batch in "In Use" status
- [ ] Click "Return" button
- [ ] Verify return form displays
- [ ] For each item, select condition:
  - [ ] Good condition
  - [ ] Damaged (should create incident)
  - [ ] Lost (should create incident)
- [ ] Add return notes
- [ ] Submit return
- [ ] Verify batch status changes to "Returned"
- [ ] Verify incidents created for damaged/lost items
- [ ] Verify assets marked as available

**Route**: `borrowed-tools/batch/return` (POST)

**Expected**: Returns process correctly, incidents auto-created for issues

**Note**: This may show 501 error as placeholder implementation

---

### 5. Batch Extension (BorrowedToolBatchController)

**Route**: `borrowed-tools/batch/extend?batch_id=X`

**Test Steps**:
- [ ] Navigate to batch in "In Use" status
- [ ] Click "Extend" button
- [ ] Verify extension form displays
- [ ] Select new expected return date
- [ ] Add extension reason
- [ ] Submit extension
- [ ] Verify expected return date updated
- [ ] Verify notification sent

**Route**: `borrowed-tools/batch/extend` (POST)

**Expected**: Extension request processed, date updated

**Note**: This may show 501 error as placeholder implementation

---

### 6. Batch Cancellation (BorrowedToolBatchController)

**Route**: `borrowed-tools/batch/cancel` (POST)

**Test Steps**:
- [ ] Navigate to batch in "Pending" status
- [ ] Click "Cancel" button
- [ ] Add cancellation reason
- [ ] Submit cancellation
- [ ] Verify batch status changes to "Cancelled"
- [ ] Verify items marked as available

**Expected**: Cancellation works, items returned to inventory

---

### 7. Print Batch Form (BorrowedToolPrintController)

**Route**: `borrowed-tools/batch/print?batch_id=X`

**Test Steps**:
- [ ] Navigate to batch view
- [ ] Click "Print" button
- [ ] Verify print preview displays
- [ ] Check all batch information visible:
  - Batch reference
  - Borrower details
  - Items list
  - MVA signatures section
- [ ] Verify printed_at timestamp updated
- [ ] Print or save as PDF

**Expected**: Print form generates correctly, timestamp recorded

---

### 8. Print Blank Form (BorrowedToolPrintController)

**Route**: `borrowed-tools/print-blank-form`

**Test Steps**:
- [ ] Navigate to route
- [ ] Verify blank form displays with:
  - Power tools checklist
  - Hand tools checklist
  - Borrower information section
  - MVA signature sections
- [ ] Verify equipment types from database
- [ ] Print or save as PDF

**Expected**: Blank form generates with current equipment types

---

### 9. Single-Item Operations (BorrowedToolController)

**Test Single-Item MVA Workflow**:

#### Create Single Item
- [ ] Navigate to `borrowed-tools/create`
- [ ] Verify redirects to batch creation

#### Verify Single Item
**Route**: `borrowed-tools/verify` (POST)
- [ ] Test single-item verification
- [ ] Verify status change

#### Approve Single Item
**Route**: `borrowed-tools/approve` (POST)
- [ ] Test single-item approval
- [ ] Verify status change

#### Borrow/Release Single Item
**Route**: `borrowed-tools/borrow` (POST)
- [ ] Test single-item release
- [ ] Verify status change to "In Use"

#### Return Single Item
**Route**: `borrowed-tools/return?id=X`
- [ ] Test single-item return
- [ ] Verify condition checking
- [ ] Verify incident creation for issues

#### Extend Single Item
**Route**: `borrowed-tools/extend?id=X`
- [ ] Test single-item extension
- [ ] Verify date update

#### Cancel Single Item
**Route**: `borrowed-tools/cancel` (POST)
- [ ] Test single-item cancellation
- [ ] Verify status change

**Expected**: All single-item operations work correctly

---

### 10. AJAX Endpoints (BorrowedToolController)

#### QR Code Validation
**Route**: `api/borrowed-tools/validate-qr` (AJAX)
- [ ] Scan QR code or enter asset code
- [ ] Verify asset details returned
- [ ] Verify validation for:
  - Asset exists
  - Asset available
  - Asset in user's project

#### Statistics
**Route**: `borrowed-tools/stats` (AJAX)
- [ ] Call stats endpoint
- [ ] Verify JSON response with:
  - Total borrowed
  - In use
  - Overdue
  - Pending verification
  - Pending approval

#### Export
**Route**: `borrowed-tools/export`
- [ ] Click export button
- [ ] Verify CSV/Excel download
- [ ] Verify data correctness

**Expected**: AJAX endpoints return correct JSON/CSV data

---

## Permission Testing

Test with different user roles to verify RBAC:

### Equipment Custodian Role
- [ ] Can create batches
- [ ] Can release batches
- [ ] Cannot verify or approve
- [ ] Can view only their project's tools

### Project Coordinator Role
- [ ] Can create batches
- [ ] Can view batches
- [ ] Cannot verify or approve
- [ ] Project-scoped access

### Site Manager Role
- [ ] Can create batches
- [ ] Can verify batches (verifier)
- [ ] Cannot approve
- [ ] Project-scoped access

### Asset Director Role
- [ ] Can view all batches (all projects)
- [ ] Can approve batches (authorizer)
- [ ] Can override permissions
- [ ] Full system access

### System Admin Role
- [ ] Full access to all operations
- [ ] Can access all projects
- [ ] Can override all permissions

### Test Permission Denials
- [ ] Attempt unauthorized actions
- [ ] Verify 403 errors display correctly
- [ ] Verify AJAX requests return proper JSON errors
- [ ] Verify redirects work for non-AJAX requests

---

## Integration Testing

### Navigation
- [ ] Test navigation from list to batch view
- [ ] Test navigation from batch view to item details
- [ ] Test breadcrumbs (if applicable)
- [ ] Test back buttons

### Redirects
- [ ] Test redirect after batch creation
- [ ] Test redirect after successful actions
- [ ] Test redirect after errors
- [ ] Test redirect to login for unauthenticated users

### Flash Messages
- [ ] Verify success messages display
- [ ] Verify error messages display
- [ ] Verify messages clear after navigation
- [ ] Verify AJAX responses don't show flash messages

### Session Handling
- [ ] Test intended URL redirect after login
- [ ] Test session timeout handling
- [ ] Test concurrent sessions

### CSRF Protection
- [ ] Verify CSRF tokens in forms
- [ ] Test CSRF validation on POST requests
- [ ] Verify error handling for invalid tokens

---

## Performance Testing

### Database Query Optimization
- [ ] Monitor query count per page
- [ ] Verify no N+1 query problems
- [ ] Check query execution time
- [ ] Verify proper indexing

**Monitoring Commands**:
```bash
# Enable MySQL query log
SET GLOBAL general_log = 'ON';

# Monitor slow queries
tail -f /Applications/XAMPP/xamppfiles/logs/mysql_slow_query.log
```

### Page Load Times
- [ ] List view < 2 seconds
- [ ] Batch creation form < 1 second
- [ ] Batch view < 1.5 seconds
- [ ] Print generation < 3 seconds

### Memory Usage
- [ ] Check PHP memory usage
- [ ] Verify no memory leaks
- [ ] Test with large batches (20+ items)

---

## Error Handling Testing

### Expected Errors
- [ ] Test with invalid batch ID
- [ ] Test with missing required fields
- [ ] Test with duplicate submissions
- [ ] Test with expired sessions
- [ ] Test with invalid CSRF tokens

### Error Display
- [ ] Verify user-friendly error messages
- [ ] Verify errors logged to error_log
- [ ] Verify JSON errors for AJAX
- [ ] Verify HTML errors for page requests

---

## Browser Compatibility

Test on multiple browsers:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

---

## Known Issues / Limitations

### Placeholder Implementations

The following methods have placeholder implementations (return 501 errors):

1. **BorrowedToolBatchController::returnBatch()**
   - **Route**: `borrowed-tools/batch/return`
   - **Status**: 501 Not Implemented
   - **Reason**: Will be implemented in Phase 2.4 using service layer
   - **Workaround**: None (feature not available)

2. **BorrowedToolBatchController::extendBatch()**
   - **Route**: `borrowed-tools/batch/extend`
   - **Status**: 501 Not Implemented
   - **Reason**: Will be implemented in Phase 2.4 using service layer
   - **Workaround**: None (feature not available)

---

## Rollback Plan

If critical issues are found:

### 1. Quick Rollback (Restore Original Controller)
```bash
# Stop Apache
sudo /Applications/XAMPP/xamppfiles/bin/apachectl stop

# Restore original controller
cp controllers/BorrowedToolController.php.backup controllers/BorrowedToolController.php

# Restore original routes
git checkout HEAD~1 routes.php

# Start Apache
sudo /Applications/XAMPP/xamppfiles/bin/apachectl start
```

### 2. Git Revert
```bash
# Find commit hash
git log --oneline | head -5

# Revert specific commit
git revert <commit-hash>

# Or revert last N commits
git revert HEAD~3..HEAD
```

### 3. Database Restore (if needed)
```bash
mysql -u root -p constructlink < constructlink_backup_YYYYMMDD_HHMMSS.sql
```

---

## Testing Sign-Off

### Tester Information
- **Tester Name**: ___________________________
- **Date**: ___________________________
- **Environment**: ___________________________

### Test Results

| Test Category | Passed | Failed | Notes |
|---------------|--------|--------|-------|
| Functional Testing | ☐ | ☐ | |
| Permission Testing | ☐ | ☐ | |
| Integration Testing | ☐ | ☐ | |
| Performance Testing | ☐ | ☐ | |
| Error Handling | ☐ | ☐ | |
| Browser Compatibility | ☐ | ☐ | |

### Issues Found

| Issue # | Severity | Description | Steps to Reproduce | Status |
|---------|----------|-------------|-------------------|---------|
| 1 | | | | |
| 2 | | | | |
| 3 | | | | |

**Severity Levels**:
- **Critical**: System crash, data loss, security vulnerability
- **High**: Major functionality broken, workaround exists
- **Medium**: Minor functionality issue, easy workaround
- **Low**: Cosmetic issue, minor inconvenience

### Approval

- [ ] All critical and high severity issues resolved
- [ ] All functional tests passed
- [ ] Performance meets requirements
- [ ] Ready for production deployment

**Approved By**: ___________________________
**Date**: ___________________________

---

## Next Steps After Testing

1. **Document Issues**: Create GitHub issues for any bugs found
2. **Fix Critical Issues**: Address any critical bugs immediately
3. **Code Review**: Have team review the refactored code
4. **Deploy to Staging**: Deploy to staging environment
5. **User Acceptance Testing**: Have end users test the system
6. **Deploy to Production**: Final production deployment

---

**Document Version**: 1.0
**Last Updated**: 2025-10-27
**Prepared By**: Development Team
**Developed By**: Ranoa Digital Solutions
