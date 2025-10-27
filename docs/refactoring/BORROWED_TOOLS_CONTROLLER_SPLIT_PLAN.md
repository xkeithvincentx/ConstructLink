# Borrowed Tools Controller Split Plan
## Phase 2.3 Execution Guide

**Status**: In Progress
**Started**: 2025-01-27
**Current Line Count**: 2,146 lines
**Target**: 3 controllers, avg 300-400 lines each

---

## Completed Steps ✅

### Phase 2.1 - Database Queries ✅
- Created `AssetModel::getAvailableEquipmentCount()`
- Created `AssetModel::getAvailableForBorrowing()`
- Created `AssetModel::getAssetProjectId()`
- Created `EquipmentTypeModel` with `getPowerTools()` and `getHandTools()`
- Eliminated 9 direct DB queries from controller

### Phase 2.2 - Service Layer ✅
- Created `BorrowedToolService.php` (workflow, validation)
- Created `BorrowedToolReturnService.php` (returns, incidents)
- Created `BorrowedToolStatisticsService.php` (statistics)

### Phase 2.3a - Helper Classes ✅
- Created `BorrowedToolsResponseHelper` (responses)
- Created `BorrowedToolsPermissionGuard` (RBAC)

### Phase 2.3b - Print Controller ✅
- Created `BorrowedToolPrintController` (135 lines)
- Extracted `printBatchForm()` and `printBlankForm()`

---

## Remaining Work

### Step 1: Create BorrowedToolBatchController.php

**Target Size**: 400-450 lines
**Methods to Extract**:

```php
// Batch Creation
public function createBatch()      // Line 1366 - GET form
public function storeBatch()       // Line 1401 - POST create

// Batch Viewing
public function viewBatch()        // Line 1505 - View batch details

// Batch MVA Workflow
public function verifyBatch()      // Line 1543 - POST verify
public function approveBatch()     // Line 1557 - POST approve
public function releaseBatch()     // Line 1571 - POST release

// Batch Returns
public function returnBatch()      // Line 1594 - GET/POST return form
// Note: processReturnSubmission may be part of returnBatch

// Batch Extensions
public function extendBatch()      // Line 1935 - GET/POST extend

// Batch Cancellation
public function cancelBatch()      // Line 2012 - POST cancel

// Helper method (keep private)
private function handleBatchMVAAction() // Line 298 - Template method
```

**Implementation Pattern**:
```php
<?php
/**
 * ConstructLink™ Borrowed Tool Batch Controller
 * Handles batch borrowed tool operations and MVA workflows
 * Phase 2.3 Refactoring - Extracted from monolithic BorrowedToolController
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';
require_once APP_ROOT . '/helpers/BorrowedTools/PermissionGuard.php';
require_once APP_ROOT . '/helpers/BorrowedTools/ResponseHelper.php';
require_once APP_ROOT . '/services/BorrowedToolService.php';
require_once APP_ROOT . '/services/BorrowedToolReturnService.php';

class BorrowedToolBatchController {
    private $permissionGuard;
    private $batchModel;
    private $borrowingService;
    private $returnService;

    public function __construct() {
        $this->permissionGuard = new BorrowedToolsPermissionGuard();

        // Authenticate
        if (!$this->permissionGuard->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }

        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';

        $this->batchModel = new BorrowedToolBatchModel();
        $this->borrowingService = new BorrowedToolService();
        $this->returnService = new BorrowedToolReturnService();
    }

    // ... implement methods
}
```

**Key Changes from Original**:
1. Use `$this->permissionGuard` instead of direct `hasBorrowedToolPermission()`
2. Use `BorrowedToolsResponseHelper::sendError()` instead of `$this->sendError()`
3. Use `BorrowedToolsResponseHelper::sendSuccess()` instead of `$this->sendSuccess()`
4. Keep `handleBatchMVAAction()` as private helper in this controller
5. Each method should be under 50 lines
6. Use early returns for validation
7. Proper error handling with try-catch

---

### Step 2: Refactor Main BorrowedToolController.php

**Target Size**: 400-450 lines
**Methods to Keep**:

