# ConstructLink™ Asset Edit Workflow & Responsibilities

## Current System Analysis

### 🔍 Current State
Based on the codebase analysis, the system currently has **LIMITED EDIT CAPABILITIES**:
- ✅ Status updates are allowed
- ✅ Verification and rejection workflows exist
- ❌ No full asset edit functionality implemented
- ❌ No edit forms or UI for corrections
- ❌ Routes defined but controller methods missing

## 📋 Workflow Scenarios & Responsibilities

### 1. **Regular Asset Creation Workflow (MVA Pattern)**

```
Procurement Officer → Asset Director → System Admin
    (Maker)           (Verifier)      (Authorizer)
```

#### Error Correction Scenarios:

##### Scenario A: Error Found During Verification (Asset Director Stage)
- **Who Detects**: Asset Director
- **Current Options**:
  1. ❌ **REJECT** → Returns to Procurement Officer
  2. ⚠️ **NO EDIT OPTION** currently available
- **Responsible Party**: 
  - Should be: Asset Director (minor corrections)
  - Currently: Must reject back to Procurement Officer

##### Scenario B: Error Found After Authorization (System Admin Stage)
- **Who Detects**: System Admin
- **Current Options**:
  1. Status change only
  2. ⚠️ **NO EDIT OPTION** available
- **Responsible Party**: 
  - Should be: System Admin (with audit trail)
  - Currently: No mechanism available

### 2. **Legacy Asset Workflow**

```
Warehouseman → Site Inventory Clerk → Asset Director → Active
   (Create)         (Verify)            (Approve)
```

#### Error Correction Scenarios:

##### Scenario A: Error During Site Verification
- **Who Detects**: Site Inventory Clerk
- **Current Options**:
  1. ✅ Can verify with notes
  2. ❌ Cannot edit details
- **Responsible Party**: 
  - Should be: Site Inventory Clerk (field corrections)
  - Currently: Must reject back to Warehouseman

##### Scenario B: Error During Final Approval
- **Who Detects**: Asset Director
- **Current Options**:
  1. ❌ Reject back to pending
  2. ⚠️ No direct edit capability
- **Responsible Party**:
  - Should be: Asset Director
  - Currently: Must reject through entire chain

## 🎯 Recommended Edit Permissions Matrix

### By Role & Asset Status

| Role | Status | Can Edit | Fields Allowed | Conditions |
|------|--------|----------|----------------|------------|
| **Procurement Officer** | Draft | ✅ Full | All | Own assets only |
| **Procurement Officer** | Pending Verification | ✅ Limited | Non-critical fields | Own assets, before verification |
| **Procurement Officer** | Rejected | ✅ Full | All | To resubmit |
| **Asset Director** | Pending Verification | ✅ Corrections | All except financial | During verification |
| **Asset Director** | Verified | ✅ Limited | Categories, disciplines, brands | Post-verification corrections |
| **System Admin** | Any Status | ✅ Full | All | With audit log |
| **Warehouseman** | Legacy-Pending | ✅ Full | All legacy fields | Own submissions |
| **Site Inventory Clerk** | Legacy-Pending | ✅ Field Updates | Location, condition, quantity | During site verification |

## 🔧 Proposed Implementation

### 1. **Edit Controller Method**
```php
public function edit($id = null) {
    // Check if user can edit based on:
    // - Role
    // - Asset status
    // - Ownership (maker_id)
    // - Workflow stage
}
```

### 2. **Edit Permissions Logic**
```php
public function canEditAsset($asset, $user) {
    $role = $user['role_name'];
    $status = $asset['status'];
    $isMaker = ($asset['maker_id'] == $user['id']);
    
    // System Admin can always edit
    if ($role === 'System Admin') return true;
    
    // Maker can edit own draft/rejected assets
    if ($isMaker && in_array($status, ['draft', 'rejected'])) {
        return true;
    }
    
    // Asset Director can edit during verification
    if ($role === 'Asset Director' && 
        in_array($status, ['pending_verification', 'verified'])) {
        return true;
    }
    
    // Site Inventory Clerk can edit legacy assets
    if ($role === 'Site Inventory Clerk' && 
        $asset['workflow_type'] === 'legacy' &&
        $status === 'pending_site_verification') {
        return true;
    }
    
    return false;
}
```

### 3. **Audit Trail for Edits**
```sql
CREATE TABLE asset_edit_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    edited_by INT NOT NULL,
    edit_type ENUM('correction', 'update', 'field_verification'),
    field_name VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    reason TEXT,
    workflow_stage VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id),
    FOREIGN KEY (edited_by) REFERENCES users(id)
);
```

## 🚨 Critical Gaps to Address

1. **No Edit UI/Forms**: Need to create edit views
2. **No Edit Routes**: Routes defined but not implemented
3. **No Correction Workflow**: Only reject/accept available
4. **No Audit Trail**: Changes not tracked
5. **No Field-Level Permissions**: Can't restrict which fields are editable

## 📊 Business Impact

### Current Problems:
- ❌ Minor typos require full rejection cycle
- ❌ Delays in asset activation due to small errors
- ❌ Frustration for users who must re-enter entire forms
- ❌ No way to correct historical data

### Benefits of Implementation:
- ✅ Faster error correction (minutes vs hours/days)
- ✅ Maintain data integrity with audit trails
- ✅ Reduce workflow bottlenecks
- ✅ Improve user satisfaction
- ✅ Enable data quality improvements

## 🎬 Recommended Next Steps

1. **Phase 1**: Implement basic edit for System Admin (full control)
2. **Phase 2**: Add role-based edit permissions
3. **Phase 3**: Implement field-level restrictions
4. **Phase 4**: Add audit trail and change history
5. **Phase 5**: Create correction request workflow

## 💡 Alternative Quick Fix

For immediate relief, implement a "Correction Request" system:
1. Any user can submit a correction request
2. Asset Director reviews and approves
3. System applies the correction with audit log
4. Original workflow continues uninterrupted

This provides a workaround while the full edit system is developed.