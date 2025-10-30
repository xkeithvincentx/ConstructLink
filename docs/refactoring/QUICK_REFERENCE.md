# Asset Refactoring Quick Reference

## Quick Start

### Include Helper Files (if not using autoloader)
```php
require_once __DIR__ . '/../helpers/AssetStatus.php';
require_once __DIR__ . '/../helpers/AssetWorkflowStatus.php';
require_once __DIR__ . '/../helpers/AssetPermission.php';
```

## Common Refactoring Patterns

### 1. Replace Role Checks

❌ **OLD:**
```php
$userRole = $currentUser['role_name'] ?? '';
if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
    http_response_code(403);
    include APP_ROOT . '/views/errors/403.php';
    return;
}
```

✅ **NEW:**
```php
AssetPermission::requirePermission(AssetPermission::EDIT_ASSET);
```

### 2. Replace Status Strings

❌ **OLD:**
```php
if ($asset['status'] === 'available') {
    echo '<span class="badge badge-success">Available</span>';
}
```

✅ **NEW:**
```php
if ($asset['status'] === AssetStatus::AVAILABLE) {
    $badge = AssetStatus::getBadgeClass($asset['status']);
    $name = AssetStatus::getDisplayName($asset['status']);
    echo "<span class=\"badge {$badge}\">{$name}</span>";
}
```

### 3. Replace Workflow Statuses

❌ **OLD:**
```php
if ($asset['workflow_status'] !== 'pending_verification') {
    return false;
}
```

✅ **NEW:**
```php
if ($asset['workflow_status'] !== AssetWorkflowStatus::PENDING_VERIFICATION) {
    return false;
}
```

### 4. Replace Status Dropdowns

❌ **OLD:**
```php
<select name="status">
    <option value="available">Available</option>
    <option value="in_use">In Use</option>
    <option value="borrowed">Borrowed</option>
</select>
```

✅ **NEW:**
```php
<select name="status">
    <?php foreach (AssetStatus::getStatusesForDropdown() as $value => $label): ?>
        <option value="<?= $value ?>"><?= $label ?></option>
    <?php endforeach; ?>
</select>
```

## Permission Constants Cheat Sheet

```php
// Viewing Permissions
AssetPermission::VIEW_ALL_ASSETS
AssetPermission::VIEW_PROJECT_ASSETS
AssetPermission::VIEW_FINANCIAL_DATA

// Management Permissions
AssetPermission::EDIT_ASSET          // Create and edit
AssetPermission::DELETE_ASSET
AssetPermission::FLAG_IDLE_ASSETS

// Operation Permissions
AssetPermission::RELEASE_ASSETS
AssetPermission::RECEIVE_ASSETS
AssetPermission::APPROVE_TRANSFERS
AssetPermission::INITIATE_TRANSFERS

// Workflow Permissions
AssetPermission::VERIFY_ASSET        // Finance Director
AssetPermission::AUTHORIZE_ASSET     // Asset Director

// Withdrawal Permissions
AssetPermission::MANAGE_WITHDRAWALS
AssetPermission::REQUEST_WITHDRAWALS

// Other Permissions
AssetPermission::MANAGE_MAINTENANCE
AssetPermission::MANAGE_BORROWED_TOOLS
AssetPermission::MANAGE_INCIDENTS
AssetPermission::APPROVE_DISPOSAL
AssetPermission::VIEW_REPORTS
```

## Asset Status Constants

```php
AssetStatus::AVAILABLE              // 'available'
AssetStatus::BORROWED               // 'borrowed'
AssetStatus::IN_USE                 // 'in_use'
AssetStatus::UNDER_MAINTENANCE      // 'under_maintenance'
AssetStatus::DAMAGED                // 'damaged'
AssetStatus::LOST                   // 'lost'
AssetStatus::DISPOSED               // 'disposed'
AssetStatus::RETIRED                // 'retired'
```

## Workflow Status Constants

```php
AssetWorkflowStatus::DRAFT                      // 'draft'
AssetWorkflowStatus::PENDING_VERIFICATION       // 'pending_verification'
AssetWorkflowStatus::PENDING_AUTHORIZATION      // 'pending_authorization'
AssetWorkflowStatus::APPROVED                   // 'approved'
AssetWorkflowStatus::REJECTED                   // 'rejected'
```

