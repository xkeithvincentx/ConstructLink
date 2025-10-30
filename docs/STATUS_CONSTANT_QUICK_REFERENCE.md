# Status Constant Quick Reference Guide
**For Developers:** Fast lookup for status constant usage in ConstructLink

---

## BORROWED TOOL STATUS CONSTANTS

### Import:
```php
require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';
require_once APP_ROOT . '/helpers/AssetStatus.php';
```

### Constants Available:

| Constant | Value | Badge Color | Icon |
|----------|-------|-------------|------|
| `BorrowedToolStatus::PENDING_VERIFICATION` | "Pending Verification" | warning | hourglass-split |
| `BorrowedToolStatus::PENDING_APPROVAL` | "Pending Approval" | warning | clock-history |
| `BorrowedToolStatus::APPROVED` | "Approved" | info | check-circle |
| `BorrowedToolStatus::RELEASED` | "Released" | primary | box-arrow-right |
| `BorrowedToolStatus::BORROWED` | "Borrowed" | primary | box-arrow-right |
| `BorrowedToolStatus::PARTIALLY_RETURNED` | "Partially Returned" | secondary | arrow-left-right |
| `BorrowedToolStatus::RETURNED` | "Returned" | success | check-circle-fill |
| `BorrowedToolStatus::CANCELED` | "Canceled" | dark | x-circle |
| `BorrowedToolStatus::OVERDUE` | "Overdue" | danger | exclamation-triangle-fill |

---

## ASSET STATUS CONSTANTS

| Constant | Value | Badge Color | Icon |
|----------|-------|-------------|------|
| `AssetStatus::AVAILABLE` | "available" | success | check-circle-fill |
| `AssetStatus::BORROWED` | "borrowed" | primary | box-arrow-right |
| `AssetStatus::IN_USE` | "in_use" | primary | gear-fill |
| `AssetStatus::IN_TRANSIT` | "in_transit" | warning | truck |
| `AssetStatus::UNDER_MAINTENANCE` | "under_maintenance" | warning | tools |
| `AssetStatus::DAMAGED` | "damaged" | warning | exclamation-triangle-fill |
| `AssetStatus::LOST` | "lost" | danger | question-circle-fill |
| `AssetStatus::DISPOSED` | "disposed" | danger | trash-fill |
| `AssetStatus::RETIRED` | "retired" | secondary | archive-fill |

---

## COMMON PATTERNS

### 1. Status Comparison (Models/Controllers)
```php
// ❌ DON'T:
if ($tool['status'] == 'Borrowed') {

// ✅ DO:
if ($tool['status'] == BorrowedToolStatus::BORROWED) {

// ✅ BETTER (Using helper):
if (BorrowedToolStatus::isActiveBorrowing($tool['status'])) {
```

### 2. Status Assignment
```php
// ❌ DON'T:
$updateData['status'] = 'Returned';

// ✅ DO:
$updateData['status'] = BorrowedToolStatus::RETURNED;
```

### 3. SQL Queries (Parameterized)
```php
// ❌ DON'T:
$sql = "SELECT * FROM borrowed_tools WHERE status = 'Borrowed'";

// ✅ DO:
$sql = "SELECT * FROM borrowed_tools WHERE status = ?";
$stmt->execute([BorrowedToolStatus::BORROWED]);
```

### 4. Status Badge Display (Views) - WCAG AA Compliant
```php
<!-- ❌ DON'T: -->
<span class="badge badge-primary">Borrowed</span>

<!-- ✅ DO (Full Accessibility): -->
<span class="badge badge-<?= BorrowedToolStatus::getStatusBadgeColor($tool['status']) ?>"
      role="status"
      aria-label="Status: <?= BorrowedToolStatus::getStatusDescription($tool['status']) ?>">
    <i class="<?= BorrowedToolStatus::getStatusIcon($tool['status']) ?>" aria-hidden="true"></i>
    <?= BorrowedToolStatus::getDisplayName($tool['status']) ?>
</span>
```

