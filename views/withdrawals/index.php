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
        <?php if (in_array($userRole, $roleConfig['withdrawals/create'] ?? [])): ?>
            <a href="?route=withdrawals/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">New Withdrawal</span>
                <span class="d-sm-none">Create</span>
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
                        <h3 class="mb-0"><?= $withdrawalStats['pending_verification'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person me-1"></i><?= $withdrawalStats['my_pending_verifications'] ?? 0 ?> for your review
                </p>
                <?php if (in_array($userRole, $roleConfig['withdrawals/verify'] ?? []) && ($withdrawalStats['my_pending_verifications'] ?? 0) > 0): ?>
                    <a href="?route=withdrawals&status=Pending%20Verification" class="btn btn-sm btn-outline-warning w-100 mt-2">
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
                        <h3 class="mb-0"><?= $withdrawalStats['pending_approval'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person-check me-1"></i><?= $withdrawalStats['my_pending_approvals'] ?? 0 ?> for your approval
                </p>
                <?php if (in_array($userRole, $roleConfig['withdrawals/approve'] ?? []) && ($withdrawalStats['my_pending_approvals'] ?? 0) > 0): ?>
                    <a href="?route=withdrawals&status=Pending%20Approval" class="btn btn-sm btn-outline-info w-100 mt-2">
                        <i class="bi bi-check-circle me-1"></i>Approve Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Approved for Release -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Approved for Release</h6>
                        <h3 class="mb-0"><?= $withdrawalStats['approved'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-box-arrow-right me-1"></i>Ready for warehouse release
                </p>
                <?php if (in_array($userRole, $roleConfig['withdrawals/release'] ?? []) && ($withdrawalStats['approved'] ?? 0) > 0): ?>
                    <a href="?route=withdrawals&status=Approved" class="btn btn-sm btn-outline-success w-100 mt-2">
                        <i class="bi bi-box-arrow-right me-1"></i>Release Assets
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Released (Active) -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-box-arrow-right text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Released (Active)</h6>
                        <h3 class="mb-0"><?= $withdrawalStats['released'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clock me-1"></i><?= $withdrawalStats['overdue_returns'] ?? 0 ?> overdue returns
                </p>
                <?php if (($withdrawalStats['overdue_returns'] ?? 0) > 0): ?>
                    <a href="?route=withdrawals&status=Released" class="btn btn-sm btn-outline-danger w-100 mt-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>Review Overdue
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Returned (Completed) -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-arrow-return-left text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Returned</h6>
                        <h3 class="mb-0"><?= $withdrawalStats['returned'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-percent me-1"></i><?= $withdrawalStats['return_rate'] ?? 0 ?>% return rate
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
                        <h3 class="mb-0"><?= $withdrawalStats['canceled'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-graph-down me-1"></i><?= $withdrawalStats['cancellation_rate'] ?? 0 ?>% cancellation rate
                </p>
            </div>
        </div>
    </div>

    <!-- Total Withdrawals -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-list-ul text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Withdrawals</h6>
                        <h3 class="mb-0"><?= $withdrawalStats['total_withdrawals'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-graph-up me-1"></i>All time
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
    <span class="badge bg-primary">Released</span> →
    <span class="badge bg-secondary">Returned</span>
</div>

<!-- Overdue Alerts -->
<?php if (!empty($overdueWithdrawals)): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Overdue Withdrawals Alert
        </h6>
        <p class="mb-2">There are <?= count($overdueWithdrawals) ?> overdue withdrawal(s) that require immediate attention:</p>
        <ul class="mb-0">
            <?php foreach (array_slice($overdueWithdrawals, 0, 3) as $overdue): ?>
                <li>
                    <strong><?= htmlspecialchars($overdue['asset_name']) ?></strong> 
                    (<?= htmlspecialchars($overdue['asset_ref']) ?>) - 
                    <?= $overdue['days_overdue'] ?> days overdue
                    <a href="?route=withdrawals/view&id=<?= $overdue['id'] ?>" class="ms-2">View Details</a>
                </li>
            <?php endforeach; ?>
            <?php if (count($overdueWithdrawals) > 3): ?>
                <li><em>... and <?= count($overdueWithdrawals) - 3 ?> more</em></li>
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
        <form method="GET" action="?route=withdrawals" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending Verification" <?= ($_GET['status'] ?? '') === 'Pending Verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="Pending Approval" <?= ($_GET['status'] ?? '') === 'Pending Approval' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Released" <?= ($_GET['status'] ?? '') === 'Released' ? 'selected' : '' ?>>Released</option>
                    <option value="Returned" <?= ($_GET['status'] ?? '') === 'Returned' ? 'selected' : '' ?>>Returned</option>
                    <option value="Canceled" <?= ($_GET['status'] ?? '') === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>
            <?php 
            // Hide project filter if user has a current project assigned
            $showProjectFilter = in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director']) 
                                || empty($user['current_project_id']);
            ?>
            <?php if ($showProjectFilter): ?>
            <div class="col-md-3">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-select" id="project_id" name="project_id">
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
            <?php endif; ?>
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
            <div class="<?= $showProjectFilter ? 'col-md-6' : 'col-md-9' ?>">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by asset name, receiver, or purpose..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="<?= $showProjectFilter ? 'col-md-6' : 'col-md-3' ?> d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=withdrawals" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Withdrawals Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Withdrawal Requests</h6>
        <div class="d-flex gap-2">
            <?php if ($auth->hasPermission('view_all_assets') || $auth->hasPermission('view_financial_data')): ?>
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
        <?php if (empty($withdrawals)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No withdrawal requests found</h5>
                <p class="text-muted">Try adjusting your filters or create a new withdrawal request.</p>
                <?php if (in_array($userRole, $roleConfig['withdrawals/create'] ?? [])): ?>
                    <a href="?route=withdrawals/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Withdrawal Request
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="withdrawalsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>Project</th>
                            <th>Receiver</th>
                            <th>Purpose</th>
                            <th>Requested</th>
                            <th>Expected Return</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                            <tr>
                                <td>
                                    <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="text-decoration-none">
                                        #<?= $withdrawal['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-box text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($withdrawal['asset_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($withdrawal['asset_ref']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($withdrawal['project_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($withdrawal['receiver_name']) ?></div>
                                        <small class="text-muted">by <?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                          title="<?= htmlspecialchars($withdrawal['purpose']) ?>">
                                        <?= htmlspecialchars($withdrawal['purpose']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= date('M j, Y', strtotime($withdrawal['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($withdrawal['expected_return']): ?>
                                        <small class="<?= strtotime($withdrawal['expected_return']) < time() && $withdrawal['status'] === 'Released' ? 'text-danger fw-bold' : '' ?>">
                                            <?= date('M j, Y', strtotime($withdrawal['expected_return'])) ?>
                                            <?php if (strtotime($withdrawal['expected_return']) < time() && $withdrawal['status'] === 'Released'): ?>
                                                <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Not specified</small>
                                    <?php endif; ?>
                                </td>
                                <td>
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
                                        <?= ucfirst($withdrawal['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                        <?php 
                        // Use specific permission arrays from roles configuration
                        $verifyRoles = $roleConfig['withdrawals/verify'] ?? [];
                        $approveRoles = $roleConfig['withdrawals/approve'] ?? [];
                        $releaseRoles = $roleConfig['withdrawals/release'] ?? [];
                        $returnRoles = $roleConfig['withdrawals/return'] ?? [];
                        $cancelRoles = $roleConfig['withdrawals/cancel'] ?? [];
                        ?>
                        
                        <?php if (in_array($userRole, $verifyRoles) && $withdrawal['status'] === 'Pending Verification'): ?>
                            <a href="?route=withdrawals/verify&id=<?= $withdrawal['id'] ?>" 
                               class="btn btn-outline-warning" title="Verify Request">
                                <i class="bi bi-search"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($userRole, $approveRoles) && $withdrawal['status'] === 'Pending Approval'): ?>
                            <a href="?route=withdrawals/approve&id=<?= $withdrawal['id'] ?>" 
                               class="btn btn-outline-info" title="Approve Request">
                                <i class="bi bi-person-check"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($userRole, $releaseRoles) && $withdrawal['status'] === 'Approved'): ?>
                            <a href="?route=withdrawals/release&id=<?= $withdrawal['id'] ?>" 
                               class="btn btn-outline-success" title="Release Asset">
                                <i class="bi bi-check-circle"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($userRole, $returnRoles) && $withdrawal['status'] === 'Released'): ?>
                            <a href="?route=withdrawals/return&id=<?= $withdrawal['id'] ?>" 
                               class="btn btn-outline-info" title="Mark as Returned">
                                <i class="bi bi-arrow-return-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($userRole, $cancelRoles) && in_array($withdrawal['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'Released'])): ?>
                            <a href="?route=withdrawals/cancel&id=<?= $withdrawal['id'] ?>" 
                               class="btn btn-outline-danger" title="Cancel Request">
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
                <nav aria-label="Withdrawals pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=withdrawals&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=withdrawals&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=withdrawals&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
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
    window.location.href = '?route=withdrawals/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Auto-refresh for pending withdrawals
if (document.querySelector('.badge.bg-warning')) {
    setTimeout(() => {
        location.reload();
    }, 60000); // Refresh every 60 seconds if there are pending withdrawals
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
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Asset Withdrawals - ConstructLink™';
$pageHeader = 'Asset Withdrawals';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Withdrawals', 'url' => '?route=withdrawals']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
