<?php
/**
 * Finance Director Dashboard
 *
 * Role-specific dashboard for Finance Director with high-value approvals,
 * budget monitoring, and financial metrics.
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
$financeData = $dashboardData['role_specific']['finance'] ?? [];
?>

<!-- Finance Director Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Financial Approvals -->
        <div class="card mb-4 card-accent-danger">
            <div class="card-header">
                <h5 class="mb-0" id="pending-approvals-title">
                    <i class="<?= IconMapper::PENDING_ACTIONS ?> me-2 text-danger" aria-hidden="true"></i>Pending Financial Approvals
                </h5>
            </div>
            <div class="card-body">
                <div class="row" role="group" aria-labelledby="pending-approvals-title">
                    <?php
                    // Define pending action items using WorkflowStatus constants
                    $pendingItems = [
                        [
                            'label' => 'High Value Requests',
                            'count' => $financeData['pending_high_value_requests'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('requests', WorkflowStatus::REQUEST_REVIEWED, ['high_value' => 1]),
                            'icon' => IconMapper::MODULE_REQUESTS,
                            'color' => 'primary'
                        ],
                        [
                            'label' => 'High Value Procurement',
                            'count' => $financeData['pending_high_value_procurement'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('procurement-orders', WorkflowStatus::PROCUREMENT_REVIEWED, ['high_value' => 1]),
                            'icon' => IconMapper::MODULE_PROCUREMENT,
                            'color' => 'warning'
                        ],
                        [
                            'label' => 'Transfer Approvals',
                            'count' => $financeData['pending_transfers'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('transfers', WorkflowStatus::TRANSFER_PENDING_APPROVAL),
                            'icon' => IconMapper::MODULE_TRANSFERS,
                            'color' => 'info'
                        ],
                        [
                            'label' => 'Maintenance Approvals',
                            'count' => $financeData['pending_maintenance_approval'] ?? 0,
                            'route' => 'maintenance?' . http_build_query(['status' => WorkflowStatus::MAINTENANCE_SCHEDULED, 'high_value' => 1]),
                            'icon' => IconMapper::MODULE_MAINTENANCE,
                            'color' => 'secondary'
                        ]
                    ];

                    // Render each pending action card using component
                    foreach ($pendingItems as $item) {
                        include APP_ROOT . '/views/dashboard/components/pending_action_card.php';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Budget Utilization -->
        <?php if (!empty($dashboardData['budget_utilization'])): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="budget-utilization-title">
                    <i class="bi bi-graph-up-arrow me-2" aria-hidden="true"></i>Project Budget Utilization
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm" aria-labelledby="budget-utilization-title">
                        <thead>
                            <tr>
                                <th scope="col">Project</th>
                                <th scope="col" class="text-end">Budget</th>
                                <th scope="col" class="text-end">Utilized</th>
                                <th scope="col" class="text-center">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboardData['budget_utilization'] as $index => $project): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['project_name']) ?></td>
                                <td class="text-end"><?= formatCurrency($project['budget']) ?></td>
                                <td class="text-end"><?= formatCurrency($project['utilized']) ?></td>
                                <td>
                                    <?php
                                    // Use progress_bar component with DashboardThresholds
                                    $label = htmlspecialchars($project['project_name']) . ' Budget';
                                    $current = $project['utilized'];
                                    $total = $project['budget'];
                                    $config = [
                                        'thresholds' => DashboardThresholds::getThresholds('budget'),
                                        'showPercentage' => true,
                                        'showCount' => false,
                                        'height' => 'progress-lg'
                                    ];
                                    include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Financial Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0" id="financial-summary-title">
                    <i class="<?= IconMapper::FINANCE_CASH ?> me-2" aria-hidden="true"></i>Financial Summary
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Define financial metrics for list_group component
                $financialMetrics = [
                    [
                        'label' => 'Total Asset Value',
                        'value' => formatCurrency($financeData['total_asset_value'] ?? 0),
                        'color' => 'primary'
                    ],
                    [
                        'label' => 'Average Asset Value',
                        'value' => formatCurrency($financeData['avg_asset_value'] ?? 0),
                        'color' => 'info'
                    ],
                    [
                        'label' => 'High Value Assets',
                        'value' => $financeData['high_value_assets'] ?? 0,
                        'color' => 'warning',
                        'icon' => IconMapper::FINANCE_HIGH_VALUE
                    ]
                ];

                // Render first 2 items as simple display
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted"><?= $financialMetrics[0]['label'] ?></span>
                        <strong><?= $financialMetrics[0]['value'] ?></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted"><?= $financialMetrics[1]['label'] ?></span>
                        <strong><?= $financialMetrics[1]['value'] ?></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted"><?= $financialMetrics[2]['label'] ?></span>
                        <span class="badge bg-<?= $financialMetrics[2]['color'] ?>" role="status">
                            <?= number_format($financialMetrics[2]['value']) ?>
                        </span>
                    </div>
                </div>

                <hr>

                <?php
                // Quick actions using component
                $title = 'Financial Operations';
                $titleIcon = IconMapper::QUICK_ACTIONS;
                $actions = [
                    [
                        'label' => 'Financial Reports',
                        'route' => 'reports/financial',
                        'icon' => 'bi-file-earmark-bar-graph',
                        'color' => 'primary'
                    ],
                    [
                        'label' => 'View High Value Assets',
                        'route' => 'assets?high_value=1',
                        'icon' => IconMapper::ACTION_VIEW,
                        'color' => 'outline-warning'
                    ]
                ];
                ?>
                <nav class="d-grid gap-2" aria-label="Financial operations">
                    <?php foreach ($actions as $action): ?>
                    <a href="?route=<?= urlencode($action['route']) ?>"
                       class="btn btn-<?= htmlspecialchars($action['color']) ?> btn-sm"
                       aria-label="<?= htmlspecialchars($action['label']) ?>">
                        <i class="<?= htmlspecialchars($action['icon']) ?> me-1" aria-hidden="true"></i>
                        <?= htmlspecialchars($action['label']) ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php
        $stats = [
            [
                'icon' => IconMapper::MODULE_ASSETS,
                'count' => $dashboardData['total_assets'] ?? 0,
                'label' => 'Total Assets',
                'color' => 'primary'
            ],
            [
                'icon' => IconMapper::MODULE_PROJECTS,
                'count' => $dashboardData['active_projects'] ?? 0,
                'label' => 'Active Projects',
                'color' => 'success'
            ],
            [
                'icon' => IconMapper::MODULE_MAINTENANCE,
                'count' => $dashboardData['maintenance_assets'] ?? 0,
                'label' => 'Maintenance',
                'color' => 'warning'
            ],
            [
                'icon' => IconMapper::MODULE_INCIDENTS,
                'count' => $dashboardData['total_incidents'] ?? 0,
                'label' => 'Incidents',
                'color' => 'danger'
            ]
        ];
        include APP_ROOT . '/views/dashboard/components/stat_cards.php';
        ?>
    </div>
</div>
