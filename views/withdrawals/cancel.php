<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';
function canCancelWithdrawal($withdrawal, $userRole, $roleConfig, $userId) {
    if ($userRole === 'System Admin') return true;
    $canCancelByRole = in_array($userRole, $roleConfig['withdrawals/cancel'] ?? []);
    $canCancelByOwnership = $withdrawal['withdrawn_by'] == $userId;
    return ($canCancelByRole || $canCancelByOwnership) && in_array($withdrawal['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'Released']);
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-x-circle me-2"></i>
        Cancel Withdrawal Request
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Withdrawal
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
    <div class="col-lg-8">
        <!-- Cancellation Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirm Cancellation
                </h6>
            </div>
            <div class="card-body">
                <?php if ($withdrawal['status'] === 'released'): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Asset Already Released:</strong> This asset has already been released. Canceling this withdrawal will:
                        <ul class="mb-0 mt-2">
                            <li>Return the asset status to "Available"</li>
                            <li>Notify relevant parties of the cancellation</li>
                            <li>Require the asset to be physically returned to inventory</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Pending Request Cancellation:</strong> This withdrawal request is still pending. Canceling will:
                        <ul class="mb-0 mt-2">
                            <li>Remove the request from the approval queue</li>
                            <li>Keep the asset status as "Available"</li>
                            <li>Notify the requester of the cancellation</li>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (canCancelWithdrawal($withdrawal, $userRole, $roleConfig, $user['id'])): ?>
                <form method="POST" action="?route=withdrawals/cancel&id=<?= $withdrawal['id'] ?>" id="cancelForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Cancellation Reason -->
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <select class="form-select" id="cancellation_reason" name="cancellation_reason" required onchange="toggleCustomReason()">
                            <option value="">Select a reason</option>
                            <option value="no_longer_needed">No longer needed</option>
                            <option value="asset_unavailable">Asset unavailable</option>
                            <option value="project_delayed">Project delayed/postponed</option>
                            <option value="duplicate_request">Duplicate request</option>
                            <option value="wrong_asset">Wrong asset requested</option>
                            <option value="budget_constraints">Budget constraints</option>
                            <option value="safety_concerns">Safety concerns</option>
                            <option value="other">Other (specify below)</option>
                        </select>
                    </div>
                    
                    <!-- Custom Reason (shown when "Other" is selected) -->
                    <div class="mb-3" id="customReasonDiv" style="display: none;">
                        <label for="custom_reason" class="form-label">Please specify the reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="custom_reason" 
                                  name="custom_reason" 
                                  rows="3" 
                                  placeholder="Please provide details about the cancellation reason..."></textarea>
                    </div>
                    
                    <!-- Detailed Explanation -->
                    <div class="mb-3">
                        <label for="reason" class="form-label">Additional Details</label>
                        <textarea class="form-control" 
                                  id="reason" 
                                  name="reason" 
                                  rows="4" 
                                  placeholder="Provide any additional context or details about this cancellation..."></textarea>
                        <div class="form-text">
                            Optional: Provide more context to help with future planning and asset management.
                        </div>
                    </div>
                    
                    <?php if ($withdrawal['status'] === 'released'): ?>
                        <!-- Asset Return Confirmation -->
                        <div class="mb-3">
                            <label class="form-label">Asset Return Status <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="asset_return_status" id="asset_returned" value="returned" required>
                                        <label class="form-check-label" for="asset_returned">
                                            <i class="bi bi-check-circle text-success me-1"></i>Asset Already Returned
                                        </label>
                                        <div class="form-text">Asset has been physically returned to inventory</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="asset_return_status" id="asset_pending_return" value="pending_return" required>
                                        <label class="form-check-label" for="asset_pending_return">
                                            <i class="bi bi-clock text-warning me-1"></i>Asset Pending Return
                                        </label>
                                        <div class="form-text">Asset will be returned separately</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Return Instructions (shown when pending return) -->
                        <div class="mb-3" id="returnInstructions" style="display: none;">
                            <label for="return_instructions" class="form-label">Return Instructions</label>
                            <textarea class="form-control" 
                                      id="return_instructions" 
                                      name="return_instructions" 
                                      rows="3" 
                                      placeholder="Provide instructions for returning the asset..."></textarea>
                            <div class="form-text text-warning">
                                Please coordinate with the receiver to ensure proper asset return.
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Impact Assessment -->
                    <div class="mb-3">
                        <label class="form-label">Impact Assessment</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="impact[]" value="project_delay" id="impact_project">
                                    <label class="form-check-label" for="impact_project">
                                        May delay project activities
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="impact[]" value="resource_reallocation" id="impact_resource">
                                    <label class="form-check-label" for="impact_resource">
                                        Requires resource reallocation
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="impact[]" value="no_impact" id="impact_none">
                                    <label class="form-check-label" for="impact_none">
                                        No significant impact
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification Options -->
                    <div class="mb-3">
                        <label class="form-label">Notification Preferences</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notify_requester" id="notify_requester" checked>
                            <label class="form-check-label" for="notify_requester">
                                Notify the original requester (<?= htmlspecialchars($withdrawal['withdrawn_by_name'] ?? 'Unknown') ?>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notify_project_manager" id="notify_project_manager" checked>
                            <label class="form-check-label" for="notify_project_manager">
                                Notify project management team
                            </label>
                        </div>
                        <?php if ($withdrawal['status'] === 'released'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_receiver" id="notify_receiver" checked>
                                <label class="form-check-label" for="notify_receiver">
                                    Notify the asset receiver (<?= htmlspecialchars($withdrawal['receiver_name'] ?? 'Unknown') ?>)
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Confirmation -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmCancellation" required>
                            <label class="form-check-label" for="confirmCancellation">
                                I understand that this action cannot be undone and confirm the cancellation of this withdrawal request.
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Go Back
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>Cancel Withdrawal
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to cancel this withdrawal or it is not in a cancelable status.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Withdrawal Summary -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Withdrawal Summary
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Request ID:</dt>
                    <dd class="col-sm-7">#<?= $withdrawal['id'] ?></dd>
                    
                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $statusClasses = [
                            'pending' => 'bg-warning',
                            'released' => 'bg-success',
                            'returned' => 'bg-info',
                            'canceled' => 'bg-secondary'
                        ];
                        $statusClass = $statusClasses[$withdrawal['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $statusClass ?>">
                            <?= ucfirst($withdrawal['status']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-5">Asset:</dt>
                    <dd class="col-sm-7">
                        <div class="fw-medium"><?= htmlspecialchars($withdrawal['asset_name'] ?? 'Unknown Asset') ?></div>
                        <small class="text-muted"><?= htmlspecialchars($withdrawal['asset_ref'] ?? 'N/A') ?></small>
                    </dd>
                    
                    <dt class="col-sm-5">Receiver:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['receiver_name'] ?? 'Unknown') ?></dd>
                    
                    <dt class="col-sm-5">Requested By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['withdrawn_by_name'] ?? 'Unknown') ?></dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-light text-dark">
                            <?= htmlspecialchars($withdrawal['project_name'] ?? 'Unknown Project') ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-5">Request Date:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['created_at'])) ?></dd>
                    
                    <?php if (!empty($withdrawal['released_at'])): ?>
                        <dt class="col-sm-5">Released Date:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['released_at'])) ?></dd>
                    <?php endif; ?>
                </dl>
                
                <div class="mt-3">
                    <h6>Purpose:</h6>
                    <p class="text-muted small"><?= nl2br(htmlspecialchars($withdrawal['purpose'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Cancellation Impact -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Cancellation Impact
                </h6>
            </div>
            <div class="card-body">
                <?php if ($withdrawal['status'] === 'pending'): ?>
                    <div class="alert alert-info alert-sm" role="alert">
                        <strong>Low Impact:</strong> Request is still pending approval.
                    </div>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1"></i> Asset remains available</li>
                        <li><i class="bi bi-check text-success me-1"></i> No workflow disruption</li>
                        <li><i class="bi bi-check text-success me-1"></i> Easy to recreate if needed</li>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-warning alert-sm" role="alert">
                        <strong>Medium Impact:</strong> Asset has been released.
                    </div>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-exclamation-triangle text-warning me-1"></i> Asset needs to be returned</li>
                        <li><i class="bi bi-exclamation-triangle text-warning me-1"></i> May affect ongoing work</li>
                        <li><i class="bi bi-exclamation-triangle text-warning me-1"></i> Requires coordination</li>
                    </ul>
                <?php endif; ?>
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
                <p class="small text-muted mb-3">Consider these alternatives before canceling:</p>
                <div class="d-grid gap-2">
                    <?php if ($withdrawal['status'] === 'pending'): ?>
                        <a href="?route=withdrawals/edit&id=<?= $withdrawal['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i>Modify Request
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($withdrawal['status'] === 'released'): ?>
                        <a href="?route=withdrawals/return&id=<?= $withdrawal['id'] ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-arrow-return-left me-1"></i>Return Asset Instead
                        </a>
                    <?php endif; ?>
                    
                    <a href="?route=withdrawals/create" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Create New Request
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Help & Support -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-question-circle me-2"></i>Need Help?
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    If you're unsure about canceling this request, contact:
                </p>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-person me-1"></i> Asset Manager</li>
                    <li><i class="bi bi-person me-1"></i> Project Coordinator</li>
                    <li><i class="bi bi-telephone me-1"></i> IT Support</li>
                </ul>
            </div>
        </div>

        <!-- Who Can Cancel -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Who Can Cancel (MVA Workflow)
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small">
                    <li><i class="bi bi-person-check text-primary me-1"></i> System Admin: Any withdrawal</li>
                    <li><i class="bi bi-person-check text-primary me-1"></i> Request Initiator: Own requests (if still pending/active)</li>
                    <li><i class="bi bi-person-check text-primary me-1"></i> Only allowed in <strong>Pending Verification</strong>, <strong>Pending Approval</strong>, <strong>Approved</strong>, or <strong>Released</strong> status</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle custom reason field
function toggleCustomReason() {
    const reasonSelect = document.getElementById('cancellation_reason');
    const customReasonDiv = document.getElementById('customReasonDiv');
    const customReasonField = document.getElementById('custom_reason');
    
    if (reasonSelect.value === 'other') {
        customReasonDiv.style.display = 'block';
        customReasonField.required = true;
    } else {
        customReasonDiv.style.display = 'none';
        customReasonField.required = false;
        customReasonField.value = '';
    }
}

// Toggle return instructions for released assets
document.querySelectorAll('input[name="asset_return_status"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const returnInstructions = document.getElementById('returnInstructions');
        
        if (this.value === 'pending_return') {
            returnInstructions.style.display = 'block';
        } else {
            returnInstructions.style.display = 'none';
            document.getElementById('return_instructions').value = '';
        }
    });
});

// Handle impact checkboxes (no_impact should be exclusive)
document.getElementById('impact_none').addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('impact_project').checked = false;
        document.getElementById('impact_resource').checked = false;
    }
});

document.querySelectorAll('#impact_project, #impact_resource').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('impact_none').checked = false;
        }
    });
});

