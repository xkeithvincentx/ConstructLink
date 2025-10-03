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
                        <h3 class="mb-0"><?= $maintenanceStats['pending_verification'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person me-1"></i><?= $maintenanceStats['my_pending_verifications'] ?? 0 ?> for your review
                </p>
                <?php if (in_array($userRole, $roleConfig['maintenance/verify'] ?? []) && ($maintenanceStats['my_pending_verifications'] ?? 0) > 0): ?>
                    <a href="?route=maintenance&status=Pending%20Verification" class="btn btn-sm btn-outline-warning w-100 mt-2">
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
                        <i class="bi bi-hourglass-split text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Pending Approval</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['pending_approval'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person-check me-1"></i><?= $maintenanceStats['my_pending_approvals'] ?? 0 ?> for your approval
                </p>
                <?php if (in_array($userRole, $roleConfig['maintenance/authorize'] ?? []) && ($maintenanceStats['my_pending_approvals'] ?? 0) > 0): ?>
                    <a href="?route=maintenance&status=Pending%20Approval" class="btn btn-sm btn-outline-info w-100 mt-2">
                        <i class="bi bi-check-circle me-1"></i>Approve Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Approved / In Progress -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-gear text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Active Maintenance</h6>
                        <h3 class="mb-0"><?= ($maintenanceStats['approved'] ?? 0) + ($maintenanceStats['in_progress'] ?? 0) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= $maintenanceStats['overdue'] ?? 0 ?> overdue
                </p>
                <?php if (($maintenanceStats['overdue'] ?? 0) > 0): ?>
                    <a href="?route=maintenance&overdue=1" class="btn btn-sm btn-outline-danger w-100 mt-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>Review Overdue
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Completed -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Completed</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['completed'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-percent me-1"></i><?= $maintenanceStats['completion_rate'] ?? 0 ?>% completion rate
                </p>
            </div>
        </div>
    </div>

    <!-- Preventive Maintenance -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-calendar-check text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Preventive Maintenance</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['preventive'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clock me-1"></i><?= $maintenanceStats['preventive_due_soon'] ?? 0 ?> due within 7 days
                </p>
            </div>
        </div>
    </div>

    <!-- Corrective Maintenance -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-tools text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Corrective Maintenance</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['corrective'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= $maintenanceStats['urgent_corrective'] ?? 0 ?> urgent repairs
                </p>
            </div>
        </div>
    </div>

    <!-- Emergency Maintenance -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--danger-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-lightning text-danger fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Emergency Maintenance</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['emergency'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-currency-dollar me-1"></i><?= formatCurrency($maintenanceStats['emergency_cost'] ?? 0) ?> spent
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
                        <h6 class="text-muted mb-1 small">Canceled Tasks</h6>
                        <h3 class="mb-0"><?= $maintenanceStats['canceled'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-slash-circle me-1"></i>Maintenance canceled
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
    <span class="badge bg-primary">In Progress</span> →
    <span class="badge bg-success">Completed</span>
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
