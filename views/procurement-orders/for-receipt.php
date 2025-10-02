<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-box-seam me-2"></i>
        Orders for Receipt
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Procurement Orders
        </a>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'receipt_confirmed'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Receipt has been confirmed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'receipt_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>Failed to confirm receipt. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Awaiting Receipt</h6>
                        <h3 class="mb-0"><?= count($ordersForReceipt ?? []) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-box-seam display-6"></i>
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
                        <h6 class="card-title">In Transit</h6>
                        <h3 class="mb-0">
                            <?php
                            $inTransitCount = 0;
                            if (!empty($ordersForReceipt)) {
                                foreach ($ordersForReceipt as $order) {
                                    if (($order['delivery_status'] ?? '') === 'In Transit') {
                                        $inTransitCount++;
                                    }
                                }
                            }
                            echo $inTransitCount;
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-truck display-6"></i>
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
                        <h6 class="card-title">Delivered</h6>
                        <h3 class="mb-0">
                            <?php
                            $deliveredCount = 0;
                            if (!empty($ordersForReceipt)) {
                                foreach ($ordersForReceipt as $order) {
                                    if (($order['delivery_status'] ?? '') === 'Delivered') {
                                        $deliveredCount++;
                                    }
                                }
                            }
                            echo $deliveredCount;
                            ?>
                        </h3>
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
                        <h3 class="mb-0">
                            <?php
                            $totalValue = 0;
                            if (!empty($ordersForReceipt)) {
                                foreach ($ordersForReceipt as $order) {
                                    $totalValue += $order['net_total'] ?? 0;
                                }
                            }
                            echo '₱' . number_format($totalValue, 2);
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-currency-dollar display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delivered Orders Alert -->
<?php if ($deliveredCount > 0): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Receipt Confirmation Required
        </h6>
        <p class="mb-0">There are <?= $deliveredCount ?> order(s) that have been delivered and require receipt confirmation.</p>
    </div>
<?php endif; ?>

<!-- Orders Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Orders Awaiting Receipt Confirmation</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($ordersForReceipt)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No Orders for Receipt</h5>
                <p class="text-muted">There are currently no orders awaiting receipt confirmation.</p>
                <a href="?route=procurement-orders" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Procurement Orders
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="ordersTable">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Project</th>
                            <th>Vendor</th>
                            <th>Items</th>
                            <th>Total Value</th>
                            <th>Delivery Status</th>
                            <th>Scheduled Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordersForReceipt as $order): ?>
                        <tr>
                            <td>
                                <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none">
                                    <strong><?= htmlspecialchars($order['po_number']) ?></strong>
                                </a>
                                <?php if (!empty($order['tracking_number'])): ?>
                                    <br><small class="text-muted">
                                        <i class="bi bi-truck"></i> <?= htmlspecialchars($order['tracking_number']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($order['project_name']) ?></div>
                                <?php if (!empty($order['project_code'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars($order['project_code']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($order['vendor_name']) ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $order['item_count'] ?> items</span>
                            </td>
                            <td>
                                ₱<?= number_format($order['net_total'], 2) ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = match($order['delivery_status'] ?? 'Pending') {
                                    'Scheduled' => 'bg-warning',
                                    'In Transit' => 'bg-info',
                                    'Delivered' => 'bg-success',
                                    'Partial' => 'bg-warning',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($order['delivery_status'] ?? 'Pending') ?>
                                </span>
                                
                                <?php if (($order['delivery_status'] ?? '') === 'Delivered'): ?>
                                    <div class="mt-1">
                                        <small class="text-success">
                                            <i class="bi bi-check-circle"></i> Ready for Receipt
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($order['scheduled_delivery_date'])): ?>
                                    <?php 
                                    $isOverdue = strtotime($order['scheduled_delivery_date']) < time() && 
                                               !in_array($order['delivery_status'] ?? '', ['Delivered', 'Received']);
                                    ?>
                                    <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                        <?= date('M j, Y', strtotime($order['scheduled_delivery_date'])) ?>
                                    </span>
                                    <?php if ($isOverdue): ?>
                                        <br><small class="text-danger">
                                            <i class="bi bi-exclamation-triangle"></i> Overdue
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not scheduled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" 
                                       class="btn btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (in_array($userRole, $roleConfig['procurement-orders/update-delivery'] ?? []) && in_array($order['delivery_status'], ['Scheduled', 'In Transit'])): ?>
                                        <a href="?route=procurement-orders/update-delivery&id=<?= $order['id'] ?>" 
                                           class="btn btn-outline-info" title="Update Delivery Status">
                                            <i class="bi bi-truck"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (($order['delivery_status'] ?? '') === 'Delivered' && in_array($userRole, $roleConfig['procurement-orders/receive'] ?? [])): ?>
                                        <a href="?route=procurement-orders/receive&id=<?= $order['id'] ?>" 
                                           class="btn btn-success" title="Confirm Receipt">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Instructions Card -->
<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-info-circle me-2"></i>
            Receipt Confirmation Instructions
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>For Warehousemen:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check text-success me-2"></i>Verify delivered items against PO</li>
                    <li><i class="bi bi-check text-success me-2"></i>Check quantity and quality</li>
                    <li><i class="bi bi-check text-success me-2"></i>Report any discrepancies</li>
                    <li><i class="bi bi-check text-success me-2"></i>Confirm receipt in system</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Delivery Status Guide:</h6>
                <ul class="list-unstyled">
                    <li><span class="badge bg-warning me-2">Scheduled</span>Delivery is scheduled</li>
                    <li><span class="badge bg-info me-2">In Transit</span>Items are being delivered</li>
                    <li><span class="badge bg-success me-2">Delivered</span>Ready for receipt confirmation</li>
                    <li><span class="badge bg-warning me-2">Partial</span>Partial delivery or discrepancy</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Print table
function printTable() {
    window.print();
}

// Auto-refresh page every 5 minutes to show updated delivery status
setTimeout(function() {
    window.location.reload();
}, 300000); // 5 minutes

// Add confirmation for receipt actions
document.querySelectorAll('a[href*="receive"]').forEach(function(link) {
    link.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to confirm receipt of this order? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Orders for Receipt - ConstructLink™';
$pageHeader = 'Orders Awaiting Receipt Confirmation';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'For Receipt', 'url' => '?route=procurement-orders/for-receipt']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
