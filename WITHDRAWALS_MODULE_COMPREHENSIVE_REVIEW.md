# Withdrawals Module: Comprehensive Code Review and Refactoring Analysis

**Date**: 2025-11-06
**Reviewer**: Code Review Agent
**Scope**: WithdrawalController.php, WithdrawalModel.php, Database Schema, Business Logic
**Status**: CRITICAL ISSUES FOUND - IMMEDIATE REFACTORING REQUIRED

---

## Executive Summary

The withdrawals module contains **CRITICAL architectural violations** that require immediate refactoring:

### Critical Issues (Severity: CRITICAL)
1. **Fat Model Anti-Pattern**: WithdrawalModel (989 lines) contains extensive business logic that belongs in services
2. **Database Schema Mismatch**: Code uses `asset_id` but database has `inventory_item_id` - CRITICAL inconsistency
3. **Mixed Consumable/Asset Logic**: Unclear separation between consumable withdrawals vs asset borrowing
4. **Hardcoded Role Checks**: Role-based access control hardcoded in multiple locations
5. **Missing Service Layer**: No dedicated service classes for business logic

### High Priority Issues
1. **File Size Violations**: Both files exceed 500-line recommended maximum (Controller: 1022 lines, Model: 989 lines)
2. **Direct Database Queries in Controller**: Controller contains raw SQL queries (lines 728-806)
3. **Duplicate Code**: Asset availability logic duplicated across multiple methods
4. **Validation Scattered**: No centralized validation service
5. **Transaction Management in Model**: Business transactions should be in services

### Business Logic Validation - CRITICAL
- **Withdrawals are designed for consumables** but code conflates them with asset borrowing
- Consumable quantity tracking exists but is mixed with asset status tracking
- No clear separation between:
  - **Consumable withdrawals** (reduce quantity, permanent or semi-permanent)
  - **Asset borrowing** (temporary use, track return)

---

## Architecture Analysis

### Current Architecture (VIOLATION)

```
WithdrawalController (1022 lines)
├── HTTP Request Handling ✓ CORRECT
├── Business Logic ✗ VIOLATION (should be in services)
├── Direct SQL Queries ✗ VIOLATION (lines 728-806)
├── Transaction Management ✗ VIOLATION (should be in services)
└── Role-based Access Control ✗ VIOLATION (hardcoded)

WithdrawalModel (989 lines)
├── Database Operations ✓ CORRECT
├── Business Logic ✗ VIOLATION (should be in services)
├── Workflow State Transitions ✗ VIOLATION (should be in services)
├── Quantity Management ✗ VIOLATION (should be in services)
└── Activity Logging ✓ CORRECT
```

### Target Architecture (2025 STANDARD)

```
WithdrawalController (<300 lines)
├── HTTP Request/Response Handling
├── Route → Service Delegation
├── View Rendering
└── Basic Input Sanitization

WithdrawalService (NEW)
├── createWithdrawalRequest()
├── processConsumableWithdrawal()
├── processAssetBorrowing()
├── validateWithdrawalEligibility()
└── calculateAvailableQuantity()

WithdrawalWorkflowService (NEW)
├── verifyWithdrawal() [MVA Step 1]
├── approveWithdrawal() [MVA Step 2]
├── releaseAsset() [MVA Step 3]
├── returnAsset()
└── cancelWithdrawal()

WithdrawalValidationService (NEW)
├── validateWithdrawalData()
├── validateConsumableQuantity()
├── validateAssetAvailability()
└── validateReturnDate()

WithdrawalModel (<300 lines)
├── CRUD Operations ONLY
├── Database Queries
└── Data Retrieval
```

---

## CRITICAL ISSUE #1: Database Schema Mismatch

### Problem
**CRITICAL INCONSISTENCY**: Code logic uses `asset_id` but database schema has `inventory_item_id`

#### Evidence

**Database Schema** (withdrawals table):
```sql
inventory_item_id int(11) NOT NULL  -- ACTUAL FIELD NAME
```

**Model Code** (WithdrawalModel.php):
```php
// Line 10: Fillable array uses 'asset_id'
protected $fillable = [
    'asset_id', 'project_id', 'purpose', ...  // WRONG FIELD NAME
];

// Line 20: Validation uses 'asset_id'
'asset_id' => 'required|integer',  // WRONG

// Line 42: Query uses correct field but receives wrong data
$stmt->execute([$data['asset_id']]);  // WRONG INPUT

// Line 534: SQL uses correct field
LEFT JOIN inventory_items a ON w.inventory_item_id = a.id  // CORRECT
```

**Controller Code** (WithdrawalController.php):
```php
// Line 150: Form data uses 'asset_id'
'asset_id' => (int)($_POST['asset_id'] ?? 0),  // WRONG

// Line 881: API uses 'asset_id'
'asset_id' => $input['asset_id'] ?? 0,  // WRONG
```

### Impact
- **Data Loss Risk**: Records may not be created correctly
- **Foreign Key Violations**: Potential constraint errors
- **Query Failures**: Joins may fail silently
- **Inconsistent Data**: Some queries use asset_id, others use inventory_item_id

### Fix Required
```php
// BEFORE (WRONG):
protected $fillable = ['asset_id', ...];

// AFTER (CORRECT):
protected $fillable = ['inventory_item_id', ...];

// BEFORE (WRONG):
'asset_id' => (int)($_POST['asset_id'] ?? 0)

// AFTER (CORRECT):
'inventory_item_id' => (int)($_POST['inventory_item_id'] ?? 0)
```

**Priority**: IMMEDIATE - Must be fixed before any other work

---

## CRITICAL ISSUE #2: Fat Model Anti-Pattern

### WithdrawalModel.php - Business Logic Violations

**Total Lines**: 989 (EXCEEDS 500-line maximum by 98%)

#### Business Logic That Belongs in Services

**1. createWithdrawal() - Lines 17-125** (109 lines)
```php
// VIOLATION: Business validation in model
if ($asset['is_consumable']) {
    if ($asset['available_quantity'] < $data['quantity']) {
        return ['success' => false, 'message' => 'Insufficient quantity'];
    }
}

// VIOLATION: Business rules in model
if ($asset['status'] !== 'available') {
    return ['success' => false, 'message' => 'Asset not available'];
}

// VIOLATION: Complex business logic
if (!$asset['is_consumable']) {
    $existingWithdrawal = $this->findFirst([...]);
    if ($existingWithdrawal) {
        return ['success' => false, 'message' => 'Asset already withdrawn'];
    }
}
```

**Should be**:
```php
// IN SERVICE CLASS:
class WithdrawalService {
    public function createWithdrawalRequest($data) {
        // Validate eligibility
        $validation = $this->validationService->validateWithdrawalRequest($data);
        if (!$validation['valid']) {
            return ResponseFormatter::error($validation['errors']);
        }

        // Check availability
        $availability = $this->checkAssetAvailability($data['inventory_item_id']);
        if (!$availability['available']) {
            return ResponseFormatter::error($availability['message']);
        }

        // Delegate to model for database operation
        return $this->withdrawalModel->create($data);
    }
}
```

