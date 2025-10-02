<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
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
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Important:</strong> Canceling this withdrawal request will:
                    <ul class="mb-0 mt-2">
                        <li>Mark the request as canceled</li>
                        <li>Make the asset available for other requests</li>
                        <?php if ($withdrawal['status'] === 'released'): ?>
                            <li><strong>Return the asset to available status</strong></li>
                        <?php endif; ?>
                        <li>This action cannot be undone</li>
                    </ul>
                </div>
                
                <form method="POST" action="?route=withdrawals/cancel&id=<?= $withdrawal['id'] ?>" id="cancelForm">
                    <?= CSRFProtection::generateToken() ?>
                    
                    <!-- Cancellation Reason -->
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="reason" 
                                  name="reason" 
                                  rows="4" 
                                  required
                                  placeholder="Please provide a reason for canceling this withdrawal request..."></textarea>
                        <div class="form-text">
                            A reason is required for audit purposes and to help improve our processes.
                        </div>
                    </div>
                    
                    <!-- Common Reasons (Quick Select) -->
                    <div class="mb-3">
                        <label class="form-label">Common Reasons (Click to use)</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setReason('No longer needed')">
                                No longer needed
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setReason('Project postponed')">
                                Project postponed
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setReason('Found alternative asset')">
                                Found alternative
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setReason('Requested by mistake')">
                                Requested by mistake
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setReason('Asset no longer available')">
                                Asset unavailable
                            </button>
                        </div>
                    </div>
                    
                    <!-- Confirmation Checkbox -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmCancel" required>
                            <label class="form-check-label" for="confirmCancel">
                                I understand that this action cannot be undone and confirm that I want to cancel this withdrawal request.
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Go Back
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>Cancel Request
                        </button>
                    </div>
                </form>
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
                    
                    <dt class="col-sm-5">Status:</dt>
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
                        <div class="fw-medium"><?= htmlspecialchars($withdrawal['asset_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($withdrawal['asset_ref']) ?></small>
                    </dd>
                    
                    <dt class="col-sm-5">Receiver:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['receiver_name']) ?></dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-light text-dark">
                            <?= htmlspecialchars($withdrawal['project_name']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-5">Requested By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?></dd>
                    
                    <dt class="col-sm-5">Request Date:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['created_at'])) ?></dd>
                    
                    <?php if ($withdrawal['expected_return']): ?>
                        <dt class="col-sm-5">Expected Return:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['expected_return'])) ?></dd>
                    <?php endif; ?>
                </dl>
                
                <div class="mt-3">
                    <h6>Purpose:</h6>
                    <p class="text-muted small"><?= nl2br(htmlspecialchars($withdrawal['purpose'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Impact Notice -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Cancellation Impact
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Asset will become available for other requests
                    </li>
                    <?php if ($withdrawal['status'] === 'released'): ?>
                        <li class="mb-2">
                            <i class="bi bi-arrow-return-left text-info me-2"></i>
                            Asset status will be reset to "Available"
                        </li>
                    <?php endif; ?>
                    <li class="mb-2">
                        <i class="bi bi-file-text text-primary me-2"></i>
                        Cancellation will be logged for audit trail
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-bell text-warning me-2"></i>
                        Relevant parties may be notified
                    </li>
                    <li class="mb-0">
                        <i class="bi bi-x-circle text-danger me-2"></i>
                        This action cannot be reversed
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-question-circle me-2"></i>Need Help?
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    If you're unsure about canceling this request, contact:
                </p>
                <ul class="list-unstyled small mb-0">
                    <li><strong>Warehouse:</strong> ext. 123</li>
                    <li><strong>Asset Manager:</strong> ext. 456</li>
                    <li><strong>IT Support:</strong> ext. 789</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Set reason from quick select buttons
function setReason(reason) {
    document.getElementById('reason').value = reason;
}

// Form validation
document.getElementById('cancelForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('reason').value.trim();
    const confirmCheckbox = document.getElementById('confirmCancel');
    
    if (!reason) {
        e.preventDefault();
        alert('Please provide a reason for cancellation.');
        document.getElementById('reason').focus();
        return false;
    }
    
    if (!confirmCheckbox.checked) {
        e.preventDefault();
        alert('Please confirm that you want to cancel this withdrawal request.');
        return false;
    }
    
    // Final confirmation
    if (!confirm('Are you absolutely sure you want to cancel this withdrawal request? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});

// Auto-resize textarea
document.getElementById('reason').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
