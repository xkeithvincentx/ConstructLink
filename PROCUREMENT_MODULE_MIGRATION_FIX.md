# Procurement Module: Database Table Migration Fix

**Date**: 2025-11-07
**Issue**: SQL queries in procurement module still referencing old `procurement_assets` table name
**Root Cause**: Table renamed to `procurement_inventory` but code still uses old table name
**Status**: CRITICAL - All queries will fail at runtime

---

## Executive Summary

**CRITICAL DATABASE MIGRATION ISSUE DETECTED**

The procurement module contains 10 SQL queries across 3 files that reference the old `procurement_assets` table, which no longer exists in the database. The table has been renamed to `procurement_inventory` with a modified schema.

**Impact**:
- Asset generation from procurement orders will FAIL
- Linking procurement to inventory items will FAIL
- Procurement asset queries will FAIL
- Asset count calculations will be incorrect

---

## Database Migration Mapping

| Old Table Name | New Table Name | Status |
|----------------|----------------|--------|
| `procurement_assets` | `procurement_inventory` | ✅ Table migrated |

### Schema Changes

**OLD TABLE: `procurement_assets`**
```sql
CREATE TABLE procurement_assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    procurement_id INT,              -- Old column name
    asset_id INT,                    -- Old column name
    procurement_item_id INT,
    serial_number VARCHAR(100),
    created_at TIMESTAMP
);
```

**NEW TABLE: `procurement_inventory`**
```sql
CREATE TABLE procurement_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    procurement_order_id INT,        -- CHANGED: procurement_id → procurement_order_id
    procurement_item_id INT,
    inventory_item_id INT,           -- CHANGED: asset_id → inventory_item_id
    serial_number VARCHAR(100),
    quantity_generated INT DEFAULT 1, -- NEW FIELD
    created_at TIMESTAMP
);
```

---

## Issues Found

### Summary
- **Total References**: 10 occurrences
- **Files Affected**: 3 files
- **Severity**: CRITICAL
- **Table Name**: `procurement_assets` (does not exist)
- **Correct Name**: `procurement_inventory`

### Column Mapping Changes Required

When updating table name from `procurement_assets` to `procurement_inventory`, also update column references:

| Old Column | New Column | Notes |
|------------|------------|-------|
| `asset_id` | `inventory_item_id` | References inventory_items.id |
| `procurement_id` | `procurement_order_id` | For ProcurementModel only |

**IMPORTANT**:
- In `ProcurementOrderController` and `ProcurementOrderModel`: Use `procurement_order_id` (already correct)
- In `ProcurementModel`: Change `procurement_id` → `procurement_order_id`

---

## Detailed Findings

### 1. `/controllers/ProcurementOrderController.php`

**Issues**: 4 occurrences (all identical pattern)

#### Line 837
```php
// CURRENT (BROKEN):
$linkSql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";

// CORRECTED:
$linkSql = "INSERT INTO procurement_inventory (inventory_item_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";
```

**Context**: Asset generation for consumable items
**Function**: `generateAssets()`
**Impact**: Critical - asset generation will fail

---

#### Line 874
```php
// CURRENT (BROKEN):
$linkSql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";

// CORRECTED:
$linkSql = "INSERT INTO procurement_inventory (inventory_item_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";
```

**Context**: Asset generation for non-consumable items (loop)
**Function**: `generateAssets()`
**Impact**: Critical - asset generation will fail

---

#### Line 1727
```php
// CURRENT (BROKEN):
$linkSql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";

// CORRECTED:
$linkSql = "INSERT INTO procurement_inventory (inventory_item_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";
```

**Context**: Receipt process - consumable asset generation
**Function**: `receiveOrder()`
**Impact**: Critical - receipt workflow will fail

---

#### Line 1764
```php
// CURRENT (BROKEN):
$linkSql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";

// CORRECTED:
$linkSql = "INSERT INTO procurement_inventory (inventory_item_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";
```

**Context**: Receipt process - non-consumable asset generation
**Function**: `receiveOrder()`
**Impact**: Critical - receipt workflow will fail

---

### 2. `/models/ProcurementModel.php`