### 5. Dropdown Population
```php
<!-- ❌ DON'T: -->
<select name="status">
    <option value="Borrowed">Borrowed</option>
    <option value="Returned">Returned</option>
</select>

<!-- ✅ DO: -->
<select name="status" aria-label="Filter by status">
    <option value="">All Statuses</option>
    <?php foreach (BorrowedToolStatus::getStatusesForDropdown() as $value => $label): ?>
        <option value="<?= htmlspecialchars($value) ?>"
                <?= ($currentStatus == $value) ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
        </option>
    <?php endforeach; ?>
</select>
```

### 6. Array of Statuses
```php
// ❌ DON'T:
$activeStatuses = ['Borrowed', 'Released', 'Overdue'];

// ✅ DO:
$activeStatuses = [
    BorrowedToolStatus::BORROWED,
    BorrowedToolStatus::RELEASED,
    BorrowedToolStatus::OVERDUE
];

// ✅ BETTER (Using helper):
$activeStatuses = BorrowedToolStatus::getActiveBorrowingStatuses();
```

---

## HELPER METHODS

### Status Validation
```php
BorrowedToolStatus::isValidStatus($status)           // Check if status exists
BorrowedToolStatus::isActiveBorrowing($status)      // Check if currently borrowed
BorrowedToolStatus::isMVAWorkflow($status)          // Check if in MVA workflow
BorrowedToolStatus::isCompleted($status)            // Check if terminal status
```

### Status Display
```php
BorrowedToolStatus::getDisplayName($status)         // "Borrowed"
BorrowedToolStatus::getStatusDescription($status)   // "Currently borrowed by user"
BorrowedToolStatus::getStatusBadgeColor($status)    // "primary"
BorrowedToolStatus::getStatusIcon($status)          // "bi-box-arrow-right"
```

### Status Lists
```php
BorrowedToolStatus::getAllStatuses()                      // All 9 statuses
BorrowedToolStatus::getMVAStatuses()                      // Pending/Approval/Approved
BorrowedToolStatus::getActiveBorrowingStatuses()          // Borrowed/Released/etc.
BorrowedToolStatus::getCompletedStatuses()                // Returned/Canceled
```

### Dropdown Helpers
```php
BorrowedToolStatus::getStatusesForDropdown()              // All statuses
BorrowedToolStatus::getActiveBorrowingStatusesForDropdown() // Active only
BorrowedToolStatus::getMVAStatusesForDropdown()           // MVA workflow only
```

---

## ASSET STATUS HELPERS

### Asset Validation
```php
AssetStatus::isValidStatus($status)                 // Check if status exists
AssetStatus::isActive($status)                      // Check if usable
AssetStatus::needsMaintenance($status)              // Check if needs maintenance
AssetStatus::isTerminal($status)                    // Check if lifecycle ended
AssetStatus::canBeBorrowed($status)                 // Check if borrowable
AssetStatus::canBeTransferred($status)              // Check if transferable
AssetStatus::canBeRetired($status)                  // Check if can be retired
```

### Asset Display
```php
AssetStatus::getDisplayName($status)                // "Available"
AssetStatus::getStatusDescription($status)          // "Available for use"
AssetStatus::getStatusBadgeColor($status)           // "success"
AssetStatus::getStatusIcon($status)                 // "bi-check-circle-fill"
```

### Asset Lists
```php
AssetStatus::getAllStatuses()                       // All 9 asset statuses
AssetStatus::getActiveStatuses()                    // Available/Borrowed/In Use
AssetStatus::getMaintenanceStatuses()               // Under Maintenance/Damaged
AssetStatus::getTerminalStatuses()                  // Lost/Disposed/Retired
```

### Asset Dropdown Helpers
```php
AssetStatus::getStatusesForDropdown()               // All asset statuses
AssetStatus::getActiveStatusesForDropdown()         // Active only
```

---

## ACCESSIBILITY REQUIREMENTS (WCAG 2.1 AA)

### ✅ Required for All Status Displays:

1. **role="status"** - Screen reader live region
2. **aria-label** - Descriptive status (use `getStatusDescription()`)
3. **aria-hidden="true"** on decorative icons
4. **Icon + Text** - Don't rely on color alone (WCAG 1.4.1)
5. **Color Contrast** - Ensure ≥4.5:1 ratio (Bootstrap badges meet this)

