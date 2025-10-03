<!-- Finance Director Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Financial Approvals -->
        <div class="card mb-4" style="border-left: 4px solid var(--danger-color);">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-hourglass-split me-2 text-danger"></i>Pending Financial Approvals
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $financeData = $dashboardData['role_specific']['finance'] ?? [];
                    $pendingItems = [
                        ['label' => 'High Value Requests', 'count' => $financeData['pending_high_value_requests'] ?? 0, 'route' => 'requests?status=Reviewed&high_value=1', 'icon' => 'bi-file-earmark-text', 'color' => 'primary'],
                        ['label' => 'High Value Procurement', 'count' => $financeData['pending_high_value_procurement'] ?? 0, 'route' => 'procurement-orders?status=Reviewed&high_value=1', 'icon' => 'bi-cart-check', 'color' => 'warning'],
                        ['label' => 'Transfer Approvals', 'count' => $financeData['pending_transfers'] ?? 0, 'route' => 'transfers?status=Pending+Approval', 'icon' => 'bi-arrow-left-right', 'color' => 'info'],
                        ['label' => 'Maintenance Approvals', 'count' => $financeData['pending_maintenance_approval'] ?? 0, 'route' => 'maintenance?status=scheduled&high_value=1', 'icon' => 'bi-tools', 'color' => 'secondary']
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
        
        <!-- Budget Utilization -->
        <?php if (!empty($dashboardData['budget_utilization'])): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up-arrow me-2"></i>Project Budget Utilization
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th class="text-end">Budget</th>
                                <th class="text-end">Utilized</th>
                                <th class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboardData['budget_utilization'] as $project): 
                                $percentage = $project['budget'] > 0 ? round(($project['utilized'] / $project['budget']) * 100, 1) : 0;
                                $progressClass = $percentage > 90 ? 'danger' : ($percentage > 75 ? 'warning' : 'success');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($project['project_name']) ?></td>
                                <td class="text-end"><?= formatCurrency($project['budget']) ?></td>
                                <td class="text-end"><?= formatCurrency($project['utilized']) ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-<?= $progressClass ?>" role="progressbar" 
                                             style="width: <?= $percentage ?>%"><?= $percentage ?>%</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Financial Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-cash-stack me-2"></i>Financial Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total Asset Value</span>
                        <strong><?= formatCurrency($financeData['total_asset_value'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Average Asset Value</span>
                        <strong><?= formatCurrency($financeData['avg_asset_value'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">High Value Assets</span>
                        <span class="badge bg-warning"><?= $financeData['high_value_assets'] ?? 0 ?></span>
                    </div>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="?route=reports/financial" class="btn btn-primary btn-sm">
                        <i class="bi bi-file-earmark-bar-graph"></i> Financial Reports
                    </a>
                    <a href="?route=assets?high_value=1" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-eye"></i> View High Value Assets
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>Quick Stats
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <i class="bi bi-box text-primary fs-3"></i>
                        <h6 class="mb-0"><?= number_format($dashboardData['total_assets'] ?? 0) ?></h6>
                        <small class="text-muted">Total Assets</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-building text-success fs-3"></i>
                        <h6 class="mb-0"><?= number_format($dashboardData['active_projects'] ?? 0) ?></h6>
                        <small class="text-muted">Active Projects</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-tools text-warning fs-3"></i>
                        <h6 class="mb-0"><?= number_format($dashboardData['maintenance_assets'] ?? 0) ?></h6>
                        <small class="text-muted">Maintenance</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-exclamation-triangle text-danger fs-3"></i>
                        <h6 class="mb-0"><?= number_format($dashboardData['total_incidents'] ?? 0) ?></h6>
                        <small class="text-muted">Incidents</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>