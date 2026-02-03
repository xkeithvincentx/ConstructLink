# Withdrawals Module Refactoring - Quick Reference Guide

**Date**: 2025-11-06
**Status**: REFACTORING REQUIRED

---

## Critical Issues Summary

### 🔴 CRITICAL (Fix Immediately)

1. **Database Field Mismatch**
   - Code uses: `asset_id`
   - Database has: `inventory_item_id`
   - **Impact**: Data integrity risk, foreign key failures
   - **Fix Time**: 4 hours

2. **Fat Model (989 lines)**
   - Business logic in model
   - Workflow management in model
   - Statistics in model
   - **Target**: <300 lines
   - **Fix Time**: 40 hours

3. **Fat Controller (1022 lines)**
   - SQL queries in controller (lines 726-806)
   - Business validation in controller
   - Workflow logic in controller
   - **Target**: <300 lines
   - **Fix Time**: 40 hours

### 🟡 HIGH PRIORITY

4. **Consumable vs Asset Confusion**
   - Return logic for consumables (wrong)
   - Mixed terminology
   - Unclear business rules
   - **Fix Time**: 16 hours

5. **No Service Layer**
   - All business logic in controller/model
   - No separation of concerns
   - **Fix Time**: 60 hours

6. **Hardcoded Roles**
   - 10+ locations with hardcoded role checks
   - Should use permission system
   - **Fix Time**: 8 hours

---

## File Size Comparison

| File | Current | Target | Reduction |
|------|---------|--------|-----------|
| WithdrawalController.php | 1022 lines | <300 lines | 70% |
| WithdrawalModel.php | 989 lines | <300 lines | 70% |
| **Total** | **2011 lines** | **~600 lines** | **70%** |

**New Service Files** (to be created):
- WithdrawalService.php (~200 lines)
- WithdrawalWorkflowService.php (~300 lines)
- WithdrawalValidationService.php (~150 lines)
- WithdrawalQueryService.php (~250 lines)
- WithdrawalStatisticsService.php (~150 lines)
- WithdrawalExportService.php (~100 lines)

---

## Current vs Target Architecture

### BEFORE (Current - VIOLATION)

```
┌─────────────────────────────────────┐
│   WithdrawalController (1022 lines) │
├─────────────────────────────────────┤
│ ❌ HTTP Handling                     │
│ ❌ SQL Queries (lines 726-806)       │
│ ❌ Business Validation               │
│ ❌ Workflow Logic                    │
│ ❌ Transaction Management            │
│ ❌ Hardcoded Roles                   │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│    WithdrawalModel (989 lines)      │
├─────────────────────────────────────┤
│ ✅ Database Operations               │
│ ❌ Business Logic                    │
│ ❌ Workflow State Transitions        │
│ ❌ Statistics Calculations           │
│ ❌ Quantity Management               │
└─────────────────────────────────────┘
           ↓
     [Database]
```

### AFTER (Target - CORRECT)

```
┌─────────────────────────────────────┐
│  WithdrawalController (<300 lines)  │
├─────────────────────────────────────┤
│ ✅ HTTP Request/Response             │
│ ✅ Route → Service Delegation        │
│ ✅ View Rendering                    │
│ ✅ Basic Input Sanitization          │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│     WithdrawalService (~200)        │
├─────────────────────────────────────┤
│ ✅ Orchestration Logic               │
│ ✅ Consumable vs Asset Detection     │
│ ✅ Availability Checking             │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────────────────┐
│  WithdrawalWorkflowService (~300)               │
├─────────────────────────────────────────────────┤
│ ✅ verifyWithdrawal() [MVA Step 1]              │
│ ✅ approveWithdrawal() [MVA Step 2]             │
│ ✅ releaseAsset() [MVA Step 3]                  │
│ ✅ returnAsset() (non-consumables only)         │
│ ✅ cancelWithdrawal()                           │
└─────────────────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  WithdrawalValidationService (~150) │
├─────────────────────────────────────┤
│ ✅ Input Validation                  │
│ ✅ Business Rule Validation          │
│ ✅ Quantity Validation               │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   WithdrawalQueryService (~250)     │
├─────────────────────────────────────┤
│ ✅ Complex Queries                   │
│ ✅ Filtering & Pagination            │
│ ✅ Detail Retrieval                  │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   WithdrawalModel (<300 lines)      │
├─────────────────────────────────────┤
│ ✅ CRUD Operations ONLY              │
│ ✅ Simple Queries                    │
│ ✅ Data Retrieval                    │
└─────────────────────────────────────┘
           ↓
     [Database]
```

---

## Immediate Action Items

### Day 1: Fix Database Field Mismatch (4 hours)

