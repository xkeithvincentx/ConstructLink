# Legacy Quantity Addition - Workflow Diagram

**Visual Guide**: Understanding the Duplicate Detection and Quantity Addition Process

---

## Overview Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     WAREHOUSEMAN CREATES LEGACY ITEM                        │
└─────────────────────────────────────────────────────────────────────────────┘
                                      ↓
                          ┌───────────────────────┐
                          │  Duplicate Detection  │
                          │   (AssetCrudService)  │
                          └───────────────────────┘
                                      ↓
                    ┌─────────────────┴─────────────────┐
                    │                                   │
              NO DUPLICATE                        DUPLICATE FOUND
                    │                                   │
                    ↓                                   ↓
        ┌────────────────────────┐       ┌──────────────────────────┐
        │  CREATE NEW ITEM       │       │  ADD PENDING QUANTITY    │
        │                        │       │  TO EXISTING ITEM        │
        │  • Generate new ref    │       │                          │
        │  • Set workflow status │       │  • Update existing item  │
        │    = pending_verif.    │       │  • Set pending_quantity  │
        │  • Log creation        │       │  • Reset workflow status │
        └────────────────────────┘       └──────────────────────────┘
                    │                                   │
                    └─────────────────┬─────────────────┘
                                      ↓
                          ┌───────────────────────┐
                          │ VERIFICATION REQUIRED │
                          │  (Site Inventory)     │
                          └───────────────────────┘
                                      ↓
                        ┌─────────────────────────┐
                        │ AUTHORIZATION REQUIRED  │
                        │  (Project Manager)      │
                        └─────────────────────────┘
                                      ↓
                            ┌─────────────────┐
                            │  APPROVED ✅    │
                            │ Quantity Added  │
                            └─────────────────┘
```

---

## Detailed Duplicate Detection Flow

```
┌──────────────────────────────────────────────────────────────────────────┐
│  Step 1: FORM SUBMISSION                                                 │
├──────────────────────────────────────────────────────────────────────────┤
│  Warehouseman fills in form:                                             │
│  • Name: "Electrical Wire 2.0mm"                                         │
│  • Category: Electrical Supplies                                         │
│  • Project: Site A Construction                                          │
│  • Quantity: 20 meters                                                   │
│  • [Submit] button clicked                                               │
└──────────────────────────────────────────────────────────────────────────┘
                                  ↓
┌──────────────────────────────────────────────────────────────────────────┐
│  Step 2: CONTROLLER PROCESSING (AssetController::legacyCreate)           │
├──────────────────────────────────────────────────────────────────────────┤
│  • Sanitize input data                                                   │
│  • Add inventory_source = 'legacy'  ← IMPORTANT FOR DUPLICATE CHECK     │
│  • Call AssetModel::createLegacyAsset($formData)                         │
└──────────────────────────────────────────────────────────────────────────┘
                                  ↓
┌──────────────────────────────────────────────────────────────────────────┐
│  Step 3: SERVICE PROCESSING (AssetCrudService::createAsset)              │
├──────────────────────────────────────────────────────────────────────────┤
│  • Validate input data                                                   │
│  • Check project access                                                  │
│  • Validate category business rules                                      │
│                                                                           │
│  IF (inventory_source == 'legacy' AND is_consumable == true)             │
│     THEN run duplicate detection                                         │
│     ELSE skip duplicate check                                            │
└──────────────────────────────────────────────────────────────────────────┘
                                  ↓
┌──────────────────────────────────────────────────────────────────────────┐
│  Step 4: DUPLICATE CHECK (AssetCrudService::checkConsumableDuplicate)    │
├──────────────────────────────────────────────────────────────────────────┤
│  Uses AssetMatchingService to find items matching:                       │
│                                                                           │
│  Matching Criteria:                                                      │
│    ✓ LOWER(name) = LOWER('Electrical Wire 2.0mm')                       │
│    ✓ category_id = 5 (Electrical Supplies)                              │
│    ✓ project_id = 12 (Site A Construction)                              │
│    ✓ model = NULL (or matches if specified)                             │
│    ✓ status IN ('available', 'borrowed', 'in_maintenance')              │
│                                                                           │
│  Query: SELECT * FROM inventory_items WHERE ... LIMIT 5                  │
└──────────────────────────────────────────────────────────────────────────┘
                                  ↓
                    ┌─────────────────┴─────────────────┐
                    │                                   │
          FOUND MATCH (id=123)               NO MATCH FOUND
                    │                                   │
                    ↓                                   ↓
