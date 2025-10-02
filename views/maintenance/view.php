<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-tools me-2"></i>
        Maintenance #<?= $maintenance['id'] ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?route=maintenance" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Maintenance
            </a>
            <a href="?route=assets/view&id=<?= $maintenance['asset_id'] ?>" class="btn btn-outline-info">
                <i class="bi bi-box me-1"></i>View Asset
            </a>
        </div>
        
        <?php 
        // Load role configuration for MVA permissions
        $roleConfig = require APP_ROOT . '/config/roles.php';
        $verifyRoles = $roleConfig['maintenance/verify'] ?? [];
        $authorizeRoles = $roleConfig['maintenance/authorize'] ?? [];
        ?>
        
        <?php if (in_array($userRole, $verifyRoles) && $maintenance['status'] === 'Pending Verification'): ?>
            <div class="btn-group me-2">
                <a href="?route=maintenance/verify&id=<?= $maintenance['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-search me-1"></i>Verify Maintenance
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (in_array($userRole, $authorizeRoles) && $maintenance['status'] === 'Pending Approval'): ?>
            <div class="btn-group me-2">
                <a href="?route=maintenance/authorize&id=<?= $maintenance['id'] ?>" class="btn btn-info">
                    <i class="bi bi-person-check me-1"></i>Authorize Maintenance
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($maintenance['status'] === 'Approved' && $auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman'])): ?>
            <div class="btn-group me-2">
                <a href="?route=maintenance/start&id=<?= $maintenance['id'] ?>" class="btn btn-success">
                    <i class="bi bi-play-circle me-1"></i>Start Maintenance
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($maintenance['status'] === 'in_progress' && $auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman'])): ?>
            <div class="btn-group me-2">
                <a href="?route=maintenance/complete&id=<?= $maintenance['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Complete Maintenance
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (in_array($maintenance['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'in_progress']) && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
            <div class="btn-group">
                <a href="?route=maintenance/edit&id=<?= $maintenance['id'] ?>" class="btn btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <button type="button" class="btn btn-outline-danger" onclick="cancelMaintenance(<?= $maintenance['id'] ?>)">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Status Alert -->
<?php if (in_array($maintenance['status'], ['Pending Verification', 'Pending Approval', 'Approved']) && strtotime($maintenance['scheduled_date']) < time()): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Overdue:</strong> This maintenance was scheduled for <?= date('M j, Y', strtotime($maintenance['scheduled_date'])) ?> and is now overdue.
    </div>
<?php endif; ?>

<!-- Maintenance Details -->
<div class="row">
    <div class="col-lg-8">
        <!-- Basic Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Maintenance Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Maintenance ID:</dt>
                            <dd class="col-sm-8">#<?= $maintenance['id'] ?></dd>

                            <dt class="col-sm-4">Asset:</dt>
                            <dd class="col-sm-8">
                                <a href="?route=assets/view&id=<?= $maintenance['asset_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($maintenance['asset_ref']) ?> - <?= htmlspecialchars($maintenance['asset_name']) ?>
                                </a>
                            </dd>

                            <dt class="col-sm-4">Type:</dt>
                            <dd class="col-sm-8">
                                <?php
                                $typeClasses = [
                                    'preventive' => 'bg-info',
                                    'corrective' => 'bg-warning',
                                    'emergency' => 'bg-danger'
                                ];
                                $typeClass = $typeClasses[$maintenance['type']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $typeClass ?>">
                                    <?= ucfirst($maintenance['type']) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-4">Priority:</dt>
                            <dd class="col-sm-8">
                                <?php
                                $priorityClasses = [
                                    'low' => 'bg-success',
                                    'medium' => 'bg-warning',
                                    'high' => 'bg-danger',
                                    'urgent' => 'bg-dark'
                                ];
                                $priorityClass = $priorityClasses[$maintenance['priority']] ?? 'bg-warning';
                                ?>
                                <span class="badge <?= $priorityClass ?>">
                                    <?= ucfirst($maintenance['priority']) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <?php
                                $statusClasses = [
                                    'Pending Verification' => 'bg-warning',
                                    'Pending Approval' => 'bg-info',
                                    'Approved' => 'bg-success',
                                    'in_progress' => 'bg-primary',
                                    'completed' => 'bg-success',
                                    'canceled' => 'bg-secondary'
                                ];
                                $statusClass = $statusClasses[$maintenance['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $statusClass ?>">
                                    <?= ucfirst(str_replace('_', ' ', $maintenance['status'])) ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Scheduled Date:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y', strtotime($maintenance['scheduled_date'])) ?></dd>

                            <?php if ($maintenance['completed_date']): ?>
                                <dt class="col-sm-5">Completed Date:</dt>
                                <dd class="col-sm-7"><?= date('M j, Y', strtotime($maintenance['completed_date'])) ?></dd>
                            <?php endif; ?>

                            <dt class="col-sm-5">Assigned To:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($maintenance['assigned_to'] ?: 'Unassigned') ?></dd>

                            <?php if ($maintenance['estimated_cost']): ?>
                                <dt class="col-sm-5">Estimated Cost:</dt>
                                <dd class="col-sm-7">₱<?= number_format($maintenance['estimated_cost'], 2) ?></dd>
                            <?php endif; ?>

                            <?php if ($maintenance['actual_cost']): ?>
                                <dt class="col-sm-5">Actual Cost:</dt>
                                <dd class="col-sm-7">₱<?= number_format($maintenance['actual_cost'], 2) ?></dd>
                            <?php endif; ?>

                            <dt class="col-sm-5">Created:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($maintenance['created_at'])) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-file-text me-2"></i>Description
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($maintenance['description'])) ?></p>
            </div>
        </div>

        <!-- Parts Used -->
        <?php if ($maintenance['parts_used']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>Parts Used
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($maintenance['parts_used'])) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Completion Notes -->
        <?php if ($maintenance['completion_notes']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clipboard-check me-2"></i>Completion Notes
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($maintenance['completion_notes'])) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Next Maintenance -->
        <?php if ($maintenance['next_maintenance_date']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calendar-event me-2"></i>Next Maintenance
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <strong>Scheduled for:</strong> <?= date('M j, Y', strtotime($maintenance['next_maintenance_date'])) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Asset Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-sm-5">Reference:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['asset_ref']) ?></dd>

                    <dt class="col-sm-5">Name:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['asset_name']) ?></dd>

                    <dt class="col-sm-5">Category:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['category_name']) ?></dd>

                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['project_name']) ?></dd>

                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $assetStatusClasses = [
                            'available' => 'success',
                            'in_use' => 'primary',
                            'borrowed' => 'warning',
                            'under_maintenance' => 'info',
                            'retired' => 'secondary'
                        ];
                        $assetStatusClass = $assetStatusClasses[$maintenance['asset_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $assetStatusClass ?>">
                            <?= ucfirst(str_replace('_', ' ', $maintenance['asset_status'])) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- MVA Workflow -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>MVA Workflow
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <!-- Maker Step -->
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">1. Maker</h6>
                            <p class="timeline-text">Request created by <?= htmlspecialchars($maintenance['created_by_name'] ?? 'N/A') ?></p>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($maintenance['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <!-- Verifier Step -->
                    <div class="timeline-item <?= in_array($maintenance['status'], ['Pending Approval', 'Approved', 'in_progress', 'completed']) ? 'completed' : ($maintenance['status'] === 'Pending Verification' ? 'active' : '') ?>">
                        <div class="timeline-marker bg-<?= in_array($maintenance['status'], ['Pending Approval', 'Approved', 'in_progress', 'completed']) ? 'success' : ($maintenance['status'] === 'Pending Verification' ? 'warning' : 'light') ?>"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">2. Verifier</h6>
                            <?php if ($maintenance['verified_by_name']): ?>
                                <p class="timeline-text">Verified by <?= htmlspecialchars($maintenance['verified_by_name']) ?></p>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($maintenance['verification_date'])) ?></small>
                            <?php else: ?>
                                <p class="timeline-text">
                                    <?= $maintenance['status'] === 'Pending Verification' ? 'Awaiting verification by Project Manager' : 'Pending verification' ?>
                                </p>
                                <small class="text-muted"><?= $maintenance['status'] === 'Pending Verification' ? 'Current step' : 'Pending' ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Authorizer Step -->
                    <div class="timeline-item <?= in_array($maintenance['status'], ['Approved', 'in_progress', 'completed']) ? 'completed' : ($maintenance['status'] === 'Pending Approval' ? 'active' : '') ?>">
                        <div class="timeline-marker bg-<?= in_array($maintenance['status'], ['Approved', 'in_progress', 'completed']) ? 'success' : ($maintenance['status'] === 'Pending Approval' ? 'info' : 'light') ?>"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">3. Authorizer</h6>
                            <?php if ($maintenance['approved_by_name']): ?>
                                <p class="timeline-text">Authorized by <?= htmlspecialchars($maintenance['approved_by_name']) ?></p>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($maintenance['approval_date'])) ?></small>
                            <?php else: ?>
                                <p class="timeline-text">
                                    <?= $maintenance['status'] === 'Pending Approval' ? 'Awaiting authorization by Asset Director' : 'Pending authorization' ?>
                                </p>
                                <small class="text-muted"><?= $maintenance['status'] === 'Pending Approval' ? 'Current step' : 'Pending' ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Execution Step -->
                    <div class="timeline-item <?= $maintenance['status'] === 'completed' ? 'completed' : ($maintenance['status'] === 'in_progress' ? 'active' : '') ?>">
                        <div class="timeline-marker bg-<?= $maintenance['status'] === 'completed' ? 'success' : ($maintenance['status'] === 'in_progress' ? 'primary' : 'light') ?>"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">4. Execution</h6>
                            <?php if ($maintenance['status'] === 'completed'): ?>
                                <p class="timeline-text">Maintenance completed</p>
                                <small class="text-muted"><?= $maintenance['completed_date'] ? date('M j, Y', strtotime($maintenance['completed_date'])) : 'N/A' ?></small>
                            <?php elseif ($maintenance['status'] === 'in_progress'): ?>
                                <p class="timeline-text">Maintenance in progress</p>
                                <small class="text-muted">Current step</small>
                            <?php else: ?>
                                <p class="timeline-text">Maintenance work execution</p>
                                <small class="text-muted">Pending</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MVA Notes -->
        <?php if ($maintenance['notes']): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-sticky me-2"></i>MVA Notes
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <?= nl2br(htmlspecialchars($maintenance['notes'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=assets/view&id=<?= $maintenance['asset_id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box"></i> View Asset Details
                    </a>
                    <a href="?route=maintenance&asset_id=<?= $maintenance['asset_id'] ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-tools"></i> Asset Maintenance History
                    </a>
                    <?php if ($maintenance['status'] === 'completed' && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="scheduleNext(<?= $maintenance['asset_id'] ?>)">
                            <i class="bi bi-calendar-plus"></i> Schedule Next Maintenance
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Maintenance History -->
        <?php if (!empty($maintenanceHistory) && count($maintenanceHistory) > 1): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>Maintenance History
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach (array_slice($maintenanceHistory, 0, 5) as $history): ?>
                            <?php if ($history['id'] != $maintenance['id']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-<?= $history['status'] === 'completed' ? 'success' : 'warning' ?>"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title"><?= ucfirst($history['type']) ?> Maintenance</h6>
                                        <p class="timeline-text small">
                                            <?= date('M j, Y', strtotime($history['scheduled_date'])) ?>
                                            <span class="badge bg-<?= $history['status'] === 'completed' ? 'success' : 'warning' ?> ms-1">
                                                <?= ucfirst($history['status']) ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($maintenanceHistory) > 5): ?>
                        <div class="text-center mt-2">
                            <a href="?route=maintenance&asset_id=<?= $maintenance['asset_id'] ?>" class="btn btn-sm btn-outline-primary">
                                View All History
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Maintenance Modal -->
<div class="modal fade" id="cancelMaintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?route=maintenance/cancel">
                <?= CSRFProtection::getTokenField() ?>
                <input type="hidden" name="maintenance_id" value="<?= $maintenance['id'] ?>">
                <div class="modal-body">
                    <p>Are you sure you want to cancel this maintenance?</p>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for cancellation:</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cancelMaintenance(maintenanceId) {
    const modal = new bootstrap.Modal(document.getElementById('cancelMaintenanceModal'));
    modal.show();
}

function scheduleNext(assetId) {
    window.location.href = `?route=maintenance/create&asset_id=${assetId}`;
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -31px;
    top: 15px;
    width: 2px;
    height: calc(100% + 5px);
    background-color: #dee2e6;
}

.timeline-title {
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.timeline-text {
    margin-bottom: 0;
    color: #6c757d;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Maintenance #' . $maintenance['id'] . ' - ConstructLink™';
$pageHeader = 'Maintenance #' . $maintenance['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Maintenance', 'url' => '?route=maintenance'],
    ['title' => 'View Details', 'url' => '?route=maintenance/view&id=' . $maintenance['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
