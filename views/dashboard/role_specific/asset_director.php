<!-- Asset Director Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Asset Actions -->
        <div class="card mb-4" style="border-left: 4px solid var(--warning-color);">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-2 text-warning"></i>Pending Asset Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $assetData = $dashboardData['role_specific']['asset_director'] ?? [];
                    $pendingItems = [
                        ['label' => 'Procurement Verification', 'count' => $assetData['pending_procurement_verification'] ?? 0, 'route' => 'procurement-orders?status=Pending', 'icon' => 'bi-box-seam', 'color' => 'primary'],
                        ['label' => 'Equipment Approvals', 'count' => $dashboardData['borrowed_tools']['pending_approval'] ?? 0, 'route' => 'borrowed-tools?status=Pending+Approval', 'icon' => 'bi-clipboard-check', 'color' => 'info'],
                        ['label' => 'Delivery Discrepancies', 'count' => $assetData['pending_discrepancies'] ?? 0, 'route' => 'delivery-tracking?status=Discrepancy+Reported', 'icon' => 'bi-truck', 'color' => 'danger'],
                        ['label' => 'Incident Resolution', 'count' => $assetData['pending_incident_resolution'] ?? 0, 'route' => 'incidents?status=Pending+Authorization', 'icon' => 'bi-shield-exclamation', 'color' => 'warning'],
                        ['label' => 'Maintenance Authorization', 'count' => $assetData['pending_maintenance_authorization'] ?? 0, 'route' => 'maintenance?status=scheduled', 'icon' => 'bi-tools', 'color' => 'success']
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
                                <i class="bi bi-eye me-1"></i>Review Now
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Asset Health Overview -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-heart-pulse me-2"></i>Asset Health Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Asset Status Distribution</h6>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Available</span>
                                <span class="text-success"><?= number_format($dashboardData['available_assets'] ?? 0) ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?= ($dashboardData['available_assets'] ?? 0) / max(($dashboardData['total_assets'] ?? 1), 1) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>In Use</span>
                                <span class="text-warning"><?= number_format($dashboardData['in_use_assets'] ?? 0) ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-warning" style="width: <?= ($dashboardData['in_use_assets'] ?? 0) / max(($dashboardData['total_assets'] ?? 1), 1) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Under Maintenance</span>
                                <span class="text-info"><?= number_format($assetData['assets_under_maintenance'] ?? 0) ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-info" style="width: <?= ($assetData['assets_under_maintenance'] ?? 0) / max(($dashboardData['total_assets'] ?? 1), 1) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Retired/Disposed</span>
                                <span class="text-secondary"><?= number_format(($assetData['retired_assets'] ?? 0) + ($assetData['disposed_assets'] ?? 0)) ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-secondary" style="width: <?= (($assetData['retired_assets'] ?? 0) + ($assetData['disposed_assets'] ?? 0)) / max(($dashboardData['total_assets'] ?? 1), 1) * 100 ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Key Metrics</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Asset Utilization Rate</span>
                                <span class="badge bg-<?= ($assetData['utilization_rate'] ?? 0) > 80 ? 'success' : 'warning' ?> rounded-pill">
                                    <?= $assetData['utilization_rate'] ?? 0 ?>%
                                </span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Categories in Use</span>
                                <span class="badge bg-primary rounded-pill"><?= $assetData['categories_in_use'] ?? 0 ?></span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Total Asset Value</span>
                                <strong><?= formatCurrency($dashboardData['total_asset_value'] ?? 0) ?></strong>
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
                    <i class="bi bi-lightning-fill me-2"></i>Asset Management
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=borrowed-tools?status=Pending+Approval" class="btn btn-info btn-sm">
                        <i class="bi bi-clipboard-check"></i> Approve Equipment
                    </a>
                    <a href="?route=assets/create" class="btn btn-success btn-sm">
                        <i class="bi bi-plus-circle"></i> Add New Asset
                    </a>
                    <a href="?route=assets/scanner" class="btn btn-info btn-sm">
                        <i class="bi bi-qr-code-scan"></i> QR Scanner
                    </a>
                    <a href="?route=maintenance/create" class="btn btn-warning btn-sm">
                        <i class="bi bi-tools"></i> Schedule Maintenance
                    </a>
                    <a href="?route=categories" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-tags"></i> Manage Categories
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Return Transit Monitoring -->
        <div class="card mb-4" style="border-left: 4px solid var(--info-color);">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-truck me-2 text-info"></i>Return Transits
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-12 mb-2">
                        <i class="bi bi-arrow-return-left text-warning fs-2"></i>
                        <h5 class="mb-0"><?= number_format($assetData['returns_in_transit'] ?? 0) ?></h5>
                        <small class="text-muted">Assets in Return Transit</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <span class="badge bg-danger rounded-pill"><?= $assetData['overdue_return_transits'] ?? 0 ?></span>
                            <br><small class="text-muted">Overdue</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <span class="badge bg-warning rounded-pill"><?= $assetData['pending_return_receipts'] ?? 0 ?></span>
                            <br><small class="text-muted">To Receive</small>
                        </div>
                    </div>
                </div>
                
                <?php if (($assetData['overdue_return_transits'] ?? 0) > 0): ?>
                <div class="alert alert-danger p-2 mt-3 mb-2">
                    <small>
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong><?= $assetData['overdue_return_transits'] ?> return(s)</strong> stuck in transit!
                    </small>
                </div>
                <?php endif; ?>
                
                <div class="d-grid">
                    <a href="?route=transfers&tab=returns" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye"></i> Monitor Returns
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Incidents -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-shield-exclamation me-2"></i>Recent Incidents
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php if ($dashboardData['lost_assets'] ?? 0 > 0): ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-geo-alt text-danger"></i> Lost Assets</span>
                        <span class="badge bg-danger"><?= $dashboardData['lost_assets'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($dashboardData['damaged_assets'] ?? 0 > 0): ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-hammer text-warning"></i> Damaged Assets</span>
                        <span class="badge bg-warning"><?= $dashboardData['damaged_assets'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($dashboardData['stolen_assets'] ?? 0 > 0): ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-shield-x text-danger"></i> Stolen Assets</span>
                        <span class="badge bg-danger"><?= $dashboardData['stolen_assets'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($dashboardData['lost_assets'] ?? 0) + ($dashboardData['damaged_assets'] ?? 0) + ($dashboardData['stolen_assets'] ?? 0) == 0): ?>
                    <p class="text-muted text-center mb-0">No recent incidents</p>
                    <?php endif; ?>
                </div>
                <div class="mt-3 d-grid">
                    <a href="?route=incidents" class="btn btn-outline-danger btn-sm">
                        View All Incidents
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>