**Files to Update**:
1. `models/WithdrawalModel.php` line 10
2. `controllers/WithdrawalController.php` lines 150, 881
3. All views: `views/withdrawals/*.php`

**Find & Replace**:
```bash
# Check occurrences
grep -rn "asset_id" controllers/WithdrawalController.php models/WithdrawalModel.php views/withdrawals/

# Replace in code (use with caution!)
# sed -i '' 's/asset_id/inventory_item_id/g' models/WithdrawalModel.php
```

**Manual Changes Required**:
```php
// BEFORE:
protected $fillable = ['asset_id', 'project_id', ...];

// AFTER:
protected $fillable = ['inventory_item_id', 'project_id', ...];
```

```php
// BEFORE:
'asset_id' => (int)($_POST['asset_id'] ?? 0)

// AFTER:
'inventory_item_id' => (int)($_POST['inventory_item_id'] ?? 0)
```

**Testing**:
- [ ] Create new withdrawal
- [ ] Verify record in database
- [ ] Check foreign key relationship
- [ ] Test all CRUD operations

---

## Week 1: Service Layer Foundation

### Day 1-2: Create Service Structure

```bash
mkdir -p services/Withdrawal
touch services/Withdrawal/WithdrawalService.php
touch services/Withdrawal/WithdrawalWorkflowService.php
touch services/Withdrawal/WithdrawalValidationService.php
touch services/Withdrawal/WithdrawalQueryService.php
```

### Day 3-5: Implement Services

**Priority Order**:
1. WithdrawalValidationService (simplest, no dependencies)
2. WithdrawalQueryService (no business logic)
3. WithdrawalService (orchestration)
4. WithdrawalWorkflowService (complex, has dependencies)

---

## Business Logic Clarification

### Consumable Withdrawals (PRIMARY USE CASE)

**Flow**:
```
Create Request
    ↓
Verify (Check quantity available)
    ↓
Approve (Authorize withdrawal)
    ↓
Release (Deduct quantity - PERMANENT)
    ↓
[COMPLETED - NO RETURN]
```

**Example**: Withdrawing 100 screws
- Available: 500 screws
- Request: 100 screws
- After release: 400 screws available
- **No return** - screws are consumed

**Code Pattern**:
```php
if ($item['is_consumable']) {
    // Deduct quantity on release
    $newQuantity = $item['available_quantity'] - $withdrawal['quantity'];
    $this->inventoryService->updateQuantity($item['id'], $newQuantity);

    // NO expected_return
    // NO actual_return
    // Status: Completed (not Released → Returned)
}
```

### Asset Borrowing (SHOULD USE borrowed_tools)

**Flow**:
```
Create Borrow Request
    ↓
Verify (Check asset available)
    ↓
Approve (Authorize borrowing)
    ↓
Release (Update status to 'borrowed')
    ↓
[IN USE - EXPECT RETURN]
    ↓
Return (Update status to 'available')
```

**Example**: Borrowing a drill
- Status: available
- After release: borrowed (status change, no quantity deduction)
- Expected return: 2025-11-15
- **Return required** - tool comes back

**Code Pattern**:
```php
if (!$item['is_consumable']) {
    // Redirect to BorrowedToolService
    return ResponseFormatter::error(
        'Non-consumable assets should use the Borrowing system'
    );

    // OR if supporting in withdrawals:
    $this->assetService->updateStatus($item['id'], 'borrowed');
    // Set expected_return
    // Track actual_return
}
```

---

## Method Migration Map

### From WithdrawalModel → Services

| Current Method | Lines | Move To | New Service |
|----------------|-------|---------|-------------|
| `createWithdrawal()` | 17-125 | Business Logic | WithdrawalService |
| `verifyWithdrawal()` | 130-159 | Workflow | WithdrawalWorkflowService |
| `approveWithdrawal()` | 164-193 | Workflow | WithdrawalWorkflowService |
| `releaseAsset()` | 251-350 | Workflow | WithdrawalWorkflowService |
| `returnAsset()` | 355-434 | Workflow | WithdrawalWorkflowService |
| `cancelWithdrawal()` | 439-517 | Workflow | WithdrawalWorkflowService |
| `getWithdrawalWithDetails()` | 524-552 | Query | WithdrawalQueryService |
| `getWithdrawalsWithFilters()` | 557-663 | Query | WithdrawalQueryService |
| `getWithdrawalStatistics()` | 668-732 | Statistics | WithdrawalStatisticsService |
| `getOverdueWithdrawals()` | 737-775 | Statistics | WithdrawalStatisticsService |

### From WithdrawalController → Services

