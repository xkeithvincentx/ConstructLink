<?php
/**
 * Finance Director Dashboard - REDESIGNED for Executive Decision-Making
 *
 * Role-specific dashboard for Finance Director with granular inventory visibility,
 * high-value approvals, budget monitoring, and financial metrics.
 *
 * REDESIGN GOALS:
 * - Answer "Do we have enough drills?" not just "Do we have enough power tools?"
 * - Eliminate low-value cards (Quick Stats) and replace with actionable insights
 * - Prioritize by urgency (critical equipment shortages first)
 * - Support procurement vs. transfer decisions with project distribution data
 *
 * @package ConstructLink
 * @subpackage Dashboard - Role Specific
 * @version 3.0 - Executive Redesign
 * @since 2025-10-30
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
$inventoryByEquipmentType = $financeData['inventory_by_equipment_type'] ?? [];
?>

<!-- Finance Director Dashboard - Executive Redesign V3.0 -->

<!-- REDESIGNED: Granular Inventory Overview by Equipment Type -->
<?php if (!empty($inventoryByEquipmentType)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-neutral">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="inventory-equipment-types-title">
                    <i class="<?= IconMapper::MODULE_ASSETS ?> me-2" aria-hidden="true"></i>
                    Inventory by Equipment Type
                </h5>
                <small class="text-muted">Granular view for procurement decisions</small>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    Drill down to equipment type level (e.g., Drills, Saws, Grinders) to identify specific shortages.
                    Expand categories to see detailed breakdowns and project distribution.
                </p>

                <div class="row g-3" role="group" aria-labelledby="inventory-equipment-types-title">
                    <?php foreach ($inventoryByEquipmentType as $category): ?>
                        <?php
                        $categoryId = $category['category_id'];
                        $categoryName = htmlspecialchars($category['category_name']);
                        $equipmentTypes = $category['equipment_types'] ?? [];
                        $urgency = $category['urgency'] ?? 'normal';
                        $urgencyLabel = htmlspecialchars($category['urgency_label'] ?? 'Unknown');
                        $totalCount = (int)$category['total_count'];
                        $availableCount = (int)$category['available_count'];
                        $inUseCount = (int)$category['in_use_count'];
                        $maintenanceCount = (int)$category['maintenance_count'];
                        $availabilityPercentage = (float)$category['availability_percentage'];

                        // Determine card styling based on category urgency
                        $cardClass = 'inventory-category-card';
                        $badgeClass = 'badge-neutral';
                        $urgencyIcon = 'bi-info-circle';

                        if ($urgency === 'critical') {
                            $cardClass .= ' border-danger';
                            $badgeClass = 'bg-danger text-white';
                            $urgencyIcon = 'bi-exclamation-triangle-fill';
                        } elseif ($urgency === 'warning') {
                            $cardClass .= ' border-warning';
                            $badgeClass = 'bg-warning text-dark';
                            $urgencyIcon = 'bi-exclamation-circle-fill';
                        }

                        $uniqueId = 'category-equipment-types-' . $categoryId;
                        $typesCount = count($equipmentTypes);
                        ?>

                        <div class="col-12 col-xl-6">
                            <div class="card <?= $cardClass ?> h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <!-- Category Name -->
                                    <div>
                                        <h6 class="mb-1 fw-bold" id="<?= $uniqueId ?>-label">
                                            <?= $categoryName ?>
                                        </h6>
                                        <span class="badge badge-neutral rounded-pill me-1">
                                            <?= $category['is_consumable'] ? 'Consumable' : 'Equipment' ?>
                                        </span>
                                        <span class="badge <?= $badgeClass ?> rounded-pill" role="status">
                                            <i class="<?= $urgencyIcon ?> me-1" aria-hidden="true"></i>
                                            <?= $urgencyLabel ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <!-- Category Summary Metrics -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-3">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="fs-5 fw-bold text-success">
                                                    <?= $availableCount ?>
                                                </div>
                                                <small class="text-muted">Available</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="fs-5 fw-bold text-primary">
                                                    <?= $inUseCount ?>
                                                </div>
                                                <small class="text-muted">In Use</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="fs-5 fw-bold text-warning">
                                                    <?= $maintenanceCount ?>
                                                </div>
                                                <small class="text-muted">Maint.</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="fs-5 fw-bold">
                                                    <?= $totalCount ?>
                                                </div>
                                                <small class="text-muted">Total</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Expand/Collapse Button for Equipment Types -->
                                    <div class="d-grid mb-3">
                                        <button class="btn btn-outline-secondary btn-sm collapsed"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#<?= $uniqueId ?>"
                                                aria-expanded="false"
                                                aria-controls="<?= $uniqueId ?>">
                                            <i class="bi bi-chevron-down me-1" aria-hidden="true"></i>
                                            <strong>Show Equipment Types (<?= $typesCount ?>)</strong>
                                        </button>
                                    </div>

                                    <!-- Collapsible Equipment Types Section -->
                                    <div class="collapse" id="<?= $uniqueId ?>">
                                        <div class="border-top pt-3">
                                            <h6 class="text-muted mb-3">
                                                <i class="bi bi-tools me-1" aria-hidden="true"></i>
                                                Equipment Type Breakdown
                                            </h6>

                                            <?php if (!empty($equipmentTypes)): ?>
                                                <?php foreach ($equipmentTypes as $equipmentType): ?>
                                                    <?php
                                                    // Include equipment type card component
                                                    include APP_ROOT . '/views/dashboard/role_specific/partials/_equipment_type_card.php';
                                                    ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="alert alert-info mb-0" role="status">
                                                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                                                    No equipment types found for this category.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($inventoryByEquipmentType)): ?>
                    <div class="alert alert-info mb-0" role="status">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                        No inventory data available. Assets will appear here once they are added to the system and linked to equipment types.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Pending Financial Approvals (KEPT - High Value) -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="pending-approvals-title">
                    <i class="bi bi-file-earmark-check me-2" aria-hidden="true"></i>
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

        <!-- Budget Utilization (CONDITIONAL - Kept for now, verify with stakeholder) -->
        <?php if (!empty($dashboardData['budget_utilization'])): ?>
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="budget-utilization-title">
                    <i class="bi bi-pie-chart me-2" aria-hidden="true"></i>
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
        <!-- Financial Summary (ENHANCED with trends placeholder) -->
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="financial-summary-title">
                    <i class="bi bi-cash-stack me-2" aria-hidden="true"></i>
                    Financial Summary
                </h5>
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

        <!-- ELIMINATED: Quick Stats Card (Low Value for Finance Director) -->
        <!-- Replaced with expanded inventory visibility above -->
    </div>
</div>

<style>
/* Smooth chevron rotation for equipment type expand buttons */
[data-bs-toggle="collapse"] .bi-chevron-down {
    transition: transform 0.2s ease-in-out;
}
[data-bs-toggle="collapse"]:not(.collapsed) .bi-chevron-down {
    transform: rotate(180deg);
}

/* Category card styling */
.inventory-category-card {
    transition: box-shadow 0.2s ease-in-out;
}
.inventory-category-card:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

/* Mobile responsiveness */
@media (max-width: 767.98px) {
    /* Stack metrics vertically on very small screens if needed */
    .inventory-category-card .card-header h6 {
        font-size: 1rem;
    }

    .inventory-category-card .badge {
        font-size: 0.75rem;
    }
}
</style>

<script>
// Optional: Track equipment type expansions for analytics
document.addEventListener('DOMContentLoaded', function() {
    const equipmentTypeToggles = document.querySelectorAll('[data-bs-toggle="collapse"][aria-controls^="category-equipment-types-"]');

    equipmentTypeToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const categoryId = this.getAttribute('aria-controls').replace('category-equipment-types-', '');
            const isExpanded = !this.classList.contains('collapsed');

            // Optional: Send analytics event
            console.log('Equipment type expansion:', {
                categoryId: categoryId,
                expanded: isExpanded
            });
        });
    });
});
</script>
