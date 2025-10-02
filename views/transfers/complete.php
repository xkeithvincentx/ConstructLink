<?php
/**
 * ConstructLink™ Transfer Complete View
 * Form to complete a transfer request
 */

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-check2-circle me-2"></i>Complete Transfer
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
                    <i class="bi bi-info-circle me-2"></i>Transfer Details
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Transfer ID:</strong> #<?= $transfer['id'] ?></p>
                        <p><strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?></p>
                        <p><strong>Transfer Type:</strong> <?= ucfirst($transfer['transfer_type']) ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-info">Approved</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>From:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?></p>
                        <p><strong>To:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?></p>
                        <p><strong>Approved By:</strong> <?= htmlspecialchars($transfer['approved_by_name']) ?></p>
                        <p><strong>Approval Date:</strong> <?= date('M j, Y', strtotime($transfer['approval_date'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completion Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Transfer Completion
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

                <?php if (canCompleteTransfer($transfer, $user)): ?>
                <form method="POST" action="?route=transfers/complete&id=<?= $transfer['id'] ?>" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Completion Notes -->
                    <div class="mb-4">
                        <label for="completion_notes" class="form-label">Completion Notes</label>
                        <textarea class="form-control" id="completion_notes" name="completion_notes" rows="4" 
                                  placeholder="Add any notes about the transfer completion..."><?= htmlspecialchars($formData['completion_notes'] ?? '') ?></textarea>
                        <div class="form-text">Document any issues, special conditions, or observations during the transfer</div>
                    </div>

                    <!-- Pre-completion Checklist -->
                    <div class="mb-4">
                        <h6>Pre-completion Checklist</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="asset_verified" name="asset_verified" value="1" required>
                            <label class="form-check-label" for="asset_verified">
                                Asset has been physically verified and is ready for transfer
                            </label>
                            <div class="invalid-feedback">Please confirm asset verification.</div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="destination_ready" name="destination_ready" value="1" required>
                            <label class="form-check-label" for="destination_ready">
                                Destination project is ready to receive the asset
                            </label>
                            <div class="invalid-feedback">Please confirm destination readiness.</div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="documentation_complete" name="documentation_complete" value="1" required>
                            <label class="form-check-label" for="documentation_complete">
                                All required documentation has been completed
                            </label>
                            <div class="invalid-feedback">Please confirm documentation completion.</div>
                        </div>
                    </div>

                    <!-- Final Confirmation -->
                    <div class="mb-4">
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Important Notice</h6>
                            <p class="mb-2">Completing this transfer will:</p>
                            <ul class="mb-0">
                                <li>Update the asset's project location in the system</li>
                                <li>Change the transfer status to "Completed"</li>
                                <li>Make the asset available at the destination project</li>
                                <?php if ($transfer['transfer_type'] === 'permanent'): ?>
                                    <li><strong>Permanently move the asset</strong> (cannot be easily reversed)</li>
                                <?php else: ?>
                                    <li>Temporarily assign the asset (can be returned later)</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirm_completion" name="confirm_completion" value="1" required>
                            <label class="form-check-label" for="confirm_completion">
                                <strong>I confirm that the transfer has been physically completed and the asset is now at the destination project.</strong>
                            </label>
                            <div class="invalid-feedback">You must confirm the completion to proceed.</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>Complete Transfer
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to complete this transfer or it is not in a completable status.
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
                    <i class="bi bi-info-circle me-2"></i>Completion Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Before Completing</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1"></i> Physical transfer completed</li>
                        <li><i class="bi bi-check text-success me-1"></i> Asset condition verified</li>
                        <li><i class="bi bi-check text-success me-1"></i> Receiving party confirmed</li>
                        <li><i class="bi bi-check text-success me-1"></i> Documentation signed</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>After Completion</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Asset location updated</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Transfer marked complete</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Notifications sent</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Records archived</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tip:</strong> Take photos or get signatures as proof of transfer completion.
                    </small>
                </div>
                <div class="mb-3">
                    <h6>Who Can Complete (MVA Workflow)</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-check text-primary me-1"></i> System Admin: Any transfer</li>
                        <li><i class="bi bi-person-check text-primary me-1"></i> Site Inventory Clerk/Project Manager: If allowed by workflow</li>
                        <li><i class="bi bi-person-check text-primary me-1"></i> Only allowed in <strong>Received</strong> status</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Transfer Timeline -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Transfer Progress
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Requested</h6>
                            <small class="text-muted"><?= date('M j, Y', strtotime($transfer['created_at'])) ?></small>
                        </div>
                    </div>
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Approved</h6>
                            <small class="text-muted"><?= date('M j, Y', strtotime($transfer['approval_date'])) ?></small>
                        </div>
                    </div>
                    <div class="timeline-item current">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Completing...</h6>
                            <small class="text-muted">In progress</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.timeline-item.completed .timeline-content {
    border-left-color: #28a745;
}

.timeline-item.current .timeline-content {
    border-left-color: #007bff;
    background: #e3f2fd;
}

.timeline-title {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    font-weight: 600;
}
</style>

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
$pageTitle = 'Complete Transfer - ConstructLink™';
include APP_ROOT . '/views/layouts/main.php';
?>
