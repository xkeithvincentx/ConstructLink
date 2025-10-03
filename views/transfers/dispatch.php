<?php
/**
 * ConstructLink™ Transfer Dispatch View
 * Form to dispatch an approved transfer
 */

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-send me-2"></i>Dispatch Transfer
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
                    <i class="bi bi-info-circle me-2"></i>Transfer Request Summary
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Transfer ID:</strong> #<?= $transfer['id'] ?></p>
                        <p><strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?></p>
                        <p><strong>Transfer Type:</strong> <?= ucfirst($transfer['transfer_type']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>From:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?></p>
                        <p><strong>To:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?></p>
                        <p><strong>Requested By:</strong> <?= htmlspecialchars($transfer['initiated_by_name']) ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <p><strong>Reason:</strong></p>
                        <p class="text-muted"><?= htmlspecialchars($transfer['reason']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dispatch Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-send me-2"></i>Dispatch Confirmation
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

                <?php if (canDispatchTransfer($transfer, $user)): ?>
                <form method="POST" action="?route=transfers/dispatch&id=<?= $transfer['id'] ?>" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Dispatch Notes -->
                    <div class="mb-4">
                        <label for="dispatch_notes" class="form-label">Dispatch Notes</label>
                        <textarea class="form-control" id="dispatch_notes" name="dispatch_notes" rows="4"
                                  placeholder="Add notes about the dispatch (e.g., courier details, tracking number, condition notes)..."><?= htmlspecialchars($formData['dispatch_notes'] ?? '') ?></textarea>
                        <div class="form-text">Add any relevant dispatch or shipping details</div>
                    </div>

                    <!-- Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirm_dispatch" name="confirm_dispatch" value="1" required>
                            <label class="form-check-label" for="confirm_dispatch">
                                <strong>I confirm that the asset has been dispatched from <?= htmlspecialchars($transfer['from_project_name']) ?> and is now in transit to <?= htmlspecialchars($transfer['to_project_name']) ?>.</strong>
                            </label>
                            <div class="invalid-feedback">You must confirm the dispatch to proceed.</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Confirm Dispatch
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to dispatch this transfer or it is not in an approved status.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar with Guidelines -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Dispatch Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Before Dispatching</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1"></i> Verify asset availability</li>
                        <li><i class="bi bi-check text-success me-1"></i> Confirm business justification</li>
                        <li><i class="bi bi-check text-success me-1"></i> Check project requirements</li>
                        <li><i class="bi bi-check text-success me-1"></i> Ensure proper authorization</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Who Can Approve (MVA Workflow)</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-check text-primary me-1"></i> System Admin: Any transfer</li>
                        <li><i class="bi bi-person-check text-primary me-1"></i> Finance Director/Asset Director: If allowed by workflow</li>
                        <li><i class="bi bi-person-check text-primary me-1"></i> Only allowed in <strong>Pending Approval</strong> status</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <small>
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Important:</strong> Once approved, the transfer can be executed and the asset will be moved.
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
                
                <div class="d-grid">
                    <a href="?route=assets/view&id=<?= $transfer['asset_id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>View Asset Details
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
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Approve Transfer - ConstructLink™';
include APP_ROOT . '/views/layouts/main.php';
?>
