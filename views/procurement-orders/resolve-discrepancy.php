<?php
/**
 * ConstructLink™ Resolve Discrepancy View
 * Resolve delivery discrepancies for procurement orders
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$userRole = $user['role_name'] ?? 'Guest';
$canResolve = in_array($userRole, $roleConfig['procurement-orders/resolve-discrepancy'] ?? []) && ($procurementOrder['status'] === 'Received' && $procurementOrder['delivery_status'] === 'Partial');

// Get items with unresolved discrepancies for this order
$procurementItemModel = new ProcurementItemModel();
$unresolvedItems = $procurementItemModel->getItemsWithUnresolvedDiscrepancies($procurementOrder['id']);
$discrepancySummary = $procurementItemModel->getDiscrepancySummary($procurementOrder['id']);
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Display Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <?php if ($canResolve): ?>
        
        <!-- Discrepancy Summary -->
        <?php if ($discrepancySummary['unresolved_discrepancies'] > 0): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Items with Discrepancies:</strong> <?= $discrepancySummary['unresolved_discrepancies'] ?> of <?= $discrepancySummary['total_items'] ?> items have unresolved discrepancies.
        </div>
        <?php endif; ?>

        <!-- Item-Level Discrepancy Resolution -->
        <?php if (!empty($unresolvedItems)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Item-Level Discrepancy Resolution
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=procurement-orders/resolve-item-discrepancy&id=<?= $procurementOrder['id'] ?>" id="itemDiscrepancyForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Ordered</th>
                                    <th>Received</th>
                                    <th>Discrepancy</th>
                                    <th width="120">Action</th>
                                    <th width="200">Resolution Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unresolvedItems as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                        <?php if ($item['category_name']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($item['category_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($item['quantity']) ?> <?= htmlspecialchars($item['unit'] ?? 'pcs') ?></td>
                                    <td>
                                        <?= number_format($item['quantity_received'] ?? 0) ?> <?= htmlspecialchars($item['unit'] ?? 'pcs') ?>
                                        <?php if ($item['quantity_received'] < $item['quantity']): ?>
                                        <br><small class="text-danger">Short: <?= number_format($item['quantity'] - $item['quantity_received']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['discrepancy_type']): ?>
                                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($item['discrepancy_type']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($item['discrepancy_notes']): ?>
                                        <br><small><?= htmlspecialchars($item['discrepancy_notes']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="resolve_items[]" value="<?= $item['id'] ?>" id="resolve_<?= $item['id'] ?>">
                                            <label class="form-check-label" for="resolve_<?= $item['id'] ?>">
                                                <small>Resolve</small>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <textarea name="item_resolution_notes[<?= $item['id'] ?>]" class="form-control form-control-sm" rows="2" 
                                                  placeholder="Resolution details..." disabled></textarea>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllItems">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllItems">Clear All</button>
                        </div>
                        <button type="submit" class="btn btn-primary" id="resolveSelectedItems" disabled>
                            <i class="bi bi-check2-all me-1"></i>Resolve Selected Items
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order-Level Resolution (Legacy Support) -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-check2-all me-2"></i>Order-Level Resolution for PO #<?= htmlspecialchars($procurementOrder['po_number']) ?>
                </h6>
                <small class="text-muted">Use this for overall order discrepancy resolution or when item-level resolution is not applicable</small>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=procurement-orders/resolve-discrepancy&id=<?= $procurementOrder['id'] ?>" id="resolveDiscrepancyForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Resolution Notes -->
                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label">Resolution Notes <span class="text-danger">*</span></label>
                        <textarea name="resolution_notes" id="resolution_notes" class="form-control" rows="4" 
                                  placeholder="Describe how the discrepancy was resolved..." required></textarea>
                        <div class="form-text">
                            Provide detailed information about how the discrepancy was addressed, actions taken, and any follow-up required.
                        </div>
                    </div>
                    
                    <!-- Resolution Action -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info bg-opacity-10">
                            <h6 class="card-title mb-0 text-info">
                                <i class="bi bi-gear me-2"></i>Resolution Action
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">What action should be taken? <span class="text-danger">*</span></label>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="resolution_action" id="action_document" value="document_only" checked>
                                    <label class="form-check-label" for="action_document">
                                        <strong>Document Only</strong>
                                        <div class="form-text">Record resolution notes only, no status change</div>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="resolution_action" id="action_reschedule" value="reschedule_delivery">
                                    <label class="form-check-label" for="action_reschedule">
                                        <strong>Re-schedule for Re-delivery</strong>
                                        <div class="form-text">Vendor will re-deliver missing/defective items</div>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="resolution_action" id="action_complete" value="mark_complete">
                                    <label class="form-check-label" for="action_complete">
                                        <strong>Accept as Complete</strong>
                                        <div class="form-text">Accept partial delivery as final (vendor credit/refund arranged)</div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Note:</strong> 
                                <span id="action-note">Choose the appropriate resolution action based on vendor agreement.</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check2-all me-1"></i>Resolve Order Discrepancy
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger mt-4">You do not have permission to resolve discrepancies for this procurement order.</div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Order Summary -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Order Summary
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">PO Number:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['po_number']) ?></dd>
                    
                    <dt class="col-sm-5">Vendor:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['vendor_name']) ?></dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['project_name']) ?></dd>
                    
                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge <?= getDeliveryStatusBadgeClass($procurementOrder['delivery_status']) ?>">
                            <?= htmlspecialchars($procurementOrder['delivery_status']) ?>
                        </span>
                    </dd>
                    
                    <?php if (!empty($procurementOrder['delivery_discrepancy_notes'])): ?>
                    <dt class="col-sm-5">Discrepancy:</dt>
                    <dd class="col-sm-7">
                        <div class="alert alert-warning alert-sm mb-0">
                            <small><?= htmlspecialchars($procurementOrder['delivery_discrepancy_notes']) ?></small>
                        </div>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- Resolution Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Resolution Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Resolution Steps:</h6>
                <ol class="small">
                    <li>Document the resolution actions taken</li>
                    <li>Include any vendor communication</li>
                    <li>Note any replacement items received</li>
                    <li>Update status if appropriate</li>
                    <li>Set follow-up reminders if needed</li>
                </ol>
                
                <h6 class="mt-3">Important Notes:</h6>
                <ul class="small">
                    <li>Be specific about resolution actions</li>
                    <li>Include any cost implications</li>
                    <li>Document lessons learned</li>
                    <li>Update stakeholders as needed</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Item-level resolution functionality
document.addEventListener('DOMContentLoaded', function() {
    const itemCheckboxes = document.querySelectorAll('input[name="resolve_items[]"]');
    const resolveButton = document.getElementById('resolveSelectedItems');
    const selectAllBtn = document.getElementById('selectAllItems');
    const clearAllBtn = document.getElementById('clearAllItems');
    
    // Enable/disable resolution notes based on checkbox selection
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const itemId = this.value;
            const notesTextarea = document.querySelector(`textarea[name="item_resolution_notes[${itemId}]"]`);
            
            if (this.checked) {
                notesTextarea.disabled = false;
                notesTextarea.required = true;
            } else {
                notesTextarea.disabled = true;
                notesTextarea.required = false;
                notesTextarea.value = '';
            }
            
            updateResolveButton();
        });
    });
    
    // Update resolve button state
    function updateResolveButton() {
        const checkedItems = document.querySelectorAll('input[name="resolve_items[]"]:checked');
        resolveButton.disabled = checkedItems.length === 0;
    }
    
    // Select all items
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change'));
            });
        });
    }
    
    // Clear all items
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            });
        });
    }
    
    // Item-level form validation
    const itemForm = document.getElementById('itemDiscrepancyForm');
    if (itemForm) {
        itemForm.addEventListener('submit', function(e) {
            const checkedItems = document.querySelectorAll('input[name="resolve_items[]"]:checked');
            
            if (checkedItems.length === 0) {
                e.preventDefault();
                alert('Please select at least one item to resolve.');
                return false;
            }
            
            // Check that all selected items have resolution notes
            let hasEmptyNotes = false;
            checkedItems.forEach(checkbox => {
                const itemId = checkbox.value;
                const notesTextarea = document.querySelector(`textarea[name="item_resolution_notes[${itemId}]"]`);
                
                if (!notesTextarea.value.trim()) {
                    hasEmptyNotes = true;
                }
            });
            
            if (hasEmptyNotes) {
                e.preventDefault();
                alert('Please provide resolution notes for all selected items.');
                return false;
            }
            
            return true;
        });
    }
});

// Update action note based on selection
document.querySelectorAll('input[name="resolution_action"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const actionNote = document.getElementById('action-note');
        switch(this.value) {
            case 'document_only':
                actionNote.textContent = 'Resolution will be documented without changing order status.';
                break;
            case 'reschedule_delivery':
                actionNote.textContent = 'Order will return to "Approved" status for re-scheduling delivery.';
                break;
            case 'mark_complete':
                actionNote.textContent = 'Order will be marked as complete with partial delivery accepted.';
                break;
        }
    });
});

// Order-level form validation
document.getElementById('resolveDiscrepancyForm').addEventListener('submit', function(e) {
    const resolutionNotes = document.getElementById('resolution_notes').value.trim();
    const selectedAction = document.querySelector('input[name="resolution_action"]:checked');
    
    if (!resolutionNotes) {
        e.preventDefault();
        alert('Please provide resolution notes.');
        return false;
    }
    
    if (!selectedAction) {
        e.preventDefault();
        alert('Please select a resolution action.');
        return false;
    }
    
    return true;
});
</script>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?> 