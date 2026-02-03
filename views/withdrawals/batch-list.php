<?php
/**
 * ConstructLink™ - Withdrawal Batch Listing
 * List and manage all withdrawal batches with filtering
 */

// Start output buffering to capture content
ob_start();

// Load ViewHelper for reusable components
require_once APP_ROOT . '/helpers/ViewHelper.php';

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';

// Calculate statistics
$stats = [
    'total_batches' => 0,
    'pending_verification' => 0,
    'pending_approval' => 0,
    'released_today' => 0
];

if (!empty($batches)) {
    $stats['total_batches'] = count($batches);
    foreach ($batches as $batch) {
        if ($batch['status'] === 'Pending Verification') {
            $stats['pending_verification']++;
        }
        if ($batch['status'] === 'Pending Approval') {
            $stats['pending_approval']++;
        }
        if ($batch['release_date'] && date('Y-m-d', strtotime($batch['release_date'])) === date('Y-m-d')) {
            $stats['released_today']++;
        }
    }
}

// Generate CSRF token for JavaScript
$csrfToken = CSRFProtection::generateToken();
?>

<!-- Withdrawal Batch Module Container with Configuration -->
<div id="withdrawal-batch-app"
     data-csrf-token="<?= htmlspecialchars($csrfToken) ?>"
     data-auto-refresh-interval="<?= config('business_rules.ui.auto_refresh_interval', 300) ?>">

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Batches</h6>
                        <h3 class="mb-0"><?= $stats['total_batches'] ?></h3>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-boxes fs-1" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-warning shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Verification</h6>
                        <h3 class="mb-0"><?= $stats['pending_verification'] ?></h3>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-search fs-1" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-info shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Approval</h6>
                        <h3 class="mb-0"><?= $stats['pending_approval'] ?></h3>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-person-check fs-1" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Released Today</h6>
                        <h3 class="mb-0"><?= $stats['released_today'] ?></h3>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-check-circle fs-1" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <!-- Desktop: Action Buttons -->
    <div class="d-none d-md-flex gap-2">
        <?php if (hasPermission('withdrawals/create')): ?>
            <a href="?route=withdrawals/create-batch"
               class="btn btn-success btn-sm"
               aria-label="Create new withdrawal batch">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>New Batch
            </a>
        <?php endif; ?>
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                id="refreshBtn"
                aria-label="Refresh list">
            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
        </button>
    </div>
</div>

<!-- Mobile: Action Buttons -->
<div class="d-md-none d-grid gap-2 mb-4">
    <?php if (hasPermission('withdrawals/create')): ?>
        <a href="?route=withdrawals/create-batch" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>New Withdrawal Batch
        </a>
    <?php endif; ?>
</div>

<!-- MVA Workflow Help (Collapsible) -->
<div class="mb-3">
    <button class="btn btn-link btn-sm text-decoration-none p-0"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mvaHelp"
            aria-expanded="false"
            aria-controls="mvaHelp">
        <i class="bi bi-question-circle me-1" aria-hidden="true"></i>
        How does the withdrawal workflow work?
    </button>
</div>