**2. releaseAsset() - Lines 251-350** (100 lines)
```php
// VIOLATION: Workflow state management in model
if ($withdrawal['status'] !== 'Approved') {
    return ['success' => false, 'message' => 'Withdrawal must be approved'];
}

// VIOLATION: Quantity calculations in model
if ($asset['is_consumable']) {
    $newAvailableQuantity = $asset['available_quantity'] - $withdrawal['quantity'];
    if ($newAvailableQuantity < 0) {
        return ['success' => false, 'message' => 'Insufficient quantity'];
    }
}

// VIOLATION: Status transitions in model
$assetUpdateResult = $assetModel->update($withdrawal['asset_id'], [
    'status' => 'in_use'
]);
```

**Should be**:
```php
// IN WORKFLOW SERVICE:
class WithdrawalWorkflowService {
    public function releaseAsset($withdrawalId, $releasedBy, $notes) {
        // Validate workflow state
        $this->validateWorkflowTransition($withdrawalId, 'Released');

        // Process consumable or asset
        $withdrawal = $this->withdrawalModel->find($withdrawalId);
        if ($this->isConsumable($withdrawal)) {
            return $this->processConsumableRelease($withdrawal);
        } else {
            return $this->processAssetBorrowing($withdrawal);
        }
    }
}
```

**3. Direct SQL Queries - Lines 524-552, 557-663, 668-732**
```php
// VIOLATION: Complex queries in model
$sql = "
    SELECT w.*,
           a.ref as asset_ref, a.name as asset_name,
           c.name as category_name, c.is_consumable,
           p.name as project_name,
           u.full_name as withdrawn_by_name,
           r.released_by, r.notes as release_notes
    FROM withdrawals w
    LEFT JOIN inventory_items a ON w.inventory_item_id = a.id
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN projects p ON w.project_id = p.id
    LEFT JOIN users u ON w.withdrawn_by = u.id
    LEFT JOIN releases r ON w.id = r.withdrawal_id
    WHERE w.id = ?
";
```

**Should be**:
```php
// IN QUERY SERVICE:
class WithdrawalQueryService {
    public function getWithdrawalDetails($id) {
        // Build query with query builder
        return $this->queryBuilder
            ->select('w.*, a.ref, a.name, c.name as category_name...')
            ->from('withdrawals', 'w')
            ->leftJoin('inventory_items', 'a', 'w.inventory_item_id = a.id')
            ->leftJoin('categories', 'c', 'a.category_id = c.id')
            ->where('w.id', '=', $id)
            ->first();
    }
}
```

### Model Responsibilities Breakdown

| Lines | Current Responsibility | Should Be In |
|-------|------------------------|--------------|
| 17-125 | Business validation & creation logic | WithdrawalService |
| 130-159 | Workflow verification | WithdrawalWorkflowService |
| 164-193 | Workflow approval | WithdrawalWorkflowService |
| 198-246 | Workflow release (complex) | WithdrawalWorkflowService |
| 251-350 | Asset release logic | WithdrawalWorkflowService |
| 355-434 | Return asset logic | WithdrawalWorkflowService |
| 439-517 | Cancellation logic | WithdrawalWorkflowService |
| 524-552 | Complex detail query | WithdrawalQueryService |
| 557-663 | Filter & pagination query | WithdrawalQueryService |
| 668-732 | Statistics calculations | WithdrawalStatisticsService |
| 737-775 | Overdue calculations | WithdrawalStatisticsService |
| 780-824 | Report generation | WithdrawalReportService |

### Proper Model Structure (Target)

```php
class WithdrawalModel extends BaseModel {
    protected $table = 'withdrawals';
    protected $fillable = [
        'inventory_item_id', 'project_id', 'purpose', 'withdrawn_by',
        'receiver_name', 'quantity', 'unit', 'expected_return',
        'actual_return', 'status', 'notes'
    ];

    // ONLY these methods should exist:
    // - find($id)
    // - create($data)
    // - update($id, $data)
    // - delete($id)
    // - findAll($conditions)
    // - Custom simple queries for specific data retrieval

    // NO business logic
    // NO workflow management
    // NO calculations
    // NO complex joins (move to QueryService)
}
```

---

## CRITICAL ISSUE #3: Fat Controller Anti-Pattern

### WithdrawalController.php - Business Logic Violations

**Total Lines**: 1022 (EXCEEDS 500-line maximum by 104%)

#### Direct SQL Queries in Controller - Lines 726-806

```php
// VIOLATION: Raw SQL in controller
private function getAvailableAssetsForWithdrawal() {
    $db = Database::getInstance()->getConnection();
    $sql = "
        SELECT a.*, c.name as category_name, c.is_consumable, p.name as project_name
        FROM inventory_items a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN projects p ON a.project_id = p.id
        WHERE p.is_active = 1
          AND (
              (c.is_consumable = 1 AND a.available_quantity > 0)
              OR
              (c.is_consumable = 0 AND a.status = 'available' AND a.id NOT IN (
                  SELECT DISTINCT asset_id FROM borrowed_tools WHERE status = 'borrowed'
                  UNION
                  SELECT DISTINCT asset_id FROM withdrawals WHERE status IN (...)
                  UNION
                  SELECT DISTINCT asset_id FROM transfers WHERE status IN (...)
              ))
          )
        ORDER BY c.is_consumable DESC, p.name ASC, a.name ASC
    ";
    // 80 LINES OF RAW SQL IN CONTROLLER!
}
```

**This is a CRITICAL violation**. Controllers should NEVER:
- Instantiate database connections
- Write SQL queries
- Perform complex business logic
- Make direct database calls

**Should be**:
```php
// IN CONTROLLER:
public function create() {
    if (!$this->hasPermission('withdrawals.create')) {
        return $this->forbidden();
    }

    // Delegate to service
    $availableAssets = $this->withdrawalService->getAvailableAssets($_GET['project_id'] ?? null);

    return $this->render('withdrawals/create', [
        'assets' => $availableAssets
    ]);
}

// IN SERVICE:
class WithdrawalService {
    public function getAvailableAssets($projectId = null) {
        return $this->withdrawalQueryService->getAvailableAssetsForWithdrawal($projectId);
    }
}
```

#### Business Logic in Controller Actions

**1. create() - Lines 129-243** (115 lines)
```php
// VIOLATION: Validation logic in controller
if (empty($formData['asset_id'])) {
    $errors[] = 'Asset is required';
}
if ($formData['quantity'] <= 0) {
    $errors[] = 'Quantity must be greater than 0';
}

// VIOLATION: Business validation in controller
if (!empty($formData['expected_return'])) {
    if (strtotime($formData['expected_return']) <= time()) {
        $errors[] = 'Expected return date must be in the future';
    }
}

// VIOLATION: Complex asset-project validation in controller
$asset = $assetModel->find($formData['asset_id']);
if ($asset && $asset['project_id'] != $formData['project_id']) {
    $errors[] = 'Selected asset does not belong to the selected project';
}
```

**Should be**:
```php
public function create() {
    if (!$this->hasPermission('withdrawals.create')) {
        return $this->forbidden();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = $this->withdrawalService->createWithdrawalRequest($_POST);

        if ($result['success']) {
            return $this->redirect('withdrawals/view', ['id' => $result['id']], 'withdrawal_created');
        }

        return $this->render('withdrawals/create', [
            'errors' => $result['errors'],
            'formData' => $_POST
        ]);
    }

    return $this->render('withdrawals/create');
}
```

