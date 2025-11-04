<?php
/**
 * Maintenance Records Partial
 * Displays complete maintenance history for an asset (Non-Consumable Only)
 *
 * @var array $maintenance - Array of maintenance records
 */
?>
<div class="table-responsive">
    <?php if (!empty($maintenance)): ?>
        <table class="table table-sm table-hover" id="maintenanceTable">
            <thead class="table-light">
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 35%;">Description</th>
                    <th style="width: 15%;">Technician</th>
                    <th style="width: 10%;">Cost</th>
                    <th style="width: 13%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($maintenance as $record): ?>
                    <tr>
                        <td>
                            <small class="text-muted">
                                <?= AssetHelper::formatDate($record['maintenance_date']) ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                <?= htmlspecialchars($record['maintenance_type'] ?? 'N/A') ?>
                            </span>
                        </td>
                        <td>
                            <?= htmlspecialchars($record['description'] ?? 'No description') ?>
                            <?php if (!empty($record['next_maintenance_date'])): ?>
                                <div class="mt-1">
                                    <small class="text-info">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        Next: <?= AssetHelper::formatDate($record['next_maintenance_date']) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($record['technician_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-wrench me-2"></i>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($record['technician_name']) ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($record['cost']) && $record['cost'] > 0): ?>
                                <span class="text-success fw-medium">
                                    ₱<?= number_format($record['cost'], 2) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= AssetHelper::getMaintenanceStatusBadgeClass($record['status']) ?>">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $record['status'] ?? 'pending'))) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle me-2"></i>
            No maintenance records found for this asset.
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($maintenance) && count($maintenance) > 10): ?>
<!-- DataTables initialization for maintenance -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        jQuery('#maintenanceTable').DataTable({
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 10,
            responsive: true,
            language: {
                search: "Search maintenance:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ maintenance records",
                infoEmpty: "No maintenance records available",
                infoFiltered: "(filtered from _MAX_ total records)"
            },
            columnDefs: [
                { orderable: true, targets: [0, 1, 3, 4, 5] },
                { searchable: true, targets: [1, 2, 3] }
            ]
        });
    }
});
</script>
<?php endif; ?>
