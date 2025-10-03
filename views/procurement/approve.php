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
        <!-- Approval Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Procurement Order Review
                </h6>
            </div>
            <div class="card-body">
                <!-- Order Summary -->
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading">
                        <i class="bi bi-info-circle me-2"></i>Review Required
                    </h6>
                    <p class="mb-0">
                        Please carefully review this procurement order before making your decision. 
                        Ensure budget availability, vendor reliability, and compliance with company policies.
                    </p>
                </div>
                
                <!-- Quick Order Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Order Information</h6>
                                <p class="mb-1"><strong>PO Number:</strong> <?= htmlspecialchars($procurement['po_number']) ?></p>
                                <p class="mb-1"><strong>Vendor:</strong> <?= htmlspecialchars($procurement['vendor_name']) ?></p>
                                <p class="mb-1"><strong>Project:</strong> <?= htmlspecialchars($procurement['project_name']) ?></p>
                                <p class="mb-0"><strong>Requested By:</strong> <?= htmlspecialchars($procurement['requested_by_name']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Financial Summary</h6>
                                <p class="mb-1"><strong>Item:</strong> <?= htmlspecialchars($procurement['item_name']) ?></p>
                                <p class="mb-1"><strong>Quantity:</strong> <?= number_format($procurement['quantity']) ?> <?= htmlspecialchars($procurement['unit'] ?? 'pcs') ?></p>
                                <p class="mb-1"><strong>Unit Price:</strong> ₱<?= number_format($procurement['unit_price'], 2) ?></p>
                                <p class="mb-0"><strong>Net Total:</strong> <span class="text-primary fw-bold">₱<?= number_format($procurement['net_total'], 2) ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Approval Form -->
                <form method="POST" action="?route=procurement/approve&id=<?= $procurement['id'] ?>" id="approvalForm">
                    <?= CSRFProtection::generateToken() ?>
                    
                    <div class="mb-4">
                        <h6>Approval Decision</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="action" id="approve" value="approve" required>
                                            <label class="form-check-label w-100" for="approve">
                                                <i class="bi bi-check-circle text-success display-6 d-block mb-2"></i>
                                                <strong>Approve Order</strong>
                                                <div class="small text-muted">Authorize this procurement order</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="action" id="reject" value="reject" required>
                                            <label class="form-check-label w-100" for="reject">
                                                <i class="bi bi-x-circle text-danger display-6 d-block mb-2"></i>
                                                <strong>Reject Order</strong>
                                                <div class="small text-muted">Decline this procurement order</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Approval Notes -->
                    <div class="mb-3" id="approval-notes" style="display: none;">
                        <label for="notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Add any notes or conditions for this approval..."></textarea>
                    </div>
                    
                    <!-- Rejection Reason -->
                    <div class="mb-3" id="rejection-reason" style="display: none;">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" 
                                  placeholder="Please provide a detailed reason for rejecting this order..."></textarea>
                        <div class="form-text">This reason will be communicated to the requester.</div>
                    </div>
                    
                    <!-- Approval Checklist -->
                    <div class="mb-4" id="approval-checklist" style="display: none;">
                        <h6>Approval Checklist</h6>
                        <div class="list-group">
                            <div class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-budget" required>
                                    <label class="form-check-label" for="check-budget">
                                        Budget availability confirmed for this project
                                    </label>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-vendor" required>
                                    <label class="form-check-label" for="check-vendor">
                                        Vendor credentials and reliability verified
                                    </label>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-pricing" required>
                                    <label class="form-check-label" for="check-pricing">
                                        Pricing is reasonable and competitive
                                    </label>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-policy" required>
                                    <label class="form-check-label" for="check-policy">
                                        Order complies with company procurement policies
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?route=procurement/view&id=<?= $procurement['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Details
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                            <i class="bi bi-check-circle me-1"></i>Submit Decision
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Detailed Financial Breakdown -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-calculator me-2"></i>Financial Breakdown
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-7">Subtotal:</div>
                    <div class="col-5 text-end">₱<?= number_format($procurement['subtotal'], 2) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-7">VAT (12%):</div>
                    <div class="col-5 text-end">₱<?= number_format($procurement['vat_amount'], 2) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-7">Handling Fee:</div>
                    <div class="col-5 text-end">₱<?= number_format($procurement['handling_fee'], 2) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-7">EWT (2%):</div>
                    <div class="col-5 text-end">-₱<?= number_format($procurement['ewt_amount'], 2) ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-7"><strong>Net Total:</strong></div>
                    <div class="col-5 text-end"><strong>₱<?= number_format($procurement['net_total'], 2) ?></strong></div>
                </div>
                
                <?php if ($procurement['net_total'] > 100000): ?>
                    <div class="alert alert-warning mt-3" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>High Value Order</strong><br>
                        This order exceeds ₱100,000 and requires special attention.
                    </div>
                <?php endif; ?>
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
                <?php if ($procurement['vendor_email']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Email:</small> <?= htmlspecialchars($procurement['vendor_email']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($procurement['payment_terms']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Payment Terms:</small> <?= htmlspecialchars($procurement['payment_terms']) ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="?route=vendors/view&id=<?= $procurement['vendor_id'] ?>" 
                       class="btn btn-outline-primary btn-sm w-100" target="_blank">
                        <i class="bi bi-eye me-1"></i>View Vendor Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Project Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-folder me-2"></i>Project Information
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong><?= htmlspecialchars($procurement['project_name']) ?></strong>
                </div>
                <div class="mb-1">
                    <small class="text-muted">Code:</small> <?= htmlspecialchars($procurement['project_code']) ?>
                </div>
                <?php if ($procurement['project_location']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Location:</small> <?= htmlspecialchars($procurement['project_location']) ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="?route=projects/view&id=<?= $procurement['project_id'] ?>" 
                       class="btn btn-outline-primary btn-sm w-100" target="_blank">
                        <i class="bi bi-eye me-1"></i>View Project Details
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quote File -->
        <?php if ($procurement['quote_file']): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-file-earmark-text me-2"></i>Vendor Quote
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Review the vendor's quotation before making your decision.</p>
                    <a href="/uploads/quotes/<?= htmlspecialchars($procurement['quote_file']) ?>" 
                       class="btn btn-outline-primary btn-sm w-100" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-1"></i>View Quote File
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const approveRadio = document.getElementById('approve');
    const rejectRadio = document.getElementById('reject');
    const approvalNotes = document.getElementById('approval-notes');
    const rejectionReason = document.getElementById('rejection-reason');
    const approvalChecklist = document.getElementById('approval-checklist');
    const submitBtn = document.getElementById('submit-btn');
    const checkboxes = document.querySelectorAll('#approval-checklist input[type="checkbox"]');
    
    // Show/hide sections based on selection
    function toggleSections() {
        if (approveRadio.checked) {
            approvalNotes.style.display = 'block';
            rejectionReason.style.display = 'none';
            approvalChecklist.style.display = 'block';
            document.getElementById('rejection_reason').required = false;
        } else if (rejectRadio.checked) {
            approvalNotes.style.display = 'none';
            rejectionReason.style.display = 'block';
            approvalChecklist.style.display = 'none';
            document.getElementById('rejection_reason').required = true;
        }
        updateSubmitButton();
    }
    
    // Update submit button state
    function updateSubmitButton() {
        let canSubmit = false;
        
        if (approveRadio.checked) {
            // For approval, all checkboxes must be checked
            canSubmit = Array.from(checkboxes).every(cb => cb.checked);
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Approve Order';
            submitBtn.className = 'btn btn-success';
        } else if (rejectRadio.checked) {
            // For rejection, just need a reason
            const reason = document.getElementById('rejection_reason').value.trim();
            canSubmit = reason.length > 0;
            submitBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Reject Order';
            submitBtn.className = 'btn btn-danger';
        }
        
        submitBtn.disabled = !canSubmit;
    }
    
    // Event listeners
    approveRadio.addEventListener('change', toggleSections);
    rejectRadio.addEventListener('change', toggleSections);
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSubmitButton);
    });
    
    document.getElementById('rejection_reason').addEventListener('input', updateSubmitButton);
    
    // Form submission confirmation
    document.getElementById('approvalForm').addEventListener('submit', function(e) {
        const action = approveRadio.checked ? 'approve' : 'reject';
        const orderValue = '₱<?= number_format($procurement['net_total'], 2) ?>';
        
        let confirmMessage = '';
        if (action === 'approve') {
            confirmMessage = `Are you sure you want to APPROVE this procurement order for ${orderValue}?`;
        } else {
            confirmMessage = `Are you sure you want to REJECT this procurement order for ${orderValue}?`;
        }
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Approve Procurement Order - ConstructLink™';
$pageHeader = 'Approve Procurement Order #' . ($procurement['po_number'] ?? 'N/A');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement', 'url' => '?route=procurement'],
    ['title' => 'Order Details', 'url' => '?route=procurement/view&id=' . ($procurement['id'] ?? '')],
    ['title' => 'Approve', 'url' => '?route=procurement/approve&id=' . ($procurement['id'] ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