**2. release() - Lines 358-491** (134 lines)
```php
// VIOLATION: Complex form processing in controller
$formData = [
    'authorization_level' => $_POST['authorization_level'] ?? '',
    'asset_condition' => $_POST['asset_condition'] ?? '',
    'receiver_verification' => Validator::sanitize($_POST['receiver_verification'] ?? ''),
    'release_notes' => Validator::sanitize($_POST['release_notes'] ?? ''),
    'emergency_reason' => Validator::sanitize($_POST['emergency_reason'] ?? ''),
    'released_by' => $user['id']
];

// VIOLATION: Business validation in controller
if (empty($formData['authorization_level']) || !in_array(...)) {
    $errors[] = 'Invalid or missing authorization level.';
}

// VIOLATION: Building business data in controller
$releaseNotes = [];
if (!empty($formData['authorization_level'])) {
    $releaseNotes[] = "Authorization Level: " . ucfirst($formData['authorization_level']);
}
$completeNotes = implode("\n", $releaseNotes);
```

**Should be**:
```php
public function release($id) {
    if (!$this->hasPermission('withdrawals.release')) {
        return $this->forbidden();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = $this->withdrawalWorkflowService->releaseAsset($id, $_POST);

        if ($result['success']) {
            return $this->redirect('withdrawals/view', ['id' => $id], 'withdrawal_released');
        }

        return $this->render('withdrawals/release', [
            'errors' => $result['errors'],
            'withdrawal' => $this->withdrawalService->getWithdrawal($id)
        ]);
    }

    return $this->render('withdrawals/release', [
        'withdrawal' => $this->withdrawalService->getWithdrawal($id)
    ]);
}
```

#### Hardcoded Role Checks - Lines 28-42, 72-74, 84-87, 938-939, 983-984

```php
// VIOLATION: Hardcoded role names
if ($userRole === 'System Admin') return true;

// VIOLATION: Hardcoded role arrays
if (!in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
    $filters['project_id'] = $userProjectId;
}

// VIOLATION: Hardcoded role checks in API methods
if (!in_array($userRole, ['System Admin', 'Asset Director']) && $withdrawal['withdrawn_by'] != $currentUser['id']) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    return;
}
```

**Should be**:
```php
// Use permission-based system
if (!$this->auth->hasPermission('withdrawals.view.all')) {
    $filters['project_id'] = $currentUser['current_project_id'];
}

// Or use role service
if (!$this->roleService->canAccessAllProjects($currentUser)) {
    $filters['project_id'] = $currentUser['current_project_id'];
}
```

### Controller Responsibilities Breakdown

| Lines | Current Responsibility | Should Be In |
|-------|------------------------|--------------|
| 28-42 | Permission checking | PermissionService |
| 129-243 | Form validation & processing | WithdrawalService |
| 248-298 | Verification workflow | WithdrawalWorkflowService |
| 303-353 | Approval workflow | WithdrawalWorkflowService |
| 358-491 | Release processing | WithdrawalWorkflowService |
| 496-546 | Return processing | WithdrawalWorkflowService |
| 551-619 | Cancellation logic | WithdrawalWorkflowService |
| 664-721 | Export logic | WithdrawalExportService |
| 726-806 | Database queries | WithdrawalQueryService |

---

## CRITICAL ISSUE #4: Consumable vs Asset Confusion

### Business Logic Analysis

**Goal**: Withdrawals should be for **consumable items** (reduce quantity, track usage)
**Reality**: Code conflates withdrawals with **asset borrowing** (temporary use, expect return)

#### Evidence of Confusion

**1. Mixed Terminology**
```php
// Model calls it "withdrawal" but implements "borrowing" logic
public function returnAsset($withdrawalId, $returnedBy, $returnNotes = null)

// Table has return dates (borrowing concept)
expected_return date
actual_return date

// But also has consumable logic
if ($asset['is_consumable']) {
    $newAvailableQuantity = $asset['available_quantity'] - $withdrawal['quantity'];
}
```

**2. Return Logic for Consumables** (Lines 355-434)
```php
// WRONG: Consumables shouldn't be "returned"
if ($asset['is_consumable']) {
    // For consumables, restore the quantity (rare case, but possible)
    $newAvailableQuantity = $asset['available_quantity'] + $withdrawal['quantity'];
}
```

**Analysis**: If an item is **consumable** (screws, nails, paint), you don't "return" it. Once withdrawn, it's consumed. The return logic should ONLY apply to **non-consumable assets** (tools, equipment).

**3. Availability Check Confusion** (Controller lines 736-745)
```php
WHERE p.is_active = 1
  AND (
      (c.is_consumable = 1 AND a.available_quantity > 0)  // Consumable check ✓
      OR
      (c.is_consumable = 0 AND a.status = 'available' AND a.id NOT IN (
          SELECT DISTINCT asset_id FROM borrowed_tools WHERE status = 'borrowed'  // Check borrowing
          UNION
          SELECT DISTINCT asset_id FROM withdrawals WHERE status IN (...)  // Check withdrawals
          UNION
          SELECT DISTINCT asset_id FROM transfers WHERE status IN (...)  // Check transfers
      ))
  )
```

**Analysis**: This query tries to handle both:
- **Consumables**: Check quantity available
- **Assets**: Check not currently borrowed/withdrawn/transferred

But this creates confusion because **non-consumable items** can be in BOTH:
- `borrowed_tools` table (borrowing system)
- `withdrawals` table (withdrawal system)

### Proper Business Logic Separation

**CONSUMABLE WITHDRAWALS** (should be primary use case):
```php
class ConsumableWithdrawalService {
    public function withdrawConsumable($data) {
        // 1. Validate consumable exists and has quantity
        $item = $this->validateConsumableItem($data['inventory_item_id']);

        // 2. Check available quantity
        if ($item['available_quantity'] < $data['quantity']) {
            return ResponseFormatter::error('Insufficient quantity available');
        }

        // 3. Create withdrawal record
        $withdrawal = $this->withdrawalModel->create($data);

        // 4. Deduct quantity immediately (no return expected)
        $this->inventoryService->reduceQuantity(
            $data['inventory_item_id'],
            $data['quantity']
        );

        // 5. No expected_return, no actual_return
        // 6. Status: Withdrawn → Completed (not Released → Returned)

        return ResponseFormatter::success($withdrawal);
    }
}
```

**ASSET BORROWING** (should use borrowed_tools table, NOT withdrawals):
```php
class AssetBorrowingService {
    public function borrowAsset($data) {
        // This should NOT be in withdrawals module
        // Should use BorrowedToolWorkflowService instead

        // 1. Validate asset is borrowable (non-consumable)
        // 2. Check asset is available
        // 3. Create borrowed_tools record
        // 4. Update asset status to 'borrowed'
        // 5. Set expected_return date
        // 6. Track actual return
    }
}
```

### Recommended Fix

**Option 1: Split Module** (RECOMMENDED)
```
withdrawals/
├── ConsumableWithdrawalService.php   (for consumables only)
├── ConsumableWithdrawalModel.php
└── ConsumableWithdrawalController.php

borrowed_tools/
├── AssetBorrowingService.php         (for non-consumable assets)
├── BorrowedToolWorkflowService.php   (existing)
└── BorrowedToolController.php        (existing)
```

**Option 2: Unified Module with Clear Separation**
```
withdrawals/
├── WithdrawalService.php
│   ├── processConsumableWithdrawal()  (no return logic)
│   └── processAssetBorrowing()        (redirect to borrowed_tools)
└── WithdrawalWorkflowService.php
    ├── Consumable workflow (simpler)
    └── Asset workflow (redirect to borrowing)
```

