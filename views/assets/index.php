<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-2">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if (in_array($userRole, $roleConfig['assets/create'] ?? [])): ?>
            <a href="?route=assets/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Add Asset</span>
                <span class="d-sm-none">Add</span>
            </a>
        <?php endif; ?>
        <?php if (in_array($userRole, $roleConfig['assets/legacy-create'] ?? [])): ?>
            <a href="?route=assets/legacy-create" class="btn btn-success btn-sm">
                <i class="bi bi-clock-history me-1"></i>
                <span class="d-none d-sm-inline">Add Legacy</span>
                <span class="d-sm-none">Legacy</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Secondary Actions (Right) -->
    <div class="btn-toolbar flex-wrap gap-2" role="toolbar" aria-label="Secondary actions">
        <!-- Workflow Dashboards -->
        <div class="btn-group btn-group-sm" role="group" aria-label="Workflow dashboards">
            <?php if (in_array($userRole, $roleConfig['assets/legacy-verify'] ?? [])): ?>
                <a href="?route=assets/verification-dashboard" class="btn btn-outline-warning">
                    <i class="bi bi-check-circle me-1"></i>
                    <span class="d-none d-lg-inline">Verification</span>
                    <span class="d-lg-none">Verify</span>
                </a>
            <?php endif; ?>
            <?php if (in_array($userRole, $roleConfig['assets/legacy-authorize'] ?? [])): ?>
                <a href="?route=assets/authorization-dashboard" class="btn btn-outline-info">
                    <i class="bi bi-shield-check me-1"></i>
                    <span class="d-none d-lg-inline">Authorization</span>
                    <span class="d-lg-none">Auth</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Tools -->
        <div class="btn-group btn-group-sm" role="group" aria-label="Tools">
            <?php if (in_array($userRole, $roleConfig['assets/scanner'] ?? [])): ?>
                <a href="?route=assets/scanner" class="btn btn-outline-secondary">
                    <i class="bi bi-qr-code-scan"></i>
                    <span class="d-none d-md-inline ms-1">Scanner</span>
                </a>
            <?php endif; ?>
            <?php if (in_array($userRole, ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director'])): ?>
                <a href="?route=assets/tag-management" class="btn btn-outline-secondary">
                    <i class="bi bi-tags"></i>
                    <span class="d-none d-md-inline ms-1">Tags</span>
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-secondary" onclick="refreshAssets()" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
                <span class="d-none d-md-inline ms-1">Refresh</span>
            </button>
        </div>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'asset_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Asset created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'asset_updated'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Asset updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'asset_deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Asset deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'export_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>Failed to export assets. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Role-Specific Asset Statistics Cards -->
<!-- Mobile: Collapsible, Desktop: Always visible -->
<div class="mb-4">
    <!-- Mobile Toggle Button -->
    <button class="btn btn-outline-secondary btn-sm w-100 d-md-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#statsCollapse" aria-expanded="false" aria-controls="statsCollapse">
        <i class="bi bi-bar-chart-line me-1"></i>
        <span>View Statistics</span>
        <i class="bi bi-chevron-down ms-auto"></i>
    </button>

    <!-- Collapsible on mobile, always visible on desktop -->
    <div class="collapse d-md-block" id="statsCollapse">

    <!-- Project Manager Dashboard Cards -->
    <?php if (in_array($userRole, ['Project Manager'])): ?>
    <div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-box-seam text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Project Assets</h6>
                        <h3 class="mb-0"><?= $roleStats['total_project_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-building me-1"></i>Assets under management
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View All Assets
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Available Assets</h6>
                        <h3 class="mb-0"><?= $roleStats['available_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-check-circle me-1"></i>Ready for deployment
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=available" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View Available
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-graph-up text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Utilization Rate</h6>
                        <h3 class="mb-0"><?= $roleStats['utilization_rate'] ?? 0 ?>%</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-graph-up me-1"></i><?= $roleStats['assets_in_use'] ?? 0 ?> assets in use
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=in_use" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View In Use
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid <?= ($roleStats['low_stock_alerts'] ?? 0) > 0 ? 'var(--warning-color)' : 'var(--neutral-color)' ?>;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-exclamation-triangle <?= ($roleStats['low_stock_alerts'] ?? 0) > 0 ? 'text-warning' : 'text-secondary' ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Stock Alerts</h6>
                        <h3 class="mb-0"><?= $roleStats['low_stock_alerts'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>Items need attention
                    <?php if (($roleStats['maintenance_pending'] ?? 0) > 0): ?>
                        <br><i class="bi bi-wrench me-1"></i><?= $roleStats['maintenance_pending'] ?> maintenance due
                    <?php endif; ?>
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <?php if (($roleStats['low_stock_alerts'] ?? 0) > 0): ?>
                    <a href="?route=assets&asset_type=low_stock" class="text-decoration-none small">
                        <i class="bi bi-eye me-1"></i>Review Alerts
                    </a>
                <?php else: ?>
                    <small class="text-muted">All levels optimal</small>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>
</div><!-- End row -->

    <?php elseif (in_array($userRole, ['Site Inventory Clerk'])): ?>
    <!-- Site Inventory Clerk Dashboard Cards -->
    <div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-box2 text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Inventory Items</h6>
                        <h3 class="mb-0"><?= $roleStats['total_inventory_items'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-box2 me-1"></i><?= number_format($roleStats['total_consumable_units'] ?? 0) ?> consumable units
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View Inventory
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Available</h6>
                        <h3 class="mb-0"><?= $roleStats['available_for_use'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-check-circle me-1"></i>Ready for issue
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=available" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View Available
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-activity text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Today's Activity</h6>
                        <h3 class="mb-0"><?= $roleStats['today_activities'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-calendar-day me-1"></i>Updates today
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View Activity
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid <?= ($roleStats['out_of_stock_items'] ?? 0) > 0 ? 'var(--danger-color)' : (($roleStats['low_stock_items'] ?? 0) > 0 ? 'var(--warning-color)' : 'var(--neutral-color)') ?>;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-exclamation-triangle <?= ($roleStats['out_of_stock_items'] ?? 0) > 0 ? 'text-danger' : (($roleStats['low_stock_items'] ?? 0) > 0 ? 'text-warning' : 'text-secondary') ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Stock Issues</h6>
                        <h3 class="mb-0"><?= ($roleStats['out_of_stock_items'] ?? 0) + ($roleStats['low_stock_items'] ?? 0) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= $roleStats['out_of_stock_items'] ?? 0 ?> out, <?= $roleStats['low_stock_items'] ?? 0 ?> low
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <?php if (($roleStats['out_of_stock_items'] ?? 0) > 0 || ($roleStats['low_stock_items'] ?? 0) > 0): ?>
                    <a href="?route=assets&asset_type=low_stock" class="text-decoration-none small">
                        <i class="bi bi-eye me-1"></i>Review Stock
                    </a>
                <?php else: ?>
                    <small class="text-muted">Stock levels good</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div><!-- End row -->

    <?php elseif (in_array($userRole, ['Warehouseman'])): ?>
    <!-- Warehouseman Dashboard Cards -->
    <div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-building text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Warehouse Inventory</h6>
                        <h3 class="mb-0"><?= $roleStats['warehouse_inventory'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-building me-1"></i>Total items under your care
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View All Items
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Available Stock</h6>
                        <h3 class="mb-0"><?= $roleStats['available_stock'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-check-circle me-1"></i>Ready for issue
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=available" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View Available
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-box-arrow-right text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Items Borrowed</h6>
                        <h3 class="mb-0"><?= $roleStats['tools_on_loan'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-arrow-left-right me-1"></i><?= $roleStats['items_in_transit'] ?? 0 ?> in transit
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=in_use" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View In Use
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid <?= ($roleStats['reorder_alerts'] ?? 0) > 0 ? 'var(--warning-color)' : 'var(--neutral-color)' ?>;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-exclamation-triangle <?= ($roleStats['reorder_alerts'] ?? 0) > 0 ? 'text-warning' : 'text-secondary' ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Low Stock Alerts</h6>
                        <h3 class="mb-0"><?= $roleStats['reorder_alerts'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= $roleStats['pending_verification'] ?? 0 ?> pending verification
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <?php if (($roleStats['reorder_alerts'] ?? 0) > 0): ?>
                    <a href="?route=assets&asset_type=low_stock" class="text-decoration-none small">
                        <i class="bi bi-eye me-1"></i>Review Alerts
                    </a>
                <?php else: ?>
                    <span class="text-muted small">
                        <i class="bi bi-check-circle me-1"></i>All stock levels good
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div><!-- End row -->

    <?php elseif (in_array($userRole, ['System Admin'])): ?>
    <!-- System Admin Dashboard Cards -->
    <div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-globe text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">System Assets</h6>
                        <h3 class="mb-0"><?= $roleStats['total_system_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-globe me-1"></i><?= $roleStats['active_projects'] ?? 0 ?> active projects
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View All Assets
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-currency-dollar text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Value</h6>
                        <h3 class="mb-0"><?= formatCurrency($roleStats['total_asset_value'] ?? 0) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-currency-dollar me-1"></i>Avg: <?= formatCurrency($roleStats['avg_asset_value'] ?? 0) ?>
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-graph-up me-1"></i>View Analytics
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-plus-circle text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Added This Week</h6>
                        <h3 class="mb-0"><?= $roleStats['assets_added_week'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-calendar-week me-1"></i>New acquisitions
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View Recent
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-trash text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Disposed Assets</h6>
                        <h3 class="mb-0"><?= $roleStats['disposed_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-trash me-1"></i>End of lifecycle
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=disposed" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View Disposed
                </a>
            </div>
        </div>
    </div>
</div><!-- End row -->

    <?php elseif (in_array($userRole, ['Finance Director'])): ?>
    <!-- Finance Director Dashboard Cards -->
    <div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-currency-dollar text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Asset Investment</h6>
                        <h3 class="mb-0"><?= formatCurrency($roleStats['total_asset_investment'] ?? 0) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-building me-1"></i><?= $roleStats['projects_with_assets'] ?? 0 ?> projects
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View Portfolio
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-gem text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">High Value Assets</h6>
                        <h3 class="mb-0"><?= $roleStats['high_value_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-gem me-1"></i>Above $10,000
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View High Value
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-graph-up text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Monthly Spend</h6>
                        <h3 class="mb-0"><?= formatCurrency($roleStats['monthly_acquisitions'] ?? 0) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-calendar-month me-1"></i>This month's acquisitions
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-calendar me-1"></i>View Recent
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-calculator text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Avg. Cost</h6>
                        <h3 class="mb-0"><?= formatCurrency($roleStats['avg_acquisition_cost'] ?? 0) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-calculator me-1"></i><?= $roleStats['client_supplied_assets'] ?? 0 ?> client-supplied
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-graph-up me-1"></i>View Analytics
                </a>
            </div>
        </div>
    </div>
</div><!-- End row -->

    <?php elseif (in_array($userRole, ['Asset Director'])): ?>
    <!-- Asset Director Dashboard Cards -->
    <div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-shield-check text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Managed Assets</h6>
                        <h3 class="mb-0"><?= $roleStats['total_managed_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-shield-check me-1"></i><?= $roleStats['projects_managed'] ?? 0 ?> projects
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View All Assets
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-speedometer2 text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Utilization Rate</h6>
                        <h3 class="mb-0"><?= $roleStats['overall_utilization'] ?? 0 ?>%</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-speedometer2 me-1"></i>Asset efficiency
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=in_use" class="text-decoration-none small">
                    <i class="bi bi-graph-up me-1"></i>View Utilization
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-wrench text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Maintenance Due</h6>
                        <h3 class="mb-0"><?= $roleStats['maintenance_required'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-wrench me-1"></i>Require attention
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=under_maintenance" class="text-decoration-none small">
                    <i class="bi bi-tools me-1"></i>Schedule Maintenance
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid <?= ($roleStats['inventory_alerts'] ?? 0) > 0 ? 'var(--danger-color)' : 'var(--neutral-color)' ?>;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-exclamation-triangle <?= ($roleStats['inventory_alerts'] ?? 0) > 0 ? 'text-danger' : 'text-secondary' ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Inventory Alerts</h6>
                        <h3 class="mb-0"><?= $roleStats['inventory_alerts'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= $roleStats['retired_assets'] ?? 0 ?> retired
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <?php if (($roleStats['inventory_alerts'] ?? 0) > 0): ?>
                    <a href="?route=assets&asset_type=low_stock" class="text-decoration-none small">
                        <i class="bi bi-eye me-1"></i>Review Alerts
                    </a>
                <?php else: ?>
                    <small class="text-muted">All inventory optimal</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div><!-- End row -->

    <?php else: ?>
    <!-- Default Asset Status Statistics Cards (for Procurement Officer and other roles) -->
    <div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Assets</h6>
                        <h3 class="mb-0"><?= $assetStats['total_assets'] ?? 0 ?></h3>
                        <?php if (isset($assetStats['total_quantity']) && $assetStats['total_quantity'] > $assetStats['total_assets']): ?>
                            <small class="opacity-75">
                                <i class="bi bi-stack me-1"></i><?= number_format($assetStats['total_quantity']) ?> total units
                            </small>
                        <?php endif; ?>
                        <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                            <div class="mt-1">
                                <small class="opacity-75">
                                    <i class="bi bi-currency-dollar me-1"></i><?= formatCurrency($assetStats['total_value'] ?? 0) ?> value
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-box-seam display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary-dark">
                <a href="?route=assets" class="text-white text-decoration-none">
                    <small><i class="bi bi-eye me-1"></i>View All Assets</small>
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Available</h6>
                        <h3 class="mb-0"><?= $assetStats['available'] ?? 0 ?></h3>
                        <?php if (isset($assetStats['available_quantity']) && $assetStats['available_quantity'] > $assetStats['available']): ?>
                            <small class="opacity-75">
                                <i class="bi bi-check-circle me-1"></i><?= number_format($assetStats['available_quantity']) ?> units ready
                            </small>
                        <?php endif; ?>
                        <div class="mt-1">
                            <small class="opacity-75">
                                <i class="bi bi-percent me-1"></i><?= isset($assetStats['total_assets']) && $assetStats['total_assets'] > 0 ? round(($assetStats['available'] / $assetStats['total_assets']) * 100, 1) : 0 ?>% of total
                            </small>
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-success-dark">
                <a href="?route=assets&status=available" class="text-white text-decoration-none">
                    <small><i class="bi bi-filter me-1"></i>View Available</small>
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">In Use</h6>
                        <h3 class="mb-0"><?= $assetStats['in_use'] ?? 0 ?></h3>
                        <?php if (isset($assetStats['consumable_in_use']) && $assetStats['consumable_in_use'] > 0): ?>
                            <small class="opacity-75">
                                <i class="bi bi-arrow-down-circle me-1"></i><?= number_format($assetStats['consumable_in_use']) ?> consumable units
                            </small>
                        <?php endif; ?>
                        <div class="mt-1">
                            <small class="opacity-75">
                                <i class="bi bi-graph-up me-1"></i><?= $assetStats['utilization_rate'] ?? 0 ?>% utilization
                            </small>
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-gear display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-info-dark">
                <a href="?route=assets&status=in_use" class="text-white text-decoration-none">
                    <small><i class="bi bi-filter me-1"></i>View In Use</small>
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card <?= ($assetStats['low_stock_count'] ?? 0) > 0 ? 'bg-warning' : 'bg-secondary' ?> text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Low Stock Alert</h6>
                        <h3 class="mb-0"><?= $assetStats['low_stock_count'] ?? 0 ?></h3>
                        <small class="opacity-75">
                            <i class="bi bi-exclamation-triangle me-1"></i>Consumables running low
                        </small>
                        <?php if (($assetStats['out_of_stock_count'] ?? 0) > 0): ?>
                            <div class="mt-1">
                                <small class="opacity-75 text-danger">
                                    <i class="bi bi-x-circle me-1"></i><?= $assetStats['out_of_stock_count'] ?> out of stock
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle display-6"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-warning-dark">
                <?php if (($assetStats['low_stock_count'] ?? 0) > 0): ?>
                    <a href="?route=assets&asset_type=low_stock" class="text-white text-decoration-none">
                        <small><i class="bi bi-eye me-1"></i>Review Stock Levels</small>
                    </a>
                <?php else: ?>
                    <small class="text-white-50">All stock levels optimal</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div><!-- End row -->

    <?php endif; ?>
    </div><!-- End collapse -->
</div><!-- End statistics section -->

<!-- MVA Workflow Statistics Cards - Only for roles that manage approvals -->
<?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h6 class="card-title text-warning">
                    <i class="bi bi-clock-history me-2"></i>Pending Verification
                </h6>
                <h4 class="text-warning mb-0"><?= $workflowStats['pending_verification'] ?? 0 ?></h4>
                <small class="text-muted">Assets awaiting Asset Director review</small>
                <?php if (in_array($userRole, ['Asset Director', 'System Admin']) && ($workflowStats['pending_verification'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=assets&workflow_status=pending_verification" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-search me-1"></i>Review Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h6 class="card-title text-info">
                    <i class="bi bi-person-check me-2"></i>Pending Authorization
                </h6>
                <h4 class="text-info mb-0"><?= $workflowStats['pending_authorization'] ?? 0 ?></h4>
                <small class="text-muted">Assets awaiting Finance Director approval</small>
                <?php if (in_array($userRole, ['Finance Director', 'System Admin']) && ($workflowStats['pending_authorization'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=assets&workflow_status=pending_authorization" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-check-circle me-1"></i>Approve Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h6 class="card-title text-success">
                    <i class="bi bi-check-circle-fill me-2"></i>Approved Assets
                </h6>
                <h4 class="text-success mb-0"><?= $workflowStats['approved'] ?? 0 ?></h4>
                <small class="text-muted">Assets ready for deployment</small>
                <?php if (($workflowStats['approved'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <small class="text-success">
                            <i class="bi bi-speedometer2 me-1"></i>
                            <?= $workflowStats['avg_approval_time_hours'] ?? 0 ?>h avg. approval time
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h6 class="card-title text-danger">
                    <i class="bi bi-x-circle me-2"></i>Rejected Assets
                </h6>
                <h4 class="text-danger mb-0"><?= $workflowStats['rejected'] ?? 0 ?></h4>
                <small class="text-muted">Assets requiring attention</small>
                <?php if (($workflowStats['rejected'] ?? 0) > 0): ?>
                    <div class="mt-2">
                        <a href="?route=assets&workflow_status=rejected" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-eye me-1"></i>Review Issues
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Maintenance Alerts -->
<?php if (!empty($assetsDueForMaintenance)): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Assets Due for Maintenance
        </h6>
        <p class="mb-2">There are <?= count($assetsDueForMaintenance) ?> asset(s) that require maintenance attention:</p>
        <ul class="mb-0">
            <?php foreach (array_slice($assetsDueForMaintenance, 0, 3) as $asset): ?>
                <li>
                    <strong><?= htmlspecialchars($asset['name']) ?></strong> 
                    (<?= htmlspecialchars($asset['ref']) ?>) - 
                    <?= $asset['days_until_due'] > 0 ? $asset['days_until_due'] . ' days until due' : 'Overdue' ?>
                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="ms-2">View Details</a>
                </li>
            <?php endforeach; ?>
            <?php if (count($assetsDueForMaintenance) > 3): ?>
                <li><em>... and <?= count($assetsDueForMaintenance) - 3 ?> more</em></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Stock Level Alerts -->
<?php if (isset($assetStats['out_of_stock_count']) && $assetStats['out_of_stock_count'] > 0): ?>
    <div class="alert alert-danger" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-circle me-2"></i>Out of Stock Alert
        </h6>
        <p class="mb-2">
            <strong><?= $assetStats['out_of_stock_count'] ?></strong> consumable asset(s) are completely out of stock and need immediate replenishment.
        </p>
        <a href="?route=assets&asset_type=out_of_stock" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-eye me-1"></i>View Out of Stock Items
        </a>
    </div>
<?php endif; ?>

<?php if (isset($assetStats['low_stock_count']) && $assetStats['low_stock_count'] > 0): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Low Stock Warning
        </h6>
        <p class="mb-2">
            <strong><?= $assetStats['low_stock_count'] ?></strong> consumable asset(s) are running low on stock (below 20% of total quantity).
        </p>
        <div class="d-flex gap-2">
            <a href="?route=assets&asset_type=low_stock" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-eye me-1"></i>View Low Stock Items
            </a>
            <?php if (in_array($userRole, $roleConfig['procurement-orders/create'] ?? [])): ?>
                <a href="?route=procurement-orders/create" class="btn btn-sm btn-warning">
                    <i class="bi bi-plus-circle me-1"></i>Create Procurement Order
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Filters -->
<!-- Mobile: Offcanvas, Desktop: Card -->
<div class="mb-4">
    <!-- Mobile Filter Button (Sticky) -->
    <div class="d-md-none position-sticky top-0 z-3 bg-body py-2 mb-3" style="z-index: 1020;">
        <button class="btn btn-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
            <i class="bi bi-funnel me-1"></i>
            Filters
            <?php
            $activeFilters = 0;
            if (!empty($_GET['status'])) $activeFilters++;
            if (!empty($_GET['category_id'])) $activeFilters++;
            if (!empty($_GET['project_id'])) $activeFilters++;
            if (!empty($_GET['maker_id'])) $activeFilters++;
            if (!empty($_GET['asset_type'])) $activeFilters++;
            if (!empty($_GET['workflow_status'])) $activeFilters++;
            if (!empty($_GET['search'])) $activeFilters++;
            if ($activeFilters > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $activeFilters ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Desktop: Card (always visible) -->
    <div class="card d-none d-md-block">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Filters
            </h6>
        </div>
        <div class="card-body p-3">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="route" value="assets">
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="available" <?= ($_GET['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="in_use" <?= ($_GET['status'] ?? '') === 'in_use' ? 'selected' : '' ?>>In Use</option>
                    <option value="borrowed" <?= ($_GET['status'] ?? '') === 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
                    <option value="in_transit" <?= ($_GET['status'] ?? '') === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
                    <option value="under_maintenance" <?= ($_GET['status'] ?? '') === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                    <option value="retired" <?= ($_GET['status'] ?? '') === 'retired' ? 'selected' : '' ?>>Retired</option>
                    <option value="disposed" <?= ($_GET['status'] ?? '') === 'disposed' ? 'selected' : '' ?>>Disposed</option>
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select form-select-sm" id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    <?php if (isset($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= ($_GET['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-select form-select-sm" id="project_id" name="project_id">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="maker_id" class="form-label">Manufacturer</label>
                <select class="form-select form-select-sm" id="maker_id" name="maker_id">
                    <option value="">All Manufacturers</option>
                    <?php if (isset($makers) && is_array($makers)): ?>
                        <?php foreach ($makers as $maker): ?>
                            <option value="<?= $maker['id'] ?>" 
                                    <?= ($_GET['maker_id'] ?? '') == $maker['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($maker['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="asset_type" class="form-label">Asset Type</label>
                <select class="form-select form-select-sm" id="asset_type" name="asset_type">
                    <option value="">All Types</option>
                    <option value="consumable" <?= ($_GET['asset_type'] ?? '') === 'consumable' ? 'selected' : '' ?>>Consumable</option>
                    <option value="non_consumable" <?= ($_GET['asset_type'] ?? '') === 'non_consumable' ? 'selected' : '' ?>>Non-Consumable</option>
                    <option value="low_stock" <?= ($_GET['asset_type'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out_of_stock" <?= ($_GET['asset_type'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>
            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="workflow_status" class="form-label">Workflow Status</label>
                <select class="form-select form-select-sm" id="workflow_status" name="workflow_status">
                    <option value="">All Workflow Status</option>
                    <option value="draft" <?= ($_GET['workflow_status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="pending_verification" <?= ($_GET['workflow_status'] ?? '') === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="pending_authorization" <?= ($_GET['workflow_status'] ?? '') === 'pending_authorization' ? 'selected' : '' ?>>Pending Authorization</option>
                    <option value="approved" <?= ($_GET['workflow_status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= ($_GET['workflow_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-xl-3 col-lg-6 col-md-8 col-12">
                <label for="search" class="form-label">Enhanced Search</label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" id="search" name="search" 
                           placeholder="Search by asset name, reference, serial number, or disciplines..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           data-enhanced-search="true"
                           autocomplete="off"
                           list="search-suggestions">
                    <span class="input-group-text" id="search-status">
                        <i class="bi bi-search text-muted" id="search-icon"></i>
                    </span>
                </div>
                <datalist id="search-suggestions"></datalist>
                <div id="search-feedback" class="form-text"></div>
            </div>
            <div class="col-xl-1 col-lg-3 col-md-4 col-12 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-search me-1 d-none d-sm-inline"></i>
                    <span class="d-none d-sm-inline">Filter</span>
                    <i class="bi bi-search d-sm-none"></i>
                </button>
                <a href="?route=assets" class="btn btn-outline-secondary btn-sm flex-fill">
                    <i class="bi bi-x-circle me-1 d-none d-sm-inline"></i>
                    <span class="d-none d-sm-inline">Clear</span>
                    <i class="bi bi-x-circle d-sm-none"></i>
                </a>
            </div>
        </form>
        </div><!-- End card-body -->
    </div><!-- End card (desktop) -->

    <!-- Mobile: Offcanvas Filters -->
    <div class="offcanvas offcanvas-bottom d-md-none" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel" style="height: 85vh;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="filterOffcanvasLabel">
                <i class="bi bi-funnel me-2"></i>Filter Assets
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form method="GET" action="" id="mobileFilterForm">
                <input type="hidden" name="route" value="assets">
                <div class="mb-3">
                    <label for="mobile_status" class="form-label">Status</label>
                    <select class="form-select" id="mobile_status" name="status">
                        <option value="">All Statuses</option>
                        <option value="available" <?= ($_GET['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="in_use" <?= ($_GET['status'] ?? '') === 'in_use' ? 'selected' : '' ?>>In Use</option>
                        <option value="borrowed" <?= ($_GET['status'] ?? '') === 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
                        <option value="in_transit" <?= ($_GET['status'] ?? '') === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
                        <option value="under_maintenance" <?= ($_GET['status'] ?? '') === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                        <option value="retired" <?= ($_GET['status'] ?? '') === 'retired' ? 'selected' : '' ?>>Retired</option>
                        <option value="disposed" <?= ($_GET['status'] ?? '') === 'disposed' ? 'selected' : '' ?>>Disposed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="mobile_category_id" class="form-label">Category</label>
                    <select class="form-select" id="mobile_category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php if (isset($categories) && is_array($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"
                                        <?= ($_GET['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name'] ?? 'Unknown') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                <div class="mb-3">
                    <label for="mobile_project_id" class="form-label">Project</label>
                    <select class="form-select" id="mobile_project_id" name="project_id">
                        <option value="">All Projects</option>
                        <?php if (isset($projects) && is_array($projects)): ?>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"
                                        <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['name'] ?? 'Unknown') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="mobile_maker_id" class="form-label">Manufacturer</label>
                    <select class="form-select" id="mobile_maker_id" name="maker_id">
                        <option value="">All Manufacturers</option>
                        <?php if (isset($makers) && is_array($makers)): ?>
                            <?php foreach ($makers as $maker): ?>
                                <option value="<?= $maker['id'] ?>"
                                        <?= ($_GET['maker_id'] ?? '') == $maker['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($maker['name'] ?? 'Unknown') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="mobile_asset_type" class="form-label">Asset Type</label>
                    <select class="form-select" id="mobile_asset_type" name="asset_type">
                        <option value="">All Types</option>
                        <option value="consumable" <?= ($_GET['asset_type'] ?? '') === 'consumable' ? 'selected' : '' ?>>Consumable</option>
                        <option value="non_consumable" <?= ($_GET['asset_type'] ?? '') === 'non_consumable' ? 'selected' : '' ?>>Non-Consumable</option>
                        <option value="low_stock" <?= ($_GET['asset_type'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                        <option value="out_of_stock" <?= ($_GET['asset_type'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                <div class="mb-3">
                    <label for="mobile_workflow_status" class="form-label">Workflow Status</label>
                    <select class="form-select" id="mobile_workflow_status" name="workflow_status">
                        <option value="">All Workflow Status</option>
                        <option value="draft" <?= ($_GET['workflow_status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="pending_verification" <?= ($_GET['workflow_status'] ?? '') === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                        <option value="pending_authorization" <?= ($_GET['workflow_status'] ?? '') === 'pending_authorization' ? 'selected' : '' ?>>Pending Authorization</option>
                        <option value="approved" <?= ($_GET['workflow_status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= ($_GET['workflow_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="mobile_search" class="form-label">Enhanced Search</label>
                    <input type="text" class="form-control" id="mobile_search" name="search"
                           placeholder="Search by asset name, reference, serial number, or disciplines..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i>Apply Filters
                    </button>
                    <a href="?route=assets" class="btn btn-outline-secondary flex-grow-1">
                        <i class="bi bi-x-circle me-1"></i>Clear All
                    </a>
                </div>
            </form>
        </div>
    </div>
</div><!-- End filters section -->

<!-- Assets Table -->
<div class="card">
    <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <h6 class="card-title mb-0">Assets</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php if (in_array($userRole, $roleConfig['assets/export'] ?? [])): ?>
                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>
                    <span class="d-none d-md-inline">Export</span>
                </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>
                <span class="d-none d-md-inline">Print</span>
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($assets)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No assets found</h5>
                <p class="text-muted">Try adjusting your filters or add your first asset to the system.</p>
                <?php if (in_array($userRole, $roleConfig['assets/create'] ?? [])): ?>
                    <a href="?route=assets/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add First Asset
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Mobile Card View (visible on small screens) -->
            <div class="d-md-none">
                <?php foreach ($assets as $asset): ?>
                    <?php
                    // Get asset data for mobile view
                    $status = $asset['status'] ?? 'available';
                    $statusClasses = [
                        'available' => 'bg-success',
                        'in_use' => 'bg-primary',
                        'borrowed' => 'bg-info',
                        'in_transit' => 'bg-warning',
                        'under_maintenance' => 'bg-secondary',
                        'retired' => 'bg-dark',
                        'disposed' => 'bg-danger'
                    ];
                    $statusClass = $statusClasses[$status] ?? 'bg-secondary';
                    $quantity = (int)($asset['quantity'] ?? 1);
                    $availableQuantity = (int)($asset['available_quantity'] ?? 1);
                    $isConsumable = isset($asset['is_consumable']) && $asset['is_consumable'] == 1;
                    $workflowStatus = $asset['workflow_status'] ?? 'approved';
                    $assetSource = $asset['asset_source'] ?? 'manual';
                    ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none fw-bold">
                                        <?= htmlspecialchars($asset['ref'] ?? 'N/A') ?>
                                    </a>
                                    <?php if (!empty($asset['qr_code'])): ?>
                                        <i class="bi bi-qr-code text-primary ms-1" title="QR Code Available"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= $statusClass ?>"><?= ucfirst($status) ?></span>
                            </div>

                            <!-- Asset Name -->
                            <div class="mb-2">
                                <div class="fw-medium"><?= htmlspecialchars($asset['name'] ?? 'Unknown') ?></div>
                                <?php if (!empty($asset['serial_number'])): ?>
                                    <small class="text-muted">S/N: <?= htmlspecialchars($asset['serial_number']) ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- Category and Location/Project -->
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">Category</small>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></span>
                                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                    <small class="text-muted d-block mt-2 mb-1">Project</small>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?></span>
                                <?php else: ?>
                                    <small class="text-muted d-block mt-2 mb-1">Location</small>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($asset['location'] ?? 'Warehouse') ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Quantity -->
                            <div class="mb-2">
                                <small class="text-muted">Quantity: </small>
                                <strong><?= number_format($availableQuantity) ?> / <?= number_format($quantity) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars($asset['unit'] ?? 'pcs') ?></small>
                                <?php if ($isConsumable && $availableQuantity == 0): ?>
                                    <span class="badge bg-danger ms-1">Out of stock</span>
                                <?php elseif ($isConsumable && $availableQuantity <= ($quantity * 0.2)): ?>
                                    <span class="badge bg-warning text-dark ms-1">Low stock</span>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 flex-wrap mt-3">
                                <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="btn btn-sm btn-primary flex-grow-1">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>

                                <?php
                                // Show workflow-specific actions for legacy assets
                                if ($assetSource === 'legacy'):
                                    if ($workflowStatus === 'pending_verification' && in_array($userRole, $roleConfig['assets/legacy-verify'] ?? [])):
                                ?>
                                    <button type="button" class="btn btn-sm btn-warning flex-grow-1"
                                            onclick="openEnhancedVerification(<?= $asset['id'] ?>);">
                                        <i class="bi bi-shield-check me-1"></i>Verify
                                    </button>
                                <?php
                                    elseif ($workflowStatus === 'pending_authorization' && in_array($userRole, $roleConfig['assets/legacy-authorize'] ?? [])):
                                ?>
                                    <button type="button" class="btn btn-sm btn-info flex-grow-1"
                                            onclick="openEnhancedAuthorization(<?= $asset['id'] ?>);">
                                        <i class="bi bi-shield-check me-1"></i>Authorize
                                    </button>
                                <?php
                                    endif;
                                endif;
                                ?>

                                <?php if (in_array($userRole, $roleConfig['assets/edit'] ?? [])): ?>
                                    <a href="?route=assets/edit&id=<?= $asset['id'] ?>" class="btn btn-sm btn-outline-warning flex-grow-1">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                <?php endif; ?>

                                <?php if ($status === 'available' && in_array($userRole, $roleConfig['withdrawals/create'] ?? [])): ?>
                                    <a href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>" class="btn btn-sm btn-outline-success flex-grow-1">
                                        <i class="bi bi-box-arrow-right me-1"></i>Withdraw
                                    </a>
                                <?php endif; ?>

                                <?php if (in_array($userRole, $roleConfig['assets/delete'] ?? [])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger flex-grow-1"
                                            onclick="deleteAsset(<?= $asset['id'] ?>)">
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Table View (hidden on small screens) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-sm" id="assetsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Reference</th>
                            <th>Asset</th>
                            <th class="d-none d-md-table-cell">Category</th>
                            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                            <th class="d-none d-lg-table-cell">Project</th>
                            <?php else: ?>
                            <th class="d-none d-lg-table-cell">Location</th>
                            <?php endif; ?>
                            <th class="text-center">Quantity</th>
                            <th class="text-center">Status</th>
                            <th class="d-none d-xl-table-cell text-center">QR Tag</th>
                            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                            <th class="d-none d-xxl-table-cell text-center">Workflow</th>
                            <?php endif; ?>
                            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                            <th class="d-none d-lg-table-cell text-end">Value</th>
                            <?php endif; ?>
                            <th class="text-center" style="min-width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td>
                                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none">
                                        <strong><?= htmlspecialchars($asset['ref'] ?? 'N/A') ?></strong>
                                    </a>
                                    <?php if (!empty($asset['qr_code'])): ?>
                                        <i class="bi bi-qr-code text-primary ms-1" title="QR Code Available"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($asset['name'] ?? 'Unknown') ?></div>
                                        <?php if (!empty($asset['serial_number'])): ?>
                                            <small class="text-muted">S/N: <?= htmlspecialchars($asset['serial_number']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <?php else: ?>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['location'] ?? 'Warehouse') ?>
                                    </span>
                                </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <?php
                                    // Get asset quantity information
                                    $quantity = (int)($asset['quantity'] ?? 1);
                                    $availableQuantity = (int)($asset['available_quantity'] ?? 1);
                                    $unit = $asset['unit'] ?? 'pcs';
                                    $isConsumable = isset($asset['is_consumable']) && $asset['is_consumable'] == 1;
                                    ?>
                                    
                                    <?php if ($isConsumable): ?>
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-1">
                                                <span class="badge bg-primary"><?= number_format($availableQuantity) ?></span>
                                                <span class="text-muted">/ <?= number_format($quantity) ?></span>
                                            </div>
                                            <div class="text-center">
                                                <small class="text-muted d-block d-sm-none">
                                                    <?= htmlspecialchars($unit) ?>
                                                </small>
                                                <small class="text-muted d-none d-sm-block">
                                                    Available / Total <?= htmlspecialchars($unit) ?>
                                                </small>
                                                <?php if ($availableQuantity == 0): ?>
                                                    <small class="text-danger">
                                                        <i class="bi bi-exclamation-circle me-1"></i>Out of stock
                                                    </small>
                                                <?php elseif ($availableQuantity < $quantity): ?>
                                                    <small class="text-warning">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        <?= number_format($quantity - $availableQuantity) ?> in use
                                                    </small>
                                                <?php elseif ($availableQuantity <= ($quantity * 0.2)): ?>
                                                    <small class="text-warning">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Low stock
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center">
                                            <span class="badge bg-light text-dark">1 <?= htmlspecialchars($unit) ?></span>
                                            <small class="text-muted d-block d-none d-sm-block">Individual item</small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $status = $asset['status'] ?? 'unknown';
                                    $statusClasses = [
                                        'available' => 'bg-success',
                                        'in_use' => 'bg-primary',
                                        'borrowed' => 'bg-info',
                                        'under_maintenance' => 'bg-warning',
                                        'retired' => 'bg-secondary',
                                        'disposed' => 'bg-dark'
                                    ];
                                    $statusClass = $statusClasses[$status] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                    </span>
                                    
                                    <?php if ($isConsumable && $availableQuantity == 0): ?>
                                        <small class="text-danger d-block">
                                            <i class="bi bi-exclamation-circle me-1"></i>Out of stock
                                        </small>
                                    <?php elseif ($isConsumable && $availableQuantity <= ($quantity * 0.2)): ?>
                                        <small class="text-warning d-block">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Low stock
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-xl-table-cell text-center">
                                    <?php 
                                    // QR Tag Status Indicators
                                    $hasQR = !empty($asset['qr_code']);
                                    $isPrinted = !empty($asset['qr_tag_printed']);
                                    $isApplied = !empty($asset['qr_tag_applied']);
                                    $isVerified = !empty($asset['qr_tag_verified']);
                                    ?>
                                    
                                    <div class="d-flex flex-column gap-1">
                                        <?php if ($hasQR): ?>
                                            <small class="text-success">
                                                <i class="bi bi-qr-code me-1"></i>QR Generated
                                            </small>
                                            
                                            <?php if ($isPrinted): ?>
                                                <small class="text-info">
                                                    <i class="bi bi-printer me-1"></i>Printed
                                                </small>
                                            <?php else: ?>
                                                <small class="text-warning">
                                                    <i class="bi bi-printer me-1"></i>Need Print
                                                </small>
                                            <?php endif; ?>
                                            
                                            <?php if ($isApplied): ?>
                                                <small class="text-primary">
                                                    <i class="bi bi-hand-index me-1"></i>Applied
                                                </small>
                                            <?php elseif ($isPrinted): ?>
                                                <small class="text-warning">
                                                    <i class="bi bi-hand-index me-1"></i>Need Apply
                                                </small>
                                            <?php endif; ?>
                                            
                                            <?php if ($isVerified): ?>
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle me-1"></i>Verified
                                                </small>
                                            <?php elseif ($isApplied): ?>
                                                <small class="text-warning">
                                                    <i class="bi bi-check-circle me-1"></i>Need Verify
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small class="text-danger">
                                                <i class="bi bi-x-circle me-1"></i>No QR Code
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                                <td class="d-none d-xxl-table-cell text-center">
                                    <?php
                                    $workflowStatus = $asset['workflow_status'] ?? 'approved';
                                    $assetSource = $asset['asset_source'] ?? 'manual';
                                    
                                    // Only show workflow status for legacy assets
                                    if ($assetSource === 'legacy'):
                                        $workflowClasses = [
                                            'draft' => 'bg-secondary',
                                            'pending_verification' => 'bg-warning',
                                            'pending_authorization' => 'bg-info',
                                            'approved' => 'bg-success'
                                        ];
                                        $workflowClass = $workflowClasses[$workflowStatus] ?? 'bg-secondary';
                                        $workflowText = [
                                            'draft' => 'Draft',
                                            'pending_verification' => 'Pending Verification',
                                            'pending_authorization' => 'Pending Authorization', 
                                            'approved' => 'Approved'
                                        ];
                                        $statusText = $workflowText[$workflowStatus] ?? 'Unknown';
                                    ?>
                                        <span class="badge <?= $workflowClass ?>" title="Legacy asset workflow status">
                                            <i class="bi bi-gear me-1"></i><?= $statusText ?>
                                        </span>
                                        <?php if ($workflowStatus === 'pending_verification'): ?>
                                            <small class="text-warning d-block">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Needs verification
                                            </small>
                                        <?php elseif ($workflowStatus === 'pending_authorization'): ?>
                                            <small class="text-info d-block">
                                                <i class="bi bi-shield-exclamation me-1"></i>Needs authorization
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark" title="Standard asset">
                                            <i class="bi bi-check-circle me-1"></i>Standard
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                                <td class="d-none d-lg-table-cell text-end">
                                    <?php if ($asset['acquisition_cost']): ?>
                                        <strong><?= formatCurrency($asset['acquisition_cost']) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=assets/view&id=<?= $asset['id'] ?>" 
                                           class="btn btn-outline-primary btn-sm" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php 
                                        // Show workflow-specific actions for legacy assets
                                        $assetSource = $asset['asset_source'] ?? 'manual';
                                        $workflowStatus = $asset['workflow_status'] ?? 'approved';
                                        
                                        // Debug: Add HTML comment for debugging
                                        echo "<!-- DEBUG: Asset ID {$asset['id']} - Source: $assetSource, Status: $workflowStatus, UserRole: $userRole -->\n";
                                        
                                        if ($assetSource === 'legacy'):
                                            if ($workflowStatus === 'pending_verification' && in_array($userRole, $roleConfig['assets/legacy-verify'] ?? [])):
                                                echo "<!-- DEBUG: Rendering VERIFY button for asset {$asset['id']} -->\n";
                                        ?>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="openEnhancedVerification(<?= $asset['id'] ?>);" 
                                                            title="Enhanced Verification Review"
                                                            data-asset-id="<?= $asset['id'] ?>"
                                                            data-action="enhanced-verify">
                                                        <i class="bi bi-shield-check me-1"></i>Review
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning btn-sm" 
                                                            onclick="console.log('Quick verify button clicked for asset <?= $asset['id'] ?>'); verifyAsset(<?= $asset['id'] ?>);" 
                                                            title="Quick Verify"
                                                            data-asset-id="<?= $asset['id'] ?>"
                                                            data-action="verify">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </div>
                                        <?php 
                                            elseif ($workflowStatus === 'pending_authorization' && in_array($userRole, $roleConfig['assets/legacy-authorize'] ?? [])):
                                                echo "<!-- DEBUG: Rendering AUTHORIZE button for asset {$asset['id']} -->\n";
                                        ?>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-info btn-sm" 
                                                            onclick="openEnhancedAuthorization(<?= $asset['id'] ?>);" 
                                                            title="Enhanced Authorization Review"
                                                            data-asset-id="<?= $asset['id'] ?>"
                                                            data-action="enhanced-authorize">
                                                        <i class="bi bi-shield-check me-1"></i>Review
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info btn-sm" 
                                                            onclick="console.log('Quick authorize button clicked for asset <?= $asset['id'] ?>'); authorizeAsset(<?= $asset['id'] ?>);" 
                                                            title="Quick Authorize"
                                                            data-asset-id="<?= $asset['id'] ?>"
                                                            data-action="authorize">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </div>
                                        <?php 
                                            else:
                                                echo "<!-- DEBUG: No workflow button for asset {$asset['id']} - Status: $workflowStatus, CanVerify: " . (in_array($userRole, $roleConfig['assets/legacy-verify'] ?? []) ? 'YES' : 'NO') . ", CanAuthorize: " . (in_array($userRole, $roleConfig['assets/legacy-authorize'] ?? []) ? 'YES' : 'NO') . " -->\n";
                                            endif;
                                        else:
                                            echo "<!-- DEBUG: Not a legacy asset - Asset {$asset['id']} is $assetSource -->\n";
                                        endif;
                                        ?>
                                        
                                        <?php if (in_array($userRole, $roleConfig['assets/edit'] ?? [])): ?>
                                            <a href="?route=assets/edit&id=<?= $asset['id'] ?>" 
                                               class="btn btn-outline-warning btn-sm" title="Edit Asset">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($status === 'available' && in_array($userRole, $roleConfig['withdrawals/create'] ?? [])): ?>
                                            <a href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>" 
                                               class="btn btn-outline-success btn-sm" title="Withdraw Asset">
                                                <i class="bi bi-box-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($userRole, $roleConfig['assets/delete'] ?? [])): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteAsset(<?= $asset['id'] ?>)" title="Delete Asset">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Assets pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <?php 
                                $prevParams = array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route');
                                $prevParams['page'] = $pagination['current_page'] - 1;
                                ?>
                                <a class="page-link" href="?route=assets&<?= http_build_query($prevParams) ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <?php 
                                $pageParams = array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route');
                                $pageParams['page'] = $i;
                                ?>
                                <a class="page-link" href="?route=assets&<?= http_build_query($pageParams) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <?php 
                                $nextParams = array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route');
                                $nextParams['page'] = $pagination['current_page'] + 1;
                                ?>
                                <a class="page-link" href="?route=assets&<?= http_build_query($nextParams) ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// CSRF Token for AJAX requests
const CSRFTokenValue = '<?= htmlspecialchars(CSRFProtection::generateToken() ?? "", ENT_QUOTES, 'UTF-8') ?>';
const CSRFToken = `_csrf_token=${encodeURIComponent(CSRFTokenValue)}`;

// Debug: Log CSRF token info
console.log('CSRF Token Value:', CSRFTokenValue ? CSRFTokenValue.substring(0, 8) + '...' : 'EMPTY');
console.log('CSRF Token Format:', CSRFToken ? CSRFToken.substring(0, 20) + '...' : 'EMPTY');

// Functions will be defined below due to hoisting

// Delete asset
function deleteAsset(assetId) {
    if (confirm('Are you sure you want to delete this asset? This action cannot be undone.')) {
        fetch('?route=assets/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ asset_id: assetId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete asset: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the asset');
        });
    }
}

// Refresh assets
function refreshAssets() {
    window.location.reload();
}

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=assets/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Verify legacy asset
function verifyAsset(assetId) {
    console.log('verifyAsset called with ID:', assetId);
    
    if (!CSRFTokenValue) {
        console.error('CSRF token is missing!');
        showAlert('error', 'Security token missing. Please refresh the page.');
        return;
    }
    
    if (confirm('Are you sure you want to verify this legacy asset?')) {
        console.log('User confirmed verification');
        
        const requestBody = `asset_id=${assetId}&${CSRFToken}`;
        console.log('Request body:', requestBody);
        
        showAlert('info', 'Verifying asset...');
        
        fetch('?route=assets/verify-asset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: requestBody
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Parsed response:', data);
            if (data.success) {
                showAlert('success', 'Asset verified successfully!');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('error', data.message || 'Failed to verify asset');
            }
        })
        .catch(error => {
            console.error('Verify error:', error);
            showAlert('error', 'An error occurred: ' + error.message);
        });
    } else {
        console.log('User cancelled verification');
    }
}

// Authorize legacy asset
function authorizeAsset(assetId) {
    console.log('authorizeAsset called with ID:', assetId);
    
    if (!CSRFTokenValue) {
        console.error('CSRF token is missing!');
        showAlert('error', 'Security token missing. Please refresh the page.');
        return;
    }
    
    if (confirm('Are you sure you want to authorize this legacy asset as project property?')) {
        console.log('User confirmed authorization');
        
        const requestBody = `asset_id=${assetId}&${CSRFToken}`;
        console.log('Request body:', requestBody);
        
        showAlert('info', 'Authorizing asset...');
        
        fetch('?route=assets/authorize-asset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: requestBody
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Parsed response:', data);
            if (data.success) {
                showAlert('success', 'Asset authorized successfully!');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('error', data.message || 'Failed to authorize asset');
            }
        })
        .catch(error => {
            console.error('Authorize error:', error);
            showAlert('error', 'An error occurred: ' + error.message);
        });
    } else {
        console.log('User cancelled authorization');
    }
}

// Show alert messages
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    
    // Remove any existing alerts first
    const existingAlerts = document.querySelectorAll('.alert.fade.show');
    existingAlerts.forEach(alert => alert.remove());
    
    let alertClass, iconClass;
    switch(type) {
        case 'error':
            alertClass = 'alert-danger';
            iconClass = 'exclamation-triangle';
            break;
        case 'info':
            alertClass = 'alert-info';
            iconClass = 'info-circle';
            break;
        case 'success':
        default:
            alertClass = 'alert-success';
            iconClass = 'check-circle';
            break;
    }
    
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="bi bi-${iconClass} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after page header
    const pageHeader = document.querySelector('.border-bottom');
    pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Enhanced search functionality with standardization
class EnhancedAssetSearch {
    constructor() {
        this.searchInput = document.getElementById('search');
        this.searchStatus = document.getElementById('search-status');
        this.searchIcon = document.getElementById('search-icon');
        this.searchFeedback = document.getElementById('search-feedback');
        this.suggestions = document.getElementById('search-suggestions');
        this.debounceTimer = null;
        
        this.init();
    }
    
    init() {
        if (!this.searchInput) return;
        
        this.searchInput.addEventListener('input', (e) => this.handleSearch(e));
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.target.form.submit();
            }
        });
        
        // Initialize on page load if there's a search term
        if (this.searchInput.value.trim()) {
            this.validateSearch(this.searchInput.value.trim());
        }
    }
    
    handleSearch(event) {
        const value = event.target.value.trim();
        
        if (value.length < 2) {
            this.clearFeedback();
            return;
        }
        
        this.updateSearchStatus('searching');
        
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.validateSearch(value);
        }, 300);
    }
    
    async validateSearch(query) {
        try {
            const response = await fetch('/api/assets/enhanced-search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRFTokenValue
                },
                body: JSON.stringify({ query: query })
            });
            
            if (!response.ok) {
                throw new Error('Search validation failed');
            }
            
            const data = await response.json();
            this.handleSearchResult(data, query);
            
        } catch (error) {
            console.warn('Enhanced search unavailable, falling back to basic search:', error);
            this.updateSearchStatus('basic');
            this.searchFeedback.innerHTML = '<i class="bi bi-info-circle me-1"></i>Basic search mode';
        }
    }
    
    handleSearchResult(result, originalQuery) {
        if (result.corrected && result.corrected !== originalQuery) {
            this.updateSearchStatus('corrected');
            this.searchFeedback.innerHTML = `
                <i class="bi bi-lightbulb me-1"></i>
                Did you mean: <strong>${result.corrected}</strong>?
                <button type="button" class="btn btn-sm btn-link p-0 ms-1" 
                        onclick="assetSearch.applySuggestion('${result.corrected}')">
                    Use this
                </button>
            `;
        } else if (result.suggestions && result.suggestions.length > 0) {
            this.updateSuggestions(result.suggestions);
            this.updateSearchStatus('suggestions');
            this.searchFeedback.innerHTML = `<i class="bi bi-lightbulb me-1"></i>Showing ${result.suggestions.length} suggestions`;
        } else {
            this.updateSearchStatus('valid');
            this.searchFeedback.innerHTML = '';
        }
        
        if (result.disciplines && result.disciplines.length > 0) {
            this.searchFeedback.innerHTML += `
                <div class="mt-1">
                    <small class="text-muted">
                        <i class="bi bi-tags me-1"></i>Disciplines: ${result.disciplines.join(', ')}
                    </small>
                </div>
            `;
        }
    }
    
    updateSearchStatus(status) {
        const iconClasses = {
            'searching': 'bi-arrow-clockwise text-primary spin',
            'corrected': 'bi-lightbulb text-warning',
            'suggestions': 'bi-list text-info',
            'valid': 'bi-check-circle text-success',
            'basic': 'bi-search text-muted'
        };
        
        this.searchIcon.className = `bi ${iconClasses[status] || 'bi-search text-muted'}`;
    }
    
    updateSuggestions(suggestions) {
        this.suggestions.innerHTML = '';
        suggestions.slice(0, 8).forEach(suggestion => {
            const option = document.createElement('option');
            option.value = suggestion;
            this.suggestions.appendChild(option);
        });
    }
    
    applySuggestion(suggestion) {
        this.searchInput.value = suggestion;
        this.clearFeedback();
        // Auto-submit the form
        this.searchInput.form.submit();
    }
    
    clearFeedback() {
        this.searchFeedback.innerHTML = '';
        this.suggestions.innerHTML = '';
        this.updateSearchStatus('basic');
    }
}

// Initialize enhanced search
let assetSearch;
document.addEventListener('DOMContentLoaded', function() {
    assetSearch = new EnhancedAssetSearch();
});

// Auto-submit form on filter change (enhanced)
document.addEventListener('DOMContentLoaded', function() {
    // Get the filter form by finding the form that contains the hidden route input
    const filterForm = document.querySelector('form[method="GET"]');
    
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('select');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Clear search when filters change for better UX
                const searchInput = filterForm.querySelector('input[name="search"]');
                if (searchInput && searchInput !== this) {
                    // Only clear if changing a different filter
                    const currentSearch = searchInput.value.trim();
                    if (currentSearch && this.value) {
                        // Show confirmation for destructive action
                        if (confirm('Changing filters will clear your current search. Continue?')) {
                            searchInput.value = '';
                            filterForm.submit();
                        }
                        return;
                    }
                }
                filterForm.submit();
            });
        });
        
        // Enhanced search handling is now managed by EnhancedAssetSearch class
    }
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+K or Cmd+K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            const searchInput = document.getElementById('search');
            if (searchInput && document.activeElement === searchInput) {
                searchInput.blur();
                if (assetSearch) {
                    assetSearch.clearFeedback();
                }
            }
        }
    });
    
    // Responsive table enhancements
    function handleTableResponsiveness() {
        const table = document.getElementById('assetsTable');
        if (!table) return;
        
        const windowWidth = window.innerWidth;
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const referenceCell = cells[0];
            const assetCell = cells[1];
            
            if (windowWidth < 768 && referenceCell && assetCell) {
                // Add category and other info to asset cell on mobile
                const categoryCell = row.querySelector('.d-none.d-md-table-cell');
                if (categoryCell && !assetCell.querySelector('.mobile-category')) {
                    const categoryBadge = categoryCell.querySelector('.badge');
                    if (categoryBadge) {
                        const mobileCategory = document.createElement('div');
                        mobileCategory.className = 'mobile-category mt-1';
                        mobileCategory.innerHTML = `<small class="text-muted">${categoryBadge.textContent}</small>`;
                        assetCell.appendChild(mobileCategory);
                    }
                }
            }
        });
    }
    
    // Handle table responsiveness on load and resize
    handleTableResponsiveness();
    window.addEventListener('resize', handleTableResponsiveness);
});
</script>

<style>
/* Enhanced search styles */
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#search-feedback {
    min-height: 20px;
    transition: all 0.3s ease;
}

.input-group .form-control:focus + .input-group-text {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Keyboard shortcut hint */
.search-hint {
    position: absolute;
    right: 45px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 11px;
    color: #6c757d;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #dee2e6;
    pointer-events: none;
}

/* Responsive table improvements */
@media (max-width: 1200px) {
    .table-responsive {
        font-size: 0.875rem;
    }
}

@media (max-width: 992px) {
    .btn-group-sm .btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .badge {
        font-size: 0.65em;
        padding: 0.25em 0.4em;
    }
}

@media (max-width: 768px) {
    .search-hint {
        display: none;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.2rem 0.3rem;
        font-size: 0.7rem;
    }
    
    .table-responsive {
        font-size: 0.75rem;
    }
    
    .badge {
        font-size: 0.6em;
        padding: 0.2em 0.3em;
    }
}

@media (max-width: 576px) {
    .table-responsive {
        font-size: 0.7rem;
    }
    
    .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
    }
    
    .btn-group .btn {
        flex: 1;
        min-width: 36px;
    }
    
    /* Stack buttons vertically on very small screens */
    .btn-group-sm .btn {
        padding: 0.15rem 0.25rem;
        font-size: 0.65rem;
        border-radius: 0.2rem;
    }
}

/* Card uniformity improvements */
.card {
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 0.375rem;
}

.card-body {
    padding: 1.25rem;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 0.75rem;
    }
}

/* Button group responsiveness */
.btn-toolbar {
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-group {
    flex-wrap: wrap;
}

@media (max-width: 576px) {
    .btn-toolbar .btn-group {
        width: 100%;
    }
    
    .btn-toolbar .btn {
        flex: 1;
        min-width: 0;
    }
}

/* Table action buttons responsive stacking */
@media (max-width: 576px) {
    .table td:last-child .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .table td:last-child .btn-group .btn {
        border-radius: 0.25rem !important;
        margin-bottom: 2px;
    }
    
    .table td:last-child .btn-group .btn:last-child {
        margin-bottom: 0;
    }
}

/* Role-based Dashboard Cards - Enhanced Responsive Design */
.row.mb-4 {
    margin-left: -0.375rem;
    margin-right: -0.375rem;
}

.row.mb-4 > [class*="col-"] {
    padding-left: 0.375rem;
    padding-right: 0.375rem;
}

/* Dashboard card enhancements for equal heights and optimal spacing */
.card.h-100 {
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card.h-100:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}

.card.h-100 .card-body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.card.h-100 .card-footer {
    flex-shrink: 0;
    margin-top: auto;
    border-top: 1px solid rgba(255,255,255,0.2);
    background-color: rgba(0,0,0,0.1);
}

/* Role-specific card background variations */
.bg-primary-dark {
    background-color: rgba(13, 110, 253, 0.8) !important;
}

.bg-success-dark {
    background-color: rgba(25, 135, 84, 0.8) !important;
}

.bg-info-dark {
    background-color: rgba(13, 202, 240, 0.8) !important;
}

.bg-warning-dark {
    background-color: rgba(255, 193, 7, 0.8) !important;
}

.bg-danger-dark {
    background-color: rgba(220, 53, 69, 0.8) !important;
}

.bg-secondary-dark {
    background-color: rgba(108, 117, 125, 0.8) !important;
}

/* Responsive breakpoints for dashboard cards */
/* Extra Large screens (1400px and up) - 4 cards per row with more spacing */
@media (min-width: 1400px) {
    .row.mb-4 {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
    }
    
    .row.mb-4 > [class*="col-"] {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .card.h-100 .card-body {
        padding: 1.5rem;
    }
}

/* Large screens (1200px to 1399px) - 4 cards per row, standard spacing */
@media (min-width: 1200px) and (max-width: 1399px) {
    .card.h-100 .card-body {
        padding: 1.25rem;
    }
    
    .card.h-100 h3 {
        font-size: 1.75rem;
    }
}

/* Medium screens (992px to 1199px) - 3 cards per row */
@media (min-width: 992px) and (max-width: 1199px) {
    .row.mb-4 .col-lg-3 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
    
    .card.h-100 .card-body {
        padding: 1rem;
    }
    
    .card.h-100 h6 {
        font-size: 0.9rem;
    }
    
    .card.h-100 h3 {
        font-size: 1.5rem;
    }
    
    .card.h-100 small {
        font-size: 0.75rem;
    }
}

/* Small screens (768px to 991px) - 2 cards per row */
@media (min-width: 768px) and (max-width: 991px) {
    .card.h-100 .card-body {
        padding: 1rem;
    }
    
    .card.h-100 h6 {
        font-size: 0.85rem;
    }
    
    .card.h-100 h3 {
        font-size: 1.4rem;
    }
    
    .card.h-100 .display-6 {
        font-size: 2.5rem;
    }
    
    .card.h-100 small {
        font-size: 0.7rem;
    }
}

/* Extra small screens (576px to 767px) - 1 card per row with optimized spacing */
@media (max-width: 767px) {
    .row.mb-4 {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        margin-bottom: 2rem !important;
    }
    
    .row.mb-4 > [class*="col-"] {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .card.h-100 {
        margin-bottom: 0.75rem;
    }
    
    .card.h-100 .card-body {
        padding: 1rem;
    }
    
    .card.h-100 h6 {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .card.h-100 h3 {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    
    .card.h-100 .display-6 {
        font-size: 2.25rem;
    }
    
    .card.h-100 small {
        font-size: 0.8rem;
        opacity: 0.8;
    }
    
    .card.h-100 .card-footer {
        padding: 0.75rem 1rem;
    }
    
    .card.h-100 .card-footer small {
        font-size: 0.75rem;
    }
}

/* Very small screens (below 576px) - Single column with compact design */
@media (max-width: 575px) {
    .row.mb-4 {
        margin-left: -0.25rem;
        margin-right: -0.25rem;
    }
    
    .row.mb-4 > [class*="col-"] {
        padding-left: 0.25rem;
        padding-right: 0.25rem;
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .card.h-100 {
        margin-bottom: 0.5rem;
        border-radius: 0.5rem;
    }
    
    .card.h-100 .card-body {
        padding: 0.75rem;
    }
    
    .card.h-100 .d-flex {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .card.h-100 h6 {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
        order: 2;
    }
    
    .card.h-100 h3 {
        font-size: 2rem;
        margin-bottom: 0.25rem;
        order: 1;
    }
    
    .card.h-100 .display-6 {
        font-size: 2rem;
        order: 1;
        align-self: center;
        margin-bottom: 0.5rem;
    }
    
    .card.h-100 small {
        font-size: 0.75rem;
        order: 3;
        margin-top: 0.25rem;
    }
    
    .card.h-100 .card-footer {
        padding: 0.5rem;
        text-align: center;
    }
    
    .card.h-100 .card-footer small {
        font-size: 0.7rem;
    }
}

/* Mobile-first adjustments */
@media (max-width: 480px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-toolbar {
        width: 100%;
        justify-content: stretch;
    }
    
    .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .form-select-sm, .form-control-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

/* Card content optimization for readability */
@media (max-width: 768px) {
    .card.h-100 .opacity-75 {
        opacity: 0.85;
    }
    
    .card.h-100 .bi {
        margin-right: 0.25rem;
    }
}

/* Ensure proper contrast and accessibility */
.card.text-white small {
    color: rgba(255, 255, 255, 0.85) !important;
}

.card.text-white .opacity-75 {
    opacity: 0.85 !important;
}
</style>

<script>
// Add search hint and mobile optimizations
document.addEventListener('DOMContentLoaded', function() {
    const searchGroup = document.querySelector('#search')?.closest('.input-group');
    if (searchGroup && window.innerWidth > 768) {
        const hint = document.createElement('span');
        hint.className = 'search-hint';
        hint.textContent = '⌘K';
        hint.title = 'Keyboard shortcut: Ctrl+K (Windows) or Cmd+K (Mac)';
        searchGroup.style.position = 'relative';
        searchGroup.appendChild(hint);
    }
    
    // Mobile-specific enhancements
    if (window.innerWidth <= 768) {
        // Add swipe gestures for table navigation on mobile
        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer) {
            let isScrolling = false;
            let startX = 0;
            
            tableContainer.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                isScrolling = false;
            });
            
            tableContainer.addEventListener('touchmove', function(e) {
                if (!startX) return;
                isScrolling = true;
            });
            
            tableContainer.addEventListener('touchend', function() {
                startX = 0;
                isScrolling = false;
            });
            
            // Add visual indicator for horizontal scrolling
            const scrollIndicator = document.createElement('div');
            scrollIndicator.className = 'text-center text-muted small mt-2 d-md-none';
            scrollIndicator.innerHTML = '<i class="bi bi-arrow-left-right me-1"></i>Swipe horizontally to see more columns';
            tableContainer.parentNode.appendChild(scrollIndicator);
        }
    }
    
    // Responsive card height equalizer
    function equalizeCardHeights() {
        const cardRows = document.querySelectorAll('.row.mb-4');
        cardRows.forEach(row => {
            const cards = row.querySelectorAll('.card');
            let maxHeight = 0;
            
            // Reset heights
            cards.forEach(card => {
                card.style.height = 'auto';
            });
            
            // Find max height
            cards.forEach(card => {
                const height = card.offsetHeight;
                if (height > maxHeight) {
                    maxHeight = height;
                }
            });
            
            // Apply max height to all cards in the row (only on larger screens)
            if (window.innerWidth >= 768) {
                cards.forEach(card => {
                    card.style.height = maxHeight + 'px';
                });
            }
        });
    }
    
    // Run on load and resize
    window.addEventListener('load', equalizeCardHeights);
    window.addEventListener('resize', function() {
        setTimeout(equalizeCardHeights, 100);
    });
});
</script>

<script>
// Verify workflow functions are properly defined
console.log('=== Function Verification ===');
console.log('verifyAsset function defined:', typeof verifyAsset);
console.log('authorizeAsset function defined:', typeof authorizeAsset);
console.log('deleteAsset function defined:', typeof deleteAsset);
console.log('showAlert function defined:', typeof showAlert);

// Make functions globally accessible (fallback)
if (typeof window !== 'undefined') {
    window.verifyAsset = verifyAsset;
    window.authorizeAsset = authorizeAsset;
    window.deleteAsset = deleteAsset;
    window.showAlert = showAlert;
}

// Alternative event delegation for workflow buttons (fallback method)
document.addEventListener('click', function(event) {
    const target = event.target;
    const button = target.closest('button[data-action]');
    
    if (button) {
        const action = button.getAttribute('data-action');
        const assetId = button.getAttribute('data-asset-id');
        
        console.log('=== Alternative Event Handler ===');
        console.log('Button clicked:', action, 'Asset ID:', assetId);
        
        if (action === 'verify' && assetId) {
            console.log('Calling verifyAsset via event delegation');
            verifyAsset(parseInt(assetId));
        } else if (action === 'authorize' && assetId) {
            console.log('Calling authorizeAsset via event delegation');
            authorizeAsset(parseInt(assetId));
        }
    }
});
</script>

<?php
// Include enhanced verification and authorization modals
include APP_ROOT . '/views/assets/enhanced_verification_modal.php';
include APP_ROOT . '/views/assets/enhanced_authorization_modal.php';
?>

<script src="assets/js/enhanced-verification.js"></script>
<script>
// Make user role available globally for enhanced verification
const userRole = '<?= htmlspecialchars($user['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Assets - ConstructLink™';
$pageHeader = 'Asset Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Assets', 'url' => '?route=assets']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
