<?php
/**
 * Critical Shortage Summary Component
 *
 * Displays urgent items requiring immediate action (out of stock or low stock).
 * Appears FIRST on the dashboard for instant visibility.
 *
 * Expected variables:
 * - $projects: Array of projects with categories and equipment types
 *
 * @package ConstructLink
 * @subpackage Dashboard - Finance Director
 * @version 1.0
 */

if (!isset($projects) || empty($projects)) {
    return;
}

// Extract critical and warning items
$criticalItems = [];
$warningItems = [];

foreach ($projects as $project) {
    foreach ($project['categories'] as $category) {
        foreach ($category['equipment_types'] as $equipType) {
            $item = [
                'project_id' => $project['project_id'],
                'project_name' => $project['project_name'],
                'category_name' => $category['category_name'],
                'equipment_type_name' => $equipType['equipment_type_name'],
                'available_count' => $equipType['available_count'],
                'in_use_count' => $equipType['in_use_count'],
                'total_count' => $equipType['total_count']
            ];

            if ($equipType['urgency'] === 'critical') {
                $criticalItems[] = $item;
            } elseif ($equipType['urgency'] === 'warning') {
                $warningItems[] = $item;
            }
        }
    }
}

// Only show if there are critical or warning items
if (empty($criticalItems) && empty($warningItems)) {
    return;
}
?>

<!-- Critical Shortage Summary - HIGHEST PRIORITY VISIBILITY -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-danger border-3 shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0" id="critical-shortages-title">
                    <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                    Critical Inventory Alert
                </h5>
                <p class="mb-0 small mt-1 opacity-90">
                    Immediate action required - equipment shortages detected
                </p>
            </div>
            <div class="card-body">
                <?php if (!empty($criticalItems)): ?>
                    <!-- OUT OF STOCK Items (Critical) -->
                    <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                        <i class="bi bi-x-circle-fill me-2 mt-1 fs-4" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-2">
                                <strong>OUT OF STOCK</strong> - <?= count($criticalItems) ?> Equipment Type<?= count($criticalItems) !== 1 ? 's' : '' ?>
                            </h6>
                            <p class="mb-2 small">
                                These items are completely unavailable but still in use. Immediate procurement or transfer required.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0" aria-label="Out of stock items">
                                    <thead class="visually-hidden">
                                        <tr>
                                            <th scope="col">Project</th>
                                            <th scope="col">Equipment Type</th>
                                            <th scope="col">Available</th>
                                            <th scope="col">In Use</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($criticalItems as $item): ?>
                                            <tr>
                                                <td class="fw-semibold text-nowrap align-middle">
                                                    <i class="bi bi-building me-1" aria-hidden="true"></i>
                                                    <?= htmlspecialchars($item['project_name']) ?>
                                                </td>
                                                <td class="align-middle">
                                                    <strong><?= htmlspecialchars($item['equipment_type_name']) ?></strong>
                                                    <small class="text-muted">(<?= htmlspecialchars($item['category_name']) ?>)</small>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="badge bg-danger fs-6">
                                                        0 available
                                                    </span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="text-muted">
                                                        <?= $item['in_use_count'] ?> in use
                                                    </span>
                                                </td>
                                                <td class="text-end align-middle text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="Actions for <?= htmlspecialchars($item['equipment_type_name']) ?>">
                                                        <a href="?route=transfers/create&from_project=<?= $item['project_id'] ?>&equipment_type=<?= urlencode($item['equipment_type_name']) ?>"
                                                           class="btn btn-warning"
                                                           aria-label="Transfer <?= htmlspecialchars($item['equipment_type_name']) ?> to <?= htmlspecialchars($item['project_name']) ?>">
                                                            <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>
                                                            Transfer
                                                        </a>
                                                        <a href="?route=procurement-orders/create&equipment_type=<?= urlencode($item['equipment_type_name']) ?>&project_id=<?= $item['project_id'] ?>"
                                                           class="btn btn-danger"
                                                           aria-label="Purchase <?= htmlspecialchars($item['equipment_type_name']) ?> for <?= htmlspecialchars($item['project_name']) ?>">
                                                            <i class="bi bi-cart-plus me-1" aria-hidden="true"></i>
                                                            Purchase
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($warningItems)): ?>
                    <!-- LOW STOCK Items (Warning) -->
                    <div class="alert alert-warning d-flex align-items-start mb-0" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2 mt-1 fs-4" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-2">
                                <strong>LOW STOCK</strong> - <?= count($warningItems) ?> Equipment Type<?= count($warningItems) !== 1 ? 's' : '' ?>
                            </h6>
                            <p class="mb-2 small">
                                These items have ≤2 units available. Consider procurement or transfer before depletion.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0" aria-label="Low stock items">
                                    <thead class="visually-hidden">
                                        <tr>
                                            <th scope="col">Project</th>
                                            <th scope="col">Equipment Type</th>
                                            <th scope="col">Available</th>
                                            <th scope="col">In Use</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($warningItems as $item): ?>
                                            <tr>
                                                <td class="fw-semibold text-nowrap align-middle">
                                                    <i class="bi bi-building me-1" aria-hidden="true"></i>
                                                    <?= htmlspecialchars($item['project_name']) ?>
                                                </td>
                                                <td class="align-middle">
                                                    <strong><?= htmlspecialchars($item['equipment_type_name']) ?></strong>
                                                    <small class="text-muted">(<?= htmlspecialchars($item['category_name']) ?>)</small>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="badge bg-warning text-dark fs-6">
                                                        <?= $item['available_count'] ?> available
                                                    </span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="text-muted">
                                                        <?= $item['in_use_count'] ?> in use
                                                    </span>
                                                </td>
                                                <td class="text-end align-middle text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="Actions for <?= htmlspecialchars($item['equipment_type_name']) ?>">
                                                        <a href="?route=transfers/create&from_project=<?= $item['project_id'] ?>&equipment_type=<?= urlencode($item['equipment_type_name']) ?>"
                                                           class="btn btn-outline-warning"
                                                           aria-label="Transfer <?= htmlspecialchars($item['equipment_type_name']) ?> to <?= htmlspecialchars($item['project_name']) ?>">
                                                            <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>
                                                            Transfer
                                                        </a>
                                                        <a href="?route=procurement-orders/create&equipment_type=<?= urlencode($item['equipment_type_name']) ?>&project_id=<?= $item['project_id'] ?>"
                                                           class="btn btn-outline-warning"
                                                           aria-label="Purchase <?= htmlspecialchars($item['equipment_type_name']) ?> for <?= htmlspecialchars($item['project_name']) ?>">
                                                            <i class="bi bi-cart-plus me-1" aria-hidden="true"></i>
                                                            Purchase
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Summary Stats -->
                <div class="row g-3 mt-3 pt-3 border-top">
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="display-6 fw-bold text-danger">
                                <?= count($criticalItems) ?>
                            </div>
                            <small class="text-muted">Out of Stock</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="display-6 fw-bold text-warning">
                                <?= count($warningItems) ?>
                            </div>
                            <small class="text-muted">Low Stock</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="display-6 fw-bold text-success">
                                <?= count($criticalItems) + count($warningItems) ?>
                            </div>
                            <small class="text-muted">Items Needing Action</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
