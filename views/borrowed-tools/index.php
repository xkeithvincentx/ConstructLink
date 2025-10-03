<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-2">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk'])): ?>
            <a href="?route=borrowed-tools/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Borrow Tool</span>
                <span class="d-sm-none">Borrow</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Secondary Actions (Right) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Secondary actions">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshBorrowedTools()">
            <i class="bi bi-arrow-clockwise"></i>
            <span class="d-none d-sm-inline ms-1">Refresh</span>
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- Pending Verification -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-clock text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Pending Verification</h6>
                        <h3 class="mb-0"><?= $borrowedToolStats['pending_verification'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person me-1"></i>Project Manager review
                </p>
            </div>
        </div>
    </div>

    <!-- Pending Approval -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-hourglass-split text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Pending Approval</h6>
                        <h3 class="mb-0"><?= $borrowedToolStats['pending_approval'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-shield-check me-1"></i>Director approval needed
                </p>
            </div>
        </div>
    </div>

    <!-- Ready to Issue -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Ready to Issue</h6>
                        <h3 class="mb-0"><?= $borrowedToolStats['approved'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-box-arrow-right me-1"></i>Approved for borrowing
                </p>
            </div>
        </div>
    </div>

    <!-- Currently Out -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-tools text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Currently Out</h6>
                        <h3 class="mb-0"><?= $borrowedToolStats['borrowed'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person-badge me-1"></i>Active borrows
                </p>
            </div>
        </div>
    </div>

    <!-- Overdue Tools -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--danger-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-exclamation-triangle text-danger fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Overdue</h6>
                        <h3 class="mb-0"><?= $borrowedToolStats['overdue'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clock me-1"></i>Requires immediate action
                </p>
            </div>
        </div>
    </div>

    <!-- Returned -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-arrow-return-left text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Returned</h6>
                        <h3 class="mb-0"><?= $borrowedToolStats['returned'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-calendar me-1"></i>This month
                </p>
            </div>
        </div>
    </div>

    <!-- Canceled -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-x-circle text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Canceled</h6>
                        <h3 class="mb-0"><?= $borrowedToolStats['canceled'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-slash-circle me-1"></i>Withdrawn requests
                </p>
            </div>
        </div>
    </div>

    <!-- Total Borrowings -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-list-ul text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Borrowings</h6>
                        <h3 class="mb-0"><?= $borrowedToolStats['total_borrowings'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-graph-up me-1"></i>All time
                </p>
            </div>
        </div>
    </div>
</div>

<!-- MVA Workflow Info Banner -->
<div class="alert alert-info mb-4">
    <strong><i class="bi bi-info-circle me-2"></i>MVA Workflow:</strong>
    <span class="badge bg-primary">Maker</span> (Warehouseman) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-info">Authorizer</span> (Asset/Finance Director) →
    <span class="badge bg-success">Approved</span> →
    <span class="badge bg-primary">Borrowed</span> →
    <span class="badge bg-secondary">Returned</span>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=borrowed-tools" class="row g-3">
            <!-- Status Filter - Role-based Options -->
            <div class="col-lg-2 col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Asset Director'])): ?>
                        <option value="Pending Verification" <?= ($_GET['status'] ?? '') === 'Pending Verification' ? 'selected' : '' ?>>
                            📋 Pending Verification
                        </option>
                    <?php endif; ?>
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                        <option value="Pending Approval" <?= ($_GET['status'] ?? '') === 'Pending Approval' ? 'selected' : '' ?>>
                            ⏳ Pending Approval
                        </option>
                    <?php endif; ?>
                    <?php if ($auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])): ?>
                        <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>
                            ✅ Ready to Issue
                        </option>
                    <?php endif; ?>
                    <option value="Borrowed" <?= ($_GET['status'] ?? '') === 'Borrowed' ? 'selected' : '' ?>>
                        🔧 Currently Out
                    </option>
                    <option value="Returned" <?= ($_GET['status'] ?? '') === 'Returned' ? 'selected' : '' ?>>
                        ↩️ Returned
                    </option>
                    <option value="Overdue" <?= ($_GET['status'] ?? '') === 'Overdue' ? 'selected' : '' ?>>
                        ⚠️ Overdue
                    </option>
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                        <option value="Canceled" <?= ($_GET['status'] ?? '') === 'Canceled' ? 'selected' : '' ?>>
                            ❌ Canceled
                        </option>
                    <?php endif; ?>
                </select>
            </div>
            
            <!-- Priority Filter - For Management Roles -->
            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                <div class="col-lg-2 col-md-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">All Priorities</option>
                        <option value="overdue" <?= ($_GET['priority'] ?? '') === 'overdue' ? 'selected' : '' ?>>🚨 Overdue Items</option>
                        <option value="due_soon" <?= ($_GET['priority'] ?? '') === 'due_soon' ? 'selected' : '' ?>>⚡ Due Soon (3 days)</option>
                        <option value="pending_action" <?= ($_GET['priority'] ?? '') === 'pending_action' ? 'selected' : '' ?>>🔄 Needs My Action</option>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Project Filter - For Project Managers and Site Staff -->
            <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk']) && !empty($projects)): ?>
                <div class="col-lg-2 col-md-3">
                    <label for="project" class="form-label">Project</label>
                    <select class="form-select" id="project" name="project">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= ($_GET['project'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['code']) ?> - <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Date Range Filters -->
            <div class="col-lg-2 col-md-3">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-3">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            
            <!-- Search Field -->
            <div class="col-lg-2 col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Asset, borrower, purpose..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            
            <!-- Action Buttons -->
            <div class="col-12 d-flex align-items-end gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=borrowed-tools" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                
                <!-- Quick Action Buttons -->
                <?php if ($auth->hasRole(['System Admin', 'Project Manager'])): ?>
                    <button type="button" class="btn btn-outline-warning" onclick="filterByStatus('Pending Verification')">
                        <i class="bi bi-clock me-1"></i>My Verifications
                    </button>
                <?php endif; ?>
                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                    <button type="button" class="btn btn-outline-info" onclick="filterByStatus('Pending Approval')">
                        <i class="bi bi-shield-check me-1"></i>My Approvals
                    </button>
                <?php endif; ?>
                <?php if ($auth->hasRole(['System Admin', 'Warehouseman'])): ?>
                    <button type="button" class="btn btn-outline-success" onclick="filterByStatus('Approved')">
                        <i class="bi bi-box-arrow-up me-1"></i>Ready to Issue
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-danger" onclick="filterByStatus('Overdue')">
                    <i class="bi bi-exclamation-triangle me-1"></i>Overdue
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Borrowed Tools Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Borrowed Tools</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
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
                    <a href="?route=borrowed-tools/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Borrow First Tool
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="borrowedToolsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset Details</th>
                            <th>Borrower Info</th>
                            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                                <th>Purpose</th>
                            <?php endif; ?>
                            <th>Return Schedule</th>
                            <th>Status & Progress</th>
                            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                                <th>MVA Workflow</th>
                            <?php endif; ?>
                            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                                <th>Request Info</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowedTools as $tool): ?>
                            <?php
                            $expectedReturn = $tool['expected_return'];
                            $isOverdue = $tool['status'] === 'Borrowed' && strtotime($expectedReturn) < time();
                            $isDueSoon = !$isOverdue && $tool['status'] === 'Borrowed' && strtotime($expectedReturn) <= strtotime('+3 days');
                            $rowClass = $isOverdue ? 'table-danger' : ($isDueSoon ? 'table-warning' : '');
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <!-- ID with Visual Priority Indicators -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($isOverdue): ?>
                                            <i class="bi bi-exclamation-triangle-fill text-danger me-1" title="Overdue"></i>
                                        <?php elseif ($isDueSoon): ?>
                                            <i class="bi bi-clock-fill text-warning me-1" title="Due Soon"></i>
                                        <?php endif; ?>
                                        <a href="?route=borrowed-tools/view&id=<?= $tool['id'] ?>" class="text-decoration-none fw-medium">
                                            #<?= $tool['id'] ?>
                                        </a>
                                    </div>
                                </td>
                                
                                <!-- Enhanced Asset Details -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?php if (!empty($tool['asset_image'])): ?>
                                                <img src="<?= htmlspecialchars($tool['asset_image']) ?>" 
                                                     class="rounded" width="40" height="40" alt="Asset">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="bi bi-tools text-primary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
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
                                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                                    <td>
                                        <div class="request-info small">
                                            <div class="fw-medium"><?= date('M j, Y', strtotime($tool['created_at'])) ?></div>
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
                                        
                                        // Determine primary action based on role and status
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
                                                <a href="<?= $primaryAction['url'] ?>" 
                                                   class="btn <?= $primaryAction['class'] ?>" 
                                                   title="<?= $primaryAction['title'] ?>">
                                                    <i class="bi bi-<?= $primaryAction['icon'] ?> me-1"></i><?= $primaryAction['text'] ?>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- View Details (Always Available) -->
                                            <a href="<?= $viewAction['url'] ?>" 
                                               class="btn <?= $viewAction['class'] ?>" 
                                               title="<?= $viewAction['title'] ?>">
                                                <i class="bi bi-<?= $viewAction['icon'] ?>"></i>
                                            </a>
                                            
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
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=borrowed-tools&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
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

<!-- Overdue Tools Alert -->
<?php if (!empty($overdueTools)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Overdue Tools Alert
                </h6>
            </div>
            <div class="card-body">
                <p class="text-danger mb-3">
                    <strong><?= count($overdueTools) ?> tool(s)</strong> are overdue for return. Please follow up with borrowers.
                </p>
                <div class="row">
                    <?php foreach (array_slice($overdueTools, 0, 6) as $overdueTool): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <div>
                                    <strong><?= htmlspecialchars($overdueTool['asset_name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        Borrowed by: <?= htmlspecialchars($overdueTool['borrower_name']) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger">
                                        <?= $overdueTool['days_overdue'] ?> days overdue
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($overdueTools) > 6): ?>
                    <p class="text-muted mt-2">And <?= count($overdueTools) - 6 ?> more overdue tools...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Enhanced Borrowed Tools Index Styles */
.workflow-step {
    text-align: center;
    min-width: 100px;
}

.workflow-steps .badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.status-cell .progress {
    width: 60px;
    margin-top: 2px;
}

.purpose-cell {
    max-width: 200px;
}

.mva-workflow .badge-sm {
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
    width: 20px;
    text-align: center;
}

.return-schedule {
    min-width: 120px;
}

.action-buttons .btn-group {
    white-space: nowrap;
}

.table-danger {
    --bs-table-bg: rgba(220, 53, 69, 0.1);
    border-left: 4px solid #dc3545;
}

.table-warning {
    --bs-table-bg: rgba(255, 193, 7, 0.1);
    border-left: 4px solid #ffc107;
}

.text-truncated-hover:hover {
    overflow: visible;
    white-space: normal;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.25rem;
    border-radius: 0.25rem;
    position: relative;
    z-index: 1000;
}

.card-body .workflow-steps {
    justify-content: center;
}

@media (max-width: 768px) {
    .workflow-steps {
        flex-direction: column;
        gap: 1rem !important;
    }
    
    .workflow-steps .bi-arrow-right {
        transform: rotate(90deg);
    }
    
    .col-lg-2 {
        margin-bottom: 1rem;
    }
}

/* Priority indicators */
.priority-high {
    border-left: 4px solid #dc3545;
}

.priority-medium {
    border-left: 4px solid #ffc107;
}

.priority-low {
    border-left: 4px solid #198754;
}

/* Enhanced badge styles */
.badge.position-relative .badge {
    font-size: 0.55rem;
}

/* Smooth transitions */
.card, .btn, .badge {
    transition: all 0.2s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Print styles */
@media print {
    .btn, .dropdown, .alert, .card-header {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
}
</style>

<script>
// Mark tool as overdue
function markOverdue(borrowId) {
    if (confirm('Mark this tool as overdue? This will update the status and may trigger notifications.')) {
        fetch('?route=borrowed-tools/markOverdue', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ borrow_id: borrowId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to mark as overdue: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking tool as overdue');
        });
    }
}

// Refresh borrowed tools
function refreshBorrowedTools() {
    window.location.reload();
}

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=borrowed-tools/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=borrowed-tools"]');
    const filterInputs = filterForm.querySelectorAll('select, input[name="date_from"], input[name="date_to"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Search with debounce
    let searchTimeout;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }
});

// Quick filter functions
function filterByStatus(status) {
    document.getElementById('status').value = status;
    document.querySelector('form[action="?route=borrowed-tools"]').submit();
}

function filterByPriority(priority) {
    const prioritySelect = document.getElementById('priority');
    if (prioritySelect) {
        prioritySelect.value = priority;
        document.querySelector('form[action="?route=borrowed-tools"]').submit();
    }
}

// Send overdue reminder function
function sendOverdueReminder(borrowId) {
    if (confirm('Send overdue reminder to borrower?')) {
        fetch('?route=borrowed-tools/sendReminder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ borrow_id: borrowId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder sent successfully!');
            } else {
                alert('Failed to send reminder: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending reminder');
        });
    }
}

// Enhanced table interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add tooltips to truncated text
    const truncatedElements = document.querySelectorAll('.text-truncate');
    truncatedElements.forEach(element => {
        if (element.scrollWidth > element.clientWidth) {
            element.classList.add('text-truncated-hover');
        }
    });
    
    // Highlight overdue rows
    const overdueRows = document.querySelectorAll('.table-danger');
    overdueRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 0 10px rgba(220, 53, 69, 0.3)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'none';
        });
    });
    
    // Auto-focus on search when using Ctrl+F
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });
});

