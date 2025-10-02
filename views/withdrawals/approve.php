<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-person-check me-2"></i>
        Approve Withdrawal Request
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Details
        </a>
    </div>
</div>

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
        <!-- Withdrawal Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Withdrawal Summary
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Request ID:</dt>
                            <dd class="col-sm-7">#<?= $withdrawal['id'] ?></dd>
                            
                            <dt class="col-sm-5">Asset:</dt>
                            <dd class="col-sm-7">
                                <strong><?= htmlspecialchars($withdrawal['asset_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($withdrawal['asset_ref']) ?></small>
                            </dd>
                            
                            <dt class="col-sm-5">Project:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['project_name']) ?></dd>
                            
                            <dt class="col-sm-5">Receiver:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['receiver_name']) ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Requested By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?></dd>
                            
                            <dt class="col-sm-5">Request Date:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($withdrawal['created_at'])) ?></dd>
                            
                            <dt class="col-sm-5">Current Status:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-info">Pending Approval</span>
                            </dd>
                            
                            <?php if ($withdrawal['expected_return']): ?>
                                <dt class="col-sm-5">Expected Return:</dt>
                                <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['expected_return'])) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
                
                <hr>
                
                <!-- Purpose -->
                <div class="mb-3">
                    <h6>Purpose:</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($withdrawal['purpose'])) ?></p>
                </div>
                
                <!-- Verification Status -->
                <?php if ($withdrawal['verified_by']): ?>
                    <div class="alert alert-success">
                        <h6 class="alert-heading">
                            <i class="bi bi-check-circle me-2"></i>Verification Complete
                        </h6>
                        <p class="mb-0">
                            This withdrawal has been verified by the Site Inventory Clerk on 
                            <?= date('M j, Y g:i A', strtotime($withdrawal['verification_date'])) ?>.
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Previous Notes -->
                <?php if ($withdrawal['notes']): ?>
                    <div class="mb-3">
                        <h6>Previous Notes:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($withdrawal['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Approval Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-check-circle me-2"></i>Approval Decision
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=withdrawals/approve&id=<?= $withdrawal['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-2"></i>Approval Checklist
                        </h6>
                        <p>Please review the following before making your approval decision:</p>
                        <ul class="mb-0">
                            <li>Withdrawal request has been verified by authorized personnel</li>
                            <li>Asset is required for legitimate project activities</li>
                            <li>Receiver is authorized and accountable for the asset</li>
                            <li>Project budget and resource allocation is appropriate</li>
                            <li>Risk assessment is acceptable</li>
                        </ul>
                    </div>
                    
                    <!-- Approval Notes -->
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes</label>
                        <textarea class="form-control" 
                                  id="approval_notes" 
                                  name="approval_notes" 
                                  rows="4" 
                                  placeholder="Add any approval notes, conditions, or instructions..."></textarea>
                        <div class="form-text">
                            Optional: Add any conditions, instructions, or comments regarding this approval.
                        </div>
                    </div>
                    
                    <!-- Approval Confirmation -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmApproval" required>
                            <label class="form-check-label" for="confirmApproval">
                                I confirm that I have reviewed this withdrawal request and approve it for processing
                            </label>
                        </div>
                    </div>
                    
                    <!-- Risk Assessment -->
                    <div class="mb-3">
                        <label class="form-label">Risk Assessment</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="risk_level" id="riskLow" value="low" required>
                            <label class="form-check-label" for="riskLow">
                                <span class="badge bg-success me-2">Low Risk</span>
                                Standard withdrawal with minimal risk
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="risk_level" id="riskMedium" value="medium">
                            <label class="form-check-label" for="riskMedium">
                                <span class="badge bg-warning me-2">Medium Risk</span>
                                Requires additional monitoring
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="risk_level" id="riskHigh" value="high">
                            <label class="form-check-label" for="riskHigh">
                                <span class="badge bg-danger me-2">High Risk</span>
                                Requires special authorization and monitoring
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Approve Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Asset Details -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Asset Details
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Reference:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['asset_ref']) ?></dd>
                    
                    <dt class="col-sm-5">Name:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['asset_name']) ?></dd>
                    
                    <dt class="col-sm-5">Category:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['category_name']) ?></dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['project_name']) ?></dd>
                    
                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-success">Available</span>
                    </dd>
                </dl>
                
                <hr>
                
                <a href="?route=assets/view&id=<?= $withdrawal['asset_id'] ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-eye me-1"></i>View Asset Details
                </a>
            </div>
        </div>
        
        <!-- Project Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-briefcase me-2"></i>Project Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['project_name']) ?></dd>
                    
                    <dt class="col-sm-5">Location:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['project_location'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-5">Manager:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($user['full_name']) ?></dd>
                </dl>
            </div>
        </div>
        
        <!-- MVA Workflow -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>MVA Workflow
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">1. Maker</h6>
                            <p class="timeline-text">Request created by <?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?></p>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($withdrawal['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">2. Verifier</h6>
                            <p class="timeline-text">Verified by Site Inventory Clerk</p>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($withdrawal['verification_date'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="timeline-item active">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">3. Authorizer</h6>
                            <p class="timeline-text">Awaiting approval by <?= htmlspecialchars($user['full_name']) ?></p>
                            <small class="text-muted">Current step</small>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-light"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">4. Release</h6>
                            <p class="timeline-text">Asset release</p>
                            <small class="text-muted">Pending</small>
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

.timeline-item.completed .timeline-marker {
    background-color: #28a745 !important;
}

.timeline-item.active .timeline-marker {
    background-color: #17a2b8 !important;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #dee2e6;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 14px;
    font-weight: 600;
}

.timeline-text {
    margin-bottom: 5px;
    font-size: 13px;
    color: #6c757d;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>