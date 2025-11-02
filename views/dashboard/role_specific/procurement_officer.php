<?php
/**
 * Procurement Officer Dashboard
 *
 * Role-specific dashboard for Procurement Officer with purchase order management,
 * vendor tracking, and delivery performance monitoring.
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
$procurementData = $dashboardData['role_specific']['procurement'] ?? [];
?>

<!-- Procurement Officer Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Procurement Actions -->
        <div class="card mb-4 card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="pending-procurement-title">
                    <i class="<?= IconMapper::MODULE_PROCUREMENT ?> me-2 text-muted" aria-hidden="true"></i>Pending Procurement Actions
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Define pending action items using WorkflowStatus constants
                $pendingItems = [
                    [
                        'label' => 'Approved Requests (Pending PO)',
                        'count' => $procurementData['approved_requests_pending_po'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('requests', WorkflowStatus::REQUEST_APPROVED),
                        'icon' => 'bi-clipboard-check',
                        'critical' => false
                    ],
                    [
                        'label' => 'Draft Orders',
                        'count' => $procurementData['draft_orders'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('procurement-orders', WorkflowStatus::PROCUREMENT_DRAFT),
                        'icon' => 'bi-file-earmark-text',
                        'critical' => false
                    ],
                    [
                        'label' => 'Pending Delivery',
                        'count' => $procurementData['pending_delivery'] ?? 0,
                        'route' => 'procurement-orders?' . http_build_query(['delivery_status' => WorkflowStatus::DELIVERY_PENDING]),
                        'icon' => IconMapper::WORKFLOW_IN_TRANSIT,
                        'critical' => false
                    ],
                    [
                        'label' => 'Recent POs (30 days)',
                        'count' => $procurementData['recent_po_count'] ?? 0,
                        'route' => 'procurement-orders?recent=30',
                        'icon' => 'bi-calendar-check',
                        'critical' => false
                    ]
                ];
                ?>

                <!-- Alpine.js Enhanced: Filterable Pending Actions -->
                <div x-data="filterableList(<?= htmlspecialchars(json_encode($pendingItems)) ?>)"
                     role="group"
                     aria-labelledby="pending-procurement-title">

                    <!-- Filter Controls -->
                    <div class="btn-group mb-3 d-flex" role="group" aria-label="Filter pending procurement actions">
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

        <!-- Delivery Performance -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="delivery-performance-title">
                    <i class="<?= IconMapper::MODULE_DASHBOARD ?> me-2" aria-hidden="true"></i>Delivery Performance
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3" id="delivery-metrics-label">Delivery Metrics</h6>

                        <?php
                        // Calculate delivery performance with proper defaults
                        $onTimeDeliveries = $procurementData['on_time_deliveries'] ?? 0;
                        $totalDeliveries = max($procurementData['total_deliveries'] ?? 1, 1);
                        $onTimePercentage = round(($onTimeDeliveries / $totalDeliveries) * 100, 1);

                        // Use DashboardThresholds for color determination
                        $deliveryColor = DashboardThresholds::getDeliveryPerformanceColor($onTimePercentage);
                        ?>

                        <div class="mb-3" role="region" aria-labelledby="delivery-metrics-label">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>On-Time Deliveries</span>
                                <span class="badge bg-<?= $deliveryColor ?>" role="status" aria-label="<?= $onTimePercentage ?>% on-time delivery rate">
                                    <?= $onTimePercentage ?>%
                                </span>
                            </div>

                            <?php
                            // Use progress bar component
                            $label = 'On-Time Deliveries';
                            $current = $onTimeDeliveries;
                            $total = $totalDeliveries;
                            $config = [
                                'color' => $deliveryColor,
                                'showPercentage' => false,
                                'showCount' => true,
                                'height' => 'progress-lg'
                            ];
                            include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                            ?>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Average Delivery Variance</span>
                                <span class="text-muted">
                                    <?= ($procurementData['avg_delivery_variance'] ?? 0) > 0 ? '+' : '' ?><?= number_format($procurementData['avg_delivery_variance'] ?? 0) ?> days
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <?php
                        // Vendor management list using component
                        $items = [
                            [
                                'label' => 'Active Vendors',
                                'value' => $procurementData['active_vendors'] ?? 0,
                                'critical' => false
                            ],
                            [
                                'label' => 'Preferred Vendors',
                                'value' => $procurementData['preferred_vendors'] ?? 0,
                                'critical' => false,
                                'icon' => 'bi-star-fill'
                            ],
                            [
                                'label' => 'Active Makers',
                                'value' => $procurementData['active_makers'] ?? 0,
                                'critical' => false,
                                'icon' => 'bi-factory'
                            ]
                        ];
                        $title = 'Vendor Management';
                        include APP_ROOT . '/views/dashboard/components/list_group.php';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        <?php
        $title = 'Procurement Operations';
        $actions = [
            [
                'label' => 'Create Purchase Order',
                'route' => 'procurement-orders/create',
                'icon' => IconMapper::ACTION_CREATE,
                'critical' => false
            ],
            [
                'label' => 'Process Approved Requests',
                'route' => WorkflowStatus::buildRoute('requests', WorkflowStatus::REQUEST_APPROVED),
                'icon' => 'bi-clipboard-check',
                'critical' => false
            ],
            [
                'label' => 'Manage Vendors',
                'route' => 'vendors',
                'icon' => 'bi-building',
                'critical' => false
            ],
            [
                'label' => 'Track Deliveries',
                'route' => 'procurement-orders?delivery=tracking',
                'icon' => IconMapper::WORKFLOW_IN_TRANSIT,
                'critical' => false
            ]
        ];
        include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
        ?>

        <!-- Vendor Quick Stats -->
        <?php
        $stats = [
            [
                'icon' => 'bi-building',
                'count' => $procurementData['active_vendors'] ?? 0,
                'label' => 'Active Vendors',
                'critical' => false
            ],
            [
                'icon' => 'bi-star-fill',
                'count' => $procurementData['preferred_vendors'] ?? 0,
                'label' => 'Preferred',
                'critical' => false
            ],
            [
                'icon' => 'bi-factory',
                'count' => $procurementData['active_makers'] ?? 0,
                'label' => 'Makers',
                'critical' => false
            ],
            [
                'icon' => IconMapper::MODULE_PROCUREMENT,
                'count' => $procurementData['recent_po_count'] ?? 0,
                'label' => 'Recent POs',
                'critical' => false
            ]
        ];
        $title = 'Vendor Overview';
        include APP_ROOT . '/views/dashboard/components/stat_cards.php';
        ?>

        <!-- Add Vendor Button -->
        <div class="d-grid mb-4">
            <a href="?route=vendors/create" class="btn btn-outline-secondary btn-sm" aria-label="Add new vendor">
                <i class="<?= IconMapper::ACTION_CREATE ?> me-1" aria-hidden="true"></i>Add Vendor
            </a>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="recent-activity-title">
                    <i class="<?= IconMapper::RECENT_ACTIVITY ?> me-2" aria-hidden="true"></i>Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Recent activity metrics using list_group component
                $items = [
                    [
                        'label' => 'POs Created This Week',
                        'value' => $procurementData['pos_this_week'] ?? 0,
                        'critical' => false
                    ],
                    [
                        'label' => 'Deliveries Scheduled',
                        'value' => $procurementData['scheduled_deliveries'] ?? 0,
                        'critical' => false
                    ],
                    [
                        'label' => 'Vendors Added',
                        'value' => $procurementData['vendors_added'] ?? 0,
                        'critical' => false
                    ]
                ];
                include APP_ROOT . '/views/dashboard/components/list_group.php';
                ?>

                <div class="mt-3 d-grid">
                    <a href="?route=procurement-orders" class="btn btn-outline-secondary btn-sm" aria-label="View all procurement orders">
                        <i class="<?= IconMapper::ACTION_VIEW ?> me-1" aria-hidden="true"></i>View All Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
