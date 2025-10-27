<?php
/**
 * ConstructLink™ - Borrowed Equipment Request Details
 * View and manage single or multiple item borrowing requests
 */

// Start output buffering
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

// Load ViewHelper for reusable UI components
require_once APP_ROOT . '/helpers/ViewHelper.php';

if (!isset($batch) || !$batch) {
    echo '<div class="alert alert-danger">Request not found</div>';
    return;
}

// Determine if this is a multi-item request
$isMultiItem = count($batch['items']) > 1;
$isSingleItem = count($batch['items']) === 1;

// For single item, extract the first (and only) item
$singleItem = $isSingleItem ? $batch['items'][0] : null;
?>

<!-- Request Header -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-start">
            <div class="col-12 col-md-8">
                <h4 class="mb-2">
                    <i class="bi bi-<?= $isMultiItem ? 'cart3' : 'box-seam' ?> me-2"></i>
                    <span class="d-inline-block"><?= htmlspecialchars($batch['batch_reference']) ?></span>
                    <?php if ($isSingleItem): ?>
                        <span class="text-muted fs-6 d-block d-md-inline mt-1 mt-md-0">— <?= htmlspecialchars($singleItem['asset_name']) ?></span>
                    <?php endif; ?>
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
            <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
                <div class="d-flex flex-column align-items-md-end align-items-start gap-2">
                    <span class="fs-6"><?= ViewHelper::renderStatusBadge($batch['status']) ?></span>
                    <?php if ($batch['is_critical_batch']): ?>
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Critical Equipment - Full MVA
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success">
                            <i class="bi bi-lightning me-1" aria-hidden="true"></i>Basic Equipment - Streamlined
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="mb-4">
    <!-- Mobile: Stacked buttons -->
    <div class="d-md-none d-grid gap-2">
        <a href="?route=borrowed-tools" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>

        <?php if ($batch['status'] === 'Pending Verification' && hasRole(['Project Manager', 'System Admin'])): ?>
            <a href="?route=borrowed-tools/batch/verify&id=<?= $batch['id'] ?>" class="btn btn-warning">
                <i class="bi bi-check-square me-1"></i>Verify Request
            </a>
        <?php endif; ?>

        <?php if ($batch['status'] === 'Pending Approval' && hasRole(['Asset Director', 'Finance Director', 'System Admin'])): ?>
            <a href="?route=borrowed-tools/batch/approve&id=<?= $batch['id'] ?>" class="btn btn-info">
                <i class="bi bi-shield-check me-1"></i>Approve Request
            </a>
        <?php endif; ?>

        <?php if ($batch['status'] === 'Approved' && hasRole(['Warehouseman', 'System Admin'])): ?>
            <a href="?route=borrowed-tools/batch/release&id=<?= $batch['id'] ?>" class="btn btn-success">
                <i class="bi bi-box-arrow-right me-1"></i>Release to Borrower
            </a>
        <?php endif; ?>


        <?php if (in_array($batch['status'], ['Pending Verification', 'Pending Approval', 'Approved'])): ?>
            <a href="?route=borrowed-tools/batch/cancel&id=<?= $batch['id'] ?>" class="btn btn-danger">
                <i class="bi bi-x-circle me-1"></i>Cancel Request
            </a>
        <?php endif; ?>

        <a href="?route=borrowed-tools/batch/print&id=<?= $batch['id'] ?>" class="btn btn-outline-primary" target="_blank">
            <i class="bi bi-printer me-1"></i>Print Form
        </a>
    </div>

    <!-- Desktop: Inline buttons -->
    <div class="d-none d-md-flex gap-2 flex-wrap">
        <a href="?route=borrowed-tools" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>

        <?php if ($batch['status'] === 'Pending Verification' && hasRole(['Project Manager', 'System Admin'])): ?>
            <a href="?route=borrowed-tools/batch/verify&id=<?= $batch['id'] ?>" class="btn btn-warning">
                <i class="bi bi-check-square me-1"></i>Verify Request
            </a>
        <?php endif; ?>

        <?php if ($batch['status'] === 'Pending Approval' && hasRole(['Asset Director', 'Finance Director', 'System Admin'])): ?>
            <a href="?route=borrowed-tools/batch/approve&id=<?= $batch['id'] ?>" class="btn btn-info">
                <i class="bi bi-shield-check me-1"></i>Approve Request
            </a>
        <?php endif; ?>

        <?php if ($batch['status'] === 'Approved' && hasRole(['Warehouseman', 'System Admin'])): ?>
            <a href="?route=borrowed-tools/batch/release&id=<?= $batch['id'] ?>" class="btn btn-success">
                <i class="bi bi-box-arrow-right me-1"></i>Release to Borrower
            </a>
        <?php endif; ?>


        <?php if (in_array($batch['status'], ['Pending Verification', 'Pending Approval', 'Approved'])): ?>
            <a href="?route=borrowed-tools/batch/cancel&id=<?= $batch['id'] ?>" class="btn btn-danger">
                <i class="bi bi-x-circle me-1"></i>Cancel Request
            </a>
        <?php endif; ?>

        <a href="?route=borrowed-tools/batch/print&id=<?= $batch['id'] ?>" class="btn btn-outline-primary" target="_blank">
            <i class="bi bi-printer me-1"></i>Print Form
        </a>
    </div>