┌────────────────────────────────────────┐  ┌─────────────────────────────┐
│  Step 5A: ADD PENDING QUANTITY         │  │  Step 5B: CREATE NEW ITEM   │
├────────────────────────────────────────┤  ├─────────────────────────────┤
│  Execute SQL:                          │  │  • Generate ref: DEF-456    │
│                                        │  │  • Create inventory_items   │
│  UPDATE inventory_items SET            │  │  • Set workflow_status      │
│    pending_quantity_addition =         │  │    = 'pending_verification' │
│      pending_quantity_addition + 20,   │  │  • Insert into database     │
│    pending_addition_made_by = 45,      │  │                             │
│    pending_addition_date = NOW(),      │  │  Result:                    │
│    workflow_status =                   │  │    New item created         │
│      'pending_verification'            │  │    ID: 456                  │
│  WHERE id = 123;                       │  │                             │
│                                        │  └─────────────────────────────┘
│  Result:                               │
│    Existing item updated               │
│    Pending quantity: 20 meters         │
└────────────────────────────────────────┘
                    │
                    ↓
┌────────────────────────────────────────┐
│  Step 6: LOG ACTIVITY                  │
├────────────────────────────────────────┤
│  INSERT INTO activity_logs:            │
│  • action: 'pending_quantity_added'    │
│  • description: "Added pending         │
│    quantity (20 meters)..."            │
│  • user_id: 45 (current user)          │
│  • table_name: 'inventory_items'       │
│  • record_id: 123                      │
└────────────────────────────────────────┘
                    │
                    ↓
┌────────────────────────────────────────┐
│  Step 7: RETURN RESPONSE               │
├────────────────────────────────────────┤
│  Return to controller:                 │
│  {                                     │
│    "success": true,                    │
│    "is_duplicate": true,               │
│    "action": "quantity_added",         │
│    "existing_item": {...},             │
│    "quantity_added": 20,               │
│    "message": "Duplicate detected..."  │
│  }                                     │
└────────────────────────────────────────┘
                    │
                    ↓
┌────────────────────────────────────────┐
│  Step 8: DISPLAY TO USER               │
├────────────────────────────────────────┤
│  Show success message:                 │
│  ⚠️ "Duplicate item detected! Added    │
│      20 meters to existing item:       │
│      Electrical Wire 2.0mm             │
│      (Ref: ABC-123).                   │
│      The quantity addition is pending  │
│      verification through the MVA      │
│      workflow."                        │
└────────────────────────────────────────┘
```

---

## Verification Flow

```
┌──────────────────────────────────────────────────────────────────────────┐
│  SITE INVENTORY CLERK DASHBOARD                                          │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  Pending Verification Items:                                             │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ Item: Electrical Wire 2.0mm                                        │ │
│  │ REF: ABC-123                                                       │ │
│  │ [⚠️ Quantity Addition]  ← Yellow badge                            │ │
│  │                                                                    │ │
│  │ Category: Electrical Supplies                                     │ │
│  │ Location: Warehouse - Shelf A3                                    │ │
│  │                                                                    │ │
│  │ Quantity: 50 meters                                               │ │
│  │ + 20 meters pending  ← Shows what's being added                   │ │
│  │                                                                    │ │
│  │ Created by: John Doe (Warehouseman)                               │ │
│  │ Date: Nov 11, 2025                                                │ │
│  │                                                                    │ │
│  │ [View Details] [✓ Verify]                                         │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
└──────────────────────────────────────────────────────────────────────────┘
                                  ↓
                         Clerk clicks [✓ Verify]
                                  ↓
┌──────────────────────────────────────────────────────────────────────────┐
│  VERIFICATION PROCESSING (AssetWorkflowService::verifyAsset)             │
├──────────────────────────────────────────────────────────────────────────┤
│  • Permission check: User has 'verify' permission                        │
│  • Validate workflow state: Must be 'pending_verification'               │
│  • Check not self-verification: verified_by ≠ made_by                   │
│                                                                           │
│  UPDATE inventory_items SET                                              │
│    workflow_status = 'pending_authorization',                            │
│    verified_by = 67,  ← Current user ID                                 │
│    verification_date = NOW()                                             │
│  WHERE id = 123;                                                         │
│                                                                           │
│  Log activity: "Asset verified by Site Inventory Clerk"                  │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Authorization Flow

