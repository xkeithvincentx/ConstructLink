<?php
/**
 * Activity Logs Partial
 * Displays complete activity log history for an asset
 *
 * @var array $completeLogs - Array of activity log entries
 */
?>
<div class="table-responsive">
    <?php if (!empty($completeLogs)): ?>
        <table class="table table-sm table-hover" id="activityLogsTable">
            <thead class="table-light">
                <tr>
                    <th style="width: 15%;">Date & Time</th>
                    <th style="width: 15%;">Action</th>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 20%;">User</th>
                    <th style="width: 10%;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($completeLogs as $log): ?>
                    <tr>
                        <td>
                            <small class="text-muted">
                                <?= AssetHelper::formatDateTime($log['created_at']) ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-info text-white">
                                <?= htmlspecialchars($log['action']) ?>
                            </span>
                        </td>
                        <td>
                            <?= htmlspecialchars($log['description']) ?>
                        </td>
                        <td>
                            <?php if (!empty($log['user_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2"></i>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($log['user_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($log['username'] ?? '') ?></small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted font-monospace">
                                <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle me-2"></i>
            No activity logs found for this asset.
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($completeLogs) && count($completeLogs) > 10): ?>
<!-- DataTables initialization for activity logs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        jQuery('#activityLogsTable').DataTable({
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 10,
            responsive: true,
            language: {
                search: "Search logs:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ activity logs",
                infoEmpty: "No activity logs available",
                infoFiltered: "(filtered from _MAX_ total logs)"
            },
            columnDefs: [
                { orderable: true, targets: [0, 1, 2, 3] },
                { searchable: true, targets: [1, 2, 3] }
            ]
        });
    }
});
</script>
<?php endif; ?>
