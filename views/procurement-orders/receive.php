<?php
// Prevent direct access
if (!defined('APP_ROOT')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Start output buffering
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$userRole = $user['role_name'] ?? 'Guest';
$canReceive = in_array($userRole, $roleConfig['procurement-orders/receive'] ?? []) && ($procurementOrder['status'] === 'Delivered');
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
        <div class="col-lg-8">
            <!-- Receive Form -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clipboard-check me-2"></i>Receive Items & Quality Check
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($procurementOrder['status'] === 'Received'): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Notice:</strong> This procurement order has already been received.
                        </div>
                    <?php elseif (!in_array($procurementOrder['status'], ['Approved', 'Scheduled for Delivery', 'In Transit', 'Delivered'])): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Notice:</strong> This procurement order is not in a valid status for receiving. Current status: <?= htmlspecialchars($procurementOrder['status']) ?>
                        </div>
                    <?php else: ?>

                        <?php if ($canReceive): ?>
                            <form method="POST" action="?route=procurement-orders/receive&id=<?= htmlspecialchars($procurementOrder['id']) ?>" id="receiveForm">
                                <?= CSRFProtection::getTokenField() ?>
                                
                                <!-- Items List -->
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th class="text-center">Ordered Qty</th>
                                                <th class="text-center">Unit Price</th>
                                                <th class="text-center">Received Qty</th>
                                                <th>Quality Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($procurementOrder['items'])): ?>
                                                <?php foreach ($procurementOrder['items'] as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-medium"><?= htmlspecialchars($item['item_name']) ?></div>
                                                            <?php if (!empty($item['description'])): ?>
                                                                <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                                            <?php endif; ?>
                                                            <?php if (!empty($item['specifications'])): ?>
                                                                <br><small class="text-info">Specs: <?= htmlspecialchars($item['specifications']) ?></small>
                                                            <?php endif; ?>
                                                            <?php if (!empty($item['model']) || !empty($item['brand'])): ?>
                                                                <br><small class="text-secondary">
                                                                    <?= htmlspecialchars($item['brand'] ?? '') ?>
                                                                    <?= !empty($item['brand']) && !empty($item['model']) ? ' - ' : '' ?>
                                                                    <?= htmlspecialchars($item['model'] ?? '') ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary"><?= number_format($item['quantity']) ?></span>
                                                            <br><small class="text-muted"><?= htmlspecialchars($item['unit']) ?></small>
                                                            <?php 
                                                            $previouslyReceived = $item['quantity_received'] ?? 0;
                                                            $remainingQty = $item['quantity'] - $previouslyReceived;
                                                            if ($previouslyReceived > 0): ?>
                                                                <br><small class="text-info"><?= $previouslyReceived ?> already received</small>
                                                                <br><small class="text-success fw-bold">Remaining: <?= $remainingQty ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            ₱<?= number_format($item['unit_price'], 2) ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php 
                                                            $previouslyReceived = $item['quantity_received'] ?? 0;
                                                            $remainingQty = $item['quantity'] - $previouslyReceived;
                                                            $maxReceivable = $remainingQty;
                                                            ?>
                                                            <input type="number" 
                                                                   name="items[<?= $item['id'] ?>][quantity_received]" 
                                                                   class="form-control form-control-sm text-center" 
                                                                   min="0" 
                                                                   max="<?= $maxReceivable ?>" 
                                                                   value="0"
                                                                   placeholder="Max: <?= $maxReceivable ?>"
                                                                   style="width: 100px;">
                                                            <input type="hidden" name="items[<?= $item['id'] ?>][quantity]" value="<?= $item['quantity'] ?>">
                                                            <input type="hidden" name="items[<?= $item['id'] ?>][previously_received]" value="<?= $previouslyReceived ?>">
                                                            <?php if ($previouslyReceived > 0): ?>
                                                                <small class="text-muted d-block mt-1">Previously: <?= $previouslyReceived ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <textarea name="items[<?= $item['id'] ?>][quality_notes]" 
                                                                      class="form-control form-control-sm" 
                                                                      rows="2" 
                                                                      placeholder="Quality check notes..."><?= htmlspecialchars($item['quality_notes'] ?? '') ?></textarea>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="bi bi-inbox display-4 mb-2"></i>
                                                        <br>No items found in this procurement order.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Overall Quality Check Notes -->
                                <div class="mb-4">
                                    <label for="quality_check_notes" class="form-label">
                                        <i class="bi bi-clipboard-check me-1"></i>Overall Quality Check Notes
                                    </label>
                                    <textarea name="quality_check_notes" 
                                              id="quality_check_notes" 
                                              class="form-control" 
                                              rows="4" 
                                              placeholder="Enter overall quality check notes, delivery condition, packaging status, etc..."><?= htmlspecialchars($procurementOrder['quality_check_notes'] ?? '') ?></textarea>
                                    <div class="form-text">
                                        Document the overall condition of the delivery, packaging quality, and any general observations.
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between">
                                    <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-1"></i>Confirm Receipt
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger mt-4">You do not have permission to receive this procurement order.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
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

                        <dt class="col-sm-5">Title:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['title']) ?></dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <?php
                            $statusClasses = [
                                'Draft' => 'bg-secondary',
                                'Pending' => 'bg-warning',
                                'Reviewed' => 'bg-info',
                                'Approved' => 'bg-success',
                                'Rejected' => 'bg-danger',
                                'Received' => 'bg-primary'
                            ];
                            $statusClass = $statusClasses[$procurementOrder['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $statusClass ?>">
                                <?= htmlspecialchars($procurementOrder['status']) ?>
                            </span>
                        </dd>

                        <dt class="col-sm-5">Vendor:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['vendor_name']) ?></dd>
                    </dl>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Approved By:</dt>
                                <dd class="col-sm-7"><?= isset($procurementOrder['approved_by_name']) && $procurementOrder['approved_by_name'] ? htmlspecialchars($procurementOrder['approved_by_name']) : 'Not approved yet' ?></dd>
                                
                                <dt class="col-sm-5">Expected Delivery:</dt>
                                <dd class="col-sm-7">
                                    <?php if (!empty($procurementOrder['scheduled_delivery_date'])): ?>
                                        <?= date('M j, Y', strtotime($procurementOrder['scheduled_delivery_date'])) ?>
                                    <?php elseif (!empty($procurementOrder['date_needed'])): ?>
                                        <?= date('M j, Y', strtotime($procurementOrder['date_needed'])) ?> <small class="text-muted">(Date Needed)</small>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </dd>
                                
                                <dt class="col-sm-5">Net Total:</dt>
                                <dd class="col-sm-7">
                                    <strong class="text-success">₱<?= number_format($procurementOrder['net_total'], 2) ?></strong>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Project:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($procurementOrder['project_name']) ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-5">Requested By:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['requested_by_name']) ?></dd>

                                <dt class="col-sm-5">Request Date:</dt>
                                <dd class="col-sm-7"><?= date('M j, Y', strtotime($procurementOrder['created_at'])) ?></dd>
                            </dl>
                        </div>
                    </div>

                    <?php if (!empty($procurementOrder['notes'])): ?>
                        <hr>
                        <div>
                            <h6>Notes:</h6>
                            <p class="text-muted small"><?= nl2br(htmlspecialchars($procurementOrder['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Receiving Guidelines -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Receiving Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Verify item quantities against delivery receipt
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Inspect items for damage or defects
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Check specifications and model numbers
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Document any discrepancies or issues
                        </li>
                        <li class="mb-0">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Take photos if items are damaged
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const receiveForm = document.getElementById('receiveForm');
    if (receiveForm) {
        receiveForm.addEventListener('submit', function(e) {
            let hasReceivedItems = false;
            const quantityInputs = document.querySelectorAll('input[name*="[quantity_received]"]');
            
            quantityInputs.forEach(function(input) {
                if (parseInt(input.value) > 0) {
                    hasReceivedItems = true;
                }
            });
            
            if (!hasReceivedItems) {
                e.preventDefault();
                alert('Please specify received quantities for at least one item.');
                return false;
            }
            
            // Confirm submission
            if (!confirm('Are you sure you want to confirm receipt of this procurement order? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Auto-fill received quantities
    const autoFillBtn = document.createElement('button');
    autoFillBtn.type = 'button';
    autoFillBtn.className = 'btn btn-outline-primary btn-sm mb-3';
    autoFillBtn.innerHTML = '<i class="bi bi-magic me-1"></i>Auto-fill Remaining Quantities';
    autoFillBtn.addEventListener('click', function() {
        const quantityInputs = document.querySelectorAll('input[name*="[quantity_received]"]');
        quantityInputs.forEach(function(input) {
            const maxQty = parseInt(input.getAttribute('max'));
            input.value = maxQty;
        });
    });
    
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.parentNode.insertBefore(autoFillBtn, tableContainer);
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
