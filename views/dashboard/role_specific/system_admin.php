<!-- System Admin Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- System Overview -->
        <div class="card mb-4">
            <div class="card-header bg-primary bg-opacity-10">
                <h5 class="mb-0 text-primary">
                    <i class="bi bi-speedometer2 me-2"></i>System Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <i class="bi bi-people text-primary fs-1"></i>
                            <h4 class="text-primary"><?= number_format($dashboardData['role_specific']['admin']['total_users'] ?? 0) ?></h4>
                            <p class="text-muted mb-0">Total Users</p>
                            <small class="text-success">
                                <?= number_format($dashboardData['role_specific']['admin']['active_users'] ?? 0) ?> active
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <i class="bi bi-activity text-success fs-1"></i>
                            <h4 class="text-success"><?= number_format($dashboardData['role_specific']['admin']['active_sessions'] ?? 0) ?></h4>
                            <p class="text-muted mb-0">Active Sessions</p>
                            <small class="text-muted">Current online users</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <i class="bi bi-database text-info fs-1"></i>
                            <h4 class="text-info"><?= number_format($dashboardData['total_assets'] ?? 0) ?></h4>
                            <p class="text-muted mb-0">Total Assets</p>
                            <small class="text-muted">System-wide</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <i class="bi bi-building text-warning fs-1"></i>
                            <h4 class="text-warning"><?= number_format($dashboardData['active_projects'] ?? 0) ?></h4>
                            <p class="text-muted mb-0">Active Projects</p>
                            <small class="text-muted">Currently running</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Health -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-shield-check me-2"></i>System Health & Metrics
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Asset Management</h6>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Available Assets</span>
                                <span class="text-success"><?= number_format($dashboardData['available_assets'] ?? 0) ?></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?= ($dashboardData['available_assets'] ?? 0) / max(($dashboardData['total_assets'] ?? 1), 1) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>In Use Assets</span>
                                <span class="text-warning"><?= number_format($dashboardData['in_use_assets'] ?? 0) ?></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: <?= ($dashboardData['in_use_assets'] ?? 0) / max(($dashboardData['total_assets'] ?? 1), 1) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Under Maintenance</span>
                                <span class="text-info"><?= number_format($dashboardData['maintenance_assets'] ?? 0) ?></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: <?= ($dashboardData['maintenance_assets'] ?? 0) / max(($dashboardData['total_assets'] ?? 1), 1) * 100 ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Workflow Status</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Pending Withdrawals</span>
                                <span class="badge bg-<?= ($dashboardData['pending_withdrawals'] ?? 0) > 0 ? 'warning' : 'success' ?>">
                                    <?= $dashboardData['pending_withdrawals'] ?? 0 ?>
                                </span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Overdue Items</span>
                                <span class="badge bg-<?= ($dashboardData['overdue_withdrawals'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                                    <?= $dashboardData['overdue_withdrawals'] ?? 0 ?>
                                </span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Total Incidents</span>
                                <span class="badge bg-<?= ($dashboardData['total_incidents'] ?? 0) > 0 ? 'warning' : 'success' ?>">
                                    <?= $dashboardData['total_incidents'] ?? 0 ?>
                                </span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Scheduled Maintenance</span>
                                <span class="badge bg-info"><?= $dashboardData['scheduled_maintenance'] ?? 0 ?></span>
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
                    <i class="bi bi-lightning-fill me-2"></i>System Administration
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=users" class="btn btn-primary btn-sm">
                        <i class="bi bi-people"></i> Manage Users
                    </a>
                    <a href="?route=admin/settings" class="btn btn-secondary btn-sm">
                        <i class="bi bi-gear"></i> System Settings
                    </a>
                    <a href="?route=admin/logs" class="btn btn-warning btn-sm">
                        <i class="bi bi-activity"></i> View Logs
                    </a>
                    <a href="?route=reports" class="btn btn-info btn-sm">
                        <i class="bi bi-graph-up"></i> Generate Reports
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-server me-2"></i>System Status
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-database text-success"></i> Database</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-shield-check text-success"></i> Authentication</span>
                        <span class="badge bg-success">Active</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cloud-check text-success"></i> API Services</span>
                        <span class="badge bg-success">Running</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-qr-code-scan text-success"></i> QR Scanner</span>
                        <span class="badge bg-success">Ready</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-envelope text-warning"></i> Email Service</span>
                        <span class="badge bg-warning">Limited</span>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span><strong>System Version</strong></span>
                    <small class="text-muted"><?= APP_VERSION ?? '2.0.0' ?></small>
                </div>
            </div>
        </div>
        
        <!-- Recent Admin Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>Recent Admin Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Users Created This Week</span>
                            <span class="badge bg-primary">0</span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Settings Modified</span>
                            <span class="badge bg-warning">0</span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Reports Generated</span>
                            <span class="badge bg-info">0</span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">System Maintenance</span>
                            <span class="badge bg-success">0</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-grid">
                    <a href="?route=admin/logs" class="btn btn-outline-secondary btn-sm">
                        View Full Log
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>