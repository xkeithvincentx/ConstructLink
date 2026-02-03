<?php
/**
 * Withdrawals List Partial
 * Displays withdrawals in a unified single/batch format with pagination
 * Matches borrowed-tools pattern for consistency
 */

// Client-side pagination on grouped items
// Note: Controller fetches all withdrawals, we paginate after grouping
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 5;
$page = max(1, $page); // Ensure page is at least 1

// Paginate the grouped display items
$totalItems = count($displayItems);
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedItems = array_slice($displayItems, $offset, $perPage);

// Calculate display range
$from = $totalItems > 0 ? $offset + 1 : 0;
$to = min($offset + $perPage, $totalItems);
?>

<!-- Withdrawals Table/Cards -->
<div class="card">
    <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <h6 class="card-title mb-0">Consumable Withdrawals</h6>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <!-- Records Per Page Selector (Desktop Only) -->
            <div class="d-none d-md-flex align-items-center gap-2">
                <label for="recordsPerPage" class="mb-0 text-nowrap" style="font-size: 0.875rem;">
                    <i class="bi bi-list-ul me-1" aria-hidden="true"></i>Show:
                </label>
                <select id="recordsPerPage"
                        class="form-select form-select-sm"
                        style="width: auto; min-width: 80px;"
                        aria-label="Records per page">
                    <option value="5" <?= $perPage == 5 ? 'selected' : '' ?>>5</option>
                    <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span class="text-muted" style="font-size: 0.875rem;">entries</span>
            </div>
            <div class="vr d-none d-md-block"></div>
            <button class="btn btn-sm btn-outline-primary" id="exportBtn" data-action="export" aria-label="Export to Excel">
                <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Export</span>
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="printBtn" data-action="print" aria-label="Print list">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Print</span>
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($paginatedItems)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No withdrawal requests found</h5>
                <p class="text-muted">Try adjusting your filters or create a new withdrawal request.</p>
                <?php if (hasPermission('withdrawals/create')): ?>
                    <a href="?route=withdrawals/create-batch" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Withdrawal Request
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">#</th>
                            <th style="width: 180px;">Reference</th>
                            <th>Consumable</th>
                            <th style="width: 150px;">Receiver</th>
                            <th style="width: 100px;" class="text-center">Quantity</th>
                            <th style="width: 100px;" class="text-center">Returns</th>
                            <th style="width: 150px;">Status</th>
                            <th style="width: 120px;">Created</th>
                            <th style="width: 200px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginatedItems as $item): ?>
                            <?php if ($item['type'] === 'batch'): ?>
                                <!-- Batch Row -->
                                <tr class="table-primary">
                                    <td><?= $item['primary']['id'] ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <i class="bi bi-collection me-1"></i>BATCH
                                        </span>
                                        <div class="small text-muted mt-1">
                                            <?= htmlspecialchars($item['primary']['batch_reference'] ?? 'WDR-BATCH-' . $item['batch_id']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= count($item['items']) ?> items</strong>
                                        <div class="small text-muted">
                                            <?php
                                            $itemNames = array_slice(array_map(function($i) {
                                                return htmlspecialchars($i['item_name']);
                                            }, $item['items']), 0, 2);
                                            echo implode(', ', $itemNames);
                                            if (count($item['items']) > 2) {
                                                echo ' +' . (count($item['items']) - 2) . ' more';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($item['primary']['receiver_name']) ?></td>
                                    <td class="text-center">
                                        <strong><?= array_sum(array_column($item['items'], 'quantity')) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $totalReturned = array_sum(array_map(function($i) {
                                            return (int)($i['returned_quantity'] ?? 0);
                                        }, $item['items']));
                                        if ($totalReturned > 0):
                                        ?>
                                            <span class="badge bg-success" title="Returned quantity">
                                                <i class="bi bi-box-arrow-down me-1"></i><?= $totalReturned ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $item['primary']['status'];
                                        $badgeClass = match($status) {
                                            'Pending Verification' => 'bg-warning text-dark',
                                            'Pending Approval' => 'bg-info',
                                            'Approved' => 'bg-success',
                                            'Released' => 'bg-primary',
                                            'Returned' => 'bg-secondary',
                                            'Canceled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?= date('M j, Y', strtotime($item['primary']['created_at'])) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= date('g:i A', strtotime($item['primary']['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php
                                            $status = $item['primary']['status'];
                                            $batchId = $item['batch_id'];
                                            ?>

                                            <!-- Slot 1: Primary Action (Verify/Approve/Release/Return) -->
                                            <?php if ($status === 'Pending Verification' && hasPermission('withdrawals/verify')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalVerifyModal"
                                                        data-batch-id="<?= $batchId ?>"
                                                        title="Verify batch">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            <?php elseif ($status === 'Pending Approval' && hasPermission('withdrawals/approve')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalApproveModal"
                                                        data-batch-id="<?= $batchId ?>"
                                                        title="Approve batch">
                                                    <i class="bi bi-shield-check"></i>
                                                </button>
                                            <?php elseif ($status === 'Approved' && hasPermission('withdrawals/release')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-info"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalReleaseModal"
                                                        data-batch-id="<?= $batchId ?>"
                                                        title="Release batch">
                                                    <i class="bi bi-box-arrow-up"></i>
                                                </button>
                                            <?php elseif ($status === 'Released' && hasPermission('withdrawals/return')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalReturnModal"
                                                        data-batch-id="<?= $batchId ?>"
                                                        title="Return batch">
                                                    <i class="bi bi-box-arrow-down"></i>
                                                </button>
                                            <?php else: ?>
                                                <span style="width: 32px; display: inline-block;"></span>
                                            <?php endif; ?>

                                            <!-- Slot 2: Cancel -->
                                            <?php if (in_array($status, ['Pending Verification', 'Pending Approval', 'Approved']) && hasPermission('withdrawals/cancel')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalCancelModal"
                                                        data-withdrawal-id="<?= $batchId ?>"
                                                        data-is-batch="true"
                                                        title="Cancel batch">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <span style="width: 32px; display: inline-block;"></span>
                                            <?php endif; ?>

                                            <!-- Slot 3: View (Always present) -->
                                            <a href="?route=withdrawals/batch-view&id=<?= $batchId ?>"
                                               class="btn btn-sm btn-outline-primary"
                                               title="View batch details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <!-- Single Row -->
                                <tr>
                                    <td><?= $item['item']['id'] ?></td>
                                    <td>
                                        <?php if (!empty($item['item']['batch_reference'])): ?>
                                            <code class="small"><?= htmlspecialchars($item['item']['batch_reference']) ?></code>
                                        <?php else: ?>
                                            <code class="small text-muted" title="Legacy withdrawal - created before batch system">
                                                WDR-<?= str_pad($item['item']['id'], 5, '0', STR_PAD_LEFT) ?>
                                            </code>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($item['item']['item_name']) ?></div>
                                        <?php if (!empty($item['item']['item_ref'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($item['item']['item_ref']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['item']['receiver_name']) ?></td>
                                    <td class="text-center">
                                        <?= $item['item']['quantity'] ?>
                                        <?php if (!empty($item['item']['unit'])): ?>
                                            <span class="small text-muted"><?= htmlspecialchars($item['item']['unit']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $returnedQty = (int)($item['item']['returned_quantity'] ?? 0);
                                        if ($returnedQty > 0):
                                        ?>
                                            <span class="badge bg-success" title="Returned quantity">
                                                <i class="bi bi-box-arrow-down me-1"></i><?= $returnedQty ?>
                                                <?php if (!empty($item['item']['unit'])): ?>
                                                    <span class="small"><?= htmlspecialchars($item['item']['unit']) ?></span>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $item['item']['status'];
                                        $badgeClass = match($status) {
                                            'Pending Verification' => 'bg-warning text-dark',
                                            'Pending Approval' => 'bg-info',
                                            'Approved' => 'bg-success',
                                            'Released' => 'bg-primary',
                                            'Returned' => 'bg-secondary',
                                            'Canceled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?= date('M j, Y', strtotime($item['item']['created_at'])) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= date('g:i A', strtotime($item['item']['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- Slot 1: Primary Action (Verify/Approve/Release/Return) -->
                                            <?php if ($status === 'Pending Verification' && hasPermission('withdrawals/verify')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalVerifyModal"
                                                        data-batch-id="<?= $item['item']['id'] ?>"
                                                        data-is-single-item="true"
                                                        title="Verify request">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            <?php elseif ($status === 'Pending Approval' && hasPermission('withdrawals/approve')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalApproveModal"
                                                        data-batch-id="<?= $item['item']['id'] ?>"
                                                        data-is-single-item="true"
                                                        title="Approve request">
                                                    <i class="bi bi-shield-check"></i>
                                                </button>
                                            <?php elseif ($status === 'Approved' && hasPermission('withdrawals/release')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-info"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalReleaseModal"
                                                        data-batch-id="<?= $item['item']['id'] ?>"
                                                        data-is-single-item="true"
                                                        title="Release consumable">
                                                    <i class="bi bi-box-arrow-up"></i>
                                                </button>
                                            <?php elseif ($status === 'Released' && hasPermission('withdrawals/return')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalReturnModal"
                                                        data-batch-id="<?= $item['item']['id'] ?>"
                                                        data-is-single-item="true"
                                                        title="Return consumable">
                                                    <i class="bi bi-box-arrow-down"></i>
                                                </button>
                                            <?php else: ?>
                                                <span style="width: 32px; display: inline-block;"></span>
                                            <?php endif; ?>

                                            <!-- Slot 2: Cancel -->
                                            <?php if (in_array($status, ['Pending Verification', 'Pending Approval', 'Approved']) && hasPermission('withdrawals/cancel')): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#withdrawalCancelModal"
                                                        data-withdrawal-id="<?= $item['item']['id'] ?>"
                                                        data-is-batch="false"
                                                        title="Cancel request">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <span style="width: 32px; display: inline-block;"></span>
                                            <?php endif; ?>

                                            <!-- Slot 3: View (Always present) -->
                                            <a href="?route=withdrawals/view&id=<?= $item['item']['id'] ?>"
                                               class="btn btn-sm btn-outline-primary"
                                               title="View details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Enhanced Pagination Controls (Matches borrowed-tools pattern) -->
            <?php if ($totalPages > 1 || $totalItems > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3">
                    <!-- Showing Info -->
                    <div class="text-muted small">
                        Showing
                        <strong><?= number_format($from) ?></strong> to
                        <strong><?= number_format($to) ?></strong>
                        of
                        <strong><?= number_format($totalItems) ?></strong>
                        entries
                        <?php if (!empty($_GET['status']) || !empty($_GET['receiver']) || !empty($_GET['date_from']) || !empty($_GET['date_to'])): ?>
                            <span class="text-primary">(filtered)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination Navigation -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Withdrawals pagination">
                            <ul class="pagination pagination-sm mb-0 justify-content-center justify-content-md-end">
                                <!-- Previous Page -->
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <?php
                                        $prevParams = $_GET;
                                        unset($prevParams['route'], $prevParams['page']);
                                        $prevParams['page'] = $page - 1;
                                        ?>
                                        <a class="page-link"
                                           href="?route=withdrawals&<?= http_build_query($prevParams) ?>"
                                           aria-label="Go to previous page">
                                            <span aria-hidden="true">&laquo;</span>
                                            <span class="d-none d-sm-inline ms-1">Previous</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <span aria-hidden="true">&laquo;</span>
                                            <span class="d-none d-sm-inline ms-1">Previous</span>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <!-- Page Numbers (Smart Pagination) -->
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                // Show first page if not in range
                                if ($startPage > 1):
                                    $firstParams = $_GET;
                                    unset($firstParams['route'], $firstParams['page']);
                                    $firstParams['page'] = 1;
                                ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                           href="?route=withdrawals&<?= http_build_query($firstParams) ?>"
                                           aria-label="Go to page 1">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Page number buttons -->
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <li class="page-item active" aria-current="page">
                                            <span class="page-link">
                                                <?= $i ?>
                                                <span class="visually-hidden">(current)</span>
                                            </span>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item">
                                            <?php
                                            $pageParams = $_GET;
                                            unset($pageParams['route'], $pageParams['page']);
                                            $pageParams['page'] = $i;
                                            ?>
                                            <a class="page-link"
                                               href="?route=withdrawals&<?= http_build_query($pageParams) ?>"
                                               aria-label="Go to page <?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Show last page if not in range -->
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <?php
                                        $lastParams = $_GET;
                                        unset($lastParams['route'], $lastParams['page']);
                                        $lastParams['page'] = $totalPages;
                                        ?>
                                        <a class="page-link"
                                           href="?route=withdrawals&<?= http_build_query($lastParams) ?>"
                                           aria-label="Go to page <?= $totalPages ?>">
                                            <?= $totalPages ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next Page -->
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <?php
                                        $nextParams = $_GET;
                                        unset($nextParams['route'], $nextParams['page']);
                                        $nextParams['page'] = $page + 1;
                                        ?>
                                        <a class="page-link"
                                           href="?route=withdrawals&<?= http_build_query($nextParams) ?>"
                                           aria-label="Go to next page">
                                            <span class="d-none d-sm-inline me-1">Next</span>
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <span class="d-none d-sm-inline me-1">Next</span>
                                            <span aria-hidden="true">&raquo;</span>
                                        </span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Records per page change handler
document.getElementById('recordsPerPage')?.addEventListener('change', function() {
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', this.value);
    params.set('page', '1'); // Reset to first page
    window.location.search = params.toString();
});

// Export handler
document.getElementById('exportBtn')?.addEventListener('click', function() {
    alert('Export functionality coming soon!');
});

// Print handler
document.getElementById('printBtn')?.addEventListener('click', function() {
    window.print();
});
</script>
