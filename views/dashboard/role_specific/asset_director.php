<?php
/**
 * Asset Director Dashboard
 *
 * Role-specific dashboard for Asset Director with pending asset actions,
 * asset health monitoring, and incident management.
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
$assetData = $dashboardData['role_specific']['asset_director'] ?? [];
?>

<!-- Asset Director Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Asset Actions -->
        <div class="card mb-4 card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="pending-actions-title">
                    <i class="<?= IconMapper::PENDING_ACTIONS ?> me-2 text-muted" aria-hidden="true"></i>Pending Asset Actions
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Define pending action items using WorkflowStatus constants
                $pendingItems = [
                    [
                        'label' => 'Procurement Verification',
                        'count' => $assetData['pending_procurement_verification'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('procurement-orders', WorkflowStatus::PROCUREMENT_PENDING),
                        'icon' => IconMapper::MODULE_PROCUREMENT,
                        'critical' => false
                    ],
                    [
                        'label' => 'Equipment Approvals',
                        'count' => $dashboardData['borrowed_tools']['pending_approval'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('borrowed-tools', WorkflowStatus::BORROWED_TOOLS_PENDING_APPROVAL),
                        'icon' => 'bi-clipboard-check',
                        'critical' => false
                    ],
                    [
                        'label' => 'Delivery Discrepancies',
                        'count' => $assetData['pending_discrepancies'] ?? 0,
                        'route' => 'delivery-tracking?' . http_build_query(['status' => 'Discrepancy Reported']),
                        'icon' => IconMapper::MODULE_TRANSFERS,
                        'critical' => true
                    ],
                    [
                        'label' => 'Incident Resolution',
                        'count' => $assetData['pending_incident_resolution'] ?? 0,
                        'route' => 'incidents?' . http_build_query(['status' => WorkflowStatus::INCIDENT_PENDING_AUTHORIZATION]),
                        'icon' => IconMapper::MODULE_INCIDENTS,
                        'critical' => true
                    ],
                    [
                        'label' => 'Maintenance Authorization',
                        'count' => $assetData['pending_maintenance_authorization'] ?? 0,
                        'route' => 'maintenance?' . http_build_query(['status' => WorkflowStatus::MAINTENANCE_SCHEDULED]),
                        'icon' => IconMapper::MODULE_MAINTENANCE,
                        'critical' => false
                    ]
                ];
                ?>

                <!-- Alpine.js Enhanced: Filterable Pending Actions -->
                <div x-data="filterableList(<?= htmlspecialchars(json_encode($pendingItems)) ?>)"
                     role="group"
                     aria-labelledby="pending-actions-title">

                    <!-- Filter Controls -->
                    <div class="btn-group mb-3 d-flex" role="group" aria-label="Filter pending actions">
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
                                :class="filter === 'critical' ? 'btn-danger' : 'btn-outline-secondary'"
                                @click="setFilter('critical')">
                            <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                            Critical (<span x-text="criticalCount"></span>)
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
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i>Review Now
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

        <!-- Asset Health Overview -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="asset-health-title">
                    <i class="bi bi-heart-pulse me-2" aria-hidden="true"></i>Asset Health Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row" role="group" aria-labelledby="asset-health-title">
                    <div class="col-md-6">
                        <h6 class="text-muted" id="asset-status-title">Asset Status Distribution</h6>
                        <div class="mb-2" role="region" aria-labelledby="asset-status-title">
                            <?php
                            // Available Assets Progress Bar
                            $label = 'Available';
                            $current = $dashboardData['available_assets'] ?? 0;
                            $total = max(($dashboardData['total_assets'] ?? 1), 1);
                            $config = [
                                'showPercentage' => false,
                                'showCount' => true,
                                'height' => 'progress-sm',
                                'color' => 'success'
                            ];
                            include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                            ?>
                        </div>
                        <div class="mb-2">
                            <?php
                            // In Use Assets Progress Bar
                            $label = 'In Use';
                            $current = $dashboardData['in_use_assets'] ?? 0;
                            $total = max(($dashboardData['total_assets'] ?? 1), 1);
                            $config = [
                                'showPercentage' => false,
                                'showCount' => true,
                                'height' => 'progress-sm',
                                'color' => 'warning'
                            ];
                            include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                            ?>
                        </div>
                        <div class="mb-2">
                            <?php
                            // Under Maintenance Progress Bar
                            $label = 'Under Maintenance';
                            $current = $assetData['assets_under_maintenance'] ?? 0;
                            $total = max(($dashboardData['total_assets'] ?? 1), 1);
                            $config = [
                                'showPercentage' => false,
                                'showCount' => true,
                                'height' => 'progress-sm',
                                'color' => 'info'
                            ];
                            include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                            ?>
                        </div>
                        <div class="mb-2">
                            <?php
                            // Retired/Disposed Progress Bar
                            $label = 'Retired/Disposed';
                            $current = ($assetData['retired_assets'] ?? 0) + ($assetData['disposed_assets'] ?? 0);
                            $total = max(($dashboardData['total_assets'] ?? 1), 1);
                            $config = [
                                'showPercentage' => false,
                                'showCount' => true,
                                'height' => 'progress-sm',
                                'color' => 'secondary'
                            ];
                            include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted" id="key-metrics-title">Key Metrics</h6>
                        <div class="list-group list-group-flush" role="list" aria-labelledby="key-metrics-title">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center" role="listitem">
                                <span>Asset Utilization Rate</span>
                                <span class="badge badge-neutral rounded-pill" role="status">
                                    <?= $assetData['utilization_rate'] ?? 0 ?>%
                                </span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center" role="listitem">
                                <span>Categories in Use</span>
                                <span class="badge badge-neutral rounded-pill" role="status"><?= $assetData['categories_in_use'] ?? 0 ?></span>
                            </div>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center" role="listitem">
                                <span>Total Asset Value</span>
                                <strong><?= formatCurrency($dashboardData['total_asset_value'] ?? 0) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        <?php
        $title = 'Asset Management';
        $actions = [
            [
                'label' => 'Approve Equipment',
                'route' => WorkflowStatus::buildRoute('borrowed-tools', WorkflowStatus::BORROWED_TOOLS_PENDING_APPROVAL),
                'icon' => 'bi-clipboard-check',
                'critical' => false
            ],
            [
                'label' => 'Add New Asset',
                'route' => 'assets/create',
                'icon' => IconMapper::ACTION_CREATE,
                'critical' => false
            ],
            [
                'label' => 'QR Scanner',
                'route' => 'assets/scanner',
                'icon' => 'bi-qr-code-scan',
                'critical' => false
            ],
            [
                'label' => 'Schedule Maintenance',
                'route' => 'maintenance/create',
                'icon' => IconMapper::MODULE_MAINTENANCE,
                'critical' => false
            ],
            [
                'label' => 'Manage Categories',
                'route' => 'categories',
                'icon' => 'bi-tags',
                'critical' => false
            ]
        ];
        include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
        ?>

        <!-- Return Transit Monitoring -->
        <div class="card mb-4 card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="return-transit-title">
                    <i class="bi bi-truck me-2 text-muted" aria-hidden="true"></i>Return Transits
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center" role="region" aria-labelledby="return-transit-title">
                    <div class="col-12 mb-2">
                        <i class="bi bi-arrow-return-left text-muted fs-2" aria-hidden="true"></i>
                        <h5 class="mb-0" aria-live="polite"><?= number_format($assetData['returns_in_transit'] ?? 0) ?></h5>
                        <small class="text-muted">Assets in Return Transit</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <span class="badge badge-neutral rounded-pill" role="status" aria-label="<?= $assetData['overdue_return_transits'] ?? 0 ?> overdue returns">
                                <?= $assetData['overdue_return_transits'] ?? 0 ?>
                            </span>
                            <br><small class="text-muted">Overdue</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <span class="badge badge-neutral rounded-pill" role="status" aria-label="<?= $assetData['pending_return_receipts'] ?? 0 ?> returns to receive">
                                <?= $assetData['pending_return_receipts'] ?? 0 ?>
                            </span>
                            <br><small class="text-muted">To Receive</small>
                        </div>
                    </div>
                </div>

                <?php if (($assetData['overdue_return_transits'] ?? 0) > 0): ?>
                <div class="alert alert-danger p-2 mt-3 mb-2" role="alert">
                    <small>
                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                        <strong><?= $assetData['overdue_return_transits'] ?> return(s)</strong> stuck in transit!
                    </small>
                </div>
                <?php endif; ?>

                <div class="d-grid">
                    <a href="?route=<?= urlencode('transfers&tab=returns') ?>"
                       class="btn btn-outline-secondary btn-sm"
                       aria-label="Monitor return transits">
                        <i class="bi bi-eye" aria-hidden="true"></i> Monitor Returns
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Incidents -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="recent-incidents-title">
                    <i class="<?= IconMapper::MODULE_INCIDENTS ?> me-2" aria-hidden="true"></i>Recent Incidents
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Use list_group component for incidents
                $items = [];

                if (($dashboardData['lost_assets'] ?? 0) > 0) {
                    $items[] = [
                        'label' => 'Lost Assets',
                        'value' => $dashboardData['lost_assets'],
                        'critical' => true,
                        'icon' => 'bi-geo-alt'
                    ];
                }

                if (($dashboardData['damaged_assets'] ?? 0) > 0) {
                    $items[] = [
                        'label' => 'Damaged Assets',
                        'value' => $dashboardData['damaged_assets'],
                        'critical' => true,
                        'icon' => 'bi-hammer'
                    ];
                }

                if (($dashboardData['stolen_assets'] ?? 0) > 0) {
                    $items[] = [
                        'label' => 'Stolen Assets',
                        'value' => $dashboardData['stolen_assets'],
                        'critical' => true,
                        'icon' => 'bi-shield-x'
                    ];
                }

                if (empty($items)) {
                    echo '<p class="text-muted text-center mb-0" role="status">No recent incidents</p>';
                } else {
                    include APP_ROOT . '/views/dashboard/components/list_group.php';
                }
                ?>
                <div class="mt-3 d-grid">
                    <a href="?route=incidents"
                       class="btn btn-outline-secondary btn-sm"
                       aria-label="View all incidents">
                        View All Incidents
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