---

## Security Analysis

### SQL Injection Protection: ✓ GOOD

All queries use parameterized statements:
```php
// GOOD: Parameterized query
$stmt = $this->db->prepare($sql);
$stmt->execute([$withdrawalId]);
```

No string concatenation found in SQL queries.

### XSS Protection: ⚠️ NEEDS IMPROVEMENT

**Controller uses Validator::sanitize()**:
```php
'purpose' => Validator::sanitize($_POST['purpose'] ?? '')
```

But views may not escape output. Need to verify views use `htmlspecialchars()` or template escaping.

### CSRF Protection: ✓ GOOD

```php
CSRFProtection::validateRequest();
```

Present in all POST actions.

### Authentication: ✓ GOOD

```php
if (!$this->auth->isAuthenticated()) {
    header('Location: ?route=login');
    exit;
}
```

### Authorization: ✗ NEEDS IMPROVEMENT

Hardcoded role checks instead of permission-based system:
```php
// BAD:
if ($userRole === 'System Admin') return true;

// GOOD:
if ($this->auth->hasPermission('withdrawals.admin')) return true;
```

---

## Performance Issues

### N+1 Query Problem: ⚠️ POTENTIAL ISSUE

**index() method** (Lines 77-88):
```php
$withdrawals = $result['data'] ?? [];

// For each withdrawal, if view accesses related data individually:
foreach ($withdrawals as $withdrawal) {
    // If view does: $withdrawal->getProject()
    // If view does: $withdrawal->getAsset()
    // If view does: $withdrawal->getUser()
    // = N+1 queries
}
```

**Fix**: Use eager loading in getWithdrawalsWithFilters():
```php
// Already done correctly with JOINs:
LEFT JOIN inventory_items a ON w.inventory_item_id = a.id
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN projects p ON w.project_id = p.id
```

**Status**: ✓ Already optimized with JOINs

### Missing Indexes: ⚠️ NEEDS VERIFICATION

Check if these indexes exist:
```sql
CREATE INDEX idx_withdrawals_status ON withdrawals(status);
CREATE INDEX idx_withdrawals_project_id ON withdrawals(project_id);
CREATE INDEX idx_withdrawals_inventory_item_id ON withdrawals(inventory_item_id);
CREATE INDEX idx_withdrawals_created_at ON withdrawals(created_at);
CREATE INDEX idx_withdrawals_expected_return ON withdrawals(expected_return);
```

### Caching: ✗ MISSING

No caching for:
- Available assets list (lines 726-757)
- Withdrawal statistics (lines 668-732)
- Project list (lines 90, 225)

**Recommendation**:
```php
$projects = Cache::remember('active_projects', 3600, function() {
    return $this->projectModel->getActiveProjects();
});
```

---

## Code Quality Issues

### 1. Magic Numbers

**Lines 56, 55, 614, 672**:
```php
$perPage = 20;  // Should be constant
$limit = 10000;  // Should be constant
```

**Fix**:
```php
class WithdrawalController {
    const DEFAULT_PER_PAGE = 20;
    const EXPORT_MAX_RECORDS = 10000;
}
```

### 2. Duplicate Code

**Asset availability check** duplicated in:
- Lines 726-757 (getAvailableAssetsForWithdrawal)
- Lines 776-806 (getAssetsByProject)
- Lines 909-932 (getAvailableAssetsForWithdrawal in model)

**Fix**: Extract to service method:
```php
class AssetAvailabilityService {
    public function getAvailableForWithdrawal($projectId = null, $includeConsumables = true) {
        // Single implementation
    }
}
```

### 3. Long Methods

Methods exceeding 50 lines:
- `createWithdrawal()` - 109 lines
- `releaseAsset()` - 100 lines
- `release()` controller - 134 lines
- `create()` controller - 115 lines

**Fix**: Break into smaller methods with single responsibilities

### 4. Deep Nesting

**Lines 186-197 (controller)**:
```php
if (!empty($formData['asset_id']) && !empty($formData['project_id'])) {
    try {
        $assetModel = new AssetModel();
        $asset = $assetModel->find($formData['asset_id']);
        if ($asset && $asset['project_id'] != $formData['project_id']) {
            $errors[] = 'Selected asset does not belong to the selected project';
        }
    } catch (Exception $e) {
        $errors[] = 'Failed to validate asset-project relationship';
    }
}
```

**Fix**: Use early returns:
```php
if (empty($formData['asset_id']) || empty($formData['project_id'])) {
    return;
}

try {
    $asset = $this->assetModel->find($formData['asset_id']);
} catch (Exception $e) {
    $errors[] = 'Failed to validate asset-project relationship';
    return;
}

if (!$asset || $asset['project_id'] != $formData['project_id']) {
    $errors[] = 'Selected asset does not belong to the selected project';
}
```

### 5. Error Handling Inconsistency

**Some methods return arrays**:
```php
return ['success' => false, 'message' => 'Error'];
```

**Some methods return false**:
```php
return false;
```

**Some methods throw exceptions**:
```php
throw new Exception('Error');
```

**Fix**: Use consistent response format:
```php
class ResponseFormatter {
    public static function success($data, $message = null)
    public static function error($message, $errors = [])
    public static function notFound($resource)
}
```

---

## Database Schema Issues

### 1. Missing Foreign Key Constraints ⚠️

**Need to verify**:
```sql
ALTER TABLE withdrawals
ADD CONSTRAINT fk_withdrawals_inventory_item
FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT;

ALTER TABLE withdrawals
ADD CONSTRAINT fk_withdrawals_project
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE RESTRICT;
```

### 2. Enum Status Values

**Current**:
```sql
status enum('Pending Verification','Pending Approval','Approved','Released','Returned','Canceled')
```

**Issue**: "Released" and "Returned" statuses appropriate for **asset borrowing**, not **consumable withdrawals**.

**Recommendation**:
```sql
-- For consumables:
status enum('Pending Verification','Pending Approval','Approved','Completed','Canceled')

-- For assets (should be in borrowed_tools):
status enum('Pending Verification','Pending Approval','Approved','Released','Returned','Canceled')
```

### 3. Missing Consumable Tracking Fields

**Should add**:
```sql
ALTER TABLE withdrawals ADD COLUMN is_consumable TINYINT(1) DEFAULT 0;
ALTER TABLE withdrawals ADD COLUMN withdrawal_type ENUM('consumable', 'asset_borrow') DEFAULT 'consumable';
```

### 4. Return Date Fields for Consumables

**Issue**: `expected_return` and `actual_return` don't make sense for consumables.

**Options**:
1. Make fields nullable and NULL for consumables
2. Split into separate tables
3. Add `withdrawal_type` and conditionally use fields

---

## Refactoring Recommendations

### Phase 1: Critical Fixes (Week 1)

**Priority: IMMEDIATE**

1. **Fix database field naming** (`asset_id` → `inventory_item_id`)
   - Update model fillable array
   - Update controller form processing
   - Update views form fields
   - Test all CRUD operations

2. **Separate consumable vs asset logic**
   - Add `is_consumable` flag to withdrawal logic
   - Remove return logic for consumables
   - Update status enum based on type

3. **Extract hardcoded roles**
   - Replace with permission checks
   - Use PermissionService or RoleService

