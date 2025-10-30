<?php
/**
 * Inventory Table View - Complete "One Glance" Dashboard
 *
 * Comprehensive table view showing ALL equipment types across ALL projects.
 * Implements "one glance" principle - all critical information visible without clicking.
 *
 * Expected variables:
 * - $projects: Array of projects with categories and equipment types
 *
 * @package ConstructLink
 * @subpackage Dashboard - Finance Director
 * @version 2.0 - One Glance Design
 */

if (!isset($projects) || empty($projects)) {
    return;
}

// Flatten data structure for table display
$tableRows = [];
foreach ($projects as $project) {
    foreach ($project['categories'] as $category) {
        foreach ($category['equipment_types'] as $equipType) {
            $tableRows[] = [
                'project_id' => $project['project_id'],
                'project_name' => $project['project_name'],
                'category_name' => $category['category_name'],
                'equipment_type_name' => $equipType['equipment_type_name'],
                'available_count' => $equipType['available_count'],
                'in_use_count' => $equipType['in_use_count'],
                'maintenance_count' => $equipType['maintenance_count'],
                'total_count' => $equipType['total_count'],
                'urgency' => $equipType['urgency'],
                'urgency_label' => $equipType['urgency_label']
            ];
        }
    }
}
?>

<div class="card card-neutral">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-0" id="complete-inventory-title">
                    <i class="bi bi-table me-2" aria-hidden="true"></i>
                    Complete Inventory Overview
                </h5>
                <p class="text-muted mb-0 small mt-1">
                    All equipment types across all projects at a glance. Sort, filter, or export as needed.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <span class="badge bg-secondary">
                    <?= count($tableRows) ?> Equipment Types
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($tableRows)): ?>
            <div class="alert alert-info mb-0" role="status">
                <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                No inventory data available.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="inventory-table"
                       class="table table-sm table-hover"
                       aria-labelledby="complete-inventory-title"
                       role="grid">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-nowrap">Project</th>
                            <th scope="col" class="text-nowrap">Category</th>
                            <th scope="col" class="text-nowrap">Equipment Type</th>
                            <th scope="col" class="text-center text-nowrap">
                                <i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i>
                                Available
                            </th>
                            <th scope="col" class="text-center text-nowrap">
                                <i class="bi bi-tools text-primary me-1" aria-hidden="true"></i>
                                In Use
                            </th>
                            <th scope="col" class="text-center text-nowrap">
                                <i class="bi bi-wrench text-warning me-1" aria-hidden="true"></i>
                                Maint.
                            </th>
                            <th scope="col" class="text-center text-nowrap">Total</th>
                            <th scope="col" class="text-center text-nowrap">Status</th>
                            <th scope="col" class="text-center text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableRows as $row): ?>
                            <?php
                            // Row styling based on urgency
                            $rowClass = '';
                            if ($row['urgency'] === 'critical') {
                                $rowClass = 'table-danger';
                            } elseif ($row['urgency'] === 'warning') {
                                $rowClass = 'table-warning';
                            }
                            ?>
                            <tr class="<?= $rowClass ?>" data-urgency="<?= htmlspecialchars($row['urgency']) ?>">
                                <td class="text-nowrap">
                                    <?= htmlspecialchars($row['project_name']) ?>
                                </td>
                                <td class="text-nowrap">
                                    <?= htmlspecialchars($row['category_name']) ?>
                                </td>
                                <td class="fw-semibold text-nowrap">
                                    <?= htmlspecialchars($row['equipment_type_name']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['available_count'] == 0): ?>
                                        <span class="badge bg-danger">0</span>
                                    <?php elseif ($row['available_count'] <= 2): ?>
                                        <span class="badge bg-warning text-dark"><?= $row['available_count'] ?></span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><?= $row['available_count'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="text-primary"><?= $row['in_use_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['maintenance_count'] > 0): ?>
                                        <span class="text-warning"><?= $row['maintenance_count'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-semibold">
                                    <?= $row['total_count'] ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['urgency'] === 'critical'): ?>
                                        <span class="badge bg-danger rounded-pill" role="status">
                                            <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                                            OUT
                                        </span>
                                    <?php elseif ($row['urgency'] === 'warning'): ?>
                                        <span class="badge bg-warning text-dark rounded-pill" role="status">
                                            <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>
                                            LOW
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success rounded-pill" role="status">
                                            <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                                            OK
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Actions for <?= htmlspecialchars($row['equipment_type_name']) ?>">
                                        <a href="?route=assets&project_id=<?= $row['project_id'] ?>&equipment_type=<?= urlencode($row['equipment_type_name']) ?>"
                                           class="btn btn-outline-secondary btn-sm"
                                           aria-label="View <?= htmlspecialchars($row['equipment_type_name']) ?> in <?= htmlspecialchars($row['project_name']) ?>"
                                           title="View Assets">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php if ($row['urgency'] === 'critical' || $row['urgency'] === 'warning'): ?>
                                            <a href="?route=transfers/create&from_project=<?= $row['project_id'] ?>&equipment_type=<?= urlencode($row['equipment_type_name']) ?>"
                                               class="btn btn-outline-primary btn-sm"
                                               aria-label="Transfer <?= htmlspecialchars($row['equipment_type_name']) ?>"
                                               title="Transfer">
                                                <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                                            </a>
                                            <a href="?route=procurement-orders/create&equipment_type=<?= urlencode($row['equipment_type_name']) ?>"
                                               class="btn btn-outline-success btn-sm"
                                               aria-label="Purchase <?= htmlspecialchars($row['equipment_type_name']) ?>"
                                               title="Purchase">
                                                <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Export Instructions -->
            <div class="alert alert-light border mt-3 mb-0 d-flex align-items-start" role="status">
                <i class="bi bi-info-circle me-2 mt-1" aria-hidden="true"></i>
                <div class="small">
                    <strong>Quick Actions:</strong> Use the search box to filter by project, category, or equipment type.
                    Click column headers to sort. Export to Excel using the button above the table.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize DataTable with export functionality
document.addEventListener('DOMContentLoaded', function() {
    if (typeof DataTableHelper !== 'undefined' && $('#inventory-table').length) {
        // Initialize with export buttons
        const table = DataTableHelper.initWithExport('#inventory-table', ['copy', 'excel', 'csv', 'print'], {
            pageLength: 50,
            order: [[7, 'asc'], [0, 'asc'], [1, 'asc']], // Sort by Status (critical first), then Project, then Category
            columnDefs: [
                { targets: [3, 4, 5, 6, 7, 8], className: 'text-center' },
                { targets: 8, orderable: false, searchable: false }
            ],
            rowCallback: function(row, data, index) {
                // Add aria-label to row for screen readers
                const urgency = $(row).attr('data-urgency');
                if (urgency === 'critical') {
                    $(row).attr('aria-label', 'Critical shortage row');
                } else if (urgency === 'warning') {
                    $(row).attr('aria-label', 'Low stock warning row');
                }
            }
        });

        // Add custom search by urgency
        $('#urgency-filter').on('change', function() {
            const urgency = $(this).val();
            if (urgency) {
                table.column(7).search(urgency, true, false).draw();
            } else {
                table.column(7).search('').draw();
            }
        });
    }
});
</script>
