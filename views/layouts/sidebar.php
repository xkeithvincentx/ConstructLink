<?php
$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

// Load branding from database
$branding = BrandingHelper::loadBranding();

// Extract current route for active state comparison
$currentRoute = $_GET['route'] ?? '';

// Helper function to check if a route is active
function isRouteActive($currentRoute, $targetRoute) {
    if (empty($currentRoute)) {
        return false;
    }

    // Remove '?route=' prefix if present in target route
    $cleanTargetRoute = str_replace('?route=', '', $targetRoute);

    // PRIMARY: Exact match (highest priority)
    // This handles routes that match exactly, e.g., 'dashboard' === 'dashboard'
    if ($currentRoute === $cleanTargetRoute) {
        return true;
    }

    // SECONDARY: Hierarchical parent matching
    // If current route starts with the target route followed by a slash,
    // then the target is a parent and should be highlighted
    // Example: current = 'borrowed-tools/create-batch', target = 'borrowed-tools' → MATCH
    // Example: current = 'borrowed-tools', target = 'borrowed' → NO MATCH (prevents partial word matches)
    if (strpos($currentRoute, $cleanTargetRoute . '/') === 0) {
        return true;
    }

    // No match found
    return false;
}

// Get navigation menu based on user role
$navigationMenu = getNavigationMenu($userRole);
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-white sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= isRouteActive($currentRoute, 'dashboard') ? 'active' : '' ?>" 
                   href="?route=dashboard">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <!-- Inventory Section (Assets in navigation config) -->
            <?php if (isset($navigationMenu['Assets'])): ?>
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Inventory</span>
                </h6>
            </li>

            <?php if (is_array($navigationMenu['Assets'])): ?>
                <?php foreach ($navigationMenu['Assets'] as $label => $url): ?>
                <li class="nav-item">
                    <a class="nav-link <?= isRouteActive($currentRoute, $url) ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($url) ?>">
                        <?php
                        // Map old labels to new inventory terminology
                        $icons = [
                            'View Assets' => 'bi bi-box',
                            'View Inventory' => 'bi bi-box',
                            'Add Asset' => 'bi bi-plus-circle',
                            'Add Item' => 'bi bi-plus-circle',
                            'Asset Scanner' => 'bi bi-qr-code-scan',
                            'Inventory Scanner' => 'bi bi-qr-code-scan'
                        ];
                        $icon = $icons[$label] ?? 'bi bi-circle';

                        // Convert old terminology to new
                        $displayLabel = str_replace(['Asset', 'Assets'], ['Item', 'Inventory'], $label);
                        ?>
                        <i class="<?= $icon ?> me-2"></i>
                        <?= htmlspecialchars($displayLabel) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?= isRouteActive($currentRoute, $navigationMenu['Assets']) ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($navigationMenu['Assets']) ?>">
                        <i class="bi bi-box me-2"></i>
                        Inventory
                    </a>
                </li>
            <?php endif; ?>
            <?php endif; ?>
            
            <!-- Operations Section -->
            <?php if (isset($navigationMenu['Operations'])): ?>
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Operations</span>
                </h6>
            </li>
            
            <?php if (is_array($navigationMenu['Operations'])): ?>
                <?php foreach ($navigationMenu['Operations'] as $label => $url): ?>
            <li class="nav-item">
                <a class="nav-link <?= isRouteActive($currentRoute, $url) ? 'active' : '' ?>" 
                   href="<?= htmlspecialchars($url) ?>">
                    <?php
                    $icons = [
                        'Requests' => 'bi bi-clipboard-check',
                        'Withdrawals' => 'bi bi-arrow-down-circle',
                        'Transfers' => 'bi bi-arrow-left-right',
                        'Maintenance' => 'bi bi-wrench',
                        'Incidents' => 'bi bi-exclamation-triangle',
                        'Borrowed Tools' => 'bi bi-clock-history'
                    ];
                    $icon = $icons[$label] ?? 'bi bi-circle';
                    ?>
                    <i class="<?= $icon ?> me-2"></i>
                    <?= htmlspecialchars($label) ?>
                    
                    <!-- Show notification badges for pending items -->
                    <?php if ($label === 'Withdrawals' && hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])): ?>
                        <span class="badge bg-warning rounded-pill ms-auto" id="pending-withdrawals-count" style="display: none;"></span>
                    <?php endif; ?>
                    
                    <?php if ($label === 'Maintenance' && hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])): ?>
                        <span class="badge bg-danger rounded-pill ms-auto" id="overdue-maintenance-count" style="display: none;"></span>
                    <?php endif; ?>
                    
                    <?php if ($label === 'Incidents' && hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])): ?>
                        <span class="badge bg-info rounded-pill ms-auto" id="open-incidents-count" style="display: none;"></span>
                    <?php endif; ?>
                </a>
            </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?= isRouteActive($currentRoute, $navigationMenu['Operations']) ? 'active' : '' ?>" 
                       href="<?= htmlspecialchars($navigationMenu['Operations']) ?>">
                        <i class="bi bi-clipboard-check me-2"></i>
                        Operations
                    </a>
                </li>
            <?php endif; ?>
            <?php endif; ?>
            
            <!-- Procurement Section -->
            <?php if (isset($navigationMenu['Procurement'])): ?>
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Procurement</span>
                </h6>
            </li>
            
            <?php if (is_array($navigationMenu['Procurement'])): ?>
                <?php foreach ($navigationMenu['Procurement'] as $label => $url): ?>
                <li class="nav-item">
                    <a class="nav-link <?= isRouteActive($currentRoute, $url) ? 'active' : '' ?>" 
                       href="<?= htmlspecialchars($url) ?>">
                        <?php
                        $icons = [
                            'Orders Dashboard' => 'bi bi-kanban',
                            'Create Order' => 'bi bi-plus-circle',
                            'Delivery Management' => 'bi bi-truck',
                            'Performance Dashboard' => 'bi bi-graph-up',
                            'Legacy Procurement' => 'bi bi-cart',
                            'Multi-Item Orders' => 'bi bi-cart-plus',
                            'Procurement Orders' => 'bi bi-cart-plus'
                        ];
                        $icon = $icons[$label] ?? 'bi bi-cart';
                        ?>
                        <i class="<?= $icon ?> me-2"></i>
                        <?= htmlspecialchars($label) ?>
                        
                        <!-- Show notification badges for consolidated procurement operations -->
                        <?php if ($label === 'Orders Dashboard' && hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                            <span class="badge bg-warning rounded-pill ms-auto" id="orders-dashboard-count" style="display: none;"></span>
                        <?php elseif ($label === 'Delivery Management' && hasRole(['System Admin', 'Procurement Officer', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
                            <span class="badge bg-info rounded-pill ms-auto" id="delivery-management-count" style="display: none;"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?= isRouteActive($currentRoute, $navigationMenu['Procurement']) ? 'active' : '' ?>" 
                       href="<?= htmlspecialchars($navigationMenu['Procurement']) ?>">
                        <i class="bi bi-cart me-2"></i>
                        Procurement
                    </a>
                </li>
            <?php endif; ?>
            <?php endif; ?>
            
            <!-- Reports Section -->
            <?php if (isset($navigationMenu['Reports'])): ?>
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Reports</span>
                </h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isRouteActive($currentRoute, $navigationMenu['Reports']) ? 'active' : '' ?>" 
                   href="<?= htmlspecialchars($navigationMenu['Reports']) ?>">
                    <i class="bi bi-graph-up me-2"></i>
                    Reports
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Master Data Section -->
            <?php if (isset($navigationMenu['Master Data'])): ?>
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Master Data</span>
                </h6>
            </li>
            
            <?php foreach ($navigationMenu['Master Data'] as $label => $url): ?>
            <li class="nav-item">
                <a class="nav-link <?= isRouteActive($currentRoute, $url) ? 'active' : '' ?>" 
                   href="<?= htmlspecialchars($url) ?>">
                    <?php
                    $icons = [
                        'Users' => 'bi bi-people',
                        'Projects' => 'bi bi-building',
                        'Categories' => 'bi bi-tags',
                        'Equipment Management' => 'bi bi-gear-fill',
                        'Vendors' => 'bi bi-shop',
                        'Makers' => 'bi bi-gear',
                        'Clients' => 'bi bi-person-badge',
                        'Brands' => 'bi bi-award',
                        'Disciplines' => 'bi bi-diagram-3'
                    ];
                    $icon = $icons[$label] ?? 'bi bi-circle';
                    ?>
                    <i class="<?= $icon ?> me-2"></i>
                    <?= htmlspecialchars($label) ?>
                </a>
            </li>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Administration Section -->
            <?php if (isset($navigationMenu['Administration'])): ?>
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Administration</span>
                </h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isRouteActive($currentRoute, $navigationMenu['Administration']) ? 'active' : '' ?>" 
                   href="<?= htmlspecialchars($navigationMenu['Administration']) ?>">
                    <i class="bi bi-gear-fill me-2"></i>
                    System Admin
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <!-- Quick Stats Widget -->
        <div class="mt-4 p-3 bg-light rounded mx-3" x-data="sidebarStats">
            <h6 class="text-muted mb-3">Quick Stats</h6>
            
            <div class="d-flex justify-content-between mb-2">
                <small class="text-muted">Total Inventory</small>
                <small class="fw-bold" x-text="stats.total_assets || '-'" ></small>
            </div>
            
            <div class="d-flex justify-content-between mb-2">
                <small class="text-muted">Available</small>
                <small class="text-success fw-bold" x-text="stats.available || '-'" ></small>
            </div>
            
            <div class="d-flex justify-content-between mb-2">
                <small class="text-muted">In Use</small>
                <small class="text-primary fw-bold" x-text="stats.in_use || '-'" ></small>
            </div>
            
            <?php if (hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])): ?>
            <div class="d-flex justify-content-between">
                <small class="text-muted">Maintenance</small>
                <small class="text-warning fw-bold" x-text="stats.under_maintenance || '-'" ></small>
            </div>
            <?php endif; ?>
            
            <div class="mt-2 pt-2 border-top">
                <small class="text-muted">Last updated: <span x-text="lastUpdated"></span></small>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="mt-3 p-2 mx-3 text-center">
            <small class="text-muted">
                <?= htmlspecialchars($branding['app_name']) ?> v<?= APP_VERSION ?><br>
                by <?= htmlspecialchars($branding['company_name']) ?>
            </small>
        </div>
    </div>
</nav>

<!-- Badge notifications handled by sidebarStats Alpine component -->

<style>
.sidebar {
    position: fixed;
    top: 76px;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 0;
    border-right: 2px solid #dee2e6;
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
}

.sidebar .nav-link {
    color: #333;
    padding: 0.75rem 1rem;
    border-radius: 0;
}

.sidebar .nav-link:hover {
    background-color: #f8f9fa;
}

.sidebar .nav-link.active {
    background-color: #0d6efd;
    color: white;
}

.sidebar .nav-link.active:hover {
    background-color: #0b5ed7;
}

.sidebar-heading {
    font-size: .75rem;
    font-weight: 600;
}

@media (max-width: 767.98px) {
    .sidebar {
        position: static;
        height: auto;
    }
}
</style>

