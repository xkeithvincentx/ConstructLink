# In Transit Status Implementation

## Overview
This document outlines the implementation of the 'in_transit' status for assets in the ConstructLink transfer MVA workflow. The change improves traceability by properly tracking asset status during transfers.

## Problem Statement
Previously, when a transfer was approved by the Finance Director/Asset Director, the asset status remained 'available' which was incorrect. Assets should be marked as 'in_transit' during the transfer process for better traceability.

## Solution Implemented

### 1. Database Schema Changes
- **File**: `database/migrations/add_in_transit_status.sql`
- **Change**: Modified assets table ENUM to include 'in_transit' status
- **Before**: `enum('available','in_use','borrowed','under_maintenance','retired','disposed')`
- **After**: `enum('available','in_use','borrowed','under_maintenance','retired','disposed','in_transit')`

### 2. Transfer Model Updates
- **File**: `models/TransferModel.php`
- **Changes**:
  - `approveTransfer()`: Now sets asset status to 'in_transit' when transfer is approved
  - `cancelTransfer()`: Updated to handle canceling approved transfers (in_transit assets)
  - Improved error messages and transaction handling

### 3. Asset Model Updates
- **File**: `models/AssetModel.php`
- **Changes**:
  - `getAssetStatistics()`: Added 'in_transit' to status counting
  - `getAssetStats()`: Added 'in_transit' to dashboard statistics
  - Comments updated to clarify that 'available' excludes 'in_transit' assets

### 4. Helper Functions Updates
- **File**: `core/helpers.php`
- **Changes**:
  - `getStatusBadgeClass()`: Added 'in_transit' => 'bg-warning text-dark'
  - `getStatusLabel()`: Added 'in_transit' => 'In Transit'
  - Also added missing 'disposed' status for completeness

### 5. View Updates
- **File**: `views/assets/index.php`
- **Changes**:
  - Added 'In Transit' option to status filter dropdown
  - Added 'Disposed' option for completeness
  - Maintains proper ordering of status options

## Transfer Workflow After Implementation

### MVA Flow Status Changes:
1. **Creation**: Asset status = 'in_use' (prevents other operations)
2. **Verification**: Asset status remains 'in_use'
3. **Approval**: Asset status = 'in_transit' ✨ **NEW**
4. **Receipt**: Asset status remains 'in_transit'
5. **Completion**: Asset status = 'available' (at new location)

### Cancellation Handling:
- Transfers can now be canceled even after approval (when in_transit)
- Asset status is restored to 'available' regardless of previous status
- Supports cancellation at any stage: Pending Verification, Pending Approval, or Approved

## Benefits

1. **Better Traceability**: Assets in transit are clearly identified
2. **Accurate Reporting**: Dashboard and reports now show assets in transit
3. **Improved Inventory Management**: Prevents confusion about asset availability
4. **Audit Trail**: Clear status progression through transfer workflow
5. **User Experience**: Visual distinction for assets in transit

## Backward Compatibility

- All existing functionality preserved
- New status only adds capabilities, doesn't break existing logic
- Default asset status remains 'available'
- Existing status checks continue to work

## Dependencies Verified

All code that depends on asset status values has been updated:
- ✅ Database schema
- ✅ Transfer model workflow
- ✅ Asset model statistics
- ✅ Helper functions
- ✅ View filters and displays
- ✅ Status badges and labels

## Testing Recommendations

1. **Run the migration**: Execute `add_in_transit_status.sql`
2. **Test transfer workflow**:
   - Create a transfer request
   - Verify asset status through each MVA stage
   - Confirm asset shows as 'in_transit' after approval
   - Complete transfer and verify status becomes 'available'
3. **Test cancellation**: Cancel an approved transfer and verify status restoration
4. **Test UI**: Check asset filters include 'In Transit' option
5. **Test dashboards**: Verify statistics include in_transit counts

## Files Modified

1. `database/migrations/add_in_transit_status.sql` (NEW)
2. `models/TransferModel.php`
3. `models/AssetModel.php`
4. `core/helpers.php`
5. `views/assets/index.php`
6. `database/migrations/README_in_transit_implementation.md` (NEW - this file)

## Status Values Reference

| Status | Description | Usage |
|--------|-------------|-------|
| available | Ready for use | Default state |
| in_use | Currently withdrawn/assigned | During withdrawals |
| borrowed | Borrowed via tools system | Borrowed tools |
| in_transit | Being transferred | **NEW** - During transfers |
| under_maintenance | Being maintained | Maintenance workflow |
| retired | Retired from service | End of life |
| disposed | Disposed/scrapped | Final state |