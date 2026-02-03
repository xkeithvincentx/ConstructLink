# Asset MVA Workflow Fix - Legacy Asset Creation

**Date:** 2025-11-11
**Issue:** Legacy assets were not respecting the MVA (Maker-Verifier-Authorizer) workflow
**Status:** ✅ **FIXED**

---

## Problem Description

When creating legacy assets through the Warehouseman interface, the assets were showing status as "Available" with `workflow_status = 'approved'` instead of following the proper MVA workflow:

1. **Warehouseman (Maker)** creates → should be `workflow_status = 'pending_verification'`
2. **Site Clerk/Asset Director (Verifier)** verifies → should become `workflow_status = 'pending_authorization'`
3. **Project Manager (Authorizer)** authorizes → should become `workflow_status = 'approved'`

### What Was Wrong

The `prepareAssetData()` function in `AssetCrudService.php` was NOT setting:
- ❌ `workflow_status` - defaulted to database default ('approved')
- ❌ `inventory_source` - was set in controller but after data preparation
- ❌ `made_by` - was not set at all (MVA Maker ID)

This meant:
- Assets created by Warehouseman were automatically approved
- No verification by Site Clerk required
- No authorization by Project Manager required
- **MVA workflow was completely bypassed**

---

## Solution Applied

### File Modified
`services/Asset/AssetCrudService.php` - `prepareAssetData()` function (lines 619-666)

### Changes Made

#### 1. **Added Workflow Status Logic**
```php
// Determine workflow status based on inventory_source
$isLegacyWorkflow = isset($data['inventory_source']) && $data['inventory_source'] === 'legacy';

// Legacy assets start at 'pending_verification' (auto-submitted for MVA workflow)
// Non-legacy assets (e.g., from procurement) are auto-approved
$workflowStatus = $isLegacyWorkflow ? 'pending_verification' : 'approved';
```

**Result:** Legacy assets now start at `'pending_verification'` instead of `'approved'`

#### 2. **Added Made By (Maker) Field**
```php
if ($isLegacyWorkflow) {
    // Get current user (Warehouseman) for made_by field
    global $auth;
    $currentUser = $auth->getCurrentUser();
    $madeBy = $currentUser['id'] ?? null;
}
```

**Result:** The Warehouseman's user ID is now recorded as the "Maker" in the MVA workflow

#### 3. **Added MVA Fields to Asset Data**
```php
$assetData = [
    // ... existing fields ...
    // MVA Workflow fields
    'workflow_status' => $workflowStatus,
    'inventory_source' => $data['inventory_source'] ?? null,
    'made_by' => $madeBy
];
```

**Result:** MVA workflow fields are now properly initialized

---

## MVA Workflow States

### Understanding the Two Status Fields

**1. `status` (Physical Status)**
- Values: `'available'`, `'in_use'`, `'borrowed'`, `'under_maintenance'`, `'retired'`, `'disposed'`, `'in_transit'`
- Represents the **physical state** of the asset
- Independent of approval workflow
- ✅ **Can be 'available' even if not yet approved** (pending verification/authorization)

**2. `workflow_status` (Approval Status)**
- Values: `'draft'`, `'pending_verification'`, `'pending_authorization'`, `'approved'`
- Represents the **approval state** in the MVA workflow
- Controls whether asset can be fully utilized
- ✅ **This is what controls the MVA workflow**

### Complete MVA Workflow for Legacy Assets

```
┌─────────────────────────────────────────────────────────────────┐
│                    LEGACY ASSET CREATION MVA WORKFLOW            │
└─────────────────────────────────────────────────────────────────┘

Step 1: MAKER (Warehouseman)
┌──────────────────────────────────────┐
│  Warehouseman creates legacy asset   │
│  ↓                                    │
│  Status: available                   │
│  Workflow Status: pending            │
│                   _verification       │
│  Made By: Warehouseman ID            │
└──────────────────────────────────────┘
                  ↓

Step 2: VERIFIER (Site Clerk / Asset Director)
┌──────────────────────────────────────┐
│  Site Clerk verifies the asset       │
│  ↓                                    │
│  Workflow Status: pending            │
│                   _authorization      │
│  Verified By: Site Clerk ID          │
│  Verification Date: timestamp        │
└──────────────────────────────────────┘
                  ↓

Step 3: AUTHORIZER (Project Manager)
┌──────────────────────────────────────┐
│  Project Manager authorizes asset    │
│  ↓                                    │
│  Workflow Status: approved           │
│  Authorized By: Project Manager ID   │
│  Authorization Date: timestamp       │
└──────────────────────────────────────┘
                  ↓

         ✅ FULLY APPROVED
    Asset ready for full utilization
```

---

## Database Schema

### inventory_items Table - MVA Workflow Columns

| Column | Type | Default | Purpose |
|--------|------|---------|---------|
| `status` | enum | 'available' | Physical status of asset |
| `workflow_status` | enum | 'approved' | MVA workflow approval status |
| `inventory_source` | enum | NULL | Source: 'legacy', 'procurement', 'manual' |
| `made_by` | int | NULL | User ID of Maker (Warehouseman) |
| `verified_by` | int | NULL | User ID of Verifier (Site Clerk) |
| `authorized_by` | int | NULL | User ID of Authorizer (Project Manager) |
| `verification_date` | timestamp | NULL | When asset was verified |
| `authorization_date` | timestamp | NULL | When asset was authorized |

### Workflow Status Values