```php
// List and View
public function index()            // Line 435 - List all borrowed tools
public function view()             // Line 660 - View single tool details

// Single Item Creation
public function create()           // Line 642 - GET create form (references batches)

// Single Item MVA Workflow
public function verify()           // Line 1106 - Single item verify
public function approve()          // Line 1135 - Single item approve
public function borrow()           // Line 1153 - Single item borrow/release
public function cancel()           // Line 1171 - Single item cancel

// Single Item Returns and Extensions
public function returnTool()       // Line 774 - Single item return
public function extend()           // Line 842 - Single item extend

// AJAX/Utility Endpoints
public function validateQRForBorrowing() // Line 1251 - AJAX QR validation
public function getStats()         // Line 949 - AJAX stats
public function export()           // Line 979 - Export functionality
public function markOverdue()      // Line 918 - Background task
public function updateOverdueStatus() // Line 1049 - Background task
public function getOverdueContacts() // Line 1073 - Get overdue list

// Deprecated (document for removal)
public function statistics()       // Line 561 - DEPRECATED (remove)

// Helper methods (keep private)
private function requireProjectAssignment()  // Line 1190
private function getProjectFilter()          // Line 1212
private function getAvailableAssetsForBorrowing() // Line 1240 (already uses model)
private function isAssetInUserProject()      // Line 1230 (already uses model)
private function handleMVAWorkflowAction()   // Line 362 - Template for single items
```

**Methods to REMOVE** (moved to other controllers):
- All batch methods → `BorrowedToolBatchController`
- All print methods → `BorrowedToolPrintController` ✅ DONE
- Response helpers → Use `BorrowedToolsResponseHelper` static methods
- Permission checks → Use `$this->permissionGuard` methods

**Refactoring Steps**:
1. Remove all batch-related methods
2. Remove all print-related methods (already done)
3. Replace `$this->sendError()` with `BorrowedToolsResponseHelper::sendError()`
4. Replace `$this->sendSuccess()` with `BorrowedToolsResponseHelper::sendSuccess()`
5. Replace `$this->hasBorrowedToolPermission()` with `$this->permissionGuard->hasPermission()`
6. Replace `$this->requirePermission()` with `$this->permissionGuard->requirePermission()`
7. Inject `$this->permissionGuard` in constructor
8. Remove deprecated `statistics()` method
9. Update comments to reflect new scope

**Final Structure**:
```php
<?php
/**
 * ConstructLink™ Borrowed Tool Controller
 * Handles single-item borrowed tool operations and main listing
 * Phase 2.3 Refactoring - Split from monolithic controller
 *
 * Responsibilities:
 * - List view (index) with statistics
 * - Single-item MVA workflow (verify, approve, borrow, cancel)
 * - Single-item returns and extensions
 * - AJAX endpoints and utility functions
 *
 * Related Controllers:
 * - BorrowedToolBatchController: Batch operations
 * - BorrowedToolPrintController: Print functionality
 */
```

---

### Step 3: Update Routing Configuration

**File**: `/config/routes.php` or routing handler

**Add Batch Routes**:
```php
// Batch operations
'borrowed-tools/batch/create' => 'BorrowedToolBatchController@createBatch',
'borrowed-tools/batch/store' => 'BorrowedToolBatchController@storeBatch',
'borrowed-tools/batch/view' => 'BorrowedToolBatchController@viewBatch',
'borrowed-tools/batch/verify' => 'BorrowedToolBatchController@verifyBatch',
'borrowed-tools/batch/approve' => 'BorrowedToolBatchController@approveBatch',
'borrowed-tools/batch/release' => 'BorrowedToolBatchController@releaseBatch',
'borrowed-tools/batch/return' => 'BorrowedToolBatchController@returnBatch',
'borrowed-tools/batch/extend' => 'BorrowedToolBatchController@extendBatch',
'borrowed-tools/batch/cancel' => 'BorrowedToolBatchController@cancelBatch',
```

**Add Print Routes**:
```php
// Print operations
'borrowed-tools/print/batch' => 'BorrowedToolPrintController@printBatchForm',
'borrowed-tools/print/blank' => 'BorrowedToolPrintController@printBlankForm',
```