| Current Method | Lines | Move To | New Service |
|----------------|-------|---------|-------------|
| `create()` business logic | 163-197 | Validation | WithdrawalValidationService |
| `release()` form processing | 407-462 | Workflow | WithdrawalWorkflowService |
| `getAvailableAssetsForWithdrawal()` | 726-757 | Query | WithdrawalQueryService |
| `getAssetsByProject()` | 762-806 | Query | WithdrawalQueryService |
| `export()` | 664-721 | Export | WithdrawalExportService |

---

## Validation Rules

### Consumable Withdrawal

**Required Fields**:
- `inventory_item_id` (must be consumable)
- `project_id`
- `purpose` (max 500 chars)
- `receiver_name` (max 100 chars)
- `quantity` (> 0, <= available_quantity)
- `withdrawn_by` (user ID)

**Optional Fields**:
- `unit` (default: 'pcs')
- `notes`

**Forbidden Fields**:
- `expected_return` (consumables don't return)
- `actual_return`

### Asset Borrowing

**Required Fields**:
- `inventory_item_id` (must be non-consumable)
- `project_id`
- `purpose`
- `receiver_name`
- `expected_return` (must be future date)
- `withdrawn_by`

**Validation**:
- `quantity` must be 1 (can't borrow multiple unique assets)
- Asset status must be 'available'
- Asset must not be in active withdrawal/borrowing

---

## Testing Checklist

### Unit Tests

**WithdrawalValidationService**:
- [ ] Validate required fields
- [ ] Validate quantity > 0
- [ ] Validate quantity <= available
- [ ] Validate future date for expected_return
- [ ] Validate string lengths

**WithdrawalService**:
- [ ] Create consumable withdrawal
- [ ] Reject asset withdrawal (redirect to borrowing)
- [ ] Check availability (consumable)
- [ ] Check availability (asset)
- [ ] Handle insufficient quantity

**WithdrawalWorkflowService**:
- [ ] Verify withdrawal (Pending → Approved)
- [ ] Approve withdrawal (Approved → Released)
- [ ] Release consumable (deduct quantity)
- [ ] Release asset (update status)
- [ ] Return asset (non-consumable only)
- [ ] Reject return for consumable
- [ ] Cancel withdrawal (restore quantity/status)

### Integration Tests

**Complete Consumable Flow**:
```php
// Create → Verify → Approve → Release
$withdrawal = $this->createConsumableWithdrawal();
$this->verifyWithdrawal($withdrawal['id']);
$this->approveWithdrawal($withdrawal['id']);
$this->releaseWithdrawal($withdrawal['id']);

// Assert quantity deducted
$item = $this->getItem($withdrawal['inventory_item_id']);
$this->assertEquals(
    $originalQuantity - $withdrawal['quantity'],
    $item['available_quantity']
);
```

**Cancel After Release**:
```php
// Create → Release → Cancel
$withdrawal = $this->createAndReleaseConsumableWithdrawal();
$this->cancelWithdrawal($withdrawal['id']);

// Assert quantity restored
$item = $this->getItem($withdrawal['inventory_item_id']);
$this->assertEquals($originalQuantity, $item['available_quantity']);
```

---

## Common Pitfalls to Avoid

### ❌ DON'T

1. **Don't put business logic in controllers**
   ```php
   // BAD:
   if ($asset['available_quantity'] < $quantity) {
       $errors[] = 'Insufficient quantity';
   }
   ```

2. **Don't put workflow in models**
   ```php
   // BAD (in Model):
   public function approveWithdrawal($id) {
       // Workflow logic here
   }
   ```

3. **Don't use `asset_id` - use `inventory_item_id`**
   ```php
   // BAD:
   'asset_id' => $_POST['asset_id']

   // GOOD:
   'inventory_item_id' => $_POST['inventory_item_id']
   ```

4. **Don't allow return for consumables**
   ```php
   // BAD:
   if ($asset['is_consumable']) {
       $newQuantity = $asset['available_quantity'] + $withdrawal['quantity'];
   }
   ```

5. **Don't hardcode roles**
   ```php
   // BAD:
   if ($userRole === 'System Admin') return true;

   // GOOD:
   if ($this->auth->hasPermission('withdrawals.admin')) return true;
   ```

### ✅ DO

1. **Do delegate to services**
   ```php
   // GOOD (in Controller):
   $result = $this->withdrawalService->createWithdrawal($_POST);
   ```

2. **Do separate concerns**
   ```php
   // GOOD:
   WithdrawalService → orchestration
   WithdrawalWorkflowService → MVA workflow
   WithdrawalModel → database only
   ```

3. **Do use consistent naming**
   ```php
   // GOOD:
   'inventory_item_id' everywhere
   ```

4. **Do distinguish consumables from assets**
   ```php
   // GOOD:
   if ($item['is_consumable']) {
       return $this->processConsumableWithdrawal($data);
   } else {
       return $this->redirectToBorrowingSystem($data);
   }
   ```

5. **Do use permission-based security**
   ```php
   // GOOD:
   if (!$this->auth->hasPermission('withdrawals.create')) {
       return $this->forbidden();
   }
   ```

---

## Quick Wins (Low-Hanging Fruit)

These can be done quickly for immediate improvement:

### 1. Extract Constants (15 minutes)

```php
class WithdrawalController {
    const DEFAULT_PER_PAGE = 20;
    const EXPORT_MAX_RECORDS = 10000;
    const MAX_PURPOSE_LENGTH = 500;
    const MAX_RECEIVER_NAME_LENGTH = 100;
}
```

### 2. Add Type Hints (30 minutes)

```php
// BEFORE:
public function createWithdrawal($data) { }

// AFTER:
public function createWithdrawal(array $data): array { }
```

### 3. Use Early Returns (1 hour)

```php
// BEFORE:
if ($asset) {
    if ($asset['status'] === 'available') {
        // Do something
    }
}

// AFTER:
if (!$asset) {
    return ResponseFormatter::notFound('Asset');
}

if ($asset['status'] !== 'available') {
    return ResponseFormatter::error('Asset not available');
}

// Do something
```

### 4. Extract Validation to Method (2 hours)

```php
// BEFORE: 30 lines of validation in controller
if (empty($formData['asset_id'])) { ... }
if (empty($formData['project_id'])) { ... }
// etc.

// AFTER:
$validation = $this->validateWithdrawalRequest($formData);
if (!$validation['valid']) {
    return $this->renderWithErrors($validation['errors']);
}
```

---

## Progress Tracking

### Checklist

**Phase 1: Critical Fixes** (Week 1)
- [ ] Fix `asset_id` → `inventory_item_id` (4 hours)
- [ ] Add consumable flag checks (4 hours)
- [ ] Remove return logic for consumables (4 hours)
- [ ] Extract role checks to config (4 hours)
- [ ] Add validation for consumable/asset types (4 hours)

**Phase 2: Service Layer** (Week 2-3)
- [ ] Create service directory structure (1 hour)
- [ ] Implement WithdrawalValidationService (8 hours)
- [ ] Implement WithdrawalQueryService (12 hours)
- [ ] Implement WithdrawalService (16 hours)
- [ ] Implement WithdrawalWorkflowService (24 hours)
- [ ] Write service unit tests (20 hours)

**Phase 3: Controller Refactoring** (Week 4)
- [ ] Remove SQL from controller (8 hours)
- [ ] Delegate to services (16 hours)
- [ ] Simplify methods (8 hours)
- [ ] Update routes (4 hours)
- [ ] Test all endpoints (8 hours)

**Phase 4: Model Refactoring** (Week 5)
- [ ] Remove business logic (12 hours)
- [ ] Remove workflow methods (8 hours)
- [ ] Remove statistics methods (8 hours)
- [ ] Simplify to CRUD only (8 hours)
- [ ] Update all references (4 hours)

**Phase 5: Testing & Documentation** (Week 6)
- [ ] Integration tests (16 hours)
- [ ] Manual testing (8 hours)
- [ ] Update API docs (8 hours)
- [ ] Update user docs (8 hours)

---

## Key Contacts & Resources

**Reference Implementations**:
- `services/BorrowedToolWorkflowService.php` - MVA workflow pattern
- `services/Asset/AssetCrudService.php` - Service pattern
- `services/BorrowedToolValidationService.php` - Validation pattern

**Documentation**:
- `/Users/keithvincentranoa/Developer/ConstructLink/WITHDRAWALS_MODULE_COMPREHENSIVE_REVIEW.md`
- `/Users/keithvincentranoa/Developer/ConstructLink/INVENTORY_TABLE_MIGRATION_FIX.md`

**Database**:
- Connection: `mysql -u root constructlink_db` (no password)
- Table: `withdrawals`
- Related: `inventory_items`, `categories`, `releases`

---

## Emergency Rollback Plan

If refactoring causes issues:

1. **Git Revert**:
   ```bash
   git checkout feature/system-refactor
   git log --oneline  # Find commit before refactoring
   git revert <commit-hash>
   ```

2. **Database Restore**:
   ```bash
   # Restore from backup taken before refactoring
   mysql -u root constructlink_db < backup_before_refactor.sql
   ```

3. **Feature Flag** (if implemented):
   ```php
   if (Config::get('use_new_withdrawal_services')) {
       return $this->newWithdrawalService->create($data);
   } else {
       return $this->legacyWithdrawalModel->create($data);
   }
   ```

---

**Document Version**: 1.0
**Last Updated**: 2025-11-06
**Status**: READY FOR IMPLEMENTATION
