<?php
// Set page variables for the layout
$pageTitle = 'Dashboard - ConstructLink™';
$pageHeader = 'Dashboard';

// Get user data
$user = Auth::getInstance()->getCurrentUser();
$userRole = $dashboardData['user_role'] ?? 'Guest';
$userId = $dashboardData['user_id'] ?? null;

// Start content capture
ob_start();
?>

<!-- Welcome Message -->
<div class="alert alert-info d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-person-circle me-3 fs-4"></i>
    <div>
        <h5 class="alert-heading mb-1">Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h5>
        <p class="mb-0">Role: <strong><?= htmlspecialchars($userRole) ?></strong> | 
            <?php if ($user['department']): ?>Department: <strong><?= htmlspecialchars($user['department']) ?></strong> | <?php endif; ?>
            Last login: <?= date('M j, Y g:i A', strtotime($user['last_login'] ?? 'now')) ?>
        </p>
    </div>
</div>

<!-- Role-Specific Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-lightning-charge me-2"></i>Quick Actions
                </h6>
                <div class="btn-group flex-wrap" role="group">
                    <?php
                    // Define role-specific quick actions
                    $quickActions = [];
                    
                    switch ($userRole) {
                        case 'System Admin':
                            $quickActions = [
                                ['icon' => 'bi-people', 'text' => 'Manage Users', 'route' => 'users', 'color' => 'primary'],
                                ['icon' => 'bi-gear', 'text' => 'System Settings', 'route' => 'admin/settings', 'color' => 'secondary'],
                                ['icon' => 'bi-graph-up', 'text' => 'Reports', 'route' => 'reports', 'color' => 'info'],
                                ['icon' => 'bi-activity', 'text' => 'View Logs', 'route' => 'admin/logs', 'color' => 'warning'],
                            ];
                            break;
                            
                        case 'Finance Director':
                            $quickActions = [
                                ['icon' => 'bi-check-circle', 'text' => 'Pending Approvals', 'route' => 'requests?status=Reviewed', 'color' => 'primary'],
                                ['icon' => 'bi-cash-stack', 'text' => 'High Value Items', 'route' => 'assets?high_value=1', 'color' => 'success'],
                                ['icon' => 'bi-graph-up', 'text' => 'Financial Reports', 'route' => 'reports', 'color' => 'info'],
                                ['icon' => 'bi-calculator', 'text' => 'Budget Overview', 'route' => 'projects', 'color' => 'warning'],
                            ];
                            break;
                            
                        case 'Asset Director':
                            $quickActions = [
                                ['icon' => 'bi-box-seam', 'text' => 'Verify Inventory', 'route' => 'procurement-orders?status=Pending', 'color' => 'primary'],
                                ['icon' => 'bi-exclamation-triangle', 'text' => 'Resolve Incidents', 'route' => 'incidents?status=Pending+Authorization', 'color' => 'danger'],
                                ['icon' => 'bi-tools', 'text' => 'Maintenance', 'route' => 'maintenance', 'color' => 'warning'],
                                ['icon' => 'bi-qr-code-scan', 'text' => 'QR Scanner', 'route' => 'assets/scanner', 'color' => 'info'],
                            ];
                            break;
                            
                        case 'Procurement Officer':
                            $quickActions = [
                                ['icon' => 'bi-cart-plus', 'text' => 'Create PO', 'route' => 'procurement-orders/create', 'color' => 'primary'],
                                ['icon' => 'bi-clipboard-check', 'text' => 'Approved Requests', 'route' => 'requests?status=Approved', 'color' => 'success'],
                                ['icon' => 'bi-truck', 'text' => 'Delivery Tracking', 'route' => 'procurement-orders?delivery=pending', 'color' => 'info'],
                                ['icon' => 'bi-building', 'text' => 'Manage Vendors', 'route' => 'vendors', 'color' => 'secondary'],
                            ];
                            break;
                            
                        case 'Warehouseman':
                            $quickActions = [
                                ['icon' => 'bi-box-arrow-in-down', 'text' => 'Receive Delivery', 'route' => 'procurement-orders/for-receipt', 'color' => 'primary'],
                                ['icon' => 'bi-box-arrow-right', 'text' => 'Release Items', 'route' => 'withdrawals?status=Approved', 'color' => 'warning'],
                                ['icon' => 'bi-tools', 'text' => 'Tool Borrowing', 'route' => 'borrowed-tools/create', 'color' => 'info'],
                                ['icon' => 'bi-clipboard-data', 'text' => 'Stock Levels', 'route' => 'assets', 'color' => 'secondary'],
                            ];
                            break;
                            
                        case 'Project Manager':
                            $quickActions = [
                                ['icon' => 'bi-clipboard-check', 'text' => 'Review Requests', 'route' => 'requests?status=Submitted', 'color' => 'primary'],
                                ['icon' => 'bi-check2-square', 'text' => 'Approve Withdrawals', 'route' => 'withdrawals?status=Pending+Approval', 'color' => 'success'],
                                ['icon' => 'bi-arrow-left-right', 'text' => 'Verify Transfers', 'route' => 'transfers?status=Pending+Verification', 'color' => 'info'],
                                ['icon' => 'bi-box', 'text' => 'Project Inventory', 'route' => 'assets', 'color' => 'secondary'],
                            ];
                            break;
                            
                        case 'Site Inventory Clerk':
                            $quickActions = [
                                ['icon' => 'bi-plus-circle', 'text' => 'Create Request', 'route' => 'requests/create', 'color' => 'primary'],
                                ['icon' => 'bi-exclamation-circle', 'text' => 'Report Incident', 'route' => 'incidents/create', 'color' => 'danger'],
                                ['icon' => 'bi-arrow-repeat', 'text' => 'Initiate Transfer', 'route' => 'transfers/create', 'color' => 'info'],
                                ['icon' => 'bi-clipboard-check', 'text' => 'Verify Delivery', 'route' => 'procurement-orders/for-receipt', 'color' => 'success'],
                            ];
                            break;
                    }
                    
                    // Add common actions
                    $quickActions[] = ['icon' => 'bi-arrow-clockwise', 'text' => 'Refresh', 'onclick' => 'refreshDashboard()', 'color' => 'secondary'];
                    
                    foreach ($quickActions as $action):
                        if (isset($action['onclick'])):
                    ?>
                        <button type="button" class="btn btn-outline-<?= $action['color'] ?> btn-sm" onclick="<?= $action['onclick'] ?>">
                            <i class="<?= $action['icon'] ?>"></i> <?= $action['text'] ?>
                        </button>
                    <?php else: ?>
                        <a href="?route=<?= $action['route'] ?>" class="btn btn-outline-<?= $action['color'] ?> btn-sm">
                            <i class="<?= $action['icon'] ?>"></i> <?= $action['text'] ?>
                        </a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Statistics Cards (Common for all roles) -->
