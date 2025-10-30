<?php
/**
 * Equipment Type Card Component
 * Reusable component for displaying equipment type inventory details
 * within category cards on Finance Director dashboard
 *
 * Required variables:
 * - $equipmentType (array): Equipment type data with counts and projects
 * - $categoryId (int): Parent category ID for unique ID generation
 * - $categoryName (string): Parent category name for accessibility
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 1.0
 * @since 2025-10-30
 */

// Validate required variables
if (!isset($equipmentType) || !isset($categoryId)) {
    return;
}

// Extract equipment type data
$equipTypeId = $equipmentType['equipment_type_id'] ?? 0;
$equipTypeName = htmlspecialchars($equipmentType['equipment_type_name'] ?? 'Unknown');
$totalCount = (int)($equipmentType['total_count'] ?? 0);
$availableCount = (int)($equipmentType['available_count'] ?? 0);
$inUseCount = (int)($equipmentType['in_use_count'] ?? 0);
$maintenanceCount = (int)($equipmentType['maintenance_count'] ?? 0);
$availabilityPercentage = (float)($equipmentType['availability_percentage'] ?? 0);
$urgency = $equipmentType['urgency'] ?? 'normal';
$urgencyLabel = htmlspecialchars($equipmentType['urgency_label'] ?? 'Unknown Status');
$projects = $equipmentType['projects'] ?? [];

// Determine styling based on urgency
$borderClass = '';
$badgeClass = 'badge-neutral';
$urgencyIcon = 'bi-info-circle';
$progressBarClass = 'bg-success';

if ($urgency === 'critical') {
    $borderClass = 'border-danger border-2';
    $badgeClass = 'bg-danger text-white';
    $urgencyIcon = 'bi-exclamation-triangle-fill';
    $progressBarClass = 'bg-danger';
} elseif ($urgency === 'warning') {
    $borderClass = 'border-warning border-2';
    $badgeClass = 'bg-warning text-dark';
    $urgencyIcon = 'bi-exclamation-circle-fill';
    $progressBarClass = 'bg-warning';
} else {
    if ($availabilityPercentage < 50) {
        $progressBarClass = 'bg-info';
    }
}

// Generate unique IDs for accessibility
$uniqueId = 'equipment-type-' . $categoryId . '-' . $equipTypeId;
$projectsId = $uniqueId . '-projects';
?>

