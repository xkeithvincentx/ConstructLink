<?php
/**
 * Borrowed Tools List Partial
 * Displays borrowed tools table/cards with pagination (mobile + desktop views)
 */
?>

<!-- Borrowed Tools Table/Cards -->
<div class="card">
    <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <h6 class="card-title mb-0">Borrowed Tools</h6>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-outline-primary" id="exportBtn" data-action="export">
                <i class="bi bi-file-earmark-excel me-1"></i>
                <span class="d-none d-md-inline">Export</span>
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="printBtn" data-action="print">
                <i class="bi bi-printer me-1"></i>
                <span class="d-none d-md-inline">Print</span>
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($borrowedTools)): ?>
            <div class="text-center py-5">
                <i class="bi bi-tools display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No borrowed tools found</h5>
                <p class="text-muted">Try adjusting your filters or borrow your first tool.</p>
                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk'])): ?>
                    <a href="?route=borrowed-tools/create-batch" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Borrow First Tool
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Mobile Card View (visible on small screens) -->
            <div class="d-md-none">
                <?php foreach ($displayItems as $displayItem): ?>
                    <?php
                    // Determine if this is a batch or single item
                    $isBatch = ($displayItem['type'] === 'batch');
                    $tool = $isBatch ? $displayItem['primary'] : $displayItem['item'];
                    $batchId = $isBatch ? $displayItem['batch_id'] : null;
                    $batchItems = $isBatch ? $displayItem['items'] : [];
                    $batchCount = $isBatch ? count($batchItems) : 0;

                    $expectedReturn = $tool['expected_return'];
                    $isOverdue = $tool['status'] === 'Borrowed' && strtotime($expectedReturn) < time();
                    $isDueSoon = !$isOverdue && $tool['status'] === 'Borrowed' && strtotime($expectedReturn) <= strtotime('+3 days');

                    // Load ViewHelper if not already loaded
                    require_once APP_ROOT . '/helpers/ViewHelper.php';
                    ?>
                    <div class="card mb-3 <?= $isOverdue ? 'border-danger' : ($isDueSoon ? 'border-warning' : '') ?>">
                        <div class="card-body">
                            <!-- Header with ID and Status -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <?php if ($isBatch): ?>
                                        <span class="fw-bold">
                                            <?php if (!empty($tool['batch_reference'])): ?>
                                                <?= htmlspecialchars($tool['batch_reference']) ?>
                                            <?php else: ?>
                                                Batch #<?= $batchId ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="?route=borrowed-tools/view&id=<?= $tool['id'] ?>" class="text-decoration-none fw-bold">
                                            #<?= $tool['id'] ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($isOverdue): ?>
                                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Overdue"></i>
                                    <?php elseif ($isDueSoon): ?>
                                        <i class="bi bi-clock-fill text-warning ms-1" title="Due Soon"></i>
                                    <?php endif; ?>
                                </div>
                                <?= ViewHelper::renderStatusBadge($tool['status']) ?>
                            </div>

                            <!-- Item Details -->
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">Item</small>
                                <?php if ($isBatch): ?>
                                    <div class="fw-medium"><?= $batchCount ?> Equipment Items</div>
                                    <button class="btn btn-sm btn-outline-secondary mt-1 w-100 batch-toggle-mobile"
                                            type="button"
                                            data-batch-id="<?= $batchId ?>">
                                        <i class="bi bi-chevron-down me-1"></i>View Items
                                    </button>
                                <?php else: ?>
                                    <div class="fw-medium"><?= htmlspecialchars($tool['asset_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($tool['asset_ref']) ?>
                                        <?php if (!empty($tool['asset_category'])): ?>
                                            | <?= htmlspecialchars($tool['asset_category']) ?>
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <!-- Borrower -->
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">Borrower</small>
                                <div class="fw-medium">
                                    <i class="bi bi-person me-1"></i>
                                    <?= htmlspecialchars($tool['borrower_name']) ?>
                                </div>
                                <?php if (!empty($tool['borrower_contact'])): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone me-1"></i>
                                        <?= htmlspecialchars($tool['borrower_contact']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <!-- Condition Information -->
                            <?php if (!$isBatch && (!empty($tool['condition_out']) || !empty($tool['condition_returned']))): ?>
                                <div class="mb-2">
                                    <small class="text-muted d-block mb-1">Condition</small>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <?= ViewHelper::renderConditionBadges($tool['condition_out'] ?? null, $tool['condition_returned'] ?? null) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Return Schedule -->
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">
                                    <?= $tool['status'] === 'Returned' ? 'Returned Date' : 'Expected Return' ?>
                                </small>
                                <?php if ($tool['status'] === 'Returned' && !empty($tool['actual_return'])): ?>
                                    <div class="fw-medium text-success">
                                        <?= date('M j, Y', strtotime($tool['actual_return'])) ?>
                                    </div>
                                    <?php
                                    $expectedTime = strtotime($expectedReturn);
                                    $actualTime = strtotime($tool['actual_return']);
                                    $daysDiff = floor(($actualTime - $expectedTime) / 86400);
                                    ?>
                                    <?php if ($daysDiff < 0): ?>
                                        <span class="badge bg-success"><?= abs($daysDiff) ?> days early</span>
                                    <?php elseif ($daysDiff > 0): ?>
                                        <span class="badge bg-warning text-dark"><?= $daysDiff ?> days late</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">On time</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="fw-medium <?= $isOverdue ? 'text-danger' : ($isDueSoon ? 'text-warning' : '') ?>">
                                        <?= date('M j, Y', strtotime($expectedReturn)) ?>
                                    </div>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            <?= abs(floor((time() - strtotime($expectedReturn)) / 86400)) ?> days overdue
                                        </span>
                                    <?php elseif ($isDueSoon): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock me-1"></i>
                                            Due in <?= ceil((strtotime($expectedReturn) - time()) / 86400) ?> days
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="d-grid gap-2">
                                <?php
                                // Determine primary action based on role and status
                                $primaryAction = null;
                                if ($isBatch) {
                                    if ($tool['status'] === 'Pending Verification' && $auth->hasRole(['System Admin', 'Project Manager'])):
                                        $primaryAction = [
                                            'modal' => true,
                                            'modal_id' => 'batchVerifyModal',
                                            'batch_id' => $batchId,
                                            'class' => 'btn-warning',
                                            'icon' => 'check-circle',
                                            'text' => 'Verify Batch'
                                        ];
                                    elseif ($tool['status'] === 'Pending Approval' && $auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])):
                                        $primaryAction = [
                                            'modal' => true,
                                            'modal_id' => 'batchAuthorizeModal',
                                            'batch_id' => $batchId,
                                            'class' => 'btn-success',
                                            'icon' => 'shield-check',
                                            'text' => 'Authorize Batch'
                                        ];
                                    elseif ($tool['status'] === 'Approved' && $auth->hasRole(['System Admin', 'Warehouseman'])):
                                        $primaryAction = [
                                            'modal' => true,
                                            'modal_id' => 'batchReleaseModal',
                                            'batch_id' => $batchId,
                                            'class' => 'btn-info',
                                            'icon' => 'box-arrow-up',
                                            'text' => 'Release Batch'
                                        ];
                                    elseif (in_array($tool['status'], ['Borrowed', 'Partially Returned']) && $auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])):
                                        $primaryAction = [
                                            'modal' => true,
                                            'modal_id' => 'batchReturnModal',
                                            'batch_id' => $batchId,
                                            'class' => $isOverdue ? 'btn-danger' : 'btn-success',
                                            'icon' => 'box-arrow-down',
                                            'text' => $tool['status'] === 'Partially Returned' ? 'Return Remaining' : ($isOverdue ? 'Return Overdue' : 'Return Batch')
                                        ];
                                    endif;
                                }
                                ?>

                                <?php if ($primaryAction): ?>
                                    <?php if (isset($primaryAction['modal']) && $primaryAction['modal']): ?>
                                        <button type="button"
                                                class="btn <?= $primaryAction['class'] ?> batch-action-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#<?= $primaryAction['modal_id'] ?>"
                                                data-batch-id="<?= $primaryAction['batch_id'] ?>">
                                            <i class="bi bi-<?= $primaryAction['icon'] ?> me-1"></i><?= $primaryAction['text'] ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($isBatch): ?>
                                    <a href="?route=borrowed-tools/batch/view&batch_id=<?= $batchId ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </a>
                                <?php else: ?>
                                    <a href="?route=borrowed-tools/view&id=<?= $tool['id'] ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Expandable Batch Items (Mobile) -->
                            <?php if ($isBatch): ?>
                                <div class="batch-items-mobile mt-3" data-batch-id="<?= $batchId ?>" style="display: none;">
                                    <hr>
                                    <h6 class="mb-2"><i class="bi bi-list-ul me-2"></i>Batch Items (<?= $batchCount ?>)</h6>
                                    <?php foreach ($batchItems as $index => $item): ?>
                                        <div class="card card-body mb-2 bg-light">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <small class="text-muted">#<?= $index + 1 ?></small>
                                                <?= ViewHelper::renderStatusBadge($item['status'], false) ?>
                                            </div>
                                            <div class="fw-medium"><?= htmlspecialchars($item['asset_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($item['asset_ref']) ?></small>
                                            <?php if (!empty($item['serial_number'])): ?>
                                                <small class="text-muted">S/N: <code><?= htmlspecialchars($item['serial_number']) ?></code></small>
                                            <?php endif; ?>
                                            <?php if (!empty($item['condition_out']) || !empty($item['condition_returned'])): ?>
                                                <div class="mt-1">
                                                    <small class="text-muted">Condition: </small>
                                                    <?= ViewHelper::renderConditionBadges($item['condition_out'] ?? null, $item['condition_returned'] ?? null) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Table View (visible on medium+ screens) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover" id="borrowedToolsTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="id">
                                Reference
                                <?php if (isset($currentSort) && $currentSort === 'id'): ?>
                                    <i class="bi bi-arrow-<?= $currentOrder === 'asc' ? 'up' : 'down' ?>"></i>
                                <?php endif; ?>
                            </th>
                            <th class="sortable" data-sort="items">
                                Items
                                <?php if (isset($currentSort) && $currentSort === 'items'): ?>
                                    <i class="bi bi-arrow-<?= $currentOrder === 'asc' ? 'up' : 'down' ?>"></i>
                                <?php endif; ?>
                            </th>
                            <th class="sortable" data-sort="borrower">
                                Borrower
                                <?php if (isset($currentSort) && $currentSort === 'borrower'): ?>
                                    <i class="bi bi-arrow-<?= $currentOrder === 'asc' ? 'up' : 'down' ?>"></i>
                                <?php endif; ?>
                            </th>
                            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                                <th>Purpose</th>
                            <?php endif; ?>
                            <th class="sortable" data-sort="date">
                                Date
                                <?php if (isset($currentSort) && $currentSort === 'date'): ?>
                                    <i class="bi bi-arrow-<?= $currentOrder === 'asc' ? 'up' : 'down' ?>"></i>
                                <?php endif; ?>
                            </th>
                            <th>Condition</th>
                            <th class="sortable" data-sort="status">
                                Status
                                <?php if (isset($currentSort) && $currentSort === 'status'): ?>
                                    <i class="bi bi-arrow-<?= $currentOrder === 'asc' ? 'up' : 'down' ?>"></i>
                                <?php endif; ?>
                            </th>
                            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                                <th>MVA</th>
                            <?php endif; ?>
                            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager', 'Site Inventory Clerk'])): ?>
                                <th>Created By</th>
                            <?php endif; ?>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayItems as $displayItem): ?>
                            <?php
                            // Determine if this is a batch or single item
                            $isBatch = ($displayItem['type'] === 'batch');
                            $tool = $isBatch ? $displayItem['primary'] : $displayItem['item'];
                            $batchId = $isBatch ? $displayItem['batch_id'] : null;
                            $batchItems = $isBatch ? $displayItem['items'] : [];
                            $batchCount = $isBatch ? count($batchItems) : 0;

                            $expectedReturn = $tool['expected_return'];
                            $isOverdue = $tool['status'] === 'Borrowed' && strtotime($expectedReturn) < time();
                            $isDueSoon = !$isOverdue && $tool['status'] === 'Borrowed' && strtotime($expectedReturn) <= strtotime('+3 days');
                            $rowClass = $isOverdue ? 'table-danger' : ($isDueSoon ? 'table-warning' : '');
                            ?>
                            <tr class="<?= $rowClass ?> <?= $isBatch ? 'batch-row' : '' ?>" data-batch-id="<?= $batchId ?>">
                                <!-- ID with Visual Priority Indicators and Batch Badge -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($isBatch): ?>
                                            <button class="btn btn-sm btn-outline-secondary me-2 batch-toggle"
                                                    type="button"
                                                    data-batch-id="<?= $batchId ?>"
                                                    title="Click to expand/collapse batch items">
                                                <i class="bi bi-chevron-right"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($isOverdue): ?>
                                            <i class="bi bi-exclamation-triangle-fill text-danger me-1" title="Overdue"></i>
                                        <?php elseif ($isDueSoon): ?>
                                            <i class="bi bi-clock-fill text-warning me-1" title="Due Soon"></i>
                                        <?php endif; ?>
                                        <a href="?route=borrowed-tools/view&id=<?= !empty($tool['batch_id']) ? $tool['batch_id'] : $tool['id'] ?>" class="text-decoration-none fw-medium">
                                            <?php if (!empty($tool['batch_reference'])): ?>
                                                <?= htmlspecialchars($tool['batch_reference']) ?>
                                            <?php else: ?>
                                                #<?= $tool['id'] ?>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </td>

                                <!-- Items -->
                                <td>
                                    <?php if ($isBatch): ?>
                                        <div class="fw-medium"><?= $batchCount ?> Equipment Items</div>
                                    <?php else: ?>
                                        <div class="fw-medium"><?= htmlspecialchars($tool['asset_ref']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($tool['asset_name']) ?></small>
                                    <?php endif; ?>
                                </td>

                                <!-- Enhanced Borrower Info -->
                                <td>
                                    <div>
                                        <div class="fw-medium">
                                            <i class="bi bi-person me-1"></i>
                                            <?= htmlspecialchars($tool['borrower_name']) ?>
                                        </div>
                                        <?php if (!empty($tool['borrower_contact'])): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone me-1"></i>
                                                <?= htmlspecialchars($tool['borrower_contact']) ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($tool['borrower_project'])): ?>
                                            <br><small class="text-primary">
                                                <i class="bi bi-building me-1"></i>
                                                <?= htmlspecialchars($tool['borrower_project']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Purpose (Management Roles Only) -->
                                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                                    <td>
                                        <div class="purpose-cell" title="<?= htmlspecialchars($tool['purpose'] ?? '') ?>">
                                            <?php if (!empty($tool['purpose'])): ?>
                                                <span class="text-truncate d-inline-block" style="max-width: 200px;">
                                                    <?= htmlspecialchars($tool['purpose']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">No purpose specified</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>

                                <!-- Date (Expected Return / Due Status) -->
                                <td>
                                    <?php
                                    $expectedReturn = $tool['expected_return'];

                                    // For active borrowed items (not yet returned)
                                    if (in_array($tool['status'], ['Approved', 'Released', 'Borrowed', 'Partially Returned'])) {
                                        $isOverdue = strtotime($expectedReturn) < time();
                                        $isDueSoon = !$isOverdue && strtotime($expectedReturn) <= strtotime('+3 days');
                                        $hasTimeRemaining = !$isOverdue && !$isDueSoon;
                                    } else {
                                        // For other statuses (Returned, Pending, Canceled, etc.)
                                        $isOverdue = false;
                                        $isDueSoon = false;
                                        $hasTimeRemaining = false;
                                    }

                                    // For returned items, check if they were returned late
                                    $returnedLate = false;
                                    if ($tool['status'] === 'Returned' && !empty($tool['return_date'])) {
                                        $returnedLate = strtotime($tool['return_date']) > strtotime($expectedReturn);
                                    }
                                    ?>
                                    <div class="fw-medium <?= $isOverdue ? 'text-danger' : ($isDueSoon ? 'text-warning' : ($returnedLate ? 'text-danger' : 'text-dark')) ?>">
                                        <?= date('M j, Y', strtotime($expectedReturn)) ?>
                                    </div>
                                    <small class="text-muted"><?= date('l', strtotime($expectedReturn)) ?></small>

                                    <?php if ($isOverdue): ?>
                                        <?php $daysOverdue = abs(floor((time() - strtotime($expectedReturn)) / 86400)); ?>
                                        <br><span class="badge bg-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            <?= $daysOverdue ?> <?= $daysOverdue == 1 ? 'day' : 'days' ?> overdue
                                        </span>
                                    <?php elseif ($isDueSoon): ?>
                                        <?php $daysUntilDue = ceil((strtotime($expectedReturn) - time()) / 86400); ?>
                                        <br><span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock me-1"></i>
                                            Due in <?= $daysUntilDue ?> <?= $daysUntilDue == 1 ? 'day' : 'days' ?>
                                        </span>
                                    <?php elseif ($hasTimeRemaining): ?>
                                        <?php $daysRemaining = ceil((strtotime($expectedReturn) - time()) / 86400); ?>
                                        <br><small class="text-success">
                                            <?= $daysRemaining ?> <?= $daysRemaining == 1 ? 'day' : 'days' ?> remaining
                                        </small>
                                    <?php elseif ($returnedLate): ?>
                                        <?php $daysLate = abs(floor((strtotime($tool['return_date']) - strtotime($expectedReturn)) / 86400)); ?>
                                        <br><small class="text-danger">
                                            Returned <?= $daysLate ?> <?= $daysLate == 1 ? 'day' : 'days' ?> late
                                        </small>
                                    <?php elseif ($tool['status'] === 'Returned'): ?>
                                        <br><small class="text-success">
                                            Returned on time
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <!-- Condition -->
                                <td>
                                    <?php if (!$isBatch): ?>
                                        <?= ViewHelper::renderConditionBadges($tool['condition_out'] ?? null, $tool['condition_returned'] ?? null, false) ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td>
                                    <?= ViewHelper::renderStatusBadge($tool['status']) ?>
                                </td>

                                <!-- MVA Workflow (Management Roles) -->
                                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                                    <td>
                                        <div class="mva-workflow small">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge badge-sm bg-light text-dark me-1">M</span>
                                                <span class="text-truncate" style="max-width: 80px;">
                                                    <?= htmlspecialchars($tool['created_by_name'] ?? 'Unknown') ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($tool['verified_by_name'])): ?>
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="badge badge-sm bg-warning text-dark me-1">V</span>
                                                    <span class="text-truncate" style="max-width: 80px;">
                                                        <?= htmlspecialchars($tool['verified_by_name']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($tool['approved_by_name'])): ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge badge-sm bg-success text-white me-1">A</span>
                                                    <span class="text-truncate" style="max-width: 80px;">
                                                        <?= htmlspecialchars($tool['approved_by_name']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>

                                <!-- Created By (Site Staff Roles) -->
                                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager', 'Site Inventory Clerk'])): ?>
                                    <td>
                                        <div class="small">
                                            <?= htmlspecialchars($tool['created_by_name'] ?? 'Unknown') ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('M j', strtotime($tool['created_at'])) ?>
                                        </small>
                                    </td>
                                <?php endif; ?>

                                <!-- Enhanced Role-Based Actions -->
                                <td>
                                    <div class="action-buttons">
                                        <!-- Primary Action Button (Most Relevant for Current Role) -->
                                        <?php
                                        $primaryAction = null;
                                        $secondaryActions = [];

                                        // For batch items - use modals instead of page links
                                        if ($isBatch) {
                                            // Determine primary action based on role and status
                                            if ($tool['status'] === 'Pending Verification' && $auth->hasRole(['System Admin', 'Project Manager'])):
                                                $primaryAction = [
                                                    'modal' => true,
                                                    'modal_id' => 'batchVerifyModal',
                                                    'batch_id' => $batchId,
                                                    'class' => 'btn-warning',
                                                    'icon' => 'check-circle',
                                                    'text' => 'Verify Batch',
                                                    'title' => 'Verify all items in this batch'
                                                ];
                                            elseif ($tool['status'] === 'Pending Approval' && $auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])):
                                                $primaryAction = [
                                                    'modal' => true,
                                                    'modal_id' => 'batchAuthorizeModal',
                                                    'batch_id' => $batchId,
                                                    'class' => 'btn-success',
                                                    'icon' => 'shield-check',
                                                    'text' => 'Authorize Batch',
                                                    'title' => 'Authorize all items in this batch'
                                                ];
                                            elseif ($tool['status'] === 'Approved' && $auth->hasRole(['System Admin', 'Warehouseman'])):
                                                $primaryAction = [
                                                    'modal' => true,
                                                    'modal_id' => 'batchReleaseModal',
                                                    'batch_id' => $batchId,
                                                    'class' => 'btn-info',
                                                    'icon' => 'box-arrow-up',
                                                    'text' => 'Release Batch',
                                                    'title' => 'Release all items in this batch'
                                                ];
                                            elseif (in_array($tool['status'], ['Borrowed', 'Partially Returned', 'Released']) && $auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])):
                                                $isBorrowedOverdue = strtotime($tool['expected_return']) < time();
                                                $primaryAction = [
                                                    'modal' => true,
                                                    'modal_id' => 'batchReturnModal',
                                                    'batch_id' => $batchId,
                                                    'class' => $isBorrowedOverdue ? 'btn-danger' : 'btn-success',
                                                    'icon' => 'box-arrow-down',
                                                    'text' => $tool['status'] === 'Partially Returned' ? 'Return Remaining' : ($isBorrowedOverdue ? 'Return Overdue' : 'Return Batch'),
                                                    'title' => 'Return items in this batch'
                                                ];
                                            endif;

                                            // View action for batch
                                            $viewAction = [
                                                'url' => "?route=borrowed-tools/batch/view&batch_id={$batchId}",
                                                'class' => 'btn-outline-primary',
                                                'icon' => 'eye',
                                                'text' => '',
                                                'title' => 'View batch details'
                                            ];

                                            // Secondary actions for batch - Extend button
                                            if (in_array($tool['status'], ['Borrowed', 'Partially Returned', 'Overdue', 'Released']) && $auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])):
                                                $secondaryActions[] = [
                                                    'modal' => true,
                                                    'modal_id' => 'batchExtendModal',
                                                    'batch_id' => $batchId,
                                                    'class' => 'btn-outline-secondary',
                                                    'icon' => 'calendar-plus',
                                                    'text' => '',
                                                    'title' => 'Extend return date for batch items'
                                                ];
                                            endif;
                                        } else {
                                            // Single item - use regular page links
                                            if ($tool['status'] === 'Pending Verification' && $auth->hasRole(['System Admin', 'Project Manager'])):
                                                $primaryAction = [
                                                    'url' => "?route=borrowed-tools/verify&id={$tool['id']}",
                                                    'class' => 'btn-warning',
                                                    'icon' => 'check-circle',
                                                    'text' => 'Verify',
                                                    'title' => 'Verify this tool borrowing request'
                                                ];
                                            elseif ($tool['status'] === 'Pending Approval' && $auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])):
                                                $primaryAction = [
                                                    'url' => "?route=borrowed-tools/approve&id={$tool['id']}",
                                                    'class' => 'btn-success',
                                                    'icon' => 'shield-check',
                                                    'text' => 'Approve',
                                                    'title' => 'Approve this tool borrowing request'
                                                ];
                                            elseif ($tool['status'] === 'Approved' && $auth->hasRole(['System Admin', 'Warehouseman'])):
                                                $primaryAction = [
                                                    'url' => "?route=borrowed-tools/borrow&id={$tool['id']}",
                                                    'class' => 'btn-info',
                                                    'icon' => 'box-arrow-up',
                                                    'text' => 'Issue Tool',
                                                    'title' => 'Mark tool as issued to borrower'
                                                ];
                                            elseif ($tool['status'] === 'Borrowed' && $auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])):
                                                $isBorrowedOverdue = strtotime($tool['expected_return']) < time();
                                                $primaryAction = [
                                                    'modal' => true,
                                                    'modal_id' => 'batchReturnModal',
                                                    'batch_id' => !empty($tool['batch_id']) ? $tool['batch_id'] : $tool['id'],
                                                    'class' => $isBorrowedOverdue ? 'btn-danger' : 'btn-success',
                                                    'icon' => 'box-arrow-down',
                                                    'text' => $isBorrowedOverdue ? 'Return Overdue' : 'Return Tool',
                                                    'title' => 'Mark tool as returned'
                                                ];
                                            endif;

                                            // Always available: View Details
                                            $viewAction = [
                                                'url' => "?route=borrowed-tools/view&id={$tool['id']}",
                                                'class' => 'btn-outline-primary',
                                                'icon' => 'eye',
                                                'text' => '',
                                                'title' => 'View full details'
                                            ];

                                            // Secondary actions based on role and status
                                            // Allow extend for both Borrowed and Overdue status
                                            if (in_array($tool['status'], ['Borrowed', 'Overdue']) && $auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])):
                                                $secondaryActions[] = [
                                                    'url' => "?route=borrowed-tools/extend&id={$tool['id']}",
                                                    'class' => 'btn-outline-secondary',
                                                    'icon' => 'calendar-plus',
                                                    'text' => '',
                                                    'title' => 'Extend return date'
                                                ];
                                            endif;
                                        }

                                        if (in_array($tool['status'], ['Pending Verification', 'Pending Approval', 'Approved']) &&
                                            $auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])):
                                            $secondaryActions[] = [
                                                'url' => "?route=borrowed-tools/cancel&id={$tool['id']}",
                                                'class' => 'btn-outline-danger',
                                                'icon' => 'x-circle',
                                                'text' => '',
                                                'title' => 'Cancel request'
                                            ];
                                        endif;
                                        ?>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <!-- Left: Action Buttons -->
                                            <div class="d-flex gap-1">
                                                <!-- Primary Action -->
                                                <?php if ($primaryAction): ?>
                                                    <?php if (isset($primaryAction['modal']) && $primaryAction['modal']): ?>
                                                        <!-- Batch action - opens modal -->
                                                        <button type="button"
                                                                class="btn btn-sm <?= $primaryAction['class'] ?> batch-action-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#<?= $primaryAction['modal_id'] ?>"
                                                                data-batch-id="<?= $primaryAction['batch_id'] ?>"
                                                                title="<?= $primaryAction['title'] ?>">
                                                            <i class="bi bi-<?= $primaryAction['icon'] ?>"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <!-- Single item action - regular link -->
                                                        <a href="<?= $primaryAction['url'] ?>"
                                                           class="btn btn-sm <?= $primaryAction['class'] ?>"
                                                           title="<?= $primaryAction['title'] ?>">
                                                            <i class="bi bi-<?= $primaryAction['icon'] ?>"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <!-- Overdue Reminder Button (if applicable) -->
                                                <?php if ($isOverdue && $auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-warning overdue-reminder-btn"
                                                            data-tool-id="<?= $tool['id'] ?>"
                                                            aria-label="Send overdue reminder"
                                                            title="Send overdue reminder">
                                                        <i class="bi bi-bell" aria-hidden="true"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Secondary Actions Dropdown -->
                                                <?php if (!empty($secondaryActions)): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php foreach ($secondaryActions as $action): ?>
                                                                <li>
                                                                    <?php if (isset($action['modal']) && $action['modal']): ?>
                                                                        <!-- Modal-based action -->
                                                                        <a class="dropdown-item batch-action-btn"
                                                                           href="#"
                                                                           data-bs-toggle="modal"
                                                                           data-bs-target="#<?= $action['modal_id'] ?>"
                                                                           data-batch-id="<?= $action['batch_id'] ?>"
                                                                           title="<?= $action['title'] ?>">
                                                                            <i class="bi bi-<?= $action['icon'] ?> me-2"></i><?= $action['title'] ?>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <!-- URL-based action -->
                                                                        <a class="dropdown-item" href="<?= $action['url'] ?>" title="<?= $action['title'] ?>">
                                                                            <i class="bi bi-<?= $action['icon'] ?> me-2"></i><?= $action['title'] ?>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Right: View Button -->
                                            <?php if ($viewAction): ?>
                                                <a href="<?= $viewAction['url'] ?>"
                                                   class="btn btn-sm <?= $viewAction['class'] ?>"
                                                   title="<?= $viewAction['title'] ?>">
                                                    <i class="bi bi-<?= $viewAction['icon'] ?>"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Expandable Batch Items Row (hidden by default) -->
                            <?php if ($isBatch): ?>
                                <tr class="batch-items-row" data-batch-id="<?= $batchId ?>" style="display: none;">
                                    <td colspan="100%" class="p-0">
                                        <div class="batch-items-container bg-light p-3">
                                            <h6 class="mb-3"><i class="bi bi-list-ul me-2"></i>Batch Items (<?= $batchCount ?>)</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-secondary">
                                                        <tr>
                                                            <th style="width: 5%">#</th>
                                                            <th style="width: 25%">Equipment</th>
                                                            <th style="width: 10%">Reference</th>
                                                            <th style="width: 6%">Borrowed</th>
                                                            <th style="width: 6%">Returned</th>
                                                            <th style="width: 6%">Remaining</th>
                                                            <th style="width: 8%">Condition</th>
                                                            <th style="width: 10%">Serial Number</th>
                                                            <th style="width: 8%">Status</th>
                                                            <th style="width: 16%">Notes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($batchItems as $index => $item): ?>
                                                            <?php
                                                            $borrowed = $item['quantity'] ?? 1;
                                                            $returned = $item['quantity_returned'] ?? 0;
                                                            $remaining = $borrowed - $returned;
                                                            ?>
                                                            <tr data-item-id="<?= $item['id'] ?>" data-asset-id="<?= $item['asset_id'] ?>">
                                                                <td><?= $index + 1 ?></td>
                                                                <td>
                                                                    <strong><?= htmlspecialchars($item['asset_name']) ?></strong>
                                                                    <?php if (!empty($item['asset_category'])): ?>
                                                                        <br><small class="text-muted"><?= htmlspecialchars($item['asset_category']) ?></small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= htmlspecialchars($item['asset_ref']) ?></td>
                                                                <td class="text-center"><span class="badge bg-primary"><?= $borrowed ?></span></td>
                                                                <td class="text-center"><span class="badge bg-success"><?= $returned ?></span></td>
                                                                <td class="text-center"><span class="badge bg-<?= $remaining > 0 ? 'warning' : 'secondary' ?>"><?= $remaining ?></span></td>
                                                                <td>
                                                                    <?= ViewHelper::renderConditionBadges($item['condition_out'] ?? null, $item['condition_returned'] ?? null, false) ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (!empty($item['serial_number'])): ?>
                                                                        <code><?= htmlspecialchars($item['serial_number']) ?></code>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    // Determine actual status based on remaining quantity
                                                                    $actualStatus = $remaining > 0 ? 'Borrowed' : 'Returned';
                                                                    ?>
                                                                    <?= ViewHelper::renderStatusBadge($actualStatus) ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (!empty($item['line_notes'])): ?>
                                                                        <small class="text-muted"><?= htmlspecialchars($item['line_notes']) ?></small>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <!-- Hidden Single Item Data Row (for modal) -->
                                <tr class="batch-items-row" data-batch-id="<?= !empty($tool['batch_id']) ? $tool['batch_id'] : $tool['id'] ?>" style="display: none;">
                                    <td colspan="100%" class="p-0">
                                        <div class="batch-items-container" style="display: none;">
                                            <table class="batch-items-table">
                                                <tbody>
                                                    <tr data-item-id="<?= $tool['id'] ?>" data-asset-id="<?= $tool['asset_id'] ?>">
                                                        <td>1</td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($tool['asset_name']) ?></strong>
                                                            <?php if (!empty($tool['category_name'])): ?>
                                                                <small class="text-muted"><?= htmlspecialchars($tool['category_name']) ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($tool['asset_ref']) ?></td>
                                                        <td><?= $tool['quantity'] ?? 1 ?></td>
                                                        <td><?= $tool['quantity_returned'] ?? 0 ?></td>
                                                        <td><?= ($tool['quantity'] ?? 1) - ($tool['quantity_returned'] ?? 0) ?></td>
                                                        <td><?= !empty($tool['condition_returned']) ? htmlspecialchars($tool['condition_returned']) : '-' ?></td>
                                                        <td><?= !empty($tool['serial_number']) ? htmlspecialchars($tool['serial_number']) : '-' ?></td>
                                                        <td><span class="badge"><?= htmlspecialchars($tool['status']) ?></span></td>
                                                        <td><?= !empty($tool['line_notes']) ? htmlspecialchars($tool['line_notes']) : '-' ?></td>
                                                        <td style="display:none;"><?= $tool['asset_id'] ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Borrowed tools pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=borrowed-tools&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <?php if ($i == $pagination['current_page']): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?= $i ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?route=borrowed-tools&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=borrowed-tools&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- List utilities are loaded via external module list-utils.js -->
