<?php
/**
 * ConstructLink™ Transfer Cancel View
 * Form to cancel a transfer request (MVA RBAC refactored)
 */

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-x-circle me-2"></i>Cancel Transfer Request
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Details
        </a>
    </div>
</div>

<!-- Transfer Information -->
<div class="row">
    <div class="col-lg-8">
        <!-- Transfer Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Transfer Request Details
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Transfer ID:</strong> #<?= $transfer['id'] ?></p>
                        <p><strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?></p>
                        <p><strong>Transfer Type:</strong> <?= ucfirst($transfer['transfer_type']) ?></p>
                        <p><strong>Current Status:</strong> 
                            <?php
                            $statusClasses = [
                                'Pending Verification' => 'warning',
                                'Pending Approval' => 'info',
                                'Approved' => 'primary',
                                'Received' => 'secondary',
                                'Completed' => 'success',
                                'Canceled' => 'danger'
                            ];
                            $badgeClass = $statusClasses[$transfer['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $badgeClass ?>">
                                <?= $transfer['status'] ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>From:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?></p>
                        <p><strong>To:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?></p>
                        <p><strong>Requested By:</strong> <?= htmlspecialchars($transfer['initiated_by_name']) ?></p>
                        <p><strong>Request Date:</strong> <?= date('M j, Y', strtotime($transfer['created_at'])) ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <p><strong>Original Reason:</strong></p>
                        <p class="text-muted"><?= htmlspecialchars($transfer['reason']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation Form -->
        <?php if (canCancelTransfer($transfer, $user)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-x-circle me-2"></i>Cancellation Details
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="?route=transfers/cancel&id=<?= $transfer['id'] ?>" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Cancellation Reason -->
                    <div class="mb-4">
                        <label for="cancel_reason" class="form-label">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="4" required
                                  placeholder="Please explain why this transfer request is being canceled..."><?= htmlspecialchars($formData['cancel_reason'] ?? '') ?></textarea>
                        <div class="form-text">Provide a clear explanation for the cancellation</div>
                        <div class="invalid-feedback">Please provide a reason for cancellation.</div>
                    </div>

                    <!-- Cancellation Impact Warning -->
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Cancellation Impact</h6>
                        <p class="mb-2">Canceling this transfer will:</p>
                        <ul class="mb-0">
                            <li>Mark the transfer request as "Canceled"</li>
                            <li>Keep the asset at its current location</li>
                            <li>Notify relevant parties of the cancellation</li>
                            <li>Prevent any further action on this transfer</li>
                            <?php if ($transfer['status'] === 'Approved'): ?>
                                <li><strong>Note:</strong> This transfer has already been approved</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirm_cancellation" name="confirm_cancellation" value="1" required>
                            <label class="form-check-label" for="confirm_cancellation">
                                <strong>I understand that this action cannot be undone and confirm the cancellation of this transfer request.</strong>
                            </label>
                            <div class="invalid-feedback">You must confirm the cancellation to proceed.</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-arrow-left me-1"></i>Go Back
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>Cancel Transfer Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger mt-4">
            <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to cancel this transfer or it is not in a cancelable status.
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar with Guidelines -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Cancellation Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Valid Reasons for Cancellation</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1"></i> Asset no longer needed</li>
                        <li><i class="bi bi-check text-success me-1"></i> Project requirements changed</li>
                        <li><i class="bi bi-check text-success me-1"></i> Asset became unavailable</li>
                        <li><i class="bi bi-check text-success me-1"></i> Alternative solution found</li>
                        <li><i class="bi bi-check text-success me-1"></i> Budget constraints</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Who Can Cancel (MVA Workflow)</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-check text-primary me-1"></i> System Admin: Any transfer</li>
                        <li><i class="bi bi-person-check text-primary me-1"></i> Request Initiator: Own requests (if still pending)</li>
                        <li><i class="bi bi-person-check text-primary me-1"></i> Project Manager: Project transfers (if still pending)</li>
                        <li><i class="bi bi-person-check text-primary me-1"></i> Only allowed in <strong>Pending Verification</strong> or <strong>Pending Approval</strong> status</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Alternative:</strong> Consider modifying the transfer instead of canceling if the requirements have only slightly changed.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Asset Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box-seam me-2"></i>Asset Details
                </h6>
            </div>
            <div class="card-body">
                <p><strong>Reference:</strong><br><?= htmlspecialchars($transfer['asset_ref']) ?></p>
                <p><strong>Name:</strong><br><?= htmlspecialchars($transfer['asset_name']) ?></p>
                <p><strong>Category:</strong><br><?= htmlspecialchars($transfer['category_name'] ?? 'Unknown') ?></p>
                <p><strong>Current Status:</strong><br>
                    <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $transfer['asset_status'])) ?></span>
                </p>
                <p><strong>Current Location:</strong><br><?= htmlspecialchars($transfer['from_project_name']) ?></p>
                
                <div class="d-grid">
                    <a href="?route=assets/view&id=<?= $transfer['asset_id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>View Asset Details
                    </a>
                </div>
            </div>
        </div>

        <!-- Alternative Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Alternative Actions
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Instead of canceling, you might consider:</p>
                <div class="d-grid gap-2">
                    <a href="?route=transfers/create" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-plus me-1"></i>Create New Transfer
                    </a>
                    <a href="?route=transfers" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-list me-1"></i>View All Transfers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Double confirmation for cancellation
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('Are you absolutely sure you want to cancel this transfer request? This action cannot be undone.')) {
        e.preventDefault();
    }
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Cancel Transfer - ConstructLink™';
include APP_ROOT . '/views/layouts/main.php';
?>
