# View Files Refactoring Guide

## Overview
This document shows how to refactor view files to use the new helper classes instead of hardcoded values.

## Files to Refactor

### 1. `/views/assets/partials/_filters.php`

#### Change 1: Status Dropdown (Lines 43-52)

**Before:**
```php
<select class="form-select form-select-sm" id="status" name="status">
    <option value="">All Statuses</option>
    <option value="available" <?= ($_GET['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
    <option value="in_use" <?= ($_GET['status'] ?? '') === 'in_use' ? 'selected' : '' ?>>In Use</option>
    <option value="borrowed" <?= ($_GET['status'] ?? '') === 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
    <option value="in_transit" <?= ($_GET['status'] ?? '') === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
    <option value="under_maintenance" <?= ($_GET['status'] ?? '') === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
    <option value="retired" <?= ($_GET['status'] ?? '') === 'retired' ? 'selected' : '' ?>>Retired</option>
    <option value="disposed" <?= ($_GET['status'] ?? '') === 'disposed' ? 'selected' : '' ?>>Disposed</option>
</select>
```

**After:**
```php
<select class="form-select form-select-sm" id="status" name="status">
    <option value="">All Statuses</option>
    <?php foreach (AssetStatus::getStatusesForDropdown() as $value => $label): ?>
        <option value="<?= $value ?>" <?= ($_GET['status'] ?? '') === $value ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
        </option>
    <?php endforeach; ?>
</select>
```

#### Change 2: Role-Based Filter Visibility (Line 68)

**Before:**
```php
<?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
    <label for="project_id" class="form-label">Project</label>
    <select class="form-select form-select-sm" id="project_id" name="project_id">
        <!-- options -->
    </select>
</div>
<?php endif; ?>
```

**After:**
```php
<?php if (AssetPermission::can(AssetPermission::VIEW_ALL_ASSETS)): ?>
<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
    <label for="project_id" class="form-label">Project</label>
    <select class="form-select form-select-sm" id="project_id" name="project_id">
        <!-- options -->
    </select>
</div>
<?php endif; ?>
```

#### Change 3: Workflow Status Dropdown (Lines 113-117)

**Before:**
```php
<select class="form-select form-select-sm" id="workflow_status" name="workflow_status">
    <option value="">All Workflow Statuses</option>
    <option value="draft" <?= ($_GET['workflow_status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
    <option value="pending_verification" <?= ($_GET['workflow_status'] ?? '') === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
    <option value="pending_authorization" <?= ($_GET['workflow_status'] ?? '') === 'pending_authorization' ? 'selected' : '' ?>>Pending Authorization</option>
    <option value="approved" <?= ($_GET['workflow_status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
</select>
```

**After:**
```php
<select class="form-select form-select-sm" id="workflow_status" name="workflow_status">
    <option value="">All Workflow Statuses</option>
    <?php foreach (AssetWorkflowStatus::getAllStatuses() as $status): ?>
        <option value="<?= $status ?>" <?= ($_GET['workflow_status'] ?? '') === $status ? 'selected' : '' ?>>
            <?= htmlspecialchars(AssetWorkflowStatus::getDisplayName($status)) ?>
        </option>
    <?php endforeach; ?>
</select>
```

### 2. `/views/assets/partials/_action_buttons.php`

#### Change: Role-Based Button Visibility

**Before:**
```php
<?php if (in_array($userRole, ['System Admin', 'Asset Director'])): ?>
    <a href="?route=assets&action=create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Create Asset
    </a>
<?php endif; ?>

<?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
    <a href="?route=assets&action=export" class="btn btn-success">
        <i class="bi bi-download me-1"></i>Export
    </a>
<?php endif; ?>
```

**After:**
```php
<?php if (AssetPermission::canEditAssets()): ?>
    <a href="?route=assets&action=create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Create Asset
    </a>
<?php endif; ?>

<?php if (AssetPermission::canViewReports()): ?>
    <a href="?route=assets&action=export" class="btn btn-success">
        <i class="bi bi-download me-1"></i>Export
    </a>
<?php endif; ?>
```

### 3. `/views/assets/partials/_asset_list.php`

#### Change 1: Status Badge Display (Lines 92-102)

**Before:**
```php
<?php
$statusClass = 'secondary';
switch ($asset['status']) {
    case 'available': $statusClass = 'success'; break;
    case 'in_use':
    case 'borrowed': $statusClass = 'primary'; break;
    case 'under_maintenance': $statusClass = 'warning'; break;
    case 'retired':
    case 'disposed': $statusClass = 'danger'; break;
}
?>
<span class="badge badge-<?= $statusClass ?>">
    <?= ucfirst(str_replace('_', ' ', $asset['status'])) ?>
</span>
```

