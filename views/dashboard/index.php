<?php
// Load branding helper
require_once APP_ROOT . '/helpers/BrandingHelper.php';

// Set page variables for the layout
$pageTitle = BrandingHelper::getPageTitle('Dashboard');
$pageHeader = 'Dashboard';

// Get user data
$user = Auth::getInstance()->getCurrentUser();
$userRole = $dashboardData['user_role'] ?? 'Guest';
$userId = $dashboardData['user_id'] ?? null;

// Start content capture
ob_start();

// Load dashboard module CSS
require_once APP_ROOT . '/helpers/AssetHelper.php';
AssetHelper::loadModuleCSS('dashboard');

// Load role-specific CSS for Finance Director
if ($userRole === 'Finance Director') {
    AssetHelper::loadModuleCSS('dashboard-finance-director');
}
?>

<!-- Compact Welcome Banner (Neutral Design V2.0) -->
<?php include APP_ROOT . '/views/dashboard/components/welcome_banner.php'; ?>

<!-- Role-Specific Quick Actions -->
<?php
/**
 * Quick Actions Display Logic
 *
 * Only show generic quick actions for roles WITHOUT dedicated role-specific dashboards.
 * Roles with role-specific dashboards (warehouseman.php, etc.) have their own
 * contextual quick actions in the sidebar to avoid duplication and maintain
 * WCAG 3.2.4 Consistent Identification compliance.
 */
$rolesWithSpecificDashboards = [
    'Warehouseman',
    'Finance Director',
    'Asset Director',
    'Procurement Officer',
    'Project Manager',
    'Site Inventory Clerk',
    'System Admin'
];

// Only show generic quick actions if role doesn't have a specific dashboard
if (!in_array($userRole, $rolesWithSpecificDashboards)):
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-lightning-charge me-2"></i>Quick Actions
                </h6>
                <div class="btn-group flex-wrap" role="group">
                    <a href="?route=assets" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box"></i> View Assets
                    </a>
                    <a href="?route=requests/create" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-plus-circle"></i> Create Request
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="Dashboard.refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Statistics Cards (Neutral Design V2.0) -->
<?php
/**
 * Build stats array for neutral design system
 * Use critical flag ONLY for items requiring immediate attention
 */

// Determine if fourth stat is critical based on role
$fourthStatCritical = false;
$fourthStatValue = 0;
$fourthStatLabel = 'Pending Requests';
$fourthStatIcon = 'bi-clock';

switch ($userRole) {
    case 'Finance Director':
        $fourthStatValue = ($dashboardData['role_specific']['finance']['pending_high_value_requests'] ?? 0) +
                          ($dashboardData['role_specific']['finance']['pending_high_value_procurement'] ?? 0) +
                          ($dashboardData['role_specific']['finance']['pending_transfers'] ?? 0);
        $fourthStatLabel = 'Pending Approvals';
        $fourthStatIcon = 'bi-hourglass-split';
        $fourthStatCritical = $fourthStatValue > 0; // Critical if any pending
        break;

    case 'Warehouseman':
        $fourthStatValue = $dashboardData['role_specific']['warehouse']['low_stock_items'] ?? 0;
        $fourthStatLabel = 'Low Stock Alerts';
        $fourthStatIcon = 'bi-exclamation-triangle';
        $fourthStatCritical = $fourthStatValue > 0; // Critical if low stock
        break;

    case 'Asset Director':
        $fourthStatValue = ($dashboardData['role_specific']['asset_director']['utilization_rate'] ?? 0) . '%';
        $fourthStatLabel = 'Asset Utilization';
        $fourthStatIcon = 'bi-percent';
        break;

    case 'Procurement Officer':
        $fourthStatValue = $dashboardData['role_specific']['procurement']['approved_requests_pending_po'] ?? 0;
        $fourthStatLabel = 'Pending POs';
        $fourthStatIcon = 'bi-clipboard-check';
        break;

    case 'Project Manager':
        $fourthStatValue = $dashboardData['role_specific']['project_manager']['managed_projects'] ?? 0;
        $fourthStatLabel = 'Active Projects';
        $fourthStatIcon = 'bi-building';
        break;

    case 'Site Inventory Clerk':
        $fourthStatValue = $dashboardData['role_specific']['site_clerk']['tools_borrowed_today'] ?? 0;
        $fourthStatLabel = 'Tools Out Today';
        $fourthStatIcon = 'bi-arrow-repeat';
        break;

    default:
        $fourthStatValue = $dashboardData['pending_withdrawals'] ?? 0;
        $overdue = $dashboardData['overdue_withdrawals'] ?? 0;
        $fourthStatLabel = 'Pending Requests';
        $fourthStatIcon = 'bi-clock';
        $fourthStatCritical = $overdue > 0; // Critical if overdue
}

// Build stats array - ALL NEUTRAL except critical items
$stats = [
    [
        'icon' => 'bi-box',
        'count' => $dashboardData['total_assets'] ?? 0,
        'label' => 'Total Assets',
        'critical' => false // Always neutral
    ],
    [
        'icon' => 'bi-check-circle',
        'count' => $dashboardData['available_assets'] ?? 0,
        'label' => 'Available',
        'critical' => false // Always neutral
    ],
    [
        'icon' => 'bi-gear',
        'count' => $dashboardData['in_use_assets'] ?? 0,
        'label' => 'In Use',
        'critical' => false // Always neutral
    ],
    [
        'icon' => $fourthStatIcon,
        'count' => $fourthStatValue,
        'label' => $fourthStatLabel,
        'critical' => $fourthStatCritical // Critical ONLY if requires attention
    ]
];

// Use stat_cards component (DRY principle)
$columns = 4;
$useCard = false; // No wrapper card - cleaner layout
include APP_ROOT . '/views/dashboard/components/stat_cards.php';
?>

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
                <button class="btn btn-outline-secondary btn-sm" onclick="Dashboard.refreshActivities()">
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

<!-- Load Dashboard Module JavaScript -->
<script src="/assets/js/modules/dashboard.js"></script>

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