<?php
/**
 * ConstructLink™ Confirm Receipt View
 * Confirm receipt of delivered procurement orders
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$userRole = $user['role_name'] ?? 'Guest';
$canConfirmReceipt = in_array($userRole, $roleConfig['procurement-orders/confirm-receipt'] ?? []) && ($procurementOrder['status'] === 'Delivered');
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-check-square me-2"></i>
        Confirm Receipt
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Order
        </a>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/confirm-receipt'] ?? [])): ?>
        <a href="?route=procurement-orders/confirm-receipt&id=<?= $procurementOrder['id'] ?>" class="btn btn-success ms-2">
            <i class="bi bi-check-square me-1"></i>Confirm Receipt
        </a>
        <?php endif; ?>
    </div>
</div>

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
        <?php if ($canConfirmReceipt): ?>
        <!-- Confirm Receipt Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Confirm Receipt for PO #<?= htmlspecialchars($procurementOrder['po_number']) ?>
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=procurement-orders/confirm-receipt&id=<?= $procurementOrder['id'] ?>" id="confirmReceiptForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Receipt Confirmation -->
                    <div class="mb-4">
                        <h6>Receipt Confirmation</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="has_discrepancy" id="no_discrepancy" value="no" checked>
                            <label class="form-check-label" for="no_discrepancy">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                All items received as expected (no discrepancies)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="has_discrepancy" id="has_discrepancy" value="yes">
                            <label class="form-check-label" for="has_discrepancy">
                                <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                                There are discrepancies with the delivered items
                            </label>
                        </div>
                    </div>
                    
                    <!-- Discrepancy Details (hidden by default) -->
                    <div id="discrepancy_section" style="display: none;">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning bg-opacity-10">
                                <h6 class="card-title mb-0 text-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Discrepancy Details
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="discrepancy_type" class="form-label">Type of Discrepancy</label>
                                    <select name="discrepancy_type" id="discrepancy_type" class="form-select">
                                        <option value="">Select discrepancy type</option>
                                        <?php foreach (getDiscrepancyTypeOptions() as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discrepancy_details" class="form-label">Discrepancy Details</label>
                                    <textarea name="discrepancy_details" id="discrepancy_details" class="form-control" rows="4" 
                                              placeholder="Describe the discrepancy in detail..."></textarea>
                                    <div class="form-text">
                                        Provide specific details about what was wrong, quantities affected, etc.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quality Check -->
                    <div class="mb-3">
                        <label for="quality_notes" class="form-label">Quality Check Notes</label>
                        <textarea name="quality_notes" id="quality_notes" class="form-control" rows="3" 
                                  placeholder="Enter quality inspection notes..."></textarea>
                        <div class="form-text">
                            Document the condition of received items, any quality observations, etc.
                        </div>
                    </div>
                    
                    <!-- Receipt Notes -->
                    <div class="mb-3">
                        <label for="receipt_notes" class="form-label">Receipt Notes</label>
                        <textarea name="receipt_notes" id="receipt_notes" class="form-control" rows="3" 
                                  placeholder="Enter any additional notes about the receipt..."></textarea>
                        <div class="form-text">
                            Include any additional information about the receipt process, storage location, etc.
                        </div>
                    </div>
                    
                    <!-- Asset Generation Option -->
                    <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/confirm-receipt'] ?? [])): ?>
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info bg-opacity-10">
                            <h6 class="card-title mb-0 text-info">
                                <i class="bi bi-box-seam me-2"></i>Asset Generation
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="generate_assets" id="generate_assets" value="1" checked>
                                <label class="form-check-label" for="generate_assets">
                                    <strong>Automatically generate assets from received items</strong>
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                When checked, the system will automatically create asset records for all received items. 
                                This is recommended for trackable items like equipment, tools, and materials.
                            </div>
                            <?php if (in_array($user['role_name'], $roleConfig['warehouseman'] ?? [])): ?>
                            <div class="alert alert-warning mt-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>Note:</strong> As a Warehouseman, you can generate assets during receipt confirmation.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-square me-1"></i>Confirm Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger mt-4">You do not have permission to confirm receipt for this procurement order.</div>
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
                    
                    <dt class="col-sm-5">Total Amount:</dt>
                    <dd class="col-sm-7">₱<?= number_format($procurementOrder['net_total'], 2) ?></dd>
                    
                    <dt class="col-sm-5">Delivery Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-success"><?= htmlspecialchars($procurementOrder['delivery_status']) ?></span>
                    </dd>
                    
                    <?php if (!empty($procurementOrder['actual_delivery_date'])): ?>
                    <dt class="col-sm-5">Delivered On:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($procurementOrder['actual_delivery_date'])) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['tracking_number'])): ?>
                    <dt class="col-sm-5">Tracking:</dt>
                    <dd class="col-sm-7"><code><?= htmlspecialchars($procurementOrder['tracking_number']) ?></code></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- Items Summary -->
        <?php if (isset($procurementOrder['items']) && !empty($procurementOrder['items'])): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>Items to Receive
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($procurementOrder['items'] as $item): ?>
                            <tr>
                                <td>
                                    <small><?= htmlspecialchars($item['item_name']) ?></small>
                                </td>
                                <td><?= number_format($item['quantity']) ?></td>
                                <td><?= htmlspecialchars($item['unit']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Receipt Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Receipt Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Before Confirming:</h6>
                <ul class="small">
                    <li>Verify all items against the PO</li>
                    <li>Check quantities and specifications</li>
                    <li>Inspect for damage or defects</li>
                    <li>Confirm item quality and condition</li>
                </ul>
                
                <h6 class="mt-3">If Discrepancies Found:</h6>
                <ul class="small">
                    <li>Document all issues clearly</li>
                    <li>Take photos if necessary</li>
                    <li>Contact procurement officer</li>
                    <li>Store items securely pending resolution</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide discrepancy section
document.querySelectorAll('input[name="has_discrepancy"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const discrepancySection = document.getElementById('discrepancy_section');
        const discrepancyType = document.getElementById('discrepancy_type');
        const discrepancyDetails = document.getElementById('discrepancy_details');
        
        if (this.value === 'yes') {
            discrepancySection.style.display = 'block';
            discrepancyType.required = true;
            discrepancyDetails.required = true;
        } else {
            discrepancySection.style.display = 'none';
            discrepancyType.required = false;
            discrepancyDetails.required = false;
            discrepancyType.value = '';
            discrepancyDetails.value = '';
        }
    });
});

// Form validation
document.getElementById('confirmReceiptForm').addEventListener('submit', function(e) {
    const hasDiscrepancy = document.querySelector('input[name="has_discrepancy"]:checked').value;
    const discrepancyType = document.getElementById('discrepancy_type').value;
    const discrepancyDetails = document.getElementById('discrepancy_details').value;
    
    if (hasDiscrepancy === 'yes') {
        if (!discrepancyType) {
            e.preventDefault();
            alert('Please select the type of discrepancy.');
            return false;
        }
        
        if (!discrepancyDetails.trim()) {
            e.preventDefault();
            alert('Please provide details about the discrepancy.');
            return false;
        }
    }
    
    // Confirmation
    const confirmMessage = hasDiscrepancy === 'yes' 
        ? 'Are you sure you want to confirm receipt with discrepancies? This will require follow-up action.'
        : 'Are you sure you want to confirm receipt of all items?';
        
    if (!confirm(confirmMessage)) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Confirm Receipt - ConstructLink™';
$pageHeader = 'Confirm Receipt for PO #' . htmlspecialchars($procurementOrder['po_number']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'View Order', 'url' => '?route=procurement-orders/view&id=' . $procurementOrder['id']],
    ['title' => 'Confirm Receipt', 'url' => '?route=procurement-orders/confirm-receipt&id=' . $procurementOrder['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
