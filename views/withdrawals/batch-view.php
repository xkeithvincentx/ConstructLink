<?php
/**
 * ConstructLink™ - Withdrawal Batch Details
 * View and manage multiple consumable withdrawal batches
 *
 * Refactored for:
 * - CSP compliance (no inline styles/scripts)
 * - WCAG 2.1 AA accessibility
 * - MVC separation (business logic in helper)
 * - DRY principles (reusable partials)
 */

// Start output buffering
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

// Load helpers
require_once APP_ROOT . '/helpers/ViewHelper.php';
require_once APP_ROOT . '/helpers/WithdrawalViewHelper.php';

if (!isset($batch) || !$batch) {
    echo '<div class="alert alert-danger" role="alert">Withdrawal batch not found</div>';
    return;
}

// Determine if this is a multi-item batch
$isMultiItem = count($batch['items']) > 1;
$isSingleItem = count($batch['items']) === 1;

// For single item, extract the first (and only) item
$singleItem = $isSingleItem ? $batch['items'][0] : null;
?>

<!-- Main Content -->
<main aria-label="Withdrawal batch details">

<!-- Batch Header -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-start">
            <div class="col-12 col-md-8">
                <h4 class="mb-2">
                    <i class="bi bi-<?= WithdrawalViewHelper::getBatchIcon($isMultiItem) ?> me-2" aria-hidden="true"></i>
                    <span class="d-inline-block"><?= htmlspecialchars($batch['batch_reference']) ?></span>
                    <?php if ($isSingleItem): ?>
                        <span class="text-muted fs-6 d-block d-md-inline mt-1 mt-md-0">— <?= htmlspecialchars($singleItem['consumable_name']) ?></span>
                    <?php endif; ?>
                </h4>
                <p class="text-muted mb-2">
                    <i class="bi bi-person me-1" aria-hidden="true"></i>
                    <strong>Receiver:</strong> <?= htmlspecialchars($batch['receiver_name']) ?>
                    <?php if ($batch['receiver_contact']): ?>
                        <span class="ms-2">
                            <i class="bi bi-telephone me-1" aria-hidden="true"></i><?= htmlspecialchars($batch['receiver_contact']) ?>
                        </span>
                    <?php endif; ?>
                </p>
                <p class="text-muted mb-0">
                    <i class="bi bi-calendar me-1" aria-hidden="true"></i>
                    <strong>Created:</strong> <?= date('M d, Y H:i', strtotime($batch['created_at'])) ?>
                    <?php if ($batch['release_date']): ?>
                        <span class="ms-3 text-success">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                            <strong>Released:</strong> <?= date('M d, Y', strtotime($batch['release_date'])) ?>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
                <div class="d-flex flex-column align-items-md-end align-items-start gap-2">
                    <span class="fs-6"><?= ViewHelper::renderStatusBadge($batch['status']) ?></span>
                    <span class="badge bg-info" role="status">
                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Full MVA Workflow
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="mb-4">
    <?php include APP_ROOT . '/views/withdrawals/partials/_batch_action_buttons.php'; ?>
</div>

<div class="row">
    <!-- Left Column: Items List -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-<?= WithdrawalViewHelper::getBatchIcon($isMultiItem) ?> me-2" aria-hidden="true"></i>
                    <?= $isMultiItem ? 'Consumables in This Batch' : 'Consumable Details' ?>
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
                            <div class="card mb-3 border">
                                <div class="card-body">
                                    <!-- Header with name and status -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <strong class="d-block"><?= htmlspecialchars($item['consumable_name']) ?></strong>
                                            <small class="text-muted d-block"><?= htmlspecialchars($item['consumable_ref']) ?></small>
                                            <small class="text-muted d-block"><?= htmlspecialchars($item['category_name']) ?></small>
                                        </div>
                                        <?= ViewHelper::renderStatusBadge($item['status']) ?>
                                    </div>

                                    <!-- Quantity info -->
                                    <div class="row text-center g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Quantity</small>
                                            <span class="badge bg-primary"><?= $item['quantity'] ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Unit</small>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($item['unit'] ?? 'N/A') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Mobile Totals Card -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <strong class="d-block mb-2">Totals</strong>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Total Items</small>
                                        <strong><?= count($batch['items']) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Total Quantity</small>
                                        <strong><?= WithdrawalViewHelper::getTotalQuantity($batch['items']) ?></strong>
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
                                    <th scope="col">Consumable</th>
                                    <th scope="col">Reference</th>
                                    <th scope="col">Category</th>
                                    <th scope="col" class="text-center">Quantity</th>
                                    <th scope="col">Unit</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batch['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item['consumable_name']) ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($item['consumable_ref']) ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($item['category_name']) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= $item['quantity'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($item['unit'] ?? 'N/A') ?></span>
                                        </td>
                                        <td>
                                            <?= ViewHelper::renderStatusBadge($item['status']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th scope="row" colspan="3">Total</th>
                                    <th class="text-center">
                                        <strong><?= WithdrawalViewHelper::getTotalQuantity($batch['items']) ?></strong>
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- Single Item: Detailed View -->
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Consumable:</dt>
                                <dd class="col-sm-7">
                                    <strong><?= htmlspecialchars($singleItem['consumable_name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($singleItem['consumable_ref']) ?></small>
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

                                <dt class="col-sm-5">Unit:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($singleItem['unit'] ?? 'N/A') ?></span>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Status:</dt>
                                <dd class="col-sm-7">
                                    <?= ViewHelper::renderStatusBadge($singleItem['status']) ?>
                                </dd>

                                <dt class="col-sm-5">Available Qty:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-success"><?= $singleItem['available_quantity_before'] ?? 'N/A' ?></span>
                                </dd>
                            </dl>
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
                        <i class="bi bi-card-text me-2" aria-hidden="true"></i>Purpose
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
    <aside class="col-lg-4" aria-label="Batch metadata and timeline">
        <!-- Batch Details -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Batch Details
                </h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Created:</dt>
                    <dd class="col-sm-7"><?= date('M d, Y H:i', strtotime($batch['created_at'])) ?></dd>

                    <dt class="col-sm-5">Created By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($batch['created_by_name']) ?></dd>

                    <dt class="col-sm-5">Total Items:</dt>
                    <dd class="col-sm-7"><?= count($batch['items']) ?></dd>

                    <dt class="col-sm-5">Total Quantity:</dt>
                    <dd class="col-sm-7"><?= WithdrawalViewHelper::getTotalQuantity($batch['items']) ?></dd>
                </dl>
            </div>
        </div>

        <!-- MVA Workflow Timeline (Collapsible) -->
        <?php
        $collapseId = 'workflowTimeline';
        $showExpanded = false; // Collapsed by default for cleaner view
        include APP_ROOT . '/views/withdrawals/partials/_workflow_timeline.php';
        ?>
    </aside>
</div>

</main>

<!-- Load withdrawal batch detail view CSS -->
<?php
require_once APP_ROOT . '/helpers/AssetHelper.php';
AssetHelper::loadModuleCSS('withdrawal-batch-detail');
?>

<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout
$pageTitle = htmlspecialchars($batch['batch_reference']) . ' - ConstructLink™';
$pageHeader = 'Withdrawal Batch Details';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
    ['title' => $batch['batch_reference'], 'url' => null]
];

include APP_ROOT . '/views/layouts/main.php';
?>
