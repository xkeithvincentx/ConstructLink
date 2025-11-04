<?php
/**
 * Withdrawals Partial
 * Displays complete withdrawal history for an asset (Consumable Only)
 *
 * @var array $withdrawals - Array of withdrawal records
 */
?>
<div class="table-responsive">
    <?php if (!empty($withdrawals)): ?>
        <table class="table table-sm table-hover" id="withdrawalsTable">
            <thead class="table-light">
                <tr>
                    <th style="width: 12%;">Withdrawal Date</th>
                    <th style="width: 8%;">Quantity</th>
                    <th style="width: 20%;">Receiver</th>
                    <th style="width: 15%;">Project</th>
                    <th style="width: 25%;">Purpose</th>
                    <th style="width: 10%;">Released By</th>
                    <th style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($withdrawals as $withdrawal): ?>
                    <tr>
                        <td>
                            <small class="text-muted">
                                <?= AssetHelper::formatDate($withdrawal['withdrawal_date'] ?? $withdrawal['created_at']) ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                <?= number_format($withdrawal['quantity'] ?? 0) ?>
                            </span>
                            <?php if (!empty($withdrawal['unit'])): ?>
                                <small class="text-muted"><?= htmlspecialchars($withdrawal['unit']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($withdrawal['receiver_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2"></i>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($withdrawal['receiver_name']) ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($withdrawal['project_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-building me-2"></i>
                                    <span class="fw-medium"><?= htmlspecialchars($withdrawal['project_name']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($withdrawal['purpose'] ?? 'N/A') ?>
                        </td>
                        <td>
                            <?php if (!empty($withdrawal['released_by_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-check me-2 text-success"></i>
                                    <span class="fw-medium"><?= htmlspecialchars($withdrawal['released_by_name']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= AssetHelper::getWithdrawalStatusBadgeClass($withdrawal['status']) ?>">
                                <?= htmlspecialchars(ucfirst($withdrawal['status'] ?? 'pending')) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary Stats -->
        <?php
        $totalWithdrawn = array_sum(array_column($withdrawals, 'quantity'));
        $releasedCount = count(array_filter($withdrawals, function($w) {
            return ($w['status'] ?? '') === 'released';
        }));
        ?>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Withdrawn</h5>
                        <p class="h3 text-primary"><?= number_format($totalWithdrawn) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h5 class="card-title">Released Withdrawals</h5>
                        <p class="h3 text-success"><?= $releasedCount ?> / <?= count($withdrawals) ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle me-2"></i>
            No withdrawal history found for this asset.
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($withdrawals) && count($withdrawals) > 10): ?>
<!-- DataTables initialization for withdrawals -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        jQuery('#withdrawalsTable').DataTable({
            order: [[0, 'desc']], // Sort by withdrawal date descending
            pageLength: 10,
            responsive: true,
            language: {
                search: "Search withdrawals:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ withdrawals",
                infoEmpty: "No withdrawals available",
                infoFiltered: "(filtered from _MAX_ total withdrawals)"
            },
            columnDefs: [
                { orderable: true, targets: [0, 1, 2, 3, 5, 6] },
                { searchable: true, targets: [2, 3, 4, 5] }
            ]
        });
    }
});
</script>
<?php endif; ?>