### Phase 2: Service Layer (Week 2-3)

**Create service classes**:

```php
services/Withdrawal/
├── WithdrawalService.php                 (main orchestration)
├── WithdrawalWorkflowService.php         (MVA workflow)
├── WithdrawalValidationService.php       (validation logic)
├── WithdrawalQueryService.php            (complex queries)
├── WithdrawalStatisticsService.php       (statistics & reports)
├── ConsumableWithdrawalService.php       (consumable-specific)
└── WithdrawalExportService.php           (export logic)
```

**Migration steps**:
1. Create service directory structure
2. Extract business logic from model to services
3. Update controller to use services
4. Refactor model to pure database operations
5. Write unit tests for services

### Phase 3: Controller Refactoring (Week 4)

**Slim down controller**:
1. Remove all SQL queries → delegate to services
2. Remove business validation → delegate to services
3. Remove workflow logic → delegate to WorkflowService
4. Keep only HTTP handling and view rendering

**Target controller structure**:
```php
class WithdrawalController extends BaseController {
    private $withdrawalService;
    private $withdrawalWorkflowService;

    public function index() {
        $filters = $this->getFiltersFromRequest();
        $result = $this->withdrawalService->getWithdrawals($filters);
        return $this->render('withdrawals/index', $result);
    }

    public function create() {
        if ($this->isPost()) {
            $result = $this->withdrawalService->createWithdrawal($_POST);
            return $this->handleResult($result, 'withdrawals/view');
        }
        return $this->render('withdrawals/create');
    }

    // ~200-300 lines total
}
```

### Phase 4: Model Refactoring (Week 5)

**Slim down model**:
1. Remove all business logic
2. Remove workflow methods
3. Remove statistics methods
4. Keep only CRUD and simple queries

**Target model structure**:
```php
class WithdrawalModel extends BaseModel {
    protected $table = 'withdrawals';
    protected $fillable = [...];

    // Only 5-10 simple methods:
    // - Custom finders
    // - Simple queries
    // - Data retrieval

    // ~150-200 lines total
}
```

### Phase 5: Testing & Documentation (Week 6)

1. **Unit tests** for all services
2. **Integration tests** for workflows
3. **API documentation** updates
4. **Code documentation** with PHPDoc
5. **User documentation** updates

---

## Detailed Refactoring Plan

### Service Class: WithdrawalService

**Responsibility**: Main orchestration for withdrawal operations

```php
<?php
/**
 * WithdrawalService
 *
 * Main service for withdrawal operations orchestration
 */
class WithdrawalService {
    private $withdrawalModel;
    private $validationService;
    private $queryService;
    private $workflowService;

    public function __construct(
        WithdrawalModel $withdrawalModel = null,
        WithdrawalValidationService $validationService = null,
        WithdrawalQueryService $queryService = null,
        WithdrawalWorkflowService $workflowService = null
    ) {
        $this->withdrawalModel = $withdrawalModel ?? new WithdrawalModel();
        $this->validationService = $validationService ?? new WithdrawalValidationService();
        $this->queryService = $queryService ?? new WithdrawalQueryService();
        $this->workflowService = $workflowService ?? new WithdrawalWorkflowService();
    }

    /**
     * Create withdrawal request
     */
    public function createWithdrawalRequest($data) {
        // Validate input
        $validation = $this->validationService->validateWithdrawalRequest($data);
        if (!$validation['valid']) {
            return ResponseFormatter::error('Validation failed', $validation['errors']);
        }

        // Check item availability
        $availability = $this->checkItemAvailability($data['inventory_item_id'], $data['quantity']);
        if (!$availability['available']) {
            return ResponseFormatter::error($availability['message']);
        }

        // Determine withdrawal type
        if ($availability['is_consumable']) {
            return $this->createConsumableWithdrawal($data, $availability['item']);
        } else {
            return $this->createAssetBorrowing($data, $availability['item']);
        }
    }

    /**
     * Create consumable withdrawal (no return expected)
     */
    private function createConsumableWithdrawal($data, $item) {
        try {
            DB::beginTransaction();

            // Create withdrawal record
            $withdrawalData = [
                'inventory_item_id' => $data['inventory_item_id'],
                'project_id' => $data['project_id'],
                'purpose' => $data['purpose'],
                'withdrawn_by' => $data['withdrawn_by'],
                'receiver_name' => $data['receiver_name'],
                'quantity' => $data['quantity'],
                'unit' => $data['unit'] ?? 'pcs',
                'status' => 'Pending Verification',
                'notes' => $data['notes'] ?? null,
                // NO expected_return for consumables
                // NO actual_return for consumables
            ];

            $withdrawal = $this->withdrawalModel->create($withdrawalData);

            // Activity log
            ActivityLog::log('withdrawal_created', 'Consumable withdrawal created', 'withdrawals', $withdrawal['id']);

            DB::commit();
            return ResponseFormatter::success($withdrawal, 'Withdrawal request created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            error_log("Consumable withdrawal error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to create withdrawal request');
        }
    }

    /**
     * Create asset borrowing (should actually use borrowed_tools)
     */
    private function createAssetBorrowing($data, $item) {
        // RECOMMENDATION: Redirect to BorrowedToolService instead
        // Assets should not use withdrawal system

        return ResponseFormatter::error(
            'Non-consumable assets should use the Borrowing system, not Withdrawals. Please use the "Borrow Tool" feature instead.'
        );

        // OR if we want to support it:
        // return $this->borrowedToolService->createBorrowRequest($data);
    }

    /**
     * Get withdrawal details
     */
    public function getWithdrawal($id) {
        return $this->queryService->getWithdrawalDetails($id);
    }

    /**
     * Get withdrawals with filters
     */
    public function getWithdrawals($filters = [], $page = 1, $perPage = 20) {
        return $this->queryService->getWithdrawalsWithFilters($filters, $page, $perPage);
    }

    /**
     * Check item availability
     */
    private function checkItemAvailability($inventoryItemId, $requestedQuantity) {
        $item = $this->queryService->getInventoryItemWithCategory($inventoryItemId);

        if (!$item) {
            return ['available' => false, 'message' => 'Item not found'];
        }

        if ($item['is_consumable']) {
            // Check quantity for consumables
            if ($item['available_quantity'] < $requestedQuantity) {
                return [
                    'available' => false,
                    'message' => "Insufficient quantity. Available: {$item['available_quantity']}, Requested: {$requestedQuantity}"
                ];
            }

            return ['available' => true, 'is_consumable' => true, 'item' => $item];
        } else {
            // Check status for assets
            if ($item['status'] !== 'available') {
                return ['available' => false, 'message' => 'Asset is not available'];
            }

            // Check if already withdrawn/borrowed
            $existingWithdrawal = $this->queryService->getActiveWithdrawalForItem($inventoryItemId);
            if ($existingWithdrawal) {
                return ['available' => false, 'message' => 'Asset is currently withdrawn or borrowed'];
            }

            return ['available' => true, 'is_consumable' => false, 'item' => $item];
        }
    }
}
```

### Service Class: WithdrawalWorkflowService

**Responsibility**: MVA workflow state transitions