### Example Template:
```php
<span class="badge badge-<?= BorrowedToolStatus::getStatusBadgeColor($status) ?>"
      role="status"
      aria-label="Status: <?= BorrowedToolStatus::getStatusDescription($status) ?>">
    <i class="<?= BorrowedToolStatus::getStatusIcon($status) ?>" aria-hidden="true"></i>
    <?= BorrowedToolStatus::getDisplayName($status) ?>
</span>
```

---

## SQL QUERY PATTERNS

### Single Status Filter:
```php
$sql = "SELECT * FROM borrowed_tools WHERE status = ?";
$stmt->execute([BorrowedToolStatus::BORROWED]);
```

### Multiple Status Filter (IN clause):
```php
$statuses = BorrowedToolStatus::getActiveBorrowingStatuses();
$placeholders = implode(',', array_fill(0, count($statuses), '?'));
$sql = "SELECT * FROM borrowed_tools WHERE status IN ($placeholders)";
$stmt->execute($statuses);
```

### CASE Statement (ORDER BY):
```php
$orderBy = sprintf('CASE status
    WHEN "%s" THEN 1
    WHEN "%s" THEN 2
    WHEN "%s" THEN 3
    ELSE 4
    END',
    BorrowedToolStatus::PENDING_VERIFICATION,
    BorrowedToolStatus::PENDING_APPROVAL,
    BorrowedToolStatus::APPROVED
);
$sql = "SELECT * FROM borrowed_tools ORDER BY $orderBy";
```

---

## MIGRATION CHECKLIST

When updating a file:

- [ ] Add `require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';`
- [ ] Replace all hardcoded status strings with constants
- [ ] Replace all hardcoded badge HTML with helper methods
- [ ] Replace all hardcoded dropdown options with helper methods
- [ ] Add `role="status"` and `aria-label` to all status badges
- [ ] Add `aria-hidden="true"` to all decorative icons
- [ ] Ensure all SQL queries are parameterized
- [ ] Test status filtering, sorting, and display
- [ ] Run accessibility audit (axe-core or similar)

---

## TESTING

### Unit Test Example:
```php
public function testStatusConstants() {
    // Verify constant values
    $this->assertEquals('Borrowed', BorrowedToolStatus::BORROWED);

    // Test validation methods
    $this->assertTrue(BorrowedToolStatus::isValidStatus('Borrowed'));
    $this->assertTrue(BorrowedToolStatus::isActiveBorrowing('Borrowed'));
    $this->assertFalse(BorrowedToolStatus::isCompleted('Borrowed'));

    // Test display methods
    $this->assertEquals('primary', BorrowedToolStatus::getStatusBadgeColor('Borrowed'));
    $this->assertEquals('Borrowed', BorrowedToolStatus::getDisplayName('Borrowed'));
}
```

---

## COMMON MISTAKES TO AVOID

### ❌ DON'T:
```php
// 1. Hardcoded status strings
if ($status == 'Borrowed') { }

// 2. Magic numbers in SQL
WHERE status = 'Borrowed'

// 3. Hardcoded badge colors
<span class="badge badge-primary">

// 4. Missing accessibility attributes
<span class="badge badge-primary">Borrowed</span>

// 5. Relying on color alone
<span class="badge badge-success"></span> <!-- No text or icon -->
```

### ✅ DO:
```php
// 1. Use constants
if ($status == BorrowedToolStatus::BORROWED) { }

// 2. Parameterized queries
WHERE status = ?
$stmt->execute([BorrowedToolStatus::BORROWED]);

// 3. Use helper methods
<span class="badge badge-<?= BorrowedToolStatus::getStatusBadgeColor($status) ?>">

// 4. Add ARIA attributes
<span class="badge" role="status" aria-label="Status: ...">

// 5. Icon + Text
<i class="bi-box-arrow-right" aria-hidden="true"></i> Borrowed
```

---

## SUPPORT

**Questions?** Refer to:
- Full implementation report: `/docs/STATUS_CONSTANT_IMPLEMENTATION_REPORT.md`
- Helper source code: `/helpers/BorrowedToolStatus.php`, `/helpers/AssetStatus.php`
- Example usage: `/models/BorrowedToolModel.php`, `/services/BorrowedToolWorkflowService.php`

**Need Help?** Contact the UI/UX Agent (God-Level) or Master Orchestrator.

---

**Last Updated:** 2025-01-27
**Version:** 1.0
**Maintained By:** UI/UX Agent (God-Level)
