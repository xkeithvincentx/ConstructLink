<?php
/**
 * Project Inventory Card Component
 *
 * Reusable component for displaying project-centric inventory view.
 * Used by Finance Director dashboard to support transfer vs. purchase decisions.
 *
 * Expected variables:
 * - $project: Array with project data (project_id, project_name, total_assets, etc.)
 *
 * @package ConstructLink
 * @subpackage Dashboard - Finance Director
 * @version 1.0
 */

if (!isset($project) || empty($project)) {
    return;
}

// Determine project card border class based on urgency
$cardBorderClass = '';
if ($project['has_critical']) {
    $cardBorderClass = 'border-danger border-2';
} elseif ($project['has_warning']) {
    $cardBorderClass = 'border-warning border-2';
}

$uniqueId = 'project-' . $project['project_id'];
?>

<div class="card <?= $cardBorderClass ?> mb-3">
    <div class="card-header bg-light">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="bi bi-building me-2" aria-hidden="true"></i>
                    <?= htmlspecialchars($project['project_name']) ?>
                </h5>
            </div>
            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <span class="badge bg-secondary me-2" role="status">
                    <?= number_format($project['total_assets']) ?> total
                </span>
                <span class="badge bg-success me-2" role="status">
                    <?= number_format($project['available_assets']) ?> available
                </span>
                <?php if ($project['has_critical']): ?>
                    <span class="badge bg-danger" role="status">
                        <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                        Critical Shortage
                    </span>
                <?php elseif ($project['has_warning']): ?>
                    <span class="badge bg-warning text-dark" role="status">
                        <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>
                        Low Stock
                    </span>
                <?php else: ?>
                    <span class="badge bg-success" role="status">
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                        Adequate Stock
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Quick Summary Stats -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="text-center p-2 bg-light rounded">
                    <div class="fs-5 fw-bold text-success">
                        <?= number_format($project['available_assets']) ?>
                    </div>
                    <small class="text-muted">Available</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-2 bg-light rounded">
                    <div class="fs-5 fw-bold text-primary">
                        <?= number_format($project['in_use_assets']) ?>
                    </div>
                    <small class="text-muted">In Use</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-2 bg-light rounded">
                    <div class="fs-5 fw-bold text-warning">
                        <?= number_format($project['maintenance_assets']) ?>
                    </div>
                    <small class="text-muted">Maintenance</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-2 bg-light rounded">
                    <div class="fs-5 fw-bold">
                        <?= number_format($project['total_assets']) ?>
                    </div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>

        <!-- Expand/Collapse Button -->
        <button class="btn btn-sm btn-outline-secondary w-100 mb-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#<?= $uniqueId ?>-details"
                aria-expanded="false"
                aria-controls="<?= $uniqueId ?>-details">
            <i class="bi bi-chevron-down me-1" aria-hidden="true"></i>
            <strong>Show Inventory Details</strong>
        </button>

        <!-- Collapsible Inventory Details -->
        <div class="collapse" id="<?= $uniqueId ?>-details">
            <?php if (!empty($project['categories'])): ?>
                <?php foreach ($project['categories'] as $category): ?>
                    <!-- Category Section -->
                    <div class="border rounded p-3 mb-3 bg-white">
                        <h6 class="fw-bold mb-2">
                            <i class="bi bi-tag me-1" aria-hidden="true"></i>
                            <?= htmlspecialchars($category['category_name']) ?>
                            <span class="badge badge-neutral rounded-pill ms-2">
                                <?= number_format($category['total_count']) ?> items
                            </span>
                        </h6>

                        <!-- Equipment Types List (Compact) -->
                        <?php if (!empty($category['equipment_types'])): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($category['equipment_types'] as $equipType): ?>
                                    <?php
                                    // Determine row styling based on urgency
                                    $rowClass = '';
                                    $textClass = '';
                                    $badgeClass = 'badge-neutral';

                                    if ($equipType['urgency'] === 'critical') {
                                        $rowClass = 'bg-danger bg-opacity-10';
                                        $badgeClass = 'bg-danger';
                                    } elseif ($equipType['urgency'] === 'warning') {
                                        $rowClass = 'bg-warning bg-opacity-10';
                                        $badgeClass = 'bg-warning text-dark';
                                    }
                                    ?>

                                    <div class="list-group-item <?= $rowClass ?> px-0 py-2 d-flex justify-content-between align-items-center flex-wrap">
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($equipType['equipment_type_name']) ?>:</strong>
                                            <span class="text-muted ms-2">
                                                <span class="text-success fw-semibold"><?= $equipType['available_count'] ?> avail</span>,
                                                <span class="text-primary"><?= $equipType['in_use_count'] ?> in use</span>
                                                <?php if ($equipType['maintenance_count'] > 0): ?>
                                                    , <span class="text-warning"><?= $equipType['maintenance_count'] ?> maint</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <?php if ($equipType['urgency'] !== 'normal'): ?>
                                            <span class="badge <?= $badgeClass ?> rounded-pill" role="status">
                                                <?php if ($equipType['urgency'] === 'critical'): ?>
                                                    <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                                                    OUT OF STOCK
                                                <?php else: ?>
                                                    <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>
                                                    LOW STOCK
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                No equipment types in this category
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info mb-0" role="status">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                    No inventory details available for this project.
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                <a href="?route=assets&project_id=<?= $project['project_id'] ?>"
                   class="btn btn-outline-secondary btn-sm"
                   aria-label="View all assets in <?= htmlspecialchars($project['project_name']) ?>">
                    <i class="bi bi-eye me-1" aria-hidden="true"></i>
                    View All Assets
                </a>
                <?php if ($project['has_critical'] || $project['has_warning']): ?>
                    <a href="?route=transfers/create&from_project=<?= $project['project_id'] ?>"
                       class="btn btn-outline-primary btn-sm"
                       aria-label="Create transfer for <?= htmlspecialchars($project['project_name']) ?>">
                        <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>
                        Transfer Assets
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