**After:**
```php
<?php
$badgeColor = AssetStatus::getStatusBadgeColor($asset['status']);
$displayName = AssetStatus::getDisplayName($asset['status']);
$icon = AssetStatus::getStatusIcon($asset['status']);
?>
<span class="badge badge-<?= $badgeColor ?>">
    <i class="<?= $icon ?> me-1"></i>
    <?= htmlspecialchars($displayName) ?>
</span>
```

#### Change 2: Workflow Status Badge (Lines 364-378)

**Before:**
```php
<?php
$workflowClass = 'secondary';
switch ($asset['workflow_status']) {
    case 'draft': $workflowClass = 'secondary'; break;
    case 'pending_verification':
    case 'pending_authorization': $workflowClass = 'warning'; break;
    case 'approved': $workflowClass = 'success'; break;
    case 'rejected': $workflowClass = 'danger'; break;
}
?>
<span class="badge badge-<?= $workflowClass ?>">
    <?= ucfirst(str_replace('_', ' ', $asset['workflow_status'])) ?>
</span>
```

**After:**
```php
<?php
$badgeColor = AssetWorkflowStatus::getStatusBadgeColor($asset['workflow_status']);
$displayName = AssetWorkflowStatus::getDisplayName($asset['workflow_status']);
$icon = AssetWorkflowStatus::getStatusIcon($asset['workflow_status']);
?>
<span class="badge badge-<?= $badgeColor ?>">
    <i class="<?= $icon ?> me-1"></i>
    <?= htmlspecialchars($displayName) ?>
</span>
```

#### Change 3: Action Buttons Based on Permissions (Lines 437-450)

**Before:**
```php
<?php if (in_array($userRole, ['System Admin', 'Asset Director'])): ?>
    <a href="?route=assets&action=edit&id=<?= $asset['id'] ?>"
       class="btn btn-sm btn-primary">
        <i class="bi bi-pencil"></i>
    </a>
<?php endif; ?>

<?php if (in_array($userRole, ['System Admin'])): ?>
    <button class="btn btn-sm btn-danger"
            onclick="deleteAsset(<?= $asset['id'] ?>)">
        <i class="bi bi-trash"></i>
    </button>
<?php endif; ?>
```

**After:**
```php
<?php if (AssetPermission::canEditAssets()): ?>
    <a href="?route=assets&action=edit&id=<?= $asset['id'] ?>"
       class="btn btn-sm btn-primary"
       title="Edit Asset">
        <i class="bi bi-pencil"></i>
    </a>
<?php endif; ?>

<?php if (AssetPermission::canDeleteAssets()): ?>
    <button class="btn btn-sm btn-danger"
            onclick="deleteAsset(<?= $asset['id'] ?>)"
            title="Delete Asset">
        <i class="bi bi-trash"></i>
    </button>
<?php endif; ?>
```

### 4. `/views/assets/partials/_statistics_cards.php`

#### Change: Role-Based Statistics Visibility

**Before:**
```php
<?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total Value</h6>
                <h3><?= number_format($assetStats['total_value'] ?? 0, 2) ?></h3>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (in_array($userRole, ['System Admin', 'Asset Director', 'Warehouseman'])): ?>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Available Assets</h6>
                <h3><?= $assetStats['available_count'] ?? 0 ?></h3>
            </div>
        </div>
    </div>
<?php endif; ?>
```

**After:**
```php
<?php if (AssetPermission::canViewFinancialData()): ?>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total Value</h6>
                <h3><?= number_format($assetStats['total_value'] ?? 0, 2) ?></h3>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (AssetPermission::canViewAssets()): ?>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Available Assets</h6>
                <h3><?= $assetStats['available_count'] ?? 0 ?></h3>
            </div>
        </div>
    </div>
<?php endif; ?>
```

### 5. `/views/assets/view.php` - Asset Details View

#### Change: Status Display with Helper

**Before:**
```php
<div class="row mb-3">
    <div class="col-md-3">
        <strong>Status:</strong>
    </div>
    <div class="col-md-9">
        <?php
        $statusBadge = 'secondary';
        if ($asset['status'] === 'available') $statusBadge = 'success';
        elseif ($asset['status'] === 'in_use') $statusBadge = 'primary';
        elseif ($asset['status'] === 'under_maintenance') $statusBadge = 'warning';
        ?>
        <span class="badge badge-<?= $statusBadge ?>">
            <?= ucfirst(str_replace('_', ' ', $asset['status'])) ?>
        </span>
    </div>
</div>
```

