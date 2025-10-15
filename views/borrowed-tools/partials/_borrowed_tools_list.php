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
            <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel me-1"></i>
                <span class="d-none d-md-inline">Export</span>
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
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

                    // Status configuration
                    $statusConfig = [
                        'Pending Verification' => ['class' => 'bg-primary', 'icon' => 'clock'],
                        'Pending Approval' => ['class' => 'bg-warning text-dark', 'icon' => 'hourglass-split'],
                        'Approved' => ['class' => 'bg-info', 'icon' => 'check-circle'],
                        'Borrowed' => ['class' => 'bg-secondary', 'icon' => 'box-arrow-up'],
                        'Returned' => ['class' => 'bg-success', 'icon' => 'check-square'],
                        'Overdue' => ['class' => 'bg-danger', 'icon' => 'exclamation-triangle'],
                        'Canceled' => ['class' => 'bg-dark', 'icon' => 'x-circle']
                    ];
                    $config = $statusConfig[$tool['status']] ?? ['class' => 'bg-secondary', 'icon' => 'question'];
                    ?>
                    <div class="card mb-3 <?= $isOverdue ? 'border-danger' : ($isDueSoon ? 'border-warning' : '') ?>">
                        <div class="card-body">
                            <!-- Header with ID and Status -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <?php if ($isBatch): ?>
                                        <span class="badge bg-primary me-1"><?= $batchCount ?> items</span>
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
                                <span class="badge <?= $config['class'] ?>">
                                    <i class="bi bi-<?= $config['icon'] ?> me-1"></i>
                                    <?= htmlspecialchars($tool['status']) ?>
                                </span>
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
                                    elseif ($tool['status'] === 'Borrowed' && $auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])):
                                        $primaryAction = [
                                            'modal' => true,
                                            'modal_id' => 'batchReturnModal',
                                            'batch_id' => $batchId,
                                            'class' => $isOverdue ? 'btn-danger' : 'btn-success',
                                            'icon' => 'box-arrow-down',
                                            'text' => $isOverdue ? 'Return Overdue' : 'Return Batch'
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
                                                <span class="badge <?= $statusConfig[$item['status']]['class'] ?? 'bg-secondary' ?>">
                                                    <?= $item['status'] ?>
                                                </span>
                                            </div>
                                            <div class="fw-medium"><?= htmlspecialchars($item['asset_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($item['asset_ref']) ?></small>
                                            <?php if (!empty($item['serial_number'])): ?>
                                                <small class="text-muted">S/N: <code><?= htmlspecialchars($item['serial_number']) ?></code></small>
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
                            <th>ID</th>
                            <th>Item Details</th>
                            <th>Borrower</th>
                            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                                <th>Purpose</th>
                            <?php endif; ?>
                            <th>Return Date</th>
                            <th>Status</th>
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
                                        <?php if ($isBatch): ?>
                                            <span class="badge bg-primary me-2" title="Batch with <?= $batchCount ?> items">
                                                <?= $batchCount ?> items
                                            </span>
                                            <span class="fw-medium">
                                                <?php if (!empty($tool['batch_reference'])): ?>
                                                    <?= htmlspecialchars($tool['batch_reference']) ?>
                                                <?php else: ?>
                                                    Batch #<?= $batchId ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="?route=borrowed-tools/view&id=<?= $tool['id'] ?>" class="text-decoration-none fw-medium">
                                                #<?= $tool['id'] ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Enhanced Asset Details -->
                                <td>
                                    <?php if ($isBatch): ?>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-medium"><?= $batchCount ?> Equipment Items</div>
                                                <small class="text-muted">
                                                    Multiple categories
                                                </small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($tool['asset_image'])): ?>
                                                <div class="me-2">
                                                    <img src="<?= htmlspecialchars($tool['asset_image']) ?>"
                                                         class="rounded" width="40" height="40" alt="Asset">
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($tool['asset_name']) ?></div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($tool['asset_ref']) ?>
                                                    <?php if (!empty($tool['asset_category'])): ?>
                                                        | <?= htmlspecialchars($tool['asset_category']) ?>
                                                    <?php endif; ?>
                                                </small>
                                                <?php if ($auth->hasRole(['System Admin', 'Asset Director']) && !empty($tool['asset_value'])): ?>
                                                    <br><small class="text-info">
                                                        Value: ₱<?= number_format($tool['asset_value'], 2) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
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

                                <!-- Enhanced Return Schedule -->
                                <td>
                                    <?php if ($tool['status'] === 'Returned' && !empty($tool['actual_return'])): ?>
                                        <!-- Show actual return date for returned items -->
                                        <div class="return-schedule">
                                            <div class="fw-medium text-success">
                                                <?= date('M j, Y', strtotime($tool['actual_return'])) ?>
                                            </div>
                                            <small class="text-muted">Returned on <?= date('l', strtotime($tool['actual_return'])) ?></small>

                                            <?php
                                            $expectedTime = strtotime($expectedReturn);
                                            $actualTime = strtotime($tool['actual_return']);
                                            $daysDiff = floor(($actualTime - $expectedTime) / 86400);
                                            ?>

                                            <?php if ($daysDiff < 0): ?>
                                                <br><span class="badge bg-success">
                                                    <?= abs($daysDiff) ?> days early
                                                </span>
                                            <?php elseif ($daysDiff > 0): ?>
                                                <br><span class="badge bg-warning text-dark">
                                                    <?= $daysDiff ?> days late
                                                </span>
                                            <?php else: ?>
                                                <br><span class="badge bg-info">
                                                    On time
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <!-- Show expected return date for items not yet returned -->
                                        <div class="return-schedule">
                                            <div class="fw-medium <?= $isOverdue ? 'text-danger' : ($isDueSoon ? 'text-warning' : 'text-dark') ?>">
                                                <?= date('M j, Y', strtotime($expectedReturn)) ?>
                                            </div>
                                            <small class="text-muted"><?= date('l', strtotime($expectedReturn)) ?></small>

                                            <?php if ($isOverdue): ?>
                                                <br><span class="badge bg-danger">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    <?= abs(floor((time() - strtotime($expectedReturn)) / 86400)) ?> days overdue
                                                </span>
                                            <?php elseif ($isDueSoon): ?>
                                                <br><span class="badge bg-warning text-dark">
                                                    <i class="bi bi-clock me-1"></i>
                                                    Due in <?= ceil((strtotime($expectedReturn) - time()) / 86400) ?> days
                                                </span>
                                            <?php elseif ($tool['status'] === 'Borrowed'): ?>
                                                <br><small class="text-success">
                                                    <?= ceil((strtotime($expectedReturn) - time()) / 86400) ?> days remaining
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Enhanced Status with Progress -->
                                <td>
                                    <?php
                                    $statusConfig = [
                                        'Pending Verification' => ['class' => 'bg-primary', 'icon' => 'clock', 'progress' => 25],
                                        'Pending Approval' => ['class' => 'bg-warning text-dark', 'icon' => 'hourglass-split', 'progress' => 50],
                                        'Approved' => ['class' => 'bg-info', 'icon' => 'check-circle', 'progress' => 75],
                                        'Borrowed' => ['class' => 'bg-secondary', 'icon' => 'box-arrow-up', 'progress' => 90],
                                        'Returned' => ['class' => 'bg-success', 'icon' => 'check-square', 'progress' => 100],
                                        'Overdue' => ['class' => 'bg-danger', 'icon' => 'exclamation-triangle', 'progress' => 90],
                                        'Canceled' => ['class' => 'bg-dark', 'icon' => 'x-circle', 'progress' => 0]
                                    ];
                                    $config = $statusConfig[$tool['status']] ?? ['class' => 'bg-secondary', 'icon' => 'question', 'progress' => 0];
                                    ?>
                                    <div class="status-cell">
                                        <span class="badge <?= $config['class'] ?> mb-1">
                                            <i class="bi bi-<?= $config['icon'] ?> me-1"></i>
                                            <?= htmlspecialchars($tool['status']) ?>
                                        </span>

                                        <!-- Progress Bar -->
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar <?= str_replace(['bg-', ' text-dark'], ['bg-', ''], $config['class']) ?>"
                                                 style="width: <?= $config['progress'] ?>%"></div>
                                        </div>

                                        <!-- Time Indicators -->
                                        <?php if (!empty($tool['borrowed_date']) && $tool['status'] === 'Borrowed'): ?>
                                            <small class="text-muted">
                                                Out for <?= floor((time() - strtotime($tool['borrowed_date'])) / 86400) ?> days
                                            </small>
                                        <?php endif; ?>
                                    </div>
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
                                                    <span class="badge badge-sm bg-success me-1">A</span>
                                                    <span class="text-truncate" style="max-width: 80px;">
                                                        <?= htmlspecialchars($tool['approved_by_name']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>

                                <!-- Request Info (for tracking) -->
                                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager', 'Site Inventory Clerk'])): ?>
                                    <td>
                                        <div class="request-info small">
                                            <?php if (!empty($tool['issued_by_name'])): ?>
                                                <div class="fw-medium text-primary">
                                                    <i class="bi bi-person-circle me-1"></i>
                                                    <?= htmlspecialchars($tool['issued_by_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="text-muted"><?= date('M j, Y', strtotime($tool['created_at'])) ?></div>
                                            <small class="text-muted"><?= date('g:i A', strtotime($tool['created_at'])) ?></small>

                                            <?php if (!empty($tool['creator_role'])): ?>
                                                <br><span class="badge bg-light text-dark">
                                                    <?= htmlspecialchars($tool['creator_role']) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($tool['urgency_level'])): ?>
                                                <br><span class="badge <?= $tool['urgency_level'] === 'high' ? 'bg-danger' : ($tool['urgency_level'] === 'medium' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                    <?= ucfirst($tool['urgency_level']) ?> Priority
                                                </span>
                                            <?php endif; ?>
                                        </div>
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
                                            elseif ($tool['status'] === 'Borrowed' && $auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])):
                                                $primaryAction = [
                                                    'modal' => true,
                                                    'modal_id' => 'batchReturnModal',
                                                    'batch_id' => $batchId,
                                                    'class' => $isOverdue ? 'btn-danger' : 'btn-success',
                                                    'icon' => 'box-arrow-down',
                                                    'text' => $isOverdue ? 'Return Overdue' : 'Return Batch',
                                                    'title' => 'Return all items in this batch'
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
                                                $primaryAction = [
                                                    'url' => "?route=borrowed-tools/return&id={$tool['id']}",
                                                    'class' => $isOverdue ? 'btn-danger' : 'btn-success',
                                                    'icon' => 'box-arrow-down',
                                                    'text' => $isOverdue ? 'Return Overdue' : 'Return Tool',
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
                                            if ($tool['status'] === 'Borrowed' && $auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])):
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

                                        <div class="btn-group btn-group-sm" role="group">
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
                                                        <i class="bi bi-<?= $primaryAction['icon'] ?> me-1"></i><?= $primaryAction['text'] ?>
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Single item action - regular link -->
                                                    <a href="<?= $primaryAction['url'] ?>"
                                                       class="btn btn-sm <?= $primaryAction['class'] ?>"
                                                       title="<?= $primaryAction['title'] ?>">
                                                        <i class="bi bi-<?= $primaryAction['icon'] ?> me-1"></i><?= $primaryAction['text'] ?>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- View Details (Always Available) -->
                                            <?php if ($viewAction): ?>
                                                <a href="<?= $viewAction['url'] ?>"
                                                   class="btn btn-sm <?= $viewAction['class'] ?>"
                                                   title="<?= $viewAction['title'] ?>">
                                                    <i class="bi bi-<?= $viewAction['icon'] ?>"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Secondary Actions Dropdown -->
                                            <?php if (!empty($secondaryActions)): ?>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <span class="visually-hidden">Toggle Dropdown</span>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php foreach ($secondaryActions as $action): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="<?= $action['url'] ?>" title="<?= $action['title'] ?>">
                                                                    <i class="bi bi-<?= $action['icon'] ?> me-2"></i><?= $action['title'] ?>
                                                                </a>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Quick Status Indicators for Actions -->
                                        <?php if ($isOverdue && $auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                                            <div class="mt-1">
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                        onclick="sendOverdueReminder(<?= $tool['id'] ?>)"
                                                        title="Send overdue reminder">
                                                    <i class="bi bi-bell"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
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
                                                            <th style="width: 40%">Equipment</th>
                                                            <th style="width: 15%">Reference</th>
                                                            <th style="width: 10%">Qty Out</th>
                                                            <th style="width: 15%">Serial Number</th>
                                                            <th style="width: 15%">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($batchItems as $index => $item): ?>
                                                            <tr data-item-id="<?= $item['id'] ?>">
                                                                <td><?= $index + 1 ?></td>
                                                                <td>
                                                                    <strong><?= htmlspecialchars($item['asset_name']) ?></strong>
                                                                    <?php if (!empty($item['asset_category'])): ?>
                                                                        <br><small class="text-muted"><?= htmlspecialchars($item['asset_category']) ?></small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= htmlspecialchars($item['asset_ref']) ?></td>
                                                                <td class="text-center"><?= $item['quantity'] ?></td>
                                                                <td>
                                                                    <?php if (!empty($item['serial_number'])): ?>
                                                                        <code><?= htmlspecialchars($item['serial_number']) ?></code>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $statusConfig = [
                                                                        'Pending Verification' => ['class' => 'bg-primary', 'icon' => 'clock'],
                                                                        'Pending Approval' => ['class' => 'bg-warning text-dark', 'icon' => 'hourglass-split'],
                                                                        'Approved' => ['class' => 'bg-info', 'icon' => 'check-circle'],
                                                                        'Borrowed' => ['class' => 'bg-secondary', 'icon' => 'box-arrow-up'],
                                                                        'Returned' => ['class' => 'bg-success', 'icon' => 'check-square'],
                                                                        'Canceled' => ['class' => 'bg-dark', 'icon' => 'x-circle']
                                                                    ];
                                                                    $config = $statusConfig[$item['status']] ?? ['class' => 'bg-secondary', 'icon' => 'question'];
                                                                    ?>
                                                                    <span class="badge <?= $config['class'] ?>">
                                                                        <i class="bi bi-<?= $config['icon'] ?> me-1"></i><?= $item['status'] ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
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

<script>
// Toggle batch items in mobile view
document.querySelectorAll('.batch-toggle-mobile').forEach(button => {
    button.addEventListener('click', function() {
        const batchId = this.getAttribute('data-batch-id');
        const batchItems = document.querySelector(`.batch-items-mobile[data-batch-id="${batchId}"]`);
        const icon = this.querySelector('i');

        if (batchItems.style.display === 'none') {
            batchItems.style.display = 'block';
            icon.classList.remove('bi-chevron-down');
            icon.classList.add('bi-chevron-up');
            this.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Hide Items';
        } else {
            batchItems.style.display = 'none';
            icon.classList.remove('bi-chevron-up');
            icon.classList.add('bi-chevron-down');
            this.innerHTML = '<i class="bi bi-chevron-down me-1"></i>View Items';
        }
    });
});

// Toggle batch items in desktop table
document.querySelectorAll('.batch-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const batchId = this.getAttribute('data-batch-id');
        const batchItemsRow = document.querySelector(`.batch-items-row[data-batch-id="${batchId}"]`);
        const icon = this.querySelector('i');

        if (batchItemsRow.style.display === 'none') {
            batchItemsRow.style.display = '';
            icon.classList.remove('bi-chevron-right');
            icon.classList.add('bi-chevron-down');
        } else {
            batchItemsRow.style.display = 'none';
            icon.classList.remove('bi-chevron-down');
            icon.classList.add('bi-chevron-right');
        }
    });
});
</script>