```php
<?php
/**
 * WithdrawalWorkflowService
 *
 * Handles MVA (Maker-Verifier-Authorizer) workflow for withdrawals
 */
class WithdrawalWorkflowService {
    use ActivityLoggingTrait;

    private $withdrawalModel;
    private $inventoryService;

    public function __construct(
        WithdrawalModel $withdrawalModel = null,
        InventoryService $inventoryService = null
    ) {
        $this->withdrawalModel = $withdrawalModel ?? new WithdrawalModel();
        $this->inventoryService = $inventoryService ?? new InventoryService();
    }

    /**
     * Verify withdrawal (Step 1: Verifier)
     */
    public function verifyWithdrawal($withdrawalId, $verifiedBy, $notes = null) {
        try {
            DB::beginTransaction();

            $withdrawal = $this->withdrawalModel->find($withdrawalId);
            if (!$withdrawal) {
                DB::rollBack();
                return ResponseFormatter::notFound('Withdrawal');
            }

            // Validate state transition
            if ($withdrawal['status'] !== 'Pending Verification') {
                DB::rollBack();
                return ResponseFormatter::error('Withdrawal is not in pending verification status');
            }

            // Update status
            $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Pending Approval',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);

            $this->logActivity('withdrawal_verified', 'Withdrawal verified', 'withdrawals', $withdrawalId);

            DB::commit();
            return ResponseFormatter::success(null, 'Withdrawal verified successfully');

        } catch (Exception $e) {
            DB::rollBack();
            error_log("Withdrawal verification error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to verify withdrawal');
        }
    }

    /**
     * Approve withdrawal (Step 2: Authorizer)
     */
    public function approveWithdrawal($withdrawalId, $approvedBy, $notes = null) {
        try {
            DB::beginTransaction();

            $withdrawal = $this->withdrawalModel->find($withdrawalId);
            if (!$withdrawal) {
                DB::rollBack();
                return ResponseFormatter::notFound('Withdrawal');
            }

            // Validate state transition
            if ($withdrawal['status'] !== 'Pending Approval') {
                DB::rollBack();
                return ResponseFormatter::error('Withdrawal is not in pending approval status');
            }

            // Update status
            $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);

            $this->logActivity('withdrawal_approved', 'Withdrawal approved', 'withdrawals', $withdrawalId);

            DB::commit();
            return ResponseFormatter::success(null, 'Withdrawal approved successfully');

        } catch (Exception $e) {
            DB::rollBack();
            error_log("Withdrawal approval error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to approve withdrawal');
        }
    }

    /**
     * Release asset (Step 3: Physical handover)
     */
    public function releaseAsset($withdrawalId, $releaseData) {
        try {
            DB::beginTransaction();

            $withdrawal = $this->getWithdrawalWithItem($withdrawalId);
            if (!$withdrawal) {
                DB::rollBack();
                return ResponseFormatter::notFound('Withdrawal');
            }

            // Validate state transition
            if ($withdrawal['status'] !== 'Approved') {
                DB::rollBack();
                return ResponseFormatter::error('Withdrawal must be approved before release');
            }

            // Process based on item type
            if ($withdrawal['is_consumable']) {
                $result = $this->releaseConsumableItem($withdrawal, $releaseData);
            } else {
                $result = $this->releaseAssetItem($withdrawal, $releaseData);
            }

            if (!$result['success']) {
                DB::rollBack();
                return $result;
            }

            // Update withdrawal status
            $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Released',
                'released_by' => $releaseData['released_by'],
                'release_date' => date('Y-m-d H:i:s'),
                'notes' => $this->buildReleaseNotes($releaseData)
            ]);

            // Create release record
            $this->createReleaseRecord($withdrawalId, $releaseData);

            $this->logActivity('asset_released', 'Asset released', 'withdrawals', $withdrawalId);

            DB::commit();
            return ResponseFormatter::success(null, 'Asset released successfully');

        } catch (Exception $e) {
            DB::rollBack();
            error_log("Asset release error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to release asset');
        }
    }

    /**
     * Release consumable item (deduct quantity)
     */
    private function releaseConsumableItem($withdrawal, $releaseData) {
        $result = $this->inventoryService->reduceQuantity(
            $withdrawal['inventory_item_id'],
            $withdrawal['quantity']
        );

        if (!$result['success']) {
            return ResponseFormatter::error('Failed to update inventory quantity');
        }

        return ResponseFormatter::success();
    }

    /**
     * Release asset item (update status)
     */
    private function releaseAssetItem($withdrawal, $releaseData) {
        $result = $this->inventoryService->updateAssetStatus(
            $withdrawal['inventory_item_id'],
            'in_use'
        );

        if (!$result['success']) {
            return ResponseFormatter::error('Failed to update asset status');
        }

        return ResponseFormatter::success();
    }

    /**
     * Return asset (only for non-consumables)
     */
    public function returnAsset($withdrawalId, $returnedBy, $returnNotes = null) {
        try {
            DB::beginTransaction();

            $withdrawal = $this->getWithdrawalWithItem($withdrawalId);
            if (!$withdrawal) {
                DB::rollBack();
                return ResponseFormatter::notFound('Withdrawal');
            }

            // Consumables cannot be returned
            if ($withdrawal['is_consumable']) {
                DB::rollBack();
                return ResponseFormatter::error('Consumable items cannot be returned');
            }

            // Validate state
            if ($withdrawal['status'] !== 'Released') {
                DB::rollBack();
                return ResponseFormatter::error('Asset is not currently released');
            }

            // Update asset status back to available
            $this->inventoryService->updateAssetStatus(
                $withdrawal['inventory_item_id'],
                'available'
            );

            // Update withdrawal
            $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Returned',
                'returned_by' => $returnedBy,
                'return_date' => date('Y-m-d H:i:s'),
                'actual_return' => date('Y-m-d'),
                'notes' => $returnNotes
            ]);

            $this->logActivity('asset_returned', 'Asset returned', 'withdrawals', $withdrawalId);

            DB::commit();
            return ResponseFormatter::success(null, 'Asset returned successfully');

        } catch (Exception $e) {
            DB::rollBack();
            error_log("Asset return error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to return asset');
        }
    }

    /**
     * Cancel withdrawal
     */
    public function cancelWithdrawal($withdrawalId, $reason) {
        try {
            DB::beginTransaction();

            $withdrawal = $this->getWithdrawalWithItem($withdrawalId);
            if (!$withdrawal) {
                DB::rollBack();
                return ResponseFormatter::notFound('Withdrawal');
            }

            // Validate cancellable states
            if (!in_array($withdrawal['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'Released'])) {
                DB::rollBack();
                return ResponseFormatter::error('Withdrawal cannot be canceled in current status');
            }

            // If already released, restore inventory
            if ($withdrawal['status'] === 'Released') {
                if ($withdrawal['is_consumable']) {
                    $this->inventoryService->increaseQuantity(
                        $withdrawal['inventory_item_id'],
                        $withdrawal['quantity']
                    );
                } else {
                    $this->inventoryService->updateAssetStatus(
                        $withdrawal['inventory_item_id'],
                        'available'
                    );
                }
            }

            // Update withdrawal
            $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Canceled',
                'notes' => $reason
            ]);

            $this->logActivity('withdrawal_canceled', 'Withdrawal canceled', 'withdrawals', $withdrawalId);

            DB::commit();
            return ResponseFormatter::success(null, 'Withdrawal canceled successfully');

        } catch (Exception $e) {
            DB::rollBack();
            error_log("Withdrawal cancellation error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to cancel withdrawal');
        }
    }

    /**
     * Get withdrawal with item details
     */
    private function getWithdrawalWithItem($withdrawalId) {
        return $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
    }

    /**
     * Build release notes from form data
     */
    private function buildReleaseNotes($releaseData) {
        $notes = [];

        if (!empty($releaseData['authorization_level'])) {
            $notes[] = "Authorization: " . ucfirst($releaseData['authorization_level']);
        }

        if (!empty($releaseData['asset_condition'])) {
            $notes[] = "Condition: " . ucfirst($releaseData['asset_condition']);
        }

        if (!empty($releaseData['receiver_verification'])) {
            $notes[] = "Receiver: " . $releaseData['receiver_verification'];
        }

        if (!empty($releaseData['emergency_reason'])) {
            $notes[] = "Emergency: " . $releaseData['emergency_reason'];
        }

        if (!empty($releaseData['release_notes'])) {
            $notes[] = "Notes: " . $releaseData['release_notes'];
        }

        return implode("\n", $notes);
    }

    /**
     * Create release record
     */
    private function createReleaseRecord($withdrawalId, $releaseData) {
        $releaseModel = new ReleaseModel();
        $releaseModel->create([
            'withdrawal_id' => $withdrawalId,
            'released_by' => $releaseData['released_by'],
            'notes' => $this->buildReleaseNotes($releaseData),
            'released_at' => date('Y-m-d H:i:s')
        ]);
    }
}
```

