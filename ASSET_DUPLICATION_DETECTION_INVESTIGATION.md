# Asset Module Duplication Detection Investigation

**Investigation Date:** 2025-11-10
**Scope:** Assets module, Request module (restock), and Procurement Order integration

---

## Executive Summary

This investigation analyzed the asset creation workflow, particularly for consumable items, to determine if duplication detection exists and how the restock workflow prevents duplicate inventory items.

### Key Findings:
1. ✅ **Duplication detection service EXISTS** - `AssetMatchingService` with `checkDuplicate()` method
2. ❌ **Legacy create form does NOT use duplication detection** currently
3. ✅ **Restock workflow properly prevents duplicates** by adding quantity to existing items
4. ✅ **Procurement orders correctly distinguish** between restock (add quantity) and new items (create assets)

---

## Investigation Results

### 1. Legacy Create Form (`views/assets/legacy_create.php`)

**Location:** `/views/assets/legacy_create.php`
**Controller:** `AssetController::legacyCreate()` (line 859)
**Form Action:** `?route=assets/legacy-create`

#### Form Data Collection:
The legacy create form collects:
- **Equipment Classification:** Type and subtype (`equipment_type_id`, `subtype_id`)
- **Basic Information:** Name, description
- **Classification:** `category_id`, `project_id`
- **Technical Specs:** Quantity, unit, specifications
- **Financial Info:** Costs, dates
- **Location & Condition:** Location, condition notes
- **Brand & Discipline:** Brand, disciplines
- **Client Supplied:** Boolean flag

#### Category Information Available:
The `_classification_section.php` partial displays category metadata:
```php
data-asset-type="<?= $category['asset_type'] ?>"
data-is-consumable="<?= $category['is_consumable'] ? '1' : '0' ?>"
data-generates-assets="<?= $category['generates_assets'] ? '1' : '0' ?>"
```

**Critical Finding:** Category `is_consumable` flag is available in the form but **NOT used for duplication checking**.

---

### 2. Asset Creation Flow

#### Controller Layer (`AssetController.php`)
```php
// Line 908
$result = $this->assetModel->createLegacyAsset($formData);
```

#### Model Layer (`AssetModel.php`)
```php
// Line 779-781
public function createLegacyAsset($data) {
    return $this->createAsset($data);
}

// Line 229-231
public function createAsset($data) {
    return $this->getCrudService()->createAsset($data);
}
```

#### Service Layer (`services/Asset/AssetCrudService.php`)
```php
// Line 72-132: createAsset() method
public function createAsset($data) {
    // Validates category business rules
    $categoryValidation = $this->validateCategoryBusinessRules($data);

    // Prepares asset data with consumable handling
    $assetData = $this->prepareAssetData($data, $categoryValidation['category']);

    // Creates asset (NO DUPLICATION CHECK)
    $asset = $this->assetModel->create($assetData);
}
```

**Critical Gap:** The `createAsset()` method does NOT call `AssetMatchingService::checkDuplicate()`.

---

### 3. AssetMatchingService - Duplication Detection System

**Location:** `/services/Asset/AssetMatchingService.php`
**Purpose:** Intelligent matching and discovery for inventory items

#### Available Methods:

##### `checkDuplicate($itemData)` (Line 324-409)
Prevents duplicate consumable creation by checking:
- ✅ Exact name match (case-insensitive)
- ✅ Same category
- ✅ Same project (if specified)
- ✅ Same model (if specified)
- ✅ Status: available, borrowed, or in_maintenance

**Returns:**
```php
[
    'is_duplicate' => true/false,
    'duplicates' => [...], // Array of matching items
    'count' => int,
    'suggestion' => 'Consider creating a restock request instead of a new item'
]
```

##### `findExistingConsumableItem($criteria)` (Line 66-168)
Searches for consumable items matching criteria:
- Category, name, project, specifications, model
- Returns stock levels and active restock counts

##### `suggestRestockCandidates($projectId, $threshold)` (Line 182-308)
Identifies low-stock consumables requiring restock.

---

### 4. Restock Workflow - Proper Duplication Prevention

#### How Restock Works:

**Step 1: User Creates Restock Request**
- Form: `/views/requests/create.php?is_restock=1`
- Selects existing consumable item from dropdown
- Specifies quantity to restock

**Step 2: Request Approval (MVA Workflow)**
- Maker → Verifier → Authorizer
- Procurement Officer creates PO

