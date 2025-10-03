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
<?php if (in_array($user['role_name'], $roleConfig['procurement-orders/cancel'] ?? [])): ?>
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
        <!-- Cancellation Form -->
        <div class="col-lg-8">
            <!-- Order Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Order Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Canceling this procurement order will permanently remove it from the system. 
                        This action cannot be undone.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">PO Number:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($order['po_number'] ?: '#' . $order['id']) ?></dd>
                                
                                <dt class="col-sm-5">Title:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($order['title']) ?></dd>
                                
                                <dt class="col-sm-5">Status:</dt>
                                <dd class="col-sm-7">
                                    <?php
                                    $statusClasses = [
                                        'draft' => 'bg-secondary',
                                        'pending' => 'bg-warning',
                                        'approved' => 'bg-success',
                                        'received' => 'bg-info',
                                        'rejected' => 'bg-danger'
                                    ];
                                    $statusClass = $statusClasses[$order['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Vendor:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($order['vendor_name'] ?? 'N/A') ?></dd>
                                
                                <dt class="col-sm-5">Project:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($order['project_name'] ?? 'N/A') ?>
                                    </span>
                                </dd>
                                
                                <dt class="col-sm-5">Net Total:</dt>
                                <dd class="col-sm-7">
                                    <strong class="text-primary">₱<?= number_format($order['net_total'] ?? 0, 2) ?></strong>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancellation Form -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-x-circle me-2"></i>Cancellation Details
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=procurement-orders/cancel&id=<?= $order['id'] ?>" id="cancelForm">
                        <?= CSRFProtection::getTokenField() ?>
                        
                        <div class="mb-3">
                            <label for="cancellation_reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                            <select class="form-select" id="cancellation_reason" name="cancellation_reason" required>
                                <option value="">Select a reason</option>
                                <option value="budget_constraints" <?= ($formData['cancellation_reason'] ?? '') === 'budget_constraints' ? 'selected' : '' ?>>Budget Constraints</option>
                                <option value="vendor_issues" <?= ($formData['cancellation_reason'] ?? '') === 'vendor_issues' ? 'selected' : '' ?>>Vendor Issues</option>
                                <option value="project_changes" <?= ($formData['cancellation_reason'] ?? '') === 'project_changes' ? 'selected' : '' ?>>Project Changes</option>
                                <option value="specification_changes" <?= ($formData['cancellation_reason'] ?? '') === 'specification_changes' ? 'selected' : '' ?>>Specification Changes</option>
                                <option value="timeline_issues" <?= ($formData['cancellation_reason'] ?? '') === 'timeline_issues' ? 'selected' : '' ?>>Timeline Issues</option>
                                <option value="duplicate_order" <?= ($formData['cancellation_reason'] ?? '') === 'duplicate_order' ? 'selected' : '' ?>>Duplicate Order</option>
                                <option value="management_decision" <?= ($formData['cancellation_reason'] ?? '') === 'management_decision' ? 'selected' : '' ?>>Management Decision</option>
                                <option value="other" <?= ($formData['cancellation_reason'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="customReasonDiv" style="display: none;">
                            <label for="custom_reason" class="form-label">Please specify the reason</label>
                            <input type="text" class="form-control" id="custom_reason" name="custom_reason" 
                                   value="<?= htmlspecialchars($formData['custom_reason'] ?? '') ?>"
                                   placeholder="Please provide specific details...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="cancellation_notes" class="form-label">Additional Notes <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="cancellation_notes" name="cancellation_notes" rows="4" required
                                      placeholder="Please provide detailed explanation for the cancellation..."><?= htmlspecialchars($formData['cancellation_notes'] ?? '') ?></textarea>
                            <div class="form-text">
                                Provide a detailed explanation that will help with future procurement decisions and vendor relationships.
                            </div>
                        </div>
                        
                        <?php if ($order['status'] === 'approved'): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> This order has already been approved. Canceling it may require 
                                vendor notification and could impact vendor relationships. Please ensure proper communication 
                                has been made with the vendor before proceeding.
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="vendor_notified" name="vendor_notified" 
                                           <?= isset($formData['vendor_notified']) ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="vendor_notified">
                                        <strong>Vendor Notification</strong><br>
                                        <small class="text-muted">I confirm that the vendor has been notified about this cancellation.</small>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm_cancellation" name="confirm_cancellation" required>
                                <label class="form-check-label" for="confirm_cancellation">
                                    <strong>Confirmation</strong><br>
                                    <small class="text-muted">I understand that this action cannot be undone and confirm that I want to cancel this procurement order.</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Go Back
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-x-circle me-1"></i>Cancel Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Cancellation Impact -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Cancellation Impact
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex align-items-start">
                            <i class="bi bi-x-circle text-danger me-2 mt-1"></i>
                            <div>
                                <strong>Order Removal</strong>
                                <br><small class="text-muted">The order will be permanently removed from the system</small>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] === 'approved'): ?>
                            <div class="list-group-item d-flex align-items-start">
                                <i class="bi bi-building text-warning me-2 mt-1"></i>
                                <div>
                                    <strong>Vendor Impact</strong>
                                    <br><small class="text-muted">May affect vendor relationships and future negotiations</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="list-group-item d-flex align-items-start">
                            <i class="bi bi-graph-down text-info me-2 mt-1"></i>
                            <div>
                                <strong>Budget Release</strong>
                                <br><small class="text-muted">Allocated budget will be released back to the project</small>
                            </div>
                        </div>
                        
                        <div class="list-group-item d-flex align-items-start">
                            <i class="bi bi-clock-history text-secondary me-2 mt-1"></i>
                            <div>
                                <strong>Timeline Impact</strong>
                                <br><small class="text-muted">Project timeline may be affected if items are critical</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calculator me-2"></i>Order Summary
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">Items:</dt>
                        <dd class="col-6 text-end"><?= count($items ?? []) ?></dd>
                        
                        <dt class="col-6">Subtotal:</dt>
                        <dd class="col-6 text-end">₱<?= number_format($order['subtotal'] ?? 0, 2) ?></dd>
                        
                        <?php if (($order['tax_amount'] ?? 0) > 0): ?>
                            <dt class="col-6">Tax:</dt>
                            <dd class="col-6 text-end">₱<?= number_format($order['tax_amount'] ?? 0, 2) ?></dd>
                        <?php endif; ?>
                        
                        <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                            <dt class="col-6">Discount:</dt>
                            <dd class="col-6 text-end text-success">-₱<?= number_format($order['discount_amount'] ?? 0, 2) ?></dd>
                        <?php endif; ?>
                        
                        <dt class="col-6 border-top pt-2"><strong>Net Total:</strong></dt>
                        <dd class="col-6 text-end border-top pt-2">
                            <strong class="text-primary">₱<?= number_format($order['net_total'] ?? 0, 2) ?></strong>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Alternative Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Alternative Actions
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Consider these alternatives before canceling:
                    </p>
                    
                    <div class="d-grid gap-2">
                        <?php if ($order['status'] === 'draft'): ?>
                            <a href="?route=procurement-orders/edit&id=<?= $order['id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil me-1"></i>Edit Order
                            </a>
                        <?php endif; ?>
                        
                        <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-eye me-1"></i>Review Order
                        </a>
                        
                        <a href="?route=procurement-orders" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-list me-1"></i>View All Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Show/hide custom reason field
document.getElementById('cancellation_reason').addEventListener('change', function() {
    const customReasonDiv = document.getElementById('customReasonDiv');
    const customReasonInput = document.getElementById('custom_reason');
    
    if (this.value === 'other') {
        customReasonDiv.style.display = 'block';
        customReasonInput.required = true;
    } else {
        customReasonDiv.style.display = 'none';
        customReasonInput.required = false;
        customReasonInput.value = '';
    }
});

// Initialize custom reason visibility
document.addEventListener('DOMContentLoaded', function() {
    const reasonSelect = document.getElementById('cancellation_reason');
    if (reasonSelect.value === 'other') {
        document.getElementById('customReasonDiv').style.display = 'block';
        document.getElementById('custom_reason').required = true;
    }
});

// Form validation
document.getElementById('cancelForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('cancellation_reason').value;
    const notes = document.getElementById('cancellation_notes').value.trim();
    const confirmed = document.getElementById('confirm_cancellation').checked;
    
    if (!reason) {
        e.preventDefault();
        alert('Please select a cancellation reason.');
        return false;
    }
    
    if (reason === 'other') {
        const customReason = document.getElementById('custom_reason').value.trim();
        if (!customReason) {
            e.preventDefault();
            alert('Please specify the custom reason.');
            document.getElementById('custom_reason').focus();
            return false;
        }
    }
    
    if (!notes) {
        e.preventDefault();
        alert('Please provide additional notes for the cancellation.');
        document.getElementById('cancellation_notes').focus();
        return false;
    }
    
    if (!confirmed) {
        e.preventDefault();
        alert('Please confirm that you want to cancel this procurement order.');
        return false;
    }
    
    <?php if ($order['status'] === 'approved'): ?>
        const vendorNotified = document.getElementById('vendor_notified').checked;
        if (!vendorNotified) {
            e.preventDefault();
            alert('Please confirm that the vendor has been notified about this cancellation.');
            return false;
        }
    <?php endif; ?>
    
    if (!confirm('Are you absolutely sure you want to cancel this procurement order? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
</script>
<?php else: ?>
<div class="alert alert-danger mt-4">You do not have permission to cancel this procurement order.</div>
<?php endif; ?>

<?php
// Store the captured content
$content = ob_get_clean();

// Include the main layout
include APP_ROOT . '/views/layouts/main.php';
?>
