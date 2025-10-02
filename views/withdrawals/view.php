<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';

// RBAC helpers for MVA workflow
function canVerifyWithdrawal($withdrawal, $userRole, $roleConfig) {
    if ($userRole === 'System Admin') return true;
    return $withdrawal['status'] === 'Pending Verification' && in_array($userRole, $roleConfig['withdrawals/verify'] ?? []);
}
function canApproveWithdrawal($withdrawal, $userRole, $roleConfig) {
    if ($userRole === 'System Admin') return true;
    return $withdrawal['status'] === 'Pending Approval' && in_array($userRole, $roleConfig['withdrawals/approve'] ?? []);
}
function canReleaseWithdrawal($withdrawal, $userRole, $roleConfig) {
    if ($userRole === 'System Admin') return true;
    return $withdrawal['status'] === 'Approved' && in_array($userRole, $roleConfig['withdrawals/release'] ?? []);
}
function canReturnWithdrawal($withdrawal, $userRole, $roleConfig) {
    if ($userRole === 'System Admin') return true;
    return $withdrawal['status'] === 'Released' && in_array($userRole, $roleConfig['withdrawals/return'] ?? []);
}
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
        <i class="bi bi-eye me-2"></i>
        Withdrawal Details #<?= $withdrawal['id'] ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=withdrawals" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Withdrawals
        </a>
        <?php if (canVerifyWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
            <a href="?route=withdrawals/verify&id=<?= $withdrawal['id'] ?>" class="btn btn-warning ms-2">
                <i class="bi bi-search me-1"></i>Verify
            </a>
        <?php endif; ?>
        <?php if (canApproveWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
            <a href="?route=withdrawals/approve&id=<?= $withdrawal['id'] ?>" class="btn btn-info ms-2">
                <i class="bi bi-person-check me-1"></i>Approve
            </a>
        <?php endif; ?>
        <?php if (canReleaseWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
            <a href="?route=withdrawals/release&id=<?= $withdrawal['id'] ?>" class="btn btn-success ms-2">
                <i class="bi bi-check-circle me-1"></i>Release
            </a>
        <?php endif; ?>
        <?php if (canReturnWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
            <a href="?route=withdrawals/return&id=<?= $withdrawal['id'] ?>" class="btn btn-primary ms-2">
                <i class="bi bi-arrow-return-left me-1"></i>Return
            </a>
        <?php endif; ?>
        <?php if (canCancelWithdrawal($withdrawal, $userRole, $roleConfig, $user['id'])): ?>
            <a href="?route=withdrawals/cancel&id=<?= $withdrawal['id'] ?>" class="btn btn-danger ms-2">
                <i class="bi bi-x-circle me-1"></i>Cancel
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Withdrawal Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Withdrawal Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Request ID:</dt>
                            <dd class="col-sm-7">#<?= $withdrawal['id'] ?></dd>
                            
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $statusClasses = [
                                    'Pending Verification' => 'bg-warning',
                                    'Pending Approval' => 'bg-info',
                                    'Approved' => 'bg-primary',
                                    'Released' => 'bg-success',
                                    'Returned' => 'bg-info',
                                    'Canceled' => 'bg-secondary'
                                ];
                                $statusClass = $statusClasses[$withdrawal['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $statusClass ?>">
                                    <?= $withdrawal['status'] ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Requested By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?></dd>
                            
                            <dt class="col-sm-5">Receiver:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['receiver_name']) ?></dd>
                            
                            <dt class="col-sm-5">Request Date:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($withdrawal['created_at'])) ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <?php if ($withdrawal['expected_return']): ?>
                                <dt class="col-sm-5">Expected Return:</dt>
                                <dd class="col-sm-7">
                                    <span class="<?= strtotime($withdrawal['expected_return']) < time() && $withdrawal['status'] === 'Released' ? 'text-danger fw-bold' : '' ?>">
                                        <?= date('M j, Y', strtotime($withdrawal['expected_return'])) ?>
                                    </span>
                                    <?php if (strtotime($withdrawal['expected_return']) < time() && $withdrawal['status'] === 'Released'): ?>
                                        <?php $daysOverdue = floor((time() - strtotime($withdrawal['expected_return'])) / (60 * 60 * 24)); ?>
                                        <br><small class="text-danger"><?= $daysOverdue ?> days overdue</small>
                                    <?php endif; ?>
                                </dd>
                            <?php endif; ?>
                            
                            <?php if ($withdrawal['release_date']): ?>
                                <dt class="col-sm-5">Released Date:</dt>
                                <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($withdrawal['release_date'])) ?></dd>
                                
                                <dt class="col-sm-5">Released By:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['released_by_name'] ?? 'N/A') ?></dd>
                            <?php endif; ?>
                            
                            <?php if ($withdrawal['actual_return']): ?>
                                <dt class="col-sm-5">Actual Return:</dt>
                                <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['actual_return'])) ?></dd>
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
                
                <!-- Notes -->
                <?php if ($withdrawal['notes']): ?>
                    <div class="mb-3">
                        <h6>Notes:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($withdrawal['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Asset Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Asset Reference:</dt>
                            <dd class="col-sm-7">
                                <a href="?route=assets/view&id=<?= $withdrawal['asset_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($withdrawal['asset_ref']) ?>
                                </a>
                            </dd>
                            
                            <dt class="col-sm-5">Asset Name:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['asset_name']) ?></dd>
                            
                            <dt class="col-sm-5">Category:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['category_name']) ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Project:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($withdrawal['project_name']) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Current Status:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $assetStatusClasses = [
                                    'available' => 'bg-success',
                                    'in_use' => 'bg-primary',
                                    'under_maintenance' => 'bg-warning',
                                    'retired' => 'bg-secondary'
                                ];
                                $assetStatusClass = $assetStatusClasses[$withdrawal['asset_status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $assetStatusClass ?>">
                                    <?= ucfirst(str_replace('_', ' ', $withdrawal['asset_status'])) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Location:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['project_location'] ?? 'N/A') ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-gear me-2"></i>Actions
                </h6>
            </div>
            <div class="card-body">
                <?php if (canVerifyWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
                    <a href="?route=withdrawals/verify&id=<?= $withdrawal['id'] ?>" class="btn btn-warning w-100 mb-2">
                        <i class="bi bi-search me-1"></i>Verify
                    </a>
                <?php endif; ?>
                
                <?php if (canApproveWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
                    <a href="?route=withdrawals/approve&id=<?= $withdrawal['id'] ?>" class="btn btn-info w-100 mb-2">
                        <i class="bi bi-person-check me-1"></i>Approve
                    </a>
                <?php endif; ?>
                
                <?php if (canReleaseWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
                    <a href="?route=withdrawals/release&id=<?= $withdrawal['id'] ?>" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-check-circle me-1"></i>Release
                    </a>
                <?php endif; ?>
                
                <?php if (canReturnWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
                    <a href="?route=withdrawals/return&id=<?= $withdrawal['id'] ?>" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-arrow-return-left me-1"></i>Return
                    </a>
                <?php endif; ?>
                
                <?php if (canCancelWithdrawal($withdrawal, $userRole, $roleConfig, $user['id'])): ?>
                    <a href="?route=withdrawals/cancel&id=<?= $withdrawal['id'] ?>" class="btn btn-outline-danger w-100 mb-2">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                <?php endif; ?>
                
                <hr>
                
                <a href="?route=assets/view&id=<?= $withdrawal['asset_id'] ?>" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-box me-1"></i>View Asset Details
                </a>
                
                <button onclick="window.print()" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-printer me-1"></i>Print Details
                </button>
            </div>
        </div>
        
        <!-- Timeline -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Timeline
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <!-- Request Created -->
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Request Created</h6>
                            <p class="timeline-text">
                                Withdrawal request submitted by <?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?>
                            </p>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($withdrawal['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <!-- Asset Released -->
                    <?php if ($withdrawal['release_date']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Asset Released</h6>
                                <p class="timeline-text">
                                    Asset released by <?= htmlspecialchars($withdrawal['released_by_name'] ?? 'N/A') ?>
                                </p>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($withdrawal['release_date'])) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Asset Returned -->
                    <?php if ($withdrawal['actual_return']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Asset Returned</h6>
                                <p class="timeline-text">Asset returned and marked as available</p>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($withdrawal['actual_return'])) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Canceled -->
                    <?php if ($withdrawal['status'] === 'Canceled'): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Request Canceled</h6>
                                <p class="timeline-text">Withdrawal request was canceled</p>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($withdrawal['updated_at'])) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Status Summary -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Status Summary
                </h6>
            </div>
            <div class="card-body">
                <?php if ($withdrawal['status'] === 'Released' && $withdrawal['release_date']): ?>
                    <?php $daysOut = floor((time() - strtotime($withdrawal['release_date'])) / (60 * 60 * 24)); ?>
                    <div class="text-center">
                        <h4 class="text-primary"><?= $daysOut ?></h4>
                        <small class="text-muted">Days Out</small>
                    </div>
                    
                    <?php if ($withdrawal['expected_return']): ?>
                        <hr>
                        <?php if (strtotime($withdrawal['expected_return']) < time()): ?>
                            <?php $daysOverdue = floor((time() - strtotime($withdrawal['expected_return'])) / (60 * 60 * 24)); ?>
                            <div class="text-center">
                                <h4 class="text-danger"><?= $daysOverdue ?></h4>
                                <small class="text-muted">Days Overdue</small>
                            </div>
                        <?php else: ?>
                            <?php $daysRemaining = floor((strtotime($withdrawal['expected_return']) - time()) / (60 * 60 * 24)); ?>
                            <div class="text-center">
                                <h4 class="text-success"><?= $daysRemaining ?></h4>
                                <small class="text-muted">Days Remaining</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php elseif ($withdrawal['status'] === 'Returned'): ?>
                    <div class="text-center">
                        <i class="bi bi-check-circle-fill text-success display-4"></i>
                        <h6 class="mt-2">Asset Returned</h6>
                        <small class="text-muted">Withdrawal completed successfully</small>
                    </div>
                <?php elseif ($withdrawal['status'] === 'Pending Verification'): ?>
                    <div class="text-center">
                        <i class="bi bi-search text-warning display-4"></i>
                        <h6 class="mt-2">Awaiting Verification</h6>
                        <small class="text-muted">Request pending verification</small>
                    </div>
                <?php elseif ($withdrawal['status'] === 'Pending Approval'): ?>
                    <div class="text-center">
                        <i class="bi bi-person-check text-info display-4"></i>
                        <h6 class="mt-2">Awaiting Approval</h6>
                        <small class="text-muted">Request pending approval</small>
                    </div>
                <?php elseif ($withdrawal['status'] === 'Approved'): ?>
                    <div class="text-center">
                        <i class="bi bi-check-circle-fill text-primary display-4"></i>
                        <h6 class="mt-2">Approved</h6>
                        <small class="text-muted">Request approved</small>
                    </div>
                <?php elseif ($withdrawal['status'] === 'Released'): ?>
                    <div class="text-center">
                        <i class="bi bi-check-circle-fill text-success display-4"></i>
                        <h6 class="mt-2">Asset Released</h6>
                        <small class="text-muted">Asset released</small>
                    </div>
                <?php elseif ($withdrawal['status'] === 'Canceled'): ?>
                    <div class="text-center">
                        <i class="bi bi-x-circle-fill text-secondary display-4"></i>
                        <h6 class="mt-2">Request Canceled</h6>
                        <small class="text-muted">Withdrawal was canceled</small>
                    </div>
                <?php endif; ?>
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

@media print {
    .btn, .card-header, .timeline-marker {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
