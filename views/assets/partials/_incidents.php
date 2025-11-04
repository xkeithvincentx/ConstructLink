<?php
/**
 * Incidents Partial
 * Displays complete incident history for an asset
 *
 * @var array $incidents - Array of incident records
 */
?>
<div class="table-responsive">
    <?php if (!empty($incidents)): ?>
        <table class="table table-sm table-hover" id="incidentsTable">
            <thead class="table-light">
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 15%;">Incident Type</th>
                    <th style="width: 35%;">Description</th>
                    <th style="width: 15%;">Reported By</th>
                    <th style="width: 13%;">Status</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incidents as $incident): ?>
                    <tr>
                        <td>
                            <small class="text-muted">
                                <?= AssetHelper::formatDate($incident['incident_date']) ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-warning">
                                <?= htmlspecialchars($incident['incident_type'] ?? 'N/A') ?>
                            </span>
                        </td>
                        <td>
                            <?= htmlspecialchars($incident['description'] ?? 'No description') ?>
                            <?php if (!empty($incident['resolution'])): ?>
                                <div class="mt-1">
                                    <small class="text-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        <?= htmlspecialchars($incident['resolution']) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($incident['reported_by_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2"></i>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($incident['reported_by_name']) ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= AssetHelper::getIncidentStatusBadgeClass($incident['status']) ?>">
                                <?= htmlspecialchars(ucfirst($incident['status'] ?? 'pending')) ?>
                            </span>
                        </td>
                        <td>
                            <a href="?route=incidents/view&id=<?= $incident['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle me-2"></i>
            No incident reports found for this asset.
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($incidents) && count($incidents) > 10): ?>
<!-- DataTables initialization for incidents -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        jQuery('#incidentsTable').DataTable({
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 10,
            responsive: true,
            language: {
                search: "Search incidents:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ incidents",
                infoEmpty: "No incidents available",
                infoFiltered: "(filtered from _MAX_ total incidents)"
            },
            columnDefs: [
                { orderable: true, targets: [0, 1, 3, 4] },
                { orderable: false, targets: [5] }, // Actions column
                { searchable: true, targets: [1, 2, 3] }
            ]
        });
    }
});
</script>
<?php endif; ?>