```
┌──────────────────────────────────────────────────────────────────────────┐
│  PROJECT MANAGER DASHBOARD                                               │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  Pending Authorization Items:                                            │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ Item: Electrical Wire 2.0mm                                        │ │
│  │ REF: ABC-123                                                       │ │
│  │ [⚠️ Quantity Addition]                                            │ │
│  │                                                                    │ │
│  │ Quantity: 50 meters                                               │ │
│  │ + 20 meters pending  ← What will be added                         │ │
│  │                                                                    │ │
│  │ Created by: John Doe (Warehouseman)                               │ │
│  │ Verified by: Jane Smith (Site Inventory Clerk)                    │ │
│  │ Verification Date: Nov 11, 2025                                   │ │
│  │                                                                    │ │
│  │ [View Details] [✓ Authorize]                                      │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
└──────────────────────────────────────────────────────────────────────────┘
                                  ↓
                      Manager clicks [✓ Authorize]
                                  ↓
┌──────────────────────────────────────────────────────────────────────────┐
│  AUTHORIZATION PROCESSING (AssetWorkflowService::authorizeAsset)         │
├──────────────────────────────────────────────────────────────────────────┤
│  • Permission check: User has 'authorize' permission                     │
│  • Validate workflow state: Must be 'pending_authorization'              │
│  • Check not self-authorization: authorized_by ≠ made_by/verified_by    │
│                                                                           │
│  Detect pending quantity: hasPendingQuantity = (pending_qty > 0)         │
│                                                                           │
│  IF hasPendingQuantity:                                                  │
│    Calculate new quantities:                                             │
│      new_quantity = 50 + 20 = 70                                         │
│      new_available_quantity = 50 + 20 = 70                               │
│                                                                           │
│  UPDATE inventory_items SET                                              │
│    workflow_status = 'approved',                                         │
│    status = 'available',                                                 │
│    quantity = 70,  ← Applied pending quantity                           │
│    available_quantity = 70,                                              │
│    pending_quantity_addition = 0,  ← Clear pending                      │
│    pending_addition_made_by = NULL,                                      │
│    pending_addition_date = NULL,                                         │
│    authorized_by = 89,                                                   │
│    authorization_date = NOW()                                            │
│  WHERE id = 123;                                                         │
│                                                                           │
│  Log: "Asset authorized (approved quantity addition: +20 meters)"        │
└──────────────────────────────────────────────────────────────────────────┘
                                  ↓
┌──────────────────────────────────────────────────────────────────────────┐
│  RESULT: QUANTITY SUCCESSFULLY ADDED ✅                                  │
├──────────────────────────────────────────────────────────────────────────┤
│  Before:                                                                 │
│    • quantity: 50 meters                                                 │
│    • available_quantity: 50 meters                                       │
│    • pending_quantity_addition: 20 meters                                │
│                                                                           │
│  After:                                                                  │
│    • quantity: 70 meters  ← Increased by 20                             │
│    • available_quantity: 70 meters                                       │
│    • pending_quantity_addition: 0  ← Cleared                            │
│    • workflow_status: 'approved'                                         │
│    • status: 'available'                                                 │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Database State Transitions

```
INITIAL STATE (Existing Item)
┌─────────────────────────────────────────────────────┐
│ id: 123                                             │
│ ref: ABC-123                                        │
│ name: Electrical Wire 2.0mm                         │
│ quantity: 50                                        │
│ available_quantity: 50                              │
│ pending_quantity_addition: 0                        │
│ workflow_status: approved                           │
│ status: available                                   │
└─────────────────────────────────────────────────────┘
                      ↓
         [Warehouseman adds 20 meters]
                      ↓
AFTER DUPLICATE DETECTION
┌─────────────────────────────────────────────────────┐
│ id: 123                                             │
│ ref: ABC-123                                        │
│ name: Electrical Wire 2.0mm                         │
│ quantity: 50  ← Unchanged                           │
│ available_quantity: 50  ← Unchanged                 │
│ pending_quantity_addition: 20  ← Added pending      │
│ pending_addition_made_by: 45                        │
│ pending_addition_date: 2025-11-11 10:30:00          │
│ workflow_status: pending_verification  ← Reset      │
│ status: available                                   │
└─────────────────────────────────────────────────────┘
                      ↓
        [Site Inventory Clerk verifies]
                      ↓
