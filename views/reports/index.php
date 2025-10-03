<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshReports()">
        <i class="bi bi-arrow-clockwise me-1"></i>
        <span class="d-none d-sm-inline">Refresh</span>
    </button>
</div>

<!-- Quick Stats Overview -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-box text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Assets</h6>
                        <h3 class="mb-0"><?= $dashboardStats['assets']['total_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-graph-up me-1"></i>Across all projects
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-building text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Active Projects</h6>
                        <h3 class="mb-0"><?= $dashboardStats['projects']['active_projects'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-check-circle me-1"></i>Currently running
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-arrow-up-right text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Active Withdrawals</h6>
                        <h3 class="mb-0"><?= $dashboardStats['withdrawals']['released_withdrawals'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clock me-1"></i>Currently released
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--danger-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-exclamation-triangle text-danger fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Open Incidents</h6>
                        <h3 class="mb-0"><?= ($dashboardStats['incidents']['under_investigation'] ?? 0) + ($dashboardStats['incidents']['verified_incidents'] ?? 0) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-shield-exclamation me-1"></i>Requires attention
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Available Reports -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Available Reports
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Asset Utilization Report -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="bi bi-graph-up-arrow display-6 text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title mb-1">Asset Utilization</h6>
                                        <small class="text-muted">Track asset usage and efficiency</small>
                                    </div>
                                </div>
                                <p class="card-text small">
                                    Analyze how effectively assets are being utilized across projects, 
                                    identify idle assets, and optimize resource allocation.
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="?route=reports/utilization" class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i>View Report
                                    </a>
                                    <small class="text-muted">Updated daily</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Withdrawal Report -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="bi bi-arrow-up-right display-6 text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title mb-1">Withdrawals</h6>
                                        <small class="text-muted">Monitor asset withdrawals and returns</small>
                                    </div>
                                </div>
                                <p class="card-text small">
                                    Track withdrawal patterns, identify overdue returns, and analyze 
                                    asset movement across projects and teams.
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="?route=reports/withdrawals" class="btn btn-success btn-sm">
                                        <i class="bi bi-eye me-1"></i>View Report
                                    </a>
                                    <small class="text-muted">Real-time</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transfer Report -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="bi bi-arrow-left-right display-6 text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title mb-1">Transfers</h6>
                                        <small class="text-muted">Asset movement between projects</small>
                                    </div>
                                </div>
                                <p class="card-text small">
                                    Monitor asset transfers between projects, track approval times, 
                                    and analyze transfer patterns and efficiency.
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="?route=reports/transfers" class="btn btn-info btn-sm">
                                        <i class="bi bi-eye me-1"></i>View Report
                                    </a>
                                    <small class="text-muted">Real-time</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Maintenance Report -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="bi bi-tools display-6 text-warning"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title mb-1">Maintenance</h6>
                                        <small class="text-muted">Track maintenance activities and costs</small>
                                    </div>
                                </div>
                                <p class="card-text small">
                                    Analyze maintenance schedules, costs, and effectiveness. 
                                    Identify maintenance trends and optimize schedules.
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="?route=reports/maintenance" class="btn btn-warning btn-sm">
                                        <i class="bi bi-eye me-1"></i>View Report
                                    </a>
                                    <small class="text-muted">Updated daily</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Incident Report -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="bi bi-exclamation-triangle display-6 text-danger"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title mb-1">Incidents</h6>
                                        <small class="text-muted">Monitor asset incidents and resolution</small>
                                    </div>
                                </div>
                                <p class="card-text small">
                                    Track incident reports, resolution times, and trends. 
                                    Identify patterns to improve asset security and handling.
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="?route=reports/incidents" class="btn btn-danger btn-sm">
                                        <i class="bi bi-eye me-1"></i>View Report
                                    </a>
                                    <small class="text-muted">Real-time</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Procurement Report -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="bi bi-cart display-6 text-secondary"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title mb-1">Procurement</h6>
                                        <small class="text-muted">Asset acquisition and spending analysis</small>
                                    </div>
                                </div>
                                <p class="card-text small">
                                    Analyze procurement patterns, vendor performance, and spending 
                                    trends across categories and projects.
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="?route=procurement/reports" class="btn btn-secondary btn-sm">
                                        <i class="bi bi-eye me-1"></i>View Report
                                    </a>
                                    <small class="text-muted">Updated daily</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=reports/export?type=summary&format=csv" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download me-1"></i>Export Summary Report
                    </a>
                    <a href="?route=dashboard" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-speedometer2 me-1"></i>View Dashboard
                    </a>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="scheduleReport()">
                        <i class="bi bi-calendar-event me-1"></i>Schedule Report
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Report Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Report Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Reports are updated in real-time or daily
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Export options available in CSV format
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Filter by date range, project, or category
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Historical data available for trend analysis
                    </li>
                    <li class="mb-0">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Access controlled by user permissions
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Refresh reports data
function refreshReports() {
    window.location.reload();
}

// Schedule report (placeholder function)
function scheduleReport() {
    alert('Report scheduling feature coming soon!');
}

// Auto-refresh every 5 minutes for real-time data
setInterval(function() {
    // Only refresh if user is still on the page
    if (document.visibilityState === 'visible') {
        const quickStats = document.querySelectorAll('.card h3');
        // Add subtle animation to indicate refresh
        quickStats.forEach(stat => {
            stat.style.opacity = '0.7';
            setTimeout(() => {
                stat.style.opacity = '1';
            }, 500);
        });
    }
}, 300000); // 5 minutes
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
