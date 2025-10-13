<?php
/**
 * ConstructLink™ - Borrowed Tool Batch Details
 * View and manage a multi-item borrowing batch
 * Developed by: Ranoa Digital Solutions
 */

// Start output buffering
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

if (!isset($batch) || !$batch) {
    echo '<div class="alert alert-danger">Batch not found</div>';
    return;
}

// Status badge colors
$statusColors = [
    'Draft' => 'secondary',
    'Pending Verification' => 'warning',
    'Pending Approval' => 'info',
    'Approved' => 'success',
    'Released' => 'primary',
    'Partially Returned' => 'warning',
    'Returned' => 'secondary',
    'Overdue' => 'danger',
    'Canceled' => 'dark'
];

$statusColor = $statusColors[$batch['status']] ?? 'secondary';
?>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $messages = [
        'batch_created' => 'Batch created successfully',
        'batch_verified' => 'Batch verified successfully',
        'batch_approved' => 'Batch approved successfully',
        'batch_released' => 'Batch released to borrower',
        'batch_returned' => 'Batch return processed successfully',
        'batch_canceled' => 'Batch canceled'
    ];
    $messageText = $messages[$_GET['message']] ?? 'Operation completed successfully';
    ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($messageText) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Batch Header -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-2">
                    <i class="bi bi-cart3 me-2"></i>
                    Batch: <?= htmlspecialchars($batch['batch_reference']) ?>
                </h4>
                <p class="text-muted mb-2">
                    <i class="bi bi-person me-1"></i>
                    <strong>Borrower:</strong> <?= htmlspecialchars($batch['borrower_name']) ?>
                    <?php if ($batch['borrower_contact']): ?>
                        <span class="ms-2">
                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($batch['borrower_contact']) ?>
                        </span>
                    <?php endif; ?>
                </p>
                <p class="text-muted mb-0">
                    <i class="bi bi-calendar me-1"></i>
                    <strong>Expected Return:</strong> <?= date('M d, Y', strtotime($batch['expected_return'])) ?>
                    <?php if ($batch['actual_return']): ?>
                        <span class="ms-3 text-success">
                            <i class="bi bi-check-circle me-1"></i>
                            <strong>Returned:</strong> <?= date('M d, Y', strtotime($batch['actual_return'])) ?>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <h5 class="mb-2">
                    <span class="badge bg-<?= $statusColor ?> fs-6">
                        <?= htmlspecialchars($batch['status']) ?>
                    </span>
                </h5>
                <?php if ($batch['is_critical_batch']): ?>
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-shield-check me-1"></i>Critical Tools - Full MVA
                    </span>
                <?php else: ?>
                    <span class="badge bg-success">
                        <i class="bi bi-lightning me-1"></i>Basic Tools - Streamlined
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="mb-4 d-flex gap-2 flex-wrap">
    <a href="?route=borrowed-tools" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>

    <?php if ($batch['status'] === 'Pending Verification' && hasRole(['Project Manager', 'System Admin'])): ?>
        <a href="?route=borrowed-tools/batch/verify&id=<?= $batch['id'] ?>" class="btn btn-warning">
            <i class="bi bi-check-square me-1"></i>Verify Batch
        </a>
    <?php endif; ?>

    <?php if ($batch['status'] === 'Pending Approval' && hasRole(['Asset Director', 'Finance Director', 'System Admin'])): ?>
        <a href="?route=borrowed-tools/batch/approve&id=<?= $batch['id'] ?>" class="btn btn-info">
            <i class="bi bi-shield-check me-1"></i>Approve Batch
        </a>
    <?php endif; ?>

    <?php if ($batch['status'] === 'Approved' && hasRole(['Warehouseman', 'System Admin'])): ?>
        <a href="?route=borrowed-tools/batch/release&id=<?= $batch['id'] ?>" class="btn btn-success">
            <i class="bi bi-box-arrow-right me-1"></i>Release to Borrower
        </a>
    <?php endif; ?>

    <?php if (in_array($batch['status'], ['Released', 'Partially Returned']) && hasRole(['Warehouseman', 'Site Inventory Clerk', 'System Admin'])): ?>
        <a href="?route=borrowed-tools/batch/return&id=<?= $batch['id'] ?>" class="btn btn-primary">
            <i class="bi bi-arrow-return-left me-1"></i>Process Return
        </a>
    <?php endif; ?>

    <?php if (in_array($batch['status'], ['Pending Verification', 'Pending Approval', 'Approved'])): ?>
        <a href="?route=borrowed-tools/batch/cancel&id=<?= $batch['id'] ?>" class="btn btn-danger">
            <i class="bi bi-x-circle me-1"></i>Cancel Batch
        </a>
    <?php endif; ?>

    <a href="?route=borrowed-tools/batch/print&id=<?= $batch['id'] ?>" class="btn btn-outline-primary" target="_blank">
        <i class="bi bi-printer me-1"></i>Print Form (4 per page)
    </a>
</div>