AFTER VERIFICATION
┌─────────────────────────────────────────────────────┐
│ id: 123                                             │
│ pending_quantity_addition: 20  ← Still pending      │
│ workflow_status: pending_authorization              │
│ verified_by: 67                                     │
│ verification_date: 2025-11-11 11:00:00              │
└─────────────────────────────────────────────────────┘
                      ↓
           [Project Manager authorizes]
                      ↓
AFTER AUTHORIZATION (FINAL)
┌─────────────────────────────────────────────────────┐
│ id: 123                                             │
│ ref: ABC-123                                        │
│ name: Electrical Wire 2.0mm                         │
│ quantity: 70  ← Applied (50 + 20)                   │
│ available_quantity: 70  ← Applied (50 + 20)         │
│ pending_quantity_addition: 0  ← Cleared             │
│ pending_addition_made_by: NULL                      │
│ pending_addition_date: NULL                         │
│ workflow_status: approved                           │
│ status: available                                   │
│ authorized_by: 89                                   │
│ authorization_date: 2025-11-11 14:00:00             │
└─────────────────────────────────────────────────────┘
```

---

## Error Scenarios

### Scenario 1: Duplicate Check Fails (Exception)

```
[Form Submission]
        ↓
[Duplicate Check Throws Exception]
        ↓
    Catch block:
    • Log error to PHP error log
    • Return is_duplicate = false
    • Proceed with item creation (FAIL-SAFE)
        ↓
[New Item Created Successfully]
```

### Scenario 2: Verification Rejected

```
[Pending Verification]
        ↓
[Clerk Clicks "Reject"]
        ↓
• pending_quantity_addition remains
• workflow_status → 'draft' or deleted
• Quantity NOT added to inventory
• Warehouseman notified
```

---

## Activity Log Trail

```
Timeline for Single Quantity Addition:

2025-11-11 10:30:00
┌────────────────────────────────────────────────────────────┐
│ Action: pending_quantity_added                             │
│ User: John Doe (Warehouseman, ID: 45)                      │
│ Description: "Added pending quantity (20 meters) to        │
│   existing item: Electrical Wire 2.0mm (ABC-123).          │
│   Awaiting verification."                                  │
│ Table: inventory_items                                     │
│ Record ID: 123                                             │
└────────────────────────────────────────────────────────────┘

2025-11-11 11:00:00
┌────────────────────────────────────────────────────────────┐
│ Action: asset_verified                                     │
│ User: Jane Smith (Site Inventory Clerk, ID: 67)            │
│ Description: "Asset 'Electrical Wire 2.0mm' verified       │
│   by Site Inventory Clerk"                                │
│ Table: inventory_items                                     │
│ Record ID: 123                                             │
└────────────────────────────────────────────────────────────┘

2025-11-11 14:00:00
┌────────────────────────────────────────────────────────────┐
│ Action: asset_authorized                                   │
│ User: Bob Johnson (Project Manager, ID: 89)                │
│ Description: "Asset 'Electrical Wire 2.0mm' authorized     │
│   by Finance Director (approved quantity addition:         │
│   +20 meters)"                                             │
│ Table: inventory_items                                     │
│ Record ID: 123                                             │
└────────────────────────────────────────────────────────────┘
```

---

## Visual Indicators Guide

### In Dashboards

**Normal Item (No Pending Quantity)**
```
┌─────────────────────────────────┐
│ Electrical Wire 3.5mm           │
│ REF: XYZ-789                    │
│ Category: Electrical Supplies   │
│ Quantity: 100 meters            │
└─────────────────────────────────┘
```

**Item with Pending Quantity Addition**
```
┌─────────────────────────────────┐
│ Electrical Wire 2.0mm           │
│ REF: ABC-123                    │
│ [⚠️ Quantity Addition]  ← Badge │
│ Category: Electrical Supplies   │
│ Quantity: 50 meters             │
│ + 20 meters pending  ← Warning  │
└─────────────────────────────────┘
```

---

**Document Version**: 1.0
**Last Updated**: 2025-11-11
**For**: ConstructLink™ System
