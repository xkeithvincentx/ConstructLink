<!-- Generic Dashboard Content -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-check me-2"></i>Key Metrics
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="bi bi-building text-primary fs-2 mb-2"></i>
                            <h4><?= number_format($dashboardData['active_projects'] ?? 0) ?></h4>
                            <p class="text-muted mb-0">Active Projects</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="bi bi-tools text-warning fs-2 mb-2"></i>
                            <h4><?= number_format($dashboardData['maintenance_assets'] ?? 0) ?></h4>
                            <p class="text-muted mb-0">Under Maintenance</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="bi bi-exclamation-triangle text-danger fs-2 mb-2"></i>
                            <h4><?= number_format($dashboardData['total_incidents'] ?? 0) ?></h4>
                            <p class="text-muted mb-0">Total Incidents</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- System Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>System Status
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Database</span>
                    <span class="badge bg-success">Online</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Authentication</span>
                    <span class="badge bg-success">Working</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>API Services</span>
                    <span class="badge bg-success">Active</span>
                </div>
                <hr>
                <div class="d-grid">
                    <a href="?route=users/profile" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-gear"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>