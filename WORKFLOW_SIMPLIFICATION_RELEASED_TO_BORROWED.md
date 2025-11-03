# Borrowed Tools Workflow Simplification: "Released" Status Removal

**Date:** 2025-01-27
**Version:** 1.0
**Status:** ✅ Complete

---

## Executive Summary

Removed "Released" from the user-facing filter dropdown to eliminate confusion between "Released" and "Borrowed" statuses. Both essentially mean the same thing: the tool is currently with the borrower.

### Key Changes:
- ✅ Removed "Released" from filter validation
- ✅ Removed "Released" from filter dropdown options
- ✅ Updated documentation to clarify "Release" is an ACTION, not a STATUS
- ✅ Maintained backward compatibility for existing database records

---

## Problem Statement

**User Confusion:**
Users were confused by having both "Released" and "Borrowed" in the filter dropdown:
- **Released:** Batch-level status (after MVA approval)
- **Borrowed:** Item-level status (after physical handover)

**User Question:**
> "Isn't Released the same as Borrowed? Why are there two statuses?"

**Answer:**
Yes, they both mean "tool is currently with borrower." The distinction was unnecessary from a user perspective.

---

## Solution: Clarify Release as an ACTION, not a STATUS

### New Workflow Understanding:

```
CRITICAL TOOL (≥ ₱50,000):
Pending Verification → Pending Approval → Approved → [Release Action] → Borrowed
                                                          ↓
                                                   (Physical handover)

BASIC TOOL (< ₱50,000):
Created → [Release Action] → Borrowed
              ↓
       (Physical handover)
```

**Key Concept:**
- **"Release"** = The ACTION of physically handing over the tool to borrower
- **"Borrowed"** = The STATUS after the release action completes

---

## Technical Implementation

### 1. Filter Validation (`/views/borrowed-tools/partials/_filters.php`)

**Before:**
```php
$allowedStatuses = [
    'Pending Verification',
    'Pending Approval',
    'Approved',
    'Released',  // ← REMOVED
    'Borrowed',
    'Partially Returned',
    'Returned',
    'Canceled',
    'Overdue'
];
```

**After:**
```php
$allowedStatuses = [
    'Pending Verification',
    'Pending Approval',
    'Approved',
    'Borrowed',  // ← Only this, not "Released"
    'Partially Returned',
    'Returned',
    'Canceled',
    'Overdue'
];
```

---

### 2. Filter Dropdown Options (`/views/borrowed-tools/partials/_filters.php`)

**Before:**
```php
$statusOptions = [
    ['value' => 'Approved', 'label' => 'Approved', 'roles' => [...]],
    ['value' => 'Released', 'label' => 'Released', 'roles' => []],  // ← REMOVED
    ['value' => 'Borrowed', 'label' => 'Borrowed', 'roles' => []],
    // ...
];
```

**After:**
```php
$statusOptions = [
    ['value' => 'Approved', 'label' => 'Approved', 'roles' => [...]],
    ['value' => 'Borrowed', 'label' => 'Borrowed', 'roles' => []],  // ← Only this
    // ...
];
```

---

### 3. Status Helper Constants (`/helpers/BorrowedToolStatus.php`)

**Updated Documentation:**
```php
// Tool is currently with the borrower (after physical release action)
// Note: "Released" is the ACTION of handing over; "Borrowed" is the resulting STATUS
const BORROWED = 'Borrowed';

// Legacy constant for backward compatibility (deprecated - use BORROWED instead)
// After the "Release" action, status becomes BORROWED, not RELEASED
const RELEASED = 'Released';
```

**Status Descriptions:**
```php
case self::BORROWED:
    return 'Currently borrowed by user (after physical release)';

case self::RELEASED:
    return 'DEPRECATED: Use "Borrowed" status instead';
```

---

### 4. Query Service (`/services/BorrowedToolQueryService.php`)

**Backward Compatibility Maintained:**
```php
// Check both single items and batches for overdue status
// Note: Using BORROWED for items, RELEASED for legacy batch records
// New batches should use BORROWED status after release action
$conditions[] = "((bt.status = ? AND bt.expected_return < CURDATE())
                 OR (btb.status = ? AND btb.expected_return < CURDATE()))";
$params[] = BorrowedToolStatus::BORROWED;
$params[] = BorrowedToolStatus::RELEASED;  // Still check for legacy records
```

---

## Database State Analysis

### Current Database Statuses:

**borrowed_tools table:**
```
- Borrowed (1 record)
- Returned (43 records)
- Canceled (1 record)
```

**borrowed_tool_batches table:**
```
- Released (2 records)  ← Legacy batches
- Partially Returned (1 record)
- Returned (28 records)
- Canceled (1 record)
```

**Observation:**
- 2 existing batches have "Released" status
- These will continue to work correctly
- Query service checks for both "Borrowed" and "Released" to handle legacy data

---

## Backward Compatibility

### How Legacy "Released" Records are Handled:

1. **Database queries** still check for `status = 'Released'` in batch table
2. **Filter queries** include both BORROWED and RELEASED when checking overdue/due soon
3. **Display logic** shows these records as "Borrowed" to users
4. **No migration needed** - existing records continue to work

**Query Example:**
```php
// Overdue filter checks both statuses
if ($filters['status'] === 'overdue') {
    $conditions[] = "((bt.status = ? AND bt.expected_return < CURDATE())
                     OR (btb.status = ? AND btb.expected_return < CURDATE()))";
    $params[] = BorrowedToolStatus::BORROWED;
    $params[] = BorrowedToolStatus::RELEASED;  // ← Still works for legacy data
}
```

---

## User Experience Impact

### Filter Dropdown (Before):
```
All Statuses
Pending Verification
Pending Approval
Approved
Released        ← Confusing
Borrowed        ← Confusing (same as Released?)
Partially Returned
Returned
Canceled
```

### Filter Dropdown (After):
```
All Statuses
Pending Verification
Pending Approval
Approved
Borrowed        ← Clear: tool is with borrower
Partially Returned
Returned
Canceled
```

**Benefits:**
- ✅ Eliminates confusion between Released and Borrowed
- ✅ Clearer workflow understanding
- ✅ Simpler filter options
- ✅ Both critical and basic tools end up as "Borrowed"

---

## Workflow Clarification

### Critical Tool Workflow (≥ ₱50,000):

```
Step 1: Warehouseman creates request
        Status: Pending Verification

Step 2: Project Manager verifies
        Status: Pending Approval

Step 3: Director approves
        Status: Approved (ready for release)

Step 4: Warehouseman clicks "Release to Borrower" button
        - Physical handover happens
        - Borrower signs paperwork
        - Status changes: Approved → Borrowed

Step 5: Tool is with borrower
        Status: Borrowed

Step 6: Tool is returned
        Status: Returned
```

**Key Point:**
"Release" is the button click and physical handover action, not a status.

---

## Files Modified

### 1. `/views/borrowed-tools/partials/_filters.php`
- Removed "Released" from `validateStatus()` function
- Removed "Released" from `renderStatusOptions()` function
- Updated documentation comments

### 2. `/helpers/BorrowedToolStatus.php`
- Added deprecation comments for RELEASED constant
- Updated status descriptions
- Clarified BORROWED is the correct status after release

### 3. `/services/BorrowedToolQueryService.php`
- Added comments explaining backward compatibility
- Clarified that RELEASED checks are for legacy records only

---

## Testing Checklist

### Filter Dropdown:
- [x] "Released" does NOT appear in status dropdown
- [x] "Borrowed" appears correctly
- [x] All other statuses appear correctly

### Filtering Functionality:
- [x] Filtering by "Borrowed" shows items with status = 'Borrowed'
- [x] Legacy batches with "Released" status are still found
- [x] Overdue filter includes both Borrowed and Released records
- [x] "All Statuses" shows everything correctly

### Backward Compatibility:
- [x] Existing 2 batches with "Released" status still work
- [x] No database migration needed
- [x] Queries handle both statuses correctly

### PHP Syntax:
- [x] `/views/borrowed-tools/partials/_filters.php` - No syntax errors
- [x] `/helpers/BorrowedToolStatus.php` - No syntax errors
- [x] `/services/BorrowedToolQueryService.php` - No syntax errors

---

## Migration Path (Future)

### Optional: Migrate Legacy "Released" Records to "Borrowed"

If desired, existing "Released" batch records can be migrated:

```sql
-- Migrate legacy "Released" batches to "Borrowed"
UPDATE borrowed_tool_batches
SET status = 'Borrowed'
WHERE status = 'Released';
```

**Recommendation:**
- Not required immediately
- Can be done during next major system maintenance
- No user-facing impact either way

---

## Documentation Updates

### Updated Comments:
- ✅ Filter validation function
- ✅ Status options rendering
- ✅ Status constant definitions
- ✅ Query service comments
- ✅ Workflow architecture documentation

### Key Documentation Added:
```
"Workflow: Approved → [Release Action] → Borrowed (Release is an action, not a status)"
```

---

## Rollback Plan

If issues occur, revert these changes:

```bash
# Revert filter changes
git revert <commit-hash>

# Or manually add "Released" back:
# 1. Add to validateStatus() array
# 2. Add to renderStatusOptions() array
# 3. Remove deprecation comments
```

**Rollback Impact:** None - backward compatible

---

## Conclusion

### What Changed:
- ❌ Removed "Released" from user-facing filter dropdown
- ✅ Kept "Released" in database and queries (backward compatible)
- ✅ Clarified "Release" = action, "Borrowed" = status
- ✅ Simplified user experience

### What Stayed the Same:
- ✅ Database schema unchanged
- ✅ Existing records work correctly
- ✅ Query logic handles both statuses
- ✅ No breaking changes

### Result:
**Clearer workflow with no technical debt or breaking changes.**

---

**Implemented by:** Ranoa Digital Solutions
**Testing Status:** ✅ Complete
**Deployment Ready:** ✅ Yes
