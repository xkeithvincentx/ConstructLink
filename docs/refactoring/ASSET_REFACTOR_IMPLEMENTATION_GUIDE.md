# Asset Module Refactoring Implementation Guide

## Overview

This document outlines the comprehensive refactoring of the Assets module to eliminate all hardcoded values and replace them with a database-driven permission system.

## Changes Implemented

### 1. Helper Classes Created

#### `/helpers/AssetStatus.php` (Enhanced)
- **Status Constants**: All asset statuses as constants
- **New Methods Added**:
  - `getDisplayName($status)` - Short status display name
  - `getStatusIcon($status)` - Bootstrap icon class
  - `getBadgeClass($status)` - Badge CSS class
  - `getTextColor($status)` - Text color CSS class
  - `canBeBorrowed($status)` - Check if borrowable
  - `canBeTransferred($status)` - Check if transferable
  - `canBeRetired($status)` - Check if can be retired
  - `getStatusesForDropdown()` - Status dropdown options
  - `getActiveStatusesForDropdown()` - Active status dropdown

#### `/helpers/AssetWorkflowStatus.php` (New)
- **Workflow Constants**: MVA workflow statuses
  - `DRAFT` = 'draft'
  - `PENDING_VERIFICATION` = 'pending_verification'
  - `PENDING_AUTHORIZATION` = 'pending_authorization'
  - `APPROVED` = 'approved'
  - `REJECTED` = 'rejected'

- **Methods**:
  - `getAllStatuses()` - All workflow statuses
  - `getPendingStatuses()` - Statuses requiring action
  - `getCompletedStatuses()` - Terminal statuses
  - `isValidStatus($status)` - Validate status
  - `isPending($status)` - Check if pending
  - `isCompleted($status)` - Check if completed
  - `allowsEditing($status)` - Check if editable
  - `getStatusBadgeColor($status)` - Badge color
  - `getStatusDescription($status)` - Human-readable description
  - `getDisplayName($status)` - Display name
  - `getStatusIcon($status)` - Bootstrap icon
  - `getNextStatus($currentStatus)` - Next workflow state
  - `getActionLabel($status)` - Action button label
  - `getTransitionRules()` - Workflow transition rules
  - `isValidTransition($from, $to)` - Validate transition
  - `getRequiredPermission($status)` - Required permission for action

#### `/helpers/AssetPermission.php` (New)
- **Permission Constants**: All asset permissions
- **Permission Check Methods**:
  - `can($permission)` - Check single permission
  - `canAny(array $permissions)` - Check any permission
  - `canAll(array $permissions)` - Check all permissions
  - `requirePermission($permission)` - Throw 403 if no permission

- **Convenience Methods**:
  - `canViewAssets()` - Can view assets
  - `canEditAssets()` - Can create/edit assets
  - `canDeleteAssets()` - Can delete assets
  - `canVerifyAssets()` - Can verify (Finance Director)
  - `canAuthorizeAssets()` - Can authorize (Asset Director)
  - `canManageWithdrawals()` - Can manage withdrawals
  - `canRequestWithdrawals()` - Can request withdrawals
  - `canReleaseAssets()` - Can release assets
  - `canReceiveAssets()` - Can receive assets
  - `canApproveTransfers()` - Can approve transfers
  - `canInitiateTransfers()` - Can initiate transfers
  - `canManageMaintenance()` - Can manage maintenance
  - `canManageBorrowedTools()` - Can manage borrowed tools
  - `canManageIncidents()` - Can manage incidents
  - `canApproveDisposal()` - Can approve disposal
  - `canViewReports()` - Can view reports
  - `canViewFinancialData()` - Can view financial data

- **Utility Methods**:
  - `getPermissionName($permission)` - Get display name
  - `getAllPermissions()` - All permissions
  - `getGroupedPermissions()` - Grouped by category
  - `getRolePermissions($roleName)` - Legacy role mapping

### 2. Database Migrations

#### `/database/migrations/001_update_asset_permissions.sql`
- Updates all roles with comprehensive permissions
- Ensures all roles have proper permission arrays
- Transaction-safe with rollback support

