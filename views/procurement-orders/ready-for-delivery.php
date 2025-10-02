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
        <i class="bi bi-calendar-check me-2"></i>
        Orders Ready for Delivery
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Procurement Orders
        </a>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'delivery_scheduled'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Delivery has been scheduled successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'schedule_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>Failed to schedule delivery. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Ready for Delivery</h6>
                        <h3 class="mb-0"><?= count($orders ?? []) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-check display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Urgent Orders</h6>
                        <h3 class="mb-0">
                            <?php
                            $urgentCount = 0;
                            if (!empty($orders)) {
                                foreach ($orders as $order) {
                                    if (!empty($order['date_needed']) && strtotime($order['date_needed']) <= strtotime('+7 days')) {
                                        $urgentCount++;
                                    }
                                }
                            }
                            echo $urgentCount;
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Value</h6>
                        <h3 class="mb-0">
                            <?php
                            $totalValue = 0;
                            if (!empty($orders)) {
                                foreach ($orders as $order) {
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

<!-- Urgent Orders Alert -->
<?php if ($urgentCount > 0): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Urgent Delivery Alert
        </h6>
        <p class="mb-0">There are <?= $urgentCount ?> order(s) with urgent delivery dates that require immediate scheduling.</p>
    </div>
<?php endif; ?>

<!-- Orders Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Orders Ready for Delivery Scheduling</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-check-circle display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No Orders Ready for Delivery</h5>
                <p class="text-muted">All approved orders have been scheduled for delivery.</p>
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
                            <th>Date Needed</th>
                            <th>Approved Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none">
                                    <strong><?= htmlspecialchars($order['po_number']) ?></strong>
                                </a>
                                <br><small class="text-muted">
                                    <span class="badge bg-success">Approved</span>
                                </small>
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
                                <?php if (!empty($order['date_needed'])): ?>
                                    <?php 
                                    $isUrgent = strtotime($order['date_needed']) <= strtotime('+7 days');
                                    ?>
                                    <span class="<?= $isUrgent ? 'text-warning fw-bold' : '' ?>">
                                        <?= date('M j, Y', strtotime($order['date_needed'])) ?>
                                    </span>
                                    <?php if ($isUrgent): ?>
                                        <br><small class="text-warning">
                                            <i class="bi bi-exclamation-triangle"></i> Urgent
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?= date('M j, Y', strtotime($order['created_at'])) ?></small>
                                <br><small class="text-muted">
                                    by <?= htmlspecialchars($order['approved_by_name'] ?? 'System') ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" 
                                       class="btn btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($order['status'] === 'Approved' && in_array($userRole, $roleConfig['procurement-orders/schedule-delivery'] ?? [])): ?>
                                    <a href="?route=procurement-orders/schedule-delivery&id=<?= $order['id'] ?>" 
                                       class="btn btn-success" title="Schedule Delivery">
                                        <i class="bi bi-calendar-plus"></i>
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
    <div class="card-header bg-success text-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-info-circle me-2"></i>
            Delivery Scheduling Instructions
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Scheduling Process:</h6>
                <ol>
                    <li>Review approved procurement order details</li>
                    <li>Coordinate with vendor for delivery schedule</li>
                    <li>Select appropriate delivery method</li>
                    <li>Set delivery location and tracking details</li>
                    <li>Notify relevant stakeholders</li>
                </ol>
            </div>
            <div class="col-md-6">
                <h6>Delivery Methods Available:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-airplane text-primary me-2"></i>Airfreight</li>
                    <li><i class="bi bi-truck text-info me-2"></i>Bus Cargo</li>
                    <li><i class="bi bi-geo-alt text-success me-2"></i>Pickup</li>
                    <li><i class="bi bi-building text-warning me-2"></i>Direct to Site</li>
                    <li><i class="bi bi-boxes text-secondary me-2"></i>Batch Delivery</li>
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>Tip:</strong> Orders with urgent delivery dates are highlighted. 
                    Consider prioritizing these for immediate scheduling.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Print table
function printTable() {
    window.print();
}

// Auto-refresh page every 10 minutes to show updated orders
setTimeout(function() {
    window.location.reload();
}, 600000); // 10 minutes

// Add confirmation for scheduling actions
document.querySelectorAll('a[href*="schedule-delivery"]').forEach(function(link) {
    link.addEventListener('click', function(e) {
        const poNumber = this.closest('tr').querySelector('strong').textContent;
        if (!confirm(`Are you sure you want to schedule delivery for ${poNumber}?`)) {
            e.preventDefault();
        }
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Orders Ready for Delivery - ConstructLink™';
$pageHeader = 'Orders Ready for Delivery Scheduling';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'Ready for Delivery', 'url' => '?route=procurement-orders/ready-for-delivery']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
