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
        <?php if (in_array($userRole, $roleConfig['maintenance/create'] ?? [])): ?>
            <a href="?route=maintenance/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Schedule Maintenance</span>
                <span class="d-sm-none">Schedule</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- MVA Workflow Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Verification</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['pending_verification'] ?? 0 ?></h3>
                        <small class="opacity-75">
                            <i class="bi bi-person me-1"></i><?= $maintenanceStats['my_pending_verifications'] ?? 0 ?> for your review
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock-history display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-warning-dark">
                <?php if (in_array($userRole, $roleConfig['maintenance/verify'] ?? []) && ($maintenanceStats['my_pending_verifications'] ?? 0) > 0): ?>
                    <a href="?route=maintenance&status=Pending%20Verification" class="text-white text-decoration-none">
                        <small><i class="bi bi-search me-1"></i>Verify Now</small>
                    </a>
                <?php else: ?>
                    <small class="text-white-50">No pending verifications</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Approval</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['pending_approval'] ?? 0 ?></h3>
                        <small class="opacity-75">
                            <i class="bi bi-person-check me-1"></i><?= $maintenanceStats['my_pending_approvals'] ?? 0 ?> for your approval
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-hourglass-split display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-info-dark">
                <?php if (in_array($userRole, $roleConfig['maintenance/authorize'] ?? []) && ($maintenanceStats['my_pending_approvals'] ?? 0) > 0): ?>
                    <a href="?route=maintenance&status=Pending%20Approval" class="text-white text-decoration-none">
                        <small><i class="bi bi-check-circle me-1"></i>Approve Now</small>
                    </a>
                <?php else: ?>
                    <small class="text-white-50">No pending approvals</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Active Maintenance</h6>
                        <h3 class="mb-0"><?= ($maintenanceStats['approved'] ?? 0) + ($maintenanceStats['in_progress'] ?? 0) ?></h3>
                        <small class="opacity-75">
                            <i class="bi bi-exclamation-triangle me-1"></i><?= $maintenanceStats['overdue'] ?? 0 ?> overdue
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-gear display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary-dark">
                <?php if (($maintenanceStats['overdue'] ?? 0) > 0): ?>
                    <a href="?route=maintenance&overdue=1" class="text-white text-decoration-none">
                        <small><i class="bi bi-exclamation-triangle me-1"></i>Review Overdue</small>
                    </a>
                <?php else: ?>
                    <small class="text-white-50">All on schedule</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Completed</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['completed'] ?? 0 ?></h3>
                        <small class="opacity-75">
                            <i class="bi bi-percent me-1"></i><?= $maintenanceStats['completion_rate'] ?? 0 ?>% completion rate
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle-fill display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-success-dark">
                <a href="?route=maintenance&status=completed" class="text-white text-decoration-none">
                    <small><i class="bi bi-filter me-1"></i>View Completed</small>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Type & Priority Cards -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card border-info h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-info">
                    <i class="bi bi-calendar-check me-2"></i>Preventive Maintenance
                </h6>
                <h4 class="text-info mb-0"><?= $maintenanceStats['preventive'] ?? 0 ?></h4>
                <small class="text-muted">Scheduled maintenance</small>
                <?php if (($maintenanceStats['preventive_due_soon'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <small class="text-warning">
                            <i class="bi bi-clock me-1"></i><?= $maintenanceStats['preventive_due_soon'] ?> due within 7 days
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-warning">
                    <i class="bi bi-tools me-2"></i>Corrective Maintenance
                </h6>
                <h4 class="text-warning mb-0"><?= $maintenanceStats['corrective'] ?? 0 ?></h4>
                <small class="text-muted">Repair and fixes</small>
                <?php if (($maintenanceStats['urgent_corrective'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <small class="text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i><?= $maintenanceStats['urgent_corrective'] ?> urgent repairs
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="card border-danger h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-danger">
                    <i class="bi bi-lightning me-2"></i>Emergency Maintenance
                </h6>
                <h4 class="text-danger mb-0"><?= $maintenanceStats['emergency'] ?? 0 ?></h4>
                <small class="text-muted">Critical repairs</small>
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                    <div class="mt-2">
                        <small class="text-danger">
                            <i class="bi bi-currency-dollar me-1"></i><?= formatCurrency($maintenanceStats['emergency_cost'] ?? 0) ?> spent
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Performance Metrics Row -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-secondary h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-secondary">
                    <i class="bi bi-speedometer2 me-2"></i>Avg. Response Time
                </h6>
                <h4 class="text-secondary mb-0"><?= $maintenanceStats['avg_response_time_hours'] ?? 0 ?>h</h4>
                <small class="text-muted">From request to start</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-primary">
                    <i class="bi bi-clock me-2"></i>Avg. Completion Time
                </h6>
                <h4 class="text-primary mb-0"><?= $maintenanceStats['avg_completion_time_hours'] ?? 0 ?>h</h4>
                <small class="text-muted">Average repair duration</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-success">
                    <i class="bi bi-graph-up me-2"></i>Equipment Uptime
                </h6>
                <h4 class="text-success mb-0"><?= $maintenanceStats['equipment_uptime_percentage'] ?? 0 ?>%</h4>
                <small class="text-muted">Overall availability</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <h6 class="card-title text-warning">
                    <i class="bi bi-x-circle me-2"></i>Canceled Tasks
                </h6>
                <h4 class="text-warning mb-0"><?= $maintenanceStats['canceled'] ?? 0 ?></h4>
                <small class="text-muted">Maintenance canceled</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=maintenance" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending Verification" <?= ($_GET['status'] ?? '') === 'Pending Verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="Pending Approval" <?= ($_GET['status'] ?? '') === 'Pending Approval' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="preventive" <?= ($_GET['type'] ?? '') === 'preventive' ? 'selected' : '' ?>>Preventive</option>
                    <option value="corrective" <?= ($_GET['type'] ?? '') === 'corrective' ? 'selected' : '' ?>>Corrective</option>
                    <option value="emergency" <?= ($_GET['type'] ?? '') === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by asset name, remarks, or technician..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=maintenance" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Maintenance Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Maintenance Records</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($maintenance)): ?>
            <div class="text-center py-5">
                <i class="bi bi-tools display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No maintenance records found</h5>
                <p class="text-muted">Try adjusting your filters or schedule new maintenance.</p>
                <?php if (in_array($userRole, $roleConfig['maintenance/create'] ?? [])): ?>
                    <a href="?route=maintenance/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Schedule Maintenance
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="maintenanceTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th>Scheduled Date</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenance as $record): ?>
                            <tr>
                                <td>
                                    <a href="?route=maintenance/view&id=<?= $record['id'] ?>" class="text-decoration-none">
                                        #<?= $record['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-box text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($record['asset_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($record['asset_ref']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                    $typeClasses = [
                        'preventive' => 'bg-info',
                        'corrective' => 'bg-warning',
                        'emergency' => 'bg-danger'
                    ];
                                    $typeClass = $typeClasses[$record['type']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $typeClass ?>">
                                        <?= ucfirst($record['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= date('M j, Y', strtotime($record['scheduled_date'])) ?></div>
                                        <small class="text-muted"><?= date('g:i A', strtotime($record['scheduled_date'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($record['assigned_to'] ?? 'Unassigned') ?></div>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'Pending Verification' => 'bg-warning',
                                        'Pending Approval' => 'bg-info',
                                        'Approved' => 'bg-success',
                                        'in_progress' => 'bg-primary',
                                        'completed' => 'bg-success',
                                        'canceled' => 'bg-secondary'
                                    ];
                                    $statusClass = $statusClasses[$record['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $record['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                    $priorityClasses = [
                        'low' => 'bg-success',
                        'medium' => 'bg-warning',
                        'high' => 'bg-danger',
                        'urgent' => 'bg-dark'
                    ];
                                    $priorityClass = $priorityClasses[$record['priority'] ?? 'medium'] ?? 'bg-warning';
                                    ?>
                                    <span class="badge <?= $priorityClass ?>">
                                        <?= ucfirst($record['priority'] ?? 'Medium') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=maintenance/view&id=<?= $record['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php 
                                        // Load role configuration for MVA permissions
                                        $roleConfig = require APP_ROOT . '/config/roles.php';
                                        $userRole = $user['role_name'] ?? '';
                                        
                                        // MVA Action Buttons
                                        $verifyRoles = $roleConfig['maintenance/verify'] ?? [];
                                        $authorizeRoles = $roleConfig['maintenance/authorize'] ?? [];
                                        ?>
                                        
                                        <?php if (in_array($userRole, $verifyRoles) && $record['status'] === 'Pending Verification'): ?>
                                            <a href="?route=maintenance/verify&id=<?= $record['id'] ?>" 
                                               class="btn btn-outline-warning" title="Verify Maintenance">
                                                <i class="bi bi-search"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($userRole, $authorizeRoles) && $record['status'] === 'Pending Approval'): ?>
                                            <a href="?route=maintenance/authorize&id=<?= $record['id'] ?>" 
                                               class="btn btn-outline-info" title="Authorize Maintenance">
                                                <i class="bi bi-person-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($record['status'] === 'Approved' && in_array($userRole, $roleConfig['maintenance/start'] ?? [])): ?>
                                            <a href="?route=maintenance/start&id=<?= $record['id'] ?>" 
                                               class="btn btn-outline-success" title="Start Maintenance">
                                                <i class="bi bi-play-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($record['status'] === 'in_progress' && in_array($userRole, $roleConfig['maintenance/complete'] ?? [])): ?>
                                            <a href="?route=maintenance/complete&id=<?= $record['id'] ?>" 
                                               class="btn btn-outline-success" title="Mark as Completed">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($record['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'in_progress']) && in_array($userRole, ['System Admin', 'Asset Director'])): ?>
                                            <a href="?route=maintenance/edit&id=<?= $record['id'] ?>" 
                                               class="btn btn-outline-secondary" title="Edit Maintenance">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($record['status'], ['Pending Verification', 'Pending Approval', 'Approved']) && in_array($userRole, $roleConfig['maintenance/cancel'] ?? [])): ?>
                                            <a href="?route=maintenance/cancel&id=<?= $record['id'] ?>" 
                                               class="btn btn-outline-danger" title="Cancel Maintenance">
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
                <nav aria-label="Maintenance pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, function($v, $k) { return $k !== 'page'; }, ARRAY_FILTER_USE_BOTH), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= http_build_query(array_filter($_GET, function($v, $k) { return $k !== 'page'; }, ARRAY_FILTER_USE_BOTH), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, function($v, $k) { return $k !== 'page'; }, ARRAY_FILTER_USE_BOTH), '', '&') ?>">
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
    window.location.href = '?route=maintenance&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Auto-refresh for pending maintenance
if (document.querySelector('.badge.bg-warning')) {
    setTimeout(() => {
        location.reload();
    }, 60000); // Refresh every 60 seconds if there are pending maintenance tasks
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Asset Maintenance - ConstructLink™';
$pageHeader = 'Asset Maintenance';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Maintenance', 'url' => '?route=maintenance']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
