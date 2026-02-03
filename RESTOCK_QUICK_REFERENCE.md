# Restock Workflow - Quick Reference Guide

## How to Use Restock Feature

### For Users (Site Inventory Clerk)

1. **Navigate to Requests**
   - Go to: `?route=requests/create`

2. **Create Restock Request**
   - Select Project (required)
   - Select Request Type: **"Restock"**
   - Inventory items will load automatically
   - Select the item to restock
   - View current stock levels (displayed automatically)
   - Enter quantity to add
   - Select reason for restock
   - Submit

3. **MVA Approval**
   - Request follows normal MVA workflow
   - Verifier reviews → Approves/Declines
   - Authorizer approves → Forwarded to Procurement

4. **Procurement & Delivery**
   - Procurement Officer creates PO
   - Vendor delivers items
   - Warehouse confirms receipt
   - **Quantity automatically added to existing item**

### For Developers

#### Database Structure
```sql
-- Restock Request
requests {
    request_type = 'Restock'
    is_restock = 1
    inventory_item_id = [item to restock]
    quantity = [amount to add]
}
```

#### Key Files
```
controllers/ProcurementOrderController.php
├── getLinkedRestockRequest($orderId)
├── processRestockDelivery($orderId, $request)
└── receiveOrder() - Modified

views/requests/create.php
├── Database-driven request types
├── Restock fields (lines 109-164)
└── JavaScript handlers (lines 338-435)

api/requests/inventory-items.php
└── Returns consumable items for project

services/RestockWorkflowService.php
└── processRestockDelivery($orderId, $requestId)
```

#### How It Works

**1. Request Creation**
```php
// Form submits with:
$_POST['request_type'] = 'Restock';
$_POST['inventory_item_id'] = 123;
$_POST['quantity'] = 50;
```

**2. Delivery Receipt**
```php
// ProcurementOrderController::receiveOrder()
$linkedRequest = $this->getLinkedRestockRequest($orderId);

if ($linkedRequest && $linkedRequest['is_restock'] == 1) {
    // RESTOCK PATH: Add to existing item
    $this->processRestockDelivery($orderId, $linkedRequest);
} else {
    // NEW ITEM PATH: Create new inventory records
    $this->generateAssets($orderId);
}
```

**3. Quantity Update**
```php
// RestockWorkflowService::processRestockDelivery()
// → AssetQuantityService::addQuantity()

UPDATE inventory_items
SET quantity = quantity + [restock_qty],
    available_quantity = available_quantity + [restock_qty]
WHERE id = [inventory_item_id];
```

#### API Usage
```javascript
// Get inventory items for project
fetch('api/requests/inventory-items.php?project_id=123')
    .then(response => response.json())
    .then(data => {
        // data.items = array of consumable items
        // data.statistics = stock level stats
    });
```

#### Database Views
```sql
-- Active restock requests
SELECT * FROM view_active_restock_requests;

-- Low stock items needing restock
SELECT * FROM view_low_stock_consumables;
```

## Troubleshooting

### Issue: Duplicate Assets Created
**Cause**: Restock request not properly flagged
**Fix**: Ensure `is_restock = 1` in requests table

### Issue: Inventory Items Not Loading
**Cause**: API endpoint permission issue or project not selected
**Fix**: Check user permissions, ensure project selected first

### Issue: Stock Levels Not Displaying
**Cause**: JavaScript not loading or item not selected
**Fix**: Check browser console, verify item has data attributes

## Testing Checklist

- [ ] Request type "Restock" appears in dropdown
- [ ] Selecting project loads inventory items
- [ ] Selecting item displays stock levels
- [ ] Quantity auto-suggests consumed amount
- [ ] Request submits successfully
- [ ] MVA workflow works normally
- [ ] PO creation works
- [ ] Delivery receipt adds quantity (not creates duplicate)
- [ ] Request status updates to "Procured"
- [ ] Activity logged correctly

## Common Queries

```sql
-- Get all restock requests
SELECT * FROM requests
WHERE request_type = 'Restock'
  AND is_restock = 1;

-- Get restocks for specific project
SELECT r.*, i.name AS item_name, i.ref AS item_ref
FROM requests r
JOIN inventory_items i ON r.inventory_item_id = i.id
WHERE r.project_id = ?
  AND r.is_restock = 1;

-- Get items needing restock
SELECT * FROM view_low_stock_consumables
WHERE project_id = ?;

-- Check restock delivery status
SELECT r.*, po.po_number, po.status AS po_status
FROM requests r
LEFT JOIN procurement_orders po ON r.procurement_id = po.id
WHERE r.is_restock = 1;
```

## Key Points

1. **No Duplicate Assets**: Restock adds to existing inventory_items record
2. **Database-Driven**: Request types from ENUM, no hardcoding
3. **Real-Time Data**: Stock levels calculated on-the-fly
4. **Secure**: Parameterized queries, XSS protection
5. **MVA Compliant**: Follows standard approval workflow

## Support

For issues or questions:
1. Check `/RESTOCK_WORKFLOW_IMPLEMENTATION_COMPLETE.md` for full details
2. Review error logs: `/Applications/XAMPP/xamppfiles/logs/`
3. Check database structure: `DESCRIBE requests;`
4. Verify views exist: `SHOW FULL TABLES WHERE Table_type = 'VIEW';`