<div class="row mb-4">
    <!-- Total Inventory -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-muted mb-1 small">Total Inventory</p>
                        <h3 class="mb-0"><?= number_format($dashboardData['total_assets'] ?? 0) ?></h3>
                    </div>
                    <div class="text-muted" style="opacity: 0.3;">
                        <i class="bi bi-box fs-1"></i>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="bi bi-cash-stack me-1"></i>
                    Value: <?= formatCurrency($dashboardData['total_asset_value'] ?? 0) ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Available Stock -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-muted mb-1 small">Available</p>
                        <h3 class="mb-0 text-success"><?= number_format($dashboardData['available_assets'] ?? 0) ?></h3>
                    </div>
                    <div class="text-success" style="opacity: 0.3;">
                        <i class="bi bi-check-circle fs-1"></i>
                    </div>
                </div>
                <small class="text-muted">
                    <?php
                    $total = $dashboardData['total_assets'] ?? 1;
                    $available = $dashboardData['available_assets'] ?? 0;
                    $percentage = $total > 0 ? round(($available / $total) * 100, 1) : 0;
                    ?>
                    <i class="bi bi-graph-up me-1"></i>
                    <?= $percentage ?>% of total
                </small>
            </div>
        </div>
    </div>

    <!-- In Use Assets -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-muted mb-1 small">In Use</p>
                        <h3 class="mb-0 text-warning"><?= number_format($dashboardData['in_use_assets'] ?? 0) ?></h3>
                    </div>
                    <div class="text-warning" style="opacity: 0.3;">
                        <i class="bi bi-gear fs-1"></i>
                    </div>
                </div>
                <small class="text-muted">
                    <?php
                    $inUse = $dashboardData['in_use_assets'] ?? 0;
                    $percentage = $total > 0 ? round(($inUse / $total) * 100, 1) : 0;
                    ?>
                    <i class="bi bi-graph-up me-1"></i>
                    <?= $percentage ?>% of total
                </small>
            </div>
        </div>
    </div>

    <!-- Role-specific fourth card -->
    <?php 
    $fourthCard = [];
    switch ($userRole) {
        case 'Finance Director':
            $pendingApprovals = ($dashboardData['role_specific']['finance']['pending_high_value_requests'] ?? 0) +
                               ($dashboardData['role_specific']['finance']['pending_high_value_procurement'] ?? 0) +
                               ($dashboardData['role_specific']['finance']['pending_transfers'] ?? 0);
            $fourthCard = [
                'border' => 'danger',
                'icon' => 'bi-hourglass-split',
                'color' => 'danger',
                'value' => $pendingApprovals,
                'title' => 'Pending Approvals',
                'subtitle' => 'Requires your attention'
            ];
            break;
            
        case 'Asset Director':
            $fourthCard = [
                'border' => 'info',
                'icon' => 'bi-percent',
                'color' => 'info',
                'value' => $dashboardData['role_specific']['asset_director']['utilization_rate'] ?? 0 . '%',
                'title' => 'Asset Utilization',
                'subtitle' => 'Current usage rate'
            ];
            break;
            
        case 'Procurement Officer':
            $fourthCard = [
                'border' => 'info',
                'icon' => 'bi-clipboard-check',
                'color' => 'info',
                'value' => $dashboardData['role_specific']['procurement']['approved_requests_pending_po'] ?? 0,
                'title' => 'Pending POs',
                'subtitle' => 'Approved requests'
            ];
            break;
            
        case 'Warehouseman':
            $fourthCard = [
                'border' => 'danger',
                'icon' => 'bi-exclamation-triangle',
                'color' => 'danger',
                'value' => $dashboardData['role_specific']['warehouse']['low_stock_items'] ?? 0,
                'title' => 'Low Stock Alerts',
                'subtitle' => 'Items < 10 units'
            ];
            break;
            
        case 'Project Manager':
            $fourthCard = [
                'border' => 'primary',
                'icon' => 'bi-building',
                'color' => 'primary',
                'value' => $dashboardData['role_specific']['project_manager']['managed_projects'] ?? 0,
                'title' => 'Active Projects',
                'subtitle' => 'Under management'
            ];
            break;
            
        case 'Site Inventory Clerk':
            $fourthCard = [
                'border' => 'warning',
                'icon' => 'bi-arrow-repeat',
                'color' => 'warning',
                'value' => $dashboardData['role_specific']['site_clerk']['tools_borrowed_today'] ?? 0,
                'title' => 'Tools Out Today',
                'subtitle' => 'Borrowed items'
            ];
            break;
            
        default:
            $fourthCard = [
                'border' => 'info',
                'icon' => 'bi-clock',
                'color' => 'info',
                'value' => $dashboardData['pending_withdrawals'] ?? 0,
                'title' => 'Pending Requests',
                'subtitle' => ($dashboardData['overdue_withdrawals'] ?? 0) > 0 ? 
                    '<span class="text-danger">' . $dashboardData['overdue_withdrawals'] . ' overdue</span>' : 
                    'No overdue items'
            ];
    }
    ?>
    
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100" style="border-left: 4px solid var(--<?= $fourthCard['color'] ?>-color);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-muted mb-1 small"><?= $fourthCard['title'] ?></p>
                        <h3 class="mb-0 text-<?= $fourthCard['color'] ?>"><?= is_numeric($fourthCard['value']) ? number_format($fourthCard['value']) : $fourthCard['value'] ?></h3>
                    </div>
                    <div class="text-<?= $fourthCard['color'] ?>" style="opacity: 0.3;">
                        <i class="<?= $fourthCard['icon'] ?> fs-1"></i>
                    </div>
                </div>
                <small class="text-muted">
                    <?= $fourthCard['subtitle'] ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Role-Specific Dashboard Sections -->
<?php
// Include role-specific dashboard components
$roleViewFile = APP_ROOT . '/views/dashboard/role_specific/' . strtolower(str_replace(' ', '_', $userRole)) . '.php';
if (file_exists($roleViewFile)) {
    include $roleViewFile;
} else {
    // Fallback to generic view
    include APP_ROOT . '/views/dashboard/role_specific/generic.php';
}
?>

<!-- Recent Activities (Common for all) -->
<div class="row">
    <div class="col-12">
        <div class="card">
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
                                            By: <?= htmlspecialchars($activity['user_name'] ?? 'System') ?>
                                            <?php if ($activity['table_name']): ?>
                                                | Module: <?= ucfirst(str_replace('_', ' ', $activity['table_name'])) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <small class="text-muted">
                                        <?= timeAgo($activity['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="?route=activity-logs" class="btn btn-sm btn-outline-primary">View All Activities</a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history display-4 text-muted mb-3"></i>
                        <p class="text-muted">No recent activities to display</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
                // In a real implementation, update the DOM with new activities
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
    .pending-action-item {
        transition: background-color 0.2s;
    }
    .pending-action-item:hover {
        background-color: rgba(0,0,0,0.05);
    }
`;
document.head.appendChild(style);

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});
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