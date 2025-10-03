<!-- Procurement Officer Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Procurement Actions -->
        <div class="card mb-4" style="border-left: 4px solid var(--success-color);">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-cart-check me-2 text-success"></i>Pending Procurement Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $procurementData = $dashboardData['role_specific']['procurement'] ?? [];
                    $pendingItems = [
                        ['label' => 'Approved Requests (Pending PO)', 'count' => $procurementData['approved_requests_pending_po'] ?? 0, 'route' => 'requests?status=Approved', 'icon' => 'bi-clipboard-check', 'color' => 'primary'],
                        ['label' => 'Draft Orders', 'count' => $procurementData['draft_orders'] ?? 0, 'route' => 'procurement-orders?status=Draft', 'icon' => 'bi-file-earmark-text', 'color' => 'warning'],
                        ['label' => 'Pending Delivery', 'count' => $procurementData['pending_delivery'] ?? 0, 'route' => 'procurement-orders?delivery_status=Pending', 'icon' => 'bi-truck', 'color' => 'info'],
                        ['label' => 'Recent POs (30 days)', 'count' => $procurementData['recent_po_count'] ?? 0, 'route' => 'procurement-orders?recent=30', 'icon' => 'bi-calendar-check', 'color' => 'secondary']
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
        
        <!-- Delivery Performance -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>Delivery Performance
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Delivery Metrics</h6>
                        <?php 
                        $onTimeDeliveries = $procurementData['on_time_deliveries'] ?? 0;
                        $totalDeliveries = $procurementData['total_deliveries'] ?? 1;
                        $onTimePercentage = $totalDeliveries > 0 ? round(($onTimeDeliveries / $totalDeliveries) * 100, 1) : 0;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>On-Time Deliveries</span>
                                <span class="badge bg-<?= $onTimePercentage >= 90 ? 'success' : ($onTimePercentage >= 80 ? 'warning' : 'danger') ?>">
                                    <?= $onTimePercentage ?>%
                                </span>
                            </div>
                            <div class="progress mt-2" style="height: 20px;">
                                <div class="progress-bar bg-<?= $onTimePercentage >= 90 ? 'success' : ($onTimePercentage >= 80 ? 'warning' : 'danger') ?>" 
                                     role="progressbar" style="width: <?= $onTimePercentage ?>%">
                                    <?= $onTimeDeliveries ?> / <?= $totalDeliveries ?>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Average Delivery Variance</span>
                                <span class="text-muted">
                                    <?= ($procurementData['avg_delivery_variance'] ?? 0) > 0 ? '+' : '' ?><?= $procurementData['avg_delivery_variance'] ?? 0 ?> days
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Vendor Management</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Active Vendors</span>
                                <span class="badge bg-primary"><?= $procurementData['active_vendors'] ?? 0 ?></span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Preferred Vendors</span>
                                <span class="badge bg-success"><?= $procurementData['preferred_vendors'] ?? 0 ?></span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Active Makers</span>
                                <span class="badge bg-info"><?= $procurementData['active_makers'] ?? 0 ?></span>
                            </div>
                        </div>
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
                    <i class="bi bi-lightning-fill me-2"></i>Procurement Operations
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=procurement-orders/create" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Create Purchase Order
                    </a>
                    <a href="?route=requests?status=Approved" class="btn btn-success btn-sm">
                        <i class="bi bi-clipboard-check"></i> Process Approved Requests
                    </a>
                    <a href="?route=vendors" class="btn btn-warning btn-sm">
                        <i class="bi bi-building"></i> Manage Vendors
                    </a>
                    <a href="?route=procurement-orders?delivery=tracking" class="btn btn-info btn-sm">
                        <i class="bi bi-truck"></i> Track Deliveries
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Vendor Quick Stats -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-building me-2"></i>Vendor Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <i class="bi bi-building text-primary fs-3"></i>
                        <h6 class="mb-0"><?= number_format($procurementData['active_vendors'] ?? 0) ?></h6>
                        <small class="text-muted">Active Vendors</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-star-fill text-warning fs-3"></i>
                        <h6 class="mb-0"><?= number_format($procurementData['preferred_vendors'] ?? 0) ?></h6>
                        <small class="text-muted">Preferred</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-factory text-info fs-3"></i>
                        <h6 class="mb-0"><?= number_format($procurementData['active_makers'] ?? 0) ?></h6>
                        <small class="text-muted">Makers</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-cart-check text-success fs-3"></i>
                        <h6 class="mb-0"><?= number_format($procurementData['recent_po_count'] ?? 0) ?></h6>
                        <small class="text-muted">Recent POs</small>
                    </div>
                </div>
                <div class="d-grid">
                    <a href="?route=vendors/create" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Add Vendor
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">POs Created This Week</span>
                            <span class="badge bg-primary">0</span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Deliveries Scheduled</span>
                            <span class="badge bg-warning">0</span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Vendors Added</span>
                            <span class="badge bg-success">0</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-grid">
                    <a href="?route=procurement-orders" class="btn btn-outline-secondary btn-sm">
                        View All Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>