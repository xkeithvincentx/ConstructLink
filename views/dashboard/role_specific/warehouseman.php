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

// Extract role-specific data
$warehouseData = $dashboardData['role_specific']['warehouse'] ?? [];
?>

<!-- Warehouseman Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Warehouse Actions -->
        <div class="card mb-4 card-accent-primary">
            <div class="card-header">
                <h5 class="mb-0" id="pending-warehouse-title">
                    <i class="bi bi-box-seam me-2 text-primary" aria-hidden="true"></i>Pending Warehouse Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row" role="group" aria-labelledby="pending-warehouse-title">
                    <?php
                    // Define pending action items using WorkflowStatus constants
                    $pendingItems = [
                        [
                            'label' => 'Scheduled Deliveries',
                            'count' => $warehouseData['scheduled_deliveries'] ?? 0,
                            'route' => 'procurement-orders?' . http_build_query(['delivery_status' => WorkflowStatus::DELIVERY_SCHEDULED]),
                            'icon' => IconMapper::WORKFLOW_IN_TRANSIT,
                            'color' => 'primary'
                        ],
                        [
                            'label' => 'Awaiting Receipt',
                            'count' => $warehouseData['awaiting_receipt'] ?? 0,
                            'route' => 'procurement-orders/for-receipt',
                            'icon' => 'bi-box-arrow-in-down',
                            'color' => 'warning'
                        ],
                        [
                            'label' => 'Pending Releases',
                            'count' => $warehouseData['pending_releases'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('withdrawals', WorkflowStatus::WITHDRAWAL_APPROVED),
                            'icon' => 'bi-box-arrow-right',
                            'color' => 'success'
                        ],
                        [
                            'label' => 'Tool Requests',
                            'count' => $warehouseData['pending_tool_requests'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('borrowed-tools', WorkflowStatus::BORROWED_TOOLS_PENDING_VERIFICATION),
                            'icon' => IconMapper::MODULE_BORROWED_TOOLS,
                            'color' => 'info'
                        ]
                    ];

                    // Set custom button text for warehouse
                    $actionText = 'Process Now';

                    // Render each pending action card using component
                    foreach ($pendingItems as $item) {
                        include APP_ROOT . '/views/dashboard/components/pending_action_card.php';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Inventory Status -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="inventory-status-title">
                    <i class="bi bi-archive me-2" aria-hidden="true"></i>Inventory Status
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3" id="stock-levels-label">Current Stock Levels</h6>

                        <?php
                        // Stock levels using list items
                        $lowStockCount = $warehouseData['low_stock_items'] ?? 0;
                        ?>
                        <div role="region" aria-labelledby="stock-levels-label">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Consumable Stock</span>
                                    <span class="badge bg-info" role="status">
                                        <?= number_format($warehouseData['consumable_stock'] ?? 0) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Tool Stock</span>
                                    <span class="badge bg-primary" role="status">
                                        <?= number_format($warehouseData['tool_stock'] ?? 0) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Low Stock Alerts</span>
                                    <span class="badge bg-<?= $lowStockCount > 0 ? 'danger' : 'success' ?>" role="status">
                                        <?= number_format($lowStockCount) ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($lowStockCount > 0): ?>
                            <div class="alert alert-warning small" role="alert">
                                <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                Some items are running low. Check inventory levels.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <?php
                        // Tool management metrics
                        $overdueTools = $warehouseData['overdue_tools'] ?? 0;
                        $items = [
                            [
                                'label' => 'Currently Borrowed',
                                'value' => $warehouseData['borrowed_tools'] ?? 0,
                                'color' => 'warning'
                            ],
                            [
                                'label' => 'Overdue Returns',
                                'value' => $overdueTools,
                                'color' => $overdueTools > 0 ? 'danger' : 'success'
                            ],
                            [
                                'label' => 'Active Withdrawals',
                                'value' => $warehouseData['active_withdrawals'] ?? 0,
                                'color' => 'info'
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
        $titleIcon = IconMapper::QUICK_ACTIONS;

        // Define all possible quick actions with permission requirements
        $allActions = [
            [
                'label' => 'Process Deliveries',
                'route' => 'procurement-orders/for-receipt',
                'icon' => 'bi-box-arrow-in-down',
                'color' => 'primary',
                'permission' => null // No specific permission defined yet
            ],
            [
                'label' => 'Release Items',
                'route' => WorkflowStatus::buildRoute('withdrawals', WorkflowStatus::WITHDRAWAL_APPROVED),
                'icon' => 'bi-box-arrow-right',
                'color' => 'warning',
                'permission' => null // No specific permission defined yet
            ],
            [
                'label' => 'New Request',
                'route' => 'borrowed-tools/create-batch',
                'icon' => IconMapper::MODULE_BORROWED_TOOLS,
                'color' => 'success',
                'permission' => 'borrowed_tools.create'
            ],
            [
                'label' => 'View Inventory',
                'route' => 'assets?status=available',
                'icon' => 'bi-list-ul',
                'color' => 'outline-secondary',
                'permission' => 'assets.view'
            ]
        ];

        // Filter actions based on permissions (DRY principle - using centralized hasPermission())
        $actions = array_filter($allActions, function($action) {
            // If no permission required, always show
            if ($action['permission'] === null) {
                return true;
            }
            // Check permission using centralized function
            return hasPermission($action['permission']);
        });

        // Remove permission key before passing to component (component doesn't need it)
        $actions = array_map(function($action) {
            unset($action['permission']);
            return $action;
        }, $actions);

        include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
        ?>

        <!-- Delivery Schedule -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0" id="schedule-title">
                    <i class="bi bi-calendar-event me-2" aria-hidden="true"></i>Today's Schedule
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Today's schedule using list_group component
                $items = [
                    [
                        'label' => 'Deliveries Expected',
                        'value' => $warehouseData['scheduled_deliveries'] ?? 0,
                        'color' => 'primary',
                        'icon' => IconMapper::WORKFLOW_IN_TRANSIT
                    ],
                    [
                        'label' => 'In Transit',
                        'value' => $warehouseData['in_transit_deliveries'] ?? 0,
                        'color' => 'warning',
                        'icon' => 'bi-box-arrow-in-down'
                    ],
                    [
                        'label' => 'Releases Due',
                        'value' => $warehouseData['pending_releases'] ?? 0,
                        'color' => 'success',
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
        $stats = [
            [
                'icon' => 'bi-box-arrow-in-down',
                'count' => $warehouseData['received_today'] ?? 0,
                'label' => 'Received Today',
                'color' => 'primary'
            ],
            [
                'icon' => 'bi-box-arrow-right',
                'count' => $warehouseData['released_today'] ?? 0,
                'label' => 'Released Today',
                'color' => 'success'
            ],
            [
                'icon' => IconMapper::MODULE_BORROWED_TOOLS,
                'count' => $warehouseData['tools_issued_today'] ?? 0,
                'label' => 'Tools Issued',
                'color' => 'warning'
            ],
            [
                'icon' => 'bi-arrow-counterclockwise',
                'count' => $warehouseData['tools_returned_today'] ?? 0,
                'label' => 'Tools Returned',
                'color' => 'info'
            ]
        ];
        $title = 'Daily Summary';
        $titleIcon = 'bi-graph-up';
        include APP_ROOT . '/views/dashboard/components/stat_cards.php';
        ?>
    </div>
</div>