#### `/database/migrations/002_rollback_asset_permissions.sql`
- Rollback script to restore original permissions
- Safety mechanism if issues occur

### 3. Code Refactoring Patterns

#### Before (Hardcoded Roles):
```php
// OLD WAY - DON'T DO THIS
$currentUser = $this->auth->getCurrentUser();
$userRole = $currentUser['role_name'] ?? '';

if (!in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
    http_response_code(403);
    include APP_ROOT . '/views/errors/403.php';
    return;
}
```

#### After (Permission-Based):
```php
// NEW WAY - USE THIS
AssetPermission::requirePermission(AssetPermission::VIEW_ALL_ASSETS);
// Or for multiple permissions:
if (!AssetPermission::canViewAssets()) {
    http_response_code(403);
    include APP_ROOT . '/views/errors/403.php';
    return;
}
```

#### Before (Hardcoded Status Strings):
```php
// OLD WAY - DON'T DO THIS
if ($asset['status'] === 'available') {
    echo '<span class="badge badge-success">Available</span>';
}

if ($workflow_status === 'pending_verification') {
    // do something
}
```

#### After (Helper Constants):
```php
// NEW WAY - USE THIS
if ($asset['status'] === AssetStatus::AVAILABLE) {
    $badgeClass = AssetStatus::getBadgeClass($asset['status']);
    $displayName = AssetStatus::getDisplayName($asset['status']);
    echo "<span class=\"badge {$badgeClass}\">{$displayName}</span>";
}

if ($workflow_status === AssetWorkflowStatus::PENDING_VERIFICATION) {
    // do something
}
```

### 4. Files Requiring Refactoring

#### Controllers (Priority: HIGH)
- [x] `/controllers/AssetController.php` (2,267 lines, 142+ role checks)
  - Lines 61, 232-234, 504, 545, 604, 652, 709, 748, 787, 829, 942, 980, 1018-1880
  - Workflow status strings: Lines 1310, 1386, 1733, 1913, 1982

- [ ] `/controllers/AssetTagController.php` (1,212 lines, multiple role checks)
  - Line 40 and throughout

#### View Files (Priority: HIGH)
- [ ] `/views/assets/partials/_action_buttons.php`
  - Replace role checks with permission checks

- [ ] `/views/assets/partials/_filters.php`
  - Lines 45-51, 113-117, 168-174 (status dropdowns)
  - Use `AssetStatus::getStatusesForDropdown()`
  - Use `AssetWorkflowStatus::getAllStatuses()`

- [ ] `/views/assets/partials/_statistics_cards.php`
  - Replace role-based visibility with permission checks

- [ ] `/views/assets/partials/_asset_list.php`
  - Lines 88-103, 364-378, 437-450 (status badges)
  - Use `AssetStatus::getBadgeClass()` and `AssetStatus::getDisplayName()`

#### Other Related Controllers
- [ ] `/controllers/TransferController.php`
- [ ] `/controllers/WithdrawalController.php`
- [ ] `/controllers/MaintenanceController.php`
- [ ] `/controllers/IncidentController.php`
- [ ] `/controllers/BorrowedToolController.php`

### 5. Permission Mapping

#### System Admin
- All permissions (full access)

#### Finance Director
- `view_all_assets`, `view_project_assets`
- `approve_disposal`
- `view_reports`, `view_financial_data`
- `approve_high_value_transfers`
- `approve_procurement`
- **MVA Role**: Verification (can verify assets)

#### Asset Director
- `view_all_assets`, `view_project_assets`
- `edit_assets` (create/edit)
- `approve_transfers`, `initiate_transfers`
- `view_reports`, `view_project_reports`
- `manage_maintenance`, `manage_incidents`
- `flag_idle_assets`
- `manage_withdrawals`, `release_assets`, `receive_assets`
- **MVA Role**: Authorization (can authorize assets)

#### Procurement Officer
- `view_all_assets`, `view_project_assets`
- `manage_procurement`, `receive_assets`
- `manage_vendors`, `manage_makers`

#### Warehouseman
- `view_project_assets`, `view_all_assets`
- `release_assets`, `receive_assets`
- `manage_withdrawals`, `manage_borrowed_tools`
- `request_withdrawals`