</div>

<div class="row">
    <!-- Left Column: Items List -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-<?= $isMultiItem ? 'list-ul' : 'box-seam' ?> me-2"></i>
                    <?= $isMultiItem ? 'Items in This Request' : 'Item Details' ?>
                    <?php if ($isMultiItem): ?>
                        <span class="badge bg-primary ms-2"><?= count($batch['items']) ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($isMultiItem): ?>
                    <!-- Multi-item: Mobile Card View -->
                    <div class="d-md-none">
                        <?php foreach ($batch['items'] as $item): ?>
                            <?php
                            $remaining = $item['quantity'] - $item['quantity_returned'];
                            ?>
                            <div class="card mb-3 border">
                                <div class="card-body">
                                    <!-- Header with name and status -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <strong class="d-block"><?= htmlspecialchars($item['asset_name']) ?></strong>
                                            <small class="text-muted d-block"><?= htmlspecialchars($item['asset_ref']) ?></small>
                                            <small class="text-muted d-block"><?= htmlspecialchars($item['category_name']) ?></small>
                                        </div>
                                        <?= ViewHelper::renderStatusBadge($item['status']) ?>
                                    </div>

                                    <?php if ($item['acquisition_cost'] > 50000): ?>
                                        <div class="mb-2">
                                            <?= ViewHelper::renderCriticalToolBadge($item['acquisition_cost']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Quantity info -->
                                    <div class="row text-center g-2">
                                        <div class="col-4">
                                            <small class="text-muted d-block">Borrowed</small>
                                            <span class="badge bg-primary"><?= $item['quantity'] ?></span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">Returned</small>
                                            <span class="badge bg-success"><?= $item['quantity_returned'] ?></span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">Remaining</small>
                                            <span class="badge bg-<?= $remaining > 0 ? 'warning' : 'secondary' ?>">
                                                <?= $remaining ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Condition info -->
                                    <?php if (!empty($item['condition_out']) || !empty($item['condition_returned'])): ?>
                                        <div class="mt-2">
                                            <small class="text-muted d-block mb-1">Condition</small>
                                            <div class="d-flex gap-2 justify-content-center">
                                                <?= ViewHelper::renderConditionBadges($item['condition_out'] ?? null, $item['condition_returned'] ?? null) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Mobile Totals Card -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <strong class="d-block mb-2">Totals</strong>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Borrowed</small>
                                        <strong><?= $batch['total_quantity'] ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Returned</small>
                                        <strong><?= array_sum(array_column($batch['items'], 'quantity_returned')) ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Remaining</small>
                                        <strong><?= $batch['total_quantity'] - array_sum(array_column($batch['items'], 'quantity_returned')) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Multi-item: Desktop Table View -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Borrowed</th>
                                    <th class="text-center">Returned</th>
                                    <th class="text-center">Remaining</th>
                                    <th>Condition</th>
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
                                            <?= ViewHelper::renderConditionBadges($item['condition_out'] ?? null, $item['condition_returned'] ?? null, false) ?>
                                        </td>
                                        <td>
                                            <?= ViewHelper::renderStatusBadge($item['status']) ?>
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
                <?php else: ?>
                    <!-- Single Item: Detailed View -->
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Item:</dt>
                                <dd class="col-sm-7">
                                    <strong><?= htmlspecialchars($singleItem['asset_name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($singleItem['asset_ref']) ?></small>
                                </dd>

                                <dt class="col-sm-5">Category:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($singleItem['category_name'] ?? 'N/A') ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-5">Quantity:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-primary"><?= $singleItem['quantity'] ?></span>
                                </dd>

                                <?php if ($singleItem['acquisition_cost'] > 50000): ?>
                                    <dt class="col-sm-5">Item Type:</dt>
                                    <dd class="col-sm-7">
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-shield-check me-1"></i>Critical (>₱50,000)
                                        </span>
                                    </dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Borrowed:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-primary"><?= $singleItem['quantity'] ?></span>
                                </dd>

                                <dt class="col-sm-5">Returned:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-success"><?= $singleItem['quantity_returned'] ?></span>
                                </dd>

                                <dt class="col-sm-5">Remaining:</dt>
                                <dd class="col-sm-7">
                                    <?php $remaining = $singleItem['quantity'] - $singleItem['quantity_returned']; ?>
                                    <span class="badge bg-<?= $remaining > 0 ? 'warning' : 'secondary' ?>">
                                        <?= $remaining ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-5">Status:</dt>
                                <dd class="col-sm-7">
                                    <?= ViewHelper::renderStatusBadge($singleItem['status']) ?>
                                </dd>

                                <?php if (!empty($singleItem['condition_out']) || !empty($singleItem['condition_returned'])): ?>
                                    <dt class="col-sm-5">Condition:</dt>
                                    <dd class="col-sm-7">
                                        <?= ViewHelper::renderConditionBadges($singleItem['condition_out'] ?? null, $singleItem['condition_returned'] ?? null) ?>
                                    </dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>

                    <!-- Quick Stats for Single Item -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="stat-item">
                                                <?php
                                                // Use release_date as start, or created_at if not released yet
                                                $startDateStr = $batch['release_date'] ?? $batch['created_at'];
                                                $startDate = new DateTime($startDateStr);

                                                // If returned, use return_date, otherwise use today
                                                if (in_array($batch['status'], ['Returned', 'Partially Returned']) && $batch['return_date']) {
                                                    $endDate = new DateTime($batch['return_date']);
                                                } elseif (in_array($batch['status'], ['Released', 'Borrowed', 'Partially Returned'])) {
                                                    $endDate = new DateTime();
                                                } else {
                                                    // Not yet released (Pending, Approved, etc)
                                                    $endDate = $startDate;
                                                }

                                                $duration = $startDate->diff($endDate);
                                                ?>
                                                <div class="stat-value text-primary"><?= $duration->days ?></div>
                                                <div class="stat-label">Days in Use</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-item">
                                                <?php
                                                // Show remaining/overdue if currently borrowed
                                                if (in_array($batch['status'], ['Released', 'Borrowed', 'Partially Returned'])):
                                                    $expectedDate = new DateTime($batch['expected_return']);
                                                    $today = new DateTime();
                                                    $remainingDiff = $today->diff($expectedDate);
                                                    $daysRemaining = $today > $expectedDate ? -$remainingDiff->days : $remainingDiff->days;
                                                ?>
                                                    <div class="stat-value <?= $daysRemaining < 0 ? 'text-danger' : 'text-success' ?>">
                                                        <?= abs($daysRemaining) ?>
                                                    </div>
                                                    <div class="stat-label">
                                                        <?= $daysRemaining < 0 ? 'Days Overdue' : 'Days Remaining' ?>
                                                    </div>
                                                <?php elseif ($batch['status'] === 'Returned'): ?>
                                                    <div class="stat-value text-success">
                                                        <i class="bi bi-check-circle"></i>
                                                    </div>
                                                    <div class="stat-label">Returned</div>
                                                <?php else: ?>
                                                    <div class="stat-value text-muted">-</div>
                                                    <div class="stat-label">Not Released</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Purpose Section (if provided) -->
        <?php if ($batch['purpose']): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-card-text me-2"></i>Purpose
                    </h6>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($batch['purpose'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Details & Timeline -->
    <div class="col-lg-4">
        <!-- Request Details -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>Request Details
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
                </dl>
            </div>
        </div>

        <!-- MVA Workflow Timeline (Collapsible) -->
        <?php
        $collapseId = 'workflowTimeline';
        $showExpanded = false; // Collapsed by default for cleaner view
        include APP_ROOT . '/views/borrowed-tools/partials/_workflow_timeline.php';
        ?>
    </div>
</div>

<!-- Load borrowed tools detail view CSS -->
<?php
require_once APP_ROOT . '/helpers/AssetHelper.php';
AssetHelper::loadModuleCSS('borrowed-tools-detail');
?>

<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout
$pageTitle = htmlspecialchars($batch['batch_reference']) . ' - ConstructLink™';
$pageHeader = 'Borrowed Equipment Request Details';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Equipment', 'url' => '?route=borrowed-tools'],
    ['title' => $batch['batch_reference'], 'url' => null]
];

include APP_ROOT . '/views/layouts/main.php';
?>