## Permission Check Methods

```php
// Single permission
if (AssetPermission::can(AssetPermission::EDIT_ASSET)) {
    // allowed
}

// Any permission
if (AssetPermission::canAny([
    AssetPermission::EDIT_ASSET,
    AssetPermission::DELETE_ASSET
])) {
    // allowed if user has either permission
}

// All permissions
if (AssetPermission::canAll([
    AssetPermission::VIEW_ALL_ASSETS,
    AssetPermission::EDIT_ASSET
])) {
    // allowed only if user has both permissions
}

// Require permission (throws 403 if not allowed)
AssetPermission::requirePermission(AssetPermission::EDIT_ASSET);

// Convenience methods
AssetPermission::canViewAssets()
AssetPermission::canEditAssets()
AssetPermission::canDeleteAssets()
AssetPermission::canVerifyAssets()
AssetPermission::canAuthorizeAssets()
AssetPermission::canManageWithdrawals()
AssetPermission::canRequestWithdrawals()
AssetPermission::canViewReports()
AssetPermission::canViewFinancialData()
```

## Status Helper Methods

```php
// Display methods
AssetStatus::getDisplayName('available')           // 'Available'
AssetStatus::getStatusDescription('available')     // 'Available for use'
AssetStatus::getStatusBadgeColor('available')      // 'success'
AssetStatus::getBadgeClass('available')            // 'badge-success'
AssetStatus::getTextColor('available')             // 'text-success'
AssetStatus::getStatusIcon('available')            // 'bi-check-circle-fill'

// Validation methods
AssetStatus::isValidStatus('available')            // true
AssetStatus::isActive('available')                 // true
AssetStatus::needsMaintenance('under_maintenance') // true
AssetStatus::isTerminal('disposed')                // true

// Business logic methods
AssetStatus::canBeBorrowed('available')            // true
AssetStatus::canBeTransferred('available')         // true
AssetStatus::canBeRetired('available')             // true

// Dropdown methods
AssetStatus::getStatusesForDropdown()              // ['available' => 'Available', ...]
AssetStatus::getActiveStatusesForDropdown()        // Active statuses only
```

## Workflow Helper Methods

```php
// Display methods
AssetWorkflowStatus::getDisplayName('pending_verification')     // 'Pending Verification'
AssetWorkflowStatus::getStatusDescription('pending_verification') // 'Pending Verification'
AssetWorkflowStatus::getStatusBadgeColor('pending_verification') // 'warning'
AssetWorkflowStatus::getStatusIcon('pending_verification')       // 'bi-hourglass-split'
AssetWorkflowStatus::getActionLabel('pending_verification')      // 'Verify Asset'

// Validation methods
AssetWorkflowStatus::isValidStatus('draft')                     // true
AssetWorkflowStatus::isPending('pending_verification')          // true
AssetWorkflowStatus::isCompleted('approved')                    // true
AssetWorkflowStatus::allowsEditing('draft')                     // true

// Workflow methods
AssetWorkflowStatus::getNextStatus('draft')                     // 'pending_verification'
AssetWorkflowStatus::isValidTransition('draft', 'pending_verification') // true
AssetWorkflowStatus::getRequiredPermission('draft')             // 'asset.submit'
AssetWorkflowStatus::getTransitionRules()                       // Complete transition rules
```

## Migration Commands

### Apply Migration
```bash
mysql -u root -p constructlink_db < database/migrations/001_update_asset_permissions.sql
```

### Verify Migration
```bash
mysql -u root -p constructlink_db -e "SELECT name, JSON_LENGTH(permissions) FROM roles;"
```

### Rollback Migration
```bash
mysql -u root -p constructlink_db < database/migrations/002_rollback_asset_permissions.sql
```

## Testing Quick Commands

### Test Permission Check
```php
// In controller or view
$currentUser = Auth::getInstance()->getCurrentUser();
var_dump($currentUser['permissions']);

// Test specific permission
var_dump(AssetPermission::can(AssetPermission::EDIT_ASSET));
```

### Test Status Display
```php
// Test status badge
$status = 'available';
echo AssetStatus::getDisplayName($status) . ' - ' .
     AssetStatus::getStatusBadgeColor($status);
```

