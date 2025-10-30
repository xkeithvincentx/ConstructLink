# Asset Module Refactoring - Testing Plan

## Overview
This document outlines the comprehensive testing strategy for the asset module refactoring from hardcoded role checks to a permission-based system.

## Pre-Testing Requirements

### 1. Database Backup
```bash
# Create backup before testing
mysqldump -u username -p constructlink_db > backup_before_testing_$(date +%Y%m%d_%H%M%S).sql

# Or using XAMPP
/Applications/XAMPP/xamppfiles/bin/mysqldump -u root -p constructlink_db > backup_before_testing_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Test Users Setup
Create test users for each role:

```sql
-- Ensure test users exist for each role
SELECT u.id, u.username, u.full_name, r.name as role
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE r.name IN (
    'System Admin',
    'Finance Director',
    'Asset Director',
    'Procurement Officer',
    'Warehouseman',
    'Project Manager',
    'Site Inventory Clerk'
);
```

### 3. Test Data Setup
```sql
-- Create test assets in different workflow statuses
INSERT INTO assets (name, status, workflow_status, category_id, created_by, created_at)
VALUES
    ('Test Asset - Draft', 'available', 'draft', 1, 1, NOW()),
    ('Test Asset - Pending Verification', 'available', 'pending_verification', 1, 1, NOW()),
    ('Test Asset - Pending Authorization', 'available', 'pending_authorization', 1, 1, NOW()),
    ('Test Asset - Approved', 'available', 'approved', 1, 1, NOW()),
    ('Test Asset - Rejected', 'available', 'rejected', 1, 1, NOW());
```

## Testing Phases

### Phase 1: Helper Class Unit Tests

#### AssetStatus Helper Tests
```php
// Test file: tests/helpers/AssetStatusTest.php

class AssetStatusTest {
    public function testGetAllStatuses() {
        $statuses = AssetStatus::getAllStatuses();
        assert(count($statuses) === 8);
        assert(in_array(AssetStatus::AVAILABLE, $statuses));
    }

    public function testIsValidStatus() {
        assert(AssetStatus::isValidStatus('available') === true);
        assert(AssetStatus::isValidStatus('invalid') === false);
    }

    public function testGetStatusBadgeColor() {
        assert(AssetStatus::getStatusBadgeColor('available') === 'success');
        assert(AssetStatus::getStatusBadgeColor('under_maintenance') === 'warning');
    }

    public function testGetDisplayName() {
        assert(AssetStatus::getDisplayName('available') === 'Available');
        assert(AssetStatus::getDisplayName('in_use') === 'In Use');
    }

    public function testCanBeBorrowed() {
        assert(AssetStatus::canBeBorrowed('available') === true);
        assert(AssetStatus::canBeBorrowed('in_use') === false);
    }
}
```

#### AssetWorkflowStatus Helper Tests
```php
// Test file: tests/helpers/AssetWorkflowStatusTest.php

class AssetWorkflowStatusTest {
    public function testGetAllStatuses() {
        $statuses = AssetWorkflowStatus::getAllStatuses();
        assert(count($statuses) === 5);
    }

    public function testIsValidTransition() {
        assert(AssetWorkflowStatus::isValidTransition('draft', 'pending_verification') === true);
        assert(AssetWorkflowStatus::isValidTransition('draft', 'approved') === false);
    }

    public function testGetNextStatus() {
        assert(AssetWorkflowStatus::getNextStatus('draft') === 'pending_verification');
        assert(AssetWorkflowStatus::getNextStatus('approved') === null);
    }

    public function testAllowsEditing() {
        assert(AssetWorkflowStatus::allowsEditing('draft') === true);
        assert(AssetWorkflowStatus::allowsEditing('approved') === false);
    }
}
```

#### AssetPermission Helper Tests
```php
// Test file: tests/helpers/AssetPermissionTest.php

class AssetPermissionTest {
    public function testCanViewAssets() {
        // Mock user with view_all_assets permission
        $result = AssetPermission::canViewAssets();
        assert(is_bool($result));
    }

    public function testGetPermissionName() {
        $name = AssetPermission::getPermissionName('view_all_assets');
        assert($name === 'View All Assets');
    }

    public function testGetGroupedPermissions() {
        $grouped = AssetPermission::getGroupedPermissions();
        assert(is_array($grouped));
        assert(isset($grouped['Asset Viewing']));
    }
}
```

### Phase 2: Database Migration Tests

#### Test Migration Execution
```bash
# Run migration
mysql -u root -p constructlink_db < database/migrations/001_update_asset_permissions.sql

# Verify migration success
mysql -u root -p constructlink_db -e "
SELECT
    name,
    JSON_LENGTH(permissions) as permission_count
FROM roles
ORDER BY id;
"
```

#### Test Permission Data
```sql
-- Verify each role has correct permissions
SELECT
    r.name,
    JSON_PRETTY(r.permissions) as permissions
FROM roles r
WHERE r.name = 'Finance Director';

-- Should contain 'view_financial_data' permission

SELECT
    r.name,
    JSON_CONTAINS(r.permissions, '\"view_all_assets\"') as has_view_all,
    JSON_CONTAINS(r.permissions, '\"edit_assets\"') as has_edit
