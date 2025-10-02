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
        <i class="bi bi-clipboard-check me-2"></i>
        Receipt & Asset Generation Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Procurement Orders
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Awaiting Receipt</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['for_receipt_count'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clipboard-check text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Ready for Assets</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['for_assets_count'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-plus-square text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Value</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($stats['total_value'], 2) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt & Asset Workflow Tabs -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Receipt & Asset Management</h6>
    </div>
    <div class="card-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="receiptTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt" type="button" role="tab">
                    <i class="bi bi-clipboard-check me-2"></i>For Receipt (<?= $stats['for_receipt_count'] ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assets-tab" data-bs-toggle="tab" data-bs-target="#assets" type="button" role="tab">
                    <i class="bi bi-plus-square me-2"></i>Generate Assets (<?= $stats['for_assets_count'] ?>)
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="receiptTabContent">
            <!-- For Receipt Tab -->
            <div class="tab-pane fade show active" id="receipt" role="tabpanel">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped" id="receiptTable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Delivery Status</th>
                                <th>Scheduled Delivery</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ordersForReceipt)): ?>
                                <?php foreach ($ordersForReceipt as $order): ?>
                                <tr>
                                    <td>
                                        <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($order['po_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($order['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $order['delivery_status'] === 'Delivered' ? 'success' : 
                                            ($order['delivery_status'] === 'In Transit' ? 'warning' : 'secondary') 
                                        ?>">
                                            <?= htmlspecialchars($order['delivery_status'] ?? 'Pending') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= isset($order['scheduled_delivery_date']) && $order['scheduled_delivery_date'] ? date('M d, Y', strtotime($order['scheduled_delivery_date'])) : 'N/A' ?>
                                    </td>
                                    <td>₱<?= number_format($order['total_value'], 2) ?></td>
                                    <td>
                                        <?php if (hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
                                            <div class="btn-group" role="group">
                                                <a href="?route=procurement-orders/receive&id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle me-1"></i>Receive
                                                </a>
                                                <a href="?route=procurement-orders/flag-discrepancy&id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Flag Issue
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No orders awaiting receipt</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Generate Assets Tab -->
            <div class="tab-pane fade" id="assets" role="tabpanel">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped" id="assetsTable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Received Date</th>
                                <th>Items Count</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ordersForAssets)): ?>
                                <?php foreach ($ordersForAssets as $order): ?>
                                <tr>
                                    <td>
                                        <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($order['po_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($order['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= isset($order['received_at']) && $order['received_at'] ? date('M d, Y', strtotime($order['received_at'])) : 'N/A' ?>
                                    </td>
                                    <td><?= $order['items_count'] ?? 0 ?></td>
                                    <td>₱<?= number_format($order['total_value'], 2) ?></td>
                                    <td>
                                        <?php if (hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])): ?>
                                            <div class="btn-group" role="group">
                                                <a href="?route=procurement-orders/generateAssets&id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-plus-square me-1"></i>Generate Assets
                                                </a>
                                                <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye me-1"></i>Preview
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No orders ready for asset generation</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Card -->
<div class="row">
    <div class="col-md-6">
        <div class="card border-left-info shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">Receipt Instructions</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted">
                    <h6 class="text-primary">For Warehousemen & Site Staff:</h6>
                    <ul>
                        <li><strong>Receive:</strong> Confirm actual quantities received match PO</li>
                        <li><strong>Flag Issues:</strong> Report discrepancies, damages, or partial deliveries</li>
                        <li><strong>Document:</strong> Take photos of received items when necessary</li>
                        <li><strong>Update Status:</strong> Mark orders as received after verification</li>
                    </ul>
                    
                    <h6 class="text-info mt-3">Receipt Status Guide:</h6>
                    <div class="row">
                        <div class="col-6">
                            <span class="badge bg-success">Delivered</span> - Ready for receipt
                        </div>
                        <div class="col-6">
                            <span class="badge bg-warning">In Transit</span> - On the way
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-left-success shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">Asset Generation Instructions</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted">
                    <h6 class="text-primary">For Asset Directors & Procurement Officers:</h6>
                    <ul>
                        <li><strong>Review:</strong> Verify all items are properly received</li>
                        <li><strong>Categorize:</strong> Assign appropriate asset categories</li>
                        <li><strong>Generate:</strong> Create asset records for tracking</li>
                        <li><strong>Assign:</strong> Link assets to projects or locations</li>
                    </ul>
                    
                    <h6 class="text-success mt-3">Asset Generation Process:</h6>
                    <div class="small">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-1-circle text-primary me-2"></i>
                            <span>Select eligible procurement orders</span>
                        </div>
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-2-circle text-info me-2"></i>
                            <span>Review and categorize items</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-3-circle text-success me-2"></i>
                            <span>Generate asset records</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTables for each tab
    $('#receiptTable').DataTable({
        "pageLength": 10,
        "order": [[ 4, "asc" ]]
    });
    
    $('#assetsTable').DataTable({
        "pageLength": 10,
        "order": [[ 3, "desc" ]]
    });
    
    // Auto-refresh every 30 seconds for real-time updates
    setInterval(function() {
        location.reload();
    }, 30000);
});
</script>

<?php
// Capture content and include layout
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>