**Issues**: 3 occurrences

#### Line 340 (Function: `linkProcurementAsset()`)
```php
// CURRENT (BROKEN):
$sql = "INSERT INTO procurement_assets (procurement_id, asset_id, serial_number, created_at) VALUES (?, ?, ?, NOW())";

// CORRECTED:
$sql = "INSERT INTO procurement_inventory (procurement_order_id, inventory_item_id, serial_number, created_at) VALUES (?, ?, ?, NOW())";
```

**Column Changes**:
- Table: `procurement_assets` → `procurement_inventory`
- `procurement_id` → `procurement_order_id`
- `asset_id` → `inventory_item_id`

**Impact**: High - legacy asset linking will fail

---

#### Line 650 (Function: `getProcurementAssets()`)
```php
// CURRENT (BROKEN):
$sql = "
    SELECT a.*, pa.serial_number as procurement_serial
    FROM inventory_items a
    INNER JOIN procurement_assets pa ON a.id = pa.asset_id
    WHERE pa.procurement_id = ?
    ORDER BY a.created_at ASC
";

// CORRECTED:
$sql = "
    SELECT a.*, pi.serial_number as procurement_serial
    FROM inventory_items a
    INNER JOIN procurement_inventory pi ON a.id = pi.inventory_item_id
    WHERE pi.procurement_order_id = ?
    ORDER BY a.created_at ASC
";
```

**Changes**:
- Table alias: `pa` → `pi` (for clarity: procurement_inventory)
- Table: `procurement_assets` → `procurement_inventory`
- Join column: `pa.asset_id` → `pi.inventory_item_id`
- WHERE column: `pa.procurement_id` → `pi.procurement_order_id`

**Impact**: High - cannot retrieve procurement assets

---

#### Line 849 (Function: `deleteProcurement()`)
```php
// CURRENT (BROKEN):
$sql = "DELETE FROM procurement_assets WHERE procurement_id = ?";

// CORRECTED:
$sql = "DELETE FROM procurement_inventory WHERE procurement_order_id = ?";
```

**Changes**:
- Table: `procurement_assets` → `procurement_inventory`
- Column: `procurement_id` → `procurement_order_id`

**Impact**: High - procurement deletion will leave orphaned records

---

### 3. `/models/ProcurementOrderModel.php`

**Issues**: 3 occurrences (all in subqueries)

#### Line 482 (Function: `getOrderItems()`)
```php
// CURRENT (BROKEN):
$sql = "
    SELECT pi.*, c.name as category_name,
           po.project_id, po.vendor_id,
           (pi.quantity_received - COALESCE(
               (SELECT COUNT(*) FROM procurement_assets pa WHERE pa.procurement_item_id = pi.id), 0
           )) as available_for_generation
    FROM procurement_items pi
    ...
";

// CORRECTED:
$sql = "
    SELECT pi.*, c.name as category_name,
           po.project_id, po.vendor_id,
           (pi.quantity_received - COALESCE(
               (SELECT COUNT(*) FROM procurement_inventory pi_inv WHERE pi_inv.procurement_item_id = pi.id), 0
           )) as available_for_generation
    FROM procurement_items pi
    ...
";
```

**Changes**:
- Subquery table: `procurement_assets` → `procurement_inventory`
- Subquery alias: `pa` → `pi_inv` (avoid collision with main query alias)
- Column: `pa.procurement_item_id` → `pi_inv.procurement_item_id` (no change in column name, just table)

**Impact**: Medium - incorrect count of assets available for generation

---

#### Line 519 (Function: `getAvailableItemsForAssetGeneration()`)
```php
// CURRENT (BROKEN):
(pi.quantity_received - COALESCE(
    (SELECT COUNT(*) FROM procurement_assets pa WHERE pa.procurement_item_id = pi.id), 0
)) as available_for_generation

// CORRECTED:
(pi.quantity_received - COALESCE(
    (SELECT COUNT(*) FROM procurement_inventory pi_inv WHERE pi_inv.procurement_item_id = pi.id), 0
)) as available_for_generation
```

**Impact**: Medium - incorrect count of assets available for generation