### Service Class: WithdrawalValidationService

**Responsibility**: Input validation and business rule validation

```php
<?php
/**
 * WithdrawalValidationService
 *
 * Handles validation for withdrawal requests
 */
class WithdrawalValidationService {

    /**
     * Validate withdrawal request data
     */
    public function validateWithdrawalRequest($data) {
        $errors = [];

        // Required fields
        if (empty($data['inventory_item_id'])) {
            $errors[] = 'Inventory item is required';
        }

        if (empty($data['project_id'])) {
            $errors[] = 'Project is required';
        }

        if (empty($data['purpose'])) {
            $errors[] = 'Purpose is required';
        } elseif (strlen($data['purpose']) > 500) {
            $errors[] = 'Purpose cannot exceed 500 characters';
        }

        if (empty($data['receiver_name'])) {
            $errors[] = 'Receiver name is required';
        } elseif (strlen($data['receiver_name']) > 100) {
            $errors[] = 'Receiver name cannot exceed 100 characters';
        }

        if (empty($data['withdrawn_by'])) {
            $errors[] = 'Withdrawn by user is required';
        }

        // Quantity validation
        if (empty($data['quantity']) || !is_numeric($data['quantity'])) {
            $errors[] = 'Valid quantity is required';
        } elseif ($data['quantity'] <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }

        // Expected return date validation (for non-consumables)
        if (!empty($data['expected_return'])) {
            if (!$this->isValidDate($data['expected_return'])) {
                $errors[] = 'Invalid expected return date format';
            } elseif (strtotime($data['expected_return']) <= time()) {
                $errors[] = 'Expected return date must be in the future';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate consumable quantity
     */
    public function validateConsumableQuantity($availableQuantity, $requestedQuantity) {
        if ($requestedQuantity > $availableQuantity) {
            return [
                'valid' => false,
                'message' => "Insufficient quantity. Available: {$availableQuantity}, Requested: {$requestedQuantity}"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate asset availability
     */
    public function validateAssetAvailability($asset) {
        if ($asset['status'] !== 'available') {
            return [
                'valid' => false,
                'message' => 'Asset is not available for withdrawal'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate asset-project relationship
     */
    public function validateAssetProjectRelationship($assetProjectId, $requestedProjectId) {
        if ($assetProjectId != $requestedProjectId) {
            return [
                'valid' => false,
                'message' => 'Selected asset does not belong to the selected project'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate date format
     */
    private function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
```

### Service Class: WithdrawalQueryService

**Responsibility**: Complex database queries and data retrieval

```php
<?php
/**
 * WithdrawalQueryService
 *
 * Handles complex queries for withdrawal data
 */
class WithdrawalQueryService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get withdrawal with full details
     */
    public function getWithdrawalDetails($id) {
        $sql = "
            SELECT w.*,
                   i.ref as item_ref,
                   i.name as item_name,
                   i.status as item_status,
                   i.quantity as item_total_quantity,
                   i.available_quantity as item_available_quantity,
                   c.name as category_name,
                   c.is_consumable,
                   p.name as project_name,
                   p.location as project_location,
                   u.full_name as withdrawn_by_name,
                   v.full_name as verified_by_name,
                   a.full_name as approved_by_name,
                   r.released_by,
                   r.notes as release_notes,
                   r.released_at,
                   rl.full_name as released_by_name,
                   ret.full_name as returned_by_name
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN projects p ON w.project_id = p.id
            LEFT JOIN users u ON w.withdrawn_by = u.id
            LEFT JOIN users v ON w.verified_by = v.id
            LEFT JOIN users a ON w.approved_by = a.id
            LEFT JOIN releases r ON w.id = r.withdrawal_id
            LEFT JOIN users rl ON r.released_by = rl.id
            LEFT JOIN users ret ON w.returned_by = ret.id
            WHERE w.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get withdrawals with filters and pagination
     */
    public function getWithdrawalsWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['status'])) {
            $conditions[] = "w.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['project_id'])) {
            $conditions[] = "w.project_id = ?";
            $params[] = $filters['project_id'];
        }

        if (!empty($filters['inventory_item_id'])) {
            $conditions[] = "w.inventory_item_id = ?";
            $params[] = $filters['inventory_item_id'];
        }

        if (!empty($filters['withdrawn_by'])) {
            $conditions[] = "w.withdrawn_by = ?";
            $params[] = $filters['withdrawn_by'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(w.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(w.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(i.ref LIKE ? OR i.name LIKE ? OR w.receiver_name LIKE ? OR w.purpose LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Count total records
        $countSql = "
            SELECT COUNT(*)
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            {$whereClause}
        ";

        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get paginated data
        $offset = ($page - 1) * $perPage;
        $orderBy = $filters['order_by'] ?? 'w.created_at DESC';

        $dataSql = "
            SELECT w.*,
                   i.ref as item_ref,
                   i.name as item_name,
                   c.name as category_name,
                   c.is_consumable,
                   p.name as project_name,
                   u.full_name as withdrawn_by_name
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN projects p ON w.project_id = p.id
            LEFT JOIN users u ON w.withdrawn_by = u.id
            {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($dataSql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }

    /**
     * Get inventory item with category
     */
    public function getInventoryItemWithCategory($inventoryItemId) {
        $sql = "
            SELECT i.*, c.is_consumable, c.name as category_name
            FROM inventory_items i
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE i.id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inventoryItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get active withdrawal for item
     */
    public function getActiveWithdrawalForItem($inventoryItemId) {
        $sql = "
            SELECT * FROM withdrawals
            WHERE inventory_item_id = ?
            AND status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Released')
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inventoryItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get available items for withdrawal
     */
    public function getAvailableItemsForWithdrawal($projectId = null, $includeConsumables = true, $includeAssets = true) {
        $conditions = ["p.is_active = 1"];
        $params = [];

        if ($projectId) {
            $conditions[] = "i.project_id = ?";
            $params[] = $projectId;
        }

        // Build availability conditions
        $availabilityConditions = [];

        if ($includeConsumables) {
            $availabilityConditions[] = "(c.is_consumable = 1 AND i.available_quantity > 0)";
        }

        if ($includeAssets) {
            $availabilityConditions[] = "
                (c.is_consumable = 0 AND i.status = 'available' AND i.id NOT IN (
                    SELECT DISTINCT inventory_item_id FROM withdrawals
                    WHERE status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Released')
                ))
            ";
        }

        if (!empty($availabilityConditions)) {
            $conditions[] = "(" . implode(" OR ", $availabilityConditions) . ")";
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "
            SELECT i.*,
                   c.name as category_name,
                   c.is_consumable,
                   p.name as project_name
            FROM inventory_items i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN projects p ON i.project_id = p.id
            {$whereClause}
            ORDER BY c.is_consumable DESC, p.name ASC, i.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

---

## Testing Recommendations

### Unit Tests

**WithdrawalServiceTest.php**:
```php
class WithdrawalServiceTest extends TestCase {
    public function testCreateConsumableWithdrawal() {
        // Test consumable withdrawal creation
    }