#### Project Manager
- `view_project_assets`
- `request_withdrawals`, `manage_withdrawals`
- `approve_site_actions`, `initiate_transfers`
- `manage_incidents`
- `view_project_reports`
- `receive_assets`

#### Site Inventory Clerk
- `view_project_assets`
- `request_withdrawals`
- `scan_qr_codes`, `log_borrower_info`
- `manage_incidents`, `manage_borrowed_tools`

### 6. Workflow Status Transitions

```
DRAFT
  ↓ (submit) - requires: edit_assets
PENDING_VERIFICATION
  ↓ (verify) - requires: view_financial_data (Finance Director)
  → (reject) - back to REJECTED
PENDING_AUTHORIZATION
  ↓ (authorize) - requires: approve_transfers (Asset Director)
  → (reject) - back to REJECTED
APPROVED (terminal state)

REJECTED
  ↓ (resubmit) - back to PENDING_VERIFICATION
```

### 7. Testing Checklist

#### Unit Tests
- [ ] Test all AssetStatus helper methods
- [ ] Test all AssetWorkflowStatus helper methods
- [ ] Test all AssetPermission helper methods

#### Integration Tests
- [ ] Test permission checks in AssetController
- [ ] Test workflow transitions
- [ ] Test role-based dashboard views
- [ ] Test asset creation with different roles
- [ ] Test asset editing with different roles
- [ ] Test asset deletion with different roles
- [ ] Test verification workflow
- [ ] Test authorization workflow

#### Manual Testing by Role
- [ ] System Admin - Full access
- [ ] Finance Director - Verification workflow
- [ ] Asset Director - Authorization workflow
- [ ] Procurement Officer - Limited access
- [ ] Warehouseman - Warehouse operations
- [ ] Project Manager - Project-level access
- [ ] Site Inventory Clerk - Site-level access

### 8. Rollback Plan

If issues occur:

1. **Database Rollback**:
   ```sql
   mysql -u username -p database_name < /path/to/002_rollback_asset_permissions.sql
   ```

2. **Code Rollback**:
   - Revert to previous commit
   - Or manually restore backed-up files

3. **Testing After Rollback**:
   - Verify all role checks work
   - Test critical workflows
   - Verify no permission errors

### 9. Deployment Steps

1. **Backup Database**:
   ```bash
   mysqldump -u username -p database_name > backup_before_refactor.sql
   ```

2. **Run Migration**:
   ```bash
   mysql -u username -p database_name < /path/to/001_update_asset_permissions.sql
   ```

3. **Deploy Helper Files**:
   - Upload `/helpers/AssetStatus.php`
   - Upload `/helpers/AssetWorkflowStatus.php`
   - Upload `/helpers/AssetPermission.php`

4. **Deploy Refactored Controllers**:
   - Upload refactored `AssetController.php`
   - Upload refactored `AssetTagController.php`

5. **Deploy Refactored Views**:
   - Upload all refactored view files

6. **Test**:
   - Test with each role
   - Verify no 403 errors for legitimate access
   - Verify proper 403 errors for unauthorized access

### 10. Benefits of Refactoring

1. **Maintainability**: Single source of truth for permissions
2. **Flexibility**: Easy to add/modify roles and permissions
3. **Security**: Centralized permission checking
4. **Type Safety**: Constants prevent typos
5. **IDE Support**: Autocomplete for constants
6. **Testability**: Easy to mock and test permissions
7. **Documentation**: Self-documenting code
8. **Scalability**: Easy to extend permission system

### 11. Migration Timeline

- **Phase 1**: Helper classes and database migration (COMPLETED)
- **Phase 2**: AssetController refactoring (IN PROGRESS)
- **Phase 3**: AssetTagController refactoring (PENDING)
- **Phase 4**: View files refactoring (PENDING)
- **Phase 5**: Related controllers refactoring (PENDING)
- **Phase 6**: Testing and validation (PENDING)
- **Phase 7**: Documentation and deployment (PENDING)

## Summary

This refactoring eliminates 142+ hardcoded role checks and 65+ hardcoded status strings, replacing them with a maintainable, database-driven permission system with type-safe constants.
