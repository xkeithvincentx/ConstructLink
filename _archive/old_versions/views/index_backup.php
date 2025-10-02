<?php
// Set page variables for the layout
$pageTitle = 'Dashboard - ConstructLink™';
$pageHeader = 'Dashboard';

// Get user data
$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$dashboardData = $dashboardData ?? [];

// Start content capture
ob_start();
?>

<!-- Welcome Message -->
<div class="alert alert-info d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-person-circle me-3 fs-4"></i>
    <div>
        <h5 class="alert-heading mb-1">Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h5>
        <p class="mb-0">Role: <strong><?= htmlspecialchars($userRole) ?></strong> | Last login: <?= date('M j, Y g:i A') ?></p>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-lightning-charge me-2"></i>Quick Actions
                </h6>
                <div class="btn-group flex-wrap" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    
                    <?php if ($userRole === 'System Admin' || $userRole === 'Asset Director' || $userRole === 'Procurement Officer'): ?>
                    <a href="?route=assets/create" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-plus-circle"></i> Add Asset
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($userRole === 'System Admin' || $userRole === 'Project Manager' || $userRole === 'Site Inventory Clerk'): ?>
                    <a href="?route=withdrawals/create" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-box-arrow-right"></i> New Withdrawal
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($userRole === 'System Admin' || $userRole === 'Asset Director'): ?>
                    <a href="?route=assets/scanner" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-qr-code-scan"></i> QR Scanner
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($userRole === 'System Admin'): ?>
                    <a href="?route=reports" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Statistics Cards -->
<div class="row mb-4">
    <!-- Total Assets -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100 border-primary">
            <div class="card-body text-center">
                <div class="text-primary mb-2">
                    <i class="bi bi-box fs-1"></i>
                </div>
                <h3 class="text-primary"><?= number_format($dashboardData['total_assets'] ?? 0) ?></h3>
                <p class="card-text text-muted">Total Assets</p>
                <small class="text-muted">
                    Value: <?= formatCurrency($dashboardData['total_asset_value'] ?? 0) ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Available Assets -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <div class="text-success mb-2">
                    <i class="bi bi-check-circle fs-1"></i>
                </div>
                <h3 class="text-success"><?= number_format($dashboardData['available_assets'] ?? 0) ?></h3>
                <p class="card-text text-muted">Available</p>
                <small class="text-muted">
                    <?php 
                    $total = $dashboardData['total_assets'] ?? 1;
                    $available = $dashboardData['available_assets'] ?? 0;
                    $percentage = $total > 0 ? round(($available / $total) * 100, 1) : 0;
                    echo $percentage . '% of total';
                    ?>
                </small>
            </div>
        </div>
    </div>

    <!-- In Use Assets -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100 border-warning">
            <div class="card-body text-center">
                <div class="text-warning mb-2">
                    <i class="bi bi-gear fs-1"></i>
                </div>
                <h3 class="text-warning"><?= number_format($dashboardData['in_use_assets'] ?? 0) ?></h3>
                <p class="card-text text-muted">In Use</p>
                <small class="text-muted">
                    <?php 
                    $inUse = $dashboardData['in_use_assets'] ?? 0;
                    $percentage = $total > 0 ? round(($inUse / $total) * 100, 1) : 0;
                    echo $percentage . '% of total';
                    ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100 border-info">
            <div class="card-body text-center">
                <div class="text-info mb-2">
                    <i class="bi bi-clock fs-1"></i>
                </div>
                <h3 class="text-info"><?= number_format($dashboardData['pending_withdrawals'] ?? 0) ?></h3>
                <p class="card-text text-muted">Pending Requests</p>
                <?php if (($dashboardData['overdue_withdrawals'] ?? 0) > 0): ?>
                <small class="text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= $dashboardData['overdue_withdrawals'] ?> overdue
                </small>
                <?php else: ?>
                <small class="text-success">No overdue items</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Statistics (Role-based) -->
<?php if ($userRole === 'System Admin' || $userRole === 'Finance Director' || $userRole === 'Asset Director'): ?>
<div class="row mb-4">
    <!-- Projects -->
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-building text-primary fs-2 mb-2"></i>
                <h4><?= number_format($dashboardData['active_projects'] ?? 0) ?></h4>
                <p class="card-text text-muted">Active Projects</p>
            </div>
        </div>
    </div>

    <!-- Maintenance -->
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-tools text-warning fs-2 mb-2"></i>
                <h4><?= number_format($dashboardData['maintenance_assets'] ?? 0) ?></h4>
                <p class="card-text text-muted">Under Maintenance</p>
            </div>
        </div>
    </div>

    <!-- Incidents -->
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle text-danger fs-2 mb-2"></i>
                <h4><?= number_format($dashboardData['total_incidents'] ?? 0) ?></h4>
                <p class="card-text text-muted">Total Incidents</p>
            </div>
        </div>
    </div>

    <!-- Retired Assets -->
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-archive text-secondary fs-2 mb-2"></i>
                <h4><?= number_format($dashboardData['retired_assets'] ?? 0) ?></h4>
                <p class="card-text text-muted">Retired Assets</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Content Row -->