**Keep Main Routes**:
```php
// Main borrowed tools routes
'borrowed-tools' => 'BorrowedToolController@index',
'borrowed-tools/create' => 'BorrowedToolController@create',
'borrowed-tools/view' => 'BorrowedToolController@view',
'borrowed-tools/verify' => 'BorrowedToolController@verify',
'borrowed-tools/approve' => 'BorrowedToolController@approve',
'borrowed-tools/borrow' => 'BorrowedToolController@borrow',
'borrowed-tools/cancel' => 'BorrowedToolController@cancel',
'borrowed-tools/return' => 'BorrowedToolController@returnTool',
'borrowed-tools/extend' => 'BorrowedToolController@extend',
'borrowed-tools/validate-qr' => 'BorrowedToolController@validateQRForBorrowing',
'borrowed-tools/stats' => 'BorrowedToolController@getStats',
'borrowed-tools/export' => 'BorrowedToolController@export',
```

---

### Step 4: Testing Checklist

#### Functional Testing
- [ ] List view displays correctly
- [ ] Single-item creation works
- [ ] Batch creation works
- [ ] Single-item MVA workflow (verify → approve → borrow)
- [ ] Batch MVA workflow (verify → approve → release)
- [ ] Single-item return
- [ ] Batch return with condition checking
- [ ] Extension requests (single and batch)
- [ ] Cancellation (single and batch)
- [ ] Print batch form
- [ ] Print blank form
- [ ] QR code validation
- [ ] Statistics display
- [ ] Export functionality
- [ ] Overdue marking

#### Permission Testing
- [ ] Test with Equipment Custodian role
- [ ] Test with Project Coordinator role
- [ ] Test with Site Manager role
- [ ] Test with Asset Director role
- [ ] Test permission denials work correctly
- [ ] Test project-scoped access

#### Integration Testing
- [ ] Navigation between controllers works
- [ ] Redirects go to correct controllers
- [ ] Flash messages display correctly
- [ ] Session handling works
- [ ] CSRF validation works

---

## Code Quality Verification

After split, verify each controller meets standards:

### File Size
- [ ] BorrowedToolController.php: 300-450 lines ✅
- [ ] BorrowedToolBatchController.php: 400-450 lines ✅
- [ ] BorrowedToolPrintController.php: 100-150 lines ✅

### Function Size
- [ ] All functions under 50 lines
- [ ] Complex functions broken into helpers

### Code Quality
- [ ] No hardcoded values
- [ ] No magic numbers
- [ ] Early returns used
- [ ] Maximum nesting depth: 3
- [ ] Proper error handling
- [ ] No branding comments

### Documentation
- [ ] Class-level PHPDoc
- [ ] Method-level PHPDoc
- [ ] Inline comments for complex logic
- [ ] No deprecated code

---

## Rollback Plan

If issues arise:

1. **Revert to Previous Commit**:
```bash
git log --oneline -10
git revert <commit-hash>
```

2. **Keep Original Controller as Backup**:
```bash
cp controllers/BorrowedToolController.php controllers/BorrowedToolController.php.backup
```

3. **Test Incrementally**:
- Deploy print controller first (✅ Done)
- Deploy batch controller next
- Deploy refactored main controller last

---

## Expected Outcomes

### Before Refactoring
- **1 Controller**: 2,146 lines
- **Responsibility**: Everything
- **Maintainability**: Low

### After Refactoring
- **3 Controllers**: ~900 lines total
- **Responsibilities**: Clear separation
  - Main: Single-item operations, listing, utilities
  - Batch: Batch operations and workflows
  - Print: Print functionality
- **Maintainability**: High
- **Services**: 3 service classes (~650 lines)
- **Helpers**: 2 helper classes (~350 lines)
- **Total Code**: ~1,900 lines (well-organized)
- **Reduction**: ~250 lines eliminated (duplicates, dead code)

### Benefits
- ✅ Single responsibility per controller
- ✅ Easier to test individual components
- ✅ Faster onboarding for new developers
- ✅ Reduced merge conflicts
- ✅ Better code reusability
- ✅ Clearer separation of concerns

---

## Next Steps

1. **Create BorrowedToolBatchController** (1-2 hours)
2. **Refactor main BorrowedToolController** (2-3 hours)
3. **Update routing configuration** (30 minutes)
4. **Comprehensive testing** (2-3 hours)
5. **Code review** using code-review-agent
6. **Deploy to staging** for validation
7. **Deploy to production**

**Total Estimated Time**: 6-9 hours

---

**Last Updated**: 2025-01-27
**By**: Development Team