**Step 3: Procurement Order Receipt**
Location: `ProcurementOrderController::confirmReceipt()` (line 1650)

```php
// Line 1653-1666
$linkedRequest = $this->getLinkedRestockRequest($id);

if ($linkedRequest && $linkedRequest['is_restock'] == 1) {
    // ✅ RESTOCK: Add quantity to existing item
    $restockResult = $this->processRestockDelivery($id, $linkedRequest);
} else if ($generateAssets) {
    // ❌ NEW ITEM: Create new inventory records
    // This is for new procurement items
}
```

**Step 4: Add Quantity to Existing Item**
Location: `RestockWorkflowService::processRestockDelivery()` (line 141)

```php
// Line 170-175
$quantityResult = $this->quantityService->addQuantity(
    $request['inventory_item_id'],
    $request['quantity'],
    "Restock delivery from PO #{$request['po_number']}",
    $procurementOrderId
);
```

---

## Problem Analysis

### Current State: Legacy Create Form

**Scenario:**
1. Warehouseman creates legacy consumable item: "Wire Nuts 10 AWG - Box of 100"
2. Project: Site A, Category: Electrical Supplies (is_consumable = 1)
3. Item is created with quantity = 100

**Later:**
1. Same warehouseman receives more stock of the same item
2. Creates another legacy item: "Wire Nuts 10 AWG - Box of 100"
3. Project: Site A, Category: Electrical Supplies
4. ❌ **NEW duplicate item is created** instead of adding quantity

**Result:** Two separate inventory records for the same consumable item.

---

## Recommendations

### Priority 1: Add Duplication Detection to Legacy Create Form

#### Implementation Plan:

**1. Frontend Detection (JavaScript)**
- Add real-time duplication checking when user enters item details
- Check on blur of name field + category selection
- Show warning modal if duplicates found

**2. Backend Validation (PHP)**
- Integrate `AssetMatchingService::checkDuplicate()` in `AssetCrudService::createAsset()`
- Check before creating asset if category `is_consumable = 1`
- Return duplicate information to user

**3. User Interface Flow:**

```
User enters consumable item details
    ↓
System checks for duplicates (AJAX)
    ↓
If duplicate found:
    ↓
    Show modal: "Item already exists in inventory!"
    Display existing item details:
    - Item Name: Wire Nuts 10 AWG
    - Current Quantity: 100 units
    - Available: 85 units
    - Project: Site A
    ↓
    Options:
    [Cancel] [Create Anyway] [Create Restock Request Instead]
    ↓
If "Create Restock Request" selected:
    ↓
    Redirect to: ?route=requests/create&is_restock=1&item_id={id}
    Pre-populate restock form
```

---

### Priority 2: Enhance AssetCrudService

**File:** `/services/Asset/AssetCrudService.php`

**Modification Location:** Line 72 (createAsset method)

```php
public function createAsset($data) {
    try {
        // Existing validation
        $validation = $this->validateAssetData($data, true);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        // Category validation
        $categoryValidation = $this->validateCategoryBusinessRules($data);
        if (!$categoryValidation['success']) {
            $this->db->rollBack();
            return $categoryValidation;
        }

        // ✅ NEW: Duplication detection for consumables
        if ($categoryValidation['category']['is_consumable'] == 1) {
            require_once APP_ROOT . '/services/Asset/AssetMatchingService.php';
            use Services\Asset\AssetMatchingService;

            $matchingService = new AssetMatchingService($this->db);
            $duplicateCheck = $matchingService->checkDuplicate([
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'project_id' => $data['project_id'] ?? null,
                'model' => $data['model'] ?? null
            ]);

            if ($duplicateCheck['data']['is_duplicate']) {
                return [
                    'success' => false,
                    'duplicate_detected' => true,
                    'duplicates' => $duplicateCheck['data']['duplicates'],
                    'suggestion' => $duplicateCheck['data']['suggestion'],
                    'message' => 'A similar consumable item already exists in this project. Consider creating a restock request instead.'
                ];
            }
        }

        // Continue with existing asset creation...
        $this->db->beginTransaction();
        // ... rest of method
    }
}
```

---

### Priority 3: Create API Endpoint for Duplicate Checking

**New File:** `/api/assets/check-duplicate.php`

