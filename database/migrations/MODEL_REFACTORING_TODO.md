# Model Refactoring TODO - Status Constants

This file tracks the remaining refactoring work for the BorrowedTool models to replace hardcoded status strings with constants.

## Quick Reference

### Status Constants Available:
```php
BorrowedToolStatus::PENDING_VERIFICATION  // 'Pending Verification'
BorrowedToolStatus::PENDING_APPROVAL      // 'Pending Approval'
BorrowedToolStatus::APPROVED              // 'Approved'
BorrowedToolStatus::RELEASED              // 'Released'
BorrowedToolStatus::BORROWED              // 'Borrowed'
BorrowedToolStatus::PARTIALLY_RETURNED    // 'Partially Returned'
BorrowedToolStatus::RETURNED              // 'Returned'
BorrowedToolStatus::CANCELED              // 'Canceled'
BorrowedToolStatus::OVERDUE               // 'Overdue'
```

---

## BorrowedToolModel.php (76 occurrences)

### Files to Update:
- `/models/BorrowedToolModel.php`

### Key Methods Requiring Changes:

1. **`getBorrowedToolsWithFilters()`**
   - Line ~150-200: WHERE status IN ('Borrowed', 'Overdue')
   - Replace with parameterized query

2. **`verifyBorrowedTool()`**
   - Status transition: 'Pending Verification' → 'Pending Approval'
   - Replace with: BorrowedToolStatus::PENDING_VERIFICATION → BorrowedToolStatus::PENDING_APPROVAL

3. **`approveBorrowedTool()`**
   - Status transition: 'Pending Approval' → 'Approved'
   - Replace with: BorrowedToolStatus::PENDING_APPROVAL → BorrowedToolStatus::APPROVED

4. **`borrowTool()`** / **`releaseTool()`**
   - Status transition: 'Approved' → 'Released' / 'Borrowed'
   - Replace with: BorrowedToolStatus::APPROVED → BorrowedToolStatus::RELEASED

5. **`returnBorrowedTool()`**
   - Status transition: 'Borrowed' → 'Returned'
   - Replace with: BorrowedToolStatus::BORROWED → BorrowedToolStatus::RETURNED

6. **`cancelBorrowedTool()`**
   - Status transition: any → 'Canceled'
   - Replace with: BorrowedToolStatus::CANCELED

7. **`getOverdueBorrowedTools()`**
   - WHERE status IN ('Borrowed', 'Released')
   - Replace with parameterized query

8. **`updateOverdueStatus()`**
   - Set status = 'Overdue'
   - Replace with: BorrowedToolStatus::OVERDUE

### Example Refactoring Pattern:

**Before:**
```php
public function verifyBorrowedTool($id, $verifiedBy, $notes) {
    $sql = "UPDATE borrowed_tools
            SET status = 'Pending Approval',
                verified_by = ?,
                verification_date = NOW(),
                verification_notes = ?
            WHERE id = ?
              AND status = 'Pending Verification'";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$verifiedBy, $notes, $id]);
}
```

**After:**
```php
public function verifyBorrowedTool($id, $verifiedBy, $notes) {
    $sql = "UPDATE borrowed_tools
            SET status = ?,
                verified_by = ?,
                verification_date = NOW(),
                verification_notes = ?
            WHERE id = ?
              AND status = ?";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        BorrowedToolStatus::PENDING_APPROVAL,
        $verifiedBy,
        $notes,
        $id,
        BorrowedToolStatus::PENDING_VERIFICATION
    ]);
}
```

---

## BorrowedToolBatchModel.php (54 occurrences)

### Files to Update:
- `/models/BorrowedToolBatchModel.php`

### Key Methods Requiring Changes:

1. **`createBatch()`**
   - Initial status: 'Pending Verification' (critical) or auto-release to 'Released' (basic)
   - Replace with: BorrowedToolStatus::PENDING_VERIFICATION or BorrowedToolStatus::RELEASED

2. **`verifyBatch()`**
   - Batch status: 'Pending Verification' → 'Pending Approval'
   - Item status: 'Pending Verification' → 'Pending Approval'
   - Replace all with constants

3. **`approveBatch()`**
   - Batch status: 'Pending Approval' → 'Approved'
   - Item status: 'Pending Approval' → 'Approved'
   - Replace all with constants

4. **`releaseBatch()`**
   - Batch status: 'Approved' → 'Released'
   - Item status: 'Approved' → 'Borrowed'
   - Replace all with constants

5. **`returnBatch()`**
   - Item status: 'Borrowed' → 'Returned'
   - Batch status: ALL items returned → 'Returned', some returned → 'Partially Returned'
   - Replace all with constants

6. **`cancelBatch()`**
   - Batch status: any → 'Canceled'
   - Item status: any → 'Canceled'
   - Replace with: BorrowedToolStatus::CANCELED

7. **`getBatchStats()`**
   - Multiple status checks for statistics
   - Replace all WHERE status = 'X' with parameterized queries

8. **`getOverdueBatchCount()`**
   - WHERE status = 'Released' AND expected_return < NOW()
   - Replace with: BorrowedToolStatus::RELEASED

### Example Refactoring Pattern:

**Before:**
```php
public function releaseBatch($batchId, $releasedBy, $notes) {
    // Update batch status
    $batchSql = "UPDATE borrowed_tool_batches
                 SET status = 'Released',
                     released_by = ?,
                     release_date = NOW(),
                     release_notes = ?
                 WHERE id = ? AND status = 'Approved'";

    // Update item statuses
    $itemSql = "UPDATE borrowed_tools
                SET status = 'Borrowed',
                    borrowed_by = ?,
                    borrowed_date = NOW()
                WHERE batch_id = ? AND status = 'Approved'";

    // Execute...
}
```

**After:**
```php
public function releaseBatch($batchId, $releasedBy, $notes) {
    // Update batch status
    $batchSql = "UPDATE borrowed_tool_batches
                 SET status = ?,
                     released_by = ?,
                     release_date = NOW(),
                     release_notes = ?
                 WHERE id = ? AND status = ?";

    // Update item statuses
    $itemSql = "UPDATE borrowed_tools
                SET status = ?,
                    borrowed_by = ?,
                    borrowed_date = NOW()
                WHERE batch_id = ? AND status = ?";

    $batchParams = [
        BorrowedToolStatus::RELEASED,
        $releasedBy,
        $notes,
        $batchId,
        BorrowedToolStatus::APPROVED
    ];

    $itemParams = [
        BorrowedToolStatus::BORROWED,
        $releasedBy,
        $batchId,
        BorrowedToolStatus::APPROVED
    ];

    // Execute...
}
```

---

## Search Commands

Use these to find all occurrences:

```bash
# Find all hardcoded status strings in BorrowedToolModel
grep -n "'Pending Verification'\|'Pending Approval'\|'Approved'\|'Released'\|'Borrowed'\|'Returned'\|'Canceled'\|'Overdue'\|'Partially Returned'" models/BorrowedToolModel.php

# Find all hardcoded status strings in BorrowedToolBatchModel
grep -n "'Pending Verification'\|'Pending Approval'\|'Approved'\|'Released'\|'Borrowed'\|'Returned'\|'Canceled'\|'Overdue'\|'Partially Returned'" models/BorrowedToolBatchModel.php

# Count occurrences per file
grep -c "Pending Verification\|Pending Approval\|Approved\|Released\|Borrowed\|Returned" models/BorrowedToolModel.php
grep -c "Pending Verification\|Pending Approval\|Approved\|Released\|Borrowed\|Returned" models/BorrowedToolBatchModel.php
```

---

## Testing After Refactoring

### Unit Tests to Run:

1. **Workflow Transitions**:
   - Create → Pending Verification ✓
   - Verify → Pending Approval ✓
   - Approve → Approved ✓
   - Release → Released/Borrowed ✓
   - Return → Returned ✓
   - Cancel → Canceled ✓

2. **Batch Operations**:
   - Create batch (critical and basic tools)
   - Verify batch
   - Approve batch
   - Release batch
   - Partial return
   - Full return
   - Cancel batch

3. **Filtering**:
   - Filter by each status
   - Filter by multiple statuses
   - Sort by status + date
   - Search with status filter

4. **Statistics**:
   - Count by status
   - Overdue calculations
   - Time-based stats
   - Dashboard metrics

### Manual Testing Checklist:

- [ ] Create new borrowed tool request
- [ ] Verify critical tool request
- [ ] Approve critical tool request
- [ ] Release tool to borrower
- [ ] Return tool with good condition
- [ ] Return tool with damaged condition (check incident creation)
- [ ] Cancel pending request
- [ ] Filter borrowed tools list by status
- [ ] View batch details with mixed statuses
- [ ] Check dashboard statistics accuracy
- [ ] Test overdue marking (manual and automated)
- [ ] Verify MVA workflow buttons show/hide correctly

---

## Completion Checklist

- [ ] Add `require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';` to both model files
- [ ] Replace all hardcoded status strings in BorrowedToolModel.php
- [ ] Replace all hardcoded status strings in BorrowedToolBatchModel.php
- [ ] Test all workflow transitions
- [ ] Test all batch operations
- [ ] Test filtering and search
- [ ] Test statistics calculations
- [ ] Update any view files that have hardcoded statuses in PHP sections
- [ ] Document any breaking changes (should be none if done correctly)
- [ ] Update REFACTORING_GUIDE.md version to v1.1

---

## Notes

- **Backward Compatibility**: Using constants maintains the same string values, so no database changes needed
- **SQL Injection Safety**: All status values are now parameterized in queries
- **Type Safety**: Constants provide autocomplete and catch typos at development time
- **Maintainability**: Single source of truth for all status values
- **Performance**: No performance impact (same string values, just referenced differently)

---

## Quick Win Approach

If doing the full refactoring at once is too much, prioritize these high-impact methods:

### Priority 1 (High Traffic):
1. `getBorrowedToolsWithFilters()` - Most frequently called
2. `getBatchWithItems()` - Used on every batch view
3. `returnBatch()` / `returnBorrowedTool()` - Critical business logic

### Priority 2 (Workflow):
4. `verifyBatch()` / `verifyBorrowedTool()`
5. `approveBatch()` / `approveBorrowedTool()`
6. `releaseBatch()` / `releaseTool()`

### Priority 3 (Supporting):
7. `getBatchStats()` - Dashboard statistics
8. `getOverdueBatchCount()` - Overdue calculations
9. `cancelBatch()` / `cancelBorrowedTool()`

This way you can refactor incrementally and test each priority group before moving to the next.
