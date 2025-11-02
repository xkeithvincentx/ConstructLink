<?php
/**
 * Warehouseman Dashboard
 *
 * Role-specific dashboard for Warehouseman with inventory management,
 * delivery processing, and tool tracking.
 *
 * Refactored to use reusable components and eliminate code duplication.
 * Implements WCAG 2.1 AA accessibility standards.
 *
 * @package ConstructLink
 * @subpackage Dashboard - Role Specific
 * @version 2.0 - Refactored
 * @since 2025-10-28
 */

// Ensure required constants are available
if (!class_exists('WorkflowStatus')) {
    require_once APP_ROOT . '/includes/constants/WorkflowStatus.php';
}
if (!class_exists('DashboardThresholds')) {
    require_once APP_ROOT . '/includes/constants/DashboardThresholds.php';
}
if (!class_exists('IconMapper')) {
    require_once APP_ROOT . '/includes/constants/IconMapper.php';
}

// Load Dashboard Service for business logic
if (!class_exists('DashboardService')) {
    require_once APP_ROOT . '/services/DashboardService.php';
}

// Extract role-specific data
$warehouseData = $dashboardData['role_specific']['warehouse'] ?? [];
?>

<!-- Warehouseman Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Warehouse Actions -->
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="pending-warehouse-title">
                    Pending Warehouse Actions
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Define pending action items using WorkflowStatus constants
                // All neutral by default - warehouse operations are routine, not critical
                $pendingItems = [
                    [
                        'label' => 'Scheduled Deliveries',
                        'count' => $warehouseData['scheduled_deliveries'] ?? 0,
                        'route' => 'procurement-orders?' . http_build_query(['delivery_status' => WorkflowStatus::DELIVERY_SCHEDULED]),
                        'icon' => IconMapper::WORKFLOW_IN_TRANSIT,
                        'critical' => false
                    ],
                    [
                        'label' => 'Awaiting Receipt',
                        'count' => $warehouseData['awaiting_receipt'] ?? 0,
                        'route' => 'procurement-orders/for-receipt',
                        'icon' => 'bi-box-arrow-in-down',
                        'critical' => false
                    ],
                    [
                        'label' => 'Pending Releases',
                        'count' => $warehouseData['pending_releases'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('withdrawals', WorkflowStatus::WITHDRAWAL_APPROVED),
                        'icon' => 'bi-box-arrow-right',
                        'critical' => false
                    ],
                    [
                        'label' => 'Tool Requests',
                        'count' => $warehouseData['pending_tool_requests'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('borrowed-tools', WorkflowStatus::BORROWED_TOOLS_PENDING_VERIFICATION),
                        'icon' => IconMapper::MODULE_BORROWED_TOOLS,
                        'critical' => false
                    ]
                ];
                ?>

                <!-- Alpine.js Enhanced: Filterable Pending Actions -->
                <div x-data="filterableList(<?= htmlspecialchars(json_encode($pendingItems)) ?>)"
                     role="group"
                     aria-labelledby="pending-warehouse-title">

                    <!-- Filter Controls -->
                    <div class="btn-group mb-3 d-flex" role="group" aria-label="Filter pending warehouse actions">
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click="setFilter('all')">
                            <i class="bi bi-list-ul me-1" aria-hidden="true"></i>
                            All (<span x-text="items.length"></span>)
                        </button>
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'pending' ? 'btn-warning' : 'btn-outline-secondary'"
                                @click="setFilter('pending')">
                            <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                            With Items (<span x-text="pendingCount"></span>)
                        </button>
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'empty' ? 'btn-success' : 'btn-outline-secondary'"
                                @click="setFilter('empty')">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                            Empty (<span x-text="items.length - pendingCount"></span>)
                        </button>
                    </div>

                    <!-- Dynamic Pending Actions List -->
                    <div class="row">
                        <template x-for="(item, index) in filteredItems" :key="item.label">
                            <div class="col-12 col-md-6 mb-4 mb-md-3 d-flex">
                                <div class="action-item flex-fill"
                                     :class="item.critical ? 'action-item-critical' : ''"
                                     role="group"
                                     :aria-labelledby="'pending-action-' + index + '-label'">

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center">
                                            <i :class="item.icon + ' me-2 fs-5'" aria-hidden="true"></i>
                                            <span class="fw-semibold" :id="'pending-action-' + index + '-label'" x-text="item.label"></span>
                                        </div>
                                        <span class="badge rounded-pill"
                                              :class="item.critical ? 'badge-critical' : 'badge-neutral'"
                                              role="status"
                                              x-text="item.count"></span>
                                    </div>

                                    <template x-if="item.count > 0">
                                        <a :href="'?route=' + item.route"
                                           class="btn btn-sm mt-1"
                                           :class="item.critical ? 'btn-danger' : 'btn-outline-secondary'">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i>Process Now
                                        </a>
                                    </template>
                                    <template x-if="item.count === 0">
                                        <small class="text-muted d-block mt-1" role="status">
                                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>No pending items
                                        </small>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="filteredItems.length === 0" class="alert alert-info" role="status">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                        No items match the selected filter.
                    </div>

                </div>
            </div>
        </div>

        <!-- QR Tag Management - Neutral Design (Critical section, uses red appropriately) -->
        <?php
        $qrNeedsPrinting = $warehouseData['qr_needs_printing'] ?? 0;
        $qrNeedsApplication = $warehouseData['qr_needs_application'] ?? 0;
        $qrNeedsVerification = $warehouseData['qr_needs_verification'] ?? 0;
        $qrTotalPending = $qrNeedsPrinting + $qrNeedsApplication + $qrNeedsVerification;
        ?>
        <?php if ($qrTotalPending > 0): ?>
        <div class="card card-neutral">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="qr-management-title">
                    QR Tag Management
                </h5>
                <span class="badge badge-critical" role="status" aria-label="<?= $qrTotalPending ?> assets need QR tag processing">
                    <?= number_format($qrTotalPending) ?> Critical
                </span>
            </div>
            <div class="card-body">
                <div class="alert alert-danger mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                    <strong>Critical:</strong> <?= number_format($qrTotalPending) ?> assets require QR tag processing for proper tracking and management.
                </div>

                <div role="region" aria-labelledby="qr-management-title">
                    <?php if ($qrNeedsPrinting > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <i class="bi bi-printer text-danger me-2" aria-hidden="true"></i>
                            <strong>Need Printing</strong>
                            <small class="d-block text-muted ms-4">Assets without printed QR tags</small>
                        </div>
                        <span class="badge badge-critical" role="status">
                            <?= number_format($qrNeedsPrinting) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ($qrNeedsApplication > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <i class="bi bi-tag text-muted me-2" aria-hidden="true"></i>
                            <strong>Need Application</strong>
                            <small class="d-block text-muted ms-4">Tags printed but not applied to assets</small>
                        </div>
                        <span class="badge badge-neutral" role="status">
                            <?= number_format($qrNeedsApplication) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ($qrNeedsVerification > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <i class="bi bi-check-circle text-muted me-2" aria-hidden="true"></i>
                            <strong>Need Verification</strong>
                            <small class="d-block text-muted ms-4">Tags applied but not verified</small>
                        </div>
                        <span class="badge badge-neutral" role="status">
                            <?= number_format($qrNeedsVerification) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <a href="?route=assets/tag-management" class="btn btn-danger" aria-label="Manage QR tags for <?= $qrTotalPending ?> assets">
                        <i class="bi bi-qr-code-scan me-2" aria-hidden="true"></i>Manage QR Tags
                    </a>
                    <?php if ($qrNeedsPrinting > 0): ?>
                    <a href="?route=assets/print-tags" class="btn btn-outline-secondary btn-sm" aria-label="Print <?= $qrNeedsPrinting ?> QR tags">
                        <i class="bi bi-printer me-1" aria-hidden="true"></i>Print <?= number_format($qrNeedsPrinting) ?> Tags
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Visual Separator for improved section distinction -->
        <hr class="dashboard-section-separator my-4" aria-hidden="true">

        <!-- Inventory Status -->
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="inventory-status-title">
                    Inventory Status
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3" id="stock-levels-label">Current Stock Levels</h6>

                        <?php
                        // Stock levels using improved threshold logic
                        $lowStockCount = $warehouseData['low_stock_items'] ?? 0;
                        $criticalStockCount = $warehouseData['critical_stock_items'] ?? 0;
                        $outOfStockCount = $warehouseData['out_of_stock_items'] ?? 0;
                        $inTransitCount = $warehouseData['assets_in_transit'] ?? 0;
                        ?>
                        <div role="region" aria-labelledby="stock-levels-label">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-box text-muted me-1" aria-hidden="true"></i>Consumables
                                    </span>
                                    <span class="badge badge-neutral" role="status">
                                        <?= number_format($warehouseData['consumable_stock'] ?? 0) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-tools text-muted me-1" aria-hidden="true"></i>Tools
                                    </span>
                                    <span class="badge badge-neutral" role="status">
                                        <?= number_format($warehouseData['tool_stock'] ?? 0) ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($outOfStockCount > 0): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-exclamation-circle-fill text-danger me-1" aria-hidden="true"></i><strong>Out of Stock</strong>
                                    </span>
                                    <span class="badge badge-critical" role="status">
                                        <?= number_format($outOfStockCount) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($criticalStockCount > 0): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-exclamation-triangle-fill text-danger me-1" aria-hidden="true"></i><strong>Critical Stock</strong>
                                        <small class="d-block text-muted ms-3">≤1 unit</small>
                                    </span>
                                    <span class="badge badge-critical" role="status">
                                        <?= number_format($criticalStockCount) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($lowStockCount > 0): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-exclamation-triangle text-warning me-1" aria-hidden="true"></i>Low Stock
                                        <small class="d-block text-muted ms-3">≤3 units</small>
                                    </span>
                                    <span class="badge badge-neutral" role="status">
                                        <?= number_format($lowStockCount) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($inTransitCount > 0): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-truck text-muted me-1" aria-hidden="true"></i>In Transit
                                    </span>
                                    <span class="badge badge-neutral" role="status">
                                        <?= number_format($inTransitCount) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($criticalStockCount > 0 || $outOfStockCount > 0): ?>
                            <div class="alert alert-danger small" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                                <strong>Action Required:</strong> Reorder critical/out-of-stock items immediately.
                            </div>
                            <?php elseif ($lowStockCount > 0): ?>
                            <div class="alert alert-warning small" role="alert">
                                <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                Some items running low. Plan reorder soon.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <?php
                        // Tool management metrics - neutral design
                        $overdueTools = $warehouseData['overdue_tools'] ?? 0;
                        $items = [
                            [
                                'label' => 'Currently Borrowed',
                                'value' => $warehouseData['borrowed_tools'] ?? 0,
                                'critical' => false
                            ],
                            [
                                'label' => 'Overdue Returns',
                                'value' => $overdueTools,
                                'critical' => $overdueTools > 0, // Red if overdue
                                'success' => $overdueTools === 0 // Green if no overdue
                            ],
                            [
                                'label' => 'Active Withdrawals',
                                'value' => $warehouseData['active_withdrawals'] ?? 0,
                                'critical' => false
                            ]
                        ];
                        $title = 'Tool Management';
                        include APP_ROOT . '/views/dashboard/components/list_group.php';
                        ?>

                        <?php if ($overdueTools > 0): ?>
                        <div class="alert alert-danger small mt-3" role="alert">
                            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                            Some tools are overdue. Follow up required.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        <?php
        $title = 'Warehouse Operations';
        $titleIcon = null; // Removed decorative icon

        // ✅ REFACTORED: Business logic moved to service layer (MVC separation)
        // Get authorized quick actions from DashboardService
        $dashboardService = new DashboardService();
        $actions = $dashboardService->getWarehousemanActions();

        include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
        ?>

        <!-- Delivery Schedule -->
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="schedule-title">
                    Today's Schedule
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Today's schedule using list_group component - neutral design
                $items = [
                    [
                        'label' => 'Deliveries Expected',
                        'value' => $warehouseData['scheduled_deliveries'] ?? 0,
                        'critical' => false,
                        'icon' => IconMapper::WORKFLOW_IN_TRANSIT
                    ],
                    [
                        'label' => 'In Transit',
                        'value' => $warehouseData['in_transit_deliveries'] ?? 0,
                        'critical' => false,
                        'icon' => 'bi-box-arrow-in-down'
                    ],
                    [
                        'label' => 'Releases Due',
                        'value' => $warehouseData['pending_releases'] ?? 0,
                        'critical' => false,
                        'icon' => 'bi-box-arrow-right'
                    ]
                ];
                include APP_ROOT . '/views/dashboard/components/list_group.php';
                ?>

                <div class="mt-3 d-grid">
                    <a href="?route=procurement-orders&delivery=today" class="btn btn-outline-primary btn-sm" aria-label="View full delivery schedule">
                        <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>View Full Schedule
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php
        // Daily Summary - neutral design (all routine operations)
        $stats = [
            [
                'icon' => 'bi-box-arrow-in-down',
                'count' => $warehouseData['received_today'] ?? 0,
                'label' => 'Received Today',
                'critical' => false
            ],
            [
                'icon' => 'bi-box-arrow-right',
                'count' => $warehouseData['released_today'] ?? 0,
                'label' => 'Released Today',
                'critical' => false
            ],
            [
                'icon' => IconMapper::MODULE_BORROWED_TOOLS,
                'count' => $warehouseData['tools_issued_today'] ?? 0,
                'label' => 'Tools Issued',
                'critical' => false
            ],
            [
                'icon' => 'bi-arrow-counterclockwise',
                'count' => $warehouseData['tools_returned_today'] ?? 0,
                'label' => 'Tools Returned',
                'critical' => false
            ]
        ];
        $title = 'Daily Summary';
        $titleIcon = null; // Removed decorative icon
        $columns = 2; // 2 columns for sidebar layout
        include APP_ROOT . '/views/dashboard/components/stat_cards.php';
        ?>
    </div>
</div>
