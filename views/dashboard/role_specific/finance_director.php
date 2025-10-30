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

<!-- Finance Director Dashboard - ONE GLANCE Design V4.0 (UX-Optimized) -->

<!-- CRITICAL SHORTAGE ALERT (First Priority - Immediate Visibility) -->
<?php
if (!empty($financeData['inventory_by_project_site'])) {
    $projects = $financeData['inventory_by_project_site'];
    include APP_ROOT . '/views/dashboard/role_specific/partials/_critical_shortage_summary.php';
}
?>

<!-- COMPLETE INVENTORY TABLE (One Glance View - All Equipment Types Visible) -->
<?php
if (!empty($financeData['inventory_by_project_site'])) {
    $projects = $financeData['inventory_by_project_site'];
    include APP_ROOT . '/views/dashboard/role_specific/partials/_inventory_table_view.php';
}
?>

<!-- Optional: Collapsible Project Cards (For Detailed View) -->
<?php if (!empty($financeData['inventory_by_project_site'])): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-neutral">
            <div class="card-header">
                <button class="btn btn-link text-decoration-none text-dark w-100 text-start p-0"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#project-cards-view"
                        aria-expanded="false"
                        aria-controls="project-cards-view">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-0">
                                <i class="bi bi-chevron-down me-2" aria-hidden="true"></i>
                                <i class="<?= IconMapper::MODULE_ASSETS ?> me-2" aria-hidden="true"></i>
                                Project-by-Project Details (Optional)
                            </h5>
                            <p class="text-muted mb-0 small mt-1">
                                Click to expand for grouped view by project
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-2 mt-md-0">
                            <span class="badge bg-secondary">
                                <?= count($financeData['inventory_by_project_site']) ?> Active Projects
                            </span>
                        </div>
                    </div>
                </button>
            </div>
            <div class="collapse" id="project-cards-view">
                <div class="card-body">
                    <!-- Key Decision Question -->
                    <div class="alert alert-info d-flex align-items-start mb-3" role="status">
                        <i class="bi bi-info-circle-fill me-2 mt-1" aria-hidden="true"></i>
                        <div>
                            <strong>Decision Support:</strong>
                            When a Project Manager requests equipment, check if other projects have surplus inventory before purchasing new assets.
                            <strong>Critical shortages</strong> (red border) indicate urgent procurement needs.
                        </div>
                    </div>

                    <!-- Project Inventory Cards -->
                    <div role="group" aria-labelledby="inventory-by-project-title">
                        <?php foreach ($financeData['inventory_by_project_site'] as $project): ?>
                            <?php include APP_ROOT . '/views/dashboard/role_specific/partials/_project_inventory_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Financial Approvals -->
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="pending-approvals-title">
                    Pending Financial Approvals
                </h5>
            </div>
            <div class="card-body">
                <div class="row" role="group" aria-labelledby="pending-approvals-title">
                    <?php
                    // Define pending action items - Neutral design (all neutral, not colored)
                    // High-value approvals are routine for Finance Director, not critical
                    $pendingItems = [
                        [
                            'label' => 'High Value Requests',
                            'count' => $financeData['pending_high_value_requests'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('requests', WorkflowStatus::REQUEST_REVIEWED, ['high_value' => 1]),
                            'icon' => IconMapper::MODULE_REQUESTS,
                            'critical' => false
                        ],
                        [
                            'label' => 'High Value Procurement',
                            'count' => $financeData['pending_high_value_procurement'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('procurement-orders', WorkflowStatus::PROCUREMENT_REVIEWED, ['high_value' => 1]),
                            'icon' => IconMapper::MODULE_PROCUREMENT,
                            'critical' => false
                        ],
                        [
                            'label' => 'Transfer Approvals',
                            'count' => $financeData['pending_transfers'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('transfers', WorkflowStatus::TRANSFER_PENDING_APPROVAL),
                            'icon' => IconMapper::MODULE_TRANSFERS,
                            'critical' => false
                        ],
                        [
                            'label' => 'Maintenance Approvals',
                            'count' => $financeData['pending_maintenance_approval'] ?? 0,
                            'route' => 'maintenance?' . http_build_query(['status' => WorkflowStatus::MAINTENANCE_SCHEDULED, 'high_value' => 1]),
                            'icon' => IconMapper::MODULE_MAINTENANCE,
                            'critical' => false
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
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="budget-utilization-title">
                    Project Budget Utilization
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
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="financial-summary-title">Financial Summary</h5>
            </div>
            <div class="card-body">
                <?php
                // Define financial metrics - Neutral design
                $financialMetrics = [
                    [
                        'label' => 'Total Asset Value',
                        'value' => formatCurrency($financeData['total_asset_value'] ?? 0),
                        'critical' => false
                    ],
                    [
                        'label' => 'Average Asset Value',
                        'value' => formatCurrency($financeData['avg_asset_value'] ?? 0),
                        'critical' => false
                    ],
                    [
                        'label' => 'High Value Assets',
                        'value' => $financeData['high_value_assets'] ?? 0,
                        'icon' => IconMapper::FINANCE_HIGH_VALUE,
                        'critical' => false
                    ]
                ];

                // Render as simple display (neutral design)
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
                        <span class="text-muted">
                            <?php if (!empty($financialMetrics[2]['icon'])): ?>
                                <i class="<?= htmlspecialchars($financialMetrics[2]['icon']) ?> me-1 text-muted" aria-hidden="true"></i>
                            <?php endif; ?>
                            <?= $financialMetrics[2]['label'] ?>
                        </span>
                        <span class="badge badge-neutral" role="status">
                            <?= number_format($financialMetrics[2]['value']) ?>
                        </span>
                    </div>
                </div>

                <hr>

                <?php
                // Quick actions - Neutral design
                $title = 'Financial Operations';
                $titleIcon = null;
                $actions = [
                    [
                        'label' => 'Financial Reports',
                        'route' => 'reports/financial',
                        'icon' => 'bi-file-earmark-bar-graph'
                    ],
                    [
                        'label' => 'View High Value Assets',
                        'route' => 'assets?high_value=1',
                        'icon' => IconMapper::ACTION_VIEW
                    ]
                ];
                ?>
                <nav class="d-grid gap-2" aria-label="Financial operations">
                    <?php foreach ($actions as $action): ?>
                    <a href="?route=<?= urlencode($action['route']) ?>"
                       class="btn btn-outline-secondary btn-sm"
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
        // Stats - Neutral design (all routine metrics)
        $incidentCount = $dashboardData['total_incidents'] ?? 0;
        $stats = [
            [
                'icon' => IconMapper::MODULE_ASSETS,
                'count' => $dashboardData['total_assets'] ?? 0,
                'label' => 'Total Assets',
                'critical' => false
            ],
            [
                'icon' => IconMapper::MODULE_PROJECTS,
                'count' => $dashboardData['active_projects'] ?? 0,
                'label' => 'Active Projects',
                'critical' => false
            ],
            [
                'icon' => IconMapper::MODULE_MAINTENANCE,
                'count' => $dashboardData['maintenance_assets'] ?? 0,
                'label' => 'Maintenance',
                'critical' => false
            ],
            [
                'icon' => IconMapper::MODULE_INCIDENTS,
                'count' => $incidentCount,
                'label' => 'Incidents',
                'critical' => $incidentCount > 5 // Critical if more than 5 incidents
            ]
        ];
        $title = 'Overview';
        $columns = 2;
        include APP_ROOT . '/views/dashboard/components/stat_cards.php';
        ?>
    </div>
</div>
