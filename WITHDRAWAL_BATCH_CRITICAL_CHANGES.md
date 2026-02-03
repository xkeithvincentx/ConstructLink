# Withdrawal Batch Critical Changes Reference

## Quick Reference for Critical Differences

### 1. MOST CRITICAL: Release Logic

#### Borrowed-Tools (Status-Based)
```php
// BorrowedToolBatchWorkflowService::releaseBatch()

// Change asset status to 'Borrowed'
$updateAssetsSql = "
    UPDATE inventory_items a
    INNER JOIN borrowed_tools bt ON a.id = bt.inventory_item_id
    SET a.status = 'Borrowed'
    WHERE bt.batch_id = ?
";
```

#### Withdrawals (Quantity-Based) ✅
```php
// WithdrawalBatchWorkflowService::releaseBatch()

// ATOMIC QUANTITY DEDUCTION
foreach ($items as $item) {
    $deductSql = "
        UPDATE inventory_items
        SET available_quantity = available_quantity - ?
        WHERE id = ?
          AND available_quantity >= ?  -- Safety check
    ";
    $deductStmt->execute([
        $item['quantity'],
        $item['inventory_item_id'],
        $item['quantity']
    ]);

    // Verify deduction succeeded (affected rows check)
    if ($deductStmt->rowCount() === 0) {
        // Rollback - concurrent withdrawal detected
        throw new Exception("Quantity deduction failed");
    }
}
```