<div class="card <?= $borderClass ?> mb-3 equipment-type-card"
     role="region"
     aria-labelledby="<?= $uniqueId ?>-heading">
    <div class="card-body py-3">
        <!-- Equipment Type Header -->
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
                <h6 class="mb-1 fw-bold" id="<?= $uniqueId ?>-heading">
                    <i class="bi bi-tools me-1 text-muted" aria-hidden="true"></i>
                    <?= $equipTypeName ?>
                </h6>
                <span class="badge <?= $badgeClass ?> rounded-pill" role="status">
                    <i class="<?= $urgencyIcon ?> me-1" aria-hidden="true"></i>
                    <?= $urgencyLabel ?>
                </span>
            </div>
        </div>

        <!-- Status Metrics (Compact Row) -->
        <div class="row g-2 mb-2">
            <div class="col-3 col-md-3">
                <div class="text-center p-2 bg-light rounded">
                    <div class="fs-5 fw-bold text-success">
                        <?= $availableCount ?>
                    </div>
                    <small class="text-muted d-none d-sm-inline">Available</small>
                    <small class="text-muted d-sm-none">Avail</small>
                </div>
            </div>
            <div class="col-3 col-md-3">
                <div class="text-center p-2 bg-light rounded">
                    <div class="fs-5 fw-bold text-primary">
                        <?= $inUseCount ?>
                    </div>
                    <small class="text-muted">In Use</small>
                </div>
            </div>
            <div class="col-3 col-md-3">
                <div class="text-center p-2 bg-light rounded">
                    <div class="fs-5 fw-bold text-warning">
                        <?= $maintenanceCount ?>
                    </div>
                    <small class="text-muted d-none d-sm-inline">Maintenance</small>
                    <small class="text-muted d-sm-none">Maint</small>
                </div>
            </div>
            <div class="col-3 col-md-3">
                <div class="text-center p-2 bg-light rounded">
                    <div class="fs-5 fw-bold">
                        <?= $totalCount ?>
                    </div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>

        <!-- Availability Progress Bar -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted">Availability</small>
                <small class="fw-semibold"><?= number_format($availabilityPercentage, 1) ?>%</small>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar <?= $progressBarClass ?>"
                     role="progressbar"
                     style="width: <?= $availabilityPercentage ?>%"
                     aria-valuenow="<?= $availabilityPercentage ?>"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-label="<?= $availabilityPercentage ?>% of <?= $equipTypeName ?> available">
                </div>
            </div>
        </div>

        <!-- Critical Stock Warning (if applicable) -->
        <?php if ($urgency === 'critical'): ?>
            <div class="alert alert-danger alert-sm mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                <strong>CRITICAL:</strong> All <?= $equipTypeName ?> currently deployed.
                <?php if ($inUseCount > 0): ?>
                    <br><small>Consider procurement or transfer from available sites.</small>
                <?php endif; ?>
            </div>
        <?php elseif ($urgency === 'warning' && $availableCount <= 1): ?>
            <div class="alert alert-warning alert-sm mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>
                <strong>WARNING:</strong> Only <?= $availableCount ?> <?= $equipTypeName ?> available.
            </div>
        <?php endif; ?>

        <!-- Project Site Distribution (Collapsible) -->
        <?php if (!empty($projects)): ?>
            <div class="border-top pt-2">
                <button class="btn btn-link btn-sm p-0 text-decoration-none w-100 text-start collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#<?= $projectsId ?>"
                        aria-expanded="false"
                        aria-controls="<?= $projectsId ?>">
                    <i class="bi bi-chevron-right me-1" aria-hidden="true"></i>
                    <strong class="d-none d-sm-inline">Project Site Distribution (<?= count($projects) ?>)</strong>
                    <strong class="d-sm-none">Sites (<?= count($projects) ?>)</strong>
                </button>
                <div class="collapse mt-2" id="<?= $projectsId ?>">
                    <div class="list-group list-group-flush small">
                        <?php foreach ($projects as $project): ?>
                            <?php
                            $projectName = htmlspecialchars($project['project_name']);
                            $projectAvailable = (int)$project['available_count'];
                            $projectTotal = (int)$project['asset_count'];
                            ?>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <span class="text-truncate me-2">
                                    <i class="bi bi-building me-1" aria-hidden="true"></i>
                                    <?= $projectName ?>
                                </span>
                                <span class="badge bg-secondary rounded-pill" style="white-space: nowrap;">
                                    <?= $projectAvailable ?> / <?= $projectTotal ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="border-top pt-2">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    No assets currently assigned to projects
                </small>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="d-grid gap-2 d-md-flex mt-3">
            <a href="?route=assets&equipment_type_id=<?= $equipTypeId ?>"
               class="btn btn-outline-secondary btn-sm <?= !empty($projects) ? 'flex-md-fill' : '' ?>"
               aria-label="View all <?= $equipTypeName ?> assets">
                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">View All</span>
                <span class="d-sm-none">View</span>
            </a>
            <?php if ($urgency !== 'normal'): ?>
                <a href="?route=requests/create&equipment_type_id=<?= $equipTypeId ?>"
                   class="btn btn-primary btn-sm <?= !empty($projects) ? 'flex-md-fill' : '' ?>"
                   aria-label="Create procurement request for <?= $equipTypeName ?>">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Initiate Procurement</span>
                    <span class="d-sm-none">Buy</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Smooth chevron rotation for collapse buttons */
[data-bs-toggle="collapse"] .bi-chevron-right {
    transition: transform 0.2s ease-in-out;
}
[data-bs-toggle="collapse"]:not(.collapsed) .bi-chevron-right {
    transform: rotate(90deg);
}

/* Compact alert for equipment type cards */
.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

/* Equipment type card hover effect */
.equipment-type-card {
    transition: box-shadow 0.2s ease-in-out;
}
.equipment-type-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>