### Test Workflow
```php
// Test workflow transition
$from = AssetWorkflowStatus::DRAFT;
$to = AssetWorkflowStatus::PENDING_VERIFICATION;
var_dump(AssetWorkflowStatus::isValidTransition($from, $to)); // true
```

## Common View Patterns

### Status Badge in Table
```php
<td>
    <span class="badge badge-<?= AssetStatus::getStatusBadgeColor($asset['status']) ?>">
        <i class="<?= AssetStatus::getStatusIcon($asset['status']) ?>"></i>
        <?= AssetStatus::getDisplayName($asset['status']) ?>
    </span>
</td>
```

### Workflow Badge
```php
<span class="badge badge-<?= AssetWorkflowStatus::getStatusBadgeColor($asset['workflow_status']) ?>">
    <i class="<?= AssetWorkflowStatus::getStatusIcon($asset['workflow_status']) ?>"></i>
    <?= AssetWorkflowStatus::getDisplayName($asset['workflow_status']) ?>
</span>
```

### Conditional Button Display
```php
<?php if (AssetPermission::canEditAssets()): ?>
    <a href="?route=assets&action=edit&id=<?= $asset['id'] ?>" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Edit
    </a>
<?php endif; ?>
```

### Status Dropdown
```php
<select name="status" class="form-control">
    <option value="">Select Status</option>
    <?php foreach (AssetStatus::getStatusesForDropdown() as $value => $label): ?>
        <option value="<?= $value ?>" <?= ($asset['status'] ?? '') === $value ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
        </option>
    <?php endforeach; ?>
</select>
```

## Role Permission Matrix (Quick Reference)

| Feature | System Admin | Finance Dir | Asset Dir | Procurement | Warehouseman | Project Mgr | Site Clerk |
|---------|--------------|-------------|-----------|-------------|--------------|-------------|------------|
| View Assets | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Create/Edit Assets | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Delete Assets | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Verify Assets | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Authorize Assets | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| View Financial | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage Withdrawals | ✅ | ❌ | ✅ | ❌ | ✅ | ✅ | ❌ |
| Request Withdrawals | ✅ | ❌ | ✅ | ❌ | ✅ | ✅ | ✅ |

## Troubleshooting

### Issue: 403 Forbidden Error
```php
// Check user permissions
$user = Auth::getInstance()->getCurrentUser();
var_dump($user['role_name']);
var_dump($user['permissions']);

// Check if permission exists
var_dump(in_array('view_all_assets', $user['permissions']));
```

### Issue: Status Not Displaying
```php
// Verify helper file is loaded
var_dump(class_exists('AssetStatus')); // should be true

// Test status value
var_dump(AssetStatus::AVAILABLE === 'available'); // should be true
```

### Issue: Workflow Transition Fails
```php
// Check current status
var_dump($asset['workflow_status']);

// Check if transition is valid
$from = $asset['workflow_status'];
$to = AssetWorkflowStatus::PENDING_VERIFICATION;
var_dump(AssetWorkflowStatus::isValidTransition($from, $to));
```

## Files Modified

### Created
- `/helpers/AssetStatus.php` (enhanced)
- `/helpers/AssetWorkflowStatus.php` (new)
- `/helpers/AssetPermission.php` (new)
- `/database/migrations/001_update_asset_permissions.sql`
- `/database/migrations/002_rollback_asset_permissions.sql`

### To Modify
- `/controllers/AssetController.php` (17 methods)
- `/controllers/AssetTagController.php`
- `/views/assets/partials/_filters.php`
- `/views/assets/partials/_action_buttons.php`
- `/views/assets/partials/_asset_list.php`
- `/views/assets/partials/_statistics_cards.php`

## Documentation Files

- `ASSET_REFACTOR_IMPLEMENTATION_GUIDE.md` - Complete implementation guide
- `ASSET_CONTROLLER_REFACTOR_CHANGES.md` - Controller refactoring details
- `VIEW_FILES_REFACTOR_GUIDE.md` - View file refactoring guide
- `TESTING_PLAN.md` - Comprehensive testing plan
- `ASSET_REFACTOR_COMPLETE_SUMMARY.md` - Executive summary
- `QUICK_REFERENCE.md` - This file

---

**Keep This File**: Reference this when refactoring controllers and views!