**Why This Matters**:
- Borrowed-tools: Temporary status change (reversible on return)
- Withdrawals: Permanent quantity reduction (consumables don't come back)
- Atomic operation prevents race conditions
- Safety check prevents negative quantities

---

### 2. CRITICAL: Cancel Logic

#### Borrowed-Tools
```php
// BorrowedToolBatchWorkflowService::cancelBatch()

// Just update status - no asset changes needed
$updated = $this->batchModel->update($batchId, [
    'status' => BorrowedToolStatus::CANCELED,
    'canceled_by' => $canceledBy,
    'cancellation_reason' => $reason
]);
```

#### Withdrawals ✅
```php
// WithdrawalBatchWorkflowService::cancelBatch()

// CRITICAL: Restore quantities if batch was already released
if ($batch['status'] === WithdrawalBatchStatus::RELEASED) {
    // Get all items
    $items = getWithdrawalItems($batchId);

    // ATOMIC QUANTITY RESTORATION
    foreach ($items as $item) {
        $restoreSql = "
            UPDATE inventory_items
            SET available_quantity = available_quantity + ?
            WHERE id = ?
        ";
        $restoreStmt->execute([
            $item['quantity'],
            $item['inventory_item_id']
        ]);
    }

    logActivity('restore_quantity', "Restored {$total_quantity} units");
}

// Then cancel the batch
$updated = $this->batchModel->update($batchId, [
    'status' => WithdrawalBatchStatus::CANCELED,
    'canceled_by' => $canceledBy,
    'cancellation_reason' => $reason
]);
```

**Why This Matters**:
- Prevents permanent inventory loss if batch is canceled after release
- Maintains accurate inventory counts
- Provides audit trail for quantity restoration

---

### 3. CRITICAL: Item Validation

#### Borrowed-Tools (Status Check)
```php
// BorrowedToolBatchModel::validateAndLockItems()

if ($asset['status'] !== AssetStatus::AVAILABLE) {
    return [
        'success' => false,
        'message' => $asset['name'] . ' is not available for borrowing'
    ];
}
```

#### Withdrawals (Quantity Check) ✅
```php
// WithdrawalBatchModel::validateAndLockConsumableItems()

// CRITICAL: Enforce consumable-only
if (!$asset['is_consumable']) {
    return [
        'success' => false,
        'message' => $asset['name'] . ' is not a consumable item'
    ];
}

// CRITICAL: Validate sufficient quantity
if ($asset['available_quantity'] < $quantity) {
    return [
        'success' => false,
        'message' => 'Insufficient quantity. Available: ' .
                     $asset['available_quantity'] . ', Requested: ' . $quantity
    ];
}
```

**Why This Matters**:
- Prevents non-consumables from being withdrawn
- Prevents over-withdrawal (requesting more than available)
- Data integrity protection

---

### 4. Batch Reference Generation

#### Borrowed-Tools
```php
// Format: BRW-[PROJECT]-[YEAR]-[SEQ]
// Example: BRW-PROJ1-2025-0001
return sprintf('BRW-%s-%s-%04d', $projectCode, $year, $sequence);
```

#### Withdrawals ✅
```php
// Format: WDR-[PROJECT]-[YEAR]-[SEQ]
// Example: WDR-PROJ1-2025-0001
return sprintf('WDR-%s-%s-%04d', $projectCode, $year, $sequence);

// Uses separate sequence table
$seqSql = "INSERT INTO withdrawal_batch_sequences (project_id, year, last_sequence)
           VALUES (?, ?, 1)
           ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1";
```

**Why This Matters**:
- Clear differentiation between borrowing (BRW) and withdrawal (WDR)
- Separate sequence tracking prevents conflicts
- ISO 55000 compliance

---

### 5. Workflow Determination

#### Borrowed-Tools (Dynamic)
```php
// BorrowedToolBatchModel::determineBatchWorkflow()

// Critical tools: Full MVA workflow
if ($isCritical) {
    return BorrowedToolStatus::PENDING_VERIFICATION;
}

// Basic tools: Streamlined workflow for authorized roles
if (in_array($currentUser['role_name'], ['Warehouseman', 'System Admin'])) {
    return BorrowedToolStatus::APPROVED; // Skip to approved
}

return BorrowedToolStatus::PENDING_VERIFICATION;
```

#### Withdrawals (Always MVA) ✅
```php
// WithdrawalBatchModel::createBatch()

// NO critical determination - all withdrawals use MVA
$workflowStatus = WithdrawalBatchStatus::PENDING_VERIFICATION;

// NO streamlined workflow - always follow MVA process
```

**Why This Matters**:
- Simpler workflow logic (no critical/basic distinction)
- All withdrawals follow same approval process
- Consistent user experience
- No $50K threshold logic needed

---

### 6. Model Differences Summary

| Aspect | Borrowed-Tools | Withdrawals |
|--------|---------------|------------|
| **Table** | `borrowed_tool_batches` | `withdrawal_batches` |
| **Terminology** | borrower_name, borrower_contact | receiver_name, receiver_contact |
| **Expected Return** | Yes (required field) | No (removed) |
| **is_critical_batch** | Yes (boolean field) | No (removed completely) |
| **Workflow Logic** | Dynamic (critical vs basic) | Static (always MVA) |
| **Item Type** | Non-consumables | Consumables only |
| **Release Action** | Status change | Quantity deduction |
| **Return Process** | Restore status | N/A (no returns) |

---

### 7. Database Schema Differences

#### Additional Field in Withdrawals
```sql
-- withdrawals table needs batch_id
ALTER TABLE withdrawals
ADD COLUMN batch_id INT NULL AFTER id;
```

#### Removed Fields from Withdrawal Batches
```sql
-- NOT in withdrawal_batches (removed from borrowed-tools):
borrower_signature_image VARCHAR(255)  -- Not needed
borrower_photo VARCHAR(255)            -- Not needed
expected_return DATE                   -- Not applicable for consumables
actual_return DATE                     -- Not applicable for consumables
returned_by INT                        -- Not applicable for consumables
return_date DATETIME                   -- Not applicable for consumables
return_notes TEXT                      -- Not applicable for consumables
is_critical_batch TINYINT(1)          -- Not needed (all follow MVA)
```

---

### 8. Status Constants

#### Borrowed-Tools Statuses
```php
PENDING_VERIFICATION
PENDING_APPROVAL
APPROVED
BORROWED           // After release
PARTIALLY_RETURNED // Some items returned
RETURNED           // All items returned
CANCELED
OVERDUE            // System-generated
```

#### Withdrawal Statuses ✅
```php
PENDING_VERIFICATION
PENDING_APPROVAL
APPROVED
RELEASED           // After release (final state for consumables)
CANCELED

// REMOVED (not applicable for consumables):
// - PARTIALLY_RETURNED
// - RETURNED
// - OVERDUE
```

---

### 9. Controller Validation

#### Borrowed-Tools
```php
// Validate expected return date
if (empty($expectedReturn)) {
    return error('Expected return date required');
}

// Check against available_quantity for consumables
if ($item['quantity'] > $asset['available_quantity']) {
    return error('Insufficient quantity');
}
```

#### Withdrawals ✅
```php
// NO expected return validation (field removed)

// CRITICAL: Enforce consumable-only
if (!$asset['is_consumable']) {
    return error('Only consumables can be withdrawn in batches');
}

// ALWAYS check quantity
if ($item['quantity'] > $asset['available_quantity']) {
    return error('Insufficient quantity');
}
```

---

### 10. Activity Logging Differences

#### Borrowed-Tools
```php
$this->logActivity(
    'release_batch',
    "Batch {$batch_reference} released to {$borrower_name}",
    'borrowed_tool_batches',
    $batchId
);
```

#### Withdrawals ✅
```php
$this->logActivity(
    'release_withdrawal_batch',
    "Batch {$batch_reference} released to {$receiver_name} with {$total_quantity} units deducted from inventory",
    'withdrawal_batches',
    $batchId
);

// Additional logging for quantity operations
$this->logActivity(
    'restore_quantity',
    "Restored {$total_quantity} units to inventory due to batch cancellation",
    'withdrawal_batches',
    $batchId
);
```

---

## Implementation Checklist

### Critical Changes Made ✅
- [x] Quantity deduction instead of status change on release
- [x] Quantity restoration on cancel after release
- [x] Consumable-only enforcement in validation
- [x] Removed expected_return field
- [x] Removed is_critical_batch logic
- [x] Changed batch reference prefix (BRW → WDR)
- [x] Changed terminology (borrower → receiver)
- [x] Removed return-related statuses
- [x] Atomic SQL operations for quantity changes
- [x] Separate sequence table for references

### Critical Logic Preserved ✅
- [x] MVA workflow (Maker-Verifier-Authorizer)
- [x] Transaction safety (BEGIN/COMMIT/ROLLBACK)
- [x] Row locking (SELECT ... FOR UPDATE)
- [x] Activity logging
- [x] CSRF protection
- [x] Permission checks
- [x] Project filtering

---

## Testing Commands

### Syntax Validation
```bash
php -l helpers/WithdrawalBatchStatus.php
php -l models/WithdrawalBatchModel.php
php -l services/WithdrawalBatchWorkflowService.php
php -l services/WithdrawalBatchQueryService.php
php -l services/WithdrawalBatchStatisticsService.php
php -l controllers/WithdrawalBatchController.php
```

### Database Migration
```bash
php migrations/add_withdrawal_batches.php
```

---

## Critical Code Patterns

### Always Use Transactions
```php
try {
    $this->db->beginTransaction();

    // ... operations ...

    $this->db->commit();
} catch (Exception $e) {
    $this->db->rollBack();
    error_log("Error: " . $e->getMessage());
    return ['success' => false, 'message' => 'Operation failed'];
}
```

### Always Lock Rows for Concurrent Safety
```php
$lockSql = "SELECT * FROM inventory_items WHERE id = ? FOR UPDATE";
$lockStmt = $this->db->prepare($lockSql);
$lockStmt->execute([$assetId]);
$asset = $lockStmt->fetch();
```

### Always Validate Consumable Status
```php
if (!$asset['is_consumable']) {
    return ['success' => false, 'message' => 'Only consumables allowed'];
}
```

### Always Use Atomic Quantity Operations
```php
// GOOD: Atomic operation
UPDATE inventory_items
SET available_quantity = available_quantity - ?
WHERE id = ? AND available_quantity >= ?

// BAD: Non-atomic (race condition)
$newQty = $asset['available_quantity'] - $quantity;
UPDATE inventory_items SET available_quantity = ? WHERE id = ?
```

---

## End of Critical Changes Reference