<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Recent Activities -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-activity me-2"></i>Recent Activities
                </h5>
                <button class="btn btn-outline-secondary btn-sm" onclick="refreshActivities()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div class="card-body">
                <?php if (!empty($dashboardData['recent_activities'])): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($dashboardData['recent_activities'], 0, 10) as $activity): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($activity['description'] ?? 'Activity') ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <?= htmlspecialchars($activity['user_name'] ?? 'System') ?>
                                        </p>
                                    </div>
                                    <small class="text-muted">
                                        <?= timeAgo($activity['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history display-4 text-muted mb-3"></i>
                        <p class="text-muted">No recent activities to display</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Role-specific Content -->
        <?php if ($userRole === 'Warehouseman' || $userRole === 'Site Inventory Clerk'): ?>
        <!-- Pending Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-check me-2"></i>Pending Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Withdrawals to Release</span>
                            <span class="badge bg-warning"><?= $dashboardData['pending_withdrawals'] ?? 0 ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Overdue Returns</span>
                            <span class="badge bg-danger"><?= $dashboardData['overdue_withdrawals'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="?route=withdrawals" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-right"></i> Manage Withdrawals
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- User Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-person-circle me-2"></i>User Information
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Full Name:</strong><br>
                    <?= htmlspecialchars($user['full_name']) ?>
                </div>
                <div class="mb-3">
                    <strong>Username:</strong><br>
                    <?= htmlspecialchars($user['username']) ?>
                </div>
                <div class="mb-3">
                    <strong>Role:</strong><br>
                    <span class="badge bg-primary"><?= htmlspecialchars($userRole) ?></span>
                </div>
                <div class="mb-3">
                    <strong>Department:</strong><br>
                    <?= htmlspecialchars($user['department'] ?? 'Not specified') ?>
                </div>
                <div class="mb-3">
                    <strong>Session Status:</strong><br>
                    <span class="badge bg-success">Active</span>
                </div>
                <div class="d-grid">
                    <a href="?route=users/profile" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-gear"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="card mb-4">
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
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>QR Scanner</span>
                    <span class="badge bg-success">Ready</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span><strong>Version</strong></span>
                    <small class="text-muted"><?= APP_VERSION ?? '1.0.0' ?></small>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php if ($userRole === 'System Admin' || $userRole === 'Finance Director'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>Quick Stats
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($dashboardData['role_specific']['admin'])): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Total Users</span>
                        <span class="badge bg-info"><?= $dashboardData['role_specific']['admin']['total_users'] ?? 0 ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Active Sessions</span>
                        <span class="badge bg-success"><?= $dashboardData['role_specific']['admin']['active_sessions'] ?? 0 ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($dashboardData['role_specific']['finance'])): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>High Value Assets</span>
                        <span class="badge bg-warning"><?= $dashboardData['role_specific']['finance']['high_value_assets'] ?? 0 ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Avg Asset Value</span>
                        <span class="text-muted"><?= formatCurrency($dashboardData['role_specific']['finance']['avg_asset_value'] ?? 0) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript for Dashboard Functionality -->
<script>
// Dashboard refresh functionality
function refreshDashboard() {
    const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
    const originalText = refreshBtn.innerHTML;
    
    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    // Simulate refresh (in real implementation, you'd call the API)
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Refresh activities
function refreshActivities() {
    const refreshBtn = document.querySelector('[onclick="refreshActivities()"]');
    const originalText = refreshBtn.innerHTML;
    
    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
    refreshBtn.disabled = true;
    
    fetch('?route=dashboard/getStats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update activities section
                console.log('Activities refreshed');
            }
        })
        .catch(error => {
            console.error('Failed to refresh activities:', error);
        })
        .finally(() => {
            refreshBtn.innerHTML = originalText;
            refreshBtn.disabled = false;
        });
}

// Auto-refresh dashboard stats every 5 minutes
setInterval(() => {
    fetch('?route=dashboard/getStats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stats cards silently
                updateStatsCards(data.data);
            }
        })
        .catch(error => {
            console.error('Auto-refresh failed:', error);
        });
}, 300000); // 5 minutes

// Update stats cards
function updateStatsCards(data) {
    // Update asset counts
    if (data.assets) {
        const totalElement = document.querySelector('.text-primary h3');
        if (totalElement) totalElement.textContent = new Intl.NumberFormat().format(data.assets.total_assets || 0);
        
        const availableElement = document.querySelector('.text-success h3');
        if (availableElement) availableElement.textContent = new Intl.NumberFormat().format(data.assets.available_assets || 0);
        
        const inUseElement = document.querySelector('.text-warning h3');
        if (inUseElement) inUseElement.textContent = new Intl.NumberFormat().format(data.assets.in_use_assets || 0);
    }
    
    // Update withdrawal counts
    if (data.withdrawals) {
        const pendingElement = document.querySelector('.text-info h3');
        if (pendingElement) pendingElement.textContent = new Intl.NumberFormat().format(data.withdrawals.pending_withdrawals || 0);
    }
}

// Add spinning animation for refresh buttons
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set breadcrumbs
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