    public function testPreventAssetWithdrawalWithoutBorrowing() {
        // Test that non-consumables redirect to borrowing
    }

    public function testInsufficientQuantityValidation() {
        // Test quantity validation
    }
}
```

**WithdrawalWorkflowServiceTest.php**:
```php
class WithdrawalWorkflowServiceTest extends TestCase {
    public function testMVAWorkflowTransitions() {
        // Test Pending → Verified → Approved → Released
    }

    public function testConsumableQuantityDeduction() {
        // Test quantity is deducted on release
    }

    public function testAssetStatusUpdate() {
        // Test asset status changes to in_use
    }

    public function testConsumableCannotBeReturned() {
        // Test consumables reject return
    }
}
```

### Integration Tests

**WithdrawalFlowTest.php**:
```php
class WithdrawalFlowTest extends TestCase {
    public function testCompleteConsumableWithdrawalFlow() {
        // Create → Verify → Approve → Release
        // Assert quantity deducted
    }

    public function testCancelledWithdrawalRestoresQuantity() {
        // Create → Release → Cancel
        // Assert quantity restored
    }
}
```

---

## Migration Checklist

### Pre-Migration
- [ ] Backup database
- [ ] Create feature branch
- [ ] Document current functionality
- [ ] Set up testing environment

### Phase 1: Critical Fixes
- [ ] Fix `asset_id` → `inventory_item_id` naming
  - [ ] Update model fillable
  - [ ] Update controller forms
  - [ ] Update views
  - [ ] Update API endpoints
  - [ ] Test all CRUD operations
- [ ] Add `is_consumable` flag to withdrawal logic
- [ ] Remove return logic for consumables
- [ ] Extract hardcoded roles to config

### Phase 2: Service Layer
- [ ] Create service directory structure
- [ ] Implement WithdrawalService
- [ ] Implement WithdrawalWorkflowService
- [ ] Implement WithdrawalValidationService
- [ ] Implement WithdrawalQueryService
- [ ] Implement WithdrawalStatisticsService
- [ ] Write unit tests for services

### Phase 3: Controller Refactoring
- [ ] Remove SQL queries from controller
- [ ] Delegate business logic to services
- [ ] Simplify controller methods
- [ ] Update controller to use services
- [ ] Reduce controller to <300 lines

### Phase 4: Model Refactoring
- [ ] Remove business logic from model
- [ ] Remove workflow methods
- [ ] Remove statistics methods
- [ ] Keep only CRUD operations
- [ ] Reduce model to <300 lines

### Phase 5: Testing
- [ ] Write unit tests
- [ ] Write integration tests
- [ ] Perform manual testing
- [ ] Load testing
- [ ] Security testing

### Phase 6: Documentation
- [ ] Update API documentation
- [ ] Update code documentation
- [ ] Update user manual
- [ ] Create migration guide

---

## Success Metrics

### Code Quality
- ✅ Controller: <300 lines (currently 1022)
- ✅ Model: <300 lines (currently 989)
- ✅ No SQL in controller
- ✅ No business logic in model
- ✅ Service layer implemented
- ✅ All hardcoded roles removed

### Functionality
- ✅ All existing features work
- ✅ Consumable withdrawals work correctly
- ✅ Asset borrowing redirects to proper system
- ✅ MVA workflow functions
- ✅ Quantity tracking accurate

### Testing
- ✅ 80%+ unit test coverage
- ✅ 100% integration test coverage for critical paths
- ✅ All tests passing
- ✅ No regression bugs

### Performance
- ✅ No N+1 queries
- ✅ Proper indexes in place
- ✅ Caching implemented
- ✅ Page load <2 seconds

---

## Summary of Critical Issues

| Issue | Severity | Lines Affected | Priority | Estimated Effort |
|-------|----------|----------------|----------|------------------|
| Database field mismatch (asset_id vs inventory_item_id) | CRITICAL | Model: 10+, Controller: 20+ | IMMEDIATE | 4 hours |
| Fat Model anti-pattern | CRITICAL | 989 lines | HIGH | 40 hours |
| Fat Controller anti-pattern | CRITICAL | 1022 lines | HIGH | 40 hours |
| SQL queries in controller | CRITICAL | Lines 726-806 | HIGH | 8 hours |
| Consumable vs Asset confusion | HIGH | Multiple | HIGH | 16 hours |
| Hardcoded role checks | HIGH | 10+ locations | MEDIUM | 8 hours |
| Missing service layer | HIGH | N/A | HIGH | 60 hours |
| No validation service | MEDIUM | Scattered | MEDIUM | 12 hours |
| Duplicate code | MEDIUM | 3+ locations | LOW | 6 hours |
| Long methods | MEDIUM | 4+ methods | LOW | 8 hours |

**Total Estimated Effort**: ~200 hours (5 weeks @ 40 hours/week)

---

## Conclusion

The withdrawals module requires **IMMEDIATE and COMPREHENSIVE refactoring**. The current implementation violates fundamental architectural principles:

1. **Fat Controller/Model**: Both files are 2x the recommended size with extensive business logic
2. **Database Mismatch**: Critical inconsistency between code (`asset_id`) and schema (`inventory_item_id`)
3. **No Service Layer**: Business logic scattered across controller and model
4. **Unclear Business Logic**: Confusion between consumable withdrawals and asset borrowing
5. **Hardcoded Security**: Role checks hardcoded instead of permission-based

**The refactoring must follow this priority order**:
1. Fix database field naming (IMMEDIATE - prevents data corruption)
2. Extract service layer (HIGH - enables proper architecture)
3. Slim down controller and model (HIGH - improves maintainability)
4. Clarify consumable vs asset logic (HIGH - fixes business logic)
5. Implement proper testing (HIGH - prevents regressions)

**This refactoring is NOT optional**. The current code:
- Violates ConstructLink architectural standards
- Contains critical data integrity risks
- Is unmaintainable at current size
- Mixes business concerns incorrectly
- Cannot scale or be tested properly

The proposed service-oriented architecture will:
- Reduce file sizes by 60-70%
- Separate concerns properly
- Enable comprehensive testing
- Improve maintainability
- Follow 2025 industry best practices
- Align with existing service patterns (BorrowedToolWorkflowService)

**Recommended Action**: Create a dedicated sprint for this refactoring with priority focus on Phase 1 (critical fixes) and Phase 2 (service layer extraction).