```php
<?php
/**
 * Check for duplicate consumable items
 * Used by legacy create form for real-time validation
 */
require_once '../../services/Asset/AssetMatchingService.php';

$matchingService = new AssetMatchingService();
$result = $matchingService->checkDuplicate($_POST);

header('Content-Type: application/json');
echo json_encode($result);
```

---

### Priority 4: Frontend JavaScript for Legacy Create Form

**New File:** `/assets/js/modules/assets/duplicate-checker.js`

```javascript
/**
 * Real-time duplicate detection for legacy create form
 */
export class DuplicateChecker {
    constructor(formElement) {
        this.form = formElement;
        this.nameField = document.getElementById('asset_name');
        this.categoryField = document.getElementById('category_id');
        this.projectField = document.getElementById('project_id');

        this.attachListeners();
    }

    attachListeners() {
        // Check on blur after user enters name
        this.nameField.addEventListener('blur', () => this.checkDuplicates());

        // Re-check if category or project changes
        this.categoryField.addEventListener('change', () => this.checkDuplicates());
        this.projectField.addEventListener('change', () => this.checkDuplicates());
    }

    async checkDuplicates() {
        const name = this.nameField.value.trim();
        const categoryId = this.categoryField.value;
        const projectId = this.projectField.value;

        if (!name || !categoryId) return;

        // Check if category is consumable
        const selectedOption = this.categoryField.selectedOptions[0];
        const isConsumable = selectedOption?.dataset.isConsumable === '1';

        if (!isConsumable) return; // Only check for consumables

        try {
            const response = await fetch('/api/assets/check-duplicate.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    name,
                    category_id: categoryId,
                    project_id: projectId
                })
            });

            const result = await response.json();

            if (result.data.is_duplicate) {
                this.showDuplicateWarning(result.data.duplicates);
            }
        } catch (error) {
            console.error('Duplicate check failed:', error);
        }
    }

    showDuplicateWarning(duplicates) {
        // Show Bootstrap modal with duplicate information
        // Provide options to cancel, create anyway, or create restock request
    }
}
```

---

## Technical Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                   LEGACY CREATE FORM                     │
│           views/assets/legacy_create.php                │
└────────────────────┬────────────────────────────────────┘
                     │
                     │ User enters consumable item
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              FRONTEND VALIDATION                         │
│     assets/js/modules/assets/duplicate-checker.js       │
│  • Checks if category is_consumable = 1                 │
│  • Calls /api/assets/check-duplicate.php                │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              DUPLICATION API                             │
│          api/assets/check-duplicate.php                  │
│  • Receives: name, category_id, project_id              │
│  • Calls AssetMatchingService::checkDuplicate()         │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│          ASSET MATCHING SERVICE                          │
│     services/Asset/AssetMatchingService.php             │
│  • Queries database for existing consumables            │
│  • Matches: name + category + project                   │
│  • Returns duplicate information                        │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              DUPLICATE FOUND?                            │
└────────────┬───────────────────────┬────────────────────┘
             │ YES                   │ NO
             ▼                       ▼
┌────────────────────────┐  ┌───────────────────────────┐
│   SHOW WARNING MODAL   │  │   CONTINUE CREATION       │
│  • Display existing    │  │                           │
│    item details        │  │                           │
│  • Options:            │  │                           │
│    - Cancel            │  │                           │
│    - Create Anyway     │  │                           │
│    - Create Restock    │  │                           │
└────────────────────────┘  └───────────────────────────┘
```

---

## Current Restock Workflow (Working Correctly)

```
┌─────────────────────────────────────────────────────────┐
│              USER NEEDS MORE STOCK                      │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│          CREATE RESTOCK REQUEST                         │
│     views/requests/create.php?is_restock=1              │
│  • Select existing consumable item                      │
│  • Specify quantity to add                              │
│  • is_restock = 1, inventory_item_id = X                │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              MVA APPROVAL WORKFLOW                      │
│  Maker → Verifier → Authorizer                          │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│          PROCUREMENT ORDER CREATED                      │
│  • PO references request_id                             │
│  • Request has is_restock = 1                           │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              DELIVERY RECEIVED                          │
│  ProcurementOrderController::confirmReceipt()           │
│  • Checks: is_restock = 1?                              │
└────────────┬───────────────────────┬────────────────────┘
             │ YES (Restock)         │ NO (New Item)
             ▼                       ▼