---

#### Line 658 (Function: `getGenerationSummary()` - inferred)
```php
// CURRENT (BROKEN):
THEN (SELECT COUNT(*) FROM procurement_assets pa WHERE pa.procurement_item_id = pi.id)

// CORRECTED:
THEN (SELECT COUNT(*) FROM procurement_inventory pi_inv WHERE pi_inv.procurement_item_id = pi.id)
```

**Impact**: Medium - incorrect asset generation statistics

---

### 4. `/models/ProcurementItemModel.php`

**Issues**: 3 occurrences (all using inventory_items correctly, but need verification)

#### Lines 181, 225, 452 - ALREADY CORRECT ✅
```php
// These queries already use the correct table name:
(SELECT COUNT(*) FROM inventory_items a WHERE a.procurement_item_id = pi.id)
```

**Status**: These queries directly query `inventory_items` table instead of the linking table.
**Verification Needed**: Confirm that `inventory_items.procurement_item_id` exists and is indexed.

---

## Column Reference Verification

Checked `inventory_items` table for procurement-related columns:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root constructlink_db -e "SHOW COLUMNS FROM inventory_items LIKE '%procure%';"
```

**Result**: Need to verify if `procurement_item_id` column exists in `inventory_items` table.

**Action Required**:
1. Verify `inventory_items.procurement_item_id` column exists
2. If not, queries in `ProcurementItemModel.php` will also fail
3. May need to use JOIN to `procurement_inventory` instead

---

## Recommended Fix Order

### Phase 1: Critical Fixes (Immediate)
1. ✅ **ProcurementOrderController.php** - Lines 837, 874, 1727, 1764
   - Fix INSERT statements for asset generation
   - Change table and column names

2. ✅ **ProcurementModel.php** - Lines 340, 650, 849
   - Fix INSERT, SELECT, DELETE statements
   - Change table and column names

3. ✅ **ProcurementOrderModel.php** - Lines 482, 519, 658
   - Fix COUNT subqueries
   - Change table names only (column names correct)

### Phase 2: Verification
4. ⚠️ **Verify inventory_items schema**
   - Check if `procurement_item_id` column exists
   - Check if `procurement_order_id` column exists
   - Verify indexes on these columns

5. ⚠️ **Test ProcurementItemModel.php queries**
   - Lines 181, 225, 452 may need adjustment depending on schema

---

## Search and Replace Operations

### Safe Replace Pattern 1: INSERT statements (ProcurementOrderController)
```bash
# Find:
INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at)

# Replace with:
INSERT INTO procurement_inventory (inventory_item_id, procurement_order_id, procurement_item_id, created_at)
```

**Files**: `controllers/ProcurementOrderController.php`
**Occurrences**: 4

---

### Safe Replace Pattern 2: ProcurementModel specific
```bash
# Pattern A: INSERT
# Find:
INSERT INTO procurement_assets (procurement_id, asset_id, serial_number, created_at)

# Replace with:
INSERT INTO procurement_inventory (procurement_order_id, inventory_item_id, serial_number, created_at)
```

```bash
# Pattern B: JOIN
# Find:
INNER JOIN procurement_assets pa ON a.id = pa.asset_id
    WHERE pa.procurement_id = ?

# Replace with:
INNER JOIN procurement_inventory pi ON a.id = pi.inventory_item_id
    WHERE pi.procurement_order_id = ?
```

```bash
# Pattern C: DELETE
# Find:
DELETE FROM procurement_assets WHERE procurement_id = ?

# Replace with:
DELETE FROM procurement_inventory WHERE procurement_order_id = ?
```

**File**: `models/ProcurementModel.php`
**Occurrences**: 3

---

### Safe Replace Pattern 3: COUNT subqueries (ProcurementOrderModel)
```bash
# Find:
(SELECT COUNT(*) FROM procurement_assets pa WHERE pa.procurement_item_id = pi.id)

