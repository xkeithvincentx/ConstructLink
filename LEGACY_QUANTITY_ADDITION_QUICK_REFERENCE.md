# Legacy Quantity Addition - Quick Reference Guide

**For**: Warehousemen, Site Inventory Clerks, and Project Managers
**Version**: 1.0
**Date**: 2025-11-11

---

## What's New?

The system now **automatically detects duplicate consumable items** and adds quantities to existing items instead of creating duplicates!

---

## For Warehousemen (Makers)

### When Creating Legacy Items

**Scenario 1: New Item (No Duplicate)**
```
You enter:
- Name: "Electrical Wire 3.5mm"
- Category: Electrical Supplies
- Project: Site A
- Quantity: 50 meters

System response:
✅ "Legacy asset created successfully and is pending verification."

What happens:
→ New item is created
→ Goes to Site Inventory Clerk for verification
```

**Scenario 2: Duplicate Item Detected**
```
You enter:
- Name: "Electrical Wire 2.0mm" (already exists)
- Category: Electrical Supplies
- Project: Site A
- Quantity: 20 meters

System response:
⚠️ "Duplicate item detected! Added 20 meters to existing item:
    Electrical Wire 2.0mm (Ref: ABC-123).
    The quantity addition is pending verification through the MVA workflow."

What happens:
→ No new item created
→ 20 meters added as "pending" to existing item
→ Goes to Site Inventory Clerk for verification
```

### What Makes Items "Duplicate"?

Items are considered duplicates when they have:
- ✅ Same name (case-insensitive)
- ✅ Same category
- ✅ Same project
- ✅ Same model (if specified)

---

## For Site Inventory Clerks (Verifiers)

### In Verification Dashboard

You'll see items marked with a **yellow badge**:

```
┌─────────────────────────────────────────────────────┐
│ Item Name                                           │
│ Electrical Wire 2.0mm                               │
│ REF: ABC-123                                        │
│ [⚠️ Quantity Addition]  ← Yellow badge              │
├─────────────────────────────────────────────────────┤
│ Quantity                                            │
│ 50 meters                                           │
│ + 20 meters pending  ← Shows pending quantity       │
└─────────────────────────────────────────────────────┘
```

### Your Task

1. **Physically verify** that the additional quantity exists on-site
2. Check that the item matches the description
3. Click **"Verify"** button to approve

**What happens after you verify:**
- Item moves to Project Manager for final authorization
- Pending quantity is not yet added to stock

---

## For Project Managers (Authorizers)

### In Authorization Dashboard

You'll see the same visual indicators:

```
┌─────────────────────────────────────────────────────┐
│ Item Name                                           │
│ Electrical Wire 2.0mm                               │
│ REF: ABC-123                                        │
│ [⚠️ Quantity Addition]  ← Yellow badge              │
├─────────────────────────────────────────────────────┤
│ Quantity                                            │
│ 50 meters                                           │
│ + 20 meters pending  ← Shows pending quantity       │
└─────────────────────────────────────────────────────┘
```

### Your Task

1. **Review** the quantity addition request
2. Ensure it aligns with project needs
3. Click **"Authorize"** button to approve

**What happens after you authorize:**
- Pending quantity is added to actual quantity
  - Before: 50 meters
  - After: 70 meters (50 + 20)
- Item status becomes "available"
- Quantity is ready for use

---

## Visual Indicators

### Yellow Badge
```
[⚠️ Quantity Addition]
```
Means: This is NOT a new item, just adding quantity to an existing item

### Quantity Display
```
50 meters
+ 20 meters pending
```
Means:
- **50 meters**: Currently approved and available
- **20 meters**: Pending approval through MVA workflow

---

## Workflow Diagram

```
[Warehouseman] Creates Item
         ↓
    Duplicate?
    ↙        ↘
   NO        YES
    ↓         ↓
Create New   Add Pending Quantity
Item         to Existing Item
    ↓         ↓
    └─────────┘
         ↓
[Site Inventory Clerk] Verifies
         ↓
[Project Manager] Authorizes
         ↓
    Quantity Added ✅
```

---

## Common Questions

### Q1: Why can't I create a new item for the same thing?
**A**: To prevent inventory clutter and maintain accurate stock counts. If the item already exists, we add to its quantity instead.

### Q2: What if I want a separate item?
**A**: Make sure it has:
- Different name, OR
- Different category, OR
- Different project, OR
- Different model number

### Q3: Can I see which items have pending quantities?
**A**: Yes! Items with pending quantities show:
- Yellow "Quantity Addition" badge
- Pending quantity in quantity column

### Q4: What happens if verification is rejected?
**A**: The pending quantity is removed and the item returns to its original state.

### Q5: Can I add quantities multiple times?
**A**: Yes! If you add 10 pcs today and 5 pcs tomorrow to the same item, both will accumulate as pending until authorized.

---

## Best Practices

### For Warehousemen
1. ✅ Check existing inventory before creating new items
2. ✅ Use consistent naming conventions
3. ✅ Specify model numbers when applicable
4. ✅ Double-check project selection

### For Verifiers
1. ✅ Physically verify quantity exists
2. ✅ Check item condition
3. ✅ Ensure storage location is correct
4. ✅ Add notes if needed

### For Authorizers
1. ✅ Review quantity additions carefully
2. ✅ Ensure alignment with project budget
3. ✅ Check for unusual quantity increases
4. ✅ Add authorization notes if needed

---

## Examples

### Example 1: Electrical Supplies

**Existing Item:**
- Name: "PVC Conduit 20mm"
- Category: Electrical Supplies
- Project: Site A Construction
- Current Quantity: 100 meters

**New Submission by Warehouseman:**
- Name: "PVC Conduit 20mm"
- Category: Electrical Supplies
- Project: Site A Construction
- Quantity: 50 meters

**Result:**
- ⚠️ Duplicate detected
- 50 meters added as pending
- Dashboard shows: "100 meters + 50 meters pending"
- After authorization: 150 meters total

---

### Example 2: Hand Tools

**Existing Item:**
- Name: "Hammer"
- Category: Hand Tools
- Project: Site A

**New Submission:**
- Name: "Hammer"
- Category: Power Tools  ← Different category
- Project: Site A

**Result:**
- ✅ NOT a duplicate (different category)
- New item is created
- Both items exist separately

---

## Troubleshooting

### Issue: "I don't see the pending quantity in the dashboard"
**Solution**: Refresh the page or check workflow status filter

### Issue: "The quantity wasn't added after authorization"
**Solution**: Contact system administrator - check activity logs

### Issue: "I need to change the pending quantity"
**Solution**: Contact the Warehouseman who submitted it for correction

---

## System Requirements

- **Database**: constructlink_db
- **Table**: inventory_items
- **Required Permissions**:
  - Warehouseman: Create legacy items
  - Site Inventory Clerk: Verify items
  - Project Manager: Authorize items

---

## Related Features

- **Restock Requests**: Different workflow for procurement-based restocks
- **Inventory Management**: View all items with quantities
- **Activity Logs**: Track all quantity changes

---

## Support

For technical issues or questions:
1. Check the activity logs
2. Review this quick reference
3. Contact your system administrator
4. Refer to full implementation documentation

---

**Last Updated**: 2025-11-11
**Feature Version**: 1.0
**System**: ConstructLink™
