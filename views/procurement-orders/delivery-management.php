<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Ready for Delivery</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['ready_count'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clock text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Scheduled</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['scheduled_count'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-check text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            In Transit</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['in_transit_count'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-truck text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            For Receipt</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['for_receipt_count'] ?? 0 ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clipboard-check text-danger" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Generate Assets</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['for_assets_count'] ?? 0 ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-plus-square text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                            Total Value</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($stats['total_value'], 2) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar text-secondary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delivery Workflow Tabs -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Delivery Workflow Management</h6>
    </div>
    <div class="card-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="deliveryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ready-tab" data-bs-toggle="tab" data-bs-target="#ready" type="button" role="tab">
                    <i class="bi bi-clock me-2"></i>Ready for Delivery (<?= $stats['ready_count'] ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="scheduled-tab" data-bs-toggle="tab" data-bs-target="#scheduled" type="button" role="tab">
                    <i class="bi bi-calendar-check me-2"></i>Scheduled (<?= $stats['scheduled_count'] ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="transit-tab" data-bs-toggle="tab" data-bs-target="#transit" type="button" role="tab">
                    <i class="bi bi-truck me-2"></i>In Transit (<?= $stats['in_transit_count'] ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt" type="button" role="tab">
                    <i class="bi bi-clipboard-check me-2"></i>For Receipt (<?= $stats['for_receipt_count'] ?? 0 ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assets-tab" data-bs-toggle="tab" data-bs-target="#assets" type="button" role="tab">
                    <i class="bi bi-plus-square me-2"></i>Generate Assets (<?= $stats['for_assets_count'] ?? 0 ?>)
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="deliveryTabContent">
            <!-- Ready for Delivery Tab -->
            <div class="tab-pane fade show active" id="ready" role="tabpanel">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped" id="readyTable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Created Date</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($readyForDelivery)): ?>
                                <?php foreach ($readyForDelivery as $order): ?>
                                <tr>
                                    <td>
                                        <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($order['po_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($order['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                                    <td><?= isset($order['created_at']) && $order['created_at'] ? date('M d, Y', strtotime($order['created_at'])) : 'N/A' ?></td>
                                    <td>₱<?= number_format($order['total_value'], 2) ?></td>
                                    <td>
                                        <?php if (hasRole(['System Admin', 'Procurement Officer'])): ?>
                                            <a href="?route=procurement-orders/schedule-delivery&id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-calendar-plus me-1"></i>Schedule
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No orders ready for delivery</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Scheduled Deliveries Tab -->
            <div class="tab-pane fade" id="scheduled" role="tabpanel">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped" id="scheduledTable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Scheduled Date</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($scheduledOrders)): ?>
                                <?php foreach ($scheduledOrders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($order['po_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($order['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= $order['scheduled_delivery_date'] ? date('M d, Y', strtotime($order['scheduled_delivery_date'])) : 'Not scheduled' ?>
                                    </td>
                                    <td>₱<?= number_format($order['total_value'], 2) ?></td>
                                    <td>
                                        <?php if (hasRole(['System Admin', 'Procurement Officer'])): ?>
                                            <a href="?route=procurement-orders/update-delivery&id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="bi bi-truck me-1"></i>Mark In Transit
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No scheduled deliveries</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- In Transit Tab -->
            <div class="tab-pane fade" id="transit" role="tabpanel">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped" id="transitTable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Tracking Number</th>
                                <th>Scheduled Delivery</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inTransitOrders)): ?>
                                <?php foreach ($inTransitOrders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($order['po_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($order['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['tracking_number'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= $order['scheduled_delivery_date'] ? date('M d, Y', strtotime($order['scheduled_delivery_date'])) : 'N/A' ?>
                                    </td>
                                    <td>₱<?= number_format($order['total_value'], 2) ?></td>
                                    <td>
                                        <?php if (hasRole(['System Admin', 'Procurement Officer', 'Warehouseman'])): ?>
                                            <a href="?route=procurement-orders/confirm-delivery&id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle me-1"></i>Mark Delivered
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No orders in transit</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- For Receipt Tab -->
            <div class="tab-pane fade" id="receipt" role="tabpanel">
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
                                    <td><?= htmlspecialchars($order['vendor_name'] ?? 'N/A') ?></td>
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
                                    <td><?= htmlspecialchars($order['vendor_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= isset($order['received_at']) && $order['received_at'] ? date('M d, Y', strtotime($order['received_at'])) : 'N/A' ?>
                                    </td>
                                    <td><?= $order['item_count'] ?? 0 ?></td>
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

<!-- Instructions Card -->
<div class="card border-left-info shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-info">Complete Delivery Workflow Instructions</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <h6 class="text-primary">Ready for Delivery</h6>
                <ul class="small text-muted">
                    <li>Orders approved and awaiting delivery scheduling</li>
                    <li>Use "Schedule" button to set delivery date</li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="text-info">Scheduled</h6>
                <ul class="small text-muted">
                    <li>Orders with confirmed delivery dates</li>
                    <li>Use "Mark In Transit" when shipment begins</li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="text-warning">In Transit</h6>
                <ul class="small text-muted">
                    <li>Orders currently being delivered</li>
                    <li>Use "Mark Delivered" upon arrival</li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="text-danger">For Receipt</h6>
                <ul class="small text-muted">
                    <li>Orders delivered and awaiting receipt confirmation</li>
                    <li>Use "Receive" to confirm quantities received</li>
                    <li>Use "Flag Issue" for discrepancies</li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="text-success">Generate Assets</h6>
                <ul class="small text-muted">
                    <li>Orders received and ready for asset generation</li>
                    <li>Use "Generate Assets" to create asset records</li>
                    <li>Use "Preview" to review order details</li>
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <strong>MVA Workflow:</strong> 
                    <span class="me-3"><strong>Delivery:</strong> Procurement Officer → Warehouseman/Site Staff → Project Manager</span>
                    <span class="me-3"><strong>Receipt:</strong> Warehouseman/Site Staff → Site Inventory Clerk/Project Manager → Project Manager</span>
                    <span><strong>Assets:</strong> Multiple Roles → Asset Director → Finance Director</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTables for each tab
    $('#readyTable').DataTable({
        "pageLength": 10,
        "order": [[ 3, "desc" ]]
    });
    
    $('#scheduledTable').DataTable({
        "pageLength": 10,
        "order": [[ 3, "asc" ]]
    });
    
    $('#transitTable').DataTable({
        "pageLength": 10,
        "order": [[ 4, "asc" ]]
    });
    
    $('#receiptTable').DataTable({
        "pageLength": 10,
        "order": [[ 4, "asc" ]]
    });
    
    $('#assetsTable').DataTable({
        "pageLength": 10,
        "order": [[ 3, "desc" ]]
    });
});
</script>

<?php
// Capture content and include layout
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>