// Form validation
document.getElementById('cancelForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('cancellation_reason').value;
    const customReason = document.getElementById('custom_reason').value.trim();
    const confirmation = document.getElementById('confirmCancellation').checked;
    
    if (!reason) {
        e.preventDefault();
        alert('Please select a cancellation reason.');
        return false;
    }
    
    if (reason === 'other' && !customReason) {
        e.preventDefault();
        alert('Please specify the custom reason for cancellation.');
        return false;
    }
    
    if (!confirmation) {
        e.preventDefault();
        alert('Please confirm that you want to cancel this withdrawal request.');
        return false;
    }
    
    // Check asset return status for released assets
    <?php if ($withdrawal['status'] === 'released'): ?>
        const assetReturnStatus = document.querySelector('input[name="asset_return_status"]:checked');
        if (!assetReturnStatus) {
            e.preventDefault();
            alert('Please specify the asset return status.');
            return false;
        }
    <?php endif; ?>
    
    // Final confirmation
    const confirmMessage = 'Are you sure you want to cancel this withdrawal request?\n\nThis action cannot be undone.';
    if (!confirm(confirmMessage)) {
        e.preventDefault();
        return false;
    }
});

// Auto-fill additional details based on reason selection
document.getElementById('cancellation_reason').addEventListener('change', function() {
    const reasonField = document.getElementById('reason');
    const selectedReason = this.options[this.selectedIndex].text;
    
    if (!reasonField.value && this.value && this.value !== 'other') {
        reasonField.value = `Withdrawal canceled due to: ${selectedReason}. `;
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