┌────────────────────────┐  ┌───────────────────────────┐
│  processRestockDelivery│  │  Generate New Assets      │
│  • Add quantity to     │  │  • Create new inventory   │
│    existing item       │  │    records                │
│  • Update status       │  │                           │
│  • Log activity        │  │                           │
└────────────────────────┘  └───────────────────────────┘
```

---

## Database Schema Considerations

### Assets Table (a.k.a. inventory_items)
```sql
CREATE TABLE assets (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    category_id INT,  -- Links to categories.is_consumable
    project_id INT,
    quantity INT DEFAULT 1,
    available_quantity INT DEFAULT 1,
    -- ... other fields
);
```

### Categories Table
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    is_consumable TINYINT(1) DEFAULT 0,  -- KEY FIELD
    asset_type ENUM('capital', 'inventory', 'expense'),
    generates_assets TINYINT(1),
    -- ... other fields
);
```

### Requests Table
```sql
CREATE TABLE requests (
    id INT PRIMARY KEY,
    is_restock TINYINT(1) DEFAULT 0,  -- KEY FIELD
    inventory_item_id INT,  -- Links to existing asset for restock
    -- ... other fields
);
```

---

## Testing Scenarios

### Test Case 1: Duplicate Detection in Legacy Create
```
GIVEN: Consumable item exists
    - Name: "Wire Nuts 10 AWG"
    - Project: Site A
    - Category: Electrical Supplies (is_consumable = 1)
    - Quantity: 100, Available: 85

WHEN: User creates new legacy item
    - Name: "Wire Nuts 10 AWG"
    - Project: Site A
    - Category: Electrical Supplies
    - Quantity: 50

THEN: System should
    ✅ Detect duplicate
    ✅ Show existing item information
    ✅ Suggest creating restock request
    ✅ Allow user to proceed if they confirm it's different
```

### Test Case 2: Non-Consumable (No Duplicate Check)
```
GIVEN: Capital asset exists
    - Name: "Angle Grinder"
    - Project: Site A
    - Category: Power Tools (is_consumable = 0)

WHEN: User creates new item with same name
    - Name: "Angle Grinder"
    - Project: Site A
    - Category: Power Tools

THEN: System should
    ✅ Allow creation without warning
    ✅ Create separate asset record (different serial number)
```

### Test Case 3: Restock Workflow Continues to Work
```
GIVEN: Existing consumable with low stock

WHEN: User creates restock request → Approved → PO created → Delivery received

THEN: System should
    ✅ Add quantity to existing item
    ✅ NOT create duplicate asset
    ✅ Update status to Procured
```

---

## Implementation Checklist

### Phase 1: Backend Implementation
- [ ] Create `/api/assets/check-duplicate.php` endpoint
- [ ] Modify `AssetCrudService::createAsset()` to integrate duplication checking
- [ ] Add unit tests for duplication detection logic
- [ ] Test with various consumable categories

### Phase 2: Frontend Implementation
- [ ] Create `/assets/js/modules/assets/duplicate-checker.js`
- [ ] Add duplicate warning modal to legacy_create.php
- [ ] Integrate with existing Alpine.js form logic
- [ ] Add loading indicators and error handling

### Phase 3: User Experience
- [ ] Design duplicate warning modal (Bootstrap 5)
- [ ] Add "Create Restock Request Instead" quick action
- [ ] Pre-populate restock form when redirected from duplicate detection
- [ ] Add user preference: "Always warn me about duplicates"

### Phase 4: Testing & Documentation
- [ ] Test all scenarios (consumable, non-consumable, different projects)
- [ ] Update user documentation
- [ ] Add training video for warehousemen
- [ ] Monitor duplicate creation rates before/after

---

## Conclusion

**Current State:**
- ✅ Restock workflow properly prevents duplicates by adding quantity
- ✅ AssetMatchingService provides duplication detection capabilities
- ❌ Legacy create form does NOT use duplication detection

**Recommended Action:**
Implement duplication detection in the legacy create form to:
1. Prevent accidental duplicate consumable creation
2. Guide users to create restock requests for existing items
3. Maintain data quality and inventory accuracy

**Effort Estimate:**
- Backend: 4-6 hours
- Frontend: 6-8 hours
- Testing: 4 hours
- **Total: 14-18 hours**

**Impact:**
- Reduces duplicate consumable records
- Improves inventory accuracy
- Guides users to proper workflow (restock vs new item)
- Maintains database integrity
