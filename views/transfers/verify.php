<?php
/**
 * ConstructLink™ Transfer Verification View
 * Project Manager verification step in MVA workflow
 */

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>
        <span class="d-none d-sm-inline">Back to Transfer</span>
    </a>
</div>

<!-- Transfer Information -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Transfer Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Transfer ID:</strong><br>
                        <span class="text-muted">#<?= htmlspecialchars($transfer['id']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-warning"><?= $transfer['status'] ?></span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Asset:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['asset_name']) ?> (<?= htmlspecialchars($transfer['asset_ref']) ?>)</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Transfer Type:</strong><br>
                        <span class="text-muted"><?= ucfirst($transfer['transfer_type']) ?></span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>From Project:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['from_project_name']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>To Project:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['to_project_name']) ?></span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Reason for Transfer:</strong><br>
                        <p class="text-muted mt-1"><?= htmlspecialchars($transfer['reason']) ?></p>
                    </div>
                </div>
                
                <?php if (!empty($transfer['notes'])): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Notes:</strong><br>
                        <p class="text-muted mt-1"><?= htmlspecialchars($transfer['notes']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Verification Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-check-circle me-2"></i>Verification Decision
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (canVerifyTransfer($transfer, $user)): ?>
                <form method="POST" action="?route=transfers/verify&id=<?= $transfer['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Verification Notes</label>
                        <textarea class="form-control" id="verification_notes" name="verification_notes" rows="4" 
                                  placeholder="Please provide verification notes, including any concerns or recommendations..."><?= htmlspecialchars($_POST['verification_notes'] ?? '') ?></textarea>
                        <div class="form-text">
                            Explain your verification decision and any relevant details about the transfer request.
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Verify Transfer
                        </button>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to verify this transfer or it is not in a verifiable status.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Verification Guidelines -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Verification Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">As a Project Manager, verify:</h6>
                    <ul class="mb-0">
                        <li>The transfer is necessary for project operations</li>
                        <li>The asset is available and in good condition</li>
                        <li>The destination project can properly utilize the asset</li>
                        <li>The transfer timeline is reasonable</li>
                        <li>Any temporary transfer has a clear return plan</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Transfer Timeline -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Current Status
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Transfer Requested</h6>
                            <p class="timeline-text">
                                By: <?= htmlspecialchars($transfer['initiated_by_name']) ?><br>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['created_at'])) ?></small>
                            </p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Pending Verification</h6>
                            <p class="timeline-text">
                                Awaiting Project Manager verification<br>
                                <small class="text-muted">Current step</small>
                            </p>
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

.timeline-title {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.timeline-text {
    margin: 0;
    font-size: 0.85rem;
    color: #6c757d;
}
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'Verify Transfer - ConstructLink™';
include APP_ROOT . '/views/layouts/main.php';
?> 