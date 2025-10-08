<?php
/**
 * Statistics Cards Partial
 * Displays role-specific inventory statistics dashboard cards
 */
?>

<!-- Role-Specific Inventory Statistics Cards -->
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
                        <h6 class="text-muted mb-1 small">Project Inventory</h6>
                        <h3 class="mb-0"><?= $roleStats['total_project_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-building me-1"></i>Items under management
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
                    <i class="bi bi-graph-up me-1"></i><?= $roleStats['assets_in_use'] ?? 0 ?> items in use
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
    <!-- Warehouseman Dashboard Cards - Capital Assets & Consumable Inventory -->
    <div class="row g-3 mb-4">
    <!-- Card 1: Total Items (Capital + Inventory) -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-boxes text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Items</h6>
                        <h3 class="mb-0"><?= $roleStats['total_items'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-gear me-1"></i><?= $roleStats['total_capital_assets'] ?? 0 ?> equipment,
                    <i class="bi bi-stack ms-1 me-1"></i><?= $roleStats['total_inventory_items'] ?? 0 ?> inventory
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View All Items
                </a>
            </div>
        </div>
    </div>

    <!-- Card 2: Available (Capital Equipment + Consumable Units) -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Available Stock</h6>
                        <h3 class="mb-0"><?= $roleStats['capital_available'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-gear me-1"></i>Equipment ready |
                    <i class="bi bi-box ms-1 me-1"></i><?= number_format($roleStats['consumable_units_available'] ?? 0) ?> units
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=available" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View Available
                </a>
            </div>
        </div>
    </div>

    <!-- Card 3: Capital Equipment In Use -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-box-arrow-right text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Equipment In Use</h6>
                        <h3 class="mb-0"><?= $roleStats['capital_in_use'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-tools me-1"></i><?= $roleStats['capital_maintenance'] ?? 0 ?> under maintenance
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets&status=in_use" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View In Use
                </a>
            </div>
        </div>
    </div>

    <!-- Card 4: Consumable Inventory Status -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid <?= ($roleStats['consumable_out_of_stock'] ?? 0) > 0 ? 'var(--danger-color)' : 'var(--neutral-color)' ?>;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-stack <?= ($roleStats['consumable_out_of_stock'] ?? 0) > 0 ? 'text-danger' : 'text-secondary' ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Inventory Status</h6>
                        <h3 class="mb-0"><?= $roleStats['consumable_in_stock'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-check-circle me-1"></i>In stock |
                    <i class="bi bi-exclamation-circle ms-1 me-1 <?= ($roleStats['consumable_out_of_stock'] ?? 0) > 0 ? 'text-danger' : '' ?>"></i><?= $roleStats['consumable_out_of_stock'] ?? 0 ?> out of stock
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <?php if (($roleStats['consumable_out_of_stock'] ?? 0) > 0): ?>
                    <a href="?route=assets&asset_type=out_of_stock" class="text-decoration-none small">
                        <i class="bi bi-eye me-1"></i>View Out of Stock
                    </a>
                <?php else: ?>
                    <span class="text-muted small">
                        <i class="bi bi-check-circle me-1"></i>All items in stock
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
                        <h6 class="text-muted mb-1 small">System Inventory</h6>
                        <h3 class="mb-0"><?= $roleStats['total_system_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-globe me-1"></i><?= $roleStats['active_projects'] ?? 0 ?> active projects
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
                        <h6 class="text-muted mb-1 small">Disposed Items</h6>
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
                        <h6 class="text-muted mb-1 small">High Value Items</h6>
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
                        <h6 class="text-muted mb-1 small">Managed Inventory</h6>
                        <h3 class="mb-0"><?= $roleStats['total_managed_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-shield-check me-1"></i><?= $roleStats['projects_managed'] ?? 0 ?> projects
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
                        <i class="bi bi-speedometer2 text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Utilization Rate</h6>
                        <h3 class="mb-0"><?= $roleStats['overall_utilization'] ?? 0 ?>%</h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-speedometer2 me-1"></i>Inventory efficiency
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
    <!-- Default Inventory Status Statistics Cards (for Procurement Officer and other roles) -->
    <div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Inventory</h6>
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
                    <small><i class="bi bi-eye me-1"></i>View All Items</small>
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
