<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if (in_array($userRole, $roleConfig['transfers/create'] ?? [])): ?>
            <a href="?route=transfers/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">New Transfer</span>
                <span class="d-sm-none">Create</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'transfer_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Transfer request created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'transfer_streamlined'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-lightning-fill me-2"></i>Transfer completed with streamlined process! Ready for final completion.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'transfer_simplified'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check2-circle me-2"></i>Transfer created with simplified process! Awaiting final approval.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'transfer_verified'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Transfer request verified successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'transfer_approved'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Transfer request approved successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'transfer_received'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Transfer received successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'transfer_completed'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Transfer completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'transfer_canceled'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Transfer request canceled successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'asset_returned'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Asset returned successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'export_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>Failed to export transfers. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- Pending Verification -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-clock-history text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Pending Verification</h6>
                        <h3 class="mb-0"><?= $transferStats['pending_verification'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person me-1"></i><?= $transferStats['my_pending_verifications'] ?? 0 ?> for your review
                </p>
                <?php if (in_array($userRole, $roleConfig['transfers/verify'] ?? []) && ($transferStats['my_pending_verifications'] ?? 0) > 0): ?>
                    <a href="?route=transfers&status=Pending%20Verification" class="btn btn-sm btn-outline-warning w-100 mt-2">
                        <i class="bi bi-search me-1"></i>Verify Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending Approval -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-person-check text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Pending Approval</h6>
                        <h3 class="mb-0"><?= $transferStats['pending_approval'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person-check me-1"></i><?= $transferStats['my_pending_approvals'] ?? 0 ?> for your approval
                </p>
                <?php if (in_array($userRole, $roleConfig['transfers/approve'] ?? []) && ($transferStats['my_pending_approvals'] ?? 0) > 0): ?>
                    <a href="?route=transfers&status=Pending%20Approval" class="btn btn-sm btn-outline-info w-100 mt-2">
                        <i class="bi bi-check-circle me-1"></i>Approve Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Approved (Ready to Transfer) -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Approved</h6>
                        <h3 class="mb-0"><?= $transferStats['approved'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-arrow-left-right me-1"></i>Ready for transfer
                </p>
            </div>
        </div>
    </div>

    <!-- In Transit (Received) -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-box-arrow-right text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">In Transit</h6>
                        <h3 class="mb-0"><?= $transferStats['received'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clock me-1"></i><?= $transferStats['overdue_returns'] ?? 0 ?> overdue returns
                </p>
                <?php if (($transferStats['overdue_returns'] ?? 0) > 0): ?>
                    <a href="?route=transfers&transfer_type=temporary" class="btn btn-sm btn-outline-danger w-100 mt-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>Review Overdue
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Completed -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Completed</h6>
                        <h3 class="mb-0"><?= $transferStats['completed'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-percent me-1"></i><?= $transferStats['completion_rate'] ?? 0 ?>% success rate
                </p>
            </div>
        </div>
    </div>

    <!-- Temporary Transfers -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-arrow-repeat text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Temporary Transfers</h6>
                        <h3 class="mb-0"><?= $transferStats['temporary_transfers'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clock me-1"></i><?= $transferStats['pending_returns'] ?? 0 ?> pending returns
                </p>
            </div>
        </div>
    </div>

    <!-- Permanent Transfers -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-arrow-right text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Permanent Transfers</h6>
                        <h3 class="mb-0"><?= $transferStats['permanent_transfers'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-currency-dollar me-1"></i><?= formatCurrency($transferStats['permanent_transfer_value'] ?? 0) ?> value
                </p>
            </div>
        </div>
    </div>

    <!-- Canceled -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-x-circle text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Canceled</h6>
                        <h3 class="mb-0"><?= $transferStats['canceled'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-slash-circle me-1"></i>Withdrawn requests
                </p>
            </div>
        </div>
    </div>
</div>

<!-- MVA Workflow Info Banner -->
<div class="alert alert-info mb-4">
    <strong><i class="bi bi-info-circle me-2"></i>MVA Workflow:</strong>
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-info">Authorizer</span> (Asset Director) →
    <span class="badge bg-success">Approved</span> →
    <span class="badge bg-primary">In Transit</span> →
    <span class="badge bg-secondary">Completed</span>
</div>

<!-- Overdue Returns Alert -->
<?php if (!empty($overdueReturns)): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Overdue Returns Alert
        </h6>
        <p class="mb-2">There are <?= count($overdueReturns) ?> overdue temporary transfer(s) that require immediate attention:</p>
        <ul class="mb-0">
            <?php foreach (array_slice($overdueReturns, 0, 3) as $overdue): ?>
                <li>
                    <strong><?= htmlspecialchars($overdue['asset_name']) ?></strong> 
                    (<?= htmlspecialchars($overdue['asset_ref']) ?>) - 
                    <?= $overdue['days_overdue'] ?> days overdue
                    <a href="?route=transfers/view&id=<?= $overdue['id'] ?>" class="ms-2">View Details</a>
                </li>
            <?php endforeach; ?>
            <?php if (count($overdueReturns) > 3): ?>
                <li><em>... and <?= count($overdueReturns) - 3 ?> more</em></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=transfers" class="row g-3">
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending Verification" <?= ($_GET['status'] ?? '') === 'Pending Verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="Pending Approval" <?= ($_GET['status'] ?? '') === 'Pending Approval' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Received" <?= ($_GET['status'] ?? '') === 'Received' ? 'selected' : '' ?>>Received</option>
                    <option value="Completed" <?= ($_GET['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Canceled" <?= ($_GET['status'] ?? '') === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="transfer_type" class="form-label">Type</label>
                <select class="form-select" id="transfer_type" name="transfer_type">
                    <option value="">All Types</option>
                    <option value="temporary" <?= ($_GET['transfer_type'] ?? '') === 'temporary' ? 'selected' : '' ?>>Temporary</option>
                    <option value="permanent" <?= ($_GET['transfer_type'] ?? '') === 'permanent' ? 'selected' : '' ?>>Permanent</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="from_project" class="form-label">From Project</label>
                <select class="form-select" id="from_project" name="from_project">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['from_project'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="to_project" class="form-label">To Project</label>
                <select class="form-select" id="to_project" name="to_project">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['to_project'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-8">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by asset name, reference, or reason..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=transfers" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transfers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Transfer Requests</h6>
        <div class="d-flex gap-2">
                        <?php if (in_array($userRole, $roleConfig['transfers/export'] ?? [])): ?>
                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export
                </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($transfers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-arrow-left-right display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No transfer requests found</h5>
                <p class="text-muted">Try adjusting your filters or create a new transfer request.</p>
                <?php if (in_array($userRole, $roleConfig['transfers/create'] ?? [])): ?>
                    <a href="?route=transfers/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Transfer Request
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="transfersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>From → To</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Initiated By</th>
                            <th>Transfer Date</th>
                            <th>Expected Return</th>
                            <th>Return Status</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transfers as $transfer): ?>
                            <tr>
                                <td>
                                    <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="text-decoration-none">
                                        #<?= $transfer['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-box text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($transfer['asset_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($transfer['asset_ref']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($transfer['from_project_name']) ?>
                                        </span>
                                        <i class="bi bi-arrow-right mx-2 text-muted"></i>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($transfer['to_project_name']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $transfer['transfer_type'] === 'permanent' ? 'bg-warning' : 'bg-info' ?>">
                                        <?= ucfirst($transfer['transfer_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                          title="<?= htmlspecialchars($transfer['reason']) ?>">
                                        <?= htmlspecialchars($transfer['reason']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($transfer['initiated_by_name']) ?></div>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($transfer['created_at'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($transfer['transfer_date']): ?>
                                        <small><?= date('M j, Y', strtotime($transfer['transfer_date'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Not set</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transfer['expected_return'] && $transfer['transfer_type'] === 'temporary'): ?>
                                        <small class="<?= strtotime($transfer['expected_return']) < time() && $transfer['status'] === 'Completed' ? 'text-danger fw-bold' : '' ?>">
                                            <?= date('M j, Y', strtotime($transfer['expected_return'])) ?>
                                            <?php if (strtotime($transfer['expected_return']) < time() && $transfer['status'] === 'Completed'): ?>
                                                <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">N/A</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transfer['transfer_type'] === 'temporary'): ?>
                                        <?php 
                                        $returnStatus = $transfer['return_status'] ?? 'not_returned';
                                        $returnStatusBadges = [
                                            'not_returned' => 'bg-secondary',
                                            'in_return_transit' => 'bg-warning text-dark',
                                            'returned' => 'bg-success'
                                        ];
                                        $returnStatusLabels = [
                                            'not_returned' => 'Not Returned',
                                            'in_return_transit' => 'In Transit',
                                            'returned' => 'Returned'
                                        ];
                                        $returnStatusIcons = [
                                            'not_returned' => 'bi-clock',
                                            'in_return_transit' => 'bi-truck',
                                            'returned' => 'bi-check-circle'
                                        ];
                                        ?>
                                        <span class="badge <?= $returnStatusBadges[$returnStatus] ?? 'bg-secondary' ?>">
                                            <i class="<?= $returnStatusIcons[$returnStatus] ?? 'bi-question' ?> me-1"></i>
                                            <?= $returnStatusLabels[$returnStatus] ?? 'Unknown' ?>
                                        </span>
                                        
                                        <?php if ($returnStatus === 'in_return_transit' && !empty($transfer['return_initiation_date'])): ?>
                                            <?php 
                                            $daysInTransit = floor((time() - strtotime($transfer['return_initiation_date'])) / (60*60*24));
                                            $transitBadgeClass = $daysInTransit > 3 ? 'bg-danger' : ($daysInTransit > 1 ? 'bg-warning text-dark' : 'bg-info');
                                            ?>
                                            <br><span class="badge <?= $transitBadgeClass ?> mt-1" style="font-size: 0.7em;">
                                                <?= $daysInTransit ?> day<?= $daysInTransit != 1 ? 's' : '' ?> in transit
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-muted">N/A</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'Pending Verification' => 'bg-warning',
                                        'Pending Approval' => 'bg-info',
                                        'Approved' => 'bg-primary',
                                        'Received' => 'bg-secondary',
                                        'Completed' => 'bg-success',
                                        'Canceled' => 'bg-danger'
                                    ];
                                    $statusClass = $statusClasses[$transfer['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= $transfer['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <!-- MVA Workflow Actions -->
                                        <?php 
                                        // Use specific permission arrays from roles configuration
                                        $verifyRoles = $roleConfig['transfers/verify'] ?? [];
                                        $approveRoles = $roleConfig['transfers/approve'] ?? [];
                                        $receiveRoles = $roleConfig['transfers/receive'] ?? [];
                                        $completeRoles = $roleConfig['transfers/complete'] ?? [];
                                        $returnRoles = $roleConfig['transfers/returnAsset'] ?? [];
                                        $cancelRoles = $roleConfig['transfers/cancel'] ?? [];
                                        ?>
                                        
                                        <?php if (canVerifyTransfer($transfer, $user)): ?>
                                            <a href="?route=transfers/verify&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-warning" title="Verify Transfer">
                                                <i class="bi bi-search"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($userRole, $approveRoles) && $transfer['status'] === 'Pending Approval'): ?>
                                            <a href="?route=transfers/approve&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-success" title="Approve Transfer">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (canReceiveTransfer($transfer, $user)): ?>
                                            <a href="?route=transfers/receive&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-info" title="Receive Transfer">
                                                <i class="bi bi-box-arrow-in-down"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($userRole, $completeRoles) && $transfer['status'] === 'Received'): ?>
                                            <a href="?route=transfers/complete&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-primary" title="Complete Transfer">
                                                <i class="bi bi-check2-all"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($userRole, $returnRoles) && 
                                                  $transfer['transfer_type'] === 'temporary' && 
                                                  $transfer['status'] === 'Completed' && 
                                                  ($transfer['return_status'] ?? 'not_returned') === 'not_returned'): ?>
                                            <a href="?route=transfers/returnAsset&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-secondary" title="Initiate Return">
                                                <i class="bi bi-arrow-return-left"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (canReceiveReturn($transfer, $user)): ?>
                                            <a href="?route=transfers/receive-return&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-warning" title="Receive Return">
                                                <i class="bi bi-box-arrow-in-down"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($userRole, $cancelRoles) && in_array($transfer['status'], ['Pending Verification', 'Pending Approval', 'Approved'])): ?>
                                            <a href="?route=transfers/cancel&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-danger" title="Cancel Transfer">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Transfers pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=transfers&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=transfers&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=transfers&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=transfers/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Auto-refresh for pending transfers
if (document.querySelector('.badge.bg-warning')) {
    setTimeout(() => {
        location.reload();
    }, 60000); // Refresh every 60 seconds if there are pending transfers
}

// Enhanced search functionality
document.getElementById('search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});

// Date range validation
document.getElementById('date_from').addEventListener('change', function() {
    const dateTo = document.getElementById('date_to');
    if (this.value && dateTo.value && this.value > dateTo.value) {
        alert('Start date cannot be later than end date');
        this.value = '';
    }
});

document.getElementById('date_to').addEventListener('change', function() {
    const dateFrom = document.getElementById('date_from');
    if (this.value && dateFrom.value && this.value < dateFrom.value) {
        alert('End date cannot be earlier than start date');
        this.value = '';
    }
});

// Cancel transfer request
function cancelTransfer(transferId) {
    if (confirm('Are you sure you want to cancel this transfer request?')) {
        fetch(`?route=api/transfers/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ transfer_id: transferId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to cancel transfer: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while canceling the transfer');
        });
    }
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Asset Transfers - ConstructLink™';
$pageHeader = 'Asset Transfers';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Transfers', 'url' => '?route=transfers']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
