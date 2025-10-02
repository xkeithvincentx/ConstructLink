<?php
/**
 * ConstructLink™ Request Index View - Unified Request Management
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-clipboard-check me-2"></i>
        Unified Request Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if (in_array($user['role_name'], $roleConfig['requests/create'] ?? [])): ?>
            <a href="?route=requests/create" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle me-1"></i>New Request
            </a>
        <?php endif; ?>
        
        <?php if (in_array($user['role_name'], $roleConfig['requests/export'] ?? [])): ?>
            <a href="?route=requests/export<?= !empty($_GET) ? '&' . http_build_query($_GET) : '' ?>" class="btn btn-success">
                <i class="bi bi-download me-1"></i>Export
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $messages = [
        'request_created' => ['type' => 'success', 'text' => 'Request has been created successfully.'],
        'request_updated' => ['type' => 'success', 'text' => 'Request has been updated successfully.'],
        'request_submitted' => ['type' => 'success', 'text' => 'Request has been submitted for review.'],
        'request_approved' => ['type' => 'success', 'text' => 'Request has been approved successfully.'],
        'request_declined' => ['type' => 'danger', 'text' => 'Request has been declined.'],
        'request_forwarded' => ['type' => 'info', 'text' => 'Request has been forwarded for further review.']
    ];
    
    $message = $messages[$_GET['message']] ?? null;
    if ($message):
    ?>
        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $message['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'export_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>Failed to export requests. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Requests</h6>
                        <h3 class="mb-0"><?= $requestStats['total_requests'] ?? 0 ?></h3>
                        <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                            <small class="opacity-75">
                                <i class="bi bi-currency-dollar me-1"></i><?= formatCurrency($requestStats['total_estimated_value'] ?? 0) ?> est. value
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clipboard-data display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary-dark">
                <a href="?route=requests" class="text-white text-decoration-none">
                    <small><i class="bi bi-eye me-1"></i>View All Requests</small>
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Review</h6>
                        <h3 class="mb-0"><?= ($requestStats['submitted'] ?? 0) + ($requestStats['reviewed'] ?? 0) + ($requestStats['forwarded'] ?? 0) ?></h3>
                        <small class="opacity-75">
                            <i class="bi bi-person-check me-1"></i><?= $requestStats['my_pending_reviews'] ?? 0 ?> require your action
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock-history display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-warning-dark">
                <?php if (($requestStats['my_pending_reviews'] ?? 0) > 0): ?>
                    <a href="?route=requests&status=<?= in_array($userRole, $roleConfig['requests/review'] ?? []) ? 'Submitted' : 'Reviewed' ?>" class="text-white text-decoration-none">
                        <small><i class="bi bi-arrow-right-circle me-1"></i>Review Now</small>
                    </a>
                <?php else: ?>
                    <small class="text-white-50">No pending reviews</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Approved</h6>
                        <h3 class="mb-0"><?= $requestStats['approved'] ?? 0 ?></h3>
                        <small class="opacity-75">
                            <i class="bi bi-graph-up me-1"></i><?= $requestStats['approval_rate'] ?? 0 ?>% approval rate
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-success-dark">
                <a href="?route=requests&status=Approved" class="text-white text-decoration-none">
                    <small><i class="bi bi-filter me-1"></i>View Approved</small>
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card <?= (($requestStats['critical'] ?? 0) + ($requestStats['urgent'] ?? 0)) > 0 ? 'bg-danger' : 'bg-secondary' ?> text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Urgent/Critical</h6>
                        <h3 class="mb-0"><?= ($requestStats['critical'] ?? 0) + ($requestStats['urgent'] ?? 0) ?></h3>
                        <small class="opacity-75">
                            <i class="bi bi-exclamation-circle me-1"></i><?= $requestStats['overdue_requests'] ?? 0 ?> overdue
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-danger-dark">
                <?php if ((($requestStats['critical'] ?? 0) + ($requestStats['urgent'] ?? 0)) > 0): ?>
                    <a href="?route=requests&urgency=Critical" class="text-white text-decoration-none">
                        <small><i class="bi bi-eye me-1"></i>Review Priority Items</small>
                    </a>
                <?php else: ?>
                    <small class="text-white-50">No urgent requests</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MVA Workflow Status Cards -->
<?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager', 'Procurement Officer'])): ?>
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h6 class="card-title text-info">
                    <i class="bi bi-pencil-square me-2"></i>Draft/Submitted
                </h6>
                <h4 class="text-info mb-0"><?= ($requestStats['draft'] ?? 0) + ($requestStats['submitted'] ?? 0) ?></h4>
                <small class="text-muted">Awaiting initial review</small>
                <?php if (in_array($userRole, $roleConfig['requests/review'] ?? []) && ($requestStats['submitted'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=requests&status=Submitted" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye me-1"></i>Review (<?= $requestStats['submitted'] ?? 0 ?>)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h6 class="card-title text-warning">
                    <i class="bi bi-search me-2"></i>Under Review
                </h6>
                <h4 class="text-warning mb-0"><?= ($requestStats['reviewed'] ?? 0) + ($requestStats['forwarded'] ?? 0) ?></h4>
                <small class="text-muted">Project Manager verified</small>
                <?php if (in_array($userRole, $roleConfig['requests/approve'] ?? []) && (($requestStats['reviewed'] ?? 0) + ($requestStats['forwarded'] ?? 0)) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=requests&status=Reviewed" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-check-circle me-1"></i>Approve Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h6 class="card-title text-success">
                    <i class="bi bi-check-circle-fill me-2"></i>Procurement Ready
                </h6>
                <h4 class="text-success mb-0"><?= $requestStats['ready_for_procurement'] ?? 0 ?></h4>
                <small class="text-muted">Approved, awaiting PO creation</small>
                <?php if (in_array($userRole, $roleConfig['procurement-orders/create'] ?? []) && ($requestStats['ready_for_procurement'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=procurement-orders/approved-requests" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-plus-circle me-1"></i>Create POs
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h6 class="card-title text-primary">
                    <i class="bi bi-truck me-2"></i>In Procurement
                </h6>
                <h4 class="text-primary mb-0"><?= $requestStats['procured'] ?? 0 ?></h4>
                <small class="text-muted">PO created and in progress</small>
                <?php if (($requestStats['procured'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <small class="text-primary">
                            <i class="bi bi-clock me-1"></i>
                            <?= $requestStats['avg_procurement_time_days'] ?? 0 ?> days avg. delivery
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delivery Alerts Section -->
<?php if (isset($deliveryAlerts) && !empty($deliveryAlerts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delivery Alerts
                    <span class="badge bg-dark ms-2"><?= count($deliveryAlerts) ?></span>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach (array_slice($deliveryAlerts, 0, 3) as $alert): ?>
                    <div class="col-md-4">
                        <div class="alert alert-<?= ($alert['type'] ?? 'info') === 'overdue' ? 'danger' : (($alert['type'] ?? 'info') === 'discrepancy' ? 'warning' : 'info') ?> mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($alert['title'] ?? 'Alert') ?></strong>
                                    <div class="small mt-1"><?= htmlspecialchars($alert['message'] ?? '') ?></div>
                                    <small class="text-muted">Request #<?= $alert['request_id'] ?? 'N/A' ?></small>
                                </div>
                                <a href="?route=requests/view&id=<?= $alert['request_id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($deliveryAlerts) > 3): ?>
                <div class="text-center">
                    <button class="btn btn-sm btn-outline-warning" onclick="toggleAllAlerts()">
                        <i class="bi bi-chevron-down me-1"></i>Show All Alerts (<?= count($deliveryAlerts) - 3 ?> more)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
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
        <form method="GET" action="?route=requests" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Draft" <?= ($_GET['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="Submitted" <?= ($_GET['status'] ?? '') === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="Reviewed" <?= ($_GET['status'] ?? '') === 'Reviewed' ? 'selected' : '' ?>>Reviewed</option>
                    <option value="Forwarded" <?= ($_GET['status'] ?? '') === 'Forwarded' ? 'selected' : '' ?>>Forwarded</option>
                    <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Declined" <?= ($_GET['status'] ?? '') === 'Declined' ? 'selected' : '' ?>>Declined</option>
                    <option value="Procured" <?= ($_GET['status'] ?? '') === 'Procured' ? 'selected' : '' ?>>Procured</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Request Type</label>
                <select name="request_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Material" <?= ($_GET['request_type'] ?? '') === 'Material' ? 'selected' : '' ?>>Material</option>
                    <option value="Tool" <?= ($_GET['request_type'] ?? '') === 'Tool' ? 'selected' : '' ?>>Tool</option>
                    <option value="Equipment" <?= ($_GET['request_type'] ?? '') === 'Equipment' ? 'selected' : '' ?>>Equipment</option>
                    <option value="Service" <?= ($_GET['request_type'] ?? '') === 'Service' ? 'selected' : '' ?>>Service</option>
                    <option value="Petty Cash" <?= ($_GET['request_type'] ?? '') === 'Petty Cash' ? 'selected' : '' ?>>Petty Cash</option>
                    <option value="Other" <?= ($_GET['request_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Project</label>
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Urgency</label>
                <select name="urgency" class="form-select">
                    <option value="">All Urgency</option>
                    <option value="Normal" <?= ($_GET['urgency'] ?? '') === 'Normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="Urgent" <?= ($_GET['urgency'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="Critical" <?= ($_GET['urgency'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" 
                       placeholder="Search by description, project, or requester..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=requests" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Request List</h6>
        <small class="text-muted">
            Showing <?= count($requests ?? []) ?> of <?= $pagination['total'] ?? 0 ?> requests
        </small>
    </div>
    <div class="card-body">
        <?php if (!empty($requests)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Project</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Delivery Status</th>
                            <th>Procurement</th>
                            <th>Requested By</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr class="<?= $request['urgency'] === 'Critical' ? 'table-danger' : ($request['urgency'] === 'Urgent' ? 'table-warning' : '') ?>">
                                <td>
                                    <a href="?route=requests/view&id=<?= $request['id'] ?>" class="text-decoration-none">
                                        #<?= $request['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($request['request_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars(substr($request['description'], 0, 50)) ?><?= strlen($request['description']) > 50 ? '...' : '' ?></div>
                                    <?php if ($request['category']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($request['category']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($request['project_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $urgencyClass = [
                                        'Normal' => 'bg-secondary',
                                        'Urgent' => 'bg-warning',
                                        'Critical' => 'bg-danger'
                                    ];
                                    $class = $urgencyClass[$request['urgency']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $class ?>">
                                        <?= htmlspecialchars($request['urgency']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'Draft' => 'bg-secondary',
                                        'Submitted' => 'bg-warning',
                                        'Reviewed' => 'bg-info',
                                        'Forwarded' => 'bg-primary',
                                        'Approved' => 'bg-success',
                                        'Declined' => 'bg-danger',
                                        'Procured' => 'bg-dark'
                                    ];
                                    $class = $statusClass[$request['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $class ?>">
                                        <?= htmlspecialchars($request['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $deliveryStatus = $request['overall_delivery_status'] ?? 'Not Started';
                                    $deliveryStatusClass = [
                                        'Completed' => 'bg-success',
                                        'In Progress' => 'bg-primary',
                                        'Scheduled' => 'bg-info',
                                        'Ready for Delivery' => 'bg-warning',
                                        'Processing' => 'bg-secondary',
                                        'Awaiting Procurement' => 'bg-light text-dark',
                                        'Not Started' => 'bg-light text-muted'
                                    ];
                                    $deliveryClass = $deliveryStatusClass[$deliveryStatus] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $deliveryClass ?> small">
                                        <?= htmlspecialchars($deliveryStatus) ?>
                                    </span>
                                    
                                    <!-- Delivery Alert Icons -->
                                    <?php if (isset($request['has_delivery_alert']) && $request['has_delivery_alert']): ?>
                                        <br><i class="bi bi-exclamation-triangle text-warning small" title="Has delivery alerts"></i>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($request['is_overdue']) && $request['is_overdue']): ?>
                                        <br><i class="bi bi-clock text-danger small" title="Overdue delivery"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($request['procurement_id']) && $request['procurement_id']): ?>
                                        <a href="?route=procurement-orders/view&id=<?= $request['procurement_id'] ?>" class="text-decoration-none">
                                            <span class="badge bg-info small">
                                                PO #<?= htmlspecialchars($request['po_number'] ?? $request['procurement_id']) ?>
                                            </span>
                                        </a>
                                        <?php if (isset($request['procurement_status'])): ?>
                                            <br>
                                            <?php
                                            $procStatusClass = [
                                                'Draft' => 'bg-secondary',
                                                'Pending' => 'bg-warning',
                                                'Approved' => 'bg-success',
                                                'Rejected' => 'bg-danger',
                                                'Scheduled for Delivery' => 'bg-info',
                                                'In Transit' => 'bg-primary',
                                                'Delivered' => 'bg-success',
                                                'Received' => 'bg-dark'
                                            ];
                                            $procClass = $procStatusClass[$request['procurement_status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?= $procClass ?> small">
                                                <?= htmlspecialchars($request['procurement_status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">No PO</span>
                                        <?php if (canCreatePOFromRequest($request, $userRole)): ?>
                                            <br>
                                            <a href="?route=procurement-orders/createFromRequest&request_id=<?= $request['id'] ?>" class="btn btn-xs btn-outline-primary">
                                                <i class="bi bi-plus-circle"></i> Create PO
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small"><?= htmlspecialchars($request['requested_by_name']) ?></div>
                                </td>
                                <td>
                                    <div class="small"><?= date('M j, Y', strtotime($request['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('g:i A', strtotime($request['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php $currentUser = $auth->getCurrentUser(); ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=requests/view&id=<?= $request['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($request['status'] === 'Submitted' && in_array($user['role_name'], $roleConfig['requests/review'] ?? [])): ?>
                                            <a href="?route=requests/review&id=<?= $request['id'] ?>" 
                                               class="btn btn-outline-info" title="Review/Forward">
                                                <i class="bi bi-arrow-right-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array($request['status'], ['Reviewed', 'Forwarded']) && in_array($user['role_name'], $roleConfig['requests/approve'] ?? [])): ?>
                                            <a href="?route=requests/approve&id=<?= $request['id'] ?>" 
                                               class="btn btn-outline-success" title="Approve">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($request['status'] === 'Approved' && in_array($user['role_name'], $roleConfig['requests/generate-po'] ?? []) && empty($request['procurement_id'])): ?>
                                            <a href="?route=requests/generate-po&request_id=<?= $request['id'] ?>" 
                                               class="btn btn-outline-primary" title="Create PO">
                                                <i class="bi bi-plus-circle"></i>
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
                <nav aria-label="Request pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=requests&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=requests&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=requests&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No requests found</h5>
                <p class="text-muted">Try adjusting your filters or create a new request.</p>
                <?php if (in_array($user['role_name'], $roleConfig['requests/create'] ?? [])): ?>
                    <a href="?route=requests/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create First Request
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportRequests(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('format', format);
    window.location.href = '?route=requests/export&' + params.toString();
}

function toggleAllAlerts() {
    const hiddenAlerts = document.querySelectorAll('.hidden-alert');
    const button = event.target;
    
    if (hiddenAlerts.length > 0) {
        hiddenAlerts.forEach(alert => {
            alert.style.display = alert.style.display === 'none' ? 'block' : 'none';
        });
        
        const isShowing = hiddenAlerts[0].style.display !== 'none';
        button.innerHTML = isShowing ? 
            '<i class="bi bi-chevron-up me-1"></i>Hide Additional Alerts' : 
            '<i class="bi bi-chevron-down me-1"></i>Show All Alerts (' + hiddenAlerts.length + ' more)';
    }
}

// Auto-refresh for real-time updates
setInterval(function() {
    // Only refresh if no filters are applied to avoid disrupting user workflow
    if (window.location.search === '?route=requests' || window.location.search === '') {
        location.reload();
    }
}, 300000); // Refresh every 5 minutes

// Highlight overdue requests
document.addEventListener('DOMContentLoaded', function() {
    const overdueRows = document.querySelectorAll('tr[data-overdue="true"]');
    overdueRows.forEach(row => {
        row.classList.add('table-danger');
    });
    
    // Add tooltips for delivery status badges
    const deliveryBadges = document.querySelectorAll('[title]');
    deliveryBadges.forEach(badge => {
        new bootstrap.Tooltip(badge);
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Request Management - ConstructLink™';
$pageHeader = 'Unified Request Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Requests', 'url' => '?route=requests']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
