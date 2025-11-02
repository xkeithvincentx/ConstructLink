<?php
/**
 * Project Manager Dashboard
 *
 * Role-specific dashboard for Project Manager with request reviews,
 * project resource management, and equipment verification.
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
$projectData = $dashboardData['role_specific']['project_manager'] ?? [];
?>

<!-- Project Manager Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Project Actions -->
        <div class="card mb-4 card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="pending-project-title">
                    <i class="bi bi-clipboard-check me-2 text-muted" aria-hidden="true"></i>Pending Project Actions
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Define pending action items using WorkflowStatus constants
                $pendingItems = [
                    [
                        'label' => 'Request Reviews',
                        'count' => $projectData['pending_request_reviews'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('requests', WorkflowStatus::REQUEST_SUBMITTED),
                        'icon' => IconMapper::MODULE_REQUESTS,
                        'critical' => false
                    ],
                    [
                        'label' => 'Equipment Verifications',
                        'count' => $dashboardData['borrowed_tools']['pending_verification'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('borrowed-tools', WorkflowStatus::BORROWED_TOOLS_PENDING_VERIFICATION),
                        'icon' => IconMapper::MODULE_BORROWED_TOOLS,
                        'critical' => false
                    ],
                    [
                        'label' => 'Withdrawal Approvals',
                        'count' => $projectData['pending_withdrawal_approvals'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('withdrawals', WorkflowStatus::WITHDRAWAL_PENDING_APPROVAL),
                        'icon' => 'bi-box-arrow-right',
                        'critical' => false
                    ],
                    [
                        'label' => 'Transfer Approvals',
                        'count' => $projectData['pending_transfer_approvals'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('transfers', WorkflowStatus::TRANSFER_PENDING_VERIFICATION),
                        'icon' => IconMapper::MODULE_TRANSFERS,
                        'critical' => false
                    ],
                    [
                        'label' => 'Receipt Confirmations',
                        'count' => $projectData['pending_receipt_confirmations'] ?? 0,
                        'route' => WorkflowStatus::buildRoute('procurement-orders', WorkflowStatus::PROCUREMENT_DELIVERED),
                        'icon' => 'bi-check-circle',
                        'critical' => false
                    ]
                ];
                ?>

                <!-- Alpine.js Enhanced: Filterable Pending Actions -->
                <div x-data="filterableList(<?= htmlspecialchars(json_encode($pendingItems)) ?>)"
                     role="group"
                     aria-labelledby="pending-project-title">

                    <!-- Filter Controls -->
                    <div class="btn-group mb-3 d-flex" role="group" aria-label="Filter pending project actions">
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

        <!-- Project Resource Overview -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="resource-overview-title">
                    <i class="bi bi-pie-chart me-2" aria-hidden="true"></i>Project Resource Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3" id="project-assets-label">Current Project Assets</h6>

                        <div role="region" aria-labelledby="project-assets-label">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Available Assets</span>
                                    <span class="badge badge-neutral" role="status">
                                        <?= number_format($projectData['available_project_assets'] ?? 0) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>In Use Assets</span>
                                    <span class="badge badge-neutral" role="status">
                                        <?= number_format($projectData['in_use_project_assets'] ?? 0) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Total Asset Value</span>
                                    <strong><?= formatCurrency($projectData['project_asset_value'] ?? 0) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <?php
                        // Project management metrics using list_group component
                        $items = [
                            [
                                'label' => 'Managed Projects',
                                'value' => $projectData['managed_projects'] ?? 0,
                                'critical' => false
                            ],
                            [
                                'label' => 'Assigned Projects',
                                'value' => $projectData['assigned_projects'] ?? 0,
                                'critical' => false
                            ],
                            [
                                'label' => 'Pending Investigations',
                                'value' => $projectData['pending_incident_investigations'] ?? 0,
                                'critical' => false
                            ]
                        ];
                        $title = 'Project Management';
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
        $title = 'Project Management';
        $actions = [
            [
                'label' => 'Review Requests',
                'route' => WorkflowStatus::buildRoute('requests', WorkflowStatus::REQUEST_SUBMITTED),
                'icon' => 'bi-clipboard-check',
                'critical' => false
            ],
            [
                'label' => 'Verify Equipment',
                'route' => WorkflowStatus::buildRoute('borrowed-tools', WorkflowStatus::BORROWED_TOOLS_PENDING_VERIFICATION),
                'icon' => IconMapper::MODULE_BORROWED_TOOLS,
                'critical' => false
            ],
            [
                'label' => 'Approve Withdrawals',
                'route' => WorkflowStatus::buildRoute('withdrawals', WorkflowStatus::WITHDRAWAL_PENDING_APPROVAL),
                'icon' => 'bi-check2-square',
                'critical' => false
            ],
            [
                'label' => 'Verify Transfers',
                'route' => WorkflowStatus::buildRoute('transfers', WorkflowStatus::TRANSFER_PENDING_VERIFICATION),
                'icon' => IconMapper::MODULE_TRANSFERS,
                'critical' => false
            ],
            [
                'label' => 'Investigate Incidents',
                'route' => WorkflowStatus::buildRoute('incidents', WorkflowStatus::INCIDENT_PENDING_VERIFICATION),
                'icon' => 'bi-shield-exclamation',
                'critical' => true
            ]
        ];
        include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
        ?>

        <!-- Project Summary -->
        <?php
        $stats = [
            [
                'icon' => IconMapper::MODULE_PROJECTS,
                'count' => $projectData['managed_projects'] ?? 0,
                'label' => 'Managed',
                'critical' => false
            ],
            [
                'icon' => 'bi-person-check',
                'count' => $projectData['assigned_projects'] ?? 0,
                'label' => 'Assigned',
                'critical' => false
            ],
            [
                'icon' => IconMapper::MODULE_ASSETS,
                'count' => $projectData['available_project_assets'] ?? 0,
                'label' => 'Available',
                'critical' => false
            ],
            [
                'icon' => 'bi-gear',
                'count' => $projectData['in_use_project_assets'] ?? 0,
                'label' => 'In Use',
                'critical' => false
            ]
        ];
        $title = 'Project Summary';
        include APP_ROOT . '/views/dashboard/components/stat_cards.php';
        ?>

        <!-- View All Projects Button -->
        <div class="d-grid mb-4">
            <a href="?route=projects" class="btn btn-outline-secondary btn-sm" aria-label="View all projects">
                <i class="<?= IconMapper::ACTION_VIEW ?> me-1" aria-hidden="true"></i>View All Projects
            </a>
        </div>

        <!-- Return Transit Monitoring -->
        <div class="card mb-4 card-neutral">
            <div class="card-header">
                <h5 class="mb-0 text-muted" id="return-transit-title">
                    <i class="<?= IconMapper::WORKFLOW_IN_TRANSIT ?> me-2" aria-hidden="true"></i>Return Transit Monitor
                </h5>
            </div>
            <div class="card-body">
                <?php
                $returnStats = [
                    [
                        'icon' => 'bi-arrow-return-left',
                        'count' => $projectData['returns_in_transit'] ?? 0,
                        'label' => 'In Transit',
                        'critical' => false
                    ],
                    [
                        'icon' => IconMapper::STATUS_WARNING,
                        'count' => $projectData['overdue_return_transits'] ?? 0,
                        'label' => 'Overdue',
                        'critical' => true
                    ],
                    [
                        'icon' => 'bi-clock-history',
                        'count' => $projectData['pending_return_receipts'] ?? 0,
                        'label' => 'To Receive',
                        'critical' => false
                    ],
                    [
                        'icon' => 'bi-calendar-x',
                        'count' => $projectData['overdue_returns'] ?? 0,
                        'label' => 'Overdue Returns',
                        'critical' => true
                    ]
                ];
                ?>

                <div class="row text-center" role="group" aria-labelledby="return-transit-title">
                    <?php foreach ($returnStats as $stat): ?>
                    <div class="col-6 mb-3">
                        <i class="<?= htmlspecialchars($stat['icon']) ?> text-muted fs-3 d-block mb-2" aria-hidden="true"></i>
                        <h6 class="mb-0" aria-live="polite"><?= number_format($stat['count']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($stat['label']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (($projectData['overdue_return_transits'] ?? 0) > 0 || ($projectData['pending_return_receipts'] ?? 0) > 0): ?>
                <div class="alert alert-warning p-2 mb-3" role="alert">
                    <small>
                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                        <strong>Action Required:</strong> You have returns that need attention.
                    </small>
                </div>
                <?php endif; ?>

                <div class="d-grid gap-2">
                    <?php if (($projectData['pending_return_receipts'] ?? 0) > 0): ?>
                    <a href="?route=transfers&return_status=in_return_transit" class="btn btn-outline-secondary btn-sm" aria-label="Receive <?= $projectData['pending_return_receipts'] ?? 0 ?> pending returns">
                        <i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Receive Returns (<?= $projectData['pending_return_receipts'] ?? 0 ?>)
                    </a>
                    <?php endif; ?>
                    <a href="?route=transfers&tab=returns" class="btn btn-outline-secondary btn-sm" aria-label="Monitor all returns">
                        <i class="<?= IconMapper::ACTION_VIEW ?> me-1" aria-hidden="true"></i>Monitor All Returns
                    </a>
                </div>
            </div>
        </div>

        <!-- Today's Tasks -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="today-tasks-title">
                    <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Today's Tasks
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Today's tasks using list_group component with icons
                $items = [
                    [
                        'label' => 'Requests to Review',
                        'value' => $projectData['pending_request_reviews'] ?? 0,
                        'critical' => false,
                        'icon' => 'bi-file-earmark-text'
                    ],
                    [
                        'label' => 'Equipment to Verify',
                        'value' => $dashboardData['borrowed_tools']['pending_verification'] ?? 0,
                        'critical' => false,
                        'icon' => IconMapper::MODULE_BORROWED_TOOLS
                    ],
                    [
                        'label' => 'Withdrawals to Approve',
                        'value' => $projectData['pending_withdrawal_approvals'] ?? 0,
                        'critical' => false,
                        'icon' => 'bi-box-arrow-right'
                    ],
                    [
                        'label' => 'Transfers to Verify',
                        'value' => $projectData['pending_transfer_approvals'] ?? 0,
                        'critical' => false,
                        'icon' => IconMapper::MODULE_TRANSFERS
                    ],
                    [
                        'label' => 'Return Transits',
                        'value' => $projectData['returns_in_transit'] ?? 0,
                        'critical' => false,
                        'icon' => IconMapper::WORKFLOW_IN_TRANSIT
                    ],
                    [
                        'label' => 'Incidents to Investigate',
                        'value' => $projectData['pending_incident_investigations'] ?? 0,
                        'critical' => true,
                        'icon' => 'bi-shield-exclamation'
                    ]
                ];
                include APP_ROOT . '/views/dashboard/components/list_group.php';
                ?>
            </div>
        </div>
    </div>
</div>
