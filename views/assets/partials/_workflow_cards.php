<?php
/**
 * Workflow Cards Partial
 * Displays MVA workflow statistics for admin roles
 */
?>

<!-- MVA Workflow Statistics Cards - Only for roles that manage approvals -->
<?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h6 class="card-title text-warning">
                    <i class="bi bi-clock-history me-2"></i>Pending Verification
                </h6>
                <h4 class="text-warning mb-0"><?= $workflowStats['pending_verification'] ?? 0 ?></h4>
                <small class="text-muted">Items awaiting Asset Director review</small>
                <?php if (in_array($userRole, ['Asset Director', 'System Admin']) && ($workflowStats['pending_verification'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=assets&workflow_status=pending_verification" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-search me-1"></i>Review Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h6 class="card-title text-info">
                    <i class="bi bi-person-check me-2"></i>Pending Authorization
                </h6>
                <h4 class="text-info mb-0"><?= $workflowStats['pending_authorization'] ?? 0 ?></h4>
                <small class="text-muted">Items awaiting Finance Director approval</small>
                <?php if (in_array($userRole, ['Finance Director', 'System Admin']) && ($workflowStats['pending_authorization'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=assets&workflow_status=pending_authorization" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-check-circle me-1"></i>Approve Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h6 class="card-title text-success">
                    <i class="bi bi-check-circle-fill me-2"></i>Approved Items
                </h6>
                <h4 class="text-success mb-0"><?= $workflowStats['approved'] ?? 0 ?></h4>
                <small class="text-muted">Items ready for deployment</small>
                <?php if (($workflowStats['approved'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <small class="text-success">
                            <i class="bi bi-speedometer2 me-1"></i>
                            <?= $workflowStats['avg_approval_time_hours'] ?? 0 ?>h avg. approval time
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h6 class="card-title text-danger">
                    <i class="bi bi-x-circle me-2"></i>Rejected Items
                </h6>
                <h4 class="text-danger mb-0"><?= $workflowStats['rejected'] ?? 0 ?></h4>
                <small class="text-muted">Items requiring attention</small>
                <?php if (($workflowStats['rejected'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=assets&workflow_status=rejected" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-eye me-1"></i>Review Issues
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