FROM roles r
WHERE r.name = 'Asset Director';

-- Both should be 1 (true)
```

#### Test Rollback
```bash
# Test rollback migration
mysql -u root -p constructlink_db < database/migrations/002_rollback_asset_permissions.sql

# Verify rollback success
mysql -u root -p constructlink_db -e "SELECT name FROM roles;"

# Re-apply forward migration
mysql -u root -p constructlink_db < database/migrations/001_update_asset_permissions.sql
```

### Phase 3: Permission Check Tests by Role

#### Test Matrix

| Permission Check | System Admin | Finance Director | Asset Director | Procurement Officer | Warehouseman | Project Manager | Site Clerk |
|-----------------|--------------|------------------|----------------|---------------------|--------------|-----------------|------------|
| canViewAssets() | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| canEditAssets() | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| canDeleteAssets() | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| canVerifyAssets() | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| canAuthorizeAssets() | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| canManageWithdrawals() | ✅ | ❌ | ✅ | ❌ | ✅ | ✅ | ❌ |
| canRequestWithdrawals() | ✅ | ❌ | ✅ | ❌ | ✅ | ✅ | ✅ |
| canViewFinancialData() | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| canApproveDisposal() | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| canManageBorrowedTools() | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |

### Phase 4: Controller Integration Tests

#### AssetController Tests

**Test Case 1: index() Method**
```
Login as: System Admin
Access: ?route=assets
Expected: ✅ 200 OK, asset list displayed
Verify: All filters visible, all action buttons visible

Login as: Site Inventory Clerk
Access: ?route=assets
Expected: ✅ 200 OK, asset list displayed
Verify: Limited filters, limited action buttons
```

**Test Case 2: create() Method**
```
Login as: Asset Director
Access: ?route=assets&action=create
Expected: ✅ 200 OK, create form displayed
Action: Submit form with valid data
Expected: ✅ Asset created successfully

Login as: Warehouseman
Access: ?route=assets&action=create
Expected: ❌ 403 Forbidden
```

**Test Case 3: edit() Method**
```
Login as: System Admin
Access: ?route=assets&action=edit&id=1
Expected: ✅ 200 OK, edit form displayed
Action: Update asset data
Expected: ✅ Asset updated successfully

Login as: Procurement Officer
Access: ?route=assets&action=edit&id=1
Expected: ❌ 403 Forbidden
```

**Test Case 4: delete() Method**
```
Login as: System Admin
Action: Delete asset via AJAX
Expected: ✅ {"success": true}

Login as: Asset Director
Action: Delete asset via AJAX
Expected: ❌ {"success": false, "message": "Unauthorized"}
```

**Test Case 5: verificationDashboard() Method**
```
Login as: Finance Director
Access: ?route=assets&action=verificationDashboard
Expected: ✅ 200 OK, verification dashboard displayed
Verify: Shows assets with workflow_status = 'pending_verification'

Login as: Asset Director
Access: ?route=assets&action=verificationDashboard
Expected: ❌ 403 Forbidden
```

**Test Case 6: authorizationDashboard() Method**
```
Login as: Asset Director
Access: ?route=assets&action=authorizationDashboard
Expected: ✅ 200 OK, authorization dashboard displayed
Verify: Shows assets with workflow_status = 'pending_authorization'

Login as: Finance Director
Access: ?route=assets&action=authorizationDashboard
Expected: ❌ 403 Forbidden
```

### Phase 5: Workflow Transition Tests

#### MVA Workflow Test Scenarios

**Scenario 1: Complete Happy Path**
```
1. Login as: Asset Director
2. Create new asset
   Expected: workflow_status = 'draft'

3. Submit for verification
   Expected: workflow_status = 'pending_verification'

4. Logout, Login as: Finance Director
5. Access verification dashboard
6. Verify asset
   Expected: workflow_status = 'pending_authorization'

7. Logout, Login as: Asset Director
8. Access authorization dashboard
9. Authorize asset
   Expected: workflow_status = 'approved'
```

**Scenario 2: Verification Rejection**
```
1. Asset with workflow_status = 'pending_verification'
2. Login as: Finance Director
3. Reject verification with reason
   Expected: workflow_status = 'draft'
   Expected: rejection_reason saved

4. Login as: Asset Director
5. Edit rejected asset
6. Resubmit for verification
   Expected: workflow_status = 'pending_verification'
```

**Scenario 3: Authorization Rejection**
```
1. Asset with workflow_status = 'pending_authorization'
2. Login as: Asset Director
3. Reject authorization with reason
   Expected: workflow_status = 'draft'
   Expected: rejection logged
```

**Scenario 4: Invalid Transitions**
```
1. Asset with workflow_status = 'draft'
2. Try to authorize directly (skip verification)
   Expected: ❌ Error: Invalid workflow transition

3. Asset with workflow_status = 'approved'
4. Try to edit
   Expected: ❌ Error: Cannot edit approved asset
