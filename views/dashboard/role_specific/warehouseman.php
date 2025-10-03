<!-- Warehouseman Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Warehouse Actions -->
        <div class="card mb-4" style="border-left: 4px solid var(--primary-color);">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-box-seam me-2 text-primary"></i>Pending Warehouse Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $warehouseData = $dashboardData['role_specific']['warehouse'] ?? [];
                    $pendingItems = [
                        ['label' => 'Scheduled Deliveries', 'count' => $warehouseData['scheduled_deliveries'] ?? 0, 'route' => 'procurement-orders?delivery_status=Scheduled', 'icon' => 'bi-truck', 'color' => 'primary'],
                        ['label' => 'Awaiting Receipt', 'count' => $warehouseData['awaiting_receipt'] ?? 0, 'route' => 'procurement-orders/for-receipt', 'icon' => 'bi-box-arrow-in-down', 'color' => 'warning'],
                        ['label' => 'Pending Releases', 'count' => $warehouseData['pending_releases'] ?? 0, 'route' => 'withdrawals?status=Approved', 'icon' => 'bi-box-arrow-right', 'color' => 'success'],
                        ['label' => 'Tool Requests', 'count' => $warehouseData['pending_tool_requests'] ?? 0, 'route' => 'borrowed-tools?status=Pending+Verification', 'icon' => 'bi-tools', 'color' => 'info']
                    ];

                    foreach ($pendingItems as $item):
                    ?>
                    <div class="col-md-6 mb-3">
                        <div class="pending-action-item p-3 rounded" style="background-color: var(--bg-light); border-left: 3px solid var(--<?= $item['color'] ?>-color);">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="<?= $item['icon'] ?> text-<?= $item['color'] ?> me-2 fs-5"></i>
                                    <span class="fw-semibold"><?= $item['label'] ?></span>
                                </div>
                                <span class="badge bg-<?= $item['color'] ?> rounded-pill"><?= $item['count'] ?></span>
                            </div>
                            <?php if ($item['count'] > 0): ?>
                            <a href="?route=<?= $item['route'] ?>" class="btn btn-sm btn-<?= $item['color'] ?> mt-1">
                                <i class="bi bi-eye me-1"></i>Process Now
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Inventory Status -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-archive me-2"></i>Inventory Status
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Current Stock Levels</h6>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Consumable Stock</span>
                                <span class="badge bg-info"><?= number_format($warehouseData['consumable_stock'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Tool Stock</span>
                                <span class="badge bg-primary"><?= number_format($warehouseData['tool_stock'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Low Stock Alerts</span>
                                <span class="badge bg-<?= ($warehouseData['low_stock_items'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                                    <?= number_format($warehouseData['low_stock_items'] ?? 0) ?>
                                </span>
                            </div>
                        </div>
                        <?php if (($warehouseData['low_stock_items'] ?? 0) > 0): ?>
                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Some items are running low. Check inventory levels.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Tool Management</h6>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Currently Borrowed</span>
                                <span class="badge bg-warning"><?= number_format($warehouseData['borrowed_tools'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Overdue Returns</span>
                                <span class="badge bg-<?= ($warehouseData['overdue_tools'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                                    <?= number_format($warehouseData['overdue_tools'] ?? 0) ?>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Active Withdrawals</span>
                                <span class="badge bg-info"><?= number_format($warehouseData['active_withdrawals'] ?? 0) ?></span>
                            </div>
                        </div>
                        <?php if (($warehouseData['overdue_tools'] ?? 0) > 0): ?>
                        <div class="alert alert-danger small">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Some tools are overdue. Follow up required.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning-fill me-2"></i>Warehouse Operations
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=procurement-orders/for-receipt" class="btn btn-primary btn-sm">
                        <i class="bi bi-box-arrow-in-down"></i> Process Deliveries
                    </a>
                    <a href="?route=withdrawals?status=Approved" class="btn btn-warning btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Release Items
                    </a>
                    <a href="?route=borrowed-tools/create" class="btn btn-info btn-sm">
                        <i class="bi bi-tools"></i> Issue Tools
                    </a>
                    <a href="?route=assets?status=available" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-list-ul"></i> View Inventory
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Delivery Schedule -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-event me-2"></i>Today's Schedule
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-truck text-primary"></i> Deliveries Expected</span>
                        <span class="badge bg-primary"><?= $warehouseData['scheduled_deliveries'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-arrow-in-down text-warning"></i> In Transit</span>
                        <span class="badge bg-warning"><?= $warehouseData['in_transit_deliveries'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-arrow-right text-success"></i> Releases Due</span>
                        <span class="badge bg-success"><?= $warehouseData['pending_releases'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="mt-3 d-grid">
                    <a href="?route=procurement-orders?delivery=today" class="btn btn-outline-primary btn-sm">
                        View Full Schedule
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>Daily Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <i class="bi bi-box-arrow-in-down text-primary fs-3"></i>
                        <h6 class="mb-0">0</h6>
                        <small class="text-muted">Received Today</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-box-arrow-right text-success fs-3"></i>
                        <h6 class="mb-0">0</h6>
                        <small class="text-muted">Released Today</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-tools text-warning fs-3"></i>
                        <h6 class="mb-0">0</h6>
                        <small class="text-muted">Tools Issued</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-arrow-counterclockwise text-info fs-3"></i>
                        <h6 class="mb-0">0</h6>
                        <small class="text-muted">Tools Returned</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>