```sql
workflow_status ENUM(
    'draft',                    -- Being created/edited
    'pending_verification',     -- Awaiting Site Clerk verification
    'pending_authorization',    -- Awaiting Project Manager authorization
    'approved'                  -- Fully approved, ready for use
) DEFAULT 'approved'
```

---

## Testing the Fix

### Test Case 1: Create Legacy Asset as Warehouseman

**Steps:**
1. Login as Warehouseman
2. Navigate to: `?route=assets/legacy-create`
3. Fill in asset details
4. Submit form

**Expected Result:**
```sql
SELECT workflow_status, inventory_source, made_by, status
FROM inventory_items
WHERE ref = 'CON-LEG-XX-XX-XXXX';

-- Should show:
workflow_status: 'pending_verification'  ✅
inventory_source: 'legacy'               ✅
made_by: {warehouseman_user_id}          ✅
status: 'available'                      ✅
```

### Test Case 2: Verify Asset as Site Clerk

**Steps:**
1. Login as Site Clerk (Asset Director)
2. Navigate to pending verification dashboard
3. Verify the asset

**Expected Result:**
```sql
workflow_status: 'pending_authorization'   ✅
verified_by: {site_clerk_user_id}          ✅
verification_date: {timestamp}             ✅
```

### Test Case 3: Authorize Asset as Project Manager

**Steps:**
1. Login as Project Manager
2. Navigate to pending authorization dashboard
3. Authorize the asset

**Expected Result:**
```sql
workflow_status: 'approved'                 ✅
authorized_by: {project_manager_user_id}    ✅
authorization_date: {timestamp}             ✅
```

---

## Role Permissions

### Who Can Do What

| Action | Role | Route |
|--------|------|-------|
| **Create** Legacy Assets | Warehouseman | `assets/legacy-create` |
| **Verify** Assets | Site Clerk, Asset Director | `assets/legacy-verify` |
| **Authorize** Assets | Project Manager | `assets/legacy-authorize` |
| **View** Pending Verification | Site Clerk, Asset Director | `assets/verification-dashboard` |
| **View** Pending Authorization | Project Manager | `assets/authorization-dashboard` |

---

## Why Two Status Fields?

### Scenario: Asset is available but not yet approved

**Example:**
```
A Warehouseman adds a new drill to the system:
  - Physical Status: 'available' (the drill exists and is physically available)
  - Workflow Status: 'pending_verification' (not yet verified by Site Clerk)

The drill is IN THE WAREHOUSE and AVAILABLE physically,
but it's NOT YET APPROVED for project use until verified and authorized.
```

This separation allows:
✅ **Physical tracking** independent of approval workflow
✅ **Proper MVA controls** while maintaining asset visibility
✅ **Audit trail** of who made, verified, and authorized each asset

---

## Non-Legacy Assets (Auto-Approved)

Assets from other sources skip the MVA workflow:

### Procurement Assets
```php
inventory_source: 'procurement'
workflow_status: 'approved'  // Auto-approved
made_by: NULL                // No maker required
```

**Reason:** Procurement assets go through their own procurement approval workflow before becoming assets.

### Manual Assets
```php
inventory_source: 'manual'
workflow_status: 'approved'  // Auto-approved
```

**Reason:** Created by authorized users (Project Manager, System Admin) who can directly approve.

---

## Views Affected

### 1. Asset List View
**File:** `views/assets/partials/_asset_list.php`

**Changes:** Shows proper workflow status badges
- 🟡 **Yellow Badge** - "Pending Verification"
- 🔵 **Blue Badge** - "Pending Authorization"
- 🟢 **Green Badge** - "Approved"

### 2. Verification Dashboard
**File:** `views/assets/verification_dashboard.php`

**Shows:** Assets with `workflow_status = 'pending_verification'`

### 3. Authorization Dashboard
**File:** `views/assets/authorization_dashboard.php`

**Shows:** Assets with `workflow_status = 'pending_authorization'`

---

## Summary

### Before Fix ❌
- Legacy assets: `workflow_status = 'approved'` (bypassed MVA)
- No maker recorded
- No verification required
- No authorization required
- **MVA workflow not functioning**

### After Fix ✅
- Legacy assets: `workflow_status = 'pending_verification'` (enters MVA workflow)
- Maker (Warehouseman) recorded in `made_by`
- Site Clerk must verify
- Project Manager must authorize
- **Full MVA workflow enforced**

---

## Related Files

- **Service:** `services/Asset/AssetCrudService.php` (Modified)
- **Controller:** `controllers/AssetController.php` (Uses service)
- **Workflow Service:** `services/Asset/AssetWorkflowService.php` (Handles transitions)
- **Views:**
  - `views/assets/legacy_create.php`
  - `views/assets/verification_dashboard.php`
  - `views/assets/authorization_dashboard.php`
  - `views/assets/partials/_asset_list.php`

---

## Migration Note

### Existing Legacy Assets

Assets created before this fix may have `workflow_status = 'approved'` even though they should have gone through MVA workflow.

**Optional SQL to identify:**
```sql
SELECT id, ref, name, workflow_status, inventory_source, made_by
FROM inventory_items
WHERE inventory_source = 'legacy'
  AND workflow_status = 'approved'
  AND (verified_by IS NULL OR authorized_by IS NULL);
```

**These assets were auto-approved and don't need retroactive workflow** - they're grandfathered in.

---

**Fix Status:** ✅ **COMPLETE**
**Testing:** Ready for testing
**Rollout:** Can be deployed immediately

---

**END OF REPORT**
