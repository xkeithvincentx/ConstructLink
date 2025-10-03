<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please correct the following errors:</strong>
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
                    <i class="bi bi-box-arrow-in-down me-2"></i>Delivery Confirmation
                </h6>
            </div>
            <div class="card-body">
                <!-- Delivery Instructions -->
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading">
                        <i class="bi bi-info-circle me-2"></i>Delivery Confirmation Process
                    </h6>
                    <ul class="mb-0">
                        <li>Verify all items have been delivered as per the purchase order</li>
                        <li>Check item condition and quality before acceptance</li>
                        <li>Record serial numbers for trackable items</li>
                        <li>Assets will be automatically created upon confirmation</li>
                    </ul>
                </div>
                
                <!-- Order Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Order Information</h6>
                                <p class="mb-1"><strong>PO Number:</strong> <?= htmlspecialchars($procurement['po_number']) ?></p>
                                <p class="mb-1"><strong>Vendor:</strong> <?= htmlspecialchars($procurement['vendor_name']) ?></p>
                                <p class="mb-1"><strong>Project:</strong> <?= htmlspecialchars($procurement['project_name']) ?></p>
                                <p class="mb-0"><strong>Status:</strong> 
                                    <span class="badge bg-success"><?= htmlspecialchars($procurement['status']) ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Item Details</h6>
                                <p class="mb-1"><strong>Item:</strong> <?= htmlspecialchars($procurement['item_name']) ?></p>
                                <p class="mb-1"><strong>Quantity:</strong> <?= number_format($procurement['quantity']) ?> <?= htmlspecialchars($procurement['unit'] ?? 'pcs') ?></p>
                                <p class="mb-1"><strong>Expected:</strong> <?= $procurement['date_needed'] ? date('M j, Y', strtotime($procurement['date_needed'])) : 'ASAP' ?></p>
                                <p class="mb-0"><strong>Value:</strong> ₱<?= number_format($procurement['net_total'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Receive Form -->
                <form method="POST" action="?route=procurement/receive&id=<?= $procurement['id'] ?>" id="receiveForm">
                    <?= CSRFProtection::generateToken() ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="model" class="form-label">Model/Specification</label>
                                <input type="text" class="form-control" id="model" name="model" 
                                       placeholder="Enter model or specification details">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Serial Numbers Section -->
                    <div class="mb-4">
                        <h6>Asset Information</h6>
                        <p class="text-muted small">
                            <?= number_format($procurement['quantity']) ?> asset(s) will be created. 
                            Provide serial numbers if available for better tracking.
                        </p>
                        
                        <div id="serial-numbers-container">
                            <?php for ($i = 1; $i <= min($procurement['quantity'], 10); $i++): ?>
                                <div class="row mb-2">
                                    <div class="col-md-2">
                                        <label class="form-label small">Asset #<?= $i ?></label>
                                    </div>
                                    <div class="col-md-10">
                                        <input type="text" class="form-control form-control-sm" 
                                               name="serial_number_<?= $i ?>" 
                                               placeholder="Serial number (optional)">
                                    </div>
                                </div>
                            <?php endfor; ?>
                            
                            <?php if ($procurement['quantity'] > 10): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Only showing first 10 items. Remaining <?= $procurement['quantity'] - 10 ?> assets will be created without serial numbers.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Delivery Notes -->
                    <div class="mb-3">
                        <label for="delivery_notes" class="form-label">Delivery Notes</label>
                        <textarea class="form-control" id="delivery_notes" name="delivery_notes" rows="4" 
                                  placeholder="Record any observations about the delivery, item condition, or special notes..."></textarea>
                    </div>
                    
                    <!-- Quality Check -->
                    <div class="mb-4">
                        <h6>Quality Verification</h6>
                        <div class="list-group">
                            <div class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-quantity" required>
                                    <label class="form-check-label" for="check-quantity">
                                        Quantity delivered matches purchase order
                                    </label>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-condition" required>
                                    <label class="form-check-label" for="check-condition">
                                        Items are in good condition and undamaged
                                    </label>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-specs" required>
                                    <label class="form-check-label" for="check-specs">
                                        Items meet specifications as ordered
                                    </label>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-complete" required>
                                    <label class="form-check-label" for="check-complete">
                                        Delivery is complete and ready for asset creation
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?route=procurement/view&id=<?= $procurement['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Details
                        </a>
                        <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                            <i class="bi bi-check-circle me-1"></i>Confirm Receipt & Create Assets
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Asset Creation Preview -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Assets to be Created
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="bi bi-boxes display-4 text-primary"></i>
                    <h4 class="mt-2"><?= number_format($procurement['quantity']) ?></h4>
                    <p class="text-muted">Asset(s) will be created</p>
                </div>
                
                <div class="mb-3">
                    <strong>Asset Details:</strong>
                    <ul class="list-unstyled mt-2">
                        <li><small><strong>Name:</strong> <?= htmlspecialchars($procurement['item_name']) ?></small></li>
                        <li><small><strong>Project:</strong> <?= htmlspecialchars($procurement['project_name']) ?></small></li>
                        <li><small><strong>Vendor:</strong> <?= htmlspecialchars($procurement['vendor_name']) ?></small></li>
                        <li><small><strong>Unit Cost:</strong> ₱<?= number_format($procurement['unit_price'], 2) ?></small></li>
                        <li><small><strong>Status:</strong> Available</small></li>
                    </ul>
                </div>
                
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>
                        Assets will be automatically assigned reference numbers and 
                        made available for withdrawal upon confirmation.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Vendor Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-building me-2"></i>Vendor Information
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong><?= htmlspecialchars($procurement['vendor_name']) ?></strong>
                </div>
                <?php if ($procurement['vendor_contact']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Contact:</small> <?= htmlspecialchars($procurement['vendor_contact']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($procurement['vendor_phone']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Phone:</small> 
                        <a href="tel:<?= htmlspecialchars($procurement['vendor_phone']) ?>">
                            <?= htmlspecialchars($procurement['vendor_phone']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Delivery Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Delivery Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <small>Verify delivery receipt/invoice</small>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <small>Inspect items for damage</small>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <small>Record serial numbers when available</small>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <small>Note any discrepancies</small>
                    </li>
                    <li class="mb-0">
                        <i class="bi bi-info-circle text-info me-2"></i>
                        <small>Assets become available immediately</small>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][required]');
    const submitBtn = document.getElementById('submit-btn');
    
    // Update submit button state
    function updateSubmitButton() {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        submitBtn.disabled = !allChecked;
    }
    
    // Event listeners for checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSubmitButton);
    });
    
    // Form submission confirmation
    document.getElementById('receiveForm').addEventListener('submit', function(e) {
        const quantity = <?= $procurement['quantity'] ?>;
        const itemName = '<?= addslashes($procurement['item_name']) ?>';
        
        const confirmMessage = `Are you sure you want to confirm receipt of ${quantity} unit(s) of "${itemName}"?\n\n` +
                              `This will:\n` +
                              `• Mark the procurement as delivered\n` +
                              `• Create ${quantity} asset(s) in the system\n` +
                              `• Make the assets available for use\n\n` +
                              `This action cannot be undone.`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-focus on delivery date
    document.getElementById('delivery_date').focus();
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Receive Procurement Order - ConstructLink™';
$pageHeader = 'Receive Procurement Order #' . ($procurement['po_number'] ?? 'N/A');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement', 'url' => '?route=procurement'],
    ['title' => 'Order Details', 'url' => '?route=procurement/view&id=' . ($procurement['id'] ?? '')],
    ['title' => 'Receive', 'url' => '?route=procurement/receive&id=' . ($procurement['id'] ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