```

### Phase 6: View File Tests

#### Test Filters Display
```
For each role, verify:
1. Status dropdown shows all statuses
2. Workflow status dropdown visibility based on permissions
3. Project filter visibility based on permissions
4. All filters work correctly
5. Active filter count displays correctly
```

#### Test Status Badges
```
For each asset status:
1. Badge color matches helper method
2. Icon displays correctly
3. Display name matches helper method
4. No hardcoded strings visible
```

#### Test Action Buttons
```
For each role, verify:
1. Create button visibility
2. Edit button visibility
3. Delete button visibility
4. Export button visibility
5. Verify button visibility (Finance Director only)
6. Authorize button visibility (Asset Director only)
```

### Phase 7: Performance Tests

#### Test Query Performance
```sql
-- Test permission query performance
EXPLAIN SELECT u.*, r.name as role_name, r.permissions
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE u.id = 1;

-- Should use index on role_id
```

#### Test Helper Method Performance
```php
// Benchmark helper methods
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    AssetStatus::getDisplayName('available');
}
$end = microtime(true);
echo "1000 calls: " . ($end - $start) . " seconds\n";
// Should be < 0.01 seconds
```

### Phase 8: Security Tests

#### Test Permission Bypass Attempts
```
1. Login as Site Inventory Clerk
2. Manually access: ?route=assets&action=delete&id=1
   Expected: ❌ 403 Forbidden

3. Try AJAX delete request with CSRF token
   Expected: ❌ Unauthorized access

4. Try to access verification dashboard
   Expected: ❌ 403 Forbidden
```

#### Test SQL Injection
```
1. Access: ?route=assets&status='; DROP TABLE assets; --
   Expected: ✅ No SQL error, safe handling

2. Access: ?route=assets&workflow_status=<script>alert('XSS')</script>
   Expected: ✅ Escaped output, no XSS
```

### Phase 9: Regression Tests

#### Test Existing Functionality
```
✅ Asset listing works
✅ Asset search works
✅ Asset pagination works
✅ Asset filtering works
✅ Asset creation works
✅ Asset editing works
✅ Asset deletion works (with correct permissions)
✅ QR code generation works
✅ Asset export works
✅ Asset bulk operations work
✅ Withdrawal operations work
✅ Transfer operations work
✅ Maintenance operations work
✅ Borrowed tools operations work
```

## Testing Checklist

### Pre-Deployment
- [ ] All helper class unit tests pass
- [ ] Database migration executes successfully
- [ ] Database rollback works correctly
- [ ] All role permission checks work
- [ ] All controller methods have correct permission checks
- [ ] All view files use helper methods
- [ ] No hardcoded role arrays remain
- [ ] No hardcoded status strings remain
- [ ] All workflow transitions work correctly

### Post-Deployment
- [ ] Monitor error logs for permission errors
- [ ] Monitor for 403 errors
- [ ] Verify all roles can access appropriate features
- [ ] Verify no legitimate users are blocked
- [ ] Check database query performance
- [ ] Verify no SQL errors in production
- [ ] Test with real user accounts

## Rollback Criteria

Execute rollback if:
- ❌ More than 5% of users report access issues
- ❌ Critical workflow broken
- ❌ Database errors occur
- ❌ Performance degradation > 20%
- ❌ Security vulnerability discovered

## Success Criteria

Deployment is successful if:
- ✅ All permission checks work correctly
- ✅ All roles can access appropriate features
- ✅ No 403 errors for legitimate access
- ✅ All workflows function correctly
- ✅ Performance remains stable
- ✅ No security issues
- ✅ Code maintainability improved
- ✅ All hardcoded values eliminated

## Testing Timeline

- **Day 1**: Helper class unit tests
- **Day 2**: Database migration tests
- **Day 3**: Controller integration tests
- **Day 4**: Workflow transition tests
- **Day 5**: View file tests
- **Day 6**: Security and performance tests
- **Day 7**: Regression tests and final verification

## Test Report Template

```
# Asset Refactoring Test Report

**Date**: [Date]
**Tester**: [Name]
**Environment**: [Development/Staging/Production]

## Test Results

### Helper Classes
- AssetStatus: [PASS/FAIL]
- AssetWorkflowStatus: [PASS/FAIL]
- AssetPermission: [PASS/FAIL]

### Database Migration
- Forward migration: [PASS/FAIL]
- Rollback migration: [PASS/FAIL]
- Permission data: [PASS/FAIL]

### Role Permissions
- System Admin: [PASS/FAIL]
- Finance Director: [PASS/FAIL]
- Asset Director: [PASS/FAIL]
- Procurement Officer: [PASS/FAIL]
- Warehouseman: [PASS/FAIL]
- Project Manager: [PASS/FAIL]
- Site Inventory Clerk: [PASS/FAIL]

### Workflows
- Asset creation: [PASS/FAIL]
- Verification workflow: [PASS/FAIL]
- Authorization workflow: [PASS/FAIL]
- Rejection handling: [PASS/FAIL]

### Issues Found
1. [Description]
2. [Description]

### Recommendations
1. [Recommendation]
2. [Recommendation]

**Overall Status**: [PASS/FAIL]
**Ready for Deployment**: [YES/NO]
```