<div class="row">
    <!-- Left Column: Items List -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>Items in This Batch
                    <span class="badge bg-primary ms-2"><?= count($batch['items']) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Borrowed</th>
                                <th class="text-center">Returned</th>
                                <th class="text-center">Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batch['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['asset_name']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($item['asset_ref']) ?></small>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($item['category_name']) ?></small>
                                        <?php if ($item['acquisition_cost'] > 50000): ?>
                                            <br><span class="badge bg-warning text-dark">Critical</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $item['quantity'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?= $item['quantity_returned'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php $remaining = $item['quantity'] - $item['quantity_returned']; ?>
                                        <span class="badge bg-<?= $remaining > 0 ? 'warning' : 'secondary' ?>">
                                            <?= $remaining ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $itemStatusColors = [
                                            'Pending Verification' => 'warning',
                                            'Pending Approval' => 'info',
                                            'Approved' => 'success',
                                            'Borrowed' => 'primary',
                                            'Returned' => 'secondary',
                                            'Canceled' => 'dark'
                                        ];
                                        $itemStatusColor = $itemStatusColors[$item['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $itemStatusColor ?>">
                                            <?= htmlspecialchars($item['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th>Total</th>
                                <th class="text-center">
                                    <strong><?= $batch['total_quantity'] ?></strong>
                                </th>
                                <th class="text-center">
                                    <strong><?= array_sum(array_column($batch['items'], 'quantity_returned')) ?></strong>
                                </th>
                                <th class="text-center">
                                    <strong><?= $batch['total_quantity'] - array_sum(array_column($batch['items'], 'quantity_returned')) ?></strong>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Details & Timeline -->
    <div class="col-lg-4">
        <!-- Batch Details -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>Batch Details
                </h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Created:</dt>
                    <dd class="col-sm-7"><?= date('M d, Y H:i', strtotime($batch['created_at'])) ?></dd>

                    <dt class="col-sm-5">Issued By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($batch['issued_by_name']) ?></dd>

                    <dt class="col-sm-5">Total Items:</dt>
                    <dd class="col-sm-7"><?= $batch['total_items'] ?></dd>

                    <dt class="col-sm-5">Total Quantity:</dt>
                    <dd class="col-sm-7"><?= $batch['total_quantity'] ?></dd>

                    <?php if ($batch['purpose']): ?>
                        <dt class="col-sm-5">Purpose:</dt>
                        <dd class="col-sm-7"><?= nl2br(htmlspecialchars($batch['purpose'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- MVA Workflow Timeline -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>Workflow Timeline
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <!-- Created/Issued -->
                    <div class="mb-3">
                        <div class="d-flex align-items-start">
                            <div class="me-2">
                                <i class="bi bi-circle-fill text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Created</strong>
                                <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['created_at'])) ?></small>
                                <br><small class="text-muted">by <?= htmlspecialchars($batch['issued_by_name']) ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Verified -->
                    <?php if ($batch['verified_by']): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="me-2">
                                    <i class="bi bi-circle-fill text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Verified</strong>
                                    <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['verification_date'])) ?></small>
                                    <br><small class="text-muted">by <?= htmlspecialchars($batch['verified_by_name']) ?></small>
                                    <?php if ($batch['verification_notes']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($batch['verification_notes']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($batch['status'] === 'Pending Verification'): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="me-2">
                                    <i class="bi bi-circle text-warning"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Awaiting Verification</strong>
                                    <br><small class="text-muted">Project Manager review required</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Approved -->
                    <?php if ($batch['approved_by']): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="me-2">
                                    <i class="bi bi-circle-fill text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Approved</strong>
                                    <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['approval_date'])) ?></small>
                                    <br><small class="text-muted">by <?= htmlspecialchars($batch['approved_by_name']) ?></small>
                                    <?php if ($batch['approval_notes']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($batch['approval_notes']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($batch['status'] === 'Pending Approval'): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="me-2">
                                    <i class="bi bi-circle text-info"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Awaiting Approval</strong>
                                    <br><small class="text-muted">Director authorization required</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Released -->
                    <?php if ($batch['released_by']): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="me-2">
                                    <i class="bi bi-circle-fill text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Released to Borrower</strong>
                                    <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['release_date'])) ?></small>
                                    <br><small class="text-muted">by <?= htmlspecialchars($batch['released_by_name']) ?></small>
                                    <?php if ($batch['release_notes']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($batch['release_notes']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($batch['status'] === 'Approved'): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="me-2">
                                    <i class="bi bi-circle text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Ready for Release</strong>
                                    <br><small class="text-muted">Warehouseman can release to borrower</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Returned -->
                    <?php if ($batch['returned_by']): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="me-2">
                                    <i class="bi bi-circle-fill text-secondary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Returned</strong>
                                    <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['return_date'])) ?></small>
                                    <br><small class="text-muted">by <?= htmlspecialchars($batch['returned_by_name']) ?></small>
                                    <?php if ($batch['return_notes']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($batch['return_notes']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Canceled -->
                    <?php if ($batch['canceled_by']): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="me-2">
                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Canceled</strong>
                                    <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['cancellation_date'])) ?></small>
                                    <br><small class="text-muted">by <?= htmlspecialchars($batch['canceled_by_name']) ?></small>
                                    <?php if ($batch['cancellation_reason']): ?>
                                        <br><small class="text-muted">Reason: <?= htmlspecialchars($batch['cancellation_reason']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout
$pageTitle = 'Batch ' . htmlspecialchars($batch['batch_reference']) . ' - ConstructLink™';
$pageHeader = 'Borrowed Tool Batch Details';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Batch ' . $batch['batch_reference'], 'url' => null]
];

include APP_ROOT . '/views/layouts/main.php';
?>
