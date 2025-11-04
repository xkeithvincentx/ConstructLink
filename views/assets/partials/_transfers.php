<?php
/**
 * Transfers Partial
 * Displays complete transfer history for an asset
 *
 * @var array $transfers - Array of transfer records
 */
?>
<div class="table-responsive">
    <?php if (!empty($transfers)): ?>
        <table class="table table-sm table-hover" id="transfersTable">
            <thead class="table-light">
                <tr>
                    <th style="width: 12%;">Transfer Date</th>
                    <th style="width: 20%;">From Project</th>
                    <th style="width: 20%;">To Project</th>
                    <th style="width: 8%;">Quantity</th>
                    <th style="width: 15%;">Requested By</th>
                    <th style="width: 15%;">Approved By</th>
                    <th style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transfers as $transfer): ?>
                    <tr>
                        <td>
                            <small class="text-muted">
                                <?= AssetHelper::formatDate($transfer['transfer_date'] ?? $transfer['created_at']) ?>
                            </small>
                        </td>
                        <td>
                            <?php if (!empty($transfer['from_project_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-arrow-right-circle me-2 text-danger"></i>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($transfer['from_project_name']) ?></div>
                                        <?php if (!empty($transfer['from_location'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($transfer['from_location']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($transfer['to_project_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-arrow-left-circle me-2 text-success"></i>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($transfer['to_project_name']) ?></div>
                                        <?php if (!empty($transfer['to_location'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($transfer['to_location']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($transfer['quantity'])): ?>
                                <span class="badge bg-info">
                                    <?= number_format($transfer['quantity']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">1</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($transfer['requested_by_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2"></i>
                                    <span class="fw-medium"><?= htmlspecialchars($transfer['requested_by_name']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($transfer['approved_by_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle me-2 text-success"></i>
                                    <span class="fw-medium"><?= htmlspecialchars($transfer['approved_by_name']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= AssetHelper::getTransferStatusBadgeClass($transfer['status']) ?>">
                                <?= htmlspecialchars(ucfirst($transfer['status'] ?? 'pending')) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle me-2"></i>
            No transfer history found for this asset.
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($transfers) && count($transfers) > 10): ?>
<!-- DataTables initialization for transfers -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        jQuery('#transfersTable').DataTable({
            order: [[0, 'desc']], // Sort by transfer date descending
            pageLength: 10,
            responsive: true,
            language: {
                search: "Search transfers:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ transfers",
                infoEmpty: "No transfers available",
                infoFiltered: "(filtered from _MAX_ total transfers)"
            },
            columnDefs: [
                { orderable: true, targets: [0, 1, 2, 3, 4, 5, 6] },
                { searchable: true, targets: [1, 2, 4, 5] }
            ]
        });
    }
});
</script>
<?php endif; ?>