// Auto-refresh for overdue tools with visual indicator
if (document.querySelector('.table-danger') || document.querySelector('.bg-danger')) {
    let refreshTimer = 300; // 5 minutes for overdue items
    
    // Show refresh countdown
    const createRefreshIndicator = () => {
        const indicator = document.createElement('div');
        indicator.id = 'refresh-indicator';
        indicator.className = 'position-fixed bottom-0 end-0 m-3 alert alert-info alert-dismissible';
        indicator.innerHTML = `
            <small>
                <i class="bi bi-arrow-clockwise me-1"></i>
                Auto-refresh in <span id="refresh-countdown">${refreshTimer}</span>s
                <button type="button" class="btn-close btn-close-sm" onclick="clearAutoRefresh()"></button>
            </small>
        `;
        document.body.appendChild(indicator);
        
        const countdown = setInterval(() => {
            refreshTimer--;
            const countdownEl = document.getElementById('refresh-countdown');
            if (countdownEl) countdownEl.textContent = refreshTimer;
            
            if (refreshTimer <= 0) {
                clearInterval(countdown);
                location.reload();
            }
        }, 1000);
        
        window.autoRefreshInterval = countdown;
    };
    
    // Start auto-refresh after 30 seconds
    setTimeout(createRefreshIndicator, 30000);
}

function clearAutoRefresh() {
    if (window.autoRefreshInterval) {
        clearInterval(window.autoRefreshInterval);
        const indicator = document.getElementById('refresh-indicator');
        if (indicator) indicator.remove();
    }
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Borrowed Tools - ConstructLink™';
$pageHeader = 'Borrowed Tools Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
