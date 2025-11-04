<?php
/**
 * Borrowed Tools Partial
 * Displays complete borrowing history for an asset (Non-Consumable Only)
 *
 * @var array $borrowHistory - Array of borrowing records
 */
?>
<div class="table-responsive">
    <?php if (!empty($borrowHistory)): ?>
        <table class="table table-sm table-hover" id="borrowHistoryTable">
            <thead class="table-light">
                <tr>
                    <th style="width: 10%;">Reference</th>
                    <th style="width: 12%;">Borrowed Date</th>
                    <th style="width: 12%;">Return Date</th>
                    <th style="width: 20%;">Borrower</th>
                    <th style="width: 25%;">Purpose</th>
                    <th style="width: 11%;">Status</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowHistory as $borrow): ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($borrow['batch_number'] ?? 'N/A') ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= AssetHelper::formatDate($borrow['borrowed_date']) ?>
                            </small>
                        </td>
                        <td>
                            <?php if (!empty($borrow['returned_date'])): ?>
                                <small class="text-success">
                                    <?= AssetHelper::formatDate($borrow['returned_date']) ?>
                                </small>
                            <?php elseif (!empty($borrow['expected_return_date'])): ?>
                                <small class="text-warning">
                                    Due: <?= AssetHelper::formatDate($borrow['expected_return_date']) ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($borrow['borrower_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2"></i>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($borrow['borrower_name']) ?></div>
                                        <?php if (!empty($borrow['project_name'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($borrow['project_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($borrow['purpose'] ?? 'N/A') ?>
                        </td>
                        <td>
                            <span class="badge <?= AssetHelper::getBorrowingStatusBadgeClass($borrow['status']) ?>">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $borrow['status'] ?? 'pending'))) ?>
                            </span>
                        </td>
                        <td>
                            <a href="?route=borrowed-tools/view&id=<?= $borrow['batch_id'] ?? $borrow['id'] ?>"
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
            No borrowing history found for this asset.
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($borrowHistory) && count($borrowHistory) > 10): ?>
<!-- DataTables initialization for borrowed tools -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        jQuery('#borrowHistoryTable').DataTable({
            order: [[1, 'desc']], // Sort by borrowed date descending
            pageLength: 10,
            responsive: true,
            language: {
                search: "Search borrowing history:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ borrowing records",
                infoEmpty: "No borrowing records available",
                infoFiltered: "(filtered from _MAX_ total records)"
            },
            columnDefs: [
                { orderable: true, targets: [0, 1, 2, 3, 5] },
                { orderable: false, targets: [6] }, // Actions column
                { searchable: true, targets: [0, 3, 4] }
            ]
        });
    }
});
</script>
<?php endif; ?>
