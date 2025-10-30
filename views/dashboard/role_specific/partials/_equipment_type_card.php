<?php
/**
 * Equipment Type Card Component - SIMPLIFIED
 * Shows ONLY equipment type name and project distribution table
 * NO stats cards, NO badges, NO buttons within equipment type section
 *
 * Required variables:
 * - $equipmentType (array): Equipment type data with counts and projects
 * - $categoryId (int): Parent category ID for unique ID generation
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.0 - Simplified for Finance Director
 * @since 2025-10-30
 */

// Validate required variables
if (!isset($equipmentType) || !isset($categoryId)) {
    return;
}

// Extract equipment type data
$equipTypeId = $equipmentType['equipment_type_id'] ?? 0;
$equipTypeName = htmlspecialchars($equipmentType['equipment_type_name'] ?? 'Unknown');
$availableCount = (int)($equipmentType['available_count'] ?? 0);
$inUseCount = (int)($equipmentType['in_use_count'] ?? 0);
$totalCount = (int)($equipmentType['total_count'] ?? 0);
$projects = $equipmentType['projects'] ?? [];

// Generate unique IDs for accessibility
$uniqueId = 'equipment-type-' . $categoryId . '-' . $equipTypeId;
?>

<!-- Simple Equipment Type Section: Name + Project Table ONLY -->
<div class="mb-4" role="region" aria-labelledby="<?= $uniqueId ?>-heading">
    <!-- Equipment Type Name as Simple Header -->
    <h6 class="mb-2 fw-bold text-secondary" id="<?= $uniqueId ?>-heading">
        <?= $equipTypeName ?>
    </h6>

    <!-- Project Distribution Table (ONLY content) -->
    <?php if (!empty($projects)): ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-start">Project Name</th>
                        <th scope="col" class="text-center">Available</th>
                        <th scope="col" class="text-center">In Use</th>
                        <th scope="col" class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $projectName = htmlspecialchars($project['project_name']);
                        $projectAvailable = (int)$project['available_count'];
                        $projectInUse = (int)($project['in_use_count'] ?? 0);
                        $projectTotal = (int)$project['asset_count'];

                        // Highlight rows with available equipment (potential sources for transfers)
                        $rowClass = $projectAvailable > 0 ? 'table-success-subtle' : '';
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="text-start">
                                <?= $projectName ?>
                            </td>
                            <td class="text-center fw-bold text-success">
                                <?= $projectAvailable ?>
                            </td>
                            <td class="text-center text-primary">
                                <?= $projectInUse ?>
                            </td>
                            <td class="text-center fw-semibold">
                                <?= $projectTotal ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td class="text-start">Total</td>
                        <td class="text-center text-success">
                            <?= $availableCount ?>
                        </td>
                        <td class="text-center text-primary">
                            <?= $inUseCount ?>
                        </td>
                        <td class="text-center">
                            <?= $totalCount ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <small class="text-muted mt-2 d-block">
            <span class="bg-success-subtle px-1">Green rows</span> indicate projects with available equipment for potential transfers
        </small>
    <?php else: ?>
        <div class="alert alert-info alert-sm mb-0" role="status">
            No assets currently assigned to projects
        </div>
    <?php endif; ?>
</div>