# Replace with:
(SELECT COUNT(*) FROM procurement_inventory pi_inv WHERE pi_inv.procurement_item_id = pi.id)
```

**File**: `models/ProcurementOrderModel.php`
**Occurrences**: 3

---

## Testing Checklist

After applying fixes, test the following workflows:

### Asset Generation
- [ ] Create procurement order
- [ ] Receive procurement order
- [ ] Generate assets from received items
- [ ] Verify assets appear in inventory
- [ ] Verify `procurement_inventory` records created
- [ ] Verify linking between procurement and inventory items

### Asset Count Calculations
- [ ] Check "available for generation" counts are accurate
- [ ] Verify generation summary statistics
- [ ] Test with both consumable and non-consumable items

### Procurement Management
- [ ] View procurement assets (getProcurementAssets)
- [ ] Delete procurement order
- [ ] Verify procurement_inventory records are cascade deleted

### Edge Cases
- [ ] Multiple assets per item
- [ ] Serial number tracking
- [ ] Consumable vs non-consumable handling

---

## SQL Verification Query

Run this query to check current state of procurement_inventory table:

```sql
-- Check table structure
DESCRIBE procurement_inventory;

-- Check if any data exists
SELECT COUNT(*) as total_records FROM procurement_inventory;

-- Check column names match expectations
SHOW COLUMNS FROM procurement_inventory WHERE Field IN ('procurement_order_id', 'inventory_item_id', 'procurement_item_id');

-- Verify foreign key relationships
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'constructlink_db'
    AND TABLE_NAME = 'procurement_inventory'
    AND REFERENCED_TABLE_NAME IS NOT NULL;
```

---

## Risk Assessment

### High Risk
- **Asset Generation**: Complete failure when generating assets from procurement
- **Data Integrity**: Orphaned records if deletions fail
- **Business Process**: Procurement-to-inventory workflow completely broken

### Medium Risk
- **Reporting**: Incorrect counts and statistics
- **UI Display**: Missing or incorrect asset information

### Low Risk
- **ProcurementItemModel**: May be using alternative query path (direct to inventory_items)

---

## Summary

| Metric | Count |
|--------|-------|
| Total Issues | 10 |
| Critical Issues | 4 (INSERT failures) |
| High Issues | 3 (SELECT/DELETE failures) |
| Medium Issues | 3 (COUNT inaccuracies) |
| Files Affected | 3 |
| Lines Modified | 10 |

**Estimated Fix Time**: 30 minutes
**Testing Time**: 1-2 hours
**Risk Level**: CRITICAL

---

## Files Modified Summary

```
controllers/ProcurementOrderController.php  (4 changes)
├── Line 837:  INSERT procurement_assets → procurement_inventory
├── Line 874:  INSERT procurement_assets → procurement_inventory
├── Line 1727: INSERT procurement_assets → procurement_inventory
└── Line 1764: INSERT procurement_assets → procurement_inventory

models/ProcurementModel.php  (3 changes)
├── Line 340: INSERT procurement_assets → procurement_inventory (+ column changes)
├── Line 650: JOIN procurement_assets → procurement_inventory (+ column changes)
└── Line 849: DELETE procurement_assets → procurement_inventory (+ column change)

models/ProcurementOrderModel.php  (3 changes)
├── Line 482: COUNT subquery procurement_assets → procurement_inventory
├── Line 519: COUNT subquery procurement_assets → procurement_inventory
└── Line 658: COUNT subquery procurement_assets → procurement_inventory
```

---

## Next Steps

1. ✅ **IMMEDIATE**: Apply table name changes to all 10 occurrences
2. ✅ **IMMEDIATE**: Update column references in ProcurementModel.php (3 occurrences)
3. ⚠️ **VERIFY**: Check inventory_items schema for procurement_item_id column
4. ✅ **TEST**: Run full procurement workflow after changes
5. ✅ **VALIDATE**: Confirm no runtime errors in PHP error logs

---

**Migration Status**: ❌ INCOMPLETE - Procurement module still using old table name
**Error Risk**: 🔴 CRITICAL - All procurement-to-inventory operations will fail
**Code Quality**: ⚠️ Needs immediate attention before production use

---

**Generated**: 2025-11-07
**Agent**: ConstructLink Code Review Agent
**Priority**: P0 - CRITICAL
