<!-- Project Manager Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Project Actions -->
        <div class="card mb-4" style="border-left: 4px solid var(--info-color);">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clipboard-check me-2 text-info"></i>Pending Project Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $projectData = $dashboardData['role_specific']['project_manager'] ?? [];
                    $pendingItems = [
                        ['label' => 'Request Reviews', 'count' => $projectData['pending_request_reviews'] ?? 0, 'route' => 'requests?status=Submitted', 'icon' => 'bi-file-earmark-text', 'color' => 'primary'],
                        ['label' => 'Equipment Verifications', 'count' => $dashboardData['borrowed_tools']['pending_verification'] ?? 0, 'route' => 'borrowed-tools?status=Pending+Verification', 'icon' => 'bi-tools', 'color' => 'warning'],
                        ['label' => 'Withdrawal Approvals', 'count' => $projectData['pending_withdrawal_approvals'] ?? 0, 'route' => 'withdrawals?status=Pending+Approval', 'icon' => 'bi-box-arrow-right', 'color' => 'success'],
                        ['label' => 'Transfer Approvals', 'count' => $projectData['pending_transfer_approvals'] ?? 0, 'route' => 'transfers?status=Pending+Verification', 'icon' => 'bi-arrow-left-right', 'color' => 'warning'],
                        ['label' => 'Receipt Confirmations', 'count' => $projectData['pending_receipt_confirmations'] ?? 0, 'route' => 'procurement-orders?status=Delivered', 'icon' => 'bi-check-circle', 'color' => 'info']
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
        
        <!-- Project Resource Overview -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart me-2"></i>Project Resource Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Current Project Assets</h6>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Available Assets</span>
                                <span class="badge bg-success"><?= number_format($projectData['available_project_assets'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>In Use Assets</span>
                                <span class="badge bg-warning"><?= number_format($projectData['in_use_project_assets'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total Asset Value</span>
                                <strong><?= formatCurrency($projectData['project_asset_value'] ?? 0) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Project Management</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Managed Projects</span>
                                <span class="badge bg-primary"><?= $projectData['managed_projects'] ?? 0 ?></span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Assigned Projects</span>
                                <span class="badge bg-info"><?= $projectData['assigned_projects'] ?? 0 ?></span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>Pending Investigations</span>
                                <span class="badge bg-warning"><?= $projectData['pending_incident_investigations'] ?? 0 ?></span>
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
                    <i class="bi bi-lightning-fill me-2"></i>Project Management
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=requests?status=Submitted" class="btn btn-primary btn-sm">
                        <i class="bi bi-clipboard-check"></i> Review Requests
                    </a>
                    <a href="?route=borrowed-tools?status=Pending+Verification" class="btn btn-warning btn-sm">
                        <i class="bi bi-tools"></i> Verify Equipment
                    </a>
                    <a href="?route=withdrawals?status=Pending+Approval" class="btn btn-success btn-sm">
                        <i class="bi bi-check2-square"></i> Approve Withdrawals
                    </a>
                    <a href="?route=transfers?status=Pending+Verification" class="btn btn-warning btn-sm">
                        <i class="bi bi-arrow-left-right"></i> Verify Transfers
                    </a>
                    <a href="?route=incidents?status=Pending+Verification" class="btn btn-danger btn-sm">
                        <i class="bi bi-shield-exclamation"></i> Investigate Incidents
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Project Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-building me-2"></i>Project Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <i class="bi bi-building text-primary fs-3"></i>
                        <h6 class="mb-0"><?= number_format($projectData['managed_projects'] ?? 0) ?></h6>
                        <small class="text-muted">Managed</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-person-check text-success fs-3"></i>
                        <h6 class="mb-0"><?= number_format($projectData['assigned_projects'] ?? 0) ?></h6>
                        <small class="text-muted">Assigned</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-box text-info fs-3"></i>
                        <h6 class="mb-0"><?= number_format($projectData['available_project_assets'] ?? 0) ?></h6>
                        <small class="text-muted">Available</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-gear text-warning fs-3"></i>
                        <h6 class="mb-0"><?= number_format($projectData['in_use_project_assets'] ?? 0) ?></h6>
                        <small class="text-muted">In Use</small>
                    </div>
                </div>
                <div class="d-grid">
                    <a href="?route=projects" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View All Projects
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Return Transit Monitoring -->
        <div class="card mb-4">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="mb-0 text-warning">
                    <i class="bi bi-truck me-2"></i>Return Transit Monitor
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <i class="bi bi-arrow-return-left text-info fs-3"></i>
                        <h6 class="mb-0"><?= number_format($projectData['returns_in_transit'] ?? 0) ?></h6>
                        <small class="text-muted">In Transit</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-exclamation-triangle text-danger fs-3"></i>
                        <h6 class="mb-0"><?= number_format($projectData['overdue_return_transits'] ?? 0) ?></h6>
                        <small class="text-muted">Overdue</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-clock-history text-warning fs-3"></i>
                        <h6 class="mb-0"><?= number_format($projectData['pending_return_receipts'] ?? 0) ?></h6>
                        <small class="text-muted">To Receive</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="bi bi-calendar-x text-secondary fs-3"></i>
                        <h6 class="mb-0"><?= number_format($projectData['overdue_returns'] ?? 0) ?></h6>
                        <small class="text-muted">Overdue Returns</small>
                    </div>
                </div>
                
                <?php if (($projectData['overdue_return_transits'] ?? 0) > 0 || ($projectData['pending_return_receipts'] ?? 0) > 0): ?>
                <div class="alert alert-warning p-2 mb-3">
                    <small>
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Action Required:</strong> You have returns that need attention.
                    </small>
                </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2">
                    <?php if (($projectData['pending_return_receipts'] ?? 0) > 0): ?>
                    <a href="?route=transfers&return_status=in_return_transit" class="btn btn-warning btn-sm">
                        <i class="bi bi-box-arrow-in-down"></i> Receive Returns (<?= $projectData['pending_return_receipts'] ?? 0 ?>)
                    </a>
                    <?php endif; ?>
                    <a href="?route=transfers&tab=returns" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye"></i> Monitor All Returns
                    </a>
                </div>
            </div>
        </div>

        <!-- Today's Tasks -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-check me-2"></i>Today's Tasks
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-text text-primary"></i> Requests to Review</span>
                            <span class="badge bg-primary"><?= $projectData['pending_request_reviews'] ?? 0 ?></span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-tools text-warning"></i> Equipment to Verify</span>
                            <span class="badge bg-warning"><?= $dashboardData['borrowed_tools']['pending_verification'] ?? 0 ?></span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-box-arrow-right text-success"></i> Withdrawals to Approve</span>
                            <span class="badge bg-success"><?= $projectData['pending_withdrawal_approvals'] ?? 0 ?></span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-arrow-left-right text-warning"></i> Transfers to Verify</span>
                            <span class="badge bg-warning"><?= $projectData['pending_transfer_approvals'] ?? 0 ?></span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-truck text-info"></i> Return Transits</span>
                            <span class="badge bg-info"><?= $projectData['returns_in_transit'] ?? 0 ?></span>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-shield-exclamation text-danger"></i> Incidents to Investigate</span>
                            <span class="badge bg-danger"><?= $projectData['pending_incident_investigations'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>