<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-clipboard-data me-2"></i>
        Procurement Orders
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
            <a href="?route=procurement/create" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Create Purchase Order
            </a>
        <?php endif; ?>
        
        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
            <div class="btn-group ms-2">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?route=procurement/export&<?= http_build_query($_GET) ?>">Export to Excel</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<?php if (!empty($procurementStats)): ?>
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-primary"><?= number_format($procurementStats['total']) ?></h5>
                <p class="card-text small text-muted">Total Orders</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-warning"><?= number_format($procurementStats['pending']) ?></h5>
                <p class="card-text small text-muted">Pending</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-success"><?= number_format($procurementStats['approved']) ?></h5>
                <p class="card-text small text-muted">Approved</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-info"><?= number_format($procurementStats['delivered']) ?></h5>
                <p class="card-text small text-muted">Delivered</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-danger"><?= number_format($procurementStats['rejected']) ?></h5>
                <p class="card-text small text-muted">Rejected</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-dark">₱<?= number_format($procurementStats['total_value'], 0) ?></h5>
                <p class="card-text small text-muted">Total Value</p>
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
        <form method="GET" action="?route=procurement" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="For Revision" <?= ($_GET['status'] ?? '') === 'For Revision' ? 'selected' : '' ?>>For Revision</option>
                    <option value="Rejected" <?= ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="Delivered" <?= ($_GET['status'] ?? '') === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-select" id="project_id" name="project_id">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>" <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($project['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="vendor_id" class="form-label">Vendor</label>
                <select class="form-select" id="vendor_id" name="vendor_id">
                    <option value="">All Vendors</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?= $vendor['id'] ?>" <?= ($_GET['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vendor['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                       placeholder="PO number, item name...">
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
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="?route=procurement" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Procurement Orders Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Procurement Orders</h6>
        <?php if (!empty($pagination)): ?>
            <small class="text-muted">
                Showing <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> to 
                <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?> 
                of <?= number_format($pagination['total']) ?> orders
            </small>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($procurements)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Vendor</th>
                            <th>Project</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
                                <th>Total Cost</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($procurements as $procurement): ?>
                            <tr>
                                <td>
                                    <a href="?route=procurement/view&id=<?= $procurement['id'] ?>" class="text-decoration-none fw-medium">
                                        <?= htmlspecialchars($procurement['po_number']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($procurement['vendor_name']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($procurement['project_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($procurement['item_name']) ?></div>
                                    <?php if ($procurement['description']): ?>
                                        <small class="text-muted"><?= htmlspecialchars(substr($procurement['description'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($procurement['quantity']) ?> <?= htmlspecialchars($procurement['unit'] ?? 'pcs') ?></td>
                                <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
                                    <td>₱<?= number_format($procurement['total_cost'], 2) ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'Pending' => 'bg-warning text-dark',
                                        'Approved' => 'bg-success',
                                        'For Revision' => 'bg-info',
                                        'Rejected' => 'bg-danger',
                                        'Delivered' => 'bg-primary',
                                        'Partial' => 'bg-secondary'
                                    ];
                                    $statusClass = $statusClasses[$procurement['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($procurement['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= date('M j, Y', strtotime($procurement['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('g:i A', strtotime($procurement['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=procurement/view&id=<?= $procurement['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($procurement['status'] === 'Pending' && $auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                            <a href="?route=procurement/approve&id=<?= $procurement['id'] ?>" 
                                               class="btn btn-outline-success" title="Approve">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($procurement['status'] === 'Approved' && $auth->hasRole(['System Admin', 'Warehouseman', 'Procurement Officer'])): ?>
                                            <a href="?route=procurement/receive&id=<?= $procurement['id'] ?>" 
                                               class="btn btn-outline-info" title="Mark as Received">
                                                <i class="bi bi-box-arrow-in-down"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($procurement['status'], ['Approved', 'Delivered']) && $auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
                                            <a href="?route=procurement/generatePO&id=<?= $procurement['id'] ?>" 
                                               class="btn btn-outline-secondary" title="Generate PDF" target="_blank">
                                                <i class="bi bi-file-earmark-pdf"></i>
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
            <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Procurement pagination" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=procurement&<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=procurement&<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=procurement&<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No procurement orders found</h5>
                <p class="text-muted">
                    <?php if (!empty($_GET['status']) || !empty($_GET['search'])): ?>
                        Try adjusting your filters or search terms.
                    <?php else: ?>
                        Get started by creating your first procurement order.
                    <?php endif; ?>
                </p>
                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                    <a href="?route=procurement/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Purchase Order
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=procurement"]');
    const selectElements = filterForm.querySelectorAll('select');
    
    selectElements.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
});
</script>

<script>
// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=procurement"]');
    const selectElements = filterForm.querySelectorAll('select');
    
    selectElements.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Procurement Management - ConstructLink™';
$pageHeader = 'Procurement Orders';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement', 'url' => '?route=procurement']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
