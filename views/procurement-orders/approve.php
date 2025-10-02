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
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-check-circle me-2"></i>
        Approve Procurement Order
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders/view&id=<?= htmlspecialchars($procurementOrder['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Order Details
        </a>
    </div>
</div>

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
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Order Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">PO Number:</dt>
                                <dd class="col-sm-7">
                                    <span class="fw-medium"><?= htmlspecialchars($procurementOrder['po_number'] ?? 'DRAFT-' . $procurementOrder['id']) ?></span>
                                </dd>
                                
                                <dt class="col-sm-5">Title:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['title']) ?></dd>
                                
                                <dt class="col-sm-5">Vendor:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['vendor_name']) ?></dd>
                                
                                <dt class="col-sm-5">Project:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($procurementOrder['project_name']) ?>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                        
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Requested By:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['requested_by_name']) ?></dd>
                                
                                <dt class="col-sm-5">Request Date:</dt>
                                <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($procurementOrder['created_at'])) ?></dd>
                                
                                <dt class="col-sm-5">Total Items:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-info"><?= count($procurementOrder['items']) ?> items</span>
                                </dd>
                                
                                <dt class="col-sm-5">Net Total:</dt>
                                <dd class="col-sm-7">
                                    <span class="fw-bold text-primary">₱<?= number_format($procurementOrder['net_total'], 2) ?></span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    
                    <?php if (!empty($procurementOrder['justification'])): ?>
                        <div class="mt-3">
                            <h6>Justification:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($procurementOrder['justification'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Delivery Information -->
                    <?php if (!empty($procurementOrder['date_needed']) || !empty($procurementOrder['delivery_method']) || !empty($procurementOrder['delivery_location'])): ?>
                        <div class="mt-4">
                            <h6><i class="bi bi-truck me-2"></i>Delivery Requirements:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <?php if (!empty($procurementOrder['date_needed'])): ?>
                                        <small class="text-muted d-block">Date Needed:</small>
                                        <span class="fw-medium"><?= date('M j, Y', strtotime($procurementOrder['date_needed'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <?php if (!empty($procurementOrder['delivery_method'])): ?>
                                        <?php
                                        $serviceDeliveryMethods = ['On-site Service', 'Remote Service', 'Digital Delivery', 'Email Delivery', 'Postal Mail', 'Office Pickup', 'Service Completion', 'N/A'];
                                        $isServiceDelivery = in_array($procurementOrder['delivery_method'], $serviceDeliveryMethods);
                                        $deliveryLabel = $isServiceDelivery ? 'Service Method:' : 'Delivery Method:';
                                        ?>
                                        <small class="text-muted d-block"><?= $deliveryLabel ?></small>
                                        <span class="fw-medium"><?= htmlspecialchars($procurementOrder['delivery_method']) ?></span>
                                        <?php if ($isServiceDelivery): ?>
                                            <small class="badge bg-info ms-1">Service</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <?php if (!empty($procurementOrder['delivery_location'])): ?>
                                        <?php
                                        $serviceLocations = ['Client Office', 'Service Provider Office', 'Digital/Email', 'Multiple Locations', 'N/A'];
                                        $isServiceLocation = in_array($procurementOrder['delivery_location'], $serviceLocations);
                                        $locationLabel = $isServiceLocation ? 'Service Location:' : 'Delivery Location:';
                                        ?>
                                        <small class="text-muted d-block"><?= $locationLabel ?></small>
                                        <span class="fw-medium"><?= htmlspecialchars($procurementOrder['delivery_location']) ?></span>
                                        <?php if ($isServiceLocation): ?>
                                            <small class="badge bg-info ms-1">Service</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Items Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Items (<?= count($procurementOrder['items']) ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($procurementOrder['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($item['item_name']) ?></div>
                                            <?php if (!empty($item['brand']) || !empty($item['model'])): ?>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($item['brand']) ?>
                                                    <?= !empty($item['brand']) && !empty($item['model']) ? ' - ' : '' ?>
                                                    <?= htmlspecialchars($item['model']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?= number_format($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?>
                                        </td>
                                        <td class="text-end">
                                            ₱<?= number_format($item['unit_price'], 2) ?>
                                        </td>
                                        <td class="text-end">
                                            ₱<?= number_format($item['subtotal'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="3">Subtotal:</th>
                                    <th class="text-end">₱<?= number_format($procurementOrder['subtotal'], 2) ?></th>
                                </tr>
                                <tr>
                                    <td colspan="3">VAT (<?= number_format($procurementOrder['vat_rate'], 2) ?>%):</td>
                                    <td class="text-end">₱<?= number_format($procurementOrder['vat_amount'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3">EWT (<?= number_format($procurementOrder['ewt_rate'], 2) ?>%):</td>
                                    <td class="text-end text-danger">-₱<?= number_format($procurementOrder['ewt_amount'], 2) ?></td>
                                </tr>
                                <?php if ($procurementOrder['handling_fee'] > 0): ?>
                                    <tr>
                                        <td colspan="3">Handling Fee:</td>
                                        <td class="text-end">₱<?= number_format($procurementOrder['handling_fee'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($procurementOrder['discount_amount'] > 0): ?>
                                    <tr>
                                        <td colspan="3">Discount:</td>
                                        <td class="text-end text-success">-₱<?= number_format($procurementOrder['discount_amount'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="table-primary fw-bold">
                                    <th colspan="3">Net Total:</th>
                                    <th class="text-end">₱<?= number_format($procurementOrder['net_total'], 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Approval Form -->
            <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/approve'] ?? [])): ?>
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-check-circle me-2"></i>Approval Decision
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="?route=procurement-orders/approve&id=<?= $procurementOrder['id'] ?>" id="approvalForm">
                            <?= CSRFProtection::getTokenField() ?>
                            
                            <div class="mb-4">
                                <label class="form-label">Decision <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card border-success">
                                            <div class="card-body text-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="action" id="approve" value="approve" required>
                                                    <label class="form-check-label w-100" for="approve">
                                                        <i class="bi bi-check-circle-fill text-success display-6 d-block mb-2"></i>
                                                        <strong>Approve</strong>
                                                        <div class="small text-muted">Approve this procurement order</div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card border-warning">
                                            <div class="card-body text-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="action" id="revise" value="revise" required>
                                                    <label class="form-check-label w-100" for="revise">
                                                        <i class="bi bi-arrow-clockwise text-warning display-6 d-block mb-2"></i>
                                                        <strong>Request Revision</strong>
                                                        <div class="small text-muted">Send back for changes</div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card border-danger">
                                            <div class="card-body text-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="action" id="reject" value="reject" required>
                                                    <label class="form-check-label w-100" for="reject">
                                                        <i class="bi bi-x-circle-fill text-danger display-6 d-block mb-2"></i>
                                                        <strong>Reject</strong>
                                                        <div class="small text-muted">Reject this procurement order</div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="notes" class="form-label">Comments/Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="4"
                                          placeholder="Provide comments, feedback, or reasons for your decision..."></textarea>
                                <div class="form-text">
                                    Your comments will be visible to the requester and will be logged for audit purposes.
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Back to Details
                                </a>
                                
                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                    <i class="bi bi-check-circle me-1"></i>Submit Decision
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger mt-4">You do not have permission to approve this procurement order.</div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Budget Analysis -->
            <?php if (!empty($procurementOrder['budget_allocation'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-pie-chart me-2"></i>Budget Analysis
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Budget Allocation:</span>
                            <span>₱<?= number_format($procurementOrder['budget_allocation'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Order Total:</span>
                            <span>₱<?= number_format($procurementOrder['net_total'], 2) ?></span>
                        </div>
                        <hr>
                        <?php
                        $budgetVariance = $procurementOrder['budget_allocation'] - $procurementOrder['net_total'];
                        $varianceClass = $budgetVariance >= 0 ? 'text-success' : 'text-danger';
                        $utilizationPercent = ($procurementOrder['budget_allocation'] > 0) ? 
                            ($procurementOrder['net_total'] / $procurementOrder['budget_allocation']) * 100 : 0;
                        ?>
                        <div class="d-flex justify-content-between fw-bold <?= $varianceClass ?>">
                            <span>Variance:</span>
                            <span><?= $budgetVariance >= 0 ? '+' : '' ?>₱<?= number_format($budgetVariance, 2) ?></span>
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Budget Utilization:</span>
                                <span><?= number_format($utilizationPercent, 1) ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar <?= $utilizationPercent > 100 ? 'bg-danger' : ($utilizationPercent > 90 ? 'bg-warning' : 'bg-success') ?>" 
                                     style="width: <?= min(100, $utilizationPercent) ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Approval Guidelines -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Approval Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-3">
                            <strong class="text-success">Approve when:</strong>
                            <ul class="mt-1 mb-0">
                                <li>All items are justified and necessary</li>
                                <li>Prices are reasonable and competitive</li>
                                <li>Budget allocation is sufficient</li>
                                <li>Vendor is reliable and approved</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <strong class="text-warning">Request Revision when:</strong>
                            <ul class="mt-1 mb-0">
                                <li>Minor changes or clarifications needed</li>
                                <li>Better alternatives should be considered</li>
                                <li>Additional documentation required</li>
                            </ul>
                        </div>
                        
                        <div class="mb-0">
                            <strong class="text-danger">Reject when:</strong>
                            <ul class="mt-1 mb-0">
                                <li>Items are not justified or necessary</li>
                                <li>Budget constraints cannot be met</li>
                                <li>Vendor does not meet requirements</li>
                                <li>Procurement violates company policies</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order History -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>Order Timeline
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Order Created</h6>
                                <p class="timeline-text">
                                    Created by <?= htmlspecialchars($procurementOrder['requested_by_name']) ?>
                                </p>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($procurementOrder['created_at'])) ?></small>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Pending Approval</h6>
                                <p class="timeline-text">Awaiting your approval decision</p>
                                <small class="text-muted">Current Status</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Enable submit button when action is selected
document.querySelectorAll('input[name="action"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('submitBtn').disabled = false;
        
        // Update button text and color based on selection
        const submitBtn = document.getElementById('submitBtn');
        const selectedAction = this.value;
        
        switch(selectedAction) {
            case 'approve':
                submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Approve Order';
                submitBtn.className = 'btn btn-success';
                break;
            case 'revise':
                submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Request Revision';
                submitBtn.className = 'btn btn-warning';
                break;
            case 'reject':
                submitBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Reject Order';
                submitBtn.className = 'btn btn-danger';
                break;
        }
    });
});

// Form validation
document.getElementById('approvalForm').addEventListener('submit', function(e) {
    const selectedAction = document.querySelector('input[name="action"]:checked');
    const notes = document.getElementById('notes').value.trim();
    
    if (!selectedAction) {
        e.preventDefault();
        alert('Please select an approval decision.');
        return false;
    }
    
    // Require notes for rejection or revision
    if ((selectedAction.value === 'reject' || selectedAction.value === 'revise') && !notes) {
        e.preventDefault();
        alert('Please provide comments/notes for your decision.');
        document.getElementById('notes').focus();
        return false;
    }
    
    // Confirmation dialog
    let confirmMessage = '';
    switch(selectedAction.value) {
        case 'approve':
            confirmMessage = 'Are you sure you want to approve this procurement order?';
            break;
        case 'revise':
            confirmMessage = 'Are you sure you want to request revision for this procurement order?';
            break;
        case 'reject':
            confirmMessage = 'Are you sure you want to reject this procurement order?';
            break;
    }
    
    if (!confirm(confirmMessage)) {
        e.preventDefault();
        return false;
    }
});
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content {
    padding-left: 10px;
}

.timeline-title {
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.timeline-text {
    font-size: 0.8rem;
    margin-bottom: 5px;
    color: #6c757d;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
