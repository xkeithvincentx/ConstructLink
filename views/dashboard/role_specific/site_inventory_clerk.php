<!-- Site Inventory Clerk Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Site Actions -->
        <div class="card mb-4" style="border-left: 4px solid var(--warning-color);">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clipboard-data me-2 text-warning"></i>Pending Site Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $siteData = $dashboardData['role_specific']['site_clerk'] ?? [];
                    $pendingItems = [
                        ['label' => 'Draft Requests', 'count' => $siteData['draft_requests'] ?? 0, 'route' => 'requests?status=Draft', 'icon' => 'bi-file-earmark-text', 'color' => 'primary'],
                        ['label' => 'Deliveries to Verify', 'count' => $siteData['deliveries_to_verify'] ?? 0, 'route' => 'procurement-orders?status=Delivered', 'icon' => 'bi-clipboard-check', 'color' => 'success'],
                        ['label' => 'Transfers to Receive', 'count' => $siteData['transfers_to_receive'] ?? 0, 'route' => 'transfers?status=Approved', 'icon' => 'bi-arrow-down', 'color' => 'info'],
                        ['label' => 'Withdrawals to Verify', 'count' => $siteData['withdrawals_to_verify'] ?? 0, 'route' => 'withdrawals?status=Pending+Verification', 'icon' => 'bi-check-circle', 'color' => 'warning']
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
        
        <!-- Site Inventory Status -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Site Inventory Status
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Current Site Assets</h6>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Available on Site</span>
                                <span class="badge bg-success"><?= number_format($siteData['available_on_site'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>In Use on Site</span>
                                <span class="badge bg-warning"><?= number_format($siteData['in_use_on_site'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Low Stock Alerts</span>
                                <span class="badge bg-<?= ($siteData['low_stock_alerts'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                                    <?= number_format($siteData['low_stock_alerts'] ?? 0) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Today's Activity</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Tools Borrowed</span>
                                <span class="badge bg-primary"><?= $siteData['tools_borrowed_today'] ?? 0 ?></span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Tools Returned</span>
                                <span class="badge bg-info"><?= $siteData['tools_returned_today'] ?? 0 ?></span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Requests Created</span>
                                <span class="badge bg-success"><?= $siteData['requests_created_today'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (($siteData['low_stock_alerts'] ?? 0) > 0): ?>
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong>Low Stock Alert:</strong> <?= $siteData['low_stock_alerts'] ?> items are running low. Consider creating requests.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning-fill me-2"></i>Site Operations
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=requests/create" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Create Request
                    </a>
                    <a href="?route=incidents/create" class="btn btn-danger btn-sm">
                        <i class="bi bi-exclamation-circle"></i> Report Incident
                    </a>
                    <a href="?route=transfers/create" class="btn btn-info btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Initiate Transfer
                    </a>
                    <a href="?route=maintenance/create" class="btn btn-warning btn-sm">
                        <i class="bi bi-tools"></i> Schedule Maintenance
                    </a>
                </div>
            </div>
        </div>

        <!-- Project Equipment -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-tools me-2"></i>Project Equipment
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-arrow-right text-primary"></i> Currently Borrowed</span>
                        <span class="badge bg-primary"><?= $dashboardData['borrowed_tools']['project_borrowed'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-exclamation-triangle text-danger"></i> Overdue Returns</span>
                        <span class="badge bg-danger"><?= $dashboardData['borrowed_tools']['project_overdue'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-event text-warning"></i> Due This Week</span>
                        <span class="badge bg-warning"><?= $dashboardData['borrowed_tools']['project_due_soon'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam text-success"></i> Available</span>
                        <span class="badge bg-success"><?= $dashboardData['borrowed_tools']['project_available'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="mt-3 d-grid gap-2">
                    <a href="?route=borrowed-tools" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View All Equipment
                    </a>
                    <a href="?route=borrowed-tools/create-batch" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Borrow Equipment
                    </a>
                </div>
            </div>
        </div>

        <!-- Incident Management -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-shield-exclamation me-2"></i>Incident Management
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-exclamation-triangle text-warning"></i> Open Incidents</span>
                        <span class="badge bg-warning"><?= $siteData['open_incidents'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock text-info"></i> Recent (7 days)</span>
                        <span class="badge bg-info"><?= $siteData['recent_incidents'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-geo-alt text-danger"></i> Lost Items</span>
                        <span class="badge bg-danger"><?= $siteData['lost_items'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-hammer text-warning"></i> Damaged Items</span>
                        <span class="badge bg-warning"><?= $siteData['damaged_items'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="mt-3 d-grid">
                    <a href="?route=incidents" class="btn btn-outline-danger btn-sm">
                        View All Incidents
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>Daily Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <i class="bi bi-tools text-primary fs-3"></i>
                        <h6 class="mb-0"><?= number_format($siteData['tools_borrowed_today'] ?? 0) ?></h6>
                        <small class="text-muted">Tools Out</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-arrow-counterclockwise text-success fs-3"></i>
                        <h6 class="mb-0"><?= number_format($siteData['tools_returned_today'] ?? 0) ?></h6>
                        <small class="text-muted">Tools In</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-file-earmark-plus text-info fs-3"></i>
                        <h6 class="mb-0"><?= number_format($siteData['requests_created_today'] ?? 0) ?></h6>
                        <small class="text-muted">Requests Made</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-clipboard-check text-warning fs-3"></i>
                        <h6 class="mb-0"><?= number_format($siteData['deliveries_to_verify'] ?? 0) ?></h6>
                        <small class="text-muted">To Verify</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>