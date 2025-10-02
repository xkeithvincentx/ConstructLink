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
        <i class="bi bi-cart-plus me-2"></i>
        Procurement Orders
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Finance Director'])): ?>
            <a href="?route=procurement-orders/create" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Procurement Order
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Orders</h6>
                        <h3 class="mb-0"><?= $procurementStats['total_orders'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-cart-plus display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Approval</h6>
                        <h3 class="mb-0"><?= $procurementStats['pending'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock-history display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Approved</h6>
                        <h3 class="mb-0"><?= $procurementStats['approved'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Value</h6>
                        <h3 class="mb-0"><?= formatCurrency($procurementStats['total_value'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-currency-dollar display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Alerts -->
<?php if (!empty($pendingApprovals)): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Orders Pending Approval
        </h6>
        <p class="mb-2">There are <?= count($pendingApprovals) ?> procurement order(s) that require approval:</p>
        <ul class="mb-0">
            <?php foreach (array_slice($pendingApprovals, 0, 3) as $order): ?>
                <li>
                    <strong>Order #<?= $order['order_number'] ?></strong> 
                    from <?= htmlspecialchars($order['vendor_name']) ?> - 
                    <?= formatCurrency($order['net_total']) ?>
                    <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="ms-2">View Details</a>
                </li>
            <?php endforeach; ?>
            <?php if (count($pendingApprovals) > 3): ?>
                <li><em>... and <?= count($pendingApprovals) - 3 ?> more</em></li>
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
        <form method="GET" action="?route=procurement-orders" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="ordered" <?= ($_GET['status'] ?? '') === 'ordered' ? 'selected' : '' ?>>Ordered</option>
                    <option value="partially_received" <?= ($_GET['status'] ?? '') === 'partially_received' ? 'selected' : '' ?>>Partially Received</option>
                    <option value="received" <?= ($_GET['status'] ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
                    <option value="canceled" <?= ($_GET['status'] ?? '') === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="vendor_id" class="form-label">Vendor</label>
                <select class="form-select" id="vendor_id" name="vendor_id">
                    <option value="">All Vendors</option>
                    <?php if (isset($vendors) && is_array($vendors)): ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?= $vendor['id'] ?>" 
                                    <?= ($_GET['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vendor['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                       placeholder="Search by order number, vendor, or description..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=procurement-orders" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Procurement Orders Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Procurement Orders</h6>
        <div class="d-flex gap-2">
            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
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
        <?php if (empty($procurementOrders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-plus display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No procurement orders found</h5>
                <p class="text-muted">Try adjusting your filters or create a new procurement order.</p>
                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Finance Director'])): ?>
                    <a href="?route=procurement-orders/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create First Order
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="procurementOrdersTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Vendor</th>
                            <th>Items</th>
                            <th>Total Value</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Expected Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($procurementOrders as $order): ?>
                            <tr>
                                <td>
                                    <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none">
                                        <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-building text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($order['vendor_name']) ?></div>
                                            <?php if (!empty($order['vendor_contact'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($order['vendor_contact']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= $order['item_count'] ?? 0 ?> items
                                    </span>
                                </td>
                                <td>
                                    <strong><?= formatCurrency($order['net_total'] ?? 0) ?></strong>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <br><small class="text-success">-<?= formatCurrency($order['discount_amount']) ?> discount</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $order['status'] ?? 'draft';
                                    $statusClasses = [
                                        'draft' => 'bg-secondary',
                                        'pending' => 'bg-warning',
                                        'approved' => 'bg-success',
                                        'ordered' => 'bg-primary',
                                        'partially_received' => 'bg-info',
                                        'received' => 'bg-success',
                                        'canceled' => 'bg-danger'
                                    ];
                                    $statusClass = $statusClasses[$status] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= date('M j, Y', strtotime($order['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($order['expected_delivery']): ?>
                                        <small class="<?= strtotime($order['expected_delivery']) < time() && in_array($order['status'], ['approved', 'ordered']) ? 'text-danger fw-bold' : '' ?>">
                                            <?= date('M j, Y', strtotime($order['expected_delivery'])) ?>
                                            <?php if (strtotime($order['expected_delivery']) < time() && in_array($order['status'], ['approved', 'ordered'])): ?>
                                                <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Not specified</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if (in_array($order['status'], ['draft', 'pending']) && $auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                                            <a href="?route=procurement-orders/edit&id=<?= $order['id'] ?>" 
                                               class="btn btn-outline-warning" title="Edit Order">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] === 'pending' && $auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                            <a href="?route=procurement-orders/approve&id=<?= $order['id'] ?>" 
                                               class="btn btn-outline-success" title="Approve Order">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($order['status'], ['approved', 'ordered', 'partially_received']) && $auth->hasRole(['System Admin', 'Procurement Officer', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
                                            <a href="?route=procurement-orders/receive&id=<?= $order['id'] ?>" 
                                               class="btn btn-outline-info" title="Receive Items">
                                                <i class="bi bi-box-arrow-in-down"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($order['status'], ['draft', 'pending', 'approved']) && $auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                            <a href="?route=procurement-orders/cancel&id=<?= $order['id'] ?>" 
                                               class="btn btn-outline-danger" title="Cancel Order">
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
                <nav aria-label="Procurement orders pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=procurement-orders&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=procurement-orders&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=procurement-orders&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
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
    window.location.href = '?route=procurement-orders/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Auto-refresh for pending orders
if (document.querySelector('.badge.bg-warning')) {
    setTimeout(() => {
        location.reload();
    }, 300000); // Refresh every 5 minutes if there are pending orders
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

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=procurement-orders"]');
    const filterInputs = filterForm.querySelectorAll('select');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Search with debounce
    let searchTimeout;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Procurement Orders - ConstructLink™';
$pageHeader = 'Procurement Orders';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