**After:**
```php
<div class="row mb-3">
    <div class="col-md-3">
        <strong>Status:</strong>
    </div>
    <div class="col-md-9">
        <span class="badge badge-<?= AssetStatus::getStatusBadgeColor($asset['status']) ?>">
            <i class="<?= AssetStatus::getStatusIcon($asset['status']) ?> me-1"></i>
            <?= AssetStatus::getDisplayName($asset['status']) ?>
        </span>
        <small class="text-muted d-block mt-1">
            <?= AssetStatus::getStatusDescription($asset['status']) ?>
        </small>
    </div>
</div>
```

### 6. `/views/assets/create.php` and `/views/assets/edit.php`

#### Change: Status Dropdown in Forms

**Before:**
```php
<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select class="form-control" id="status" name="status" required>
        <option value="">Select Status</option>
        <option value="available">Available</option>
        <option value="in_use">In Use</option>
        <option value="under_maintenance">Under Maintenance</option>
        <option value="retired">Retired</option>
    </select>
</div>
```

**After:**
```php
<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select class="form-control" id="status" name="status" required>
        <option value="">Select Status</option>
        <?php foreach (AssetStatus::getStatusesForDropdown() as $value => $label): ?>
            <option value="<?= $value ?>"
                    <?= (isset($asset) && $asset['status'] === $value) ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <small class="form-text text-muted">
        Current status determines asset availability and operations
    </small>
</div>
```

## Complete Example: Refactored Filter File

Here's a complete example showing the refactored `_filters.php`:

```php
<?php
/**
 * Filters Partial - REFACTORED VERSION
 * Uses helper classes instead of hardcoded values
 */
?>

<!-- Status Filter -->
<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
    <label for="status" class="form-label">Status</label>
    <select class="form-select form-select-sm" id="status" name="status">
        <option value="">All Statuses</option>
        <?php foreach (AssetStatus::getStatusesForDropdown() as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($_GET['status'] ?? '') === $value ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Project Filter (only for users with permission) -->
<?php if (AssetPermission::can(AssetPermission::VIEW_ALL_ASSETS)): ?>
<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
    <label for="project_id" class="form-label">Project</label>
    <select class="form-select form-select-sm" id="project_id" name="project_id">
        <option value="">All Projects</option>
        <?php if (isset($projects) && is_array($projects)): ?>
            <?php foreach ($projects as $project): ?>
                <option value="<?= $project['id'] ?>"
                        <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($project['name'] ?? 'Unknown') ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>
<?php endif; ?>

<!-- Workflow Status Filter (only for users who can verify/authorize) -->
<?php if (AssetPermission::canAny([AssetPermission::VERIFY_ASSET, AssetPermission::AUTHORIZE_ASSET])): ?>
<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
    <label for="workflow_status" class="form-label">Workflow Status</label>
    <select class="form-select form-select-sm" id="workflow_status" name="workflow_status">
        <option value="">All Workflow Statuses</option>
        <?php foreach (AssetWorkflowStatus::getAllStatuses() as $status): ?>
            <option value="<?= $status ?>" <?= ($_GET['workflow_status'] ?? '') === $status ? 'selected' : '' ?>>
                <?= htmlspecialchars(AssetWorkflowStatus::getDisplayName($status)) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>
```

## Summary

### Total View Files to Refactor:
1. `/views/assets/partials/_filters.php` - 3 major changes
2. `/views/assets/partials/_action_buttons.php` - All role checks
3. `/views/assets/partials/_asset_list.php` - Status badges and action buttons
4. `/views/assets/partials/_statistics_cards.php` - Visibility checks
5. `/views/assets/view.php` - Status displays
6. `/views/assets/create.php` - Form dropdowns
7. `/views/assets/edit.php` - Form dropdowns
8. `/views/assets/verification_dashboard.php` - Permission checks
9. `/views/assets/authorization_dashboard.php` - Permission checks

### Benefits:
- **Consistency**: All status displays use the same helper methods
- **Maintainability**: Change status labels in one place
- **Flexibility**: Easy to add icons, colors, or additional status metadata
- **Type Safety**: No typos in status strings
- **Security**: Centralized permission checks
- **UX**: Consistent icons and colors across all views

### Testing Checklist:
- [ ] All status dropdowns show correct options
- [ ] All status badges display with correct colors
- [ ] All permission-based elements show/hide correctly
- [ ] All workflow status displays work correctly
- [ ] No hardcoded strings remain
- [ ] Forms submit with correct status values
- [ ] Filters work correctly with helper constants
