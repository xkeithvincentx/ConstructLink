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

                <!-- Alpine.js Enhanced: Expand/Collapse All Controls -->
                <div class="mb-4 d-flex gap-2" role="group" aria-label="Category expansion controls">
                    <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            @click="expandAll()"
                            aria-label="Expand all equipment type categories">
                        <i class="bi bi-arrows-expand me-1" aria-hidden="true"></i>
                        Expand All
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            @click="collapseAll()"
                            aria-label="Collapse all equipment type categories">
                        <i class="bi bi-arrows-collapse me-1" aria-hidden="true"></i>
                        Collapse All
                    </button>
                </div>

                <div class="row g-3"
                     role="group"
                     aria-labelledby="inventory-equipment-types-title"
                     x-data="{
                         openCategories: {},
                         toggleCategory(id) {
                             this.openCategories[id] = !this.openCategories[id];
                         },
                         expandAll() {
                             <?php foreach ($inventoryByEquipmentType as $cat): ?>
                             this.openCategories[<?= $cat['category_id'] ?>] = true;
                             <?php endforeach; ?>
                         },
                         collapseAll() {
                             this.openCategories = {};
                         }
                     }">
                    <?php foreach ($inventoryByEquipmentType as $category): ?>
                        <?php
                        $categoryId = $category['category_id'];
                        $categoryName = htmlspecialchars($category['category_name']);
                        $equipmentTypes = $category['equipment_types'] ?? [];
                        $totalCount = (int)$category['total_count'];
                        $availableCount = (int)$category['available_count'];
                        $inUseCount = (int)$category['in_use_count'];
                        $maintenanceCount = (int)$category['maintenance_count'];

                        $uniqueId = 'category-equipment-types-' . $categoryId;
                        $typesCount = count($equipmentTypes);
                        ?>

                        <div class="col-12 col-xl-6">
                            <div class="card inventory-category-card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0 fw-bold" id="<?= $uniqueId ?>-label">
                                        <?= $categoryName ?>
                                    </h6>
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

                                    <!-- Alpine.js Enhanced: Expand/Collapse Button for Equipment Types -->
                                    <div class="d-grid mb-3" x-data="{ categoryId: <?= $categoryId ?> }">
                                        <button class="btn btn-outline-secondary btn-sm"
                                                type="button"
                                                @click="toggleCategory(categoryId)"
                                                :aria-expanded="openCategories[categoryId] ? 'true' : 'false'"
                                                aria-controls="<?= $uniqueId ?>">
                                            <i class="bi me-1"
                                               :class="openCategories[categoryId] ? 'bi-chevron-up' : 'bi-chevron-down'"
                                               aria-hidden="true"></i>
                                            <strong x-text="openCategories[categoryId] ? 'Hide' : 'Show'"></strong>
                                            <strong> Project Distribution by Equipment Type (<?= $typesCount ?>)</strong>
                                        </button>
                                    </div>

                                    <!-- Alpine.js Enhanced: Collapsible Equipment Types Section -->
                                    <div x-show="openCategories[<?= $categoryId ?>]"
                                         x-transition:enter="transition ease-out duration-300"
                                         x-transition:enter-start="opacity-0 transform scale-y-90"
                                         x-transition:enter-end="opacity-100 transform scale-y-100"
                                         x-transition:leave="transition ease-in duration-200"
                                         x-transition:leave-start="opacity-100 transform scale-y-100"
                                         x-transition:leave-end="opacity-0 transform scale-y-90"
                                         style="transform-origin: top;"
                                         id="<?= $uniqueId ?>">
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

                // Calculate filter counts
                $totalItems = count($pendingItems);
                $itemsWithPending = count(array_filter($pendingItems, fn($item) => $item['count'] > 0));
                $emptyItems = $totalItems - $itemsWithPending;
                ?>

                <!-- Alpine.js Enhanced: Filterable Pending Actions -->
                <div x-data="filterableList(<?= htmlspecialchars(json_encode($pendingItems)) ?>)"
                     role="group"
                     aria-labelledby="pending-approvals-title">

                    <!-- Filter Controls -->
                    <div class="btn-group mb-3 d-flex" role="group" aria-label="Filter pending approvals">
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click="setFilter('all')"
                                aria-pressed="{{ filter === 'all' ? 'true' : 'false' }}">
                            <i class="bi bi-list-ul me-1" aria-hidden="true"></i>
                            All (<span x-text="items.length"></span>)
                        </button>
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'pending' ? 'btn-warning' : 'btn-outline-secondary'"
                                @click="setFilter('pending')"
                                aria-pressed="{{ filter === 'pending' ? 'true' : 'false' }}">
                            <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                            With Items (<span x-text="pendingCount"></span>)
                        </button>
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'empty' ? 'btn-success' : 'btn-outline-secondary'"
                                @click="setFilter('empty')"
                                aria-pressed="{{ filter === 'empty' ? 'true' : 'false' }}">
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
                                              :aria-label="item.count + ' pending ' + item.label.toLowerCase()"
                                              x-text="item.count"></span>
                                    </div>

                                    <template x-if="item.count > 0">
                                        <a :href="'?route=' + item.route"
                                           class="btn btn-sm mt-1"
                                           :class="item.critical ? 'btn-danger' : 'btn-outline-secondary'"
                                           :aria-label="'Review Now - ' + item.count + ' ' + item.label.toLowerCase()">
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
