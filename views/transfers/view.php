<?php
/**
 * ConstructLink™ Transfer View
 * Display transfer details with centralized MVA RBAC
 */

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="?route=transfers" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>
        <span class="d-none d-sm-inline">Back to Transfers</span>
    </a>

    <div class="btn-group">
        <!-- MVA Workflow Actions -->
        <?php if (canVerifyTransfer($transfer, $user)): ?>
            <a href="?route=transfers/verify&id=<?= $transfer['id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-search me-1"></i>Verify
            </a>
        <?php endif; ?>

        <?php if (canAuthorizeTransfer($transfer, $user)): ?>
            <a href="?route=transfers/approve&id=<?= $transfer['id'] ?>" class="btn btn-success btn-sm">
                <i class="bi bi-check-circle me-1"></i>Approve
            </a>
        <?php endif; ?>

        <?php if (canDispatchTransfer($transfer, $user)): ?>
            <a href="?route=transfers/dispatch&id=<?= $transfer['id'] ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-send me-1"></i>Dispatch
            </a>
        <?php endif; ?>

        <?php if (canReceiveTransfer($transfer, $user)): ?>
            <a href="?route=transfers/receive&id=<?= $transfer['id'] ?>" class="btn btn-info btn-sm">
                <i class="bi bi-check-circle me-1"></i>Complete
            </a>
        <?php endif; ?>

        <?php if (canReturnAsset($transfer, $user)): ?>
            <a href="?route=transfers/returnAsset&id=<?= $transfer['id'] ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-return-left me-1"></i>Initiate Return
            </a>
        <?php endif; ?>

        <?php if (canReceiveReturn($transfer, $user)): ?>
            <a href="?route=transfers/receive-return&id=<?= $transfer['id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-box-arrow-in-down me-1"></i>Receive Return
            </a>
        <?php endif; ?>

        <?php if (canCancelTransfer($transfer, $user)): ?>
            <a href="?route=transfers/cancel&id=<?= $transfer['id'] ?>" class="btn btn-danger btn-sm">
                <i class="bi bi-x-circle me-1"></i>Cancel
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Transfer Details -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Transfer Information
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
                        <?php
                        $statusClass = [
                            'Pending Verification' => 'warning',
                            'Pending Approval' => 'info',
                            'Approved' => 'primary',
                            'Received' => 'secondary',
                            'Completed' => 'success',
                            'Canceled' => 'danger'
                        ];
                        $badgeClass = $statusClass[$transfer['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $badgeClass ?>"><?= $transfer['status'] ?></span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Transfer Type:</strong><br>
                        <span class="text-muted"><?= ucfirst($transfer['transfer_type']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Transfer Date:</strong><br>
                        <span class="text-muted"><?= date('M j, Y', strtotime($transfer['transfer_date'])) ?></span>
                    </div>
                </div>

                <?php if ($transfer['transfer_type'] === 'temporary'): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Expected Return:</strong><br>
                        <?php if (!empty($transfer['expected_return'])): ?>
                            <span class="text-muted"><?= date('M j, Y', strtotime($transfer['expected_return'])) ?></span>
                            <?php 
                            $today = date('Y-m-d');
                            $expectedReturn = $transfer['expected_return'];
                            $currentReturnStatus = $transfer['return_status'] ?? 'not_returned';
                            
                            // Only show overdue if not yet returned and completed
                            if ($transfer['status'] === 'Completed' && $currentReturnStatus === 'not_returned'):
                                if ($expectedReturn < $today): ?>
                                    <br><span class="badge bg-danger mt-1">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        <?= abs((strtotime($today) - strtotime($expectedReturn)) / (60*60*24)) ?> days overdue
                                    </span>
                                <?php elseif ($expectedReturn <= date('Y-m-d', strtotime('+7 days'))): ?>
                                    <br><span class="badge bg-warning mt-1">
                                        <i class="bi bi-clock me-1"></i>Due soon
                                    </span>
                                <?php endif;
                            elseif ($currentReturnStatus === 'returned'): ?>
                                <br><span class="badge bg-success mt-1">
                                    <i class="bi bi-check-circle me-1"></i>Returned on time
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Return Status:</strong><br>
                        <?php 
                        $currentReturnStatus = $transfer['return_status'] ?? 'not_returned';
                        $returnStatusBadges = [
                            'not_returned' => 'bg-secondary',
                            'in_return_transit' => 'bg-warning text-dark',
                            'returned' => 'bg-success'
                        ];
                        $returnStatusLabels = [
                            'not_returned' => 'Not Returned',
                            'in_return_transit' => 'In Return Transit',
                            'returned' => 'Returned'
                        ];
                        $returnStatusIcons = [
                            'not_returned' => 'bi-clock',
                            'in_return_transit' => 'bi-truck',
                            'returned' => 'bi-check-circle'
                        ];
                        ?>
                        <span class="badge <?= $returnStatusBadges[$currentReturnStatus] ?? 'bg-secondary' ?>">
                            <i class="<?= $returnStatusIcons[$currentReturnStatus] ?? 'bi-question' ?> me-1"></i>
                            <?= $returnStatusLabels[$currentReturnStatus] ?? 'Unknown' ?>
                        </span>
                        
                        <?php if (!empty($transfer['actual_return'])): ?>
                            <br><span class="text-muted small mt-1">
                                Returned: <?= date('M j, Y', strtotime($transfer['actual_return'])) ?>
                            </span>
                        <?php elseif ($currentReturnStatus === 'in_return_transit' && !empty($transfer['return_initiation_date'])): ?>
                            <?php 
                            $daysInTransit = floor((time() - strtotime($transfer['return_initiation_date'])) / (60*60*24));
                            $transitBadgeClass = $daysInTransit > 3 ? 'bg-danger' : ($daysInTransit > 1 ? 'bg-warning text-dark' : 'bg-info');
                            ?>
                            <br><span class="badge <?= $transitBadgeClass ?> mt-1 small">
                                <?= $daysInTransit ?> day<?= $daysInTransit != 1 ? 's' : '' ?> in transit
                            </span>
                        <?php elseif ($transfer['status'] === 'Completed' && $currentReturnStatus === 'not_returned'): ?>
                            <br><span class="text-warning small mt-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>Awaiting return
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

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

        <!-- Asset Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-box-seam me-2"></i>Asset Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Asset Reference:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['asset_ref']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Asset Name:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['asset_name']) ?></span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Category:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['category_name'] ?? 'Unknown') ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Current Status:</strong><br>
                        <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $transfer['asset_status'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-building me-2"></i>Project Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>From Project:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['from_project_name']) ?></span>
                        <?php if (!empty($transfer['from_project_location'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($transfer['from_project_location']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>To Project:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['to_project_name']) ?></span>
                        <?php if (!empty($transfer['to_project_location'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($transfer['to_project_location']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <?php if ($transfer['transfer_type'] === 'temporary' && $transfer['status'] === 'Completed'): ?>
        <!-- Return Workflow -->
        <div class="card mb-4">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="card-title mb-0 text-warning">
                    <i class="bi bi-arrow-return-left me-2"></i>Return Workflow
                </h6>
            </div>
            <div class="card-body">
                <?php 
                $currentReturnStatus = $transfer['return_status'] ?? 'not_returned';
                ?>
                
                <div class="return-timeline">
                    <!-- Step 1: Not Returned -->
                    <div class="timeline-item <?= $currentReturnStatus === 'not_returned' ? 'active' : ($currentReturnStatus !== 'not_returned' ? 'completed' : '') ?>">
                        <div class="timeline-marker <?= $currentReturnStatus === 'not_returned' ? 'bg-warning' : 'bg-success' ?>">
                            <i class="bi <?= $currentReturnStatus === 'not_returned' ? 'bi-clock' : 'bi-check' ?> text-white"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Asset at Destination</h6>
                            <small class="text-muted">Ready for return initiation</small>
                        </div>
                    </div>
                    
                    <!-- Step 2: In Transit -->
                    <div class="timeline-item <?= $currentReturnStatus === 'in_return_transit' ? 'active' : ($currentReturnStatus === 'returned' ? 'completed' : '') ?>">
                        <div class="timeline-marker <?= $currentReturnStatus === 'in_return_transit' ? 'bg-warning' : ($currentReturnStatus === 'returned' ? 'bg-success' : 'bg-secondary') ?>">
                            <i class="bi <?= $currentReturnStatus === 'in_return_transit' ? 'bi-truck' : ($currentReturnStatus === 'returned' ? 'bi-check' : 'bi-circle') ?> text-white"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Return Transit</h6>
                            <?php if ($currentReturnStatus === 'in_return_transit' && !empty($transfer['return_initiation_date'])): ?>
                                <small class="text-muted">
                                    Initiated: <?= date('M j, g:i A', strtotime($transfer['return_initiation_date'])) ?>
                                    <?php 
                                    $daysInTransit = floor((time() - strtotime($transfer['return_initiation_date'])) / (60*60*24));
                                    ?>
                                    <br><?= $daysInTransit ?> day<?= $daysInTransit != 1 ? 's' : '' ?> in transit
                                </small>
                            <?php elseif ($currentReturnStatus === 'returned'): ?>
                                <small class="text-success">Transit completed</small>
                            <?php else: ?>
                                <small class="text-muted">Pending initiation</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Step 3: Returned -->
                    <div class="timeline-item <?= $currentReturnStatus === 'returned' ? 'completed' : '' ?>">
                        <div class="timeline-marker <?= $currentReturnStatus === 'returned' ? 'bg-success' : 'bg-secondary' ?>">
                            <i class="bi <?= $currentReturnStatus === 'returned' ? 'bi-check-circle' : 'bi-circle' ?> text-white"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Back at Origin</h6>
                            <?php if ($currentReturnStatus === 'returned' && !empty($transfer['return_receipt_date'])): ?>
                                <small class="text-success">
                                    Received: <?= date('M j, g:i A', strtotime($transfer['return_receipt_date'])) ?>
                                </small>
                            <?php else: ?>
                                <small class="text-muted">Awaiting receipt</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($currentReturnStatus === 'in_return_transit'): ?>
                <div class="alert alert-warning p-2 mt-3">
                    <small>
                        <i class="bi bi-truck me-1"></i>
                        <strong>Asset in transit</strong> - Waiting for receipt at origin project
                    </small>
                </div>
                <?php elseif ($currentReturnStatus === 'not_returned' && !empty($transfer['expected_return'])): ?>
                    <?php if ($transfer['expected_return'] < date('Y-m-d')): ?>
                    <div class="alert alert-danger p-2 mt-3">
                        <small>
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Overdue return</strong> - Expected back <?= date('M j', strtotime($transfer['expected_return'])) ?>
                        </small>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transfer Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Transfer Timeline
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

                    <?php if ($transfer['status'] !== 'Pending Verification'): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-<?= $transfer['status'] === 'Canceled' ? 'danger' : 'success' ?>"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">
                                <?php if ($transfer['status'] === 'Canceled'): ?>
                                    Transfer Canceled
                                <?php elseif ($transfer['status'] === 'Pending Approval'): ?>
                                    Transfer Verified
                                <?php elseif (in_array($transfer['status'], ['Approved', 'Received', 'Completed'])): ?>
                                    Transfer Approved
                                <?php endif; ?>
                            </h6>
                            <p class="timeline-text">
                                <?php if (!empty($transfer['verified_by_name'])): ?>
                                    By: <?= htmlspecialchars($transfer['verified_by_name']) ?><br>
                                <?php elseif (!empty($transfer['approved_by_name'])): ?>
                                    By: <?= htmlspecialchars($transfer['approved_by_name']) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($transfer['verification_date'])): ?>
                                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['verification_date'])) ?></small>
                                <?php elseif (!empty($transfer['approval_date'])): ?>
                                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['approval_date'])) ?></small>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array($transfer['status'], ['Received', 'Completed'])): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Transfer Received</h6>
                            <p class="timeline-text">
                                <?php if (!empty($transfer['received_by_name'])): ?>
                                    By: <?= htmlspecialchars($transfer['received_by_name']) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($transfer['receipt_date'])): ?>
                                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['receipt_date'])) ?></small>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($transfer['status'] === 'Completed'): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Transfer Completed</h6>
                            <p class="timeline-text">
                                Asset moved to destination project<br>
                                <?php if (!empty($transfer['completed_by_name'])): ?>
                                    By: <?= htmlspecialchars($transfer['completed_by_name']) ?><br>
                                <?php endif; ?>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['updated_at'])) ?></small>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($transfer['actual_return'])): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-secondary"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Asset Returned</h6>
                            <p class="timeline-text">
                                Asset returned to original project<br>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['actual_return'])) ?></small>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=assets/view&id=<?= $transfer['asset_id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>View Asset Details
                    </a>
                    <a href="?route=projects/view&id=<?= $transfer['from_project'] ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-building me-1"></i>View From Project
                    </a>
                    <a href="?route=projects/view&id=<?= $transfer['to_project'] ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-building me-1"></i>View To Project
                    </a>
                </div>
            </div>
        </div>

        <!-- Transfer Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Additional Information
                </h6>
            </div>
            <div class="card-body">
                <p><strong>Created:</strong><br>
                <?= date('M j, Y g:i A', strtotime($transfer['created_at'])) ?></p>

                <p><strong>Last Updated:</strong><br>
                <?= date('M j, Y g:i A', strtotime($transfer['updated_at'])) ?></p>

                <?php if ($transfer['transfer_type'] === 'temporary'): ?>
                    <div class="alert alert-info p-2">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            This is a temporary transfer. The asset should be returned to the original project when no longer needed.
                        </small>
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
$pageTitle = 'Transfer Details - ConstructLink™';
include APP_ROOT . '/views/layouts/main.php';
?>