<div class="collapse" id="mvaHelp">
    <div class="alert alert-info mb-4" role="status">
        <strong><i class="bi bi-info-circle me-2" aria-hidden="true"></i>MVA Workflow for Consumable Withdrawals:</strong>
        <ol class="mb-0 ps-3 mt-2">
            <li><strong>Maker</strong> (Warehouseman) creates withdrawal batch</li>
            <li><strong>Verifier</strong> (Project Manager) verifies consumables match project needs</li>
            <li><strong>Authorizer</strong> (Asset/Finance Director) approves budget and authorization</li>
            <li>Warehouseman releases consumables (status: <span class="badge bg-success">Approved</span> → <span class="badge bg-primary">Released</span>)</li>
            <li>Consumables are consumed or used (no return expected)</li>
        </ol>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=withdrawals/batch-list" id="filterForm">
            <input type="hidden" name="route" value="withdrawals/batch-list">
            <div class="row g-3">
                <!-- Status Filter -->
                <div class="col-md-3">
                    <label for="status_filter" class="form-label">Status</label>
                    <select class="form-select" id="status_filter" name="status">
                        <option value="">All Statuses</option>
                        <option value="Pending Verification">Pending Verification</option>
                        <option value="Pending Approval">Pending Approval</option>
                        <option value="Approved">Approved</option>
                        <option value="Released">Released</option>
                        <option value="Canceled">Canceled</option>
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from">
                </div>

                <!-- Date To -->
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to">
                </div>

                <!-- Receiver Search -->
                <div class="col-md-3">
                    <label for="receiver_search" class="form-label">Receiver Name</label>
                    <input type="text" class="form-control" id="receiver_search" name="receiver" placeholder="Search receiver...">
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Apply Filters
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='?route=withdrawals/batch-list'">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear Filters
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Batch Listing -->
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="bi bi-list-ul me-2" aria-hidden="true"></i>Withdrawal Batches
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($batches)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                No withdrawal batches found. Create your first batch to get started.
            </div>
        <?php else: ?>
            <!-- Desktop Table View -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Batch Reference</th>
                            <th scope="col">Receiver</th>
                            <th scope="col">Purpose</th>
                            <th scope="col" class="text-center">Items</th>
                            <th scope="col" class="text-center">Quantity</th>
                            <th scope="col">Status</th>
                            <th scope="col">Created</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                            <tr>
                                <td>
                                    <a href="?route=withdrawals/batch-view&id=<?= $batch['id'] ?>" class="text-decoration-none">
                                        <strong><?= htmlspecialchars($batch['batch_reference']) ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <?= htmlspecialchars($batch['receiver_name']) ?>
                                    <?php if ($batch['receiver_contact']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($batch['receiver_contact']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars(substr($batch['purpose'], 0, 50)) ?><?= strlen($batch['purpose']) > 50 ? '...' : '' ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?= $batch['total_items'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $batch['total_quantity'] ?></span>
                                </td>
                                <td>
                                    <?= ViewHelper::renderStatusBadge($batch['status']) ?>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($batch['created_at'])) ?>
                                    <br><small class="text-muted"><?= date('H:i', strtotime($batch['created_at'])) ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=withdrawals/batch-view&id=<?= $batch['id'] ?>"
                                           class="btn btn-outline-primary"
                                           title="View Details">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="?route=withdrawals/batch-print&id=<?= $batch['id'] ?>"
                                           class="btn btn-outline-secondary"
                                           target="_blank"
                                           title="Print Slip">
                                            <i class="bi bi-printer" aria-hidden="true"></i>
                                        </a>
                                        <?php if ($batch['status'] === 'Pending Verification' && hasPermission('withdrawals/verify')): ?>
                                            <a href="?route=withdrawals/batch-verify&id=<?= $batch['id'] ?>"
                                               class="btn btn-outline-warning"
                                               title="Verify Batch">
                                                <i class="bi bi-search" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($batch['status'] === 'Pending Approval' && hasPermission('withdrawals/approve')): ?>
                                            <a href="?route=withdrawals/batch-approve&id=<?= $batch['id'] ?>"
                                               class="btn btn-outline-info"
                                               title="Approve Batch">
                                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($batch['status'] === 'Approved' && hasPermission('withdrawals/release')): ?>
                                            <a href="?route=withdrawals/batch-release&id=<?= $batch['id'] ?>"
                                               class="btn btn-outline-success"
                                               title="Release Batch">
                                                <i class="bi bi-box-arrow-up" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="d-md-none">
                <?php foreach ($batches as $batch): ?>
                    <div class="card mb-3 border">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <a href="?route=withdrawals/batch-view&id=<?= $batch['id'] ?>" class="text-decoration-none">
                                        <strong class="d-block"><?= htmlspecialchars($batch['batch_reference']) ?></strong>
                                    </a>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-person me-1" aria-hidden="true"></i><?= htmlspecialchars($batch['receiver_name']) ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar me-1" aria-hidden="true"></i><?= date('M d, Y', strtotime($batch['created_at'])) ?>
                                    </small>
                                </div>
                                <?= ViewHelper::renderStatusBadge($batch['status']) ?>
                            </div>

                            <p class="mb-2 small">
                                <?= htmlspecialchars(substr($batch['purpose'], 0, 80)) ?><?= strlen($batch['purpose']) > 80 ? '...' : '' ?>
                            </p>

                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Items</small>
                                    <span class="badge bg-primary"><?= $batch['total_items'] ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Quantity</small>
                                    <span class="badge bg-info"><?= $batch['total_quantity'] ?></span>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="?route=withdrawals/batch-view&id=<?= $batch['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-eye me-1" aria-hidden="true"></i>View
                                </a>
                                <a href="?route=withdrawals/batch-print&id=<?= $batch['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination (if needed) -->
<?php if (!empty($pagination)): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Batch list pagination">
            <ul class="pagination">
                <?php if ($pagination['current_page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?route=withdrawals/batch-list&page=<?= $pagination['current_page'] - 1 ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?route=withdrawals/batch-list&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?route=withdrawals/batch-list&page=<?= $pagination['current_page'] + 1 ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<!-- Close withdrawal-batch-app container -->
</div>

<!-- Load withdrawal batch module CSS -->
<?php
require_once APP_ROOT . '/helpers/AssetHelper.php';
AssetHelper::loadModuleCSS('withdrawal-batch');
?>

<!-- Load external JavaScript module -->
<?php
AssetHelper::loadModuleJS('withdrawal-batch-init', ['type' => 'module']);
?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Withdrawal Batches - ConstructLink™';
$pageHeader = 'Withdrawal Batches';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
    ['title' => 'Batches', 'url' => '?route=withdrawals/batch-list']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
